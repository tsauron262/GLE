<?php

/*
 * * GLE by Synopsis et DRSI
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
 * GLE-1.2
 */
require_once('../main.inc.php');
llxHeader();

//if (isset($_REQUEST['connect']))
//    echo "<script>$(window).load(function() {initSynchServ(idActionMax);});</script>";

print "<div class='titre'>Outil GLE</div>";
print "<br/>";
if (isset($user->rights->SynopsisTools->Global->phpMyAdmin)) {
    print" <br/><br/><a href='myAdmin.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin</span></a>";
    print" <br/><br/><a href='./Synopsis_MyAdmin/index.php'  target='_blank'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>PhpMyAdmin (Nouvelle onglet)</span></a>";
}
if (isset($user->rights->SynopsisTools->Global->fileInfo)) {
    print" <br/><br/><a href='./fichierLog.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichier de log</span></a>";
    print" <br/><br/><a href='./listFileInfo.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Fichiers de maj</span></a>";
}

if (isset($user->rights->SynopsisPrepaCom->import->Admin))
    print" <br/><br/><a href='../Synopsis_PrepaCommande/import/testImport.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Import 8sens -> GLE</span></a>";


print" <br/><br/><a href='../synopsistools/agenda/vue.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Agenda</span></a>";

print" <br/><br/><a href='../synopsistools/connect.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Connect</span></a>";

if (isset($conf->global->GOOGLE_ENABLE_GMAPS))
    print" <br/><br/><a href='../google/gmaps_all.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Carte des tiers</span></a>";


print" <br/><br/><a href='../synopsisapple/test.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Test Apple</span></a>";

if (isset($user->rights->SynopsisPrepaCom->import->Admin))
    print" <br/><br/><a href='../synopsistools/public/extractFact.php?sortie=file'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Extraction facture</span></a>";










//if (isset($_REQUEST['propalMail'])) {
//    $sql = $db->query("SELECT DISTINCT(`fk_propal`) FROM `llx_propaldet` WHERE `description` LIKE '661-8153%' AND rowid > 42918 AND total_ht != 382.78");
//    $text = array();
//    while ($ligne = $db->fetch_object($sql)) {
//        $user = 0;
//        $sql2 = $db->query("SELECT * FROM `llx_synopsischrono_view_105` WHERE propalid = " . $ligne->fk_propal);
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
//        echo $tab[0]->email . "Problème technique GLE" . $tab[1];
//        if($_REQUEST['propalMail'] == "mail")
//        mailSyn2("Problème technique GLE", $tab[0]->email.",jc.cannet@bimp.fr", "", $tab[1]);
//    }
//}

if(isset($_REQUEST['lienFactProp'])){
    $sql = $db->query("SELECT f.* FROM llx_facture f LEFT JOIN llx_element_element e ON `sourcetype` LIKE  'propal'
AND  `targettype` LIKE  'facture' AND fk_target = f.rowid WHERE fk_source is null");
    while($ligne = $db->fetch_object($sql)){
        $sql2 = $db->query("SELECT * FROM llx_propal WHERE fk_statut != 3 AND fk_soc = ".$ligne->fk_soc);
        $nb = $db->num_rows($sql2);
        echo $nb."|".$ligne->fk_soc. "<br/>";
        if($nb == 1){
            $ligne2 = $db->fetch_object($sql2);echo "jjjjjjjjjjjjjjjjjjjjjjj".$ligne2->rowid."'";
            addElementElement("propal", "facture", $ligne2->rowid, $ligne->rowid);
        }
       
    }
}





llxFooter();
?>
