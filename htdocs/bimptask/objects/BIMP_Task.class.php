<?php

class BIMP_Task extends BimpObject
{

    public static $valSrc = array('task0001@bimp-groupe.net'           => 'tache test',
        'validationcommande@bimp-groupe.net' => "Validation commande",
        'Synchro-8SENS'                      => "Synchro-8SENS",
        'supportyesss@bimp-groupe.net'       => "Support YESS",
        'supportcogedim@bimp-groupe.net'     => "Support COGEDIM",
        'hotline@bimp-groupe.net'            => 'Hotline',
        'consoles@bimp-groupe.net'           => "CONSOLES",
        'licences@bimp-groupe.net'           => "LICENCES",
        'vols@bimp-groupe.net'               => "VOLS",
        'sms-apple@bimp-groupe.net'          => "Code APPLE",
        'suivicontrat@bimp-groupe.net'       => "Suivi contrat",
        'facturation'                        => "Facturation",
        'dispatch@bimp.fr'                   => 'Dispatch',
        'suivicontrat@bimp.fr'               => 'Suivi contrat',
        'other'                              => 'Autre');
    public static $types_manuel = array(
        'dev'        => 'Développement',
//        'adminVente' => 'Administration des Ventes'
    );
    public static $srcNotAttribute = array('sms-apple@bimp-groupe.net');
    public static $nbNonLu = 0;
    public static $nbAlert = 0;
    public static $valStatus = array(0 => array('label' => "A traiter", 'classes' => array('danger')), 1 => array('label' => "En cours", 'classes' => array('important')), 2 => array('label' => "Attente utilisateur", 'classes' => array('danger')), 3 => array('label' => "Attente technique", 'classes' => array('danger')), 4 => array('label' => "Terminé", 'classes' => array('success')));
    public static $valPrio = array(0 => array('label' => "Normal", 'classes' => array('info')), 20 => array('label' => "Urgent", 'classes' => array('error')));
    const MARQEUR_MAIL = "IDTASK:5467856456";
    const ID_USER_DEF = 215;
    public $mailReponse = 'Tâche ERP<reponse@bimp-groupe.net>';
//    public $mailReponse = 'sms-apple@bimp-groupe.net';

//    public function areNotesEditable()
//    {
//        return ($this->can("edit") && $this->isEditable());
//    }

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
    
    public function notifier($subject, $message, $rappel = false){
        $mails = array();
        foreach($this->getUserNotif(true) as $userN){
            $mails[] = BimpTools::getMailOrSuperiorMail($userN->id);
        }
        $to = implode(',', $mails);
        
        
        $this->sendMail($to, $this->mailReponse, $subject, $message, $rappel);
    }
    
    public function getUserNotif($excludeMy = false){
        global $user;
        $users = array();
        $users[$this->getData('user_create')] = $this->getChildObject('user_create');
        BimpObject::loadClass('bimpcore', 'BimpLink');
        $users = BimpTools::merge_array($users, BimpLink::getUsersLinked($this), true);
        
        $notes = $this->getNotes();
        foreach($notes as $note)
            $users = BimpTools::merge_array($users, BimpLink::getUsersLinked($note), true);
        $parentTask = $this->getChildObject('task_mere');
        if($parentTask && $parentTask->isLoaded())
            $users = BimpTools::merge_array($users, $parentTask->getUserNotif($excludeMy), true);
        
      if($excludeMy)
          unset($users[$user->id]);
//            echo '<pre>';  print_r($users);
        return $users;
    }
    
    public function displayUserNotif(){
        $html = '';
        $users = $this->getUserNotif();
        foreach($users as $user)
            $html .= $user->getLink().'<br/>';
        
        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        if($this->asFilleEnCours())
            return ' (action en cours sur des sous Tache)';
    }

