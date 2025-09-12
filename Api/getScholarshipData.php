<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

/* -----------------------
   Utilities / helpers
   ----------------------- */

function send_json($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_text(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}
function preprocess_query(string $q): array {
    // remove common conversational prefixes/suffixes
    $q = trim($q);
    if ($q === '') return ['core'=>'','tokens'=>[]];

    // common polite prefixes users type
    $prefixes = [
        'tell me about','tell me','tell','show me','show','what is','what are',
        'give me','find','search for','list','please','pls','hi','hello','hey'
    ];
    $pattern = '/^\\s*(?:' . implode('|', array_map('preg_quote', $prefixes)) . ')\\b\\s*/i';
    $q = preg_replace($pattern, '', $q);

    // strip punctuation at ends and extra spaces
    $q = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q);
    $q = preg_replace('/\s+/u', ' ', $q);
    $q = trim($q);

    // normalize (lowercase & collapse)
    $core = normalize_text($q);

    // build tokens but remove trivial stopwords and the word 'scholar' (we want specifics like 'obc')
    $stop = [
        'the','a','an','in','on','of','for','to','and','is','are','be','by','with',
        'scholar','scholarship','scholarships','please','me','my','about','tell'
    ];
    $parts = preg_split('/\s+/u', $core, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        if (mb_strlen($p, 'UTF-8') <= 1) continue; // too short
        if (in_array($p, $stop, true)) continue;
        $tokens[] = $p;
    }
    // keep unique tokens, in order
    $tokens = array_values(array_unique($tokens));
    return ['core' => $core, 'tokens' => $tokens];
}
// ---- ADD: is_greeting_query (paste after preprocess_query or near other helpers) ----
function is_greeting_query(string $q): bool {
    $n = normalize_text($q);
    if ($n === '') return false;

    // short greetings or polite niceties — if the whole query is a short greeting, treat as greeting
    $greetings = [
        'hi','hii','hello','hey','hey there','hi there','namaste',
        'good morning','good afternoon','good evening','greetings'
    ];

    // exact match (after normalization)
    foreach ($greetings as $g) {
        if ($n === normalize_text($g)) return true;
    }

    // if query is very short (<= 3 tokens) and contains any greeting token, treat as greeting
    $parts = preg_split('/\s+/u', $n, -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts) <= 3) {
        foreach ($parts as $p) {
            if (in_array($p, array_map(function($x){ return normalize_text($x); }, $greetings), true)) return true;
        }
    }

    return false;
}


function parse_income_limit_text(string $txt): array {
    $txt = trim($txt);
    $min = null; $max = null;
    if ($txt === '') return [$min, $max];
    $t = strtolower($txt);
    if (preg_match('/no limit|not applicable|nil|--/i', $txt)) {
        return [$min, $max];
    }
    if (preg_match('/(\d[\d,\.]*)\s*(?:to|-|–)\s*(\d[\d,\.]*)/u', $txt, $m)) {
        $min = intval(preg_replace('/[^\d]/','',$m[1]));
        $max = intval(preg_replace('/[^\d]/','',$m[2]));
        return [$min, $max];
    }
    if (preg_match('/(?:upto|under|below)\s*([\d,\.]+)/i', $txt, $m)) {
        $max = intval(preg_replace('/[^\d]/','',$m[1]));
        return [$min, $max];
    }
    if (preg_match('/(\d[\d,\.]{2,})/u',$txt,$m)) {
        $max = intval(preg_replace('/[^\d]/','',$m[1]));
    }
    return [$min, $max];
}

function extract_urls(string $s): array {
    $out = [];
    if (trim($s) === '') return $out;
    $parts = preg_split('/[,\s;]+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($parts as $p) {
        $p = trim($p, "\"' \t\n\r");
        if ($p === '') continue;
        if (filter_var($p, FILTER_VALIDATE_EMAIL)) { $out[] = $p; continue; }
        if (preg_match('/^(https?:\/\/|www\.)/i', $p) || strpos($p, '.') !== false) {
            if (strpos($p, 'www.') === 0) $p = 'https://' . $p;
            $out[] = $p;
        }
    }
    return array_values(array_unique($out));
}

