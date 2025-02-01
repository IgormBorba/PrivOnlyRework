<?php
require_once __DIR__ . '/vendor/autoload.php';

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/.env')) {
    $envVars = parse_ini_file(__DIR__ . '/.env');
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Constantes
define('FASTSOFT_SECRET_KEY', $_ENV['FASTSOFT_SECRET_KEY'] ?? '');
define('FASTSOFT_API_URL', $_ENV['FASTSOFT_API_URL'] ?? 'https://api.hypercashbrasil.com.br/api/user/transactions');
define('LOG_FILE', __DIR__ . '/logs/recurring.txt');
define('MAX_RETRY_ATTEMPTS', 3);

// Garante que os diretórios existam
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0775, true);
}

// Garante que o arquivo de log exista
if (!file_exists(LOG_FILE)) {
    touch(LOG_FILE);
    chmod(LOG_FILE, 0664);
}

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
 * Processa uma cobrança recorrente
 */
function processRecurringCharge($lead, $amount) {
    writeLog("Iniciando cobrança recorrente para lead #{$lead['id']}");

    // Busca a primeira transação aprovada do lead para replicar o mesmo padrão
    $transactionsFile = __DIR__ . '/data/transactions.json';
    $transactions = [];
    $firstApprovedTransaction = null;
    
    if (file_exists($transactionsFile)) {
        $transactions = json_decode(file_get_contents($transactionsFile), true) ?? [];
        
        // Filtra transações do lead e ordena por data
        $leadTransactions = array_filter($transactions, function($t) use ($lead) {
            return $t['lead_id'] === $lead['id'] && $t['status'] === 'approved';
        });
        
        if (!empty($leadTransactions)) {
            usort($leadTransactions, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            $firstApprovedTransaction = reset($leadTransactions);
        }
    }

    // Se não encontrou transação aprovada, não pode processar
    if (!$firstApprovedTransaction) {
        writeLog("Nenhuma transação aprovada encontrada para o lead #{$lead['id']}");
        return false;
    }

    // Busca o request original da transação aprovada
    $originalRequestFile = __DIR__ . '/logs/debug.txt';
    $originalPayload = null;
    
    if (file_exists($originalRequestFile)) {
        $logs = file_get_contents($originalRequestFile);
        if (preg_match_all('/\[.*?\] Received JSON\nData: (.*?)\n-{50}/s', $logs, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $data = json_decode($jsonStr, true);
                if (isset($data['customer']['email']) && 
                    $data['customer']['email'] === $lead['email'] &&
                    isset($data['card']['number']) && 
                    substr($data['card']['number'], -4) === substr($lead['card_number'], -4)) {
                    $originalPayload = $data;
                    break;
                }
            }
        }
    }

    // Se encontrou o payload original, usa ele como base
    if ($originalPayload) {
        $payload = $originalPayload;
        // Atualiza apenas os campos necessários
        $payload['amount'] = $amount;
        $payload['items'][0]['unitPrice'] = $amount;
        
        // Garante que os dados do cartão estão atualizados
        $payload['card'] = [
            'number' => $lead['card_number'],
            'holderName' => mb_strtoupper($lead['card_holder'], 'UTF-8'),
            'expirationMonth' => (int)substr($lead['card_expiration'], 0, 2),
            'expirationYear' => (int)('20' . substr($lead['card_expiration'], -2)),
            'cvv' => $lead['card_cvv']
        ];
    } else {
        // Fallback para o formato padrão se não encontrar o original
        $payload = [
            'action' => 'create_card_payment',
            'amount' => $amount,
            'installments' => 1,
            'card' => [
                'number' => $lead['card_number'],
                'holderName' => mb_strtoupper($lead['card_holder'], 'UTF-8'),
                'expirationMonth' => (int)substr($lead['card_expiration'], 0, 2),
                'expirationYear' => (int)('20' . substr($lead['card_expiration'], -2)),
                'cvv' => $lead['card_cvv']
            ],
            'customer' => [
                'name' => mb_strtoupper($lead['name'], 'UTF-8'),
                'email' => strtolower($lead['email']),
                'document' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $lead['document_number'])
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

        // Adiciona endereço se existir
        if (!empty($lead['address'])) {
            $addressParts = explode(' - ', $lead['address']);
            $streetParts = explode(', ', $addressParts[0]);
            $cityState = explode('/', $addressParts[1]);
            
            $payload['customer']['address'] = [
                'street' => $streetParts[0],
                'complement' => '',
                'city' => $cityState[0],
                'state' => $cityState[1],
                'country' => 'BR',
                'zipCode' => '00000000'
            ];
        }

        // Se tiver telefone, adiciona ao payload
        if (!empty($lead['phone'])) {
            $payload['customer']['phone'] = preg_replace('/\D/', '', $lead['phone']);
        }
    }

    writeLog("Payload da cobrança recorrente", $payload);

    $curl = curl_init();
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
            "Authorization: " . FASTSOFT_SECRET_KEY,
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    writeLog("Resposta da API", [
        'httpCode' => $httpCode,
        'response' => $response
    ]);

    if (curl_errno($curl)) {
        $errorMsg = 'CURL error: ' . curl_error($curl);
        curl_close($curl);
        writeLog("Erro na requisição", $errorMsg);
        return false;
    }

    curl_close($curl);
    $jsonResp = json_decode($response, true);

    if (!is_array($jsonResp)) {
        writeLog("Resposta inválida da API");
        return false;
    }

    if ($httpCode !== 200 || (isset($jsonResp['status']) && $jsonResp['status'] !== 'AUTHORIZED')) {
        $errorMessage = $jsonResp['message'] ?? $jsonResp['error'] ?? 'Payment error';
        writeLog("Erro no pagamento", $errorMessage);
        return false;
    }

    return $jsonResp;
}

/**
 * Atualiza o arquivo de transações
 */
function saveTransaction($leadId, $status, $amountBRL, $transactionId, $amountUSD = null, $dollarRate = null) {
    $transactionsFile = __DIR__ . '/data/transactions.json';
    $transactions = [];
    
    if (file_exists($transactionsFile)) {
        $transactions = json_decode(file_get_contents($transactionsFile), true) ?? [];
    }
    
    $newId = count($transactions) + 1;
    
    $transaction = [
        'id' => $newId,
        'lead_id' => $leadId,
        'status' => $status,
        'amount' => $amountBRL,
        'amount_usd' => $amountUSD,
        'dollar_rate' => $dollarRate,
        'transaction_id' => $transactionId,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $transactions[] = $transaction;
    file_put_contents($transactionsFile, json_encode($transactions, JSON_PRETTY_PRINT));
    return $newId;
}

/**
 * Atualiza o arquivo de leads
 */
function updateLead($leadId, $data) {
    $leadsFile = __DIR__ . '/data/leads.json';
    $leads = [];
    
    if (file_exists($leadsFile)) {
        $leads = json_decode(file_get_contents($leadsFile), true) ?? [];
    }
    
    foreach ($leads as &$lead) {
        if ($lead['id'] === $leadId) {
            foreach ($data as $key => $value) {
                $lead[$key] = $value;
            }
            break;
        }
    }
    
    file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
}

/**
 * Processa todas as cobranças pendentes
 */
function processAllPendingCharges() {
    global $dollarRate;  // Acessa a taxa do dólar
    writeLog("Iniciando processamento de cobranças pendentes");
    
    $leadsFile = __DIR__ . '/data/leads.json';
    if (!file_exists($leadsFile)) {
        writeLog("Arquivo de leads não encontrado");
        return;
    }
    
    $leads = json_decode(file_get_contents($leadsFile), true) ?? [];
    $today = date('Y-m-d');
    
    foreach ($leads as $lead) {
        if ($lead['subscription_status'] === 'active' && $lead['next_charge'] <= $today) {
            writeLog("Processando lead #{$lead['id']} - {$lead['name']}");
            
            // Determina o valor baseado no intervalo de recorrência
            $interval = (int)$_ENV['RECURRING_INTERVAL'] ?? 7;
            $amountUSD = (float)$_ENV["RECURRING_{$interval}_VALUE"] ?? 0;  // Valor em dólar
            $amountBRL = (int)($amountUSD * $dollarRate * 100);  // Converte para reais em centavos
            
            if ($amountUSD <= 0) {
                writeLog("Valor inválido para cobrança do lead #{$lead['id']}");
                continue;
            }
            
            writeLog("Valores da cobrança", [
                'valor_usd' => $amountUSD,
                'valor_brl' => $amountBRL / 100,
                'taxa_dolar' => $dollarRate
            ]);
            
            // Tenta realizar a cobrança
            $attempt = 1;
            $success = false;
            
            while ($attempt <= MAX_RETRY_ATTEMPTS && !$success) {
                writeLog("Tentativa #{$attempt} para lead #{$lead['id']}");
                
                $result = processRecurringCharge($lead, $amountBRL);  // Passa o valor em reais
                
                if ($result) {
                    $success = true;
                    
                    // Salva a transação com os valores em dólar e reais
                    saveTransaction(
                        $lead['id'],
                        'approved',
                        $amountBRL,  // Valor em reais
                        $result['id'] ?? 'tr_' . time(),
                        $amountUSD,  // Valor em dólar
                        $dollarRate  // Taxa usada
                    );
                    
                    // Atualiza a próxima cobrança
                    $nextCharge = date('Y-m-d', strtotime("+{$interval} days"));
                    updateLead($lead['id'], [
                        'next_charge' => $nextCharge,
                        'subscription_status' => 'active'
                    ]);
                    
                    writeLog("Cobrança realizada com sucesso para lead #{$lead['id']}");
                } else {
                    // Salva a tentativa falha
                    saveTransaction(
                        $lead['id'],
                        'rejected',
                        $amountBRL,  // Valor em reais
                        'tr_' . time(),
                        $amountUSD,  // Valor em dólar
                        $dollarRate  // Taxa usada
                    );
                    
                    if ($attempt === MAX_RETRY_ATTEMPTS) {
                        updateLead($lead['id'], [
                            'subscription_status' => 'inactive'
                        ]);
                        writeLog("Todas as tentativas falharam para lead #{$lead['id']}");
                    } else {
                        $nextCharge = date('Y-m-d', strtotime("+1 day"));
                        updateLead($lead['id'], [
                            'next_charge' => $nextCharge
                        ]);
                        writeLog("Agendando nova tentativa para lead #{$lead['id']}");
                    }
                }
                
                $attempt++;
                
                if (!$success && $attempt <= MAX_RETRY_ATTEMPTS) {
                    sleep(3600);
                }
            }
        }
    }
    
    writeLog("Processamento de cobranças pendentes finalizado");
}

// Executa o processamento
processAllPendingCharges(); 