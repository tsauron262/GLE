<?php

class processController extends BimpController
{

    public function ajaxProcessBds_initProcessOperation()
    {
        $errors = array();
        $warnings = array();
        $result_html = '';
        $process_html = '';

        $id_process = (int) BimpTools::getValue('id_process', 0);
        $id_operation = (int) BimpTools::getValue('id_operation', 0);
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
            'request_id'        => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessExecuteOperationStep()
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

                $result = $bds_process->executeOperationStep($id_operation, $step_name, $errors, $id_report, $iteration);
            }
        }

        return array(
            'errors'      => $errors,
            'warnings'    => $warnings,
            'step_result' => $result,
            'request_id'  => BimpTools::getValue('request_id', 0)
        );
    }
}
