<?php

class Bimp_User extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );

    // Gestion des droits: 

    public function canView()
    {
        global $user;

        if ((int) $user->id === (int) $this->id) {
            return 1;
        }

        if ($user->admin || $user->rights->user->user->lire) {
            return 1;
        }

        return 0;
    }

    public function canCreate()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 1;
        }

        return 0;
    }

    public function canEdit()
    {
        global $user;

        if ($this->id == $user->id) {
            return 1;
        }

        return $this->canCreate();
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    public function canViewField($field_name)
    {
        switch ($field_name) {
            case 'office_phone':
                return 1;
        }

        return parent::canViewField($field_name);
    }

    public function canViewUserCommissions()
    {
        return 0;
    }

    // Getters: 

    public function getCardFields($card_name)
    {
        $fields = parent::getCardFields($card_name);

        switch ($card_name) {
            case 'default':
                $fields[] = 'address';
                $fields[] = 'zip';
                $fields[] = 'town';
                $fields[] = 'fk_country';
                $fields[] = 'office_phone';
                $fields[] = 'user_mobile';
                $fields[] = 'email';
                $fields[] = 'skype';
                break;
        }

        return $fields;
    }

    public function getLinkFields($with_card = true)
    {
        $fields = parent::getLinkFields($with_card);
        $fields[] = 'statut';

        return $fields;
    }

    public function getPageTitle()
    {
        return $this->getInstanceName();
    }

    public function getLink($params = array(), $forced_context = '')
    {
        if ($this->isLoaded() && $this->getData('statut') == 0) {
            $params['disabled'] = true;
        }

        return parent::getLink($params, $forced_context);
    }

    public static function getUsersByShipto($shipTo)
    {
        if (!$shipTo) {
            return array();
        }

        $shipTo = (int) $shipTo;

        $cache_key = 'users_gsx_data_fro_shipto_' . $shipTo;

        if (!isset(BimpCache::$cache[$cache_key])) {
            BimpCache::$cache[$cache_key] = array();

            $sql = 'SELECT u.`rowid` as id, u.email, ue.apple_techid as techid, ue.apple_centre as centre_sav FROM ' . MAIN_DB_PREFIX . 'user u';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user_extrafields ue ON u.rowid = ue.fk_object';
            $sql .= ' WHERE ue.apple_shipto = \'' . $shipTo . '\' AND ue.apple_techid IS NOT NULL AND u.statut = 1 AND ue.gsxresa = 1';

            $rows = BimpCache::getBdb()->executeS($sql, 'array');

            $centre_sav = '';

            foreach ($rows as $r) {
                if (!empty($r['centre_sav'])) {
                    $centres = explode(' ', $r['centre_sav']);
                    if (isset($centres[0]) && $centres[0] != "") {
                        $centre_sav = $centres[0];
                    } elseif (isset($centres[1]) && $centres[1] != "") {
                        $centre_sav = $centres[1];
                    }
                }

                BimpCache::$cache[$cache_key][] = array(
                    'id'     => $r['id'],
                    'techid' => $r['techid'],
                    'email'  => $r['email'],
                    'centre' => $centre_sav
                );
            }
        }

        return BimpCache::$cache[$cache_key];
    }

    // Getters params: 

    public function getEditFormName()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 'default';
        }

        if ((int) $user->id === (int) $this->id) {
            return 'light';
        }

        return null;
    }

    public function getDefaultListHeaderButton()
    {
        $buttons = array();

        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => 'Générer export des congés',
            'icon_before' => 'fas_file-excel',
            'attr'        => array(
                'type'    => 'button',
                'onclick' => $this->getJsActionOnclick('exportConges', array(
                    'types_conges' => json_encode(array(0, 1, 2)), 'types_valide' => json_encode(array(0, 1))
                        ), array(
                    'form_name' => 'export_conges'
                ))
            )
        );

