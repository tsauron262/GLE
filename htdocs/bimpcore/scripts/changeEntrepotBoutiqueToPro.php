<?php
require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

$sql = $db->query("SELECT rowid, id_entrepot_pro FROM llx_entrepot WHERE id_entrepot_pro > 0");

$errors = array();
$i = $ok = $bad = 0;
while($ln = $db->fetch_object($sql)){
    $list = BimpObject::getBimpObjectList('bimpcommercial', 'Bimp_Commande', array('entrepot'=>$ln->rowid, 'fk_statut'=>array('0', '1'), 'ef.type'=>'C'));
    echo count($list).'pp';
    foreach($list as $id){
        $commande = BimpObject::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande',$id);
        $commande->set('entrepot', $ln->id_entrepot_pro);
        if($commande->getData('fk_mode_reglement') < 1)
            $commande->set('fk_mode_reglement', 2);
        $tmperrors = $commande->update();
        if(count($tmperrors)){
            $tmperrors = array('Commande '.$commande->getLink(), $tmperrors);
            $errors = BimpTools::merge_array($errors, $tmperrors);
            $bad++;
        }
        else
            $ok++;
        $i++;
        if($i > 50)
            break;
    }
}
echo '<br/>'.$ok.' commandes OK '.$bad.' commandes echéc';
echo '<pre>';
print_r($errors);



$sql = $db->query("SELECT rowid, id_entrepot_pro FROM llx_entrepot WHERE id_entrepot_pro > 0");

$errors = array();
$ok = $bad = 0;
while($ln = $db->fetch_object($sql)){
    $list = BimpObject::getBimpObjectList('bimpcommercial', 'Bimp_CommandeFourn', array('entrepot'=>$ln->rowid, 'fk_statut'=>array('0', '1', '2', '3', '4'), 'ef.type'=>'C'));
    echo count($list).'pp';
    foreach($list as $id){
        $commandeF = BimpObject::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn',$id);
        $commandeF->set('entrepot', $ln->id_entrepot_pro);
        $tmperrors = $commandeF->update();
        if(count($tmperrors)){
            $tmperrors = array('Commande fourn '.$commandeF->getLink().$id, $tmperrors);
            $errors = BimpTools::merge_array($errors, $tmperrors);
            $bad++;
        }
        else
            $ok++;
        $i++;
        if($i > 50)
            break;
    }
}
echo '<br/>'.$ok.' commandes OK '.$bad.' commandes echéc';
echo '<pre>';
print_r($errors);