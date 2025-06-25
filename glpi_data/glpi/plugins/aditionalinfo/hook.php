<?php

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

?>