//        global $user;
//
//        if ($user->admin) {
//            $buttons[] = array(
//                'classes'     => array('btn', 'btn-default'),
//                'label'       => 'Rediriger les demandes de validation',
//                'icon_before' => 'fas_exchange-alt',
//                'attr'        => array(
//                    'type'    => 'button',
//                    'onclick' => $this->getJsLoadModalCustomContent('renderValidationsRedirForm', 'Redirection des demandes de validation', array())
//                )
//            );
//        }

        return $buttons;
    }

    public function getActionsButtons()
    {
        $buttons = array();

        return $buttons;
    }

    // Affichage: 

    public function displayCountry()
    {
        $id = (int) $this->getData('fk_country');
        if ($id) {
            $countries = BimpCache::getCountriesArray();
            if (isset($countries[$id])) {
                return $countries[$id];
            }
        }
        return '';
    }

    public function displayFullAddress($icon = false, $single_line = false)
    {
        $html = '';

        if ($this->getData('address')) {
            $html .= $this->getData('address') . ($single_line ? ' - ' : '<br/>');
        }

        if ($this->getData('zip')) {
            $html .= $this->getData('zip');

            if ($this->getData('town')) {
                $html .= ' ' . $this->getData('town');
            }
            $html .= ($single_line ? '' : '<br/>');
        } elseif ($this->getData('town')) {
            $html .= $this->getData('town') . ($single_line ? '' : '<br/>');
        }

        if ($this->getData('fk_pays')) {
            $html .= $this->displayCountry();
        }

        if ($html && $icon) {
            $html = BimpRender::renderIcon('fas_map-marker-alt', 'iconLeft') . $html;
        }

        return $html;
    }

    public function displayFullContactInfos($icon = true, $single_line = false)
    {
        $html = '';

        if ($single_line) {
            $phone = $this->getData('office_phone');
            $mobile = $this->getData('user_mobile');
            $mail = $this->getData('email');

            if ($phone) {
                $html .= ($icon ? BimpRender::renderIcon('fas_phone', 'iconLeft') : '') . $phone;
            }
            if ($mobile) {
                $html .= ($html ? ' - ' : '') . ($icon ? BimpRender::renderIcon('fas_mobile-alt', 'iconLeft') : '') . $mobile;
            }

            if ($mail) {
                $html .= ($html ? ' - ' : '');
                $html .= '<a href="mailto:' . $mail . '">';
                $html .= ($icon ? BimpRender::renderIcon('fas_envelope', 'iconLeft') : '') . $mail;
                $html .= '</a>';
            }
        } else {
            foreach (array(
        'user_mobile'  => 'fas_mobile',
        'office_phone' => 'fas_phone',
        'email'        => 'fas_envelope',
        'fax'          => 'fas_fax',
        'skype'        => 'fab_skype',
        'url'          => 'fas_globe',
            ) as $field => $icon_class) {
                if ($this->getData($field)) {
                    if ($field === 'email') {
                        $html .= '<a href="mailto:' . $this->getData('email') . '">';
                    } elseif ($field === 'url') {
                        $html .= '<a href="' . $this->getData('url') . '" target="_blank">';
                    }

                    $html .= ($html ? '<br/>' : '') . ($icon ? BimpRender::renderIcon($icon_class, 'iconLeft') : '') . $this->getData($field);

                    if (in_array($field, array('email', 'url'))) {
                        $html .= '</a>';
                    }
                }
            }
        }


        return $html;
    }

    public function displayPublicEmail()
    {
        $emails = BimpTools::cleanEmailsStr($this->getData('email'));

        if ($emails) {
            $emails = explode(',', $emails);
            foreach ($emails as $e) {
                if (preg_match('/^.+@bimp\.fr$/', $e)) {
                    return $e;
                }
            }
        }

        return '';
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $html = $this->displayFullAddress(1, 1);

        $contact_infos = $this->displayFullContactInfos(1, 1);

        if ($contact_infos) {
            $html .= ($html ? '<br/>' : '') . $contact_infos;
        }

        return $html;
    }

    public function renderPageView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        global $user;

        $tabs = array();

        $isAdmin = $user->admin;
        $isItself = ($user->id == $this->id);

        $tabs[] = array(
            'id'      => 'default',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $this->renderView('default', false)
        );

        if ($isAdmin || $isItself) {
            $tabs[] = array(
                'id'      => 'params',
                'title'   => BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Paramètres',
                'content' => $this->renderParamsView()
            );
        }

        if ($isAdmin) {
            $tabs[] = array(
                'id'            => 'perms',
                'title'         => BimpRender::renderIcon('fas_check', 'iconLeft') . 'Permissions',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderPermsView', '$(\'#perms .nav_tab_ajax_result\')', array(''), array('button' => ''))
            );
            $tabs[] = array(
                'id'            => 'groups',
                'title'         => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Groupes',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#groups .nav_tab_ajax_result\')', array('user_groups'), array('button' => ''))
            );

