<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RgpdProcess extends BDSProcess
{

    public static $object_default_params = array(
        'date_field'        => 'date_create',
        'date_create_field' => 'date_create',
        'delete_drafts'     => 1,
        'status_field'      => 'status',
        'draft_value'       => 0,
        'total_field'       => 'total_ttc'
    );
    public static $objects = array(
        'propales'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Propal',
            'date_field'        => 'datep',
            'date_create_field' => 'datec',
            'total_field'       => 'total',
            'status_field'      => 'fk_statut'
        ),
        'commandes' => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Commande',
            'date_field'        => 'date_commande',
            'date_create_field' => 'date_creation',
            'status_field'      => 'fk_statut'
        ),
        'factures'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Facture',
            'date_field'        => 'datef',
            'date_create_field' => 'datec',
            'status_field'      => 'fk_statut'
        ),
        'contrats'  => array(
            'module'            => 'bimpcontract',
            'object_name'       => 'BContract_contrat',
            'date_field'        => 'date_contrat',
            'date_create_field' => 'datec',
            'status_field'      => 'statut'
        ),
        'sav'       => array(
            'module'      => 'bimpsupport',
            'object_name' => 'BS_SAV',
            'total_field' => ''
        ),
        'tickets'   => array(
            'module'      => 'bimpsupport',
            'object_name' => 'BS_Ticket',
            'draft_value' => 1
        ),
        'fi'        => array(
            'module'            => 'bimptechnique',
            'object_name'       => 'BT_ficheInter',
            'date_field'        => 'datec',
            'date_create_field' => 'datec',
            'status_field'      => 'fk_statut',
            'total_field'       => ''
        )
    );

    // Init opérations:

    public function initTest(&$data, &$errors = array())
    {
        $html = '';

        $data = $this->getElementsToProcess();

        if (!empty($data['drafts'])) {
            $html .= '<h3>Pièces brouillons à supprimer</h3>';
            foreach ($data['drafts'] as $type => $elements) {
                $instance = BimpObject::getInstance(self::$objects[$type]['module'], self::$objects[$type]['object_name']);

                $title = BimpTools::ucfirst($instance->getLabel('name_plur')) . ' à supprimer (' . count($elements) . ')';
                $html .= BimpRender::renderFoldableContainer($title, '<pre>' . print_r($elements, 1) . '</pre>', array(
                            'open'        => false,
                            'offset_left' => true
                ));
            }
        }

        $data['result_html'] = $html;
    }

    public function initDailyCheck(&$data, &$errors = array())
    {
        $data = array(
            'steps' => array()
        );

        $elements = $this->getElementsToProcess();

        if (isset($elements['drafs']) && !empty($elements['drafts'])) {
            foreach ($elements['drafs'] as $type => $list) {
                if (!empty($list)) {
                    $instance = BimpObject::getInstance(self::$objects[$type]['module'], self::$objects[$type]['object_name']);

                    $data['steps']['delete_drafts_' . $type] = array(
                        'label'                  => 'Suppression des ' . $instance->getLabel('name_plur') . ' brouillons',
                        'on_error'               => 'continue',
                        'elements'               => $list,
                        'nbElementsPerIteration' => 10
                    );
                }
            }
        }
    }

    // Exec opérations:

    public function executeDailyCheck($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        if (preg_match('/^delete_drafts_(.+)$/', $step_name, $matches)) {
            $type = $matches[1];
        }
        return $result;
    }

    // Traitements: 

    public function getElementsToProcess()
    {
        $data = array(
            'drafts' => array()
        );

        // Recherche des pièces brouillons à suppr. 
        if ((int) BimpTools::getArrayValueFromPath($this->options, 'delete_drafts', 0)) {
            $dt = new DateTime();
            $dt->sub(new DateInterval(BimpTools::getArrayValueFromPath($this->params, 'drafts_delay_interval', 'P3Y')));
            $date_delete_drafs = $dt->format('Y-m-d');

            foreach (self::$objects as $type => $params) {
                $params = BimpTools::overrideArray(self::$object_default_params, $params);

                if (!(int) $params['delete_drafts'] || !(int) BimpTools::getArrayValueFromPath($this->options, 'process_' . $type, 0)) {
                    continue;
                }

                $obj = BimpObject::getInstance($params['module'], $params['object_name']);
                $primary = $obj->getPrimary();

                $where = $params['status_field'] . ' = ' . $params['draft_value'];
                $where .= ' AND ' . $params['date_create_field'] . ' <= \'' . $date_delete_drafs . '\'';

                $rows = $this->db->getRows($obj->getTable(), $where, null, 'array', array($primary));

                if (is_array($rows)) {
                    $data['drafts'][$type] = array();

                    foreach ($rows as $r) {
                        $data['drafts'][$type][] = (int) $r[$primary];

                        if ((int) BimpTools::getArrayValueFromPath($this->options, 'test_one', 0)) {
                            break;
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function deleteDrafts($type, $list, &$errors = array())
    {
        if (!(int) self::$objects[$type]['delete_drafts']) {
            $errors[] = 'Les brouillons ne sont pas supprimables pour les objets de type "' . $type . '"';
            return;
        }

        $instance = BimpObject::getInstance(self::$objects[$type]['module'], self::$objects[$type]['object_name']);
        $this->setCurrentObject($instance);

        foreach ($list as $id_object) {
            $this->incProcessed();
            $object = BimpCache::getBimpObjectInstance(self::$objects[$type]['module'], self::$objects[$type]['object_name'], $id_object);

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

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Rgpd',
                    'title'       => 'Traiements RGPD',
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

            // options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les brouillons à supprimer',
                        'name'          => 'delete_drafts',
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
                        'label'         => 'Traiter les propales',
                        'name'          => 'process_propales',
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
                        'label'         => 'Traiter les commandes',
                        'name'          => 'process_commandes',
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
                        'label'         => 'Traiter les factures',
                        'name'          => 'process_factures',
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
                $options[] = (int) $opt->id;
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
                $options[] = (int) $opt->id;
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
                $options[] = (int) $opt->id;
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

            $options = array();

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Tests',
                        'name'          => 'test',
                        'description'   => 'Liste le nombre d\'éléments à supprimer via l\'opération "Vérifications quotidiennes"',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 0,
                        'reports_delay' => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op) && !empty($options)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
