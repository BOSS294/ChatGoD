<?php
/**
 * Api/getCollegeData.php
 *
 * ChatGoD — College data search API (v1.2.0)
 * ™ ChatGoD (ChatGoD Labs). All rights reserved.
 *
 * Description
 *  - Lightweight NLP + keyword extraction -> searches `college_data`.
 *  - If direct matches are found, returns structured results (CLG_BASIC, COURSES, FEES, etc).
 *  - If no matches, falls back to `college_qa_suggestions` (nearest Q/A) and then generic suggestions.
 *  - Designed for a widget frontend; keep AUTH_TOKEN server-side for production (do not embed in public JS).
 *
 * Changes / improvements in v1.2.0
 *  - Fixed placeholder binding issues (no repeated named params) to avoid SQLSTATE HY093.
 *  - Enhanced keyword extraction (unigrams + bigrams, normalization, basic de-duplication & weighting).
 *  - Better fallback ranking for QA suggestions using simple string-similarity scoring.
 *  - Clear, structured JSON output with `extracted_keywords`, `results`, `nearest_qa`, `nearest_suggestions`.
 *
 * Usage
 *  POST JSON:
 *    { "auth_token": "<token>", "query": "placements and fees", "limit": 6 }
 *
 * Output (success)
 *  {
 *    "status":"ok",
 *    "college":{ "CLGID": "...", "CLG_NAME":"...", "EMAIL":"...", "PHONE":"..." },
 *    "query":"...",
 *    "extracted_keywords": [...],
 *    "results_count": N,
 *    "results": [...],
 *    "suggestions": [...],
 *    "nearest_qa": [...],            // present when no direct results but QA suggestions exist
 *    "nearest_suggestions": [...]   // generic / derived suggestions when nothing matched
 *  }
 *
 * Security
 *  - Ensure Modules/secrets.env and Connector are not web-accessible.
 *  - DO NOT expose CLG_AUTH_TOKEN in public JS in production. Use server-side proxy endpoints.
 *
 * Version: 1.2.0
 * Author: ChatGoD Labs
 * Last updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

// small helper to send json and exit
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
    //   - normalizes unicode, removes punctuation
    //   - extracts unigram tokens and bigrams
    //   - filters stopwords and short tokens
    //   - returns top-K by weighted frequency (bigrams given higher weight)
    // -------------------
    function normalize_text(string $s): string {
        // lower-case
        $s = mb_strtolower($s, 'UTF-8');
        // replace punctuation with space but keep unicode letters/digits/space
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
        // collapse whitespace
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);
        return $s;
    }

    // Minimal multilingual stopwords (English + some common Hindi/Marathi short words)
    $STOPWORDS = [
        'the','a','an','and','or','of','in','on','at','for','to','is','are','was','were','be','by','with','that','this','it','from','as','i','you','we','they','have','has','had',
        'please','show','find','get','give','tell','how','what','who','which','when','where','me','my','our','your',
        // short Devanagari/common words (helpful when users write in Hindi/Marathi)
        'क्या','कैसे','है','किस','में','का','की','के','हैं','करें','करे','मुझे','आप','हूँ','हूँ।'
    ];

    function extract_keywords_advanced(string $text, int $top = 8, array $stopwords = []): array {
        $out = [];
        $text = normalize_text($text);
        if ($text === '') return [];
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) return [];

        // unigram frequency
        $freq = [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) <= 2) continue;
            if (in_array($t, $stopwords, true)) continue;
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }

        // bigrams frequency (give higher weight)
        $bigrams = [];
        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $a = $tokens[$i]; $b = $tokens[$i+1];
            if (in_array($a, $stopwords, true) || in_array($b, $stopwords, true)) continue;
            if (mb_strlen($a) <= 1 || mb_strlen($b) <= 1) continue;
            $bi = $a . ' ' . $b;
            $bigrams[$bi] = ($bigrams[$bi] ?? 0) + 1;
        }

        // combine: bigrams get weight 2, unigrams weight 1
        $combined = [];
        foreach ($freq as $k => $v) $combined[$k] = ($combined[$k] ?? 0) + $v * 1;
        foreach ($bigrams as $k => $v) $combined[$k] = ($combined[$k] ?? 0) + $v * 2;

        // sort by weight and return top keys
        arsort($combined);
        $keys = array_keys($combined);
        return array_slice($keys, 0, $top);
    }

    // extract keywords
    $keywords = extract_keywords_advanced($query, 10, $STOPWORDS);

    // also expose normalized query tokens
    $normalized_query = normalize_text($query);

    // convenience: build boolean fulltext string using unique placeholders (avoid reusing :ft)
    function boolean_query_from_keywords(array $keywords): string {
        $out = [];
        foreach ($keywords as $w) {
            // strip special characters that may break boolean mode
            $wClean = preg_replace('/[+\-><\(\)~*\"@]/u', ' ', $w);
            $wClean = trim($wClean);
            if ($wClean === '') continue;
            // prefix + and suffix * for a prefix match
            // note: we will bind two distinct placeholders in SQL (:ft1 and :ft2)
            $out[] = '+' . str_replace(' ', '* *', $wClean) . '*';
        }
        return implode(' ', $out);
    }

    $results = [];
    $rows = [];

    // If keywords exist, attempt fulltext boolean search first
    if (count($keywords) > 0) {
        $ft = boolean_query_from_keywords($keywords);

        // Use distinct placeholders :ft1 and :ft2 so PDO doesn't require special handling for repeated named params
        $sql = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT,
                       MATCH(SEARCH_TEXT) AGAINST(:ft1 IN BOOLEAN MODE) AS score
                FROM college_data
                WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND MATCH(SEARCH_TEXT) AGAINST(:ft2 IN BOOLEAN MODE)
                ORDER BY score DESC
                LIMIT :lim";

        $stmt = $pdo->prepare($sql);
        // bind both ft1 and ft2 to the same string
        $stmt->bindValue(':ft1', $ft, PDO::PARAM_STR);
        $stmt->bindValue(':ft2', $ft, PDO::PARAM_STR);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fulltext may not be supported or boolean syntax fails — fallback gracefully to LIKE below
            Connector\log_event('INFO', 'FT search failed, falling back to LIKE', ['exception'=>$e->getMessage()]);
            $rows = [];
        }

        // If no rows from fulltext, fallback to dynamic LIKE queries (safe parameter binding)
        if (empty($rows)) {
            $likeParts = [];
            $params = [':clg' => $clgId];
            $i = 0;
            foreach ($keywords as $kw) {
                $i++;
                // create unique placeholder names :kw1, :kw2, etc
                $p = ":kw{$i}";
                $params[$p] = '%' . $kw . '%';
                // search in SEARCH_TEXT and KEYWORDS JSON column
                $likeParts[] = "SEARCH_TEXT LIKE {$p}";
                $likeParts[] = "KEYWORDS LIKE {$p}";
            }

            // if likeParts is empty (defensive), don't run invalid SQL
            if (!empty($likeParts)) {
                $sql2 = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT, 0 AS score
                         FROM college_data
                         WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND (" . implode(' OR ', $likeParts) . ")
                         LIMIT :lim";
                $stmt2 = $pdo->prepare($sql2);
                foreach ($params as $k => $v) $stmt2->bindValue($k, $v, PDO::PARAM_STR);
                $stmt2->bindValue(':lim', $limit, PDO::PARAM_INT);
                $stmt2->execute();
                $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // format rows
        foreach ($rows as $r) {
            $basic = null;
            if (!empty($r['CLG_BASIC'])) {
                $basic = json_decode($r['CLG_BASIC'], true);
            }
            $snippet = '';
            if (!empty($r['SEARCH_TEXT'])) {
                $text = $r['SEARCH_TEXT'];
                // find first occurrence of any keyword (case-insensitive)
                $pos = null;
                foreach ($keywords as $kw) {
                    $p = mb_stripos($text, $kw, 0, 'UTF-8');
                    if ($p !== false) { $pos = $p; break; }
                }
                if ($pos === null) {
                    $snippet = mb_substr($text, 0, 220, 'UTF-8');
                } else {
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
        // no keywords -> return most recent published (helpful fallback)
        $stmt = $pdo->prepare("SELECT DATAID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT FROM college_data WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' ORDER BY INFO_ADDED_ON DESC LIMIT :lim");
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
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
        // first, try a targeted LIKE search on question/tags
        if (count($keywords) > 0) {
            $likeParts = [];
            $params = [':clg' => $clgId];
            $i = 0;
            foreach ($keywords as $kw) {
                $i++;
                $p = ":q{$i}";
                $params[$p] = '%' . $kw . '%';
                $likeParts[] = "QUESTION LIKE {$p}";
                $likeParts[] = "TAGS LIKE {$p}";
                $likeParts[] = "ANSWER LIKE {$p}";
            }

            if (!empty($likeParts)) {
                $sqlq = "SELECT ID, QUESTION, ANSWER, TAGS FROM college_qa_suggestions WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND (" . implode(' OR ', $likeParts) . ") LIMIT 30";
                $stmtq = $pdo->prepare($sqlq);
                foreach ($params as $k => $v) $stmtq->bindValue($k, $v, PDO::PARAM_STR);
                $stmtq->execute();
                $rowsq = $stmtq->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rowsq = [];
            }
        } else {
            $rowsq = [];
        }

        // If we found candidates, rank them by simple similarity score (Levenshtein / token overlap)
        $ranked = [];
        if (!empty($rowsq)) {
            $qnorm = normalize_text($query);
            foreach ($rowsq as $rq) {
                $question = $rq['QUESTION'] ?? '';
                $answer = $rq['ANSWER'] ?? '';
                $score = 0.0;

                // token overlap score
                $tokQ = preg_split('/\s+/u', normalize_text($question), -1, PREG_SPLIT_NO_EMPTY);
                $tokQuery = preg_split('/\s+/u', $qnorm, -1, PREG_SPLIT_NO_EMPTY);
                $overlap = count(array_intersect($tokQ, $tokQuery));
                $score += $overlap * 2.0;

                // string similarity (shorter normalized levenshtein-based ratio)
                // compute levenshtein on ascii-fallback (may be approximate for unicode)
                $a = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $question) ?: $question;
                $b = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $query) ?: $query;
                $lev = levenshtein(substr($a, 0, 200), substr($b, 0, 200));
                $maxlen = max(mb_strlen($a), mb_strlen($b), 1);
                $sim = 1 - ($lev / $maxlen); // between 0..1 (may be negative)
                $score += max(0, $sim) * 3.0;

                $ranked[] = ['row' => $rq, 'score' => $score];
            }

            // sort descending
            usort($ranked, function($A, $B){ return $B['score'] <=> $A['score']; });

            // keep top 8
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

        // If still empty, try to pull CLG_BASIC.suggestions (friendly quick-suggestions)
        $nearest_suggestions = [];
        $stmtS = $pdo->prepare("SELECT CLG_BASIC FROM college_data WHERE CLG_ID = :clg AND DATA_TYPE = 'BASIC' AND DATA_STATUS='PUBLISHED' LIMIT 1");
        $stmtS->execute([':clg' => $clgId]);
        $bRow = $stmtS->fetch(PDO::FETCH_ASSOC);
        if ($bRow && !empty($bRow['CLG_BASIC'])) {
            $b = json_decode($bRow['CLG_BASIC'], true);
            if (!empty($b['suggestions']) && is_array($b['suggestions'])) {
                $nearest_suggestions = $b['suggestions'];
            }
        }
        if (empty($nearest_suggestions)) {
            // generic fallback
            $nearest_suggestions = ['Ask about placements', 'Ask about fees', 'Ask about hostel', 'How do I apply?'];
        }

        // return early (no direct results)
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

    // compute suggestions from result CLG_BASIC.suggestions (if present)
    $suggestions = [];
    foreach ($results as $res) {
        if (!empty($res['CLG_BASIC']['suggestions']) && is_array($res['CLG_BASIC']['suggestions'])) {
            foreach ($res['CLG_BASIC']['suggestions'] as $s) {
                if (!in_array($s, $suggestions, true)) $suggestions[] = $s;
            }
        }
    }
    if (empty($suggestions)) $suggestions = ['Ask about placements','Ask about fees','Ask about hostel'];

    // final successful response
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
    Connector\log_event('ERROR', 'DB error in getCollegeData', ['exception' => $e->getMessage()]);
    send_json(['status'=>'error','message'=>'database error'], 500);
} catch (Throwable $t) {
    Connector\log_event('ERROR', 'Unexpected error in getCollegeData', ['exception' => $t->getMessage()]);
    send_json(['status'=>'error','message'=>'server error'], 500);
}
