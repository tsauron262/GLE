<?php

require "../main.inc.php";


require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';


llxHeader();

$sql = $db->query("SELECT -DATEDIFF(c.tms, now()) as nbJ, `serial_number`, c.id,

c.ref FROM `llx_synopsischrono` c, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `ready_for_pick_up` = 0 AND DATEDIFF(c.tms, now()) < -15  AND DATEDIFF(c.tms, now()) > -60
AND serial_number is not null

ORDER BY `nbJ`  DESC LIMIT 0,10");
echo "debut";
            $GSXdatas = new gsxDatas($ligne->serial_number);
        while ($ligne = $db->fetch_object($sql)){
            echo "dd";
            if ($GSXdatas->connect){
                $response = $GSXdatas->gsx->lookup($ligne->serial_number, 'warranty');
                echo "Tentative de maj de ".$ligne->ref." <br/>";
            }
            else{
                echo "Connexion GSX impossible";
            }
        }
        
        
        llxFooter();