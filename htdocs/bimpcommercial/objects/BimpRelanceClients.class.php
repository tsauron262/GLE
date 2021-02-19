<?php

class BimpRelanceClients extends BimpObject
{

    public static $modes = array(
        'cron' => array('label', 'Tâche planifiée', 'icon' => 'fas_clock'),
        'global' => array('label' => 'Globale', 'icon' => 'fas_users'),
        'indiv'  => array('label' => 'Individuelle', 'icon' => 'fas_user'),
        'free'   => array('label' => 'Libre', 'icon' => 'fas_pen')
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
                if ($user->admin || (int) $user->id === 1237 ||
                        $user->rights->bimpcommercial->admin_relance_global ||
                        $user->rights->bimpcommercial->admin_relance_individuelle) {
                    return 1;
                }
                return 0;

            case 'generateUnpaidFacturesFile':
                if ($user->admin || (int) $user->rights->bimpcommercial->admin_recouvrement) {
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

        if ($this->canSetAction('generateUnpaidFacturesFile')) {
            $file_name = 'Dépôt contentieux OLYS Bimp ERP MM AAAA.xls';

            $year = (int) date('Y');
            $month = ((int) date('n') - 1);
            if ($month <= 0) {
                $month = 12;
                $year -= 1;
            }

            $month = BimpTools::addZeros($month, 2);

            $file_name = 'Depot_contentieux_OLYS_Bimp_ERP_' . $month . '_' . $year;

            $date_from = $year . '-' . $month . '-01';

            $dt_to = new DateTime($date_from);
            $dt_to->add(new DateInterval('P1M'));
            $dt_to->sub(new DateInterval('P1D'));

            $date_to = $dt_to->format('Y-m-d');

            $buttons[] = array(
                'label'       => 'Fichier factures impayées',
                'icon_before' => 'fas_file-excel',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $this->getJsActionOnclick('generateUnpaidFacturesFile', array(
                        'file_name' => $file_name,
                        'date_from' => $date_from,
                        'date_to'   => $date_to
                            ), array(
                        'form_name' => 'unpaid_file',
                    ))
                )
            );
        }

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

    public function actionGenerateUnpaidFacturesFile($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $date_from = BimpTools::getArrayValueFromPath($data, 'date_from', '', $errors, 1, 'Date de début absente');
        $date_to = BimpTools::getArrayValueFromPath($data, 'date_to', '', $errors, 1, 'Date de fin absente');
        $file_name = BimpTools::getArrayValueFromPath($data, 'file_name', '', $errors, 1, 'Nom du fichier absent');

        if (!count($errors)) {
            $factures = array();

            BimpObject::loadClass('bimpcommercial', 'BimpRelanceClientsLine');
            $lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'BimpRelanceClientsLine', array(
                        'relance_idx' => 5,
                        'status'      => BimpRelanceClientsLine::RELANCE_CONTENTIEUX,
                        'date_send'   => array(
                            'min' => $date_from . ' 00:00:00',
                            'max' => $date_to . ' 23:59:59'
                        )
            ));

            foreach ($lines as $line) {
                $line_facs = $line->getData('factures');
                foreach ($line_facs as $id_fac) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                    $remain_to_pay = (float) $facture->getRemainToPay(false, true);

                    if ($remain_to_pay) {
                        $factures[] = $id_fac;
                    }
                }
            }

            $facs = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Facture', array(
                        'paiement_status'    => 5,
                        'date_irrecouvrable' => array(
                            'min' => $date_from . ' 00:00:00',
                            'max' => $date_to . ' 23:59:59'
                        )
            ));

            foreach ($facs as $id_fac) {
                if (!in_array($factures, $id_fac)) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                    $remain_to_pay = (float) $facture->getRemainToPay(false, true);

                    if ($remain_to_pay) {
                        $factures[] = $id_fac;
                    }
                }
            }

            if (empty($factures)) {
                $errors[] = 'Aucune facture impayée trouvée pour la période indiquée';
            } else {

                BimpCore::loadPhpExcel();
                $excel = new PHPExcel();
                $sheet = $excel->getActiveSheet();

                $row = 1;
                $col = 0;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Nom client');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Adresse');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Code postale');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Ville');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Pays');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'E-mail client');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Tel. client');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'N° compte client');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Code comptable client');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Réf. facture');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Montant impayé TTC');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Date relance n°1');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Date relance n°2');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Date relance n°3');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Date relance n°4');
                $col++;

                $sheet->setCellValueByColumnAndRow($col, $row, 'Statut paiement');
                $col++;

                $countries = BimpCache::getCountriesArray();

                foreach ($factures as $id_fac) {
                    $contact = null;
                    $client = null;

                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                    if (BimpObject::objectLoaded($facture)) {
                        $client = $facture->getChildObject('client');

                        if (BimpObject::objectLoaded($client)) {
                            $id_contact = (int) $client->getData('id_contact_relances');

                            if (!$id_contact) {
                                $id_contact = $facture->getIdContactForRelance(5);
                            }

                            if ($id_contact) {
                                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

                                if (!BimpObject::objectLoaded($contact)) {
                                    $warnings[] = 'Le contact #' . $id_contact . ' n\'existe plus pour la facture ' . $facture->getRef();
                                    continue;
                                }
                            }

                            $relances_data = array();

                            $where = 'factures LIKE \'%[' . $id_fac . ']%\' AND status >= 10 AND status < 20';

                            $relances_rows = $this->db->getRows('bimp_relance_clients_line', $where, null, 'array', array('relance_idx', 'date_send'), 'date_send', 'asc');
                            if (is_array($relances_rows)) {
                                foreach ($relances_rows as $rr) {
                                    if ((string) $rr['date_send']) {
                                        $dt_relance = new DateTime($rr['date_send']);
                                        $relances_data[(int) $rr['relance_idx']] = $dt_relance->format('d / m / Y');
                                    }
                                }
                            }

                            $name = $client->getName();
                            $before_address = '';
                            $address = '';
                            $zip = '';
                            $town = '';
                            $country = '';

                            if (BimpObject::objectLoaded($contact)) {
                                if ($client->isCompany()) {
                                    $before_address .= $contact->getName() . "\n";
                                }

                                if ((string) $contact->getData('address')) {
                                    $address = $contact->getData('address');
                                }

                                if ($address) {
                                    if ($contact->getData('zip')) {
                                        $zip = $contact->getData('zip');
                                    }

                                    if ($contact->getData('town')) {
                                        $town = $contact->getData('town');
                                    }

                                    if ((int) $contact->getData('fk_pays')) {
                                        $country = $contact->getData('fk_pays');
                                    }
                                }
                            }

                            if (!$address) {
                                $address = $client->getData('address');
                                $zip = $client->getData('zip');
                                $town = $client->getData('town');
                                $country = $client->getData('fk_pays');
                            }

                            $tel = '';
                            if (BimpObject::objectLoaded($contact)) {
                                $tel = $contact->getData('phone');
                                if (!$tel) {
                                    $tel = $contact->getData('phone_mobile');
                                }
                                if (!$tel) {
                                    $tel = $contact->getData('phone_perso');
                                }
                            }
                            if (!$tel) {
                                $tel = $client->getData('phone');
                            }

                            $email = '';

                            if (BimpObject::objectLoaded($contact)) {
                                $email = $contact->getData('email');
                            }

                            if (!$email) {
                                $email = $client->getData('email');
                            }

                            $row++;
                            $col = 0;

                            $sheet->setCellValueByColumnAndRow($col, $row, $name);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $before_address . $address);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $zip);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $town);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (isset($countries[(int) $country]) ? $countries[(int) $country] : 'ID GLE: ' . $country));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $email);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $tel);
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $client->getData('code_client'));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $client->getData('code_compta'));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $facture->getRef());
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (float) $facture->getRemainToPay(false, true));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (isset($relances_data[1]) ? $relances_data[1] : ''));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (isset($relances_data[2]) ? $relances_data[2] : ''));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (isset($relances_data[3]) ? $relances_data[3] : ''));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, (isset($relances_data[4]) ? $relances_data[4] : ''));
                            $col++;

                            $sheet->setCellValueByColumnAndRow($col, $row, $facture->displayData('paiement_status', 'default', false, true));
                            $col++;
                        } else {
                            if ((int) $facture->getData('fk_soc')) {
                                $warnings[] = 'Le client #' . $facture->getData('fk_soc') . ' n\'existe plus';
                            } else {
                                $warnings[] = 'Client absent pour la facture ' . $facture->getRef();
                            }
                        }
                    } else {
                        $warnings[] = 'La facture #' . $id_fac . ' n\'existe plus';
                    }
                }

                $dir = 'bimpcore/factures_impayees/' . date('Y') . '/' . date('m_d');

                if (!file_exists($dir)) {
                    $error = BimpTools::makeDirectories($dir, DOL_DATA_ROOT);

                    if ($error) {
                        $errors[] = $error;
                    }
                }

                if (!count($errors)) {
                    $file_path = DOL_DATA_ROOT . '/' . $dir . '/' . $file_name . '.xlsx';
                    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                    $writer->save($file_path);

                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode('factures_impayees/' . date('Y') . '/' . date('m_d') . '/' . $file_name . '.xlsx');
                    $success_callback = 'window.open(\'' . $url . '\')';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides: 

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_date_prevue = $this->getInitData('date_prevue');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_date_prevue) {
                $opt = BimpTools::getPostFieldValue('lines_update_option', 'none');

                $filters = null;

                switch ($opt) {
                    case 'all':
                        $filters = array();
                        break;

                    case 'unchanged':
                        $filters = array(
                            'date_prevue' => $init_date_prevue
                        );
                        break;

                    case 'changed':
                        $filters = array(
                            'date_prevue' => array(
                                'operator' => '!=',
                                'value'    => $init_date_prevue
                            )
                        );
                        break;
                }

                if (is_array($filters)) {
                    $lines = $this->getChildrenObjects('lines', $filters);

                    $date = $this->getData('date_prevue');

                    foreach ($lines as $line) {
                        $line->set('date_prevue', $date);

                        $lw = array();
                        $lerr = $line->update($lw, true);

                        if (count($lerr)) {
                            $warnings[] = BimpTools::getMsgFromArray($lerr, 'Echec de la mise à jour de la ligne de relance #' . $line->id);
                        }

                        if (count($lw)) {
                            $warnings[] = BimpTools::getMsgFromArray($lerr, 'Erreur lors la mise à jour de la ligne de relance #' . $line->id);
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
