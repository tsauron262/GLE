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
        $nbJ = 15;
$sql = $db->query("SELECT -DATEDIFF(c.tms, now()) as nbJ, c.id,

c.ref FROM `llx_synopsischrono` c, llx_synopsischrono_chrono_105 cs

WHERE c.id = cs.id AND cs.Etat != 999 AND DATEDIFF(c.tms, now()) < ".-$nbJ."");
    
    while ($ligne = $db->fetch_object($sql)) {
        echo "SAV Non fermé depuis plus de : ".$nbJ." jours || ".$ligne->ref."</br>";
    }
}