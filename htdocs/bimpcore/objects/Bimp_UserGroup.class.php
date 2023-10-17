<?php

class Bimp_UserGroup extends BimpObject
{

    public function __construct($module, $object_name) {
        $this->redirectMode = 4;
        if(BimpTools::isModuleDoliActif('MULTICOMPANY'))
            $this->redirectMode = 5;
        return parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canView()
    {
        global $user;
        if ($user->admin || $user->rights->user->user->lire) {
            return 1;
        }

        return $this->canCreate();
    }

    public function canCreate()
    {
        global $user;
        return ($user->rights->user->user->creer || $user->admin);
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    public function canSetAction($action): int
    {
        global $user;

        switch ($action) {
            case 'addRight':
            case 'removeRight':
            case 'addUsers':
            case 'removeUsers':
                if ((int) $user->rights->user->user->creer || $user->admin) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('removeUsers', 'addUsers', 'addRight', 'removeRight', 'getAllEmails'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getUserListExtraButtons()
    {
        $buttons = array();

        if (BimpTools::getValue('fc', '') === 'user') {
            $id_user = (int) BimpTools::getValue('id', 0);
            if ($id_user && $this->isActionAllowed('removeUsers') && $this->canSetAction('removeUsers')) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                if (BimpObject::objectLoaded($user)) {
                    $buttons[] = array(
                        'label'   => 'Retirer l\'utilisateur du groupe',
                        'icon'    => 'fas_unlink',
                        'onclick' => $this->getJsActionOnclick('removeUsers', array(
                            'ids_users' => array($id_user)
                                ), array(
                            'confirm_msg' => htmlentities('Veuillez confirmer le retrait de l\\\'utilisateur "' . $user->getName() . '" du groupe "' . $this->getName() . '"')
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getUserListHeaderButtons()
    {
        $buttons = array();

        $id_user = 0;
        if (BimpTools::getValue('fc', '') === 'user') {
            $id_user = (int) BimpTools::getValue('id', 0);

            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
            if (BimpObject::objectLoaded($user)) {
                if ($user->isActionAllowed('addToGroup') && $user->canSetAction('addToGroup')) {
                    $buttons[] = array(
                        'label'   => 'Ajouter l\'utilisateur à un groupe',
                        'icon'    => 'fas_plus-circle',
                        'onclick' => $user->getJsActionOnclick('addToGroup', array(), array(
                            'form_name' => 'add_to_groupe'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('getAllEmails') && $this->canSetAction('getAllEmails')) {
            $buttons[] = array(
                'label'   => 'Liste des adresses e-mail',
                'icon'    => 'fas_at',
                'onclick' => $this->getJsActionOnclick('getAllEmails')
            );
        }

        return $buttons;
    }

    public function getPageTitle()
    {
        return 'Groupe ' . $this->getName();
    }

    // Getters données: 

    public function getUserGroupUsers($active_only = false)
    {
        $list = BimpCache::getGroupUsersList($this->id);

        $users = array();

        foreach ($list as $id_user) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
            if (BimpObject::objectLoaded($user)) {
                if (!$active_only || (int) $user->getData('statut')) {
                    $users[$id_user] = $user;
                }
            }
        }

        return $users;
    }

    public function getRights()
    {
        if ($this->isLoaded()) {
            return self::getUsergroupRights($this->id);
        }

        return array();
    }

    // Getters statics: 

    public static function getUsergroupRights($id_usergroup)
    {
        $cache_key = 'usergroup_' . $id_usergroup . '_rights';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('usergroup_rights', 'fk_usergroup = ' . $id_usergroup, null, 'array', array('fk_id'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][] = (int) $r['fk_id'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    // Getters Statics: 

    public static function getUserGroupsRights($id_user)
    {
        if ((int) $id_user) {
            $cache_key = 'user_' . $id_user . '_groups_rights';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $groups = BimpCache::getUserUserGroupsList($id_user);

                if (!empty($groups)) {
                    $rows = self::getBdb()->getRows('usergroup_rights', 'fk_usergroup IN (' . implode(',', $groups) . ')', null, 'array', array('fk_usergroup', 'fk_id'));

                    foreach ($rows as $r) {
                        if (!isset(self::$cache[$cache_key][(int) $r['fk_id']])) {
                            self::$cache[$cache_key][(int) $r['fk_id']] = array();
                        }

                        self::$cache[$cache_key][(int) $r['fk_id']][] = $r['fk_usergroup'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    // Rendus HTML: 

    public function renderUsersList()
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $users = $this->getUserGroupUsers();

        if (empty($users)) {
            $html .= BimpRender::renderAlerts('Aucun utilisateur enregistré pour ce groupe', 'warning');
        } else {
            $headers = array(
                'name'    => 'Nom',
                'login'   => 'Identifiant',
                'contact' => 'Infos contact',
                'buttons' => array('label' => '', 'col_style' => 'text-align: right')
            );

            $rows = array();

            foreach ($users as $user) {
                $buttons = '';

                if ($this->canSetAction('removeUsers') && $this->isActionAllowed('removeUsers')) {
                    $buttons .= BimpRender::renderRowButton('Retirer du groupe', 'fas_times-circle', $this->getJsActionOnclick('removeUsers', array(
                                        'ids_users' => array($user->id)
                                            ), array()));
                }

                $rows[] = array(
                    'row_data' => array(
                        'id_user' => $user->id
                    ),
                    'name'     => $user->getLink(),
                    'login'    => $user->getData('login'),
                    'contact'  => $user->displayFullContactInfos(),
                    'buttons'  => $buttons
                );
            }

            $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                        'main_id'    => 'group_users_list',
                        'searchable' => true,
                        'checkboxes' => true
            ));
        }

        $title = BimpRender::renderIcon('fas_users', 'iconLeft') . 'Liste des utilisateurs';
        $footer = '';

        if ($this->canSetAction('removeUsers') && $this->isActionAllowed('removeUsers')) {
            $footer .= '<div class="buttonsContainer align-left">';
            $footer .= '<span class="btn btn-default" onclick="BimpUsergroupUsersTable.removeSelectedUsers($(this), ' . $this->id . ')">';
            $footer .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Retirer les utilisateurs sélectionnés du groupe';
            $footer .= '</span>';
        }

        $footer .= '</div>';

        $header_buttons = array();

        if ($this->canSetAction('addUsers') && $this->isActionAllowed('addUsers')) {
            $header_buttons[] = array(
                'label'   => 'Ajouter des utilisateurs',
                'icon'    => 'fas_plus-circle',
                'onclick' => $this->getJsActionOnclick('addUsers', array(), array(
                    'form_name' => 'add_user'
                        )
                )
            );
        }

        return BimpRender::renderPanel($title, $html, $footer, array(
                    'type'           => 'secondary',
                    'header_buttons' => $header_buttons
                        )
        );
    }

    public function renderPageView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        global $user;

        $tabs = array();

        $isAdmin = $user->admin;
        //        $isItself = ($user->id == $this->id); // Ceci n'est valable que pour l'objet Bimp_User, à suppr. 

        $tabs[] = array(
            'id'      => 'infos',
            'title'   => BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Fiche',
            // Pas la peine de charger l'onglet principal en ajax
            //            'ajax'          => 1,
            //            'ajax_callback' => $this->getJsLoadCustomContent('renderInfosView', '$(\'#infos .nav_tab_ajax_result\')', array(''), array('button' => ''))
            'content' => $this->renderView('default') // Pour l'affichage des données, utiliser une vue (section "views" dans le yml) plutôt qu'une fonction. 
        );

        if ($isAdmin) {
            $tabs[] = array(
                'id'            => 'perms',
                'title'         => BimpRender::renderIcon('fas_check', 'iconLeft') . 'Permissions',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderPermsView', '$(\'#perms .nav_tab_ajax_result\')', array(''), array('button' => ''))
            );
        }

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderParamsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        global $user;

        $params = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ParamsUser');

        if ($user->admin || $user->id === $this->id) {
            $tabs = array();

            $tabs[] = array(
                'id'            => 'interface_tab',
                'title'         => 'Interface',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderInterfaceView', '$(\'#interface_tab .nav_tab_ajax_result\')', array(''), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'lists_configs_tab',
                'title'         => 'Configuration des listes',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#lists_configs_tab .nav_tab_ajax_result\')', array('lists_configs'), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'filters_configs_tab',
                'title'         => 'Configuration des filtres',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#filters_configs_tab .nav_tab_ajax_result\')', array('filters_configs'), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'lists_filters_tab',
                'title'         => 'Filtres enregistrés',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#lists_filters_tab .nav_tab_ajax_result\')', array('lists_filters'), array('button' => ''))
            );

            return BimpRender::renderNavTabs($tabs, 'params_tabs');
        }

        return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
    }

    public function renderInfosView()
    {
        // Supprimer cette fonction une fois les commentaires lus. 
        // 2 grosses erreurs ici: 
        //   - 1: les données sont déjà présentes dans $this->data (cet appel est fait sur une instance fetchée) 
        //   - 2: Il ne faut pas faire 4 requêtes SQL là où on peut en faire une seule. 

        $group_name = $this->db->getValue('usergroup', 'nom', 'rowid = ' . $_REQUEST['id']);
        $group_note = $this->db->getValue('usergroup', 'note', 'rowid = ' . $_REQUEST['id']);
        $alias = $this->db->getValue('usergroup_extrafields', 'alias', 'fk_object = ' . $_REQUEST['id']);
        $mail = $this->db->getValue('usergroup_extrafields', 'mail', 'fk_object = ' . $_REQUEST['id']);
        //        
        //        
        // Ce code HTML est généré automatiquement (via le composant BC_FieldsTable, section "fields_tables" dans le yml de l'objet). 
        $html = '<div id="Bimp_UserGroup_informations_fields_table_' . $_REQUEST['id'] . '_container" class="objectComponentContainer object_fields_table_container Bimp_UserGroup_fields_table_container">'
                . '<div class="panel panel-secondary foldable open"><div class="panel-heading"><div class="panel-title">'
                . '<i class="fas fa5-info-circle iconLeft"></i>informations</div><div class="header_buttons">'
                . '<span class="panel-caret"></span></div></div><div class="panel-body"><div id="Bimp_UserGroup_informations_fields_table_' . $_REQUEST['id'] . '" class="object_component object_fields_table Bimp_UserGroup_component Bimp_UserGroup_fields_table Bimp_UserGroup_fields_table_informations" data-identifier="Bimp_UserGroup_informations_fields_table_' . $_REQUEST['id'] . '" data-type="fields_table" data-name="informations" data-module="bimpcore" data-object_name="Bimp_UserGroup" data-id_object="' . $_REQUEST['id'] . '" data-objects_change_reload="">'
                . '<div id="Bimp_UserGroup_informations_fields_table_' . $_REQUEST['id'] . '_params" class="object_component_params"></div><div class="container-fluid object_component_content object_fields_table_content"><table class="objectFieldsTable Bimp_UserGroup_fieldsTable">'
                . '<tbody><tr><th>Nom</th><td>'
                . '<input type="hidden" name="nom" value="' . $group_name . '">' . $group_name . '</td></tr>'
                . '<tr><th>Mail principal</th><td>'
                . '<input type="hidden" name="mail" value="' . $mail . '">' . $mail . ''
                . '</td></tr><tr><th>Alias mail</th><td>'
                . '<input type="hidden" name="alias" value="' . $alias . '"></td></tr>'
                . '<tr><th>Secteur</th><td>'
                . '<input type="hidden" name="secteur" value=""></td></tr>'
                . '<tr><th>Note</th><td>'
                . '<input type="hidden" name="note" value="' . $group_note . '">' . $group_note . '</td>'
                . '</tr></tbody></table></div>';
        return $html;
    }

    public function renderPermsView()
    {
        $tabs = array();

        $tabs[] = array(
            'id'      => 'usergroup_rights',
            'title'   => BimpRender::renderIcon('fas_check', 'iconLeft') . 'Droits du groupe',
            'content' => $this->renderLinkedObjectsList('rights')
        );
        $tabs[] = array(
            'id'            => 'group_all_rights',
            'title'         => BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Tous les droits',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderAllRightsList', '$(\'#group_all_rights .nav_tab_ajax_result\')', array(''), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs, 'perms');
    }

    public function renderAllRightsList()
    {
        global $langs;
        $langs->loadLangs(array('users', 'admin'));

        $html = '';

        $rows = array();

        $headers = array(
            'module'  => array('label' => 'Module', 'search_values' => array()),
            'right'   => 'Droit',
            'active'  => array(
                'label'         => 'Actif',
                'align'         => 'center',
                'search_values' => array(
                    'no'      => array('label' => 'NON', 'classes' => array('danger'), 'icon' => 'fas_times'),
                    'yes'     => array('label' => 'OUI', 'classes' => array('success'), 'icon' => 'fas_check'),
                    'inherit' => array('label' => 'Hérité', 'classes' => array('info'), 'icon' => 'fas_arrow-circle-down')
                )
            ),
            'actions' => array('label' => 'Actions', 'align' => 'center', 'searchable' => 0),
            'libelle' => 'Libellé',
        );

        $rights = BimpCache::getRightsDefDataByModules();
        $group_rights = $this->getRights();

        // Ces fonction sont valables pour les users, par pour les groupes, à suppr. 
        //        $user_rights = $this->getAllRights();
        //        $user_groups = BimpCache::getUserUserGroupsList($this->id);

        $modules_list = array();

        $add_allowed = ($this->isActionAllowed('addRight') && $this->canSetAction('addRight'));
        $remove_allowed = ($this->isActionAllowed('removeRight') && $this->canSetAction('removeRight'));

        foreach ($rights as $module => $module_rights) {
            $modules_list[$module] = $module;
            foreach ($module_rights as $id_right => $data) {
                //                $has_groups_right = false; // inutile ici
                $active = '';
                //                $groups = ''; // Idem
                $actions = '';

                if (in_array($id_right, $group_rights)) {
                    if ($add_allowed) {
                        $onclick = 'BimpUserGroupRightsTable.addUserGroupRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Ajouter', 'fas_plus', $onclick, 'add_right_button', array(
                                    'styles' => array('display' => 'none')
                        ));
                    }

                    if ($remove_allowed) {
                        $onclick = 'BimpUserGroupRightsTable.removeUserGroupRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Retirer', 'fas_minus', $onclick, 'remove_right_button');
                    }

                    $active = array(
                        'content' => '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'OUI</span>',
                        'value'   => 'yes'
                    );
                } else {
                    if ($add_allowed) {
                        $onclick = 'BimpUserGroupRightsTable.addUserGroupRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Ajouter', 'fas_plus', $onclick, 'add_right_button');
                    }

                    if ($remove_allowed) {
                        $onclick = 'BimpUserGroupRightsTable.removeUserGroupRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Retirer', 'fas_minus', $onclick, 'remove_right_button', array(
                                    'styles' => array('display' => 'none')
                        ));
                    }

                    // La notion de droit hérité est valable pour les users, par pour les groupes. 
                    //                    if ($has_groups_right) {
                    //                        $active = array(
                    //                            'content' => '<span class="info">' . BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft') . 'Hérité</span>',
                    //                            'value'   => 'inherit'
                    //                        );
                    //                    } else {
                    $active = array(
                        'content' => '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'NON</span>',
                        'value'   => 'no'
                    );
                    //                    }
                }

                $right = '';
                $is_lire = (in_array($data['perms'], array('lire', 'read')) || in_array($data['subperms'], array('lire', 'read')));

                if ($is_lire) {
                    $right .= '<b>';
                }
                $right .= ($data['perms'] . (!empty($data['subperms']) ? '->' . $data['subperms'] : ''));
                if ($is_lire) {
                    $right .= '</b>';
                }

                $rows[] = array(
                    'row_data' => array(
                        'id_right' => $id_right
                    ),
                    'module'   => array('value' => $module),
                    'right'    => $right,
                    'libelle'  => $langs->trans($data['libelle']),
                    'active'   => $active,
                    'actions'  => $actions
                );
            }
        }

        $headers['module']['search_values'] = $modules_list;

        $buttons = '';

        if ($add_allowed || $remove_allowed) {
            $buttons .= '<div class="buttonsContainer">';
            if ($add_allowed) {
                $buttons .= '<span class="btn btn-default" onclick="BimpUserGroupRightsTable.addSelectedRights($(this), ' . $this->id . ')">';
                $buttons .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter les droits sélectionnés';
                $buttons .= '</span>';
            }
            if ($remove_allowed) {
                $buttons .= '<span class="btn btn-default" onclick="BimpUserGroupRightsTable.removeSelectedRights($(this), ' . $this->id . ')">';
                $buttons .= BimpRender::renderIcon('fas_minus-circle', 'iconLeft') . 'Retirer les droits sélectionnés';
                $buttons .= '</span>';
            }
            $buttons .= '</div>';
        }

        $html .= $buttons;

        $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                    //                    'main_class' => 'bimp_user_rights_table', // cette classe est faite pour identifier la liste de manière unique, il faut donc la différencier de la liste des droits user
                    'main_class' => 'bimp_usergroup_rights_table',
                    'searchable' => true,
                    'sortable'   => true,
                    'checkboxes' => true
        ));

        $html .= $buttons;

        return BimpRender::renderPanel('Liste des droits', $html, '', array(
                    'foldable' => true,
                    'type'     => 'secondary'
        ));
    }

    public function renderLinkedObjectsList($list_type)
    {
        $html = '';

        $errors = array();
        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $list = null;

        switch ($list_type) {
            case 'rights':
                $right = BimpObject::getInstance('bimpcore', 'Bimp_UserGroupRight');
                $list = new BC_ListTable($right, 'default', 1, null, 'Droits du groupe');
                $list->addFieldFilterValue('fk_usergroup', $this->id);
                break;
        }

        if (is_a($list, 'BC_ListTable')) {
            $html .= $list->renderHtml();
        } elseif ($list_type) {
            $html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
        } else {
            $html .= BimpRender::renderAlerts('Type de liste non spécifié');
        }

        return $html;
    }

    // Actions :

    public function actionAddUsers($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids_users = BimpTools::getArrayValueFromPath($data, 'ids_users', array());

        if (!is_array($ids_users) || empty($ids_users)) {
            $errors[] = 'Aucun utilisateur sélectionné';
        } else {
            $nOk = 0;
            foreach ($ids_users as $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if (!BimpObject::objectLoaded($user)) {
                    $warnings[] = 'L\'utilisateur #' . $id_user . ' n\'existe pas';
                } elseif ($user->dol_object->SetInGroup($this->id, 1) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($user->dol_object), 'Echec de l\'ajout de l\'utilisateur ' . $user->getName());
                } else {
                    $nOk++;
                }
            }

            if ($nOk) {
                $success = $nOk . ' utilisateur(s) ajouté(s) avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveUsers($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids_users = BimpTools::getArrayValueFromPath($data, 'ids_users', array());

        if (!is_array($ids_users) || empty($ids_users)) {
            $errors[] = 'Aucun utilisateur sélectionné';
        } else {
            $nOk = 0;

            foreach ($ids_users as $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if ($user->dol_object->RemoveFromGroup($this->id, 1) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($user->dol_object), 'Echec du retrait de l\'utilisateur ' . $user->getName());
                } else {
                    $nOk++;
                }
            }

            if ($nOk) {
                $success = $nOk . ' utilisateur(s) retiré(s) avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddRight($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_rights = BimpTools::getArrayValueFromPath($data, 'id_rights', array());
        $results = array();

        if (empty($id_rights)) {
            $errors[] = 'Aucun droit sélectionné';
        } else {
            $nOk = 0;

            foreach ($id_rights as $id_right) {
                $right_def = $this->db->getRow('rights_def', 'id = ' . $id_right, null, 'array');

                if (is_null($right_def)) {
                    $warnings[] = 'Le droit #' . $id_right . ' n\'existe plus';
                } else {
                    if (!(int) $this->db->getValue('usergroup_rights', 'rowid', 'fk_usergroup = ' . $this->id . ' AND fk_id = ' . $id_right)) {
                        if ($this->db->insert('usergroup_rights', array(
                                    'entity'       => 1,
                                    'fk_usergroup' => $this->id,
                                    'fk_id'        => $id_right
                                )) > 0) {
                            $nOk++;
                            $results[$id_right] = 1;

                            // Ajout du droit lire si nécessaire: 
                            if (!in_array($right_def['perms'], array('lire', 'read')) && !in_array($right_def['subperms'], array('lire', 'read'))) {
                                $where = 'module = \'' . $right_def['module'] . '\'';
                                if ($right_def['subperms']) {
                                    $where .= ' AND perms = \'' . $right_def['perms'] . '\' AND subperms IN (\'lire\', \'read\')';
                                } else {
                                    $where .= ' AND perms IN (\'lire\', \'read\')';
                                }
                                $id_right_lire = (int) $this->db->getValue('rights_def', 'id', $where);

                                if ($id_right_lire) {
                                    if (!(int) $this->db->getValue('usergroup_rights', 'rowid', 'fk_usergroup = ' . $this->id . ' AND fk_id = ' . $id_right_lire)) {
                                        if ($this->db->insert('usergroup_rights', array(
                                                    'entity'       => 1,
                                                    'fk_usergroup' => $this->id,
                                                    'fk_id'        => $id_right_lire
                                                )) > 0) {
                                            $nOk++;
                                            $results[$id_right_lire] = 1;
                                        } else {
                                            $sql_err = $this->db->err();
                                            $right_lire_def = $this->db->getRow('rights_def', 'id = ' . $id_right_lire, null, 'array');
                                            $label = $right_def['module'] . '->' . $right_lire_def['perms'] . (!empty($right_lire_def['subperms']) ? '->' . $right_lire_def['subperms'] : '');
                                            $warnings[] = 'Echec de l\'ajout du droit "' . $label . '"' . ($sql_err ? ' - ' . $sql_err : '');
                                        }
                                    }
                                }
                            }
                        } else {
                            $sql_err = $this->db->err();
                            $label = $right_def['module'] . '->' . $right_def['perms'] . (!empty($right_def['subperms']) ? '->' . $right_def['subperms'] : '');
                            $warnings[] = 'Echec de l\'ajout du droit "' . $label . '"' . ($sql_err ? ' - ' . $sql_err : '');

                            $results[$id_right] = 0;
                        }
                    } else {
                        $label = $right_def['module'] . '->' . $right_def['perms'] . (!empty($right_def['subperms']) ? '->' . $right_def['subperms'] : '');
                        //                        $warnings[] = 'l\'utilisateur possède déjà le droit "' . $label . '"'; // Ouch! 
                        $warnings[] = 'le groupe possède déjà le droit "' . $label . '"'; // Ouch! 
                    }

                    if ($nOk === 1) {
                        $success = 'Droit ajouté avec succès';
                    } elseif ($nOk > 1) {
                        $success = $nOk . ' droits ont été ajoutés avec succès';
                    }
                }
            }
        }


        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'results'  => $results
        );
    }

    public function actionRemoveRight($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_rights = BimpTools::getArrayValueFromPath($data, 'id_rights', array());
        $results = array();

        if (empty($id_rights)) {
            $errors[] = 'Aucun droit sélectionné';
        } else {
            $nOk = 0;

            foreach ($id_rights as $id_right) {
                $right_def = $this->db->getRow('rights_def', 'id = ' . $id_right, null, 'array');

                if (is_null($right_def)) {
                    $warnings[] = 'Le droit #' . $id_right . ' n\'existe plus';
                } else {
                    if ((int) $this->db->getValue('usergroup_rights', 'rowid', 'fk_usergroup = ' . $this->id . ' AND fk_id = ' . $id_right)) {
                        $module = BimpTools::getArrayValueFromPath($right_def, 'module', '');
                        $subperms = BimpTools::getArrayValueFromPath($right_def, 'subperms', '');
                        $perms = BimpTools::getArrayValueFromPath($right_def, 'perms', '');

                        if ($this->db->delete('usergroup_rights', "fk_usergroup = " . $this->id . " AND fk_id = " . $id_right)) {
                            $nOk++;

                            $results[$id_right] = 1;

                            if ($module) {
                                // Si droit lire, suppr des droits du même ensemble: 
                                if (in_array($subperms, array('lire', 'read')) || in_array($perms, array('lire', 'read'))) {
                                    $filters = array(
                                        'a.fk_usergroup' => $this->id,
                                        'r.module'       => $module,
                                    );

                                    if (in_array($subperms, array('lire', 'read'))) {
                                        $filters['r.perms'] = $perms;
                                        $filters['r.subperms'] = 'IS_NOT_NULL';
                                    }

                                    $sql = BimpTools::getSqlFullSelectQuery('usergroup_rights', array('a.rowid, r.id as id_right'), $filters, array(
                                                'r' => array(
                                                    'table' => 'rights_def',
                                                    'on'    => 'r.id = a.fk_id',
                                                    'alias' => 'r'
                                                )
                                    ));

                                    $extra_rights = $this->db->executeS($sql, 'array');

                                    if (is_array($extra_rights)) {
                                        foreach ($extra_rights as $er) {
                                            if (!in_array((int) $er['id_right'], $id_rights)) {
                                                if ($this->db->delete('usergroup_rights', 'rowid = ' . (int) $er['rowid']) <= 0) {
                                                    $sql_err = $this->db->err();
                                                    $extra_right_def = $this->db->getRow('rights_def', 'id = ' . (int) $er['id_right'], null, 'array');
                                                    $label = $extra_right_def['module'] . '->' . $extra_right_def['perms'] . (!empty($extra_right_def['subperms']) ? '->' . $extra_right_def['subperms'] : '');
                                                    $warnings[] = 'Echec de la suppression du droit "' . $label . '"' . ($sql_err ? ' - ' . $sql_err : '');
                                                } else {
                                                    $nOk++;
                                                    $results[$er['id_right']] = 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $sql_err = $this->db->err();
                            $label = $right_def['module'] . '->' . $right_def['perms'] . (!empty($right_def['subperms']) ? '->' . $right_def['subperms'] : '');
                            $warnings[] = 'Echec de la suppression du droit "' . $label . '"' . ($sql_err ? ' - ' . $sql_err : '');

                            $results[$id_right] = 0;
                        }
                    } else {
                        $label = $right_def['module'] . '->' . $right_def['perms'] . (!empty($right_def['subperms']) ? '->' . $right_def['subperms'] : '');
                        $warnings[] = 'Le groupe ne possède déjà pas le droit "' . $label . '"';
                    }
                }
            }

            if ($nOk === 1) {
                $success = 'Droit retiré avec succès';
            } elseif ($nOk > 1) {
                $success = $nOk . ' droits ont été retirés avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'results'  => $results
        );
    }

    public function actionGetAllEmails($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $active_only = (int) BimpTools::getArrayValueFromPath($data, 'active_only', 1);

        $users = $this->getUserGroupUsers($active_only);
        $emails = '';

        foreach ($users as $user) {
            $email = BimpTools::cleanEmailsStr($user->getData('email'));
            $emails .= ($emails ? ',' : '') . $email;
        }

        $title = 'Liste des adresses e-mails du groupe "' . $this->getName() . '"';
        $html = '';

        if (!$emails) {
            $html .= BimpRender::renderAlerts('Aucune adresse e-mail trouvée', 'warning');
        } else {
            $html .= '<pre>' . $emails . '</pre>';
        }

        $sc = 'setTimeout(function() {bimpModal.newContent(\'' . $title . '\', \'' . $html . '\');}, 500)';

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }
}
