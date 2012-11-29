<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-25-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : updateCamp-xmlresponse.php
  * GLE-1.1
  */

require_once('../../main.inc.php');

$action = $_REQUEST['action'];
$socid = $_REQUEST['socid'];
$campId = $_REQUEST['campId'];

switch ($action)
{
    case 'update':
    {
        $nom = $_REQUEST['nom'];
        $dateDeb = $_REQUEST['dateDeb'];
        $dateFin = $_REQUEST['dateFin'];
        $note = $_REQUEST['note'];
        $camm=array();
        $debdateUS= "";
        $findateUS="";
        $debts=0;
        $fints=0;

//reformat date
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$dateDeb,$arr))
        {
            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
            $debts = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            $debts += $arr[5]*60 + $arr[4] * 3600;
        }else if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/",$dateDeb,$arr))
        {
            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." 00:00";
            $debts = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
        }else {
            print "wrong format for start date \n";
        }
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$dateFin,$arr))
        {
            $findateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
            $fints = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            $fints += $arr[5]*60 + $arr[4] * 3600;
        }else if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/",$dateFin,$arr))
        {
            $findateUS = $arr[3]."-".$arr[2]."-".$arr[1]." 00:00";
            $fints = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
        }else {
            print "wrong format for end date \n";
        }


        $resp = $_REQUEST['resp'];
        foreach ($_REQUEST as $key=>$val)
        {
            if (preg_match('/^comm[0-9]*/',$key))
            {
                $camm[]=$val;
            }
        }
        $db->begin();
        $requete = "UPDATE Babel_campagne
                       SET nom =  '$nom',
                           dateDebut = '$debdateUS',
                           dateFin = '$findateUS',
                           note_public = '$note'
                     WHERE id = ".$campId;
        $sql = $db->query($requete);
        if (!$sql)
        {
            print $requete."\n";
        }

        $requete = "DELETE FROM Babel_campagne_people WHERE campagne_refid = ".$campId;
        $sql1 = $db->query($requete);
        if (!$sql1)
        {
            print $requete."\n";
        }
        $errstatut = true;
        foreach ($camm as $key=>$val)
        {
            if ($errstatut)
                $requete = "INSERT INTO Babel_campagne_people
                                        (user_refid , campagne_refid, isResponsable)
                                 VALUES ($val,$campId,0)";
                $errstatut = $db->query($requete);
        if (!$errstatut)
        {
            print $requete."\n";
        }

        }
        $requete = "INSERT INTO Babel_campagne_people
                                (user_refid , campagne_refid, isResponsable)
                         VALUES ($resp,$campId,1)";
        $sql2 =$db->query($requete);
        if (!$sql2)
        {
            print $requete."\n";
        }

        if ($sql && $sql1 && $sql2 &&$errstatut)
        {
            $db->commit();
            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            print $xmlStr ;
            print "<OK>OK</OK>";
        } else {
            $db->rollback();
        }

    }
    break;
}





?>
