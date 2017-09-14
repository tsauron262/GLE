

<?php

/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../main.inc.php');

include_once("./class/maj.class.php");

global $nbError;
$nbError = 0;


$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Maj vers 6");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Maj vers 6"));

    include_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        $extrafields = new ExtraFields($db);
    
    $tabModuleReactive = array("modSynopsistools", "modSynopsisApple", "modSynopsisChrono");
    
if (isset($_REQUEST['ok'])) {
    foreach($tabModuleReactive as $mod){
        unActivateModule($mod,0);
        test($db, "Module ".$mod." desactiver");
    }
    
    echo "<form><input type='submit' name='ok2' value='ok'/></form>";
}
elseif (isset($_REQUEST['ok2'])) {
    foreach($tabModuleReactive as $mod){
        activateModule($mod,0);
        test($db, "Module ".$mod." activer");
    }
    
    
    $dest = "type2";
    $result=$extrafields->addExtraField($dest, $dest, 'select', 101, null, "product",null,null,null,array("options"=>array(0=>"Materiel", 1=>"Service inter",2=>"Service Contrat",3=>"Déplacement inter",4=>"Déplacement Contrat",5=>"Logiciel")));
    
    if($result)
    test($db, "Type 2 extrafields");
    
    
    
    $sql = $db->query("SELECT min(`id`) as id FROM `llx_Synopsis_contratdet_GMAO` WHERE 1 GROUP BY `contratdet_refid`  
 having COUNT(`contratdet_refid`) > 1");

    while ($res = $db->fetch_object($sql)) {
        $db->query("DELETE FROM llx_Synopsis_contratdet_GMAO WHERE id = " . $res->id);
        test($db,$res->id . " Supprimé");
        
    }
    
    test($db, "Correction gmao contradet ok");

    $db->query("UPDATE `llx_product_extrafields` pe SET `type2` = (SELECT fk_product_type FROM llx_product p WHERE pe.`fk_object` = p.rowid)");
    $db->query("UPDATE `llx_product` SET fk_product_type = 1 WHERE fk_product_type IN (2,3,4)");

    test($db, "Correction product type");



    $db->query("INSERT INTO `llx_contratdet_extrafields` (`fk_object`) (SELECT rowid FROM llx_contratdet WHERE rowid not in (SELECT fk_object FROM llx_contratdet_extrafields))");

    test($db, "Creation contradet extrafields");


    $tabDepGmaoToContradet = array("sla", "durValid", array("fk_prod", "fk_equipement"), "reconductionAuto", "hotline", "telemaintenanceCur", "maintenanceCur", "nbVisite", "nbVisiteCur", "fk_contrat", "rang");
$i=0;
    foreach ($tabDepGmaoToContradet as $tmp) {
        $i++;
        if (is_array($tmp)) {
            $source = $tmp[0];
            $dest = $tmp[1];
        } else
            $source = $dest = $tmp;
        
        
        $result=$extrafields->addExtraField($dest, $dest, 'varchar', $i+10, 255, "contratdet");
        
        test($db, "Création de ".$dest." extrafields");
        
        
        $db->query("UPDATE llx_contratdet_extrafields cde  SET " . $dest . " = (SELECT " . $source . " from llx_Synopsis_contratdet_GMAO cdg WHERE cde.fk_object = cdg.contratdet_refid)");
        
        
        test($db, "Transfo de ".$source." en ".$dest ."");
    }
    
    
    echo "<br/><br/>Nombre erreurs ".$nbError;

}

else {
    echo "Commencé ?";




    echo "<form><input type='submit' name='ok' value='ok'/></form>";
}


function test($db, $mess){
    global $nbError;
    if($db->lasterror == "")
        echo "<br/><br/>".$mess." OK";
    else{
        if(stripos($mess, "extrafields") === false){
            echo "<br/><br/><span class='error'>".$db->lasterror."   (".$mess.") KO</span>";
            $nbError++;
        }
    }
    $db->lasterror = "";
}