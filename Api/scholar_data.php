<?php
/**
 * Api/scholar_data.php
 *
 * CSV -> MAIN_SCHOLAR_DATA importer
 * - Works in CLI mode: php scholar_data.php /path/to/scholarships.csv [--clg=CLGID]
 * - Works in HTTP mode: POST/GET to this script (uses data.csv in same folder) and optional ?clg=CLGID
 *
 * NOTE: helper functions are defined once (available in both modes).
 */

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

// ---------------------- Helper functions (available to both CLI and HTTP) ----------------------

/** Normalize and extract integer from number-like string (commas allowed) */
function clean_number($s) {
    if ($s === null) return null;
    $s = trim((string)$s);
    if ($s === '' || preg_match('/^[-\s]*$/', $s)) return null;
    // remove any non-digit and non-dot characters
    $s2 = preg_replace('/[^\d\.]/u', '', $s);
    if ($s2 === '') return null;
    return (int) round(floatval($s2));
}

/** Parse income limit text into min/max numeric INR values */
function parse_income_limit($txt) {
    $txt = trim((string)$txt);
    $min = null; $max = null;
    if ($txt === '') return [$min, $max];

    // "10000 to 75000" or "10000 - 75000" etc
    if (preg_match('/(\d[\d,\.]*)\s*(?:to|-|–)\s*(\d[\d,\.]*)/u', $txt, $m)) {
        $min = clean_number($m[1]);
        $max = clean_number($m[2]);
        return [$min, $max];
    }

    // "upto 300000", "under 250000"
    if (preg_match('/(?:upto|under|below)\s*([\d,\.]+)/i', $txt, $m)) {
        $max = clean_number($m[1]);
        return [$min, $max];
    }

    // single number in text
    if (preg_match('/(\d[\d,\.]{2,})/u', $txt, $m)) {
        $max = clean_number($m[1]);
        return [$min, $max];
    }

    return [$min, $max];
}

/** detect categories from name+criteria heuristically */
function detect_categories($name, $criteria) {
    $c = [];
    $text = mb_strtolower(trim($name . ' ' . $criteria), 'UTF-8');
    $map = [
        'obc'=>'OBC',' sc'=>'SC','st'=>'ST','minority'=>'Minority',
        'girl'=>'Girls','ladli'=>'Girls','jain'=>'Jain','handicap'=>'Differently Abled',
        'orphan'=>'Orphan','aicte'=>'AICTE','beedi'=>'Beedi Sharmik','bpl'=>'BPL'
    ];
    foreach ($map as $k=>$v) {
        if (mb_stripos($text, trim($k), 0, 'UTF-8') !== false) $c[$v] = true;
    }
    return array_values(array_keys($c));
}

/** detect documents required from criteria heuristically */
function detect_documents($criteria) {
    $docs = [];
    $text = mb_strtolower((string)$criteria, 'UTF-8');
    $candidates = [
        'caste certificate','domicile certificate','domicile proof','12th mark sheet','bpl card',
        'bank detail','bank details','npci','upload npcI','npcI','kyc','kyc required','id proof',
        'income proof','jee main','sgpa','disability certificate','community certificate','beedi shramik card'
    ];
    foreach ($candidates as $cand) {
        if (mb_stripos($text, $cand, 0, 'UTF-8') !== false) $docs[] = ucwords($cand);
    }
    return array_values(array_unique($docs));
}