    public function createIfNotActif()
    {
        $tasks = $this->getList(array('dst' => $this->getData('dst'), 'src' => $this->getData('src'), 'subj' => $this->getData('subj'), 'txt' => $this->getData('txt'), 'prio' => $this->getData('prio'), 'status' => 0));
        if (count($tasks) == 0)
            parent::create();
        return array();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $return = parent::create($warnings, $force_create);

        $this->updateField('date_update', $this->getData('date_create'));
        $this->updateField('auto', ($this->getData('dst') != '') ? 1 : 0);
        
        if($this->getData('id_task')){
            $parent = $this->getChildObject('task_mere');
            if($parent->getData('status') == 4)
                $parent->updateField('status', 1);
        }

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

    public function getType()
    {
        $type = 'other';
        if ($this->getData('auto')) {
            $d = $this->getData("dst");
            if (isset(self::$valSrc[$d]))
                $type = $d;
        } else {
            $type = $this->getData('type_manuel');
        }
        return $type;
    }

    public function displayType()
    {
        if ($this->getData('auto'))
            return self::$valSrc[$this->getType()];
        else
            return $this->displayData('type_manuel');
    }

    public function getTypeTacheSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        global $user;

        if (isset(static::$valSrc[$value])) {
            $filters['auto'] = 1;
            if ($value != 'other')
                $filters['dst'] = $value;
            else {
                $tabsDroit = self::getTableSqlDroitPasDroit($user);
                //            print_r($tabsDroit);die;
                $filters['dst'] = array("not_in" => $tabsDroit[2]);
            }
        } elseif (isset(static::$types_manuel[$value])) {
            $filters['auto'] = 0;
            $filters['type_manuel'] = $value;
        } else {
            BimpCore::addlog('Type de tâche inconnue ' . $value);
        }

        return self::$valStatus;
    }

    public static function getStatus_list_taskArray()
    {
        $status = self::$valStatus;
        
        return $status;
    }
    
    public function getStatusPossible() {
        $status = self::$valStatus;
        if($this->asFilleEnCours())
            unset($status[4]);
        unset($status[$this->getData('status')]);
        
        return $status;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return ($this->getInitData("status") < 4) or $force_edit;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable($force_delete, $errors);
    }

    public function getRight($right)
    {
        global $user;
        if (!$this->isLoaded())
            return 1;
        if ($this->getData("id_user_owner") == $user->id)
            return 1;
        if ($this->getData("user_create") == $user->id && $right == 'read')
            return 1;
        $classRight = $this->getType();
        return $user->rights->bimptask->$classRight->$right;
    }

    public function getFiltreRightArray($user)
    {
        $filtre = array();
        $tabFiltre = self::getFiltreDstRight($user);
        if (count($tabFiltre[1])) {
            $filtre['dst_par_type'] = array(
                'or' => array(
                    'mode_auto' => array(
                        'and_fields' => array(
                            'auto' => 1,
                            'dst'  => array($tabFiltre[0] => $tabFiltre[1])
                        )
                    ),
                    'mode_manu' => array(
                        'and_fields' => array(
                            'auto'        => 0,
                            'type_manuel' => array($tabFiltre[0] => $tabFiltre[1])
                        )
                    )
            ));
        } else
            $filtre['id'] = array('operator' => '>', 'value' => '0'); //toujours vraie
        return $filtre;
    }

    public function getListFiltre($type = "normal")
    {
        global $user;
        $list = new BC_ListTable($this, 'default', 1, null, ($type == "my" ? 'Mes tâches assignées' : ($type == "byMy" ? 'Mes tâches créées' : 'Toutes les tâches')));
        $list->addIdentifierSuffix($type);

        if ($type == 'byMy')
            $list->addFieldFilterValue('user_create', (int) $user->id);
        elseif ($type == "my")
            $list->addFieldFilterValue('id_user_owner', (int) $user->id);
        else
            $list->addFieldFilterValue('fgdg_dst', array(
                ($type == "my" ? 'and_fields' : 'or') => BimpTools::merge_array(array(
                    'id_user_owner' => $user->id
                        ), $this->getFiltreRightArray($user))
            ));
        return $list;
    }

