<?php

include_once(__DIR__ . '/inc/functions.php');

function plugin_aditionalinfo_ensure_class_loaded(): bool
{
  if (class_exists('PluginAditionalinfoTicket')) {
    plugin_aditionalinfo_log("Classe PluginAditionalinfoTicket já está carregada");
    return true;
  }

  if (!class_exists('CommonDBTM')) {
    plugin_aditionalinfo_log("ERRO: Classe CommonDBTM não está carregada. Certifique-se de que o GLPI está corretamente instalado e configurado.");
    return false;
  }

  $class_file = GLPI_ROOT . '/plugins/aditionalinfo/inc/aditionalinfo.class.php';

  if (file_exists($class_file)) {
    include_once($class_file);
    plugin_aditionalinfo_log("Arquivo de classe encontrado e incluído: $class_file");

    if (class_exists('PluginAditionalinfoTicket')) {
      plugin_aditionalinfo_log("Classe PluginAditionalinfoTicket carregada com sucesso após include");
      return true;
    } else {
      plugin_aditionalinfo_log("ERRO: Classe PluginAditionalinfoTicket não encontrada após incluir o arquivo $class_file");
      return false;
    }
  } else {
    plugin_aditionalinfo_log("ERRO: Arquivo de classe não encontrado: $class_file");
    return false;
  }
}

/**
 * Instala o plugin
 */
function plugin_aditionalinfo_install(): bool
{
  global $DB;

  $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_aditionalinfo_tickets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `tickets_id` int(11) NOT NULL DEFAULT '0',
      `external_responsible` varchar(255) DEFAULT NULL,
      `external_deadline` date DEFAULT NULL,
      `external_status` varchar(50) DEFAULT 'pendente',
      `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `tickets_id` (`tickets_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
  $DB->queryOrDie($query, $DB->error());

  return true;
}

/**
 * Desinstala o plugin
 */
function plugin_aditionalinfo_uninstall(): bool
{
  global $DB;

  $query = "DROP TABLE IF EXISTS `glpi_plugin_aditionalinfo_tickets`";
  $DB->queryOrDie($query, $DB->error());

  return true;
}

/**
 * Carrega o conteúdo do formulário de informações adicionais
 */
function plugin_aditionalinfo_pre_item_form($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    $data = [];

    // Incluir o arquivo CSS
    echo '<link rel="stylesheet" type="text/css" href="' . Plugin::getWebDir('aditionalinfo') . '/css/additional-info.css">';

    if ($ticket_id && $ticket_id > 0) {
      try {
        $additional_info = new PluginAditionalinfoTicket();
        $data = $additional_info->getDataForTicket($ticket_id);

        plugin_aditionalinfo_log("Dados adicionais carregados com sucesso para o ticket ID: $ticket_id" . json_encode($data));

      } catch (Exception $e) {
        plugin_aditionalinfo_log("Erro ao carregar dados adicionais para o ticket ID: $ticket_id - " . $e->getMessage());
      }
    } else {
      plugin_aditionalinfo_log("Novo ticket sendo criado, não há dados adicionais para carregar.");
    }

    echo "<div id='additional-info-section'>";
    echo plugin_aditionalinfo_get_form_content($data);
    echo "</div>";
  }
}

/**
 * Gera o conteúdo do formulário de informações adicionais
 */
function plugin_aditionalinfo_get_form_content($data): string
{
  $external_responsible = $data['external_responsible'] ?? '';
  $external_deadline = $data['external_deadline'] ?? '';
  $external_status = $data['external_status'] ?? 'pendente';

  $content = '
  <div class="additional-info-container">
    <div class="additional-info-title">
      📋 Informações Adicionais
    </div>
    
    <ul class="additional-info-list">
      <li class="additional-info-item">
        <label class="additional-info-label">Responsável<br>Externo</label>
        <div class="additional-info-field">
          <input type="text" 
                 name="external_responsible" 
                 value="' . htmlspecialchars($external_responsible) . '" 
                 placeholder="Nome do responsável externo"
                 class="additional-info-input">
        </div>
      </li>
      
      <li class="additional-info-item">
        <label class="additional-info-label">Status<br>Externo</label>
        <div class="additional-info-field">
          <select name="external_status" class="additional-info-select">
            <option value="pendente"' . ($external_status == 'pendente' ? ' selected' : '') . '>⏳ Pendente</option>
            <option value="em_progresso"' . ($external_status == 'em_progresso' ? ' selected' : '') . '>🔄 Em Progresso</option>
            <option value="concluido"' . ($external_status == 'concluido' ? ' selected' : '') . '>✅ Concluído</option>
          </select>
        </div>
      </li>
      
      <li class="additional-info-item">
        <label class="additional-info-label">Prazo de<br>Atendimento</label>
        <div class="additional-info-field">
          <input type="date" 
                 name="external_deadline" 
                 value="' . htmlspecialchars($external_deadline) . '"
                 class="additional-info-input">
        </div>
      </li>
    </ul>
  </div>';

  return $content;
}