/** extract obvious urls/emails from a site cell */
function extract_urls($s) {
    $urls = [];
    if (!$s) return $urls;
    $parts = preg_split('/[,\|\n\r;]+|\s+/u', (string)$s, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($parts as $p) {
        $p = trim($p, " \t\n\r\"'");
        if ($p === '') continue;
        if (filter_var($p, FILTER_VALIDATE_EMAIL)) { $urls[] = $p; continue; }
        // add http if starts with www.
        if (preg_match('/^(https?:\/\/|www\.)/i', $p) || preg_match('/\./', $p)) {
            if (strpos($p, 'www.') === 0) $p = 'https://' . $p;
            $urls[] = $p;
        }
    }
    return array_values(array_unique($urls));
}

/** split course-year string into array tokens */
function split_course_years($course_year) {
    $c = trim((string)$course_year);
    if ($c === '' || strtoupper($c) === 'ALL') return ['ALL'];
    $parts = preg_split('/[\/,;]+| and | & |–| - /iu', $c, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($parts as $p) { $p = trim($p); if ($p !== '') $out[] = $p; }
    return $out ?: [$c];
}

// ---------------------- Main logic (CLI or HTTP) ----------------------

/** Core import routine used in both CLI and HTTP modes */
function run_import(string $csvFile, ?string $clgId = null) : array {
    if (!is_readable($csvFile)) {
        return ['status'=>'error','message'=>"CSV not readable: {$csvFile}"];
    }

    $pdo = Connector\db_connect();

    $fh = fopen($csvFile, 'r');
    if ($fh === false) return ['status'=>'error','message'=>'Failed to open CSV'];

    // read header
    $header = fgetcsv($fh);
    if ($header === false) { fclose($fh); return ['status'=>'error','message'=>'CSV empty']; }
    $map = array_map('trim', $header);

    // prepare insert SQL (ensure table exists)
    $insertSql = "INSERT INTO MAIN_SCHOLAR_DATA (
      ID, SOURCE_ROW, CLG_ID, NAME, INCOME_LIMIT_TEXT, INCOME_MIN, INCOME_MAX, CRITERIA,
      DOCUMENTS_REQUIRED, COURSE_YEAR, COURSES, SCHOLARSHIP_AMOUNT_TEXT, SCHOLARSHIP_AMOUNT_MIN, SCHOLARSHIP_AMOUNT_MAX,
      PAYMENT_REMARK, SITE_TEXT, SITE_URLS, ELIGIBILITY_CATEGORIES, KEYWORDS, NORMALIZED_TEXT, SOURCE, INSERTED_BY, IS_ACTIVE
    ) VALUES (
      :id, :source_row, :clg_id, :name, :income_limit_text, :income_min, :income_max, :criteria,
      :documents_required, :course_year, :courses, :sch_text, :sch_min, :sch_max,
      :payment_remark, :site_text, :site_urls, :elig_cats, :keywords, :normalized_text, :source, :inserted_by, :is_active
    )";
    $stmt = $pdo->prepare($insertSql);

    $inserted = 0;
    $errors = [];
    while (($row = fgetcsv($fh)) !== false) {
        // try to map row to header columns; if mismatch, skip
        if (count($row) !== count($map)) {
            // attempt to pad/truncate to match header length
            if (count($row) < count($map)) $row = array_pad($row, count($map), '');
            else $row = array_slice($row, 0, count($map));
        }
        $data = @array_combine($map, $row);
        if ($data === false) {
            $errors[] = ['row' => $row, 'error' => 'Header mapping failed'];
            continue;
        }

        $source_row = isset($data['S.N.']) ? intval($data['S.N.']) : (isset($data['S.N']) ? intval($data['S.N']) : null);
        $name = trim($data['Name of Scholarship'] ?? $data['Name'] ?? '');
        $income_txt = trim($data['Income Limit'] ?? '');
        $criteria = trim($data['Criteria'] ?? '');
        $course_year = trim($data['Course & Year'] ?? $data['Course'] ?? 'ALL');
        $sch_amt_text = trim($data['Scholarship Amount (₹)'] ?? $data['Scholarship Amount'] ?? '');
        $payment_remark = trim($data['Remark'] ?? '');
        $site_text = trim($data['Site'] ?? '');

        // parse
        list($inc_min, $inc_max) = parse_income_limit($income_txt);

        $sch_min = null; $sch_max = null;
        if (preg_match('/(\d[\d,\.]*)\s*(?:to|-|–)\s*(\d[\d,\.]*)/u', $sch_amt_text, $m)) {
            $sch_min = clean_number($m[1]);
            $sch_max = clean_number($m[2]);
        } elseif (preg_match('/\d/', $sch_amt_text)) {
            $sch_min = clean_number($sch_amt_text);
            $sch_max = $sch_min;
        }

        $elig_cats = detect_categories($name, $criteria);
        $docs = detect_documents($criteria);
        $urls = extract_urls($site_text);
        $courses = split_course_years($course_year);

        // keywords heuristic
        $kw = [];
        foreach ($elig_cats as $c) $kw[] = strtolower($c);
        foreach (preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name)) as $t) {
            if (mb_strlen($t) > 2) $kw[] = strtolower($t);
        }
        foreach ($courses as $c) if (mb_strlen($c) > 1) $kw[] = strtolower($c);
        $kw = array_values(array_unique($kw));

        $normalized_text = mb_strtolower(implode(' ', [$name, $income_txt, $criteria, $course_year, $sch_amt_text, $site_text]), 'UTF-8');

        $id = Connector\uuid_v4();
        $params = [
            ':id' => $id,
            ':source_row' => $source_row,
            ':clg_id' => $clgId,
            ':name' => $name,
            ':income_limit_text' => $income_txt,
            ':income_min' => $inc_min,
            ':income_max' => $inc_max,
            ':criteria' => $criteria,
            ':documents_required' => json_encode($docs, JSON_UNESCAPED_UNICODE),
            ':course_year' => $course_year,
            ':courses' => json_encode($courses, JSON_UNESCAPED_UNICODE),
            ':sch_text' => $sch_amt_text,
            ':sch_min' => $sch_min,
            ':sch_max' => $sch_max,
            ':payment_remark' => $payment_remark,
            ':site_text' => $site_text,
            ':site_urls' => json_encode($urls, JSON_UNESCAPED_UNICODE),
            ':elig_cats' => json_encode($elig_cats, JSON_UNESCAPED_UNICODE),
            ':keywords' => json_encode($kw, JSON_UNESCAPED_UNICODE),
            ':normalized_text' => $normalized_text,
            ':source' => 'csv_import',
            ':inserted_by' => (function_exists('get_current_user') ? get_current_user() : 'import_script'),
            ':is_active' => 1
        ];

        try {
            $stmt->execute($params);
            $inserted++;
        } catch (PDOException $e) {
            $errors[] = ['row_number' => $source_row, 'error' => $e->getMessage()];
            // continue on error
        }
    }

    fclose($fh);
    return ['status'=>'ok','inserted'=>$inserted,'errors'=>$errors];
}

