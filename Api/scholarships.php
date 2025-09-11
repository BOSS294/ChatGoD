<?php
/**
 * Api/scholarships.php
 *
 * ChatGoD — Scholarships API (v1.0.0)
 * ™ ChatGoD Labs — All rights reserved.
 *
 * Usage:
 *  - GET /Api/scholarships.php?clg=<CLGID>
 *      -> returns { status: "ok", list: [ { name, count } ... ] }
 *
 *  - GET /Api/scholarships.php?name=<url-encoded-name>&clg=<CLGID>
 *      -> returns consolidated object for the scholarship name
 *
 * Notes:
 *  - clg parameter optional (filters by CLG_ID)
 *  - This endpoint expects a table MAIN_SCHOLAR_DATA (see earlier import script)
 *
 * Version: 1.0.0
 * Author: ChatGoD Labs
 * Last updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

function send_json($obj, int $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

// Accept either GET or POST; read params
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$req = $method === 'POST' ? $_POST : $_GET;

$name = isset($req['name']) ? trim((string)$req['name']) : '';
$clg = isset($req['clg']) ? trim((string)$req['clg']) : null;

try {
    $pdo = Connector\db_connect();

    if ($name === '') {
        // return list (unique scholarship names + counts)
        if ($clg) {
            $sql = "SELECT NAME, COUNT(*) AS cnt
                    FROM MAIN_SCHOLAR_DATA
                    WHERE CLG_ID = :clg AND IS_ACTIVE = 1
                    GROUP BY NAME
                    ORDER BY NAME ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':clg' => $clg]);
        } else {
            $sql = "SELECT NAME, COUNT(*) AS cnt
                    FROM MAIN_SCHOLAR_DATA
                    WHERE IS_ACTIVE = 1
                    GROUP BY NAME
                    ORDER BY NAME ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $list = [];
        foreach ($rows as $r) {
            $list[] = ['name' => $r['NAME'], 'count' => (int)$r['cnt']];
        }
        send_json(['status' => 'ok', 'list' => $list]);
    }

    // DETAIL: fetch all rows matching the name (case-insensitive)
    // use collate for case-insensitive compare in a utf8mb4 setup
    if ($clg) {
        $sql = "SELECT * FROM MAIN_SCHOLAR_DATA
                WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND NAME COLLATE utf8mb4_unicode_ci = :name
                ORDER BY SOURCE_ROW ASC, ID ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':clg' => $clg, ':name' => $name]);
    } else {
        $sql = "SELECT * FROM MAIN_SCHOLAR_DATA
                WHERE IS_ACTIVE = 1 AND NAME COLLATE utf8mb4_unicode_ci = :name
                ORDER BY SOURCE_ROW ASC, ID ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        // no direct rows — return not found
        send_json(['status' => 'ok', 'results_count' => 0, 'message' => 'No records found for that scholarship name', 'name' => $name, 'results' => []]);
    }

    // Consolidation logic
    $consolidated = [
        'name' => $name,
        'records' => count($rows),
        'income_limit_texts' => [],
        'income_min' => null,
        'income_max' => null,
        'criteria' => null,
        'documents_required' => [],
        'payment_remark' => null,
        'site_texts' => [],
        'site_urls' => [],
        'eligibility_categories' => [],
        'normalized_texts' => [],
        'source_rows' => [],
        'courses' => [] // each item: { course_label, scholarship_text, sch_min, sch_max, source_id }
    ];

    // helper to add unique entries
    $addUnique = function(array &$arr, $val) {
        if ($val === null || $val === '') return;
        if (!in_array($val, $arr, true)) $arr[] = $val;
    };

    foreach ($rows as $r) {
        $consolidated['source_rows'][] = $r['SOURCE_ROW'] ?? null;

        // income limit text & numeric min/max
        $ilt = $r['INCOME_LIMIT_TEXT'] ?? '';
        if ($ilt !== '') $addUnique($consolidated['income_limit_texts'], $ilt);
        // numeric
        if (!empty($r['INCOME_MIN']) && is_numeric($r['INCOME_MIN'])) {
            $min = (int)$r['INCOME_MIN'];
            if ($consolidated['income_min'] === null || $min < $consolidated['income_min']) $consolidated['income_min'] = $min;
        }
        if (!empty($r['INCOME_MAX']) && is_numeric($r['INCOME_MAX'])) {
            $max = (int)$r['INCOME_MAX'];
            if ($consolidated['income_max'] === null || $max > $consolidated['income_max']) $consolidated['income_max'] = $max;
        }

        // criteria: prefer a non-empty criteria; if multiple, keep first non-empty
        if ($consolidated['criteria'] === null && !empty($r['CRITERIA'])) {
            $consolidated['criteria'] = $r['CRITERIA'];
        } elseif (!empty($r['CRITERIA'])) {
            // optionally add alternate criteria as normalized_texts
            $addUnique($consolidated['normalized_texts'], $r['CRITERIA']);
        }

        // documents_required (stored as JSON in import)
        if (!empty($r['DOCUMENTS_REQUIRED'])) {
            $docs = json_decode($r['DOCUMENTS_REQUIRED'], true);
            if (is_array($docs)) foreach ($docs as $d) $addUnique($consolidated['documents_required'], $d);
        }

        // payment remark
        if ($consolidated['payment_remark'] === null && !empty($r['PAYMENT_REMARK'])) {
            $consolidated['payment_remark'] = $r['PAYMENT_REMARK'];
        }
        // site_texts & site_urls
        if (!empty($r['SITE_TEXT'])) $addUnique($consolidated['site_texts'], $r['SITE_TEXT']);
        if (!empty($r['SITE_URLS'])) {
            $u = json_decode($r['SITE_URLS'], true);
            if (is_array($u)) foreach ($u as $uu) $addUnique($consolidated['site_urls'], $uu);
        }
        // eligibility categories stored as JSON
        if (!empty($r['ELIGIBILITY_CATEGORIES'])) {
            $cats = json_decode($r['ELIGIBILITY_CATEGORIES'], true);
            if (is_array($cats)) foreach ($cats as $c) $addUnique($consolidated['eligibility_categories'], $c);
        }

        // courses: prefer COURSES JSON if present, else COURSE_YEAR text
        $course_label_candidates = [];
        if (!empty($r['COURSES'])) {
            $cjson = json_decode($r['COURSES'], true);
            if (is_array($cjson)) {
                foreach ($cjson as $c) $course_label_candidates[] = (string)$c;
            }
        }
        if (empty($course_label_candidates) && !empty($r['COURSE_YEAR'])) {
            $course_label_candidates[] = $r['COURSE_YEAR'];
        }
        // build one course entry for each candidate course label
        $sch_text = $r['SCHOLARSHIP_AMOUNT_TEXT'] ?? ($r['SCHOLARSHIP_AMOUNT'] ?? '');
        $sch_min = isset($r['SCHOLARSHIP_AMOUNT_MIN']) && is_numeric($r['SCHOLARSHIP_AMOUNT_MIN']) ? (int)$r['SCHOLARSHIP_AMOUNT_MIN'] : null;
        $sch_max = isset($r['SCHOLARSHIP_AMOUNT_MAX']) && is_numeric($r['SCHOLARSHIP_AMOUNT_MAX']) ? (int)$r['SCHOLARSHIP_AMOUNT_MAX'] : $sch_min;

        foreach ($course_label_candidates as $cLabel) {
            $cLabel = trim((string)$cLabel);
            if ($cLabel === '') continue;
            // avoid duplicate course entry (course label + amount)
            $found = false;
            foreach ($consolidated['courses'] as $ex) {
                if (strcasecmp($ex['course_label'], $cLabel) === 0 && ($ex['scholarship_text'] === $sch_text || $ex['sch_min'] === $sch_min)) {
                    $found = true; break;
                }
            }
            if ($found) continue;
            $consolidated['courses'][] = [
                'course_label' => $cLabel,
                'scholarship_text' => $sch_text,
                'sch_min' => $sch_min,
                'sch_max' => $sch_max,
                'source_id' => $r['ID'] ?? null
            ];
        }
    }

    // sort courses nicely: try to put '1st', '2nd' etc in order if possible
    usort($consolidated['courses'], function($a, $b){
        $ax = $a['course_label']; $bx = $b['course_label'];
        // attempt numeric extract
        preg_match('/(\d+)/', $ax, $ma); preg_match('/(\d+)/', $bx, $mb);
        $na = $ma[1] ?? null; $nb = $mb[1] ?? null;
        if ($na !== null && $nb !== null) return ((int)$na) <=> ((int)$nb);
        return strcasecmp($ax, $bx);
    });

    // final tidy: choose "primary" income text (most frequent)
    $primary_income_text = count($consolidated['income_limit_texts']) ? $consolidated['income_limit_texts'][0] : null;
    if (count($consolidated['income_limit_texts']) > 1) {
        // pick longest (more descriptive) as primary
        usort($consolidated['income_limit_texts'], function($x,$y){ return mb_strlen($y) <=> mb_strlen($x); });
        $primary_income_text = $consolidated['income_limit_texts'][0];
    }

    // package response
    $resp = [
        'status' => 'ok',
        'name' => $consolidated['name'],
        'records' => $consolidated['records'],
        'income_limit_text' => $primary_income_text,
        'income_min' => $consolidated['income_min'],
        'income_max' => $consolidated['income_max'],
        'criteria' => $consolidated['criteria'],
        'documents_required' => $consolidated['documents_required'],
        'payment_remark' => $consolidated['payment_remark'],
        'site_texts' => $consolidated['site_texts'],
        'site_urls' => $consolidated['site_urls'],
        'eligibility_categories' => $consolidated['eligibility_categories'],
        'courses' => $consolidated['courses']
    ];

    send_json($resp);

} catch (PDOException $e) {
    Connector\log_event('ERROR', 'DB error in scholarships API', ['exception' => $e->getMessage()]);
    send_json(['status'=>'error','message'=>'database error','detail'=>$e->getMessage()], 500);
} catch (Throwable $t) {
    Connector\log_event('ERROR', 'Unexpected error in scholarships API', ['exception' => $t->getMessage()]);
    send_json(['status'=>'error','message'=>'server error','detail'=>$t->getMessage()], 500);
}
