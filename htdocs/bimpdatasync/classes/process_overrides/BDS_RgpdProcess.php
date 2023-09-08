<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RgpdProcess extends BDSProcess
{

    public static $default_public_title = 'Traiements RGPD';
    public static $object_default_params = array(
        'date_field'        => 'date_create',
        'date_create_field' => 'date_create',
        'status_field'      => 'status',
        'total_field'       => '',
        'client_field'      => 'id_client',
        'delete_drafts'     => 1,
        'delete_files'      => 1,
        'draft_value'       => 0,
        'client_activity'   => 1,
        'objects_defs_kw'   => ''
    );
    public static $objects = array(
        'propales'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Propal',
            'date_field'        => 'datep',
            'date_create_field' => 'datec',
            'total_field'       => 'total',
            'status_field'      => 'fk_statut',
            'client_field'      => 'fk_soc',
            'objects_defs_kw'   => 'Devis'
        ),
        'commandes' => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Commande',
            'date_field'        => 'date_commande',
            'date_create_field' => 'date_creation',
            'total_field'       => 'total_ttc',
            'status_field'      => 'fk_statut',
            'client_field'      => 'fk_soc',
            'objects_defs_kw'   => 'Commande'
        ),
        'factures'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Facture',
            'date_field'        => 'datef',
            'date_create_field' => 'datec',
            'total_field'       => 'total_ttc',
            'status_field'      => 'fk_statut',
            'client_field'      => 'fk_soc',
            'objects_defs_kw'   => 'Facture'
        ),
        'contrats'  => array(
            'module'            => 'bimpcontract',
            'object_name'       => 'BContract_contrat',
            'date_field'        => 'date_contrat',
            'date_create_field' => 'datec',
            'status_field'      => 'statut',
            'client_field'      => 'fk_soc',
            'objects_defs_kw'   => 'Contrat'
        ),
        'sav'       => array(
            'module'          => 'bimpsupport',
            'object_name'     => 'BS_SAV',
            'objects_defs_kw' => 'SAV'
        ),
        'tickets'   => array(
            'module'          => 'bimpsupport',
            'object_name'     => 'BS_Ticket',
            'delete_drafts'   => 0,
            'objects_defs_kw' => 'Ticket hotline'
        ),
        'fi'        => array(
            'module'            => 'bimptechnique',
            'object_name'       => 'BT_ficheInter',
            'date_field'        => 'datec',
            'date_create_field' => 'datec',
            'status_field'      => 'fk_statut',
            'client_field'      => 'fk_soc',
            'objects_defs_kw'   => 'Fiche inter'
        )
    );
    public static $objects_params = array();
    public static $objects_instances = array();

    // Init opérations:

    public function initTest(&$data, &$errors = array())
    {
        $html = '';

        $data = $this->getElementsToProcess();

        if (isset($data['drafts']) && !empty($data['drafts'])) {
            $html .= '<h3>Pièces brouillons à supprimer</h3>';
            foreach ($data['drafts'] as $type => $elements) {
                $instance = $this->getObjectInstance($type);

                $list_html = '';

                foreach ($elements as $id_object) {
                    $instance->id = $id_object;

                    if (is_object($instance->dol_object)) {
                        $instance->dol_object->id = $id_object;
                    }

                    $url = $instance->getUrl();

                    if ($url) {
                        $list_html .= '- <a href="' . $url . '" target="_blanck">#' . $id_object . '</a><br/>';
                    } else {
                        $list_html .= '- #' . $id_object . '<br/>';
                    }
                }

                $title = BimpTools::ucfirst($instance->getLabel('name_plur')) . ' à supprimer (' . count($elements) . ')';
                $html .= BimpRender::renderFoldableContainer($title, $list_html, array(
                            'open'        => false,
                            'offset_left' => true
                ));
            }
        }

        if (isset($data['files']) && !empty($data['files'])) {
            $html .= '<h3>Pièces dont les fichiers sont à supprimer</h3>';
            foreach ($data['files'] as $type => $elements) {
                $instance = $this->getObjectInstance($type);

                $list_html = '';

                foreach ($elements as $id_object) {
                    $instance->id = $id_object;

                    if (is_object($instance->dol_object)) {
                        $instance->dol_object->id = $id_object;
                    }

                    $url = $instance->getUrl();

                    if ($url) {
                        $list_html .= '- <a href="' . $url . '" target="_blanck">#' . $id_object . '</a><br/>';
                    } else {
                        $list_html .= '- #' . $id_object . '<br/>';
                    }
                }

                $title = BimpTools::ucfirst($instance->getLabel('name_plur')) . ' (' . count($elements) . ')';
                $html .= BimpRender::renderFoldableContainer($title, $list_html, array(
                            'open'        => false,
                            'offset_left' => true
                ));
            }
        }

        if (isset($data['clients']) && !empty($data['clients'])) {
            $instance = BimpObject::getInstance('bimpcore', 'Bimp_Client');
            $html .= '<h3>Clients à anonymiser</h3>';

            $list_html = '';

            foreach ($data['clients'] as $id_client) {
                $instance->id = $id_client;
                $instance->dol_object->id = $id_client;

                $url = $instance->getUrl();

                if ($url) {
                    $list_html .= '- <a href="' . $url . '" target="_blanck">#' . $id_client . '</a><br/>';
                } else {
                    $list_html .= '- #' . $id_client . '<br/>';
                }
            }

            $title = BimpTools::ucfirst($instance->getLabel('name_plur')) . ' (' . count($data['clients']) . ')';
            $html .= BimpRender::renderFoldableContainer($title, $list_html, array(
                        'open'        => false,
                        'offset_left' => true
            ));
        }

        $data['result_html'] = $html;
    }

    public function initDailyCheck(&$data, &$errors = array())
    {
        $data['has_finalization'] = 1;
        $data['steps'] = array();

        $elements = $this->getElementsToProcess();

        $this->DebugData($elements, 'Objets à traiter');

        if (isset($elements['drafts']) && !empty($elements['drafts'])) {
            foreach ($elements['drafts'] as $type => $list) {
                if (!empty($list)) {
                    $instance = $this->getObjectInstance($type);

                    $data['steps']['delete_drafts_' . $type] = array(
                        'label'                  => 'Suppression des ' . $instance->getLabel('name_plur') . ' brouillons',
                        'on_error'               => 'continue',
                        'elements'               => $list,
                        'nbElementsPerIteration' => 10
                    );
                }
            }
        }

        if (isset($elements['files']) && !empty($elements['files'])) {
            foreach ($elements['files'] as $type => $list) {
                if (!empty($list)) {
                    $instance = $this->getObjectInstance($type);

                    $data['steps']['delete_files_' . $type] = array(
                        'label'                  => 'Suppression des fichiers pour les ' . $instance->getLabel('name_plur'),
                        'on_error'               => 'continue',
                        'elements'               => $list,
                        'nbElementsPerIteration' => 50
                    );
                }
            }
        }

        $where = 'client IN (1,2,3) AND solvabilite_status = 0';
        $where .= ' AND (date_last_activity IS NULL)'; // OR date_last_activity = \'0000-00-00\')';
        $nb_clients = (int) $this->db->getCount('societe', $where, 'rowid');

        if ($nb_clients > 0 && $nb_clients <= 1000) {
            $pages = array();
            for ($i = 1; $i <= ceil($nb_clients / 100); $i++) {
                $pages[] = $i;
            }

            $data['steps']['check_clients'] = array(
                'check_clients' => array(
                    'label'                  => 'Vérification de la dernière activité des clients',
                    'on_error'               => 'continue',
                    'elements'               => $pages,
                    'nbElementsPerIteration' => 1
                )
            );
        } elseif ($nb_clients > 1000) {
            $msg = 'Il y a plus de 1000 clients dont la date de dernière activité n\'est pas définie.<br/>';
            $msg .= 'Il est nécessaire de lancer l\'opération "Vérifier les dates de dernière activité de tous les clients" depuis un navigateur';
            $this->Alert($msg);
            BimpCore::addlog('Processus RGPD: vérification des dates de dernière activité des clients nécessaire', Bimp_Log::BIMP_LOG_URGENT, 'bds', $this->process, array(
                'Nombre de dates non définies' => $nb_clients
            ));
        }

        if (isset($elements['clients']) && !empty($elements['clients'])) {
            $data['steps']['anonymise_clients'] = array(
                'label'                  => 'Anonymisation des clients',
                'on_error'               => 'continue',
                'elements'               => $elements['clients'],
                'nbElementsPerIteration' => 50
            );
        }
    }

    public function initFilesToDelete(&$data, &$errors = array())
    {
        $data['has_finalization'] = 1;
        $data['steps'] = array();

        foreach (self::$objects as $type => $params) {
            if ((int) $this->getOption('process_' . $type, 0)) {
                $params = $this->getObjectParams($type);

                if ((int) $params['delete_files']) {
                    $instance = $this->getObjectInstance($type);

                    $data['steps']['find_files_to_delete_' . $type] = array(
                        'label'    => 'Recherche des fichiers à supprimer pour les ' . $instance->getLabel('name_plur'),
                        'on_error' => 'continue',
                    );
                }
            }
        }
    }

    public function initCheckClientsActivity(&$data, &$errors = array())
    {
        if ((int) $this->getOption('null_only')) {
            $soc_instance = BimpObject::getInstance('bimpcore', 'Bimp_Client');

            $filters = array(
                'client'             => array(
                    'in' => array(1, 2, 3)
                ),
                'solvabilite_status' => 0
            );

            if ((int) $this->getOption('null_only')) {
//                $filters['date_last_activity'] = 'IS_NULL';
                $filters['date_last_activity'] = array(
                    'or_field' => array(
                        'IS_NULL',
                        '0000-00-00'
                    )
                );
            }

            $rows = $soc_instance->getList($filters, ((int) $this->getOption('test_one', 0) ? 1 : 0), null, 'rowid', 'ASC');
            $clients = array();

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $clients[] = (int) $r['rowid'];
                }
            }

            if (!empty($clients)) {
                $data['steps'] = array(
                    'check_clients' => array(
                        'label'                  => 'Vérification de la dernière activité des clients',
                        'on_error'               => 'retry',
                        'elements'               => $clients,
                        'nbElementsPerIteration' => 100
                    )
                );
            } else {
                $data['result_html'] = BimpRender::renderAlerts('Aucun client à traiter', 'warning');
            }
        } else {
            if ((int) $this->getOption('test_one', 0)) {
                $nb_clients = 1;
            } else {
                $where = 'client IN (1,2,3) AND solvabilite_status = 0';
                $nb_clients = (int) $this->db->getCount('societe', $where, 'rowid');
            }

            if ($nb_clients) {
                $pages = array();
                for ($i = 1; $i <= ceil($nb_clients / 100); $i++) {
                    $pages[] = $i;
                }

                $data['steps'] = array(
                    'check_clients' => array(
                        'label'                  => 'Vérification de la dernière activité des clients',
                        'on_error'               => 'retry',
                        'elements'               => $pages,
                        'nbElementsPerIteration' => 1
                    )
                );
            } else {
                $data['result_html'] = BimpRender::renderAlerts('Aucun client à traiter', 'warning');
            }
        }
    }

    // Exec opérations:

    public function executeDailyCheck($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        global $rgpd_processing;
        $rgpd_processing = true;

        if (preg_match('/^delete_drafts_(.+)$/', $step_name, $matches)) {
            $this->deleteDrafts($matches[1], $this->references, $errors);
        } elseif (preg_match('/^delete_files_(.+)$/', $step_name, $matches)) {
            $this->deleteFiles($matches[1], $this->references, $errors);
        } elseif ($step_name === 'check_clients') {
            if (isset($this->references[0]) && (int) $this->references[0]) {
                $clients = $this->findClientCheckActivity((int) $this->references[0], $errors);

                if (!empty($clients)) {
                    $this->checkClientsActivity($clients, $errors);
                }
            }
        } elseif ($step_name === 'anonymise_clients') {
            $this->anonymiseClients($this->references, $errors);
        }

        $rgpd_processing = false;

        return $result;
    }

    public function executeFilesToDelete($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        global $rgpd_processing;
        $rgpd_processing = true;

        if (preg_match('/^find_files_to_delete_(.+)$/', $step_name, $matches)) {
            $elements = $this->findFilesToDelete($matches[1]);
            if (isset($elements[$matches[1]]) && !empty($elements[$matches[1]])) {
                $instance = $this->getObjectInstance($matches[1]);

                $this->DebugData($elements[$matches[1]], BimpTools::ucfirst($instance->getLabel('name_plur')));

                $result['new_steps'] = array(
                    'process_files_to_delete_' . $matches[1] => array(
                        'label'                  => 'Suppression des fichiers pour les ' . $instance->getLabel('name_plur'),
                        'on_error'               => 'continue',
                        'elements'               => $elements[$matches[1]],
                        'nbElementsPerIteration' => 50
                    )
                );
            }
        } elseif (preg_match('/^process_files_to_delete_(.+)$/', $step_name, $matches)) {
            $this->deleteFiles($matches[1], $this->references, $errors);
        }

        $rgpd_processing = false;

        return $result;
    }

    public function executeCheckClientsActivity($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        global $rgpd_processing;
        $rgpd_processing = true;

        switch ($step_name) {
            case 'check_clients':
                if ((int) $this->getOption('null_only')) {
                    $this->checkClientsActivity($this->references, $errors);
                } else {
                    if (isset($this->references[0]) && (int) $this->references[0]) {
                        $clients = $this->findClientCheckActivity((int) $this->references[0], $errors);

                        if (!empty($clients)) {
                            $this->checkClientsActivity($clients, $errors);
                        }
                    }
                }
                break;
        }

        $rgpd_processing = false;

        return $result;
    }

    // Finalisation opérations: 

    public function finalizeDailyCheck(&$errors = array(), $extra_data = array())
    {
        $this->saveFilesDates($errors);

        return array();
    }

    public function finalizeFilesToDelete(&$errors = array(), $extra_data = array())
    {
        $this->saveFilesDates($errors);

        return array();
    }

    // Recherche des éléments à traiter: 

    public function getElementsToProcess($allowed = array())
    {
        $allowed = BimpTools::overrideArray(array(
                    'delete_drafts'     => 1,
                    'delete_files'      => 1,
                    'anonymize_clients' => 1
                        ), $allowed);

        $data = array(
            'drafts'  => array(),
            'files'   => array(),
            'clients' => array()
        );

        // Recherche des pièces brouillons à suppr. 
        if ($allowed['delete_drafts'] && (int) $this->getOption('delete_drafts', 0)) {
            $data['drafts'] = $this->findDraftsToDelete();
        }

        // Recherche des pièces dont les fichiers sont à supprimer: 
        if ($allowed['delete_files'] && (int) $this->getOption('delete_files', 0)) {
            $data['files'] = $this->findFilesToDelete('all');
        }

        // Recherche des clients à anonymiser: 
        if ($allowed['anonymize_clients'] && (int) $this->getOption('anonymize_clients', 0)) {
            $data['clients'] = $this->findClientsToAnonymise();
        }

        return $data;
    }

    public function findDraftsToDelete()
    {
        $data = array();

        $dt = new DateTime();
        $dt->sub(new DateInterval($this->getParam('drafts_delay_interval', 'P3Y')));
        $date_delete_drafs = $dt->format('Y-m-d');
        $test_one = (int) $this->getOption('test_one', 0);
        $excluded_clients = $this->getParam('excluded_clients', '');

        foreach (self::$objects as $type => $params) {
            $params = $this->getObjectParams($type);

            if (!(int) $params['delete_drafts'] || !(int) $this->getOption('process_' . $type, 0)) {
                continue;
            }

            $obj = $this->getObjectInstance($type);
            $primary = $obj->getPrimary();

            $where = $params['status_field'] . ' = ' . $params['draft_value'];
            $where .= ' AND ' . $params['date_create_field'] . ' <= \'' . $date_delete_drafs . '\'';

            if ($excluded_clients) {
                $where .= ' AND ' . $params['client_field'] .= ' NOT IN (' . $excluded_clients . ')';
            }

            $rows = $this->db->getRows($obj->getTable(), $where, null, 'array', array($primary));

            if (is_array($rows)) {
                $data[$type] = array();

                foreach ($rows as $r) {
                    $data[$type][] = (int) $r[$primary];

                    if ($test_one) {
                        break;
                    }
                }
            } else {
                $this->Error('Erreurn SQL : ' . $this->db->err());
            }
        }

        return $data;
    }

    public function findFilesToDelete($type = 'all')
    {
        // Recherche de fichiers dont délai légal de conservation dépassé ET client anonymisé. 
        $data = array();
        $types = array();

        $dt = new DateTime();
        $dt->sub(new DateInterval('P10Y'));
        $date_to_10y = $dt->format('Y-m-d');

        if ($type === 'all') {
            foreach (self::$objects as $type => $obj_params) {
                if ((int) $this->getOption('process_' . $type, 0)) {
                    $types[] = $type;
                }
            }
        } else {
            $types = array($type);
        }

        $test_one = (int) $this->getOption('test_one', 0);

        foreach ($types as $type) {
            $params = $this->getObjectParams($type);
            if (!$params['delete_files']) {
                continue;
            }

            $date_from_10y = $this->getParam('files_from_10y_' . $type, '0000-00-00');

            $data[$type] = array();

            $instance = $this->getObjectInstance($type);
            $table = $instance->getTable();
            $primary = $instance->getPrimary();

            // Recherche pièces de + de 10 ans:
            $where = 'a.' . $params['date_create_field'] . ' > \'' . $date_from_10y . '\'';
            $where .= ' AND ' . 'a.' . $params['date_create_field'] . ' <= \'' . $date_to_10y . '\'';

            $rows = $this->db->getRows($table . ' a', $where, null, 'array', array('a.' . $primary), null, null, array(
                's' => array(
                    'table' => 'societe',
                    'on'    => 'a.' . $params['client_field'] . ' = s.rowid',
                    'alias' => 's'
                )
            ));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $data[$type][] = (int) $r[$primary];

                    if ($test_one) {
                        break;
                    }
                }
            } else {
                die($this->db->err());
            }

            if ($type === 'propales') {
                // Propales refusées de + de 3 ans : 
                $date_from_3y = $this->getParam('files_from_3y_propales', '0000-00-00');

                $dt = new DateTime();
                $dt->sub(new DateInterval('P3Y'));
                $date_to_3y = $dt->format('Y-m-d');

                $where = $params['date_create_field'] . ' > \'' . $date_from_3y . '\' AND ' . $params['date_create_field'] . ' <= \'' . $date_to_3y . '\'';
                $where .= ' AND fk_statut = 3';

                $rows = $this->db->getRows($table, $where, null, 'array', array($primary));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $data[$type][] = (int) $r[$primary];

                        if ($test_one) {
                            break;
                        }
                    }
                } else {
                    die($this->db->err());
                }
            }
        }

        return $data;
    }

    public function findClientCheckActivity($p = 1, &$errors = array())
    {
        $clients = array();

        if ($p) {
            $soc_instance = BimpObject::getInstance('bimpcore', 'Bimp_Client');

            $filters = array(
                'client'             => array(
                    'in' => array(1, 2, 3)
                ),
                'solvabilite_status' => 0
            );

            if ((int) $this->getOption('null_only')) {
                $filters['date_last_activity'] = 'IS_NULL';
//                $filters['date_last_activity'] = array(
//                    'or_field' => array(
//                        'IS_NULL',
//                        '0000-00-00'
//                    )
//                );
            }

            $n = ((int) $this->getOption('test_one', 0) ? 1 : 100);
            $rows = $soc_instance->getList($filters, $n, $p, 'rowid', 'ASC');

            if (!empty($rows)) {
                $clients = array();

                foreach ($rows as $r) {
                    $clients[] = (int) $r['rowid'];
                }

                $this->DebugData($clients, 'Clients dont la date de dernière activité est à définir');
            }
        }

        return $clients;
    }

    public function findClientsToAnonymise()
    {
        if ((int) $this->getOption('test_one', 0)) {
            $limit = 1;
        } else {
            $limit = (int) $this->getOption('clients_to_anonymise_limit', 0);
        }

        $clients = array();
        $excluded_clients = $this->getParam('excluded_clients', '');

        // Recherche clients pros sans activité depuis 10 ans: 
        $dt = new DateTime();
        $dt->sub(new DateInterval('P5Y'));
        $dt_5y = $dt->format('Y-m-d');

        $where = 'is_anonymized = 0 AND client IN (1,2,3)';
        $where .= ' AND date_last_activity IS NOT NULL AND date_last_activity > \'0000-00-00\' AND date_last_activity <= \'' . $dt_5y . '\'';

        if ($excluded_clients) {
            $where .= ' AND rowid NOT IN (' . $excluded_clients . ')';
        }

        $rows = $this->db->getRows('societe', $where, ($limit ? $limit : null), 'array', array('rowid'), 'date_last_activity', 'ASC');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $clients[] = (int) $r['rowid'];
            }
        }

        return $clients;
    }

    // Traitements: 

    public function deleteDrafts($type, $list, &$errors = array())
    {
        if (!isset(self::$objects[$type])) {
            $errors[] = 'Type "' . $type . '" non défini';
            return;
        }

        $params = $this->getObjectParams($type);

        if (!(int) $params['delete_drafts']) {
            $errors[] = 'Les brouillons ne sont pas supprimables pour les objets de type "' . $type . '"';
            return;
        }

        $instance = $this->getObjectInstance($type);
        $this->setCurrentObject($instance);

        foreach ($list as $id_object) {
            $this->incProcessed();
            $object = BimpCache::getBimpObjectInstance($params['module'], $params['object_name'], $id_object);

            if (!BimpObject::objectLoaded($object)) {
                $this->incIgnored();
                $this->Error(BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe plus', $instance, '#' . $id_object);
            } else {
                $ref = $object->getRef(true);
                $del_warnings = array();
                $del_errors = $object->delete($del_warnings, true);

                if (count($del_errors)) {
                    $this->incIgnored();
                    $this->Error(BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression'), $object, $ref);
                } else {
                    $this->incDeleted();
                    $this->Success('Suppression ' . $instance->getLabel('of_the') . ' #' . $id_object . ' effectuée avec succès', $instance, $ref);
                }

                if (count($del_warnings)) {
                    $this->Alert(BimpTools::getMsgFromArray($del_warnings, 'Erreurs suite à la suppression ' . $instance->getLabel('of_the') . ' #' . $id_object), $instance, $ref);
                }
            }
        }
    }

    public function deleteFiles($type, $list, &$errors = array())
    {
        if (!isset(self::$objects[$type])) {
            $errors[] = 'Type "' . $type . '" non défini';
            return;
        }

        $params = $this->getObjectParams($type);
        $this->setCurrentObjectData('bimpcore', 'BimpFile');

        global $bimp_errors_handle_locked;
        $bimp_errors_handle_locked = true;
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);

        foreach ($list as $id_object) {
            $object = BimpCache::getBimpObjectInstance($params['module'], $params['object_name'], $id_object);

            if (BimpObject::objectLoaded($object)) {
                $dir = $object->getFilesDir();

                if ($dir) {
                    if (preg_match('/^(.+)\/$/', $dir, $matches)) {
                        $dir = $matches[1];
                    }

                    foreach (array('', '_anonymized') as $suffix) {
                        if (is_dir($dir . $suffix)) {
                            $files = scandir($dir);
                            $this->DebugData($files, 'Fichiers (Dossier: ' . $dir . ')');

                            $files_ok = array();
                            $files_fails = array();
                            foreach ($files as $f) {
                                if (in_array($f, array('.', '..'))) {
                                    continue;
                                }

                                $this->incProcessed();
                                error_clear_last();

                                if (!unlink($dir . '/' . $f)) {
                                    $this->incIgnored();
                                    $err = error_get_last();
                                    $files_fails[] = $f . (isset($err['message']) ? ' - ' . $err['message'] : '');
                                } else {
                                    $this->incDeleted();
                                    $files_ok[] = $f;
                                    $this->Success('Suppression OK', $object, $f);
                                }
                            }

                            if (count($files_ok)) {
                                $msg = count($files_ok) . ' fichier(s) supprimé(s) avec succès';
                                foreach ($files_ok as $file) {
                                    $msg .= '<br/><b>' . $file . '</b>';
                                }
                                $this->Success($msg, $object, $object->getRef());
                            }

                            if (count($files_fails)) {
                                $msg = count($files_fails) . ' échecs de suppression de fichier';
                                foreach ($files_ok as $file) {
                                    $msg .= '<br/><b>' . $file . '</b>';
                                }
                                $this->Error($msg, $object, $object->getRef());
                            }
                        }
                    }
                }
            }
        }

        $bimp_errors_handle_locked = false;
    }

    public function checkClientsActivity($clients, &$errors = array(), &$warnings = array(), &$infos = '', $inc_obj_counts = true)
    {
        $client_instance = BimpObject::getInstance('bimpcore', 'Bimp_Client');

        if ($inc_obj_counts) {
            $this->setCurrentObject($client_instance);
        }

        foreach ($clients as $id_client) {
            if ($inc_obj_counts) {
                $this->incProcessed();
            }

            $check_factures = false;

            $date_last_activity = $this->db->getValue('societe', 'date_last_activity', 'rowid = ' . $id_client);
            $origin = '';

            if (is_null($date_last_activity) || $date_last_activity === '0000-00-00') {
                $date_last_activity = $this->db->getValue('societe', 'datec', 'rowid = ' . $id_client);
                $origin = 'Création de la fiche client';
            }

            if ($date_last_activity === '0000-00-00 00:00:00') {
                $date_last_activity = '0000-00-00';
            } elseif ($date_last_activity) {
                $date_last_activity = date('Y-m-d', strtotime($date_last_activity));
            } else {
                $date_last_activity = '0000-00-00';
            }

            if ($date_last_activity === '0000-00-00') {
                $check_factures = true;
            }

//            $this->debug_content .= '<br/>Client #' . $id_client . ': date: ' . $date_last_activity . ' - Orgine: ' . $origin;

            foreach (self::$objects as $type => $params) {
                if ($type === 'factures' && !$check_factures) {
                    continue;
                }

                $params = $this->getObjectParams($type);

                if (!$params['client_activity'] || !$params['client_field']) {
                    continue;
                }

                $instance = $this->getObjectInstance($type);
                $primary = $instance->getPrimary();

                $where = $params['client_field'] . ' = ' . $id_client;

                if ($type == 'propales') {
                    $where .= ' AND fk_statut != 3';
                }

                $rows = $this->db->getRows($instance->getTable(), $where, '1', 'array', array($primary . ' as id', $params['date_create_field'] . ' as datec'), $params['date_create_field'], 'DESC');

                if (isset($rows[0])) {
                    $data = $rows[0];

                    if ($data['datec']) {
                        $datec = date('Y-m-d', strtotime($data['datec']));

                        if ($datec > $date_last_activity) {
                            $date_last_activity = $datec;
                            $origin = 'Création ' . $instance->getLabel('of_the') . ($params['objects_defs_kw'] ? ' {{' . $params['objects_defs_kw'] . ':' . $data['id'] . '}}' : ' #' . $data['id']);
                        }
                    }
                }
            }

            if (!$date_last_activity || $date_last_activity == '0000-00-00') {
                $sql = 'SELECT s.datec FROM ' . MAIN_DB_PREFIX . 'societe s';
                $sql .= ' WHERE s.rowid = (SELECT MAX(s2.rowid) FROM llx_societe s2 WHERE s2.datec > \'0000-00-00 00:00:00\' AND s2.rowid < ' . $id_client . ')';
                $result = $this->db->executeS($sql, 'array');
                if (isset($result[0]['datec']) && $result[0]['datec']) {
                    $date_last_activity = date('Y-m-d', strtotime($result[0]['datec']));
                    $origin = 'Date de création estimée de la fiche client (client importé - date de création réelle inconnue)';
                }
            }

            if ($origin) {
                $this->debug_content .= '<br/>NEW #' . $id_client . ': date: ' . $date_last_activity . ' - Orgine: ' . $origin;
                if ($this->db->update('societe', array(
                            'date_last_activity'   => $date_last_activity,
                            'last_activity_origin' => $origin
                                ), 'rowid = ' . $id_client) > 0) {
                    if ($inc_obj_counts) {
                        $this->incUpdated();
                    }

                    $infos .= ($infos ? '<br/><br/>' : '') . '- Mise à jour de la date de dernière activité pour le client #' . $id_client . '<br/>';
                    $infos .= 'Nouvelle date: <b>' . date('d / m / Y', strtotime($date_last_activity)) . '</b><br/>';
                    $infos .= 'Origine: <b>' . $origin . '</b>';
                    continue;
                } else {
                    $msg = 'Echec màj date dernière activité - ' . $this->db->err();
                    $this->Error($msg, $client_instance, $id_client);
                    $warnings[] = $msg;
                }
            }

            if ($inc_obj_counts) {
                $this->incIgnored();
            }
        }
    }

    public function anonymiseClients($clients, &$errors = array())
    {
        $this->setCurrentObjectData('bimpcore', 'Bimp_Client');

        $dt = new DateTime();
        $dt->sub(new DateInterval('P5Y'));
        $dt_5y = $dt->format('Y-m-d');

        foreach ($clients as $id_client) {
            $this->incProcessed();

            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

            if (BimpObject::objectLoaded($client)) {
                // Vérif date dernière activité (par précaution): 
                $check_errors = array();
                $check_warnings = array();
                $infos = '';
                $this->checkClientsActivity(array($id_client), $check_errors, $check_warnings, $infos, false);

                if (count($check_warnings)) {
                    $msg = BimpTools::getMsgFromArray($check_warnings, 'Erreurs lors de la vérification de la date de dernière activité - Anonymisation non effectuée');
                    $this->Error($msg, $client, $client->getRef());
                    $this->incIgnored();
                    continue;
                }

                // Vérif date dernière activité toujours ko:
                $date_last_activity = $client->getSavedData('date_last_activity');
                if ($date_last_activity > $dt_5y) {
                    $this->incIgnored();
                    continue;
                }

                // Anonymisation des données: 
                $client_warnings = array();
                $client_errors = $client->anonymiseData(true, 'Dernière activité le ' . date('d / m / Y', strtotime($date_last_activity)), $client_warnings, $this);

                if (count($client_errors)) {
                    $this->Error(BimpTools::getMsgFromArray($client_errors, 'Erreurs anonymisation des données'), $client, $client->getRef());
                    $this->incIgnored();
                } else {
                    $this->Success('Anonymisation des données effectuée avec succès (Date dernière activité: ' . date('d / m / Y', strtotime($date_last_activity)) . ')', $client, $client->getRef());
                    $this->incUpdated();
                }

                if (count($client_warnings)) {
                    $this->Alert(BimpTools::getMsgFromArray($client_warnings, 'Erreurs lors de l\'anonymisation des données'), $client, $client->getRef());
                }
            } else {
                $this->incIgnored();
            }
        }
    }

    public function saveFilesDates(&$errors = array())
    {
        if (!(int) $this->getOption('delete_files', 0)) {
            return;
        }

        if ((int) $this->getOption('test_one', 0)) {
            $this->debug_content .= BimpRender::renderAlerts('Mode test : pas de màj des dates min de recherche des fichiers à supprimer', 'warning');
            return;
        }

        $dates_errors = array();

        $dt = new DateTime();
        $dt->sub(new DateInterval('P10Y'));
        $date_10y = $dt->format('Y-m-d');

        $dt = new DateTime();
        $dt->sub(new DateInterval('P5Y'));
        $date_5y = $dt->format('Y-m-d');

        foreach (self::$objects as $type => $obj_params) {
            if ((int) $this->getOption('process_' . $type, 0)) {
                $param_errors = array();
                $param = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessParam', array(
                            'id_process' => (int) $this->process->id,
                            'name'       => 'files_from_10y_' . $type
                                ), true);

                if (!BimpObject::objectLoaded($param)) {
                    $instance = $this->getObjectInstance($type);

                    $param = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                                'id_process' => (int) $this->process->id,
                                'name'       => 'files_from_10y_' . $type,
                                'label'      => 'Date min recherche fichiers à supprimer pour les ' . $instance->getLabel('name_plur') . ' (10 ans)',
                                'value'      => $date_10y
                                    ), true, $param_errors);

                    if (count($param_errors)) {
                        $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec création du paramètre "files_from_10y_' . $type . '"');
                    } else {
                        $this->debug_content .= BimpRender::renderAlerts('Ajout paramètre "' . $param->getData('label') . '" OK', 'success');
                    }
                } else {
                    $param_errors = $param->updateField('value', $date_10y);

                    if (count($param_errors)) {
                        $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec mise à jour du paramètre "files_from_10y_' . $type . '"');
                    } else {
                        $this->debug_content .= BimpRender::renderAlerts('Màj paramètre "' . $param->getData('label') . '" OK', 'success');
                    }
                }


                $param_errors = array();
                $param = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessParam', array(
                            'id_process' => (int) $this->process->id,
                            'name'       => 'files_from_5y_' . $type
                                ), true);

                if (!BimpObject::objectLoaded($param)) {
                    $instance = $this->getObjectInstance($type);

                    $param = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                                'id_process' => (int) $this->process->id,
                                'name'       => 'files_from_5y_' . $type,
                                'label'      => 'Date min recherche fichiers à supprimer pour les ' . $instance->getLabel('name_plur') . ' (5 ans)',
                                'value'      => $date_5y
                                    ), true, $param_errors);

                    if (count($param_errors)) {
                        $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec création du paramètre "files_from_5y_' . $type . '"');
                    } else {
                        $this->debug_content .= BimpRender::renderAlerts('Ajout paramètre "' . $param->getData('label') . '" OK', 'success');
                    }
                } else {
                    $param_errors = $param->updateField('value', $date_5y);

                    if (count($param_errors)) {
                        $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec mise à jour du paramètre "files_from_5y_' . $type . '"');
                    } else {
                        $this->debug_content .= BimpRender::renderAlerts('Màj paramètre "' . $param->getData('label') . '" OK', 'success');
                    }
                }

                if ($type === 'propales') {
                    $dt = new DateTime();
                    $dt->sub(new DateInterval('P3Y'));
                    $date_3y = $dt->format('Y-m-d');

                    $param_errors = array();
                    $param = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessParam', array(
                                'id_process' => (int) $this->process->id,
                                'name'       => 'files_from_3y_propales'
                                    ), true);

                    if (!BimpObject::objectLoaded($param)) {
                        $instance = $this->getObjectInstance($type);

                        $param = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                                    'id_process' => (int) $this->process->id,
                                    'name'       => 'files_from_3y_propales',
                                    'label'      => 'Date min recherche fichiers à supprimer pour les propales refusées de plus de 3 ans',
                                    'value'      => $date_3y
                                        ), true, $param_errors);

                        if (count($param_errors)) {
                            $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec création du paramètre "files_from_3y_propales"');
                        } else {
                            $this->debug_content .= BimpRender::renderAlerts('Ajout paramètre "' . $param->getData('label') . '" OK', 'success');
                        }
                    } else {
                        $param_errors = $param->updateField('value', $date_3y);

                        if (count($param_errors)) {
                            $dates_errors[] = BimpTools::getMsgFromArray($param_errors, 'Echec mise à jour du paramètre "files_from_3y_propales"');
                        } else {
                            $this->debug_content .= BimpRender::renderAlerts('Màj paramètre "' . $param->getData('label') . '" OK', 'success');
                        }
                    }
                }
            }
        }

        if (!empty($dates_errors)) {
            $errors = BimpTools::merge_array($errors, $dates_errors);
            $this->Error(BimpTools::getMsgFromArray($dates_errors, 'Erreurs lors de la mise à jour des dates pour la recherche des fichiers à supprimer'));
        }
    }

    public function onClientAnonymised($client)
    {
        $errors = array();

        if (!is_a($client, 'Bimp_Client')) {
            $errors[] = 'Objet client invalide';
        } elseif ($client->isLoaded($errors)) {
            $dt = new DateTime();
            $dt->sub(new DateInterval('P5Y'));
            $dt_5y = $dt->format('Y-m-d');

            global $bimp_errors_handle_locked;
            $bimp_errors_handle_locked = true;

//            error_reporting(E_ALL);
//            ini_set('display_errors', 1);

            // Déplacement fichiers + 5 ans dans dossier d'archives:
            foreach (self::$objects as $type => $params) {
                $params = $this->getObjectParams($type);

                if (!(int) $params['delete_files']) {
                    continue;
                }

                $instance = $this->getObjectInstance($type);

                foreach (BimpCache::getBimpObjectObjects($instance->module, $instance->object_name, array(
                    $params['client_field']      => $client->id,
                    $params['date_create_field'] => array(
                        'operator' => '<',
                        'value'    => $dt_5y
                    )
                )) as $obj) {
                    $dir = $obj->getFilesDir();
                    if (!$dir || !is_dir($dir)) {
                        continue;
                    }

                    if (preg_match('/^(.+)\/+$/', $dir, $matches)) {
                        $dir = $matches[1];
                    }
                    // Renommage dossier:
                    error_clear_last();
                    if (!rename($dir, $dir . '_anonymized')) {
                        $this->incIgnored();
                        $err = error_get_last();
                        $errors[] = 'Echec renommage du dossier "' . $dir . '" en "' . $dir . '_anonymized"' . (isset($err['message']) ? ' - ' . $err['message'] : '');
                    }
                }
            }

            $bimp_errors_handle_locked = false;
        }

        return $errors;
    }

    public function onClientUnAnonymised($client)
    {
        $errors = array();

        if (!is_a($client, 'Bimp_Client')) {
            $errors[] = 'Objet client invalide';
        } elseif ($client->isLoaded($errors)) {
            global $bimp_errors_handle_locked;
            $bimp_errors_handle_locked = true;

//            error_reporting(E_ALL);
//            ini_set('display_errors', 1);

            foreach (self::$objects as $type => $params) {
                $params = $this->getObjectParams($type);
                $instance = $this->getObjectInstance($type);

                foreach (BimpCache::getBimpObjectObjects($instance->module, $instance->object_name, array(
                    $params['client_field'] => $client->id
                )) as $obj) {
                    $dir = $obj->getFilesDir();
                    if (preg_match('/^(.+)\/+$/', $dir, $matches)) {
                        $dir = $matches[1];
                    }

                    if (!$dir) {
                        continue;
                    }

                    $dir_anon = $dir . '_anonymized';

                    if (!is_dir($dir_anon)) {
                        continue;
                    }

                    if (is_dir($dir)) {
                        // Dépl. fichiers: 
                        foreach (scandir($dir_anon) as $file) {
                            if (in_array($file, array('.', '..'))) {
                                continue;
                            }

                            $new_file = $file;
                            if (file_exists($dir . '/' . $file)) {
                                $infos = pathinfo($file);
                                $new_file = $infos['filename'] . '_old.' . $infos['extension'];
                            }

                            error_clear_last();
                            if (!rename($dir_anon . '/' . $file, $dir . '/' . $new_file)) {
                                $err = error_get_last();
                                $errors[] = 'Echec déplacement du fichier "' . $file . '"' . (isset($err['message']) ? ' - ' . $err['message'] : '');
                            } else {
                                $errors[] = 'Dépl. ' . $file . ' => ' . $new_file;
                            }
                        }

                        unlink($dir_anon);
                    } else {
                        // Renommage dossier:
                        error_clear_last();
                        if (!rename($dir_anon, $dir)) {
                            $err = error_get_last();
                            $errors[] = 'Echec renommage du dossier "' . $dir_anon . '" en "' . $dir . '"' . (isset($err['message']) ? ' - ' . $err['message'] : '');
                        } else {
                            $errors[] = 'Rename ' . $dir_anon . ' => ' . $dir;
                        }
                    }
                }
            }

            $bimp_errors_handle_locked = false;
        }

        return $errors;
    }

    // Getters statiques: 

    public static function getObjectInstance($type)
    {
        if (!isset(self::$objects_instances[$type])) {
            $params = self::getObjectParams($type);
            self::$objects_instances[$type] = BimpObject::getInstance($params['module'], $params['object_name']);
        }

        return self::$objects_instances[$type];
    }

    public static function getObjectParams($type)
    {
        if (!isset(self::$objects_params[$type])) {
            self::$objects_params[$type] = BimpTools::overrideArray(self::$object_default_params, self::$objects[$type]);
        }

        return self::$objects_params[$type];
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Rgpd',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Suppression / anonymisation des données clients',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Paramètres: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'drafts_delay_interval',
                'label'      => 'Intervalle délai suppr. pièces brouillons',
                'value'      => 'P3Y'
                    ), true, $warnings, $warnings);

            foreach (self::$objects as $type => $obj_params) {
                $instance = self::getObjectInstance($type);

                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                    'id_process' => (int) $process->id,
                    'name'       => 'files_from_10y_' . $type,
                    'label'      => 'Date min recherche fichiers à supprimer pour les ' . $instance->getLabel('name_plur') . ' (10 ans)',
                    'value'      => '0000-00-00'
                        ), true, $warnings, $warnings);
            }

