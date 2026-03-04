<?php
/**
 * SMS Gateway Webhook Receiver
 * Receives incoming SMS, stores in DB, and forwards to Telegram
 */

$TELEGRAM_BOT_TOKEN = '8515081450:AAELepJAL4bdssdzGm9zKXnoPhETKs7ag9E';
$TELEGRAM_CHAT_ID = '8488370666';

$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);

$raw = file_get_contents('php://input');
file_put_contents($logDir . '/sms-webhook.log', date('Y-m-d H:i:s') . " BODY: $raw\n", FILE_APPEND);

$payload = json_decode($raw, true);

if (!is_array($payload) || empty($raw)) {
    http_response_code(200);
    echo json_encode(['ok' => true, 'note' => 'empty']);
    exit;
}

$inner = $payload['payload'] ?? $payload;
$from = $inner['phoneNumber'] ?? $inner['phone_number'] ?? 'Unknown';
$text = $inner['message'] ?? $inner['text'] ?? $inner['body'] ?? '';
$timestamp = $inner['receivedAt'] ?? date('Y-m-d H:i:s');
$event = $payload['event'] ?? 'unknown';
$deviceId = $payload['deviceId'] ?? null;
$externalId = $inner['messageId'] ?? $payload['id'] ?? null;

if (empty($text) && empty($from)) {
    http_response_code(200);
    echo json_encode(['ok' => true, 'skipped' => 'no content']);
    exit;
}

// Store in database
try {
    $envFile = __DIR__ . '/../.env';
    $env = [];
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line && $line[0] !== '#' && strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $env[trim($key)] = trim($val);
        }
    }
    
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? 'tasks_production'
    );
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'tasks', $env['DB_PASSWORD'] ?? '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO sms_messages (direction, phone_number, message, sender_name, status, provider, device_id, external_id, sent_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        'incoming',
        $from,
        $text,
        null, // sender_name unknown for incoming
        'received',
        'android',
        $deviceId,
        $externalId,
        date('Y-m-d H:i:s', strtotime($timestamp)),
    ]);
    
    file_put_contents($logDir . '/sms-webhook.log', date('Y-m-d H:i:s') . " DB: stored ID " . $pdo->lastInsertId() . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logDir . '/sms-webhook.log', date('Y-m-d H:i:s') . " DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Forward to Telegram
$tgMessage = "📱 *Incoming SMS*\nFrom: `$from`\nTime: $timestamp\n───────────\n$text";

$url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'chat_id' => $TELEGRAM_CHAT_ID,
        'text' => $tgMessage,
        'parse_mode' => 'Markdown',
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
curl_exec($ch);
curl_close($ch);

http_response_code(200);
echo json_encode(['ok' => true, 'stored' => true]);
