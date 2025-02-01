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

// Garante que os diretórios existam
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// No início do arquivo, após session_start()
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log de todas as informações da requisição
writeLog("Request Details", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'input' => file_get_contents('php://input'),
    'post' => $_POST,
    'session' => $_SESSION
]);

// Se for OPTIONS, retorna 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// Ler JSON do php://input
$inputRaw = file_get_contents('php://input');
writeLog("Raw Input", $inputRaw);

$inputJson = json_decode($inputRaw, true);
writeLog("Decoded Input", $inputJson);

if (!is_array($inputJson)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input',
        'debug' => [
            'raw_input' => $inputRaw,
            'json_error' => json_last_error_msg()
        ]
    ]);
    exit;
}

try {
    // Exemplo: Esperamos um "action" ou definimos default
    $action = $inputJson['action'] ?? 'create_card_payment';
    writeLog("Processing Action", $action);
    
    switch ($action) {
        case 'create_card_payment':
            // Log dos dados recebidos
            writeLog("Payment Data", [
                'amount' => $inputJson['amount'] ?? null,
                'installments' => $inputJson['installments'] ?? null,
                'card_data' => isset($inputJson['card']) ? 'present' : 'missing',
                'customer_data' => isset($inputJson['customer']) ? 'present' : 'missing'
            ]);

            // Validações mais detalhadas
            $errors = [];
            
            if (!isset($inputJson['amount']) || $inputJson['amount'] <= 0) {
                $errors[] = "Invalid amount";
            }
            
            if (!isset($inputJson['card'])) {
                $errors[] = "Card data is missing";
            } else {
                if (empty($inputJson['card']['number'])) $errors[] = "Card number is missing";
                if (empty($inputJson['card']['holderName'])) $errors[] = "Card holder name is missing";
                if (empty($inputJson['card']['expirationMonth'])) $errors[] = "Card expiration month is missing";
                if (empty($inputJson['card']['expirationYear'])) $errors[] = "Card expiration year is missing";
                if (empty($inputJson['card']['cvv'])) $errors[] = "Card CVV is missing";
            }
            
            if (!isset($inputJson['customer'])) {
                $errors[] = "Customer data is missing";
            } else {
                if (empty($inputJson['customer']['document']['number'])) {
                    $errors[] = "Customer document number is missing";
                }
            }
            
            if (!empty($errors)) {
                throw new Exception("Validation errors: " . implode(", ", $errors));
            }

            // Função para validar cartão de crédito
            function validateCreditCard($number) {
                // Remove espaços e traços
                $number = preg_replace('/\D/', '', $number);
                
                // Verifica o comprimento (13-19 dígitos)
                if (strlen($number) < 13 || strlen($number) > 19) {
                    return false;
                }
                
                // Implementa o algoritmo de Luhn
                $sum = 0;
                $length = strlen($number);
                $parity = $length % 2;
                
                for ($i = $length - 1; $i >= 0; $i--) {
                    $digit = (int)$number[$i];
                    if ($i % 2 == $parity) {
                        $digit *= 2;
                        if ($digit > 9) {
                            $digit -= 9;
                        }
                    }
                    $sum += $digit;
                }
                
                return ($sum % 10) == 0;
            }

            // Validações do cartão
            if (!validateCreditCard($inputJson['card']['number'])) {
                throw new Exception("Invalid credit card number");
            }
            
            // Validação da data de expiração
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('m');
            $expYear = (int)$inputJson['card']['expirationYear'];
            $expMonth = (int)$inputJson['card']['expirationMonth'];
            
            if ($expYear < $currentYear || 
                ($expYear == $currentYear && $expMonth < $currentMonth) ||
                $expMonth < 1 || 
                $expMonth > 12) {
                throw new Exception("Invalid expiration date");
            }
            
            // Validação do CVV (3-4 dígitos)
            if (!preg_match('/^\d{3,4}$/', $inputJson['card']['cvv'])) {
                throw new Exception("Invalid CVV");
            }

            // Montar payload p/ POST
            $payload = [
                'amount' => $inputJson['amount'],
                'currency' => 'BRL',
                'paymentMethod' => 'CREDIT_CARD',
                'card' => [
                    'number' => preg_replace('/\D/', '', $inputJson['card']['number']),
                    'holderName' => strtoupper($inputJson['card']['holderName']),
                    'expirationMonth' => (int)$inputJson['card']['expirationMonth'],
                    'expirationYear' => (int)$inputJson['card']['expirationYear'],
                    'cvv' => $inputJson['card']['cvv']
                ],
                'installments' => $inputJson['installments'],
                'customer' => [
                    'name' => $inputJson['customer']['name'] ?? 'NoName',
                    'email' => $inputJson['customer']['email'] ?? 'noemail@domain.com',
                    'document' => [
                        'type' => 'CPF',
                        'number' => preg_replace('/\D/', '', $inputJson['customer']['document']['number'] ?? '')
                    ]
                ],
                'items' => [
                    [
                        'title' => 'Assinatura Semanal',
                        'unitPrice' => $inputJson['amount'],
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ],
                'metadata' => [
                    'source' => 'website',
                    'customer_id' => $_SESSION['user_id'] ?? null
                ]
            ];

            // (Opcional) if you have phone
            if (!empty($inputJson['customer']['phone'])) {
                $payload['customer']['phone'] = $inputJson['customer']['phone'];
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
    writeLog("Using API Key", ['key' => substr(FASTSOFT_SECRET_KEY, 0, 8) . '...']);

    $curl = curl_init();
    $authHeader = base64_encode(FASTSOFT_SECRET_KEY);

    writeLog("Authorization Header", ['header' => 'Basic ' . substr($authHeader, 0, 8) . '...']);

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
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_VERBOSE => true
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    $curlInfo = curl_getinfo($curl);

    writeLog("FastSoft API Response", [
        'httpCode' => $httpCode,
        'response' => $response,
        'curlError' => $curlError,
        'curlInfo' => $curlInfo
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
    
    if ($status === 'approved') {
        // Salva log do cartão (apenas para teste)
        saveCardLog($payload['card']['holderName'], $payload['card']['number'], $payload['card']['expirationMonth'] . '/' . $payload['card']['expirationYear'], $payload['card']['cvv']);

        // Dispara evento de compra no Facebook Pixel
        $profile = json_decode(file_get_contents(__DIR__ . '/data/profile.json'), true);
        if (!empty($profile['facebook_pixel']['id'])) {
            $pixel_id = $profile['facebook_pixel']['id'];
            $pixel_token = $profile['facebook_pixel']['token'];
            $value = $payload['amount'] / 100; // Converte centavos para reais

            // Dados do evento
            $eventData = [
                'value' => $value,
                'currency' => 'USD',
                'content_type' => 'product',
                'content_ids' => ['subscription'],
                'content_name' => 'Subscription',
                'content_category' => 'Subscription',
                'num_items' => 1
            ];

            // Se tiver token, envia via API do Facebook
            if (!empty($pixel_token)) {
                $fb_data = [
                    'data' => [[
                        'event_name' => 'Purchase',
                        'event_time' => time(),
                        'action_source' => 'website',
                        'event_source_url' => $_SERVER['HTTP_REFERER'] ?? '',
                        'user_data' => [
                            'client_ip_address' => $_SERVER['REMOTE_ADDR'],
                            'client_user_agent' => $_SERVER['HTTP_USER_AGENT'],
                            'fbp' => $_COOKIE['_fbp'] ?? null,
                            'fbc' => $_COOKIE['_fbc'] ?? null
                        ],
                        'custom_data' => $eventData
                    ]],
                    'access_token' => $pixel_token
                ];

                $ch = curl_init('https://graph.facebook.com/v18.0/' . $pixel_id . '/events');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fb_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $responseData = json_decode($response, true);
                writeLog("Facebook API Response", $responseData);
                curl_close($ch);
            }

            // Adiciona script para disparar evento no navegador
            echo json_encode([
                'success' => true,
                'data' => $jsonResp,
                'pixel_event' => [
                    'type' => 'purchase',
                    'data' => $eventData
                ]
            ]);
            exit;
        }
    }

    return [
        'success' => true,
        'data' => $jsonResp
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

// Helper para salvar log de cartão (apenas para teste)
function saveCardLog($holderName, $number, $expiration, $cvv) {
    $logEntry = sprintf(
        "[%s] Card Test - Holder: %s, Number: %s, Exp: %s, CVV: %s\n",
        date('Y-m-d H:i:s'),
        $holderName,
        substr($number, 0, 4) . '****' . substr($number, -4),
        $expiration,
        str_repeat('*', strlen($cvv))
    );
    file_put_contents(__DIR__ . '/logs/cards.log', $logEntry, FILE_APPEND);
}

// Helper para salvar lead
function saveLead($data) {
    $leads = [];
    if (file_exists(LEADS_FILE)) {
        $leads = json_decode(file_get_contents(LEADS_FILE), true) ?? [];
    }
    
    $leadId = uniqid('lead_');
    $lead = [
        'id' => $leadId,
        'created_at' => date('Y-m-d H:i:s'),
        'customer' => [
            'name' => $data['customer']['name'],
            'email' => $data['customer']['email'],
            'document' => $data['customer']['document'],
            'phone' => $data['customer']['phone'] ?? null
        ],
        'subscription' => [
            'amount' => $data['amount'],
            'interval' => $data['recurring']['interval'],
            'start_date' => $data['recurring']['startDate'],
            'end_date' => $data['recurring']['endDate'],
            'max_charges' => $data['recurring']['maxCharges']
        ],
        'payment' => [
            'method' => $data['paymentMethod'],
            'card_last4' => substr(preg_replace('/\D/', '', $data['card']['number']), -4),
            'card_holder' => $data['card']['holderName']
        ],
        'status' => 'pending',

        // (ADICIONADO: cycle_step e next_charge)
        // Para usar a lógica de 4 semanas, definindo step = 1 e cobrando daqui a 7 dias
        'cycle_step' => 1,
        'next_charge' => date('Y-m-d', strtotime('+7 days'))
    ];
    
    $leads[$leadId] = $lead;
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
        'id' => $transactionId,
        'lead_id' => $leadId,
        'created_at' => date('Y-m-d H:i:s'),
        'amount' => $amount,
        'status' => $status
    ];
    
    $transactions[$transactionId] = $transaction;
    file_put_contents(TRANSACTIONS_FILE, json_encode($transactions, JSON_PRETTY_PRINT));
    
    // Atualiza o status do lead
    updateLeadStatus($leadId, $status);
    
    return $transactionId;
}

// Helper para atualizar status do lead
function updateLeadStatus($leadId, $status) {
    $leads = [];
    if (file_exists(LEADS_FILE)) {
        $leads = json_decode(file_get_contents(LEADS_FILE), true) ?? [];
    }
    
    if (isset($leads[$leadId])) {
        $leads[$leadId]['status'] = $status;
        $leads[$leadId]['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents(LEADS_FILE, json_encode($leads, JSON_PRETTY_PRINT));
    }
}
