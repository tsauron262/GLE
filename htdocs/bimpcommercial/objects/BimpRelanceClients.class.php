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
                if ((int) $user->id === 1) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    public function isActionAllowed($action, &$errors = array())
    {
//        BimpObject::
        switch ($action) {
            case 'sendAllEmails':
                if (!$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL)) {
                    $errors[] = 'Il n\'y a aucune relance en attente d\'envoi par e-mail';
                    return 0;
                }
                return 1;

            case 'allSent':
            case 'generateRemainToSendPdf':
                if (!$this->hasLinesAttente(BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL)) {
                    $errors[] = 'Il n\'y a aucune relance en attente d\'envoi par courrier';
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

            $num = (int) $this->db->getCount('relance_clients_line', $where);
            return ($num > 0 ? 1 : 0);
        }

        return 0;
    }

    // Getters params: 

    public function getFilesDir()
    {
        $date = $this->getData('date');

        if ($date) {
            $dt = new DateTime($date);
            return DOL_DATA_ROOT . '/bimpcore/relances/';
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=bimpcore&file=' . urlencode('relances/' . $file_name);
    }

    public function getRemainToSendPDFFileName()
    {
        if ($this->isLoaded()) {
            return 'relances_a_envoyer_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getRemainToSendPdfFileName()
    {
        if ($this->isLoaded()) {
            return 'Relances_a_envoyer_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $line_instance = BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine');

            $buttons[] = array(
                'label'   => 'Détail',
                'icon'    => 'fas_bars',
                'onclick' => $line_instance->getJsLoadModalList('relance', array(
                    'id_parent' => $this->id
                ))
            );

            if ($this->isActionAllowed('sendAllEmails') && $this->canSetAction('sendAllEmails')) {
                $buttons[] = array(
                    'label'   => 'Envoyer tous les e-mails en attente',
                    'icon'    => 'fas_share',
                    'onclick' => $this->getJsActionOnclick('sendAllEmails', array(), array(
                        'confirm_msg' => 'Veuillez confirmer l\\\'envoi de tous les e-mails de relance en attente'
                    ))
                );
            }

            if ($this->isActionAllowed('allSent') && $this->canSetAction('allSent')) {
                $buttons[] = array(
                    'label'   => 'Valider tous les envois par courrier',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('allSent', array(), array(
                        'confirm_msg' => 'Veuillez confirmer que tous les courriers ont été envoyés pour cette relance'
                    ))
                );
            }

            if ($this->isActionAllowed('generateRemainToSendPdf') && $this->canSetAction('generateRemainToSendPdf')) {
                $buttons[] = array(
                    'label'   => 'PDF des courriers restant à envoyés',
                    'icon'    => 'fas_file-export',
                    'onclick' => $this->getJsActionOnclick('generateRemainToSendPdf')
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

    public function sendEmails($force_send = false)
    {
        $errors = array();

        BimpObject::loadClass('bimpcore', 'BimpRelanceClientsLine');
        $lines = $this->getChildrenObjects('lines', array(
            'status' => BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL
        ));

        foreach ($lines as $line) {
            $line_errors = $line->sendRelanceEmail($w, $force_send);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, $line->getRelanceLineLabel());
            }
        }

        return $errors;
    }

    public function generateRemainToSendPdf(&$filePath = '', $display = false)
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
                    $line_errors = array();
                    $line->generatePdf($line_errors);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, $line->getRelanceLineLabel());
                    }
                }

                if (!empty($files)) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';
                    $filePath = 'Relances_a_envoyer_' . $this->id . '.pdf';
                    $pdf = new BimpConcatPdf();
                    $pdf->concatFiles($filePath, $files, ($display ? 'I' : 'F'));
                }
            }
        }

        return $errors;
    }

    // Actions: 

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
}
