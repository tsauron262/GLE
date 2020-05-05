<?php

class BDSRender
{

    public static function renderOperationProcess($data)
    { 
        $html = '';
        $process = null;
        $operation = null;

        $errors = array();
        if (!isset($data['id_process']) || !(int) $data['id_process']) {
            $errors[] = 'ID du processus absent';
        } else {
            $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $data['id_process']);
            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'Le processus d\'ID ' . $data['id_process'] . ' n\'existe plus';
            }
        }

        if (!isset($data['id_operation']) || !(int) $data['id_operation']) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $data['id_operation']);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $data['id_operation'] . ' n\'existe plus';
            }
        }

        if (!count($errors)) {
            $html .= '<div id="process_' . $process->id . '_operation_' . $operation->id . '_progress_container" data-id_process="' . $data['id_process'] . '"';
            $html .= (isset($data['id_operation']) ? 'data-id_operation="' . $data['id_operation'] . '"' : '') . '>';

            $icon = (isset(BDS_Process::$types[$process->getData('type')]['icon']) ? BDS_Process::$types[$process->getData('type')]['icon'] : 'fas_cogs');
            $html .= '<h3>' . BimpRender::renderIcon($icon, 'iconLeft') . $data['operation_title'] . '</h3>';

            $html .= '<div id="stepsProgressionContainer">';
            if (isset($data['steps']) && count($data['steps'])) {
                $html .= '<table id="operationStepsTable" class="bds_operation_steps_table">';
                $html .= '<tbody>';
                foreach ($data['steps'] as $step_name => $step) {
                    $html .= '<tr id="step_' . $step_name . '" class="operationStepRow waiting">';

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


            $html .= '<div class="row">';
            $html .= '<div class="operationStepAjaxResult col-xs-12 col-md-6">';
            if (isset($data['result_content'])) {
                $html .= $data['result_content'];
            }
            $html .= '</div>';
            $html .= '</div>';

//            $html .= '<div id="cancelNotification" style="display: none">
//                  <p class="alert alert-warning">Annulation du processus en cours. Attente de la fin de traitement du paquet en cours.</p>
//              </div>
//              <div id="holdNotification" style="display: none">
//                  <p>Processus Suspendu</p>
//              </div>';

            $html .= '<div class="formSubmit">';
            $html .= '<span id="bds_cancelOperationButton" class="btn btn-danger" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].cancel();" style="display: none">';
            $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler';
            $html .= '</span>';

            $html .= '<span id="bds_holdOperationButton" class="btn btn-warning" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].hold();" style="display: none">';
            $html .= BimpRender::renderIcon('fas_pause', 'iconLeft') . 'Suspendre';
            $html .= '</span>';

            $html .= '<span id="bds_retryOperationStepButton" class="btn btn-success" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].retryOperationStep();" style="display: none">';
            $html .= BimpRender::renderIcon('fas_refresh') . 'Nouvelle tentative';
            $html .= '</span>';

            $html .= '<span id="bds_resumeOperationButton" class="btn btn-success" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].resume();" style="display: none">';
            $html .= BimpRender::renderIcon('fas_play', 'iconLeft') . 'Reprendre';
            $html .= '</span>';

            $backUrl = DOL_URL_ROOT . '/bimpdatasync/index.php?fc=process&id=' . (int) $data['id_process'] . '&tab=operations';
            $html .= '<span id="bds_backButton" class="btn btn-default" onclick="window.location = \'' . $backUrl . '\';" style="display: none">';
            $html .= BimpRender::renderIcon('fas_reply', 'iconLeft') . 'Retour';
            $html .= '</span>';
            $html .= '</div>';

            if (isset($data['debug_content']) && $data['debug_content']) {
                $debug_title = BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos débug';
                $html .= BimpRender::renderFoldableContainer($debug_title, $data['debug_content'], array(
                            'id' => 'processDebugContent'
                ));
            }

            if (isset($data['id_report']) && (int) $data['id_report']) {
                $report = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Report', (int) $data['id_report']);

                if (!BimpObject::objectLoaded($report)) {
                    $errors[] = 'Le rapport d\'ID ' . $data['id_report'] . ' n\'existe pas';
                } else {
                    $html .= '<div class="buttonsContainer" style="text-align: left">';
                    $html .= '<span id="bds_enableReportButton" class="btn btn-default" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].enableReport();" style="display: none">';
                    $html .= BimpRender::renderIcon('fas_eye', 'iconLeft') . 'Afficher le rapport';
                    $html .= '</span>';
                    $html .= '<span id="bds_disableReportButton" class="btn btn-default" onclick="if (typeof(bds_operations[' . $operation->id . ']) === \'object\') bds_operations[' . $operation->id . '].disableReport();">';
                    $html .= BimpRender::renderIcon('fas_eye-slash', 'iconLeft') . 'Masquer le rapport';
                    $html .= '</span>';
                    $html .= '</div>';

                    $html .= '<div id="processReportContainer">'; // Ne pas mettre d'attr style dans ce tag. 
                    $html .= $report->renderChildrenList('objects_data', 'report', 1, 'Infos objets traités', 'fas_chart-bar');
                    $html .= $report->renderChildrenList('lines', 'report', 1, 'Notifications', 'fas_comment');
                    $html .= '</div>';
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }
}
