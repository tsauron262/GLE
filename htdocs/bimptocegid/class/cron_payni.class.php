<?php 

    require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/export.class.php';
    
    class Cron_payni {
        
        protected $export_class  = null;
        protected $rapport       = [];
        protected $files_for_ftp = [];
        protected $entitie       = null;
        protected $version_tra   = null;
        protected $ldlc_ftp_host = 'ftp-edi.groupe-ldlc.com';
        protected $ldlc_ftp_user = 'bimp-erp';
        protected $ldlc_ftp_pass = 'Yu5pTR?(3q99Aa';
        protected $ldlc_ftp_path = '/FTP-BIMP-ERP/accounting/'; // Bien penssé a changer pour les test à /FTP-BIMP-ERP/accountingtest/
        protected $local_path    = PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/';
        protected $size_vide_tra = 149;
        
        private $auto_payni             = true;
        private $export_payni           = true;
        
        public function automatique() {
            global $db;
            $this->version_tra = BimpCore::getConf('BIMPTOCEGID_version_tra');
            $this->entitie = BimpCore::getConf('BIMPTOCEGID_file_entity');
            $this->export_class = new export($db);
            $this->export_class->create_daily_files();
            $this->files_for_ftp = $this->getFilesArrayForTranfert();
            
            if($this->export_payni)     $this->export_class->exportPayInc();
            
            $this->FTP();
            $this->menage();
            $this->send_rapport();
            
        }

        protected function send_rapport() {
            
            $sujet = "Rapport export PAYNI du " . date('d/m/Y');
            $to = 'dev@bimp.fr';
            $from = null;
            
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
            
            $savePAYNIArray = Array();
            // message pour les payni
            if(array_key_exists("PAYNI", $this->export_class->good)) {
                $logs .= "PAYNI (Succès)\n";
                foreach($this->export_class->good['PAYNI'] as $name => $log) {
                    $logs .= ''.$name.': ' . $log . "\n";
                    $savePAYNIArray[] = $name;
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
            
            $logs .= "\n\n";
            
            // Message FTP
            if(array_key_exists("FTP", $this->rapport)) {
                $logs .= 'FTP (Process) ' . $this->ldlc_ftp_path . "\n";
                $logs .= implode("\n", $this->rapport['FTP']);
            }
            
            $logs .= "\n\n";
            
            // Message pour le ménage
            if(array_key_exists("MENAGE", $this->rapport)) {
                $logs .= 'Ménage (Process)' . "\n";
                $logs .= implode("\n", $this->rapport['MENAGE']);
            }
            
            $logs .= "\n\nListe des paiement non identifiés en cas d'erreurs: \n" . implode(',' , $savePAYNIArray) . "\n";
            
            $this->output .= $logs;
            
            $log_file = fopen(PATH_TMP . '/' . 'exportCegid' . '/' . 'rapports_payni' . '/' . date('d_m_Y') . '.log', 'w');
            fwrite($log_file, $logs);
            fclose($log_file);

            mailSyn2($sujet, $to, $from, "Bonjour, vous trouverez en pièce jointe le rapport des exports PAYNI de ce matin.", [PATH_TMP . '/' . 'exportCegid' . '/' . 'rapports_payni' . '/' . date('d_m_Y') . '.log']);
            
        }
        
        protected function getFilesArrayForTranfert():array {
            $files = [];

            if($this->auto_payni)       $files[] = "6_" . $this->entitie . '_(PAYNI)_' . '*' . '_' . $this->version_tra . '.tra';
            
            $this->rapport['FILES_FTP'] = 'Liste des fichiers transférés automatiquement sur le FTP de LDLC' . "\n" . implode("\n", $files) . "\n";
            
            return $files;
        }
        
        protected function FTP() {
            
            $files = [];
                        
            foreach ($this->files_for_ftp as $pattern) {
                $files = array_merge($files, glob($this->local_path . $pattern));
            }
            
            $ftp = ftp_connect($this->ldlc_ftp_host, 21);
            if($ftp === false) { $this->rapport['FTP'][] = "Erreur de connexion au FTP LDLC";} else { $this->rapport['FTP'][] = 'Connexion avec le FTP LDLC Ok'; }
            if(!ftp_login($ftp, $this->ldlc_ftp_user, $this->ldlc_ftp_pass)){ $this->rapport['FTP'][] = 'Erreur de login FTP LDLC'; } else { $this->rapport['FTP'][] = 'Login avec le FTP LDLC Ok'; }
            if (defined('FTP_SORTANT_MODE_PASSIF')) { ftp_pasv($ftp, true); } else { ftp_pasv($ftp, false); }
            
            $present_sur_ftp_ldlc = ftp_nlist($ftp, $this->ldlc_ftp_path);
            if(count($files) > 0) {
                foreach($files as $file_path) {
                    $filename = basename($file_path);
                    if(!in_array($this->ldlc_ftp_path . $filename, $present_sur_ftp_ldlc)) {
                        if(filesize($file_path) > $this->size_vide_tra) {
                            if(ftp_put($ftp, $this->ldlc_ftp_path . $filename, $this->local_path . $filename, FTP_ASCII)) {
                                $this->rapport['FTP'][] = $filename . " transféré avec succès sur le FTP de LDLC";
                                unlink($file_path);
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
            } else {
                $this->rapport['FTP'][] = "Aucun fichiers à transférer";
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
