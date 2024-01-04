<?php
    require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_facture.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_factureFournisseur.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_payInc.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_paiement.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_importPaiement.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_deplacementPaiement.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_bordereauCHK.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    
    class export {
        
        public $lastDateExported;
        public $yesterday;
        private $bdb;
        public $TRA_facture;
        private $TRA_payInc;
        private $TRA_paiement;
        private $TRA_importPaiement;
        private $TRA_factureFournisseur;
        private $TRA_bordereauCHK;
        private $dir = "/exportCegid/BY_DATE/";
        public $fails = Array();
        public $good = Array();
        public $warn = Array();
        public $tiers = Array();
        public $moment;
        public $rollBack = false;
        public $excludeArrayScanDire = Array('..', '.', 'imported_auto', 'imported', 'rollback', 'a importer');
        
        function __construct($db) {
            $hier = new DateTime();
            $this->moment = ((int)$hier->format('H') < 12) ? 'AM' : 'PM'; 
            $this->yesterday = $hier->sub(new DateInterval("P1D"));
            $this->lastDateExported = new DateTime(BimpCore::getConf("last_export_date", null, "bimptocegid"));
            $this->bdb = new BimpDb($db);
            $this->TRA_facture = new TRA_facture($this->bdb, PATH_TMP . $this->dir . $this->getMyFile("tiers"));
            $this->TRA_factureFournisseur = new TRA_factureFournisseur($this->bdb, PATH_TMP . $this->dir . $this->getMyFile("tiers"));
            $this->TRA_payInc = new TRA_payInc($this->bdb);
            $this->TRA_paiement = new TRA_paiement($this->bdb, PATH_TMP . $this->dir . $this->getMyFile("tiers"));
            $this->TRA_deplacementPaiement = new TRA_deplacementPaiement($this->bdb, PATH_TMP . $this->dir . $this->getMyFile("tiers"));
            $this->TRA_importPaiement = new TRA_importPaiement($this->bdb);
            $this->TRA_bordereauCHK = new TRA_bordereauCHK($this->bdb);
            die('exported = 0 AND datep >= "'.$this->lastDateExported->format('Y-m-d').' 00:00:00"'.$this->getEntityFilter());
        }
        
        public function exportFactureFournisseur($ref = ''):void {
            global $db;
            $errors = Array();
            
            $list = $this->bdb->getRows('facture_fourn', 'exported = 0 AND fk_statut IN(1,2) AND (datef   >= "'.$this->lastDateExported->format('Y-m-d').' 00:00:00")'.$this->getEntityFilter());
            
            $file = PATH_TMP . $this->dir . $this->getMyFile("achats");
            if(count($list) > 0) {
                foreach($list as $facture) {
                    $ecriture = "";
                    $instance= BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_FactureFourn", $facture->rowid);
                    $ecriture .= $this->TRA_factureFournisseur->constructTra($instance);
                    if($this->write_tra($ecriture, $file)) {
                        $instance->updateField('exported', 1);
                        $this->good['ACHATS'][$instance->getRef()]= "Ok dans le fichier TRA " . $file;
                    } else {
                        $this->fails['ACHATS'][$instance->getRef()] = "Non écrit dans le TRA " . $file;
                    }
                }
            } else {
                $this->warn['ACHATS']['bimptocegid'] = "Pas de nouvelles factures à exportés";
            }
        }
        
        public function getEntityFilter(){
            $entity = getEntity('', 0);
            return ' AND entity = '.$entity;
        }
      
        public function exportFacture($ref = ""):void {
            global $db;
            $errors = [];
            $list = $this->bdb->getRows('facture', 'exported = 0 AND fk_statut IN(1,2) AND type != 3 AND datef > "2023-01-01" AND (datef >= "'.$this->lastDateExported->format('Y-m-d').'")'.$this->getEntityFilter());
                                    
            $file = PATH_TMP . $this->dir . $this->getMyFile("ventes");
            if(count($list) > 0) {
                foreach($list as $facture) {
                    $ecriture = "";
                    $instance= BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_Facture", $facture->rowid); 
                    $ecriture .= $this->TRA_facture->constructTra($instance);
                    
                    if($instance->getData('fk_mode_reglement') == 3) {

                        if($instance->getData('rib_client')) {
                            $ribANDmandat = BimpCache::getBimpObjectInstance('bimptocegid', "BTC_exportRibAndMandat");
                            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $instance->getData('fk_soc'));
                            $ecriture_rib = $ribANDmandat->export_rib($instance, $societe);
                            $ecriture_mdt = $ribANDmandat->export_mandat($instance, $societe);
                            if(!empty($ecriture_mdt) && !empty($ecriture_rib)) {
                                $this->write_tra($ecriture_rib, PATH_TMP . $this->dir . $this->getMyFile("ribs"));
                                $this->write_tra($ecriture_mdt, PATH_TMP . $this->dir . $this->getMyFile("mandats"));
                                $ribANDmandat->passTo_exported($instance);
                            }
                        }  else {
                            $subject = "EXPORT COMPTA - RIB MANQUANT";
                            $msg = "La facture " . $instance->getNomUrl() . " a été exportée avec comme mode de règlement mandat de prélèvement SEPA mais n'a pas de RIB";
                            $mail = new BimpMail(null, $subject, BimpCore::getConf('devs_email'), null, $msg);
                            $mail->send();
                        }
                    }
                    
                    if($this->write_tra($ecriture, $file)) {
                        $instance->updateField('exported', 1);
                        $this->good['VENTES'][$instance->getRef()]= "Ok dans le fichier TRA " . $file;
                    } else {
                        $this->fails['VENTES'][$instance->getRef()] = "Non écrit dans le TRA " . $file;
                    }
                }
                $this->tiers = $this->TRA_facture->rapportTier;
            } else {
                $this->warn['VENTES']['bimptocegid'] = "Pas de nouvelles factures à exportés";
            }
        }

        public function exportImportPaiement() {

            $instance = BimpCache::getBimpObjectInstance('bimpfinanc', 'Bimp_ImportPaiementLine');
            
            $list = $instance->getList(['exported' => 0, 'infos' => Array('in' => Array('C2BO'/*, 'YOUNITED', 'ONEY'*/))]);
            if(count($list) > 0) {
                foreach($list as $import) {
                    $instance->fetch($import['id']);
                    $file = PATH_TMP . $this->dir . 'IP' . $instance->getParentId() . '-' . $instance->id . '.tra';
                    if(!file_exists($file)) $this->createFile ($file);

                    if($this->write_tra($this->TRA_importPaiement->constructTRA($instance), $file)) {
                        $this->good['IP']['IP' . $instance->getParentId() . '-' . $instance->id] = 'Ok dans le fichier ' . $file;
                        $instance->updateField('exported', 1);
                    } else {
                        $this->fails['IP']['IP' . $instance->getParentId() . '-' . $instance->id] = "Erreur lors de l'écriture dans le fichier";
                    }
                }
            } else {
                $this->warn['IP']['IP' . $instance->getParentId() . '-' . $instance->id] = 'Pas d\'import de paiement à exporter en compta';
            }

        }
        
        public function exportDeplacementPaiament($ref  = ''):void {
            
            global $db;
            $errors = [];
            $file = PATH_TMP . $this->dir . $this->getMyFile('deplacementPaiements');
            
            $list = $this->bdb->getRows('mvt_paiement', 'traite = 0 AND date >= "'.$this->lastDateExported->format('Y-m-d').'"'.$this->getEntityFilter());
            
            if(count($list) > 0)  {
                foreach ($list as $line)  {
                    $datas = json_decode($line->datas);
                    
                    $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $datas->id_paiement);
                    $datas->code = (int) $paiement->getData('fk_paiement');
                    
                    if ($datas->code) {
                        if($this->bdb->getValue('c_paiement', 'code', 'id = ' . $datas->code) != 'NO_COM') {
                            if($this->write_tra($this->TRA_deplacementPaiement->constructTra($paiement, $datas), $file)) {
                                $this->good['DP'][$paiement->getRef()] = 'Ok dans le fichier ' . $file;
                                $this->bdb->update('mvt_paiement', Array('traite' => 1), 'id = ' . $line->id);
                            } else {
                                $this->fails['DP'][$paiement->getRef()] = 'Erreur de déplacement de ce paiement';
                            }
                        }
                    } else {
                        $this->fails['DP'][$paiement->getRef()] = 'Type de paiement absent';
                    }
                }
            }
            
            $this->tiers = $this->TRA_deplacementPaiement->rapportTier;
            
        }
        
        public function exportBordereauxCHK($ref = '', $want = Array()) {
            global $db;
            $errors = [];
            $file = PATH_TMP . $this->dir . $this->getMyFile('bordereauxCHK');
            
            $bordereaux = $this->bdb->getRows('bordereau_cheque', 'exported = 0'.$this->getEntityFilter());
            
            if(count($bordereaux) > 0) {
                foreach($bordereaux as $bordereau) {
                    
                    $chks = $this->bdb->getRows('bank', 'fk_bordereau = ' . $bordereau->rowid);
                    
                    if($this->write_tra($this->TRA_bordereauCHK->constructTra($bordereau, $chks), $file)) {
                        $this->bdb->update('bordereau_cheque', Array('exported' => 1), 'rowid = ' . $bordereau->rowid);
                    }
                    
                }
                
            }
                        
        }
        
        public function exportPaiement($ref = '', $want = Array()):void  {
            global $db;
            $errors = [];
            $file = PATH_TMP . $this->dir . $this->getMyFile("paiements");
            $list = $this->bdb->getRows('paiement', 'exported = 0 AND datep >= "'.$this->lastDateExported->format('Y-m-d').' 00:00:00"'.$this->getEntityFilter());

            foreach($list as $pay) {
                $reglement = $this->bdb->getRow('c_paiement', 'id = ' . $pay->fk_paiement);
                    if($reglement->code != 'NO_COM') {
                        $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', $pay->rowid);
                        $liste_transactions = $this->bdb->getRows('paiement_facture', 'fk_paiement = ' . $pay->rowid);
                        foreach($liste_transactions as $transaction) {
                            $ecriture = $this->TRA_paiement->constructTra($transaction, $paiement, $pay);
                            if($this->write_tra($ecriture, $file)) {
                                $this->good['PAY'][$pay->ref] = "Ok dans le fichier " . $file;
                                $paiement->updateField('exported', 1);
                            } else {
                                $this->fails['PAY'][$pay->ref] = "Erreur lors de l'écriture dans le fichier";
                            }
                            $ecriture = "";
                        }
                    } /* else {
                        $this->warn['PAY'][$pay->ref] = 'Non exporté car mode de reglement NO_COM';
                    } */
            }

            $this->tiers = $this->TRA_paiement->rapportTier;
        }
        
        public function exportPayInc():void {
            $errors = [];
            $list = $this->TRA_payInc->getAllForThisDay();
            $file = PATH_TMP . $this->dir . $this->getMyFile("payni");
            $ecriture  = "";
            if(count($list) > 0) {
                foreach($list as $infos) {
                    $ecriture .= $this->TRA_payInc->constructTRA($infos);
                    if($this->write_tra($ecriture, $file)) {
                        $this->good['PAYNI'][$infos["num"]]= "Ok dans le fichier TRA " . $file;
                    } else {
                        $this->fails['PAYNI'][$infos["num"]] = "Nom écrit dans le TRA " . $file;
                    }
                    $ecriture = "";
                }
            } else {
                $this->warn['PAYNI']['bimptocegid'] = "Pas de nouveaux paiements non identifiés à exportés";
            }
            
            
            
        }
        
        protected function head_tra():string {
            $head = "";
            $head .= sizing("***", 3);
            $head .= sizing("S5", 2);
            $head .= sizing("CLI", 3);
            $head .= sizing("JRL", 3);
            $head .= sizing("ETE", 3);
            $head .= sizing("", 3);
            $head .= sizing("01011900", 8);
            $head .= sizing("01011900", 8);
            $head .= sizing("007", 3);
            $head .= sizing("", 5);
            $head .= sizing(date('dmYHi'), 12);
            $head .= sizing("CEG", 35);
            $head .= sizing("", 35);
            $head .= sizing("", 4);
            $head .= sizing("", 9);
            $head .= sizing("01011900", 8);
            $head .= sizing("001", 3);
            $head .= sizing("-", 1);
            $head .= "\n";
            return $head;
        }
        
        public function getMyFile($type):string {
            
            $dateTime = $this->yesterday;
            
            $extendsEntity        = BimpCore::getExtendsEntity();
            $day            = $dateTime->format('d');
            $month          = $dateTime->format('m');
            $year           = $dateTime->format('Y');
            $version_tra    = BimpCore::getConf('version_tra', null, "bimptocegid");
            $extention      = ".tra";
            $files_dir      = PATH_TMP . $this->dir;
            $number         = null;
            
            switch ($type) {
                case 'tiers': $number  = 0; break;
                case 'ventes': $number  = 1; break;
                case 'paiements': $number  = 2; break;
                case 'achats': $number  = 3; break;
                case 'ribs': $number  = 4; break;
                case 'mandats': $number  = 5; break;
                case 'payni': $number  = 6; break;
                case 'deplacementPaiements': $number = 7; break;
                case 'bordereauxCHK': $number = 8;
            }
            
            return $number . "_" . $extendsEntity ."_(" . strtoupper($type) . ")_" .$year . '-' . $month . '-' . $day . '-' . $this->moment . '_' . $version_tra . $extention;
            
        }
        
        public function createFile($file):void {
            
            $files_dir = PATH_TMP . $this->dir;
            
            if(!is_dir($files_dir)) {
                mkdir($files_dir, 0777, true);
                mkdir($files_dir . "imported/", 0777, true);
                mkdir($files_dir, 0777, true);
            }
            
            shell_exec("chmod -R 777 " . $files_dir);
            
            $f = fopen($file, 'a+');
            fwrite($f, $this->head_tra());
            fclose($f);
            
        }
        
        public function create_daily_files():void {

            $files_dir = PATH_TMP . $this->dir;
            
            if(!is_dir($files_dir)) {
                mkdir($files_dir, 0777, true);
                mkdir($files_dir . "imported/", 0777, true);
                mkdir($files_dir . "rollback/", 0777, true);
                mkdir($files_dir, 0777, true);
            }
            
            shell_exec("chmod -R 777 " . $files_dir);
            
            $files = Array(
                $this->getMyFile('tiers'),
                $this->getMyFile('ventes'),
                $this->getMyFile('paiements'),
                $this->getMyFile('achats'),
                $this->getMyFile('ribs'),
                $this->getMyFile('mandats'),
                $this->getMyFile('payni'),
                $this->getMyFile('deplacementPaiements'),
                $this->getMyFile('bordereauxCHK')
            );
                        
            foreach($files as $file) {
                if(!file_exists($files_dir . $file))  {
                    $this->good['FILES'][$file] = "Créer avec succès"; 
                    $f = fopen($files_dir . $file, 'a+');
                    fwrite($f, $this->head_tra());
                    fclose($f);
                } else {
                    $this->warn['FILES'][$file] = 'Le fichier existe déjà';
                }
            }
            
        }
        
        protected function write_tra($ecriture, $file):bool {
            $opened_file = fopen($file, 'a+');
            if(fwrite($opened_file, $ecriture)) {
                return true;
            } else {
                $this->rollBack = true;
                return false;
            }
        }
        
    }