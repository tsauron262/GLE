<?php

class Bimp_User extends BimpObject
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );
    public static $days = array(// de 1 à 6 : jours semaines impaires - de 8 à 13 : jours semaines paires
        1  => 'Lundi (sem. impaires)',
        2  => 'Mardi (sem. impaires)',
        3  => 'Mercredi  (sem. impaires)',
        4  => 'Jeudi  (sem. impaires)',
        5  => 'Vendredi  (sem. impaires)',
        6  => 'Samedi  (sem. impaires)',
        8  => 'Lundi (sem. paires)',
        9  => 'Mardi (sem. paires)',
        10 => 'Mercredi (sem. paires)',
        11 => 'Jeudi (sem. paires)',
        12 => 'Vendredi (sem. paires)',
        13 => 'Samedi (sem. paires)'
    );

    public function __construct($module, $object_name)
    {
        if (BimpTools::isModuleDoliActif('MULTICOMPANY'))
            $this->redirectMode = 5;
        return parent::__construct($module, $object_name);
    }

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

        return $this->canCreate();
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

        if ($user->login == 'l.gay') {
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

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'addRight':
            case 'removeRight':
            case 'addToGroup':
            case 'removeFromGroup':
                if ((int) $user->rights->user->user->creer || $user->admin) {
                    return 1;
                }
                return 0;

            case 'editInterfaceParams':
                if ($user->admin || ($user->id == $this->id)) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'extra_materiel':
                global $user;

                if ($user->admin || $user->login == 'l.gay') {
                    return 1;
                }
                return 0;
        }
        return parent::canEditField($field_name);
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = [])
    {
        if (in_array($action, array('addRight', 'removeRight', 'addToGroup'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isOff($date = null, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }
        if (is_null($date)) {
            $dt = new DateTime();
        } elseif (is_string($date)) {
            $dt = new DateTime($date);
        } elseif (is_a($date, 'DateTime')) {
            $dt = $date;
        } else {
            $errors[] = "Format de la date invalide";
            return 0;
        }

        foreach ($this->getData('day_off') as $id_day_off) {
            if ((int) $dt->format('W') % 2 == 0 && (int) $dt->format('w') + 7 == $id_day_off or
                    (int) $dt->format('W') % 2 == 1 && (int) $dt->format('w') == $id_day_off) {
                return 1;
            }
        }

        return 0;
    }

    public function isAvailable($date = null, &$errors = array(), &$unavailable_reason = '')
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if (!(int) $this->getData('statut')) {
            $unavailable_reason = 'inactif';
            return 0;
        }

        if (empty($date)) {
            $dt = new DateTime();
        } elseif (is_string($date)) {
            $dt = new DateTime($date);
        } elseif (is_a($date, 'DateTime')) {
            $dt = $date;
        }

        $hour = (int) $dt->format('h');

        if ($hour < 12) {
            // Si on est avant midi, on vérifie les dispo à 10h
            $date = $dt->format('Y-m-d 10:00:00');
        } elseif ($hour < 18) {
            // Si on est après-midi mais pas le soir, on vérifie les dispo à 15h
            $date = $dt->format('Y-m-d 15:00:00');
        } else {
            // Pendant la soirée on vérifie les dispo le lendemain matin (10h)
            $date = BimpTools::getNextOpenDay($dt->format('Y-m-d')) . ' 10:00:00';
        }

        // L'utilisateur est-il off ?
        if ($this->isOff($dt, $errors)) {
            $unavailable_reason = 'en off';
            return 0;
        }

        $sql = 'SELECT *';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
        $sql .= ' WHERE fk_user_action = ' . $this->id;
        $sql .= ' AND code IN ("CONGES", "RTT_DEM")';
        $sql .= ' AND (';

        $sql .= ' datep < "' . $date . '" AND ';
        $sql .= ' datep2 > "' . $date . '"';

        $sql .= ')';

        if (!empty(self::getBdb()->executeS($sql, 'object'))) {
            $unavailable_reason = 'en congé ou rtt';
            return 0;
        }

        return 1;
    }

    public function isUserSuperior($id_user_to_check, $max_depth = 100)
    {
        if ($this->isLoaded()) {
            $id_user = $this->id;
            for ($i = 0; $i < $max_depth; $i++) {
                $id_user = (int) $this->db->getValue('user', 'fk_user', 'rowid = ' . $id_user);

                if (!$id_user) {
                    break;
                }

                if ($id_user == $id_user_to_check) {
                    return 1;
                }
            }
        }

        return 0;
    }

    // Getters données: 

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
//                $fields[] = 'skype';
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

    public function getIsObjectActiveFields()
    {
        return array('statut');
    }

    public function getGroupsRights()
    {
        if ($this->isLoaded()) {
            return self::getUserGroupsRights($this->id);
        }

        return array();
    }

    public function getRights()
    {
        if ($this->isLoaded()) {
            return self::getUserRights($this->id);
        }

        return array();
    }

    public function getAllRights()
    {
        return array(
            'rights'        => $this->getRights(),
            'groups_rights' => $this->getGroupsRights()
        );
    }

    public function getUserParams()
    {
        if ($this->isLoaded()) {
            $cache_key = 'user_' . $this->id . '_userparams_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $where = 'fk_user = ' . $this->id;
                $rows = $this->db->getRows('user_param', $where, null, 'array');

                foreach ($rows as $row) {
                    self::$cache[$cache_key][$row['param']] = $row['value'];
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getUserParamValue($param_name, $default_value = '')
    {
        $params = $this->getUserParams();
        return BimpTools::getArrayValueFromPath($params, $param_name, $default_value);
    }

    public function getEmailOrSuperiorEmail($allow_default = true)
    {
        $email = '';

        if ((int) $this->getData('statut')) {
            $email = $this->getData('email');
        }

        if (!$email) {
            $id_superior = (int) $this->getData('fk_user');

            if ($id_superior) {
                $superior = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_superior);
                if (BimpObject::objectLoaded($superior) && (int) $superior->getData('statut')) {
                    $email = $superior->getData('email');
                }
            }
        }

        if (!$email && $allow_default) {
            $email = BimpCore::getConf('default_user_email', null);
        }

        return $email;
    }

    public function getFullName()
    {
        return $this->getData('firstname') . ' ' . $this->getData('lastname');
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

    public static function getUserRights($id_user)
    {
        if ((int) $id_user) {
            $cache_key = 'user_' . $id_user . '_rights';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $rows = self::getBdb()->getRows('user_rights', 'fk_user = ' . $id_user, null, 'array', array('fk_id'));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][] = (int) $r['fk_id'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function getUsersByShipto($shipTo)
    {
        if (!$shipTo) {
            return array();
        }

        $shipTo = (int) $shipTo;

        $cache_key = 'users_gsx_data_for_shipto_' . $shipTo;

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

    public function getActionsButtons()
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Afficher disponibilités',
            'icon'    => 'fas_user-check',
            'onclick' => $this->getJsActionOnclick('displayAvailabilities', array(), array(
                'form_name' => 'disponibilities'
            ))
        );

        if ($this->can('edit') && $this->isEditable()) {
            $buttons[] = array(
                'label'   => 'Changer la photo',
                'icon'    => 'fas_file-image',
                'onclick' => $this->getJsLoadModalForm('photo', 'Changer la photo')
            );
        }

        if ($this->isActionAllowed('editInterfaceParams') && $this->canSetAction('editInterfaceParams')) {
            $buttons[] = array(
                'label'   => 'Paramètres interface',
                'icon'    => 'fas_cog',
                'onclick' => $this->getJsActionOnclick('editInterfaceParams', array(), array(
                    'form_name' => 'interface_params'
                ))
            );
        }

        return $buttons;
    }

    public function getEditFormName()
    {
        global $user;
        if ($user->admin || (isset($user->rights->user->user->creer) && $user->rights->user->user->creer)) {
            return 'default';
        }

        if ((int) $user->id === (int) $this->id) {
            return 'light';
        }

        return 'light';
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
        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => 'Ajouter un utilisateur',
            'icon_before' => 'fas_plus-circle',
            'attr'        => array(
                'type'    => 'button',
                'onclick' => "document.location.replace('" . DOL_URL_ROOT . "/user/card.php?leftmenu=users&action=create');"
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

    public function getFilterByGroup()
    {
        $id_group = $_REQUEST['id'];
        $filters = array();
        $filters[] = [
            'name'   => 'rowid',
            'filter' => array(
                'in' => (array) $this->db->getValues('usergroup_user', 'fk_user', 'fk_usergroup = ' . $id_group)
            )
        ];

        return $filters;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'group':
                $elem_alias = $main_alias . '___usergroupuser';
                $joins[$elem_alias] = array(
                    'table' => 'usergroup_user',
                    'on'    => $elem_alias . '.fk_user = ' . $main_alias . '.rowid',
                    'alias' => $elem_alias
                );
                $key = 'in';
                if ($excluded) {
                    $key = 'not_in';
                }
                $filters[$elem_alias . '.fk_usergroup'] = array(
                    $key => $values
                );
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'group':
                $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', (int) $value);
                if (BimpObject::ObjectLoaded($group)) {
                    return $group->getName();
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
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
//        'skype'        => 'fab_skype',
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

    public function displayDaysOff()
    {
        $html = $this->displayData('day_off', 'default', false);

        if ($this->canEditField('day_off')) {
            $html .= '<div style="margin-top: 10px; text-align: right">';
            $html .= '<span class="btn btn-default" onclick="' . $this->getJsLoadModalForm('day_off', 'Modifier vos jours off') . '">';
            $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . 'Modifier';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderLogo($format = 'mini', $preview = false)
    {
        $html = '<div class="bimp_img_container">';
        $html .= Form::showphoto('userphoto', $this->dol_object, 100, 100, 0, '', $format, 0);
        $html .= '</div>';

        return $html;
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $html = $this->displayFullAddress(1, 1);

        $contact_infos = $this->displayFullContactInfos(1, 1);

        if ($contact_infos) {
            $html .= ($html ? '<br/>' : '') . $contact_infos;
        }

        $errors = array();
        $am_reason = '';
        $pm_reason = '';

        $dispo_am = $this->isAvailable(date('Y-m-d 10:00:00'), $errors, $am_reason);
        $dispo_pm = $this->isAvailable(date('Y-m-d 15:00:00'), $errors, $pm_reason);

        if (!$dispo_am || !$dispo_pm) {
            $html .= '<div style="margin-top: 10px">';
            if (!$dispo_am && !$dispo_pm && $am_reason == $pm_reason) {
                $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non disponible aujourd\'hui' . ($am_reason ? ' (' . $am_reason . ')' : '') . '</span>';
            } else {
                if (!$dispo_am) {
                    $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non disponible ce matin' . ($am_reason ? ' (' . $am_reason . ')' : '') . '</span>';
                }
                if (!$dispo_pm) {
                    $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non disponible cet après-midi' . ($pm_reason ? ' (' . $pm_reason . ')' : '') . '</span>';
                }
            }
            $html .= '</div>';
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

        $isUsersAdmin = $this->canCreate();
        $isAdmin = $user->admin;
        $isItself = ($user->id == $this->id);

        $tabs[] = array(
            'id'      => 'default',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $this->renderView('default', false)
        );

        if ($isAdmin || $isItself || $isUsersAdmin) {
            $tabs[] = array(
                'id'      => 'params',
                'title'   => BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Paramètres',
                'content' => $this->renderParamsView()
            );

            if ($isAdmin || $isUsersAdmin) {
                $tabs[] = array(
                    'id'            => 'perms',
                    'title'         => BimpRender::renderIcon('fas_check', 'iconLeft') . 'Permissions',
                    'ajax'          => 1,
                    'ajax_callback' => $this->getJsLoadCustomContent('renderPermsView', '$(\'#perms .nav_tab_ajax_result\')', array(''), array('button' => ''))
                );
            }

            if ($isAdmin || $isUsersAdmin) {
                $tabs[] = array(
                    'id'            => 'groups',
                    'title'         => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Groupes',
                    'ajax'          => 1,
                    'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#groups .nav_tab_ajax_result\')', array('user_groups'), array('button' => ''))
                );
            }

            if (BimpCore::isModuleActive('BIMPTASK'))
                $tabs[] = array(
                    'id'      => 'tasks',
                    'title'   => BimpRender::renderIcon('fas_tasks', 'iconLeft') . 'Mes tâches',
                    'content' => $this->renderTasksView()
                );
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

        $tabs[] = array(
            'id'      => 'materiel',
            'title'   => BimpRender::renderIcon('fas_tv', 'iconLeft') . 'Materiel',
            'content' => $this->renderMaterielView()
        );

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderTasksView()
    {
        $tabs = array();

        $task = BimpObject::getInstance('bimptask', 'BIMP_Task');
        $filtres = BIMP_Task::getFiltreDstRight($this->dol_object);

        // Liste mes tâches assignées
        $list = new BC_ListTable($task, 'default', 1, null, 'Mes tâches assignées');
        $list->addIdentifierSuffix('my_tasks');

        $list->addFieldFilterValue('id_user_owner', (int) $this->id);
        if (count($filtres[1]) > 0) {
            $list->addFieldFilterValue('dst', array(
                $filtres[0] => $filtres[1]
            ));
        }

        $tabs[] = array(
            'id'      => 'my_tasks',
            'title'   => 'Mes tâches assignées',
            'content' => $list->renderHtml()
        );

//        // Liste mes tâches créées
        $list = new BC_ListTable($task, 'default', 1, null, 'Mes tâches créées');
        $list->addIdentifierSuffix('by_me');
        $list->addFieldFilterValue('user_create', (int) $this->id);

        $tabs[] = array(
            'id'      => 'tasks_by_me',
            'title'   => 'Mes tâches crées',
            'content' => $list->renderHtml()
        );

        return BimpRender::renderNavTabs($tabs, 'tasks');
    }

    public function renderMaterielView()
    {
        $tabs = array();

        $tabs[] = array(
            'id'            => 'user_materiel_tab',
            'title'         => BimpRender::renderIcon('fas_tv', 'iconLeft') . 'Materiel',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#user_materiel_tab .nav_tab_ajax_result\')', array('materiel'), array('button' => ''))
        );
        $tabs[] = array(
            'id'            => 'user_materielNS_tab',
            'title'         => BimpRender::renderIcon('fas_tv', 'iconLeft') . 'Materiel non sérialisé',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#user_materielNS_tab .nav_tab_ajax_result\')', array('materielNS'), array('button' => ''))
        );

        $view = new BC_View($this, 'extra_materiel');

        $tabs[] = array(
            'id'      => 'user_extra_materiel',
            'title'   => BimpRender::renderIcon('fas_tv', 'iconLeft') . 'Autre materiel',
            'content' => $view->renderHtml()
        );

        return BimpRender::renderNavTabs($tabs, 'conges_tabs');
    }

    public function renderParamsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        global $user;

        if ($user->admin || $user->id === $this->id) {
            $tabs = array();

            //            $tabs[] = array(
            //                'id'            => 'interface_tab',
            //                'title'         => 'Interface',
            //                'ajax'          => 1,
            //                //'ajax_callback' => $params->renderList('default', true, 'Liste des paramètres', null, array('fk_user' => $user->id))
            //                'ajax_callback' => $this->getJsLoadCustomContent('renderInterfaceView', '$(\'#interface_tab .nav_tab_ajax_result\')', array(''), array('button' => ''))
            //            );

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
        $tabs = array();

        $tabs[] = array(
            'id'            => 'user_rights',
            'title'         => BimpRender::renderIcon('fas_user-check', 'iconLeft') . 'Droits utilisateur',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#user_rights .nav_tab_ajax_result\')', array('user_rights'), array('button' => ''))
        );

        $tabs[] = array(
            'id'            => 'usergroups_rights',
            'title'         => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Droits groupes de l\'utilisateur',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#usergroups_rights .nav_tab_ajax_result\')', array('usergroups_rights'), array('button' => ''))
        );

        $tabs[] = array(
            'id'            => 'all_rights',
            'title'         => BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Tous les droits',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderAllRightsList', '$(\'#all_rights .nav_tab_ajax_result\')', array(''), array('button' => ''))
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
            'groups'  => 'Groupes ayant le droit',
            'libelle' => 'Libellé',
        );

        $rights = BimpCache::getRightsDefDataByModules();
        $user_rights = $this->getAllRights();
        $user_groups = BimpCache::getUserUserGroupsList($this->id);

        $modules_list = array();

        $add_allowed = ($this->isActionAllowed('addRight') && $this->canSetAction('addRight'));
        $remove_allowed = ($this->isActionAllowed('removeRight') && $this->canSetAction('removeRight'));

        foreach ($rights as $module => $module_rights) {
            $modules_list[$module] = $module;
            foreach ($module_rights as $id_right => $data) {
                $has_groups_right = false;
                $active = '';
                $groups = '';
                $actions = '';

                if (isset($user_rights['groups_rights'][(int) $id_right])) {
                    foreach ($user_groups as $id_group) {
                        if (in_array((int) $id_group, $user_rights['groups_rights'][(int) $id_right])) {
                            $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);

                            if (BimpObject::objectLoaded($group)) {
                                $groups .= ($groups ? '<br/>' : '') . $group->getLink();
                                $has_groups_right = true;
                            }
                        }
                    }
                }

                if (in_array($id_right, $user_rights['rights'])) {
                    if ($add_allowed) {
                        $onclick = 'BimpUserRightsTable.addUserRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Ajouter', 'fas_plus', $onclick, 'add_right_button', array(
                                    'styles' => array('display' => 'none')
                        ));
                    }

                    if ($remove_allowed) {
                        $onclick = 'BimpUserRightsTable.removeUserRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Retirer', 'fas_minus', $onclick, 'remove_right_button');
                    }

                    $active = array(
                        'content' => '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'OUI</span>',
                        'value'   => 'yes'
                    );
                } else {
                    if ($add_allowed) {
                        $onclick = 'BimpUserRightsTable.addUserRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Ajouter', 'fas_plus', $onclick, 'add_right_button');
                    }

                    if ($remove_allowed) {
                        $onclick = 'BimpUserRightsTable.removeUserRights($(this), ' . $this->id . ', [' . $id_right . '])';
                        $actions .= BimpRender::renderRowButton('Retirer', 'fas_minus', $onclick, 'remove_right_button', array(
                                    'styles' => array('display' => 'none')
                        ));
                    }

                    if ($has_groups_right) {
                        $active = array(
                            'content' => '<span class="info">' . BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft') . 'Hérité</span>',
                            'value'   => 'inherit'
                        );
                    } else {
                        $active = array(
                            'content' => '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'NON</span>',
                            'value'   => 'no'
                        );
                    }
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
                    'groups'   => $groups,
                    'actions'  => $actions
                );
            }
        }

        $headers['module']['search_values'] = $modules_list;

        $buttons = '';

        if ($add_allowed || $remove_allowed) {
            $buttons .= '<div class="buttonsContainer">';
            if ($add_allowed) {
                $buttons .= '<span class="btn btn-default" onclick="BimpUserRightsTable.addSelectedRights($(this), ' . $this->id . ')">';
                $buttons .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter les droits sélectionnés';
                $buttons .= '</span>';
            }
            if ($remove_allowed) {
                $buttons .= '<span class="btn btn-default" onclick="BimpUserRightsTable.removeSelectedRights($(this), ' . $this->id . ')">';
                $buttons .= BimpRender::renderIcon('fas_minus-circle', 'iconLeft') . 'Retirer les droits sélectionnés';
                $buttons .= '</span>';
            }
            $buttons .= '</div>';
        }

        $html .= $buttons;

        $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                    'main_class' => 'bimp_user_rights_table',
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

    public function renderUserTheme($object, $edit = 0, $foruserprofile = false)
    {
        global $conf, $langs;

        $html = '';

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

        $hoverdisabled = '';
        if (empty($foruserprofile))
            $hoverdisabled = (isset($conf->global->THEME_ELDY_USE_HOVER) && $conf->global->THEME_ELDY_USE_HOVER == '0');
        else
            $hoverdisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_HOVER) || $fuser->conf->THEME_ELDY_USE_HOVER == '0') : '');

        $colspan = 2;
        if ($foruserprofile)
            $colspan = 4;

        $thumbsbyrow = 6;
        $html .= '<table class="noborder" width="100%">';

        // Title
        if ($foruserprofile) {
            $html .= '<tr class="liste_titre"><th class="titlefield">' . $langs->trans("Parameter") . '</th><th>' . $langs->trans("DefaultValue") . '</th>';
            $html .= '<th colspan="2">&nbsp;</th>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>' . $langs->trans("DefaultSkin") . '</td>';
            $html .= '<td>' . $conf->global->MAIN_THEME . '</td>';
            $html .= '<td align="left" class="nowrap" width="20%"><input id="check_MAIN_THEME" name="check_MAIN_THEME"' . ($edit ? '' : ' disabled') . ' type="checkbox" ' . ($selected_theme ? " checked" : "") . '> ' . $langs->trans("UsePersonalValue") . '</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '</tr>';
        } else {
            $html .= '<tr class="liste_titre"><th class="titlefield">' . $langs->trans("DefaultSkin") . '</th>';
            $html .= '<th align="right">';
            $url = 'https://www.dolistore.com/lang-en/4-skins';
            if (preg_match('/fr/i', $langs->defaultlang))
                $url = 'https://www.dolistore.com/fr/4-themes';
            //if (preg_match('/es/i',$langs->defaultlang)) $url='http://www.dolistore.com/lang-es/4-themes';
            $html .= '<a href="' . $url . '" target="_blank">';
            $html .= $langs->trans('DownloadMoreSkins');
            $html .= '</a>';
            $html .= '</th></tr>';

            $html .= '<tr>';
            $html .= '<td>' . $langs->trans("ThemeDir") . '</td>';
            $html .= '<td>';
            foreach ($dirthemes as $dirtheme) {
                echo '"' . $dirtheme . '" ';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr><td colspan="' . $colspan . '">';
        $html .= '<table class="nobordernopadding" width="100%"><tr><td><div align="center">';

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

                            $html .= '<a href="' . DOL_URL_ROOT . '/bimpcore/?fc=user&id=' . $this->id . '&theme=' . $subdir . '&navtab-maintabs=params&navtab-params_tabs=interface_tab">';
                            //$html .= '<a href="' .  ["PHP_SELF"] . ($edit ? '?action=edit&theme=' : '?theme=') . $subdir . (GETPOST('optioncss', 'alpha', 1) ? '&optioncss=' . GETPOST('optioncss', 'alpha', 1) : '') . ($object ? '&id=' . $object->id : '') . '" style="font-weight: normal;" alt="' . $langs->trans("Preview") . '">';

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


        $html .= '</div></td></tr></table>';
        $html .= '</td></tr>';

        return $html;
    }

    public function renderInterfaceView()
    {
        global $conf, $langs, $db, $user, $bc;
        BimpTools::loadDolClass("user");

        $hookmanager = new HookManager($db);

        $langs->loadLangs(array('companies', 'products', 'admin', 'users', 'languages', 'projects', 'members'));

        $canreaduser = ($user->admin || $user->rights->user->user->lire);

        $id = $this->id;
        $action = GETPOST('action', 'alpha');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'userihm';

        if ($id) {
            $caneditfield = ((($user->id == $id) && $user->rights->user->self->creer) || (($user->id != $id) && $user->rights->user->user->creer));
        }

        // Security check
        $socid = 0;
        if ($user->societe_id > 0)
            $socid = $user->societe_id;
        $feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');
        if ($user->id == $id) {
            $feature2 = '';
            $canreaduser = 1;
        }
        $result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
        if ($user->id <> $id && !$canreaduser)
            accessforbidden();

        $dirtop = "../core/menus/standard";
        $dirleft = "../core/menus/standard";

        $object = new User($db);
        $object->fetch($this->id, "", "", 1);
        $object->getrights();

        $form = new Form($db);
        $formadmin = new FormAdmin($db);

        $hookmanager->initHooks(array('usercard', 'userihm', 'globalcard'));

        $buttons = array();

        $parameters = array('id' => $socid);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0)
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

        $valLang = $this->db->getValue('user_param', 'value', 'fk_user = ' . (int) $user->id . ' AND param = "MAIN_LANG_DEFAULT"');

        // List of possible landing pages
        $tmparray = array('index.php' => 'Dashboard');
        if (!empty($conf->societe->enabled))
            $tmparray['societe/index.php?mainmenu=companies&leftmenu='] = 'ThirdPartiesArea';
        if (!empty($conf->projet->enabled))
            $tmparray['projet/index.php?mainmenu=project&leftmenu='] = 'ProjectsArea';
        if (!empty($conf->holiday->enabled) || !empty($conf->expensereport->enabled))
            $tmparray['hrm/index.php?mainmenu=hrm&leftmenu='] = 'HRMArea';   // TODO Complete list with first level of menus
        if (!empty($conf->product->enabled) || !empty($conf->service->enabled))
            $tmparray['bimpcore/?fc=products&mainmenu=products'] = 'ProductsAndServicesArea';
        if (!empty($conf->propal->enabled) || !empty($conf->commande->enabled) || !empty($conf->ficheinter->enabled) || !empty($conf->contrat->enabled))
            $tmparray['bimpcommercial/index.php?fc=tabCommercial'] = 'CommercialArea';
        if (!empty($conf->compta->enabled) || !empty($conf->accounting->enabled))
            $tmparray['compta/index.php?mainmenu=compta&leftmenu='] = 'AccountancyTreasuryArea';
        if (!empty($conf->adherent->enabled))
            $tmparray['adherents/index.php?mainmenu=members&leftmenu='] = 'MembersArea';
        if (!empty($conf->agenda->enabled))
            $tmparray['comm/action/index.php?mainmenu=agenda&leftmenu='] = 'Agenda';


        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . $langs->trans("Parameter") . '</th>';
        $html .= '<th>' . $langs->trans("DefaultValue") . '</th>';
        $html .= '<th>' . $langs->trans("PersonalValue") . '</th>';
        $html .= '<th></th>';
        $html .= '<th> Valeur modifiée </th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $htmlP .= '<tr>';
        $htmlP .= '<td>' . $langs->trans("LandingPage") . '</td>';
        $htmlP .= '<td>' . (empty($conf->global->MAIN_LANDING_PAGE) ? '' : $conf->global->MAIN_LANDING_PAGE) . '</td>';
        $htmlP .= '<td><input ' . $bc[$var] . ' name="check_MAIN_LANDING_PAGE" disabled id="check_MAIN_LANDING_PAGE" type="checkbox" ' . (!empty($object->conf->MAIN_LANDING_PAGE) ? " checked" : "");
        $htmlP .= empty($dolibarr_main_demo) ? '' : ' disabled="disabled"';
        $htmlP .= '> ' . $langs->trans("UsePersonalValue") . '</td>';
        $htmlP .= '<td>';
        $htmlP .= '<td>';
        if (empty($tmparray[$object->conf->MAIN_LANDING_PAGE])) {
            $htmlP .= $langs->trans($tmparray[$object->conf->MAIN_LANDING_PAGE] ? 'Pas de modif apportée' : $object->conf->MAIN_LANDING_PAGE);
        } else
            $htmlP .= $tmparray[$object->conf->MAIN_LANDING_PAGE];
        $html .= '</td>';
        $html .= '</td>';
        $htmlP .= '</tr>';

        $htmlP .= '<tr>';
        $htmlP .= '<td>' . $langs->trans("Language") . '</td>';
        $htmlP .= '<td>';
        $s = picto_from_langcode((empty($valLang)) ? $conf->global->MAIN_LANG_DEFAULT : $valLang);
        $htmlP .= ($s ? $s . ' ' : '');
        $htmlP .= ((isset($valLang) && $valLang == 'auto' || isset($conf->global->MAIN_LANG_DEFAULT) && $conf->global->MAIN_LANG_DEFAULT == 'auto' ? $langs->trans("AutoDetectLang") : (empty($valLang) ? $langs->trans("Language_" . $conf->global->MAIN_LANG_DEFAULT) : $langs->trans("Language_" . $valLang))));
        $htmlP .= '</td>';
        $htmlP .= '<td>';
        $htmlP .= '<input ' . $bc[$var] . ' type="checkbox" disabled ' . (!empty($valLang) ? " checked" : "") . '> ' . $langs->trans("UsePersonalValue") . '';
        $htmlP .= '</td>';
        $htmlP .= '<td>';
        $htmlP .= '<td>' . $langs->trans("Language_" . $valLang) . '</td>';
        $htmlP .= '</td>';
        $htmlP .= '</tr>';

        $modThemeVal = $this->db->getValue('user_param', 'value', 'param = "MAIN_THEME" AND fk_user = ' . $this->id);

        $htmlP .= '<tr>';
        $htmlP .= '<td>' . $langs->trans("DefaultSkin") . '</td>';
        $htmlP .= '<td>' . $conf->global->MAIN_THEME . '</td>';
        $htmlP .= '<td>';
        $htmlP .= '<input id="check_MAIN_THEME" name="check_MAIN_THEME"' . ($edit ? '' : ' disabled') . ' type="checkbox" ' . ($selected_theme ? " checked" : "") . '> ' . $langs->trans("UsePersonalValue");
        $htmlP .= '</td>';
        $htmlP .= '<td>';
        $htmlP .= '<td>' . $modThemeVal . '</td>';
        $htmlP .= '</td>';
        $htmlP .= '</tr>';

        if ($htmlP == '') {
            $htmlP .= '<tr>';
            $htmlP .= '<td colspan="5">' . BimpRender::renderAlerts($no_linked, 'info') . '</td>';
            $htmlP .= '</tr>';
        }

        $html .= $htmlP;
        $html .= '</tbody>';
        $html .= '</table>';

        $interface_instance = BimpObject::getInstance('bimpcore', 'Bimp_ParamsUser');

        $params = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ParamsUser');

        //            $saveButton = array(
        //                'label' => 'Enregistrer le Thème',
        //                'onclick' => $this->getJsActionOnclick('test')
        //            );

        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => "Modifier l'interface",
            'icon_before' => 'fas_plus-circle',
            'attr'        => array(
                'onclick' => $params->getJsActionOnclick('editTheme', array(), array(
                    'form_name' => 'theme_edit'
                ))
            //getJsLoadModalForm('theme_edit', "Modification de l interface", array(), '')
            )
        );

        $list = $params->renderList('default', true, 'Liste des paramètres', null, array('fk_user' => $user->id));

        return BimpRender::renderPanel('Paramètres interface', $html, '',
                                       array(
                            'foldable'       => false,
                            'type'           => 'secondary',
                            'icon'           => 'fas_image',
                            'header_buttons' => $buttons
                        )
        );
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

    public function renderLinkedObjectsList($list_type)
    {
        global $db, $conf, $langs;

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
                if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                    return $this->renderUserGroupsTable();
                }

                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_UserGroup'), 'user', 1, null, 'Liste des groupes de "' . $user_label . '"', 'fas_users');
                $list->addFieldFilterValue('ugu.fk_user', $this->id);
                break;

            // Onglet "Liste des configurations de listes": 
            case 'lists_configs':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'ListTableConfig'), 'default', 1, null, 'Liste des configurations de listes de "' . $user_label . '"', 'fas_cog');
                $list->addIdentifierSuffix('user_' . $this->id);
                $list->addFieldFilterValue('owner_type', ListTableConfig::OWNER_TYPE_USER);
                $list->addFieldFilterValue('id_owner', $this->id);
                break;

            // Onglet "'Liste des configuration de filtres":
            case 'filters_configs':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'FiltersConfig'), 'default', 1, null, 'Liste des configuration de filtres de "' . $user_label . '"', 'fas_cog');
                $list->addIdentifierSuffix('user_' . $this->id);
                $list->addFieldFilterValue('owner_type', ListTableConfig::OWNER_TYPE_USER);
                $list->addFieldFilterValue('id_owner', $this->id);
                break;

            case 'lists_filters':
                $list = new BC_ListTable(BimpObject::getInstance('bimpuserconfig', 'ListFilters'), 'default', 1, null, 'Filtres enregistrés de "' . $user_label . '"', 'fas_cog');
                $list->addIdentifierSuffix('user_' . $this->id);
                $list->addFieldFilterValue('owner_type', ListFilters::OWNER_TYPE_USER);
                $list->addFieldFilterValue('id_owner', $this->id);
                break;

            // Onglet "Droits utilisateur": 
            case 'user_rights':
                $right = BimpObject::getInstance('bimpcore', 'Bimp_UserRight');
                $list = new BC_ListTable($right, 'user', 1, null, 'Droits assignés à ' . $this->getName());
                $list->addFieldFilterValue('fk_user', (int) $this->id);
                break;

            case 'usergroups_rights':
                $right = BimpObject::getInstance('bimpcore', 'Bimp_UserGroupRight');
                $list = new BC_ListTable($right, 'group', 1, null, 'Droits assignés aux groupes d\'appartenance de ' . $this->getName());
                $list->addFieldFilterValue('fk_usergroup', array(
                    'in' => BimpCache::getUserUserGroupsList((int) $this->id)
                ));
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

            // Onglet "Materiel": 
            case 'materiel':
                $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'Equipment'), 'default', 1, null, 'Materiel serialisé de "' . $user_label . '"', 'fas_tv');

                $list->addJoin('be_equipment_place', 'a___places.id_equipment = a.id', 'a___places');

                $list->addFieldFilterValue('a___places.position', 1);
                $list->addFieldFilterValue('a___places.id_user', $this->id);
                break;
                $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'BE_PackageProduct'), 'default', 1, null, 'Materiel serialisé de "' . $user_label . '"', 'fas_tv');

                //                $list->addJoin('be_equipment_place', 'a___places.id_equipment = a.id', 'a___places');
                //                
                //                $list->addFieldFilterValue('a___places.position', 1);
                //                $list->addFieldFilterValue('a___places.id_user', $this->id);
                break;
            case 'materielNS':
                $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'BE_PackageProduct'), 'user', 1, null, 'Materiel non serialisé de "' . $user_label . '"', 'fas_tv');

                //                SELECT COUNT(DISTINCT a.id) as nb_rows
                //FROM llx_be_package_product a
                //LEFT JOIN llx_be_package a___parent ON a___parent.id = a.id_package
                //LEFT JOIN llx_be_package_place a___parent___places ON a___parent___places.id_package = a___parent.id
                //WHERE (a___parent___places.position <= '1') AND a.id_package = '4429'


                $list->addJoin('be_package', 'a___parent.id = a.id_package', 'a___parent');
                $list->addJoin('be_package_place', 'a___parent___places.id_package = a___parent.id', 'a___parent___places');
                //                
                $list->addFieldFilterValue('a___parent___places.position', 1);
                $list->addFieldFilterValue('a___parent___places.id_user', $this->id);
                $list->addFieldFilterValue('a___parent___places.type', 3);

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

    public function renderAvailabilities($date_from = null, $date_to = null)
    {
        $html = '';
        $errors = array();

        if (is_null($date_from)) {
            $date_from = date('Y-m-d');
        } else {
            $date_from = date('Y-m-d', strtotime($date_from));
        }

        if (is_null($date_to)) {
            $date_to = date('Y-m-d');
        } else {
            $date_to = date('Y-m-d', strtotime($date_to));
        }

        if ($date_to < $date_from) {
            $errors[] = 'Date de fin antérieure à la date de début';
        } else {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 160px">Date</th>';
            $html .= '<th>Disponibilité matin</th>';
            $html .= '<th>Disponibilité après-midi</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody class="headers_col">';

            $dt = new DateTime($date_from);
            $interval = new DateInterval('P1D');

            $i = 0;
            while ($dt->format('Y-m-d') <= $date_to) {
                $html .= '<tr>';
                $html .= '<td>' . $dt->format('d / m / Y') . '</td>';
                $html .= '<td>';

                $i++;

                $reason = '';
                $day_errors = array();
                $tms = strtotime($dt->format('Y-m-d 00:00:00'));

                if (num_public_holiday($tms, $tms, '', 1) != 0) {
                    $html .= '<span>' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermé</span>';
                    $html .= '</td><td>';
                    $html .= '<span>' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermé</span>';
                } else {
                    if (!$this->isAvailable($dt->format('Y-m-d 10:00:00'), $day_errors, $reason)) {
                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non Disponible' . ($reason ? ' (' . $reason . ')' : '') . '</span>';
                    } elseif (!empty($day_errors)) {
                        $html .= BimpRender::renderAlerts($day_errors);
                    } else {
                        $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Disponible</span>';
                    }

                    $html .= '</td><td>';

                    if (!$this->isAvailable($dt->format('Y-m-d 15:00:00'), $day_errors, $reason)) {
                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non Disponible' . ($reason ? ' (' . $reason . ')' : '') . '</span>';
                    } elseif (!empty($day_errors)) {
                        $html .= BimpRender::renderAlerts($day_errors);
                    } else {
                        $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Disponible</span>';
                    }
                }

                $html .= '</td>';
                $html .= '</tr>';

                if ($i >= 365) {
                    break;
                }

                $dt->add($interval);
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderUserGroupsTable()
    {
        if (!BimpTools::isModuleDoliActif('MULTICOMPANY')) {
            return $this->renderLinkedObjectsList('user_groups');
        }

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        $groups = self::getUserUserGroupsList($this->id, true);

        $html = '';
        $content = '';

        if (empty($groups)) {
            $content = BimpRender::renderAlerts('Aucun groupe');
        } else {
            $headers = array(
                'group'    => 'Groupe',
                'entities' => 'Entités'
            );

            $rows = array();
            $groups_links = BimpCache::getUserGroupsArray(false, true);
            $entities_names = BimpCache::getEntitiesCacheArray(false);

            ksort($groups);
            
            foreach ($groups as $id_group => $ids_entities) {
                $ent = '';

                foreach ($ids_entities as $id_entity) {
                    $ent .= ($ent ? '<br/>' : '') . (isset($entities_names[$id_entity]) ? $entities_names[$id_entity] : 'Entité #' . $id_entity);

                    if ($this->isActionAllowed('removeFromGroup') && $this->canSetAction('removeFromGroup')) {
//                    $onclick = 'BimpUserGroupsTable.unlinkUserGroupEntity(' . $this->id . ', ' . $id_group . ', ' . $id_entity . ')';
                        $onclick = $this->getJsActionOnclick('removeFromGroup', array(
                            'id_group'  => $id_group,
                            'id_entity' => $id_entity
                                ), array());

                        $ent .= '&nbsp;<span class="btn btn-light-danger iconBtn" onclick="' . $onclick . '">';
                        $ent .= BimpRender::renderIcon('fas_unlink');
                        $ent .= '</span>';
                    }
                }

                $rows[] = array(
                    'group'    => (isset($groups_links[$id_group]) ? $groups_links[$id_group] : 'Group #' . $id_group),
                    'entities' => $ent
                );
            }

            $content .= BimpRender::renderBimpListTable($rows, $headers, array());
        }

        $html .= '<div class="col-sm-12 col-md-6">';
        $html .= '<div class="buttonsContainer align-right" style="margin-bottom: 10px">';

        if ($this->isActionAllowed('addToGroup') && $this->canSetAction('addToGroup')) {
            $onclick = $this->getJsActionOnclick('addToGroup', array(), array(
                'form_name' => 'add_to_groupe'
            ));

            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter un groupe / entité(s)';
            $html .= '</span>';
        }

        $onclick = $this->getJsLoadCustomContent('renderUserGroupsTable', '$(this).findParentByClass(\'nav_tab_ajax_result\')', array(), array('button' => '$(this)'));
        $html .= '<span class="btn btn-default reloadUserGroupsTableBtn" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';

        $html .= '</div>';

        $html .= $content;

        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function saveInterfaceParam($param_name, $value)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $where = 'param = \'' . $param_name . '\' AND fk_user = ' . (int) $this->id;
        $cur_value = $this->db->getValue('user_param', 'value', $where);

        if ((string) $cur_value && $cur_value != $value) {
            if ($this->db->update('user_param', array(
                        'value' => $value
                            ), $where) <= 0) {
                $errors[] = 'Echec de la mise à jour du paramètres "' . $param_name . '" - ' . $this->db->err();
            }
        } else {
            if (!$this->db->insert('user_param', array(
                        'fk_user' => $this->id,
                        'param'   => $param_name,
                        'value'   => $value
                    ))) {
                $errors[] = 'Echec de l\'enregistrement du paramètres "' . $param_name . '" - ' . $this->db->err();
            }
        }

        return $errors;
    }

    public function processPhotoUpload()
    {
        if (!isset($_FILES['photo'])) {
            return array();
        }

        $errors = array();

        if ($this->isLoaded($errors)) {
            if ((int) BimpTools::getValue('no_photo', 0)) {
                if ($this->db->update($this->getTable(), array(
                            'photo' => ''
                                ), 'rowid = ' . (int) $this->id) <= 0) {
                    $errors[] = 'Echec de la suppression de la photo - ' . $this->db->err();
                } else {
                    $this->set('photo', '');
                }
            } elseif (is_uploaded_file($_FILES['photo']['tmp_name'])) {
                global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini, $quality;

                require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                if (image_format_supported($_FILES['photo']['name'])) {
                    global $conf;
                    $dir = $conf->user->dir_output;
                    dol_mkdir($dir);

                    if (is_dir($dir)) {
                        if (!preg_match('/^.+\/$/', $dir)) {
                            $dir .= '/';
                        }
                        $file_name = dol_sanitizeFileName($_FILES['photo']['name']);
                        $file_path = $dir . $file_name;
                        if (dol_move_uploaded_file($_FILES['photo']['tmp_name'], $file_path, 1) > 0) {
                            $this->updateField('photo', $file_name);
                            $this->dol_object->photo = $file_name;
//                            $this->dol_object->addThumbs($file_path);

                            $file_osencoded = dol_osencode($file_path);
                            if (file_exists($file_osencoded)) {
                                vignette($file_osencoded, $maxwidthsmall, $maxheightsmall, '_small', $quality);
                                vignette($file_osencoded, $maxwidthmini, $maxheightmini, '_mini', $quality);
                            }
                        } else {
                            $errors[] = 'Echec de l\'enregistrement du fichierr';
                        }
                    } else {
                        $errors[] = 'Echec de la création du dossier de destination de la photo';
                    }
                } else {
                    $errors[] = 'Format non supporté';
                }
            } else {
                switch ($_FILES['photo']['error']) {
                    case 1:
                    case 2:
                        $errors[] = "Fichier trop volumineux";
                        break;
                    case 3:
                        $errors[] = "Echec du téléchargement du fichier";
                        break;

                    default:
                        $errors[] = 'Echec du téléchargement pour une raison inconnue';
                        break;
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionExportConges($data, &$success = '')
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
                    if (!(int) $this->db->getValue('user_rights', 'rowid', 'fk_user = ' . $this->id . ' AND fk_id = ' . $id_right)) {
                        if ($this->db->insert('user_rights', array(
                                    'entity'  => 1,
                                    'fk_user' => $this->id,
                                    'fk_id'   => $id_right
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
                                    if (!(int) $this->db->getValue('user_rights', 'rowid', 'fk_user = ' . $this->id . ' AND fk_id = ' . $id_right_lire)) {
                                        if ($this->db->insert('user_rights', array(
                                                    'entity'  => 1,
                                                    'fk_user' => $this->id,
                                                    'fk_id'   => $id_right_lire
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
                        $warnings[] = 'l\'utilisateur possède déjà le droit "' . $label . '"';
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

            $groupsRights = $this->getGroupsRights();

            foreach ($id_rights as $id_right) {
                $right_def = $this->db->getRow('rights_def', 'id = ' . $id_right, null, 'array');

                if (is_null($right_def)) {
                    $warnings[] = 'Le droit #' . $id_right . ' n\'existe plus';
                } else {
                    if ((int) $this->db->getValue('user_rights', 'rowid', 'fk_user = ' . $this->id . ' AND fk_id = ' . $id_right)) {
                        $module = BimpTools::getArrayValueFromPath($right_def, 'module', '');
                        $subperms = BimpTools::getArrayValueFromPath($right_def, 'subperms', '');
                        $perms = BimpTools::getArrayValueFromPath($right_def, 'perms', '');

                        if ($this->db->delete('user_rights', "entity = 1 AND fk_user = " . $this->id . " AND fk_id = " . $id_right)) {
                            $nOk++;

                            $results[$id_right] = array(
                                'ok'     => 1,
                                'active' => (isset($groupsRights[$id_right]) ? 'inherit' : 'no')
                            );

                            if ($module) {
                                // Si droit lire, suppr des droits du même ensemble: 
                                if (in_array($subperms, array('lire', 'read')) || in_array($perms, array('lire', 'read'))) {
                                    $filters = array(
                                        'a.fk_user' => $this->id,
                                        'r.module'  => $module,
                                    );

                                    if (in_array($subperms, array('lire', 'read'))) {
                                        $filters['r.perms'] = $perms;
                                        $filters['r.subperms'] = 'IS_NOT_NULL';
                                    }

                                    $sql = BimpTools::getSqlFullSelectQuery('user_rights', array('a.rowid, r.id as id_right'), $filters, array(
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
                                                if ($this->db->delete('user_rights', 'rowid = ' . (int) $er['rowid']) <= 0) {
                                                    $sql_err = $this->db->err();
                                                    $extra_right_def = $this->db->getRow('rights_def', 'id = ' . (int) $er['id_right'], null, 'array');
                                                    $label = $extra_right_def['module'] . '->' . $extra_right_def['perms'] . (!empty($extra_right_def['subperms']) ? '->' . $extra_right_def['subperms'] : '');
                                                    $warnings[] = 'Echec de la suppression du droit "' . $label . '"' . ($sql_err ? ' - ' . $sql_err : '');
                                                } else {
                                                    $nOk++;
                                                    $results[$er['id_right']] = array(
                                                        'ok'     => 1,
                                                        'active' => (isset($groupsRights[$id_right]) ? 'inherit' : 'no')
                                                    );
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

                            $results[$id_right] = array(
                                'ok' => 0
                            );
                        }
                    } else {
                        $label = $right_def['module'] . '->' . $right_def['perms'] . (!empty($right_def['subperms']) ? '->' . $right_def['subperms'] : '');
                        $warnings[] = 'L\'utilisteur ne possède déjà pas le droit "' . $label . '"';
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

    public function actionEditInterfaceParams($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $nOk = 0;

        foreach (array(
    'MAIN_THEME' => 'Thème' // En prévision d'éventuels futures paramètres
        ) as $param_name => $param_label) {
            $value = BimpTools::getArrayValueFromPath($data, $param_name);

            if (!is_null($value)) {
                $save_errors = $this->saveInterfaceParam($param_name, $value);

                if (count($save_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($save_errors, $param_label);
                } else {
                    $nOk++;
                }
            }
        }

        if ($nOk) {
            $success = $nOk . ' paramètre(s) enregistré(s) avec succès';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToGroup($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_group = (int) BimpTools::getArrayValueFromPath($data, 'id_usergroup', 0);
        if (!$id_group) {
            $errors[] = 'Veuillez sélectionner un groupe';
        } else {
            $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);

            if (BimpObject::objectLoaded($group)) {
                if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                    $entities = BimpTools::getArrayValueFromPath($data, 'entities', array());

                    if (empty($entities)) {
                        $errors[] = 'Aucune entité sélectionnée';
                    } else {
                        foreach ($entities as $id_entity) {
                            if ($this->dol_object->SetInGroup($id_group, $id_entity) <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout de l\'utilisateur ' . $this->getName() . ' (entité #' . $id_entity . ')');
                            }
                        }
                    }
                } else {
                    if ($this->dol_object->SetInGroup($id_group, 1) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout de l\'utilisateur ' . $this->getName());
                    }
                }

                if (empty($errors)) {
                    $success = 'Ajout de l\'utilisateur ' . $this->getName() . ' au groupe ' . $group->getName() . ' effectué avec succès';
                }
            } else {
                $errors[] = 'Le groupe #' . $id_group . ' n\'existe pas';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => '$(\'.reloadUserGroupsTableBtn\').click();'
        );
    }

    public function actionRemoveFromGroup($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait du groupe effectué avec succès';

        $id_group = (int) BimpTools::getArrayValueFromPath($data, 'id_group', 0, $errors, true, 'Groupe absent');
        if ($this->isLoaded($errors)) {
            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                $entity = BimpTools::getArrayValueFromPath($data, 'id_entity', 0, $errors, true, 'Entité absente');
            } else {
                $entity = 1;
            }

            if (!count($errors)) {
                if ($this->dol_object->RemoveFromGroup($id_group, $entity) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du groupe (Entité #' . $entity . ')');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => '$(\'.reloadUserGroupsTableBtn\').click();'
        );
    }

    public function actionDisplayAvailabilities($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if ($this->isLoaded($errors)) {
            $date_from = BimpTools::getArrayValueFromPath($data, 'date_from', null);
            $date_to = BimpTools::getArrayValueFromPath($data, 'date_to', null);
            $html = $this->renderAvailabilities($date_from, $date_to);

            $success_callback .= 'setTimeout(function() {bimpModal.newContent(\'Disponibilités de ' . $this->getName() . '\', \'' . str_replace("'", "\'", $html) . '\', false, \'\', $());}, 500);';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides

    public function onSave(&$errors = array(), &$warnings = array())
    {
        $logo_errors = $this->processPhotoUpload();

        if (count($logo_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($logo_errors, 'Photo');
        }

        parent::onSave($errors, $warnings);
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->isLoaded()) {
            $this->dol_object->oldcopy = clone $this->dol_object;
        }

        $init_statut = (int) $this->getInitData('statut');
        $statut = (int) $this->getData('statut');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_statut !== $statut) {
                $this->updateField('statut', $statut);
            }
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function getAvailableUsersList($users_in, $date_from = null, &$errors = array(), &$warnings = array())
    {
        if (!is_array($users_in)) {
            $users_in = array($users_in);
        }

        $users_out = array();

        foreach ($users_in as $u) {
            if (is_int($u) || preg_match('/^[0-9]+$/', $u)) {
                // ID utilisateur
                $id_user = (int) $u;
                if (!in_array($id_user, $users_out)) {
                    if (self::isUserAvailable($id_user, $date_from, $errors)) {
                        $users_out[] = $id_user;
                    }
                }
            } elseif (in_array($u, array('superior', 'parent'))) {
                // Supérieur hiérarchique
                $user = BimpCore::getBimpUser();
                if (BimpObject::objectLoaded($user)) {
                    $id_parent = (int) $user->getData('fk_user');

                    if (!$id_parent || $id_parent == 1414 || in_array($id_parent, $users_out)) {
                        continue;
                    }

                    if (self::isUserAvailable($id_parent, $date_from, $errors)) {
                        $users_out[] = $id_parent;
                    }
                }
            } else {
                // Code d'un groupe d'utilisateur
                $ids_users = self::getUsersInGroup($u);

                foreach ($ids_users as $id_user) {
                    if (in_array($id_user, $users_out)) {
                        continue;
                    }

                    if (self::isUserAvailable($id_user, $date_from, $errors)) {
                        $users_out[] = $id_user;
                    }
                }
            }
        }

        return $users_out;
    }

    public static function getUsersInGroup($group_name)
    {
        $cache_key = 'usergroup_users_by_group_name_' . $group_name;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $sql = 'SELECT ugu.fk_user as id_user';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'usergroup_user ugu';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup ug ON ug.rowid = ugu.fk_usergroup';
            $sql .= ' WHERE ug.nom = "' . $group_name . '"';

            $rows = self::getBdb()->executeS($sql, 'object');

            foreach ($rows as $r) {
                self::$cache[$cache_key][$r->id_user] = $r->id_user;
            }
        }

        return self::$cache[$cache_key];
    }

    public static function isUserOff($id_user, &$errors = array(), $date = null)
    {
        if (is_null($id_user) || $id_user < 0) {
            $errors[] = "ID de l'utilisateur absent ou mal renseigné";
            return 0;
        }

        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
        if (!BimpObject::objectLoaded($user)) {
            $errors[] = "Utilisateur d'ID " . $id_user . "absent";
            return 0;
        }

        return $user->isOff($date, $errors);
    }

    public static function isUserAvailable($id_user, $date = null, &$errors = array(), &$unavailable_reason = '')
    {
        if (empty($id_user)) {
            $errors[] = 'ID utilisateur absent';
            return 0;
        }

        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
        if (!BimpObject::objectLoaded($user)) {
            return 0;
        }

        return $user->isAvailable($date, $errors, $unavailable_reason);
    }

    // Boxes: 

    public function boxCreateUser($boxObj, $context)
    {
        global $user;
        $boxObj->boxlabel = 'Création client par commercial';

        if ($context == 'init')
            return 1;

        $boxObj->config['nbJ'] = array('type' => 'int', 'val_default' => 31, 'title' => 'Nb Jours');
        $boxObj->config['my'] = array('type' => 'radio', 'val_default' => 1, 'title' => 'Personne à afficher', 'values' => array(0 => 'Tout le monde', 1 => 'N-1'));
        $nbJ = ((isset($boxObj->confUser['nbJ']) && $boxObj->confUser['nbJ'] > 0) ? $boxObj->confUser['nbJ'] : $boxObj->config['nbJ']['val_default']);
        $my = (isset($boxObj->confUser['my']) ? $boxObj->confUser['my'] : $boxObj->config['my']['val_default']);

        $boxObj->boxlabel .= ' sur ' . $nbJ . ' jours';

        $sql = "SELECT count(*) as nb, sc.fk_user, u.lastname, u.firstname FROM llx_societe s
    LEFT JOIN llx_societe_commerciaux sc ON sc.fk_soc = s.rowid
    LEFT JOIN llx_user u ON u.rowid = sc.fk_user 
    LEFT JOIN llx_user u2 ON u2.rowid = u.fk_user 
    WHERE client > 0 AND  DATEDIFF(now(), s.datec ) <= " . $nbJ . " ";

        $userId = $user->id;
        if ($my)
            $sql .= "AND (u.fk_user = " . $userId . " || u2.fk_user = " . $userId . " || u.rowid = " . $userId . ") ";
        $sql .= "GROUP BY sc.fk_user ORDER BY nb DESC";

        $lns = BimpCache::getBdb()->executeS($sql);

        $data = $data2 = array();
        $i = 0;
        foreach ($lns as $ln) {
            $data[] = array($ln->lastname . ' ' . $ln->firstname, $ln->nb);
            $data2[] = array('user' => $ln->lastname . ' ' . $ln->firstname, 'nb' => $ln->nb);
        }

        if (count($data) > 0)
            $boxObj->addCamenbere('', $data);

        $boxObj->addList(array('user' => 'Utilisateur', 'nb' => 'Nombre de créations'), $data2);
        return 1;
    }

    public function boxServiceUser($boxObj, $context)
    {
        global $user;
        $boxObj->boxlabel = 'Répartition service par commercial';

        if ($context == 'init')
            return 1;

        $boxObj->config['nbJ'] = array('type' => 'int', 'val_default' => 31, 'title' => 'Nb Jours');
        $boxObj->config['my'] = array('type' => 'radio', 'val_default' => 1, 'title' => 'Personne à afficher', 'values' => array(0 => 'Tout le monde', 1 => 'N-1 + N-2'));
        $nbJ = ((isset($boxObj->confUser['nbJ']) && $boxObj->confUser['nbJ'] > 0) ? $boxObj->confUser['nbJ'] : $boxObj->config['nbJ']['val_default']);
        $my = (isset($boxObj->confUser['my']) ? $boxObj->confUser['my'] : $boxObj->config['my']['val_default']);

        $boxObj->boxlabel .= ' sur ' . $nbJ . ' jours';

        $sql = "SELECT u.lastname, u.firstname, SUM(a.total_ht) as total, COUNT(DISTINCT a.rowid) as nbTot, SUM(IF(fk_product_type=1, a.total_ht, 0)) as totalServ, SUM(IF(fk_product_type=1, 1, 0)) as nbServ, SUM(a.qty) as qtyTot, SUM(IF(fk_product_type=1, a.qty, 0)) as qtyServ
    FROM llx_facturedet a
    LEFT JOIN llx_facture f ON f.rowid = a.fk_facture
    LEFT JOIN llx_element_contact elemcont ON elemcont.element_id = a.fk_facture
    LEFT JOIN llx_c_type_contact typecont ON elemcont.fk_c_type_contact = typecont.rowid
    LEFT JOIN llx_user u ON u.rowid = elemcont.fk_socpeople 
    LEFT JOIN llx_user u2 ON u2.rowid = u.fk_user 
    LEFT JOIN llx_product product ON product.rowid = a.fk_product
    WHERE typecont.element = 'facture' AND typecont.source = 'internal' AND typecont.code = 'SALESREPFOLL' AND f.type IN ('0','1','2','4','5') ";
        $sql .= "AND  DATEDIFF(now(), f.datef ) <= " . $nbJ . " ";

        $userId = $user->id;
        if ($my)
            $sql .= "AND (u.fk_user = " . $userId . " || u2.fk_user = " . $userId . " || u.rowid = " . $userId . ") ";
        $sql .= "GROUP BY elemcont.fk_socpeople ORDER BY lastname ASC";

        $lns = BimpCache::getBdb()->executeS($sql);

        $data = array();
        $i = 0;
        foreach ($lns as $ln) {
            if ($ln->total != 0)
                $pourc = price($ln->totalServ / $ln->total * 100);
            else
                $pourc = 'n/c';
            $data[] = array('user' => $ln->lastname . ' ' . $ln->firstname, 'total' => price($ln->total), 'totalServ' => price($ln->totalServ), 'pourc' => $pourc . ' %', 'qty' => round($ln->qtyTot), 'qtyServ' => round($ln->qtyServ));
        }


        $boxObj->addList(array('user' => 'Utilisateur', 'total' => 'CA', 'totalServ' => 'CA Service', 'pourc' => 'Pourcentage service', 'qty' => 'Qty', 'qtyServ' => 'Qty Service'), $data);
        return 1;
    }
}
