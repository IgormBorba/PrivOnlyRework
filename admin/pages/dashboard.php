<?php
// Processar atualização do intervalo de recorrência
// (Mantemos o código PHP referente ao recurring_interval comentado,
// caso queira reaproveitar. Mas removemos o CARD do HTML.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recurring_interval'])) {
    $interval = (int)$_POST['recurring_interval'];
    if ($interval > 0) {
        // ====== SEU CÓDIGO ORIGINAL ABAIXO (COMENTADO) ======
        // $envContent = file_get_contents(__DIR__ . '/../../.env');
        // $envContent = preg_replace(
        //     '/RECURRING_INTERVAL=\d+/',
        //     'RECURRING_INTERVAL=' . $interval,
        //     $envContent
        // );
        // if (file_put_contents(__DIR__ . '/../../.env', $envContent)) {
        //     $_SESSION['message'] = 'Intervalo de recorrência atualizado com sucesso!';
        //     $_SESSION['message_type'] = 'success';
        // } else {
        //     $_SESSION['message'] = 'Erro ao atualizar o intervalo de recorrência.';
        //     $_SESSION['message_type'] = 'danger';
        // }
        // header('Location: index.php?page=dashboard');
        // exit;

        /*
         * Aqui adicionamos a lógica para inserir ou atualizar a linha
         * RECURRING_INTERVAL= no .env caso ela não exista.
         */
        $envPath = __DIR__ . '/../.env';  // Ajustado para o .env que está em /admin
        $envContent = file_get_contents($envPath);

        if (!preg_match('/RECURRING_INTERVAL=\d+/', $envContent)) {
            // Se não existir, adiciona ao final
            $envContent .= "\nRECURRING_INTERVAL={$interval}\n";
        } else {
            // Se existir, substitui
            $envContent = preg_replace(
                '/RECURRING_INTERVAL=\d+/',
                'RECURRING_INTERVAL=' . $interval,
                $envContent
            );
        }

        if (file_put_contents($envPath, $envContent)) {
            $_SESSION['message'] = 'Intervalo de recorrência atualizado com sucesso!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Erro ao atualizar o intervalo de recorrência.';
            $_SESSION['message_type'] = 'danger';
        }

        header('Location: index.php?page=dashboard');
        exit;
    }
}

/*
 * AQUI ADICIONAMOS OS 4 NOVOS CAMPOS: price_7, price_14, price_21, price_28
 * para salvar RECURRING_7_VALUE, RECURRING_14_VALUE, RECURRING_21_VALUE, RECURRING_28_VALUE
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['price_7'], $_POST['price_14'], $_POST['price_21'], $_POST['price_28'])) 
{
    // Convertemos para float
    $val7  = (float)$_POST['price_7'];
    $val14 = (float)$_POST['price_14'];
    $val21 = (float)$_POST['price_21'];
    $val28 = (float)$_POST['price_28'];

    // Ajustado para apontar para o .env dentro de /admin
    $envPath = __DIR__ . '/../.env';
    $envContent = file_get_contents($envPath);

    // Função genérica para inserir ou atualizar no .env
    function setEnvValue(&$content, $key, $value) {
        // Caso a linha não exista, adiciona, senão substitui com preg_replace
        if (!preg_match("/^{$key}=/m", $content)) {
            $content .= "\n{$key}={$value}\n";
        } else {
            $content = preg_replace(
                "/^{$key}=.*$/m",
                "{$key}={$value}",
                $content
            );
        }
    }

    // Seta cada valor no .env
    setEnvValue($envContent, 'RECURRING_7_VALUE',  $val7);
    setEnvValue($envContent, 'RECURRING_14_VALUE', $val14);
    setEnvValue($envContent, 'RECURRING_21_VALUE', $val21);
    setEnvValue($envContent, 'RECURRING_28_VALUE', $val28);

    if (file_put_contents($envPath, $envContent)) {
        $_SESSION['message'] = 'Valores de recorrência (7,14,21,28) salvos com sucesso!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erro ao salvar valores de recorrência.';
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: index.php?page=dashboard');
    exit;
}

// Carregar dados dos usuários
$users_data = [];
if (file_exists(__DIR__ . '/../../data/leads.json')) {
    $users_data = json_decode(file_get_contents(__DIR__ . '/../../data/leads.json'), true) ?? [];
}

// Carregar transações
$transactions = [];
if (file_exists(__DIR__ . '/../../data/transactions.json')) {
    $transactions = json_decode(file_get_contents(__DIR__ . '/../../data/transactions.json'), true) ?? [];
}

// Atualizar dados dos usuários com informações das transações
foreach ($users_data as &$user) {
    $user_transactions = array_filter($transactions, function($t) use ($user) {
        return $t['user_id'] === $user['id'];
    });
    
    $user['transaction_count'] = count($user_transactions);
    $user['total_spent'] = array_sum(array_column($user_transactions, 'amount'));
    
    // Encontrar última transação
    if (!empty($user_transactions)) {
        usort($user_transactions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $last_transaction = reset($user_transactions);
        $user['last_transaction_date'] = $last_transaction['created_at'];
        $user['last_transaction_status'] = $last_transaction['status'];
    }
}
unset($user); // Limpar referência

// Calcular estatísticas
$total_users = count($users_data);
$active_subscriptions = count(array_filter($users_data, fn($u) => $u['subscription_status'] === 'active'));
$total_transactions = count($transactions);
$total_revenue = array_sum(array_column($transactions, 'amount'));

// Carregar configurações
$env = parse_ini_file(__DIR__ . '/../.env');
$recurring_interval = $env['RECURRING_INTERVAL'] ?? 7;

/* 4 valores */
$r7  = $env['RECURRING_7_VALUE']  ?? 0;
$r14 = $env['RECURRING_14_VALUE'] ?? 0;
$r21 = $env['RECURRING_21_VALUE'] ?? 0;
$r28 = $env['RECURRING_28_VALUE'] ?? 0;
?>

