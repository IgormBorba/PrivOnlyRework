<?php
// Configurar diretório de sessões local
ini_set('session.save_handler', 'files');
ini_set('session.save_path', __DIR__ . '/../sessions');
session_start();

// Log para debug
error_log('Logout attempt - Session ID: ' . session_id());

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header('Location: login.php');
exit; 