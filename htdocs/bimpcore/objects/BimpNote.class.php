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
        self::BN_DEST_NO => 'Aucun',
        self::BN_DEST_USER  => 'Utilisateur',
        self::BN_DEST_GROUP => 'Group'
    );

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
    
    public static function getGroupIds($idUser){
        require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
        $userGroup = new UserGroup(self::getBdb()->db);
        $listIdGr = array();
        foreach($userGroup->listGroupsForUser($idUser,false) as $obj)
                $listIdGr[] = $obj->id;
        return $listIdGr;
    }
    
    public static function getMyConversations($notViewedInFirst = true, $limit=10){
        global $user;
        $listIdGr = self::getGroupIds($user->id);
        $reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create FROM `".MAIN_DB_PREFIX."bimpcore_note` "
                . "WHERE ";
        $where = "(type_dest = 1 AND fk_user_dest = ".$user->id.") "
                . "         OR (type_dest = 2 AND fk_group_dest IN ('".implode("','", $listIdGr)."'))"
                . "         ";
        $reqFin = " GROUP BY `obj_type`,`obj_module`,`obj_name`,`id_obj`";
//        if($notViewedInFirst)
//            $reqFin .= " ORDER by mviewed ASC";
//        else
            $reqFin .= " ORDER by mdate_create DESC";
        $reqFin.= " LIMIT 0,".$limit;
            $tabFils = array();
        $tabReq = array($reqDeb."(".$where.") AND viewed = 0 ".$reqFin, $reqDeb.$where." OR (type_author = 1 AND user_create = ".$user->id.") ".$reqFin);
        foreach($tabReq as $rang => $req){
            $sql = self::getBdb()->db->query($req);
            while($ln = self::getBdb()->db->fetch_object($sql)){
                if($ln->obj_type == "bimp_object"){
                    $tabFils[] = array("lu"=>$rang  , "obj"=>BimpObject::getInstance($ln->obj_module, $ln->obj_name, $ln->id_obj));

                }
            }
        }
        return $tabFils;
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == "viewed"){
            $this->getMyConversations();
            if($this->getData("type_dest") != self::BN_DEST_NO)
                return 0;
        }
        
        return parent::isFieldEditable($field, $force_edit);
 }

    public function isCreatable($force_create = false)
    {        
        return (int) $this->isEditable($force_create);
    }

    public function isEditable($force_edit = false)
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
            return (int) $parent->areNotesEditable($force_edit);
        }

        return 1;
    }
    
    public function isDeletable($force_delete = false)
    {
        return (int) $this->isEditable($force_delete);
    }
    
    public function canEdit() {
        global $user;
        if($this->getData("user_create") == $user->id)
            return 1;
        return 0;
    }

    // Affichage: 

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
    
