<?php
// Processar atualização do intervalo de recorrência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recurring_interval'])) {
    $interval = (int)$_POST['recurring_interval'];
    if ($interval > 0) {
        $envContent = file_get_contents(__DIR__ . '/../../.env');
        $envContent = preg_replace(
            '/RECURRING_INTERVAL=\d+/',
            'RECURRING_INTERVAL=' . $interval,
            $envContent
        );
        if (file_put_contents(__DIR__ . '/../../.env', $envContent)) {
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
$env = parse_ini_file(__DIR__ . '/../../.env');
$recurring_interval = $env['RECURRING_INTERVAL'] ?? 7;
?>

<!-- Formulário de Configuração de Recorrência -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Configuração de Recorrência</h4>
                <form method="POST" class="row align-items-end">
                    <div class="col-md-4">
                        <label for="recurring_interval" class="form-label">Intervalo de Recorrência (em dias)</label>
                        <input type="number" 
                               class="form-control" 
                               id="recurring_interval" 
                               name="recurring_interval" 
                               min="1" 
                               value="<?php echo $recurring_interval; ?>" 
                               required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i>
                            Salvar Configuração
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resumo</h4>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>Total de Usuários</h5>
                                <h2><?php echo $total_users; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>Assinaturas Ativas</h5>
                                <h2><?php echo $active_subscriptions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>Total de Transações</h5>
                                <h2><?php echo $total_transactions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>Valor Total (R$)</h5>
                                <h2><?php echo number_format($total_revenue / 100, 2, ',', '.'); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Usuários -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Cartões e Usuários Ativos</h4>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                <td colspan="14" class="text-center">Nenhum usuário cadastrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users_data as $user): ?>
                            <tr class="<?php echo $user['subscription_status'] === 'active' ? 'table-success' : ''; ?>">
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
                                    <span class="badge bg-<?php echo $user['subscription_status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($user['subscription_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['subscription_status'] === 'active'): ?>
                                        <span class="text-primary">
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
                                    <span class="badge bg-<?php echo $user['last_transaction_status'] === 'approved' ? 'success' : 'danger'; ?>">
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalhes do cartão -->
<div class="modal fade" id="cardDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        <p><strong>Titular:</strong> ${user.card_holder_name}</p>
        <p><strong>Número:</strong> **** **** **** ${user.card_last4}</p>
        <p><strong>Validade:</strong> ${user.card_expiration}</p>
        <p><strong>Bandeira:</strong> ${user.card_brand}</p>
    `;
    
    modal.show();
}

function viewTransactions(userId) {
    window.location.href = `index.php?page=view_transactions&user_id=${userId}`;
}
</script> 