/**
 * Pré-processa o formulário antes de adicionar um item
 */
function plugin_aditionalinfo_item_add($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();

    if (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status'])) {
      plugin_aditionalinfo_save_data($ticket_id);
    } elseif (isset($_SESSION['plugin_aditionalinfo_temp'])) {
      plugin_aditionalinfo_log("Usando dados temporários da sessão para o ticket $ticket_id");
      plugin_aditionalinfo_log("Dados temporários: " . json_encode($_SESSION['plugin_aditionalinfo_temp']));

      if (plugin_aditionalinfo_ensure_class_loaded()) {
        $additional_info = new PluginAditionalinfoTicket();
        $data = $_SESSION['plugin_aditionalinfo_temp'];
        $data['tickets_id'] = $ticket_id;

        $result = $additional_info->saveDataForTicket($data);
        plugin_aditionalinfo_log("Dados adicionais salvos para o ticket ID: $ticket_id - Resultado: " . ($result ? 'sucesso' : 'falha'));

        if ($result) {
          unset($_SESSION['plugin_aditionalinfo_temp']);
          plugin_aditionalinfo_log("Dados temporários removidos da sessão após salvar para o ticket $ticket_id");
        }
      } else {
        plugin_aditionalinfo_log("ERRO: Não foi possível carregar a classe PluginAditionalinfoTicket para salvar dados temporários do ticket $ticket_id");
      }
    } else {
      plugin_aditionalinfo_log("ERRO: Nenhum dado adicional encontrado para salvar no ticket $ticket_id");
    }
  }
}

/**
 * Atualiza os dados adicionais de um item
 */
function plugin_aditionalinfo_item_update($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    plugin_aditionalinfo_save_data($ticket_id);
  }
}

/**
 * Processa os dados antes de criar um ticket
 */
function plugin_aditionalinfo_pre_item_add($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    if (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status'])) {
      $_SESSION['plugin_aditionalinfo_temp'] = [
        'external_responsible' => $_POST['external_responsible'] ?? '',
        'external_deadline' => (!empty($_POST['external_deadline'])) ? $_POST['external_deadline'] : null,
        'external_status' => $_POST['external_status'] ?? 'pendente'
      ];
      plugin_aditionalinfo_log("Dados temporários salvos em pre_item_add: " . json_encode($_SESSION['plugin_aditionalinfo_temp']));
    } else {
      plugin_aditionalinfo_log("Nenhum campo do plugin encontrado no POST durante pre_item_add");
    }
  }
}

/**
 * Processa os dados antes de atualizar um ticket
 */
function plugin_aditionalinfo_pre_item_update($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    plugin_aditionalinfo_save_data($ticket_id);
  }
}

/**
 * Salva os dados adicionais de um ticket
 */
function plugin_aditionalinfo_save_data($ticket_id): bool
{
  if (
    isset($_POST['external_responsible']) ||
    isset($_POST['external_deadline']) ||
    isset($_POST['external_status'])
  ) {

    try {
      if (!plugin_aditionalinfo_ensure_class_loaded()) {
        plugin_aditionalinfo_log("FATAL: Não foi possível carregar a classe PluginAditionalinfoTicket em save_data");
        return false;
      }

      $additional_info = new PluginAditionalinfoTicket();

      $data = [
        'tickets_id' => $ticket_id,
        'external_responsible' => $_POST['external_responsible'] ?? '',
        'external_deadline' => (!empty($_POST['external_deadline'])) ? $_POST['external_deadline'] : null,
        'external_status' => $_POST['external_status'] ?? 'pendente'
      ];

      $result = $additional_info->saveDataForTicket($data);
      plugin_aditionalinfo_log("Dados salvos com sucesso para o ticket $ticket_id, resultado: " . ($result ? 'true' : 'false'));

      return true;

    } catch (Exception $e) {
      plugin_aditionalinfo_log("Erro ao salvar dados: " . $e->getMessage());
      return false;
    }
  } else {
    plugin_aditionalinfo_log("Nenhum dado de informação adicional encontrado no POST para o ticket $ticket_id");
    return false;
  }
}

/**
 * Verifica se os hooks estão registrados corretamente após a inicialização do plugin
 */
function plugin_aditionalinfo_post_init(): void
{
  plugin_aditionalinfo_ensure_class_loaded();

  global $PLUGIN_HOOKS;
  if (isset($PLUGIN_HOOKS['item_add']['aditionalinfo'])) {
    plugin_aditionalinfo_log("Hook item_add está registrado");
  } else {
    plugin_aditionalinfo_log("ERRO: Hook item_add NÃO está registrado");
  }

  if (isset($PLUGIN_HOOKS['item_update']['aditionalinfo'])) {
    plugin_aditionalinfo_log("Hook item_update está registrado");
  } else {
    plugin_aditionalinfo_log("ERRO: Hook item_update NÃO está registrado");
  }
}

?>