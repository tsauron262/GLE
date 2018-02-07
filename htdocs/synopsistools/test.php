<?php

require "../main.inc.php";


require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';


llxHeader();

$sql = $db->query("SELECT -DATEDIFF(c.tms, now()) as nbJ, r.rowid as rid, `serial_number`, c.id,

c.ref FROM `llx_synopsischrono` c, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `closed` = 0 AND DATEDIFF(c.tms, now()) < 0  AND DATEDIFF(c.tms, now()) > -20
AND serial_number is not null

ORDER BY `nbJ` DESC, c.id LIMIT 0,500");
echo "debut";

$GSXdatas = new gsxDatas($ligne->serial_number);
$repair = new Repair($db, $GSXdatas->gsx, false);
        while ($ligne = $db->fetch_object($sql)){
            if ($GSXdatas->connect){
                $repair->rowId = $ligne->rid;
                $repair->load();
                if($repair->lookup())
                    echo "Tentative de maj de ".$ligne->ref." statut ".$repair->repairComplete." num ".$repair->repairNumber.". num2 ".$repair->confirmNumbers['repair']."<br/>";
                else
                    echo "Echec de ma recup de ".$ligne->ref."<br/>";
            }
            else{
                echo "Connexion GSX impossible";
            }
        }
        
        
        llxFooter();