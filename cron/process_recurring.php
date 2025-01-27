<?php
require_once __DIR__ . '/../payment_handler.php';

// Processa cobranças recorrentes
function processRecurringCharges() {
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
                    return $t['lead_id'] === $lead['id'];
                });
                
                if (empty($lastTransaction)) {
                    throw new Exception("No previous transaction found");
                }
                
                $lastTransaction = end($lastTransaction);
                
                // Criar payload para nova cobrança
                $payload = [
                    'amount' => $lastTransaction['amount'],
                    'currency' => 'BRL',
                    'paymentMethod' => 'CREDIT_CARD',
                    'card' => [
                        // Dados do cartão salvos de forma segura
                        'number' => '****' . $lead['card_last4'],
                        // ... outros dados necessários
                    ]
                ];
                
                // Processar pagamento
                $result = fastsoftCreateTransaction($payload);
                
                if ($result['success']) {
                    // Atualiza próxima cobrança
                    $lead['next_charge'] = date('Y-m-d', strtotime('+7 days'));
                    
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
                    
                    // Log de falha
                    file_put_contents(
                        __DIR__ . '/../logs/rejected.log',
                        date('Y-m-d H:i:s') . " - Recurring charge failed for lead {$lead['id']}: {$lead['failure_reason']}\n",
                        FILE_APPEND
                    );
                }
                
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
processRecurringCharges(); 