<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 16 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : resa_ajax.php
  * GLE-1.0
  */

  require_once("../../main.inc.php");
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Ressources/ressource.class.php");
  $action = $_REQUEST['action'];
  $xml ="";
  $datedeb = $_REQUEST['datedeb'];
  $datefin = $_REQUEST['datefin'];
  $projId = $_REQUEST['projId'];
  if (!preg_match("/^[0-9]*$/",$projId))
  {
    $projId=false;
  }
  $taskId = $_REQUEST['taskId'];
  if (!preg_match("/^[0-9]*$/",$taskId))
  {
    $taskId=false;
  }
  $ressourceId = $_REQUEST['ressourceId'];
  $zimId = $_REQUEST['zimId'];
  $resaId = $_REQUEST['resaId'];
  $userImputation = $_REQUEST['imputation'];
  $datedebR = $_REQUEST['datedeb'];
  $datefinR = $_REQUEST['datefin'];

$datedeb="";
$dateDebDet=array();
if ((($action != 'get' && $action != 'del' && $action != 'listTask') && $action != 'del') && preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$datedebR,$arr))
{
    $hour = $arr[4];
    if ("x".$hour == "x") $hour = "00";
    if (strlen($hour) == 1) $hour = "0".$hour;
    $min = $arr[5];
    if (strlen($min) == 1) $min = "0".$min;
    if ("x".$min == "x") $min = "00";

    $datedeb = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
    $date = strtotime($datedeb);
    $date2 = $date;// + (3600*8);
//    $date = $date + (3600*12);
//    $date -= 3600;
    $dateDebDet['month']= date('m',$date2);
    $dateDebDet['day']= date('d',$date2);
    $dateDebDet['year']= date('Y',$date2);
    $dateDebDet['hour']= date('H',$date2);
    $dateDebDet['min']= date('i',$date2);
    $datedeb = date('Y-m-d H:i',$date);
} else if(($action != 'get' && $action != 'del') ) {
    $xml="<ko>Le format de la date est incorrecte</ko>";
}
$datefin="";
$dateFinDet=array();
if ((($action != 'get' && $action != 'del'&& $action != 'listTask') && $action != 'del') && preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$datefinR,$arr))
{
    $hour = $arr[4];
    if ("x".$hour == "x") $hour = "00";
    if (strlen($hour) == 1) $hour = "0".$hour;
    $min = $arr[5];
    if (strlen($min) == 1) $min = "0".$min;
    if ("x".$min == "x") $min = "00";

    $datefin = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
    $date = strtotime($datefin);
    $date2 = $date;// + (3600*8);
//    $date = $date + (3600*12);
//    $date -= 3600;
    $dateFinDet['month']= date('m',$date2);
    $dateFinDet['day']= date('d',$date2);
    $dateFinDet['year']= date('Y',$date2);
    $dateFinDet['hour']= date('H',$date2);
    $dateFinDet['min']= date('i',$date2);
    $datefin = date('Y-m-d H:i',$date);

} else if (($action != 'get' && $action != 'del'&& $action != 'listTask')){
    $xml="<ko>Le format de la date est incorrecte</ko>";
}

$ressource = $zim = $resZim = $calId = false;
if ($action != 'listTask')
{
    $ressource = new Ressource($db);
    $ressource->fetch($ressourceId);

    $nomRessource = strtolower($ressource->nom."-".$ressource->id);
    
    $zim=new Zimbra($nomRessource);
    //           $zim->debug=true;
    $zim->debug=false;
    $resZim = $zim->connect();

    $calId = $zim->getMainCalId($resZim);

}

