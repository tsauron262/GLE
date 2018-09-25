<?php

if (isset($_GET['actionTest'])) {
    require "../../main.inc.php";
    llxHeader();
    $class = new test_sav();
    if ($_GET['actionTest'] == "mailNonFerme")
        $class->mailNonFerme();
    if ($_GET['actionTest'] == "fermetureAuto") {
        $class->tentativeFermetureAuto();
    }
    if ($_GET['actionTest'] == "rfpuAuto") {
        $class->tentativeARestitueAuto();
    }


    if ($_GET['actionTest'] == "global") {
        $class->testGlobal();
    }
    echo "<br/><br/>Fin";
    llxFooter();
}




class test_sav {
    public $output = "";
    public $nbErr = 0;
    public $useCache = false;
    
    function __construct() {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/objects/GSX_Repair.class.php';
    }

    function testGlobal() {
        $this->ok = 0;
        $_GET['envoieMail'] = "yes";
//        $this->tentativeARestitueAuto(4);
//        $this->tentativeARestitueAuto(1);
//        $this->tentativeARestitueAuto(2);
//        $this->tentativeARestitueAuto(3);
        $this->tentativeARestitueAuto(0);

//        $this->tentativeFermetureAuto(4);
//        $this->tentativeFermetureAuto(1);
//        $this->tentativeFermetureAuto(2);
//        $this->tentativeFermetureAuto(3);
        $this->tentativeFermetureAuto(0);
        
        if($this->nbErr > 0)
            $this->output .= $this->nbErr." posant prôbléme.";
        $this->output .= $this->ok." resolu.";
        
        
        return 'END';
    }

    function getReq($statut, $iTribu) {

        $req = "SELECT DATEDIFF(now(), s.date_update) as nbJ, id_user_tech as Technicien, r.id as rid, `serial`, s.id as cid,

s.ref FROM `llx_bs_sav` s, `llx_bimp_gsx_repair` r

WHERE r.`id_sav` = s.`id` AND `" . ($statut == "closed" ? "repair_complete" : "ready_for_pick_up") . "` = 0
AND serial is not null
AND DATEDIFF(now(), s.date_update) < 100 
AND s.status = " . ($statut == "closed" ? "999" : "9");
        
        
        
        
        
            global $user;
            $user->array_options['options_apple_id'] = "tommy@drsi.fr";
            $user->array_options['options_apple_service'] = "897316";
            $user->array_options['options_apple_shipto'] = "1046075";

//        if ($iTribu == 1) {
//            $req .= " AND ( ref LIKE('SAVN%'))";
//            global $user;
//            $user->array_options['options_apple_id'] = "f.marino@bimp.fr";
//            $user->array_options['options_apple_service'] = "0000579256";
//            $user->array_options['options_apple_shipto'] = "0000459993";
//        } elseif ($iTribu == 2) {
//            $req .= " AND ( ref LIKE('SAVMONTP%') || ref LIKE('SAVMAU%'))";
//            global $user;
//            $user->array_options['options_apple_id'] = "xavier@itribustore.fr";
//            $user->array_options['options_apple_service'] = "0000579256";
//            $user->array_options['options_apple_shipto'] = "0000579256";
//        } elseif ($iTribu == 3) {
//            $req .= " AND ( ref LIKE('SAVP%'))";
//            global $user;
//            $user->array_options['options_apple_id'] = "elodie@itribustore.fr";
//            $user->array_options['options_apple_service'] = "579256";
//            $user->array_options['options_apple_shipto'] = "883234";
//        } elseif ($iTribu == 4)
//            $req .= " AND ( ref NOT LIKE('SAVN%') && ref NOT LIKE('SAVP%') && ref NOT LIKE('SAVMONTP%') && ref NOT LIKE('SAVMAU%') )";

        $req .= " AND DATEDIFF(now(), s.date_update) < 100 ORDER BY `nbJ` DESC, s.id";

        $req .= " LIMIT 0,500";
        return $req;
    }

