<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportProcess.php');

require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');

class BDS_ImportsCalendarProcess extends BDSImportProcess {
    
//    DELETE FROM llx_actioncomm WHERE id >  	4530150;
//DELETE FROM llx_actioncomm_resources WHERE fk_actioncomm > 4530150
    
    const LOGIN = 'LOGIN';
    const DATE  = 'DTE';
    const ABS   = 'ABS_NBJ';
    const DEB   = 'ABS_HDEB';
    const FIN   = 'ABS_HFIN';
    
    public static $keys = array(
        self::LOGIN => self::LOGIN,
        self::DATE  => self::DATE,
        self::ABS   => self::ABS,
        self::DEB   => self::DEB,
        self::FIN   => self::FIN
    );
    
    public function getFileData($file_name, $keys, &$errors = array()/*, $headerRowIdx = -1, $firstDataRowIdx = 0, $params = array()*/) {

        if (!$file_name) {
            $errors[] = 'Nom du fichier absent';
            return array();
        }
        

        if (!file_exists($this->local_dir . $file_name)) {
            $errors[] = BimpRender::renderAlerts('Aucun fichier ' . $file_name . ' trouvé dans le dossier "' . $this->local_dir . '"');
            return array();
        }

        $file = $this->local_dir . $file_name;

        $file_errors = array();
        $rows = $this->getCsvFileDataByKeys($file, $keys, $file_errors, $this->params['delimiter']);


        if (count($file_errors)) {
            $errors = array_merge($errors, $file_errors);
        }
        
        return $rows;
        
    }

    public function initUpdateCalendar(&$data, &$errors = array()) {
        $data['steps'] = array();

//        if (isset($this->options['file_to_upload']) && (string) $this->options['file_to_upload']) {
            $data['steps']['get_data_from_file'] = array(
                'label'    => 'Création des évènement dans le calendrier',
                'on_error' => 'stop'
            );
            
//        }
    }

    public function executeUpdateCalendar($step_name, &$errors = array()) {

        $result = array();

        switch ($step_name) {
            case 'get_data_from_file':
                
                $file_errors = array();
                
                $file = BimpTools::getArrayValueFromPath($this->params, 'path_local_file', '');
                
                // Choisi dans l'input (exécution manuelle)
                if(isset($this->options['file_to_upload']) and (string) $this->options['file_to_upload'])
                    copy($this->options['file_to_upload'], $file);
                
                // Retélécharger le fichier
                elseif(isset($this->options['re_download_file']) and (int) $this->options['re_download_file']) {
                    
                    $this->downloadFtpFile(BimpTools::getArrayValueFromPath($this->params, 'remote_filename', ''), $errors);
                                        
                }
                
                if(!file_exists($file)) {
                    $this->Error("Fichier " . $file . " introuvable.");
                    break;
                }

                $rows_calendar = $this->getFileData($file, self::$keys,  $file_errors);

                if (count($file_errors))
                    $errors = array_merge($errors, $file_errors);

                
                if(!empty($rows_calendar)) {
                    
                    $errors = BimpTools::merge_array($errors, $this->createEvents($rows_calendar));
                                       
                    
                } else {
                    $this->Info("Aucune ligne trouvée");
                }
                break;
            
            default:
                $errors[] = 'Étape inconnue ' . $step_name;
                break;
        }


        return $result;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array()) {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsCalendar',
                    'title'       => 'Imports calendrier',
                    'description' => 'Importe les évènements du calendrier et les congés envoyés par LDLC',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            
            // Params: FTP
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
                'value'      => '/FTP-BIMP-ERP/innovpro/'
                    ), true, $warnings, $warnings);
            
            // Params: Calendrier
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'path_local_file',
                'label'      => 'Adresse du fichier local',
                'value'      => PATH_TMP . '/bimpdatasync/imports/import_agenda.txt'
                    ), true, $warnings, $warnings);
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'remote_filename',
                'label'      => 'Nom du fichier distant',
                'value'      => 'PLG_OLYS.csv'
                    ), true, $warnings, $warnings);
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name' => 'delimiter',
                'label' => 'Délimiteur',
                'value' => ';'
                    ), true, $warnings, $warnings);

            // Options: 
            $opt1 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process' => (int) $process->id,
                        'label' => 'Fichier à importer',
                        'name' => 'file_to_upload',
                        'info' => "Si un fichier est renseigné, les paramètres \"Adresse du fichier distant\""
                . " et \"Adresse du fichier distant\" seront ignoré.",
                        'type' => 'file',
                        'default_value' => '',
                        'required' => 0
                            ), true, $warnings, $warnings);
            
            $opt2 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Rétélécharger le fichier',
                        'name'          => 're_download_file',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);


            // Opérations: FTP
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                'id_process'  => (int) $process->id,
                'title'       => 'Test de connection FTP',
                'name'        => 'ftp_test',
                'description' => '',
                'warning'     => '',
                'active'      => 1,
                'use_report'  => 0
                    ), true, $warnings, $warnings);
            
            // Opérations: Import
            $operation = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process' => (int) $process->id,
                        'title' => 'Mise à jour du calendrier',
                        'name' => 'updateCalendar',
                        'description' => '',
                        'warning' => '',
                        'active' => 1,
                        'use_report' => 1,
                        'reports_delay' => 30
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($operation)) {
                $warnings = array_merge($warnings, $operation->addAssociates('options', array($opt1->id, $opt2->id)));
            }