switch($action)
{
    case "listTask":
    {
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
        $proj=new Project($db);
        if ($projId)
        {
            $proj->fetch($projId);
            $proj->showTreeTask('setTask');
        }
        $xml="<div>KO</div>";
        print $xml;
        exit;
    }
    case "get":
    {
       //get From db
       $requete = "SELECT unix_timestamp(datedeb) as datedebF,
                          unix_timestamp(datefin) as datefinF,
                          zimbraId,
                          ".MAIN_DB_PREFIX."Synopsis_global_ressources.id,
                          ".MAIN_DB_PREFIX."Synopsis_global_ressources.nom ,
                          fk_user_author,
                          fk_user_imputation
                     FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources,
                          ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                    WHERE ".MAIN_DB_PREFIX."Synopsis_global_ressources.id = ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_ressource
                      AND zimbraId = ".$zimId;
       //get From zimbra
        $sql = $db->query($requete);
        //$xml .= $requete;
        while ($res=$db->fetch_object($sql))
        {
            $tmpUser = new User($db);
            $tmpUser->id = $res->fk_user_author;
            $tmpUser->fetch($tmpUser->id);
            $author = $tmpUser->getNomUrl(1);
            $tmpUser->id = $res->fk_user_imputation;
            $tmpUser->fetch($tmpUser->id);
            $imput = $tmpUser->getNomUrl(1);


            $xml .= "<resa>";
                $xml .= "<datedeb><![CDATA[".date('d/m/Y H:i',$res->datedebF)."]]></datedeb>";
                $xml .= "<datefin><![CDATA[".date('d/m/Y H:i',$res->datefinF)."]]></datefin>";
                $xml .= "<zimbraId><![CDATA[".$res->zimbraId."]]></zimbraId>";
                $xml .= "<rowid><![CDATA[".$res->id."]]></rowid>";
                $xml .= "<nom><![CDATA[".$res->nom."]]></nom>";
                $xml .= "<fk_user_author><![CDATA[".$author."]]></fk_user_author>";
                $xml .= "<fk_user_imputation><![CDATA[".$imput."]]></fk_user_imputation>";
            $xml .= "</resa>";
        }
       //display

    }
    break;
    case "add":
    {
        //Recherche les doublons
        //dateDeb format unix
        $udatefin = strtotime($datefin);
        $udatedeb = strtotime($datedeb);
        $requete = "SELECT id as srowid
                      FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                     WHERE fk_ressource = ".$ressourceId ."
                       AND (".$udatedeb." BETWEEN UNIX_TIMESTAMP(datedeb) AND UNIX_TIMESTAMP(datefin)
                        OR ".$udatefin." BETWEEN UNIX_TIMESTAMP(datedeb) AND UNIX_TIMESTAMP(datefin))";
        $sql = $db->query($requete);
        if ($db->num_rows($sql) == 0)
        {
            $zim1=new Zimbra( Zimbra::getZimbraCred($db,$userImputation));
           $zim1->debug=false;

            $resZim1 = $zim1->connect();
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_li_Ressources WHERE Ressource_refid = ".$ressourceId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);

            $fuser = new User($db);
            $fuser->id = $userImputation;
            $fuser->fetch($fuser->id);
            global $user;
            /*$aptArr1=array("start"    => array( "year"=> $dateDebDet['year'] , "month" => $dateDebDet['month'] , "day" => $dateDebDet['day'], "hour"=>$dateDebDet['hour'] , "min" => $dateDebDet['min'] ),
                          "end"      => array( "year"=> $dateFinDet['year'] , "month" => $dateFinDet['month'] , "day" => $dateFinDet['day'], "hour"=>$dateFinDet['hour'] , "min" => $dateFinDet['min'] ),
                          "fb"       => "B",
                          "transp"   => "O",
                          "status"   => "TENT",
                          "allDay"   => "0",
                          "l"        => "10",
                          "name"     => "Reservation",
                          "isOrg"    => "1",
                          "url"      => $dolibarr_main_url_root."/Synopsis_Ressources/resa.php?ressource_id=".$ressourceId,
                          "desc"     => "Reservation par ".$user->firstname . " ".$user->name. " pour ".$fuser->firstname . " ".$fuser->name,
                          "descHtml" => "R&eacute;servation par " .$user->getNomUrl(0,'iframe',1) . " pour " . $fuser->getNomUrl(0,'iframe',1)
                     );
           $response1 = $zim1->createApptBabel($aptArr1);*/
    //TODO 2 calendriers => le cal de la ressource et le cal global
    //Trouve le calendrier global

            $aptArr=array("start"    => array( "year"=> $dateDebDet['year'] , "month" => $dateDebDet['month'] , "day" => $dateDebDet['day'], "hour"=>$dateDebDet['hour'] , "min" => $dateDebDet['min'] ),
                          "end"      => array( "year"=> $dateFinDet['year'] , "month" => $dateFinDet['month'] , "day" => $dateFinDet['day'], "hour"=>$dateFinDet['hour'] , "min" => $dateFinDet['min'] ),
                          "fb"       => "B",
                          "transp"   => "O",
                          "status"   => "TENT",
                          "allDay"   => "0",
                          "l"        => "10",
                          "url"      => $dolibarr_main_url_root."/Synopsis_Ressources/resa.php?ressource_id=".$ressourceId,
                          "name"     => "Reservation",
                          "isOrg"    => "1",
                          "desc"     => "Reservation par ".$user->prenom . " ".$user->nom. " pour ".$fuser->prenom . " ".$fuser->nom,
                          "descHtml" => "R&eacute;servation par " .$user->getNomUrl(0,'iframe',1) . " pour " . $fuser->getNomUrl(0,'iframe',1)
                     );
           $zim->debug=false;
           $response = $zim->createApptBabel($aptArr);
           if (!$response)
           {
                $xml .= "<KO>4KO</KO>";
           } else {
               $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                                       (fk_ressource, datedeb, datefin, fk_user_author, fk_user_imputation,zimbraId,tms)
                                VALUES ($ressourceId,'$datedeb','$datefin',$user->id,$userImputation, '".$response['CreateAppointmentResponse_attribute_apptId'][0]."',now())";
                if ($projId)
                {
                    if ($taskId)
                    {
                       $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                                               (fk_ressource, fk_projet, fk_projet_task, datedeb, datefin, fk_user_author, fk_user_imputation,zimbraId,tms)
                                        VALUES ($ressourceId,".$projId.",".$taskId.",'$datedeb','$datefin',$user->id,$userImputation, '".$response['CreateAppointmentResponse_attribute_apptId'][0]."',now())";
                    } else {
                       $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                                               (fk_ressource, fk_projet, datedeb, datefin, fk_user_author, fk_user_imputation,zimbraId,tms)
                                        VALUES ($ressourceId,".$projId.",'$datedeb','$datefin',$user->id,$userImputation, '".$response['CreateAppointmentResponse_attribute_apptId'][0]."',now())";

                    }
                }
                $db->query($requete);
                if ($sql)
                {
                    $xml .= "<OK>OK</OK>";
                } else {
                    $xml .= "<KO>5KO</KO>";
                }
           }
        } else {
            $xml .= "<KO>Doublon</KO>";
        }
    }
    break;
    case "edit":
    {
        $fuser = new User($db);
        $fuser->fetch($userImputation);
        $aptArr=array("start"    => array( "year"=> $dateDebDet['year'] , "month" => $dateDebDet['month'] , "day" => $dateDebDet['day'], "hour"=>$dateDebDet['hour'] , "min" => $dateDebDet['min'] ),
                      "end"      => array( "year"=> $dateFinDet['year'] , "month" => $dateFinDet['month'] , "day" => $dateFinDet['day'], "hour"=>$dateFinDet['hour'] , "min" => $dateFinDet['min'] ),
                      "fb"       => "B",
                      "transp"   => "O",
                      "status"   => "TENT",
                      "allDay"   => "0",
                      "name"     => "Resa",
                      "url"      => $dolibarr_main_url_root."/Synopsis_Ressources/resa.php?ressource_id=".$ressourceId,
                      "isOrg"    => "1",
                          "l"    => $calId,
                      "desc"     => utf8_encode("Réservation par ".$user->fullname . " pour ".$fuser->fullname),
                      "descHtml" => "R&eacute;servation par " .$user->getNomUrl(0,'iframe',1) . " pour " . $fuser->getNomUrl(0,'iframe',1)
                 );
       //modify in zimbra
       $response = $zim-modifyApptBabel($aptArr,$zimId);
       //modify in db
       //Si ds la db updt sinon insert
       $requete = "SELECT count(*) as cnt FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa WHERE zimbraId = ".$zimId;
       $sql = $db->query($requete);
       $res = $db->fetch_object($sql);
       if ($res->cnt != 1)
       {
           $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                                   (fk_ressource, datedeb, datefin, fk_user_author, fk_user_imputation,zimbraId)
                            VALUES ($ressourceId,'$datedeb','$datefin',$user->id,$userImputation, ".$zimId.")";
           die($requete);
            $db->query($requete);
            if ($sql)
            {
                $xml .= "<OK>OK</OK>";
            } else {
                $xml .= "<KO>6KO</KO>";
            }

       } else {
           $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa
                          SET fk_ressource = $ressourceId ,
                              datedeb='$datedeb',
                              datefin='$datefin',
                              fk_user_author=$user->id,
                              fk_user_imputation= $userImputation
                        WHERE zimbraId =  " . $zimId;
            $db->query($requete);
            if ($sql)
            {
                $xml .= "<OK>OK</OK>";
            } else {
                $xml .= "<KO>1KO</KO>";
            }
       }
    }
    break;
    case 'del':
    {
       //del in zimbra
       if ($resaId ."x" != "x")
       {
           $res = $zim->Babel_DelZimCal($resaId);
            
           $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa WHERE zimbraId = ".$resaId;
           $sql = $db->query($requete);
           if ($sql)
           {
               $xml .= "<OK>OK</OK>";
           } else {
               $xml .= "<KO>2KO</KO>";
           }
       } else {
           $xml .= "<KO>3KO</KO>";
       }
    }
}


    if (!$zim->debug)
    {
        header("Content-Type: text/xml");
    }
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>
