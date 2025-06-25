<?php
// Acesse em: http://localhost:8080/plugins/aditionalinfo/view_logs.php

$log_file = __DIR__ . '/debug.log';

echo "<html><head><title>Plugin Debug Logs</title>";
echo "<meta http-equiv='refresh' content='10'>";
echo "</head><body>";
echo "<h1>Plugin Additional Info - Debug Logs</h1>";
echo "<p>Atualização automática a cada 10 segundos</p>";
echo "<hr>";

if (file_exists($log_file)) {
  $logs = file_get_contents($log_file);
  if ($logs) {
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    echo htmlspecialchars($logs);
    echo "</pre>";
  } else {
    echo "<p>Arquivo de log vazio.</p>";
  }
} else {
  echo "<p>Arquivo de log não encontrado. O plugin ainda não foi usado ou há problemas de permissão.</p>";
  echo "<p>Caminho esperado: $log_file</p>";
}

echo "<hr>";
echo "<p><a href='?clear=1'>Limpar logs</a> | <a href='?'>Atualizar</a></p>";

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
  if (file_exists($log_file)) {
    unlink($log_file);
    echo "<p style='color: green;'>Logs limpos!</p>";
    echo "<meta http-equiv='refresh' content='1'>";
  }
}

echo "</body></html>";
?>