<?php

class BimpNote extends BimpObject
{
    # Visibilités:
    # ATTENTION : ne pas utiliser 3 et 4 (anciennes valeurs)

    const BN_AUTHOR = 1;
    const BN_ADMIN = 2;
    const BN_MEMBERS = 10;
    const BN_PARTNERS = 11;
    const BN_ALL = 20;

    public static $visibilities = array(
        self::BN_AUTHOR   => array('label' => 'Auteur/Destinataire seulement', 'classes' => array('danger')),
        self::BN_ADMIN    => array('label' => 'Administrateurs seulement', 'classes' => array('important')),
        self::BN_MEMBERS  => array('label' => 'Membres', 'classes' => array('warning')),
        self::BN_PARTNERS => array('label' => 'Membres et partenaires', 'classes' => array('warning')),
        self::BN_ALL      => array('label' => 'Membres, partenaires et clients', 'classes' => array('success')),
    );
    # Types d'auteur:

    const BN_AUTHOR_USER = 1;
    const BN_AUTHOR_SOC = 2;
    const BN_AUTHOR_FREE = 3;
    const BN_AUTHOR_GROUP = 4;

    public static $types_author = array(
        self::BN_AUTHOR_USER  => 'Utilisateur',
        self::BN_AUTHOR_GROUP => 'Groupe',
        self::BN_AUTHOR_SOC   => 'Tiers',
        self::BN_AUTHOR_FREE  => 'Libre'
    );
    # Types destinataire:

    const BN_DEST_NO = 0;
    const BN_DEST_USER = 1;
    const BN_DEST_SOC = 2;
    const BN_DEST_GROUP = 4;

    public static $types_dest = array(
        self::BN_DEST_NO    => 'Aucun',
        self::BN_DEST_USER  => 'Utilisateur',
        self::BN_DEST_GROUP => 'Groupe',
        self::BN_DEST_SOC   => 'Tiers (par mail)'
    );
    # Pas d'ID en dur dans le code : utiliser des variables de conf. 
    # Les ID sont à mettre dans config module bimpcore onglet "Groupes" => /bimpcore/index.php?fc=dev&tab=modules_conf
//    const BN_GROUPID_LOGISTIQUE = 108; => BimpCore::getUserGroupId('logistique')
//    const BN_GROUPID_FACT = 408; => BimpCore::getUserGroupId('facturation')
//    const BN_GROUPID_ATRADIUS = 680; => BimpCore::getUserGroupId('atradius')
//    const BN_GROUPID_CONTRAT = 686; => BimpCore::getUserGroupId('contrat')
//    const BN_GROUPID_ACHAT = 8 (remplacé par 120); => BimpCore::getUserGroupId('achat')
    
    private static $jsReload = '
    if (typeof notifNote !== "undefined" && notifNote !== null)
        notifNote.reloadNotif();
            ';

    // Droits users: 
    public function canEdit()
    {
        global $user;
        if ($user->admin)
            return 1;
        if ($this->getData("user_create") == $user->id && !$this->getInitData("viewed") && !$this->getData("auto"))
            return 1;
        return 0;
    }

    public function getHisto()
    {
        $parent = $this->getParentInstance();
        if ($parent && is_object($parent)) {
            return $parent->renderNotesList(false, 'chat', '', false, false);
        }
    }

