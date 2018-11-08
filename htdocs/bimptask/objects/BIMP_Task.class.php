<?php

class BIMP_Task extends BimpObject
{

  
    public function renderHeaderExtraLeft()
    {   
        $html = '';
      
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
        return ($this->getData("status") < 4); 
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
                $msg .= $note;
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

    
    public function getInfosExtraBtn()
    {
        return $this->getButtons();
    }
    
    public function getButtons(){
        $buttons = array();
        if($this->getData("status") < 4){
                $buttons[] = array(
                    'label'   => 'Répondre par mail',
                    'icon'    => 'send',
                    'onclick' => $this->getJsActionOnclick('sendMail', array(), array('form_name' => 'newMail'))
                );
                $buttons[] = array(
                    'label'   => 'Classer terminé',
                    'icon'    => 'send',
                    'onclick' => $this->getJsActionOnclick('close')
                );
        }
        return $buttons;
    }
}
