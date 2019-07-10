<?php

/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : index.php
 * BIMP-ERP-1.2
 */
require_once('../main.inc.php');







llxHeader();

//if (isset($_REQUEST['connect']))
//    echo "<script>$(window).on('load', function() {initSynchServ(idActionMax);});</script>";

$text=$langs->trans("Tools")." Synopsis";

print load_fiche_titre($text);
if (isset($user->rights->SynopsisTools->Global->phpMyAdmin)) {
    print" <br/><br/><a href='myAdmin.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin</span></a>";
    print" <br/><br/><a href='./Synopsis_MyAdmin/index.php'  target='_blank'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin (Nouvelle onglet)</span></a>";
    print" <br/><br/><a href='./Synopsis_MyAdmin3/index.php'  target='_blank'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin (Nouvelle onglet) old version</span></a>";
}
if (isset($user->rights->SynopsisTools->Global->fileInfo)) {
    print" <br/><br/><a href='./fichierLog.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichier de log</span></a>";
    print" <br/><br/><a href='./listFileInfo.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichiers de maj</span></a>";
}

if (isset($user->rights->SynopsisPrepaCom->import->Admin))
    print" <br/><br/><a href='../Synopsis_PrepaCommande/import/testImport.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Import 8sens -> BIMP-ERP</span></a>";


print" <br/><br/><a href='../synopsistools/agenda/vue.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Agenda</span></a>";

print" <br/><br/><a href='../synopsistools/connect.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Connect</span></a>";

if (isset($conf->global->GOOGLE_ENABLE_GMAPS))
    print" <br/><br/><a href='../google/gmaps_all.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Carte des tiers</span></a>";


print" <br/><br/><a href='../synopsisapple/test.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Apple</span></a>";

if (isset($user->rights->SynopsisPrepaCom->import->Admin)){
    print" <br/><br/><a href='../synopsistools/public/extractFact.php?sortie=file'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Extraction facture</span></a>";
    
    
    print" <br/><br/><a href='../synopsistools/class/testSav.class.php?actionTest=mailNonFerme&nbJ=15'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Mail SAV non Fermé</span></a>";
    print" <br/><br/><a href='../synopsistools/class/testSav.class.php?actionTest=fermetureAuto'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fermeture SAV Auto</span></a>";
    print" <br/><br/><a href='../synopsistools/class/testSav.class.php?actionTest=rfpuAuto'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Mail RFPU SAV Auto</span></a>";
    print" <br/><br/><a href='../bimpcore/scripts/edit_signature.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>MAJ signatures utilisateurs</span></a>";
}






//if (isset($_REQUEST['propalMail'])) {
//    $sql = $db->query("SELECT DISTINCT(`fk_propal`) FROM `" . MAIN_DB_PREFIX . "propaldet` WHERE `description` LIKE '661-8153%' AND rowid > 42918 AND total_ht != 382.78");
//    $text = array();
//    while ($ligne = $db->fetch_object($sql)) {
//        $user = 0;
//        $sql2 = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_view_105` WHERE propalid = " . $ligne->fk_propal);
//        if ($db->num_rows($sql2) > 0) {
//            $ligne2 = $db->fetch_object($sql2);
//            $user = $ligne2->Technicien;
////            echo "<br/>oooooooooo";
//        }
//        $prop = new Propal($db);
//        $prop->fetch($ligne->fk_propal);
//        if($prop->statut != 3){
//        if (!$user > 0) {
////            echo $ligne->fk_propal.$prop->getNomUrl(1);
//            $user = $prop->user_author_id;
//        }
//
////        echo "<br/>" . $user . "|";
//        -
//        $userObj = new User($db);
//        $userObj->fetch($user);
//        $str = "Suite à un problème technique certaines lignes de cette propal ne sont plus juste.<br/>Merci de vérifier l'objet en question.<br/>" . $prop->getNomUrl(1) . "<br/><br/>";
//        if (isset($text[$user]))
//            $text[$user][1] = $text[$user][1].$str;
//        else
//            $text[$user] = array($userObj, $str);
//        }
//    }
//    foreach ($text as $id => $tab) {
//        echo $tab[0]->email . "Problème technique BIMP-ERP" . $tab[1];
//        if($_REQUEST['propalMail'] == "mail")
//        mailSyn2("Problème technique BIMP-ERP", $tab[0]->email.",jc.cannet@bimp.fr", "", $tab[1]);
//    }
//}

if(isset($_REQUEST['lienFactProp'])){
    $sql = $db->query("SELECT f.* FROM " . MAIN_DB_PREFIX . "facture f LEFT JOIN " . MAIN_DB_PREFIX . "element_element e ON `sourcetype` LIKE  'propal'
AND  `targettype` LIKE  'facture' AND fk_target = f.rowid WHERE fk_source is null");
    while($ligne = $db->fetch_object($sql)){
        $sql2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "propal WHERE fk_statut != 3 AND fk_soc = ".$ligne->fk_soc);
        $nb = $db->num_rows($sql2);
        echo $nb."|".$ligne->fk_soc. "<br/>";
        if($nb == 1){
            $ligne2 = $db->fetch_object($sql2);echo "jjjjjjjjjjjjjjjjjjjjjjj".$ligne2->rowid."'";
            addElementElement("propal", "facture", $ligne2->rowid, $ligne->rowid);
        }
       
    }
}


