<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * lang/pt_br/local_siteconfigdiff.php for local_siteconfigdiff.
 *
 * @package    local_siteconfigdiff
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'Ações';
$string['added'] = 'Adicionada';
$string['all'] = 'Todos';
$string['allowedappliers'] = 'IDs dos administradores autorizados';
$string['allowedappliers_help'] = 'IDs separados por vírgula. Vazio impede qualquer aplicação automática, mesmo por administradores.';
$string['apply_plan'] = 'Aplicar alterações revisadas';
$string['audit'] = 'Resultados da auditoria';
$string['author'] = 'ID do autor';
$string['back'] = 'Voltar ao painel';
$string['changed'] = 'Alterada';
$string['compare'] = 'Comparar snapshots';
$string['comparison'] = 'Comparação';
$string['comparisonnotice'] = 'As diferenças são somente leitura até um administrador autorizado criar e revisar um plano seletivo. Valores ocultos não podem ser comparados.';
$string['comparisons'] = 'Histórico de comparações';
$string['component'] = 'Componente';
$string['create_plan'] = 'Criar prévia de aplicação';
$string['create_snapshot'] = 'Capturar snapshot do site atual';
$string['created'] = 'Criado em';
$string['currentsite'] = 'Site atual (ao vivo)';
$string['dashboard'] = 'Comparações de configurações';
$string['description'] = 'Compare snapshots sanitizados de ambientes Moodle. Somente configurações expressamente revisadas podem ser aplicadas após autorização independente.';
$string['destination'] = 'Destino';
$string['download_csv'] = 'Baixar CSV';
$string['download_json'] = 'Baixar JSON';
$string['download_plan'] = 'Exportar plano de aplicação (JSON)';
$string['eventdescription'] = 'Operação de comparação de configurações: {$a->action}';
$string['eventlogged'] = 'Atividade de comparação de configurações';
$string['filter'] = 'Filtrar';
$string['group'] = 'Grupo administrativo';
$string['hidden'] = 'Valor oculto';
$string['impact'] = 'Impacto potencial';
$string['impact_calendar'] = 'Funcionamento do calendário';
$string['impact_language'] = 'Idioma do site';
$string['impact_locale'] = 'Configurações regionais';
$string['impact_theme'] = 'Tema, cache e apresentação';
$string['impact_time'] = 'Fuso horário e agendamento';
$string['impact_unknown'] = 'Desconhecido';
$string['impact_unreviewed'] = 'Configuração não revisada ou sensível';
$string['import_snapshot'] = 'Importar snapshot sanitizado';
$string['installation'] = 'Identificador da instalação';
$string['jsonfile'] = 'Arquivo JSON sanitizado (máximo 1 MiB)';
$string['locktimeout'] = 'Outra aplicação está em andamento para este plano.';
$string['low'] = 'Baixo';
$string['manual'] = 'Manual';
$string['missingplugin'] = 'Plugin ausente no destino';
$string['newvalue'] = 'Valor de origem (proposto)';
$string['oldvalue'] = 'Valor do destino (atual na prévia)';
$string['operationfailed'] = 'Operação rejeitada. Verifique os dados, as permissões e o log de diagnóstico do desenvolvedor.';
$string['plan'] = 'Plano de aplicação';
$string['plans'] = 'Planos de aplicação';
$string['planwarning'] = 'Revise cada item. A aplicação confere o valor esperado no destino antes de gravar. Efeitos externos não são revertidos automaticamente.';
$string['pluginchanges'] = 'Diferenças de versões de plugins';
$string['pluginname'] = 'Diferenças de configurações do site';
$string['pluginversions'] = 'Versões dos plugins (origem / destino)';
$string['prepare_reversal'] = 'Preparar prévia de reversão';
$string['privacy:metadata:local_siteconfigdiff_diff'] = 'Criador de uma comparação';
$string['privacy:metadata:local_siteconfigdiff_log'] = 'Autor de uma alteração de configuração';
$string['privacy:metadata:local_siteconfigdiff_plan'] = 'Criador de um plano de aplicação';
$string['privacy:metadata:local_siteconfigdiff_snap'] = 'Criador de um snapshot';
$string['reason'] = 'Motivo';
$string['reason_destination_changed_since_preview'] = 'Destino alterado desde a prévia';
$string['reason_not_allowlisted'] = 'Configuração não permitida';
$string['reason_ok'] = 'Aplicado com set_config()';
$string['reason_set_config_exception'] = 'Moodle retornou um erro';
$string['reason_set_config_failed'] = 'Moodle rejeitou a alteração';
$string['removed'] = 'Removida';
$string['risk'] = 'Risco';
$string['risk_low'] = 'Baixo';
$string['risk_manual'] = 'Intervenção manual';
$string['risk_unknown'] = 'Não revisado';
$string['saved'] = 'Operação salva.';
$string['search'] = 'Buscar componente ou configuração';
$string['select'] = 'Selecionar';
$string['setting'] = 'Configuração';
$string['siteconfigdiff:apply'] = 'Aplicar configurações permitidas após autorização explícita';
$string['siteconfigdiff:export'] = 'Exportar snapshots e comparações de configurações';
$string['siteconfigdiff:import'] = 'Importar snapshots sanitizados de configurações';
$string['siteconfigdiff:view'] = 'Visualizar comparações sanitizadas';
$string['snapshotname'] = 'Nome do snapshot';
$string['snapshots'] = 'Snapshots armazenados';
$string['source'] = 'Origem';
$string['sourceversion'] = 'Versão na origem';
$string['state_added'] = 'Adicionado';
$string['state_applied'] = 'Aplicado';
$string['state_changed'] = 'Alterado';
$string['state_completed'] = 'Concluído';
$string['state_failed'] = 'Falhou';
$string['state_hidden'] = 'Oculto ou indeterminado';
$string['state_missing_destination'] = 'Não instalado no destino';
$string['state_missing_source'] = 'Não instalado na origem';
$string['state_partial'] = 'Aplicado parcialmente';
$string['state_pending'] = 'Aguardando aprovação';
$string['state_rejected'] = 'Rejeitado';
$string['state_removed'] = 'Removido';
$string['state_running'] = 'Em execução';
$string['state_stale'] = 'Desatualizado';
$string['state_version_mismatch'] = 'Versão do plugin diferente';
$string['status'] = 'Status';
$string['targetversion'] = 'Versão no destino';
$string['unknown'] = 'Desconhecido';
