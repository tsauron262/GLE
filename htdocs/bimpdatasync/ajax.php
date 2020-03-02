<?php

require_once '../main.inc.php';
require_once __DIR__ . '/BDS_Lib.php';
require_once __DIR__ . '/views/render.php';

ini_set('display_errors', 1);

global $user;

$errors = array();
$success = 0;
$html = '';

if (BDS_Tools::isSubmit('action')) {
    $action = BDS_Tools::getValue('action');
    switch ($action) {
        case 'saveObject':
            $id_object = BDS_Tools::getValue('id_object');
            $object_name = BDS_Tools::getValue('object_name');

            if (is_null($object_name)) {
                $errors[] = 'Type d\'objet absent';
            }

            if (!count($errors)) {
                if (!class_exists($object_name)) {
                    if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                        require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                    }
                }

                if (!class_exists($object_name)) {
                    $errors[] = 'Classe "' . $object_name . '" inexistante';
                } else {
                    $object = new $object_name();

                    if (!is_null($id_object)) {
                        $object->fetch($id_object);
                    }

                    $errors = $object->validateForm();

                    if (!count($errors)) {
                        if (isset($object->id) && $object->id) {
                            $errors = $object->update();
                            if (!count($errors)) {
                                $success = 'Mise à jour ' . $object_name::getLabel('of_the') . ' effectuée avec succès';
                            }
                        } else {
                            $errors = $object->create();
                            if (!count($errors)) {
                                $success = 'Création ' . $object_name::getLabel('of_the') . ' effectuée avec succès';
                            }
                        }
                    }
                }
            }
            break;

        case 'deleteObjects':
            $objects = BDS_Tools::getValue('objects', array());
            $object_name = BDS_Tools::getValue('object_name', null);
            if (!count($objects)) {
                $errors[] = 'Liste des objets à supprimer vide ou absente';
            }
            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type d\'objet absent';
            }

            if (!count($errors)) {
                if (!class_exists($object_name)) {
                    if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                        require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                    }
                }

                if (!class_exists($object_name)) {
                    $errors[] = 'Classe "' . $object_name . '" inexistante';
                } else {
                    foreach ($objects as $id_object) {
                        $object = new $object_name();
                        if ($object->fetch($id_object)) {
                            $errors = BimpTools::merge_array($errors, $object->delete());
                        }
                        unset($object);
                    }
                    if (!count($errors)) {
                        if ($object_name::isLabelFemale()) {
                            $success = 'Toutes les ' . $object_name::getLabel('name_plur') . ' ont été supprimées';
                        } else {
                            $success = 'Tous les ' . $object_name::getLabel('name_plur') . ' ont été supprimés';
                        }
                        $success .= ' avec succès';
                    }
                }
            }
            break;

        case 'saveObjectAssociations':
            $id_object = BDS_Tools::getValue('id_object');
            $object_name = BDS_Tools::getValue('object_name');
            $association = BDS_Tools::getValue('association');
            $list = BDS_Tools::getValue('list', array());

            if (is_null($id_object) || !$id_object) {
                $errors[] = 'ID Absent';
            }
            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type d\'objet Absent';
            }
            if (is_null($association) || !$association) {
                $errors[] = 'Type d\'association absent';
            }

            if (!count($errors)) {
                if (!class_exists($object_name)) {
                    if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                        require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                    }
                }

                if (!class_exists($object_name)) {
                    $errors[] = 'Classe "' . $object_name . '" inexistante';
                } else {
                    $object = new $object_name();

                    if (!is_null($id_object)) {
                        $object->fetch($id_object);
                    }

                    $errors = $object->saveAssociations($association, $list);
                    if (!count($errors)) {
                        $class_name = $object_name::$associations[$association]['class_name'];
                        $label = $class_name::getLabel('name_plur');
                        $success = 'Enregistrement de la liste des ' . $class_name::getLabel('name_plur') . ' associé';
                        if ($class_name::isLabelFemale()) {
                            $success .= 'e';
                        }
                        $success .= 's effectué avec succès';
                    }
                }
            }

            break;

        case 'loadObjectForm':
            $object_name = BDS_Tools::getValue('object_name');
            $id_object = BDS_Tools::getValue('id_object', 0);
            $id_parent = BDS_Tools::getValue('id_parent', 0);

            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type de l\'objet absent';
            }

            if (!count($errors)) {
                if (!class_exists($object_name)) {
                    if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                        require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                    }
                }

                if (!class_exists($object_name)) {
                    $errors[] = 'Classe "' . $object_name . '" inexistante';
                } else {
                    if ($id_object) {
                        $object = new $object_name();
                        if (!$object->fetch($id_object)) {
                            $errors[] = ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé';
                        } else {
                            $html = $object->renderEditForm((int) $id_parent ? $id_parent : null);
                        }
                    } else {
                        $html = $object_name::renderCreateForm((int) $id_parent ? $id_parent : null);
                    }
                }
            }
            break;

        case 'loadObjectList':
            $id_parent = BDS_Tools::getValue('id_parent', null);
            if (!$id_parent) {
                $id_parent = null;
            }
            $object_name = BDS_Tools::getValue('object_name');
            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type d\'objet absent';
            }

            if (!count($errors)) {
                if (!class_exists($object_name)) {
                    if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                        require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                    }
                }

                if (!class_exists($object_name)) {
                    $errors[] = 'Classe "' . $object_name . '" inexistante';
                } else {
                    $html = $object_name::renderListRows($id_parent);
                }
            }
            break;

        case 'initProcessOperation':
            $data = array();
            $options = BDS_Tools::getValue('options', array());
            $id_process = BDS_Tools::getValue('id_process');
            $id_operation = BDS_Tools::getValue('id_operation');
            $html = 0;

            $options['mode'] = 'ajax';

            if (is_null($id_process) || !$id_process) {
                $errors[] = 'ID du processus absent';
            }
            if (is_null($id_operation) || !$id_operation) {
                $errors[] = 'ID de l\'opération absent';
            }

            if (!count($errors)) {
                $error = 0;
                $process = BDS_Process::createProcessById($user, $id_process, $error, $options);
                if (is_null($process)) {
                    $errors[] = 'Echec de l\'inialisation du processus' . ($error ? ' - ' . $error : '');
                } else {
                    $data = $process->initOperation($id_operation, $errors);
                    if (!count($errors) && !isset($data['result_html'])) {
                        $html = renderOperationProcess($data);
                    }
                    if (isset($data['debug_content']) && $data['debug_content']) {
                        $data['debug_content'] = renderDebugContent($data['debug_content']);
                    }
                }
            }

            die(json_encode(array(
                'errors'  => $errors,
                'data'    => $data,
                'options' => $options,
                'html'    => $html
            )));
            break;

        case 'executeOperationStep':
            $id_process = BDS_Tools::getValue('id_process');
            $id_operation = BDS_Tools::getValue('id_operation');
            $step = BDS_Tools::getValue('step_name');
            $options = BDS_Tools::getValue('options', array());
            $report_ref = BDS_Tools::getValue('report_ref');
            $iteration = BDS_Tools::getValue('iteration', 1);

            $report_html = 0;
            $step_result = array();

            $options['mode'] = 'ajax';

            if (is_null($id_process) || !$id_process) {
                $errors[] = 'ID du processus absent';
            }
            if (is_null($id_operation) || !$id_operation) {
                $errors[] = 'ID de l\'opération absent';
            }
            if (is_null($step) || !$step) {
                $errors[] = 'Identifiant de l\'étape absent';
            }

            if (!count($errors)) {
                $error = 0;
                $process = BDS_Process::createProcessById($user, $id_process, $error, $options);
                if (is_null($process)) {
                    $errors[] = 'Echec de l\'inialisation du processus' . ($error ? ' - ' . $error : '');
                } else {
                    if (BDS_Tools::isSubmit('elements')) {
                        $process->setReferences(BDS_Tools::getValue('elements'));
                    }
                    $step_result = $process->executeOperationStep($id_operation, $step, $errors, $report_ref, $iteration);
                    if (is_null($step_result) || !$step_result) {
                        $step_result = array();
                    }
                    if (BDS_Tools::getValue('return_report', 0) && !is_null($process->report)) {
                        if (!function_exists('renderReportContent')) {
                            require_once __DIR__ . '/views/render.php';
                        }
                        $report_html = renderReportContent($process->report);
                    }
                }
            }

            die(json_encode(array(
                'errors'      => $errors,
                'step_result' => $step_result,
                'report_html' => $report_html
            )));
            break;

        case 'loadReport':
            if (!BDS_Tools::isSubmit('report_ref')) {
                die('');
            }

            $report = new BDS_Report(null, null, BDS_Tools::getValue('report_ref'));
            if (!function_exists('renderReportContent')) {
                require_once __DIR__ . '/views/render.php';
            }

            die(renderReportContent($report));
            break;

        case 'executeObjectProcess':
            $id_process = BDS_Tools::getValue('id_process');
            $process_action = BDS_Tools::getValue('process_action');
            $object_name = BDS_Tools::getValue('object_name');
            $id_object = BDS_Tools::getValue('id_object');

            $options = array(
                'mode' => 'ajax'
            );

            if (is_null($id_process) || !$id_process) {
                $errors[] = 'ID du processus absent';
            }
            if (is_null($process_action) || !$process_action) {
                $errors[] = 'Type d\'opération absent';
            }
            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type d\'objet absent';
            }
            if (is_null($id_object) || !$id_object) {
                $errors[] = 'ID de l\'objet absent';
            }

            if (!count($errors)) {
                $error = 0;
                $process = BDS_Process::createProcessById($user, $id_process, $error, $options);
                if (is_null($process)) {
                    $errors[] = 'Echec de l\'inialisation du processus' . ($error ? ' - ' . $error : '');
                } else {
                    $result = $process->executeObjectProcess($process_action, $object_name, $id_object);
                    $result['errors'] = array();
                    die(json_encode($result));
                }
            }

            die(json_encode(array(
                'errors' => $errors
            )));
            break;

        default:
            $errors[] = 'Action invalide: "' . $action . '"';
            break;
    }
} else {
    $errors[] = 'Echec de la requête (Aucune action spécifiée)';
}

die(json_encode(array(
    'errors'  => $errors,
    'success' => $success,
    'html'    => $html
)));
