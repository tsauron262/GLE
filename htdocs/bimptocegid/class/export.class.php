<?php
    require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_facture.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_payInc.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    
    class export {
        
        public $lastDateExported;
        public $yesterday;
        private $bdb;
        private $TRA_facture;
        private $TRA_payInc;
        private $dir = "/exportCegid/BY_DATE/";
        public $fails = Array();
        public $good = Array();
        public $warn = Array();
        
        function __construct($db) {
            $hier = new DateTime();
            $this->yesterday = $hier->sub(new DateInterval("P1D"));
            $this->yesterday = new DateTime('2019-09-16');
            $this->lastDateExported = new DateTime(BimpCore::getConf("BIMPTOCEGID_last_export_date"));
            $this->bdb = new BimpDb($db);
            $this->TRA_facture = new TRA_facture($this->bdb);
            $this->TRA_payInc = new TRA_payInc($this->bdb);
        }
        
      
        public function exportFacture($ref = "") {
            global $db;
            $errors = [];
            $list = $this->bdb->getRows('facture', 'exported = 0 AND fk_statut IN(1,2) AND type != 3 AND (datef BETWEEN "'.$this->lastDateExported->format('Y-m-d').'" AND "'.$this->yesterday->format('Y-m-d').'" OR date_valid BETWEEN "'.$this->lastDateExported->format('Y-m-d').'" AND "'.$this->yesterday->format('Y-m-d').'")', 10);
            if(count($list) > 0) {
                echo "<pre>";
                $instance= BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_Facture");
                foreach($list as $facture) {
                    $instance->fetch($facture->rowid);
                    $ecriture .= $this->TRA_facture->constructTra($instance);
                }
                
            }
            //$this->TRA_facture->setRef($ref);
            echo $ecriture;
            //print_r($list);
        }
        
        public function exportFactureFournisseur($ref):bool {
            
        }
        
        public function exportPaiement($ref):bool  {
            
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
            
            
            //echo '<pre>' . print_r($this->good) . print_r($this->fails);
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
        
        private function getMyFile($type):string {
            $entitie        = BimpCore::getConf('BIMPTOCEGID_file_entity');
            $day_fo_year    = date('z');
            $day            = date('d');
            $month          = date('m');
            $year           = date('Y');
            $version_tra    = BimpCore::getConf('BIMPTOCEGID_version_tra');
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
            }
            
            return $number . "_" . $entitie ."_(" . strtoupper($type) . ")_" .$year . '_' . $month . '_' . $day . '_' . $version_tra . $extention;
            
        }
        
        public function create_daily_files():void {

            $files_dir = PATH_TMP . $this->dir;
            
            if(!is_dir($files_dir)) {
                mkdir($files_dir, 0777, true);
                mkdir($files_dir . "imported/", 0777, true);
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
            if(fwrite($opened_file, $ecriture . "\n")) {
                return true;
            } else {
                return false;
            }
        }
        
    }