//    public function displayData($field, $display_name = 'default', $display_input_value = true, $no_html = false) {
//        if($field=="fk_group_dest"){
//            require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
//            $grp = new UserGroup($this->db->db);
//            $grp->fetch($this->getData("fk_group_dest"));
//            if($no_html){
//                global $langs;
//                return $grp->getFullName ($langs);
//            }
//            else
//                return $grp->getNomUrl(1);
//        }
//        
//        return parent::displayData($field, $display_name, $display_input_value, $no_html);
//    }
    
    
    public function displayChatmsg(){
        global $user;
        $html = "<style>
/*.BimpNote_list_table_container {*/
    .msg_cotainer {
        /* margin-top: auto; */
        /* margin-bottom: auto; */
        /* margin-left: 10px; */
        border-radius: 25px;
        background-color: #E6E1E0;
        padding: 10px;
        position: relative;
        margin-bottom: 1.5rem!important;
        min-width: 150px;
        max-width: 65%;
        text-align: center;

        font-size: 1.3em;
        line-height: normal;
    }
    .justify-content-end .msg_cotainer {
        background-color: #78e08f;
    }
    .justify-content-start .msg_cotainer {
        background-color: #82ccdd;
    }

    .justify-content-end {
        -ms-flex-pack: end!important;
        justify-content: flex-end!important;
    }
    .d-flex {
        display: -ms-flexbox!important;
        display: flex!important;
    }
    .msg_time {
        position: absolute;
        left: 25px;
        bottom: -15px;
        color: rgba(0,0,0,0.5);
        font-size: 10px;
    }
    .justify-content-end .msg_time{
        left: auto;
        right: 25px;
    }
    .img-circle{
        margin: 5px;
    }
    .nonLu{
        opacity: 0.4;
    }
    .nonLu.my{
        opacity: 1; 
      animation: colorblink 2s infinite;
    }
/*}*/
@keyframes colorblink { 
    0% { }
    50% { 
        opacity: 0; 
    } 
}   
</style>";
        
        
        $author = $this->displayAuthor(false,true);
        $html .= '<div class="d-flex justify-content-'.($this->i_am_dest()?"start" : ($this->i_am_author() ?"end" : "")).' mb-4">
            <span data-toggle="tooltip" data-placement="top" title="'.$author.'" class="chat-img pull-left">
                <img src="https://placehold.it/55/'.($this->getData('type_author') == self::BN_AUTHOR_USER? '55C1E7' : '5500E7').'/fff&amp;text='.$this->getInitiale($author).'" alt="User Avatar" class="img-circle">
            </span>';
        $html .= '<div class="msg_cotainer">'.$this->getData("content");
        if($this->getData('user_create') != $user->id)
            $html .= '<span class="rowButton bs-popover"><i class="fas fa-share link" onclick="'.$this->getJsActionOnclick('repondre', array("type_dest"=>1, "fk_user_dest"=>$this->getData("user_create"), "content"=>"", "id"=>""), array('form_name' => 'rep')).'"></i></span>';
        
        $html .= '<span class="msg_time">'. dol_print_date($this->getData("date_create"), "%d/%m/%y %H:%M:%S").'</span>
                                                                </div>';
        if($this->getData('type_dest') != self::BN_DEST_NO){
            $dest = $this->displayDestinataire(false,true);
            if($dest != "")
                $html .= '    <span data-toggle="tooltip" data-placement="top" title="'.$dest.'" class="chat-img pull-left '.($this->getData("viewed")? "" : "nonLu").($this->i_am_dest()? " my" : "").'">
                                    <img src="https://placehold.it/45/'.($this->getData('type_dest') == self::BN_DEST_USER? '55C1E7' : '5500E7').'/fff&amp;text='.$this->getInitiale($dest).'" alt="User Avatar" class="img-circle">
                                </span>';
        }
        $html .= "";
        
	$html .= '</div>';
        
        
        if(!$this->getData("viewed") && $this->i_am_dest()){
            $this->updateField('viewed', 1);
        }
        return $html;
    }
    
    public function getInitiale($str){
        $str = str_replace(array("_", "-"), " ", $str);
        $return = $str;
        $tabT = explode(" ", $str);
        if(count($tabT) > 0){
            $return = "";
            foreach($tabT as $part){
                $return .= substr($part, 0,1);
            }
        }
        return strtoupper(substr($return, 0,2));
    }
    
    public function actionRepondre(){
        global $user;
        $data = BimpTools::getValue("extra_data");
        
        $data["type_author"] = self::BN_AUTHOR_USER;
        $data["user_create"] = $user->id;
        $data["viewed"] = 0;
        
        $this->validateArray($data);
        $this->create();
    }

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
    
    public function i_am_dest(){
        global $user;
        if($this->getData("type_dest") == self::BN_DEST_USER && $this->getData("fk_user_dest") == $user->id)
            return 1;
        
        $listIdGr = self::getGroupIds($user->id);
        
        
        if($this->getData("type_dest") == self::BN_DEST_GROUP && in_array($this->getData("fk_group_dest"),$listIdGr))
            return 1;
        
        return 0;
    }
    
    public function i_am_author(){
        global $user;
        if($this->getData("type_author") == self::BN_AUTHOR_USER && $this->getData("user_create") == $user->id)
            return 1;
        
        return 0;
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
}
