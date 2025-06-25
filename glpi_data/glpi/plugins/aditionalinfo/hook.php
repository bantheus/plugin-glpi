<?php

/**
 * Log
 */
function plugin_aditionalinfo_log($message): void
{
  $log_file = GLPI_ROOT . '/plugins/aditionalinfo/debug.log';
  $timestamp = date('d/m/Y H:i:s');
  file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

/**
 * Função para garantir que a classe seja carregada
 */
function plugin_aditionalinfo_ensure_class_loaded(): bool
{
  if (class_exists('PluginAditionalinfoTicket')) {
    plugin_aditionalinfo_log("Classe PluginAditionalinfoTicket já está carregada");
    return true;
  }

  if (!class_exists('CommonDBTM')) {
    plugin_aditionalinfo_log("ERRO: CommonDBTM não disponível - GLPI não foi inicializado corretamente");
    return false;
  }

  $class_file = GLPI_ROOT . '/plugins/aditionalinfo/ticketadditionalinfo.class.php';

  if (file_exists($class_file)) {
    include_once($class_file);
    plugin_aditionalinfo_log("Arquivo de classe incluído: $class_file");

    if (class_exists('PluginAditionalinfoTicket')) {
      plugin_aditionalinfo_log("Classe PluginAditionalinfoTicket carregada com sucesso");
      return true;
    } else {
      plugin_aditionalinfo_log("ERRO: Classe ainda não disponível após include");
      return false;
    }
  } else {
    plugin_aditionalinfo_log("ERRO: Arquivo de classe não encontrado: $class_file");
    return false;
  }
}

/**
 * Install plugin
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
 * Uninstall plugin
 */
function plugin_aditionalinfo_uninstall(): bool
{
  global $DB;

  $query = "DROP TABLE IF EXISTS `glpi_plugin_aditionalinfo_tickets`";
  $DB->queryOrDie($query, $DB->error());

  return true;
}

/**
 * Hook pre_item_form - Adiciona aba ao formulário
 */
function plugin_aditionalinfo_pre_item_form($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    $data = [];

    plugin_aditionalinfo_log("Processando ticket ID: $ticket_id");

    if ($ticket_id && $ticket_id > 0) {
      try {
        $additional_info = new PluginAditionalinfoTicket();
        $data = $additional_info->getDataForTicket($ticket_id);

        plugin_aditionalinfo_log("Dados carregados para o ticket $ticket_id: " . json_encode($data));
      } catch (Exception $e) {
        plugin_aditionalinfo_log("Erro ao carregar dados: " . $e->getMessage());
      }
    } else {
      plugin_aditionalinfo_log("Criação de novo ticket, nenhum dado para carregar");
    }

    echo "<div id='additional-info-section'>";
    echo plugin_aditionalinfo_get_form_content($data);
    echo "</div>";
  }
}

/**
 * Gerar conteúdo do formulário
 */
function plugin_aditionalinfo_get_form_content($data): string
{
  $external_responsible = isset($data['external_responsible']) ? $data['external_responsible'] : '';
  $external_deadline = isset($data['external_deadline']) ? $data['external_deadline'] : '';
  $external_status = isset($data['external_status']) ? $data['external_status'] : 'pendente';

  plugin_aditionalinfo_log("Valores do formulário: responsible='$external_responsible', deadline='$external_deadline', status='$external_status'");

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
 * Hook item_add - Processar dados ao criar ticket
 */
function plugin_aditionalinfo_item_add($params): void
{
  plugin_aditionalinfo_log("item_add chamado para: " . $params['item']->getType());

  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    plugin_aditionalinfo_log("item_add processando ticket ID: $ticket_id");

    if (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status'])) {
      plugin_aditionalinfo_log("Campos do plugin encontrados no POST durante item_add");
      plugin_aditionalinfo_save_data($ticket_id);
    } elseif (isset($_SESSION['plugin_aditionalinfo_temp'])) {
      plugin_aditionalinfo_log("Usando dados temporários da sessão para o ticket $ticket_id");
      plugin_aditionalinfo_log("Dados da sessão: " . json_encode($_SESSION['plugin_aditionalinfo_temp']));

      if (plugin_aditionalinfo_ensure_class_loaded()) {
        $additional_info = new PluginAditionalinfoTicket();
        $data = $_SESSION['plugin_aditionalinfo_temp'];
        $data['tickets_id'] = $ticket_id;

        $result = $additional_info->saveDataForTicket($data);
        plugin_aditionalinfo_log("Resultado do salvamento dos dados da sessão: " . ($result ? 'sucesso' : 'falhou'));

        if ($result) {
          unset($_SESSION['plugin_aditionalinfo_temp']);
          plugin_aditionalinfo_log("Dados da sessão limpos com sucesso");
        }
      } else {
        plugin_aditionalinfo_log("ERRO: Não é possível carregar a classe em item_add");
      }
    } else {
      plugin_aditionalinfo_log("Nenhum dado do plugin encontrado no POST ou sessão para o ticket $ticket_id");
    }
  }
}

