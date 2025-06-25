<?php
/**
 * Plugin Informações Adicionais para Chamados - Teste PLSS
 */

define('PLUGIN_ADITIONALINFO_VERSION', '1.0.0');

/**
 * Inicializa o plugin e define os hooks necessários.
 */
function plugin_init_aditionalinfo(): void
{
  global $PLUGIN_HOOKS;

  if (function_exists('plugin_aditionalinfo_log')) {
    plugin_aditionalinfo_log("Iniciando o plugin Informações Adicionais para Chamados");
  }

  $PLUGIN_HOOKS['csrf_compliant']['aditionalinfo'] = true;

  $PLUGIN_HOOKS['add_css']['aditionalinfo'] = 'css/additional-info.css';

  $PLUGIN_HOOKS['pre_item_form']['aditionalinfo'] = 'plugin_aditionalinfo_pre_item_form';

  $PLUGIN_HOOKS['item_add']['aditionalinfo'] = 'plugin_aditionalinfo_item_add';
  $PLUGIN_HOOKS['item_update']['aditionalinfo'] = 'plugin_aditionalinfo_item_update';
  $PLUGIN_HOOKS['pre_item_add']['aditionalinfo'] = 'plugin_aditionalinfo_pre_item_add';
  $PLUGIN_HOOKS['pre_item_update']['aditionalinfo'] = 'plugin_aditionalinfo_pre_item_update';

  $PLUGIN_HOOKS['post_init']['aditionalinfo'] = 'plugin_aditionalinfo_post_init';

  $PLUGIN_HOOKS['init']['aditionalinfo'] = 'plugin_aditionalinfo_init_session';

  if (function_exists('plugin_aditionalinfo_log')) {
    plugin_aditionalinfo_log("Plugin Informações Adicionais para Chamados inicializado com sucesso.");
  }
}

/**
 * Informações do plugin.
 */
function plugin_version_aditionalinfo(): array
{
  return [
    'name' => 'Plugin Informações Adicionais para Chamados',
    'version' => PLUGIN_ADITIONALINFO_VERSION,
    'author' => 'Matheus Schmidt',
    'license' => 'GPLv2+',
    'homepage' => '',
    'requirements' => [
      'glpi' => [
        'min' => '10.0',
      ]
    ]
  ];
}

/**
 * Verifica os pré-requisitos do plugin.
 */
function plugin_aditionalinfo_check_prerequisites(): bool
{
  if (version_compare(GLPI_VERSION, '10.0', 'lt')) {
    echo "Este plugin requer GLPI 10.0 ou superior.";
    return false;
  }
  return true;
}

/**
 * Verifica a configuração do plugin.
 */
function plugin_aditionalinfo_check_config($verbose = false): bool
{
  $plugin_dir = GLPI_ROOT . "/plugins/aditionalinfo";
  if (!is_readable($plugin_dir)) {
    if ($verbose) {
      echo "Diretório do plugin não possui permissões de leitura adequadas.";
    }
    return false;
  }

  if (!file_exists($plugin_dir . "/setup.php")) {
    if ($verbose) {
      echo "Arquivo setup.php não encontrado no diretório do plugin.";
    }
    return false;
  }

  return true;
}
?>