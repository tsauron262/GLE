<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class cron extends BimpCron
{

    public $from = null;
    public $jours_relance_brouillon = 5;
    public $jours_relance_echeance = 30;
    public $output = "";
    public $send = true;
    public $id_relance_for_pineri = [260, 358, 154, 111, 97, 19];
    public $id_relance_for_romain = [195];

    CONST CONTRAT_BROUILLON = 0;
    CONST CONTRAT_DEMANDE = 10;
    CONST CONTRAT_ACTIF = 11;
    CONST CONTRAT_WAIT_ACTIVER = 3;
    CONST CONTRAT_ACTIVER_TMP = 12;
    CONST CONTRAT_ACTIVER_SUP = 13;
    CONST CONTRAT_RENOUVELLEMENT_NON = 0;
    CONST CONTRAT_RENOUVELLEMENT_1_FOIS = 1;
    CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 2;
    CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 3;
    CONST CONTRAT_RENOUVELLEMENT_4_FOIS = 4;
    CONST CONTRAT_RENOUVELLEMENT_5_FOIS = 5;
    CONST CONTRAT_RENOUVELLEMENT_6_FOIS = 6;
    CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12;
    CONST CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM = 666;

    public $arrayTacite = [
        self::CONTRAT_RENOUVELLEMENT_1_FOIS, self::CONTRAT_RENOUVELLEMENT_2_FOIS, self::CONTRAT_RENOUVELLEMENT_3_FOIS,
        self::CONTRAT_RENOUVELLEMENT_4_FOIS, self::CONTRAT_RENOUVELLEMENT_5_FOIS, self::CONTRAT_RENOUVELLEMENT_6_FOIS,
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM
    ];

    public function dailyProcess()
    {
//        $this->autoClose();
//        $this->mailJourActivation();
//        $this->relanceActivationProvisoire();
//        $this->relance_brouillon();
//        $this->echeance_contrat();
//        $this->relance_echeance_tacite();
//        $this->relance_demande();
//        $this->tacite();
        $this->facturation_auto();
//        $this->notifDemainFacturation();
//        $this->relanceAvenantProvisoir();
        return 0;
    }

    public function relanceAvenantProvisoir()
    {

        $avenant = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenant');
        $list = $avenant->getList(Array('statut' => 5));
        $now = new DateTime();

        $this->output .= print_r($now, 1);

        foreach ($list as $av) {
            $avenant->fetch($av['id']);
            $contrat = $avenant->getParentInstance();
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
            $commercial = $contrat->getCommercialClient(true);
            $dateEffect = new DateTime($avenant->getData('date_activate'));
            $diff = $dateEffect->diff($now);

            if ($diff->days >= 5) {
                if ($diff->days > 15) {
                    // On supprime
                    $avenant->updateField('statut', 4);
                    $avenant->updateField('private_close_note', 'Delais de signature dépassé');
                    $subject = 'ABANDON ' . $avenant->getRefAv() . ' ' . $client->getName();
                    $msg = 'Bonjour ' . $commercial->getName();
                    $msg .= '<br />L\'avenant ' . $avenant->getRefAv() . ' a été abandonné car il n\'a pas été signé dans les 15 jours qui ont suivi son activation.';
                } else {
                    // On relance le commercial
                    $subject = 'Avenant non signé ' . $avenant->getRefAv() . ' ' . $client->getName();
                    $msg = 'Bonjour ' . $commercial->getName();
                    $reste = (15 - (int) $diff->days);
                    $msg .= '<br />L\'avenant ' . $avenant->getRefAv() . ' n\'est pas signé, il vous reste ' . $reste . ' jours avant son abandon automatique';
                }

                $msg .= '<br ><br />Contrat: ' . $contrat->getLink();
                mailSyn2($subject, $commercial->getData('email'), null, $msg);
            }

            $this->output .= '<pre>' . print_r($diff, 1) . '</pre>';
        }
    }

    public function notifDemainFacturation()
    {
        $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        $list = $contrat->getList(['statut' => self::CONTRAT_ACTIF]);

        $demain = new DateTime();
        $demain->add(new DateInterval('P1D'));

        if (count($list) > 0) {
            foreach ($list as $infos) {
                $contrat->fetch($infos['rowid']);
                $echeancier->find(['id_contrat' => $contrat->id], true);
                if ($contrat->isLoaded()) {
                    if ($contrat->facturationIsDemainCron()) {
                        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                        $commercial_suivi = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $contrat->getData('fk_commercial_suivi'));
                        $commercial_signa = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $contrat->getData('fk_commercial_signature'));
                        $sujet = 'Rappel facturation contrat ' . $contrat->getRef() . ' - ' . $client->getData('code_client') . ' ' . $client->getName();
                        $message = 'Bonjour,<br />Pour rappel, le contrat numéro ' . $contrat->getNomUrl() . ' doit être facturé le ' . $demain->format('d/m/Y') . '.<br />'
                                . '<u>Informations</u><br />'
                                . 'Référence du contrat: ' . $contrat->getNomUrl() . '<br />Client: ' . $client->getNomUrl() . ' ' . $client->getName() . '<br />'
                                . 'Commercial suivi de contrat: ' . $commercial_suivi->getName() . '<br />Commercial signataire du contrat: ' . $commercial_signa->getName();
                        $mustSend = ($echeancier->isLoaded() && $echeancier->getData('validate')) ? false : true;
                        if (($mustSend)) {
                            mailSyn2($sujet, 'facturationclients@bimp.fr', null, $message);
                            $this->output .= $contrat->getRef() . ' => FAIT: RELANCE FACTURATION DEMAIN';
                        }
                    }
                }
            }
        }
    }

    public function mailJourActivation()
    {
        $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        $list = $contrat->getList(['statut' => self::CONTRAT_WAIT_ACTIVER]);
        $msg = "Bonjour, Voici la liste des contrats à activer:<br />";
        $to_send = false;
        foreach ($list as $index => $i) {
            $contrat->fetch($i['rowid']);
            $date = New DateTime($contrat->getData('date_start'));
            $now = New DateTime();
            $tms_date = strtotime($date->format('Y-m-d'));
            $tms_now = strtotime($now->format('Y-m-d'));
            if ($tms_date == $tms_now || $tms_date < $tms_now) { // PLUS AJOUTER SI LE CONTRAT  EST SIGNER AUSSI
                // Envoyer le mail pour activation aujoursd'hui
                $to_send = true;
                $this->output .= $contrat->getRef() . ": Relance jour<br />";
                $msg .= $contrat->getNomUrl() . " => date d'activation prévu: <b>" . $date->format('d/m/Y') . "</b><br />";
                BimpObject::loadClass('bimpcore', 'BimpNote');
                $contrat->addNote("Contrat en attente de validation" . $contrat->getNomUrl() . " => date d'activation prévu: <b>" . $date->format('d/m/Y'),
                                                                                                                                                  BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                                                                                                                  BimpNote::BN_DEST_GROUP, BimpCore::getUserGroupId('contrat'));
            }
        }
        if ($to_send) {
            $this->sendMailGroupeContrat("Contrats en attente de validation", $msg);
        }
    }

    public function relanceActivationProvisoire()
    {
        $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        $list = $contrat->getList(['statut' => self::CONTRAT_ACTIVER_TMP]);
        foreach ($list as $index => $i) {
            $contrat->fetch($i['rowid']);
            $start_prov = New DateTime($contrat->getData('date_start_provisoire'));
            $end_prov = New DateTime($contrat->getData('date_start_provisoire'));
            $end_prov->add(new DateInterval("P14D"));
            $this->output .= $contrat->getNomUrl() . "(Date: " . $start_prov->format('d/m/Y') . " au " . $end_prov->format('d/m/Y') . ") ";
            $today = new DateTime();
            $diff = $today->diff($end_prov);
            if ($diff->invert == 1) {
                $this->output .= ": suspenssion du contrat";
                $commercial = $contrat->getCommercialClient(true);
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                $msg = "L'activation provisoire de votre contrat " . $contrat->getNomUrl() . " pour le client " . $client->getNomUrl() . " " . $client->getName() . ", vient d'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client";
                mailSyn2("Contrat suspendu", $commercial->getData('email'), null, $msg);
                $contrat->addLog("Contrat suspendu automatiquement pour cause de non signature");
                $contrat->updateField('statut', self::CONTRAT_ACTIVER_SUP);
            } else {
                $this->output .= $diff->d . " jours avant la suspenssion automatique";
                if ($diff->d % 2 == 0 && $diff->d > 14 && $diff->d > 0) {
                    $this->output .= ": Relance par mail";
                    $commercial = $contrat->getCommercialClient(true);
                    $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                    $msg = "Votre contrat " . $contrat->getNomUrl() . " pour le client " . $client->getNomUrl() . " " . $client->getName() . " est activé provisoirement car il n'est pas revenu signé. Il sera automatiquement désactivé le " . $end_prov->format('d / m  / Y') . " si le nécessaire n'a pas été fait.";
                    mailSyn2("Contrat en attente de signature", $commercial->getData('email'), null, $msg);
                } elseif ($diff->d > 0) {
                    $this->output .= ": Pas de relance car tout les deux jours";
                } elseif ($diff->d == 0 || $diff->d < 0) {
                    // C'est la suspenssion
                    $this->output .= ": suspenssion du contrat";
                    $commercial = $contrat->getCommercialClient(true);
                    $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                    $msg = "L'activation provisoire de votre contrat " . $contrat->getNomUrl() . " pour le client " . $client->getNomUrl() . " " . $client->getName() . ", vient d'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client";
                    mailSyn2("Contrat suspendu", $commercial->getData('email'), null, $msg);
                    $contrat->addLog("Contrat suspendu automatiquement pour cause de non signature");
                    $contrat->updateField('statut', self::CONTRAT_ACTIVER_SUP);
                }
            }
            $this->output .= "<br />";
        }
    }

    public function relanceContratResteAPayerPeriodiquementFinish()
    {
        $contrats = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
    }

    public function autoClose()
    {
        $this->output .= "START auto close<br />";
        $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        $liste = $contrat->getList(Array('statut' => self::CONTRAT_ACTIF));
        foreach ($liste as $index => $infos) {
            $contrat->fetch($infos['rowid']);
            if ((int) $contrat->getJourRestantReel() <= 0 && ($contrat->getData('tacite') == 0 || $contrat->getData('tacite') == 12)) {
                $contrat->closeFromCron();
                $this->output .= $contrat->getRef() . " : " . (int) $contrat->getJourRestantReel() . ' => FERMé<br/>';
            }
        }
        $this->output .= "STOP auto close<br />";
    }

    public function tacite()
    {
        $date = date('Y-m-d');
        $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        $list = $contrats->getList(Array('statut' => self::CONTRAT_ACTIF));
        $this->output .= count($list) . " contrat(s) Actif.<br />";
        foreach ($list as $index => $c) {
            $contrats->fetch($c['rowid']);
//                $this->output .= '<br/>'.$contrats->getLink();
            if ($contrats->isLoaded()) {
                if (in_array($contrats->getData('tacite'), $this->arrayTacite)) {
                    if ((strtotime($contrats->displayRealEndDate('Y-m-d')) <= strtotime($date)) && !$contrats->getData('anticipate_close_note')) {
                        if ($contrats->tacite(true)) {
                            $this->output .= "Contrat N°" . $contrats->getRef() . ' [Renouvellement TACITE]';

                            $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrats->getData('fk_commercial_suivi'));
                            $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrats->getData('fk_soc'));
                            $email_commercial = $commercial->getData('email');
                            if ($commercial->getData('statut') == 0) {
                                $email_commercial = "debugerp@bimp.fr";
                            }
                            $this->output .= $email_commercial . "<br />";
                            mailSyn2("[Contrat] - Renouvellement tacite - " . $contrats->getRef(), $email_commercial, null, "Bonjour, le contrat N°" . $contrats->dol_object->getNomUrl() . " a été renouvellé tacitement.<br /> Client: " . $client->getData('code_client') . " " . $client->getName());
                        }
//                            else
//                                $this->output .= ' ne sembla pas avoir était renouvelle correctment';
                    }
//                        else
//                            $this->output .= ' ne semble pas qualifié car date non atteins';
                }
//                    else
//                        $this->output .= ' ne semble pas qualifiée car plus de tacite ( '.$contrats->getData('tacite').')';
            }
