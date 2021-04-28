<?php

class BimpNote extends BimpObject
{

    // Visibilités:
    const BIMP_NOTE_AUTHOR = 1;
    const BIMP_NOTE_ADMIN = 2;
    const BIMP_NOTE_MEMBERS = 3;
    const BIMP_NOTE_ALL = 4;
    // Types d'auteur:
    const BN_AUTHOR_USER = 1;
    const BN_AUTHOR_SOC = 2;
    const BN_AUTHOR_FREE = 3;
    // Types dest:
    const BN_DEST_NO = 0;
    const BN_DEST_USER = 1;
    const BN_DEST_GROUP = 2;
    // Id GR:
    const BN_GROUPID_LOGISTIQUE = 108;
    const BN_GROUPID_FACT = 408;

    public static $visibilities = array(
        self::BIMP_NOTE_AUTHOR  => array('label' => 'Auteur seulement', 'classes' => array('danger')),
        self::BIMP_NOTE_ADMIN   => array('label' => 'Administrateurs seulement', 'classes' => array('important')),
        self::BIMP_NOTE_MEMBERS => array('label' => 'Membres', 'classes' => array('warning')),
        self::BIMP_NOTE_ALL     => array('label' => 'Membres et client', 'classes' => array('success')),
    );
    public static $types_author = array(
        self::BN_AUTHOR_USER => 'Utilisateur',
        self::BN_AUTHOR_SOC  => 'Tiers',
        self::BN_AUTHOR_FREE => 'Libre'
    );
    public static $types_dest = array(
        self::BN_DEST_NO    => 'Aucun',
        self::BN_DEST_USER  => 'Utilisateur',
        self::BN_DEST_GROUP => 'Group'
    );

    public function create(&$warnings = array(), $force_create = false)
    {
        $return = parent::create($warnings, $force_create);

        if (!count($return)) {
            $obj = $this->getParentInstance();
            if (is_object($obj) && $obj->isLoaded() && method_exists($obj, 'afterCreateNote'))
                $obj->afterCreateNote($this);
        }
        return $return;
    }

    public function canEdit()
    {
        global $user;
        if ($user->admin)
            return 1;
        if ($this->getData("user_create") == $user->id && !$this->getInitData("viewed") && !$this->getData("auto"))
            return 1;
        return 0;
    }

    // Getters booléens:

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($field == "viewed") {
//            $this->getMyConversations();
            if ($this->getData("type_dest") != self::BN_DEST_NO)
                return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        return (int) $this->isEditable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
            return (int) $parent->areNotesEditable($force_edit);
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return (int) $this->isEditable($force_delete, $errors);
    }

    public function i_am_dest()
    {
        global $user;
        if ($this->getData("type_dest") == self::BN_DEST_USER && $this->getData("fk_user_dest") == $user->id)
            return 1;

        $listIdGr = self::getGroupIds($user->id);


        if ($this->getData("type_dest") == self::BN_DEST_GROUP && in_array($this->getData("fk_group_dest"), $listIdGr))
            return 1;

        return 0;
    }

    public function i_am_author()
    {
        global $user;
        if ($this->getData("type_author") == self::BN_AUTHOR_USER && $this->getData("user_create") == $user->id)
            return 1;

        return 0;
    }

