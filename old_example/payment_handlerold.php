<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('FASTSOFT_SECRET_KEY', 'a3c0b9ba-0fef-4b75-a633-5acbfe6f960a'); // sua chave
define('FASTSOFT_API_URL', 'https://api.hypercashbrasil.com.br/api/user/transactions');
define('LOG_FILE', 'debug.txt');

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

    return [
        'success' => true,
        'data' => $jsonResp
    ];
}
