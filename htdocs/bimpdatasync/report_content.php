<?php

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

    echo '<div id="reportContent" data-import_ref="' . $report->file_ref . '">';
    echo '<table class="noborder" width="100%">';
    echo '<tr class="liste_titre">';
    echo '<td>' . $report->getData('title') . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<div class="reportInfos">';
    echo '<a class="button deleteReportButton" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php?deleteReport=' . $report->file_ref . '" style="color: #800">';
    echo 'Supprimer ce rapport';
    echo '</a>';
    if (count($infos)) {
        echo '<ul>';
        foreach ($infos as $info) {
            echo '<li><strong>' . $info['name'] . ': </strong>' . $info['value'] . '</li>';
        }
        echo '</ul>';
    }
    if (count($objectInfos)) {
        foreach ($objectsInfos as $objectInfos) {
            echo '<p style="margin-left: 15px">' . $objectInfos['name'] . ': </p>';
            if (isset($objectInfos['infos']) && count($objectInfos['infos'])) {
                echo '<ul>';
                foreach ($objectInfos['infos'] as $info) {
                    echo '<li><strong>' . $info['name'] . ': </strong>' . $info['value'] . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>Aucune opération effectuée</p>';
            }
        }
    }
    echo '</div>';

    if (count($report->rows)) {
        echo '<div class="reportRowsFilters">';
        echo '<select id="reportRowsFilter" name="reportRowsFilter">
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
        echo '</div>';
    }

    echo '<div class="reportTableContainer">';
    echo '<table class="noborder" width="100%">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="10%">Statut</th>';
    echo '<th width="10%">Heure</th>';
    echo '<th width="20%">Objet Dolibarr</th>';
    echo '<th width="20%">Référence</th>';
    echo '<th width="40%">Message</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo '<tr>';
    echo '<td colspan="5">';

    echo '<div class="reportRowsContainer">';
    echo '<table width="100%">';
    echo '<tbody>';

    if (count($report->rows)) {
        $even = true;
        foreach ($report->rows as $r) {
            echo '<tr class="reportRow' . ($even ? ' even' : '') . '" data-msg_type="' . $r['type'] . '">';
            echo '<td class="rowStatus">';
            switch ($r['type']) {
                case 'danger':
                    echo '<span class="' . $r['type'] . '">[ERREUR]</span>';
                    break;

                case 'warning':
                    echo '<span class="' . $r['type'] . '">[ALERTE]</span>';
                    break;

                case 'success':
                    echo '<span class="' . $r['type'] . '">[SUCCES]</span>';
                    break;

                case 'info':
                    echo '<span class="' . $r['type'] . '">[INFO]</span>';
                    break;
            }
            echo '</td>';

            echo '<td class="rowTime">' . (isset($r['time']) ? $r['time'] : ' - ') . '</td>';

            echo '<td class="rowObject">';

            $object_link = BDS_Tools::makeObjectUrl($r['object'], $r['id_object']);
            $object_label = BDS_Tools::makeObjectName($bdb, $r['object'], $r['id_object']);

            if ($object_link !== '') {
                echo '<a href="' . $object_link . '" target="_blank">';
            }

            echo $object_label;

            if ($object_link !== '') {
                echo '</a>';
            }
            echo '</td>';

            echo '<td class="rowReference">' . (isset($r['reference']) ? $r['reference'] : ' - ') . '</td>';

            echo '<td class="rowMessage">';
            echo '<div class="alert alert-' . $r['type'] . '">';
            if (isset($r['msg'])) {
                echo $r['msg'];
            } else {
                echo ' - ';
            }
            echo '</div>';
            echo '</td>';

            echo '</tr>';
            $even = !$even;
        }
    } else {
        echo '<tr colspan="5">';
        echo '<td style="text-align: center">';
        echo '<p class="info">Aucun message</p>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
}

function renderObjectNotifications($rows, $title)
{
    echo '<div id="searchResultContent">';
    echo '<table class="noborder" width="100%">';

    echo '<tr class="liste_titre">';
    echo '<td>' . $title . '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';

    if (count($rows)) {
        echo '<div class="reportRowsFilters">';
        echo '<select id="reportRowsFilter" name="reportRowsFilter">
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
        echo '</div>';
    }

    echo '<div class="reportTableContainer">';
    echo '<table class="noborder" width="100%">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="10%">Date</th>';
    echo '<th width="10%">Statut</th>';
    echo '<th width="10%">Heure</th>';
    echo '<th width="55%">Message</th>';
    echo '<th width="15%"></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo '<tr>';
    echo '<td colspan="7">';

    echo '<div class="reportRowsContainer">';
    echo '<table width="100%">';
    echo '<tbody>';

    if (count($rows)) {
        $even = true;
        foreach ($rows as $r) {
            echo '<tr class="reportRow' . ($even ? ' even' : '') . '" data-msg_type="' . $r['type'] . '">';
            echo '<td width="10%">';
            echo '<strong>Le ' . $r['date'] . '<br/>';
            echo '</td>';
            echo '<td width="10%">';
            switch ($r['type']) {
                case 'danger':
                    echo '<span class="' . $r['type'] . '">[ERREUR]</span>';
                    break;

                case 'warning':
                    echo '<span class="' . $r['type'] . '">[ALERTE]</span>';
                    break;

                case 'success':
                    echo '<span class="' . $r['type'] . '">[SUCCES]</span>';
                    break;

                case 'info':
                    echo '<span class="' . $r['type'] . '">[INFO]</span>';
                    break;
            }
            echo '</td>';

            echo '<td width="10%">' . (isset($r['time']) ? $r['time'] : ' - ') . '</td>';

            echo '<td width="55%">';
            echo '<div class="alert alert-' . $r['type'] . '">';
            if (isset($r['msg'])) {
                echo $r['msg'];
            } else {
                echo ' - ';
            }
            echo '</div>';
            echo '</td>';
            echo '<td width="15%">';
            if (isset($r['file_ref']) && $r['file_ref']) {
                echo '<a class="button" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php?reportToLoad=' . $r['file_ref'] . '">';
                echo 'Voir le rapport</a>';
            }
            echo '</td>';
            echo '</tr>';
            $even = !$even;
        }
    } else {
        echo '<tr colspan="5">';
        echo '<td style="text-align: center">';
        echo '<p class="info">Aucun résultat trouvé</p>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
}
