<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */

//Get Datas
//Insert new act com
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

$cactioncomm = new CActionComm($db);
$actioncomm = new ActionComm($db);
$contact = new Contact($db);


$contactid = $_REQUEST["contactid"];
$datedeb = $_REQUEST['datedeb'];
$datefin = $_REQUEST['datefin'];
$code = $_REQUEST["codeType"];
$label = ($_REQUEST["label"]."x" != "x"?$_REQUEST["label"]:false);
$affectTo = $_REQUEST["affectedto"];
$note = $_REQUEST["note"];
$socid = $_REQUEST["socid"] ;

$contact = new Contact($db);
if ($_REQUEST["contactid"])
{
    $result=$contact->fetch($contactid);
}


$cactioncomm->fetch($code);

    // Initialisation objet actioncomm
    $actioncomm->type_id = $cactioncomm->id;
    $actioncomm->type_code = $cactioncomm->code;
    //TODO ??
    //$actioncomm->priority = isset($_POST["priority"])?$_POST["priority"]:0;
    $actioncomm->label = trim($label);

//Date :
$bool = false;
    if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$datedeb,$arr))
    {
        $datep = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
        $actioncomm->datep = strtotime($datep);
        $bool = true;
    }
    if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$datefin,$arr))
    {
        $datep2 = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
        $actioncomm->datef = strtotime($datep2);
    } else {
        $bool = false ;
    }
    if ($bool)
    {
        $duree = $actioncomm->datef - $actioncomm->datep;
        $actioncomm->duree=$duree;
    }

    if (! $label)
    {
        if ($code == 'AC_RDV' && $contact->getFullName($langs))
        {
            $actioncomm->label = $langs->transnoentities("TaskRDVWith",$contact->getFullName($langs));
        }
        else
        {
            if ($langs->trans("Action".$actioncomm->type_code) != "Action".$actioncomm->type_code)
            {
                $actioncomm->label = $langs->transnoentities("Action".$actioncomm->type_code)."\n";
            }
            else $actioncomm->label = $cactioncomm->libelle;
        }
    }

    if ($actioncomm->type_code == 'AC_RDV')
    {
        // RDV
        if ($actioncomm->datef)
        {
            $actioncomm->percentage = 100;
        } else {
            $actioncomm->percentage = 0;
        }
    }


    $usertodo=new User($db);
    if ($affectTo > 0)
    {
        $usertodo->fetch($affectTo);
    }
    $actioncomm->userownerid = $usertodo->id;

    $actioncomm->note = trim($note);
    if (isset($contactid)) $actioncomm->contact = $contact;
    if (isset($socid) && $socid> 0)
    {
        $societe = new Societe($db);
        $societe->fetch($socid);
        $actioncomm->societe = $societe;
    }


        $db->begin();

        // On cree l'action
        $idaction=$actioncomm->add($user);

        if ($idaction > 0)
        {
            if (! $actioncomm->error)
            {
                $db->commit();
                header("Content-Type: text/xml");
                $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
                $xmlStr .= '<ajax-response><response>'."\n";
                $xmlStr .= "<xml>OK</xml>";
                $xmlStr .= '</response></ajax-response>'."\n";
                print $xmlStr;
            }
            else
            {
                // Si erreur
                $db->rollback();
                $error=$actioncomm->error;
                header("Content-Type: text/xml");
                $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
                $xmlStr .= '<ajax-response><response>'."\n";
                $xmlStr .= "<xml>".$error."</xml>";
                $xmlStr .= '</response></ajax-response>'."\n";
                print $xmlStr;

            }
        }
        else
        {
            $db->rollback();
            $error=$actioncomm->error;
            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            $xmlStr .= '<ajax-response><response>'."\n";
            $xmlStr .= "<xml>".$error."</xml>";
            $xmlStr .= '</response></ajax-response>'."\n";
            print $xmlStr;

        }

?>