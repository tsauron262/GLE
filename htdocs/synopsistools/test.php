<?php

require "../main.inc.php";


require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';


llxHeader();


mailNonFerme();
        
        
        llxFooter();
        
        
        
function tentativeFermetureAuto(){
            global $db;
            $req = "SELECT -DATEDIFF(c.tms, now()) as nbJ, r.rowid as rid, `serial_number`, c.id,

c.ref FROM `llx_synopsischrono` c, `llx_synopsischrono_chrono_105` cs, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `closed` = 0 AND DATEDIFF(c.tms, now()) > -15
AND serial_number is not null
AND c.id = cs.id AND cs.Etat = 999

ORDER BY `nbJ` DESC, c.id";
            
            $req .= " LIMIT 0,500";
            $sql = $db->query($req);

    $GSXdatas = new gsxDatas($ligne->serial_number);
    $repair = new Repair($db, $GSXdatas->gsx, false);
    while ($ligne = $db->fetch_object($sql)) {
        if ($GSXdatas->connect) {
            $repair->rowId = $ligne->rid;
            $repair->load();
            if ($repair->lookup())
                echo "Tentative de maj de " . $ligne->ref . " statut " . $repair->repairComplete . " num " . $repair->repairNumber . ". num2 " . $repair->confirmNumbers['repair'] . "<br/>";
            else
                echo "Echec de ma recup de " . $ligne->ref . "<br/>";
        }
        else {
            echo "Connexion GSX impossible";
        }
    }
}



function mailNonFerme(){
    global $db;
        $nbJ = (isset($_GET['nbJ']))? $_GET['nbJ'] : 60;
$sql = $db->query("SELECT -DATEDIFF(c.tms, now()) as nbJ, c.id, Etat, `fk_user_modif` as user, fk_user_author as user2,

c.ref FROM `llx_synopsischrono` c, llx_synopsischrono_chrono_105 cs

WHERE c.id = cs.id AND cs.Etat != 999 AND cs.Etat != 2 AND cs.Etat != 9 AND DATEDIFF(c.tms, now()) < ".-$nbJ."");
    $user = new User($db);
    $tabUser = array();
    while ($ligne = $db->fetch_object($sql)) {
        if($ligne->user > 0)
            $userId = $ligne->user;
        elseif($ligne->user2 > 0)
            $userId = $ligne->user2;
        else
            $userId = 0;
        
        
        
        if(!isset($tabUser[$userId])){
            $user = new User($db);
            $user->fetch($userId);
            $tabUser[$userId] = $user;
        }
        
        echo "SAV Non fermé depuis : ".$ligne->nbJ." jours || ".$ligne->ref."   par : ".$tabUser[$userId]->getNomUrl(1)." </br>";
    }
}