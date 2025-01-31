<?php
// Verificar se o ID do usuário foi fornecido
if (!isset($_GET['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$user_id = $_GET['user_id'];

// Carregar dados do usuário
$users_data = [];
if (file_exists(__DIR__ . '/../../data/users.json')) {
    $users_data = json_decode(file_get_contents(__DIR__ . '/../../data/users.json'), true) ?? [];
}

// Encontrar o usuário específico
$user = null;
foreach ($users_data as $u) {
    if ($u['id'] == $user_id) {
        $user = $u;
        break;
    }
}

if (!$user) {
    $_SESSION['message'] = 'Usuário não encontrado.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php?page=dashboard');
    exit;
}

// Carregar transações do usuário
$transactions = [];
if (file_exists(__DIR__ . '/../../data/transactions.json')) {
    $all_transactions = json_decode(file_get_contents(__DIR__ . '/../../data/transactions.json'), true) ?? [];
    $transactions = array_filter($all_transactions, fn($t) => $t['user_id'] == $user_id);
    // Ordenar por data (mais recente primeiro)
    usort($transactions, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">
                            Transações do Usuário: <?php echo htmlspecialchars($user['name']); ?>
                        </h4>
                        <a href="index.php?page=dashboard" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Voltar
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID da Transação</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Tipo</th>
                                    <th>Método</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Nenhuma transação encontrada.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($transaction['created_at'])); ?></td>
                                    <td>R$ <?php echo number_format($transaction['amount'] / 100, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['status'] === 'approved' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($transaction['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewTransactionDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                            <i class="bi bi-info-circle"></i>
                                            Detalhes
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
</div>

<!-- Modal para detalhes da transação -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Transação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewTransactionDetails(transaction) {
    const modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
    const detailsDiv = document.getElementById('transactionDetails');
    
    const statusClass = transaction.status === 'approved' ? 'success' : 'danger';
    const formattedAmount = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(transaction.amount / 100);
    
    detailsDiv.innerHTML = `
        <dl class="row">
            <dt class="col-sm-4">ID:</dt>
            <dd class="col-sm-8">${transaction.id}</dd>
            
            <dt class="col-sm-4">Data:</dt>
            <dd class="col-sm-8">${new Date(transaction.created_at).toLocaleString('pt-BR')}</dd>
            
            <dt class="col-sm-4">Valor:</dt>
            <dd class="col-sm-8">${formattedAmount}</dd>
            
            <dt class="col-sm-4">Status:</dt>
            <dd class="col-sm-8">
                <span class="badge bg-${statusClass}">${transaction.status}</span>
            </dd>
            
            <dt class="col-sm-4">Tipo:</dt>
            <dd class="col-sm-8">${transaction.type}</dd>
            
            <dt class="col-sm-4">Método:</dt>
            <dd class="col-sm-8">
                <span class="badge bg-info">${transaction.payment_method}</span>
            </dd>
            
            ${transaction.error_message ? `
                <dt class="col-sm-4">Erro:</dt>
                <dd class="col-sm-8 text-danger">${transaction.error_message}</dd>
            ` : ''}
            
            ${transaction.gateway_response ? `
                <dt class="col-sm-4">Gateway:</dt>
                <dd class="col-sm-8">
                    <pre class="mb-0"><code>${JSON.stringify(transaction.gateway_response, null, 2)}</code></pre>
                </dd>
            ` : ''}
        </dl>
    `;
    
    modal.show();
}
</script>
