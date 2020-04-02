<?php

class BimpRelanceClientsLine extends BimpObject
{

    const RELANCE_ATTENTE_MAIL = 0;
    const RELANCE_ATTENTE_COURRIER = 1;
    const RELANCE_OK_MAIL = 10;
    const RELANCE_OK_COURRIER = 11;
    const RELANCE_ABANDON = 20;
    const RELANCE_ANNULEE = 21;

    public static $status_list = array(
        self::RELANCE_ATTENTE_MAIL     => array('label' => 'En attente d\'envoi par e-mail', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_ATTENTE_COURRIER => array('label' => 'En attente d\'envoi par courrier', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_OK_MAIL          => array('label' => 'Envoyée par e-mail', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_OK_COURRIER      => array('label' => 'Envoyée par courrier', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_ABANDON          => array('label' => 'Abandonnée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::RELANCE_ANNULEE          => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );

    // Droits user: 

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'sendEmail':
            case 'generatePdf':
                if (!$user->admin) {
                    return 0;
                }
                return 1;
        }
        return parent::canSetAction($action);
    }

    public function canSetStatus($status)
    {
        global $user;
        if (!$user->admin) {
            return 0;
        }
        return 1;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('generatePdf'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'generatePdf':
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'email':
            case 'date_prevue':
            case 'factures':
                if ((int) $this->getData('status') >= 10) {
                    return 0;
                }
                break;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isValidForRelance(&$errors = array())
    {
        $check = 1;

        if (!(int) $this->getData('id_client')) {
            $errors[] = 'Client absent';
            $check = 0;
        }

        return (int) $check;
    }

    public function isFactureRelancable(Bimp_Facture $facture, &$errors = array())
    {
        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'Cette facture n\'existe plus';
            return 0;
        }

        if (!(int) $facture->getData('relance_active')) {
            $errors[] = 'Les relances ont été désactivées pour cette facture';
            return 0;
        }

        $remain_to_pay = (float) $facture->getRemainToPay();
        if (!round($remain_to_pay, 2)) {
            $errors[] = 'Cettre facture a été soldée';
            return 0;
        }

        return 1;
    }

    // Getters params:

    public function getPdfFileName()
    {
        if ($this->isLoaded()) {
            return 'Relance_' . (int) $this->getData('relance_idx') . '_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getPdfFilepath()
    {
        $fileName = $this->getPdfFileName();

        if ($fileName) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFilesDir() . '/' . $fileName;
            }
        }

        return '';
    }

    public function getPdfFileUrl()
    {
        $file = $this->getPdfFilepath();
        if ($file && file_exists($file)) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFileUrl($this->getPdfFileName());
            }
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            $relance_idx = (int) $this->getData('relance_idx');

            if ($status < 10) {
                if ($status === self::RELANCE_ATTENTE_MAIL) {

                    if ($this->isActionAllowed('sendEmail') && $this->canSetAction('sendEmail')) {
                        $buttons[] = array(
                            'label'   => 'Envoyer l\'e-mail de relance',
                            'icon'    => 'fas_share',
                            'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                                'confirm_msg' => 'Veuillez confirmer l\\\'envoi de l\\\'e-mail de relance'
                            ))
                        );
                    }

                    if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL)) {
                        $buttons[] = array(
                            'label'   => 'E-mail envoyé',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_MAIL)
                        );
                    }

                    if ($this->canSetStatus(self::RELANCE_ATTENTE_COURRIER)) {
                        $buttons[] = array(
                            'label'   => 'Mettre en attente d\'envoi par courrier',
                            'icon'    => 'fas_envelope-open-text',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_COURRIER)
                        );
                    }
                } elseif ($status === self::RELANCE_ATTENTE_COURRIER) {
                    if ($this->canSetStatus(self::RELANCE_OK_COURRIER)) {
                        $buttons[] = array(
                            'label'   => 'Courrier envoyé',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_COURRIER)
                        );
                    }

