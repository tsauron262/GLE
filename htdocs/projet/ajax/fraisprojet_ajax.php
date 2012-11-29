<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fraisprojet_ajax.php
  * GLE-1.1
  */

require_once("../../main.inc.php");
$action = $_REQUEST['oper'];
$socid=$_REQUEST['socid'];
$fraisId = $_REQUEST["id"];
$project_id = $_REQUEST["projId"];


$xml ="";


switch($action)
{
    case "add":
    {
        $montantHT=preg_replace('/,/','.',floatval($_REQUEST['cout']));
        $dateAchat=$_REQUEST['dateAchat'];
        if(preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['dateAchat'],$arr))
        {
            $dateAchat= date('Y-m-d',mktime(0, 0, 0, $arr[2],$arr[1], $arr[3]));
        }
        $user_id=$_REQUEST['userid'];
        $acheteur_id=$_REQUEST['acheteur'];
        $fk_task=($_REQUEST['tache']>0?$_REQUEST['tache']:"null");
        $fk_projet=$_REQUEST['projId'];
        $designation=$_REQUEST['nom'];
        $fk_facture_fourn=($_REQUEST['factureRef']>0?$_REQUEST['factureRef']:"null");
        $fk_commande_fourn=($_REQUEST['commandRef']>0?$_REQUEST['commandRef']:"null");
        $requete = 'INSERT INTO '.MAIN_DB_PREFIX.'Synopsis_projet_frais ( montantHT, dateAchat, user_id, acheteur_id, fk_task, fk_projet, designation, fk_facture_fourn, fk_commande_fourn)
                                            VALUES ("'.$montantHT.'", "'.$dateAchat.'", "'.$user_id.'", "'.$acheteur_id.'", "'.$fk_task.'", "'.$fk_projet.'", "'.$designation.'", '.$fk_facture_fourn.', '.$fk_commande_fourn.')';
        $sql = $db->query($requete);

        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
    }
    break;
    case "edit":
    {
        $id = $_REQUEST['id'];
        $montantHT=preg_replace('/,/','.',floatval($_REQUEST['cout']));
        $dateAchat=$_REQUEST['dateAchat'];
        if(preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['dateAchat'],$arr))
        {
            $dateAchat= date('Y-m-d',mktime(0, 0, 0, $arr[2],$arr[1], $arr[3]));
        }
        $user_id=$_REQUEST['userid'];
        $acheteur_id=$_REQUEST['acheteur'];
        $fk_task=($_REQUEST['tache']>0?$_REQUEST['tache']:"null");

        $fk_projet=$_REQUEST['projId'];
        $designation=$_REQUEST['nom'];
        $fk_facture_fourn=($_REQUEST['factureRef']>0?$_REQUEST['factureRef']:"null");
        $fk_commande_fourn=($_REQUEST['commandRef']>0?$_REQUEST['commandRef']:"null");

        $requete = 'UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_frais
                       SET
                           montantHT="'.$montantHT.'",
                           dateAchat="'.$dateAchat.'",
                           user_id="'.$user_id.'",
                           acheteur_id="'.$acheteur_id.'",
                           fk_task="'.$fk_task.'",
                           fk_projet="'.$fk_projet.'",
                           designation="'.$designation.'",
                           fk_facture_fourn='.$fk_facture_fourn.',
                           fk_commande_fourn='.$fk_commande_fourn.'
                     WHERE id="'.$id.'" ';
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>OK $requete</ok>";
        } else {
            $xml .= "<ko>ko\n$requete</ko>";
        }
    }
    break;
    case 'del':
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_frais WHERE id=".$fraisId;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
    }
    break;
}



    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>