//            $tabs[] = array(
//                'id'      => 'groups',
//                'title'   => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Groupes',
//                'content' => $this->renderUserGroups()
//            );
        }

        if ($isAdmin || $isItself || $this->canViewUserCommissions()) {
            $tabs[] = array(
                'id'            => 'commissions',
                'title'         => BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Commissions',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#commissions .nav_tab_ajax_result\')', array('commissions'), array('button' => ''))
            );
        }

        $tabs[] = array(
            'id'      => 'commercial',
            'title'   => BimpRender::renderIcon('fas_briefcase', 'iconLeft') . 'Commercial',
            'content' => $this->renderCommercialView()
        );
        return BimpRender::renderNavTabs($tabs);
    }

    public function renderParamsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        global $user;

        if ($user->admin || $user->id === $this->id) {
            $tabs = array();

            $tabs[] = array(
                'id'            => 'interface_tab',
                'title'         => 'Interface',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderInterfaceView', '$(\'#interface_tab .nav_tab_ajax_result\')', array(), array('button' => ''))
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

    public function renderPermsView()
    {
        $html = 'Permission - en cours de développement';

        return $html;
    }

    function showUserTheme($object, $edit = 0, $foruserprofile = false)
    {
        global $conf, $langs;

        $dirthemes = array('/theme');
        if (!empty($conf->modules_parts['theme'])) {  // Using this feature slow down application
            foreach ($conf->modules_parts['theme'] as $reldir) {
                $dirthemes = array_merge($dirthemes, (array) ($reldir . 'theme'));
            }
        }
        $dirthemes = array_unique($dirthemes);

        $selected_theme = '';
        $title = '';

        if (empty($foruserprofile))
            $selected_theme = $conf->global->MAIN_THEME;
        else
            $selected_theme = ((is_object($object) && !empty($object->conf->MAIN_THEME)) ? $object->conf->MAIN_THEME : '');

        $i = 0;
        foreach ($dirthemes as $dir) {
            $dirtheme = dol_buildpath($dir, 0); // This include loop on $conf->file->dol_document_root
            $urltheme = dol_buildpath($dir, 1);
            if (is_dir($dirtheme)) {
                $handle = opendir($dirtheme);
                if (is_resource($handle)) {
                    while (($subdir = readdir($handle)) !== false) {
                        if (is_dir($dirtheme . "/" . $subdir) && substr($subdir, 0, 1) <> '.' && substr($subdir, 0, 3) <> 'CVS' && !preg_match('/common|phones/i', $subdir)) {
                            // Disable not stable themes (dir ends with _exp or _dev)
                            if ($conf->global->MAIN_FEATURES_LEVEL < 2 && preg_match('/_dev$/i', $subdir))
                                continue;
                            if ($conf->global->MAIN_FEATURES_LEVEL < 1 && preg_match('/_exp$/i', $subdir))
                                continue;

                            $html .= '<div class="inline-block" style="margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';

                            $file = $dirtheme . "/" . $subdir . "/thumb.png";
                            $url = $urltheme . "/" . $subdir . "/thumb.png";

                            if (!file_exists($file))
                                $url = DOL_URL_ROOT . '/public/theme/common/nophoto.png';

                            $html .= '<a href="' . $_SERVER["PHP_SELF"] . ($edit ? '?action=edit&theme=' : '?theme=') . $subdir . (GETPOST('optioncss', 'alpha', 1) ? '&optioncss=' . GETPOST('optioncss', 'alpha', 1) : '') . ($object ? '&id=' . $object->id : '') . '" style="font-weight: normal;" alt="' . $langs->trans("Preview") . '">';

                            if ($subdir == $conf->global->MAIN_THEME)
                                $title = $langs->trans("ThemeCurrentlyActive");
                            else
                                $title = $langs->trans("ShowPreview");

                            $html .= '<img src="' . $url . '" border="0" width="80" height="60" alt="' . $title . '" title="' . $title . '" style="margin-bottom: 5px;">';
                            $html .= '</a><br>';

                            if ($subdir == $selected_theme) {
                                $html .= '<input ' . ($edit ? '' : 'disabled') . ' type="radio" class="themethumbs" style="border: 0px;" checked name="main_theme" value="' . $subdir . '"> <b>' . $subdir . '</b>';
                            } else {
                                $html .= '<input ' . ($edit ? '' : 'disabled') . ' type="radio" class="themethumbs" style="border: 0px;" name="main_theme" value="' . $subdir . '"> ' . $subdir;
                            }
                            $html .= '</div>';

                            $i++;
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderInterfaceView()
    {
        global $conf, $langs, $db;
        BimpTools::loadDolClass("user");
        $object = new User($db);
        $object->fetch(GETPOST('id'), "", "", 1);
        $object->getrights();

        // Load translation files required by page
        $langs->loadLangs(array('companies', 'products', 'admin', 'users', 'languages', 'projects', 'members'));

        $tmparray = array('index.php' => 'Dashboard');

        $html .= '<table class="noborder" width="100%">';
        $html .= '<tr class="liste_titre"><td width="50%">' . $langs->trans("Parameter") . '</td><td width="50%">' . $langs->trans("DefaultValue") . '</td></tr>';

        // Language
        $html .= '<tr class="oddeven"><td>' . $langs->trans("Language") . '</td>';
        $html .= '<td>';
        $s = picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
        $html .= ($s ? $s . ' ' : '');
        $html .= (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->global->MAIN_LANG_DEFAULT == 'auto' ? $langs->trans("AutoDetectLang") : $langs->trans("Language_" . $conf->global->MAIN_LANG_DEFAULT));
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr class="oddeven"><td>' . $langs->trans("MaxSizeList") . '</td>';
        $html .= '<td>' . (!empty($conf->global->MAIN_SIZE_LISTE_LIMIT) ? $conf->global->MAIN_SIZE_LISTE_LIMIT : '&nbsp;') . '</td></tr>';

        // Taille max des listes
        $html .= '<tr class="oddeven"><td>' . $langs->trans("MaxSizeList") . '</td>';
        $html .= '<td>' . $conf->global->MAIN_SIZE_LISTE_LIMIT . '</td></tr>';
        $html .= '</table><br>';

        //Thème
        $html .= $this->showUserTheme($object, 0, true);

        return $html;
    }

    public function renderCommercialView()
    {
        $tabs = array();

        $tabs[] = array(
            'id'            => 'user_clients_tab',
            'title'         => BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Clients',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#user_clients_tab .nav_tab_ajax_result\')', array('clients'), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs, 'conges_tabs');
    }

    public function renderUserGroups()
    {
        $html = '';

//        global $db;
//        
//        //$id_group = $this->getIdGroup();
//
//        $form = new Form($db);
//        $exclude = array();
//
//        $html = '';
//
//        $rows = BimpCache::getUserUserGroupsArray($this->id, 0, 0);
//
//        $html .= '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $this->id . '" method="POST">' . "\n";
//        $html .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
//        $html .= '<input type="hidden" name="action" value="addgroup" />';
//
//        $html .= '<table class="noborder" width="100%">'
//                . '<tbody>'
//                . '<tr class="liste_titre"><th class="liste_titre">Groupes</th>'
//                . '<th class="liste_titre" align="right">';
//
//        $html .= $form->select_dolgroups('', 'usergroup', 1, $exclude, 0, '', '', $this->entity, 0, 0, '', 0, '', 'maxwidth300');
//
//        //$html .= BimpInput::renderInput('search_group', 'id_group', $id_group);
//
//        $keys = array_keys($rows);
//        $i = -1;
//        foreach ($rows as $row) {
//            $i++;
//            $html .= '<tr class="oddeven">'
//                    . '<td>'
//                    . '<img src="' . DOL_URL_ROOT . '/theme/BimpTheme/img/object_group.png" alt="" title="Afficher groupe" class="inline-block">'
//                    . '<a href="' . DOL_URL_ROOT . '/user/group/card.php?id=' . $keys[$i] . '"> ' . $row . ' </a>'
//                    . '</td>'
//                    . '<td align="right">'
//                    . '<a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $this->id . '&amp;action=removegroup&amp;group=' . $keys[$i] . '">'
//                    . '<span class="fa fa-chain-broken marginleftonly valignmiddle" style=" color: #555;" alt="Supprimer du groupe" title="Supprimer du groupe">'
//                    . '</span>'
//                    . '</a>'
//                    . '</td>'
//                    . '</tr>';
//        }
//
//        $html .= '</th>'
//                . '</tr>'
//                . '</tbody>'
//                . '</table>'
//                . '</form>';

        return $html;
    }

    public function renderLinkedObjectsList($list_type)
    {
        global $db;

        $html = '';

        $errors = array();
        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $list = null;
        $user_label = $this->getName();

        switch ($list_type) {
            // Onglet "Groupes": 
            case 'user_groups':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_UserGroup'), 'user', 1, null, 'Liste des groupes de "' . $user_label . '"', 'fas_users');
                $list->addFieldFilterValue('ugu.fk_user', $this->id);
                break;

//              return "Groupes aux quel appartient l'utilisateur";
            // Onglet "Liste des configurations de listes": 
            case 'lists_configs':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'ListConfig'), 'default', 1, null, 'Liste des configurations de listes de "' . $user_label . '"', 'fas_cog');
                //$list->addFieldFilterValue('id_owner', $this->id);
                break;

            // Onglet "'Liste des configuration de filtres": 
            case 'filters_configs':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'ListTableConfig'), 'default', 1, null, 'Liste des configuration de filtres de "' . $user_label . '"', 'fas_cog');
                $list->addFieldFilterValue('owner_type', ListTableConfig::OWNER_TYPE_USER);
                $list->addFieldFilterValue('id_owner', $this->id);
                break;

            case 'lists_filters':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'ListFilters'), 'default', 1, null, 'Filtres enregistrés de "' . $user_label . '"', 'fas_cog');
                $list->addFieldFilterValue('owner_type', ListFilters::OWNER_TYPE_USER);
                $list->addFieldFilterValue('id_owner', $this->id);
                break;

            // Onglet "Commission": 
            case 'commissions':
                $list = new BC_ListTable(BimpObject::getInstance('bimpfinanc', 'BimpCommission'), 'user', 1, null, 'Commissions de "' . $user_label . '"', 'fas_comment-dollar');
                $list->addFieldFilterValue('type', BimpCommission::TYPE_USER);
                $list->addFieldFilterValue('id_user', $this->id);
                break;

            // Onglet "Commercial": 
            case 'clients':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Client'), 'default', 1, null, 'Clients dont "' . $user_label . '" est le commercial', 'fas_user-circle');
                $sql = $this->id . ' IN (SELECT sc.fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux sc WHERE sc.fk_soc = a.rowid)';
                $list->addFieldFilterValue('commercial_custom', array(
                    'custom' => $sql
                ));
                break;
        }

        if (is_a($list, 'BC_ListTable')) {
            $html .= $list->renderHtml();
        } elseif ($list_type && !$html) {
            $html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
        } elseif (!$html) {
            $html .= BimpRender::renderAlerts('Type de liste non spécifié');
        }

        return $html;
    }

    public function renderValidationCommandeRedirFormInputs()
    {
        return '';
    }

    public function renderValidationsRedirForm()
    {
        $html = '';

        $html .= '<div class="singleLineForm">';
        $html .= '<div class="singleLineFormCaption">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft');
        $html .= 'Ajouter une redirection';
        $html .= '</div>';

        $html .= '<div class="singleLineFormContent">';
        $html .= '<label>';
        $html .= 'Rediriger les demandes envoyées à';
        $html .= '</label>';

        $html .= BimpInput::renderInputContainer('validations_redir_from', 0, BimpInput::renderInput('search_user', 'validations_redir_from', 0));

        $html .= '<label>';
        $html .= 'Vers';
        $html .= '</label>';

        $html .= BimpInput::renderInputContainer('validations_redir_to', 0, BimpInput::renderInput('search_user', 'validations_redir_to', 0));
        $html .= '</div>';

        $html .= '<div class="align-right" style="margin: 5px">';
        $html .= '<span class="btn btn-primary" onclick="">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $html .= '</span>';
        $html .= '</div>';

        $html .= $this->renderValidationsRedirsList();

        $html .= '</div>';

        return $html;
    }

    public function renderValidationsRedirsList()
    {
        $html = '';

        $redirs = json_decode(BimpCore::getConf('users_validations_redirections', '[]'), 1);

        if (is_array($redirs) && !empty($redirs)) {
            $html .= '<div style="margin-top: 15px">';
            $html .= '<h4>Liste des redirections</h4>';

            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($redirs as $redir) {
                
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        return $html;
    }

    // Actions: 

    public function actionExportConges($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $date_from = (isset($data['date_from']) ? $data['date_from'] : '');
        $date_to = (isset($data['date_to']) ? $data['date_to'] : '');

        if (!$date_from) {
            $errors[] = 'Veuillez indiquer une date de début';
        }

        if (!$date_to) {
            $errors[] = 'Veuillez indiquer une date de fin';
        }

        if ($date_to < $date_from) {
            $errors[] = 'La date de fin est inférieure à la date de début';
        }

        $types_conges = (isset($data['types_conges']) ? $data['types_conges'] : array());
        $types_valide = (isset($data['types_valide']) ? $data['types_valide'] : array());

        if (empty($types_conges)) {
            $errors[] = 'Veuillez sélectionner au moins un type de congé';
        }
        if (empty($types_valide)) {
            $errors[] = 'Veuillez sélectionner au moins un type de validation';
        }

        if (!count($errors)) {
            $where = 'date_debut <= \'' . $date_to . '\'';
            $where .= ' AND date_fin >= \'' . $date_from . '\'';

            $where .= ' AND type_conges IN (' . implode(',', $types_conges) . ')';
            if (in_array(0, $types_valide) AND in_array(1, $types_valide))
                $where .= '';
            elseif (in_array(1, $types_valide))
                $where .= ' AND statut IN (1, 2, 3)';
            elseif (in_array(0, $types_valide))
                $where .= ' AND statut IN (6)';



            $rows = $this->db->getRows('holiday', $where, null, 'array', null, 'rowid', 'desc');

            if (empty($rows)) {
                $warnings[] = 'Aucun congé trouvé';
            } else {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

                $data = array();

                $userCP = new User($this->db->db);
                $typesCongesLabels = array(
                    0 => 'Congés payés',
                    1 => 'Absence exceptionnelle',
                    2 => 'RTT'
                );

                foreach ($rows as $r) {
                    if ($r['date_debut'] < $date_from) {
                        $r['date_debut'] = $date_from;
                    }
                    if ($r['date_fin'] > $date_to) {
                        $r['date_fin'] = $date_to;
                    }

                    $date_debut_gmt = $this->db->db->jdate($r['date_debut'], 1);
                    $date_fin_gmt = $this->db->db->jdate($r['date_fin'], 1);
                    $nbJours = num_open_dayUser((int) $r['fk_user'], $date_debut_gmt, $date_fin_gmt, 0, 1, (int) $r['halfday']);
                    $userCP->fetch((int) $r['fk_user']);

                    if (!BimpObject::objectLoaded($userCP)) {
                        $warnings[] = 'L\'utilisateur #' . $r['fk_user'] . ' n\'existe plus - non inclus dans le fichier';
                        continue;
                    }

                    $dt_from = new DateTime($r['date_debut']);
                    $dt_to = new DateTime($r['date_fin']);

                    $data[] = array(
                        $userCP->lastname,
                        $userCP->firstname,
                        $this->db->getValue('user', 'matricule', 'rowid = ' . (int) $userCP->id),
                        $userCP->town,
                        $typesCongesLabels[(int) $r['type_conges']],
                        str_replace(';', ',', $r['description']),
                        $dt_from->format('d / m / Y'),
                        $dt_to->format('d / m / Y'),
                        $nbJours
                    );
                }

                $str = 'NOM;PRENOM;MATRICULE;VILLE;TYPE CONGES;INFOS;DATE DEBUT;DATE FIN;NOMBRE JOURS' . "\n";

                foreach ($data as $line) {
                    $fl = true;
                    foreach ($line as $data) {
                        if (!$fl) {
                            $str .= ';';
                        } else {
                            $fl = false;
                        }
                        $str .= '"' . $data . '"';
                    }
                    $str .= "\n";
                }

                if (file_put_contents(DOL_DATA_ROOT . '/bimpcore/export_conges.csv', $str)) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode('export_conges.csv');
                    $success_callback = 'window.open(\'' . $url . '\');';
                } else {
                    $errors[] = 'Echec de la création du fichier';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->isLoaded()) {
            $this->dol_object->oldcopy = clone $this->dol_object;
        }

        return parent::update($warnings, $force_update);
    }

    // Gestion des groupes: (Temporaire jusqu'à création de Bimp_UserGroup) 

    public static function displayUserGroup($id_group, $display_type = 'nom', $with_icon = false)
    {
        $html = '';

        $groups = BimpCache::getUserGroupsArray();
        $icon = '';

        if ($with_icon) {
            $icon = BimpRender::renderIcon('fas_users', 'iconLeft');
        }

        if (isset($groups[$id_group])) {
            switch ($display_type) {
                case 'nom_url';
                    $url = BimpTools::getDolObjectUrl('UserGroup', $id_group);
                    if ($url) {
                        $html .= '<a href="' . $url . '" target="_blank">' . $icon . $groups[$id_group] . '</a>';
                        break;
                    }

                case 'nom':
                default:
                    $html .= $icon . $groups[$id_group];
                    break;
            }
        } else {
            $html .= $icon . 'Groupe #' . $id_group;
        }

        return $html;
    }
}
