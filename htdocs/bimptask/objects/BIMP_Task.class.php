<?php

class BIMP_Task extends BimpObject
{

    public static $valSrc = array('task0001@bimp-groupe.net' => 'tache test', 
        'validationcommande@bimp-groupe.net' => "Validation commande", 
        'Synchro-8SENS' => "Synchro-8SENS", 
        'supportyesss@bimp-groupe.net' => "Support YESS",
        'supportcogedim@bimp-groupe.net' => "Support COGEDIM", 
        'hotline@bimp-groupe.net'       => 'Hotline',
        'consoles@bimp-groupe.net' => "CONSOLES", 
        'licences@bimp-groupe.net' => "LICENCES", 
        'vols@bimp-groupe.net' => "VOLS", 
        'sms-apple@bimp-groupe.net' => "Code APPLE", 
        'suivicontrat@bimp-groupe.net' => "Suivi contrat", 
        'other' => 'AUTRE');
    public static $srcNotAttribute = array('sms-apple@bimp-groupe.net');
    public static $nbNonLu = 0;
    public static $nbAlert = 0;
    public static $valStatus = array(0 => array('label' => "En cours", 'classes' => array('error')), 4 => array('label' => "Terminé", 'classes' => array('info')));
    public static $valPrio = array(0 => array('label' => "Normal", 'classes' => array('info')), 20 => array('label' => "Urgent", 'classes' => array('error')));

    public function areNotesEditable()
    {
        return ($this->can("edit") && $this->isEditable());
    }

    public function fetch($id, $parent = null)
    {
        $errors = parent::fetch($id, $parent);

        $test = $this->getData("test_ferme");
        if ($test != "" && $this->getData("status") != 4) {
            $tabTest = explode(":", $test);
            if (count($tabTest) == 2) {
                $sql = $this->db->db->query("SELECT * FROM " . MAIN_DB_PREFIX . $tabTest[0] . " WHERE " . $tabTest[1]);
                if ($this->db->db->num_rows($sql) > 0) {
                    $inut = "";
                    $this->actionClose(array(), $inut);
                }
            }
        }
        return $errors;
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        return $html;
    }

    public function createIfNotActif()
    {
        $tasks = $this->getList(array('dst' => $this->getData('dst'), 'src' => $this->getData('src'), 'subj' => $this->getData('subj'), 'txt' => $this->getData('txt'), 'prio' => $this->getData('prio'), 'status' => 0));
        if (count($tasks) == 0)
            parent::create();
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $return = parent::create($warnings, $force_create);
        
        $this->updateField('date_update', $this->getData('date_create'));
        
        return $return;
    }

    public function renderLight()
    {
        $html = "";

        $class = array();
        if ($this->getData("prio") == 20) {
            $class[] = 'clignote';
            self::$nbAlert++;
        }

        $html .= "<a class='" . implode(" ", $class) . "'  href='" . DOL_URL_ROOT . "/bimptask/index.php?fc=task&id=" . $this->id . "'>";
        $html .= $this->getData("subj") . ' de "' . $this->getData("src") . '" ' . dol_trunc($this->getData("txt"));
        $html .= "</a>";

        $notes = $this->getNotes();
        $notViewed = 0;
        foreach ($notes as $note)
            if (!$note->getData("viewed")) {
                $notViewed++;
                self::$nbNonLu++;
            }
        $butViewShort = array();
        if (count($notes))
            $butViewShort[] = array("onclick" => "loadModalView('bimptask', 'BIMP_Task', " . $this->id . ", 'notes', $(this), 'Infos')", "icon" => "fas fa-comments", "labelShort" => count($notes) . " Info(s)" . ($notViewed ? " " . $notViewed . " Non lue(s)" : ""), "class" => ($notViewed == 0 ? "btn-default" : "btn-danger"));
        foreach ($butViewShort as $btn) {
            $html .= '<button class="btn  ' . $btn['class'] . '" type="button" onclick="' . $btn["onclick"] . '"><i class="fa fa-' . $btn['icon'] . ' iconLeft"></i>' . (isset($btn['labelShort']) ? $btn['labelShort'] : $btn['label']) . '</button>';
        }
        foreach ($this->getButtons() as $btn) {
            $html .= '<button class="btn btn-default" type="button" onclick="' . $btn["onclick"] . '"><i class="fa fa-' . $btn['icon'] . ' iconLeft"></i>' . (isset($btn['labelShort']) ? $btn['labelShort'] : $btn['label']) . '</button>';
        }

        return $html;
    }

    public static function getPrio_list_taskArray()
    {
        return self::$valPrio;
    }
    
    public function getType(){
        $d = $this->getData("dst");
        $type = 'other';
        if(isset(self::$valSrc[$d]))
            $type = $d;
        return $type;
    }
    
    public function displayType(){
        return self::$valSrc[$this->getType()];
    }
    

    public function getTypeTacheSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        global $user;
        if($value != 'other')
            $filters['dst'] = $value;
        else{
            $tabsDroit =  self::getTableSqlDroitPasDroit($user);
//            print_r($tabsDroit);die;
            $filters['dst'] = array("not_in"=> $tabsDroit[2]);
        }
        
