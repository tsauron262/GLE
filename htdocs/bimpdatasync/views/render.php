<?php

require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

function renderReportContent(BDS_Report $report)
{
    global $db;
    $bdb = new BDSDb($db);

    $infos = array();

    $begin = new DateTime($report->getData('begin'));
    $infos[] = array(
        'name'  => 'Début',
        'value' => $begin->format('d / m / Y')
    );

    $end = new DateTime($report->getData('end'));
    $infos[] = array(
        'name'  => 'Fin',
        'value' => $end->format('d / m / Y')
    );

    $infos[] = array(
        'name'  => 'Nombre d\'erreurs',
        'value' => $report->getData('nbErrors')
    );

    $infos[] = array(
        'name'  => 'Nombre d\'alertes',
        'value' => $report->getData('nbAlerts')
    );

    $objectsInfos = $report->getObjectsInfos();

    $html = '';

    $html .= '<div id="reportContent" data-import_ref="' . $report->file_ref . '">';
    $html .= '<table class="noborder" width="100%">';
    $html .= '<tr class="liste_titre">';
    $html .= '<td>' . $report->getData('title') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<div class="reportInfos">';
    $html .= '<a class="butActionDelete deleteReportButton" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php?deleteReport=' . $report->file_ref . '">';
    $html .= 'Supprimer ce rapport';
    $html .= '</a>';
    if (count($infos)) {
        $html .= '<ul>';
        foreach ($infos as $info) {
            $html .= '<li><strong>' . $info['name'] . ': </strong>' . $info['value'] . '</li>';
        }
        $html .= '</ul>';
    }
    if (count($objectsInfos)) {
        foreach ($objectsInfos as $objectInfos) {
            $html .= '<p style="margin-left: 15px">' . $objectInfos['name'] . ': </p>';
            if (isset($objectInfos['infos']) && count($objectInfos['infos'])) {
                $html .= '<ul>';
                foreach ($objectInfos['infos'] as $info) {
                    $html .= '<li><strong>' . $info['name'] . ': </strong>' . $info['value'] . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p>Aucune opération effectuée</p>';
            }
        }
    }
    $html .= '</div>';

    if (count($report->rows)) {
        $html .= '<div class="reportRowsFilters">';
        $html .= '<select class="reportRowsFilter" name="reportRowsFilter">
               <option value="all">
                  Tout afficher
               </option>
               <option value="danger">
                  Afficher uniquement les erreurs
               </option>
               <option value="warning">
                  Afficher uniquement les alertes
               </option>
               <option value="info">
                  Afficher uniquement les infos
               </option>
               <option value="success">
                  Afficher uniquement les succès
               </option>
            </select>';
        $html .= '</div>';
    }

    $html .= '<div class="reportTableContainer">';
    $html .= '<table class="noborder" width="100%">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th width="10%">Statut</th>';
    $html .= '<th width="10%">Heure</th>';
    $html .= '<th width="20%">Objet Dolibarr</th>';
    $html .= '<th width="20%">Référence</th>';
    $html .= '<th width="40%">Message</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="5">';

    $html .= '<div class="reportRowsContainer">';
    $html .= '<table width="100%">';
    $html .= '<tbody>';

    if (count($report->rows)) {
        $even = true;
        foreach ($report->rows as $r) {
            $html .= '<tr class="reportRow' . ($even ? ' even' : '') . '" data-msg_type="' . $r['type'] . '">';
            $html .= '<td class="rowStatus">';
            switch ($r['type']) {
                case 'danger':
                    $html .= '<span class="' . $r['type'] . '">[ERREUR]</span>';
                    break;

                case 'warning':
                    $html .= '<span class="' . $r['type'] . '">[ALERTE]</span>';
                    break;

                case 'success':
                    $html .= '<span class="' . $r['type'] . '">[SUCCES]</span>';
                    break;

                case 'info':
                    $html .= '<span class="' . $r['type'] . '">[INFO]</span>';
                    break;
            }
            $html .= '</td>';

            $html .= '<td class="rowTime">' . (isset($r['time']) ? $r['time'] : ' - ') . '</td>';

            $html .= '<td class="rowObject">';

            $object_link = BDS_Tools::makeObjectUrl($r['object'], $r['id_object']);
            $object_label = BDS_Tools::makeObjectName($bdb, $r['object'], $r['id_object']);

            if ($object_link !== '') {
                $html .= '<a href="' . $object_link . '" target="_blank">';
            }

            $html .= $object_label;

            if ($object_link !== '') {
                $html .= '</a>';
            }
            $html .= '</td>';

            $html .= '<td class="rowReference">' . (isset($r['reference']) ? $r['reference'] : ' - ') . '</td>';

            $html .= '<td class="rowMessage">';
            $html .= '<div class="alert alert-' . $r['type'] . '">';
            if (isset($r['msg'])) {
                $html .= $r['msg'];
            } else {
                $html .= ' - ';
            }
            $html .= '</div>';
            $html .= '</td>';

            $html .= '</tr>';
            $even = !$even;
        }
    } else {
        $html .= '<tr colspan="5">';
        $html .= '<td style="text-align: center">';
        $html .= '<p class="info">Aucun message</p>';
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</div>';

    return $html;
}

function renderObjectNotifications($rows, $title)
{
    $html = '';
    $html .= '<div id="searchResultContent">';
    $html .= '<table class="noborder" width="100%">';

    $html .= '<tr class="liste_titre">';
    $html .= '<td>' . $title . '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';

    if (count($rows)) {
        $html .= '<div class="reportRowsFilters">';
        $html .= '<select class="reportRowsFilter" name="reportRowsFilter">
               <option value="all">
                  Tout afficher
               </option>
               <option value="danger">
                  Afficher uniquement les erreurs
               </option>
               <option value="warning">
                  Afficher uniquement les alertes
               </option>
               <option value="info">
                  Afficher uniquement les infos
               </option>
               <option value="success">
                  Afficher uniquement les succès
               </option>
            </select>';
        $html .= '</div>';
    }

    $html .= '<div class="reportTableContainer">';
    $html .= '<table class="noborder" width="100%">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th width="10%">Date</th>';
    $html .= '<th width="10%">Heure</th>';
    $html .= '<th width="20%">Origine de l\'opération</th>';
    $html .= '<th width="10%">Statut</th>';
    $html .= '<th width="35%">Message</th>';
    $html .= '<th width="15%"></th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="7">';

    $html .= '<div class="reportRowsContainer">';
    $html .= '<table width="100%">';
    $html .= '<tbody>';

    if (count($rows)) {
        $even = true;
        foreach ($rows as $r) {
            $html .= '<tr class="reportRow' . ($even ? ' even' : '') . '"';
            $html .= ' data-msg_type="' . $r['type'] . '"';
            $html .= ' data-operation_type="' . $r['operation_type'] . '">';

            $html .= '<td width="10%">';
            $html .= '<strong>Le ' . $r['date'] . '<br/>';
            $html .= '</td>';

            $html .= '<td width="10%">' . (isset($r['time']) ? $r['time'] : ' - ') . '</td>';

            $html .= '<td width="20%">';
            $html .= '<span class="typeLabel" style="background-color: ' . BDS_Report::$OperationsTypes[$r['operation_type']]['color'] . '">';
            $html .= BDS_Report::$OperationsTypes[$r['operation_type']]['name'];
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td width="10%">';
            switch ($r['type']) {
                case 'danger':
                    $html .= '<span class="' . $r['type'] . '">[ERREUR]</span>';
                    break;

                case 'warning':
                    $html .= '<span class="' . $r['type'] . '">[ALERTE]</span>';
                    break;

                case 'success':
                    $html .= '<span class="' . $r['type'] . '">[SUCCES]</span>';
                    break;

                case 'info':
                    $html .= '<span class="' . $r['type'] . '">[INFO]</span>';
                    break;
            }
            $html .= '</td>';

            $html .= '<td width="35%">';
            $html .= '<div class="alert alert-' . $r['type'] . '">';
            if (isset($r['msg'])) {
                $html .= $r['msg'];
            } else {
                $html .= ' - ';
            }
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td width="15%">';
            if (isset($r['file_ref']) && $r['file_ref']) {
                $html .= '<a class="button" target="_blank" ';
                $html .= 'href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php?reportToLoad=' . $r['file_ref'] . '">';
                $html .= 'Voir le rapport</a>';
            }
            $html .= '</td>';
            $html .= '</tr>';
            $even = !$even;
        }
    } else {
        $html .= '<tr colspan="5">';
        $html .= '<td style="text-align: center">';
        $html .= '<p class="info">Aucun résultat trouvé</p>';
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</div>';

    return $html;
}

function renderProcessesRecentActivity($processes_data)
{
    if (!count($processes_data)) {
        return '';
    }

    $html = '';

    $html .= '<div class="toolBar">';
    $html .= '<a class="butAction" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php">';
    $html .= 'Vor tous les rapports';
    $html .= '</a>';
    $html .= '</div>';
    global $db;
    $bdb = new BDSDb($db);
    foreach ($processes_data as $id_process => $data) {
        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>' . $bdb->getValue(BDSProcess::$table, 'title', '`id` = ' . (int) $id_process) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';

        $html .= renderObjectsStatusInfos(BDS_Process::getProcessObjectsStatusInfos($id_process));

        $html .= '<div class="foldable_section closed">';
        $html .= '<div class="foldable_section_caption">';
        $html .= 'Activité récente';
        $html .= '</div>';
        $html .= '<div class="foldable_section_content">';
        $html .= '<div class="reportTableContainer">';
        $html .= '<table class="noborder" width="100%">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th width="10%">Date</th>';
        $html .= '<th width="10%">Heure</th>';
        $html .= '<th width="15%">Origine</th>';
        $html .= '<th width="25%">Opération</th>';
        $html .= '<th width="10%">Erreurs</th>';
        $html .= '<th width="10%">Alertes</th>';
        $html .= '<th width="20%"></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td colspan="7">';

        $html .= '<div class="reportRowsContainer">';
        $html .= '<table width="100%">';
        $html .= '<tbody>';

        $even = false;
        foreach ($data as $row) {
            $html .= '<tr class="reportRow' . ($even ? ' even' : '') . '">';

            $date = new DateTime($row['begin']);

            $html .= '<td width="10%"><strong>Le ' . $date->format('d / m / Y') . '</strong></td>';
            $html .= '<td width="10%">à ' . $date->format('H:i') . '</td>';

            $html .= '<td width="15%">';
            $html .= '<span class="typeLabel" style="background-color: ' . BDS_Report::$OperationsTypes[$row['type']]['color'] . '">';
            $html .= BDS_Report::$OperationsTypes[$row['type']]['name'];
            $html .= '</span>';
            $html .= '</td>';

            $html .= '<td width="25%">' . $row['title'] . '</td>';
            $html .= '<td width="10%">';
            if ($row['nbErrors']) {
                $html .= '<span class="danger">' . $row['nbErrors'] . ' erreur';
                $html .= ($row['nbErrors'] > 1 ? 's' : '') . '</span>';
            }
            $html .= '</td>';

            $html .= '<td width="10%">';
            if ($row['nbAlerts']) {
                $html .= '<span class="warning">' . $row['nbAlerts'] . ' alerte';
                $html .= ($row['nbAlerts'] > 1 ? 's' : '') . '</span>';
            }
            $html .= '</td>';

            $html .= '<td width="20%">';

            $html .= '<a style="float: right" class="button" href="./rapports.php?reportToLoad=' . $row['report_ref'] . '" ';
            $html .= 'target="_blank">Voir le rapport</a>';

            if (count($row['objectsInfos'])) {
                $html .= '<span style="float: right" class="detailsDisplayButton closed" onclick="toggleDetailsDisplay($(this), \'' . $row['report_ref'] . '\')">Détails</span>';
            }

            $html .= '</td>';

            $html .= '</tr>';

            if (count($row['objectsInfos'])) {
                $html .= '<tr class="reportDetailsRow" id="reportDetails_' . $row['report_ref'] . '">';
                $html .= '<td colspan="7">';


                foreach ($row['objectsInfos'] as $objectInfos) {
                    $html .= '<p style="margin-left: 15px">' . $objectInfos['name'] . ': </p>';
                    if (isset($objectInfos['infos']) && count($objectInfos['infos'])) {
                        $html .= '<ul>';
                        foreach ($objectInfos['infos'] as $info) {
                            $html .= '<li><strong>' . $info['name'] . ': </strong>' . $info['value'] . '</li>';
                        }
                        $html .= '</ul>';
                    } else {
                        $html .= '<p>Aucune opération effectuée</p>';
                    }
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            $even = !$even;
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div></div>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
    }
    return $html;
}

function renderObjectsStatusInfos($objects)
{
    $html = '';

    if (!is_null($objects) && count($objects)) {
        $html .= '<div class="foldable_section closed">';
        $html .= '<div class="foldable_section_caption">';
        $html .= 'Statuts des objets';
        $html .= '</div>';
        $html .= '<div class="foldable_section_content">';
        foreach ($objects as $object_name => $infos) {
            if (count($infos)) {
                if (isset(BDS_Report::$objectsLabels[$object_name])) {
                    $html .= '<h4 style="margin-bottom: 5px">' . ucfirst(BDS_Report::getObjectLabel($object_name, true)) . '</h4>';
                } else {
                    $html .= '<h4>Objets: "' . $object_name . '"</h4>';
                }

                $html .= '<ul style="background-color: #F0F0F0; margin: 0; padding: 5px 15px">';
                foreach ($infos as $status_code => $status) {
                    $html .= '<li><strong><span class="';
                    $html .= ($status_code > 0) ? 'warning' : ($status_code < 0) ? 'danger' : 'success';
                    $html .= '">"' . $status['label'] . '":</span>&nbsp;' . $status['count'] . '</strong></li>';
                }
                $html .= '</ul>';
            }
        }
        $html .= '</div></div>';
    }

    return $html;
}

function renderOperationProcess($data)
{
    $backUrl = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $data['id_process'] . '&tab=operations';
    $html = '';

    $html .= '<div id="operationProgressContainer" data-id_process="' . $data['id_process'] . '"';
    $html .= (isset($data['id_operation']) ? 'data-id_operation="' . $data['id_operation'] . '"' : '') . '>';

    $html .= '<table class="noborder" width="100%">';
    $html .= '<tr class="liste_titre">';
    $html .= '<td>' . $data['operation_title'] . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>';

    $html .= '<div id="stepsProgressionContainer">';
    if (isset($data['steps']) && count($data['steps'])) {
        $html .= '<table id="operationStepsTable">';
        $html .= '<tbody>';
        foreach ($data['steps'] as $step) {
            $html .= '<tr id="step_' . $step['name'] . '" class="operationStepRow waiting">';

            $html .= '<td class="stepLabel">' . $step['label'] . '</td>';

            $html .= '<td class="stepCount">';
            $html .= '<span class="nbStepElementsDone">0</span>&nbsp;/&nbsp;';
            $html .= '<span class="nbStepElementsTotal">' . (isset($step['elements']) ? count($step['elements']) : '1') . '</span>';
            $html .= '</td>';

            $html .= '<td class="stepProgession">';
            $html .= '<div class="progessionBar">';
            $html .= '<div class="progressionDone" style="width: 0%"></div>';
            $html .= '</div>';
            $html .= '<div class="stepExtraInfo"></div>';
            $html .= '</td>';

            $html .= '<td class="stepStatus">';
            $html .= '<span></span>';
            $html .= '</td>';

            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
    }
    $html .= '</div>';

    $html .= '<div id="operationStepAjaxResult">';
    if (isset($data['result_content'])) {
        $html .= $data['result_content'];
    }
    $html .= '</div>';

    $html .= '<div id="cancelNotification" style="display: none">
                  <p class="alert alert-warning">Annulation du processus en cours. Attente de la fin de traitement du paquet en cours.</p>
              </div>
              <div id="holdNotification" style="display: none">
                  <p>Processus Suspendu</p>
              </div>';

    $html .= '<div class="formSubmit">';
    $html .= '<span id="cancelOperationButton" class="button" onclick="if (Operation) Operation.cancel();" style="display: none">Annuler</span>';
    $html .= '<span id="holdOperationButton" class="button" onclick="if (Operation) Operation.hold();" style="display: none">Suspendre</span>';
    $html .= '<span id="retryOperationStepButton" class="button" onclick="if (Operation) Operation.retryOperationStep();" style="display: none">Nouvelle tentative</span>';
    $html .= '<span id="resumeOperationButton" class="button" onclick="if (Operation) Operation.resume();" style="display: none">Reprendre</span>';
    $html .= '<span id="backButton" class="button" onclick="window.location = \'' . $backUrl . '\';" style="display: none">Retour</span>';
    $html .= '</div>';

    $html .= '</td></tr></table></div>';

    if (isset($data['debug_content'])) {
        $html .= renderDebugContent($data['debug_content']);
    }

    if ($data['use_report']) {
        $html .= '<div>';

        $html .= '<div class="buttonsContainer" style="text-align: left">';
        $html .= '<span id="enableReportButton" class="button" onclick="if (Operation) Operation.enableReport();">Afficher le rapport</span>';
        $html .= '<span id="disableReportButton" class="button" onclick="if (Operation) Operation.disableReport();" style="display: none">Masquer le rapport</span>';
        $html .= '</div>';

        $html .= '<div id="reportLoadingResult"></div>';

        $html .= '<div id="reportContentContainer">';
        if (isset($data['report_content'])) {
            $html .= $data['report_content'];
        }
        $html .= '</div>';

        $html .= '</div>';
    }

    return $html;
}

function renderDebugContent($debug_content)
{
    $html = '';
    $html .= '<div id="debugContainer">';
    $html .= '<div class="foldable_section closed">';
    $html .= '<div class="foldable_section_caption">';
    $html .= 'Affichage débug';
    $html .= '</div>';
    $html .= '<div class="foldable_section_content" id="debugContent">';
    $html .= $debug_content;
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

function renderObjectProcessesData($data)
{
    $html = '';

    $html .= '<div id="processesDataContainer">';
    $html .= '<table class="noborder" width="100%">';

    $html .= '<tr class="liste_titre">';
    $html .= '<td>Liste des processus</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';

    $html .= '<div class="processDataTableContainer">';
    $html .= '<table class="noborder" style="border: none" width="100%">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="text-align: left" width="20%">Processus</th>';
    $html .= '<th style="text-align: left" width="25%">Référence</th>';
    $html .= '<th style="text-align: left" width="15%">Statut</th>';
    $html .= '<th style="text-align: left" width="20%">Dernière opération</th>';
    $html .= '<th style="text-align: left" width="20%"></th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $html .= renderObjectProcessDataRows($data);

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';

    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $html .= '</div>';

    return $html;
}

function renderObjectProcessDataRows($data)
{
    $html = '';
    if (count($data)) {
        $process_url = DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=';
        foreach ($data as $process) {
            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<a href="' . $process_url . $process['id_process'] . '" target="_blank">';
            $html .= $process['process_name'];
            $html .= '</a>';
            $html .= '</td>';

            $html .= '<td>' . $process['data']['references'] . '</td>';
            $html .= '<td>';
            $html .= '<span class="';
            if ((int) $process['data']['status_value'] > 0) {
                $html .= 'warning';
            } elseif ((int) $process['data']['status_value'] < 0) {
                $html .= 'danger';
            } else {
                $html .= 'success';
            }
            $html .= '">' . $process['data']['status_label'] . '</span>';
            $html .= '</td>';

            $html .= '<td>';

            if (isset($process['data']['date_update']) && $process['data']['date_update']) {
                $date = new DateTime($process['data']['date_update']);
                $html .= 'Le <span style="font-weight: bold">' . $date->format('d / m / Y') . '</span>';
                $html .= ' à ' . $date->format('H:i:s');
            } else {
                $html .= '<span class="warning">Non spécifié</span>';
            }

            if (isset($process['data']['actions']) && count($process['data']['actions'])) {
                $html .= '<td>';
                foreach ($process['data']['actions'] as $name => $label) {
                    $html .= '<span class="butAction"';
                    $html .= ' onclick="executeObjectProcess($(this), \'' . $name . '\', ' . $process['id_process'] . ', \'' . $process['object_name'] . '\', ' . $process['id_object'] . ')"';
                    $html .= '>' . $label . '</span>';
                }
                $html .= '</td>';
            }

            $html .= '</td>';
            $html .= '</tr>';

            if (isset($process['data']['actions']) && count($process['data']['actions'])) {
                $html .= '<tr style="display: none">';
                $html .= '<td colspan="5">';
                $html .= '<div id="process_' . $process['id_process'] . '_ajaxResult">';
                $html .= '</div>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
    } else {
        $html .= '<p class="alert alert-info">Cet objet n\'est impliqué dans aucun processus</p>';
    }
    return $html;
}

function renderProcessObjectsList($data, $fields)
{
    $html = '';
    global $db;
    $form = new Form($db);

    $base_url = $_SERVER['PHP_SELF'] . '?tab=objects&id_process=' . GETPOST('id_process');

    $current_object = BDS_Tools::getValue('object_name', 0);
    $sort_by = BDS_Tools::getValue('sort_by', 0);
    $sort_way = BDS_Tools::getValue('sort_way', 0);

    $colspan = 2 + count($fields);

    if (count($data)) {
        foreach ($data as $object_name => $object) {
            $html .= '<div class="foldable_section ';
            $html .= ($current_object === $object_name) ? 'open' : 'closed';
            $html .= '">';
            $html .= '<div class="foldable_section_caption">';
            $html .= ucfirst($object['label_plur']) . '&nbsp;&nbsp;';
            $html .= '<span class="badge">' . count($object['list']) . '</span>';

            if (isset($object['nbFails']) && $object['nbFails']) {
                $html .= '&nbsp;&nbsp;<span class="badge badge-danger">';
                $html .= $object['nbFails'] . ' échec' . ((int) $object['nbFails'] > 1 ? 's' : '');
                $html .= '</span>';
            }
            if (isset($object['nbProcessing']) && $object['nbProcessing']) {
                $html .= '&nbsp;&nbsp;<span class="badge badge-warning">';
                $html .= $object['nbProcessing'] . ' en cours';
                $html .= '</span>';
            }

            $html .= '</div>';

            $html .= '<div class="foldable_section_content">';

            if (count($object['list'])) {
                $url = $base_url . '&object_name=' . $object_name;

                if (count($object['bulkActions'])) {
                    $html .= '<div class="buttonsContainer">';
                    foreach ($object['bulkActions'] as $bulkAction) {
                        $onclick = $bulkAction['function'];
                        $onclick = str_replace('{object_name}', $object_name, $onclick);
                        $html .= '<span class="butAction" onclick="' . $onclick . '">';
                        $html .= $bulkAction['label'];
                        $html .= '</span>';
                    }
                }
                $html .= '</div>';
                $html .= '<form id="object_' . $object_name . '_searchForm" method="post" action="' . $url . '">';
                $html .= '<div style="margin: 15px 0">';

                $html .= '<table class="noborder" width="100%;">';

                $html .= '<thead>';
                $html .= '<tr class="liste_titre">';

                $html .= '<th width="5%"></th>';

                foreach ($fields as $field_name => $params) {
                    $html .= '<th width="' . $params['width'] . '">';
                    $href = $url;
                    $class = 'sortTitle';
                    if ($params['sort']) {
                        $href .= '&sort_by=' . $field_name . '&sort_way=';
                        if (($current_object === $object_name) &&
                                ($sort_by === $field_name)) {
                            $href .= ($sort_way === 'desc') ? 'asc' : 'desc';
                            $class .= ' active sort-' . (($sort_way === 'desc') ? 'asc' : 'desc');
                        } else {
                            $href .= 'desc';
                            $class .= ' sort-desc';
                        }
                    }
                    $html .= '<a href="' . $href . '" class="' . $class . '">';
                    if (isset($params['label'])) {
                        $html .= $params['label'];
                    } elseif (isset($params['label_eval'])) {
                        $html .= eval($params['label_eval']);
                    }
                    $html .= '</a>';
                    $html .= '</th>';
                }

                $html .= '<th></th>';
                $html .= '</tr>';

                $html .= '<tr class="liste_titre">';
                $html .= '<td style="text-align: center">';
                $html .= '<input type="checkbox" name="' . $object_name . '_checkall" ';
                $html .= 'onchange="toggleObjectListCheck(\'' . $object_name . '\', $(this))"/>';
                $html .= '</td>';

                foreach ($fields as $field_name => $params) {
                    $html .= '<td width="' . $params['width'] . '">';
                    if (isset($params['search'])) {
                        $input_name = 'object_' . $object_name . '_field_' . $field_name . '_search';

                        $input_value = null;

                        if (!BDS_Tools::isSubmit($object_name . '_searchReset')) {
                            $input_value = BDS_Tools::getValue($input_name, null);
                        }

                        switch ($params['search']) {
                            case 'text':
                                $html .= '<input type="text" id="' . $input_name . '"';
                                $html .= ' name="' . $input_name . '"';
                                if (!is_null($input_value)) {
                                    $html .= ' value="' . $input_value . '"';
                                }
                                $html .= ' style="width: 80%; margin-left: 5%; margin-right: 5%"/>';
                                break;

                            case 'select':
                                $html .= '<select id="' . $input_name . '" name="' . $input_name . '">';
                                foreach ($params['search_query'] as $value => $label) {
                                    $html .= '<option value="' . $value . '"';
                                    if (!is_null($input_value) && ($input_value == $value)) {
                                        $html .= ' selected';
                                    }
                                    $html .= '>' . $label . '</option>';
                                }
                                $html .= '</select>';
                                break;

                            case 'date':
                                ini_set('display_errors', 1);
                                $input_value = null;
                                if (!BDS_Tools::isSubmit($object_name . '_searchReset')) {
                                    $date_to = new DateTime(BDS_Tools::getDateTimeFromForm($input_name . '_to', date('Y-m-d H:i:s')));
                                    $date_from = new DateTime(BDS_Tools::getDateTimeFromForm($input_name . '_from', '2017-01-01 00:00:00'));
                                } else {
                                    $date_to = new DateTime();
                                    $date_from = new DateTime('2017-01-01 00:00:00');
                                }

                                $fields[$field_name]['search_from'] = $date_from->format('Y-m-d H:i:s');
                                $fields[$field_name]['search_to'] = $date_to->format('Y-m-d H:i:s');

                                $html .= 'Du:&nbsp;' . $form->select_date($date_from->getTimestamp(), $input_name . '_from', 0, 0, 0, '', 1, 0, 1) . '<br/>';
                                $html .= 'Au:&nbsp;' . $form->select_date($date_to->getTimestamp(), $input_name . '_to', 0, 0, 0, '', 1, 0, 1);
                                break;
                        }
                        if (!is_null($input_value)) {
                            $fields[$field_name]['search_value'] = $input_value;
                        }
                    }
                    $html .= '</td>';
                }

                $html .= '<td>';
                $html .= '<input type="submit" name="' . $object_name . '_searchSubmit" class="button searchSubmit" value="Rechercher"/>';
                $html .= '<input type="submit" name="' . $object_name . '_searchReset" class="button searchReset" value="Réinitialiser"/>';
                $html .= '</td>';

                $html .= '</tr>';

                $html .= '</thead>';

                $html .= '<tbody>';

                $search = BDS_Tools::isSubmit($object_name . '_searchSubmit');

                foreach ($object['list'] as $row) {
                    if ($search) {
                        foreach ($fields as $field_name => $params) {
                            if ($params['search'] === 'date') {
                                if (isset($row[$field_name . '_value'])) {
                                    $row_date = new DateTime($row[$field_name . '_value']);
                                } elseif (isset($row[$field_name])) {
                                    $row_date = new DateTime($row[$field_name]);
                                } else {
                                    continue 2;
                                }
                                $row_value = $row_date->format('Y-m-d');
                                unset($row_date);
                                if (isset($params['search_from'])) {
                                    if ($row_value < $params['search_from']) {
                                        continue 2;
                                    }
                                }
                                if (isset($params['search_to'])) {
                                    if ($row_value > $params['search_to']) {
                                        continue 2;
                                    }
                                }
                            } elseif (isset($params['search_value']) &&
                                    !is_null($params['search_value']) &&
                                    $params['search_value'] !== '') {
                                if (isset($row[$field_name . '_value'])) {
                                    $row_value = $row[$field_name . '_value'];
                                } elseif (isset($row[$field_name])) {
                                    $row_value = $row[$field_name];
                                } else {
                                    $row_value = null;
                                }
                                if (is_null($row_value) ||
                                        ($row_value != $params['search_value'])) {
                                    continue 2;
                                }
                            }
                        }
                    }

                    $html .= '<tr class="objectRow ' . $object_name . 'Row" ';
                    $html .= 'data-id_data="' . $row['id_data'] . '"';
                    $html .= ' data-id_object="' . $row['id_object'] . '"';
                    $html .= '>';

                    $html .= '<td style="text-align: center">';
                    $html .= '<input type="checkbox" id="' . $object_name . '_' . $row['id_object'] . '_check" ';
                    $html .= 'name="' . $object_name . '_' . $row['id_object'] . '_check"';
                    $html .= ' class="' . $object_name . '_check"/>';
                    $html .= '</td>';

                    foreach ($fields as $field_name => $params) {
                        $html .= '<td>';
                        if (isset($row[$field_name . '_html'])) {
                            $html .= $row[$field_name . '_html'];
                        } elseif (isset($row[$field_name])) {
                            $html .= $row[$field_name];
                        }
                        $html .= '</td>';
                    }

                    $html .= '<td>';
                    foreach ($object['buttons'] as $button) {
                        $onclick = $button['onclick'];
                        $onclick = str_replace('{object_name}', $object_name, $onclick);
                        $onclick = str_replace('{id_object}', $row['id_object'], $onclick);
                        $html .= '<span class="' . $button['class'] . '" style="float: right" onclick="' . $onclick . '">';
                        $html .= $button['label'] . '</span>';
                    }
                    $html .= '</td>';
                    $html .= '</tr>';

                    $html .= '<tr style="display:none">';
                    $html .= '<td colspan="' . $colspan . '">';
                    $html .= '<div class="objectAjaxResult" id="' . $object_name . '_' . $row['id_object'] . '_ajaxResult"></div>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '</table>';

                $html .= '</div>';
                $html .= '</form>';
                if (count($object['bulkActions'])) {
                    $html .= '<div class="buttonsContainer">';
                    foreach ($object['bulkActions'] as $bulkAction) {
                        $onclick = $bulkAction['function'];
                        $onclick = str_replace('{object_name}', $object_name, $onclick);
                        $html .= '<span class="butAction bulkActionButton" onclick="' . $onclick . '">';
                        $html .= $bulkAction['label'];
                        $html .= '</span>';
                    }
                }
            } else {
                $html .= '<p class="alert alert-warning" style="text-align: center">Aucun enregistrement</p>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }
    } else {
        $html .= '<p class="alert alert-warning">Aucun objet synchronisé enregistré</p>';
    }

    return $html;
}
