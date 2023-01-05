<?php

require("../../main.inc.php");

llxHeader();


$tabTable = array('propal' => array('datec'), 
    'commande' => array('date_creation'), 
    'facture' => array('datec'), 
    'bs_sav' => array('date_create', 'id_client'), 
    'commande_fournisseur' => array('date_creation'), 
    'facture_fourn' => array('datec'), 
    'societe' => array('datec', 'rowid')
    );

$exclude = array();
foreach($tabTable as $table => $data){
    if(!isset($data[1]))
        $data[1] = 'fk_soc';
    $req = "SELECT ".$data[1]." as id, MAX(".$data[0].") as date FROM ".MAIN_DB_PREFIX.$table ." WHERE `".$data[0]."` > ADDDATE(now(), INTERVAL -5 YEAR) AND ".$data[1]." IN (SELECT rowid FROM llx_societe WHERE date_last_activity < ADDDATE(now(), INTERVAL -4 YEAR) || date_last_activity is NULL) GROUP BY ".$data[1];
    echo $req.'<br/>';
    $sql = $db->query($req);
    while ($ln = $db->fetch_array($sql)){
        if(!isset($exclude[$ln[0]]) || date($ln['date']) > $exclude[$ln[0]])
            $exclude[$ln['id']] = $ln['date'];
    }
    
}

$i = 0;
foreach($exclude as $id => $date){
    $i++;
    if($i > 55000)
        break;
    
    $db->query('UPDATE llx_societe SET date_last_activity = "'.$date.'" WHERE rowid = '.$id);
}


$sql = $db->query("SELECT *  FROM `llx_societe` WHERE (`date_last_activity` IS NULL || date_last_activity < ADDDATE(now(), INTERVAL -5 YEAR)) AND `datec` < ADDDATE(now(), INTERVAL -2 YEAR) ORDER BY `code_compta` ASC;");

echo '<br/>Nombre de client a détruire : '.$db->num_rows($sql).'<br/><br/>';


echo('nb '.count($exclude). ' req '. ($i - 1));
llxFooter();