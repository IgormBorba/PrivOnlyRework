<?php
// salvar_log.php

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega o conteúdo enviado no campo 'log'
    $log = isset($_POST['log']) ? $_POST['log'] : '';

    // Acrescenta no arquivo debug.log
    // (Se não existir, o PHP cria; se existir, faz append)
    file_put_contents(__DIR__ . '/debug.log', $log, FILE_APPEND);

    // Retorna JSON de sucesso
    echo json_encode(['success' => true, 'message' => 'Log gravado']);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
