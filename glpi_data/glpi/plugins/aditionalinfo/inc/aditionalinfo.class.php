<?php

include_once(__DIR__ . '/functions.php');

class PluginAditionalinfoTicket extends CommonDBTM
{

  static $rightname = 'ticket';

  /**
   * Obtém os dados adicionais para um ticket específico.
   */
  public function getDataForTicket($ticket_id): array|null
  {
    global $DB;

    $ticket_id = intval($ticket_id);
    if ($ticket_id <= 0) {
      plugin_aditionalinfo_log("ID do ticket inválido: $ticket_id");
      return [];
    }

    try {
      $query = "SELECT * FROM glpi_plugin_aditionalinfo_tickets WHERE tickets_id = $ticket_id";

      plugin_aditionalinfo_log("Executando consulta: $query");

      $result = $DB->query($query);

      if ($result && $DB->numrows($result) > 0) {
        $data = $DB->fetchAssoc($result);
        plugin_aditionalinfo_log("Dados adicionais encontrados: " . json_encode($data));
        return $data;
      }

      plugin_aditionalinfo_log("Nenhum dado adicional encontrado para o ticket ID: $ticket_id");
      return [];
    } catch (Exception $e) {
      plugin_aditionalinfo_log("Erro interno" . $e->getMessage());
      return [];
    }
  }

  /**
   * Salva os dados adicionais para um ticket específico.
   */
  public function saveDataForTicket($data): bool
  {
    global $DB;

    $ticket_id = intval($data['tickets_id']);

    if ($ticket_id <= 0) {
      plugin_aditionalinfo_log("ID do ticket inválido: $ticket_id");
      return false;
    }

    try {
      $existing = $this->getDataForTicket($ticket_id);

      if (!empty($existing)) {
        $query = "UPDATE glpi_plugin_aditionalinfo_tickets SET 
               external_responsible = '" . $DB->escape($data['external_responsible']) . "',
               external_deadline = " . ($data['external_deadline'] ? "'" . $DB->escape($data['external_deadline']) . "'" : "NULL") . ",
               external_status = '" . $DB->escape($data['external_status']) . "',
               date_mod = NOW()
               WHERE tickets_id = $ticket_id";
        plugin_aditionalinfo_log("Atualizando dados adicionais para o ticket ID: $ticket_id");

      } else {
        $query = "INSERT INTO glpi_plugin_aditionalinfo_tickets 
               (tickets_id, external_responsible, external_deadline, external_status, date_creation, date_mod)
               VALUES ($ticket_id, 
                  '" . $DB->escape($data['external_responsible']) . "',
                  " . ($data['external_deadline'] ? "'" . $DB->escape($data['external_deadline']) . "'" : "NULL") . ",
                  '" . $DB->escape($data['external_status']) . "',
                  NOW(),
                  NOW())";
        plugin_aditionalinfo_log("Inserindo novos dados adicionais para o ticket ID: $ticket_id");
      }

      $result = $DB->query($query);

      if ($result) {
        plugin_aditionalinfo_log("Dados adicionais salvos com sucesso para o ticket ID: $ticket_id");
        return true;
      } else {
        plugin_aditionalinfo_log("Erro ao salvar dados adicionais para o ticket ID: $ticket_id - " . $DB->error());
        return false;
      }
    } catch (Exception $e) {
      plugin_aditionalinfo_log("Erro interno: " . $e->getMessage());
      return false;
    }
  }

}

?>