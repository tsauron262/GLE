<?php
include_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/BimpAbstractFollow.class.php';

class BIMP_Task extends BimpAbstractFollow
{

    public static $valSrc = array(); // définie dans l'extends entity
    public static $types_manuel = array(
        'dev'        => 'Développement',
        'adminVente' => 'Administration des Ventes',
        'dispatch' => 'Dispatch',
        'sav' => 'SAV'
    );
    public static $srcNotAttribute = array(/* 'sms-apple@bimp-groupe.net' */);
    public static $nbNonLu = 0;
    public static $nbAlert = 0;
    public static $valStatus = array(
        0 => array('label' => "A traiter", 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        1 => array('label' => "En cours", 'icon' => 'fas_cogs', 'classes' => array('info')),
        2 => array('label' => "Attente utilisateur", 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        3 => array('label' => "Attente technique", 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        4 => array('label' => "Terminé", 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $valPrio = array(
        0  => array('label' => "Normale (Niveau 3)", 'classes' => array('info')),
        10 => array('label' => "Importante (Niveau 2)", 'classes' => array('warning')),
        20 => array('label' => "Urgente (Niveau 1)", 'classes' => array('danger'))
    );
    public static $sous_types = array(
        'dev' => array(
            'bug' => array('label' => 'Bug'),
            'dev' => array('label' => 'Développement')
        ),
        'dispatch' => array(
            'hotline' => array('label' => 'Hot-Line'),
            'commande' => array('label' => 'Commande'),
            'commerc' => array('label' => 'Service Commercial'),
            'tech' => array('label' => 'Service Technique')
        ),
        'sav' => array(
            'autre' => array('label' => 'Autre')
        )
    );
    private static $jsReload = 'if (typeof notifTask !== "undefined" && notifTask !== null) notifTask.reloadNotif();';

    
    
    // Droits users: 

    public function getUserRight($right)
    {
        global $user;
        if ($user->admin) {
            return 1;
        }

        if (!$this->isLoaded()) {
            $classRight = BimpTools::getPostFieldValue('type_manuel', null);
            if (is_null($classRight))
                return 1;
        } else
            $classRight = $this->getType();

        if ($this->getData("id_user_owner") == $user->id)
            return 1;

        if ($this->getData("user_create") == $user->id/* && $right == 'read' */) // => Pourquoi? le créateur devrait pouvoir modifier la tâche
            return 1;

        return $user->rights->bimptask->$classRight->$right;
    }

    public function canView()
    {
        if ($this->isNotLoaded()) {
            return 1;
        }

        global $user;
        if ($user->admin) {
            return 1;
        }

        if ($this->getUserRight("read"))
            return 1;

        $users = $this->getUsersFollow();
        foreach ($users as $userT) {
            if ($userT->id == $user->id)
                return 1;
        }

        return 0;
    }

    public function canEdit()
    {
        return $this->getUserRight("write");
    }

    public function canEditAll()
    {
        return 1; //todo
    }

    public function canDelete()
    {
        return $this->getUserRight("delete");
    }

    public function canAttribute()
    {
        return $this->getUserRight("attribute");
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'id_user_owner':
            case 'id_task':
                return $this->canAttribute();
        }

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addFiles':
                return $this->canEdit();
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($field == 'status')
            return 0;

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return ($this->getInitData("status") < 4) or $force_edit;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable($force_delete, $errors);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'reopen':
                if ((int) $this->getData('status') !== 4) {
                    $errors[] = 'Cette tâche n\'a pas besoin d\'être réouverte';
                    return 0;
                }

                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function hasFilleEnCours()
    {
        if ($this->isLoaded()) {
            if ((int) $this->db->getCount($this->getTable(), 'id_task = ' . $this->id . ' AND status < 4')) {
                return 1;
            }
        }

        return 0;
    }

    public function hasSousTaches()
    {
        if ($this->isLoaded()) {
            if ((int) $this->db->getCount($this->getTable(), 'id_task = ' . $this->id)) {
                return 1;
            }
        }

        return 0;
    }

    // Getters params: 

    public function getButtons()
    {
        global $user;
        $buttons = parent::getButtons();

        if ($btn = $this->getAddFilesButton()) {
            $buttons[] = $btn;
        }

        if ($this->hasSousTaches()) {
            $buttons[] = array(
                'label'   => 'Liste des sous-tâches',
                'icon'    => 'fas_bars',
                'onclick' => $this->getJsLoadModalList('sousTache', array(
                    'title'         => htmlentities($this->getData('subj')) . ' : sous-tâches',
                    'extra_filters' => array(
                        'id_task' => $this->id
                    )
                ))
            );
        }

        if ($this->isEditable() && !in_array($this->getType(), self::$srcNotAttribute)) {
            if ($this->can("edit")) {
                if (filter_var($this->getData("src"), FILTER_VALIDATE_EMAIL) && filter_var($this->getData("dst"), FILTER_VALIDATE_EMAIL)) {
                    $buttons[] = array(
                        'label'      => 'Répondre par mail',
                        'labelShort' => 'Rep Mail',
                        'icon'       => 'fas_paper-plane',
                        'onclick'    => $this->getJsActionOnclick('sendMail', array(), array(
                            'form_name' => 'newMail'
                                )
                        )
                    );
                }

                if (!$this->hasFilleEnCours())
                    $buttons[] = array(
                        'label'      => 'Classer terminée',
                        'labelShort' => 'Terminer',
                        'icon'       => 'fas_check',
                        'onclick'    => $this->getJsActionOnclick('close', array(), array('form_name' => 'close'))
                    );
            }
            if ($this->can("edit") || $this->canAttribute()) {
                if ($this->getData("id_user_owner") < 1) {
                    $buttons[] = array(
                        'label'   => 'Attribuer',
                        'icon'    => 'fas_user',
                        'onclick' => $this->getJsActionOnclick('attribute', array(), array('form_name' => 'attribute'))
                    );
                }

                if ($this->getData("id_user_owner") == $user->id) {
                    $buttons[] = array(
                        'label'   => 'Refuser l\'attribution',
                        'icon'    => 'fas_times-circle',
                        'onclick' => $this->getJsActionOnclick('attribute', array('id_user_owner' => 0), array('confirm_msg' => "Refuser l\'attribution ?"))
                    );
                }
            }
            if ($this->can("edit") || $this->isEditable()) {
                $buttons[] = array(
                    'label'   => 'Changer le statut',
                    'icon'    => 'fas_pen',
                    'onclick' => $this->getJsActionOnclick('changeStatut', array(), array('form_name' => 'changeStatut'))
                );
            }
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array())
            );
        }
        return $buttons;
    }

    public function getInfosExtraBtn()
    {
        return $this->getButtons();
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

    // Getters Array:

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
        } /* else
          $filtre['id'] = array('operator' => '>', 'value' => '0'); //toujours vraie   !!! pourquoi ? fait bugger */
        return $filtre;
    }

    public function getSous_type_list_taskArray()
    {
        $type = BimpTools::getPostFieldValue('type_manuel', $this->getData('type_manuel'));

        if (isset($type) && self::$sous_types[$type])
            return self::$sous_types[$type];

        return array('nc'=>'Divers');
    }

    public function getAllSousTypesArray()
    {
        $types = array();

        foreach (self::$sous_types as $type => $sousTypes) {
            foreach ($sousTypes as $sous_type => $data) {
                $types[$sous_type] = $data['label'];
            }
        }

        return $types;
    }

    public static function getPrio_list_taskArray()
    {
        return self::$valPrio;
    }

    public static function getTypeArray()
    {
        return BimpTools::merge_array(static::$valSrc, static::$types_manuel);
    }

    public static function getStatus_list_taskArray()
    {
        $status = self::$valStatus;

        return $status;
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

    // Getters données: 

    public function getParentTask()
    {
        if ((int) $this->getData('id_task')) {
            return BimpCache::getBimpObjectInstance('bimptask', 'BIMP_Task', (int) $this->getData('id_task'));
        }

        return null;
    }

    public function getRefProperty()
    {
        return 'subj';
    }

    public function getUsersFollow($excludeMe = false, $exclude_unactive = true, &$users_no_follow = array())
    {
        $users = parent::getUsersFollow($excludeMe, $exclude_unactive, $users_no_follow);
        global $user;
        $users[$this->getData('user_create')] = $this->getChildObject('user_create');

        
        BimpObject::loadClass('bimpcore', 'BimpLink');
        $users = BimpTools::merge_array($users, BimpLink::getUsersLinked($this), true);

        $notes = $this->getNotes();
        foreach ($notes as $note) {
            $users = BimpTools::merge_array($users, BimpLink::getUsersLinked($note), true);
        }

        foreach ($users as $id_user => $u) {
            if (!BimpObject::objectLoaded($u)) {
                unset($users[$id_user]);
            }

            if ($excludeMe && $id_user == $user->id) {
                unset($users[$id_user]);
            }

            if ($exclude_unactive && !(int) $u->getData('statut')) {
                unset($users[$id_user]);
            }

            if (is_array($users_no_follow) && in_array($id_user, $users_no_follow)) {
                unset($users[$id_user]);
            }
        }



        $parent_task = $this->getParentTask();

        if (BimpObject::objectLoaded($parent_task)) {
            foreach ($parent_task->getUsersFollow($excludeMe, $exclude_unactive, $users_no_follow) as $id_user => $u) {
                if (!array_key_exists($id_user, $users)) {
                    $users[$id_user] = $u;
                }
            }
        }

        return $users;
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
                            'id'      => array(
                                'operator' => '>',
                                'value'    => $id_max
                            ),
                            'or_user' => array(
                                'or' => array(
                                    'owner'  => array(
                                        'and_fields' => array(
                                            'id_user_owner' => (int) $id_user,
                                            'status'        => array(0, 1, 3)
                                        )
                                    ),
                                    'author' => array(
                                        'and_fields' => array(
                                            'user_create' => (int) $id_user,
                                            'status'      => 2
                                        )
                                    )
                                )
                            )), 'my_task', $nb_my
                        ), self::getNewTasks(BimpTools::merge_array(array(// Tâches non affectées
                                    'id'            => array(
                                        'operator' => '>',
                                        'value'    => $id_max
                                    ),
                                    'id_user_owner' => 0,
                                    'status'        => array(0, 1, 3
                                    )
                                        ), $this->getFiltreRightArray($user)), 'unaffected_task', $nb_unaffected
        ));

        $tasks['nb_my'] = $nb_my;
        $tasks['unaffected_task'] = $nb_unaffected;
        return $tasks;
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

    private static function getNewTasks($filters, $user_type, &$nb, $exclude_parent_tasks = true)
    {
        global $user;
        $bdb = self::getBdb();

        $tasks = array();

        $i = 0;

        if ($exclude_parent_tasks) {
            $filters['(SELECT COUNT(DISTINCT st.id) FROM ' . MAIN_DB_PREFIX . 'bimp_task st WHERE st.id_task = a.id AND st.status < 4)'] = 0;
        }

        $sql = 'SELECT DISTINCT a.id';
        $sql .= BimpTools::getSqlFrom('bimp_task');
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy('id', 'ASC', 'a');

        $rows = $bdb->executeS($sql, 'array');

        if (!is_array($rows)) {
            return array();
        }

        foreach ($rows as $r) {
            $t = BimpCache::getBimpObjectInstance('bimptask', 'BIMP_Task', (int) $r['id']);
            if (!BimpObject::objectLoaded($t)) {
                continue;
            }

            if ($t->can('view')) {
                $nb++;
                $where = 'obj_type = \'bimp_object\' AND obj_module = \'bimptask\' AND obj_name = \'BIMP_Task\' AND id_obj = ' . $t->id;
                $where .= ' AND viewed = 0 AND user_create != ' . (int) $user->id;
                $not_viewed = (int) $bdb->getCount('bimpcore_note', $where);

                $user_author = $t->getChildObject('user_create');
                $prio = (int) $t->getData('prio');
                $prio_badge = '';
                switch ($prio) {
                    case 20:
                        $prio_badge = '<span class="badge badge-danger" style="margin-right: 8px; font-size: 10px">' . BimpRender::renderIcon('fas_exclamation', 'iconLeft') . 'Urgent</span>';
                        break;

                    case 10:
                        $prio_badge = '<span class="badge badge-warning" style="margin-right: 8px; font-size: 10px">Important</span>';
                        break;
                }

                $status = (int) $t->getData('status');
                $status_icon = '<span class="' . implode(' ', self::$valStatus[$status]['classes']) . ' bs-popover" style="margin-right: 8px"';
                $status_icon .= BimpRender::renderPopoverData(self::$valStatus[$status]['label']) . '>';
                $status_icon .= BimpRender::renderIcon(self::$valStatus[$status]['icon']) . '</span>';

                $parent_task = null;

                if ((int) $t->getData('id_task')) {
                    $parent_task = $t->getChildObject('task_mere');
                }

                $task = array(
                    'id'            => $t->getData('id'),
                    'user_type'     => $user_type,
                    'prio'          => $prio,
                    'status_icon'   => $status_icon,
                    'prio_badge'    => $prio_badge,
                    'subj'          => $t->getData('subj'),
                    'src'           => $t->getData('src'),
                    'txt'           => $t->displayData("txt", 'default', false),
                    'date_create'   => $t->getData('date_create'),
                    'url'           => DOL_URL_ROOT . '/bimptask/index.php?fc=task&id=' . $t->getData('id'),
                    'not_viewed'    => (int) $not_viewed,
                    'can_rep_mail'  => (int) ($t->can('edit') and filter_var($t->getData('src'), FILTER_VALIDATE_EMAIL) and filter_var($t->getData('dst'), FILTER_VALIDATE_EMAIL)),
                    'can_close'     => (int) $t->can('edit'),
                    'can_attribute' => (int) ($t->can('edit') or $t->canAttribute()),
                    'can_edit'      => (int) $t->can('edit'),
                    'author'        => (BimpObject::objectLoaded($user_author) ? $user_author->getName() : ''),
                    'parent_task'   => (BimpObject::objectLoaded($parent_task) ? $parent_task->getLink() : '')
                );

                $tasks[] = $task;

                $i++;
            }
        }

        return $tasks;
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

    public function getType()
    {
        $type = 'other';

        if ($this->getData('auto')) {
            $d = $this->getData("dst");
            if (isset(self::$valSrc[$d])) {
                $type = $d;
            }
        } else {
            $type = $this->getData('type_manuel');
        }

        return $type;
    }

    public function getStatusPossible()
    {
        $status = self::$valStatus;
        if ($this->hasFilleEnCours())
            unset($status[4]);
        unset($status[$this->getData('status')]);

        return $status;
    }

    // Affichages: 

    public function displayType()
    {
        if ($this->getData('auto'))
            return self::$valSrc[$this->getType()];
        else
            return $this->displayData('type_manuel');
    }

    public function displaySousTachesCounts()
    {
        $html = '';

        if ($this->isLoaded()) {
            $sql = 'SELECT ';
            $sql .= ' SUM(' . BimpTools::getSqlCase(array(
                        'a.status' => 0
                            ), 1, 0) . ') as a_traiter';
            $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                        'a.status' => 1
                            ), 1, 0) . ') as en_cours';
            $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                        'a.status' => array('in' => array(2, 3))
                            ), 1, 0) . ') as en_attentes';
            $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                        'a.status' => 4
                            ), 1, 0) . ') as terminees';

            $sql .= BimpTools::getSqlFrom('bimp_task');

            $sql .= BimpTools::getSqlWhere(array(
                        'a.id_task' => $this->id
                            )
            );

            $rows = self::getBdb()->executeS($sql, 'array');

            if (!empty($rows)) {
                $row = $rows[0];

                if ((int) $row['a_traiter'] > 0) {
                    $html .= ($html ? '&nbsp;' : '') . '<span class="badge badge-important">' . (int) $row['a_traiter'] . '</span>';
                }
                if ((int) $row['en_cours'] > 0) {
                    $html .= ($html ? '&nbsp;' : '') . '<span class="badge badge-info">' . (int) $row['en_cours'] . '</span>';
                }
                if ((int) $row['en_attentes'] > 0) {
                    $html .= ($html ? '&nbsp;' : '') . '<span class="badge badge-warning">' . (int) $row['en_attentes'] . '</span>';
                }
                if ((int) $row['terminees'] > 0) {
                    $html .= ($html ? '&nbsp;' : '') . '<span class="badge badge-success">' . (int) $row['terminees'] . '</span>';
                }
            }
        }

        return $html;
    }

    public function displayPageLink()
    {
        $url = $this->getData('url');
        if ($url) {
            return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
        }

        return '';
    }

    // Rendus HTML: 

    public function renderHeaderStatusExtra()
    {
        if ($this->hasFilleEnCours())
            return ' (action en cours sur des sous Tâches)';
    }

    public static function renderCounts($type = '')
    {
        $html = '';

        // Tâches à traiter: 

        $sql = 'SELECT a.sous_type, a.id_user_owner';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.prio' => 20
                        ), 1, 0) . ') as nb_niv1';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.prio' => 10
                        ), 1, 0) . ') as nb_niv2';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.prio' => 0
                        ), 1, 0) . ') as nb_niv3';

        $sql .= BimpTools::getSqlFrom('bimp_task');

        $count_st_key = '(SELECT COUNT(DISTINCT st.id) FROM ' . MAIN_DB_PREFIX . 'bimp_task st WHERE st.id_task = a.id)';
        $sql .= BimpTools::getSqlWhere(array(
                    'a.status'      => array(
                        'operator' => '<',
                        'value'    => 4
                    ),
                    'a.type_manuel' => $type,
                    $count_st_key   => 0 // On exclue les tâches comportant des sous-tâches
                        )
        );
        $sql .= ' GROUP BY a.sous_type, a.id_user_owner';

