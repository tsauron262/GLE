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
        
        $success_callback = 'bn.notificationActive.notif_task.obj.remove(' . $this->id . ')';
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionAttribute($data, &$success)
    {
        global $user;
        $errors = $warnings = array();
        
        $this->updateField("id_user_owner", $data['id_user_owner']);

        $instance_task = 'bn.notificationActive.notif_task.obj';
        
        if ($data['id_user_owner'] > 0) {
            $success = "Attribué";
            // Attribuée à l'utilisateur courant
            if((int) $data['id_user_owner'] == $user->id)
                $success_callback = $instance_task . '.move(' . $this->id . ', ' . $this->getData('prio') . ', ' . $this->getData('id_user_owner') . ')';
            // Attribuée à quelqu'un d'autre
            else
                $success_callback = $instance_task . '.remove(' . $this->id . ')';
   
        } else {
            $success = "Désattribué";
            if($this->canView())
                $success_callback = $instance_task . '.move(' . $this->id . ', ' . $this->getData('prio') . ', ' . $this->getData('id_user_owner') . ')';
            else
                $success_callback = $instance_task . '.remove(' . $this->id . ')';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => $success_callback
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
    
    public function getTaskForUser($id_user, $id_max, &$errors = array()) {
        
        $tasks = array();
        $nb_my = 0;
        $nb_unaffected = 0;
        
        
        $tasks['content'] = BimpTools::merge_array(
            // Tâches affectées à l'utilisateur actuel        
            self::getNewTasks(array(
                'id' => array(
                    'operator' => '>',
                    'value' => $id_max
                ),
                'id_user_owner' => (int) $id_user,
                'status' => array(
                    'operator' => '<',
                    'value' => 4
            )), 'my_task', $nb_my),
        
            // Tâches non affectées
            self::getNewTasks(array(
                'id' => array(
                    'operator' => '>',
                    'value' => $id_max
                ),
                'id_user_owner' => 0,
                'status' => array(
                    'operator' => '<',
                    'value' => 4
                )
            ), 'unaffected_task', $nb_unaffected
        ));
        
        $tasks['nb_my'] = $nb_my;
        $tasks['unaffected_task'] = $nb_unaffected;
        return $tasks;
    }
    
    private static function getNewTasks($filters, $user_type, &$nb) {
        
        $tasks = array();
        
        $max_task_view = 25;
        $i = $j = 0;
        
        $sql = BimpTools::getSqlSelect(array('id'));
        $sql .= BimpTools::getSqlFrom('bimp_task');
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy('prio', 'DESC', 'a', 'id', 'DESC');

        $rows = self::getBdb()->executeS($sql, 'array');
        
        if (is_array($rows) and count($rows)) {
            $nb = count($rows);
            foreach ($rows as $r) {
                $task = BimpCache::getBimpObjectInstance('bimptask', 'BIMP_Task', (int) $r['id']);
                if (BimpObject::objectLoaded($task))
                    $l_tasks_user[] = $task;
            }
        }

        
        foreach ($l_tasks_user as $t) {
            if ($t->can('view')) {
                if ($j < $max_task_view) {
                    
                    $notes = $t->getNotes();
                    $not_viewed = 0;
                    foreach ($notes as $note) {
                        if (!$note->getData('viewed'))
                            $not_viewed++;
                    }
                    
                    $task = array(
                        'id'            => $t->getData('id'),
                        'user_type'     => $user_type,
                        'prio'          => $t->getData('prio'),
                        'subj'          => $t->getData('subj'),
                        'src'           => $t->getData('src'),
//                        'txt' => $t->getData("txt"),
                        'txt'           => dol_trunc($t->getData('txt'), 60),
                        'date_create'   => $t->getData('date_create'),
                        'url'           => DOL_URL_ROOT . '/bimptask/index.php?fc=task&id=' . $t->getData('id'),
                        'not_viewed'    => (int) $not_viewed,
                        'can_rep_mail'  => (int) ($t->can('edit') and filter_var($t->getData('src'), FILTER_VALIDATE_EMAIL) and filter_var($t->getData('dst'), FILTER_VALIDATE_EMAIL)),
                        'can_close'     => (int) $t->can('edit'),
                        'can_attribute' => (int) ($t->can('edit') or $t->canAttribute())
                            );

                    $tasks[] = $task;

                    $i++;
                }
                $j++;
            }
        }

        return $tasks;
    }
}
