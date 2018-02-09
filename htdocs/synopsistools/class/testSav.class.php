<?php

if (isset($_GET['actionTest'])) {
    require "../../main.inc.php";
    llxHeader();
    $class = new testSav();
    if ($_GET['actionTest'] == "mailNonFerme")
        mailNonFerme();
    if ($_GET['actionTest'] == "fermetureAuto") {
        $class->tentativeFermetureAuto(4);
        $class->tentativeFermetureAuto(1);
        $class->tentativeFermetureAuto(2);
        $class->tentativeFermetureAuto(3);
    }
    if ($_GET['actionTest'] == "rfpuAuto") {
        $class->tentativeARestitueAuto(4);
        $class->tentativeARestitueAuto(1);
        $class->tentativeARestitueAuto(2);
        $class->tentativeARestitueAuto(3);
    }


    if ($_GET['actionTest'] == "global") {
        $class->testGlobal();
    }
    llxFooter();
}




class testSav {
    
    function __construct() {
        require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';
        echo "ap";
    }

    function testGlobal() {
        echo("av");
        $_GET['envoieMail'] = "yes";
        echo "av2";
        $this->tentativeARestitueAuto(4);
        die("pendant");
        $this->tentativeARestitueAuto(1);
        $this->tentativeARestitueAuto(2);
        $this->tentativeARestitueAuto(3);

        $this->tentativeFermetureAuto(4);
        $this->tentativeFermetureAuto(1);
        $this->tentativeFermetureAuto(2);
        $this->tentativeFermetureAuto(3);
        
        return true;
    }

    function getReq($statut, $iTribu) {

        $req = "SELECT DATEDIFF(now(), c.tms) as nbJ, r.rowid as rid, `serial_number`, c.id as cid,

c.ref FROM `llx_synopsischrono` c, `llx_synopsischrono_chrono_105` cs, `llx_synopsis_apple_repair` r

WHERE r.`chronoId` = c.`id` AND `" . ($statut == "closed" ? "closed" : "ready_for_pick_up") . "` = 0
AND serial_number is not null
AND DATEDIFF(now(), c.tms) < 730 
AND c.id = cs.id AND cs.Etat = " . ($statut == "closed" ? "999" : "9");
        
        
        
            global $user;
            $user->array_options['options_apple_id'] = "tommy@drsi.fr";
            $user->array_options['options_apple_service'] = "897316";
            $user->array_options['options_apple_shipto'] = "1046075";

        if ($iTribu == 1) {
            $req .= " AND ( ref LIKE('SAVN%'))";
            global $user;
            $user->array_options['options_apple_id'] = "f.marino@bimp.fr";
            $user->array_options['options_apple_service'] = "0000579256";
            $user->array_options['options_apple_shipto'] = "0000459993";
        } elseif ($iTribu == 2) {
            $req .= " AND ( ref LIKE('SAVMONTP%') || ref LIKE('SAVMAU%'))";
            global $user;
            $user->array_options['options_apple_id'] = "xavier@itribustore.fr";
            $user->array_options['options_apple_service'] = "0000579256";
            $user->array_options['options_apple_shipto'] = "0000579256";
        } elseif ($iTribu == 3) {
            $req .= " AND ( ref LIKE('SAVP%'))";
            global $user;
            $user->array_options['options_apple_id'] = "elodie@itribustore.fr";
            $user->array_options['options_apple_service'] = "579256";
            $user->array_options['options_apple_shipto'] = "883234";
        } elseif ($iTribu == 4)
            $req .= " AND ( ref NOT LIKE('SAVN%') && ref NOT LIKE('SAVP%') && ref NOT LIKE('SAVMONTP%') && ref NOT LIKE('SAVMAU%') )";

        $req .= " ORDER BY `nbJ` DESC, c.id";

        $req .= " LIMIT 0,500";
        return $req;
    }