// ---------------------- CLI mode ----------------------
if (php_sapi_name() === 'cli') {
    global $argv;
    if ($argc < 2) {
        echo "Usage: php " . basename(__FILE__) . " /path/to/scholarships.csv [--clg=CLGID]\n";
        exit(1);
    }
    $csvFile = $argv[1];
    $clgId = null;
    foreach ($argv as $a) {
        if (strpos($a, '--clg=') === 0) $clgId = substr($a, 6);
    }
    $res = run_import($csvFile, $clgId);
    if (isset($res['status']) && $res['status'] === 'ok') {
        echo "Inserted: " . intval($res['inserted']) . PHP_EOL;
        if (!empty($res['errors'])) {
            echo "Errors: " . count($res['errors']) . PHP_EOL;
            foreach ($res['errors'] as $err) {
                echo "- " . json_encode($err) . PHP_EOL;
            }
        }
    } else {
        echo "Import failed: " . json_encode($res) . PHP_EOL;
    }
    exit(0);
}

// ---------------------- HTTP mode ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // optional admin key check could be placed here
    $csvFile = __DIR__ . '/data.csv'; // default CSV path (change as needed)
    // allow override via query/post param 'csv' or 'file'
    if (!empty($_REQUEST['csv'])) $csvFile = $_REQUEST['csv'];
    if (!empty($_REQUEST['file'])) $csvFile = $_REQUEST['file'];

    $clgId = null;
    if (!empty($_REQUEST['clg'])) $clgId = trim((string)$_REQUEST['clg']);

    $result = run_import($csvFile, $clgId);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// otherwise nothing to do
http_response_code(405);
echo "Method not allowed";
exit;
