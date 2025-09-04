<?php
// Api/getCollegeData.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

// small helper to return JSON and exit
function send_json($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

// Read JSON body
$inp = json_decode(file_get_contents('php://input'), true);
if (!is_array($inp)) send_json(['status'=>'error','message'=>'Invalid JSON body'], 400);

$auth = trim($inp['auth_token'] ?? '');
$query = trim($inp['query'] ?? '');
$limit = (int)($inp['limit'] ?? 10);
$limit = $limit > 0 && $limit <= 50 ? $limit : 10;

if ($auth === '') send_json(['status'=>'error','message'=>'auth_token required'], 401);

try {
    $pdo = Connector\db_connect();

    // 1) validate auth_token and get college
    $stmt = $pdo->prepare("SELECT CLGID, CLG_NAME, CLG_CONTACT_EMAIL, CLG_CONTACT_NUMBER FROM colleges WHERE CLG_AUTH_TOKEN = :t AND IS_ACTIVE = 1 LIMIT 1");
    $stmt->execute([':t' => $auth]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) {
        Connector\log_event('WARNING', 'Invalid auth token attempt', ['auth'=>$auth, 'ip'=>Connector\client_ip()]);
        send_json(['status'=>'error','message'=>'Invalid auth_token or inactive college'], 401);
    }
    $clgId = $college['CLGID'];

    // 2) simple NLP keyword extractor
    function extract_keywords(string $text, int $top = 6): array {
        $text = mb_strtolower($text, 'UTF-8');
        // remove punctuation (keep unicode letters & digits & whitespace)
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) return [];

        $stopwords = [
            'the','a','an','and','or','of','in','on','at','for','to','is','are','was','were','be','by','with','that','this','it','from','as','i','you','we','they','have','has','had',
            // common verbs + filler
            'please','show','find','get','give','tell','how','what','who','which','when','where'
        ];
        $freq = [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) <= 2) continue; // too short
            if (in_array($t, $stopwords, true)) continue;
            // basic normalization: reduce repeated letters, trim
            $t = preg_replace('/(.)\\1{2,}/u','$1$1', $t);
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, $top);
    }

    $keywords = extract_keywords($query, 8);

    // 3) build boolean fulltext string (escape basic boolean operators)
    function boolean_query_from_keywords(array $keywords): string {
        $out = [];
        foreach ($keywords as $w) {
            // remove boolean-mode special chars
            $wClean = preg_replace('/[+\-><\(\)~*\"@]/', ' ', $w);
            $wClean = trim($wClean);
            if ($wClean === '') continue;
            // prefix + and suffix * for prefix match
            $out[] = '+' . $wClean . '*';
        }
        return implode(' ', $out);
    }

    $results = [];
    if (count($keywords) > 0) {
        $ft = boolean_query_from_keywords($keywords);

        // try fulltext boolean search first (requires fulltext index on SEARCH_TEXT)
        $sql = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT,
                       MATCH(SEARCH_TEXT) AGAINST(:ft IN BOOLEAN MODE) AS score
                FROM college_data
                WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND MATCH(SEARCH_TEXT) AGAINST(:ft IN BOOLEAN MODE)
                ORDER BY score DESC
                LIMIT :lim";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ft', $ft, PDO::PARAM_STR);
        $stmt->bindValue(':clg', $clgId, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // maybe fulltext not supported: fallback to LIKE
            $rows = [];
        }

        // fallback if no rows found
        if (empty($rows)) {
            // build simple LIKE ORs
            $likeParts = [];
            $params = [':clg' => $clgId];
            $i = 0;
            foreach ($keywords as $kw) {
                $i++;
                $p = ":kw{$i}";
                $params[$p] = '%' . $kw . '%';
                $likeParts[] = "SEARCH_TEXT LIKE {$p}";
            }
            $sql2 = "SELECT DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT, 0 AS score
                     FROM college_data
                     WHERE CLG_ID = :clg AND DATA_STATUS = 'PUBLISHED' AND (" . implode(' OR ', $likeParts) . ")
                     LIMIT :lim";
            $stmt2 = $pdo->prepare($sql2);
            foreach ($params as $k => $v) {
                $stmt2->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt2->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt2->execute();
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        // format rows: decode JSON and compute snippet
        foreach ($rows as $r) {
            $basic = null;
            if (!empty($r['CLG_BASIC'])) {
                $basic = json_decode($r['CLG_BASIC'], true);
            }
            $snippet = '';
            if (!empty($r['SEARCH_TEXT'])) {
                $text = $r['SEARCH_TEXT'];
                // find first occurrence of any keyword
                $pos = null;
                foreach ($keywords as $kw) {
                    $p = mb_stripos($text, $kw, 0, 'UTF-8');
                    if ($p !== false) { $pos = $p; break; }
                }
                if ($pos === null) {
                    $snippet = mb_substr($text, 0, 200, 'UTF-8');
                } else {
                    $start = max(0, $pos - 60);
                    $snippet = ($start > 0 ? '...' : '') . mb_substr($text, $start, 160, 'UTF-8') . (mb_strlen($text) > $start + 160 ? '...' : '');
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
        // no extracted keywords => return top N published records for the college as general fallback
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

    // 4) Compute suggestions: prefer CLG_BASIC.suggestions if present in any BASIC row; otherwise derive some from results
    $suggestions = [];
    foreach ($results as $res) {
        if (!empty($res['CLG_BASIC']['suggestions']) && is_array($res['CLG_BASIC']['suggestions'])) {
            foreach ($res['CLG_BASIC']['suggestions'] as $s) {
                if (!in_array($s, $suggestions, true)) $suggestions[] = $s;
            }
        }
    }
    // fallback suggestions
    if (empty($suggestions)) $suggestions = ['Ask about placements', 'Ask about fees', 'Ask about hostel'];

    // 5) Return structured JSON
    send_json([
        'status' => 'ok',
        'college' => [
            'CLGID' => $college['CLGID'],
            'CLG_NAME' => $college['CLG_NAME'],
            'EMAIL' => $college['CLG_CONTACT_EMAIL'],
            'PHONE' => $college['CLG_CONTACT_NUMBER']
        ],
        'query' => $query,
        'keywords' => $keywords,
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
