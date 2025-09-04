<?php
// Api/getCollegeData.php (UPDATED)
// Lightweight NLP -> DB search -> fallback to QA suggestions table
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

function send_json($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

$inp = json_decode(file_get_contents('php://input'), true);
if (!is_array($inp)) send_json(['status'=>'error','message'=>'Invalid JSON body'], 400);

$auth = trim($inp['auth_token'] ?? '');
$query = trim($inp['query'] ?? '');
$limit = (int)($inp['limit'] ?? 10);
$limit = $limit > 0 && $limit <= 50 ? $limit : 10;

if ($auth === '') send_json(['status'=>'error','message'=>'auth_token required'], 401);

try {
    $pdo = Connector\db_connect();

    // Validate token & get college
    $stmt = $pdo->prepare("SELECT CLGID, CLG_NAME, CLG_CONTACT_EMAIL, CLG_CONTACT_NUMBER FROM colleges WHERE CLG_AUTH_TOKEN = :t AND IS_ACTIVE = 1 LIMIT 1");
    $stmt->execute([':t' => $auth]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) {
        Connector\log_event('WARNING', 'Invalid auth token attempt', ['auth'=>$auth, 'ip'=>Connector\client_ip()]);
        send_json(['status'=>'error','message'=>'Invalid auth_token or inactive college'], 401);
    }
    $clgId = $college['CLGID'];

    // NLP: basic keyword extraction
    function extract_keywords(string $text, int $top = 6): array {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) return [];
        $stopwords = [
            'the','a','an','and','or','of','in','on','at','for','to','is','are','was','were','be','by','with','that','this','it','from','as','i','you','we','they','have','has','had',
            'please','show','find','get','give','tell','how','what','who','which','when','where'
        ];
        $freq = [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) <= 2) continue;
            if (in_array($t, $stopwords, true)) continue;
            $t = preg_replace('/(.)\\1{2,}/u','$1$1', $t);
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, $top);
    }

    $keywords = extract_keywords($query, 8);

    function boolean_query_from_keywords(array $keywords): string {
        $out = [];
        foreach ($keywords as $w) {
            $wClean = preg_replace('/[+\-><\(\)~*\"@]/', ' ', $w);
            $wClean = trim($wClean);
            if ($wClean === '') continue;
            $out[] = '+' . $wClean . '*';
        }
        return implode(' ', $out);
    }

    $results = [];
    $rows = [];

    if (count($keywords) > 0) {
        $ft = boolean_query_from_keywords($keywords);
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
            $rows = [];
        }

        // fallback LIKE if none
        if (empty($rows)) {
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
            foreach ($params as $k => $v) $stmt2->bindValue($k, $v, PDO::PARAM_STR);
            $stmt2->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt2->execute();
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        // format rows
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
                if ($pos === null) $snippet = mb_substr($text, 0, 200, 'UTF-8');
                else {
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
        // no keywords -> return most recent published
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

    // If we found no results, query college_qa_suggestions for nearest matches
    $nearest_qa = [];
    if (empty($results)) {
        if (count($keywords) > 0) {
            // build LIKE query across QUESTION and TAGS
            $likeParts = [];
            $params = [':clg' => $clgId];
            $i = 0;
            foreach ($keywords as $kw) {
                $i++;
                $p = ":q{$i}";
                // search QUESTION and tags (tags stored as JSON text)
                $likeParts[] = "QUESTION LIKE {$p}";
                $likeParts[] = "TAGS LIKE {$p}";
                $params[$p] = '%' . $kw . '%';
            }
            // search active suggestions only
            $sqlq = "SELECT ID, QUESTION, ANSWER, TAGS FROM college_qa_suggestions WHERE CLG_ID = :clg AND IS_ACTIVE = 1 AND (" . implode(' OR ', $likeParts) . ") LIMIT 8";
            $stmtq = $pdo->prepare($sqlq);
            foreach ($params as $k => $v) $stmtq->bindValue($k, $v, PDO::PARAM_STR);
            $stmtq->execute();
            $rowsq = $stmtq->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rowsq as $rq) {
                $nearest_qa[] = [
                    'id' => $rq['ID'],
                    'question' => $rq['QUESTION'],
                    'answer' => $rq['ANSWER'],
                    'tags' => $rq['TAGS'] ? json_decode($rq['TAGS'], true) : null
                ];
            }
        }

        // if still empty, return generic nearest_suggestions (prefer local CLG_BASIC.suggestions if any)
        $nearest_suggestions = [];
        // try CLG_BASIC suggestions from any published basic record
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
            // fallback generic
            $nearest_suggestions = ['Ask about placements', 'Ask about fees', 'Ask about hostel', 'How do I apply?'];
        }

        // respond with nearest_qa (if any) or nearest_suggestions
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
            'results_count' => 0,
            'results' => [],
            'nearest_qa' => $nearest_qa,
            'nearest_suggestions' => $nearest_suggestions
        ], 200);
    }

    // 4) Compute suggestions from the results' CLG_BASIC.suggestions
    $suggestions = [];
    foreach ($results as $res) {
        if (!empty($res['CLG_BASIC']['suggestions']) && is_array($res['CLG_BASIC']['suggestions'])) {
            foreach ($res['CLG_BASIC']['suggestions'] as $s) {
                if (!in_array($s, $suggestions, true)) $suggestions[] = $s;
            }
        }
    }
    if (empty($suggestions)) $suggestions = ['Ask about placements','Ask about fees','Ask about hostel'];

    // return results
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
