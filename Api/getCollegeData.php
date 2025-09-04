<?php
/**
 * Api/getCollegeData.php
 *
 * ChatGoD — College data search API (v1.2.1)
 * ™ ChatGoD (ChatGoD Labs). All rights reserved.
 *
 * Description
 *  - Lightweight NLP + keyword extraction -> searches `college_data`.
 *  - If direct matches are found, returns structured results (CLG_BASIC, COURSES, FEES, etc).
 *  - If no matches, falls back to `college_qa_suggestions` (nearest Q/A) and then generic suggestions.
 *  - Designed for a widget frontend; keep AUTH_TOKEN server-side for production (do not embed in public JS).
 *
 * Changes in v1.2.1
 *  - Fixes SQLSTATE[HY093] by avoiding binding LIMIT as a named parameter (MySQL native prepared statements
 *    can be inconsistent with binding LIMIT). LIMIT is now inserted as an integer after sanitization.
 *  - Tightened placeholder binding: we only bind placeholders that exist in the prepared SQL.
 *  - Minor defensive checks and improved logging for fallback branches.
 *
 * Usage
 *  POST JSON:
 *    { "auth_token": "<token>", "query": "placements and fees", "limit": 6 }
 *
 * Version: 1.2.1
 * Author: ChatGoD Labs
 * Last updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

function send_json($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

// read request body
$inp = json_decode(file_get_contents('php://input'), true);
if (!is_array($inp)) send_json(['status'=>'error','message'=>'Invalid JSON body'], 400);

$auth = trim((string)($inp['auth_token'] ?? ''));
$query = trim((string)($inp['query'] ?? ''));
$limit = (int)($inp['limit'] ?? 10);
$limit = ($limit > 0 && $limit <= 50) ? $limit : 10;

if ($auth === '') send_json(['status'=>'error','message'=>'auth_token required'], 401);

try {
    $pdo = Connector\db_connect();

    // --- validate auth token and college ---
    $stmt = $pdo->prepare("SELECT CLGID, CLG_NAME, CLG_CONTACT_EMAIL, CLG_CONTACT_NUMBER FROM colleges WHERE CLG_AUTH_TOKEN = :t AND IS_ACTIVE = 1 LIMIT 1");
    $stmt->execute([':t' => $auth]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) {
        Connector\log_event('WARNING', 'Invalid auth token attempt', ['auth'=>$auth, 'ip'=>Connector\client_ip()]);
        send_json(['status'=>'error','message'=>'Invalid auth_token or inactive college'], 401);
    }
    $clgId = $college['CLGID'];

    // -------------------
    // NLP / Keyword extractor (improved)
    // -------------------
    function normalize_text(string $s): string {
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}\s\.]+/u', ' ', $s); // allow dots
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    $STOPWORDS = [
        'the','a','an','and','or','of','in','on','at','for','to','is','are','was','were','be','by','with','that','this','it','from','as','i','you','we','they','have','has','had',
        'please','show','find','get','give','tell','how','what','who','which','when','where','me','my','our','your',
        'क्या','कैसे','है','किस','में','का','की','के','हैं','करें','करे','मुझे','आप','हूँ','हूँ।'
    ];

    function extract_keywords_advanced(string $text, int $top = 8, array $stopwords = []): array {
        $out = [];
        $text = normalize_text($text);
        if ($text === '') return [];
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) return [];

        $freq = [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) < 2) continue; // allow 2-char tokens
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

    $keywords = extract_keywords_advanced($query, 10, $STOPWORDS);
    $normalized_query = normalize_text($query);

    function boolean_query_from_keywords(array $keywords): string {
        $out = [];
        foreach ($keywords as $w) {
            $wClean = preg_replace('/[+\-><\(\)~*\"@]/u', ' ', $w);
            $wClean = trim($wClean);
            if ($wClean === '') continue;
            $out[] = '+' . str_replace(' ', '* *', $wClean) . '*';
        }
        return implode(' ', $out);
    }

    $results = [];
    $rows = [];

    // IMPORTANT: avoid binding LIMIT as a parameter for MySQL native prepared statements to prevent HY093.
    $safeLimit = (int)$limit;

    if (count($keywords) > 0) {
        $ft = boolean_query_from_keywords($keywords);

        // Use distinct placeholders ft1/ft2 and insert LIMIT directly as integer
        $sql = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT,
                       MATCH(SEARCH_TEXT) AGAINST(:ft1 IN BOOLEAN MODE) AS score
                FROM college_data
                WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND MATCH(SEARCH_TEXT) AGAINST(:ft2 IN BOOLEAN MODE)
                ORDER BY score DESC
                LIMIT {$safeLimit}";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ft1', $ft, PDO::PARAM_STR);
        $stmt->bindValue(':ft2', $ft, PDO::PARAM_STR);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->execute();

        try {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Connector\log_event('INFO', 'FT search failed, falling back to LIKE', ['exception'=>$e->getMessage()]);
            $rows = [];
        }

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

        foreach ($rows as $r) {
            $basic = !empty($r['CLG_BASIC']) ? json_decode($r['CLG_BASIC'], true) : null;
            $snippet = '';
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
        // fallback most recent published - LIMIT inserted directly
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

    // If no results, attempt QA suggestions search (nearest QA)
    $nearest_qa = [];
    if (empty($results)) {
        $rowsq = [];
        if (count($keywords) > 0) {
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
            $sqlq = "SELECT ID, QUESTION, ANSWER, TAGS FROM college_qa_suggestions WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND (" . implode(' OR ', $likeParts) . ") LIMIT 30";
            $stmtq = $pdo->prepare($sqlq);
            foreach ($params as $k => $v) {
                if (strpos($sqlq, $k) !== false) $stmtq->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmtq->execute();
            $rowsq = $stmtq->fetchAll(PDO::FETCH_ASSOC);
        }

        $ranked = [];
        if (!empty($rowsq)) {
            $qnorm = normalize_text($query);
            foreach ($rowsq as $rq) {
                $question = $rq['QUESTION'] ?? '';
                $answer = $rq['ANSWER'] ?? '';
                $score = 0.0;

                $tokQ = preg_split('/\s+/u', normalize_text($question), -1, PREG_SPLIT_NO_EMPTY);
                $tokQuery = preg_split('/\s+/u', $qnorm, -1, PREG_SPLIT_NO_EMPTY);
                $overlap = count(array_intersect($tokQ, $tokQuery));
                $score += $overlap * 2.0;

                $a = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $question) ?: $question;
                $b = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $query) ?: $query;
                $lev = @levenshtein(substr($a, 0, 200), substr($b, 0, 200));
                $maxlen = max(mb_strlen($a), mb_strlen($b), 1);
                $sim = 1 - ($lev / $maxlen);
                $score += max(0, $sim) * 3.0;

                $ranked[] = ['row' => $rq, 'score' => $score];
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

        $nearest_suggestions = [];
        $stmtS = $pdo->prepare("SELECT CLG_BASIC FROM college_data WHERE CLG_ID = :clg AND DATA_TYPE = 'BASIC' AND DATA_STATUS='PUBLISHED' LIMIT 1");
        $stmtS->execute([':clg' => $clgId]);
        $bRow = $stmtS->fetch(PDO::FETCH_ASSOC);
        if ($bRow && !empty($bRow['CLG_BASIC'])) {
            $b = json_decode($bRow['CLG_BASIC'], true);
            if (!empty($b['suggestions']) && is_array($b['suggestions'])) $nearest_suggestions = $b['suggestions'];
        }
        if (empty($nearest_suggestions)) $nearest_suggestions = ['Ask about placements', 'Ask about fees', 'Ask about hostel', 'How do I apply?'];

        send_json([
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
        ], 200);
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

    send_json([
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
    ], 200);

} catch (PDOException $e) {
    // Determine which SQL and params to log
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
        // For FT query, manually build params array
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
