<?php

include_once(__DIR__ . '/inc/functions.php');

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

?>