//        die($sql);
        $rows = self::getBdb()->executeS($sql, 'array');

        // Trie: 
        if (!empty($rows)) {

            $counts = array();
            $sous_types = array();
            $users = array();

            foreach ($rows as $r) {
                if (!in_array($r['sous_type'], $sous_types)) {
                    $sous_types[] = $r['sous_type'];
                }
                if (!in_array((int) $r['id_user_owner'], $users)) {
                    $users[] = (int) $r['id_user_owner'];
                }
                if (!isset($counts[(int) $r['id_user_owner']])) {
                    $counts[$r['id_user_owner']] = array();
                }
                if (!isset($types[(int) $r['id_user_owner']][$r['sous_type']])) {
                    $counts[$r['id_user_owner']][$r['sous_type']] = array();
                }

                $counts[(int) $r['id_user_owner']][$r['sous_type']] = $r;
            }

            $html = '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th></th>';

            foreach ($sous_types as $sous_type) {
                $label = '';
                if ($sous_type) {
                    $label = self::$sous_types['dev'][$sous_type]['label'];
//                    $label = BimpTools::getArrayValueFromPath(self::$sous_types, $type . '/' . $sous_type . '/label', $sous_type);
                } else {
                    $label = 'Non défini';
                }
                $html .= '<th style="text-align: center">' . $label . '</th>';
            }

            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody class="headers_col">';

            ksort($users);

            foreach ($users as $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                $html .= '<tr>';
                $html .= '<th>' . (BimpObject::objectLoaded($user) ? $user->getData('firstname') : ($id_user ? 'Utilisateur #' . $id_user : 'Non attrbuée')) . '</th>';
                foreach ($sous_types as $sous_type) {
                    $html .= '<td style="text-align: center">';
                    $nb_niv3 = (int) BimpTools::getArrayValueFromPath($counts, $id_user . '/' . $sous_type . '/nb_niv3', 0);
                    if ($nb_niv3) {
                        $html .= '<span class="badge badge-info">' . $nb_niv3 . '</span>';
                    }

                    $nb_niv2 = (int) BimpTools::getArrayValueFromPath($counts, $id_user . '/' . $sous_type . '/nb_niv2', 0);
                    if ($nb_niv2) {
                        $html .= '&nbsp;<span class="badge badge-warning">' . $nb_niv2 . '</span>';
                    }

                    $nb_niv1 = (int) BimpTools::getArrayValueFromPath($counts, $id_user . '/' . $sous_type . '/nb_niv1', 0);
                    if ($nb_niv1) {
                        $html .= '&nbsp;<span class="badge badge-danger">' . $nb_niv1 . '</span>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            return BimpRender::renderPanel('Tâches à traiter', $html, '', array(
                        'type' => 'secondary'
            ));
        }

        return $html;
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

    public function renderSousTachesList()
    {
        $bc = new BC_ListTable(BimpObject::getInstance($this->module, $this->object_name), 'sousTache', 1, null, 'Sous Tâches');
        $bc->addFieldFilterValue('id_task', $this->id);
        return $bc->renderHtml();
    }

    // Traitements: 

    public function createIfNotActif()
    {
        $tasks = $this->getList(array('dst' => $this->getData('dst'), 'src' => $this->getData('src'), 'subj' => $this->getData('subj'), 'txt' => $this->getData('txt'), 'prio' => $this->getData('prio'), 'status' => 0));
        if (count($tasks) == 0) {
            $warnings = array();
            return $this->create($warnings, true);
        }
        return array();
    }

    public function notifier($subject, $message, $rappel = false, $files = array())
    {
        $mails = array();
        foreach ($this->getUsersFollow(true) as $userN) {
            $mails[] = BimpTools::getUserEmailOrSuperiorEmail($userN->id);
        }
        $to = implode(',', $mails);
        
        $this->sendMail($to, 'Tache ERP<' . BimpCore::getConf('mailReponse', null, 'bimptask') . '>', $subject, $message, $rappel, $files);
        
        foreach($this->getEmailFollow() as $to){//pour ne pas partager email et lien
            $this->sendMail($to, 'Tache ERP<' . BimpCore::getConf('mailReponse', null, 'bimptask') . '>', $subject, $message, $rappel, $files, false);
        }
    }

    public function sendMail($to, $from, $sujet, $msg, $rappel = true, $files = array(), $withLink = true)
    {
        $errors = array();
        $sep = "<br/>---------------------<br/>";

        $msg = str_replace("<br>", "<br/>", $msg);

        $html = $sep . "Merci d'inclure ces lignes dans les prochaines conversations<br/>" . BimpCore::getConf('marqueur_mail', null, 'bimptask') . $this->id . '<br/>'. $sep . '<br/><br/>';

        if($withLink)
            $html .= '<h3>' . $this->getLink(array('syntaxe' => 'Tâche "<subj>"')) . '</h3>';
        else
            $html .= '<h3>' . $this->getData('subj') . '</h3>';

        $html .= $msg;

        if ($rappel) {
            $html .= '<br/><br/>' . $this->displayData('txt', 'default', 0, 0, 1) . '<br/><br/>';
            if ($this->getData('comment')) {
                $html .= '<b>Commentaire: </b>' . $this->displayData('comment', 'default', 0, 0, 1);
            }

            $notes = $this->getNotes();
            if (count($notes)) {
                $html .= "<br/><br/><b>Fil de discussion :</b>";
                foreach ($notes as $note) {
                    $html .= $sep;
                    $html .= $note->getData("content");
                }
            }
        }

        if (is_null($sujet)) {
            $sujet = "Re:" . $this->displayData("subj", 'default', 0, 0, 1);
        }

        $bimpMail = new BimpMail($this, $sujet, $to, $from, $html);
        $bimpMail->addFiles($files);
        $bimpMail->send($errors);

        return $errors;
    }

    public function reouvrir()
    {
        $errors = array();

        if ($this->getData('status') == 4 || $this->getData('status') == 2) {
            $errors = $this->updateField('status', 1);

            if (!count($errors)) {
                $parentTask = $this->getChildObject('task_mere');
                if ($parentTask && $parentTask->isLoaded()) {
                    $parentTask->reouvrir();
                }
            }
        }

        return $errors;
    }

    public function addRepMail($user, $src, $txt)
    {
        if ($this->getData('status') == 4 || $this->getData('status') == 2) {
            $this->reouvrir();
            $txt = 'Cettte tâche est réouverte a la suite du messsage : <br/><br/>' . $txt;
        }

        $id_user_def = (int) BimpCore::getConf('id_user_def', null, 'bimptask');

        $this->addNote($txt, BimpNote::BN_ALL, 0, 0, $src, ($user->id == $id_user_def ? BimpNote::BN_AUTHOR_FREE : BimpNote::BN_AUTHOR_USER), null, null, null, 0);
        foreach ($this->getUsersFollow(true) as $userT) {
            $this->addNote($txt, null, 0, 0, $src, ($user->id == $id_user_def ? BimpNote::BN_AUTHOR_FREE : BimpNote::BN_AUTHOR_USER), BimpNote::BN_DEST_USER, null, (int) $userT->id, 1);
        }
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

    public function afterCreateNote($note)
    {
        $this->updateField('date_update', $note->getData('date_create'));
    }

    // Actions: 

    public function actionClose($data, &$success)
    {
        $errors = $warnings = array();
        $success = "Tâche fermée";
        $errors = $this->updateField("status", 4);
        $success_callback = '';

        if (!count($errors)) {
            $success_callback = self::$jsReload;

            $comment = BimpTools::getArrayValueFromPath($data, 'comment', '');

            $msg = 'Tâche terminée' . ($comment ? '<br/><b>Commentaire : </b>' . $comment : '');
            $this->addObjectLog($msg);

            if ((int) BimpTools::getArrayValueFromPath($data, 'notify', 0)) {
                $user = BimpCore::getBimpUser();
                $msg = 'Bonjour, <br/><br/>La tâche "' . $this->getData('subj') . '" a été marquée terminée' . (BimpObject::objectLoaded($user) ? ' par ' . $user->getName() : '');

                if ($comment) {
                    $msg .= '<br/><br/><b>Commentaire : </b><br/>';
                    $msg .= $comment;
                }
                $this->notifier('Tâche "' . $this->getData('subj') . '" terminée', $msg, false);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Tâche réouverte avec succès';

        $errors = $this->reouvrir();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAttribute($data, &$success)
    {
        global $user;
        $errors = $warnings = array();

        $this->updateField("id_user_owner", $data['id_user_owner']);

        if ($data['id_user_owner'] > 0) {
            $success = "Attribué";
        } else {
            $success = "Désattribué";
        }

        $user_name = 'personne';
        if ((int) $data['id_user_owner']) {
            $owner = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $data['id_user_owner']);

            if (BimpObject::objectLoaded($owner)) {
                $user_name = $owner->getName();
            }
        }

        $msg = 'Attribuée à ' . $user_name;
        $this->addObjectLog($msg);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => self::$jsReload
        );
    }

    public function actionChangeStatut($data, &$success)
    {
        $errors = $warnings = array();
        $success = "statut modifié";
        $errors = $this->updateField("status", $data['status']);

        $msg = 'Statut passé à "' . $this->displayData('status', 'default', false) . '"<br/>';

        if ($data['comment']) {
            $msg .= '<b>Commentaire : </b>' . $data['comment'];
        }

        $files = array();
        // Fichiers joints: 
        if (isset($data['join_files']) && is_array($data['join_files'])) {
            foreach ($data['join_files'] as $id_file) {
                $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_file);
                if ($file->isLoaded()) {
                    $file_path = $file->getFilePath();
                    $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                    if (!file_exists($file_path)) {
                        $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                    } else {
                        $files[] = array($file_path, dol_mimetype($file_name), $file_name);
//                            $filename_list[] = $file_path;
//                            $mimetype_list[] = dol_mimetype($file_name);
//                            $mimefilename_list[] = $file_name;
                    }
                } else {
                    $errors[] = 'Le fichier d\'ID ' . $id_file . ' n\'existe pas';
                }
            }
        }

        if ($data['notif']) {
            $this->notifier('Changement statut tâche "' . $this->getData('subj') . '"', $msg, true, $files);
        }

        $this->addObjectLog($msg);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => self::$jsReload
        );
    }

    public function actionSendMail($data, &$success)
    {
        $success = "Message envoyé";
        $errors = $warnings = array();

        $errors = $this->sendMail($this->getData("src"), $this->getData("dst"), null, $data['email'], (isset($data['include_file']) && $data['include_file']));

        if (!count($errors))
            $this->addNote($data['email'], BimpNote::BN_ALL, 1);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

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

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $this->updateField('date_update', $this->getData('date_create'));
            $this->updateField('auto', ($this->getData('dst') != '') ? 1 : 0);

            if ($this->getData('id_task')) {
                $parent = $this->getChildObject('task_mere');
                $parent->reouvrir();
            }

            $files = BimpTools::getPostFieldValue('task_files', array());

            if (!empty($files)) {
                $files_dir = $this->getFilesDir();
                BimpTools::moveTmpFiles($warnings, $files, $files_dir);
            }
        }

        return $errors;
    }

    public function update(&$warnings = [], $force_update = false)
    {
        $init_id_owner = (int) $this->getInitData('id_user_owner');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $id_owner = (int) $this->getData('id_user_owner');
            if ($init_id_owner != $id_owner) {
                $msg = 'Attribuée à ' . ($id_owner ? ' {{Utilisateur:' . $id_owner . '}}' : 'personne');
                $this->addObjectLog($msg);
            }
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function addAutoTask($dst, $subject, $msg, $test_ferme = '')
    {
        global $conf;
        $errors = array();
        if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
            $task = BimpObject::getInstance("bimptask", "BIMP_Task");
            $tab = array("src" => "GLE-AUTO", "dst" => $dst, "subj" => $subject, "txt" => $msg, "prio" => 20, "test_ferme" => $test_ferme, 'auto' => 1);
            $errors = array_merge($errors, $task->validateArray($tab));
            $errors = array_merge($errors, $task->createIfNotActif());
        }
        return $errors;
    }
}



BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');
global $tabCentre;
if(is_array($tabCentre)){
    foreach($tabCentre as $code => $centre){
        BIMP_Task::$sous_types['sav'][$code] = array('label' => 'SAV'.$code);
    }
}
