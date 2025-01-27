<?php
session_start();

// Verificação de autenticação básica
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$leads = json_decode(file_get_contents(__DIR__ . '/../data/leads.json'), true) ?? [];
$transactions = json_decode(file_get_contents(__DIR__ . '/../data/transactions.json'), true) ?? [];

$filter = $_GET['filter'] ?? 'all';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Subscription Management</h1>
            <div>
                <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
            </div>
        </div>
        
        <div class="mb-3">
            <a href="?filter=all" class="btn btn-outline-primary">All</a>
            <a href="?filter=active" class="btn btn-outline-success">Active</a>
            <a href="?filter=failed" class="btn btn-outline-danger">Failed</a>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Card</th>
                    <th>Status</th>
                    <th>Next Charge</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                    <?php if ($filter === 'all' || $filter === $lead['subscription_status']): ?>
                    <tr>
                        <td><?= htmlspecialchars($lead['id']) ?></td>
                        <td><?= htmlspecialchars($lead['name']) ?></td>
                        <td><?= htmlspecialchars($lead['email']) ?></td>
                        <td>****<?= htmlspecialchars($lead['card_last4']) ?></td>
                        <td><?= htmlspecialchars($lead['subscription_status']) ?></td>
                        <td><?= htmlspecialchars($lead['next_charge']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewTransactions(<?= $lead['id'] ?>)">
                                View Transactions
                            </button>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function viewTransactions(leadId) {
        fetch(`view_transactions.php?lead_id=${leadId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                let html = `
                    <div class="modal fade" id="transactionsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Transactions History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;
                
                data.transactions.forEach(t => {
                    html += `
                        <tr>
                            <td>${t.id}</td>
                            <td>${t.created_at}</td>
                            <td>$${(t.amount/100).toFixed(2)}</td>
                            <td>${t.status}</td>
                        </tr>
                    `;
                });
                
                html += `
                                        </tbody>
                                    </table>
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
            .catch(err => alert('Error loading transactions'));
    }
    </script>
</body>
</html> 