    public function canView()
    {
        global $user;
        if ($this->isNotLoaded())
//            foreach (self::$valSrc as $src => $nom)
//                if ($user->rights->bimptask->$src->read)
            return 1;

        return $this->getRight("read");
    }

    public function canEdit()
    {
        return $this->getRight("write");
    }

    public function canEditAll()
    {
        return 1; //todo
    }

    public function canDelete()
    {
//        static::majRight();
        return $this->getRight("delete");
    }

    public function canAttribute()
    {
        return $this->getRight("attribute");
    }
    
    public function asFilleEnCours(){
        $files = $this->getChildrenObjects('task_fille', array('status' => array('operator' => '<', 'value' => '4'), 'id_task' => $this->id));
        foreach($files as $file){
            return 1;
        }
        return 0;
    }
    
    public function addRepMail($user, $src, $txt){
        if($this->getData('status') == 4){
            $this->updateField('status', 1);
            $txt = 'Cettte tâche est réouverte a la suite d\'un messsage<br/><br/>'.$txt;
        }
        $this->addNote($txt, 4, 0, 0, $src, ($user->id == self::ID_USER_DEF ? BimpNote::BN_AUTHOR_FREE : BimpNote::BN_AUTHOR_USER), null, null, null, 0);
        foreach($this->getUserNotif(true) as $userT){
            $this->addNote($txt, 4, 0, 0, $src, ($user->id == self::ID_USER_DEF ? BimpNote::BN_AUTHOR_FREE : BimpNote::BN_AUTHOR_USER), BimpNote::BN_DEST_USER, null, $userT->id, 1);
        }
    }   
    
    public function displaySousTache(){
        $bc = new BC_ListTable(BimpObject::getInstance($this->module, $this->object_name), 'sousTache', 1, null, 'Sous Tâches');
        $bc->addFieldFilterValue('id_task', $this->id);
        return $bc->renderHtml();
    }

    public function canEditField($field_name)
    {
        if ($field_name == "id_user_owner")
            return $this->canAttribute();
        return parent::canEditField($field_name);
    }

    public static function getTypeArray()
    {
        return BimpTools::merge_array(static::$valSrc, static::$types_manuel);
    }

    public static function getTableSqlDroitPasDroit($user)
    {
        $tabDroit = $tabPasDroit = $tabTous = array();
        foreach (self::getTypeArray() as $src => $nom) {
            if ($src != "other") {
                if ($user->rights->bimptask->$src->read)
                    $tabDroit[] = '' . $src . '';
                else
                    $tabPasDroit[] = '' . $src . '';
                $tabTous[] = '' . $src . '';
            }
        }
        return array($tabDroit, $tabPasDroit, $tabTous);
    }

    public static function getFiltreDstRight($user)
    {
        $tabT = self::getTableSqlDroitPasDroit($user);
        if ($user->rights->bimptask->other->read)
            return array("not_in", $tabT[1]);
        else
            return array("in", $tabT[0]);
    }
    
    public function sendMail($to, $from, $sujet, $msg, $rappel = true){
        $errors = array();
        $sep = "<br/>---------------------<br/>";
        $idTask =  self::MARQEUR_MAIL . $this->getData("id");
        $msg = str_replace("<br>", "<br/>", $msg);



        $msg = $sep . "Merci d'inclure ces lignes dans les prochaines conversations<br/>" . $idTask . $sep.'<br/><br/>'.$msg;

        if($rappel){
            $msg .= '<br/><br/><h1>'.$this->displayData('subj').'</h1><br/>'.$this->displayData('txt').'<br/><br/>'.$this->displayData('comment');
        
            
            
            $notes = $this->getNotes();
            $msg .= "<br/><br/>Fil de discussion :";
            foreach ($notes as $note) {
                $msg .= $sep;
                $msg .= $note->getData("content");
            }
        }

        if(is_null($sujet))
            $sujet = "Re:" . $this->getData("subj");
 
//        $msg = str_replace("<br />", "\n", $msg);
//        $msg = str_replace("<br/>", "\n", $msg);
//        $msg = str_replace("<br/>", "\n", $msg);


        if (!mailSyn2($sujet, $to, $from, $msg))
            $errors[] = "Envoi email impossible";
        return $errors;
    }

