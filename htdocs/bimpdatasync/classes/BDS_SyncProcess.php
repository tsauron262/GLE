<?php

include_once __DIR__ . '/BDS_SyncData.php';

class BDS_SyncProcess extends BDS_Process
{

    public static $ext_debug_mod = false;
    public static $ext_process_name = null;
    public $soap_client = null;
    public $authentication = array();

    const BDS_STATUS_SYNCHRONISED = 0;
    const BDS_STATUS_EXPORTING = 1;
    const BDS_STATUS_IMPORTING = 2;
    const BDS_STATUS_DELETING = 3;
    const BDS_STATUS_EXPORT_FAIL = -1;
    const BDS_STATUS_IMPORT_FAIL = -2;
    const BDS_STATUS_DELETE_FAIL = -3;

    public static $status_labels = array(
        0  => 'synchronisé',
        1  => 'en cours d\'export',
        2  => 'en cours d\'import',
        3  => 'en cours de suppression',
        -1 => 'échec export',
        -2 => 'échec import',
        -3 => 'échec suppression'
    );

    public function __construct(\BDSProcess $processDefinition, $user, $params = null)
    {
        if (isset($params['ext_debug_mod']) && $params['ext_debug_mod']) {
            if ((isset($params['debug_mod']) && $params['debug_mod']) || self::$debug_mod)
                self::$ext_debug_mod = true;
        }
        parent::__construct($processDefinition, $user, $params);
    }

    public static function getClassName()
    {
        return 'BDS_SyncProcess';
    }

    // Actions SOAP:
    protected function soapClientInit()
    {
        if (!$this->parameters_ok) {
            $this->Error('Echec de l\'initialisation du client SOAP: paramètres invalides. Veuillez vérifier la configuration du processus.');
            return false;
        }
        if (!is_null($this->soap_client)) {
            return true;
        }

        require_once(__DIR__ . '/nusoap/lib/nusoap.php');

        $this->soap_client = new nusoap_client($this->parameters['ws_url']);
        if ($this->soap_client) {
//            $this->soap_client->soap_defencoding = 'UTF-8';
            return true;
        }

        $this->Error('Echec de l\'initialisation du client SOAP');
        $this->soap_client = null;
        return false;
    }

    protected function soapCall($ws_method, $params)
    {
        if (is_null($this->soap_client)) {
            if (!$this->soapClientInit()) {
                return null;
            }
        }

        $parameters['authentication'] = $this->authentication;
        $params['debug_mod'] = (int) self::$ext_debug_mod;
        $parameters['params'] = $params;

        if (self::$debug_mod) {
            $this->debug_content .= '<h3>Soap Call méthode: "' . $ws_method . '"</h3>';
            $this->debug_content .= '<h4>Paramètres: </h4><pre>';
            $this->debug_content .= print_r($parameters, 1);
            $this->debug_content .= '</pre>';
        }

        return $this->soap_client->call($ws_method, $parameters);
    }

    protected function soapExportObjects($objects, $ext_process_name)
    {
        $response = $this->soapCall('set', array(
            'process_name'   => $ext_process_name,
            'operation'      => 'onSetObjectsRequest',
            'ext_id_process' => (int) $this->processDefinition->id,
            'objects'        => $objects
        ));

        if (self::$debug_mod) {
            if (self::$ext_debug_mod) {
                $this->debug_content .= '<h4>SOAP CLIENT: </h4><pre>';
                $this->debug_content .= print_r($this->soap_client, 1);
                $this->debug_content .= '</pre>';
                return;
            } else {
                $this->debug_content .= '<h4>Réponse: </h4>';
                if (is_array($response)) {
                    $this->debug_content .= '<pre>';
                    $this->debug_content .= print_r($response, 1);
                    $this->debug_content .= '</pre>';
                } else {
                    $this->debug_content .= $response . '<br/>';
                }
            }
        }

        if (!self::$ext_debug_mod) {
            if (is_null($response)) {
                $msg = 'Echec de l\'export (Aucune réponse reçue)';
                $this->Error($msg);
            } elseif (!isset($response['success']) || !$response['success']) {
                $msg = 'Echec de l\'export<br/>';
                if (isset($response['errors'])) {
                    $nErrors = count($response['errors']);
                    if ($nErrors) {
                        $msg .= $nErrors . 'erreur' . (($nErrors > 1) ? 's' : '') . ':<br/>';
                        $msg .= '<ul>';
                        foreach ($response['errors'] as $error) {
                            $msg .= '<li>' . $error . '</li>';
                        }
                        $msg .= '</ul>';
                    }
                }
                $this->Error($msg);
            }
        }

        if (isset($response['return']) && count($response['return'])) {
            $this->processObjectsExportResult(isset($response['ext_id_process']), $response['return']);
        } else {
            foreach ($objects as $object_data) {
                foreach ($object_data['list'] as $object) {
                    if (isset($object['ext_id_sync_data']) && $object['ext_id_sync_data']) {
                        BDS_SyncData::updateStatusById($this->db, $object['ext_id_sync_data'], self::BDS_STATUS_EXPORT_FAIL);
                    }
                }
            }
        }
    }

    protected function soapImportObjects($objects, $ext_process_name)
    {
        $response = $this->soapCall('set', array(
            'process_name'   => $ext_process_name,
            'operation'      => 'onGetObjectsRequest',
            'ext_id_process' => (int) $this->processDefinition->id,
            'objects'        => $objects
        ));

        if (self::$debug_mod) {
            if (self::$ext_debug_mod) {
                $this->debug_content .= '<h4>SOAP CLIENT: </h4><pre>';
                $this->debug_content .= print_r($this->soap_client, 1);
                $this->debug_content .= '</pre>';
                return;
            } else {
                $this->debug_content .= '<h4>Réponse: </h4>';
                if (is_array($response)) {
                    $this->debug_content .= '<pre>';
                    $this->debug_content .= print_r($response, 1);
                    $this->debug_content .= '</pre>';
                } else {
                    $this->debug_content .= $response . '<br/>';
                }
            }
        }

        if (!self::$ext_debug_mod) {
            if (is_null($response)) {
                $msg = 'Echec de l\'import (Aucune réponse reçue)';
                $this->Error($msg);
            } elseif (!isset($response['success']) || !$response['success']) {
                $msg = 'Echec de l\'import<br/>';
                if (isset($response['errors'])) {
                    $nErrors = count($response['errors']);
                    if ($nErrors) {
                        $msg .= $nErrors . 'erreur' . (($nErrors > 1) ? 's' : '') . ':<br/>';
                        $msg .= '<ul>';
                        foreach ($response['errors'] as $error) {
                            $msg .= '<li>' . $error . '</li>';
                        }
                        $msg .= '</ul>';
                    }
                }
                $this->Error($msg);
            }
        }

        if (isset($response['return']) && count($response['return'])) {
            $this->processObjectsImportResult(isset($response['ext_id_process']), $response['return']);
        } else {
            foreach ($objects as $object_data) {
                foreach ($object_data['list'] as $object) {
                    if (isset($object['ext_id_sync_data']) && $object['ext_id_sync_data']) {
                        BDS_SyncData::updateStatusById($this->db, $object['ext_id_sync_data'], self::BDS_STATUS_IMPORT_FAIL);
                    }
                }
            }
        }
    }

