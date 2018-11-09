<?php

class BIMP_Task extends BimpObject
{

  
    public function renderHeaderExtraLeft()
    {   
        $html = '';
      
        return $html;
    }
    
//    public function canEdit(){
//        return 0;
//    }
    
    public function renderLight(){
        $html = "";
        
        $html .= "<a href='".DOL_URL_ROOT."/bimptask/index.php?fc=task&id=".$this->id."'>";
        $html .= $this->getData("subj"). ' de "'.$this->getData("src").'" '.dol_trunc($this->getData("txt"));
        $html .= "</a>";
        foreach($this->getButtons() as $btn){
            $html .= '<button class="btn btn-default" type="button" onclick="'.$btn["onclick"].'"><i class="fa fa-'.$btn['icon'].' iconLeft"></i>'.(isset($btn['labelShort'])? $btn['labelShort'] : $btn['label']).'</button>';
        }
        
        return $html;
    }
    
    
    public static function getStatus_list_taskArray(){
        global $db;
        if(!isset(self::$cache["status_list_task"])){
            $result = array();
            $sql = $db->query("SELECT * FROM `llx_bimp_task_status`");
            while($ln = $db->fetch_object($sql)){
                $result[$ln->status] = array('label'=>$ln->ref);
            }
            self::$cache["status_list_task"] = $result;
        }
        //return array(0=>array('label'=>"cool", 'classes'=>array('info')));//sucess info dangerous important
        return self::$cache["status_list_task"];
    }
    
    public function isEditable() {
        return ($this->getData("status") < 4 && $this->canEdit()); 
    }
    
//    public function isDeletable() {
//        return 0;
//    }
    
    public function actionSendMail($data, &$success){
        $success = "impec";
        $errors = $warnings = array();
        $sep = "<br/>---------------------<br/>";
        $idTask = "IDTASK:5467856456".$this->getData("id");
        $data['email'] = str_replace("<br>", "<br/>", $data['email']);
        
        
//        $notes = $this->getChildrenObjects("bimp_note");
        $notes = array("msg1", "msg2", "msg3");
        
        
        $msg = $data['email'];
        
        
        $msg .= "<br/>".$sep."Merci d'inclure ces lignes dans les prochaines conversations<br/>".$idTask.$sep;
        
        if($data['include_file']){
            $msg .= "<br/>Fil de discution :";
            foreach($notes as $note){
                $msg .= $sep;
                $msg .= $note;
            }
            
            $msg .= $sep."Message original :";
            $msg .= $sep;
            $msg .= $this->getData("txt");
        }
        
        $sujet = "Re:".$this->getData("subj");
        $to = $this->getData("src");
        $from = $this->getData("dst");
        
        $success .= "<br/>to:".$to."<br/>from:".$from."<br/>sujet:".$sujet."<br/>msg : ".$msg;
//        if(!mailSyn2($sujet, $to, $from, $msg))
        $msg .= "Destinataire tronqué ".$to." remplcé par tommy et peter<br/>";
        if(!mailSyn2($sujet, "tommy@bimp.fr, peter@bimp.fr", $from, $msg))
                $errors[] = "Envoie email impossible";
        else{
            $this->addNote($data['email'], 4);
        }
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function actionClose($data, &$success){
        $errors = $warnings = array();
        $success = "Tache fermé";
        $errors = $this->updateField("status", 4);
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function actionAttribute($data, &$success){
        $errors = $warnings = array();
        if($data['id_user_owner'] > 0)
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
                    'onclick' => $this->getJsActionOnclick('close')
                );
                if($this->getData("id_user_owner") < 1){
                    $buttons[] = array(
                        'label'   => 'Attribué',
                        'icon'    => 'walk',
                        'onclick' => $this->getJsActionOnclick('attribute', array(), array('form_name' => 'attribute'))
                    );
                }
                if($this->getData("id_user_owner") == $user->id){
                    $buttons[] = array(
                        'label'   => 'refusé l\'attribution',
                        'icon'    => 'cancel',
                        'onclick' => $this->getJsActionOnclick('attribute', array('id_user_owner'=>0))
                    );
                }
        }
        return $buttons;
    }
}
