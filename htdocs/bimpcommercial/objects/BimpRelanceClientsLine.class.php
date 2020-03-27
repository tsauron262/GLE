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
        self::RELANCE_ATTENTE_MAIL     => array('label' => 'En attente d\'envoi par mail', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_ATTENTE_COURRIER => array('label' => 'En attente d\'envoi par courrier', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_OK_MAIL          => array('label' => 'Envoyée par e-mail', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_OK_COURRIER      => array('label' => 'Envoyée par courrier', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_ABANDON          => array('label' => 'Relance abandonnée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::RELANCE_ANNULEE          => array('label' => 'Relance annulée (facture payée)', 'icon' => 'fas_times', 'classes' => array('danger')),
    );

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'generatePdf':

                return 1;
        }

        return parent::isActionAllowed($action, $errors);
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
    // Getters params: 
    
    public function getPdfFilepath()
    {
        $file = $this->getData('pdf_file');

        if ($file) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFilesDir() . '/' . $file;
            }
        }

        return '';
    }

    public function getPdfFileUrl()
    {
        $file = $this->getData('pdf_file');
        if ($file) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFileUrl($file);
            }
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $url = $this->getPdfFileUrl();
            if ($url) {
                $buttons[] = array(
                    'label'   => 'Fichier PDF',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => 'window.open(\'' . $url . '\');'
                );
            }

            $status = (int) $this->getData('status');
            if ($status < 10) {
                $buttons[] = array(
                    'label'   => 'Courrier envoyé',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_COURRIER)
                );
                $buttons[] = array(
                    'label'   => 'Abandonner',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ABANDON)
                );
            } else {
                $buttons[] = array(
                    'label'   => 'Remettre en attente',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_COURRIER)
                );
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
        
        $factures = $this->getData('factures');
        $client = $this->getChild();
        
        $i = 0;
        foreach ($factures as $id_facture) {
            $i++;
            $contact = null;

            if ((int) $id_contact) {
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
            }

            // Création des données du PDF: 
            $pdf_data = array(
                'relance_idx'  => (int) $relance_idx,
                'factures'     => array(),
                'rows'         => array(),
                'solde'        => 0,
                'total_debit'  => 0,
                'total_credit' => 0
            );

            foreach ($contact_factures as $fac) {
                $pdf_data['factures'][] = $fac;
                $this->hydrateRelancePdfDataFactureRows($fac, $pdf_data);
            }

            $file_name = 'Relance_' . $relance_idx . '_' . date('Y-m-d_H-i') . '_' . $i . '.pdf';
            $pdf = new RelancePaiementPDF($db);
            $pdf->client = $client;
            $pdf->contact = $contact;
            $pdf->data = $pdf_data;

            if (!count($pdf->errors)) {
                // Génération du PDF: 
                $pdf->render($dir . '/' . $file_name, false);
            }

            if (count($pdf->errors)) {
                $warnings[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la génération du PDF pour la relance n°' . $relance_idx . ' du client ' . $client->getRef() . ' - ' . $client->getName());
            } else {
                // Création de la ligne de relance: 
                $relanceLine = BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine');
                $relanceLine->validateArray(array(
                    'id_relance'  => $relance->id,
                    'id_client'   => $client->id,
                    'id_contact'  => $id_contact,
                    'relance_idx' => $relance_idx,
                    'pdf_file'    => $file_name,
                    'status'      => BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER
                ));

                if ($relance_idx > 2) {
                    $pdf_files[] = $dir . '/' . $file_name;
                } else {
                    // Envoi du mail: 
                    $email = '';
                    if (BimpObject::objectLoaded($contact)) {
                        $email = $contact->getData('email');
                    } else {
                        $email = $client->getData('email');
                    }

                    if (!$email) {
                        $msg = 'Email absent pour l\'envoi de la relance n°' . $relance_idx . ' au client "' . $client->getRef() . ' - ' . $client->getName() . '"';
                        if (BimpObject::objectLoaded($contact)) {
                            $msg .= ' (contact: ' . $contact->getName() . ')';
                        }
                        $msg .= '. Le PDF a été ajouté à la liste des PDF à envoyer par courrier';
                        $warnings[] = $msg;
                        $pdf_files[] = $dir . '/' . $file_name;
                    } else {
                        $email = str_replace(' ', '', $email);
                        $email = str_replace(';', ',', $email);

                        $relanceLine->set('email', $email);

                        $mail_body = $pdf->content_html;
                        $mail_body = str_replace('font-size: 7px;', 'font-size: 9px;', $mail_body);
                        $mail_body = str_replace('font-size: 8px;', 'font-size: 10px;', $mail_body);
                        $mail_body = str_replace('font-size: 9px;', 'font-size: 11px;', $mail_body);
                        $mail_body = str_replace('font-size: 10px;', 'font-size: 12;', $mail_body);

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

                        if (!mailSyn2($subject, $email, $from, $mail_body, array($dir . '/' . $file_name), array('application/pdf'), array($file_name))) {
                            // Mail KO
                            $msg = 'Echec de l\'envoi par email de la relance n°' . $relance_idx . ' au client "' . $client->getRef() . ' - ' . $client->getName() . '" (';
                            if (BimpObject::objectLoaded($contact)) {
                                $msg .= 'contact: ' . $contact->getName() . ', ';
                            }
                            $msg .= 'email: ' . $email . ')';
                            $msg .= '. Le PDF a été ajouté à la liste des PDF à envoyer par courrier';
                            $warnings[] = $msg;
                            $pdf_files[] = $dir . '/' . $file_name;
                        } else {
                            // Mail OK
                            $relanceLine->set('status', BimpRelanceClientsLine::RELANCE_OK_MAIL);
                            $relanceLine->set('date_send', date('Y-m-d H:i:s'));
                            if ($mode === 'man' && BimpObject::objectLoaded($user)) {
                                $relanceLine->set('id_user_send', (int) $user->id);
                            }
                        }
                    }
                }

                // Maj des données de la facture
                $relanceLine_factures = array();
                foreach ($contact_factures as $fac) {
                    $fac->updateField('nb_relance', (int) $relance_idx, null, true);
                    $fac->updateField('date_relance', $now, null, true);
                    $relanceLine_factures[] = (int) $fac->id;
                }

                // Création de la ligne de relance: 
                $relanceLine->set('factures', $relanceLine_factures);
                $rl_warnings = array();
                $rl_errors = $relanceLine->create($rl_warnings, true);
                if (count($rl_errors)) {
                    $err_label = 'Relance n°' . $relance_idx . ' - client: ' . $client->getRef() . ' ' . $client->getName();
                    if (BimpObject::objectLoaded($contact)) {
                        $err_label .= ' - contact: ' . $contact->getName();
                    }
                    $warnings[] = BimpTools::getMsgFromArray($rl_errors, 'Echec de la création de la ligne de relance (' . $err_label . ')');
                }
            }
        }
    }

    public function sendRelanceEmail()
    {
        
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
            } else {
                $this->set('date_send', '0000-00-00 00:00:00');
                $this->set('id_user_send', 0);
            }

            $this->update($w, true);
        }
    }
}