    protected function soapDeleteObjects($objects, $ext_process_name)
    {
        $response = $this->soapCall('set', array(
            'process_name'   => $ext_process_name,
            'operation'      => 'onDeleteObjectsRequest',
            'ext_id_process' => $this->processDefinition->id,
            'objects'        => $objects
        ));

        if (self::$debug_mod) {
            if (self::$ext_debug_mod) {
                $this->debug_content .= '<h4>[DEBUG MOD EXTERNE] Réponse: </h4><pre>';
                $this->debug_content .= print_r($this->soap_client['responseData'], 1);
                $this->debug_content .= '</pre>';
            } else {
                $this->debug_content .= '<h4>Réponse: </h4>';
                if (is_array($response)) {
                    $this->debug_content .= '<pre>';
                    $this->debug_content .= print_r($response, 1);
                    $this->debug_content .= '</pre>';
                } else {
                    $this->debug_content .= $response . '<br/>';
                }
            }
        }

        if (!self::$ext_debug_mod) {
            if (is_null($response)) {
                $msg = 'Echec de la requête de suppression externe (aucune réponse reçue)';
                $this->Error($msg);
            } elseif (!isset($response['success']) || !$response['success']) {
                $msg = 'Echec de la suppression externe<br/>';
                if (isset($response['errors'])) {
                    $nErrors = count($response['errors']);
                    if ($nErrors) {
                        $msg .= $nErrors . 'erreur' . (($nErrors > 1) ? 's' : '') . ':<br/>';
                        $msg .= '<ul>';
                        foreach ($response['errors'] as $error) {
                            $msg .= '<li>' . $error . '</li>';
                        }
                        $msg .= '</ul>';
                    }
                }
                $this->Error($msg);
            }
        }

        if (isset($response['return']) && count($response['return'])) {
            $this->processObjectsDeleteResult(isset($response['ext_id_process']), $response['return']);
        } else {
            foreach ($objects as $object_data) {
                foreach ($object_data['list'] as $object) {
                    if (isset($object['ext_id_sync_data']) && $object['ext_id_sync_data']) {
                        BDS_SyncData::updateStatusById($this->db, $object['ext_id_sync_data'], self::BDS_STATUS_DELETE_FAIL);
                    }
                }
            }
        }
    }

    // Traitement des requêtes externes:

    protected function onSetObjectsRequest($params, &$errors)
    {
        if (self::$debug_mod) {
            echo 'Paramètres reçus: <pre>';
            print_r($params);
            echo '</pre>';
        }
        $ext_id_process = (isset($params['ext_id_process']) ? $params['ext_id_process'] : null);
        $return = array();

        if (isset($params['objects'])) {
            foreach ($params['objects'] as $object_data) {
                if (!isset($object_data['ext_object_name']) || !$object_data['ext_object_name']) {
                    $msg = 'Nom externe du type d\'objet absent pour le type local "';
                    if (isset($object_data['object_name']) && $object_data['object_name']) {
                        $msg .= $object_data['object_name'];
                    } else {
                        $msg .= 'inconnu';
                    }
                    $msg .= '"';
                    $this->Error($msg);
                    $errors[] = $msg;
                    continue;
                }
                $ext_object_name = $object_data['ext_object_name'];
                $return[$ext_object_name] = array();
                if (!isset($object_data['object_name']) || !$object_data['object_name']) {
                    $msg = 'Nom local du type d\'objet absent pour le type d\'objet externe "' . $ext_object_name . '"';
                    $this->Error($msg);
                    $errors[] = $msg;
                } elseif (isset($object_data['list']) && count($object_data['list'])) {
                    foreach ($object_data['list'] as $object) {
                        $object_errors = array();
                        $ext_id_object = 0;
                        if (isset($object['ext_id_object']) && $object['ext_id_object']) {
                            $ext_id_object = $object['ext_id_object'];
                        } else {
                            $object_errors[] = 'ID externe de l\'objet absent';
                        }

                        if (!isset($object['data']) || !$object['data']) {
                            $object_errors[] = 'Données de mise à jour absentes';
                        }

                        if (!isset($object['ext_id_sync_data']) || !$object['ext_id_sync_data']) {
                            $object_errors[] = 'ID externe des données de synchronisation absent';
                        }

                        if (!count($object_errors)) {
                            $result = $this->updateObject(
                                    $object_data['object_name'], $object['data'], $object['ext_id_sync_data'], $ext_id_process, $ext_object_name, $ext_id_object
                            );
                        } else {
                            $result = array(
                                'errors' => $object_errors
                            );
                        }
                        $result['id_object'] = $ext_id_object;
                        $return[$ext_object_name][] = $result;
                    }
                }
            }
        }

        return $return;
    }

