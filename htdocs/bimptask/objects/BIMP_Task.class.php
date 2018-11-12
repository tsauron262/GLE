<?php

class BIMP_Task extends BimpObject
{
    public static $valSrc = array("task0001@bimp.fr", "validationcommande@bimp.fr", "other");


    public function areNotesEditable()
    {
        return ($this->canEdit() && $this->isEditable());
    }
    
    public function fetch($id, $parent = null){
        $result = parent::fetch($id, $parent);
        $test = $this->getData("test_ferme");
        if($test != ""){
            $tabTest = explode(":", $test);
            if(count($tabTest) == 2){
                $sql = $this->db->db->query("SELECT * FROM ".MAIN_DB_PREFIX.$tabTest[0]." WHERE ".$tabTest[1]);
                if($this->db->db->num_rows($sql) > 0){
                    $inut = "";
                    $this->actionClose(array(), $inut);
                }
            }
        }
        return $result;
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        return $html;
    }
    
    public function renderLight(){
        $html = "";

        $html .= "<a href='" . DOL_URL_ROOT . "/bimptask/index.php?fc=task&id=" . $this->id . "'>";
        $html .= $this->getData("subj") . ' de "' . $this->getData("src") . '" ' . dol_trunc($this->getData("txt"));
        $html .= "</a>";
        foreach ($this->getButtons() as $btn) {
            $html .= '<button class="btn btn-default" type="button" onclick="' . $btn["onclick"] . '"><i class="fa fa-' . $btn['icon'] . ' iconLeft"></i>' . (isset($btn['labelShort']) ? $btn['labelShort'] : $btn['label']) . '</button>';
        }

        return $html;
    }

    public static function getStatus_list_taskArray()
    {
        global $db;
        if (!isset(self::$cache["status_list_task"])) {
            $result = array();
            $sql = $db->query("SELECT * FROM `llx_bimp_task_status`");
            while ($ln = $db->fetch_object($sql)) {
                $result[$ln->status] = array('label' => $ln->ref);
            }
            self::$cache["status_list_task"] = $result;
        }
        //return array(0=>array('label'=>"cool", 'classes'=>array('info')));//sucess info dangerous important
        return self::$cache["status_list_task"];
    }
    
    public function isEditable() {
        return ($this->getData("status") < 4); 
    }
    
    public function isDeletable() {
        return $this->isEditable(); 
    }
    
    public function getRight($right){
        global $user;
        if($this->getData("id_user_owner") == $user->id)
            return 1;
        $classRight = "other";
        if(in_array($this->getData("dst"),  self::$valSrc)){
            $classRight = $this->getData("dst");
        }
        return $user->rights->bimptask->$classRight->$right;
    }
    
    public function getListFiltre($type = "normal"){
        global $user;
        $list = new BC_ListTable($this, 'default', 1, null, ($type == "my" ? 'Mes taches assignées' : 'Toutes les taches'));
        $tabFiltre = self::getFiltreDstRight($user);
        if(count($tabFiltre[1])>0)
            $list->addFieldFilterValue('fgdg_dst', array(
                    ($type == "my" ? 'and_fields' : 'or') => array(
                        'dst'        => array(
                             $tabFiltre[0] => $tabFiltre[1]
                        ),
                        'id_user_owner' => $user->id
                    )
                ));
        elseif($type == "my")
            $list->addFieldFilterValue('id_user_owner', (int) $user->id);
        return $list;
    }
    
    public function canView(){
        global $user;
        if($this->isNotLoaded())
            foreach(self::$valSrc as $src)
                if($user->rights->bimptask->$src->read)
                    return 1;
        
        return $this->getRight("read");
    }
    
    public function canEdit(){
        return $this->getRight("write");
    }
    public function canDelete(){
        return $this->getRight("delete");
    }
    public function canAttribute(){
        return $this->getRight("attribute");
    }
    public function canEditField($field_name) {
        if($field_name == "id_user_owner")
            return $this->canAttribute();
        return parent::canEditField($field_name);
    }
    public static function getFiltreDstRight($user){
        $tabDroit = $tabPasDroit = array();
        foreach(self::$valSrc as $src){
            if($src != "other"){
                if($user->rights->bimptask->$src->read)
                    $tabDroit[] = '"'.$src.'"';
                else
                    $tabPasDroit[] = '"'.$src.'"';
            }
        }
        if($user->rights->bimptask->other->read)
            return array("not_in", $tabPasDroit);
        else
            return array("in", $tabDroit);
    }



//    public function isDeletable() {
//        return 0;
//    }

    public function actionSendMail($data, &$success)
    {
        $success = "impec";
        $errors = $warnings = array();
        $sep = "<br/>---------------------<br/>";
        $idTask = "IDTASK:5467856456" . $this->getData("id");
        $data['email'] = str_replace("<br>", "<br/>", $data['email']);


//        $notes = $this->getChildrenObjects("bimp_note");
        $notes = array("msg1", "msg2", "msg3");
        $notes = $this->getNotes();

        $msg = $data['email'];


        $msg .= "<br/>" . $sep . "Merci d'inclure ces lignes dans les prochaines conversations<br/>" . $idTask . $sep;

        if ($data['include_file']) {
            $msg .= "<br/>Fil de discution :";
            foreach ($notes as $note) {
//                $msg .= get_class($note);
//                if (is_a($note, "BimpNote")) {
//                    $msg .= print_r($note);
                    $msg .= $sep;
                    $msg .= $note->getData("content");
//                }
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
        
        
        $success .= "<br/>dest:" . $to . "<br/>from:" . $from . "<br/>sujet:" . $sujet . "<br/>msg : " . $msg;
        if(!mailSyn2($sujet, $to, $from, $msg))
//        $msg .= "Destinataire tronqué " . $to . " remplcé par tommy et peter<br/>";
//        if(!mailSyn2($sujet, "tommy@bimp.fr, peter@bimp.fr", $from, $msg))
                $errors[] = "Envoie email impossible";
        else{
            $this->addNote($data['email'], 4);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionClose($data, &$success)
    {
        $errors = $warnings = array();
        $success = "Tache fermé";
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

    public function getButtons(){
        global $user;
        $buttons = array();
        if($this->isEditable()){
            if($this->canEdit()){
                $buttons[] = array(
                    'label'   => 'Répondre par mail',
                    'labelShort'   => 'Rep Mail',
                    'icon'    => 'send',
                    'onclick' => $this->getJsActionOnclick('sendMail', array(), array('form_name' => 'newMail'))
                );
                $buttons[] = array(
                    'label'   => 'Classer terminé',
                    'labelShort'   => 'Terminé',
                    'icon'    => 'close',
                    'onclick' => $this->getJsActionOnclick('close', array(), array('confirm_msg' => 'Terminer la tache ?'))
                );
            }
            if($this->canEdit() || $this->canAttribute()){
                if($this->getData("id_user_owner") < 1){
                    $buttons[] = array(
                        'label'   => 'Attribué',
                        'icon'    => 'user',
                        'onclick' => $this->getJsActionOnclick('attribute', array(), array('form_name' => 'attribute'))
                    );
                }
                if($this->getData("id_user_owner") == $user->id){
                    $buttons[] = array(
                        'label'   => 'Refusé l\'attribution',
                        'icon'    => 'window-close',
                        'onclick' => $this->getJsActionOnclick('attribute', array('id_user_owner'=>0))
                    );
                }
            }
        }
        return $buttons;
    }
}