//
//                // Crons:
//
//                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessCron', array(
//                    'id_process'   => (int) $process->id,
//                    'id_operation' => (int) $op->id,
//                    'title'        => 'Màj Prix/Stocks LDLC',
//                    'active'       => 0,
//                    'freq_val'     => '1',
//                    'freq_type'    => 'week',
//                    'start'        => date('Y-m-d H:i:s')
//                        ), true, $warnings, $warnings);
//            }
        }
    }
    
    private function getUserArray() {
        
        $users = array();
        
        $sql = BimpTools::getSqlSelect(array('rowid', 'login'));
        $sql .= BimpTools::getSqlFrom('user');
        $rows = BimpObject::getBdb()->executeS($sql, 'array');
        
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $users[$this->stripAccents($r['login'])] = $r['rowid'];
            }
        }

        return $users;
    }
    
    private function stripAccents($str) {
    return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}
    
    public function createEvents($rows_calendar) {
        
        $errors = array();
        
        global $user;
        $user_create = $user;
        $line = 1;
        $ignorer = 0;
        $doublon_absence = 0;
                
        $users = $this->getUserArray();
           
        foreach($rows_calendar as $r) {
            
            $line++;
            
            $err = "";
            
            if(!isset($r[self::ABS]) or (float) $r[self::ABS] < 0)
                $err .= "Quantité de congé mal ou non renseigné " .  $r[self::ABS] . "<br/>";
            
            if(!isset($r[self::LOGIN]) or !is_string($r[self::LOGIN]))
                $err .= "Login mal ou non renseigné" . $r[self::LOGIN] . "<br/>";
            
            if(!isset($r[self::DATE]))
                $err .= "Date de congé non renseigné<br/>";
            
            
            if($err != '') {
                $this->Error($err . " ligne " .  $line);
                continue;
            }
                
            
            // Absences
            if(0 < $r[self::ABS]) {
                
                $d_p= explode('/', $r[self::DATE]);                
                
                // Date start
                if($r[self::DEB] != '00:00' and $r[self::DEB] != '')
                    $date1 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' ' . $r[self::DEB] . ':00');
                else
                    $date1 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' 08:00:00');
                $date_start = $date1->format('U');
                
                // Date end
                if($r[self::FIN] != '00:00' and $r[self::FIN] != '')
                    $date2 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' ' . $r[self::FIN] . ':00');
                else
                    $date2 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' 20:00:00');
                $date_end = $date2->format('U');
                
                
                $l_user = BimpCache::getBimpObjectList('bimpcore', 'Bimp_User', array('login' => $r[self::LOGIN]));
                if(empty($l_user)) {
                    $this->Error("Login inconnu :" . $r[self::LOGIN] . " ligne " . $line);
                    continue;
                }
                
                if((int)$users[$r[self::LOGIN]] > 0) {
                    $sql = 'SELECT id, datep, datep2, code, label';
                    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
                    $sql .= ' WHERE (datep  BETWEEN "' . $this->db->db->idate($date_start) . '" and "' . $this->db->db->idate($date_end) . '"';
                    $sql .= ' OR    datep2 BETWEEN "' . $this->db->db->idate($date_start) . '" and "' . $this->db->db->idate($date_end) . '")';
                    $sql .= ' AND label="Absence"';
                    $sql .= ' AND fk_user_action=' . $users[$r[self::LOGIN]];
                    $result = $this->db->db->query($sql);

                    if ($result && $this->db->db->num_rows($result)) {
                        $obj = $result->fetch_object();
                        $action = new ActionComm($this->db->db);

                        $action->fetch($obj->id);
                        $this->Alert("Création d'absence loqué par " . $action->getNomUrl(), $action, $r[self::LOGIN]);
                        $doublon_absence++;
                        continue;
                    }
                } else {
                    $this->Error(" Login inconnu " . $r[self::LOGIN] . '. Entrée du ' 
                            . $date1->format('d/m/Y H:i:s') . ' au ' .
                            $date2->format('d/m/Y H:i:s') . ' ignorée', null, $r[self::LOGIN]);
                    continue;
                }
                
                $id_user = $l_user[0];
                
                $ac = new ActionComm($this->db->db);
                $ac->userownerid = $id_user;
                $ac->type_code = 'CONGES';
                $ac->datep = $date_start;
                $ac->datef = $date_end;
                $ac->label = "Absence";
                $ac->percentage = -1;

                $ac->create($user_create);
                
                
                if(empty($ac->errors) and $ac->error == '') {
                    $this->incCreated();
                    $this->Success("Crée " . $r[self::LOGIN] . ' du ' 
                            . $date1->format('d/m/Y H:i:s') . ' au ' .
                            $date2->format('d/m/Y H:i:s') . ' ' . $ac->getNomUrl(), $ac, $r[self::LOGIN]);
                } else {
                    $errors = BimpTools::merge_array($errors, $ac->errors);
                    $errors[] = $ac->error;
                    $this->Error($ac->error . $line . print_r($ac->errors, 1));
                }
        
                
            } else {
                $ignorer++;
            }
            
        }
        
        if($ignorer != 0) {
            $this->Info("Nombre de lignes ignorées (sans abscences): " . $ignorer);
        }
        
        if($doublon_absence != 0) {
            $this->Info("Nombre de lignes ignorées (déjà renseignée): " . $doublon_absence);
        }
        
        return $errors;
        
    }
    
    // Fonction de BDSImportFournCatalogProcess
    public function initFtpTest(&$data, &$errors = array())
    {
        BimpObject::loadClass('bimpdatasync', 'BDS_Report');

        $host = BimpTools::getArrayValueFromPath($this->params, 'ftp_host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'ftp_login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'ftp_pwd', '');
        $port = BimpTools::getArrayValueFromPath($this->params, 'ftp_port', 21);
        $passive = (int) BimpTools::getArrayValueFromPath($this->params, 'ftp_passive', 0);

        if (!$host) {
            $errors[] = 'Hôte absent';
        }

        if (!$login) {
            $errors[] = 'Login absent';
        }

        if (!$pword) {
            $errors[] = 'Mot de passe absent';
        }

        if (!count($errors)) {
            $ftp = $this->ftpConnect($host, $login, $pword, $port, $passive, $errors);

            if ($ftp !== false) {
                $data['result_html'] = BimpRender::renderAlerts('Connection FTP réussie', 'success');
            }
        }
    }
    
    public function downloadFtpFile($fileName, &$errors = array(), $mode = FTP_BINARY)
    {
        $ftp_dir = BimpTools::getArrayValueFromPath($this->params, 'ftp_dir', '');
        $local_file_path = BimpTools::getArrayValueFromPath($this->params, 'path_local_file', '');
        $host = BimpTools::getArrayValueFromPath($this->params, 'ftp_host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'ftp_login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'ftp_pwd', '');
        $port = BimpTools::getArrayValueFromPath($this->params, 'ftp_port', 21);
        $passive = ((int) BimpTools::getArrayValueFromPath($this->params, 'ftp_passive', 0) ? true : false);

        if (!$host) {
            $errors[] = 'Hôte absent';
        }

        if (!$login) {
            $errors[] = 'Login absent';
        }

        if (!$pword) {
            $errors[] = 'Mot de passe absent';
        }

        if (!is_dir(dirname($local_file_path))) {
            $errors[] = 'Le dossier local "' . dirname($local_file_path) . '" n\'existe pas';
        }

        $check = false;

        if (!count($errors)) {
            $ftp = $this->ftpConnect($host, $login, $pword, $port, $passive, $errors);

            if ($ftp !== false && !count($errors)) {
                if ($this->options['debug']) {
                    error_reporting(E_ALL);
                }

                $this->Msg('DOSSIER FTP: ' . $ftp_dir);
                $files = ftp_nlist($ftp, '/');
//
                $this->DebugData($files, 'LISTE FICHIERS FTP');
                
                
                $ftp_file_path = $ftp_dir . '/' . $fileName;

                if (ftp_get($ftp, $local_file_path, $ftp_file_path, $mode)) {
                    $this->Success('Téléchargement du fichier "' . $fileName . '" OK', null, $fileName);
                    $check = true;
                }

                if ($this->options['debug']) {
                    error_reporting(E_ERROR);
                }

                if (!$check) {
                    $errors[] = 'Echec du téléchargement du fichier "' . $ftp_file_path . '" dans "'.$local_file_path.'"';
                }
            }
        }

        return $check;
    }    
}
