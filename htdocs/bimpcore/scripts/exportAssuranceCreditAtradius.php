<?php
/*
 *  Assurance Crédit Atradius
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set('display_errors', 1);
ini_set('memory_limit', "2048MB");
$can_execute = ($user->admin) ? true : false;

if($can_execute) {
    
    $bdd = new BimpDb($db);
    
    $moinTroisAns = new DateTime();
    $moinTroisAns->sub(new DateInterval("P36M"));
    
//    $c_typent_exclude = [];
//    $array_filial = [];
//    
//    $sql = "SELECT id, code, libelle FROM llx_c_typent WHERE active = 1 AND code = 'TE_ADMIN' OR code = 'TE_PRIVATE' OR code = 'TE_UNKNOWN'";
//    $res = $bdd->executeS($sql);
//    foreach($res as $index => $i) { $c_typent_exclude[] = $i->id;}
//    
//    $sql = "SELECT fk_object FROM llx_societe_extrafields WHERE is_subsidiary = 1";
//    $res = $bdd->executeS($sql);
//    foreach($res as $index => $i) { $array_filial[] = $i->fk_object; }
//    
//    echo '<pre>';
//    echo "Date limite dernière facture: " . $moinTroisAns->format('d/m/Y') . "<br />";
//    
//    $sql = "SELECT * FROM ";
//    $sql.= "llx_societe as s WHERE s.rowid > 0 ";
//    foreach($array_filial as $id_exclude) {
//        $sql .= "AND s.rowid <> " . $id_exclude . " ";
//    }
//    foreach($c_typent_exclude as $id_exclude) {
//        $sql .= "AND s.fk_typent <> " . $id_exclude . " ";
//    }
//    echo $sql . '<br />';
//    $res = $bdd->executeS($sql);
//    
//    foreach($res as $index => $object) {
//        // Vérification de la dernière facture
//        if($bdd->getRows('facture', "fk_soc = " . $object->rowid . " AND datef >= '".$moinTroisAns->format('Y-m-d')."'")) {
//            echo $object->nom . " (".$object->rowid.")<br />";
//        }
//    }
    $c_typent_exclude = [];
    $sql = "SELECT id, code, libelle FROM llx_c_typent WHERE active = 1 AND code = 'TE_ADMIN' OR code = 'TE_PRIVATE' OR code = 'TE_UNKNOWN'";
    $res = $bdd->execute($sql);
    foreach($res as $index => $i) { $c_typent_exclude[] = $i->id;}
    
    $sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."' AND f.datef < '2021-01-06'";

    $res = $bdd->execute($sql, 'array');
    $allSociete = [];
    echo count($res) . "<br />";
    
    $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe");
    $tt = 0;
    foreach($res as $index => $array) {
        $client->fetch($array['fk_soc']);
        if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
            $tt++;
            echo $client->getData('nom') . "<br />" ;
        }
        
    }
    echo "<br />" . $tt;
    
}
