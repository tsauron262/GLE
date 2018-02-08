<?php

require "../main.inc.php";


require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';


llxHeader();

if (isset($_GET['action'])) {
    if ($_GET['action'] == "mailNonFerme")
        mailNonFerme();
    if ($_GET['action'] == "fermetureAuto")
        tentativeFermetureAuto();
    if ($_GET['action'] == "mailFermePasGsx")
        mailFermePasGsx();
}


llxFooter();

function tentativeFermetureAuto() {
    global $db;
    $req = "SELECT -DATEDIFF(c.tms, now()) as nbJ, r.rowid as rid, `serial_number`, c.id as cid,

c.ref FROM `llx_synopsischrono` c, `llx_synopsischrono_chrono_105` cs, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `closed` = 0 AND DATEDIFF(c.tms, now()) > -30
AND serial_number is not null
AND c.id = cs.id AND cs.Etat = 999

ORDER BY `nbJ` DESC, c.id";

    $req .= " LIMIT 0,500";
    $sql = $db->query($req);

    
    $user->array_options['options_apple_id']= "elodie@itribustore.fr";
    $user->array_options['options_apple_service'] = "579256";
    $user->array_options['options_apple_shipto'] = "883234";
    
    
    $GSXdatas = new gsxDatas($ligne->serial_number);
    $repair = new Repair($db, $GSXdatas->gsx, false);
    
    
    
    while ($ligne = $db->fetch_object($sql)) {
        if ($GSXdatas->connect) {
            if (!isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                $repair->rowId = $ligne->rid;
                $repair->load();
                if ($repair->lookup())
                    echo "Tentative de maj de " . $ligne->ref . " statut " . $repair->repairComplete . " num " . $repair->repairNumber . ". num2 " . $repair->confirmNumbers['repair'] . " Reponsse : ".$repair->repairLookUp['repairStatus']."<br/>";
                else {
                    echo "Echec de la recup de " . $ligne->ref . "<br/>";
                    $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                }
            } else
                echo "Echec de la recup de " . getNomUrlChrono($ligne->cid, $ligne->ref) . " (en cache)<br/>";
        }
        else {
            echo "Connexion GSX impossible";
        }
    }
}

function mailNonFerme() {
    global $db;
    $nbJ = (isset($_GET['nbJ'])) ? $_GET['nbJ'] : 60;
    $sql = $db->query("SELECT -DATEDIFF(c.tms, now()) as nbJ, c.id as cid, Etat, `fk_user_modif` as user, fk_user_author as user2,

c.ref FROM `llx_synopsischrono` c, llx_synopsischrono_chrono_105 cs

WHERE c.id = cs.id AND cs.Etat != 999 AND cs.Etat != 2 AND cs.Etat != 9 AND DATEDIFF(c.tms, now()) < " . -$nbJ . " ORDER BY user");
    $user = new User($db);
    $tabUser = array();
    while ($ligne = $db->fetch_object($sql)) {
        if ($ligne->user > 0)
            $userId = $ligne->user;
        elseif ($ligne->user2 > 0)
            $userId = $ligne->user2;
        else
            $userId = 0;



        if (!isset($tabUser[$userId])) {
            $user = new User($db);
            $user->fetch($userId);
            $tabUser[$userId] = $user;
        }

        echo "SAV Non fermé depuis : " . $ligne->nbJ . " jours || " . getNomUrlChrono($ligne->cid, $ligne->ref) . "   par : " . $tabUser[$userId]->getNomUrl(1) . " </br>";
    }
}

function mailFermePasGsx() {
    global $db;
    tentativeFermetureAuto();
    $req = "SELECT -DATEDIFF(c.tms, now()) as nbJ, r.rowid as rid, `serial_number`, c.id as cid, c.ref, Technicien 
        
FROM `llx_synopsischrono` c, `llx_synopsischrono_chrono_105` cs, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `closed` = 0 AND DATEDIFF(c.tms, now()) > -30
AND serial_number is not null
AND c.id = cs.id AND cs.Etat = 999

ORDER BY `nbJ` DESC, c.id";

    $req .= " LIMIT 0,500";
    $sql = $db->query($req);

    $GSXdatas = new gsxDatas($ligne->serial_number);
    $repair = new Repair($db, $GSXdatas->gsx, false);
    while ($ligne = $db->fetch_object($sql)) {
        if (!isset($_SESSION['idRepairIncc'][$ligne->rid])) {
            $repair->rowId = $ligne->rid;
            $repair->load();
            
            $mailTech = "jc.cannet@bimp.fr";
            if($ligne->Technicien > 0){
                $user = new User($db);
                $user->fetch($ligne->Technicien);
                if($user->statut == 1 && $user->email != "")
                    $mailTech = $user->email;
            }
            
            mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV ".getNomUrlChrono($ligne->cid, $ligne->ref)." avec comme code repa : ".$repair->confirmNumbers['repair']." n'est pas fermé dans GSX.");
            echo "Necessite fermeture manuelle de " . getNomUrlChrono($ligne->cid, $ligne->ref)  . " num " . $repair->repairNumber . ". num2 " . $repair->confirmNumbers['repair'] . " Mail envoyé a ".$mailTech."<br/>";
        }
    }
}

function getNomUrlChrono($id, $ref){
    global $db;
    $chrono = new Chrono($db);
    $chrono->ref = $ref;
    $chrono->id = $id;
    return $chrono->getNomUrl();
}
