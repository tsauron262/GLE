<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class Bimp_Propal_ExtEntity extends Bimp_Propal
{

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'close':
            case 'modify':
            case 'review':
            case 'reopen':
                if ((int) $this->getData('id_demande_fin')) {
                    $df = $this->getChildObject('demande_fin');
                    if (BimpObject::objectLoaded($df)) {
                        $df_status = (int) $df->getData('status');
                        if ($df_status > 0 && $df_status < 10) {
                            $errors[] = 'Une demande de location est en attente d\'acceptation';
                            return 0;
                        }

                        if ($df_status === BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                            if ((int) $df->getData('devis_fin_status') < 20) {
                                $errors[] = 'Devis de location non refusé ou annulé';
                                return 0;
                            }
                            if ((int) $df->getData('contrat_fin_status') < 20) {
                                $errors[] = 'Contrat de location non refusé ou annulé';
                                return 0;
                            }
                        }
                    }
                }
                break;

            case 'createOrder':
            case 'createInvoice':
            case 'classifyBilled':
            case 'createContrat':
            case 'createSignature':
            case 'addAcompte':
                if (in_array($action, array('createOrder', 'createInvoice'))) {
                    if (!(int) $this->getData('id_demande_fin')) {
                        if (!(int) $this->getData('id_signature')) {
                            $errors[] = 'Fiche signature obligatoire';
                            return 0;
                        }

                        $signature = $this->getChildObject('signature');
                        if (!BimpObject::objectLoaded($signature)) {
                            $errors[] = 'Fiche signature invalide';
                            return 0;
                        } elseif (!$signature->isSigned()) {
                            $errors[] = 'Fiche signature non signée';
                            return 0;
                        }
                    }
                }

                if ($action !== 'createOrder' && (int) $this->getData('id_demande_fin')) {
                    $df = $this->getChildObject('demande_fin');
                    if (BimpObject::objectLoaded($df)) {
                        $df_status = (int) $df->getData('status');
                        if ($df_status > 0 && $df_status < 10) {
                            $errors[] = 'Une demande de location est en attente d\'acceptation';
                            return 0;
                        }

                        if ($df_status === BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                            if ((int) $df->getData('devis_fin_status') !== BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                                $errors[] = 'Devis de location non signé';
                                return 0;
                            }
                            if ((int) $df->getData('contrat_fin_status') !== BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                                $errors[] = 'Contrat de location non signé';
                                return 0;
                            }
                        } elseif ($df_status < 20) {
                            $errors[] = 'Devis de location non accepté par le client';
                            return 0;
                        }
                    }
                }
                break;

            case 'getPropositionLocation':
                if (!$this->isLoaded()) {
                    return 0;
                }

                if (BimpCore::isUserDev()) {
                    return 1;
                }

                if (!in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                    $errors[] = 'Statut invalide pour la génération d\'une proposition de location';
                    return 0;
                }

                if ((int) $this->getData('id_demande_fin')) {
                    $errors[] = 'Une demande de location a déjà été faite pour ce devis';
                    return 0;
                }

                if (!$this->isDemandeFinAllowed($errors)) {
                    return 0;
                }
                return 1;
        }
        
        return parent::isActionAllowed($action, $errors);
    }

    public function isDemandeFinAllowed(&$errors = array())
    {
        if (!(int) BimpCore::getConf('allow_df_from_propal', null, 'bimpcommercial')) {
            $errors[] = 'Demandes de location à partir des devis désactivées';
            return 0;
        }

        return 1;
    }

    public function isDemandeFinCreatable(&$errors = array())
    {
        if (!parent::isDemandeFinCreatable($errors)) {
            return 0;
        }

        if (!in_array((int) $this->getData('fk_statut'), array(1, 2, 4))) {
            $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '" ou "accepté' . $this->e() . '"';
            return 0;
        }

        return 1;
    }

    public function isDocuSignAllowed(&$errors = array(), &$is_required = false)
    {
        if (!parent::isDocuSignAllowed($errors)) {
            return 0;
        }

        $sav = $this->getSav();
        if (BimpObject::objectLoaded($sav)) {
            $errors[] = 'Signature via DocuSign on autorisée pour les devis SAV';
            return 0;
        }

        // Ajouter conditions spécifiques à BIMP ici
        // (ne pas oublier d'alimenter $errors)

        global $user;
        if (!$user->admin) { // Temporaire
            $errors[] = 'Réservé aux admin pour l\'instant';
            return 0;
        }

        $is_required = false;
        return 1;
    }

    public function isSignDistAllowed(&$errors = array())
    {
        if (!parent::isSignDistAllowed($errors)) {
            return 0;
        }

        // Ajouter conditions spécifiques à BIMP ici
        // (ne pas oublier d'alimenter $errors)

        return 1;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();
        $df_buttons = parent::getDemandeFinButtons();

        $err = array();
        if ($this->isActionAllowed('getPropositionLocation', $err) && $this->canSetAction('getPropositionLocation')) {
            $df_buttons[] = array(
                'label'   => 'Obtenir une simulation de location',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('getPropositionLocation', array(), array(
                    'form_name' => 'proposition_loc'
                ))
            );
        }

        if (!empty($df_buttons)) {
            if (isset($buttons['buttons_groups'])) {
                $buttons['buttons_groups'][] = array(
                    'label'   => 'Location',
                    'icon'    => 'fas_hand-holding-usd',
                    'buttons' => $df_buttons
                );
            } else {
                return array(
                    'buttons_groups' => array(
                        array(
                            'label'   => 'Actions',
                            'icon'    => 'fas_cogs',
                            'buttons' => $buttons
                        ),
                        array(
                            'label'   => 'Location',
                            'icon'    => 'fas_hand-holding-usd',
                            'buttons' => $df_buttons
                        )
                    )
                );
            }
        }

        return $buttons;
    }

    // Getters array: 

    public function getBcdfTargetsArray()
    {
        BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $className);
        return $className::$targets;
    }

    public function getDFDurationsArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$durations;
    }

    public function getDFPeriodicitiesArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$periodicities;
    }

    public function getDFCalcModesArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$calc_modes;
    }

    // Getters Données: 

    public function getInputValue($input_name)
    {
        if ($this->field_exists($input_name) && $this->isLoaded()) {
            return $this->getData($input_name);
        }

        switch ($input_name) {
            case 'duration':
            case 'periodicity':
            case 'mode_calcul':
                BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $className);
                $target = BimpTools::getPostFieldValue('target', '');
                if (!$target) {
                    $target = $className::$def_target;
                }
                if (isset($className::$targets_defaults[$target][$input_name])) {
                    return $className::$targets_defaults[$target][$input_name];
                }
                return $className::${'def_' . $input_name};
        }

        return null;
    }

    // Rendus HTML:

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '<div class="buttonsContainer">';
        $html .= BimpComm_ExtEntity::renderHeaderExtraRight($no_div);
        $html .= parent::renderHeaderExtraRight(true);
        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function onDocFinancementSigned($doc_type)
    {
        switch ($doc_type) {
            case 'devis_fin':
                if ((int) $this->getData('fk_statut') !== Propal::STATUS_SIGNED) {
                    $this->updateField('fk_statut', Propal::STATUS_SIGNED);

                    // Vérification de l\'existance d'une commande: 
                    $where = '`fk_source` = ' . (int) $this->id . ' AND `sourcetype` = \'propal\'';
                    $where .= ' AND `targettype` = \'commande\'';

                    $id_commande = (int) $this->db->getValue('element_element', 'fk_target', $where, 'fk_target');
                    if ($id_commande) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                        if (BimpObject::objectLoaded($commande)) {
                            $df = $this->getChildObject('demande_fin');

                            if (BimpObject::objectLoaded($df)) {
                                $this->db->update('commande', array(
                                    'id_demande_fin'    => $df->id,
                                    'id_client_facture' => $df->getTargetIdClient()
                                        ), 'rowid = ' . $id_commande);
                            }
                        }
                    }
                }
                break;
        }
    }

    // Actions: 

    public function actionGetPropositionLocation($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $target = BimpTools::getArrayValueFromPath($data, 'target', '');

        if (!$target) {
            $errors[] = 'Veuillez sélectionner le financeur';
        } else {
            unset($data['target']);

            $bcdf = BimpObject::getInstance('bimpcommercial', 'BimpCommDemandeFin');
            $bcdf->set('target', $target);
            $api = $bcdf->getExternalApi($errors);

            if (!count($errors)) {
                $montant_materiel = 0;
                $montant_services = 0;
                $lines = array();

                foreach ($this->getLines('all') as $line) {
                    if ((int) $line->id_remise_except) {
                        continue; // On exclut les événtuels acomptes / avoirs. 
                    }

                    switch ($line->getData('type')) {
                        case ObjectLine::LINE_PRODUCT:
                            $product = $line->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $product_type = 1; // Produit
                                if ($product->field_exists('type2') && (int) $product->getData('type2') === 5) {
                                    $product_type = 3; // Logiciel
                                } elseif ($product->isTypeService()) {
                                    $product_type = 2; // Service
                                }
                                $lines[] = array(
                                    'label' => $product->getRef() . '<br/>' . $product->getName(),
                                    'qty'   => $line->getFullQty()
                                );
                                if ($product_type == 1) {
                                    $montant_materiel += $line->getTotalHTWithRemises(true);
                                } else {
                                    $montant_services += $line->getTotalHTWithRemises(true);
                                }
                            }
                            break;

                        case ObjectLine::LINE_FREE:
                            $lines[] = array(
                                'label' => $line->description,
                                'qty'   => $line->getFullQty()
                            );
                            if ($line->isService()) {
                                $montant_services += $line->getTotalHT(true);
                            } else {
                                $montant_materiel += $line->getTotalHT(true);
                            }
                            break;

                        case ObjectLine::LINE_TEXT:
                            $lines[] = array(
                                'text'  => 1,
                                'label' => $line->desc,
                            );
                            break;
                    }
                }

                $data['lines'] = json_encode($lines);
                $data['montant_materiels'] = $montant_materiel;
                $data['montant_services'] = $montant_services;

                $result = $api->getPropositionLocation($data, $errors, $warnings);

                if ((int) BimpTools::getArrayValueFromPath($result, 'success', 0)) {
                    $file_content = BimpTools::getArrayValueFromPath($result, 'file', '');
                    if (!$file_content) {
                        $errors[] = 'Contenu du fichier non reçu';
                    } else {
                        $dir = $this->getFilesDir();

                        if (!is_dir($dir)) {
                            $err = BimpTools::makeDirectories($dir);

                            if ($err) {
                                $errors[] = 'Echec de la création du dossier de destination - ' . $err;
                            }
                        } else {
                            $file_name = 'Proposition_Location.pdf';
                            $file_path = $dir . $file_name;

                            if (!file_put_contents($file_path, base64_decode($file_content))) {
                                $errors[] = 'Echec de l\'enregistrement du fichier';
                            } else {
                                $file = BimpObject::getInstance('bimpcore', 'BimpFile');
                                $file->checkObjectFiles($this->module, $this->object_name, $this->id);

                                $file = BimpCache::findBimpObjectInstance('bimpcore', 'BimpFile', array(
                                            'parent_module'      => $this->module,
                                            'parent_object_name' => $this->object_name,
                                            'id_parent'          => $this->id,
                                            'file_name'          => 'Proposition_Location',
                                            'file_ext'           => 'pdf'
                                                ), true);

                                if (BimpObject::objectLoaded($file)) {
                                    $file->updateField('in_emails', 1);
                                }

                                $url = $this->getFileUrl($file_name);
                                if ($url) {
                                    $sc = 'window.open(\'' . $url . '\');';
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }
}
