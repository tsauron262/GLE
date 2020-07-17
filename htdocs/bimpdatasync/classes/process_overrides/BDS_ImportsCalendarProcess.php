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
        $rows = $this->getCsvFileDataByKeys($file, $keys, $file_errors, $this->params['delimiter']/*, $headerRowIdx, $firstDataRowIdx, $params*/);


        if (count($file_errors)) {
            $errors = array_merge($errors, $file_errors);
        }
        
        return $rows;
        
    }

    public function initUpdateCalendar(&$data, &$errors = array()) {
        $data['steps'] = array();
        
        // TODO Check de la précédante MAJ soit inférieur à celle prévu
        // Ou interdire exec manuelle

//        if (isset($this->options['file_to_upload']) && (string) $this->options['file_to_upload']) {
            $data['steps']['get_data_from_file'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'stop'
            );
            
//        }
    }

    public function executeUpdateCalendar($step_name, &$errors = array()) {

        $result = array();

        switch ($step_name) {
            case 'get_data_from_file':
                
                $file_errors = array();
                
                
                if(isset($this->options['file_to_upload']) and (string) $this->options['file_to_upload'])
                    $file = $this->options['file_to_upload'];
                elseif(isset($this->options['re_download_file']) and (int) $this->options['re_download_file'])
                    $file = $this->params['path_remote_file'];
                else
                    $file = $this->params['path_local_file'];

                $rows_calendar = $this->getFileData($file, self::$keys,  $file_errors);
                
//                echo '<pre>';
//                print_r($rows_calendar);
//                die();

                if (count($file_errors))
                    $errors = array_merge($errors, $file_errors);

                
                if(!empty($rows_calendar)) {
                    
                    $errors = BimpTools::merge_array($errors, $this->createEvents($rows_calendar));
                                       
                    
                } else {
                    $this->Info("Aucune ligne trouvée");
                }
                break;
            
                //                    $result['new_steps'] = array(
//                        'create_holiday' => array(
//                            'label'    => 'Création des jours de congé',
//                            'on_error' => 'continue',
////                            'rows'     => $this->file_rows
//                        )
//                    );
            case 'create_holiday':
                
//                global $rows_calendar;
//
//                print_r($rows_calendar);
//                
//                if(is_array($rows_calendar) and !empty($rows_calendar)) {
//                    
//                    
//                    foreach($rows_calendar as $ok) {
//                        print_r($ok);
//                    }
//                    die();
//                } else {
//                    echo 'pas cool dzadz';
//                    print_r($rows_calendar);
//                    die();
//                }
                
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

            // Params: 
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'path_local_file',
                'label'      => 'Adresse du fichier local',
                'value'      => DOL_DOCUMENT_ROOT . '/bimpdatasync/imports/import_agenda.txt'
                    ), true, $warnings, $warnings);
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'path_remote_file',
                'label'      => 'Adresse du fichier distant',
                'value'      => 'localhost/bimp-erp_old/import_agenda.txt'
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
                        'info' => "Si un fichier est renseigné, l'option \"Retélécharger le fichier\" sera ignorée",
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


            // Opérations: 
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
    
    public function createEvents($rows_calendar) {
        
        $errors = array();
        
        global $user;
        $user_create = $user;
        $line = 1;
        
        foreach($rows_calendar as $r) {
            
            $line++;
            
            $err = "";
            
            if(!isset($r[self::ABS]) or (float) $r[self::ABS] < 0)
                $err .= "Quantité de congé mal ou non renseigné " .  $r[self::ABS] . "<br/>";
            
            if(!isset($r[self::LOGIN]) or !is_string($r[self::LOGIN]))
                $err .= "Login mal ou non renseigné" . [self::LOGIN] . "<br/>";
            
            if(!isset($r[self::DATE]))
                $err .= "Date de congé non renseigné<br/>";
            
            
            if($err != '') {
                $this->Error($err . " ligne " .  $line);
                continue;
            }
                
            
            // Absences
            if(0 < $r[self::ABS]) {
                
                $d_p = explode('/', $r[self::DATE]);
                
                // Date start
                if($r[self::DEB] != '00:00' and $r[self::DEB] != '')
                    $date1 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' ' . $r[self::DEB] . ':00');
                else
                    $date1 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' 00:00:00');
                $date_start = $date1->format('U');
                
                // Date end
                if($r[self::FIN] != '00:00' and $r[self::FIN] != '')
                    $date2 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' ' . $r[self::DEB] . ':00');
                else
                    $date2 = new DateTime('20' . $d_p[2] .'-' . $d_p[0] . '-' . $d_p[1] . ' 23:00:00');
                $date_end = $date2->format('U');
                
                $l_user = BimpCache::getBimpObjectList('bimpcore', 'Bimp_User', array('login' => $r[self::LOGIN]));
                if(empty($l_user)) {
                    $this->Error("Login inconnu :" . $r[self::LOGIN] . " ligne " . $line);
                    continue;
                }
                
                $id_user = $l_user[0];
                
                $ac = new ActionComm($this->db->db);
                $ac->userownerid = $id_user;
                $ac->type_code = 'CONGES';
                $ac->datep = $date_start;
                $ac->datef = $date_end;
                $ac->note = "Export LDLC";
                $ac->create($user_create);
                
                
                if(empty($ac->errors) and $ac->error == '')
                    $this->incCreated();
                else {
                    $errors = BimpTools::merge_array($errors, $ac->errors);
                    $errors[] = $ac->error;
                }
        
                
            }
            
        }
        
        return $errors;
        
    }

}
