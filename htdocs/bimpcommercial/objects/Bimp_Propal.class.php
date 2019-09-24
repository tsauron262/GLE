<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_Propal extends BimpComm
{

    public static $dol_module = 'propal';
    public static $email_type = 'propal_send';
    public $id_sav = null;
    public $sav = null;
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Signée (A facturer)', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Non signée (fermée)', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        4 => array('label' => 'Facturée (fermée)', 'icon' => 'check', 'classes' => array('success')),
    );
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

    // Gestion des droits users

    public function canCreate()
    {
        global $user;
        if (isset($user->rights->propal->creer)) {
            return (int) $user->rights->propal->creer;
        }
        return 1;
    }

    public function canEdit()
    {
        return $this->can("create");
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->propal->propal_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'addContact':
                if (!empty($user->rights->propale->creer)) {
                    return 1;
                }
                return 0;

            case 'close':
            case 'reopen':
            case 'classifyBilled':
                if (!empty($user->rights->propal->cloturer)) {
                    return 1;
                }
                return 0;

            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send) {
                    return 1;
                }
                return 0;

            case 'modify':
            case 'review':
                return $this->can("edit");

            case 'createOrder':
                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                return $commande->can("create");

            case 'createContract':
                if ($user->rights->contrat->creer) {
                    return 1;
                }
                return 0;

            case 'createInvoice':
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                return $facture->can("create");

            case 'setRemiseGlobale':
                return $this->can("edit");
        }
        return 1;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        global $conf;
        $status = $this->getData('fk_statut');

        if (in_array($action, array('modify', 'modify', 'review', 'close', 'reopen', 'sendEmail', 'createOrder', 'createContract', 'createInvoice', 'classifyBilled'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                return 0;
            }
            if (is_null($status)) {
                $errors[] = 'Statut absent';
                return 0;
            }
        }
        $status = (int) $status;
        $soc = $this->getChildObject('client');

        switch ($action) {
            case 'validate':
                if (!BimpObject::objectLoaded($soc)) {
                    $errors[] = 'Client absent';
                    return 0;
                }
                if ($status !== Propal::STATUS_DRAFT) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (!count($this->dol_object->lines)) {
                    $errors[] = 'Aucune ligne enregistrée pour ' . $this->getLabel('this');
                    return 0;
                }
                return 1;

            case 'modify':
            case 'close':
                if ($status !== Propal::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'review':
                if ($status === 0) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est encore au statut brouillon';
                }

                if ((int) $this->getIdSav()) {
                    $sav = $this->getSav();
                    if (BimpObject::objectLoaded($sav)) {
                        $errors[] = ucfirst($this->getLabel('this')) . ' est liée au SAV ' . $sav->getNomUrl(0, 1, 1, 'default') . '. Veuillez utiliser le bouton réviser depuis la fiche SAV';
                    }
                }
                if ($status === Propal::STATUS_SIGNED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est signée';
                }

                if ($status === Propal::STATUS_BILLED) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est facturée';
                }

                $where = '`fk_source` = ' . $this->id . ' AND `sourcetype` = \'propal\'';
                $where .= ' AND `targettype` = \'commande\'';
                $id_commande = (int) $this->db->getValue('element_element', 'fk_target', $where);
                if ($id_commande) {
                    $errors[] = 'Une commande a été créée à partir de cette proposition commerciale';
                }

                return (count($errors) ? 0 : 1);

            case 'reopen':
                if (!in_array($status, array(Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'sendEmail':
                if (!in_array($status, array(Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'createOrder':
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->commande->enabled)) {
                    $errors[] = 'Création des commandes désactivée';
                    return 0;
                }
                return 1;

            case 'createContract':
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->contrat->enabled)) {
                    $errors[] = 'Création des contrats désactivée';
                    return 0;
                }
                return 1;

            case 'createInvoice':
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                if (empty($conf->facture->enabled)) {
                    $errors[] = 'Création des factures désactivée';
                    return 0;
                }
                return 1;

            case 'classifyBilled':
                $factures = $this->dol_object->getInvoiceArrayList();
                if ($status !== Propal::STATUS_SIGNED) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                $factures = $this->dol_object->getInvoiceArrayList();
                if (!empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED) && (!is_array($factures) || !count($factures))) {
                    $errors[] = 'Aucune facture créée pour ' . $this->getLabel('this');
                    return 0;
                }
                return 1;

            default:
                return (int) parent::isActionAllowed($action, $errors);
        }

        return 1;
    }

    public function iAmAdminRedirect()
    {
        global $user;
        if (in_array($user->id, array(60, 282)))
            return true;
        return parent::iAmAdminRedirect();
    }

    // Getters: 

    public function getIdSav()
    {
        if (is_null($this->id_sav)) {
            if ($this->isLoaded()) {
                $this->id_sav = (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $this->id);
            } else {
                return 0;
            }
        }

        return $this->id_sav;
    }

    public function getSav()
    {
        if (is_null($this->sav)) {
            $id_sav = (int) $this->getIdSav();
            if ($id_sav) {
                $this->sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
            }
        }

        return $this->sav;
    }

    public function getDureeValidite()
    {
        if ($this->isLoaded()) {
            return 1;
        }

        return 15;
    }

    // Getters - overrides BimpComm

    public function getModelPdf()
    {
        if ((int) $this->getIdSav()) {
            return 'bimpdevissav';
        }

        return $this->getData('model_pdf');
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFPropales')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
        }

        return ModelePDFPropales::liste_modeles($this->db->db);
    }

    public function getCloseStatusArray()
    {
        return array(
            2 => self::$status_list[2]['label'],
            3 => self::$status_list[3]['label']
        );
    }

    public function getActionsButtons()
    {
        global $langs;
        $langs->load('propal');

        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Générer le PDF',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );

            $status = $this->getData('fk_statut');

            if (!is_null($status)) {
                $status = (int) $status;

                // Valider:
                if ($this->isActionAllowed('validate')) {
                    if ($this->canSetAction('validate')) {
                        $buttons[] = array(
                            'label'   => 'Valider',
                            'icon'    => 'check',
                            'onclick' => $this->getJsActionOnclick('validate', array(), array(
                                'confirm_msg' => 'Veuillez confirmer la validation ' . $this->getLabel('of_this')
                            ))
                        );
                    } else {
                        $buttons[] = array(
                            'label'    => 'Valider',
                            'icon'     => 'check',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => 'Vous n\'avez pas la permission de valider cette proposition commerciale'
                        );
                    }
                }

                // Modifier
//                if ($this->isActionAllowed('modify')) {
//                    if ($this->canSetAction('modify') && $user->admin) {
//                        $buttons[] = array(
//                            'label'   => 'Modifier',
//                            'icon'    => 'fas_undo',
//                            'onclick' => $this->getJsActionOnclick('modify', array())
//                        );
//                    } else {
//                        $buttons[] = array(
//                            'label'    => 'Modifier',
//                            'icon'     => 'fas_undo',
//                            'onclick'  => '',
//                            'disabled' => 1,
//                            'popover'  => 'Vous n\'avez pas la permission de modifier cette proposition commerciale'
//                        );
//                    }
//                }
                // Réviser: 
                if ($status > 0) {
                    $errors = array();
                    $msg = '';
                    if ($this->isActionAllowed('review', $errors)) {
                        if ($this->canSetAction('review')) {
                            $buttons[] = array(
                                'label'   => 'Réviser',
                                'icon'    => 'fas_undo',
                                'onclick' => $this->getJsActionOnclick('review', array(), array(
                                    'confirm_msg' => 'Veuillez confirmer la mise en révision de cette proposition commerciale'
                                ))
                            );
                        } else {
                            $msg = 'Vous n\'avez pas la permission';
                        }
                    } else {
                        $msg = BimpTools::getMsgFromArray($errors);
                    }
                    if ($msg) {
                        $buttons[] = array(
                            'label'    => 'Réviser',
                            'icon'     => 'fas_undo',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => $msg
                        );
                    }
                }


                // Accepter / Refuser
                if ($this->isActionAllowed('close')) {
                    if ($this->canSetAction('close')) {
                        $buttons[] = array(
                            'label'   => 'Fermer (Accepter/Refuser)',
                            'icon'    => 'times',
                            'onclick' => $this->getJsActionOnclick('close', array(), array(
                                'form_name' => 'close'
                            ))
                        );

                        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                        $values = array(
                            'fields' => array(
                                'entrepot'           => (int) $this->getData('entrepot'),
                                'ef_type'            => $this->getData('ef_type'),
                                'fk_soc'             => (int) $this->getData('fk_soc'),
                                'ref_client'         => $this->getData('ref_client'),
                                'fk_cond_reglement'  => (int) $this->getData('fk_cond_reglement'),
                                'fk_mode_reglement'  => (int) $this->getData('fk_mode_reglement'),
                                'fk_availability'    => (int) $this->getData('fk_availability'),
                                'fk_input_reason'    => (int) $this->getData('fk_input_reason'),
                                'date_commande'      => date('Y-m-d'),
                                'date_livraison'     => $this->getData('date_livraison'),
                                'libelle'            => $this->getData('libelle'),
                                'pdf_hide_pu'        => $this->getData('pdf_hide_pu'),
                                'pdf_hide_reduc'     => $this->getData('pdf_hide_reduc'),
                                'pdf_hide_total'     => $this->getData('pdf_hide_total'),
                                'pdf_hide_ttc'       => $this->getData('pdf_hide_ttc'),
                                'pdf_periodicity'    => $this->getData('pdf_periodicity'),
                                'pdf_periods_number' => $this->getData('pdf_periods_number'),
                                'origin'             => 'propal',
                                'origin_id'          => (int) $this->id,
                                'close_propal'       => 1
                            )
                        );
                        $onclick = $commande->getJsLoadModalForm('default', 'Création d\\\'une commande (Signature préalable de la proposition commerciale)', $values, '', 'redirect');

                        $buttons[] = array(
                            'label'   => BimpRender::renderIcon('fas_dolly', 'iconLeft') . 'Accepter et créer commande',
                            'icon'    => 'fas_check',
                            'onclick' => $onclick
                        );
                    } else {
                        $buttons[] = array(
                            'label'    => 'Fermer',
                            'icon'     => 'times',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => 'Vous n\'avez pas la permission de fermer cette proposition commerciale'
                        );
                    }
                }

                // Réouvrir:
                if ($this->isActionAllowed('reopen')) {
                    if ($this->canSetAction('reopen')) {
                        $text = $langs->trans('ConfirmReOpenProp', $this->getRef());
                        $buttons[] = array(
                            'label'   => 'Réouvrir',
                            'icon'    => 'undo',
                            'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                                'confirm_msg' => strip_tags($text)
                            ))
                        );
                    } else {
                        $buttons[] = array(
                            'label'    => 'Réouvrir',
                            'icon'     => 'undo',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => 'Vous n\'avez pas la permission de réouvrir cette proposition commerciale'
                        );
                    }
                }

                // Envoi mail:
                if ($this->isActionAllowed('sendEmail') && $this->canSetAction('sendEmail')) {
                    $onclick = $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ));
                    $buttons[] = array(
                        'label'   => 'Envoyer par email',
                        'icon'    => 'envelope',
                        'onclick' => $onclick,
                    );
                }

                // Créer commande: 
                if ($this->isActionAllowed('createOrder') && $this->canSetAction('createOrder')) {
                    $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                    $values = array(
                        'fields' => array(
                            'entrepot'          => (int) $this->getData('entrepot'),
                            'ef_type'           => $this->getData('ef_type'),
                            'fk_soc'            => (int) $this->getData('fk_soc'),
                            'ref_client'        => $this->getData('ref_client'),
                            'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                            'fk_mode_reglement' => (int) $this->getData('fk_mode_reglement'),
                            'fk_availability'   => (int) $this->getData('fk_availability'),
                            'fk_input_reason'   => (int) $this->getData('fk_input_reason'),
                            'date_commande'     => date('Y-m-d'),
                            'date_livraison'    => $this->getData('date_livraison'),
                            'libelle'           => $this->getData('libelle'),
                            'origin'            => 'propal',
                            'origin_id'         => (int) $this->id,
                        )
                    );
                    $onclick = $commande->getJsLoadModalForm('default', 'Création d\\\'une commande', $values, '', 'redirect');
                    $buttons[] = array(
                        'label'   => 'Créer une commande',
                        'icon'    => 'fas_dolly',
                        'onclick' => $onclick
                    );
                }

                // Créer contrat:
                if ($this->isActionAllowed('createContract') && $this->canSetAction('createContract')) {
                    $url = DOL_URL_ROOT . '/contrat/card.php?action=create&origin=propal&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
                    $buttons[] = array(
                        'label'   => 'Créer un contrat',
                        'icon'    => 'fas_file-signature',
//                        'onclick' => $this->getJsActionOnclick('createContract')
                        'onclick' => 'window.location = \'' . $url . '\''
                    );
                }