//            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'files_from_3y_propales',
                'label'      => 'Date min recherche fichiers à supprimer pour les propales refusées de plus de 3 ans',
                'value'      => '0000-00-00'
                    ), true, $warnings, $warnings);

            // options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les brouillons à supprimer',
                        'name'          => 'delete_drafts',
                        'info'          => 'Suppression de toutes les pièces brouillons de plus de 3ans',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['delete_drafts'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les fichiers à supprimer',
                        'name'          => 'delete_files',
                        'info'          => 'Suppression des fichiers des pièces de + de 10 ans si total >= 120 € et de + de 6 ans si total < 120 €',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['delete_files'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les clients à anonymiser',
                        'name'          => 'anonymize_clients',
                        'info'          => 'Effacement des données personnelles des clients sans activité depuis 6 ans pour les particuliers et depuis 10 ans pour les pros',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['anonymise_clients'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nombre max de clients à anonymiser',
                        'name'          => 'clients_to_anonymise_limit',
                        'info'          => '0 = aucune limite',
                        'type'          => 'text',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['clients_to_anonymise_limit'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les propales',
                        'name'          => 'process_propales',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_propales'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les commandes',
                        'name'          => 'process_commandes',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_commandes'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les factures',
                        'name'          => 'process_factures',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_factures'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les contrats',
                        'name'          => 'process_contrats',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les SAV',
                        'name'          => 'process_sav',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_sav'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les Tickets hotline',
                        'name'          => 'process_tickets',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_tickets'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les Fiche Intervention',
                        'name'          => 'process_fi',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['process_fi'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Tester un seul élément par type d\'objet',
                        'name'          => 'test_one',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['test_one'] = (int) $opt->id;
            }

            // Opérations: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Vérifications quotidiennes',
                        'name'          => 'dailyCheck',
                        'description'   => '',
                        'warning'       => 'Les pièces supprimées le seront de manière irréversible',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op) && !empty($options)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Tests',
                        'name'          => 'test',
                        'description'   => 'Liste le nombre d\'éléments à traiter via l\'opération "Vérifications quotidiennes"',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 0,
                        'reports_delay' => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op) && !empty($options)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Traitement des fichiers à supprimer',
                        'name'          => 'FilesToDelete',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op) && !empty($options)) {
                $op_options = $options;
                unset($op_options['delete_drafts']);
                unset($op_options['delete_files']);
                unset($op_options['anonymise_clients']);
                unset($op_options['clients_to_anonymise_limit']);

                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Vérifier les dates de dernière activité de tous les clients',
                        'name'          => 'CheckClientsActivity',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                            'id_process'    => (int) $process->id,
                            'label'         => 'Traiter seulement les dates nulles actuellement',
                            'name'          => 'null_only',
                            'info'          => '',
                            'type'          => 'toggle',
                            'default_value' => '0',
                            'required'      => 0
                                ), true, $warnings, $warnings);

                if (BimpObject::objectLoaded($opt)) {
                    $options['null_only'] = (int) $opt->id;
                }

                $warnings = array_merge($warnings, $op->addAssociates('options', array(
                            $options['null_only'],
                            $options['test_one']
                )));
            }
        }
    }
}
