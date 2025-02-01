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

// Carrega a taxa do dólar
$dollarRate = (float)($_ENV['DOLLAR_RATE'] ?? 5.00);

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

/**
 * Função de fallback para mb_strtoupper
 */
function safeStrToUpper($str) {
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($str, 'UTF-8');
    }
    return strtoupper($str);
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
            // Converte o valor de dólar para reais
            $amountUSD = (float)($inputJson['amount'] ?? 0) / 100;    // valor em dólar (centavos para dólar)
            $amountBRL = (int)($amountUSD * $dollarRate * 100);      // converte para reais e volta para centavos
            
            $cardData = $inputJson['card'] ?? [];
            $customerData = $inputJson['customer'] ?? [];
            
            if ($amountUSD <= 0) {
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
                'amount' => $amountBRL, // Usa o valor em reais
                'currency' => 'BRL',
                'paymentMethod' => 'CREDIT_CARD',
                'card' => [
                    'number' => preg_replace('/\D/', '', $cardData['number']),
                    'holderName' => safeStrToUpper($cardData['holderName']),
                    'expirationMonth' => (int)$cardData['expirationMonth'],
                    'expirationYear' => (int)$cardData['expirationYear'],
                    'cvv' => $cardData['cvv']
                ],
                'installments' => 1,
                'customer' => [
                    'name' => safeStrToUpper($customerData['name']),
                    'email' => $_SESSION['user_email'] ?? $customerData['email'],
                    'document' => [
                        'type' => 'CPF',
                        'number' => preg_replace('/\D/', '', $customerData['document']['number'])
                    ]
                ],
                'items' => [
                    [
                        'title' => 'Assinatura Semanal',
                        'unitPrice' => $amountBRL,
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ]
            ];

            // Adiciona endereço apenas se existir
            if (!empty($customerData['address'])) {
                $address = [];
                
                if (!empty($customerData['address']['street'])) {
                    $address['street'] = $customerData['address']['street'];
                }
                if (!empty($customerData['address']['complement'])) {
                    $address['complement'] = $customerData['address']['complement'];
                }
                if (!empty($customerData['address']['city'])) {
                    $address['city'] = $customerData['address']['city'];
                }
                if (!empty($customerData['address']['state'])) {
                    $address['state'] = $customerData['address']['state'];
                }
                if (!empty($customerData['address']['country'])) {
                    $address['country'] = $customerData['address']['country'];
                }
                if (!empty($customerData['address']['zipCode'])) {
                    $address['zipCode'] = preg_replace('/\D/', '', $customerData['address']['zipCode']);
                }

                if (!empty($address)) {
                    $payload['customer']['address'] = $address;
                }
            }

            // Se tiver telefone, adiciona ao payload
            if (!empty($customerData['phone'])) {
                $payload['customer']['phone'] = preg_replace('/\D/', '', $customerData['phone']);
            }

            $responseData = fastsoftCreateTransaction($payload);
            
            // Adiciona os valores em dólar e reais na resposta
            if ($responseData['success']) {
                $responseData['amount_usd'] = $amountUSD;
                $responseData['amount_brl'] = $amountBRL / 100;
                $responseData['dollar_rate'] = $dollarRate;
            }
            
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
