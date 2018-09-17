<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_Propal extends BimpComm
{

    public static $comm_type = 'propal';
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Signée (A facturer)', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Non signée (fermée)', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        4 => array('label' => 'Facturée (fermée)', 'icon' => 'check', 'classes' => array('success')),
    );

    // Getters: 

    public function getIdSav()
    {
        if ($this->isLoaded()) {
            return (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $this->id);
        }

        return 0;
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

    public function getListFilters()
    {
        return array();
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $langs->load('propal');

        $buttons = array();

        if ($this->isLoaded()) {

            $pdf_dir = $this->getDirOutput();
            $ref = dol_sanitizeFileName($this->getRef());
            $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
            if (file_exists($pdf_file)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$comm_type . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
                $onclick = 'window.open(\'' . $url . '\');';
                $buttons[] = array(
                    'label'   => "Voir " . $ref . '.pdf',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $onclick
                );
            }

            $buttons[] = array(
                'label'   => 'Générer le PDF',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );

            $status = $this->getData('fk_statut');

            if (!is_null($status)) {
                $status = (int) $status;
                $soc = $this->getChildObject('client');

                // Valider:
                if ($this->isActionAllowed('validate')) {
                    if ($this->canSetAction('validate')) {
                        $ref = substr($this->getRef(), 1, 4);
                        if ($ref == 'PROV') {
                            $numref = $this->dol_object->getNextNumRef($soc->dol_object);
                        } else {
                            $numref = $ref;
                        }

                        $text = $langs->trans('ConfirmValidateProp', $numref);
                        if (!empty($conf->notification->enabled)) {
                            if (!class_exists('Notify')) {
                                require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
                            }
                            $notify = new Notify($this->db->db);
                            $text .= "\n";
                            $text .= $notify->confirmMessage('PROPAL_VALIDATE', (int) $this->getData('fk_soc'), $this->dol_object);
                        }
                        $buttons[] = array(
                            'label'   => 'Valider',
                            'icon'    => 'check',
                            'onclick' => $this->getJsActionOnclick('validate', array('new_ref' => $numref), array(
                                'confirm_msg' => strip_tags($text)
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
                if ($this->isActionAllowed('modify')) {
                    if ($this->canSetAction('modify')) {
                        $buttons[] = array(
                            'label'   => 'Modifier',
                            'icon'    => 'undo',
                            'onclick' => $this->getJsActionOnclick('modify', array())
                        );
                    } else {
                        $buttons[] = array(
                            'label'    => 'Modifier',
                            'icon'     => 'undo',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => 'Vous n\'avez pas la permission de modifier cette proposition commerciale'
                        );
                    }
                }

                // Accepter / Refuser
                if ($this->isActionAllowed('close')) {
                    if ($this->canSetAction('close')) {
                        $buttons[] = array(
                            'label'   => 'Fermer',
                            'icon'    => 'times',
                            'onclick' => $this->getJsActionOnclick('close', array(), array(
                                'form_name' => 'close'
                            ))
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
                if ($this->isActionAllowed('sendMail') && $this->canSetAction('sendMail')) {
//                    comm/propal/card.php?id=123513&action=presend&mode=init#formmailbeforetitle
//                    $onclick = 'bimpModal.loadAjaxContent($(this), \'loadMailForm\', {id: ' . $this->id . '}, \'Envoyer par email\')';
                    $url = DOL_URL_ROOT . '/comm/propal/card.php?id=' . $this->id . '&action=presend&mode=init#formmailbeforetitle';
                    $onclick = 'window.location = \'' . $url . '\'';
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
                if ($this->canCreate()) {
                    $buttons[] = array(
                        'label'   => 'Cloner',
                        'icon'    => 'copy',
                        'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
                            'form_name'   => 'duplicate_propal',
                            'confirm_msg' => 'Etes-vous sûr de vouloir cloner la proposition commerciale ' . $this->getRef()
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

    public function getCommercialSearchFilters(&$filters, $value)
    {
        $filters['tc.element'] = 'propal';
        $filters['tc.source'] = 'internal';
        $filters['tc.code'] = 'SALESREPSIGN';
        $filters['ec.fk_socpeople'] = (int) $value;
    }

    // Affichages: 

    public function displayCommercial()
    {
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
            if (isset($contacts[0]) && $contacts[0]) {
                BimpTools::loadDolClass('contact');
                $user = new User($this->db->db);
                if ($user->fetch((int) $contacts[0]) > 0) {
                    return $user->getNomUrl(1) . BimpRender::renderObjectIcons($user);
                }
            }
        }

        return '';
    }

    // Rendus HTML: 

    public function renderMailForm()
    {
        return BimpRender::renderAlerts('L\'envoi par email est désactivé pour le moment', 'warning');

//        if (!$this->isLoaded()) {
//            return BimpRender::renderAlerts('ID ' . $this->getLabel('of_the') . ' absent');
//        }
//
//        global $conf, $langs, $user;
//        $errors = array();
//
//        $object = $this->dol_object;
//
//        $object->fetch_projet();
//
//        $ref = dol_sanitizeFileName($object->ref);
//
//        include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
//
//        $fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/') . '[^\-]+');
//
//        $file = $fileparams['fullname'];
//
//        // Define output language
//        $outputlangs = $langs;
//        $newlang = '';
//        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id']))
//            $newlang = $_REQUEST['lang_id'];
//        if ($conf->global->MAIN_MULTILANGS && empty($newlang))
//            $newlang = $object->thirdparty->default_lang;
//
//        if (!empty($newlang)) {
//            $outputlangs = new Translate('', $conf);
//            $outputlangs->setDefaultLang($newlang);
//            $outputlangs->load('commercial');
//        }
//
//        // Build document if it not exists
//        if (!$file || !is_readable($file)) {
//            $result = $object->generateDocument($this->getModelPdf(), $outputlangs);
//            if ($result <= 0) {
//                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Echec de la génération du document PDF');
//            } else {
//                $fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/') . '[^\-]+');
//                $file = $fileparams['fullname'];
//            }
//        }
//
//        if (!count($errors)) {
//            // Create form object
//            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
//            $formmail = new FormMail($this->db->db);
//            $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
//            $formmail->fromtype = !empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user';
//
//            if ($formmail->fromtype === 'user') {
//                $formmail->fromid = $user->id;
//            }
//            $formmail->trackid = 'pro' . $object->id;
//            if (!empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2)) { // If bit 2 is set
//                include DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
//                $formmail->frommail = dolAddEmailTrackId($formmail->frommail, 'pro' . $object->id);
//            }
//            $formmail->withfrom = 1;
//            $liste = array();
//            $object->fetch_thirdparty();
//            foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value)
//                $liste [$key] = $value;
//            $formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
//            $formmail->withtocc = $liste;
//            $formmail->withtoccc = (!empty($conf->global->MAIN_EMAIL_USECCC) ? $conf->global->MAIN_EMAIL_USECCC : false);
//            if (empty($object->ref_client)) {
//                $formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__');
//            } else if (!empty($object->ref_client)) {
//                $formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__ (__REFCLIENT__)');
//            }
//            $formmail->withfile = 2;
//            $formmail->withbody = 1;
//            $formmail->withdeliveryreceipt = 1;
//            $formmail->withcancel = 1;
//
//            // Tableau des substitutions
//            $formmail->setSubstitFromObject($object);
//            $formmail->substit['__PROPREF__'] = $object->ref; // For backward compatibility
//            // Find the good contact adress
//            $custcontact = '';
//            $contactarr = array();
//            $contactarr = $object->liste_contact(- 1, 'external');
//
//            if (is_array($contactarr) && count($contactarr) > 0) {
//                foreach ($contactarr as $contact) {
//                    if ($contact ['libelle'] == $langs->trans('TypeContact_propal_external_CUSTOMER')) { // TODO Use code and not label
//                        $contactstatic = new Contact($this->db->db);
//                        $contactstatic->fetch($contact ['id']);
//                        $custcontact = $contactstatic->getFullName($langs, 1);
//                        if ($contactstatic->email != "" && !isset($_REQUEST["receiver"]))
//                            $_POST["receiver"] = array($contactstatic->id, "");
//                    }
//                }
//
//                if (!empty($custcontact)) {
//                    $formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
//                }
//            }
//
//            // Tableau des parametres complementaires
//            $formmail->param['action'] = 'send';
//            $formmail->param['models'] = 'propal_send';
//            $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
//            $formmail->param['id'] = $object->id;
//            $formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?fc=propal&id=' . $object->id;
//            // Init list of files
//            if (GETPOST("mode") == 'init') {
//                $formmail->clear_attached_files();
//                $formmail->add_attached_files($file, basename($file), dol_mimetype($file));
//            }
//
//            return $formmail->get_form();
//        }
    }

    // Rendus HTML - overrides BimpObject

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $html .= '<div class="buttonsContainer">';
        $html .= "<a class='btn btn-default' href='../comm/propal/card.php?id=" . $this->id . "'><i class='fa fa-file iconLeft'></i>Ancienne version</a>";
        $html .= '</div>';

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
        }

        return $html;
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

        if (isset($new_data['date_livraison']) && $new_data['date_livraison'] !== $this->getData('date_livraison')) {
            $this->dol_object->date_livraison = BimpTools::getDateForDolDate($new_data['date_livraison']);

            $date_diff = (int) BimpTools::getDateForDolDate($new_data['date_livraison']) - (BimpTools::getDateForDolDate($this->getData('date_livraison')));
            foreach ($this->dol_object->lines as $line) {
                if (isset($line->date_start) && (int) $line->date_start) {
                    $line->date_start = $line->date_start + $date_diff;
                }
                if (isset($line->date_end) && (int) $line->date_start) {
                    $line->date_end = $line->date_end + $date_diff;
                }
            }
        }

        return parent::duplicate($new_data, $warnings, $force_create);
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
            'success_callback' => 'window.location.reload();'
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
            'success_callback' => 'window.location.reload();'
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

        $bimpObjectFields = array();
        $this->hydrateDolObject($bimpObjectFields);

        if (method_exists($this, 'beforeUpdateDolObject')) {
            $this->beforeUpdateDolObject();
        }

        // Mise à jour des champs Bimp_Propal:
        foreach ($bimpObjectFields as $field => $value) {
            $field_errors = $this->updateField($field, $value);
            if (count($field_errors)) {
                $errors[] = BimpTools::getMsgFromArray($field_errors, 'Echec de la mise à jour du champ "' . $field . '"');
            }
        }

        // Mise à jour des extra_fields: 
        if ($this->dol_object->update_extrafields($user) <= 0) {
            $errors[] = 'Echec de la mise à jour des champs supplémentaires';
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

    // Gestion des droits - overrides BimpObject: 

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
        return $this->canCreate();
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
                return $this->canEdit();

            case 'createOrder':
                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                return $commande->canCreate();

            case 'createContract':
                if ($user->rights->contrat->creer) {
                    return 1;
                }
                return 0;

            case 'createInvoice':
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                return $facture->canCreate();
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return 0;
        }

        global $conf;
        $status = $this->getData('fk_statut');
        if (is_null($status)) {
            return 0;
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

            case 'reopen':
                if (!in_array($status, array(Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED))) {
                    $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' invalide';
                    return 0;
                }
                return 1;

            case 'sendMail':
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
        }

        return 1;
    }
}
