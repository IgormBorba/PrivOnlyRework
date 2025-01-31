<?php
require_once __DIR__ . '/../payment_handler.php';

// Carregar configurações do .env
$env = parse_ini_file(__DIR__ . '/../.env');

// Mantemos a leitura original do $recurring_interval, mas não usaremos mais:
$recurring_interval = (int)($env['RECURRING_INTERVAL'] ?? 7);

// Lemos os 4 valores para cada "semana" do ciclo
$r7  = (float)($env['RECURRING_7_VALUE']  ?? 0);
$r14 = (float)($env['RECURRING_14_VALUE'] ?? 0);
$r21 = (float)($env['RECURRING_21_VALUE'] ?? 0);
$r28 = (float)($env['RECURRING_28_VALUE'] ?? 0);

// Processa cobranças recorrentes
function processRecurringCharges($interval)
{
    $leads = json_decode(file_get_contents(LEADS_FILE), true) ?? [];
    $today = date('Y-m-d');

    foreach ($leads as &$lead) {
        if ($lead['subscription_status'] !== 'active') {
            continue;
        }

        // Se não existir 'cycle_step', definimos = 1
        if (!isset($lead['cycle_step'])) {
            $lead['cycle_step'] = 1;
        }

        if (isset($lead['next_charge']) && $lead['next_charge'] === $today) {
            try {
                // Carrega todas as transações
                $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?? [];

                // Procura a última transação aprovada deste lead
                $lastApproved = array_filter($transactions, function($t) use ($lead) {
                    return ($t['user_id'] === $lead['id'] && $t['status'] === 'approved');
                });

                if (empty($lastApproved)) {
                    throw new Exception("No previous successful transaction found");
                }

                $lastTransaction = end($lastApproved);

                // -------------------------------
                // LÓGICA DE 4 SEMANAS (cycle_step)
                // -------------------------------
                $valorCobrar = 0;
                switch ($lead['cycle_step']) {
                    case 1:
                        $valorCobrar = $GLOBALS['r7'];
                        break;
                    case 2:
                        $valorCobrar = $GLOBALS['r14'];
                        break;
                    case 3:
                        $valorCobrar = $GLOBALS['r21'];
                        break;
                    case 4:
                        $valorCobrar = $GLOBALS['r28'];
                        break;
                    default:
                        $valorCobrar = $GLOBALS['r7'];
                        $lead['cycle_step'] = 1;
                }

                // (Comentamos o uso do $lastTransaction['amount'] e do $interval)
                /*
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
                */

                // Usamos 'valorCobrar' do step
                $payload = [
                    'amount' => intval($valorCobrar * 100),
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
                    // Em caso de sucesso

                    // (Comentamos a linha antiga)
                    // $lead['next_charge'] = date('Y-m-d', strtotime("+{$interval} days"));

                    // Nova lógica: +7 dias
                    $lead['next_charge'] = date('Y-m-d', strtotime('+7 days'));

                    // Incrementa cycle_step
                    $lead['cycle_step']++;
                    if ($lead['cycle_step'] > 4) {
                        $lead['cycle_step'] = 1;
                    }

                    $lead['subscription_status'] = 'active';

                    // Registrar transação
                    $transaction = [
                        'id' => uniqid('trans_'),
                        'user_id' => $lead['id'],
                        // 'amount' => $lastTransaction['amount'],
                        'amount' => intval($valorCobrar * 100),
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
                        date('Y-m-d H:i:s') .
                        " - Recurring charge success for lead {$lead['id']} " .
                        "(cycle_step={$lead['cycle_step']})\n",
                        FILE_APPEND
                    );
                } else {
                    // Falha
                    $lead['subscription_status'] = 'failed';
                    $lead['failure_reason'] = $result['error'] ?? 'Unknown error';

                    $transaction = [
                        'id' => uniqid('trans_'),
                        'user_id' => $lead['id'],
                        // 'amount' => $lastTransaction['amount'],
                        'amount' => intval($valorCobrar * 100),
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
                        date('Y-m-d H:i:s') .
                        " - Recurring charge failed for lead {$lead['id']}: {$lead['failure_reason']}\n",
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
