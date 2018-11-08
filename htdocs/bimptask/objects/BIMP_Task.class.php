<?php

class BIMP_Task extends BimpObject
{

    const BF_DEMANDE_BROUILLON = 0;
    const BF_DEMANDE_ATT_RETOUR = 1;
    const BF_DEMANDE_SIGNE = 2;
    const BF_DEMANDE_SIGNE_ATT_CESSION = 3;
    const BF_DEMANDE_CEDE = 4;
    const BF_DEMANDE_SANS_SUITE = 5;
    const BF_DEMANDE_RECONDUIT = 6;
    const BF_DEMANDE_REMPLACE = 7;
    const BF_DEMANDE_TERMINE = 999;

   

    // Getters: 

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
    
    public function actionSendMail($data, &$success){
        $success = "impec";
        $errors = $warnings = array();
        $sep = "<br/>---------------------<br/>";
        $idTask = "IDTASK:5467856456".$this->getData("id");
        
        
//        $notes = $this->getChildrenObjects("bimp_note");
        $notes = array("msg1", "msg2", "msg3");
        
        
        $msg = $data['txt2'];
        
        
        $msg .= "<br/>".$sep."Merci d'inclure ces lignes dans les prochaines conversations<br/>".$idTask.$sep;
        
        $msg .= "<br/>Fil de discution :";
        foreach($notes as $note){
            $msg .= $sep;
            $msg .= $note;
        }
        
        $sujet = "Re:".$this->getData("subj");
        $to = $this->getData("src");
        $from = $this->getData("dst");
        
        $success .= "<br/>to:".$to."<br/>from:".$from."<br/>sujet:".$sujet."<br/>msg : ".$msg;
//        if(!mailSyn2($sujet, $to, $from, $msg))
        $msg .= "Destinataire tronqué ".$to."<br/>";
        if(!mailSyn2($sujet, "tommy@bimp.fr", $from, $msg))
                $errors[] = "Envoie email impossible";
        else{
            $this->addNote($data['txt2'], 4);
        }
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    
    public function getInfosExtraBtn()
    {
        global $langs;
        $buttons = array();
        
                $buttons[] = array(
                    'label'   => 'Répondre par mail',
                    'icon'    => 'send',
                    'onclick' => $this->getJsActionOnclick('sendMail', array(), array('form_name' => 'newMail'))
                );
       
        return $buttons;
    }
}
