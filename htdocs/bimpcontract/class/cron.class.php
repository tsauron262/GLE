<?php 
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
 require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
 require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
    class Cron {
        		
        public $from = "admin@bimp.fr";
        public $jours_relance_brouillon = 5;
        public $jours_relance_echeance = 30; 
        public $output = "";
        public $send = true;
        
        public $id_relance_for_pineri = [260, 358, 154, 111, 97, 19];

        CONST CONTRAT_BROUILLON = 0;
        CONST CONTRAT_DEMANDE = 10;
        CONST CONTRAT_ACTIF = 11;
        
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
            $this->relance_brouillon();
            //$this->echeance_contrat();
            $this->relance_demande();
            $this->facturation_auto();
            $this->tacite();
            return "OK";
        }
        
        public function tacite() {
            $date = date('Y-m-d');
            $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
            $list = $contrats->getList(Array('statut' => self::CONTRAT_ACTIF, 'ref' => "CT2003-001"));
            $this->output = count($list) . " contrat(s) Actif.<br />";
            foreach($list as $index => $c) {
                $contrats->fetch($c['rowid']);
                if($contrats->isLoaded() && in_array($contrats->getData('tacite'), $this->arrayTacite)) {
                    if(strtotime($contrats->displayRealEndDate('Y-m-d')) <= strtotime($date)) {
                        if($contrats->tacite(true)) {
                            $this->output .= "Contrat N°" . $contrats->getRef() . ' [Renouvellement TACITE]';
                            
                            $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrats->getData('fk_commercial_suivi'));
                            $email_commercial = $commercial->getData('email');
                            if($commercial->getdata('statut') == 0) {
                                $email_commercial = "debugerp@bimp.fr";
                            } 
                            $this->output .= $email_commercial . "<br />";
                            mailSyn2("[Contrat] - Renouvellement tacite", "facturationclients@bimp.fr, $email_commercial", "admin@bimp.fr", "Bonjour, le contrat N°" . $contrats->getRef() . " a été renouvellé tacitement. Il est de nouveau facturable.");
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
                        mailSyn2("Facturation Contrat [".$c->getRef()."]", "facturationclients@bimp.fr", 'admin@bimp.fr', $msg);
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
        
        public function echeance_contrat() {
            $list = $this->getListContratsWithStatut(self::CONTRAT_ACTIF);

            $now = new DateTime();
            $nombre_relance = 0;
            foreach($list as $i => $contrat) {
                $send = false;
                $c = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->rowid);
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $c->getData('fk_soc'));
//                
//                $format_date_ = "";
//                
//                if($c->getData('current_renouvellement') > 0) {
//                    $format_date_ = $c->displayRealEndDate("Y-m-d");
//                } elseif($c->getData('end_date_contrat')) {
//                    $format_date_ = $c->getData('end_date_contrat');
//                }
//                
//                
                if($c->getData('end_date_contrat')) {
                    $endDate = new DateTime($c->getData('end_date_contrat'));
                    $diff = $now->diff($endDate);

                    //$this->output .= print_r($diff, 1);

                    if($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                        $send = true;
                        $this->output .= $c->getData('ref') . " (Relance)<br />";
                        $message = "Contrat " . $c->getData('ref') . "<br />Client ".$client->dol_object->getNomUrl()." <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                    } elseif($diff->invert == 1) {
                        global $user;
                        $this->output .= $c->getData('ref') . " (Clos)<br />";
                        $logs = $c->getData('logs');
                        $new_logs = $logs . "<br />" . "- <strong>Le ".date('d/m/Y')." à ".date('H:m')."</strong> Cloture automatique";
                        
                        if ($c->dol_object->closeAll($user) >= 1) {
                            $echeancier = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
                            $c->updateField('logs', $new_logs);
                            $c->updateField('statut', 2);
                            $c->updateField('date_cloture', date('Y-m-d H:i:s'));
                            $c->updateField('fk_user_cloture', $user->id);
                            if($echeancier->find(['id_contrat' => $c->id])) {
                                $echeancier->updateField('statut', 0);
                            }
                        }
                        

                    }
                } else {
                    global $db, $user;
                    $bimp = new BimpDb($db);
                    $val = $bimp->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $c->id);
                    $endDate = new DateTime($val);
                    
                    $diff = $now->diff($endDate);
                    if($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                        $send = true;
                        //$this->output .= $c->getData('ref') . " (Relance -> Vieux Contrat)<br />";
                        $message = "Contrat " . $c->getNomUrl(). "<br />Client ".$client->dol_object->getNomUrl()." <br /> dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                    } elseif($diff->invert == 1 && ($c->getData('tacite') == 0 || $c->getData('tacite') == 12)) {
                        //$this->output .= $c->getData('ref') . " (Clos)<br />";
                        $logs = $c->getData('logs');
                        $new_logs = $logs . "<br />" . "- <strong>Le ".date('d/m/Y')." à ".date('H:m')."</strong> Cloture automatique";
                        if ($c->dol_object->closeAll($user) >= 1) {
                            $echeancier = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
                            $c->updateField('logs', $new_logs);
                            $c->updateField('statut', 2);
                            $c->updateField('date_cloture', date('Y-m-d H:i:s'));
                            $c->updateField('fk_user_cloture', $user->id);
                            if($echeancier->find(['id_contrat' => $c->id])) {
                                $echeancier->updateField('statut', 0);
                            }
                        }

                    }
                    
                    
                }
                
                
                if($this->send && $send && $c->getData('relance_renouvellement') == 1) {
                    $this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref') . "[".$client->getData('code_client')."]", $c->getData('fk_commercial_suivi'), $message, $c);
                    $nombre_relance++;
                }
                
            }
            if($nombre_relance > 0)
                $this->output .= $nombre_relance . " relance echeances faites</br />";
            
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
            mailSyn2($sujet, $email, 'admin@bimp.fr', $message);
        }
        
        public function sendMailGroupeContrat($sujet, $message) {
            
            mailSyn2($sujet, 'contrats@bimp.fr', 'admin@bimp.fr', $message);
            
        }
       
    }