    public function i_view()
    {
        if (!$this->getData("viewed") && $this->i_am_dest()) {
            if (empty($this->updateField('viewed', 1)))
                return 1;
        }

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

    // Getters: 

    public static function getFiltersByUser($id_user = null)
    {
        $filters = array();

        if (is_null($id_user)) {
            global $user;
        } elseif ((int) $id_user) {
            $user = new User($this->db->db);
            $user->fetch((int) $id_user);
        }

        if (!BimpObject::objectLoaded($user)) {
            $filters['visibility'] = array(
                'operator' => '>',
                'value'    => 3
            );
        } elseif (!$user->admin) {
            $filters['or_visibility'] = array(
                'or' => array(
                    'visibility'  => array(
                        'operator' => '>',
                        'value'    => 2
                    ),
                    'user_create' => $user->id
                )
            );
        }

        return $filters;
    }

    public static function getMyConversations($notViewedInFirst = true, $limit = 10)
    {
        global $user;
        $listIdGr = self::getGroupIds($user->id);
        $reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create, MAX(id) as idNoteRef FROM `" . MAIN_DB_PREFIX . "bimpcore_note` "
                . "WHERE auto = 0 AND ";
        $where = "(type_dest = 1 AND fk_user_dest = " . $user->id . ") "
                . "         OR (type_dest = 2 AND fk_group_dest IN ('" . implode("','", $listIdGr) . "'))"
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

    public function getListExtraBtn()
    {
        global $user;
        $buttons = array();
        if ($this->isLoaded()) {
            if ($this->getData('user_create') != $user->id)
                $buttons[] = array(
                    'label'   => 'Répondre par mail',
                    'icon'    => 'far fa-paper-plane',
                    'onclick' => $this->getJsRepondre());
        }
        return $buttons;
    }

    public function getInitiale($str)
    {
        $str = str_replace(array("_", "-"), " ", $str);
        $return = $str;
        $tabT = explode(" ", $str);
        if (count($tabT) > 0) {
            $return = "";
            foreach ($tabT as $part) {
                $return .= substr($part, 0, 1);
            }
        }
        return strtoupper(substr($return, 0, 2));
    }

    public function getJsRepondre()
    {
        return $this->getJsActionOnclick('repondre', array("type_dest" => 1, "fk_user_dest" => $this->getData("user_create"), "content" => "", "id" => ""), array('form_name' => 'rep'));
    }

    public function getNoteForUser($id_user, $id_max, &$errors = array())
    {
        $messages = array();
        $messages['id_current_user'] = (int) $id_user;

        $conversations = $this->getMyNewConversations($id_max, true, 30);

        foreach ($conversations as $c) {

            if (!$c['lu'])
                $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $c['idNoteRef']);
            else {
                $sql = 'SELECT MAX(id) AS id_max';
                $sql .= ' FROM `' . MAIN_DB_PREFIX . 'bimpcore_note`';
                $sql .= ' WHERE `obj_type` = "bimp_object" AND `obj_module` = "' . $c['obj']->module . '"';
                $sql .= ' AND `obj_name` = "' . $c['obj']->object_name . '" AND `id_obj` = ' . $c['obj']->id;
                $res = $this->db->db->query($sql);
                if ($res) {
                    $ln = $this->db->db->fetch_object($res);
                    $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $ln->id_max);
                }
            }

            if ($note) {
                $msg = array();

                // Note
                //            $note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', (int) $c['idNoteRef']);
                $msg['content'] = $note->getData('content');
                $msg['id'] = (int) $c['idNoteRef'];
                $msg['user_create'] = (int) $note->getData('user_create');
                $msg['date_create'] = $note->getData('date_create');
                //            $msg['viewed'] = (int) $note->getData('viewed');
                $msg['is_user_or_grp'] = (int) $note->getData('type_dest') != self::BN_DEST_NO;
                $msg['is_user'] = (int) $note->getData('type_dest') == self::BN_DEST_USER;
                $msg['is_grp'] = (int) $note->getData('type_dest') == self::BN_DEST_GROUP;
                $msg['type_author'] = $this->getData('type_author') == self::BN_AUTHOR_USER;
                $msg['i_am_dest'] = (int) $note->i_am_dest();

                $msg['obj_type'] = $note->getData('obj_type');
                $msg['obj_module'] = $note->getData('obj_module');
                $msg['obj_name'] = $note->getData('obj_name');
                $msg['id_obj'] = (int) $note->getData('id_obj');
                $msg['is_viewed'] = (int) $c['lu'];


                // Obj
                $msg['obj']['nom_url'] = $c['obj']->getNomUrl();
                if (method_exists($c['obj'], "getChildObject")) {
                    $soc = $c['obj']->getChildObject("societe");
                    if (!$soc or ! $soc->isLoaded())
                        $soc = $c['obj']->getChildObject("client");

                    if ($soc && $soc->isLoaded())
                        $msg['obj']['client_nom_url'] = $soc->getNomUrl(1);
                }

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
                if ($msg['is_user']) {
                    $dest = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $note->getData('fk_user_dest'));
                    $msg['dest']['nom'] = $dest->getData('firstname') . ' ' . $dest->getData('lastname');
                } elseif ($msg['is_grp'])
                    $msg['dest']['nom'] = $note->displayDestinataire(false, true);
            }
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
        }

