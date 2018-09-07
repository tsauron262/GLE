<?php

require("../main.inc.php");

llxHeader();



$sql = $db->query("SELECT * FROM `llx_propal_extrafields` WHERE `type` IS NULL ORDER BY `fk_object` ASC");

while($ligne = $db->fetch_object($sql)){
    $propal = new Propal($db);
    $propal->fetch($ligne->fk_object);
    $userT = new User($db);
    $userT->fetch($propal->user_author_id);
    mailSyn("Secteur Devis", "tommy@bimp.fr", "admin@bimp.fr", "Bonjour, suite  aune erreur de ma part, certain devis on perdu leur Secteur, en voici un que vous avez créer ".$propal->getNomUrl(1)." merci de resaisir le secteur en question. <br/><br/> Désolé de la géne occasioné");
}











die;
global $user;
echo "{".$conf->global->MAIN_SECURITY_HASH_ALGO."}".$user->pass_indatabase_crypted."<br/><br/>";


if($_REQUEST['action'] == "caisse"){
    $tabVal = array('SAVA', 'AMP','ACY','ACY','ACY','B07','LYO3','LYO6','CHY','BES','BES','BES','BES','CLE','CLE','CLE','GRE','MAR','MAR','MAU','MAU','MTB','MTP','MTP','NIM','NIM','NIM','PER','PER','PER','STE','STP','STP','VAL');
$tabVal2 = array();

foreach($tabVal as $val){
    if(isset($tabVal2[$val]))
            $tabVal2[$val]++;   
    else{
        $tabVal2[$val] = 1;
    }
}

foreach($tabVal2 as $val => $nb){
        if(!$sql = $db->query("SELECT rowid FROM llx_entrepot WHERE label = '".$val."';"))
            die("erreur sql ");
    if($db->num_rows($sql) < 1)
        die("centre introuvable");
    else{
        $ligne = $db->fetch_object($sql);
        
        
        for($i=1; $i<=$nb;$i++){
            echo "Caisse ".$i." ".$val."<br/>";
            $db->query("INSERT INTO `llx_bc_caisse`(`id_entrepot`, `name`, `status`) VALUES (".$ligne->rowid.",'"."Caisse ".$i."',0);");
        }
        
    }
}



    
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

// Création d'une réservation pour un transfert: 
$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
//
//foreach(array("resaB") as $resa)
//$errors = $reservation->validateArray(array(
//    'id_entrepot' => 4, // ID entrepot: obligatoire. 
//    'status'      => 201, // cf status
//    'type'        => 2, // 2 = transfert
//    'id_commercial', // id user du commercial (facultatif)
//    'id_equipment'=>110, // si produit sérialisé
//    'id_product', // sinon
//    'id_transfert' => 666,
//    'qty', // quantités si produit non sérialisé
//    'date_from' => date_format(dol_now(), "AAAA-MM-JJ HH:MM:SS"), // date de début de la résa (AAAA-MM-JJ HH:MM:SS) 
//    'note' => $resa // note facultative
//        ));
//
//if (!count($errors)) {
//    $errors = $reservation->create();
//}
//else{
//    echo "erreur".print_r($errors,1);
//}


require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php'; // Si pas déjà require

$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher

$list = $reservation->getList(array(
   'id_transfert' => 666, // ID du transfert
), null, null, 'id', 'asc', 'array', array(
   'id', // Mettre ici la liste des champs à retourner.,
    'qty',
    'id_equipment',
    'id_product',
    'status'
));

echo "<pre>";
print_r($list);


echo "fin".print_r($errors,1);;