if(isset($_REQUEST['fkSocProductCli'])){
    $req = "SELECT c.id as cid, cc5.id, c.fk_soc, cc5.fk_soc as fk_soc5 FROM `llx_synopsischrono` c, `llx_synopsischrono` cc5, llx_synopsischrono_chrono_101 c1, llx_synopsischrono_chrono_105 c5, llx_element_element el WHERE c1.id = c.id AND c.fk_soc is null AND el.sourcetype = 'sav' AND el.targettype = 'productCli' AND el.fk_target = c1.id AND el.fk_source = c5.id AND cc5.fk_soc is not null AND cc5.fk_soc > 0 AND c5.id = cc5.id ORDER BY `c`.`id` ASC";
    
    $sql = $db->query($req);
    while($ligne = $db->fetch_object($sql)){
        $req2 = "UPDATE llx_synopsischrono cU SET cU.fk_soc = ".$ligne->fk_soc5. " WHERE cU.id =".$ligne->cid;
   echo $req2;     
    $sql2 = $db->query($req2);
    }
}

if(isset($_REQUEST['lienProdSav2'])){
    $NoMachine = "ZZ501AAAOWP";
    $req = "SELECT c.id, c.fk_soc FROM `llx_synopsischrono`c , llx_propaldet p, llx_synopsischrono_chrono_105 c5 WHERE `propalid` = p.fk_propal AND p.description LIKE '%".$NoMachine."%' AND c.id not in (SELECT fk_source  FROM `llx_element_element` WHERE `sourcetype` LIKE 'sav') AND c5.id = c.id";

    $sql = $db->query($req);
    
        $chronoProd = new Chrono($db);
        echo "<br/><br/>debut attribution ZZ au sav perdue<br/><br/>";
    while($ligne = $db->fetch_object($sql)){
        $res = existProd($NoMachine, $ligne->fk_soc);
        if($res > 0)
                $idP = $res;
        else{
            
            $chronoProd->model_refid = 101;
            $chronoProd->socid = $socid;
            $chronoProd->description = "Non-Serialized Products";
            $dataArrProd = array(1011 => $NoMachine);
            $idP = $chronoProd->create();
            $testProd = $chronoProd->setDatas($idP, $dataArrProd);
        }
        addElementElement("sav", "productCli", $ligne->id, $idP);
        echo "<br/><br/>Insertion de :"."sav"." | "."productCli"." | ". $ligne->id." | ".$idP;
    }
    
    
    
}

function existProd($nomachine, $socid) {
    global $db;    
    
    $requete = "SELECT c.id FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101 c1, " . MAIN_DB_PREFIX . "synopsischrono c WHERE c1.id = c.id AND N__Serie = '" . addslashes($nomachine) . "' AND fk_soc = ".$socid;

    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0) {
        $obj = $db->fetch_object($sql);
        $return = $obj->id;
        return $return;
    } else {
        return -2;
    }
}


if(isset($_REQUEST['lienProdSav'])){
    $sql1 = $db->query("SELECT count(cc1.id) as nbProd, count(cc5.id) as nbSav, c1.id as id1, c5.id as id5 FROM `llx_synopsischrono_chrono_105` c5, llx_synopsischrono cc5 LEFT JOIN  llx_synopsischrono cc1 ON cc1.fk_soc=cc5.fk_soc , llx_synopsischrono_chrono_101 c1  



WHERE cc1.id = c1.id AND cc5.id = c5.id

AND c5.id NOT IN (SELECT fk_source  FROM `llx_element_element` WHERE `sourcetype` LIKE 'sav') GROUP BY cc5.id 
ORDER BY `nbSav`  DESC");
    
    while($ligne = $db->fetch_object($sql1)){
        if($ligne->nbProd == 1 && $ligne->nbSav == 1){
            addElementElement ("sav", "productCli", $ligne->id5, $ligne->id1);
            echo "lien entre  ".$ligne->id5." et ".$ligne->id1;
//            die;
        }
    }
}

//$my_key = "TriDESSuperEncryptKeyBIMP-ERP";
//$data = "P@sŝw0rd";
//$data = pkcs5_pad($data, mcrypt_get_block_size("tripledes", "cbc"));
//$td = mcrypt_encrypt(MCRYPT_3DES, $my_key, $data, MCRYPT_MODE_ECB);
////$td = mcrypt_encrypt(MCRYPT_3DES, $my_key, $td, MCRYPT_MODE_ECB);
////$td = mcrypt_encrypt(MCRYPT_3DES, $my_key, $td, MCRYPT_MODE_ECB);
//    if(!$td)
//    die("<br/><br/>false");
//echo("<br/><br/>ici|{3DES}". base64_encode($td));
//
//
//$td2 = mcrypt_decrypt(MCRYPT_3DES, $my_key, $td, MCRYPT_MODE_ECB);
////$td2 = mcrypt_decrypt(MCRYPT_3DES, $my_key, $td2, MCRYPT_MODE_ECB);
////$td2 = mcrypt_decrypt(MCRYPT_3DES, $my_key, $td2, MCRYPT_MODE_ECB);
//
//
//echo("<br/><br/>icidecodé|".$td2."|");
//
//if($td2 === base64_decode("CYQmiCYaTIGPFJmHpXn7Wg=="))
//    die("identique");
//
//$td3 = mcrypt_decrypt(MCRYPT_3DES, $my_key, base64_decode("CYQmiCYaTIGPFJmHpXn7Wg=="), MCRYPT_MODE_ECB);
//
//
//echo("<br/><br/>icidecodé|".$td3."|");
//
//function pkcs5_pad ($text, $blocksize)
//{
//    $pad = $blocksize - (strlen($text) % $blocksize);
//    return $text . str_repeat(chr($pad), $pad);
//}

//function pkcs5_unpad($text)
//{
//    $pad = ord($text{strlen($text)-1});
//    if ($pad > strlen($text)) return false;
//    if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
//    return substr($text, 0, -1 * $pad);
//}

llxFooter();
?>