    function tentativeFermetureAuto($iTribu = 0) {
        global $db;
        $sql = $db->query($this->getReq('closed', $iTribu));


        $repair = new GSX_Repair('bimpapple', 'GSX_Repair');



        while ($ligne = $db->fetch_object($sql)) {
                if (!$this->useCache || !isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                    $repair->fetch($ligne->rid);
                    if (count($repair->lookup()) == 0) {
                        echo "Tentative de maj de " . $ligne->ref . ". Fermé dans GLE" . $repair->getData('repair_complete') . " num " . $repair->getData('repair_number') . ". num2 " . $repair->getData('repair_confirm_number') . " Statut dans GSX : " . $repair->repairLookUp['repairStatus'] . "<br/>";
                        if ($repair->getData('repair_complete')) {
                            echo "Fermée dans GSX maj dans GLE.<br/>";
                            $this->ok++;
                        }
                        elseif($repair->repairLookUp['repairStatus'] == "Fermée et complétée"){
                            echo "fermé dans GSX Impossible de Fermé dans GLE ";
                            $this->nbErr++;
                        } 
                        else {
                            
                            
                            
                            
                            $mailTech = "jc.cannet@bimp.fr";
                            if ($ligne->Technicien > 0) {
                                $user = new User($db);
                                $user->fetch($ligne->Technicien);
                                if ($user->statut == 1 && $user->email != "")
                                    $mailTech = $user->email;
                            }

                            if ($repair->repairLookUp['repairStatus'] == "Prêt pour enlèvement") {
                                if (count($repair->close(1, 0)) == 0){
                                    echo "Semble avoir été fermé en auto<br/>";
                                    $this->ok++;
                                }
                                else {
                                    $this->nbErr++;
                                    echo "N'arrive pas a être fermé<br/> ";
                                    if (isset($_GET['envoieMail']))
                                        mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->getData('repair_confirm_number') . " n'est pas fermé dans GSX.  Reponse : " . $repair->repairLookUp['repairStatus']);
                                }
                            }
                            else {//tentative de passage a rfpu
                                if (count($repair->updateStatus('RFPU')) == 0){
                                    echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                    $this->ok++;
                                }
                                else {
                                    $this->nbErr++;
                                    echo "N'arrive pas a être passé a RFPU dans GSX<br/> ";
                                    if (isset($_GET['envoieMail']))
                                        mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->getData('repair_confirm_number') . " n'est pas passé RFPU dans GSX. Reponse : " . $repair->repairLookUp['repairStatus']);
                                }
                            }
                        }
                    }
                    else {
                        $this->nbErr++;
                        echo "Echec de la recup de " . $ligne->ref . " " . $ligne->nbJ . " jours<br/>";
                        $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else{
                    $this->nbErr++;
                    echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " (en cache) " . $ligne->nbJ . " jours<br/>";
                }
            
        }
    }

    function tentativeARestitueAuto($iTribu = 0) {
        global $db;
        $sql = $db->query($this->getReq('ready', $iTribu));


        $repair = new GSX_Repair('bimpapple', 'GSX_Repair');



        while ($ligne = $db->fetch_object($sql)) {
                if (!$this->useCache || !isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                    $repair->fetch($ligne->rid);
                    if (count($repair->lookup()) == 0) {
                        echo "Tentative de maj de " . $ligne->ref . " statut ready for pickup : " . $repair->getData('ready_for_pick_up') . " num " . $repair->repairNumber . ". num2 " . $repair->getData('repair_confirm_number') . " Reponse : " . $repair->repairLookUp['repairStatus'] . "<br/>";
                        if ($repair->repairLookUp['repairStatus'] == "Prêt pour enlèvement" || $repair->getData('ready_for_pick_up')) {
                            echo "Passage dans GLE a RFPU<br/>";
                            $repair->readyForPickUp = 1;
                            $repair->update();
                            $this->ok++;
                        } else {
                            if (count($repair->updateStatus('RFPU')) == 0){
                                echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                $this->ok++;
                            }
                            else {
                                $this->nbErr++;
                                echo "N'arrive pas a être passé a RFPU dans GSX<br/> ";

                                $mailTech = "jc.cannet@bimp.fr";
                                if ($ligne->Technicien > 0) {
                                    $user = new User($db);
                                    $user->fetch($ligne->Technicien);
                                    if ($user->statut == 1 && $user->email != "")
                                        $mailTech = $user->email;
                                }
                                if (isset($_GET['envoieMail']))
                                    mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " avec comme code repa : " . $repair->getData('repair_confirm_number') . " n'est pas passé RFPU dans GSX. Reponse : " . $repair->repairLookUp['repairStatus']);
                            }
                        }
                    }
                    else {
                        $this->nbErr++;
                        echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . "<br/>";
                        $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else{
                    $this->nbErr++;
                    echo "Echec de la recup de " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " (en cache)<br/>";
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
        return "<a href='".DOL_URL_ROOT."/bimpsupport/index.php?fc=sav&id=".$id."'>".$ref."</a>";
    }

}

