<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportProcess.php');


class BDS_ExportsComptaProcess extends BDSImportProcess {
        
    public static function install(&$errors = array(), &$warnings = array()) {
        
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
            'name'        => 'ExportsCompta',
            'title'       => 'Transfère FTP Compta',
            'description' => 'Exporte les fichiers TRA du FTP BIMP au FTP LDLC',
            'type'        => 'export',
            'active'      => 1
        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_host',
                'label'      => 'Hôte',
                'value'      => 'ftp-edi.groupe-ldlc.com'
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_login',
                'label'      => 'Login',
                'value'      => 'bimp-erp'
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_pwd',
                'label'      => 'MDP',
                'value'      => 'MEDx33w+3u('
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir',
                'label'      => 'Dossier FTP',
                'value'      => '/FTP-BIMP-ERP/accounting/'
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'tra_sender',
                'label'      => 'Société émettrice du fichier TRA',
                'value'      => 'BIMP'
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'tra_version',
                'label'      => 'Version du fichier TRA',
                'value'      => 'Y2'
            ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                'id_process'  => (int) $process->id,
                'title'       => 'Envois FTP',
                'name'        => 'sendOnFtp',
                'description' => '',
                'warning'     => '',
                'active'      => 1,
                'use_report'  => 1
            ), true, $warnings, $warnings);

        }
    }
    
    public function initSendOnFtp(&$data, &$errors = array())
    {

        $data['steps']["transfert_ftp"] = array(
            'label'                  => "Transfert des fichiers TRA",
            'on_error'               => 'stop',
            'nbElementsPerIteration' => 0
        );

    }
    
    public function executeSendOnFtp($step_name, &$errors = array()) {
        
        $errors = [];
        BimpObject::loadClass('bimpdatasync', 'BDS_Report');
        
        $host = BimpTools::getArrayValueFromPath($this->params, 'ftp_host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'ftp_login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'ftp_pwd', '');
        $port = BimpTools::getArrayValueFromPath($this->params, 'ftp_port', 21);
        $passive = (int) BimpTools::getArrayValueFromPath($this->params, 'ftp_passive', 0);;
        if (!$host) { $errors[] = 'Hôte absent'; }
        if (!$login) { $errors[] = 'Login absent'; }
        if (!$pword) { $errors[] = 'Mot de passe absent'; }
        
        if(!count($errors)) {
            $ftp = $this->ftpConnect($host, $login, $pword, $port, $passive, $errors);
            if ($ftp !== false) { 
                $this->Info("Connexion réussie", $this, "FTP");
                $on_ftp_ldlc = ftp_nlist($ftp, $this->params['ftp_dir']);
                $local_folder = DIR_SYNCH_COMPTA . 'exportCegid/BY_DATE/';
                $this->Info($local_folder, $this, "LOCAL");
                $scanned_directory = array_diff(scandir($local_folder), array('..', '.', 'imported', 'imported_auto'));
                $this->Info(implode("<br />", $on_ftp_ldlc), $this, "FICHIERS DISTANT AVANT TRANSFERT");
                $this->Info(implode("<br />", $scanned_directory), $this, "FICHIERS LOCAUX AVANT TRANSFERT");
                foreach($scanned_directory as $file) {
                    if(!in_array($this->params['ftp_dir'] . $file, $on_ftp_ldlc)) {
                        $this->Info("Fichier <b>" . $file . "</b> est OK pour le transfert", $this, "TRANSFERT");
                        if(ftp_put($ftp, $this->params['ftp_dir'] . $file, $local_folder . $file, FTP_ASCII)) {
                            $this->Success("Fichier <b>".$file."</b> (". filesize($local_folder . $file)." o) transféré avec succès sur le FTP de cégid le " . date('d/m/Y'), $this, "TRANSFERT");
                            $file_from = $local_folder . $file;
                            $file_to = $local_folder . 'imported_auto/' . $file;
                            if(copy($file_from, $file_to)) {
                                $this->Success("Fichier <b>".$file."</b> copié avec succès dans le dossier imported_auto le " . date('d/m/Y'), $this, "COPY");
                                if(unlink($local_folder . $file)) {
                                    $this->Success("Fichier <b>".$file."</b> supprimé avec succès du dossier local le " . date('d/m/Y'), $this, "COPY");
                                } else {
                                    $this->Alert("Fichier <b>".$file."</b> non supprimé du dossier local", $this, "COPY");
                                }
                            } else {
                                $this->Alert("Fichier <b>".$file."</b> non copié dans le dossier imported_auto", $this, "COPY");
                            }
                        } else {
                            $this->Alert("Fichier <b>".$file."</b> non transféré", $this, "TRANSFERT");
                        }
                    } else {
                        $this->Alert("Fichier <b>".$file."</b> non transféré car déjà présent sur le FTP de cégid", $this, "TRANSFERT");
                    }
                }
            }
        }
        
        return $errors;
    }
    
    public function maFonction() {
        
    }

}