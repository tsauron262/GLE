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
  * Name : ressource_ajax.php
  * GLE-1.0
  */



//$file = AddSlashes(file_get_contents("/tmp/2009_04_08_rapport.jpg"));
//$requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources ".MAIN_DB_PREFIX."Synopsis_global_ressources SET photo = '".$file."' WHERE  id = ".$_REQUEST['ressource_id'];
//$sql = $db->query($requete);

require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
$zim1 = new Zimbra("gle");
$userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
$passAdminZim = $conf->global->ZIMBRA_ADMINPASS;
$zim1->connectAdmin($userAdminZim,$passAdminZim);
$zim1->debug=false;
$action = $_REQUEST['oper'];
$socid=$_REQUEST['socid'];
$xml ="";
//  var_dump($_REQUEST);

//Ajoute le type de resa

switch($action)
{
    case "add":
    {
        $dataAchatR = $_REQUEST['date_achat'];
        $cout =  floatval(preg_replace("/,/",".",$_REQUEST['cout']));
        $valeur = floatval(preg_replace("/,/",".",$_REQUEST['valeur']));
        $description = preg_replace("/'/","\\\'",$_REQUEST['description']);
        $nom = preg_replace("/'/","\\\'",$_REQUEST['nom']);
        $user_resp = $_REQUEST['fk_user_resp'];
        $categorie = $_REQUEST['categorie'];
        $typeResa = $_REQUEST['typeResa'];
        if ("x".$categorie == "x" || $categorie < 1)
        {
            $categorie = " NULL ";
        }
        if (!($user_resp > 0 ))
        {
            $xml ="<ko>Pas de responsable</ko>";
            break;
        }
        if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$dataAchatR,$arr))
        {
            $hour = $arr[4];
            if ("x".$hour == "x") $hour = "00";
            $min = $arr[5];
            if ("x".$min == "x") $min = "00";
            $date_achat = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$dataAchatR,$arr))
        {
            $hour = "00";
            $min = "00";
            $date_achat = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else {
            $xml="<ko>Mauvais format de date</ko>";
            break;
        }

        $nom = checkNom($nom,$db);
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources
                                (isGroup,nom, fk_user_resp, description, date_achat , valeur, cout, fk_parent_ressource, fk_resa_type)
                         VALUES (0,'".$nom."',".$user_resp.",'".$description."','".$date_achat."',".$valeur.",".$cout.",".$categorie.",$typeResa)";
        $sql = $db->query($requete);
        if ($sql)
        {
            $newid = $db->last_insert_id("".MAIN_DB_PREFIX."Synopsis_global_ressources");
            //CreateZimbra
            $newAccountDet['cn']=$nom;
            $newAccountDet['co']="France";
            $newAccountDet['company']=$conf->MAIN_INFO_SOCIETE_NOM;
            $newAccountDet['displayName']=$nom;
            $newAccountDet['gn']=$nom;
            $newAccountDet['l']=$conf->MAIN_INFO_SOCIETE_VILLE;
            $newAccountDet['ou']=$conf->MAIN_INFO_SOCIETE_NOM;
            $newAccountDet['street']=$conf->MAIN_INFO_SOCIETE_ADRESSE;
            $newAccountDet['postalCode']=$conf->MAIN_INFO_SOCIETE_CP;
            $newAccountDet['sn']=$nom;
            $newAccountDet['st']="";
            $tmpUser = new User($db);
            $tmpUser->fetch($user_resp);

            $newAccountDet['telephoneNumber']= $tmpUser->prefPhone;
            $newAccountDet['zimbraCalResType']="Equipment"; //Equipment ou Location
            $username = rawurlencode(preg_replace('/ /',"",$nom)."-".$newid);
            $password = md5("test".rand(0,10).date('u'));
            //

            $ret = $zim1->BabelCreateRessources($username,$newAccountDet);
            $ressource_zimid = $ret['calresource'][0]['id'];
            $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources SET zimbra_id = '".$ressource_zimid."' WHERE id= ".$newid;
            $db->query($requete);
            //automount
            
            $zimbraId = Zimbra::getZimbraId($db, $tmpUser->id);
            $login = Zimbra::getZimbraCred($db, $tmpUser->id);
            
            $zimbraId2 = ZIMBRA_ADMINID;
            $login2 = ZIMBRA_ADMINUSER;

            $zim = new Zimbra($username);
            $zim->connect();
            $zim->BabelShareCal($zimbraId,10);
            $zim->BabelShareCal($zimbraId2,10); // 10 est l'id du calendrier utilisateur principal


            if($login == '') die("nom=rien".$tmpUser->id);
            $zim2 = new Zimbra($login);
            $zim2->connect();
            $zim2->BabelCreateMountPoint($ressource_zimid,10,$nom);

            if($login2 == '') die("nom=rien");
            $zim2 = new Zimbra($login2);
            $zim2->connect();
//            die($zim2->getIdFolder("Ressource"));
            $zim2->BabelCreateMountPoint($ressource_zimid,10,$nom, $zim2->getIdFolder($db, "Ressources"));//REssource en fixe

        } else {
            $xml = "<ko>".$db->lasterrno." ". $requete. "</ko>";
        }
    }
    break;
    case "edit":
    {
        $dataAchatR = $_REQUEST['date_achat'];
        $id = $_REQUEST['id'];
        $cout = floatval(preg_replace("/,/",".",$_REQUEST['cout']));
        $valeur = floatval(preg_replace("/,/",".",$_REQUEST['valeur']));
        $description = preg_replace("/'/","\\\'",$_REQUEST['description']);
        $nom = preg_replace("/'/","\\\'",$_REQUEST['nom']);
        $user_resp = $_REQUEST['fk_user_resp'];
        $categorie = $_REQUEST['categorie'];
        $typeResa = $_REQUEST['typeResa'];

        if ("x".$categorie == "x" || $categorie < 1)
        {
            $categorie = " NULL ";
        }
        if (!($user_resp > 0 ))
        {
            $xml ="<ko>Pas de responsable</ko>";
            break;
        }

        if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$dataAchatR,$arr))
        {
            $hour = $arr[4];
            if ("x".$hour == "x") $hour = "00";
            $min = $arr[5];
            if ("x".$min == "x") $min = "00";
            $date_achat = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$dataAchatR,$arr))
        {
            $hour = "00";
            $min = "00";
            $date_achat = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else {
            $xml="<ko>Le format de la date est incorrecte</ko>";
            break;
        }
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$id;
        $changeResp=false;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $changeOwner = false;
        if($res->fk_user_resp != $user_resp) $changeOwner = true;
        $changeName = false;
//        if($res->nom != $nom) {
//            $changeName = true;
//            $nom = checkNom($nom,$db);
//        }



        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources
                       SET nom='".$nom."',
                           fk_user_resp=".$user_resp.",
                           description = '" . $description ."',
                           date_achat = '".$date_achat."',
                           valeur = ".$valeur.",
                           cout = ".$cout.",
                           fk_parent_ressource = " .$categorie. ",
                           fk_resa_type =".$typeResa."
                     WHERE id = ".$id;
        $sql = $db->query($requete);
        if ($sql)
        {

            //ModZimbra
//            if ($changeName)
//            {
//                //rename ressource
//                $username = rawurlencode(preg_replace('/ /',"",$nom)."-".$newid);
//
//                $zim = new Zimbra($username);
//                $zim->connect();
//                $zim->BabelShareCal($tmpUser->zimbraId,10); // 10 est l'id du calendrier utilisateur principal
//
//                $zim->BabelRenameRessources($res->zimbra_id,$nom);
//                //apply next feature
//                $newAccountDet['cn']=$nom;
//                $newAccountDet['co']="France";
//                $newAccountDet['company']=$conf->MAIN_INFO_SOCIETE_NOM;
//                $newAccountDet['displayName']=$nom;
//                $newAccountDet['gn']=$nom;
//                $newAccountDet['l']=$conf->MAIN_INFO_SOCIETE_VILLE;
//                $newAccountDet['ou']=$conf->MAIN_INFO_SOCIETE_NOM;
//                $newAccountDet['street']=$conf->MAIN_INFO_SOCIETE_ADRESSE;
//                $newAccountDet['postalCode']=$conf->MAIN_INFO_SOCIETE_CP;
//                $newAccountDet['sn']=$nom;
//                $newAccountDet['st']="";
//                $tmpUser = new User($db);
//                $tmpUser->fetch($user_resp);
//
//                $newAccountDet['telephoneNumber']= $tmpUser->prefPhone;
//                $newAccountDet['zimbraCalResType']="Equipment"; //Equipment ou Location
//                $password = md5("test".rand(0,10).date('u'));
//                //
//
//                $ret = $zim1->BabelModRessources($username,$password,$newAccountDet);
//                $ressource_zimid = $ret['calresource'][0]['id'];
//                $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources SET zimbra_id = '".$ressource_zimid."' WHERE id= ".$newid;
//                $db->query($requete);
//            }
//
//
//            if ($changeOwner)
//            {
//                //delete perms for old user if needed
//                $zim = new Zimbra($username);
//                $zim->connect();
//                $zim->BabelCloseShareCal($tmpUser->zimbraId,10); // 10 est l'id du calendrier utilisateur principal
//
//
//                //automount
//
//
//                $zim = new Zimbra($username);
//                $zim->connect();
//                $zim->BabelShareCal($tmpUser->zimbraId,10); // 10 est l'id du calendrier utilisateur principal
//
//
//                $tmpUser->getZimbraCred($tmpUser->id);
//                $zim2 = new Zimbra($tmpUser->ZimbraLogin);
//                $zim2->connect();
//                $zim2->BabelCreateMountPoint($ressource_zimid,10,$nom);
//            }



            $xml = "<ok>".$id."</ok>";
        } else {
            $xml = "<ko>".$db->error."</ko>";
        }

    }
    break;
    case 'del':
    {
        $id = $_REQUEST['id'];
        //Delete perms ??
        //Delete from zimbra
        //getRessource zimbra Id
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$id;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $zimId = $res->zimbra_id;
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$id;
        $sql = $db->query($requete);
        if ($sql)
        {
            $zim1->BabelDeleteRessources($zimId);
            $xml = "<ok>".$id."</ok>";
        } else {
            $xml = "<ko>".$db->error."</ko>";
        }
    }
}



    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

function checkNom($nom,$db,$suffix=0)
{
    $suffix++;
    $requete = "SELECT count(*) as cnt FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE nom ='$nom'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    if ($res->cnt > 0)
    {
        $nom = preg_replace('/[\W][0-9]*$/',"",$nom);
        $nom = $nom."-".$suffix;
        $nom = checkNom($nom,$db,$suffix);
    }
    return($nom);

}
?>
