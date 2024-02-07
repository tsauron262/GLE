<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ConvertProcess extends BDSProcess
{

    public static $current_version = 2;
    public static $methods = array(
        ''                    => '',
//        'SignaturesToConvert'        => 'Conversion des signatures',
//        'ProductRemisesCrtToConvert' => 'Conversion des remises CRT des produits',
//        'PropalesCrtToConvert'       => 'Conversion des remises CRT des lignes de propales',
//        'CommandesCrtToConvert'      => 'Conversion des remises CRT des lignes de commandes',
//        'FacturesCrtToConvert'       => 'Conversion des remises CRT des lignes de factures',
        'ShipmentsToConvert'  => 'Conversion des lignes d\'expédition',
        'ReceptionsToConvert' => 'Conversion des lignes de réception',
        'abosToConvert'       => 'Conversion des Abonnements'
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
        $errors[] = 'Script Désactivé';
        return array();
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
        $errors[] = 'Script Désactivé';
        return array();
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
        $errors[] = 'Script Désactivé';
        return array();
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
        $errors[] = 'Script Désactivé';
        return array();
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
        $errors[] = 'Script Désactivé';
        return array();
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

    // Données Expéditions :

    public function findShipmentsToConvert(&$errors = array())
    {
//        $errors[] = 'Script Désactivé';
//        return array();

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
                    $qty = (isset($shipment_data['qty']) ? (float) $shipment_data['qty'] : (isset($shipment_data['equipments']) ? count($shipment_data['equipments']) : 0));

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
//                                    $this->Info('Aj asso equipement #' . $id_equipment . ' pour l\'expé #' . $id_shipment . ' OK', $line);
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

    // Données Réceptions :

    public function findReceptionsToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimp_commande_fourn_line');
        $sql .= ' WHERE a.receptions != \'\' AND a.receptions != \'{}\' > 0';
        $sql .= ' AND (SELECT COUNT(rl.id) FROM ' . MAIN_DB_PREFIX . 'bl_reception_line rl WHERE rl.id_commande_fourn_line = a.id) = 0';
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

    public function execReceptionsToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimp_commande_fourn_line', 'id IN (' . implode(',', $this->references) . ')', null, 'array', array('id', 'receptions'));

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimplogistique', 'BL_ReceptionLine');
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
            foreach ($rows as $r) {
                $line->id = (int) $r['id'];
                $receptions = json_decode($r['receptions'], 1);

                $this->DebugData($receptions, 'LIGNE #' . $r['id']);

                foreach ($receptions as $id_reception => $reception_data) {
                    $qty = (isset($reception_data['qty']) ? (float) $reception_data['qty'] : (isset($reception_data['equipments']) ? count($reception_data['equipments']) : 0));

                    if (!$qty) {
                        continue;
                    }

                    if ((int) $this->db->getValue('bl_reception_line', 'id', 'id_reception = ' . $id_reception . ' AND id_commande_fourn_line = ' . $r['id'])) {
                        continue;
                    }

                    $this->incProcessed();

                    $id_reception_line = $this->db->insert('bl_reception_line', array(
                        'id_reception'           => $id_reception,
                        'id_commande_fourn_line' => $r['id'],
                        'qty'                    => $qty
                            ), true);

                    if ($id_reception_line <= 0) {
                        $this->incIgnored();
                        $this->Error('Echec ajout de la ligne à la réception #' . $id_reception . ' - ' . $this->db->err(), $line);
                    } else {
                        $this->Success('Création de la ligne de réception OK', $line);
                        $this->incCreated();

                        $base_data = array(
                            'association'        => 'equipments',
                            'src_object_module'  => 'bimplogistique',
                            'src_object_name'    => 'BL_ReceptionLine',
                            'src_object_type'    => 'bimp_object',
                            'src_id_object'      => $id_reception_line,
                            'dest_object_module' => 'bimpequipment',
                            'dest_object_name'   => 'Equipment',
                            'dest_object_type'   => 'bimp_object'
                        );

                        if (isset($reception_data['equipments']) && !empty($reception_data['equipments'])) {
                            foreach ($reception_data['equipments'] as $id_equipment => $eq_data) {
                                if (!(int) $id_equipment) {
                                    $this->Error('ID eq invalide', $line);
                                    break;
                                }
                                $data = $base_data;
                                $data['dest_id_object'] = $id_equipment;
                                if ($this->db->insert('bimpcore_objects_associations', $data) <= 0) {
                                    $this->Error('Echec asso equipement #' . $id_equipment . ' pour la récep #' . $id_reception . ' - ' . $this->db->err(), $line);
                                } else {
//                                    $this->Info('Aj asso equipement #' . $id_equipment . ' pour la récep #' . $id_reception . ' OK', $line);
                                }
                            }
                        } elseif (isset($reception_data['return_equipments']) && !empty($reception_data['return_equipments'])) {
                            foreach ($reception_data['return_equipments'] as $id_equipment => $eq_data) {
                                if (!(int) $id_equipment) {
                                    $this->Error('ID eq invalide', $line);
                                    break;
                                }
                                $data = $base_data;
                                $data['dest_id_object'] = $id_equipment;
                                if ($this->db->insert('bimpcore_objects_associations', $data) <= 0) {
                                    $this->Error('Echec asso equipement #' . $id_equipment . ' pour la récep #' . $id_reception . ' - ' . $this->db->err(), $line);
                                } else {
//                                    $this->Info('Aj asso equipement #' . $id_equipment . ' pour la récep #' . $id_reception . ' OK', $line);
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

    // Abonnements: 

    public function findAbosToConvert(&$errors = array())
    {
        $elems = array();

        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        BimpObject::loadClass('bimpcore', 'Bimp_Product');

        $fields = array('DISTINCT a.id as id_line', 'a.id_obj as id_commande');

        $joins = array(
            'c'    => array(
                'table' => 'commande',
                'on'    => 'c.rowid = a.id_obj'
            ),
            'cdet' => array(
                'table' => 'commandedet',
                'on'    => 'cdet.rowid = a.id_line'
            ),
            'pef'  => array(
                'table' => 'product_extrafields',
                'on'    => 'pef.fk_object = cdet.fk_product'
            )
        );

        $filters = array(
//            'c.rowid'                  => 272169,
            'c.fk_statut'              => 1,
//            'c.rowid'                  => array(
//                'not_in' => array(33460, 150926, )
//                'not_in' => array(242102, 244285, 247393, 249512, 251356, 251365, 253349, 253569, 253573, 253611, 253819, 256339, 256959, 258031, 258489, 258594, 258878, 258978, 259617, 270548, 265263, 262477, 262612, 264878, 259471, 259726, 259793, 259907, 260774, 262079, 256259, 256725, 258973, 253960, 254421, 254803, 237622, 241172, 242222, 150296, 156248, 185523, 173892, 187315, 187613, 180787, 176264, 177399, 175384, 166617, 165207, 165477, 163893, 190608, 1601103, 207407, 207401, 198753, 202046, 197604, 193248, 196127, 195135, 192524, 191376, 212882, 213978, 221129, 229650, 231707, 232659, 234192, 236104, 234007, 236987, 249927, 251125, 251290, 251330, 251370, 252831, 253565)
//            ),
            'a.id_contrat_line_export' => 0,
//            'a.no_abos_convert'        => 0,
            '(a.qty_modif + cdet.qty)' => array(
                'operator' => '>',
                'value'    => 0
            ),
//            'or_periodicity'           => array(
//                'or' => array(
//                    'a.fac_periodicity'   => array(
//                        'operator' => '>',
//                        'value'    => 0
//                    ),
//                    'a.achat_periodicity' => array(
//                        'operator' => '>',
//                        'value'    => 0
//                    )
//                )
//            ),
            'pef.type2'                => Bimp_Product::$abonnements_sous_types
        );

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'c');

        $sql = BimpTools::getSqlFullSelectQuery('bimp_commande_line', $fields, $filters, $joins, array(
                    'order_by'  => 'a.id',
                    'order_way' => 'asc'
        ));

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows)) {
            $commandes = array();

            foreach ($rows as $r) {
                if (!isset($commandes[$r['id_commande']])) {
                    $commandes[$r['id_commande']] = array();
                }

                $commandes[$r['id_commande']][] = $r['id_line'];
            }

            foreach ($commandes as $id_commande => $lines) {
                $elems[] = json_encode(array('id_commande' => $id_commande, 'lines' => $lines));
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execAbosToConvert(&$errors = array())
    {
        global $conf, $user;
        $user->fetch(1);

        $validated = array();

        if ($conf->db->name == 'ERP_PROD_BIMP') {
            $validated = array(1993675, 1993626, 2177715, 2163842, 2163839, 2162417, 2162393, 2156007, 2153705, 2151131, 2151125, 2149378, 2125545, 2125542, 2124733, 2123471, 2120946, 2120940, 2115490, 2112678, 2112675, 2112672, 2109522, 2105810, 2105807, 2103706, 2089323, 2087491, 2083851, 2079608, 2075215, 2071152, 2071149, 2070797, 2064480, 2061360, 2061180, 2049177, 2045090, 2062118, 2044158, 2044150, 2042346, 2041641, 2011860, 2011243, 2011239, 2009879, 2009649, 2009633, 2007873, 1993675, 1979870, 2163343, 2163340, 2161821, 2139235, 2135673, 2135670, 2129531, 2129525, 2111663, 2098281, 2086707, 2086704, 2086245, 2085567, 2050325, 2067418, 2064368, 2056806, 2054510, 2050983, 2050331, 2048255, 2045098, 2045029, 2011868, 2009617, 2004715, 1993695, 1993685, 1993660, 1993475, 1993155, 1991870, 1983207, 1925857, 1883811, 1778047, 1735898, 1729823, 1704905, 1704023, 1700801, 1640651, 1630068, 1629974, 1525977, 1496504, 1445292, 1443840, 1436715);
            $not_validated = array(
                1925047 => 'A traiter manuellement', // Commande # 242102 - Ligne #1925047 (n° 1)
                2094835 => 'A traiter manuellement', // Commande # 266485 - Ligne #2094835 (n° 7)
                2087485 => 'A traiter manuellement', // Commande # 265444 - Ligne #2087485 (n° 14)
                2083433 => 'A traiter manuellement', // Commande # 264878 - Ligne #2083433 (n° 2)
                2068234 => 'A traiter manuellement', // Commande # 262612 - Ligne #2068234 (n° 2)
            );
            // Exemple de commande sans aucun achat depuis 2021 : 197604
        }
        $this->db->db->commitAll();

        $one_day_interval = new DateInterval('P1D');
        $commandes_fails = array();
        $no_transac_db = BimpCache::getBdb(true);

        foreach ($this->references as $data) {
            $this->setCurrentObjectData('bimpcommercial', 'Bimp_Commande');
            $this->incProcessed();
            $data = json_decode($data, 1);

            $id_commande = $data['id_commande'];
            $lines = $data['lines'];

            if ($id_commande) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                if (!BimpObject::objectLoaded($commande)) {
                    $this->Error('Commande non trouvée', null, '#' . $id_commande);
                    $this->incIgnored();
                } else {
//                    if ((int) $commande->getData('id_client_facture') && (int) $commande->getData('id_client_facture') !== (int) $commande->getData('fk_soc')) {
//                        $this->Error('Client facturation différent du client final (Commande non traitée)', $commande, '#' . $id_commande . ' - ' . $commande->getRef());
//                        $this->incIgnored();
//                        continue;
//                    }

                    $contrat_lines = array();
                    $regul_stocks = array();
                    $this->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeLine');
                    $has_line_errors = false;

                    foreach ($lines as $id_line) {
                        $this->incProcessed();
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (!BimpObject::objectLoaded($line)) {
                            $this->Error('Ligne #' . $id_line . ' absente', $commande);
                            $this->incIgnored();
                            $has_line_errors = true;
                            continue;
                        }

                        $product = $line->getProduct();
                        $line_ref = 'Commande # ' . $commande->id . ' - Ligne #' . $line->id . ' (n° ' . $line->getData('position') . ')';

                        if (!BimpObject::objectLoaded($product)) {
                            $this->Error('Produit absent', $commande, $line_ref);
                            $this->incIgnored();
                            $has_line_errors = true;
                            continue;
                        }

                        $qty = (float) $line->getFullQty();
                        if (!$qty) {
                            $this->Error('Qté nulle', $commande, $line_ref);
                            $this->incIgnored();
                            $has_line_errors = true;
                            continue;
                        }

                        $line_infos = array();
                        $line_errors = array();

                        $line_infos['qty'] = $qty;
                        $line_infos['id_product'] = $product->id;

                        $qty_shipped = round((float) $line->getShippedQty(), 6);
                        $qty_shipped_valid = round((float) $line->getShippedQty(null, true), 6);

                        if ($qty_shipped !== $qty_shipped_valid) {
                            $line_errors[] = 'Expéditions non validées';
                        }

                        $bought_qty = round((float) $line->getBoughtQty(), 6);

                        $billed_qty = (float) $line->getBilledQty();
                        $billed_qty_valid = (float) $line->getBilledQty(null, true);
                        if ($billed_qty !== $billed_qty_valid) {
                            $line_errors[] = 'Factures non validées';
                        }

                        $line_infos['shipped_qty'] = $qty_shipped;
                        $line_infos['bought_qty'] = $bought_qty;
                        $line_infos['billed_qty'] = $billed_qty;

                        $stock_regul = round($qty_shipped - $billed_qty, 6);
                        $line_infos['stock_regul'] = $stock_regul;
                        if ($stock_regul) {
                            $regul_stocks[$line->id] = $stock_regul;
                        }

                        $fac_periodicity = (int) $line->getData('fac_periodicity');
                        $achat_periodicity = (int) $line->getData('achat_periodicity');

                        $line_infos['fac_periodicity'] = $fac_periodicity;
                        $line_infos['achat_periodicity'] = $achat_periodicity;

                        $line_infos['fac_nb_perdiods'] = (int) $line->getData('fac_nb_periods');
                        $line_infos['achat_nb_perdiods'] = (int) $line->getData('achat_nb_periods');
                        $line_infos['alerts'] = array();

                        $date_from = '';
                        $date_to = '';
                        $duration = 0;

                        if ($line->date_from) {
                            $date_from = date('Y-m-d', strtotime($line->date_from));
                            $line_infos['date_from'] = $date_from;
                        }

                        if ($line->date_to) {
                            $date_to = date('Y-m-d', strtotime($line->date_to));
                            $line_infos['date_to'] = $date_to;
                        }

                        if ($date_from && $date_to) {
                            $interval = BimpTools::getDatesIntervalData($date_from, $date_to);
                            if ($interval['remain_days'] > 1) {
                                if (!$duration) {
                                    $duration = ($interval['full_years'] + 1) * 12;
                                }
                                $dt = new DateTime($date_to);
                                $dt->sub(new DateInterval('P' . $duration . 'M'));
                                $dt->add($one_day_interval);
                                $date_from = $dt->format('Y-m-d');
                                $line_infos['alerts'][] = 'Début ajusté à l\'année entière la plus proche';
                            } elseif (!$duration) {
                                $duration = $interval['full_monthes'];
                                $line_infos['alerts'][] = 'Durée basée sur les dates';
                            }
                        } else {
                            if ($fac_periodicity && $achat_periodicity) {
//                                if (($line->getData('fac_nb_periods') * $fac_periodicity) != ($line->getData('achat_nb_periods') * $achat_periodicity)) {
//                                    $line_errors[] = 'La durée de facturation (' . $line->getData('fac_nb_periods') * $fac_periodicity . ') ne correspond pas à la durée d\'achat (' . $line->getData('achat_nb_periods') * $achat_periodicity . ')';
//                                } else
                                if ($line->getData('fac_periods_start') != $line->getData('achat_periods_start')) {
                                    $line_errors[] = 'Les dates de début de facturation (' . $line->getData('fac_periods_start') . ') et d\'achat (' . $line->getData('achat_periods_start') . ') ne correspondent pas';
                                }
                            }
                        }

                        if (!count($line_errors)) {
                            if (!$duration) {
                                if ($fac_periodicity && (int) $line->getData('fac_nb_periods')) {
                                    $duration = $line->getData('fac_nb_periods') * $fac_periodicity;
                                    $line_infos['alerts'][] = 'Durée basée sur la facturation';
                                } elseif ($achat_periodicity && (int) $line->getData('achat_nb_periods')) {
                                    $duration = $line->getData('achat_nb_periods') * $achat_periodicity;
                                    $line_infos['alerts'][] = 'Durée basée sur les achats';
                                } elseif ((int) $line->getData('exp_periodicity') && (int) $line->getData('exp_nb_periods')) {
                                    $duration = (int) $line->getData('exp_periodicity') * (int) $line->getData('exp_nb_periods');
                                    $line_infos['alerts'][] = 'Durée basée sur les livraisons';
                                }
                            }

                            if (!$duration) {
                                $this->Error('Durée abonnement non définie', $commande, $line_ref);
                                $this->incIgnored();
                                $has_line_errors = true;
                                continue;
                            }

                            if (!$date_from) {
                                if ($fac_periodicity && $line->getData('fac_periods_start')) {
                                    $date_from = $line->getData('fac_periods_start');
                                } elseif ($achat_periodicity && $line->getData('achat_periods_start')) {
                                    $date_from = $line->getData('achat_periods_start');
                                }
                            }

                            if (!$date_from) {
                                $this->Error('Date début abonnement non définie', $commande, $line_ref);
                                $this->incIgnored();
                                $has_line_errors = true;
                                continue;
                            }

                            $dt = new DateTime($date_from);
                            $date_from = $dt->format('Y-m-d 00:00:00');
                            $dt->add(new DateInterval('P' . $duration . 'M'));
                            $dt->sub($one_day_interval);
                            $date_to = $dt->format('Y-m-d 23:59:59');

                            $date_fac_start = null;
                            $date_next_fac = null;
                            $date_achat_start = null;
                            $date_next_achat = null;

                            $to_bill = 0;
                            $to_buy = 0;

                            if ($fac_periodicity) {
                                $fac_data = $line->getNbPeriodsToBillData();
                                $line_infos['fac_data'] = $fac_data;

                                $to_bill = (int) $fac_data['nb_periods_max'];
                                if (!$to_bill) {
                                    $dt = new DateTime($date_to);
                                    $dt->add($one_day_interval);
                                    $date_fac_start = $date_next_fac = $dt->format('Y-m-d');
                                } else {
                                    $dt = new DateTime($date_from);
                                    if ((float) $fac_data['nb_periods_billed']) {
                                        $dt->add(new DateInterval('P' . floor($fac_data['nb_periods_billed']) * $fac_periodicity . 'M'));

                                        if (floor($fac_data['nb_periods_billed']) != (float) $fac_data['nb_periods_billed']) {
                                            $nb_days_supp = ($fac_data['nb_periods_billed'] - floor($fac_data['nb_periods_billed'])) * (30 * $fac_periodicity);

                                            if ($nb_days_supp) {
                                                $line_infos['first_fac_days_supp'] = $nb_days_supp;
                                                $dt->add(new DateInterval('P' . round($nb_days_supp) . 'D'));
                                            }
                                        }
                                    }
                                    $date_fac_start = $dt->format('Y-m-d');
                                    if ((int) $line->getData('fact_echue')) {
                                        $dt->add(new DateInterval('P' . $fac_periodicity . 'M'));
                                    }
                                    $date_next_fac = $dt->format('Y-m-d');
                                }
                            } else {
                                if (!$billed_qty) {
                                    $fac_periodicity = $duration;
                                    $dt = new DateTime($date_from);
                                    $date_fac_start = $dt->format('Y-m-d');
                                    if ((int) $line->getData('fact_echue')) {
                                        $dt->add(new DateInterval('P' . $fac_periodicity . 'M'));
                                    }
                                    $date_next_fac = $dt->format('Y-m-d');
                                } elseif ($billed_qty != $qty) {
                                    $line_errors[] = 'Ligne facturée partiellement (' . $billed_qty . ' / ' . $qty . ') sans périodicité de facturation définie';
                                } else {
                                    $fac_periodicity = $duration;
                                    $dt = new DateTime($date_to);
                                    $dt->add($one_day_interval);
                                    $date_fac_start = $date_next_fac = $dt->format('Y-m-d');
                                }
                            }

                            if ($achat_periodicity) {
                                $achat_data = $line->getNbPeriodesToBuyData();
                                $line_infos['achat_data'] = $achat_data;

                                $to_buy = (float) $achat_data['nb_periods_max'];
                                if (!$to_buy) {
                                    $dt = new DateTime($date_to);
                                    $dt->add($one_day_interval);
                                    $date_achat_start = $date_next_achat = $dt->format('Y-m-d');
                                } else {
                                    $dt = new DateTime($date_from);
                                    if ((float) $achat_data['nb_periods_bought']) {
                                        $dt->add(new DateInterval('P' . (floor($achat_data['nb_periods_bought']) * $achat_periodicity) . 'M'));

                                        if (floor($achat_data['nb_periods_bought']) != (float) $achat_data['nb_periods_bought']) {
                                            $nb_days_supp = ($achat_data['nb_periods_bought'] - floor($achat_data['nb_periods_bought'])) * (30 * $achat_periodicity);

                                            if ($nb_days_supp) {
                                                $line_infos['first_achat_days_supp'] = $nb_days_supp;
                                                $dt->add(new DateInterval('P' . round($nb_days_supp) . 'D'));
                                            }
                                        }
                                    }
                                    $date_achat_start = $date_next_achat = $dt->format('Y-m-d');
                                }
                            }
                        }

                        $infos = '';
                        if ((int) $line_infos['id_product']) {
                            $infos .= '{{Produit:' . $line_infos['id_product'] . '}}<br/>';
                        }

                        if (!empty($line_infos['alerts'])) {
                            $infos .= BimpRender::renderAlerts($line_infos['alerts'], 'warning');
                        }

                        if ($line_infos['date_from']) {
                            $infos .= 'Du  <b>' . date('d / m / Y', strtotime($line_infos['date_from'])) . '</b> ';
                        }

                        if ($line_infos['date_to']) {
                            $infos .= 'au  <b>' . date('d / m / Y', strtotime($line_infos['date_to'])) . '</b>';
                        }
                        if ($line_infos['date_from'] || $line_infos['date_to']) {
                            $infos .= '<br/>';
                        }

                        $infos .= '<b>Qty : ' . $line_infos['qty'] . '</b>';
                        $infos .= '<span class="' . (!$line_infos['shipped_qty'] ? 'danger' : ($line_infos['shipped_qty'] == $line_infos['qty'] ? 'success' : 'warning')) . '"> - exp: ' . $line_infos['shipped_qty'] . '</span>';
                        $infos .= '<span class="' . (!$line_infos['bought_qty'] ? 'danger' : ($line_infos['bought_qty'] == $line_infos['qty'] ? 'success' : 'warning')) . '"> - achats: ' . $line_infos['bought_qty'] . '</span>';
                        $infos .= '<span class="' . (!$line_infos['billed_qty'] ? 'danger' : ($line_infos['billed_qty'] == $line_infos['qty'] ? 'success' : 'warning')) . '"> - fac : ' . $line_infos['billed_qty'] . '</span><br/>';

                        $infos .= 'Stock régul : ' . $line_infos['stock_regul'] . '<br/>';
                        $infos .= '<b>Fac : </b>';
                        if ((int) $line_infos['fac_periodicity']) {
                            $infos .= '<br/> - Périodicité : ' . $line_infos['fac_periodicity'];
                            $infos .= ' - Nb périodes facturée(s) : ' . $line_infos['fac_data']['nb_periods_billed'] . ' sur ' . $line_infos['fac_data']['nb_total_periods'] . ' (restantes : ' . $line_infos['fac_data']['nb_periods_max'] . ')<br/>';
                            $infos .= ' - Début : ' . date('d / m / Y', strtotime($line_infos['fac_data']['start_date'])) . '<br/>';
                        } else {
                            $infos .= '<span class="danger">Aucune</span><br/>';
                        }
                        if (isset($line_infos['first_fac_days_supp'])) {
                            $infos .= 'Décalage début facturation : ' . (int) $line_infos['first_fac_days_supp'] . ' jours <br/>';
                        }

                        $infos .= '<b>Achat : </b>';
                        if ((int) $line_infos['achat_periodicity']) {
                            $infos .= '<br/> - Périodicité : ' . $line_infos['achat_periodicity'];
                            $infos .= ' - Nb périodes achetée(s) : ' . $line_infos['achat_data']['nb_periods_bought'] . ' sur ' . $line_infos['achat_data']['nb_total_periods'] . ' (restantes : ' . $line_infos['achat_data']['nb_periods_max'] . ')<br/>';
                            $infos .= ' - Début : ' . date('d / m / Y', strtotime($line_infos['achat_data']['start_date'])) . '<br/>';
                        } else {
                            $infos .= '<span class="danger">Aucune</span><br/>';
                        }
                        if (isset($line_infos['first_achat_days_supp'])) {
                            $infos .= '<b>Décalage début achat : ' . (int) $line_infos['first_achat_days_supp'] . ' jours</b><br/>';
                        }

                        if (count($line_errors)) {
                            $msg = BimpRender::renderFoldableContainer('Infos ligne', $infos, array('open' => true));
                            $msg .= BimpTools::getMsgFromArray($line_errors, 'Erreur(s)');
                            $this->Error($msg, $commande, $line_ref);
                            $this->incIgnored();
                            $has_line_errors = true;
                            continue;
                        } else {
//                            if (!$to_bill && (!$achat_periodicity || !$to_buy)) {
//                                $this->Alert('Il ne reste aucune période à facturer ni à acheter', $commande, $line_ref);
//                                $no_transac_db->update('bimp_commande_line', array(
//                                    'no_abos_convert' => 1
//                                        ), 'id = ' . $line->id);
//                                $this->incIgnored();
//                                continue;
//                            }

                            $pfp = null;
                            if ($line->id_fourn_price) {
                                $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $line->id_fourn_price);
                            }

                            $id_fourn = (int) $product->getData('achat_def_id_fourn');

                            if ($id_fourn) {
                                if (!BimpObject::objectLoaded($pfp) || (int) $pfp->getData('fk_soc') !== $id_fourn) {
                                    $new_pfp = $product->getLastFournPrice($id_fourn);
                                    if (BimpObject::objectLoaded($new_pfp)) {
                                        $pfp = $new_pfp;
                                    }
                                }
                            }

                            if (!BimpObject::objectLoaded($pfp)) {
                                $pfp = $product->getLastFournPrice();
                            }

                            $status = 4;

                            if ((!(int) $fac_periodicity || $date_next_fac > $date_to) &&
                                    (!$achat_periodicity || $date_next_achat > $date_to)) {
                                $status = 5;
                            }

                            $contrat_line = array(
                                'line_type'                    => 2,
                                'statut'                       => $status,
                                'fk_product'                   => $product->id,
                                'product_type'                 => $line->product_type,
                                'description'                  => $line->desc,
                                'qty'                          => $qty,
                                'price_ht'                     => $line->pu_ht,
                                'tva_tx'                       => $line->tva_tx,
                                'remise_percent'               => $line->remise,
                                'fac_periodicity'              => (int) $fac_periodicity,
                                'fac_term'                     => (int) (!(int) $line->getData('fact_echue')),
                                'achat_periodicity'            => (int) $achat_periodicity,
                                'duration'                     => (int) $duration,
                                'variable_qty'                 => (int) $product->getData('variable_qty'),
                                'date_ouverture'               => $date_from,
                                'date_fin_validite'            => $date_to,
                                'date_fac_start'               => $date_fac_start,
                                'date_next_facture'            => $date_next_fac,
                                'date_achat_start'             => $date_achat_start,
                                'date_next_achat'              => $date_next_achat,
                                'fk_user_author'               => (int) $commande->getData('fk_user_author'),
                                'fk_user_ouverture'            => (int) $commande->getData('id_user_resp'),
                                'line_origin_type'             => 'commande_line',
                                'id_line_origin'               => $line->id,
                                'fk_product_fournisseur_price' => (BimpObject::objectLoaded($pfp) ? $pfp->id : 0),
                                'buy_price_ht'                 => (BimpObject::objectLoaded($pfp) ? $pfp->getData('price') : $line->pa_ht)
                            );

                            $msg = '';
                            if (in_array($line->id, $validated)) {
                                $msg .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Validée</span><br/>';
                            } elseif (isset($not_validated[$line->id])) {
                                $msg .= '<span class="danger">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Invalide</span><br/>';
                                $msg .= '<span class="danger">' . $not_validated[$line->id] . '</span><br/>';
                            }

                            $infos = 'LIGNE DE COMMANDE : <br/>' . $infos . '<br/>LIGNE DE CONTRAT : <br/>';
                            $infos .= 'Du  <b>' . date('d / m / Y', strtotime($contrat_line['date_ouverture'])) . '</b> ';
                            $infos .= 'au  <b>' . date('d / m / Y', strtotime($contrat_line['date_fin_validite'])) . '</b>';
                            $infos .= '  - <span class="' . (!in_array($contrat_line['duration'], array(1, 3, 6, 12, 24, 36)) ? 'danger' : 'bold') . '">' . $contrat_line['duration'] . ' mois</span><br/>';
                            $infos .= '<b>Fac : </b><span class="' . (in_array($contrat_line['fac_periodicity'], array(1, 3, 6, 12, 24, 36)) ? 'bold' : 'danger') . '">' . $contrat_line['fac_periodicity'] . '</span>';
                            $infos .= ' - <span class="' . ($contrat_line['date_fac_start'] > $date_to ? 'danger' : ($contrat_line['date_fac_start'] > $date_from ? 'warning' : 'bold')) . '">' . date('d / m / Y', strtotime($contrat_line['date_fac_start'])) . '</span><br/>';
//                            $infos .= ' / <b>' . date('d / m / Y', strtotime($contrat_line['date_next_facture'])) . '</b><br/>';
                            $infos .= '<b>Achat : </b><span class="' . (in_array($contrat_line['achat_periodicity'], array(1, 3, 6, 12, 24, 36)) ? 'bold' : 'danger') . '">' . $contrat_line['achat_periodicity'] . '</span>';
                            if ((int) $contrat_line['achat_periodicity']) {
                                $infos .= ' - <span class="' . ($contrat_line['date_achat_start'] > $date_to ? 'danger' : ($contrat_line['date_achat_start'] > $date_from ? 'warning' : 'bold')) . '">' . date('d / m / Y', strtotime($contrat_line['date_achat_start'])) . '</span><br/>';
//                            $infos .= ' / Prochain achat : <b>' . date('d / m / Y', strtotime($contrat_line['date_next_achat'])) . '</b><br/>';
                            }

                            $this->Info($msg . $infos, $commande, $line_ref);
                            $contrat_lines[] = $contrat_line;
                        }
                    }

//                    if ($conf->db->name != 'ERP_PROD_BIMP_01092023') {
//                        $this->setCurrentObjectData('bimpcommercial', 'Bimp_Commande');
//                        if (empty($contrat_lines) || $has_line_errors) {
//                            $this->incIgnored();
//                        } else {
//                            $this->Info('Commmande ok', $commande);
//                            $this->incUpdated();
//                        }
//                    } else {
                    if (!empty($contrat_lines) && !$has_line_errors) {
                        $this->db->db->begin();

                        $new_contrat = false;
                        $contrat = BimpCache::findBimpObjectInstance('bimpcontrat', 'BCT_Contrat', array(
                                    'fk_soc'             => (int) $commande->getData('fk_soc'),
                                    'fk_soc_facturation' => (int) $commande->getData('id_client_facture'),
                                    'entrepot'           => (int) $commande->getData('entrepot'),
                                    'version'            => 2
                                        ), true);

                        if (!BimpObject::objectLoaded($contrat)) {
                            $new_contrat = true;
                            $contrat_errors = $contrat_warnings = array();
                            $contrat = BimpObject::createBimpObject('bimpcontrat', 'BCT_Contrat', array(
                                        'fk_soc'             => (int) $commande->getData('fk_soc'),
                                        'fk_soc_facturation' => (int) $commande->getData('id_client_facture'),
                                        'entrepot'           => (int) $commande->getData('entrepot'),
                                        'secteur'            => $commande->getData('ef_type'),
                                        'expertise'          => $commande->getData('expertise'),
                                        'moderegl'           => $commande->getData('fk_mode_reglement'),
                                        'condregl'           => $commande->getData('fk_cond_reglement')
                                            ), true, $contrat_errors, $contrat_warnings);

                            if (count($contrat_warnings)) {
                                $this->Alert(BimpTools::getMsgFromArray($contrat_warnings, 'Erreurs lors de la création du contrat'), $commande);
                            }

                            if (count($contrat_errors)) {
                                $this->Error(BimpTools::getMsgFromArray($contrat_errors, 'Echec création du contrat'), $commande);
                                $this->db->db->rollback();
                                continue;
                            } else {
                                $contrat->actionValidate(array());
                            }
                        }

                        if (BimpObject::objectLoaded($contrat)) {
                            $nOk = 0;
                            foreach ($contrat_lines as $contrat_line_data) {
                                $contrat_line_data['fk_contrat'] = $contrat->id;

                                $line_errors = $line_warnings = array();
                                $contrat_line = BimpObject::createBimpObject('bimpcontrat', 'BCT_ContratLine', $contrat_line_data, true, $line_errors, $line_warnings);

                                if (count($line_errors)) {
                                    $this->Error($line_errors, $commande);
                                    $this->db->db->rollback();
                                    continue 2;
                                } else {
                                    $contrat_line->updateField('statut', $contrat_line_data['statut']);

                                    // Maj ligne de commande : 
                                    $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $contrat_line_data['id_line_origin']);
                                    if (BimpObject::objectLoaded($commande_line)) {
                                        $line_ref = 'Ligne #' . $commande_line->id . ' (n° ' . $commande_line->getData('position') . ')';

                                        $commande_line->set('id_contrat_line_export', $contrat_line->id);
                                        $commande_line->set('qty_to_ship', 0);
                                        $commande_line->set('qty_to_bill', 0);
                                        $commande_line->set('qty_shipped_not_billed', 0);
                                        $commande_line->set('qty_billed_not_shipped', 0);

                                        if ($this->db->update('bimp_commande_line', array(
                                                    'id_contrat_line_export' => $contrat_line->id,
                                                    'qty_to_ship'            => 0,
                                                    'qty_to_bill'            => 0,
                                                    'qty_shipped_not_billed' => 0,
                                                    'qty_billed_not_shipped' => 0
                                                        ), 'id = ' . $commande_line->id) <= 0) {

                                            $this->Error('Echec de la màj de la ligne de commande - ' . $this->db->err(), $commande, $line_ref);
                                            $this->db->db->rollback();
                                            continue 2;
                                        }

                                        // Annulation réservations : 
                                        $reservations = $commande_line->getReservations('status', 'asc', array(0, 2, 3, 4, 100, 101, 200));

                                        foreach ($reservations as $res) {
                                            $res_errors = $res->setNewStatus(303);

                                            if (count($res_errors)) {
                                                $this->Error(BimpTools::getMsgFromArray($res_errors, 'Echec annulation réservation #' . $res->id), $commande, $line_ref);
                                                $this->db->db->rollback();
                                                continue 2;
                                            }
                                        }

                                        // Régul stock : 
                                        if (isset($regul_stocks[$commande_line->id])) {
                                            $product = $commande_line->getProduct();
                                            $mvt = ($regul_stocks[$commande_line->id] < 0 ? 1 : 0);
                                            $code_mvt = 'REGUL_COMMANDE_LINE_' . $commande_line->id;
                                            $mvt_label = 'Régularisation - Transfert ligne de commmande #' . $commande_line->id . ' vers ligne de contrat #' . $contrat_line->id;
                                            $stock_errors = $product->correctStocks($commande->getData('entrepot'), abs($regul_stocks[$commande_line->id]), $mvt, $code_mvt, $mvt_label, 'commande', (int) $commande->id);

                                            if (count($stock_errors)) {
                                                $this->Error(BimpTools::getMsgFromArray($stock_errors, 'Echec régule stock'), $commande, $line_ref);
                                                $this->db->db->rollback();
                                                continue 2;
                                            }
                                        }
                                    }

                                    $nOk++;
                                    if (count($line_warnings)) {
                                        $this->Alert($line_warnings, $contrat);
                                    }
                                }
                            }

                            if ($nOk > 0) {
                                if ($new_contrat) {
                                    $this->setCurrentObjectData('bimpcontrat', 'BCT_Contrat');
                                    $this->Success('Contrat {{Contrat2:' . $contrat->id . '}} créé avec succès', $commande);
                                    $this->incCreated();
                                }

                                $this->Success($nOk . ' ligne(s) de contrat créée(s) avec succès', $contrat);
                                $this->setCurrentObjectData('bimpcontrat', 'BCT_ContratLine');
                                $this->incCreated('current', $nOk);

                                $commande->checkLogistiqueStatus();
                                $commande->checkShipmentStatus();
                                $commande->checkInvoiceStatus();

                                $this->db->db->commit();
                                if ((int) $this->getOption('test_one', 0)) {
                                    break;
                                }
                            } else {
                                $this->db->db->rollback();
                            }
                        }
                    } else {
                        $this->setCurrentObjectData('bimpcommercial', 'Bimp_Commande');
                        $this->incIgnored();

                        $commandes_fails[] = $id_commande;
                    }
//                    }
                }
            } else {
                $this->Error('ID commande absent (Lignes : ' . explode(', ', $lines));
                $this->incIgnored();
            }
        }

        if (!empty($commandes_fails)) {
            $this->Alert('Commandes non traitables: ' . implode(', ', $commandes_fails));
        }
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

    public static function updateProcess($id_process, $cur_version, &$warnings = array())
    {
        $errors = array();

        if ($cur_version < 2) {
            $bdb = BimpCache::getBdb();
            $bdb->update('bds_process_option', array(
                'label'         => 'Type de conversion',
                'type'          => 'select',
                'select_values' => 'static::methods',
                'info'          => ''
                    ), 'id_process = ' . $id_process . ' AND name = \'method\'');
        }

        return $errors;
    }
}
