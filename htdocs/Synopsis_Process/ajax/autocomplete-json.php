<?php


require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");

$limite=($_REQUEST['limit']>0?$_REQUEST['limit']:10);


//Recherche du type
$arr=array();
$requete = "SELECT s.requete_refid, list_refid
              FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src as s, " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as m
             WHERE m.src_refid = s.id
               AND (s.requete_refid IS NOT NULL OR s.list_refid IS NOT NULL)
               AND m.id = ".$_REQUEST['type'];
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
switch(true)
{
    case ($res->requete_refid > 0):
    {
        $requete = new requete($db);
        $requete->fetch($res->requete_refid);
        $requete->getValues();
        $cnt = 0;
        foreach($requete->valuesArr as $key=>$val){
            if (preg_match('/'.utf8_decode($_REQUEST['q']).'/i',$val))
            {
                array_push($arr,array('id' =>$key , 'label' => utf8_encode($val)));
                $cnt++;
            }
            if ($cnt > $limite) break;
        }
    }
    break;
    case ($res->list_refid > 0):
    {
        //Si liste

        $requete="SELECT *
                    FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members
                   WHERE label LIKE '%".utf8_decode($_REQUEST['q'])."%'
                     AND list_refid = ".$res->list_refid."
                   LIMIT ".$limite;

        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            array_push($arr,array('id' =>$res->valeur , 'label' => utf8_encode($res->label)));
        }

    }
    break;
}


echo json_encode($arr);

//Si requete

?>