    public function canClientView()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ($this->isLoaded()) {
                if ($this->getData('visibility') < self::BN_ALL) {
                    return 0;
                }
            }

            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'setAsViewed':
                if ($this->isLoaded()) {
                    global $user;
                    if ($this->getData('user_create') == $user->id) {
                        $errors[] = 'L\'utilisateur connecté est l\'auteur';
                        return 0;
                    }

                    if (!$this->isUserDest()) {
                        $errors[] = 'Vous ne faites pas partie des destinataires';
                        return 0;
                    }
                }
                return 1;
        }

        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'viewed':
                if (!$this->isLoaded()) {
                    return 0;
                }
                return $this->canSetAction('setAsViewed');
        }
        return parent::canEditField($field_name);
    }

    // Getters booléens:

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($field == "viewed") {
            if ($this->getData("type_dest") != self::BN_DEST_NO)
                return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        if ($this->modeArchive)
            return 0;
        return (int) $this->isEditable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($this->modeArchive)
            return 0;

        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
            return (int) $parent->areNotesEditable();
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($this->modeArchive)
            return 0;
        return (int) $this->isEditable($force_delete, $errors);
    }

    public function isActionAllowed($action, &$errors = [])
    {
        if ($this->modeArchive) {
            $errors[] = 'Mode archive';
            return 0;
        }

        switch ($action) {
            case 'repondre':
                if ($this->isLoaded()) {
//                    if ((int) $this->getData('type_author') !== self::BN_AUTHOR_USER && (int) $this->getData('type_author') !== self::BN_AUTHOR_GROUP) {
//                        $errors[] = 'L\'auteur n\'est pas un utilisateur'; // Nécessaire dans l'immédiat (pour prolease) mais le système sera revu. 
//                        return 0;
//                    }
                    global $user;
                    if ($this->getData('user_create') == $user->id) {
                        $errors[] = 'L\'utilisateur connecté est l\'auteur';
                        return 0;
                    }
                }

                return 1;

            case 'setAsViewed':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('viewed')) {
                    $errors[] = 'Déjà vue';
                    return 0;
                }

                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isUserDest()
    {
        global $user;
        if ($this->getData("type_dest") == self::BN_DEST_USER && $this->getData("fk_user_dest") == $user->id) {
            return 1;
        }

        $listIdGr = self::getUserUserGroupsList($user->id);

        if ($this->getData("type_dest") == self::BN_DEST_GROUP && in_array($this->getData("fk_group_dest"), $listIdGr)) {
            return 1;
        }

        return 0;
    }

    public function isUserAuthor()
    {
        global $user;
        if ($this->getData("type_author") == self::BN_AUTHOR_USER && $this->getData("user_create") == $user->id)
            return 1;

        return 0;
    }

    // Getters Overrides BimpObject:

    public function getParentInstance()
    {
        if (is_null($this->parent)) {
            $object_type = (string) $this->getData('obj_type');
            $module = (string) $this->getData('obj_module');
            $object_name = (string) $this->getData('obj_name');
            $id_object = (int) $this->getData('id_obj');

            if ($object_type && $module && $object_name && $id_object) {
                if ($object_type === 'bimp_object') {
                    $this->parent = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
                    if (!BimpObject::objectLoaded($this->parent)) {
                        unset($this->parent);
                        $this->parent = null;
                    }
                }
            }
        }

        return $this->parent;
    }

    public function getParentLink()
    {
        $html = '';
        if (is_null($this->parent)) {
            $object_type = (string) $this->getData('obj_type');
            $module = (string) $this->getData('obj_module');
            $object_name = (string) $this->getData('obj_name');
            $id_object = (int) $this->getData('id_obj');

            if ($object_type && $module && $object_name && $id_object) {
                if ($object_type === 'bimp_object') {
//                    $coll = new BimpCollection($module, $object_name);
                    $html = BimpCache::getBimpObjectLink($module, $object_name, $id_object);
                }
            }
        } else {
            return $this->parent->getLink();
        }

        return $html;
    }

    // Getters:

    public static function getFiltersByUser($id_user = null)
    {
        $filters = array();

        if (is_null($id_user) || (int) $id_user < 1) {
            global $user;
        } else {
            global $db;
            $user = new User($db);
            $user->fetch((int) $id_user);
        }

        if (!BimpObject::objectLoaded($user)) {
            $filters['visibility'] = array(
                'operator' => '>=',
                'value'    => self::BN_ALL
            );
        } elseif (/* !$user->admin */1) {
            $filters['or_visibility'] = array(
                'or' => array(
                    'visibility'     => array(
                        'operator' => '>=',
                        'value'    => self::BN_MEMBERS
                    ),
                    'user_create'    => $user->id,
                    'and_user_dest'  => array(
                        'and_fields' => array(
                            'fk_user_dest' => $user->id,
                            'type_dest'    => self::BN_DEST_USER
                        )
                    ),
                    'and_group_dest' => array(
                        'and_fields' => array(
                            'fk_group_dest' => self::getUserUserGroupsList($user->id),
                            'type_dest'     => self::BN_DEST_GROUP
                        )
                    )
                )
            );
        }
        return $filters;
    }

    public static function getMyConversations($notViewedInFirst = true, $limit = 10)
    {
        global $user;
        $listIdGr = self::getUserUserGroupsList($user->id);
        $reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create, MAX(id) as idNoteRef FROM `" . MAIN_DB_PREFIX . "bimpcore_note` "
                . "WHERE ";//auto = 0 AND ";
        $where = "(type_dest = 1 AND fk_user_dest = " . $user->id . ") "
                . "         OR (type_dest = 4 AND fk_group_dest IN ('" . implode("','", $listIdGr) . "'))"
                . "         ";
        $reqFin = " GROUP BY `obj_type`,`obj_module`,`obj_name`,`id_obj`";
//        if($notViewedInFirst)
//            $reqFin .= " ORDER by mviewed ASC";
//        else
        $reqFin .= " ORDER by mdate_create DESC";
        $reqFin .= " LIMIT 0," . $limit;
        $tabFils = array();
        $tabNoDoublons = array();
        $tabReq = array($reqDeb . "(" . $where . ") AND viewed = 0 " . $reqFin, $reqDeb . "(" . $where . " OR (type_author = 1 AND user_create = " . $user->id . "))" . $reqFin);
//        echo '<pre>';
//        print_r($tabReq);
//        die();
        foreach ($tabReq as $rang => $req) {
            $sql = self::getBdb()->db->query($req);
            if ($sql) {
                while ($ln = self::getBdb()->db->fetch_object($sql)) {
                    $hash = $ln->obj_module . $ln->obj_name . $ln->id_obj;
                    if (!isset($tabNoDoublons[$hash])) {
                        $tabNoDoublons[$hash] = true;
                        if ($ln->obj_type == "bimp_object") {
                            $tabFils[] = array("lu" => $rang, "obj" => BimpObject::getInstance($ln->obj_module, $ln->obj_name, $ln->id_obj), "idNoteRef" => $ln->idNoteRef);
                        }
                    }
                }
            }
        }
        return $tabFils;
    }

    public function getLink($params = [], $forced_context = '')
    {
        if (!isset($params['parent_link']) || (int) $params['parent_link']) {
            $parent = $this->getParentInstance();
            if (is_object($parent) && method_exists($parent, 'getLink'))
                return $parent->getLink($params, $forced_context);
        }

        return parent::getLink($params, $forced_context);
    }

    public function getActionsButtons()
    {
        $buttons = array();
        if ($this->isActionAllowed('repondre') && $this->canSetAction('repondre')) {
            $buttons[] = array(
                'label'   => 'Répondre',
                'icon'    => 'fas_share',
                'onclick' => $this->getJsRepondre()
            );
        }

        if ($this->isActionAllowed('setAsViewed') && $this->canSetAction('setAsViewed')) {
            $buttons[] = array(
                'label'   => 'Marquer comme vue',
                'icon'    => 'fas_envelope-open',
                'onclick' => $this->getJsActionOnclick('setAsViewed')
            );
        }

        return $buttons;
    }

    public function getListsExtraBulkActions()
    {
        return array(
            array(
                'label'   => 'Marquer vues',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsBulkActionOnclick('setAsViewed', array(), array(
                    'confirm_msg' => 'Veuillez confirmer (Attention certaines notes sont supprimées après lecture)'
                        )
                )
            )
        );
    }

    public function getListExtraBtn()
    {
        return $this->getActionsButtons();
    }

    public function getInitiale($str)
    {
        $str = str_replace(array("_", "-"), " ", $str);
        $return = "";
        if (strlen($str) > 0) {
            $tabT = explode(" ", $str);
            foreach ($tabT as $part) {
                $return .= substr($part, 0, 1);
            }
            $return = strtoupper(substr($return, 0, 3));
        }
        return $return;
    }

    public function getJsRepondre()
    {
        $filtre = array(
            "content" => "",
            "id"      => "",
            "type_dest"      => "",
            "fk_group_dest"      => "",
            "fk_user_dest"      => "",
            "type_dest"      => "",
            "id"      => ""
        );
        $filtre['fk_user_dest'] = $this->getData("user_create");
        if ($this->getData('type_author') == self::BN_AUTHOR_USER) {
            $filtre['type_dest'] = self::BN_DEST_USER;
        } elseif ($this->getData('type_author') == self::BN_AUTHOR_GROUP) {
            $filtre['type_dest'] = self::BN_DEST_GROUP;
            $filtre['fk_group_dest'] = $this->getData("fk_group_author");
        } elseif ($this->getData('type_author') == self::BN_AUTHOR_SOC) {
            $filtre['type_dest'] = self::BN_DEST_SOC;
            $filtre['mail_dest'] = $this->getData("email");
        }
        return $this->getJsActionOnclick('repondre', $filtre, array(
                    'form_name' => 'rep'
                        )
        );
    }

    public function getNoteForUser($id_user, $id_max, &$errors = array())
    {
        $messages = array();
        $messages['id_current_user'] = (int) $id_user;

        $conversations = $this->getMyNewConversations($id_max, true, 30, $id_user, false, false);

        foreach ($conversations as $c) {
            $note = null;
            $msg = array();
            if (!$c['lu'])
                $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $c['idNoteRef']);
            else {
                if ($c['id_obj']) {
                    $sql = 'SELECT MAX(id) AS id_max';
                    $sql .= ' FROM `' . MAIN_DB_PREFIX . 'bimpcore_note`';
                    $sql .= ' WHERE `obj_type` = "bimp_object" AND `obj_module` = "' . $c['obj_module'] . '"';
                    $sql .= ' AND `obj_name` = "' . $c['obj_name'] . '" AND `id_obj` = ' . $c['id_obj'];
                    $res = $this->db->db->query($sql);
                    if ($res) {
                        $ln = $this->db->db->fetch_object($res);
                        $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $ln->id_max);
                    }
                }
            }

            if ($note) {
                // Note
                //            $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $c['idNoteRef']);
                $msg['content'] = $note->displayData('content', 'default', false, false, true);
                $msg['id'] = (int) $c['idNoteRef'];
                $msg['user_create'] = (int) $note->getData('user_create');
                $msg['date_create'] = $note->getData('date_create');
                //            $msg['viewed'] = (int) $note->getData('viewed');
                $msg['is_user_or_grp'] = (int) $note->getData('type_dest') != self::BN_DEST_NO;
                $msg['is_user'] = (int) $note->getData('type_dest') == self::BN_DEST_USER;
                $msg['is_grp'] = (int) $note->getData('type_dest') == self::BN_DEST_GROUP;
                $msg['type_author'] = $this->getData('type_author') == self::BN_AUTHOR_USER;
                $msg['is_user_dest'] = (int) $note->isUserDest();
                $msg['is_user_author'] = (int) $note->isUserAuthor();

                $msg['obj_type'] = $note->getData('obj_type');
                $msg['obj_module'] = $note->getData('obj_module');
                $msg['obj_name'] = $note->getData('obj_name');
                $msg['id_obj'] = (int) $note->getData('id_obj');
                $msg['is_viewed'] = (int) $c['lu'];

                // Obj
//                $obj = BimpCache::getBimpObjectInstance($c['obj_module'], $c['obj_name'], (int) $c['id_obj']);
                $bc = BimpCollection::getInstance($c['obj_module'], $c['obj_name']);
                $link = $bc->getLink((int) $c['id_obj']);

                if ($link) {
                    $msg['obj']['nom_url'] = $link;
                } else {
                    $msg['introuvable'] = $c['obj_module'] . ' ' . $c['obj_name'] . ' ' . $c['id_obj'];
                }

//                $msg['obj']['nom_url'] = $c['obj']->getLink(array('external_link'=>0, 'modal_view'=>0));
                /*  if (method_exists($c['obj'], "getChildObject")) {//ne fonctionne pas sans objet. Et obet trop lourd.
                  $soc = $c['obj']->getChildObject("societe");
                  if (!$soc or!$soc->isLoaded())
                  $soc = $c['obj']->getChildObject("client");

                  if ($soc && $soc->isLoaded())
                  $msg['obj']['client_nom_url'] = $soc->getLink(array('external_link'=>0, 'modal_view'=>0));
                  } */

                // Author
                $author = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $note->getData('user_create'));
                $msg['author']['id'] = (int) $author->getData('id');
                $msg['author']['nom'] = $author->getData('firstname') . ' ' . $author->getData('lastname');
                //            $msg['author']['firstname'] = $author->getData('firstname');
                //            $msg['author']['lastname'] = $author->getData('lastname');
                //            if($msg['is_user']) {
                //                $msg['dest']['firstname'] = $dest->getData('firstname');
                //                $msg['dest']['lastname'] = $dest->getData('lastname');
                //                $msg['dest']['id'] = (int) $dest->getData('id');
                //            } elseif($msg['is_grp']) {
                //                $msg['dest']['firstname'] = $dest->getData('firstname');
                //                $msg['dest']['lastname'] = $dest->getData('lastname');
                //            }
                // Destinataire
//                if ($msg['is_user']) {
//                    $dest = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $note->getData('fk_user_dest'));
//                    $msg['dest']['nom'] = $dest->getData('firstname') . ' ' . $dest->getData('lastname');
//                } elseif ($msg['is_grp'])
                $msg['dest']['nom'] = $note->displayDestinataire(false, true);
            }
            if (count($msg))
                $messages['content'][] = $msg;
        }

        if (!empty($messages['content']))
            $messages['content'] = array_reverse($messages['content']);
        else
            $messages['content'] = array();

        return $messages;
    }

    // Affichage: 

    public function displayDestinataire($display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type_dest')) {
            case self::BN_DEST_USER:
                return $this->displayData('fk_user_dest', 'nom_url', $display_input_value, $no_html);

            case self::BN_DEST_GROUP:
                return $this->displayData('fk_group_dest', 'nom_url', $display_input_value, $no_html);
                
            case self::BN_DEST_SOC:
                return 'Tier (par mail)';
        }

        return '';
    }

    public function displayAuthor($display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type_author')) {
            case self::BN_AUTHOR_USER:
                $user = $this->getChildObject('user_create');
                if (BimpObject::objectLoaded($user) && strtolower($user->getData('login')) === 'client_user') {
                    return '';
                }
                return $this->displayData('user_create', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_SOC:
                return $this->displayData('id_societe', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_FREE:
                return $this->displayData('email', 'default', $display_input_value, $no_html);
            case self::BN_AUTHOR_GROUP:
                return $this->displayData('fk_group_author', 'nom_url', $display_input_value, $no_html);
        }

        return '';
    }

    public function displayChatmsg($style = '', $checkview = true)
    {
        global $user;
        $html = "";

        $author = $this->displayAuthor(false, true);

        $html .= '<div class="d-flex justify-content-' . ($this->isUserDest() ? "start" : ($this->isUserAuthor() ? "end" : "")) . ($style == "petit" ? ' petit' : '') . ' mb-4">';
        $html .= BimpTools::getBadge($this->getInitiale($author), ($style == "petit" ? '35' : '55'), ($this->getData('type_author') == self::BN_AUTHOR_USER ? '55C1E7' : '5500E7'), $author);
        $html .= '<div class="msg_cotainer">' . $this->displayData("content");
        if ($style != "petit" && $this->getData('user_create') != $user->id)
            $html .= '<span class="rowButton bs-popover"><i class="fas fa-share link" onclick="' . $this->getJsRepondre() . '"></i></span>';

        $html .= '<span class="msg_time">' . dol_print_date($this->db->db->jdate($this->getData("date_create")), "%d/%m/%y %H:%M:%S") . '</span>
                                                                </div>';
        if ($this->getData('type_dest') != self::BN_DEST_NO) {
            $dest = $this->displayDestinataire(false, true);
            if ($dest != "")
                $html .= BimpTools::getBadge($this->getInitiale($dest), ($style == "petit" ? '28' : '45'), ($this->getData('type_dest') == self::BN_DEST_USER ? '55C1E7' : '5500E7'), $dest);
        }
        $html .= "";

        $html .= '</div>';

        if ($checkview && !(int) $this->getData('viewed') && $this->isUserDest()) {
            $this->updateField('viewed', 1);
        }
        return $html;
    }

    // Traitements: 

    public function traiteContent()
    {
        $note = $this->getData('content');
        $note = trim($note);
        $tab = array(CHR(13) . CHR(10) => "[saut]", CHR(13) . CHR(10) . ' ' => "[saut]", CHR(10) => "[saut]");
        $tab2 = array("[saut][saut][saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut]" => CHR(13) . CHR(10));
        $note = strtr($note, $tab);
        $note = strtr($note, $tab2);
        $note = strtr($note, $tab);
        $note = strtr($note, $tab2);
        $note = strtr($note, $tab);
        $note = strtr($note, $tab2);
//        die('<textarea>'.$note.'</textarea>');
        $this->set('content', $note);
    }
    
    public function repMail($dst, $src, $subj, $txt){
        $errors = array();
        $data = array();
        $data['obj_type'] = $this->getData('obj_type');
        $data['obj_module'] = $this->getData('obj_module');
        $data['obj_name'] = $this->getData('obj_name');
        $data['id_obj'] = $this->getData('id_obj');
        $data['id_parent_note'] = $this->id;
        $data['type_author'] = self::BN_AUTHOR_SOC;
        $data['email'] = $src;
        $data['content'] = $txt;
        $data['type_dest'] = $this->getData('type_author');
        $data['fk_group_dest'] = $this->getData('fk_group_author');
        $data['fk_user_dest'] = $this->getData('user_create');
        $parent = $this->getParentInstance();
        if($parent->getData('id_client') > 0)
            $data['id_societe'] = $parent->getData('id_client');
        elseif($parent->getData('id_soc') > 0)
            $data['id_societe'] = $parent->getData('id_soc');
        elseif($parent->getData('fk_soc') > 0)
            $data['id_societe'] = $parent->getData('fk_soc');
                
                
        if(!count($errors)){
            $obj = BimpObject::createBimpObject($this->module, $this->object_name, $data, true, $errors, $warnings);
            if(!count($errors))
                return 1;
            else
                BimpCore::addlog('Création reponse mail impossible', 1, 'bimpcore', $this, $errors);
        }
        return 0;
    }

    // Actions: 

    public function actionRepondre($data, &$success = '')
    {
        $errors = array();
        $warnings = array();

        global $user;

        if ($this->getData('viewed') == 0 && $this->isUserDest()) {
            $this->updateField('viewed', 1);
        }

//        $data["type_author"] = self::BN_AUTHOR_USER;
        $data["user_create"] = $user->id;
        $data["viewed"] = 0;
        $data['id_parent_note'] = $this->id;
        

        if ((int) $this->getData('visibility') === self::BN_PARTNERS) {
            $data['visibility'] = self::BN_PARTNERS;
            $data['type_author'] = self::BN_AUTHOR_USER;
        }
        

        if(!count($errors)){
            BimpObject::createBimpObject($this->module, $this->object_name, $data, true, $errors, $warnings);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => self::$jsReload
        );
    }

    public function actionSetAsViewed($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (isset($data['id_objects'])) {
            $nOk = 0;
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            foreach ($ids as $id_note) {
                $note = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Note', $id_note);
                if (BimpObject::objectLoaded($note)) {
                    if ($note->isActionAllowed('setAsViewed') && $note->canSetAction('setAsViewed')) {
                        $note_errors = array();
                        if ((int) $this->getData('delete_on_view')) {
                            $note_errors = $note->delete($warnings, true);
                        } else {
                            $note_errors = $note->updateField('viewed', 1);
                        }
                        if (!count($note_errors)) {
                            $nOk++;
                        }
                    }
                }
            }

            if ($nOk > 1) {
                $success = $nOk . ' notes marquées vues avec succès';
            } else {
                $success = $nOk . ' note marquée vue avec succès';
            }
        } if ($this->isLoaded($errors)) {
            $success = 'Marquée comme vue';

            if ((int) $this->getData('delete_on_view')) {
                $errors = $this->delete($warnings, true);
            } else {
                $errors = $this->updateField('viewed', 1);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => self::$jsReload
        );
    }

    // Overrrides: 

    public function validate()
    {
        $this->traiteContent();

        if (in_array((int) $this->getData('visibilty'), array(3, 4))) {
            BimpCore::addlog('Visibilité note à modifier', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
                'visibilité' => $this->getData('visibilty'),
                'Info'       => 'Les identifiants ont changé : remplacer dans le code PHP 3 par 10 et 4 par 20.<br/>Toujours utliser les constantes de classes quand elles existent(ex : BimpNote::BN_ALL) et jamais les valeurs numériques directement.'
                    ), true);
            switch ($this->getData('visiblity')) {
                case 3:
                    $this->set('visiblity', self::BN_MEMBERS);
                    break;
                case 4:
                    $this->set('visiblity', self::BN_ALL);
                    break;
            }
        }

        $errors = parent::validate();

        if (!count($errors)) {
            switch ((int) $this->getData('type_author')) {
                case self::BN_AUTHOR_USER:
                    break;

                case self::BN_AUTHOR_SOC:
                    if (!(int) $this->getData('id_societe')) {
                        $errors[] = 'Société à l\'origine de la note absente';
                    }
                    break;

                case self::BN_AUTHOR_FREE:
                    if (!(string) $this->getData('email')) {
                        $errors[] = 'Adresse e-mail absente';
                    }
                    break;
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        if($this->getData('type_dest') == self::BN_DEST_SOC){
            $this->set('visiblity',self::BN_ALL);
            $mail = BimpTools::getValue('mail_dest');
            $content = $this->getData('content');
            if($mail == '')
                $errors[] = 'Email vide : '.$mail;
            $this->set('content','Envoyée a '.$mail.'<br/>'.$this->getData('content'));
        }
        
        if(!count($errors)){
            $errors = parent::create($warnings, $force_create);

            if($this->getData('type_dest') == self::BN_DEST_SOC){
                $sep = "<br/>---------------------<br/>";
                $html = $sep . "Merci d'inclure ces lignes dans les prochaines conversations<br/>" . BimpCore::getConf('marqueur_mail_note') . $this->id . '<br/>'. $sep . '<br/><br/>';

                $html .= 'Réponse à votre message : <br/>';
                $html .= $content;
                if($this->getData('id_parent_note') > 0){
                    $oldNote = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $this->getData('id_parent_note'));
                    $html .= '<br/><br/>Rappel du message initial : <br/>'.$oldNote->getData('content');
                }

                $bimpMail = new BimpMail($this->getParentInstance(), 'Nouveau message', $mail, BimpCore::getConf('mailReponse', null, 'bimptask'), $html);
                $bimpMail->send($errors);
            }

            if (!count($errors)) {
                $obj = $this->getParentInstance();
                if (is_object($obj) && $obj->isLoaded() && method_exists($obj, 'afterCreateNote')) {
                    $obj->afterCreateNote($this);
                }
            }
            return $errors;
        }
        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $return = parent::update($warnings, $force_update);
        return $return;
    }

    public function fetch($id, $parent = null)
    {
        $return = parent::fetch($id, $parent);

        // Par précaution + compat avec les notes archivées: 
        if (in_array($this->getData('visibility'), array(3, 4))) {
            switch ($this->getData('visibility')) {
                case 3:
                    $this->set('visibility', self::BN_MEMBERS);
                    break;

                case 4:
                    $this->set('visibility', self::BN_ALL);
                    break;
            }
        }
        return $return;
    }

    // Méthodes statiques:

    public static function copyObjectNotes($object_src, $object_dest)
    {
        $errors = array();

        if (!is_a($object_src, 'BimpObject') || !BimpObject::objectLoaded($object_src)) {
            $errors[] = 'Objet source invalide';
        }

        if (!is_a($object_dest, 'BimpObject') || !BimpObject::objectLoaded($object_dest)) {
            $errors[] = 'Objet de destination invalide';
        }

        if (!count($errors)) {
            $notes = BimpCache::getBimpObjectObjects('bimpcore', 'BimpNote', array(
                        'obj_type'   => 'bimp_object',
                        'obj_module' => $object_src->module,
                        'obj_name'   => $object_src->object_name,
                        'id_obj'     => (int) $object_src->id
            ));

            foreach ($notes as $note) {
                $newNote = BimpObject::getInstance('bimpcore', 'BimpNote');
                $newNote->validateArray($note->getDataArray());
                $newNote->set('obj_type', 'bimp_object');
                $newNote->set('obj_module', $object_dest->module);
                $newNote->set('obj_name', $object_dest->object_name);
                $newNote->set('id_obj', (int) $object_dest->id);

                $warnings = array();
                $create_errors = $newNote->create($warnings, true);

                if (count($create_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($create_errors, 'Echec de la copie de la note #' . $note->id);
                }
            }
        }

        return $errors;
    }

    public static function getMyNewConversations($id_max = 0, $notViewedInFirst = true, $limit = 10, $idUser = null, $onlyNotViewed = false, $withObject = true)
    {
        if (is_null($idUser)) {
            global $user;
            $idUser = $user->id;
        }
        $listIdGr = self::getUserUserGroupsList($idUser);
        $reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create, MAX(id) as idNoteRef"
                . " FROM `" . MAIN_DB_PREFIX . "bimpcore_note` "
                . "WHERE id>" . $id_max . ' AND ';
        $where = "(type_dest = 1 AND fk_user_dest = " . $idUser . ") ";
        if (count($listIdGr) > 0)
            $where .= "         OR (type_dest = 4 AND fk_group_dest IN ('" . implode("','", $listIdGr) . "'))";
        $where .= "         ";

        $reqFin = " GROUP BY `obj_type`,`obj_module`,`obj_name`,`id_obj`";
//        if($notViewedInFirst)
//            $reqFin .= " ORDER by mviewed ASC";
//        else
        $reqFin .= " ORDER by mdate_create DESC";
        $reqFin .= " LIMIT 0," . $limit;
        $tabFils = array();
        $tabNoDoublons = array();
        $tabReq = array();
        $tabReq[0] = $reqDeb . "(" . $where . ") AND viewed = 0 " . $reqFin;
        if (!$onlyNotViewed)
            $tabReq[1] = $reqDeb . "(" . $where . " OR (type_author = 1 AND user_create = " . $idUser . ")) " . $reqFin;

//        echo '<pre>';
//        print_r($tabReq);
//        die();
        foreach ($tabReq as $rang => $req) {
            $sql = self::getBdb()->db->query($req);
            if ($sql) {
                while ($ln = self::getBdb()->db->fetch_object($sql)) {
                    $hash = $ln->obj_module . $ln->obj_name . $ln->id_obj;
                    if (!isset($tabNoDoublons[$hash])) {
                        $tabNoDoublons[$hash] = true;
                        $data = array("lu" => $rang, "idNoteRef" => $ln->idNoteRef);
                        if ($withObject && $ln->obj_type == "bimp_object") {
                            $data['obj'] = BimpCache::getBimpObjectInstance($ln->obj_module, $ln->obj_name, $ln->id_obj);
                            $tabFils[] = $data;
                        } elseif (!$withObject) {
                            $data['obj_module'] = $ln->obj_module;
                            $data['obj_name'] = $ln->obj_name;
                            $data['id_obj'] = $ln->id_obj;
                            $tabFils[] = $data;
                        }
                    }
                }
            }
        }
        return $tabFils;
    }

    public static function cronNonLu()
    {
        $listUser = BimpObject::getBimpObjectList('bimpcore', 'Bimp_User', array('statut' => 1));

        global $db;
        $userT = new User($db);
//        $listUser = array(242);
        foreach ($listUser as $idUser) {
            $html = '';
            $notes = BimpNote::getMyNewConversations(0, true, 500, $idUser, true, false);
            $maxForMail = 20;
            $data = array();
            foreach ($notes as $note)
                if ($note['lu'] == 0 && count($data) < $maxForMail) {
                    $noteObj = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $note['idNoteRef']);
                    $data[] = 'Message de ' . $noteObj->displayData('user_create', 'nom') . ' concernant ' . $noteObj->getParentLink() . ': <br/><i>' . $noteObj->displayData('content') . '</i>';
                }
            if (count($data) > 0) {
                $userT->fetch($idUser);
                $html = '';
//                $htmlTitre = '<h2>User : ' . $userT->getFullName($langs) . '</h3><br/>';
                $html .= 'Bonjour vous avez ' . count($notes) . ' message(s) non lu : <br/>';
                if (count($data) >= $maxForMail)
                    $html .= 'Voici les ' . count($data) . ' derniers<br/>';
                $html .= '<br/>Pour désactiver cette relance, vous pouvez : <br/>- soit répondre au message de la pièce émettrice (dans les notes de pied de page) <br/>- soit cliquer sur la petite enveloppe "Message" en haut à droite de la page ERP.<br/><br/>';

                $html .= implode('<br/><br/>', $data);
                mailSyn2('Message dans l\'erp', $userT->email, null, $html);

//                echo $htmlTitre . $html;
            }
        }

        return '';
    }
}
