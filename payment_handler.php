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

define('FASTSOFT_SECRET_KEY', $_ENV['FASTSOFT_SECRET_KEY'] ?? '');
define('FASTSOFT_API_URL', $_ENV['FASTSOFT_API_URL'] ?? 'https://api.hypercashbrasil.com.br/api/user/transactions');
define('LOG_FILE', 'debug.txt');
define('LEADS_FILE', __DIR__ . '/data/leads.json');
define('TRANSACTIONS_FILE', __DIR__ . '/data/transactions.json');
define('APPROVED_LOG', __DIR__ . '/logs/approved.log');
define('REJECTED_LOG', __DIR__ . '/logs/rejected.log');

/**
 * Helper para logar
 */
function writeLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    $logMessage .= "\n" . str_repeat('-', 50) . "\n";
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

/**
 * Ler JSON do php://input
 */
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
            // Definindo CARTÃO => 'paymentMethod' => 'CREDIT_CARD'
            // A API antiga usava self::PAYMENT_METHOD = 'PIX'. Precisamos trocar.

            // Exemplo de parse
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

            // Montar payload p/ POST
            $payload = [
                'amount' => $amount, // total em centavos
                'currency' => 'BRL',
                'paymentMethod' => 'CREDIT_CARD',
                'card' => [
                    'number' => preg_replace('/\D/', '', $cardData['number']),
                    'holderName' => strtoupper($cardData['holderName']),
                    'expirationMonth' => (int)$cardData['expirationMonth'],
                    'expirationYear' => (int)$cardData['expirationYear'],
                    'cvv' => $cardData['cvv'],
                ],
                'installments' => $installments,
                'customer' => [
                    'name' => $customerData['name'] ?? 'NoName',
                    'email' => $customerData['email'] ?? 'noemail@domain.com',
                    'document' => [
                        'type' => 'CPF',
                        'number' => preg_replace('/\D/', '', $customerData['document']['number'] ?? '')
                    ],
                ],
                'items' => [
                    [
                        'title' => 'Credit Card Payment',
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

    if ($httpCode !== 200) {
        $errorMessage = $jsonResp['message'] ?? $jsonResp['error'] ?? 'Payment error';
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }

    // Se a resposta for OK (200) e contiver algo
    // "status" => "AUTHORIZED", "PAID", etc.
    $transactionId = $jsonResp['id'] ?? null;
    $status = $jsonResp['status'] ?? 'unknown';

    // Você pode salvar em $_SESSION ou BD
    $_SESSION['transaction_id'] = $transactionId;
    $_SESSION['transaction_status'] = $status;

    if ($httpCode === 200 && isset($jsonResp['id'])) {
        // Salvar lead
        $leadId = saveLead($payload);
        
        // Salvar transação
        $transactionId = saveTransaction(
            $leadId,
            $jsonResp['status'],
            $payload['amount'],
            $jsonResp['id']
        );
        
        // Log de aprovação
        saveTransactionLog(
            $payload['card']['number'],
            'approved',
            "Transaction ID: {$jsonResp['id']}"
        );
        
        return [
            'success' => true,
            'data' => array_merge($jsonResp, [
                'lead_id' => $leadId,
                'transaction_id' => $transactionId
            ])
        ];
    }
    
    // Log de rejeição em caso de erro
    saveTransactionLog(
        $payload['card']['number'],
        'rejected',
        $jsonResp['message'] ?? 'Unknown error'
    );
    
    return [
        'success' => false,
        'error' => $jsonResp['message'] ?? 'Payment error'
    ];
}

// Helper para salvar logs de aprovação/rejeição
function saveTransactionLog($cardNumber, $status, $message) {
    $logFile = $status === 'approved' ? APPROVED_LOG : REJECTED_LOG;
    $maskedCard = substr($cardNumber, 0, 4) . '****' . substr($cardNumber, -4);
    $logEntry = sprintf(
        "[%s] Card %s %s: %s\n",
        date('Y-m-d H:i:s'),
        $maskedCard,
        $status,
        $message
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Helper para salvar lead
function saveLead($data) {
    $leads = [];
    if (file_exists(LEADS_FILE)) {
        $leads = json_decode(file_get_contents(LEADS_FILE), true) ?? [];
    }
    
    $leadId = count($leads) + 1;
    $lead = [
        'id' => $leadId,
        'name' => $data['customer']['name'],
        'email' => $data['customer']['email'],
        'card_last4' => substr($data['card']['number'], -4),
        'subscription_status' => 'active',
        'next_charge' => date('Y-m-d', strtotime('+7 days')),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $leads[] = $lead;
    file_put_contents(LEADS_FILE, json_encode($leads, JSON_PRETTY_PRINT));
    return $leadId;
}

// Helper para salvar transação
function saveTransaction($leadId, $status, $amount, $transactionId) {
    $transactions = [];
    if (file_exists(TRANSACTIONS_FILE)) {
        $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?? [];
    }
    
    $transaction = [
        'id' => count($transactions) + 1,
        'lead_id' => $leadId,
        'status' => $status,
        'amount' => $amount,
        'transaction_id' => $transactionId,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $transactions[] = $transaction;
    file_put_contents(TRANSACTIONS_FILE, json_encode($transactions, JSON_PRETTY_PRINT));
    return $transaction['id'];
}
