<?php
// Configurar diretório de sessões local
ini_set('session.save_handler', 'files');
ini_set('session.save_path', __DIR__ . '/../sessions');
session_start();

// Log para debug
error_log('Logout attempt - Session ID: ' . session_id());

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header('Location: login.php');
exit; 