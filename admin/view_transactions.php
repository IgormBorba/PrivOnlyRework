<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$leadId = $_GET['lead_id'] ?? 0;
if (!$leadId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid lead ID']);
    exit;
}

$transactions = json_decode(file_get_contents(__DIR__ . '/../data/transactions.json'), true) ?? [];

// Filtrar transações do lead
$leadTransactions = array_filter($transactions, function($t) use ($leadId) {
    return $t['lead_id'] == $leadId;
});

// Ordenar por data (mais recente primeiro)
usort($leadTransactions, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

header('Content-Type: application/json');
echo json_encode(['transactions' => array_values($leadTransactions)]); 