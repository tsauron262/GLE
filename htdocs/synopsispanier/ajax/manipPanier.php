<?php

/* 
 * To change this license header, choose Licese Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/synopsispanier/class/synopsispanier.class.php');
$panier= new Synopsispanier($db);
$panier->fetch($_REQUEST["idReferent"], $_REQUEST["type"]);

if ($_REQUEST["idObject"] > 0)
    if (isset ($_REQUEST["action"]) && $_REQUEST["action"]== 'sup')
        $res = $panier->deleteElement ($_REQUEST["idObject"]);
//        $res = $db->query("DELETE FROM ".MAIN_DB_PREFIX."Synopsys_Panier WHERE referent = ".$_REQUEST["idReferent"]." and type = '".$_REQUEST["type"]."' and valeur = ".$_REQUEST["idObject"].";"); 
    else
        $res = $panier->addElement ($_REQUEST["idObject"]);
//        $res = $db->query("INSERT INTO ".MAIN_DB_PREFIX."Synopsys_Panier (referent, type, valeur) VALUES(".$_REQUEST["idReferent"].",'".$_REQUEST["type"]."',".$_REQUEST["idObject"].");");

    
$panier->getTabID();
//$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsys_Panier WHERE referent='".$_REQUEST["idReferent"]."' AND type ='".$_REQUEST["type"]."';";
//$res2 = $db->query($requete);
//echo "tabID = new Array();";
//while ($ligne = $db->fetch_object($result))
//{
//    echo "tabID.push(".$ligne->valeur.");";
//}
//echo "Enregistrement de l'ID ".$_REQUEST["idObject"]." de type ".$_REQUEST["type"];
//echo "erreur";