        return self::$valStatus;
    }
    

    public static function getStatus_list_taskArray()
    {
        return self::$valStatus;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return ($this->getInitData("status") < 4);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable($force_delete, $errors);
    }

    public function getRight($right)
    {
        global $user;
        if ($this->getData("id_user_owner") == $user->id)
            return 1;
        $classRight = $this->getType();
        
        return $user->rights->bimptask->$classRight->$right;
    }

    public function getListFiltre($type = "normal")
    {
        global $user;
        $list = new BC_ListTable($this, 'default', 1, null, ($type == "my" ? 'Mes tâches assignées' : 'Toutes les tâches'));
        $tabFiltre = self::getFiltreDstRight($user);
        if (count($tabFiltre[1]) > 0)
            $list->addFieldFilterValue('fgdg_dst', array(
                ($type == "my" ? 'and_fields' : 'or') => array(
                    'dst'           => array(
                        $tabFiltre[0] => $tabFiltre[1]
                    ),
                    'id_user_owner' => $user->id
                )
            ));
        elseif ($type == "my")
            $list->addFieldFilterValue('id_user_owner', (int) $user->id);
        return $list;
    }

    protected function canView()
    {
        global $user;
        if ($this->isNotLoaded())
//            foreach (self::$valSrc as $src => $nom)
//                if ($user->rights->bimptask->$src->read)
                    return 1;

        return $this->getRight("read");
    }

    protected function canEdit()
    {
        return $this->getRight("write");
    }

    public function canDelete()
    {
        return $this->getRight("delete");
    }

    public function canAttribute()
    {
        return $this->getRight("attribute");
    }

    public function canEditField($field_name)
    {
        if ($field_name == "id_user_owner")
            return $this->canAttribute();
        return parent::canEditField($field_name);
    }
    
    public static function getTableSqlDroitPasDroit($user){
        $tabDroit = $tabPasDroit = $tabTous = array();
        foreach (self::$valSrc as $src => $nom) {
            if ($src != "other") {
                if ($user->rights->bimptask->$src->read)
                    $tabDroit[] = '"' . $src . '"';
                else
                    $tabPasDroit[] = '"' . $src . '"';
                $tabTous[] = '"' . $src . '"';
                
            }
        }
        return array($tabDroit,$tabPasDroit, $tabTous);
    }

    public static function getFiltreDstRight($user)
    {
        $tabT = self::getTableSqlDroitPasDroit($user);
        if ($user->rights->bimptask->other->read)
            return array("not_in", $tabT[1]);
        else
            return array("in", $tabT[0]);
    }

    public function actionSendMail($data, &$success)
    {
        $success = "Message envoyé";
        $errors = $warnings = array();
        $sep = "<br/>---------------------<br/>";
        $idTask = "IDTASK:5467856456" . $this->getData("id");
        $data['email'] = str_replace("<br>", "<br/>", $data['email']);


        $notes = $this->getNotes();

        $msg = $data['email'];


        $msg .= "<br/>" . $sep . "Merci d'inclure ces lignes dans les prochaines conversations<br/>" . $idTask . $sep;

        if ($data['include_file']) {
            $msg .= "<br/>Fil de discussion :";
            foreach ($notes as $note) {
                $msg .= $sep;
                $msg .= $note->getData("content");
            }

            $msg .= $sep . "Message original :";
            $msg .= $sep;
            $msg .= $this->getData("txt");
        }

        $sujet = "Re:" . $this->getData("subj");
        $to = $this->getData("src");
        $from = $this->getData("dst");

//        $msg = str_replace("<br />", "\n", $msg);
//        $msg = str_replace("<br/>", "\n", $msg);
//        $msg = str_replace("<br/>", "\n", $msg);


        if (!mailSyn2($sujet, $to, $from, $msg))
            $errors[] = "Envoi email impossible";
        else {
            $this->addNote($data['email'], 4, 1);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function afterCreateNote($note){
            $this->updateField ('date_update', $note->getData('date_create'));
    }

    public function actionClose($data, &$success)
    {
        $errors = $warnings = array();
        $success = "Tâche fermée";
        $errors = $this->updateField("status", 4);
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAttribute($data, &$success)
    {
        $errors = $warnings = array();
        if ($data['id_user_owner'] > 0)
            $success = "Attribué";
        else
            $success = "Désattribué";
        $this->updateField("id_user_owner", $data['id_user_owner']);
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function getInfosExtraBtn()
    {
        return $this->getButtons();
    }

    public function getButtons()
    {
        global $user;
        $buttons = array();
        if ($this->isEditable() && !in_array($this->getType(),self::$srcNotAttribute)) {
            if ($this->can("edit")) {
                if (filter_var($this->getData("src"), FILTER_VALIDATE_EMAIL) && filter_var($this->getData("dst"), FILTER_VALIDATE_EMAIL))
                    $buttons[] = array(
                        'label'      => 'Répondre par mail',
                        'labelShort' => 'Rep Mail',
                        'icon'       => 'send',
                        'onclick'    => $this->getJsActionOnclick('sendMail', array(), array('form_name' => 'newMail'))
                    );
                $buttons[] = array(
                    'label'      => 'Classer terminé',
                    'labelShort' => 'Terminer',
                    'icon'       => 'close',
                    'onclick'    => $this->getJsActionOnclick('close', array(), array('confirm_msg' => 'Terminer la tâche ?'))
                );
            }
            if ($this->can("edit") || $this->canAttribute()) {
                if ($this->getData("id_user_owner") < 1) {
                    $buttons[] = array(
                        'label'   => 'Attribuer',
                        'icon'    => 'user',
                        'onclick' => $this->getJsActionOnclick('attribute', array(), array('form_name' => 'attribute'))
                    );
                }
                if ($this->getData("id_user_owner") == $user->id) {
                    $buttons[] = array(
                        'label'   => 'Refuser l\'attribution',
                        'icon'    => 'window-close',
                        'onclick' => $this->getJsActionOnclick('attribute', array('id_user_owner' => 0), array('confirm_msg' => "Refuser l\'attribution ?"))
                    );
                }
            }
        }
        return $buttons;
    }
    
    public function getFilesListExtraBtn(BimpFile $file)
    {
        $buttons = array();
        
        $buttons[] = array(
            'label' => 'Déplacer',
            'icon' => 'fas_file-export',
            'onclick' => $this->getJsActionOnclick('moveFile', array(
                'id_file' => $file->id,
                'move_to_object_name' => 'client',
                'keep_copy' => 0,
                'create_link' => 1
            ), array(
                'form_name' => 'move_file'
            ))
        );
        
        return  $buttons;
    }
    
    public function getTaskForUser($id_user, $max_task_view) {
        
        $tasks = array();
        

        $alert = false;
        $max_task_view = 25;
        $i = $j = 0;
        BIMP_Task::$nbNonLu = 0;
        BIMP_Task::$nbAlert = 0;
        
        $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

        // Tâches affectées à l'utilisateur actuel
        $l_tasks_user = $task->getList(array('id_user_owner' => (int) $id_user, 'status' => array(
                'operator' => '<',
                'value' => 4
        )), null, null,'date_update');

        foreach ($l_tasks_user as $t) {
            $task->fetch($t["id"]);
            if ($task->can("view")) {
                if ($j < $max_task_view) {
                    $content .= $task->renderLight();
                    $content .= "<br/>";
                    $i++;
                }
                $tasks['content'][] = $t;
                $j++;
            }
        }
        
        $class = array();
        if (BIMP_Task::$nbAlert > 0) {
            $class[] = 'clignote';
            $alert = true;
        }

        $nonLu1 = BIMP_Task::$nbNonLu;





        
        // Tâches non affectées
        $content2 = "";
        $l_tasks_unaffected = $task->getList(array('id_user_owner' => 0,
            'status' => array(
                'operator' => '<',
                'value' => 4
        )), null, null,'date_update');

        $i2 = $j2 = 0;
        BIMP_Task::$nbNonLu = 0;
        BIMP_Task::$nbAlert = 0;
        foreach ($l_tasks_unaffected as $taskData) {
            $task->fetch($taskData["id"]);
            if ($task->can("view")) {
                if ($j2 < $max_task_view) {
                    $content2 .= $task->renderLight();
                    $content2 .= "<br/>";
                    $i2++;
                }
                
                $j2++;
            }
        }

        $class2 = array();
        if (BIMP_Task::$nbAlert > 0) {
            $class2[] = 'clignote';
            $alert = true;
        }

        if ($alert) {
            $contentT .= "<script>playAlert();</script>";
        } else {
            $contentT .= "<script>stopAlert();</script>";
        }

        if ($i2 > 0)
            $content2 .= $contentT;
        else
            $content .= $contentT;
        $nonLu2 = BIMP_Task::$nbNonLu;


        if ($i > 0)
            $this->bimp_fixe_tabs->addTab("mYtask", "<span class='" . implode(" ", $class) . "' >" . $i . (($j != $i) ? " / " . $j : "") . " tâche(s) en attente" . ($nonLu1 > 0 ? " <span class='red'>" . $nonLu1 . " message" . ($nonLu1 > 1 ? 's' : '') . " non lu" . ($nonLu1 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);

        if ($i2 > 0)
            $this->bimp_fixe_tabs->addTab("taskAPersonne", "<span class='" . implode(" ", $class2) . "' >" . $i2 . (($j2 != $i2) ? " / " . $j2 : "") . " tâche" . ($i2 > 1 ? 's' : '') . " non attribuée" . ($i2 > 1 ? 's' : '') . ($nonLu2 > 0 ? " <span class='red'>" . $nonLu2 . " message" . ($nonLu2 > 1 ? 's' : '') . " non lu" . ($nonLu2 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content2);


        
        return $tasks;
    }
}
