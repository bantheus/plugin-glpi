<?php

class PluginAditionalinfoTicket extends CommonDBTM
{

  static $rightname = 'ticket';

  /**
   * Log
   */
  private function log($message): void
  {
    $log_file = GLPI_ROOT . '/plugins/aditionalinfo/debug.log';
    $timestamp = date('d/m/Y H:i:s');
    file_put_contents($log_file, "[$timestamp] [CLASS] $message\n", FILE_APPEND | LOCK_EX);
  }

  /**
   * Pega dados adicionais para um ticket específico
   */
  public function getDataForTicket($ticket_id): array|null
  {
    global $DB;

    $ticket_id = intval($ticket_id);
    if ($ticket_id <= 0) {
      $this->log("getDataForTicket: ID do ticket inválido: $ticket_id");
      return [];
    }

    try {
      $query = "SELECT * FROM " . self::getTable() . " 
                  WHERE tickets_id = $ticket_id";

      $this->log("getDataForTicket: Consulta: $query");

      $result = $DB->query($query);

      if ($result && $DB->numrows($result) > 0) {
        $data = $DB->fetchAssoc($result);
        $this->log("getDataForTicket: Dados encontrados: " . json_encode($data));
        return $data;
      }

      $this->log("getDataForTicket: Nenhum dado encontrado para o ticket $ticket_id");
      return [];

    } catch (Exception $e) {
      $this->log("getDataForTicket: Erro no banco de dados: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Salva dados adicionais para um ticket específico
   */
  public function saveDataForTicket($data): bool
  {
    global $DB;

    $ticket_id = intval($data['tickets_id']);

    $this->log("saveDataForTicket: Chamado com dados: " . json_encode($data));

    if ($ticket_id <= 0) {
      $this->log("saveDataForTicket: ID do ticket inválido: $ticket_id");
      return false;
    }

    try {
      $existing = $this->getDataForTicket($ticket_id);
      $this->log("saveDataForTicket: Resultado da verificação de dados existentes: " . (empty($existing) ? 'nenhum' : 'encontrado'));

      if (!empty($existing)) {
        $query = "UPDATE " . self::getTable() . " SET 
                     external_responsible = '" . $DB->escape($data['external_responsible']) . "',
                     external_deadline = " . ($data['external_deadline'] ? "'" . $DB->escape($data['external_deadline']) . "'" : "NULL") . ",
                     external_status = '" . $DB->escape($data['external_status']) . "',
                     date_mod = NOW()
                     WHERE tickets_id = $ticket_id";
        $this->log("saveDataForTicket: Consulta UPDATE: $query");
      } else {
        $query = "INSERT INTO " . self::getTable() . " 
                     (tickets_id, external_responsible, external_deadline, external_status, date_creation, date_mod)
                     VALUES ($ticket_id, 
                            '" . $DB->escape($data['external_responsible']) . "',
                            " . ($data['external_deadline'] ? "'" . $DB->escape($data['external_deadline']) . "'" : "NULL") . ",
                            '" . $DB->escape($data['external_status']) . "',
                            NOW(),
                            NOW())";
        $this->log("saveDataForTicket: Consulta INSERT: $query");
      }

      $result = $DB->query($query);

      if ($result) {
        $this->log("saveDataForTicket: Consulta executada com sucesso");
        return true;
      } else {
        $this->log("saveDataForTicket: Falha na consulta: " . $DB->error());
        return false;
      }

    } catch (Exception $e) {
      $this->log("saveDataForTicket: Exceção: " . $e->getMessage());
      $this->log("saveDataForTicket: Rastreamento da pilha: " . $e->getTraceAsString());
      return false;
    }
  }

  /**
   * Pega o nome da tabela associada a esta classe
   */
  static function getTable($classname = NULL): string
  {
    return 'glpi_plugin_aditionalinfo_tickets';
  }

  /**
   * Retorna o nome do tipo de item
   */
  static function getTypeName($nb = 0): string
  {
    if ($nb > 1) {
      return __('Additional Info Tickets', 'aditionalinfo');
    }
    return __('Additional Info Ticket', 'aditionalinfo');
  }
}

?>