function detect_documents(string $criteria): array {
    $c = mb_strtolower($criteria, 'UTF-8');
    $candidates = [
        'caste certificate','domicile certificate','domicile proof','12th mark','12th marksheet','bpl card','bank','npcI','npci','kyc','aadhaar','aadhar',
        'income proof','jee main','sgpa','disability','community certificate','beedi shramik card'
    ];
    $out = [];
    foreach ($candidates as $cand) {
        if (mb_stripos($c, $cand) !== false) $out[] = ucfirst($cand);
    }
    return array_values(array_unique($out));
}

function detect_categories(string $name, string $criteria): array {
    $t = mb_strtolower($name . ' ' . $criteria, 'UTF-8');
    $map = [
        'obc' => 'OBC','sc' => 'SC','st' => 'ST','minorit' => 'Minority',
        'girl' => 'Girls','ladli' => 'Girls','jain' => 'Jain','handicap' => 'Differently Abled',
        'orphan' => 'Orphan','aicte' => 'AICTE','beedi' => 'Beedi Worker'
    ];
    $out = [];
    foreach ($map as $k => $v) if (mb_stripos($t, $k) !== false) $out[] = $v;
    return array_values(array_unique($out));
}

function consolidate_rows(array $rows): array {
    $grouped = [];
    foreach ($rows as $r) {
        $name = $r['NAME'] ?? '';
        $k = $name;
        if (!isset($grouped[$k])) {
            $grouped[$k] = [
                'name' => $name,
                'income_limit_texts' => [],
                'income_min' => $r['INCOME_MIN'] ? intval($r['INCOME_MIN']) : null,
                'income_max' => $r['INCOME_MAX'] ? intval($r['INCOME_MAX']) : null,
                'criteria' => $r['CRITERIA'] ?? '',
                'documents_required' => [],
                'payment_remark' => $r['PAYMENT_REMARK'] ?? '',
                'site_text' => $r['SITE_TEXT'] ?? '',
                'site_urls' => [],
                'eligibility_categories' => [],
                'courses' => [],
                'normalized_text' => $r['NORMALIZED_TEXT'] ?? '',
                'source_rows' => []
            ];
        }

        $inc_txt = $r['INCOME_LIMIT_TEXT'] ?? '';
        if ($inc_txt && !in_array($inc_txt, $grouped[$k]['income_limit_texts'], true)) $grouped[$k]['income_limit_texts'][] = $inc_txt;

        if (!empty($r['INCOME_MIN'])) {
            $val = intval($r['INCOME_MIN']);
            if ($grouped[$k]['income_min'] === null || $val < $grouped[$k]['income_min']) $grouped[$k]['income_min'] = $val;
        }
        if (!empty($r['INCOME_MAX'])) {
            $val = intval($r['INCOME_MAX']);
            if ($grouped[$k]['income_max'] === null || $val > $grouped[$k]['income_max']) $grouped[$k]['income_max'] = $val;
        }

        if (!empty($r['CRITERIA']) && strlen($r['CRITERIA']) > strlen($grouped[$k]['criteria'])) $grouped[$k]['criteria'] = $r['CRITERIA'];

        if (!empty($r['DOCUMENTS_REQUIRED'])) {
            $docs = json_decode($r['DOCUMENTS_REQUIRED'], true);
            if (!is_array($docs)) $docs = [$r['DOCUMENTS_REQUIRED']];
            foreach ($docs as $d) if ($d && !in_array($d, $grouped[$k]['documents_required'], true)) $grouped[$k]['documents_required'][] = $d;
        }

        if (!empty($r['PAYMENT_REMARK'])) $grouped[$k]['payment_remark'] = $r['PAYMENT_REMARK'];

        if (!empty($r['SITE_URLS'])) {
            $urls = json_decode($r['SITE_URLS'], true);
            if (!is_array($urls)) $urls = extract_urls($r['SITE_URLS']);
            foreach ($urls as $u) if ($u && !in_array($u, $grouped[$k]['site_urls'], true)) $grouped[$k]['site_urls'][] = $u;
        } elseif (!empty($r['SITE_TEXT'])) {
            foreach (extract_urls($r['SITE_TEXT']) as $u) if (!in_array($u, $grouped[$k]['site_urls'], true)) $grouped[$k]['site_urls'][] = $u;
        }

        if (!empty($r['ELIGIBILITY_CATEGORIES'])) {
            $cats = json_decode($r['ELIGIBILITY_CATEGORIES'], true);
            if (!is_array($cats)) $cats = [$r['ELIGIBILITY_CATEGORIES']];
            foreach ($cats as $c) if ($c && !in_array($c, $grouped[$k]['eligibility_categories'], true)) $grouped[$k]['eligibility_categories'][] = $c;
        } else {
            foreach (detect_categories($r['NAME'] ?? '', $r['CRITERIA'] ?? '') as $c) {
                if (!in_array($c, $grouped[$k]['eligibility_categories'], true)) $grouped[$k]['eligibility_categories'][] = $c;
            }
        }

        $coursesFromRow = [];
        if (!empty($r['COURSES'])) {
            $carr = json_decode($r['COURSES'], true);
            if (is_array($carr)) {
                foreach ($carr as $citem) {
                    if (is_string($citem)) {
                        $coursesFromRow[] = ['course_label' => $citem, 'scholarship_text' => $r['SCHOLARSHIP_AMOUNT_TEXT'] ?? null, 'sch_min' => intval($r['SCHOLARSHIP_AMOUNT_MIN'] ?? 0)];
                    } elseif (is_array($citem)) {
                        $lbl = $citem['course'] ?? ($citem['name'] ?? ($citem['course_label'] ?? ''));
                        $coursesFromRow[] = [
                            'course_label' => $lbl,
                            'scholarship_text' => $citem['scholarship_text'] ?? ($r['SCHOLARSHIP_AMOUNT_TEXT'] ?? ''),
                            'sch_min' => intval($citem['sch_min'] ?? $citem['amount'] ?? $r['SCHOLARSHIP_AMOUNT_MIN'] ?? 0),
                            'sch_max' => intval($citem['sch_max'] ?? $r['SCHOLARSHIP_AMOUNT_MAX'] ?? 0)
                        ];
                    }
                }
            }
        } else {
            $course_label = $r['COURSE_YEAR'] ?? ($r['COURSE'] ?? null);
            if ($course_label) {
                $coursesFromRow[] = [
                    'course_label' => $course_label,
                    'scholarship_text' => $r['SCHOLARSHIP_AMOUNT_TEXT'] ?? '',
                    'sch_min' => isset($r['SCHOLARSHIP_AMOUNT_MIN']) && $r['SCHOLARSHIP_AMOUNT_MIN'] !== '' ? intval($r['SCHOLARSHIP_AMOUNT_MIN']) : (isset($r['SCHOLARSHIP_AMOUNT']) ? intval($r['SCHOLARSHIP_AMOUNT']) : 0),
                    'sch_max' => isset($r['SCHOLARSHIP_AMOUNT_MAX']) && $r['SCHOLARSHIP_AMOUNT_MAX'] !== '' ? intval($r['SCHOLARSHIP_AMOUNT_MAX']) : null
                ];
            }
        }

        foreach ($coursesFromRow as $cr) {
            $present = false;
            foreach ($grouped[$k]['courses'] as &$ex) {
                if (mb_strtolower((string)$ex['course_label'],'UTF-8') === mb_strtolower((string)$cr['course_label'],'UTF-8')) {
                    $present = true;
                    if (!empty($cr['sch_min']) && (empty($ex['sch_min']) || $cr['sch_min'] > $ex['sch_min'])) $ex['sch_min'] = $cr['sch_min'];
                }
            }
            if (!$present) $grouped[$k]['courses'][] = $cr;
            unset($ex);
        }

        $grouped[$k]['source_rows'][] = $r;
    }

    $out = [];
    foreach ($grouped as $g) $out[] = $g;
    return $out;
}

