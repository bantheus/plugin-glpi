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

}

?>