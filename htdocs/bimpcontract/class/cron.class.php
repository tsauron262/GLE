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
        
        function zu_gehen() {
            $this->relance_brouillon();
            $this->echeance_contrat();
            $this->relance_demande();
            //$this->facturation_auto();
            return "OK";
        }
        
        function facturation_auto() {
            
            $echeanciers = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
            
            $list = $echeanciers->getList(['validate' => 1]);
            //echo '<pre>'; 
            //print_r($list);
            
            foreach($list as $i => $infos) {
                    
                $infos = (object) $infos;
                
                $e = BimpObject::getInstance('bimpcontract', 'BContract_echeancier', $infos->id);
                $c = $e->getParentInstance();
                $date_facturation = new DateTime($e->getData('next_facture_date'));
                $date_next_facture = new DateTime($e->getData('next_facture_date'));
                $date_next_facture->add(new DateInterval("P" . $c->getData('periodicity'). "M"))->sub(new DateInterval("P1D"));
                
                $reste_a_payer = $c->reste_a_payer();
                $reste_periode = ceil($c->reste_periode());
                $morceauPeriode = (($data->reste_periode - ($i-1)) >= 1)? 1 : (($data->reste_periode - ($i-1)));
                $amount = $reste_a_payer / $data->reste_periode * $morceauPeriode;
                
                $data = [
                    'date_start' => $date_facturation->format('Y-m-d'),
                    'date_end' => $date_next_facture->format('Y-m-d'),
                    'montant_ht' => ''
                ];
                print_r($data);
                //$e->actionCreateFacture($data);
            }
            
            //echo '</pre>';
            
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
                    $this->sendMailCommercial('BROUILLON - Contrat ' . $contrat->ref, $contrat->fk_commercial_suivi, $message);
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
                $endDate = new DateTime($c->getData('end_date_contrat'));
                
                $diff = $now->diff($endDate);
                
                //$this->output .= print_r($diff, 1);
                
                if($diff->y == 0 && $diff->m == 0 && $diff->d <= 30 && $diff->d > 0 && $diff->invert == 0) {
                    $send = true;
                    $message = "Le contrat " . $c->getData('ref') . " dont vous êtes le commercial arrive à expiration dans <b>$diff->d jour.s</b>";
                } elseif($diff->invert == 1) {
                    $this->output .= $c->getData('ref') . " (Clos)<br />";
                    $logs = $c->getData('logs');
                    $new_logs = $logs . "<br />" . "- <strong>Le ".date('d/m/Y')." à ".date('H:m')."</strong> Cloture automatique";
                    
                    $c->updateField('logs', $new_logs);
                    $c->updateField('statut', 2);
                    
                }
                
                if($this->send && $send && $c->getData('relance_renouvellement') == 1) {
                    $this->sendMailCommercial('ECHEANCE - Contrat ' . $c->getData('ref'), $c->getData('fk_commercial_suivi'), $message);
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
        public function sendMailCommercial($sujet, $id_commercial, $message) {
            $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_commercial);
            $email = $commercial->getData('email');

            if($commercial->getData('statut') == 0) {
                $supp_h = BimpObject::getInstance('bimpcore', 'Bimp_User', $commercial->getData('fk_user'));
                $email = $supp_h->getData('email');
                
                if($supp_h->getData('statut') == 0) {
                    $email = 'debugerp@bimp.fr';
                }
                
            }
            
            //mailSyn2($sujet, $email, 'admin@bimp.fr', $message);
        }
        
        public function sendMailGroupeContrat($sujet, $message) {
            
            //mailSyn2($sujet, 'contrats@bimp.fr', 'admin@bimp.fr', $message);
            
        }
       
    }