    protected function onDeleteObjectsRequest($params, &$errors)
    {
        $ext_id_process = (isset($params['ext_id_process']) ? $params['ext_id_process'] : null);
        $return = array();

        if (isset($params['objects'])) {
            foreach ($params['objects'] as $object_data) {
                if (!isset($object_data['ext_object_name']) || !$object_data['ext_object_name']) {
                    $msg = 'Nom externe du type d\'objet absent pour le type local "';
                    if (isset($object_data['object_name']) && $object_data['object_name']) {
                        $msg .= $object_data['object_name'];
                    } else {
                        $msg .= 'inconnu';
                    }
                    $msg .= '"';
                    $this->Error($msg);
                    $errors[] = $msg;
                    continue;
                }

                $ext_object_name = $object_data['ext_object_name'];
                $return[$ext_object_name] = array();
                if (!isset($object_data['object_name']) || !$object_data['object_name']) {
                    $msg = 'Nom local du type d\'objet absent pour le type d\'objet externe "' . $ext_object_name . '"';
                    $this->Error($msg);
                    $errors[] = $msg;
                } elseif (isset($object_data['list']) && count($object_data['list'])) {
                    $object_name = $object_data['object_name'];

                    foreach ($object_data['list'] as $object) {
                        $object_errors = array();
                        $sync_data = new BDS_SyncData();
                        $id_object = 0;
                        $id_sync_data = 0;
                        $ext_id_object = 0;
                        $ext_id_sync_data = 0;

                        // Vérification des données reçues:
                        if (isset($object['id_sync_data']) && $object['id_sync_data']) {
                            $sync_data->id = (int) $object['id_sync_data'];
                        }
                        if (isset($object['ext_id_sync_data']) && $object['ext_id_sync_data']) {
                            $sync_data->ext_id = (int) $object['ext_id_sync_data'];
                            $ext_id_sync_data = $object['ext_id_sync_data'];
                        }
                        if (isset($object['id_object']) && $object['id_object']) {
                            $sync_data->setLocValues($this->processDefinition->id, $object_name, (int) $object['id_object']);
                            $id_object = $object['id_object'];
                        }
                        if (isset($object['ext_id_object']) && $object['ext_id_object']) {
                            $sync_data->setExtValues($ext_id_process, $ext_object_name, (int) $object['ext_id_object']);
                            $ext_id_object = $object['ext_id_object'];
                        }

                        $success = 0;

                        if ($sync_data->loadOrCreate(true)) {
                            $id_sync_data = $sync_data->id;
                            if ((!$id_object) && isset($sync_data->loc_id_object) && $sync_data->loc_id_object) {
                                $id_object = $sync_data->loc_id_object;
                            }

                            $this->setCurrentObject($object_name, $id_object ? $id_object : null, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);

                            // Suppression de l'objet: 
                            if ($id_object) {
                                $method = 'delete' . ucfirst($object_name);
                                if (!method_exists($this, $method)) {
                                    $msg = 'Echec de la suppression - Méthode "' . $method . '" inexistante';
                                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                                    $object_errors[] = $msg;
                                } elseif ($this->{$method}($id_object, $object_errors)) {
                                    $success = 1;
                                    $msg = 'Suppression effectuée avec succès';
                                    $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                                }
                            } else {
                                // L'objet n'exite pas. Les données de synchronisations sont obsolètes:
                                $sync_data->delete();
                                $success = 1;
                            }
                        } elseif (!$id_object) {
                            // L'objet n'existe pas, on simule une suppression réussie: 
                            $success = 1;
                        }

                        $result = array(
                            'success'          => $success,
                            'errors'           => $object_errors,
                            'id_object'        => $ext_id_object,
                            'ext_id_object'    => $id_object,
                            'id_sync_data'     => $ext_id_sync_data,
                            'ext_id_sync_data' => $id_sync_data,
                        );

                        $return[$ext_object_name][] = $result;
                    }
                }
            }
        }

        return $return;
    }

    // Traitement des exports d'objets:

    protected function initTriggerActionExecution($action, $object)
    {
        // Nécessaire pour éviter les boucles d'export / import infinies:
        if (!is_null($object) && is_object($object)) {
            if (isset($object->do_not_export) && (int) $object->do_not_export) {
                return false;
            }

            $object_name = $this->getObjectClass($object);
            if ($object_name && isset($object->id) && $object->id) {
                $status = BDS_SyncData::getObjectValue($this->db, 'status', $this->processDefinition->id, $object_name, $object->id, 'loc_id_object');
                if (!is_null($status) && ($status > 0)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function getObjectsExportData($object_name, $ext_object_name, $objects_ids, &$errors)
    {
        $objects_data = array(
            'object_name'     => $ext_object_name,
            'ext_object_name' => $object_name,
            'list'            => array()
        );

        $method = 'get' . ucfirst($object_name) . 'ExportData';

        if (method_exists($this, $method)) {
            foreach ($objects_ids as $id_object) {

                $data = $this->{$method}($id_object);

                if (!is_null($data)) {
                    $sync_data = new BDS_SyncData();
                    $sync_data->setLocValues((int) $this->processDefinition->id, $object_name, $id_object);
                    $sync_data->setExtValues(0, $ext_object_name, 0);
                    if (!$sync_data->loadOrCreate()) {
                        $msg = 'Echec de l\'initialisation des données de synchronisation. Export impossible';
                        $this->Error($msg, $object_name, $id_object);
                        continue;
                    }

                    if ($sync_data->status > 0) {
                        $msg = 'Des opérations de synchronisation sont en cours sur cet objet. Objet ignoré pour l\'export';
                        $this->Alert($msg, $object_name, $id_object);
                        continue;
                    }

                    $sync_data->status = self::BDS_STATUS_EXPORTING;

                    $objects_data['list'][] = array(
                        'ext_id_object'    => (int) $id_object,
                        'ext_id_sync_data' => (int) $sync_data->id,
                        'data'             => $data
                    );

                    $sync_data->save();
                    unset($sync_data);
                }
            }
        } else {
            $errors[] = 'Erreur technique: méthode "' . $method . '" inexistante';
        }

        return $objects_data;
    }

    protected function getObjectsImportData($object_name, $ext_object_name, $objects_ids, &$errors)
    {
        $objects_data = array(
            'object_name'     => $ext_object_name,
            'ext_object_name' => $object_name,
            'list'            => array()
        );

        foreach ($objects_ids as $id_object) {
            $sync_data = new BDS_SyncData();
            $sync_data->setLocValues((int) $this->processDefinition->id, $object_name, $id_object);
            $sync_data->setExtValues(0, $ext_object_name, 0);
            if (!$sync_data->loadOrCreate()) {
                $msg = 'Echec de l\'initialisation des données de synchronisation. Import impossible';
                $this->Error($msg, $object_name, $id_object);
                continue;
            }

            if ($sync_data->status > 0) {
                $msg = 'Des opérations de synchronisation sont en cours sur cet objet. Objet ignoré pour l\'import';
                $this->Alert($msg, $object_name, $id_object);
                continue;
            }

            $sync_data->status = self::BDS_STATUS_IMPORTING;

            $objects_data['list'][] = array(
                'ext_id_object'    => (int) $id_object,
                'ext_id_sync_data' => (int) $sync_data->id,
                'id_object'        => ($sync_data->ext_id_object ? $sync_data->ext_id_object : null),
                'id_sync_data'     => ($sync_data->ext_id ? $sync_data->ext_id : null),
            );

            $sync_data->save();
            unset($sync_data);
        }
        return $objects_data;
    }

    protected function getObjectsDeleteData($object_name, $ext_object_name, $objects_ids)
    {
        $objects_data = array(
            'object_name'     => $ext_object_name,
            'ext_object_name' => $object_name,
            'list'            => array()
        );

        foreach ($objects_ids as $id_object) {
            $object_data = array(
                'ext_id_object' => $id_object
            );
            $sync_data = new BDS_SyncData();
            $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
            if ($sync_data->loadOrCreate(true)) {
                $object_data['ext_id_sync_data'] = (int) $sync_data->id;
                if (isset($sync_data->ext_id) && $sync_data->ext_id) {
                    $object_data['id_sync_data'] = $sync_data->ext_id;
                }
                if (isset($sync_data->ext_id_object)) {
                    $object_data['id_object'] = (int) $sync_data->ext_id_object;
                }
                BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, $object_name, $id_object, self::BDS_STATUS_DELETING);
            }
            $objects_data['list'][] = $object_data;
        }
        return $objects_data;
    }

    protected function processObjectsExportResult($ext_id_process, $objects)
    {
        if (!is_null($objects) && count($objects)) {
            foreach ($objects as $object_name => $objects_list) {
                if (!is_null($objects_list) && count($objects_list)) {
                    foreach ($objects_list as $result) {
                        $this->processObjectExportResult($ext_id_process, $object_name, $result);
                    }
                }
            }
        }
    }

    protected function processObjectExportResult($ext_id_process, $object_name, $result)
    {
        if (!isset($result['id_object']) || !$result['id_object']) {
            $msg = 'ID de l\'objet ' . $object_name . 'absent';
            $msg .= '. Impossible de traiter les résultats de l\'export pour un objet';
            $this->Error($msg, $object_name, isset($result['ext_id_object']) && $result['ext_id_object'] ? 'ID externe: ' . $result['ext_id_object'] : null);
            return;
        }
        $id_object = $result['id_object'];
        $ext_object_name = '';
        $ext_id_object = 0;

        if (isset($result['ext_object_name'])) {
            $ext_object_name = $result['ext_object_name'];
        }

        if (isset($result['ext_id_object'])) {
            $ext_id_object = $result['ext_id_object'];
        }

        $sync_data = new BDS_SyncData(isset($result['id_sync_data']) ? $result['id_sync_data'] : null);
        $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
        $sync_data->setExtValues($ext_id_process, $ext_object_name, $ext_id_object);

        if (isset($result['ext_id_sync_data'])) {
            $sync_data->ext_id = (int) $result['ext_id_sync_data'];
        }

        if (!$sync_data->loadOrCreate()) {
            $msg = 'Echec de la récupération des données de synchronisation pour l\'objet "' . $object_name . '" d\'ID ' . $id_object;
            $msg .= '. Impossible de traiter les résultats de l\'export pour cet objet';
            $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
            return;
        }

        if (isset($result['sync_objects'])) {
            foreach ($result['sync_objects'] as $type => $objects) {
                $rows = array();
                foreach ($objects as $object) {
                    $rows[$object['loc_value']] = $object['ext_value'];
                }
                $sync_data->setObjects($type, $rows);
            }
        }

        if (isset($result['errors']) && count($result['errors'])) {
            $nErrors = count($result['errors']);
            $msg = $nErrors . ' erreur' . (($nErrors > 1) ? 's détectées' : ' détectée') . ': ';
            $msg .= '<ul>';
            foreach ($result['errors'] as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul>';
            $this->Error($msg, $object_name, $id_object);
            $sync_data->status = self::BDS_STATUS_EXPORT_FAIL;
        } else {
            $msg = 'Export effectué avec succès';
            $this->Success($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
            $sync_data->status = self::BDS_STATUS_SYNCHRONISED;
        }

        $errors = $sync_data->save();

        if (count($errors)) {
            $msg = 'Des erreurs sont survenues lors de l\'enregistrement des données de synchronisation';
            $msg .= '<ul>';
            foreach ($error as $e) {
                $msg .= '<li>' . $e . '</li>';
            }
            $msg .= '</ul>';
            $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        }

        if (isset($result['objects']) && count($result['objects'])) {
            $this->processObjectsExportResult($ext_id_process, $result['objects']);
        }
    }

    protected function processObjectsImportResult($ext_id_process, $objects)
    {
        if (!is_null($objects) && count($objects)) {
            foreach ($objects as $object_name => $objects_list) {
                if (!is_null($objects_list) && count($objects_list)) {
                    foreach ($objects_list as $result) {
                        $this->processObjectImportResult($ext_id_process, $object_name, $result);
                    }
                }
            }
        }
    }

    protected function processObjectImportResult($ext_id_process, $object_name, $result)
    {
        $ext_object_name = '';
        $ext_id_object = 0;
        $id_object = 0;

        if (isset($result['ext_object_name'])) {
            $ext_object_name = $result['ext_object_name'];
        }
        if (isset($result['ext_id_object'])) {
            $ext_id_object = $result['ext_id_object'];
        }
        if (isset($result['id_object'])) {
            $id_object = $result['id_object'];
        }

        $sync_data = new BDS_SyncData(isset($result['id_sync_data']) ? $result['id_sync_data'] : null);
        $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
        $sync_data->setExtValues($ext_id_process, $ext_object_name, $ext_id_object);
        if (isset($result['ext_id_sync_data'])) {
            $sync_data->ext_id = (int) $result['ext_id_sync_data'];
        }

        if (!$sync_data->loadOrCreate()) {
            $msg = 'Echec de la récupération des données de synchronisation pour l\'objet "' . $object_name . '" d\'ID ' . $id_object;
            $msg .= '. Impossible de traiter les résultats de l\'export pour cet objet';
            $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
            return;
        }

        if (isset($result['errors']) && count($result['errors'])) {
            $nErrors = count($result['errors']);
            $msg = $nErrors . ' erreur' . (($nErrors > 1) ? 's détectées' : ' détectée') . ': ';
            $msg .= '<ul>';
            foreach ($result['errors'] as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul>';
            $this->Error($msg, $object_name, $id_object);
            $sync_data->status = self::BDS_STATUS_IMPORT_FAIL;
        } else {
            $method = 'update' . ucfirst($object_name);

            if (method_exists($this, $method)) {
                $update_result = $this->{$method}($result['data'], $sync_data);

                if (isset($update_result['object']) && isset($update_result['object']->id) && $update_result['object']->id) {
                    if (!isset($sync_data->loc_id_object) || !$sync_data->loc_id_object) {
                        $sync_data->loc_id_object = (int) $update_result['object']->id;
                    }

                    if (!count($update_result['errors'])) {
                        $msg = 'Import effectué avec succès';
                        $this->Success($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
                        $sync_data->status = self::BDS_STATUS_SYNCHRONISED;
                    }
                }
            } else {
                $msg = 'Mise à jour impossible. Méthode "' . $method . '" inexistante';
                $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
            }
        }

        if ($sync_data->status !== self::BDS_STATUS_SYNCHRONISED) {
            $msg = 'Echec de l\'import';
            $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
            $sync_data->status = self::BDS_STATUS_IMPORT_FAIL;
        }

        $errors = $sync_data->save();
        unset($sync_data);

        if (count($errors)) {
            $msg = 'Des erreurs sont survenues lors de l\'enregistrement des données de synchronisation';
            $msg .= '<ul>';
            foreach ($error as $e) {
                $msg .= '<li>' . $e . '</li>';
            }
            $msg .= '</ul>';
            $this->Error($msg, $object_name, $id_object, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        }

        if (isset($result['objects']) && count($result['objects'])) {
            $this->processObjectsImportResult($ext_id_process, $result['objects']);
        }
    }

    protected function processObjectsDeleteResult($ext_id_process, $objects)
    {
        if (!is_null($objects) && count($objects)) {
            foreach ($objects as $object_name => $objects_list) {
                if (!is_null($objects_list) && count($objects_list)) {
                    foreach ($objects_list as $result) {
                        $this->processObjectDeleteResult($ext_id_process, $object_name, $result);
                    }
                }
            }
        }
    }

    protected function processObjectDeleteResult($ext_id_process, $object_name, $result)
    {
        if (!isset($result['id_object']) || !$result['id_object']) {
            $msg = 'ID de l\'objet ' . $object_name . 'absent';
            $msg .= '. Impossible de traiter les résultats de la requête de suppression de l\'objet';
            $this->Error($msg, $object_name, isset($result['ext_id_object']) && $result['ext_id_object'] ? 'ID externe: ' . $result['ext_id_object'] : null);
            $id_object = 0;
        } else {
            $id_object = $result['id_object'];
        }

        $this->setCurrentObject($object_name, $id_object ? $id_object : null, isset($result['ext_id_object']) ? 'ID externe: ' . $result['ext_id_object'] : null);

        if (isset($result['success']) && $result['success']) {
            $msg = 'Suppression effectuée avec succès';
            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());

            if ($id_object) {
                $sync_data = new BDS_SyncData(isset($result['id_sync_data']) ? $result['id_sync_data'] : null);
                $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
                if ($sync_data->loadOrCreate(true)) {
                    $errors = $sync_data->delete();
                    if (count($errors)) {
                        $msg = 'Des erreurs sont survenues lors de la suppression des données de synchronisations locales';
                        $msg .= '<ul>';
                        foreach ($errors as $e) {
                            $msg .= '<li>' . $e . '</li>';
                        }
                        $msg .= '</ul>';
                        $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    }
                } else {
                    $msg = 'Données de synchronisation non trouvées';
                    $this->Alert($msg);
                }
            }
        } else {
            BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, $object_name, $id_object, self::BDS_STATUS_DELETE_FAIL);
        }

        if (isset($result['objects']) && count($result['objects'])) {
            $this->processObjectsExportResult($ext_id_process, $result['objects']);
        }
    }

    protected function checkOptionsForObjectsExport($object_name, $objectsIds)
    {
        $export_mod = 'all';
        if (isset($this->options['export_mod']) && $this->options['export_mod']) {
            $export_mod = $this->options['export_mod'];
        }

        $export_fail = false;
        if (isset($this->options['allow_export_fail']) && $this->options['allow_export_fail']) {
            $export_fail = (int) $this->options['allow_export_fail'];
        }
        $import_fail = false;
        if (isset($this->options['allow_import_fail']) && $this->options['allow_import_fail']) {
            $import_fail = (int) $this->options['allow_import_fail'];
        }

        $sync_objects = BDS_SyncData::getObjectsList($this->db, $this->processDefinition->id, $object_name);

        $objects = array();
        $ignored = array();

        foreach ($objectsIds as $id_object) {
            if (!in_array($id_object, $objects)) {
                if (array_key_exists((int) $id_object, $sync_objects)) {
                    if ($export_mod === 'not_sync_only') {
                        $ignored[$id_object] = 'Déjà synchronisé';
                        continue;
                    }
                    $status = (int) $sync_objects[$id_object]['status'];

                    if ($status !== 0) {
                        if (in_array($status, array(self::BDS_STATUS_EXPORTING, self::BDS_STATUS_EXPORT_FAIL))) {
                            if (!$export_fail) {
                                $ignored[$id_object] = 'Dernier export échoué';
                                continue;
                            }
                        } elseif (in_array($status, array(self::BDS_STATUS_IMPORTING, self::BDS_STATUS_IMPORT_FAIL))) {
                            if (!$import_fail) {
                                $ignored[$id_object] = 'Dernier import échoué';
                                continue;
                            }
                        }
                        BDS_SyncData::updateStatusById($this->db, $sync_objects[$id_object]['id_sync_data'], self::BDS_STATUS_SYNCHRONISED);
                    }
                } elseif ($export_mod === 'sync_only') {
                    $ignored[$id_object] = 'Non synchronisé';
                    continue;
                }

                $objects[] = (int) $id_object;
            }
        }

        if (self::$debug_mod) {
            $this->debug_content .= '<h4>Objets "' . $object_name . '" exclus de l\'export</h4>';
            $this->debug_content .= '<pre>';
            $this->debug_content .= print_r($ignored, 1);
            $this->debug_content .= '</pre>';
        }

        return $objects;
    }

    protected function executeObjectExport($object_name, $id_object)
    {
        $sync_data = new BDS_SyncData();
        $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
        if ($sync_data->loadOrCreate(true)) {
            if (isset($sync_data->ext_object_name) && $sync_data->ext_object_name) {
                $this->current_object['ref'] = 'ID externe: ' . ($sync_data->ext_id_object ? $sync_data->ext_id_object : 'inconnu');
                $sync_data->status = 0;
                $sync_data->save();
                $errors = array();
                $objects = $this->getObjectsExportData($object_name, $sync_data->ext_object_name, array($id_object), $errors);
                if (!count($errors) && count($objects['list'])) {
                    $this->soapExportObjects(array($objects), static::$ext_process_name);
                } else {
                    foreach ($errors as $e) {
                        $this->Error($e, $this->curId(), $this->curName(), $this->curRef());
                    }
                }
            } else {
                $this->Error('Type d\'objet externe non enregistré');
            }
        } else {
            $this->Error('Données de synchronisation non trouvées pour cet objet');
        }
    }

    protected function executeObjectImport($object_name, $id_object)
    {
        $sync_data = new BDS_SyncData();
        $sync_data->setLocValues($this->processDefinition->id, $object_name, $id_object);
        if ($sync_data->loadOrCreate(true)) {
            if (isset($sync_data->ext_object_name) && $sync_data->ext_object_name) {
                $this->current_object['ref'] = 'ID externe: ' . ($sync_data->ext_id_object ? $sync_data->ext_id_object : 'inconnu');
                $sync_data->status = 0;
                $sync_data->save();
                $errors = array();
                $objects = $this->getObjectsImportData($object_name, $sync_data->ext_object_name, array($id_object), $errors);
                if (!count($errors) && count($objects['list'])) {
                    $this->soapImportObjects(array($objects), static::$ext_process_name);
                } else {
                    foreach ($errors as $e) {
                        $this->Error($e, $this->curId(), $this->curName(), $this->curRef());
                    }
                }
            } else {
                $this->Error('Type d\'objet externe non enregistré');
            }
        } else {
            $this->Error('Données de synchronisation non trouvées pour cet objet');
        }
    }

    // Traitement des objets Dolibarr:

    protected function updateObject($object_name, $data, $ext_id_sync_data, $ext_id_process = 0, $ext_object_name = '', $ext_id_object = 0)
    {
        $return = array(
            'id_sync_data'     => $ext_id_sync_data,
            'id_object'        => $ext_id_object,
            'ext_id_object'    => 0,
            'ext_object_name'  => $object_name,
            'ext_id_sync_data' => 0,
            'errors'           => array(),
            'objects'          => array(),
            'sync_objects'     => array(),
        );

        $method = 'update' . ucfirst($object_name);

        if (!method_exists($this, $method)) {
            $msg = 'Erreur technique: impossible de mettre à jour l\'objet de type "' . $object_name . '"';
            $msg .= ' - méthode "' . $method . '" absente';
            $return['errors'][] = $msg;
            $this->Error($msg);
            return $return;
        }

        $sync_data = new BDS_SyncData();
        $sync_data->ext_id = $ext_id_sync_data;
        $sync_data->setExtValues($ext_id_process, $ext_object_name, $ext_id_object);
        $sync_data->loc_object_name = $object_name;
        $sync_data->loc_id_process = (int) $this->processDefinition->id;

        if (!$sync_data->loadOrCreate()) {
            $msg = 'Echec de l\'initialisation des données de synchronisation';
            $return['errors'][] = $msg;
            $msg .= '. pour l\'objet d\'ID externe "' . $ext_id_object . '". Import impossible';
            $this->Error($msg, $object_name, isset($sync_data->loc_id_object) && $sync_data->loc_id_object ? $sync_data->loc_id_object : null);
            return $return;
        }

        $return['ext_id_sync_data'] = (int) $sync_data->id;

        $sync_data->status = self::BDS_STATUS_IMPORTING;

        $errors = $sync_data->save();

        if (count($errors)) {
            $msg = 'Erreurs lors de l\'enregistrement des données de synchronisation: ';
            $msg .= '<ul>';
            foreach ($errors as $e) {
                $msg .= '<li>' . $e . '</li>';
            }
            $msg .= '</ul>';
            $return['errors'][] = $msg;
            $msg .= 'Import impossible';
            $this->Error($msg, $object_name, isset($sync_data->loc_id_object) && $sync_data->loc_id_object ? $sync_data->loc_id_object : null);
            return $return;
        }

        // Mise à jour de l'objet selon le type (méthode à implémenter dans la classe BDS_SyncProcess spécifique):
        $result = $this->{$method}($data, $sync_data);

        if (isset($result['object']) && isset($result['object']->id) && $result['object']->id) {
            $return['ext_id_object'] = (int) $result['object']->id;
            $sync_data->loc_id_object = (int) $result['object']->id;
        }

        if (isset($result['objects'])) {
            $return['objects'] = $result['objects'];
        }

        if (isset($result['object']->error) && $result['object']->error) {
            $return['errors'][] = $this->ObjectError($result['object']);
        }

        if (isset($result['errors']) && count($result['errors'])) {
            $return['errors'] = BimpTools::merge_array($return['errors'], $result['errors']);
        }

        $return['sync_objects'] = $sync_data->getObjectsForExport();

        if (!count($return['errors'])) {
            if (isset($return['ext_id_object']) && $return['ext_id_object']) {
                $sync_data->status = self::BDS_STATUS_SYNCHRONISED;
            } else {
                $return['errors'][] = 'Echec de la création de l\'objet "' . $object_name . '" pour une raison inconnue';
                $sync_data->status = self::BDS_STATUS_IMPORT_FAIL;
            }
        } else {
            $sync_data->status = self::BDS_STATUS_IMPORT_FAIL;
        }

        $sync_data->save();
        unset($sync_data);

        return $return;
    }

    protected function saveObject(&$object, $label = null, &$errors = null, $display_success = false)
    {
        $isCurrentObject = $this->isCurrent($object);
        $object_name = $this->getObjectClass($object);
        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $object_name . '"';
        }

        if (!is_null($object) && is_object($object)) {
            $object->do_not_export = 1;
            if (isset($object->id) && $object->id) {
                if (method_exists($object, 'update')) {
                    if (in_array($object_name, array('Product', 'Societe', 'Contact'))) {
                        $result = $object->update($object->id, $this->user);
                    } else {
                        $result = $object->update($this->user);
                    }
                    if ($result <= 0) {
                        $msg = 'Echec de la mise à jour ' . $label;
                        if (!$isCurrentObject) {
                            $msg .= ' d\'ID: ' . $object->id;
                        }
                        $msg .= $this->ObjectError($object);

                        if (!is_null($errors)) {
                            $errors[] = $msg;
                        }

                        $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());

                        return false;
                    } else {
                        if ($isCurrentObject) {
                            $this->incUpdated();
                        }
                        if ($display_success || $isCurrentObject) {
                            $msg = 'Mise à jour ' . $label . ' effectuée avec succès';
                            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                        }
                        return true;
                    }
                } else {
                    $msg = 'Erreur technique: impossible d\'effectuer la mise à jour ' . $label . ' - Méthode "update()" inexistante';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    if (!is_null($errors)) {
                        $errors[] = $msg;
                    }
                }
            } else {
                if (method_exists($object, 'create')) {
                    $result = $object->create($this->user);
                    if ($result <= 0) {
                        $msg = 'Echec de la création ' . $label;
                        $msg .= $this->ObjectError($object);
                        if (!is_null($errors)) {
                            $errors[] = $msg;
                        }
                        $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                        return false;
                    } else {
                        if ($isCurrentObject) {
                            $this->current_object['id'] = $object->id;
                            $this->incCreated();
                        }
                        if ($display_success) {
                            $msg = 'Création ' . $label . ' effectuée avec succès';
                            if (!$isCurrentObject) {
                                $msg .= ' (ID: ' . $object->id . ')';
                            }
                            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                        }
                        return true;
                    }
                } else {
                    $msg = 'Erreur technique: impossible d\'effectuer la création ' . $label . ' - Méthode "create()" inexistante';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    if (!is_null($errors)) {
                        $errors[] = $msg;
                    }
                }
            }
        } else {
            $msg = 'Impossible d\'effectuer la création ' . $label . ' (Objet null)';
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
        }
        return false;
    }

    protected function deleteObject($object, $label = null, &$errors = null, $display_info = true)
    {
        if (!isset($object->id) || !$object->id) {
            if (!is_null($errors)) {
                $errors[] = 'Impossible de supprimer l\'objet (ID Absent)';
            }
            return false;
        }

        $object_name = $this->getObjectClass($object);
        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $object_name . '"';
        }

        $id_object = $object->id;
        $is_current_object = $this->isCurrent($object);

        if (method_exists($object, 'delete')) {
            $object->do_not_export = 1;
            if (in_array($object_name, array('Categorie', 'Product', 'Commande'))) {
                $result = $object->delete($this->user);
            } elseif (in_array($object_name, array('Societe'))) {
                $result = $object->delete($object->id, $this->user);
            } else {
                $result = $object->delete();
            }
            if ($result > 0) {
                if ($is_current_object || $display_info) {
                    $this->Info('Suppression ' . $label . ' d\'ID ' . $id_object . ' effectuée', $this->curName(), $is_current_object ? null : $this->curId(), $this->curRef());
                }
                if ($is_current_object) {
                    $this->incDeleted();
                }
                BDS_SyncData::deleteByLocObject($this->processDefinition->id, $object_name, $id_object, $errors);
                return true;
            } else {
                $msg = 'Echec de la suppression ' . $label;
                if (!$is_current_object) {
                    $msg .= ' d\'ID ' . $id_object;
                }
                $msg .= $this->ObjectError($object);
                if (!is_null($errors)) {
                    $errors[] = $msg;
                }
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                return false;
            }
        } else {
            $msg = 'Erreur technique: impossible d\'effectuer la suppression ' . $label;
            $msg .= ' - méthode "delete()" inexistante';
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
        }

        return false;
    }

    // Gestion statique des données de synchronisation des objets: 

    public static function getObjectProcessData($id_process, $id_object, $object_name)
    {
        $sync_data = new BDS_SyncData();
        $sync_data->setLocValues($id_process, $object_name, $id_object);
        if ($sync_data->loadOrCreate(true)) {
            $reference = 'Object externe: ';
            $reference .= $sync_data->ext_object_name ? '"' . $sync_data->ext_object_name . '"' : '(inconnu)';
            $reference .= ', ID: ' . ($sync_data->ext_id_object ? $sync_data->ext_id_object : 'iconnu');
            $actions = array();
            if (method_exists(static::getClassName(), 'get' . ucfirst($object_name) . 'ExportData')) {
                $actions['export'] = 'Exporter';
            }
            if (method_exists(static::getClassName(), 'update' . ucfirst($object_name))) {
                $actions['import'] = 'Importer';
            }
            return array(
                'references'   => $reference,
                'status_label' => static::$status_labels[(int) $sync_data->status],
                'status_value' => $sync_data->status,
                'date_add'     => $sync_data->date_add,
                'date_update'  => $sync_data->date_update,
                'actions'      => $actions
            );
        }
        return array();
    }

    public static function getObjectsStatusInfos($id_process, $object_name = null)
    {
        $data = array();

        global $db;
        $bdb = new BDSDb($db);

        $where = '`loc_id_process` = ' . (int) $id_process;

        if (!is_null($object_name)) {
            $where .= ' AND `loc_object_name` = \'' . $object_name . '\'';
        }
        $rows = $bdb->getRows(BDS_SyncData::$table, $where, null, 'object', array(
            'status', 'loc_object_name'
        ));
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                if (!isset($r->status) || !isset($r->loc_object_name) || !$r->loc_object_name) {
                    continue;
                }
                if (!isset($data[$r->loc_object_name])) {
                    $data[$r->loc_object_name] = array();
                }
                if (!isset($data[$r->loc_object_name][(int) $r->status])) {
                    $data[$r->loc_object_name][(int) $r->status] = array(
                        'label' => self::$status_labels[(int) $r->status],
                        'count' => 1
                    );
                } else {
                    $data[$r->loc_object_name][(int) $r->status]['count'] ++;
                }
            }
        }
        return $data;
    }

    public static function renderProcessObjectsList($process)
    {
        global $db;
        $bdb = new BDSDb($db);

        $sort_by = BDS_Tools::getValue('sort_by', 'date_update');
        $sort_way = BDS_Tools::getValue('sort_way', 'desc');

        $objects = BDS_SyncData::getAllObjectsList($bdb, $process->id, $sort_by, $sort_way);

        foreach ($objects as $object_name => &$object) {
            $object['nbFails'] = 0;
            $object['nbProcessing'] = 0;
            $rows = array();
            $object_label = ucfirst(BDS_Report::getObjectLabel($object_name));
            foreach ($object['list'] as $row) {
                $date_add = new DateTime($row['date_add']);
                $date_update = new DateTime($row['date_update']);

                $object_link = BDS_Tools::makeObjectUrl($object_name, $row['id_object']);
                $name = BDS_Tools::makeObjectName($bdb, $object_name, $row['id_object'], false);

                $label = '';
                if ($object_link) {
                    $label .= '<a href="' . $object_link . '" target="_blank">';
                    $label .= $name ? $name : $object_label . ' ' . $row['id_object'];
                    $label .= '</a>';
                } else {
                    $label .= $name ? $name : $object_label . ' ' . $row['id_object'];
                }

                $status = '<span class="';
                if ((int) $row['status'] < 0) {
                    $object['nbFails'] ++;
                    $status .= 'danger';
                } elseif ((int) $row['status'] > 0) {
                    $object['nbProcessing'] ++;
                    $status .= 'warning';
                } else {
                    $status .= 'success';
                }
                $status .= '">' . self::$status_labels[(int) $row['status']] . '</span>';

                $rows[] = array(
                    'id_data'            => $row['id_sync_data'],
                    'id_object'          => $row['id_object'],
                    'object_label_html'  => $label,
                    'object_label_value' => $name ? $name : null,
                    'ext_id_object'      => $row['ext_id_object'],
                    'date_add_html'      => $date_add->format('d / m / Y - H:i:s'),
                    'date_add_value'     => $row['date_add'],
                    'date_update_html'   => $date_update->format('d / m / Y - H:i:s'),
                    'date_update_value'  => $row['date_update'],
                    'status_html'        => $status,
                    'status_value'       => (int) $row['status']
                );
                unset($date_add);
                unset($date_update);
            }
            $object['list'] = $rows;
            $object['buttons'] = array();
            $object['bulkActions'] = array();
            $method = 'update' . ucfirst($object_name);
            if (method_exists(static::getClassName(), $method)) {
                $object['buttons'][] = array(
                    'label'   => 'Importer',
                    'class'   => 'butAction',
                    'onclick' => 'executeObjectProcess($(this), \'import\', ' . $process->id . ', \'{object_name}\', {id_object})'
                );
                $object['bulkActions'][] = array(
                    'function' => 'executeSelectedObjectProcess(\'import\', ' . $process->id . ', \'{object_name}\')',
                    'label'    => 'Importer les éléments sélectionnés'
                );
            }
            $method = 'get' . ucfirst($object_name) . 'ExportData';
            if (method_exists(static::getClassName(), $method)) {
                $object['buttons'][] = array(
                    'label'   => 'Exporter',
                    'class'   => 'butAction',
                    'onclick' => 'executeObjectProcess($(this), \'export\', ' . $process->id . ', \'{object_name}\', {id_object})'
                );
                $object['bulkActions'][] = array(
                    'function' => 'executeSelectedObjectProcess(\'export\', ' . $process->id . ', \'{object_name}\')',
                    'label'    => 'Exporter les éléments sélectionnés'
                );
            }
        }
        $fields = array(
            'id_object'     => array(
                'label'  => 'ID',
                'sort'   => true,
                'search' => 'text',
                'width'  => '5%'
            ),
            'object_label'  => array(
                'label_eval' => 'return ucfirst($object[\'label\']);',
                'sort'       => false,
                'search'     => 'text',
                'width'      => '20%'
            ),
            'ext_id_object' => array(
                'label'  => 'ID externe',
                'sort'   => true,
                'search' => 'text',
                'width'  => '7%'
            ),
            'date_add'      => array(
                'label'  => '1ère synchronisation',
                'sort'   => true,
                'search' => 'date',
                'width'  => '15%'
            ),
            'date_update'   => array(
                'label'  => 'Dernière mise à jour',
                'sort'   => true,
                'search' => 'date',
                'width'  => '15%'
            ),
            'status'        => array(
                'label'        => 'Statut',
                'sort'         => true,
                'search'       => 'select',
                'search_query' => self::$status_labels,
                'width'        => '10%'
            ),
        );

        return renderProcessObjectsList($objects, $fields);
    }

    // Outils de traitement des Catégories et Produits:

    protected function importProductImageByUrl($product, $file, $url, $dir = null, $cover = false)
    {
        if (is_null($dir)) {
            $dir = BDS_Tools::getProductImagesDir($product);
            if (is_null($dir)) {
                $msg = 'Impossible d\'importer l\'image "' . $url . '" (Répertoire non trouvé)';
                $this->Error($msg, 'Product', $product->id);
                return null;
            }
        }

        if ($cover) {
            $file = 'cover_' . $file;
        }

        $file_path = $dir . $file;

        if (!file_exists($dir)) {
            dol_mkdir($dir);
        }

        if (!file_exists($dir)) {
            $msg = 'Impossible d\'importer l\'image "' . $file . '" - ';
            $msg .= 'Echec de la création du répertoire de destination';
            $this->Error($msg, 'Product', $product->id);
            return null;
        }

        if (file_put_contents($file_path, file_get_contents($url))) {
            if (!function_exists('vignette')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
            }
            vignette($file_path, 160, 120, '_small', 50, "thumbs");
            vignette($file_path, 160, 120, '_mini', 50, "thumbs");
            return $file;
        } else {
            $msg = 'Echec de la création du fichier "' . $file_path . '" (' . $url . ')';
            $this->Error($msg, 'Product', $product->id);
        }
        return null;
    }

    // Divers

    public function getExtraTabs()
    {
        return array(
            array(
                'title' => 'Objets synchronisés',
                'name'  => 'objects',
                ''
            )
        );
    }
}
