<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$dbPath = '/opt/gurufix/db/gurufix.sqlite';
$db = new SQLite3($dbPath);
$db->enableExceptions(true);

$path = str_replace('/api', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];

function sendTelegram($message, $db) {
    $token = $db->querySingle("SELECT value FROM settings WHERE key='telegram_bot_token'");
    $chatId = $db->querySingle("SELECT value FROM settings WHERE key='telegram_chat_id'");
    if (!$token || !$chatId) return false;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $postData = json_encode(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $postData]];
    $context = stream_context_create($opts);
    file_get_contents($url, false, $context);
    return true;
}

// GET /api/settings
if ($path === '/settings' && $method === 'GET') {
    $res = $db->query("SELECT key, value FROM settings");
    $settings = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $settings[$row['key']] = $row['value'];
    echo json_encode($settings);
    exit;
}

// GET /api/services
if ($path === '/services' && $method === 'GET') {
    $res = $db->query("SELECT * FROM services ORDER BY sort_order");
    $services = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $services[] = $row;
    echo json_encode($services);
    exit;
}

// GET /api/reviews
if ($path === '/reviews' && $method === 'GET') {
    $res = $db->query("SELECT * FROM reviews WHERE is_published = 1 ORDER BY id DESC");
    $reviews = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $reviews[] = $row;
    echo json_encode($reviews);
    exit;
}

// POST /api/leads
if ($path === '/leads' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $service_id = isset($input['service_id']) ? (int)$input['service_id'] : null;
    $message = trim($input['message'] ?? '');
    if ($name === '' || $phone === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Имя и телефон обязательны']);
        exit;
    }
    $stmt = $db->prepare("INSERT INTO leads (name, phone, service_id, message, created_at) VALUES (:name, :phone, :service_id, :message, datetime('now'))");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':service_id', $service_id, SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->execute();
    $serviceName = '';
    if ($service_id) {
        $res = $db->querySingle("SELECT title FROM services WHERE id = $service_id");
        if ($res) $serviceName = $res;
    }
    $telegramMsg = "📩 <b>Новая заявка с сайта</b>\n\n👤 Имя: $name\n📞 Телефон: $phone\n";
    if ($serviceName) $telegramMsg .= "🛠 Услуга: $serviceName\n";
    if ($message) $telegramMsg .= "💬 Сообщение: $message\n";
    sendTelegram($telegramMsg, $db);
    echo json_encode(['status' => 'ok', 'message' => 'Заявка принята']);
    exit;
}

// GET /api/admin/leads – пагинация + поиск
if ($path === '/admin/leads' && $method === 'GET') {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $offset = ($page - 1) * $limit;
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = "WHERE name LIKE :search OR phone LIKE :search OR message LIKE :search";
        $params[':search'] = "%$search%";
    }
    $countSql = "SELECT COUNT(*) as total FROM leads $where";
    $stmt = $db->prepare($countSql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, SQLITE3_TEXT);
    $total = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['total'];
    $sql = "SELECT * FROM leads $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, SQLITE3_TEXT);
    $res = $stmt->execute();
    $leads = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $leads[] = $row;
    echo json_encode(['data' => $leads, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    exit;
}

// POST /api/admin/services/order – обновление порядка услуг
if ($path === "/admin/services/order" && $method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $order = $input["order"] ?? [];
    foreach ($order as $idx => $id) {
        $stmt = $db->prepare("UPDATE services SET sort_order = :sort WHERE id = :id");
        $stmt->bindValue(":sort", $idx, SQLITE3_INTEGER);
        $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo json_encode(["status" => "ok"]);
    exit;
}


http_response_code(404);
echo json_encode(['error' => 'Not found']);
// POST /api/admin/services/order – обновление порядка услуг
if ($path === '/admin/services/order' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    foreach ($order as $idx => $id) {
        $stmt = $db->prepare("UPDATE services SET sort_order = :sort WHERE id = :id");
        $stmt->bindValue(':sort', $idx, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo json_encode(['status' => 'ok']);
    exit;
}
