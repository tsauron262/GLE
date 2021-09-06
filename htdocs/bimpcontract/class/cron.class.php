<?php 
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
 require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
 require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
    class Cron {
        		
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
        CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 3;
        CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 6;
        CONST CONTRAT_RENOUVELLEMENT_4_FOIS = 4;
        CONST CONTRAT_RENOUVELLEMENT_5_FOIS = 5;
        CONST CONTRAT_RENOUVELLEMENT_6_FOIS = 7;
        CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12;
        
        public $arrayTacite = [
            self::CONTRAT_RENOUVELLEMENT_1_FOIS, self::CONTRAT_RENOUVELLEMENT_2_FOIS, self::CONTRAT_RENOUVELLEMENT_3_FOIS,
            self::CONTRAT_RENOUVELLEMENT_4_FOIS, self::CONTRAT_RENOUVELLEMENT_5_FOIS, self::CONTRAT_RENOUVELLEMENT_6_FOIS
        ];
        
        function zu_gehen() {
            $this->autoClose();
            $this->mailJourActivation();
            $this->relanceActivationProvisoire();
            $this->relance_brouillon();
            $this->echeance_contrat();
            $this->relance_demande();
            $this->tacite();
            $this->facturation_auto();
            return "OK";
        }
        
        public function mailJourActivation() {
            $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            $list  = $contrat->getList(['statut' => self::CONTRAT_WAIT_ACTIVER]);
            $msg = "Bonjour, Voici la liste des contrats à activer:<br />";
            $to_send = false;
            foreach($list as $index => $i) {
                $contrat->fetch($i['rowid']);
                $date = New DateTime($contrat->getData('date_start'));
                $now = New DateTime();
                $tms_date = strtotime($date->format('Y-m-d'));
                $tms_now = strtotime($now->format('Y-m-d'));
                if($tms_date == $tms_now || $tms_date < $tms_now) { // PLUS AJOUTER SI LE CONTRAT  EST SIGNER AUSSI
                    // Envoyer le mail pour activation aujoursd'hui
                    $to_send = true;
                    $this->output .= $contrat->getRef() . ": Relance jour<br />";
                    $msg .= $contrat->getNomUrl() . " => date d'activation prévu: <b>" . $date->format('d/m/Y') . "</b><br />";
                } 
            }
            if($to_send) {
                $this->sendMailGroupeContrat("Contrats en attente de validation", $msg);
            }
        }
        
        public function relanceActivationProvisoire() {
            $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            $list  = $contrat->getList(['statut' => self::CONTRAT_ACTIVER_TMP]);
            foreach($list as $index => $i) {
                $contrat->fetch($i['rowid']);
                $start_prov = New DateTime($contrat->getData('date_start_provisoire'));
                $end_prov = New DateTime($contrat->getData('date_start_provisoire'));
                $end_prov->add(new DateInterval("P14D"));
                $this->output .= $contrat->getNomUrl() . "(Date: ".$start_prov->format('d/m/Y')." au ".$end_prov->format('d/m/Y').") ";
                $today = new DateTime();
                $diff = $today->diff($end_prov);
                if($diff->invert == 1) {
                    $this->output .= ": suspenssion du contrat";
                    $commercial = $contrat->getCommercialClient(true);
                    $client = BimpObject::getInstance('bimpcore',  'Bimp_Societe', $contrat->getData('fk_soc'));
                    $msg = "L'activation provisoire de votre contrat ".$contrat->getNomUrl()." pour le client ".$client->getNomUrl()." ".$client->getName().", vient d'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client";
                    mailSyn2("Contrat suspendu", $commercial->getData('email'), null, $msg);
                    $contrat->addLog("Contrat suspendu automatiquement pour cause de non signature");
                    $contrat->updateField('statut', self::CONTRAT_ACTIVER_SUP);
                } else {
                    $this->output .= $diff->d . " jours avant la suspenssion automatique";
                    if($diff->d%2 == 0 && $diff->d > 14 && $diff->d > 0) {
                        $this->output .= ": Relance par mail";
                        $commercial = $contrat->getCommercialClient(true);
                        $client = BimpObject::getInstance('bimpcore',  'Bimp_Societe', $contrat->getData('fk_soc'));
                        $msg = "Votre contrat ".$contrat->getNomUrl()." pour le client ".$client->getNomUrl()." ".$client->getName()." est activé provisoirement car il n'est pas revenu signé. Il sera automatiquement désactivé le ".$end_prov->format('d / m  / Y')." si le nécessaire n'a pas été fait.";
                        mailSyn2("Contrat en attente de signature", $commercial->getData('email'), null, $msg);
                    } elseif($diff->d > 0) {
                        $this->output .= ": Pas de relance car tout les deux jours";
                    } elseif($diff->d == 0 || $diff->d < 0){
                        // C'est la suspenssion
                        $this->output .= ": suspenssion du contrat";
                        $commercial = $contrat->getCommercialClient(true);
                        $client = BimpObject::getInstance('bimpcore',  'Bimp_Societe', $contrat->getData('fk_soc'));
                        $msg = "L'activation provisoire de votre contrat ".$contrat->getNomUrl()." pour le client ".$client->getNomUrl()." ".$client->getName().", vient d'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client";
                        mailSyn2("Contrat suspendu", $commercial->getData('email'), null, $msg);
                        $contrat->addLog("Contrat suspendu automatiquement pour cause de non signature");
                        $contrat->updateField('statut', self::CONTRAT_ACTIVER_SUP);
                    }
                }
                $this->output .= "<br />";
            }
        }

        public function relanceContratResteAPayerPeriodiquementFinish() {

            $contrats = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');

        }
        
        public function autoClose() {
            $this->output .= "START auto close<br />";
            $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            $liste = $contrat->getList(Array('statut' => self::CONTRAT_ACTIF));
            foreach($liste as $index => $infos) {
                $contrat->fetch($infos['rowid']);
                if((int) $contrat->getJourRestantReel() < 0) {
                    $contrat->closeFromCron();
                    $this->output .= $contrat->getRef() . " : " . (int) $contrat->getJourRestantReel() . ' => FERMé';
                }
            }
            $this->output .= "STOP auto close<br />";
        }
        
        public function tacite() {
            $date = date('Y-m-d');
            $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            $list = $contrats->getList(Array('statut' => self::CONTRAT_ACTIF));
            $this->output .= count($list) . " contrat(s) Actif.<br />";
            foreach($list as $index => $c) {
                $contrats->fetch($c['rowid']);
                if($contrats->isLoaded() && in_array($contrats->getData('tacite'), $this->arrayTacite)) {
                    if(strtotime($contrats->displayRealEndDate('Y-m-d')) <= strtotime($date)) {
                        if($contrats->tacite(true)) {
                            $this->output .= "Contrat N°" . $contrats->getRef() . ' [Renouvellement TACITE]';

                            $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrats->getData('fk_commercial_suivi'));
                            $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrats->getData('fk_soc'));
                            $email_commercial = $commercial->getData('email');
                            if($commercial->getdata('statut') == 0) {
                                $email_commercial = "debugerp@bimp.fr";
                            } 
                            $this->output .= $email_commercial . "<br />";
                            mailSyn2("[Contrat] - Renouvellement tacite - " . $contrats->getRef(), "facturationclients@bimp.fr, $email_commercial", null, "Bonjour, le contrat N°" . $contrats->dol_object->getNomUrl() . " a été renouvellé tacitement. Il est de nouveau facturable. <br /> Client: " . $client->getData('code_client') . " " . $client->getName());
                        }
                    }
                }
            }
        }
        
        function facturation_auto() {
            global $langs;
            $echeanciers = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
            $today = new DateTime();
            $list = $echeanciers->getList(['validate' => 1, 'next_facture_date' => ['min' => '2000-01-01', 'max' => "now()"]]);
            foreach($list as $i => $infos) {
                $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $infos['id_contrat']);
                $echeanciers->fetch($infos['id']);
                //$data = $c->renderEcheancier(false);
                
                $data = Array(
                    'factures_send' => getElementElement('contrat', 'facture', $c->id),
                    'reste_a_payer' => $c->reste_a_payer(),
                    'reste_periode' => $c->reste_periode(),
                    'periodicity' => $c->getData('periodicity')
                );
                
                $data = $echeanciers->displayEcheancier((object) $data, false);
                
                $canBilling = true;
                if($c->getData('facturation_echu')) {
                    if(strtotime($today->format('Y-m-d')) < strtotime($data['date_end'])){
                        $canBilling = false;
                        $this->output .= $c->getRef() . ': Pas de facturation car terme échu pas encore arrivé<br />';
                    } 
                }
                if($c->getData('statut') != 11) {
                    $canBilling = false;
                }

                if($echeanciers->isDejaFactured($data['date_start'], $data['date_end'])) {
                    $canBilling = false;
                }

                if($canBilling){
                    $id_facture = $echeanciers->actionCreateFacture($data);
                    if($id_facture > 0) {
                        //$f = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        $s = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $c->getData('fk_soc'));
                        $comm = BimpObject::getInstance('bimpcore', 'Bimp_User', $c->getData('fk_commercial_suivi'));
                        //$this->output .= $c->getRef() . ' : Facturation automatique ('.$f->getRef().')<br />';
                        $msg = "Une facture a été créée automatiquement. Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider.<br />";
                        $msg.= "Client : " . $s->dol_object->getNomUrl() . '<br />'; 
                        $msg.= "Contrat : " . $c->dol_object->getNomUrl() . "<br/>Commercial : ".$comm->dol_object->getFullName($langs)."<br />";
                        //$msg.= "Facture : " . $f->getRef();
                        //$this->output .= $msg;
                        mailSyn2("Facturation Contrat [".$c->getRef()."]", "facturationclients@bimp.fr", null, $msg);
                    }
                }
            }        
        }
        
        public function relance_demande() {
            $list = $this->getListContratsWithStatut(self::CONTRAT_DEMANDE);
            $relance = false;
            $message = "<h3>Liste des contrats en attentes de validation de votre part</h3>";
            foreach($list as $i => $contrat) {
                $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
                $relance = true;
                $message .= '<b>Contrat : '.$c->getNomUrl().'</b><br />';
                $message .= '<b>Logs: <br /><i>'.$c->getData('logs').'</i></b><br /><br />';
                
                
            }
            if($relance) {
                $this->output .= "Relance au groupe contrat pour les demandes de validation<br />";
                $this->sendMailGroupeContrat('Contrat en attentes de validation', $message);
            }
                

        }
        
        public function relance_brouillon() {
            
            $list = $this->getListContratsWithStatut(self::CONTRAT_BROUILLON);
            $nombre_relance = 0;         
            foreach($list as $i => $contrat) {
                $send = false;
                $datec = new DateTime($contrat->datec);
                $now = new DateTime(date('Y-m-d'));
                
                $diff = $datec->diff($now);
                
                $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
                
                if($diff->y > 0 || $diff->m > 0) {
                    $send = true;
                    $message = "Bonjour, <br /> Le contrat " . $c->getNomUrl() . " dont vous êtes le commercial est au statut BROUILLON depuis: <br /><b> ";
                    $message .= $diff->y . " année.s " . $diff->m . " mois et " . $diff->d . " jour.s</b> <br />";
                    //$this->output = $message;
                } elseif($diff->d >= $this->jours_relance_brouillon) {
                    $send = true;
                    $message = "Bonjour, <br /> Le contrat " . $contrat->ref . " dont vous êtes le commercial est au statut BROUILLON depuis: <br /><b>" . $diff->d . " jour.s</b><br />";
                    
                }

                if($this->send && $send){
                    $nombre_relance++;
                    $this->sendMailCommercial('BROUILLON - Contrat ' . $contrat->ref, $contrat->fk_commercial_suivi, $message, $c);
                }

            }
            
            if($nombre_relance > 0)
                $this->output .= $nombre_relance . " relances brouillon faites <br />";
            
        }

        public function relance_echeance_tacite() {

            $now = new DateTime();
            $contrat = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
            $list = $this->getListContratsWithStatut(self::CONTRAT_ACTIF);
            foreach($list as $index => $object) {
                $contrat->fetch($object->rowid);
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getDate('fk_soc'));
                
            }            

        }
        
        public function echeance_contrat() {
            $this->output .= "***ECHEANCE***";
            $list = $this->getListContratsWithStatut(self::CONTRAT_ACTIF);

            $now = new DateTime();
            $nombre_relance = 0;
            $nombre_pas_relance = 0;
            $not_tacite = [0,12];
            foreach($list as $i => $contrat) {
                $send = false;
                $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $c->getData('fk_soc'));
                $commercial_suivi = $c->getData('fk_commercial_suivi');
                if($c->getData('periodicity')) {
                    
                    $endDate = new DateTime($c->displayRealEndDate("Y-m-d"));
                    $diff = $now->diff($endDate);
                    if($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                        $send = true;
                        $nombre_relance++;
                        $message = "Contrat " . $c->getData('ref') . "<br />Client ".$client->dol_object->getNomUrl()." <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                        if($c->getData('relance_renouvellement') && !in_array($c->getData('tacite'), $not_tacite)){
                            $this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref') . "[".$client->getData('code_client')."]", $c->getData('fk_commercial_suivi'), $message, $c);
                        }
                            
                    } else {
                        $nombre_pas_relance++;
                    }
                } else {
                    global $db, $user;
                    
                    $bimp = new BimpDb($db);
                    $val = $bimp->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $c->id);
                    if($val) {
                        $endDate = new DateTime($val);
                            $diff = $now->diff($endDate);
                            if($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                                if($c->getData('relance_renouvellement') && !in_array($c->getData('tacite'), $not_tacite)){
                                    $this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref') . "[".$client->getData('code_client')."]", $c->getData('fk_commercial_suivi'), $message, $c);
                                }
                                    

                                $nombre_relance++;
                                $message = "Contrat " . $c->getNomUrl(). "<br />Client ".$client->dol_object->getNomUrl()." <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                        }  else {
                            $nombre_pas_relance++;
                        }
                    }
                
                
                    
                
                
            }
        }
        $this->output .= "///ECHEANCE///";
        }
        
        public function getListContratsWithStatut($statut) {
            
            $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            
            return $contrats->getList(["statut" => $statut], null, null, 'id', 'DESC', 'object');
            
        }
        public function sendMailCommercial($sujet, $id_commercial, $message, $contrat) {
            global $db;
            $bimp = new BimpDb($db);
            $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_commercial);
            $email = $commercial->getData('email');

            if($commercial->getData('statut') == 0) {
                $supp_h = BimpObject::getInstance('bimpcore', 'Bimp_User', $commercial->getData('fk_user'));
                $email = $supp_h->getData('email');
                
                if($supp_h->getData('statut') == 0) {
                    
                    // Vérifier si le commercial client est actif
                    $id_commercial_client = $bimp->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $contrat->getData('fk_soc'));
                    $commercial_client = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_commercial_client);
                    
                    if($commercial_client->getData('statut') == 1) {
                        $email = $commercial_client->getData('email');
                    } else {
                        $email = 'debugerp@bimp.fr';
                    }
                }
                
            }
            $this->output .= "Relance => " . $email . " -> " . $contrat->getData('ref') . '<br />';
            mailSyn2($sujet, $email, null, $message);
        }
        
        public function sendMailGroupeContrat($sujet, $message) {
            
            mailSyn2($sujet, 'contrats@bimp.fr', null, $message);
            
        }
        
        
       
    }