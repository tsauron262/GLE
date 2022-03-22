<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RgpdProcess extends BDSProcess
{

    public static $objects = array(
        'propales'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Propal',
            'date_field'        => 'datep',
            'date_create_field' => 'datec',
            'total_field'       => 'total',
            'delete_drafts'     => 1,
            'status_field'      => 'fk_statut',
            'draft_value'       => 0,
        ),
        'commandes' => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Commande',
            'date_field'        => 'date_commande',
            'total_field'       => 'total_ttc',
            'date_create_field' => 'datec',
            'delete_drafts'     => 1,
            'status_field'      => 'fk_statut',
            'draft_value'       => 0,
        ),
        'factures'  => array(
            'module'            => 'bimpcommercial',
            'object_name'       => 'Bimp_Facture',
            'date_field'        => 'datef',
            'date_create_field' => 'datec',
            'total_field'       => 'total_ttc',
            'delete_drafts'     => 0,
        )
    );

    // Init opérations:

    public function initTest(&$data, &$errors = array())
    {
        $html = '';

        $dt_10_years = new DateTime();
        $dt_10_years->sub(new DateInterval('P10Y'));

        $dt_6_years = new DateTime();
        $dt_6_years->sub(new DateInterval('P6Y'));

        $dt_3_years = new DateTime();
        $dt_3_years->sub(new DateInterval('P3Y'));

        foreach (self::$objects as $type => $params) {
            $instance = BimpObject::getInstance($params['module'], $params['object_name']);

            if (!is_a($instance, $params['object_name'])) {
                $html .= BimpRender::renderAlerts('Erreur de paramètres pour le type "' . $type . '" (nom ou module invalide)');
                continue;
            }

            $rows = BimpCache::getBimpObjectList($params['module'], $params['object_name'], array(
                        $params['date_field'] => array(
                            'operator' => '<',
                            'value'    => $dt_10_years->format('Y-m-d')
                        )
            ));

            $html .= BimpTools::ucfirst($instance->getLabel()) . ' > 10 ans: <b>' . count($rows) . '</b><br/>';
            $rows = BimpCache::getBimpObjectList($params['module'], $params['object_name'], array(
                        $params['date_field']  => array(
                            'and' => array(
                                array(
                                    'operator' => '<',
                                    'value'    => $dt_6_years->format('Y-m-d')
                                ),
                                array(
                                    'operator' => '>=',
                                    'value'    => $dt_10_years->format('Y-m-d')
                                )
                            )
                        ),
                        $params['total_field'] => array(
                            'operator' => '<',
                            'value'    => 120
                        )
            ));

            $html .= BimpTools::ucfirst($instance->getLabel()) . ' > 6 ans et < 120€: <b>' . count($rows) . '</b><br/><br/>';

            if ((int) $params['delete_drafts']) {
                $rows = BimpCache::getBimpObjectList($params['module'], $params['object_name'], array(
                            $params['date_field']   => array(
                                'operator' => '<',
                                'value'    => $dt_3_years->format('Y-m-d')
                            ),
                            $params['status_field'] => $params['draft_value']
                ));
            }
        }

        $data['result_html'] = $html;
    }

    public function initDailyCheck(&$data, &$errors = array())
    {
        $errors[] = 'Désactivé pour l\'instant';
        return;

        $data['steps'] = array();

        // Recherche des objets à supprimer: 
        $dt_10_years = new DateTime();
        $dt_10_years->sub(new DateInterval('P10Y'));

        $dt_6_years = new DateTime();
        $dt_6_years->sub(new DateInterval('P6Y'));

        foreach (self::$objects as $type => $params) {
            if (!(int) $this->options['process_' . $type]) {
                continue;
            }

            $to_delete = array();

            $instance = BimpObject::getInstance($params['module'], $params['object_name']);

            if (!is_a($instance, $params['object_name'])) {
                $errors[] = 'Erreur de paramètres pour le type "' . $type . '" (nom ou module invalide)';
                continue;
            }

            $list = BimpCache::getBimpObjectList($params['module'], $params['object_name'], array(
                        'datep' => array(
                            'operator' => '<',
                            'value'    => $dt_10_years->format('Y-m-d')
                        )
            ));

            if (is_array($list) && !empty($list)) {
                foreach ($list as $id) {
                    if (!in_array((int) $id, $to_delete)) {
                        $to_delete[] = (int) $id;
                    }
                }
            }

            $list = BimpCache::getBimpObjectList($params['module'], $params['object_name'], array(
                        $params['date_field']  => array(
                            'and' => array(
                                array(
                                    'operator' => '<',
                                    'value'    => $dt_6_years->format('Y-m-d')
                                ),
                                array(
                                    'operator' => '>=',
                                    'value'    => $dt_10_years->format('Y-m-d')
                                )
                            )
                        ),
                        $params['total_field'] => array(
                            'operator' => '<',
                            'value'    => 120
                        )
            ));

            if (is_array($list) && !empty($list)) {
                foreach ($list as $id) {
                    if (!in_array((int) $id, $to_delete)) {
                        $to_delete[] = (int) $id;
                    }
                }
            }

            if (!empty($to_delete)) {
                $data['steps']['delete_' . $type] = array(
                    'label'                  => 'Suppression des ' . $instance->getLabel('name_plur') . ' (' . count($to_delete) . ')',
                    'on_error'               => 'stop',
                    'elements'               => $to_delete,
                    'nbElementsPerIteration' => 10
                );
            }
        }
    }

    // Exec opérations:

    public function executeDailyCheck($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        if (preg_match('/^delete_(.+)$/', $step_name, $matches)) {
            $object_type = $matches[1];

            if (isset(self::$objects[$object_type])) {
                if (!empty($this->references)) {
                    $obj = self::$objects[$object_type];
                    $instance = BimpObject::getInstance($obj['module'], $obj['object_name']);

                    $this->setCurrentObject($instance);

                    global $rgpd_delete;
                    $rgpd_delete = true;

                    foreach ($this->references as $id_object) {
                        $name = BimpTools::ucfirst($instance->getLabel()) . ' #' . $id_object;
                        $object = BimpObject::getBimpObjectInstance($obj['module'], $obj['object_name'], $id_object);

                        if (BimpObject::objectLoaded($object)) {
                            $ref = $object->getRef(false);
                            if ($ref) {
                                $name .= ' - ' . $ref;
                            }

                            $obj_warnings = array();
                            $obj_errors = $object->delete($obj_warnings, true);

                            if (count($obj_warnings)) {
                                $this->Alert(BimpTools::getMsgFromArray($obj_warnings), $instance, $name);
                            }

                            if (count($obj_errors)) {
                                $this->Error(BimpTools::getMsgFromArray($obj_errors, 'Echec suppression'), $instance, $name);
                            } else {
                                $this->incDeleted();
                                $this->Success('Suppression effectuée', $instance, $name);
                                continue;
                            }
                        } else {
                            $this->Error(ucfirst($instance->getLabel('this')) . ' n\'existe pas', $instance, $name);
                        }

                        $this->incIgnored();
                    }

                    $rgpd_delete = false;
                }
            } else {
                $errors[] = 'Le type d\'objet "' . $object_type . '" n\'existe pas';
            }
        }

        return $result;
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
            $options = array();

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

            // Opérations: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Vérifications quotidiennes',
                        'name'          => 'dailyCheck',
                        'description'   => 'Suppression des pièces commerciales clients > 10 et > 6 ans si total ttc < 120 euros',
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
        }
    }
}