//                else
//                    $this->output .= ' ne semble pas qualifiée car pas loaddé';
        }
    }

    public function facturation_auto()
    {
        $bdb = BimpCache::getBdb();
        $today = date('Y-m-d');

        $this->output .= '<br/><br/>--- Facturation auto --- <br/>';

        $sql = 'SELECT a.id, a.id_contrat FROM ' . MAIN_DB_PREFIX . 'bcontract_prelevement a';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'contrat c ON c.rowid = a.id_contrat';
        $sql .= ' WHERE a.validate = 1 AND a.next_facture_date > \'0000-00-00\' AND a.next_facture_date <= \'' . $today . '\'';
        $sql .= ' AND c.statut = 11';

        $rows = $bdb->executeS($sql, 'array');

//        $this->output .= 'ROWS: <pre>' . print_r($rows, 1) . '</pre>';

        if (!is_array($rows)) {
            $this->output .= 'ERR : ' . $bdb->err();
            return;
        }

        foreach ($rows as $r) {
            $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier', (int) $r['id']);

            if (!BimpObject::objectLoaded($echeancier)) {
                continue;
            }

            $contrat = $echeancier->getParentInstance();
            if (!BimpObject::objectLoaded($contrat)) {
                continue;
            }

            $this->output .= '<br/><br/> - ' . $contrat->getLink(array('syntaxe' => '<ref>')) . ' : <br/>';

            $errors = array();
            $data = $echeancier->getNextFactureData($errors);

            if (!$data['date_start'] || !$data['date_end'] || $data['date_end'] < $data['date_start']) {
                $this->output .= 'Date incorrectes';
                continue;
            }

            if ((int) $contrat->getData('facturation_echu') && $today < $data['date_end']) {
                $this->output .= $contrat->getRef() . ': Pas de facturation car terme échu non atteint';
                continue;
            }

            if ((int) $contrat->getData('statut') != 11) {
                $this->output .= $contrat->getRef() . ': contrat non actif';
                continue;
            }

            if ($echeancier->isPeriodInvoiced($data['date_start'], $data['date_end'])) {
                $this->output .= 'Déja facturé';
                continue;
            }

            $this->output .= 'Data : <pre>' . print_r($data, 1) . '</pre>';

            $s = "";
            $result = $echeancier->actionCreateFacture($data, $s);

            if (count($result['errors'])) {
                $this->output .= 'ECHEC FAC - <pre>' . print_r($result['errors'], 1) . '</pre>';
            } else {
                $id_facture = BimpTools::getArrayValueFromPath($result, 'id_facture', 0);

                $facture = null;
                if ($id_facture) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                }

                if (BimpObject::objectLoaded($facture)) {
                    $this->output .= 'Facture créée : ' . $facture->getLink() . '<br/>Succès: ' . $s;

                    $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                    $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrat->getData('fk_commercial_suivi'));

                    $msg = "Une facture a été créée automatiquement. Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider.<br />";
                    $msg .= "Client : " . $client->getLink() . '<br />';
                    $msg .= "Contrat : " . $contrat->getLink() . "<br/>";
                    $msg .= "Commercial : " . $commercial->getName() . "<br />";

                    $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote');
                    $note->set('obj_type', 'bimp_object');
                    $note->set('obj_module', 'bimpcontract');
                    $note->set('obj_name', 'BContract_contrat');
                    $note->set('id_obj', $contrat->id);
                    $note->set('type_author', $note::BN_AUTHOR_USER);
                    $note->set('type_dest', $note::BN_DEST_GROUP);
                    $note->set('fk_group_dest', BimpCore::getUserGroupId('facturation'));
                    $note->set('content', $msg);

//                $w = array();
//                $errors = $note->create($w, true);
//                if (count($errors)) {
//                    mailSyn2("Facturation Contrat [" . $contrat->getRef() . "] client " . $client->getRef() . " " . $client->getName(), "facturationclients@bimp.fr", null, $msg);
//                }

                    break;
                }
            }
        }
    }

    public function relance_demande()
    {
        $list = $this->getListContratsWithStatut(self::CONTRAT_DEMANDE);
        $relance = false;
        $message = "<h3>Liste des contrats en attentes de validation de votre part</h3>";
        foreach ($list as $i => $contrat) {
            $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
            $relance = true;
            $message .= '<b>Contrat : ' . $c->getNomUrl() . '</b><br />';
            $message .= '<b>Logs: <br /><i>' . $c->getData('logs') . '</i></b><br /><br />';
        }
        if ($relance) {
            $this->output .= "Relance au groupe contrat pour les demandes de validation<br />";
            $this->sendMailGroupeContrat('Contrat en attentes de validation', $message);
        }
    }

    public function relance_brouillon()
    {

        $list = $this->getListContratsWithStatut(self::CONTRAT_BROUILLON);
        $nombre_relance = 0;
        foreach ($list as $i => $contrat) {
            $send = false;
            $datec = new DateTime($contrat->datec);
            $now = new DateTime(date('Y-m-d'));

            $diff = $datec->diff($now);

            $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);

            if ($diff->y > 0 || $diff->m > 0) {
                $send = true;
                $message = "Bonjour, <br /> Le contrat " . $c->getNomUrl() . " dont vous êtes le commercial est au statut BROUILLON depuis: <br /><b> ";
                $message .= $diff->y . " année.s " . $diff->m . " mois et " . $diff->d . " jour.s</b> <br />";
                //$this->output = $message;
            } elseif ($diff->d >= $this->jours_relance_brouillon) {
                $send = true;
                $message = "Bonjour, <br /> Le contrat " . $contrat->ref . " dont vous êtes le commercial est au statut BROUILLON depuis: <br /><b>" . $diff->d . " jour.s</b><br />";
            }

            if ($this->send && $send) {
                $nombre_relance++;
                $this->sendMailCommercial('BROUILLON - Contrat ' . $contrat->ref, $contrat->fk_commercial_suivi, $message, $c);
            }
        }

        if ($nombre_relance > 0)
            $this->output .= $nombre_relance . " relances brouillon faites <br />";
    }

    public function relance_echeance_tacite()
    {

        // 28 jours => 21 Jours => 14 Jours => 7 Jours => Jours de renouvellement

        $filters = [
            'statut' => 11,
            'tacite' => Array(
                'in' => Array(1, 3, 6, 4, 5, 7, 666)
            )
        ];

        $nombres_jours_relance = Array(28, 21, 14, 7, 0);

        $list = BimpCache::getBimpObjectObjects("bimpcontract", "BContract_contrat", $filters);
        $this->output .= "=> RELANCE RECONDUCTION TACITE =><br />";
        $toDay = new DateTime();
        $this->output .= "Aujourd'hui: " . $toDay->format('d/m/Y') . "<br />";
        foreach ($list as $object) {
            $message = "";
            $dateContrat = new DateTime($object->displayRealEndDate("Y-m-d"));
            $diff = $toDay->diff($dateContrat);
            if ($diff->invert == 0) {
                $output = $object->getRef() . " => expire dans " . $diff->days . " jour.s (" . $dateContrat->format('d/m/Y') . ") => ";

                if (in_array($diff->days, $nombres_jours_relance)) {
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $object->getData('fk_soc'));
                    $sujet = $object->getRef() . " - Reconduction tacite - " . $client->getRef() . ' ' . $client->getName();
                    $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $object->getData('fk_commercial_suivi'));
                    if ($diff->days > 0)
                        $message = "Bonjour " . $commercial->getName() . "<br />Votre contrat N°" . $object->getNomUrl() . " pour le client "
                                . $client->getNomUrl() . "(" . $client->getName() . ") sera renouvelé tacitement dans " . $diff->days . " jour.s";
                    else
                        $message = "Bonjour, " . $commercial->getName() . '<br />Votre contrat N°' . $object->getNomUrl() . ' pour le client'
                                . $client->getNomUrl() . '(' . $client->getName() . ') a atteint sa date d\'échéance mais il est en tacite reconduction. Il sera donc renouvelé demain';

                    $bimpMail = new BimpMail($object, $sujet, $commercial->getData('email'), null, $message);
                    if ($bimpMail->send()) {
                        $output .= "<i class='fa fa-check success' ></i> " . $commercial->getData('email');
                    } else {
                        $output .= "<i class='fa fa-retweet warnings' ></i> " . $commercial->getData('email');
                    }
                } else {
                    $output .= "<i class='fa fa-times danger' ></i>";
                }

                $this->output .= $output . "<br />";
            }
        }
        $this->output .= "<= RELANCE RECONDUCTION TACITE <=<br />";
    }

    public function echeance_contrat()
    {
        $this->output .= "***ECHEANCE***<br />";
        $list = $this->getListContratsWithStatut(self::CONTRAT_ACTIF);

        $now = new DateTime();
        $nombre_relance = 0;
        $nombre_pas_relance = 0;
        $not_tacite = [0, 12];

        foreach ($list as $i => $contrat) {
            $send = false;
            $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
            $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $c->getData('fk_soc'));

            $commercial_suivi = $c->getData('fk_commercial_suivi');
            BimpObject::loadClass('bimpcore', 'Bimp_User');
            $dispo_users = Bimp_User::getAvailableUsersList(array($commercial_suivi, 'parent'));

            if (!empty($dispo_users)) {
                $commercial_suivi = (int) $dispo_users[0];
            }

            $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $commercial_suivi);
            $email_comm = BimpTools::cleanEmailsStr($commercial->getData('email'));
            if ($c->getData('periodicity')) {
                $endDate = new DateTime($c->displayRealEndDate("Y-m-d"));
                $diff = $now->diff($endDate);
                if ($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                    $send = true;
                    $nombre_relance++;
                    $message = "Contrat " . $c->getData('ref') . "<br />Client " . $client->dol_object->getNomUrl() . " <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                    if ($c->getData('relance_renouvellement') && in_array($c->getData('tacite'), $not_tacite)) {
                        //$this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref') . "[".$client->getData('code_client')."]", $c->getData('fk_commercial_suivi'), $message, $c);
                        $sujet = "Echéance contrat - " . $c->getRef() . " - " . $client->getData('code_client');
                        $bimp_mail = new BimpMail($c, $sujet, $email_comm, '', $message, '', '');
                        $bimp_mail->send();
                        $this->output .= "Mail envoyé à <b>$email_comm</b> pour le contrat <b>" . $c->getRef() . "</b><br />";
                    }
                } else {
                    $nombre_pas_relance++;
                }
            } else {
                global $db, $user;

                $bimp = new BimpDb($db);
                $val = $bimp->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $c->id);
                if ($val) {
                    $endDate = new DateTime($val);
                    $diff = $now->diff($endDate);
                    if ($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                        if ($c->getData('relance_renouvellement') && in_array($c->getData('tacite'), $not_tacite)) {
                            $message = "Contrat " . $c->getNomUrl() . "<br />Client " . $client->dol_object->getNomUrl() . " <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                            $sujet = "Echéance contrat - " . $c->getRef() . " - " . $client->getData('code_client');
                            $bimp_mail = new BimpMail($c, $sujet, $email_comm, '', $message, '', '');
                            $bimp_mail->send();
                            $this->output .= "Mail envoyé à <b>$email_comm</b> pour le contrat <b>" . $c->getRef() . "</b><br />";
                            //$this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref') . "[".$client->getData('code_client')."]", $c->getData('fk_commercial_suivi'), $message, $c);
                        }

                        $nombre_relance++;
                    } else {
                        $nombre_pas_relance++;
                    }
                }
            }
        }
        $this->output .= "///ECHEANCE///";
    }

    public function getListContratsWithStatut($statut)
    {

        $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');

        return $contrats->getList(["statut" => $statut], null, null, 'id', 'DESC', 'object');
    }

    public function sendMailCommercial($sujet, $id_commercial, $message, $contrat)
    {
        global $db;
        $bimp = new BimpDb($db);
        $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_commercial);
        $email = $commercial->getData('email');

        if ($commercial->getData('statut') == 0) {
            $supp_h = BimpObject::getInstance('bimpcore', 'Bimp_User', $commercial->getData('fk_user'));
            $email = $supp_h->getData('email');

            if ($supp_h->getData('statut') == 0) {

                // Vérifier si le commercial client est actif
                $id_commercial_client = $bimp->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $contrat->getData('fk_soc'));
                $commercial_client = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_commercial_client);

                if ($commercial_client->getData('statut') == 1) {
                    $email = $commercial_client->getData('email');
                } else {
                    $email = 'debugerp@bimp.fr';
                }
            }
        }
        $this->output .= "Relance => " . $email . " -> " . $contrat->getData('ref') . '<br />';
        mailSyn2($sujet, $email, null, $message);
    }

    public function sendMailGroupeContrat($sujet, $message)
    {
        mailSyn2($sujet, 'contrats@bimp.fr', null, $message);
    }
}
