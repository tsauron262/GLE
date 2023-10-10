<?php 

    require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/export.class.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/controle.class.php';
//    ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    class Cron {
        
        protected $modeTest      = false;
        
        protected $export_class  = null;
        protected $stopCompta    = false;
        protected $filesNotFtp   = [];
        protected $rapport       = [];
        protected $files_for_ftp = [];
        protected $entitie       = null;
        protected $version_tra   = null;
        protected $ldlc_ftp_host = 'ftp-edi.groupe-ldlc.com';
        protected $ldlc_ftp_user = 'bimp-erp';
        protected $ldlc_ftp_pass = 'Yu5pTR?(3q99Aa';
        protected $ldlc_ftp_path = ''; 
        protected $local_path    = PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/';
        protected $size_vide_tra = 149;
        protected $rollback = false;
        protected $copyErrors = Array();

        private $auto_tiers             = true;
        private $auto_ventes            = true;
        private $auto_paiements         = true;
        private $auto_achats            = true;
        private $auto_rib_mandats       = true;
        private $auto_payni             = true;
        private $auto_importPaiement    = true;
        private $auto_deplacementPay    = true;
        private $auto_bordereauCHK      = true;
        
        private $export_ventes          = true;
        private $export_paiements       = true;
        private $export_achats          = true;
        private $export_payni           = true;
        private $export_importPaiement  = true;
        private $export_deplacementPay  = true;
        private $export_bordereauCHK    = true;
        public $output = '';
        
        public function __construct() {// Bien penssé a changer pour les test à /accountingtest/
            $this->ldlc_ftp_path = "/".BimpCore::getConf('exports_ldlc_ftp_dir').'/accounting';
            
            if($this->modeTest) {
                $this->ldlc_ftp_path .= 'test/';
            }
            else{
                $this->ldlc_ftp_path .= '/';
            }
        }
        
        public function manualSendTRA() {
            $errors = $warnings = Array();
            
            $this->version_tra = BimpCore::getConf('version_tra', null, "bimptocegid");
            $this->entitie = BimpCore::getConf('file_entity', null, "bimptocegid");
            $this->files_for_ftp = $this->getFilesArrayForTranfert();
            
            if(!count($errors)) {
                $this->renameFileAvantFTP();
                $this->checkFiles();
                if(!$this->stopCompta) {
                    $this->FTP();
                    $this->menage();
                    $success = 'Fichiers transférés avec succès';
                }
            }
                        
            return Array('success' => $success, 'errors' => $errors, 'warnings' => $warnings);
        }
        
        public function automatique() {
            global $db;
            
            if(((defined('ID_ERP') && ID_ERP == 6) || $this->modeTest)) {
                $this->export_class = new export($db);
                if(!is_dir($this->local_path))
                    mkdir($this->local_path);
                if(!is_dir($this->local_path))
                    die($this->local_path.' introuvable');
                if(!count(array_diff(scandir($this->local_path), $this->export_class->excludeArrayScanDire))) {
                    $db->begin(); //Ouvre la transaction

                    $this->version_tra = BimpCore::getConf('version_tra', null, "bimptocegid");
                    $this->entitie = BimpCore::getConf('file_entity', null, "bimptocegid");
                    
                    $this->export_class->create_daily_files();
                    $this->files_for_ftp = $this->getFilesArrayForTranfert();

                    
                    if($this->export_ventes && !$this->export_class->rollBack)                                                    $this->export_class->exportFacture();
                    if(!BimpCore::getConf('export_only_fact', 0, 'bimptocegid')){
                        if($this->export_payni && !$this->export_class->rollBack)                                                     $this->export_class->exportPayInc();
                        if($this->export_paiements && !$this->export_class->rollBack)                                                 $this->export_class->exportPaiement();
                        if($this->export_achats && !$this->export_class->rollBack)                                                    $this->export_class->exportFactureFournisseur();
                        if($this->export_importPaiement &&  !$this->export_class->rollBack)                                           $this->export_class->exportImportPaiement();
                        if($this->export_deplacementPay && !$this->export_class->rollBack)                                            $this->export_class->exportDeplacementPaiament();
                        if($this->export_bordereauCHK && !$this->export_class->rollBack)                                              $this->export_class->exportBordereauxCHK();
                    }

                    $this->renameFileAvantFTP();
                    $this->checkFiles();

                    if(!$this->stopCompta && !$this->export_class->rollBack) {
                        $this->menage();
                        $this->send_rapport(1);
                        $db->commit();
                        $this->FTP();
                        $this->send_rapport(2);
                        return 0;
                    } else {
                        //if($this->export_class->rollBack) {
                            $db->rollback(); // Annule la transaction
                            $this->fichiersToRollback();

                            $message = 'Bonjour, ROLLBACK de la compta. Aucune action n\'à été faites pour remonter la compta d\'Aujourd\'hui, les fichiers ont étés tran sférés  dans le dossier rollback';

                            if(count($this->copyErrors) > 0) {
                                $message .= ' SAUF: ';
                                foreach($this->copyErrors as $file) {
                                    $message .= $file . ',';
                                }
                            }
                            mailSyn2("Urgent - COMPTA ROLLBACK", 'dev@bimp.fr', null, $message);
                        //}
                    }
                } else {
                    mailSyn2('URGENT COMPTA', 'dev@bimp.fr', null, 'Dossier d\'export non vide. Rien à été fait. <br />' . print_r(array_diff(scandir($this->local_path), $this->export_class->excludeArrayScanDire), 1));
                }
            } else {
                die('Pas sur la bonne instance de l\'ERP');
            }
            return 0;
        }
        
        protected function checkFiles():void {
            $files = $this->getFilesArrayForTranfert();
            $checkControle = Array();
            $nbFileInError = 0;
            if(count($files) > 0) {
                foreach($files as $pattern) {
                    $transfertFiles = glob($this->local_path . $pattern);
                    if(count($transfertFiles) > 0) {
                        foreach($transfertFiles as $file) {
                            if(filesize($file) > $this->size_vide_tra) {
                                $controle = controle::tra($file, file($file));
                                $checkControle[basename($file)] = (object) $controle;
                            }
                        }
                    }
                }
                
                $mustSend = false;
                $message = '';
                if(count($checkControle) > 0)  {
                    foreach($checkControle as $fileName => $controle) {                       
                        $error = 0;
                        
                        $message .= '<b>' . $fileName . '</b><br />';
                        if($controle->header != '') {
                            if(!in_array($fileName, $this->filesNotFtp)) $this->filesNotFtp[] = $fileName;
                            $error++;
                            $message .= 'Erreur sur le header  du fichier<br />';
                        }
                        if(count($controle->alignement) > 0) {
                            if(!in_array($fileName, $this->filesNotFtp)) $this->filesNotFtp[] = $fileName;
                            $error++;
                            $message .= 'Erreur d\'alignement: <br />';
                            foreach($controle->alignement as $line)  {
                                $message .= $line . '<br />';
                            }
                        }
                        if(count($controle->balance) > 0) {
                            if(!in_array($fileName, $this->filesNotFtp)) $this->filesNotFtp[] = $fileName;
                            $error++;
                            $message .= 'Erreur de balance: <br />';
                            foreach($controle->balance as $facture => $i) {
                                if((int)$i['LINE'] > 0) {
                                    $ecart = (float) $i['TTC'] - (float) $i['CONTROLE'];
                                    if($ecart > 0) $ecart = '<b style="color:green">+' . round($ecart, 2) . '</b>';
                                    else $ecart = '<b style="color:red">'.round($ecart,2).'</b>';

                                    $message .='TRA<b>#' . $i['LINE'] . '</b> ' . $facture . ': TTC = ' . $i['TTC'] . '€, TOTAL DES LIGNES: ' . $i['CONTROLE'] . '€ Merci de faire un '.$ecart.'€ sur la ligne service ou produit de la facture</b><br />';

                                }
                            }
                        }
                        $message .= '<br /><br />';
                        
                        if($error) {
                            $mustSend = true;
                            if(stripos($fileName, '(TIERS)')) {
                                $this->stopCompta = true;
                            }
                        }
                        
                    }
                    
                    if($this->stopCompta) {
                        $message .= 'Une erreur à été détectée dans le fichier TIERS donc AUCUN fichier n\'a été transféré en compta';
                    } else {
                        $message .= 'Seul les fichiers mentionnés ci_dessous ne sont pas transférés en compta<br />';
                        $message .= implode("\n", $this->filesNotFtp);
                    }
                }

                if($mustSend) {
                    mailSyn2('Urgent - Fichiers TRA non conformes', 'dev@bimp.fr', null, $message);
                }
 
            }
        }
        
        protected function fichiersToRollback():void {
            // TODO
            $listFichierLocal = array_diff(scandir($this->local_path), $this->export_class->excludeArrayScanDire);
            
            $copyErrors = Array();
            
            foreach($listFichierLocal as $fileName){
                if(!is_dir($this->local_path . 'rollback/' . date('Y-m-d'))) {
                    mkdir($this->local_path . "rollback/" . date('Y-m-d'), 0777, true);
                }
                if(copy($this->local_path . $fileName, $this->local_path . 'rollback/' . date('Y-m-d') . '/' . $fileName . '--' . date('H:i:s') . '.tra'))  {
                    unlink($this->local_path . $fileName);
                } else{
                    $copyErrors[] = $fileName;
                }
            }
            
            $this->copyErrors = $copyErrors;
            
        }

        protected function send_rapport($mode = 1) {

            $sujet = "Rapport export comptable du " . date('d/m/Y');
            $to = BimpCore::getConf('devs_email');
            $from = null;
            $logs = '';
            
            if($mode == 1){
                // Message type de pièce automatique
                if(array_key_exists('FILES_FTP', $this->rapport)) {
                    $logs .= $this->rapport['FILES_FTP'];
                }

                $logs .= "\n";

                // Message pour les fichiers
                if(array_key_exists("FILES", $this->export_class->good)) {
                    $logs .= "Fichiers (Succès)\n";
                    foreach($this->export_class->good['FILES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("FILES", $this->export_class->fails)) {
                    $logs .= "Fichiers (Erreurs)\n";
                    foreach($this->export_class->fails['FILES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("FILES", $this->export_class->warn)) {
                    $logs .= "Fichiers (Informations)\n";
                    foreach($this->export_class->warn['FILES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $logs .= "\n";

                // message pour les payni
                if(array_key_exists("PAYNI", $this->export_class->good)) {
                    $logs .= "PAYNI (Succès)\n";
                    foreach($this->export_class->good['PAYNI'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("PAYNI", $this->export_class->fails)) {
                    $logs .= "PAYNI (Erreurs)\n";
                    foreach($this->export_class->fails['PAYNI'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("PAYNI", $this->export_class->warn)) {
                    $logs .= "PAYNI (Informations)\n";
                    foreach($this->export_class->warn['PAYNI'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $logs .= "\n";

                $saveArrayFacture = Array();
                // message pour les ventes
                if(array_key_exists("VENTES", $this->export_class->good)) {
                    $logs .= "VENTES (Succès)\n";

                    foreach($this->export_class->good['VENTES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                        $saveArrayFacture[] = $name;
                    }
                }
                if(array_key_exists("VENTES", $this->export_class->fails)) {
                    $logs .= "\nVENTES (Erreurs)\n";
                    foreach($this->export_class->fails['VENTES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("VENTES", $this->export_class->warn)) {
                    $logs .= "\nVENTES (Informations)\n";
                    foreach($this->export_class->warn['VENTES'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $logs .= "\n";

                $saveArrayFacture = Array();
                // message pour les ventes
                if(array_key_exists("ACHATS", $this->export_class->good)) {
                    $logs .= "ACHATS (Succès)\n";

                    foreach($this->export_class->good['ACHATS'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                        $saveArrayFacture[] = $name;
                    }
                }
                if(array_key_exists("ACHATS", $this->export_class->fails)) {
                    $logs .= "\ACHATS (Erreurs)\n";
                    foreach($this->export_class->fails['ACHATS'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("ACHATS", $this->export_class->warn)) {
                    $logs .= "\ACHATS (Informations)\n";
                    foreach($this->export_class->warn['ACHATS'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $logs .= "\n";

                $saveArrayPaiement = Array();
                // message pour les paiements
                if(array_key_exists("PAY", $this->export_class->good)) {
                    $logs .= "PAY (Succès)\n";

                    foreach($this->export_class->good['PAY'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                        $saveArrayPaiement[] = $name;
                    }
                }
                if(array_key_exists("PAY", $this->export_class->fails)) {
                    $logs .= "\nPAY (Erreurs)\n";
                    foreach($this->export_class->fails['PAY'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("PAY", $this->export_class->warn)) {
                    $logs .= "\nPAY (Informations)\n";
                    foreach($this->export_class->warn['PAY'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $logs .= "\n";

                $saveArrayPaiementDep = Array();
                // message pour les paiements
                if(array_key_exists("DP", $this->export_class->good)) {
                    $logs .= "Déplacement paiements (Succès)\n";

                    foreach($this->export_class->good['DP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                        $saveArrayPaiementDep[] = $name;
                    }
                }
                if(array_key_exists("DP", $this->export_class->fails)) {
                    $logs .= "\nDéplacement paiements (Erreurs)\n";
                    foreach($this->export_class->fails['DP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("DP", $this->export_class->warn)) {
                    $logs .= "\nDéplacement paiements (Informations)\n";
                    foreach($this->export_class->warn['DP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $log .= "\n";

                $saveArrayPaiementImport = Array();
                // message pour les paiements
                if(array_key_exists("IP", $this->export_class->good)) {
                    $logs .= "ImportPaiement (Succès)\n";

                    foreach($this->export_class->good['IP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                        $saveArrayPaiementImport[] = $name;
                    }
                }
                if(array_key_exists("IP", $this->export_class->fails)) {
                    $logs .= "\nImportPaiement (Erreurs)\n";
                    foreach($this->export_class->fails['IP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }
                if(array_key_exists("IP", $this->export_class->warn)) {
                    $logs .= "\nImportPaiement (Informations)\n";
                    foreach($this->export_class->warn['IP'] as $name => $log) {
                        $logs .= ''.$name.': ' . $log . "\n";
                    }
                }

                $saveTiersArray = Array();
                // Message pour les tiers
                if(count($this->export_class->tiers) > 0) {
                    $logs .= "\nTIERS (Création)\n";
                    foreach($this->export_class->tiers as $aux => $log) {
                        $logs .= '' . $aux . ': ' . $log . "\n";
                        $saveTiersArray[] = $aux;
                    }
                } 

                $logs .= "\n\n";

                // Message pour le ménage
                if(array_key_exists("MENAGE", $this->rapport)) {
                    $logs .= 'Ménage (Process)' . "\n";
                    $logs .= implode("\n", $this->rapport['MENAGE']);
                }

                $logs .= "\n\nListe des factures en cas d'erreurs: \n" . implode(',' , $saveArrayFacture) . "\n";
                $logs .= "Liste des paiement en cas d'erreurs: \n" . implode(',' , $saveArrayPaiement) . "\n";
                $logs .= "Liste des import paiement en cas d'erreurs: \n" . implode(',' , $saveArrayPaiementImport) . "\n";
                $logs .= "Liste des tiers en cas d'erreurs: \n" . implode(',' , $saveTiersArray) . "\n";
            
            }
            else{
            
                $logs .= "\n\n";

                // Message FTP
                if(array_key_exists("FTP", $this->rapport)) {
                    $logs .= 'FTP (Process) ' . $this->ldlc_ftp_path . "\n";
                    $logs .= implode("\n", $this->rapport['FTP']);
                }
            }
            
            $this->output .= $logs;
            
            $filePath = PATH_TMP . '/' . 'exportCegid' . '/' . 'rapports' . '/';
            if(!is_dir($filePath))
                mkdir($filePath);
            $fileName = date('d_m_Y') . '_'.$this->export_class->moment.'.log';
            $log_file = fopen($filePath . $fileName, 'a');
            if(!fwrite($log_file, $logs . "\n\n"))
                 mailSyn2($sujet. ' impossible d\'écrire le fichier', $to, $from, "Bonjour, vous trouverez en pièce jointe le rapport des exports comptable existant, et voici le nouveau : ".$logs, array($filePath . $fileName),array('text/plain'), array($fileName));
            fclose($log_file);
            chmod($filePath . $fileName, 777);

            
        }
        
        protected function getFilesArrayForTranfert():array {
            $files = [];
                        
            if($this->auto_tiers)       $files[] = "0_" . $this->entitie . '_(TIERS)_' . '*'   . $this->version_tra . '.tra';
            if($this->auto_ventes)      $files[] = "1_" . $this->entitie . '_(VENTES)_' . '*' .  $this->version_tra . '.tra';
            if($this->auto_paiements)   $files[] = "2_" . $this->entitie . '_(PAIEMENTS)_' . '*' .  $this->version_tra . '.tra';
            if($this->auto_achats)      $files[] = "3_" . $this->entitie . '_(ACHATS)_' . '*' .  $this->version_tra . '.tra';
            if($this->auto_rib_mandats) {
                $files[] = "4_" . $this->entitie . '_(RIBS)_' . '*'  . $this->version_tra . '.tra';
                $files[] = "5_" . $this->entitie . '_(MANDATS)_' . '*'  . $this->version_tra . '.tra';
            }
            if($this->auto_payni)                                                   $files[] = "6_" . $this->entitie . '_(PAYNI)_' . '*'  . $this->version_tra . '.tra';
            if($this->auto_importPaiement)                                          $files[] = 'IP*.tra';
            if($this->auto_deplacementPay)                                          $files[] = "7_" . $this->entitie . '_(DEPLACEMENTPAIEMENTS)' . '*' .  $this->version_tra . '.tra';
            if($this->auto_bordereauCHK)                                            $files[] = "8_" . $this->entitie . '_(BORDEREAUXCHK)' . '*'  . $this->version_tra . '.tra';
            
            $this->rapport['FILES_FTP'] = 'Liste des fichiers transférés automatiquement sur le FTP de LDLC' . "\n"
                    . implode("\n", $files) . "\n";
            
            return $files;
        }
        
        public function renameFileAvantFTP() {
            $renamed = Array();
            foreach($this->files_for_ftp as $file) {
                if($file[0] != 'I') {
                    $renamed = glob($this->local_path . $file);
                    if(count($renamed) > 0) {
                        $i = 1;
                        foreach($renamed as $filePath) {
                            $fileName = basename($filePath);
                            $exploded = explode('_', $fileName);
                            $newFileName = $exploded[0] . '_' . $exploded[1] . '_' . $exploded[2] . '_' . $exploded[3] . '-' . date('his') . '-' . $i . '_' . 'Y2.tra';
                            if(!rename($filePath, $this->local_path . $newFileName)) {
                                $this->stopCompta = true;
                            }
                            $i++;
                        }
                    }
                }
            }
        }
        
        public function FTP() {

            $files = [];

            foreach ($this->files_for_ftp as $pattern) {
                $files = array_merge($files, glob($this->local_path . $pattern));
            }
                                    
            $ftp = ftp_connect($this->ldlc_ftp_host, 21);
            if($ftp === false) { 
                $this->rapport['FTP'][] = "Erreur de connexion au FTP LDLC";
                
            } 
            else { 
                $this->rapport['FTP'][] = 'Connexion avec le FTP LDLC Ok';
                if(!ftp_login($ftp, $this->ldlc_ftp_user, $this->ldlc_ftp_pass)){ 
                    $this->rapport['FTP'][] = 'Erreur de login FTP LDLC'; 
                } 
                else { 
                    $this->rapport['FTP'][] = 'Login avec le FTP LDLC Ok'; 
                    
                    if (defined('FTP_SORTANT_MODE_PASSIF')) { 
                        ftp_pasv($ftp, true); 
                        
                    } else { 
                        ftp_pasv($ftp, false); 
                    }
                    
                    $present_sur_ftp_ldlc = ftp_nlist($ftp, $this->ldlc_ftp_path);
                    if(count($files) > 0) {
                        foreach($files as $file_path) {
                            $filename = basename($file_path);
                            if(!in_array($filename, $this->filesNotFtp)) {
                                if(!in_array($this->ldlc_ftp_path . $filename, $present_sur_ftp_ldlc)) {
                                    if(filesize($file_path) > $this->size_vide_tra) {
                                        if(ftp_put($ftp, $this->ldlc_ftp_path . $filename, $this->local_path . $filename, FTP_ASCII)) {
                                            $this->rapport['FTP'][] = $filename . " transféré avec succès sur le FTP de LDLC";
                                            if(!is_dir($this->local_path . 'imported_auto/'))
                                                    mkdir($this->local_path . 'imported_auto/');
                                            if(copy($file_path, $this->local_path . 'imported_auto/' . $filename)) {
                                                unlink($file_path);
                                            } else {
                                                mailSyn2("Compta - Erreur de copie", 'dev@bimP.fr', null, 'Le fichier ' . $filename . ' ne c\'est pas copié dans ledossier d\'import');
                                            }
                                        } else {
                                            $this->rapport['FTP'][] = $filename . " non transféré sur le FTP de LDLC";
                                        }
                                    } else {
                                        $this->rapport['FTP'][] = $filename . " non transféré sur le FTP de LDLC car il est vide (fichier supprimé automatiquement)";
                                        unlink($file_path);
                                    }
                                } else {
                                    $this->rapport['FTP'][] = $filename . ' déjà présent sur le FTP de LDLC';
                                }
                            }
                        }
                    } else {
                        $this->rapport['FTP'][] = "Aucun fichiers à transférer";
                    }
                }
            }
        }
        
        protected function menage() {
            
            $liste_files = scandir($this->local_path);
            
            if(count($liste_files) > 0) {
                foreach($liste_files as $filename) {
                    if(filesize($this->local_path . $filename) == $this->size_vide_tra) {
                        unlink($this->local_path . $filename);
                        $this->rapport['MENAGE'][] = $this->local_path . $filename . " supprimé automatiquement du dossier local car vide";
                    }
                }
            } else {
                $this->rapport['MENAGE'][] = "Auncun fichiers vide à supprimer";
            }
            
        }
       
        
    }
