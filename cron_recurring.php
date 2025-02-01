<?php
// Este arquivo deve ser executado via CRON diariamente
// Exemplo de configuração CRON:
// 0 0 * * * php /path/to/cron_recurring.php

require_once __DIR__ . '/recurring_charge.php';

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Executa o processamento de cobranças
processAllPendingCharges(); 