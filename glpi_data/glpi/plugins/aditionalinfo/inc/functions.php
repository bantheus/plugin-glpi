<?php

/**
 * Função de log
 */
function plugin_aditionalinfo_log($message): void
{
  $log_file = GLPI_ROOT . '/plugins/aditionalinfo/debug.log';
  $timestamp = date('d-m-Y H:i:s');
  file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

?>