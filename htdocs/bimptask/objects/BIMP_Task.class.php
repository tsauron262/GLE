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

    
    public function getInfosExtraBtn()
    {
        global $langs;
        $buttons = array();
       
        return $buttons;
    }
}