function find_best_name_suggestion(PDO $pdo, string $clgId, string $query): array {
    $queryNorm = normalize_text($query);
    $best = null; $bestScore = PHP_INT_MAX;
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT NAME FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND IS_ACTIVE = 1 LIMIT 500");
        $stmt->execute([':clg' => $clgId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($rows as $name) {
            $n = normalize_text((string)$name);
            if ($n === '') continue;
            $a = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE', $n) ?: $n;
            $b = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE', $queryNorm) ?: $queryNorm;
            $lev = @levenshtein(substr($a,0,200), substr($b,0,200));
            if ($lev === false) continue;
            $maxlen = max(mb_strlen($a), mb_strlen($b), 1);
            $ratio = $lev / $maxlen;
            if ($ratio < $bestScore) { $bestScore = $ratio; $best = $name; }
        }
    } catch (Throwable $e) { /* ignore */ }
    if ($best === null) return [null, null];
    return [$best, $bestScore];
}

// ---- REPLACE: run_search_rows ----
function run_search_rows(PDO $pdo, string $clgId, string $query, int $limit): array {
    $pre = preprocess_query($query);
    $core = $pre['core'];
    $tokens = $pre['tokens'];

    // If core looks like an exact name, try exact match first (case-insensitive)
    if ($core !== '') {
        try {
            $sqlExact = "SELECT * FROM MAIN_SCHOLAR_DATA
                         WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND LOWER(NAME) = :exact
                         LIMIT " . (int)$limit;
            $st = $pdo->prepare($sqlExact);
            $st->bindValue(':clg', $clgId, PDO::PARAM_STR);
            $st->bindValue(':exact', mb_strtolower($core, 'UTF-8'), PDO::PARAM_STR);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        } catch (Throwable $e) { /* ignore and continue to fuzzy search */ }
    }

    // Build fuzzy tokenized search (one LIKE per token, OR'd across columns)
    if (!empty($tokens)) {
        $whereParts = ["CLG_ID = :clg", "IS_ACTIVE = 1"];
        $likeClauses = [];
        $params = [':clg' => $clgId];
        $idx = 0;
        foreach ($tokens as $t) {
            // limit token count to avoid huge queries
            if ($idx >= 8) break;
            $pname = ':t' . $idx;
            $likeClauses[] = "(LOWER(NAME) LIKE $pname OR LOWER(NORMALIZED_TEXT) LIKE $pname OR LOWER(KEYWORDS) LIKE $pname)";
            $params[$pname] = '%' . mb_strtolower($t, 'UTF-8') . '%';
            $idx++;
        }
        if (!empty($likeClauses)) {
            $whereParts[] = '(' . implode(' OR ', $likeClauses) . ')';
            $sql = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE " . implode(' AND ', $whereParts) . " ORDER BY NAME ASC LIMIT " . (int)$limit;
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        }
    }

    // Fallback to original broad LIKE on normalized query (as before)
    try {
        $qnorm = normalize_text($query);
        $like = '%' . str_replace('%','\\%',$qnorm) . '%';
        $sql = "SELECT * FROM MAIN_SCHOLAR_DATA
                WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND (
                  LOWER(NAME) LIKE :like OR LOWER(NORMALIZED_TEXT) LIKE :like OR LOWER(KEYWORDS) LIKE :like
                ) ORDER BY NAME ASC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->bindValue(':like', mb_strtolower($like, 'UTF-8'), PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (Throwable $e) {
        return [];
    }
}


/* -----------------------
   New helper: build catalog (distinct names + counts)
   ----------------------- */
function get_catalog(PDO $pdo, string $clgId, int $limit = 1000): array {
    // use GROUP BY to get counts and order alphabetically
    $sql = "SELECT NAME, COUNT(*) AS cnt FROM MAIN_SCHOLAR_DATA
            WHERE CLG_ID = :clg AND IS_ACTIVE = 1
            GROUP BY NAME
            ORDER BY NAME ASC
            LIMIT " . (int)$limit;
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        $out[] = ['idx' => $i, 'name' => $r['NAME'], 'count' => intval($r['cnt'])];
    }
    return $out;
}

/* -----------------------
   Detect "general catalog" user intent
   ----------------------- */
// ---- REPLACE: looks_like_catalog_query ----
function looks_like_catalog_query(string $q): bool {
    $n = normalize_text($q);
    if ($n === '') return false;

    // If user explicitly asks a generic catalog without modifiers, treat as catalog.
    // But if query contains a category/term like 'obc','sc','st','girl','minority','central','state'
    // we treat it as a focused search (not a catalog request).
    $category_tokens = ['obc','sc','st','girls','girl','minority','ladli','beedi','central','state','bpl','caste','domicile'];
    foreach ($category_tokens as $tk) {
        if (mb_stripos($n, $tk, 0, 'UTF-8') !== false) {
            return false; // has a specific category -> not a general catalog request
        }
    }

    $patterns = [
        'tell me about scholarships','tell me about scholarship','about scholarships',
        'list scholarships','show scholarships','all scholarships',
        'scholarships list','scholarship list','which scholarships','available scholarships',
        'what scholarships','which scholarship'
    ];
    foreach ($patterns as $p) {
        if (mb_stripos($n, $p, 0, 'UTF-8') !== false) return true;
    }

    // fallback: if query contains only the word 'scholar' (and nothing else), show catalog
    if (preg_match('/^scholar(s|ship)?$/i', trim($n))) return true;

    return false;
}

/* -----------------------
   Request parsing
   ----------------------- */
$raw = @file_get_contents('php://input');
$inp = json_decode($raw, true);
if (!is_array($inp)) send_json(['status'=>'error','message'=>'Invalid JSON body'], 400);

$auth = trim((string)($inp['auth_token'] ?? ''));
$action = trim(strtolower((string)($inp['action'] ?? '')));
$query = trim((string)($inp['query'] ?? ''));
$limit = (int)($inp['limit'] ?? 10);
$limit = ($limit > 0 && $limit <= 200) ? $limit : 10;
$clgFilter = isset($inp['clg_id']) ? trim((string)$inp['clg_id']) : null;

if ($auth === '') send_json(['status'=>'error','message'=>'auth_token required'], 401);

try {
    $pdo = Connector\db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // validate auth token and college
    $stmt = $pdo->prepare("SELECT CLGID, CLG_NAME, CLG_CONTACT_EMAIL, CLG_CONTACT_NUMBER FROM colleges WHERE CLG_AUTH_TOKEN = :t AND IS_ACTIVE = 1 LIMIT 1");
    $stmt->execute([':t' => $auth]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) {
        Connector\log_event('WARNING', 'Invalid auth token attempt (scholar API)', ['auth'=>$auth,'ip'=>Connector\client_ip()]);
        send_json(['status'=>'error','message'=>'Invalid auth_token or inactive college'], 401);
    }
    $clgId = $college['CLGID'];
    if ($clgFilter) $clgId = $clgFilter;
    // ---- ADD: early greeting response (paste immediately after clgId is set) ----
    if (($action === '' || $action === 'search') && is_greeting_query($query)) {
        // Return a lightweight greeting payload for the widget to show suggestions instead of running a search.
        send_json([
            'status' => 'ok',
            'greeting' => true,
            'message' => 'Hello! 👋 I\'m ChatGoD — I can help find scholarships, required documents, and how to apply. Try: "Tell me about OBC scholarship" or click one of the suggestions.',
            'nearest_suggestions' => ['Help me find a scholarship','How to apply','Documents required']
        ], 200);
    }

    // If action is empty but the user query looks like "tell me about scholarships" return catalog
    if ($action === '' && looks_like_catalog_query($query)) {
        $catalog = get_catalog($pdo, $clgId, 1000);
        send_json([
            'status' => 'ok',
            'college' => ['CLGID' => $college['CLGID'], 'CLG_NAME' => $college['CLG_NAME']],
            'query' => $query,
            'catalog' => $catalog,
            'count' => count($catalog)
        ], 200);
    }

    // ACTION: list -> names (catalog)
    if ($action === 'list') {
        $catalog = get_catalog($pdo, $clgId, 1000);
        // also provide just names array for backward compatibility
        $names = array_map(function($i){ return $i['name']; }, $catalog);
        send_json(['status'=>'ok','catalog'=>$catalog,'names'=>$names], 200);
    }

    // ACTION: get_by_name -> consolidated records for a name
    if ($action === 'get_by_name') {
        $name = trim((string)($inp['name'] ?? $query));
        if ($name === '') send_json(['status'=>'error','message'=>'name required for get_by_name'], 400);

        $sql = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND LOWER(NAME) = LOWER(:name) ORDER BY SOURCE_ROW ASC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $suggested_name = null; $corrected_query = null;
        if (empty($rows)) {
            $sql2 = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND NAME LIKE :like ORDER BY NAME LIMIT 50";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->bindValue(':clg', $clgId, PDO::PARAM_STR);
            $stmt2->bindValue(':like', '%' . $name . '%', PDO::PARAM_STR);
            $stmt2->execute();
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                list($best, $ratio) = find_best_name_suggestion($pdo, $clgId, $name);
                    if ($best !== null && $ratio !== null && $ratio <= 0.65) {
                    $suggested_name = $best;
                    $corrected_query = $best;
                }
            }
        }

        if (empty($rows)) {
            $nearest_suggestions = ['Ask about placements','Ask about fees','Ask about hostel','How do I apply?'];
            send_json([
                'status'=>'ok',
                'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
                'query'=>$name,
                'corrected_query'=>$corrected_query,
                'suggested_name'=>$suggested_name,
                'results'=>[],
                'nearest_suggestions'=>$nearest_suggestions
            ], 200);
        }

        $consolidated = consolidate_rows($rows);
        send_json([
            'status'=>'ok',
            'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
            'query'=>$name,
            'results'=>$consolidated
        ], 200);
    }

    // ACTION: search -> search across text/keywords; also treat general "tell me about scholarships" queries
    if ($action === 'search') {
        if ($query === '') send_json(['status'=>'error','message'=>'query required for search'], 400);

        // If the query is a general catalog request, return catalog instead of row-level results
        if (looks_like_catalog_query($query)) {
            $catalog = get_catalog($pdo, $clgId, 1000);
            send_json([
                'status' => 'ok',
                'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
                'query' => $query,
                'catalog' => $catalog,
                'count' => count($catalog)
            ], 200);
        }

        $sqlExact = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND LOWER(NAME) = LOWER(:q) LIMIT " . (int)$limit;
        $st = $pdo->prepare($sqlExact);
        $st->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $st->bindValue(':q', $query, PDO::PARAM_STR);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $rows = run_search_rows($pdo, $clgId, $query, $limit);
        }

        $suggested_name = null; $corrected_query = null;
        if (empty($rows)) {
            list($best, $ratio) = find_best_name_suggestion($pdo, $clgId, $query);
                if ($best !== null && $ratio !== null && $ratio <= 0.65) {
                $suggested_name = $best; $corrected_query = $best;
                $sql3 = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND LOWER(NAME) = LOWER(:name) LIMIT " . (int)$limit;
                $stmt3 = $pdo->prepare($sql3);
                $stmt3->bindValue(':clg', $clgId, PDO::PARAM_STR);
                $stmt3->bindValue(':name', $best, PDO::PARAM_STR);
                $stmt3->execute();
                $rows = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        if (empty($rows)) {
            $nearest_suggestions = ['Ask about placements','Ask about fees','Ask about hostel','How do I apply?'];
            try {
                $stmtS = $pdo->prepare("SELECT CLG_BASIC FROM MAIN_SCHOLAR_DATA WHERE CLG_ID = :clg AND LOWER(NAME) LIKE '%basic%' AND IS_ACTIVE = 1 LIMIT 1");
                $stmtS->execute([':clg' => $clgId]);
                $bRow = $stmtS->fetch(PDO::FETCH_ASSOC);
                if ($bRow && !empty($bRow['CLG_BASIC'])) {
                    $b = json_decode($bRow['CLG_BASIC'], true);
                    if (!empty($b['suggestions']) && is_array($b['suggestions'])) $nearest_suggestions = $b['suggestions'];
                }
            } catch (Throwable $e) { /* ignore */ }

            send_json([
                'status'=>'ok',
                'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
                'query'=>$query,
                'corrected_query'=>$corrected_query,
                'suggested_name'=>$suggested_name,
                'results'=>[],
                'nearest_suggestions'=>$nearest_suggestions
            ], 200);
        }

        $consolidated = consolidate_rows($rows);
        send_json([
            'status'=>'ok',
            'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
            'query'=>$query,
            'corrected_query'=>$corrected_query,
            'suggested_name'=>$suggested_name,
            'results'=>$consolidated
        ], 200);
    }

    // ACTION: refine -> filter by provided filters (category, income, course)
    if ($action === 'refine') {
        $filters = is_array($inp['filters'] ?? null) ? $inp['filters'] : [];
        $whereParts = ["CLG_ID = :clg", "IS_ACTIVE = 1"];
        $params = [':clg' => $clgId];

        if (!empty($filters['category'])) {
            $whereParts[] = "(LOWER(ELIGIBILITY_CATEGORIES) LIKE :cat OR LOWER(NAME) LIKE :cat OR LOWER(CRITERIA) LIKE :cat)";
            $params[':cat'] = '%' . mb_strtolower($filters['category'],'UTF-8') . '%';
        }
        if (!empty($filters['income'])) {
            if (preg_match('/below_(\d+)/', $filters['income'], $m)) {
                $val = intval($m[1]);
                $whereParts[] = "((INCOME_MAX IS NOT NULL AND INCOME_MAX <= :incval) OR (INCOME_MAX IS NULL AND INCOME_MIN IS NOT NULL AND INCOME_MIN <= :incval))";
                $params[':incval'] = $val;
            }
        }
        if (!empty($filters['course'])) {
            $whereParts[] = "(LOWER(COURSES) LIKE :course OR LOWER(COURSE_YEAR) LIKE :course OR LOWER(NAME) LIKE :course)";
            $params[':course'] = '%' . mb_strtolower($filters['course'],'UTF-8') . '%';
        }

        $sql = "SELECT * FROM MAIN_SCHOLAR_DATA WHERE " . implode(' AND ', $whereParts) . " LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            send_json(['status'=>'ok','results'=>[],'nearest_suggestions'=>['Ask about placements','Ask about fees']], 200);
        }
        $consolidated = consolidate_rows($rows);
        send_json(['status'=>'ok','results'=>$consolidated], 200);
    }

    send_json(['status'=>'error','message'=>'unknown action: ' . $action], 400);

} catch (PDOException $e) {
    Connector\log_event('ERROR', 'DB error in getScholarshipData', ['exception' => $e->getMessage()]);
    send_json(['status'=>'error','message'=>'database error','error_detail'=>$e->getMessage()], 500);
} catch (Throwable $t) {
    Connector\log_event('ERROR', 'Unexpected error in getScholarshipData', ['exception' => $t->getMessage()]);
    send_json(['status'=>'error','message'=>'server error','error_detail'=>$t->getMessage()], 500);
}
