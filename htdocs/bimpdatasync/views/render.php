<?php

require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

function renderReportContent(BDS_Report $report)
{
    global $db;
    $bdb = new BimpDb($db);

    ini_set('display_errors', 1);
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

    $objects_data = $report->getObjectsData();
    $objectsInfos = array();
    foreach ($objects_data as $object_name => $data) {
        $label_data = $report->getObjectLabelData($object_name);
        $objectInfos = array(
            'name'  => ucfirst($label_data['plurial']),
            'infos' => array()
        );
        $label = BDS_Tools::makeObjectLabel($label_data['name'], 'of_plur', $label_data['isFemale'], $label_data['plurial']);
        foreach ($data as $dataName => $value) {
            $name = 0;
            if ((int) $value > 0) {
                switch ($dataName) {
                    case 'nbProcessed':
                        $name = 'Nombre ' . $label . ' traité' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;

                    case 'nbUpdated':
                        $name = 'Nombre ' . $label . ' mis' . ($label_data['isFemale'] ? 'es' : '') . ' à jour';
                        break;

                    case 'nbCreated':
                        $name = 'Nombre ' . $label . ' créé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;

                    case 'nbDeleted':
                        $name = 'Nombre ' . $label . ' supprimé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;

                    case 'nbActivated':
                        $name = 'Nombre ' . $label . ' activé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;

                    case 'nbDeactivated':
                        $name = 'Nombre ' . $label . ' désactivé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;

                    case 'nbIgnored':
                        $name = 'Nombre ' . $label . ' ignoré' . ($label_data['isFemale'] ? 'e' : '') . 's';
                        break;
                }
                if ($name) {
                    $objectInfos['infos'][] = array(
                        'name'  => $name,
                        'value' => $value
                    );
                }
            }
        }
        $objectsInfos[] = $objectInfos;
    }

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
    if (count($objectInfos)) {
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
        $html .= '<select id="reportRowsFilter" name="reportRowsFilter">
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
        $html .= '<select id="reportRowsFilter" name="reportRowsFilter">
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
    $html .= '<th width="10%">Statut</th>';
    $html .= '<th width="10%">Heure</th>';
    $html .= '<th width="55%">Message</th>';
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
            $html .= '<tr class="reportRow' . ($even ? ' even' : '') . '" data-msg_type="' . $r['type'] . '">';
            $html .= '<td width="10%">';
            $html .= '<strong>Le ' . $r['date'] . '<br/>';
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

            $html .= '<td width="10%">' . (isset($r['time']) ? $r['time'] : ' - ') . '</td>';

            $html .= '<td width="55%">';
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
                $html .= '<a class="button" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php?reportToLoad=' . $r['file_ref'] . '">';
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