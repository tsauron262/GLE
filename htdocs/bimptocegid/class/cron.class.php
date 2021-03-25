<?php 

//    require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
//    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
//    
//    class Cron {
//        public function gleTOcegid() {
//            $yesteday = new DateTime('2019-07-10');
//            $yesteday->sub(new DateInterval("P1D"));
//            $date = $yesteday->format('Y-m-d');
//            $filesToCegid = [
//                "0_BIMPtoCEGID_(TIERS)_" . $date . ".TRA",
//                "2_BIMPtoCEGID_(PAIEMENTS)_" . $date . ".TRA"
//            ];
//            $local_dir = DIR_SYNCH . 'exportCegid/BY_DATE/';
//            $ftp_url = "ftp-edi.groupe-ldlc.com";
//            $ftp_login = "bimp-erp";
//            $ftp_mdp = "MEDx33w+3u(";
//            $ftp_folder = "/FTP-BIMP-ERP/accountingtest/";
//            $this->output = "";
//            if($ftp_connexion = ftp_connect($ftp_url)) {
//                $this->output .= 'Connexion FTP OK<br />';
//                if(ftp_login($ftp_connexion, $ftp_login, $ftp_mdp)) {
//                    $this->output .= 'Login FTP LDLC Ok<br />';
//                    $ftp_files_test = ftp_nlist($ftp_connexion, $ftp_folder);
//                    foreach($filesToCegid as $fileName) {
//                        $this->output .= "**********<br />";
//                        $file = $local_dir . $fileName;
//                        if(file_exists($file)) {
//                            $this->output .= $fileName . ' existe<br />';
//                            if(!in_array($ftp_folder . $fileName, $ftp_files_test)) {
//                                if(ftp_put($ftp_connexion, $ftp_folder . $fileName, $file, FTP_ASCII)) {
//                                    $this->output .= $fileName . ' Transféré avec succès<br />';
//                                    $file_from = $file;
//                                    $file_to = $local_dir . 'imported_auto/' . $fileName;
//                                    if(copy($file_from, $file_to)) {
//                                        $this->output .= 'Copier avec succès<br />';
//                                        if(unlink($file)) {
//                                            $this->output .= 'Fichier supprimer avec succès du dossier local de provenance<br />';
//                                        } else {
//                                            $this->output .= 'Erreur lors de la suppression du fichier du dossier local de provenance<br />';
//                                            mailSyn2("Erreur FTP compta", "dev@bimp.fr", null, "Le fichier $fileName n'à pas été supprimé du dossier local");
//                                        }
//                                    } else {
//                                        $this->output .= "Erreur lors de la copie du fichier " . $fileName . " dans le dossier imported_auto<br />";
//                                        mailSyn2("Erreur FTP compta", "dev@bimp.fr", null, "Le fichier $fileName n'à pas été déplacé dans le dossier imported_auto");
//                                    }
//                                }
//                            } else {
//                                $this->output .= 'Le fichier ' . $fileName . " n'à pas été transféré car il existe deja sur le serveur distant<br />";
//                            }
//                        } else {
//                            $this->output .= "Le fichier " . $file . " n'existe pas dans le dosssier local <br />";
//                        }
//                    }
//                    
//                    $scanned_local_dir = array_diff(scandir($dir), array('..', '.', 'imported', 'imported_auto'));
//                    
//                    $ftp_files = ftp_nlist($ftp_connexion, $ftp_folder);
//                    $this->output .= '<pre>Contenu du dossier FTP<br />' . print_r($ftp_files, 1) . '</pre><br />';
//                }
//                if(ftp_close($ftp_connexion)){
//                    $this->output .= 'Connexion FTP close avec succès';
//                } else {
//                    $this->output .= 'Erreur lors de la fermuture de la connexion FTP';
//                    mailSyn2("Erreur FTP compta", "dev@bimp.fr", null, "Erreur lors de la fermuture de la connexion FTP");
//                }
//            }
//            return "OK";
//        }
//    }