<?php
require_once __DIR__ . '/../payment_handler.php';

// Carregar configurações
$env = parse_ini_file(__DIR__ . '/../.env');
$recurring_interval = (int)($env['RECURRING_INTERVAL'] ?? 7);

// Processa cobranças recorrentes
function processRecurringCharges($interval) {
    $leads = json_decode(file_get_contents(LEADS_FILE), true) ?? [];
    $today = date('Y-m-d');
    
    foreach ($leads as &$lead) {
        if ($lead['subscription_status'] !== 'active') {
            continue;
        }
        
        if ($lead['next_charge'] === $today) {
            try {
                // Recuperar última transação
                $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?? [];
                $lastTransaction = array_filter($transactions, function($t) use ($lead) {
                    return $t['user_id'] === $lead['id'] && $t['status'] === 'approved';
                });
                
                if (empty($lastTransaction)) {
                    throw new Exception("No previous successful transaction found");
                }
                
                $lastTransaction = end($lastTransaction);
                
                // Criar payload para nova cobrança
                $payload = [
                    'amount' => $lastTransaction['amount'],
                    'currency' => 'BRL',
                    'paymentMethod' => 'CREDIT_CARD',
                    'card' => [
                        'number' => $lead['card_number'],
                        'holder_name' => $lead['card_holder_name'],
                        'expiration_month' => $lead['card_expiration_month'],
                        'expiration_year' => $lead['card_expiration_year'],
                        'cvv' => $lead['card_cvv']
                    ]
                ];
                
                // Processar pagamento
                $result = fastsoftCreateTransaction($payload);
                
                if ($result['success']) {
                    // Atualiza próxima cobrança
                    $lead['next_charge'] = date('Y-m-d', strtotime("+{$interval} days"));
                    $lead['subscription_status'] = 'active';
                    
                    // Registrar transação
                    $transaction = [
                        'id' => uniqid('trans_'),
                        'user_id' => $lead['id'],
                        'amount' => $lastTransaction['amount'],
                        'status' => 'approved',
                        'type' => 'recurring',
                        'payment_method' => 'credit_card',
                        'created_at' => date('Y-m-d H:i:s'),
                        'gateway_response' => $result
                    ];
                    $transactions[] = $transaction;
                    
                    // Log de sucesso
                    file_put_contents(
                        __DIR__ . '/../logs/approved.log',
                        date('Y-m-d H:i:s') . " - Recurring charge success for lead {$lead['id']}\n",
                        FILE_APPEND
                    );
                } else {
                    // Marca como falha e registra o erro
                    $lead['subscription_status'] = 'failed';
                    $lead['failure_reason'] = $result['error'] ?? 'Unknown error';
                    
                    // Registrar transação falha
                    $transaction = [
                        'id' => uniqid('trans_'),
                        'user_id' => $lead['id'],
                        'amount' => $lastTransaction['amount'],
                        'status' => 'failed',
                        'type' => 'recurring',
                        'payment_method' => 'credit_card',
                        'created_at' => date('Y-m-d H:i:s'),
                        'error_message' => $lead['failure_reason'],
                        'gateway_response' => $result
                    ];
                    $transactions[] = $transaction;
                    
                    // Log de falha
                    file_put_contents(
                        __DIR__ . '/../logs/rejected.log',
                        date('Y-m-d H:i:s') . " - Recurring charge failed for lead {$lead['id']}: {$lead['failure_reason']}\n",
                        FILE_APPEND
                    );
                }
                
                // Atualizar arquivo de transações
                file_put_contents(TRANSACTIONS_FILE, json_encode($transactions, JSON_PRETTY_PRINT));
                
            } catch (Exception $e) {
                // Log de erro crítico
                file_put_contents(
                    __DIR__ . '/../logs/error.log',
                    date('Y-m-d H:i:s') . " - Critical error processing lead {$lead['id']}: {$e->getMessage()}\n",
                    FILE_APPEND
                );
            }
        }
    }
    
    // Atualiza arquivo de leads
    file_put_contents(LEADS_FILE, json_encode($leads, JSON_PRETTY_PRINT));
}

// Executar processamento
processRecurringCharges($recurring_interval); 