        return '';
    }

    public function displayAuthor($display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type_author')) {
            case self::BN_AUTHOR_USER:
                return $this->displayData('user_create', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_SOC:
                return $this->displayData('id_societe', 'nom_url', $display_input_value, $no_html);

            case self::BN_AUTHOR_FREE:
                return $this->displayData('email', 'default', $display_input_value, $no_html);
        }

        return '';
    }

    public function displayChatmsg($style = '', $checkview = true)
    {
        global $user;
        $html = "";


        $author = $this->displayAuthor(false, true);
        $html .= '<div class="d-flex justify-content-' . ($this->i_am_dest() ? "start" : ($this->i_am_author() ? "end" : "")) . ($style == "petit" ? ' petit' : '') . ' mb-4">
            <span data-toggle="tooltip" data-placement="top" title="' . $author . '" class="chat-img pull-left">
                <img src="https://placehold.it/' . ($style == "petit" ? '35' : '55') . '/' . ($this->getData('type_author') == self::BN_AUTHOR_USER ? '55C1E7' : '5500E7') . '/fff&amp;text=' . $this->getInitiale($author) . '" alt="User Avatar" class="img-circle">
            </span>';
        $html .= '<div class="msg_cotainer">' . $this->getData("content");
        if ($style != "petit" && $this->getData('user_create') != $user->id)
            $html .= '<span class="rowButton bs-popover"><i class="fas fa-share link" onclick="' . $this->getJsRepondre() . '"></i></span>';

        $html .= '<span class="msg_time">' . dol_print_date($this->db->db->jdate($this->getData("date_create")), "%d/%m/%y %H:%M:%S") . '</span>
                                                                </div>';
        if ($this->getData('type_dest') != self::BN_DEST_NO) {
            $dest = $this->displayDestinataire(false, true);
            if ($dest != "")
                $html .= '    <span data-toggle="tooltip" data-placement="top" title="' . $dest . '" class="chat-img pull-left ' . ($this->getData("viewed") ? "" : "nonLu") . ($this->i_am_dest() ? " my" : "") . '">
                                    <img src="https://placehold.it/' . ($style == "petit" ? '28' : '45') . '/' . ($this->getData('type_dest') == self::BN_DEST_USER ? '55C1E7' : '5500E7') . '/fff&amp;text=' . $this->getInitiale($dest) . '" alt="User Avatar" class="img-circle">
                                </span>';
        }
        $html .= "";

        $html .= '</div>';


        if ($checkview) {
            $this->i_view();
        }
        return $html;
    }

    // Actions: 

    public function actionRepondre($data, &$success = '')
    {
        $errors = array();
        $warnings = array();

        global $user;

        if ($this->getData('viewed') == 0) {
            $this->updateField('viewed', 1);
        }

        $data["type_author"] = self::BN_AUTHOR_USER;
        $data["user_create"] = $user->id;
        $data["viewed"] = 0;

        BimpObject::createBimpObject($this->module, $this->object_name, $data, true, $errors, $warnings);
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            switch ((int) $this->getData('type_author')) {
                case self::BN_AUTHOR_USER:
                    if ($this->isLoaded()) {
                        if (!(int) $this->getData('user_create')) {
                            $errors[] = 'ID de l\'utilisateur absent';
                        }
                    }
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

    public static function getMyNewConversations($id_max, $notViewedInFirst = true, $limit = 10)
    {
        global $user;
        $listIdGr = self::getGroupIds($user->id);
        $reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create, MAX(id) as idNoteRef"
                . " FROM `" . MAIN_DB_PREFIX . "bimpcore_note` "
                . "WHERE auto = 0 AND id>" . $id_max . ' AND ';
        $where = "(type_dest = 1 AND fk_user_dest = " . $user->id . ") "
                . "         OR (type_dest = 2 AND fk_group_dest IN ('" . implode("','", $listIdGr) . "'))"
                . "         ";

        $reqFin = " GROUP BY `obj_type`,`obj_module`,`obj_name`,`id_obj`";
//        if($notViewedInFirst)
//            $reqFin .= " ORDER by mviewed ASC";
//        else
        $reqFin .= " ORDER by mdate_create DESC";
        $reqFin .= " LIMIT 0," . $limit;
        $tabFils = array();
        $tabNoDoublons = array();
        $tabReq = array(
            $reqDeb . "(" . $where . ") AND viewed = 0 " . $reqFin,
            $reqDeb . "(" . $where . " OR (type_author = 1 AND user_create = " . $user->id . ")) " . $reqFin);

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
}
