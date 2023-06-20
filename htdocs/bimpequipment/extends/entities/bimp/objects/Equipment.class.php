<?php

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/Equipment.class.php';

class Equipment_ExtEntity extends Equipment
{

    // Getters booléens: 

    public function as_client_spare()
    {
        if ($this->getIdEntrepotSpare())
            return true;
        return false;
    }

    public function as_client_spare_or_date()
    {
        if ($this->as_client_spare() || $this->getData('date_fin_spare'))
            return true;
        return false;
    }

    public function as_sapre_actif()
    {
        if (!$this->as_client_spare())
            return false;
        if ($this->getData('date_fin_spare') && strtotime($this->getData('date_fin_spare')) > dol_now())
            return true;
    }

    public function isActionAllowed($action, &$errors = [])
    {
        switch ($action) {
            case 'importSpare':
                if (BimpTools::getValue('fc', '') == 'client') {
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) BimpTools::getValue('id', 0));
                    if (BimpObject::objectLoaded($client)) {
                        if (!(int) $client->getData('entrepot_spare')) {
                            $errors[] = 'Le client ' . $client->getLink() . ' n\'a pas d\'entrepôt SPARE';
                            return 0;
                        }
                    } else {
                        $errors[] = 'Client absent';
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters 

    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();

        if ($this->as_sapre_actif())
            $buttons[] = array(
                'label'   => 'Echange SPARE',
                'icon'    => 'fas_link',
                'onclick' => $this->getJsActionOnclick('changeSpareMaterial', array('libelle' => "SPARE " . $this->getData('serial')), array('form_name' => 'changeSpare'))
            );

        return $buttons;
    }

    public function getIdEntrepotSpare()
    {
        $place = $this->getCurrentPlace();
        if ($place && $place->isLoaded() && $place->getData('id_client') > 0) {
            $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $place->getData('id_client'));
            return $cli->getData('entrepot_spare');
        }
    }

    public function getChangeEquipment($memeRef = true)
    {
        $filtre = array(
            "places.type"        => 2,
            "places.position"    => 1,
            "places.id_entrepot" => $this->getIdEntrepotSpare()
        );
        if ($memeRef)
            $filtre['id_product'] = $this->getData("id_product");
        $list = BimpCache::getBimpObjectObjects('bimpequipment', 'Equipment',
                                                $filtre, null, null, array('places' => array(
                        'table' => 'be_equipment_place',
                        'alias' => 'places',
                        'on'    => 'places.id_equipment = a.id'
                    ))
        );
        $result = array();
        foreach ($list as $obj)
            $result[$obj->id] = $obj->getRef();
        return $result;
    }

