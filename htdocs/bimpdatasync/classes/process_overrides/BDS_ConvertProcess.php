<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ConvertProcess extends BDSProcess
{

    public static $methods = array(
        'SignaturesToConvert'        => 'Conversion des signatures',
        'ProductRemisesCrtToConvert' => 'Conversion des remises CRT des produits',
        'PropalesCrtToConvert'       => 'Conversion des remises CRT des lignes de propales',
        'CommandesCrtToConvert'      => 'Conversion des remises CRT des lignes de commandes',
        'FacturesCrtToConvert'       => 'Conversion des remises CRT des lignes de factures',
    );
    public static $default_public_title = 'Scripts de conversions des données';

    // Process : 

    public function initConvert(&$data, &$errors = array())
    {
        $method = $this->getOption('method', '');
        if (!$method) {
            $errors[] = 'Veuillez saisir la méthode à appeller';
        } else {
            if (!method_exists($this, 'find' . ucfirst($method))) {
                $errors[] = 'La méthode find' . ucfirst($method) . ' n\'existe pas';
            }
            if (!method_exists($this, 'exec' . ucfirst($method))) {
                $errors[] = 'La méthode exec' . ucfirst($method) . ' n\'existe pas';
            }

            $elements = $this->{'find' . $method}($errors);

            if (empty($elements)) {
                $errors[] = 'Aucun élément à convertir trouvé';
            } else {
                $data['steps'] = array(
                    'convert' => array(
                        'label'                  => BimpTools::getArrayValueFromPath(self::$methods, $method, 'Conversion'),
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => (int) $this->getOption('nb_elements_per_iterations', 100)
                    )
                );
            }
        }
    }

    public function executeConvert($step_name, &$errors = array(), $extra_data = array())
    {
        $method = $this->getOption('method', '');
        return $this->{'exec' . $method}($errors);
    }

    // Méthodes : 

    public function findSignaturesToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimpcore_signature');
        $sql .= ' WHERE (SELECT COUNT(bss.id) FROM ' . MAIN_DB_PREFIX . 'bimpcore_signature_signataire bss WHERE bss.id_signature = a.id) = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execSignaturesToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimpcore_signature', 'id IN (' . implode(',', $this->references) . ')', null, 'array');

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcore', 'BimpSignataire');
            $signature = BimpObject::getInstance('bimpcore', 'BimpSignature');
            foreach ($rows as $r) {
                $this->incProcessed();
                if ($this->db->insert('bimpcore_signature_signataire', array(
                            'id_signature'              => (int) $r['id'],
                            'status'                    => (int) $r['status'],
                            'type_signature'            => (int) $r['type'],
                            'code'                      => 'default',
                            'label'                     => 'Signataire',
                            'id_client'                 => (int) $r['id_client'],
                            'id_contact'                => (int) $r['id_contact'],
                            'id_user_client_signataire' => (int) $r['id_user_client_signataire'],
                            'nom'                       => $r['nom_signataire'],
                            'email'                     => $r['email_signataire'],
                            'fonction'                  => $r['fonction_signataire'],
                            'date_open'                 => $r['date_open'],
                            'allowed_users_client'      => $r['allowed_users_client'],
                            'date_signed'               => $r['date_signed'],
                            'ip_signataire'             => $r['ip_signataire'],
                            'base_64_signature'         => $r['base_64_signature'],
                            'allow_elec'                => $r['allow_elec'],
                            'allow_dist'                => $r['allow_dist'],
                            'allow_docusign'            => 0,
                            'allow_refuse'              => 0,
                            'need_sms_code'             => $r['need_sms_code'],
                            'code_sms_infos'            => $r['code_sms_infos']
                        )) <= 0) {
                    $this->incIgnored();
                    $signature->id = (int) $r['id'];
                    $this->Error('Echec création du signataire - ' . $this->db->err(), $signature);
                } else {
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    public function findProductRemisesCrtToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.fk_object'));
        $sql .= BimpTools::getSqlFrom('product_extrafields');
        $sql .= ' WHERE a.crt > 0';
        $sql .= ' AND (SELECT COUNT(pra.id) FROM ' . MAIN_DB_PREFIX . 'product_remise_arriere pra WHERE pra.id_product = a.fk_object AND pra.type = \'crt\') = 0';
        $sql .= ' ORDER BY a.fk_object asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['fk_object'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execProductRemisesCrtToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('product_extrafields', 'fk_object IN (' . implode(',', $this->references) . ')', null, 'array', array('fk_object', 'crt'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcore', 'Bimp_ProductRA');
            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            foreach ($rows as $r) {
                $this->incProcessed();
                $product->id = (int) $r['fk_object'];
                if ($this->db->insert('product_remise_arriere', array(
                            'id_product' => (int) $r['fk_object'],
                            'type'       => 'crt',
                            'nom'        => 'CRT',
                            'value'      => (float) $r['crt'],
                            'active'     => 1
                        )) <= 0) {
                    $this->incIgnored();
                    $this->Error('Echec création de la remise arrière CRT - ' . $this->db->err(), $product);
                } else {
                    $this->Success('Création Remise CRT OK', $product);
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    public function findPropalesCrtToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimp_propal_line');
        $sql .= ' WHERE a.remise_crt > 0 AND a.remise_crt_percent != 0';
        $sql .= ' AND (SELECT COUNT(lra.id) FROM ' . MAIN_DB_PREFIX . 'object_line_remise_arriere lra WHERE lra.id_object_line = a.id AND lra.object_type = \'propal\' AND lra.type = \'crt\') = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execPropalesCrtToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimp_propal_line', 'id IN (' . implode(',', $this->references) . ')', null, 'array', array('id', 'remise_crt_percent'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcommercial', 'ObjectLineRemiseArriere');
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
            foreach ($rows as $r) {
                $this->incProcessed();
                $line->id = (int) $r['id'];
                if ($this->db->insert('object_line_remise_arriere', array(
                            'id_object_line' => (int) $r['id'],
                            'object_type'    => 'propal',
                            'type'           => 'crt',
                            'label'          => 'CRT',
                            'value'          => (float) $r['remise_crt_percent'],
                        )) <= 0) {
                    $this->incIgnored();
                    $this->Error('Echec création de la remise arrière CRT - ' . $this->db->err(), $line);
                } else {
                    $this->Success('Création Remise CRT OK', $line);
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    public function findCommandesCrtToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimp_commande_line');
        $sql .= ' WHERE a.remise_crt > 0 AND a.remise_crt_percent != 0';
        $sql .= ' AND (SELECT COUNT(lra.id) FROM ' . MAIN_DB_PREFIX . 'object_line_remise_arriere lra WHERE lra.id_object_line = a.id AND lra.object_type = \'commande\' AND lra.type = \'crt\') = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execCommandesCrtToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimp_commande_line', 'id IN (' . implode(',', $this->references) . ')', null, 'array', array('id', 'remise_crt_percent'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcommercial', 'ObjectLineRemiseArriere');
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
            foreach ($rows as $r) {
                $this->incProcessed();
                $line->id = (int) $r['id'];
                if ($this->db->insert('object_line_remise_arriere', array(
                            'id_object_line' => (int) $r['id'],
                            'object_type'    => 'commande',
                            'type'           => 'crt',
                            'label'          => 'CRT',
                            'value'          => (float) $r['remise_crt_percent'],
                        )) <= 0) {
                    $this->incIgnored();
                    $this->Error('Echec création de la remise arrière CRT - ' . $this->db->err(), $line);
                } else {
                    $this->Success('Création Remise CRT OK', $line);
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    public function findFacturesCrtToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimp_facture_line');
        $sql .= ' WHERE a.remise_crt > 0 AND a.remise_crt_percent != 0';
        $sql .= ' AND (SELECT COUNT(lra.id) FROM ' . MAIN_DB_PREFIX . 'object_line_remise_arriere lra WHERE lra.id_object_line = a.id AND lra.object_type = \'facture\' AND lra.type = \'crt\') = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execFacturesCrtToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimp_facture_line', 'id IN (' . implode(',', $this->references) . ')', null, 'array', array('id', 'remise_crt_percent'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcommercial', 'ObjectLineRemiseArriere');
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
            foreach ($rows as $r) {
                $this->incProcessed();
                $line->id = (int) $r['id'];
                if ($this->db->insert('object_line_remise_arriere', array(
                            'id_object_line' => (int) $r['id'],
                            'object_type'    => 'facture',
                            'type'           => 'crt',
                            'label'          => 'CRT',
                            'value'          => (float) $r['remise_crt_percent'],
                        )) <= 0) {
                    $this->incIgnored();
                    $this->Error('Echec création de la remise arrière CRT - ' . $this->db->err(), $line);
                } else {
                    $this->Success('Création Remise CRT OK', $line);
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    public function findShipmentsToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimp_commande_line');
        $sql .= ' WHERE a.shipments != \'\' AND a.shipments != \'{}\' > 0';
        $sql .= ' AND (SELECT COUNT(sl.id) FROM ' . MAIN_DB_PREFIX . 'bl_shipment_line sl WHERE sl.id_commande_line = a.id) = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execShipmentsToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimp_commande_line', 'id IN (' . implode(',', $this->references) . ')', null, 'array', array('id', 'shipments'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimplogistique', 'BL_ShipmentLine');
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
            foreach ($rows as $r) {
                $line->id = (int) $r['id'];
                $shipments = json_decode($r['shipments'], 1);

                $this->DebugData($shipments, 'LIGNE #' . $r['id']);

                foreach ($shipments as $id_shipment => $shipment_data) {
                    $qty = (isset($shipment_data['qty']) ? (int) $shipment_data['qty'] : (isset($shipment_data['equipments']) ? count($shipment_data['equipments']) : 0));

                    if (!$qty) {
                        continue;
                    }

                    if ((int) $this->db->getValue('bl_shipment_line', 'id', 'id_shipment = ' . $id_shipment . ' AND id_commande_line = ' . $r['id'])) {
                        continue;
                    }

                    $this->incProcessed();

                    $id_shipment_line = $this->db->insert('bl_shipment_line', array(
                        'id_shipment'      => $id_shipment,
                        'id_commande_line' => $r['id'],
                        'id_entrepot_dest' => (isset($shipment_data['id_entrepot']) ? (int) $shipment_data['id_entrepot'] : 0),
                        'qty'              => $qty
                            ), true);

                    if ($id_shipment_line <= 0) {
                        $this->incIgnored();
                        $this->Error('Echec ajout de la ligne à l\'expédition #' . $id_shipment . ' - ' . $this->db->err(), $line);
                    } else {
                        $this->Success('Création de la ligne d\'expédition OK', $line);
                        $this->incCreated();

                        if (isset($shipment_data['equipments']) && !empty($shipment_data['equipments'])) {
                            $base_data = array(
                                'association'        => 'equipments',
                                'src_object_module'  => 'bimplogistique',
                                'src_object_name'    => 'BL_ShipmentLine',
                                'src_object_type'    => 'bimp_object',
                                'src_id_object'      => $id_shipment_line,
                                'dest_object_module' => 'bimpequipment',
                                'dest_object_name'   => 'Equipment',
                                'dest_object_type'   => 'bimp_object'
                            );

                            foreach ($shipment_data['equipments'] as $id_equipment) {
                                if (!(int) $id_equipment) {
                                    $this->Error('ID eq invalide', $line);
                                    break;
                                }
                                $data = $base_data;
                                $data['dest_id_object'] = $id_equipment;
                                if ($this->db->insert('bimpcore_objects_associations', $data) <= 0) {
                                    $this->Error('Echec asso equipement #' . $id_equipment . ' pour l\'expé #' . $id_shipment . ' - ' . $this->db->err(), $line);
                                } else {
                                    $this->Info('Aj asso equipement #' . $id_equipment . ' pour l\'expé #' . $id_shipment . ' OK', $line);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    // install : 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Convert',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Conversions des données',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Méthode à appeller',
                        'name'          => 'method',
                        'info'          => 'Indiquer le nom de la méthode à appeller sans le préfixe find / exec',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['nb_elements_per_iterations'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Tester un seul élément',
                        'name'          => 'test_one',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nb éléments par itérations',
                        'name'          => 'nb_elements_per_iterations',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '100',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opérations: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Conversions / corrections',
                        'name'          => 'convert',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 15
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
