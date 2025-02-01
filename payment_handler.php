<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega variáveis de ambiente do arquivo .env se existir
if (file_exists(__DIR__ . '/.env')) {
    $envVars = parse_ini_file(__DIR__ . '/.env');
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Define constantes com caminhos absolutos
define('FASTSOFT_SECRET_KEY', $_ENV['FASTSOFT_SECRET_KEY'] ?? '');
define('FASTSOFT_API_URL', $_ENV['FASTSOFT_API_URL'] ?? 'https://api.hypercashbrasil.com.br/api/user/transactions');
define('LOG_FILE', __DIR__ . '/logs/debug.txt');

// Garante que os diretórios existam
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0775, true);
}

// Garante que o arquivo de log exista
if (!file_exists(LOG_FILE)) {
    touch(LOG_FILE);
    chmod(LOG_FILE, 0664);
}

// Headers básicos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/**
 * Helper para logar
 */
function writeLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logMessage .= "Data: " . print_r($data, true) . "\n";
        } else {
            $logMessage .= "Data: {$data}\n";
        }
    }
    
    $logMessage .= str_repeat('-', 50) . "\n";
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

// Se for OPTIONS, retorna 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ler JSON do php://input
$inputRaw = file_get_contents('php://input');
$inputJson = json_decode($inputRaw, true);

if (!is_array($inputJson)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input'
    ]);
    exit;
}

writeLog("Received JSON", $inputJson);

try {
    // Exemplo: Esperamos um "action" ou definimos default
    $action = $inputJson['action'] ?? 'create_card_payment';
    
    switch ($action) {
        case 'create_card_payment':
            // Montamos um payload para a API da FastSoft
            $amount = (int)($inputJson['amount'] ?? 0);    // valor em centavos
            $installments = (int)($inputJson['installments'] ?? 1);

            $cardData = $inputJson['card'] ?? [];
            $customerData = $inputJson['customer'] ?? [];
            
            if ($amount <= 0) {
                throw new Exception("Invalid amount");
            }
            if (empty($cardData['number']) || empty($cardData['holderName']) || empty($cardData['expirationMonth']) || empty($cardData['expirationYear']) || empty($cardData['cvv'])) {
                throw new Exception("Incomplete card data");
            }
            if (empty($customerData['document']['number'])) {
                throw new Exception("Missing customer document");
            }

            // Montar payload p/ POST - Ajustado para corresponder ao exemplo que funciona
            $payload = [
                'amount' => $amount,
                'currency' => 'BRL',
                'paymentMethod' => 'CREDIT_CARD',
                'card' => [
                    'number' => preg_replace('/\D/', '', $cardData['number']),
                    'holderName' => mb_strtoupper($cardData['holderName'], 'UTF-8'),
                    'expirationMonth' => (int)$cardData['expirationMonth'],
                    'expirationYear' => (int)$cardData['expirationYear'],
                    'cvv' => $cardData['cvv']
                ],
                'installments' => $installments,
                'customer' => [
                    'name' => $customerData['name'] ?? 'NoName',
                    'email' => $customerData['email'] ?? 'noemail@domain.com',
                    'document' => [
                        'type' => 'CPF',
                        'number' => preg_replace('/\D/', '', $customerData['document']['number'])
                    ]
                ],
                'items' => [
                    [
                        'title' => 'Assinatura Semanal',
                        'unitPrice' => $amount,
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ]
            ];

            // (Opcional) if you have phone
            if (!empty($customerData['phone'])) {
                $payload['customer']['phone'] = $customerData['phone'];
            }

            $responseData = fastsoftCreateTransaction($payload);
            echo json_encode($responseData);
            exit;

        default:
            throw new Exception("Invalid action: {$action}");
    }

} catch (Exception $e) {
    writeLog("Payment Handler Error", ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Função que chama a API da FastSoft
 */
function fastsoftCreateTransaction(array $payload): array
{
    writeLog("Creating credit card transaction", $payload);

    $curl = curl_init();
    $authHeader = base64_encode(FASTSOFT_SECRET_KEY);

    curl_setopt_array($curl, [
        CURLOPT_URL => FASTSOFT_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic " . $authHeader,
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    writeLog("FastSoft API Response", [
        'httpCode' => $httpCode,
        'response' => $response
    ]);

    if (curl_errno($curl)) {
        $errorMsg = 'CURL error: ' . curl_error($curl);
        curl_close($curl);
        return [
            'success' => false,
            'error' => $errorMsg
        ];
    }

    curl_close($curl);
    $jsonResp = json_decode($response, true);

    if (!is_array($jsonResp)) {
        return [
            'success' => false,
            'error' => 'Invalid API response'
        ];
    }

    // Ajustado para corresponder ao exemplo que funciona
    if ($httpCode !== 200 || (isset($jsonResp['status']) && $jsonResp['status'] !== 'AUTHORIZED')) {
        $errorMessage = $jsonResp['message'] ?? $jsonResp['error'] ?? 'Payment error';
        writeLog("Payment Error", [
            'error' => $errorMessage,
            'response' => $jsonResp
        ]);
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }

    // Se a resposta for OK (200) e contiver algo
    $transactionId = $jsonResp['id'] ?? null;
    $status = $jsonResp['status'] ?? 'unknown';

    // Você pode salvar em $_SESSION ou BD
    $_SESSION['transaction_id'] = $transactionId;
    $_SESSION['transaction_status'] = $status;

    return [
        'success' => true,
        'data' => $jsonResp
    ];
}