    public function getDefaultListHeaderButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('importSpare') && $this->canSetAction('importSpare')) {
            $data = array();

            if (BimpTools::getValue('fc', '') == 'client') {
                $data['id_client'] = (int) BimpTools::getValue('id', 0);
            }

            $buttons[] = array(
                'label'   => 'Import SPARE',
                'icon'    => 'fas_file-import',
                'onclick' => $this->getJsActionOnclick('importSpare', $data, array(
                    'form_name' => 'import_spare'
                ))
            );
        }

        return $buttons;
    }

    // Traitements: 

    public function createCommandeLnText($commande, $text, &$errors, &$warnings)
    {
        $line = $commande->getLineInstance();
        $errors = BimpTools::merge_array($errors, $line->validateArray(array(
                            'id_obj'    => (int) $commande->id,
                            'type'      => ObjectLine::LINE_TEXT,
                            'deletable' => 1,
                            'editable'  => 0,
                            'remisable' => 0,
        )));

        if (!count($errors)) {
            $line->id_product = null;
            $line->qty = 1;
            $line->desc = $text;

            $errors = BimpTools::merge_array($errors, $line->create($warnings, true));
        }
    }

    // Actions: 

    public function actionChangeSpareMaterial($data, &$success = '')
    {
        $errors = $warnings = array();
        $success_callback = '';
        $success = 'Echange OK';

        //inversion des dates de spare
        $newEquipment = BimpCache::getBimpObjectInstance($this->module, $this->object_name, ($data['memeProd'] ? $data['newEquipment'] : $data['newEquipment2']));
        $newEquipment->updateField('date_fin_spare', $this->getData('date_fin_spare'));
        $this->updateField('date_fin_spare', null);

        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $place = $this->getCurrentPlace();
        if ($place && $place->isLoaded() && $place->getData('id_client') > 0) {
            //creation commande
            $commande->validateArray(array(
                'fk_soc'        => $place->getData('id_client'),
                'entrepot'      => $this->getIdEntrepotSpare(),
                'ef_type'       => 'C',
                'date_commande' => dol_now(),
                'libelle'       => $data['libelle'],
                'expertise'     => 60
                    )
            );
            $errors = $commande->create($warnings, true);
            if (!count($errors))
                $success_callback = 'window.open(\'' . $commande->getUrl() . '\');';

            //ajout info text
            if ($data['infos1'] != '')
                $this->createCommandeLnText($commande, $data['infos1'], $errors, $warnings);

            //creation ligne d'envoie
            if (!count($errors)) {
                $this->createCommandeLnText($commande, 'Expédition du téléphone SPARE :', $errors, $warnings);
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'    => (int) $commande->id,
                    'type'      => ObjectLine::LINE_PRODUCT,
                    'deletable' => 1,
                    'editable'  => 0,
                    'remisable' => 0,
                ));

                if (!count($errors)) {
                    $line->id_product = $newEquipment->getData('id_product');
                    $line->qty = 1;

                    $errors = $line->create($warnings, true);

                    if (!count($errors)) {
                        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                        $errors = $reservation->validateArray(array(
                            'id_client'               => (int) $place->getData('id_client'),
                            'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                            'id_entrepot'             => $this->getIdEntrepotSpare(),
                            'id_equipment'            => $newEquipment->id,
                            'id_commande_client'      => $commande->id,
                            'id_commande_client_line' => $line->id,
                            'status'                  => 200,
                            'date_from'               => date("Y-m-d H:i:s")
                        ));

                        if (!count($errors)) {
                            $errors = $reservation->create($warnings, true);
                        }
                    }
                }
            }

            //creation ligne de retour
            if (!count($errors)) {
                $this->createCommandeLnText($commande, 'Récupération du téléphone endommagé :', $errors, $warnings);
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'              => (int) $commande->id,
                    'type'                => ObjectLine::LINE_PRODUCT,
                    'deletable'           => 1,
                    'editable'            => 0,
                    'remisable'           => 0,
                    'equipments_returned' => array($this->id => 506)
                ));

                if (!count($errors)) {
                    $line->id_product = $this->getData('id_product');
                    $line->qty = -1;

                    $errors = $line->create($warnings, true);

                    if (!count($errors)) {
                        $this->updateField('id_commande_line_return', $line->id);
                    }
                }
            }

            //ajout info text
            if ($data['infos2'] != '')
                $this->createCommandeLnText($commande, $data['infos2'], $errors, $warnings);

            //creation ligne de port
            if (!count($errors)) {
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'    => (int) $commande->id,
                    'type'      => ObjectLine::LINE_PRODUCT,
                    'deletable' => 1,
                    'editable'  => 0,
                    'remisable' => 0
                ));

                if (!count($errors)) {
                    $line->id_product = 4300;
                    $line->qty = 1;
                    $line->pu_ht = 0;

                    $errors = $line->create($warnings, true);
                }
            }
        } else
            $errors[] = 'Client introuvable';



        return array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $success_callback);
    }

    public function actionImportSpare($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_client = (int) BimpTools::getArrayValueFromPath($data, 'id_client', 0);
        if (!$id_client) {
            $errors[] = 'Aucun client sélectionné';
        } else {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Le client #' . $id_client . ' n\'existe plus';
            } elseif (!(int) $client->getData('entrepot_spare')) {
                $errors[] = 'Le client ' . $client->getLink() . ' n\'a pas d\'entrepôt SPARE';
            }
        }

        $file_name = BimpTools::getArrayValueFromPath($data, 'file/0', '');
        if (!$file_name) {
            $errors[] = 'Fichier absent';
        } else {
            $file = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir() . '/' . $file_name;

            if (!file_exists($file)) {
                $errors[] = 'Le fichier semble ne pas avoir été téléchargé correctement';
            }
        }

        if (!count($errors)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (empty($lines)) {
                $errors[] = 'Fichier vide';
            } else {
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $keys = array(
                    'ref_prod'       => 0,
                    'serial'         => 1,
                    'date_fin_spare' => 2,
                    'note'           => 3,
                    'ref_commande'   => 4
                );
                $i = 0;
                foreach ($lines as $idx => $line) {
                    $i++;
                    $line_errors = array();

                    $line_data = str_getcsv($line, ';');
                    $ref_prod = BimpTools::getArrayValueFromPath($line_data, $keys['ref_prod'], '', $line_errors, true, 'Réf produit absente');
                    $serial = BimpTools::getArrayValueFromPath($line_data, $keys['serial'], '', $line_errors, true, 'N° de série absent');
                    $date_fin_spare = BimpTools::getArrayValueFromPath($line_data, $keys['date_fin_spare'], '', $line_errors, true, 'Date fin de Spare absente');
                    $note = BimpTools::getArrayValueFromPath($line_data, $keys['note'], '');
                    $ref_commmande = BimpTools::getArrayValueFromPath($line_data, $keys['ref_commande'], '', $line_errors, true, 'Ref commande contrat Spare absente');

                    $date_fin_spare = str_replace(' ', '', $date_fin_spare);
                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date_fin_spare, $matches)) {
                        $date_fin_spare = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                    } else {
                        $line_errors[] = 'Format de la date de fin de Spare invalie (Attendu : JJ/MM/AAAA';
                    }

                    if (!count($line_errors)) {
                        $full_note = $note . ($note ? "\n" : '') . 'Référence commande contrat Spare : ' . $ref_commmande;
                        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                    'ref' => $ref_prod
                                        ), true);

                        if (!BimpObject::objectLoaded($prod)) {
                            $line_errors[] = 'Aucun produit trouvé pour la référence "' . $ref_prod . '"';
                        } else {
                            $eq = BimpObject::createBimpObject('bimpequipment', 'Equipment', array(
                                        'id_product'     => $prod->id,
                                        'serial'         => $serial,
                                        'note'           => $full_note,
                                        'date_fin_spare' => $date_fin_spare
                                            ), true, $line_errors);

                            if (BimpObject::objectLoaded($eq)) {
                                BimpObject::createBimpObject('bimpequipment', 'BE_Place', array(
                                    'id_equipment' => $eq->id,
                                    'type'         => BE_Place::BE_PLACE_CLIENT,
                                    'id_client'    => $id_client,
                                    'date'         => date('Y-m-d H:i:s'),
                                    'infos'        => $full_note,
                                    'origin'       => 'societe',
                                    'id_origin'    => $client->id
                                        ), true, $line_errors);
                            }
                        }
                    }

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $i);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
