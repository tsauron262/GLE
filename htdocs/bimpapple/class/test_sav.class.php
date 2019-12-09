<?php

if (isset($_GET['actionTest'])) {
    require "../../main.inc.php";
    llxHeader();
    
    session_write_close();
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
    public $nbOk = 0;
    public $nbMail = 0;
    public $useCache = false;
    
    function __construct() {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/objects/GSX_Repair.class.php';
    }

    function testGlobal() {
        $_GET['envoieMail'] = "yes";
    session_write_close();
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
        $this->output .= $this->nbOk." resolu.";
        $this->output .= $this->nbMail." mail.";
        
        
        return 'END';
    }

    function getReq($statut, $iTribu) {

        $req = "SELECT DATEDIFF(now(), s.date_update) as nbJ, id_user_tech as Technicien, r.id as rid, `serial`, s.id as cid,

s.ref FROM `".MAIN_DB_PREFIX."bs_sav` s, `".MAIN_DB_PREFIX."bimp_gsx_repair` r

WHERE r.`id_sav` = s.`id` AND `" . ($statut == "closed" ? "repair_complete" : "ready_for_pick_up") . "` = 0
AND serial is not null
AND canceled = 0
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
                    if(isset($repair->gsx))
                        $repair->gsx->errors['soap'] = array();
                    $repair->fetch($ligne->rid);
                    $erreurSOAP = $repair->lookup();
                    if (count($erreurSOAP) == 0) {
                        echo "Tentative de maj de " . $ligne->ref;
                        if ($repair->getData('repair_complete')) {
                            echo "Fermée dans GSX maj dans GLE.<br/>";
                            $this->nbOk++;
                        }
                        elseif($repair->repairLookUp['repairStatusCode'] == "SPCM"){//"Fermée et complétée"){
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

                            if ($repair->repairLookUp['repairStatusCode'] == "Prêt pour enlèvement") {
                                $erreurSOAP = $repair->close(1, 0);
                                if (count($erreurSOAP) == 0){
                                    echo "Semble avoir été fermé en auto<br/>";
                                    $this->nbOk++;
                                }
                                else {
                                    $this->nbErr++;
                                    $messErreur = $this->displayError("N'arrive pas a être fermé", $ligne, $repair, $erreurSOAP);
                                    echo $messErreur;
                                    if (isset($_GET['envoieMail'])){
                                        mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                        $this->nbMail++;
                                    }
                                }
                            }
                            else {//tentative de passage a rfpu
                                $erreurSOAP = $repair->updateStatus('RFPU');
                                if (count($erreurSOAP) == 0){
                                    echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                    $this->nbOk++;
                                }
                                else {
                                    $this->nbErr++;
                                    $messErreur = $this->displayError("N'arrive pas a être passé a RFPU dans GSX", $ligne, $repair, $erreurSOAP);
                                    echo $messErreur;
                                    if (isset($_GET['envoieMail'])){
                                        mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                        $this->nbMail++;
                                    }
                                }
                            }
                        }
                    }
                    else {
                        $this->nbErr++;
                        $messErreur = $this->displayError("Echec de la recup dans GSX", $ligne, $repair, $erreurSOAP);
                        echo $messErreur;
                        $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else{
                    $this->nbErr++;
                    $messErreur = $this->displayError("Echec de la recup dans GSX (en cache)", $ligne, null, $erreurSOAP);
                    echo $messErreur;
                }
            
        }
    }
    
    function displayError($mess, $ligne, $repair = null, $tabError = null){
        $html = "<br/>".$mess ."<br/> SAV :". $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " Depuis : " . $ligne->nbJ . " jours";
        if(isset($repair)){
            $html .= "<br/>Code repa : " . $repair->getData('repair_number') . "  Statut GSX : " . $repair->repairLookUp['repairStatusCode'];
            $html .= "<br/>RFPU dans GLE ?".$repair->getData('ready_for_pick_up')." Fermé dans GLE ?".$repair->getData('repair_complete');
        }
        if(is_array($tabError) && count($tabError) > 0)
           $html .= "<br/><pre>".print_r($tabError,1)."</pre>";
        
        $html .= "<br/>";
        return $html;
    }

    function tentativeARestitueAuto($iTribu = 0) {
        global $db;
        $sql = $db->query($this->getReq('ready', $iTribu));


        $repair = new GSX_Repair('bimpapple', 'GSX_Repair');



        while ($ligne = $db->fetch_object($sql)) {
                if (!$this->useCache || !isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                    if(isset($repair->gsx))
                        $repair->gsx->errors['soap'] = array();
                    $repair->fetch($ligne->rid);
                    $erreurSOAP = $repair->lookup();
                    if (count($erreurSOAP) == 0) {
                        echo "Tentative de maj de " . $ligne->ref;
                        if ($repair->repairLookUp['repairStatusCode'] == "Prêt pour enlèvement" || $repair->getData('ready_for_pick_up')) {
                            echo "Passage dans GLE a RFPU<br/>";
                            $repair->readyForPickUp = 1;
                            $repair->update();
                            $this->nbOk++;
                        } else {
                            if (count($repair->updateStatus('RFPU')) == 0){
                                echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                $this->nbOk++;
                            }
                            else {
                                $this->nbErr++;
                                $messErreur = $this->displayError("N'arrive pas a être passé a RFPU dans GSX", $ligne, $repair, $erreurSOAP);
                                echo $messErreur;

                                $mailTech = "jc.cannet@bimp.fr";
                                if ($ligne->Technicien > 0) {
                                    $user = new User($db);
                                    $user->fetch($ligne->Technicien);
                                    if ($user->statut == 1 && $user->email != "")
                                        $mailTech = $user->email;
                                }
//                                if (isset($_GET['envoieMail'])){
//                                    mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
//                                    $this->nbMail++;
//                                }
                            }
                        }
                    }
                    else {
                        $this->nbErr++;
                        $messErreur = $this->displayError("Echec de la recup dans GSX ", $ligne, $repair, $erreurSOAP);
                        echo $messErreur;
                        $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                    }
                } else{
                    $this->nbErr++;
                    $messErreur = $this->displayError("Echec de la recup dans GSX (en cache)", $ligne, null, $erreurSOAP);
                    echo $messErreur;
                }
            
        }
    }

    function mailNonFerme() {
        global $db;
        $nbJ = (isset($_GET['nbJ'])) ? $_GET['nbJ'] : 60;
        $sql = $db->query("SELECT DATEDIFF(now(), c.tms) as nbJ, c.id as cid, Etat, `fk_user_modif` as user, fk_user_author as user2,

c.ref FROM `".MAIN_DB_PREFIX."synopsischrono` c, ".MAIN_DB_PREFIX."synopsischrono_chrono_105 cs

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