                    if ($relance_idx <= 2) {
                        if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL)) {
                            $buttons[] = array(
                                'label'   => 'Mettre en attente d\'envoi par e-mail',
                                'icon'    => 'fas_at',
                                'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_MAIL)
                            );
                        }
                    }
                }

                if ($this->canSetStatus(self::RELANCE_ABANDON)) {
                    $buttons[] = array(
                        'label'   => 'Abandonner',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ABANDON)
                    );
                }

                if ($this->isActionAllowed('generatePdf') && $this->canSetAction('generatePdf')) {
                    $buttons[] = array(
                        'label'   => 'Fichier PDF',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => $this->getJsActionOnclick('generatePdf'),
                    );
                }
            } else {
                if ($this->canSetStatus(self::RELANCE_ATTENTE_MAIL) && $this->canSetStatus(self::RELANCE_ATTENTE_COURRIER)) {
                    $buttons[] = array(
                        'label'   => 'Remettre en attente d\'envoi',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsNewStatusOnclick(($relance_idx <= 2 ? self::RELANCE_ATTENTE_MAIL : self::RELANCE_ATTENTE_COURRIER))
                    );
                }

                $url = $this->getPdfFileUrl();
                if ($url) {
                    $buttons[] = array(
                        'label'   => 'Fichier PDF',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => 'window.open(\'' . $url . '\');'
                    );
                }
            }
        }

        return $buttons;
    }

    public function getListBulkActions()
    {
        $actions = array();

        $actions[] = array(
            'label'   => 'Courriers envoyés',
            'icon'    => 'fas_check',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_OK_COURRIER . ', {}, \'Veuillez confirmer l\\\'envoi des relances sélectionnées\')'
        );

        $actions[] = array(
            'label'   => 'Abandonner',
            'icon'    => 'fas_times',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ABANDON . ', {}, \'Veuillez confirmer l\\\'abandon des relances sélectionnées\')'
        );

        $actions[] = array(
            'label'   => 'Remettre en attente',
            'icon'    => 'fas_undo',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ATTENTE_COURRIER . ', {}, \'Veuillez confirmer la mise en attente d\\\'envoi des relances sélectionnées\')'
        );

        return $actions;
    }

    // Getters données: 

    public function getRelanceLineLabel()
    {
        $client = $this->getData('client');
        $contact = $this->getChildObject('contact');

        $label = 'Relance n°' . (int) $this->getData('relance_idx');

        if (BimpObject::objectLoaded($client)) {
            $label .= ' - client: ' . $client->getRef() . ' ' . $client->getName();
        }

        if (BimpObject::objectLoaded($contact)) {
            $label .= ' - contact: ' . $contact->getName();
        }

        return $label;
    }

    // Getters Array: 

    public function getAvailableFacturesArray()
    {
        $facs = array();
        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_fac) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    $facs[$id_fac] = $fac->getRef();
                }
            }
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $clients_factures = $client->getFacturesToRelanceByClients(true, null, array(), array((int) $this->getData('relance_idx')));

            foreach ($clients_factures as $id_client => $client_data) {
                if ((int) $id_client !== (int) $client->id) {
                    continue;
                }

                foreach ($client_data['relances'] as $relance_idx => $factures) {
                    if ((int) $relance_idx !== (int) $this->getData('relance_idx')) {
                        continue;
                    }

                    foreach ($factures as $id_fac => $fac_data) {
                        if (array_key_exists((int) $id_fac, $facs)) {
                            continue;
                        }

                        if ((int) $fac_data['id_cur_relance']) {
                            continue;
                        }

                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                        if (BimpObject::objectLoaded($fac)) {
                            $id_contact = (int) $fac->getIdContactForRelance((int) $relance_idx);

                            if ($id_contact !== (int) $this->getData('id_contact')) {
                                continue;
                            }

                            $facs[(int) $id_fac] = $fac->getRef();
                        }
                    }
                }
            }
        }

        return $facs;
    }

    // Affichages: 

    public function displayFactures()
    {
        $html = '';

        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_fac) {
                $html .= ($html ? '<br/>' : '');
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    $html .= $fac->getLink();
                } else {
                    $html .= '<span class="danger">La facture d\'ID ' . $id_fac . ' n\'existe plus</span>';
                }
            }
        }

        return $html;
    }

    // Traitements: 

    public function generatePdf(&$errors = array(), &$warnings = array())
    {
        if (!$this->isActionAllowed('generatePdf', $errors)) {
            return null;
        }

        $relance = $this->getParentInstance();
        $factures = $this->getData('factures');
        $client = $this->getChildObject('client');
        $contact = $this->getChildObject('contact');
        $relance_idx = (int) $this->getData('relance_idx');

        if (!BimpObject::objectLoaded($relance)) {
            $errors[] = 'Relance absente';
        }

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
        } else {
            $client->isRelanceAllowed($errors);
        }

        if (!count($errors)) {
            $dir = $client->getFilesDir();

            // Création des données du PDF: 
            $pdf_data = array(
                'relance_idx'  => (int) $relance_idx,
                'factures'     => array(),
                'rows'         => array(),
                'solde'        => 0,
                'total_debit'  => 0,
                'total_credit' => 0
            );

            // Vérification et ajout des données de chaque facture: 
            foreach ($factures as $id_facture) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                if (!BimpObject::objectLoaded($fac)) {
                    $warnings[] = 'La facture #' . $id_facture . ' n\'existe plus';
                } else {
                    $fac_errors = array();
                    if (!$this->isFactureRelancable($fac, $fac_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'La facture "' . $fac->getRef() . '" n\'a pas été incluse dans la relance');
                    } else {
                        $pdf_data['factures'][] = $fac;
                        $this->hydrateRelancePdfDataFactureRows($fac, $pdf_data);
                    }
                }
            }

            if (!empty($pdf_data['factures'])) {
                // Création du PDF: 
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/RelancePaiementPDF.php';
                $pdf = new RelancePaiementPDF($this->db->db);
                $pdf->client = $client;
                $pdf->contact = $contact;
                $pdf->data = $pdf_data;

                if (!count($pdf->errors)) {
                    // Génération du PDF: 
                    $pdf->render($dir . '/' . $this->getPdfFileName(), false);
                }

                if (count($pdf->errors)) {
                    $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la génération du PDF');
                } else {
                    return $pdf;
                }
            } else {
                $warnings[] = 'Il n\'y a aucune facture a relancer. Relance annulée';
                $this->updateField('status', self::RELANCE_ANNULEE);
            }
        }

        return null;
    }

    public function hydrateRelancePdfDataFactureRows($facture, &$pdf_data)
    {
        $commandes_list = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db, array('commande'));
        $comm_refs = '';

        foreach ($commandes_list as $item) {
            $comm_ref = $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $item['id_object']);
            if ($comm_ref) {
                $comm_refs = ($comm_refs ? '<br/>' : '') . $comm_ref;
            }
        }

        $fac_total = (float) $facture->getData('total_ttc');

        // Total facture: 
        $pdf_data['rows'][] = array(
            'date'     => $facture->displayData('datef', 'default', false),
            'fac'      => $facture->getRef(),
            'comm'     => $comm_refs,
            'lib'      => 'Total ' . $facture->getLabel(),
            'debit'    => ($fac_total > 0 ? BimpTools::displayMoneyValue($fac_total, '') . ' €' : ''),
            'credit'   => ($fac_total < 0 ? BimpTools::displayMoneyValue(abs($fac_total), '') . ' €' : ''),
            'echeance' => $facture->displayData('date_lim_reglement', 'default', false)
        );

        if ($fac_total > 0) {
            $pdf_data['total_debit'] += $fac_total;
        } else {
            $pdf_data['total_credit'] += abs($fac_total);
        }

        // Ajout des avoirs utilisés:
        $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount_ttc'];

                if (!$amount) {
                    continue;
                }

                $avoir_fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                $label = '';
                if ($avoir_fac->isLoaded()) {
                    switch ((int) $avoir_fac->getData('type')) {
                        case Facture::TYPE_DEPOSIT:
                            $label = 'Acompte ' . $avoir_fac->getRef();
                            break;

                        case facture::TYPE_CREDIT_NOTE:
                            $label = 'Avoir ' . $avoir_fac->getRef();
                            break;

                        default:
                            $label = 'Trop perçu sur facture ' . $avoir_fac->getRef();
                            break;
                    }
                } else {
                    $label = ((string) $r['description'] ? $r['description'] : 'Remise');
                }

                $dt = new DateTime($r['datec']);
                $pdf_data['rows'][] = array(
                    'date'     => $dt->format('d / m / Y'),
                    'fac'      => $facture->getRef(),
                    'comm'     => '',
                    'lib'      => $label,
                    'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                    'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                    'echeance' => ''
                );

                if ($amount < 0) {
                    $pdf_data['total_debit'] += abs($amount);
                } else {
                    $pdf_data['total_credit'] += $amount;
                }
            }
        }

        // Ajout des paiements de la facture: 
        $rows = $this->db->getRows('paiement_facture', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount'];

                if (!$amount) {
                    continue;
                }

                $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $r['fk_paiement']);
                if (BimpObject::objectLoaded($paiement)) {
                    $pdf_data['rows'][] = array(
                        'date'     => $paiement->displayData('datep'),
                        'fac'      => $facture->getRef(),
                        'comm'     => '',
                        'lib'      => 'Paiement ' . $paiement->displayType(),
                        'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                        'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                        'echeance' => ''
                    );
                    if ($amount < 0) {
                        $pdf_data['total_debit'] += abs($amount);
                    } else {
                        $pdf_data['total_credit'] += $amount;
                    }
                }
            }
        }
    }

    public function sendRelanceEmail(&$warnings = array(), $force_send = false)
    {
        $errors = array();

        if ((int) $this->getData('status') !== self::RELANCE_ATTENTE_MAIL) {
            $errors[] = 'Cette relance n\'est pas en attente d\'envoi par e-mail';
            return $errors;
        }

        $pdf = $this->generatePdf($errors, $warnings);

        if (!is_null($pdf) && !count($errors)) {
            if (!$force_send) {
                if ((int) $this->getData('relance_idx') > 2) {
                    $errors[] = 'Cette relance ne peut pas être envoyée par mail (' . $this->getData('relance_idx') . 'ème relance)';
                } elseif ($this->getData('date_prevue') > date('Y-m-d')) {
                    $errors[] = 'Cette relance ne peut pas être envoyée par mail (Date d\'envoi prévue ultérieure à aujourd\'hui)';
                }
            }

            $relance = $this->getParentInstance();
            if (!BimpObject::objectLoaded($relance)) {
                $errors[] = 'ID de la relance absent';
            }

            if (!count($errors)) {
                // Envoi du mail: 
                $email = $this->getData('email');

                if (!$email) {
                    $errors[] = 'Adresse e-mail absente';
                } else {
                    $client = $this->getChildObject('client');
                    $relance_idx = (int) $this->getData('relance_idx');

                    $mail_body = $pdf->content_html;
                    $mail_body = str_replace('font-size: 7px;', 'font-size: 9px;', $mail_body);
                    $mail_body = str_replace('font-size: 8px;', 'font-size: 10px;', $mail_body);
                    $mail_body = str_replace('font-size: 9px;', 'font-size: 11px;', $mail_body);
                    $mail_body = str_replace('font-size: 10px;', 'font-size: 12px;', $mail_body);

                    $subject = ($relance_idx == 1 ? 'LETTRE DE RAPPEL' : 'DEUXIEME RAPPEL');

                    $from = '';

                    $commercial = $client->getCommercial(false);

                    if (BimpObject::objectLoaded($commercial)) {
                        $from = $commercial->getData('email');
                    }

                    if (!$from) {
                        // todo: utiliser config en base. 
                        $from = 'recouvrement@bimp.fr';
                    }

                    $filePath = $this->getPdfFilepath();
                    $fileName = $this->getPdfFileName();

                    if (!mailSyn2($subject, $email, $from, $mail_body, array($filePath), array('application/pdf'), array($fileName))) {
                        // Mail KO
                        $errors[] = 'Echec de l\'envoi de la relance par e-mail';
                    } else {
                        // Mail OK
                        $this->set('status', BimpRelanceClientsLine::RELANCE_OK_MAIL);
                        $this->set('date_send', date('Y-m-d H:i:s'));

                        global $user;
                        if ((string) $relance->getData('mode') === 'man' && BimpObject::objectLoaded($user)) {
                            $this->set('id_user_send', (int) $user->id);
                        }
                        $up_errors = $this->update($w, true);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne de relance');
                        }

                        $factures = $this->getData('factures');
                        $now = date('Y-m-d');
                        foreach ($factures as $id_facture) {
                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                            if (BimpObject::objectLoaded($facture)) {
                                $facture->updateField('nb_relance', $relance_idx, null, true);
                                $facture->updateField('date_relance', $now, null, true);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function onNewStatus($new_status, $current_status, $extra_data, $warnings)
    {
        if ($this->isLoaded()) {
            if ($new_status >= 10) {
                global $user;
                $this->set('date_send', date('Y-m-d H:i:s'));
                if (BimpObject::objectLoaded($user)) {
                    $this->set('id_user_send', (int) $user->id);
                }

                if ($new_status < 20) {
                    $relance_idx = (int) $this->getData('relance_idx');
                    $now = date('Y-m-d');
                    $factures = $this->getData('factures');
                    foreach ($factures as $id_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            $facture->updateField('nb_relance', $relance_idx, null, true);
                            $facture->updateField('date_relance', $now, null, true);
                        }
                    }
                }
            } else {
                $this->set('date_send', '0000-00-00 00:00:00');
                $this->set('id_user_send', 0);
            }

            $this->update($w, true);
        }
    }

    // Actions: 

    public function actionGeneratePdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $pdf = $this->generatePdf($errors, $warnings);

        if (!is_null($pdf) && !count($errors)) {
            $url = $this->getPdfFileUrl();
            if ($url) {
                $success_callback = 'window.open(\'' . $url . '\');';
            } else {
                $errors[] = 'Echec de la génération du PDF';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSendEmail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        $errors = $this->sendRelanceEmail($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        if (!(int) $this->getData('status')) {
            if ((int) $this->getData('relance_idx') < 2) {
                $this->set('status', self::RELANCE_ATTENTE_MAIL);
            } else {
                $this->set('status', self::RELANCE_ATTENTE_COURRIER);
            }
        }

        return parent::validate();
    }
}