/**
 * Hook item_update - Processar dados ao atualizar ticket
 */
function plugin_aditionalinfo_item_update($params): void
{
  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    plugin_aditionalinfo_save_data($ticket_id);
  }
}

/**
 * Hook pre_item_add - Processar dados antes de criar ticket
 */
function plugin_aditionalinfo_pre_item_add($params): void
{
  plugin_aditionalinfo_log("pre_item_add chamado para: " . $params['item']->getType());
  plugin_aditionalinfo_log("Dados POST em pre_item_add: " . json_encode($_POST));

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
 * Hook pre_item_update - Processar dados antes de atualizar ticket
 */
function plugin_aditionalinfo_pre_item_update($params): void
{
  plugin_aditionalinfo_log("pre_item_update chamado para: " . $params['item']->getType());

  if ($params['item']->getType() == 'Ticket') {
    $ticket_id = $params['item']->getID();
    plugin_aditionalinfo_save_data($ticket_id);
  }
}

/**
 * Salvar dados adicionais
 */
function plugin_aditionalinfo_save_data($ticket_id): bool
{
  plugin_aditionalinfo_log("save_data chamado para o ticket $ticket_id");
  plugin_aditionalinfo_log("Dados POST: " . json_encode($_POST));

  if (
    isset($_POST['external_responsible']) ||
    isset($_POST['external_deadline']) ||
    isset($_POST['external_status'])
  ) {

    plugin_aditionalinfo_log("Campos de informações adicionais encontrados no POST para o ticket $ticket_id");

    try {
      if (!plugin_aditionalinfo_ensure_class_loaded()) {
        plugin_aditionalinfo_log("FATAL: Não é possível carregar a classe PluginAditionalinfoTicket em save_data");
        return false;
      }

      $additional_info = new PluginAditionalinfoTicket();

      $data = [
        'tickets_id' => $ticket_id,
        'external_responsible' => $_POST['external_responsible'] ?? '',
        'external_deadline' => (!empty($_POST['external_deadline'])) ? $_POST['external_deadline'] : null,
        'external_status' => $_POST['external_status'] ?? 'pendente'
      ];

      plugin_aditionalinfo_log("Dados processados: " . json_encode($data));

      $result = $additional_info->saveDataForTicket($data);
      plugin_aditionalinfo_log("Dados salvos com sucesso para o ticket $ticket_id, resultado: " . ($result ? 'true' : 'false'));

      return true;

    } catch (Exception $e) {
      plugin_aditionalinfo_log("Erro ao salvar dados: " . $e->getMessage());
      plugin_aditionalinfo_log("Rastreamento da pilha: " . $e->getTraceAsString());
      return false;
    }
  } else {
    plugin_aditionalinfo_log("Nenhum dado de informação adicional encontrado no POST para o ticket $ticket_id");
    return false;
  }
}

/**
 * Hook post_init - Inicialização
 */
function plugin_aditionalinfo_post_init(): void
{
  plugin_aditionalinfo_log("post_init chamado - carregando plugin");

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

/**
 * Hook init - Carregar traduções
 */
function plugin_aditionalinfo_init_session(): void
{
  global $CFG_GLPI;

  $lang = $_SESSION['glpilanguage'] ?? 'pt_BR';
  $lang_file = GLPI_ROOT . "/plugins/aditionalinfo/locales/$lang.php";

  if (is_readable($lang_file)) {
    include_once($lang_file);
  } elseif (is_readable(GLPI_ROOT . "/plugins/aditionalinfo/locales/en_GB.php")) {
    include_once(GLPI_ROOT . "/plugins/aditionalinfo/locales/en_GB.php");
  }
}

/**
 * Hook alternativo - capturar qualquer ação de ticket
 */
function plugin_aditionalinfo_capture_ticket_action(): void
{
  if (isset($_POST) && !empty($_POST)) {
    if (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status'])) {
      plugin_aditionalinfo_log("POST com campos do plugin detectado: " . json_encode($_POST));
    }

    $is_ticket_action = false;
    $action_type = '';

    if (isset($_POST['itemtype']) && $_POST['itemtype'] == 'Ticket') {
      $is_ticket_action = true;
      $action_type = 'itemtype';
      plugin_aditionalinfo_log("Ação de ticket detectada via itemtype");
    } elseif (
      isset($_POST['id']) && isset($_POST['update']) &&
      (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status']))
    ) {
      $is_ticket_action = true;
      $action_type = 'update';
      plugin_aditionalinfo_log("Ação de atualização de ticket detectada via id+update+campos");
    } elseif (
      isset($_POST['add']) &&
      (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status']))
    ) {
      $is_ticket_action = true;
      $action_type = 'add';
      plugin_aditionalinfo_log("Ação de adição de ticket detectada via add+campos");
    }

    if ($is_ticket_action) {
      if (isset($_POST['external_responsible']) || isset($_POST['external_deadline']) || isset($_POST['external_status'])) {
        plugin_aditionalinfo_log("Campos do plugin detectados no POST - tipo de ação: $action_type");

        if ($action_type == 'add') {
          $_SESSION['plugin_aditionalinfo_temp'] = [
            'external_responsible' => $_POST['external_responsible'] ?? '',
            'external_deadline' => (!empty($_POST['external_deadline'])) ? $_POST['external_deadline'] : null,
            'external_status' => $_POST['external_status'] ?? 'pendente'
          ];
          plugin_aditionalinfo_log("Alternativo: Dados temporários salvos para criação: " . json_encode($_SESSION['plugin_aditionalinfo_temp']));

          register_shutdown_function('plugin_aditionalinfo_process_pending_creation');

        } elseif (isset($_POST['id']) && $_POST['id'] > 0) {
          plugin_aditionalinfo_log("Alternativo: Salvando dados para o ticket ID: " . $_POST['id']);
          plugin_aditionalinfo_direct_save($_POST['id']);
        }
      }
    }
  }
}

/**
 * Processar dados pendentes de criação
 */
function plugin_aditionalinfo_process_pending_creation(): void
{
  if (isset($_SESSION['plugin_aditionalinfo_temp'])) {
    plugin_aditionalinfo_log("Processando dados de criação pendentes");

    global $DB;

    try {
      $query = "SELECT id FROM glpi_tickets ORDER BY id DESC LIMIT 1";
      $result = $DB->query($query);

      if ($result && $DB->numrows($result) > 0) {
        $row = $DB->fetchAssoc($result);
        $ticket_id = $row['id'];

        plugin_aditionalinfo_log("ID do ticket mais recente encontrado: $ticket_id");

        if (plugin_aditionalinfo_ensure_class_loaded()) {
          $additional_info = new PluginAditionalinfoTicket();
          $data = $_SESSION['plugin_aditionalinfo_temp'];
          $data['tickets_id'] = $ticket_id;

          $result = $additional_info->saveDataForTicket($data);
          plugin_aditionalinfo_log("Resultado do salvamento de dados pendentes: " . ($result ? 'sucesso' : 'falhou'));

          if ($result) {
            unset($_SESSION['plugin_aditionalinfo_temp']);
            plugin_aditionalinfo_log("Dados da sessão pendentes limpos");
          }
        }
      }
    } catch (Exception $e) {
      plugin_aditionalinfo_log("Erro ao processar criação pendente: " . $e->getMessage());
    }
  }
}

/**
 * Função de salvamento direto
 */
function plugin_aditionalinfo_direct_save($ticket_id)
{
  plugin_aditionalinfo_log("Direct save function called for ticket $ticket_id");

  try {
    if (!plugin_aditionalinfo_ensure_class_loaded()) {
      plugin_aditionalinfo_log("FATAL: Cannot load class PluginAditionalinfoTicket");
      return false;
    }

    plugin_aditionalinfo_log("Creating PluginAditionalinfoTicket instance");
    $additional_info = new PluginAditionalinfoTicket();

    $data = [
      'tickets_id' => $ticket_id,
      'external_responsible' => $_POST['external_responsible'] ?? '',
      'external_deadline' => (!empty($_POST['external_deadline'])) ? $_POST['external_deadline'] : null,
      'external_status' => $_POST['external_status'] ?? 'pendente'
    ];

    plugin_aditionalinfo_log("Direct save data: " . json_encode($data));
    plugin_aditionalinfo_log("Calling saveDataForTicket method");

    $result = $additional_info->saveDataForTicket($data);
    plugin_aditionalinfo_log("saveDataForTicket returned: " . ($result ? 'true' : 'false'));
    plugin_aditionalinfo_log("Direct save successful for ticket $ticket_id");

    return true;

  } catch (Exception $e) {
    plugin_aditionalinfo_log("Direct save error: " . $e->getMessage());
    plugin_aditionalinfo_log("Error trace: " . $e->getTraceAsString());
    return false;
  } catch (Error $e) {
    plugin_aditionalinfo_log("Direct save fatal error: " . $e->getMessage());
    plugin_aditionalinfo_log("Error trace: " . $e->getTraceAsString());
    return false;
  }
}

plugin_aditionalinfo_capture_ticket_action();

?>