//                
                // Créer facture / avoir
                if ($this->isActionAllowed('createInvoice') && $this->canSetAction('createInvoice')) {
                    $url = DOL_URL_ROOT . '/compta/facture/card.php?action=create&origin=propal&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
                    $buttons[] = array(
                        'label'   => 'Créer une facture ou un avoir',
                        'icon'    => 'fas_file-invoice-dollar',
//                        'onclick' => $this->getJsActionOnclick('createInvoice')
                        'onclick' => 'window.location = \'' . $url . '\''
                    );
                }

                // Classer facturée
                if ($this->isActionAllowed('classifyBilled') && $this->canSetAction('classifyBilled')) {
                    $buttons[] = array(
                        'label'   => 'Classer facturée',
                        'icon'    => 'check',
                        'onclick' => $this->getJsActionOnclick('classifyBilled')
                    );
                }

                // Cloner: 
                if ($this->can("create")) {
                    $buttons[] = array(
                        'label'   => 'Cloner',
                        'icon'    => 'copy',
                        'onclick' => $this->getJsActionOnclick('duplicate', array(
                            'datep' => date('Y-m-d')
                                ), array(
                            'form_name' => 'duplicate_propal'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->propal->dir_output;
    }

    // Rendus HTML - overrides BimpObject

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . date('d / m / Y', $this->dol_object->datec) . '</strong>';

            $user->fetch((int) $this->dol_object->user_author_id);
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->dol_object->user_valid_id) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le <strong>' . date('d / m / Y', $this->dol_object->datev) . '</strong>';
                $user->fetch((int) $this->dol_object->user_valid_id);
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }

            if ($status >= 2 && (int) $this->dol_object->user_close_id) {
                $date_cloture = $this->db->getValue('propal', 'date_cloture', '`rowid` = ' . (int) $this->id);
                if (!is_null($date_cloture) && $date_cloture) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Fermée le <strong>' . date('d / m / Y', BimpTools::getDateForDolDate($date_cloture)) . '</strong>';
                    $user->fetch((int) $this->dol_object->user_close_id);
                    $html .= ' par ' . $user->getNomUrl(1);
                    $html .= '</div>';
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Client: </strong>';
                $html .= BimpObject::getInstanceNomUrlWithIcons($client);
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '';

        $html .= '<div class="buttonsContainer">';

        $pdf_dir = $this->getDirOutput();
        $ref = dol_sanitizeFileName($this->getRef());
        $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
        if (file_exists($pdf_file)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
//            $onclick = 'window.open(\'' . $url . '\');';

            $html .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => $ref . '.pdf',
                        'icon_before' => 'fas_file-pdf',
                        'attr'        => array(
                            'href'   => $url,
                            'target' => '_blanck',
                        )
                            ), "a");
        }

        $html .= BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => 'Ancienne version',
                    'icon_before' => 'fa_file',
                    'attr'        => array(
                        'href' => "../comm/propal/card.php?id=" . $this->id
                    )
                        ), "a");

        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function review($check = true, &$errors = array(), &$warnings = array())
    {
        $errors = array();
        if ($check) {
            if (!$this->isActionAllowed('review', $errors)) {
                return 0;
            }
        }

        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

        $revision = new BimpRevisionPropal($this->dol_object);
        $new_id_propal = (int) $revision->reviserPropal(false, true, $this->getData('model_pdf'), $errors);

        if (!$new_id_propal) {
            return 0;
        }

        $newPropal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $new_id_propal);

        if (!BimpObject::objectLoaded($newPropal)) {
            $errors[] = 'Echec de la création de la révision pour une raison inconnue';
            return 0;
        }

        $newPropal->set('remise_globale_label', $this->getData('remise_globale_label'));
        $newPropal->set('zone_vente', $this->getData('zone_vente'));
        $pw = array();
        $newPropal->update($pw, true);

        $lines = $this->getLines();

        $totHt = (float) $this->dol_object->total_ht;

        // Ajout de la ligne "Proposition commerciale révisée" dans la propale actuelle: 
        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
        $line->desc = 'Proposition commerciale révisée';
        $line->tva_tx = (($this->dol_object->total_ttc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
        $line->pu_ht = -$totHt;
        $line->pa_ht = -$totHt;
        $line->qty = 1;

        $line->validateArray(array(
            'id_obj'    => (int) $this->id,
            'type'      => ObjectLine::LINE_FREE,
            'deletable' => 0,
            'editable'  => 0,
            'remisable' => 0
        ));

        $line_warnings = array();
        $line_errors = $line->create($line_warnings, true);

        if (count($line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne "révision"');
        }

        if (count($line_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la création de la ligne "révision"');
        }

        global $user, $langs;
        $this->addNote('Proposition commerciale mise en révision le ' . date('d / m / Y') . ' par ' . $user->getFullName($langs) . "\n" . 'Révision: ' . $newPropal->getRef());
        $newPropal->addNote('Révision de la proposition: ' . $this->getRef());

        // Copie des lignes: 
        $newLines = array();
        foreach ($lines as $line) {
            $remises = $line->getRemises();
            $newLine = clone $line;

            $newLine->id = null;
            $newLine->set('id', 0);
            $newLine->set('id_line', 0);
            $newLine->set('id_parent_line', 0);
            $newLine->remise = 0;
            $newLine->setIdParent($new_id_propal);

            $newLine_warnings = array();
            $newLine_errors = $newLine->create($newLine_warnings, true);

            if (count($newLine_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($newLine_warnings, 'Erreurs suite à la copie de la ligne n° ' . $line->getData('position'));
            }

            if (count($newLine_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($newLine_errors, 'Echec de la copie de la ligne n°' . $line->getData('position'));
            } else {
                $newLines[(int) $line->id] = $newLine->id;
                if (count($remises)) {
                    $i = 0;
                    foreach ($remises as $remise) {
                        $i++;
                        $newRemise = clone $remise;
                        $newRemise->id = null;
                        $newRemise->set('id', 0);
                        $newRemise->set('id_object_line', $newLine->id);

                        $newRemise_warnings = array();
                        $newRemise_errors = $newRemise->create($newRemise_warnings, true);

                        if (count($newRemise_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($newRemise_errors, 'Echec de la création ' . $newRemise->getLabel('of_the') . ' n°' . $i);
                        }
                        if (count($newRemise_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($newRemise_warnings, 'Erreurs suite à la création ' . $newRemise->getLabel('of_the') . ' n°' . $i);
                        }
                    }
                }
            }
        }

        // Attribution des lignes parentes: 
        foreach ($lines as $line) {
            $id_parent_line = (int) $line->getData('id_parent_line');

            if ($id_parent_line) {
                if (isset($newLines[(int) $line->id]) && (int) $newLines[(int) $line->id] && isset($newLines[$id_parent_line]) && (int) $newLines[$id_parent_line]) {
                    $line_instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name . 'Line', (int) $newLines[(int) $line->id]);
                    if (BimpObject::objectLoaded($line_instance)) {
                        $line_instance->updateField('id_parent_line', (int) $newLines[(int) $id_parent_line]);
                    }
                }
            }
        }

        // Ajout de la remise globale: 
        if ((float) $this->getData('remise_globale')) {
            $newPropal->setRemiseGlobalePercent((float) $this->getData('remise_globale'));
        }

        return $new_id_propal;
    }

    // Traitements - overrides BimpComm: 

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {

        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!$this->fetch($this->id)) {
            return array(BimpTools::ucfirst($this->getLabel('this')) . ' est invalide. Copie impossible');
        }

        $date_diff = 0;
        if (isset($new_data['date_livraison']) && $new_data['date_livraison'] !== $this->getData('date_livraison')) {
            $date_diff = (int) BimpTools::getDateForDolDate($new_data['date_livraison']) - (BimpTools::getDateForDolDate($this->getData('date_livraison')));
        }

        $now = date('Y-m-d H:i:s');
        $new_data['datep'] = $now;
        $new_data['datec'] = $now;
        $fin_validite = BimpTools::getDateForDolDate($now) + ($this->dol_object->duree_validite * 24 * 3600);
        $new_data['fin_validite'] = BimpTools::getDateFromDolDate($fin_validite);

        $errors = parent::duplicate($new_data, $warnings, $force_create);

        if ($date_diff) {
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $update = false;
                if (isset($line->date_from) && (string) $line->date_from) {
                    $new_date_from = (BimpTools::getDateForDolDate($line->date_from) + $date_diff);
                    $line->date_from = BimpTools::getDateFromDolDate($new_date_from);
                    $update = true;
                }

                if (isset($line->date_to) && (string) $line->date_to) {
                    $new_date_to = (BimpTools::getDateForDolDate($line->date_to) + $date_diff);
                    $line->date_to = BimpTools::getDateFromDolDate($new_date_to);
                    $update = true;
                }

                if ($update) {
                    $line_warnings = array();
                    $line->update($line_warnings, true);
                }
            }
        }

        return $errors;
    }

    // Actions:

    public function actionClose($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Cloture de la proposition commerciale effectuée avec succès';

        if (!isset($data['new_status']) || !(int) $data['new_status']) {
            $errors[] = 'Nouveau statut non spécifié';
        } else {
            global $user;
            $note = isset($data['note']) ? $data['note'] : '';
            if ($this->dol_object->cloture($user, (int) $data['new_status'], $note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la cloture de la proposition commerciale');
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        global $user;
        if ($this->dol_object->reopen($user, Propal::STATUS_VALIDATED) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_the'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionClassifyBilled($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel() . ' classé' . ($this->isLabelFemale() ? 'e' : '') . ' facturé' . ($this->isLabelFemale() ? 'e' : '') . ' avec succès');

        global $conf, $user;
        $factures = $this->dol_object->getInvoiceArrayList();
        if ((is_array($factures) && count($factures)) ||
                empty($conf->global->WORKFLOW_PROPAL_NEED_INVOICE_TO_BE_CLASSIFIED_BILLED)) {
            if ($this->dol_object->cloture($user, 4, '') < 0) {
                $errors[] = BimpTools::getErrorsFromDolObject($this->dol_object);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGeneratePdf($data, &$success)
    {
        $wanings = array();
        if ((int) $this->id && $data['model'] == "bimpdevissav") {
            if (!(int) $this->getIdSav()) {
                $data['model'] = "bimpdevis";
                $wanings[] = 'Aucun SAV associé à cette propale trouvé';
            }
        }

        return parent::actionGeneratePdf($data, $success, array(), $wanings);
    }

    public function actionReview($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Proposition commerciale révisée avec succès';

        $new_id_propal = $this->review(false, $errors, $warnings);

        $url = DOL_URL_ROOT . '/bimpcommercial/index.php?fc=propal&id=' . $new_id_propal;

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'window.location = \'' . $url . '\''
        );
    }

    // Overrides BimpObject: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (BimpTools::isSubmit('duree_validite')) {
            $this->dol_object->duree_validite = (int) BimpTools::getValue('duree_validite');
        }

        return $errors;
    }

    protected function updateDolObject(&$errors)
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        global $user;

        $bimpObjectFields = array();
        $this->hydrateDolObject($bimpObjectFields);

        if (method_exists($this, 'beforeUpdateDolObject')) {
            $this->beforeUpdateDolObject();
        }

        $result = $this->dol_object->update($user);
        if ($result <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la propale');
            return $errors;
        }

        // Mise à jour des champs Bimp_Propal:
        foreach ($bimpObjectFields as $field => $value) {
            $field_errors = $this->updateField($field, $value);
            if (count($field_errors)) {
                $errors[] = BimpTools::getMsgFromArray($field_errors, 'Echec de la mise à jour du champ "' . $field . '"');
            }
        }

        // Mise à jour des extra_fields: 
        if ($this->dol_object->insertExtraFields('', $user) <= 0) {
            $errors[] = 'Echec de la mise à jour des champs supplémentaires';
        }

        $this->dol_object->fetch($this->id);

        // Ref. client
        if ((string) $this->getData('ref_client') !== (string) $this->dol_object->ref_client) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_ref_client($user, $this->getData('ref_client')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la référence client');
            }
        }

        // Origine:
        if ((int) $this->getData('fk_input_reason') !== (int) $this->dol_object->demand_reason_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_demand_reason($user, (int) $this->getData('fk_input_reason')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de l\'origine');
            }
        }

        // Conditions de réglement: 
        if ((int) $this->getData('fk_cond_reglement') !== (int) $this->dol_object->cond_reglement_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->setPaymentTerms((int) $this->getData('fk_cond_reglement')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour des conditions de réglement');
            }
        }

        // Mode de réglement: 
        if ((int) $this->getData('fk_mode_reglement') !== (int) $this->dol_object->mode_reglement_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->setPaymentMethods((int) $this->getData('fk_mode_reglement')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du mode de réglement');
            }
        }

        // Note privée: 
        if ((string) $this->getData('note_private') !== (string) $this->dol_object->note_private) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->update_note((string) $this->getData('note_private'), '_private') <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la note privée');
            }
        }

        // Note publique: 
        if ((string) $this->getData('note_public') !== (string) $this->dol_object->note_public) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->update_note((string) $this->getData('note_public'), '_public') <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la note publique');
            }
        }

        // Date: 
        if ((string) $this->getData('datep')) {
            $date = BimpTools::getDateForDolDate($this->getData('datep'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->date) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date');
            }
        }

        // Date fin validité: 
        if ((string) $this->getData('fin_validite')) {
            $date = BimpTools::getDateForDolDate($this->getData('fin_validite'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->fin_validite) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_echeance($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de fin de validité');
            }
        }

        // Date livraison: 
        if ((string) $this->getData('date_livraison')) {
            $date = BimpTools::getDateForDolDate($this->getData('date_livraison'));
        } else {
            $date = '';
        }
        if ($date !== $this->dol_object->date_livraison) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date_livraison($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de livraison');
            }
        }

        // Délai de livraison: 
        if ((int) $this->getData('fk_availability') !== (int) $this->dol_object->availability_id) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_availability($user, (int) $this->getData('fk_availability')) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du délai de livraison');
            }
        }


        if (!count($errors)) {
            return 1;
        }

        return 0;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $id_user = (int) BimpTools::getValue('id_user_commercial', 0);
            if ($id_user) {
                if ($this->dol_object->add_contact($id_user, 'SALESREPSIGN', 'internal') <= 0) {
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'enregistrement du commercial signataire');
                }
            }
        }

        return $errors;
    }
}
