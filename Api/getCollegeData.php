<?php
/**
 * Api/getCollegeData.php
 *
 * ChatGoD — College data search API (v1.2.1 -> v1.3.0)
 * Improvements:
 *  - Greeting / pleasantry fast-path responses
 *  - APCu / file caching for repeated queries
 *  - Improved QA ranking (overlap + levenshtein + precomputed RANK_SCORE)
 *  - More robust QA fallback (LIKE + optional FULLTEXT attempt)
 *  - Defensive guards and clearer JSON encoding
 *
 * Usage
 *  POST JSON:
 *    { "auth_token": "<token>", "query": "placements and fees", "limit": 6 }
 *
 * Version: 1.3.0
 * Author: ChatGoD Labs (updated)
 * Last updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!function_exists('apcu_fetch')) {
    function apcu_fetch($key, &$success = null) {
        $success = false;
        return false;
    }
}
if (!function_exists('apcu_store')) {
    function apcu_store($key, $var, $ttl = 0) {
        return false;
    }
}

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

/* -----------------------
   Helper functions (top-level)
   ----------------------- */

function send_json($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_text(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\s\.]+/u', ' ', $s); // allow dots
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

function extract_keywords_advanced(string $text, int $top = 8, array $stopwords = []): array {
    $out = [];
    $text = normalize_text($text);
    if ($text === '') return [];
    $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (!$tokens) return [];

    $freq = [];
    foreach ($tokens as $t) {
        if (mb_strlen($t) < 2) continue;
        if (in_array($t, $stopwords, true)) continue;
        $freq[$t] = ($freq[$t] ?? 0) + 1;
    }

    $bigrams = [];
    for ($i = 0; $i < count($tokens) - 1; $i++) {
        $a = $tokens[$i]; $b = $tokens[$i+1];
        if (in_array($a, $stopwords, true) || in_array($b, $stopwords, true)) continue;
        if (mb_strlen($a) <= 1 || mb_strlen($b) <= 1) continue;
        $bi = $a . ' ' . $b;
        $bigrams[$bi] = ($bigrams[$bi] ?? 0) + 1;
    }

    $combined = [];
    foreach ($freq as $k => $v) $combined[$k] = ($combined[$k] ?? 0) + $v * 1;
    foreach ($bigrams as $k => $v) $combined[$k] = ($combined[$k] ?? 0) + $v * 2;
    arsort($combined);
    return array_slice(array_keys($combined), 0, $top);
}

function boolean_query_from_keywords(array $keywords): string {
    $out = [];
    foreach ($keywords as $w) {
        $wClean = preg_replace('/[+\-><\(\)~*\"@]/u', ' ', $w);
        $wClean = trim($wClean);
        if ($wClean === '') continue;
        // build a conservative boolean expression
        $parts = array_map(function($p){ return $p . '*'; }, explode(' ', $wClean));
        $out[] = '+' . implode(' ', $parts);
    }
    return implode(' ', $out);
}

/* Simple caching: APCu preferred, fallback to file cache in sys_get_temp_dir */
function cache_get(string $key) {
    if (function_exists('apcu_fetch')) {
        $val = apcu_fetch($key, $ok);
        return $ok ? $val : null;
    }
    $fn = sys_get_temp_dir() . '/chatgod_cache_' . md5($key) . '.json';
    if (is_readable($fn)) {
        $c = json_decode(@file_get_contents($fn), true);
        if ($c && isset($c['expiry']) && $c['expiry'] > time()) return $c['value'];
    }
    return null;
}
function cache_set(string $key, $val, int $ttl = 120) {
    if (function_exists('apcu_store')) {
        apcu_store($key, $val, $ttl);
        return true;
    }
    $fn = sys_get_temp_dir() . '/chatgod_cache_' . md5($key) . '.json';
    $c = ['expiry' => time() + $ttl, 'value' => $val];
    @file_put_contents($fn, json_encode($c, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    return true;
}

/* QA ranking helper (overlap + levenshtein + optional RANK_SCORE) */
function score_qa_row(array $rq, string $queryNorm, array $tokQuery): float {
    $q = trim($rq['QUESTION'] ?? '');
    $tags = json_decode($rq['TAGS'] ?? '[]', true) ?: [];

    $qNorm = normalize_text($q);
    $tokQ = preg_split('/\s+/u', $qNorm, -1, PREG_SPLIT_NO_EMPTY);

    $overlap = count(array_intersect($tokQ, $tokQuery));
    $score = $overlap * 2.0;

    $aAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $q) ?: $q;
    $bAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $queryNorm) ?: $queryNorm;
    $lev = @levenshtein(substr($aAscii,0,200), substr($bAscii,0,200));
    $maxlen = max(mb_strlen($aAscii), mb_strlen($bAscii), 1);
    $sim = 1 - ($lev / $maxlen);
    if ($sim > 0) $score += $sim * 2.5;

    if (isset($rq['RANK_SCORE'])) {
        $score += (float)$rq['RANK_SCORE'];
    }

    foreach ($tags as $t) {
        if (in_array(normalize_text((string)$t), $tokQuery)) $score += 0.8;
    }

    return $score;
}

/* -----------------------
   Input parsing & validation
   ----------------------- */

$raw = @file_get_contents('php://input');
$inp = json_decode($raw, true);
if (!is_array($inp)) send_json(['status'=>'error','message'=>'Invalid JSON body'], 400);

$auth = trim((string)($inp['auth_token'] ?? ''));
$query = trim((string)($inp['query'] ?? ''));
$limit = (int)($inp['limit'] ?? 10);
$limit = ($limit > 0 && $limit <= 50) ? $limit : 10;

if ($auth === '') send_json(['status'=>'error','message'=>'auth_token required'], 401);

try {
    $pdo = Connector\db_connect();

    // validate auth token and college
    $stmt = $pdo->prepare("SELECT CLGID, CLG_NAME, CLG_CONTACT_EMAIL, CLG_CONTACT_NUMBER FROM colleges WHERE CLG_AUTH_TOKEN = :t AND IS_ACTIVE = 1 LIMIT 1");
    $stmt->execute([':t' => $auth]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) {
        Connector\log_event('WARNING', 'Invalid auth token attempt', ['auth'=>$auth, 'ip'=>Connector\client_ip()]);
        send_json(['status'=>'error','message'=>'Invalid auth_token or inactive college'], 401);
    }
    $clgId = $college['CLGID'];

    // normalize and keywords
    $STOPWORDS = [
        'the','a','an','and','or','of','in','on','at','for','to','is','are','was','were','be','by','with','that','this','it','from','as','i','you','we','they','have','has','had',
        'please','show','find','get','give','tell','how','what','who','which','when','where','me','my','our','your',
        'क्या','कैसे','है','किस','में','का','की','के','हैं','करें','करे','मुझे','आप','हूँ','हूँ।'
    ];

    $normalized_query = normalize_text($query);
    $keywords = extract_keywords_advanced($query, 10, $STOPWORDS);
    $safeLimit = (int)$limit;

    /* -----------------------
       Greeting / pleasantry fast-path
       ----------------------- */
    $greetings_map = [
      'hi' => 'Hello! How can I help you today? You can ask about placements, fees, hostels or courses.',
      'hello' => 'Hello! I can help with placements, fees, courses, hostels — what do you want to know?',
      'hey' => 'Hey! Ask me about placements, fee structure, admissions or campus facilities.',
      'please' => 'Sure — please tell me what details you need (placements, fees, hostel, courses).',
      'thanks' => "You're welcome! Anything else I can help with?",
      'thank you' => "Glad to help — want to ask about placements or courses next?"
    ];
    if ($normalized_query !== '') {
        foreach ($greetings_map as $k => $resp) {
            if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $normalized_query)) {
                send_json([
                    'status'=>'ok',
                    'college'=>['CLGID'=>$college['CLGID'],'CLG_NAME'=>$college['CLG_NAME']],
                    'query'=>$query,
                    'normalized_query'=>$normalized_query,
                    'extracted_keywords'=>[],
                    'results_count'=>0,
                    'results'=>[],
                    'nearest_qa'=>[],
                    'nearest_suggestions'=>[$resp, 'Ask about placements', 'Ask about fees']
                ], 200);
            }
        }
    }

    /* -----------------------
       Build cache key and try cache
       ----------------------- */
    $cacheKey = "cg_search:{$clgId}:" . md5($normalized_query . '|' . $safeLimit);
    $cached = cache_get($cacheKey);
    if ($cached !== null) {
        // minor telemetry
        Connector\log_event('DEBUG', 'Cache hit for search', ['clg'=>$clgId, 'query'=>mb_substr($normalized_query,0,120)]);
        send_json($cached, 200);
    }

    /* -----------------------
       Fulltext search attempt (primary)
       ----------------------- */
    $results = [];
    $rows = [];
    if (count($keywords) > 0) {
        $ft = boolean_query_from_keywords($keywords);

        $sql = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT,
                       MATCH(SEARCH_TEXT) AGAINST(:ft1 IN BOOLEAN MODE) AS score
                FROM college_data
                WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND MATCH(SEARCH_TEXT) AGAINST(:ft2 IN BOOLEAN MODE)
                ORDER BY score DESC
                LIMIT {$safeLimit}";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':ft1', $ft, PDO::PARAM_STR);
            $stmt->bindValue(':ft2', $ft, PDO::PARAM_STR);
            $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // log and fallback to LIKE search
            Connector\log_event('INFO', 'FT search failed, falling back to LIKE', ['exception'=>$e->getMessage(), 'clg'=>$clgId]);
            $rows = [];
        }

        /* -----------------------
           LIKE fallback if FT returned nothing
           ----------------------- */
        if (empty($rows)) {
            $likeParts = [];
            $params = [':clg' => $clgId];
            $i = 0;
            foreach ($keywords as $kw) {
                $i++;
                $p1 = ":kw{$i}_st";
                $p2 = ":kw{$i}_kw";
                $params[$p1] = '%' . $kw . '%';
                $params[$p2] = '%' . $kw . '%';
                $likeParts[] = "SEARCH_TEXT LIKE {$p1}";
                $likeParts[] = "KEYWORDS LIKE {$p2}";
            }
            if (!empty($likeParts)) {
                $sql2 = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT, 0 AS score
                         FROM college_data
                         WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND (" . implode(' OR ', $likeParts) . ")
                         LIMIT {$safeLimit}";
                $stmt2 = $pdo->prepare($sql2);
                foreach ($params as $k => $v) {
                    if (strpos($sql2, $k) !== false) {
                        $stmt2->bindValue($k, $v, PDO::PARAM_STR);
                    }
                }
                $stmt2->execute();
                $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // assemble results from rows
        foreach ($rows as $r) {
            $basic = !empty($r['CLG_BASIC']) ? json_decode($r['CLG_BASIC'], true) : null;
            $snippet = '';

            switch ($r['DATA_TYPE']) {
                case 'DEPARTMENTS':
                    $depts = !empty($r['CLG_DEPARTMENTS']) ? json_decode($r['CLG_DEPARTMENTS'], true) : null;
                    if (isset($depts['departments']) && is_array($depts['departments'])) {
                        $lines = [];
                        foreach ($depts['departments'] as $d) {
                            $labs = isset($d['labs']) && is_array($d['labs']) ? implode(', ', $d['labs']) : '';
                            $lines[] = "{$d['name']} (HOD: {$d['hod']}, Faculty: {$d['faculty_count']}" . ($labs ? ", Labs: {$labs}" : "") . ")";
                        }
                        $snippet = implode("; ", $lines);
                    }
                    break;

                case 'COURSES':
                    $courses = !empty($r['CLG_COURSES']) ? json_decode($r['CLG_COURSES'], true) : null;
                    if (isset($courses['courses']) && is_array($courses['courses'])) {
                        $lines = [];
                        foreach ($courses['courses'] as $c) {
                            $lines[] = "{$c['name']} ({$c['duration']} yrs, Fees: {$c['fees']})";
                        }
                        $snippet = implode("; ", $lines);
                    }
                    break;

                case 'FEES':
                    $fees = !empty($r['CLG_FEES']) ? json_decode($r['CLG_FEES'], true) : null;
                    if (is_array($fees)) {
                        $lines = [];
                        foreach ($fees as $f) {
                            $lines[] = "{$f['course']}: ₹{$f['amount']} per year";
                        }
                        $snippet = implode("; ", $lines);
                    }
                    break;

                case 'LOCATIONS':
                    $locs = !empty($r['CLG_LOCATIONS']) ? json_decode($r['CLG_LOCATIONS'], true) : null;
                    if (is_array($locs)) {
                        $lines = [];
                        foreach ($locs as $l) {
                            $lines[] = "{$l['campus']} ({$l['address']})";
                        }
                        $snippet = implode("; ", $lines);
                    }
                    break;

                case 'BASIC':
                    if (is_array($basic)) {
                        $snippet = "{$basic['name']} — {$basic['desc']}";
                    }
                    break;

                default:
                    // fallback to SEARCH_TEXT
                    if (!empty($r['SEARCH_TEXT'])) {
                        $text = $r['SEARCH_TEXT'];
                        $pos = null;
                        foreach ($keywords as $kw) {
                            $p = mb_stripos($text, $kw, 0, 'UTF-8');
                            if ($p !== false) { $pos = $p; break; }
                        }
                        if ($pos === null) $snippet = mb_substr($text, 0, 220, 'UTF-8');
                        else {
                            $start = max(0, $pos - 80);
                            $snippet = ($start > 0 ? '...' : '') . mb_substr($text, $start, 200, 'UTF-8') . (mb_strlen($text) > $start + 200 ? '...' : '');
                        }
                    }
            }

            $results[] = [
                'DATAID' => $r['DATAID'],
                'DATA_TYPE' => $r['DATA_TYPE'],
                'CLG_BASIC' => $basic,
                'CLG_LOCATIONS' => $r['CLG_LOCATIONS'] ? json_decode($r['CLG_LOCATIONS'], true) : null,
                'CLG_COURSES' => $r['CLG_COURSES'] ? json_decode($r['CLG_COURSES'], true) : null,
                'CLG_FEES' => $r['CLG_FEES'] ? json_decode($r['CLG_FEES'], true) : null,
                'CLG_DEPARTMENTS' => $r['CLG_DEPARTMENTS'] ? json_decode($r['CLG_DEPARTMENTS'], true) : null,
                'KEYWORDS' => $r['KEYWORDS'] ? json_decode($r['KEYWORDS'], true) : null,
                'snippet' => $snippet,
                'score' => isset($r['score']) ? (float)$r['score'] : 0.0
            ];
        }
    } else {
        // no keywords -> return most recent published entries
        $sqlFallback = "SELECT DATAID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT
                        FROM college_data
                        WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED'
                        ORDER BY INFO_ADDED_ON DESC
                        LIMIT {$safeLimit}";
        $stmt = $pdo->prepare($sqlFallback);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $results[] = [
                'DATAID' => $r['DATAID'],
                'DATA_TYPE' => $r['DATA_TYPE'],
                'CLG_BASIC' => $r['CLG_BASIC'] ? json_decode($r['CLG_BASIC'], true) : null,
                'CLG_LOCATIONS' => $r['CLG_LOCATIONS'] ? json_decode($r['CLG_LOCATIONS'], true) : null,
                'CLG_COURSES' => $r['CLG_COURSES'] ? json_decode($r['CLG_COURSES'], true) : null,
                'CLG_FEES' => $r['CLG_FEES'] ? json_decode($r['CLG_FEES'], true) : null,
                'CLG_DEPARTMENTS' => $r['CLG_DEPARTMENTS'] ? json_decode($r['CLG_DEPARTMENTS'], true) : null,
                'KEYWORDS' => $r['KEYWORDS'] ? json_decode($r['KEYWORDS'], true) : null,
                'snippet' => mb_substr($r['SEARCH_TEXT'] ?? '', 0, 200, 'UTF-8'),
                'score' => 0.0
            ];
        }
    }

    /* -----------------------
       If no results, QA fallback
       ----------------------- */
    $nearest_qa = [];
    if (empty($results)) {
        $rowsq = [];
        if (count($keywords) > 0) {
            // attempt FULLTEXT on QUESTION+ANSWER if available (best effort) then fallback to LIKE
            $ftq = boolean_query_from_keywords($keywords);
            $sqlq_ft = "SELECT ID, QUESTION, ANSWER, TAGS, COALESCE(RANK_SCORE,0) AS RANK_SCORE
                        FROM college_qa_suggestions
                        WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND MATCH(QUESTION, ANSWER) AGAINST(:ft IN BOOLEAN MODE)
                        LIMIT 30";
            try {
                $stmtq = $pdo->prepare($sqlq_ft);
                $stmtq->bindValue(':clg', $clgId, PDO::PARAM_STR);
                $stmtq->bindValue(':ft', $ftq, PDO::PARAM_STR);
                $stmtq->execute();
                $rowsq = $stmtq->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // if fulltext fails (no index or error), fall back to LIKE below
                Connector\log_event('INFO', 'QA fulltext failed, fallback to LIKE', ['exception'=>$e->getMessage(),'clg'=>$clgId]);
                $rowsq = [];
            }

            if (empty($rowsq)) {
                $likeParts = [];
                $params = [':clg' => $clgId];
                $i = 0;
                foreach ($keywords as $kw) {
                    $i++;
                    $pQ = ":q{$i}_q";
                    $pT = ":q{$i}_t";
                    $pA = ":q{$i}_a";
                    $params[$pQ] = '%' . $kw . '%';
                    $params[$pT] = '%' . $kw . '%';
                    $params[$pA] = '%' . $kw . '%';
                    $likeParts[] = "QUESTION LIKE {$pQ}";
                    $likeParts[] = "TAGS LIKE {$pT}";
                    $likeParts[] = "ANSWER LIKE {$pA}";
                }
                if (!empty($likeParts)) {
                    $sqlq = "SELECT ID, QUESTION, ANSWER, TAGS, COALESCE(RANK_SCORE,0) AS RANK_SCORE
                             FROM college_qa_suggestions
                             WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND (" . implode(' OR ', $likeParts) . ") LIMIT 60";
                    $stmtq = $pdo->prepare($sqlq);
                    foreach ($params as $k => $v) {
                        if (strpos($sqlq, $k) !== false) $stmtq->bindValue($k, $v, PDO::PARAM_STR);
                    }
                    $stmtq->execute();
                    $rowsq = $stmtq->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }

        if (!empty($rowsq)) {
            $qnorm = normalize_text($query);
            $tokQuery = preg_split('/\s+/u', $qnorm, -1, PREG_SPLIT_NO_EMPTY);
            $ranked = [];
            foreach ($rowsq as $rq) {
                $s = score_qa_row($rq, $qnorm, $tokQuery);
                $ranked[] = ['row' => $rq, 'score' => $s];
            }
            usort($ranked, function($A, $B){ return $B['score'] <=> $A['score']; });
            foreach (array_slice($ranked, 0, 8) as $r) {
                $rq = $r['row'];
                $nearest_qa[] = [
                    'id' => $rq['ID'],
                    'question' => $rq['QUESTION'],
                    'answer' => $rq['ANSWER'],
                    'tags' => $rq['TAGS'] ? json_decode($rq['TAGS'], true) : null,
                    'score' => $r['score']
                ];
            }
        }

        // nearest suggestions (from BASIC)
        $nearest_suggestions = [];
        $stmtS = $pdo->prepare("SELECT CLG_BASIC FROM college_data WHERE CLG_ID = :clg AND DATA_TYPE = 'BASIC' AND DATA_STATUS='PUBLISHED' LIMIT 1");
        $stmtS->execute([':clg' => $clgId]);
        $bRow = $stmtS->fetch(PDO::FETCH_ASSOC);
        if ($bRow && !empty($bRow['CLG_BASIC'])) {
            $b = json_decode($bRow['CLG_BASIC'], true);
            if (!empty($b['suggestions']) && is_array($b['suggestions'])) $nearest_suggestions = $b['suggestions'];
        }
        if (empty($nearest_suggestions)) $nearest_suggestions = ['Ask about placements', 'Ask about fees', 'Ask about hostel', 'How do I apply?'];

        $payload = [
            'status' => 'ok',
            'college' => [
                'CLGID' => $college['CLGID'],
                'CLG_NAME' => $college['CLG_NAME'],
                'EMAIL' => $college['CLG_CONTACT_EMAIL'],
                'PHONE' => $college['CLG_CONTACT_NUMBER']
            ],
            'query' => $query,
            'normalized_query' => $normalized_query,
            'extracted_keywords' => $keywords,
            'results_count' => 0,
            'results' => [],
            'nearest_qa' => $nearest_qa,
            'nearest_suggestions' => $nearest_suggestions
        ];

        cache_set($cacheKey, $payload, 90);
        send_json($payload, 200);
    }

    // compute suggestions from result CLG_BASIC.suggestions
    $suggestions = [];
    foreach ($results as $res) {
        if (!empty($res['CLG_BASIC']['suggestions']) && is_array($res['CLG_BASIC']['suggestions'])) {
            foreach ($res['CLG_BASIC']['suggestions'] as $s) {
                if (!in_array($s, $suggestions, true)) $suggestions[] = $s;
            }
        }
    }
    if (empty($suggestions)) $suggestions = ['Ask about placements','Ask about fees','Ask about hostel'];

    $payload = [
        'status' => 'ok',
        'college' => [
            'CLGID' => $college['CLGID'],
            'CLG_NAME' => $college['CLG_NAME'],
            'EMAIL' => $college['CLG_CONTACT_EMAIL'],
            'PHONE' => $college['CLG_CONTACT_NUMBER']
        ],
        'query' => $query,
        'normalized_query' => $normalized_query,
        'extracted_keywords' => $keywords,
        'results_count' => count($results),
        'results' => $results,
        'suggestions' => $suggestions
    ];

    // cache results for short time to reduce DB load
    cache_set($cacheKey, $payload, 90);

    send_json($payload, 200);

} catch (PDOException $e) {
    $last_sql = null;
    $last_params = null;
    if (isset($sqlq)) {
        $last_sql = $sqlq;
        $last_params = $params ?? null;
    } elseif (isset($sql2)) {
        $last_sql = $sql2;
        $last_params = $params ?? null;
    } elseif (isset($sql)) {
        $last_sql = $sql;
        $last_params = [
            ':ft1' => $ft ?? null,
            ':ft2' => $ft ?? null,
            ':clg' => $clgId ?? null
        ];
    }
    Connector\log_event('ERROR', 'DB error in getCollegeData', [
        'exception' => $e->getMessage(),
        'last_sql' => $last_sql,
        'last_params' => $last_params
    ]);
    send_json([
        'status'=>'error',
        'message'=>'database error',
        'error_detail'=>$e->getMessage(),
        'last_sql' => $last_sql,
        'last_params' => $last_params
    ], 500);
} catch (Throwable $t) {
    Connector\log_event('ERROR', 'Unexpected error in getCollegeData', ['exception' => $t->getMessage()]);
    send_json([
        'status'=>'error',
        'message'=>'server error',
        'error_detail'=>$t->getMessage()
    ], 500);
}
