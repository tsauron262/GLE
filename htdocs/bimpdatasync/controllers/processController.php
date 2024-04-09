<?php

class processController extends BimpController
{

    public function ajaxProcessBds_initProcessOperation()
    {
        $errors = array();
        $warnings = array();
        $result_html = '';
        $process_html = '';

        $id_process = (int) BimpTools::getValue('id_process', 0, 'int');
        $id_operation = (int) BimpTools::getValue('id_operation', 0, 'int');
        $options = array();
        $data = array();

        $options['mode'] = 'ajax';

        if (is_null($id_process) || !$id_process) {
            $errors[] = 'ID du processus absent';
        } else {
            $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);
            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'Le processus d\'ID ' . $id_process . ' n\'existe plus';
            }
        }

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $id_operation . ' n\'existe plus';
            }

            $operation->getPostOptions($options, $errors, $warnings);
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';
            $bds_process = BDSProcess::createProcessById($id_process, $errors, $options);

            if (!is_null($bds_process)) {
                $data = $bds_process->initOperation($id_operation, $errors);

                $options = $bds_process->options;

                if (!count($errors) && is_a($bds_process, 'BDSProcess')) {
                    if (isset($data['result_html'])) {
                        $result_html = $data['result_html'];

                        if ($bds_process->options['debug'] && isset($data['debug_content']) && (string) $data['debug_content']) {
                            $result_html .= $data['debug_content'];
                        }
                    } else {
                        $process_html = BDSRender::renderOperationProcess($data);
                    }
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'result_html'       => $result_html,
            'process_html'      => $process_html,
            'operation_data'    => $data,
            'operation_options' => $options,
            'request_id'        => BimpTools::getValue('request_id', 0, 'int')
        );
    }

    public function ajaxProcessBds_initObjectActionProcess()
    {
        $errors = array();
        $warnings = array();
        $html = '';
        $process_html = '';
        $modal_idx = (int) BimpTools::getValue('modal_idx', 0, 'int');
        $id_process = 0;
        $id_operation = 0;

        $operation_data = array();
        $operation_options = array(
            'module'            => BimpTools::getValue('module', '', 'aZ09'),
            'object_name'       => BimpTools::getValue('object_name', '', 'aZ09'),
            'id_object'         => (int) BimpTools::getValue('id_object', 0, 'int'),
            'action'            => BimpTools::getValue('object_action', '', 'aZ09'),
            'action_extra_data' => BimpTools::getValue('extra_data', '', 'json'),
            'mode'              => 'ajax',
            'debug'             => BimpCore::isUserDev(),
            'force_use_report'     => (int) BimpTools::getValue('use_report', 0, 'int')
        );

        require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

        $process = BDSProcess::createProcessByName('ObjectsActions', $errors, $operation_options);

        if (!count($errors)) {
            $where = 'id_process = ' . (int) $process->process->id . ' AND name = \'ObjectAction\'';

            $id_operation = BimpCache::getBdb()->getValue('bds_process_operation', 'id', $where);

            if (!$id_operation) {
                $errors[] = 'ID de l\'opération absente';
            } else {
                $operation_data = $process->initOperation($id_operation, $errors);

                if (!count($errors) && is_a($process, 'BDSProcess')) {
                    $operation_options = $process->options;

                    if (isset($operation_data['result_html'])) {
                        $html = $operation_data['result_html'];

                        if ($process->options['debug'] && isset($operation_data['debug_content']) && (string) $operation_data['debug_content']) {
                            $html .= $operation_data['debug_content'];
                        }
                    } else {
                        $temp_html = BDSRender::renderOperationProcess($operation_data, array(
                                    'back_button_type'     => 'close_modale',
                                    'back_button_callback' => 'triggerObjectChange(\'' . $operation_options['module'] . '\', \'' . $operation_options['object_name'] . '\', ' . $operation_options['id_object'] . ');'
                        ));

                        if ($temp_html) {
                            $alert = '<span style="font-size: 16px; font-weight: bold">';
                            $alert .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Attention: veuillez ne pas fermer cette fenêtre avant la fin des opérations';
                            $alert .= '</span>';

                            $process_html = '<div style="margin-bottom: 15px; text-align: center">';
                            $process_html .= BimpRender::renderAlerts($alert, 'warning');
                            $process_html .= '</div>';
                            $process_html .= $temp_html;
                        }
                    }
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'html'              => $html,
            'id_process'        => $id_process,
            'id_operation'      => $id_operation,
            'operation_data'    => $operation_data,
            'operation_options' => $operation_options,
            'process_html'      => $process_html,
            'modal_idx'         => $modal_idx,
            'request_id'        => BimpTools::getValue('request_id', 0, 'int')
        );
    }

    public function ajaxProcessBds_executeOperationStep()
    {
        $errors = array();
        $warnings = array();
        $result = array();

        $id_process = (int) BimpTools::getPostFieldValue('id_process', 0);
        $id_operation = (int) BimpTools::getPostFieldValue('id_operation', 0);

        if (is_null($id_process) || !$id_process) {
            $errors[] = 'ID du processus absent';
        } else {
            $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);
            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'Le processus d\'ID ' . $id_process . ' n\'existe plus';
            }
        }

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $id_operation . ' n\'existe plus';
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

            $options = BimpTools::getPostFieldValue('options', array());
            $elements = BimpTools::getPostFieldValue('elements', array());

            $bds_process = BDSProcess::createProcessById($id_process, $errors, $options, $elements);

            if (!count($errors) && is_a($bds_process, 'BDSProcess')) {
                $id_report = (int) BimpTools::getPostFieldValue('id_report', 0);
                $iteration = (int) BimpTools::getPostFieldValue('iteration', 0);
                $step_name = BimpTools::getPostFieldValue('step_name', '');
                $extra_data = array(
                    'operation' => BimpTools::getPostFieldValue('operation_data', array()),
                    'step'      => BimpTools::getPostFieldValue('step_data', array())
                );

                $result = $bds_process->executeOperationStep($id_operation, $step_name, $id_report, $iteration, $extra_data);
            }
        }

        return array(
            'errors'      => $errors,
            'warnings'    => $warnings,
            'step_result' => $result,
            'request_id'  => BimpTools::getValue('request_id', 0, 'int')
        );
    }

    public function ajaxProcessBds_finalizeOperationStep()
    {
        $errors = array();
        $warnings = array();

        $id_process = (int) BimpTools::getPostFieldValue('id_process', 0, 'int');
        $id_operation = (int) BimpTools::getPostFieldValue('id_operation', 0, 'int');

        if (is_null($id_process) || !$id_process) {
            $errors[] = 'ID du processus absent';
        } else {
            $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);
            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'Le processus d\'ID ' . $id_process . ' n\'existe plus';
            }
        }

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $id_operation . ' n\'existe plus';
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

            $options = BimpTools::getPostFieldValue('options', array());
            $options['mode'] = 'ajax';

            $bds_process = BDSProcess::createProcessById($id_process, $errors, $options);

            if (!is_null($bds_process)) {
                $id_report = (int) BimpTools::getPostFieldValue('id_report', 0);
                $operation_data = BimpTools::getPostFieldValue('operation_data', array());

                $result = $bds_process->finalizeOperation($id_operation, $id_report, $operation_data, $errors);
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'result'     => $result,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        );
    }

    public function ajaxProcessBds_cancelOperation()
    {
        $errors = array();
        $warnings = array();

        $id_process = (int) BimpTools::getPostFieldValue('id_process', 0);
        $id_operation = (int) BimpTools::getPostFieldValue('id_operation', 0);

        if (is_null($id_process) || !$id_process) {
            $errors[] = 'ID du processus absent';
        } else {
            $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);
            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'Le processus d\'ID ' . $id_process . ' n\'existe plus';
            }
        }

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $id_operation . ' n\'existe plus';
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

            $options = BimpTools::getPostFieldValue('options', array());
            $options['mode'] = 'ajax';

            $bds_process = BDSProcess::createProcessById($id_process, $errors, $options);

            if (!is_null($bds_process)) {
                $id_report = (int) BimpTools::getPostFieldValue('id_report', 0);
                $operation_data = BimpTools::getPostFieldValue('operation_data', array());

                $result = $bds_process->cancelOperation($id_operation, $id_report, $operation_data, $errors);
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'result'     => $result,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        );
    }
}
