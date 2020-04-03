<?php

class BimpRelanceClients extends BimpObject
{

    public static $modes = array(
        'auto' => array('label' => 'Automatique', 'icon' => 'fas_cogs'),
        'man'  => array('label' => 'Manuel', 'icon' => 'far_hand-point-up')
    );

    // Getters booléens: 

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'sendAllEmails':
            case 'allSent':
            case 'generateRemainToSendPdf':
            case 'abandonneAll':
                if ((int) $user->id === 1) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        BimpObject::loadClass('bimpcommercial', 'BimpRelanceClientsLine');

        switch ($action) {
            case 'sendAllEmails':
                if (!$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL)) {
                    $errors[] = 'Il n\'y a aucune relance en attente d\'envoi par e-mail';
                    return 0;
                }
                return 1;

            case 'allSent':
            case 'generateRemainToSendPdf':
                if (!$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER)) {
                    $errors[] = 'Il n\'y a aucune relance en attente d\'envoi par courrier';
                    return 0;
                }
                return 1;

            case 'abandonneAll':
                if (!$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER) && !$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL)) {
                    $errors[] = 'Il n\'y a aucune relance en attente d\'envoi';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function hasLinesAttente($status = null)
    {
        if ($this->isLoaded()) {
            $where = 'id_relance = ' . (int) $this->id;

            if (is_null($status)) {
                $where .= ' AND status < 10';
            } else {
                $where .= ' AND status = ' . (int) $status;
            }

            $num = (int) $this->db->getCount('bimp_relance_clients_line', $where);

            return ($num > 0 ? 1 : 0);
        }

        return 0;
    }

    // Getters params: 

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/bimpcore/relances/' . $this->id;
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        if ($this->isLoaded()) {
            return DOL_URL_ROOT . '/' . $page . '.php?modulepart=bimpcore&file=' . urlencode('relances/' . $this->id . '/' . $file_name);
        }

        return '';
    }

    public function getRemainToSendPDFFileName()
    {
        if ($this->isLoaded()) {
            return 'relances_a_envoyer_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $line_instance = BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine');
            $has_att_email = $this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL);
            $has_att_courrier = $this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER);

            $buttons[] = array(
                'label'   => 'Détail',
                'icon'    => 'fas_bars',
                'onclick' => $line_instance->getJsLoadModalList('relance', array(
                    'id_parent' => $this->id
                ))
            );

            if ($has_att_email && $this->canSetAction('sendAllEmails')) {
                $buttons[] = array(
                    'label'   => 'Envoyer tous les e-mails en attente',
                    'icon'    => 'fas_share',
                    'onclick' => $this->getJsActionOnclick('sendAllEmails', array(), array(
                        'confirm_msg' => 'Veuillez confirmer l\\\'envoi de tous les e-mails de relance en attente'
                    ))
                );
            }

            if ($has_att_courrier && $this->canSetAction('allSent')) {
                $buttons[] = array(
                    'label'   => 'Valider tous les envois par courrier',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('allSent', array(), array(
                        'confirm_msg' => 'Veuillez confirmer que tous les courriers ont été envoyés pour cette relance'
                    ))
                );
            }

            if ($has_att_courrier && $this->canSetAction('generateRemainToSendPdf')) {
                $buttons[] = array(
                    'label'   => 'PDF des courriers restant à envoyés',
                    'icon'    => 'fas_file-export',
                    'onclick' => $this->getJsActionOnclick('generateRemainToSendPdf')
                );
            }

            if (($has_att_courrier || $has_att_email) && $this->canSetAction('abandonneAll')) {
                $buttons[] = array(
                    'label'   => 'Tout abandonner',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('abandonneAll')
                );
            }
        }

        return $buttons;
    }

    public function getListExtraHeaderButtons()
    {
        $buttons = array();

        $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');

        if ($client->canSetAction('relancePaiements')) {
            $buttons[] = array(
                'label'       => 'Relance impayés',
                'icon_before' => 'fas_cogs',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $client->getJsActionOnclick('relancePaiements', array(), array(
                        'form_name' => 'relance_paiements',
//                        'on_form_submit' => 'function($form, extra_data) {return onRelanceClientsPaiementsFormSubmit($form, extra_data);}'
                    ))
                )
            );
        }

        return $buttons;
    }

    // Affichages: 

    public function displayStatus()
    {
        if ($this->isLoaded()) {
            $html = '';

            $lines = $this->getChildrenObjects('lines');

            $status = array();

            if (empty($lines)) {
                $html .= '<span class="danger">Aucune ligne de détail</span>';
            } else {
                foreach ($lines as $line) {
                    $s = (int) $line->getData('status');
                    if (!isset($status[$s])) {
                        $status[$s] = 0;
                    }
                    $status[$s] ++;
                }

                foreach ($status as $s => $n) {
                    $class = BimpRelanceClientsLine::$status_list[$s]['classes'][0];
                    $label = BimpRelanceClientsLine::$status_list[$s]['label'];
                    $icon = BimpRelanceClientsLine::$status_list[$s]['icon'];
                    $html .= '<div style="margin-bottom: 4px">';
                    $html .= '<span class="' . $class . '">' . BimpRender::renderIcon($icon, 'iconLeft') . $label . ': ' . $n . '</span>';
                    $html .= '</div>';
                }
            }

            return $html;
        }

        return '';
    }

    // Traitements:

    public function sendEmails($force_send = false, &$warnings = array())
    {
        $errors = array();

        BimpObject::loadClass('bimpcore', 'BimpRelanceClientsLine');
        $lines = $this->getChildrenObjects('lines', array(
            'status' => BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL
        ));

        foreach ($lines as $line) {
            $line_warnings = array();
            $line_errors = $line->sendRelanceEmail($line_warnings, $force_send);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, $line->getRelanceLineLabel());
            }
            if (count($line_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings, $line->getRelanceLineLabel());
            }
        }

        return $errors;
    }

    public function generateRemainToSendPdf(&$pdf_url = '', &$warnings = array(), $display = false)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            BimpObject::loadClass('bimpcommercial', 'BimpRelanceClientsLine');

            $lines = $this->getChildrenObjects('lines', array(
                'status' => BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER
            ));

            if (!empty($lines)) {
                $files = array();

                foreach ($lines as $line) {
                    $line_warnings = array();
                    $line_errors = array();

                    $pdf = $line->generatePdf($line_errors, $line_warnings);

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, $line->getRelanceLineLabel());
                    }
                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, $line->getRelanceLineLabel());
                    }

                    if (!is_null($pdf) && !count($errors)) {
                        $files[] = $line->getPdfFilepath();
                    }
                }

                if (!empty($files)) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';
                    $fileName = $this->getRemainToSendPDFFileName();
                    $dir = $this->getFilesDir();

                    if (!$dir) {
                        $errors[] = 'ID de la relance absent';
                    } elseif (!file_exists($dir)) {
                        $error = BimpTools::makeDirectories('bimpcore/relances/' . $this->id, DOL_DATA_ROOT);
                        if ($error) {
                            $errors[] = $error;
                        }
                    }

                    if (!count($errors)) {
                        if ($display) {
                            $filePath = $fileName;
                        } else {
                            $filePath = $dir . '/' . $fileName;
                        }

                        $pdf = new BimpConcatPdf();
                        $pdf->concatFiles($filePath, $files, ($display ? 'I' : 'F'));
                        $pdf_url = $this->getFileUrl($fileName);
                    }
                } else {
                    $errors[] = 'Il n\'y a aucune relance à envoyer par courrier';
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionSendAllEmails($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if ($this->isLoaded($errors)) {
            $mail_warnings = array();
            $errors = $this->sendEmails(false, $warnings);
            $warnings = array_merge($mail_warnings, $warnings);
        }

        if (!count($warnings)) {
            $success = 'Tous les emails ont été envoyés avec succès';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAllSent($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Opération effectuée avec succès';

        if ($this->isLoaded($errors)) {
            $lines = $this->getChildrenObjects('lines');

            foreach ($lines as $line) {
                if ((int) $line->getData('status') < 10) {
                    $line_errors = $line->setNewStatus(BimpRelanceClientsLine::RELANCE_OK_COURRIER);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne de relance d\'ID ' . $line->id);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateRemainToSendPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $pdf_url = '';
        
        if ($this->isLoaded($errors)) {
            $errors = $this->generateRemainToSendPdf($pdf_url, $warnings, false);

            if (!count($errors)) {
                if ($pdf_url) {
                    $success_callback = 'window.open(\'' . $pdf_url . '\');';
                } else {
                    $errors[] = 'Echec de l\'enregistrement du fichier';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionAbandonneAll($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Toutes les relances ont été abandonnées avec succès';

        if ($this->isLoaded($errors)) {
            $lines = $this->getChildrenObjects('lines', array(
                'status' => array(
                    'operator' => '<',
                    'value'    => 10
                )
            ));

            if (empty($lines)) {
                $errors[] = 'Il n\'y a aucune relance en attente d\'envoi';
            } else {
                foreach ($lines as $line) {
                    $line_errors = $line->setNewStatus(BimpRelanceClientsLine::RELANCE_ABANDON);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, $line->getRelanceLineLabel());
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