    function tentativeFermetureAuto($iTribu = 0) {

        global $db;
        $sql = $db->query($this->getReq('closed', $iTribu));

echo "av gsx";
        $GSXdatas = new gsxDatas($ligne->serial_number);
        $repair = new Repair($db, $GSXdatas->gsx, false);

echo "ap gsx ".$sql;

        while ($ligne = $db->fetch_object($sql)) {
            echo "debiut boucle";
            if ($GSXdatas->connect) {
                if (1){//!isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                    $repair->rowId = $ligne->rid;
                    $repair->load();
                    if ($repair->lookup()) {
                        echo "Tentative de maj de " . $ligne->ref . " statut " . $repair->repairComplete . " num " . $repair->repairNumber . ". num2 " . $repair->confirmNumbers['repair'] . " Reponse : " . $repair->repairLookUp['repairStatus'] . "<br/>";
                        if ($repair->repairComplete) {
                            echo "Fermée dans GSX maj dans GLE.<br/>";
                        } else {
                            $mailTech = "jc.cannet@bimp.fr";
                            if ($ligne->Technicien > 0) {
                                $user = new User($db);
                                $user->fetch($ligne->Technicien);
                                if ($user->statut == 1 && $user->email != "")
                                    $mailTech = $user->email;
                            }
$mailTech = "tommy@bimp.fr, jc.cannet@bimp.fr";

                            if ($repair->repairLookUp['repairStatus'] == "Prêt pour enlèvement") {
                                if ($repair->close(1, 0))
                                    echo "Semble avoir été fermé en auto<br/>";
                                else {
                                    echo "N'arrive pas a être fermé<br/> ";
                                    if (isset($_GET['envoieMail']))
                                        mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->confirmNumbers['repair'] . " n'est pas fermé dans GSX.  Reponse : " . $repair->repairLookUp['repairStatus']);
                                }
                            }
                            else {//tentative de passage a rfpu
                                if ($repair->updateStatus('RFPU'))
                                    echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                else {
                                    echo "N'arrive pas a être passé a RFPU dans GSX<br/> ";
                                    if (isset($_GET['envoieMail']))
                                        mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->confirmNumbers['repair'] . " n'est pas passé RFPU dans GSX. Reponse : " . $repair->repairLookUp['repairStatus']);
                                }
                            }
                        }
                    }
                    else {
                        echo "Echec de la recup de " . $ligne->ref . " " . $ligne->nbJ . " jours<br/>";
                        //$_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else
                    echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " (en cache) " . $ligne->nbJ . " jours<br/>";
            }
            else {
                echo "Connexion GSX impossible";
            }
        }
    }

    function tentativeARestitueAuto($iTribu = 0) {

        global $db;
        $sql = $db->query($this->getReq('ready', $iTribu));


echo "av gsx";
        $GSXdatas = new gsxDatas($ligne->serial_number);
        $repair = new Repair($db, $GSXdatas->gsx, false);

echo "ap gsx";

        while ($ligne = $db->fetch_object($sql)) {
            if ($GSXdatas->connect) {
                if (!isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                    $repair->rowId = $ligne->rid;
                    $repair->load();
                    if ($repair->lookup()) {
                        echo "Tentative de maj de " . $ligne->ref . " statut " . $repair->repairComplete . " num " . $repair->repairNumber . ". num2 " . $repair->confirmNumbers['repair'] . " Reponse : " . $repair->repairLookUp['repairStatus'] . "<br/>";
                        if ($repair->repairLookUp['repairStatus'] == "Prêt pour enlèvement" || $repair->repairComplete) {
                            echo "Passage dans GLE a RFPU<br/>";
                            $repair->readyForPickUp = 1;
                            $repair->update();
                        } else {
                            if ($repair->updateStatus('RFPU'))
                                echo "Semble avoir été passer dans GSX a RFPU<br/>";
                            else {
                                echo "N'arrive pas a être passé a RFPU dans GSX<br/> ";

                                $mailTech = "jc.cannet@bimp.fr";
                                if ($ligne->Technicien > 0) {
                                    $user = new User($db);
                                    $user->fetch($ligne->Technicien);
                                    if ($user->statut == 1 && $user->email != "")
                                        $mailTech = $user->email;
                                }
$mailTech = "tommy@bimp.fr, jc.cannet@bimp.fr";
                                if (isset($_GET['envoieMail']))
                                    mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->confirmNumbers['repair'] . " n'est pas passé RFPU dans GSX. Reponse : " . $repair->repairLookUp['repairStatus']);
                            }
                        }
                    }
                    else {
                        echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . "<br/>";
                        $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else
                    echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " (en cache)<br/>";
            }
            else {
                echo "Connexion GSX impossible";
            }
        }
    }

    function mailNonFerme() {
        global $db;
        $nbJ = (isset($_GET['nbJ'])) ? $_GET['nbJ'] : 60;
        $sql = $db->query("SELECT DATEDIFF(now(), c.tms) as nbJ, c.id as cid, Etat, `fk_user_modif` as user, fk_user_author as user2,

c.ref FROM `llx_synopsischrono` c, llx_synopsischrono_chrono_105 cs

WHERE c.id = cs.id AND cs.Etat != 999 AND cs.Etat != 2 AND cs.Etat != 9 AND DATEDIFF(now(), c.tms) > " . $nbJ . " ORDER BY user");
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

            echo "SAV Non fermé depuis : " . $ligne->nbJ . " jours || " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . "   par : " . $tabUser[$userId]->getNomUrl(1) . " </br>";
        }
    }

    function getNomUrlChrono($id, $ref) {
        global $db;
        $chrono = new Chrono($db);
        $chrono->ref = $ref;
        $chrono->id = $id;
        return $chrono->getNomUrl();
    }

}