<!-- 
    ======= Estilo custom: TEMA ESCURO, texto SEMPRE claro =======
    (NÃO REMOVEMOS LINHAS, APENAS CSS para forçar texto branco)
-->
<style>
@import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

* {
    font-family: 'Share Tech Mono', monospace;
    box-sizing: border-box;
}

/* Fundo global bem escuro */
body {
    background: #0A0A0A !important;
    color: #EEE !important;
}

/* Container */
.container, .container-fluid {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}
[class*="col-"] {
    padding: 0.5rem;
}

/* Cartões e cabeçalhos */
.card {
    background: #161616;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    color: #EEE !important;
}
.card-body {
    background: #1E1E1E;
    border-radius: 10px;
}
.card-title {
    color: #FF4444 !important;
    font-weight: 600;
    font-size: 1.3rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.card-title i {
    color: #FF6C6C !important;
}

/* Botões */
.btn-primary, .btn-info {
    border: none;
    font-weight: 600;
    outline: none;
    box-shadow: none;
    color: #fff !important;
}
.btn-primary {
    background: linear-gradient(45deg, #BF0000, #FF4444) !important;
}
.btn-primary:hover {
    background: linear-gradient(45deg, #9e0000, #f13131) !important;
}
.btn-info {
    background: linear-gradient(45deg, #00ff00, #44ff44) !important;
    color: #111 !important;
}
.btn-info:hover {
    background: linear-gradient(45deg, #0c0, #22dd22) !important;
    color: #111 !important;
}

/* Form */
.form-label {
    color: #FF5555 !important;
    margin-bottom: 0.4rem;
    font-weight: 500;
}
.form-control {
    background: #111 !important;
    color: #EEE !important;
    border: 1px solid #333 !important;
}
.form-control:focus {
    box-shadow: 0 0 3px #FF4444 !important;
}

/* Cards do resumo */
.bg-light {
    background-color: #242424 !important;
    color: #EEE !important;
    border: 1px solid #444 !important;
}
.bg-light h5 {
    color: #FF4444 !important;
    font-weight: 600;
}
.bg-light h2 {
    color: #fff !important; 
    font-size: 1.8rem; 
    margin-top: 0.5rem;
}

/* Tabela: text = #EEE, fundo escuro */
.table {
    color: #EEE !important;
}
.table-responsive {
    border-radius: 8px;
    overflow: auto;
}
.table-dark th {
    background-color: #2a2a2a !important;
    color: #EEE !important;
    border: none !important;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: #1a1a1a !important;
}
.table-hover tbody tr:hover {
    background-color: #252525 !important;
}
/* Para não pintar a linha toda de verde */
.table-success {
    background: none !important;
    color: #EEE !important;
}

/* A cor do link no email */
.table a {
    color: #ff9999 !important;
}

/* Badges */
.badge-success {
    background: #44ff44 !important;
    color: #111 !important;
}
.badge-danger {
    background: #ff4444 !important;
    color: #111 !important;
}

.text-muted {
    color: #999 !important;
}
.text-primary {
    color: #66aaff !important;
}
.fw-bold {
    font-weight: 600 !important;
}

/* Modal */
.modal-content {
    background: #242424 !important; 
    color: #EEE !important;
    border: 1px solid #444 !important;
    border-radius: 8px !important;
}
.modal-header {
    border-bottom: 1px solid #444 !important;
}
.modal-header .modal-title {
    color: #FF4444 !important; 
    font-size: 1.2rem !important;
}
.btn-close {
    background: none; 
    border: none;
}
</style>
<!-- ========== FIM ESTILO CUSTOM ========== -->

<!-- 
    Removido o card "Configuração de Recorrência (em dias)"
    que existia originalmente no HTML
    (mantivemos o código PHP dele no topo apenas comentado)
-->

<!-- Novos campos: 7,14,21,28 dias -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">
                    <i class="bi bi-cash-coin"></i>
                    Valores de Recorrência
                </h4>
                <form method="POST" class="row align-items-end">
                    <div class="col-md-3">
                        <label for="price_7" class="form-label">Valor (7 dias)</label>
                        <input type="number" step="0.01" class="form-control" name="price_7" id="price_7"
                               value="<?php echo $r7; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="price_14" class="form-label">Valor (14 dias)</label>
                        <input type="number" step="0.01" class="form-control" name="price_14" id="price_14"
                               value="<?php echo $r14; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="price_21" class="form-label">Valor (21 dias)</label>
                        <input type="number" step="0.01" class="form-control" name="price_21" id="price_21"
                               value="<?php echo $r21; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="price_28" class="form-label">Valor (28 dias)</label>
                        <input type="number" step="0.01" class="form-control" name="price_28" id="price_28"
                               value="<?php echo $r28; ?>">
                    </div>
                    
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i>
                            Salvar Valores
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- FIM dos campos de 7,14,21,28 -->

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    Resumo
                </h4>
                <div class="row text-center gy-3">
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body py-4">
                                <h5>Total de Usuários</h5>
                                <h2 class="mb-0"><?php echo $total_users; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body py-4">
                                <h5>Assinaturas Ativas</h5>
                                <h2 class="mb-0"><?php echo $active_subscriptions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body py-4">
                                <h5>Total de Transações</h5>
                                <h2 class="mb-0"><?php echo $total_transactions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body py-4">
                                <h5>Valor Total (R$)</h5>
                                <h2 class="mb-0"><?php echo number_format($total_revenue / 100, 2, ',', '.'); ?></h2>
                            </div>
                        </div>
                    </div>
                </div><!-- row -->
            </div>
        </div>
    </div>
</div>

<!-- Lista de Usuários -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="bi bi-people-fill"></i>
                    Cartões e Usuários Ativos
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Documento</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Dados do Cartão</th>
                                <th>Status</th>
                                <th>Próxima Cobrança</th>
                                <th>Data de Cadastro</th>
                                <th>Total Gasto</th>
                                <th>Última Transação</th>
                                <th>Status da Última Transação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users_data)): ?>
                            <tr>
                                <td colspan="14" class="text-center text-muted fw-bold">Nenhum usuário cadastrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users_data as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($user['document_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewCardDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        Ver Cartão
                                    </button>
                                </td>
                                <td>
                                    <?php if ($user['subscription_status'] === 'active'): ?>
                                        <span class="badge badge-success">active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['subscription_status'] === 'active'): ?>
                                        <span class="text-primary fw-bold">
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo htmlspecialchars($user['next_charge']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td>R$ <?php echo number_format($user['total_spent'] / 100, 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($user['last_transaction_date']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($user['last_transaction_status'] === 'approved') ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($user['last_transaction_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewTransactions(<?php echo $user['id']; ?>)">
                                        <i class="bi bi-list-ul"></i>
                                        Transações
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div> <!-- table-responsive -->
            </div> <!-- card-body -->
        </div> <!-- card -->
    </div> <!-- col-12 -->
</div> <!-- row -->

<!-- Modal para detalhes do cartão -->
<div class="modal fade" id="cardDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#242424; color:#EEE; border:1px solid #444;">
            <div class="modal-header" style="border-bottom:1px solid #444;">
                <h5 class="modal-title" style="color:#FF4444;">
                    Detalhes do Cartão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="background:none;"></button>
            </div>
            <div class="modal-body">
                <div id="cardDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewCardDetails(user) {
    const modal = new bootstrap.Modal(document.getElementById('cardDetailsModal'));
    const detailsDiv = document.getElementById('cardDetails');
    
    detailsDiv.innerHTML = `
        <div class="p-3">
            <p><strong>Titular:</strong> ${user.card_holder_name}</p>
            <p><strong>Número:</strong> **** **** **** ${user.card_last4}</p>
            <p><strong>Validade:</strong> ${user.card_expiration}</p>
            <p><strong>Bandeira:</strong> ${user.card_brand}</p>
        </div>
    `;
    
    modal.show();
}

function viewTransactions(userId) {
    window.location.href = \`index.php?page=view_transactions&user_id=\${userId}\`;
}
</script>