    public function actionSendMail($data, &$success)
    {
        $success = "Message envoyé";
        $errors = $warnings = array();
        
        $errors = $this->sendMail($this->getData("src"), $this->getData("dst"), null, $data['email'], (isset($data['include_file']) && $data['include_file']));

        if(!count($errors))
            $this->addNote($data['email'], BimpNote::BN_ALL, 1);
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function afterCreateNote($note)
    {
        $this->updateField('date_update', $note->getData('date_create'));
    }

    public function actionClose($data, &$success)
    {
        $errors = $warnings = array();
        $success = "Tâche fermée";
        $errors = $this->updateField("status", 4);

        $success_callback = 'bn.notificationActive.notif_task.obj.remove(' . $this->id . ')';

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
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
            if ((int) $data['id_user_owner'] == $user->id)
                $success_callback = $instance_task . '.move(' . $this->id . ', ' . $this->getData('prio') . ', ' . $this->getData('id_user_owner') . ')';
            // Attribuée à quelqu'un d'autre
            else
                $success_callback = $instance_task . '.remove(' . $this->id . ')';
        } else {
            $success = "Désattribué";
            if ($this->can('view'))
                $success_callback = $instance_task . '.move(' . $this->id . ', ' . $this->getData('prio') . ', ' . $this->getData('id_user_owner') . ')';
            else
                $success_callback = $instance_task . '.remove(' . $this->id . ')';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
        public function actionChangeStatut($data, &$success)
    {
        $errors = $warnings = array();
        $success = "statut modifié";
        $errors = $this->updateField("status", $data['status']);

        $msg = 'Statut passé à "'.$this->displayData('status').'" '.$data['text'];
        
        if($data['notif']){
            $this->notifier('Changement statut tâche "'.$this->getData('subj').'"', $msg, true);
        }
        $this->addNote($msg);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'status')
            return 0;
        return parent::isFieldEditable($field, $force_edit);
    }

    
    public function getInfosExtraBtn()
    {
        return $this->getButtons();
    }
    
    public function getRefProperty()
    {
        return 'subj';
    }

    public function getButtons()
    {
        global $user;
        $buttons = array();
        if ($this->isEditable() && !in_array($this->getType(), self::$srcNotAttribute)) {
            if ($this->can("edit")) {
                if (filter_var($this->getData("src"), FILTER_VALIDATE_EMAIL) && filter_var($this->getData("dst"), FILTER_VALIDATE_EMAIL))
                    $buttons[] = array(
                        'label'      => 'Répondre par mail',
                        'labelShort' => 'Rep Mail',
                        'icon'       => 'send',
                        'onclick'    => $this->getJsActionOnclick('sendMail', array(), array('form_name' => 'newMail'))
                    );
                if(!$this->asFilleEnCours())
                    $buttons[] = array(
                        'label'      => 'Classer terminé',
                        'labelShort' => 'Terminer',
                        'icon'       => 'close',
                        'onclick'    => $this->getJsActionOnclick('close', array(), array('confirm_msg' => 'Terminer la tâche sans notifications ?'))
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
            if ($this->can("edit") || $this->isEditable()) {
                $buttons[] = array(
                    'label'   => 'Changer le statut',
                    'icon'    => 'spinner',
                    'onclick' => $this->getJsActionOnclick('changeStatut', array(), array('form_name' => 'changeStatut'))
                );
            }
        }
        return $buttons;
    }

    public function getFilesListExtraBtn(BimpFile $file)
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Déplacer',
            'icon'    => 'fas_file-export',
            'onclick' => $this->getJsActionOnclick('moveFile', array(
                'id_file'             => $file->id,
                'move_to_object_name' => 'client',
                'keep_copy'           => 0,
                'create_link'         => 1
                    ), array(
                'form_name' => 'move_file'
            ))
        );

        return $buttons;
    }

    public function getTaskForUser($id_user, $id_max, &$errors = array())
    {

        $tasks = array();
        $nb_my = 0;
        $nb_unaffected = 0;

        global $user;

        $tasks['content'] = BimpTools::merge_array(
                        // Tâches affectées à l'utilisateur actuel        
                        self::getNewTasks(array(
                            'id'            => array(
                                'operator' => '>',
                                'value'    => $id_max
                            ),
                            'id_user_owner' => (int) $id_user,
                            'status'        => array(
                                'operator' => '<',
                                'value'    => 4
                            )), 'my_task', $nb_my),
                        // Tâches non affectées
                                          self::getNewTasks(BimpTools::merge_array(array(
                                    'id'            => array(
                                        'operator' => '>',
                                        'value'    => $id_max
                                    ),
                                    'id_user_owner' => 0,
                                    'status'        => array(
                                        'operator' => '<',
                                        'value'    => 4
                                    )
                                        ), $this->getFiltreRightArray($user)), 'unaffected_task', $nb_unaffected
        ));

        $tasks['nb_my'] = $nb_my;
        $tasks['unaffected_task'] = $nb_unaffected;
        return $tasks;
    }

    private static function getNewTasks($filters, $user_type, &$nb)
    {

        $tasks = array();

        $max_task_view = 40;
        $i = $j = 0;

        $sql = BimpTools::getSqlSelect(array('id'));
        $sql .= BimpTools::getSqlFrom('bimp_task');
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy('prio', 'DESC', 'a', 'id', 'DESC');

        $rows = self::getBdb()->executeS($sql, 'array');

        $l_tasks_user = array();

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
                        'txt'           => $t->getData("txt"),
//                        'txt'           => dol_trunc($t->getData('txt'), 60),
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

    public static function majRight()
    {
        global $db;
        $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "rights_def WHERE module = 'bimptask'");
        while ($ln = $db->fetch_object($sql)) {
            $tabRights[] = $ln->perms . $ln->subperms;
        }
        foreach (static::getTypeArray() as $type => $label) {
            if ($type != 'other') {
                foreach (array('read' => 'Lire', 'write' => 'Modifié', 'delete' => 'Supprimé', 'attribute' => 'Attribué') as $subperms => $subLabel) {
                    if (!in_array($type . $subperms, $tabRights)) {
                        echo '<br/>' . $subLabel . ' ' . $label;
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "rights_def (`libelle`, `module`, `entity`, `perms`, `subperms`, `type`, `bydefault`) VALUES ('" . $subLabel . ' ' . $label . "', 'bimptask', 1, '" . $type . "', '" . $subperms . "', 'w', 0);");
                    }
                }
            }
        }



//        $right = 
//        
//        
//        
//                    $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
//            $this->rights[$r][1] = 'Read '.$nom; // Permission label
//            $this->rights[$r][3] = 0;      // Permission by default for new user (0/1)
//            $this->rights[$r][4] = $typeTask;    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $this->rights[$r][5] = 'read';        // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $r++;
//            $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
//            $this->rights[$r][1] = 'Write '.$nom; // Permission label
//            $this->rights[$r][3] = 0;      // Permission by default for new user (0/1)
//            $this->rights[$r][4] = $typeTask;    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $this->rights[$r][5] = 'write';        // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $r++;
//            $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
//            $this->rights[$r][1] = 'Supprimer '.$nom; // Permission label
//            $this->rights[$r][3] = 0;      // Permission by default for new user (0/1)
//            $this->rights[$r][4] = $typeTask;    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $this->rights[$r][5] = 'delete';        // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $r++;
//            $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
//            $this->rights[$r][1] = 'Attribué '.$nom; // Permission label
//            $this->rights[$r][3] = 0;      // Permission by default for new user (0/1)
//            $this->rights[$r][4] = $typeTask;    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $this->rights[$r][5] = 'attribute';        // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
//            $r++;
    }
}
