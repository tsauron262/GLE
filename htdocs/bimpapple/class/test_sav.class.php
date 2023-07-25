<?php

if (isset($_GET['actionTest'])) {
    require "../../main.inc.php";
    llxHeader();

    session_write_close();
    $class = new test_sav($db);
    if ($_GET['actionTest'] == "fermetureAuto") {
        $class->tentativeFermetureAuto();
    }
    if ($_GET['actionTest'] == "rfpuAuto") {
        $class->tentativeARestitueAuto();
    }
    if ($_GET['actionTest'] == "fetchEquipmentsImei") {
        $class->fetchEquipmentsImei((isset($_REQUEST['nb']) ? $_REQUEST['nb'] : 10), false);
    }
    if ($_GET['actionTest'] == "fetchImeiPetit") {
        $class->fetchImeiPetit((isset($_REQUEST['nb']) ? $_REQUEST['nb'] : 1));
    }




    if ($_GET['actionTest'] == "global") {
        $class->testGlobal();

        echo $class->output;
    }
    echo "<br/><br/>Fin";
    llxFooter();
}

class test_sav
{

    public $output = "";
    public $nbErr = 0;
    public $nbOk = 0;
    public $nbMail = 0;
    public $nbImei = 0;
    public $useCache = false;

    function __construct($db)
    {
        $this->db = $db;
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/objects/GSX_Repair.class.php';

//        $this->initGsx();
    }

    function initGsx($idUser = 0)
    {
        $error = array();
        $this->repair = new GSX_Repair('bimpapple', 'GSX_Repair');
        if ($idUser > 0 || !$this->repair->initGsx($error)) {
            if ($idUser == 0)
                $idUser = 2;
            global $user, $db, $conf, $langs;
            $conf->entity = 1;
            $user = new User($db);
            $user->fetch($idUser);
            $user->fetch_optionals($idUser);
            if (!$this->repair->initGsx($error, true)) {
                $this->output .= " Non authentifié sur GSX ! " . $user->getFullName($langs) . '<br/>';
                return 0;
            }
        }
        return 1;
    }

    function testGlobal($idUser = 0)
    {
        global $db;
        $_GET['envoieMail'] = "yes";
        session_write_close();
        if (!$this->initGsx($idUser) && !$this->initGsx(242) && !$this->initGsx(60) && $idUser == 0) {
            $sql = $db->query("SELECT MAX(u.rowid) as idUser, gsx_acti_token FROM `llx_user` u, llx_user_extrafields ue WHERE u.rowid = ue.`fk_object` and gsx_acti_token != '' GROUP by `gsx_acti_token`");
            $nbcompte = $db->num_rows($sql);
            if ($nbcompte > 0) {
                $ln = $db->fetch_object($sql);
                $idUser = $ln->idUser;
            }
        }



        $this->tentativeARestitueAuto(0);

        $this->tentativeFermetureAuto(0);

        if ($this->nbErr > 0)
            $this->output .= $this->nbErr . " posant prôbléme.";
        $this->output .= $this->nbOk . " resolu.";
        $this->output .= $this->nbMail . " mail.";

        $this->fetchEquipmentsImei(100);

        global $user;
        $user->fetch(1);

        return 0;
    }

    function mailLocalise()
    {
        $errors = array();
        $sql = $this->db->query('SELECT DISTINCT a.id as id
FROM llx_bs_sav a
LEFT JOIN llx_be_equipment a___equipment ON a___equipment.id = a.id_equipment
WHERE  a.status IN ("-1",0) AND a___equipment.status_gsx IN ("3")');
        while ($ln = $this->db->fetch_object($sql)) {
            $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV', $ln->id);
            $tmpErrors = $sav->sendMsg('localise');
            if (!count($tmpErrors))
                $ok++;
            BimpTools::merge_array($errors, $tmpErrors);
        }

        $this->output .= 'Terminé ' . $ok . ' mail envoyée ' . print_r($errors, 1);
        return 0;
    }

    function getReq($statut, $iTribu)
    {

        $req = "SELECT DATEDIFF(now(), s.date_update) as nbJ, id_user_tech as Technicien, r.id as rid, `serial`, s.id as cid,

s.ref FROM `" . MAIN_DB_PREFIX . "bs_sav` s, `" . MAIN_DB_PREFIX . "bimp_gsx_repair` r

WHERE r.`id_sav` = s.`id` AND `" . ($statut == "closed" ? "repair_complete" : "ready_for_pick_up") . "` = 0
AND serial is not null
AND canceled = 0
AND DATEDIFF(now(), s.date_update) < 60 ";

        if ($statut == "closed") {
            $req .= " AND (s.status = 999 || (DATEDIFF(now(), s.date_terminer) > 5) && s.status >= 9)";
        } else
            $req .= " AND s.status = 9";


        $req .= " AND DATEDIFF(now(), s.date_update) < 100 ORDER BY `nbJ` DESC, s.id";

        $req .= " LIMIT 0,500";

        return $req;
    }

    function tentativeFermetureAuto($iTribu = 0)
    {
        global $db;
        $sql = $db->query($this->getReq('closed', $iTribu));

        while ($ligne = $db->fetch_object($sql)) {
            if (!$this->useCache || !isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                if (isset($this->repair->gsx))
                    $this->repair->gsx->errors['soap'] = array();
                $this->repair->fetch($ligne->rid);
                $erreurSOAP = $this->repair->lookup();
                if (count($erreurSOAP) == 0) {
                    echo "Tentative de maj de " . $ligne->ref;
                    if ($this->repair->getData('repair_complete')) {
                        echo "Fermée dans GSX maj dans GLE.<br/>";
                        $this->nbOk++;
                    } elseif ($this->repair->repairLookUp['repairStatus'] == "SPCM") {//"Fermée et complétée"){
                        echo "fermé dans GSX Impossible de Fermé dans GLE ";
                        $this->nbErr++;
                    } else {
                        $mailTech = "jc.cannet@bimp.fr";
                        if ($ligne->Technicien > 0) {
                            $user = new User($db);
                            $user->fetch($ligne->Technicien);
                            if ($user->statut == 1 && $user->email != "")
                                $mailTech = $user->email;
                        }
                        BimpObject::loadClass('bimpcore', 'Bimp_User');
                        $shipToUsers = Bimp_User::getUsersByShipto($this->repair->getData('ship_to'));
                        if (!empty($shipToUsers)) {
                            foreach ($shipToUsers as $u) {
                                if (isset($u['email']) && $u['email']) {
                                    $mailTech .= ($mailTech ? ',' : '') . $u['email'];
                                }
                            }
                        }
                        $mailTech = BimpTools::cleanEmailsStr($mailTech);

                        if (in_array($this->repair->repairLookUp['repairStatus'], GSX_Repair::$readyForPickupCodes)) {
                            $erreurSOAP = $this->repair->close(1, 0);
                            if (isset($erreurSOAP['errors']))
                                $erreurSOAP = $erreurSOAP['errors'];

                            if (count($erreurSOAP) == 0) {
                                echo "Semble avoir été fermé en auto<br/>";
                                $this->nbOk++;
                            } else {
                                $this->nbErr++;
                                $messErreur = $this->displayError("N'arrive pas a être fermé", $ligne, $this->repair, $erreurSOAP);
                                echo $messErreur;
//                                $mailTech .= ",tommy@bimp.fr";
                                if (isset($_GET['envoieMail'])) {
                                    mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                    $this->nbMail++;
                                }
                            }
                        } else {//tentative de passage a rfpu
                            $erreurSOAP = $this->repair->updateStatus('RFPU');
                            if (count($erreurSOAP) == 0) {
                                echo "Semble avoir été passer dans GSX a RFPU<br/>";
                                $this->nbOk++;
                            } else {
                                $this->nbErr++;
                                $messErreur = $this->displayError("N'arrive pas a être passé a RFPU dans GSX", $ligne, $this->repair, $erreurSOAP);
                                echo $messErreur;

//                                $mailTech .= ", tommy@bimp.fr";
                                if (isset($_GET['envoieMail'])) {
                                    mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                    $this->nbMail++;
                                }
                            }
                        }
                    }
                } else {
                    $this->nbErr++;
                    $messErreur = $this->displayError("Echec de la recup dans GSX", $ligne, $this->repair, $erreurSOAP);
                    echo $messErreur;
                    $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                }
            } else {
                $this->nbErr++;
                $messErreur = $this->displayError("Echec de la recup dans GSX (en cache)", $ligne, null, $erreurSOAP);
                echo $messErreur;
            }
        }
    }

    function displayError($mess, $ligne, $repair = null, $tabError = null)
    {
        $html = "<br/>" . $mess . "<br/> SAV :" . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . " Depuis : " . $ligne->nbJ . " jours";
        if (isset($repair)) {
            $html .= "<br/>Code repa : " . $repair->getData('repair_number') . "  Statut GSX : " . $repair->repairLookUp['repairStatus'];
            $html .= "<br/>RFPU dans GLE ?" . $repair->getData('ready_for_pick_up') . " Fermé dans GLE ?" . $repair->getData('repair_complete');
        }
        if (is_array($tabError) && count($tabError) > 0)
            $html .= "<br/><pre>" . print_r($tabError, 1) . "</pre>";

        $html .= "<br/>";
        return $html;
    }

    function tentativeARestitueAuto($iTribu = 0)
    {
        global $db;
        $sql = $db->query($this->getReq('ready', $iTribu));

        while ($ligne = $db->fetch_object($sql)) {
            if (!$this->useCache || !isset($_SESSION['idRepairIncc'][$ligne->rid])) {
                if (isset($this->repair->gsx))
                    $this->repair->gsx->errors['soap'] = array();
                $this->repair->fetch($ligne->rid);
                $erreurSOAP = $this->repair->lookup();
                if (count($erreurSOAP) == 0) {
                    echo "Tentative de maj de " . $ligne->ref;

                    $mailTech = "jc.cannet@bimp.fr";
                    if ($ligne->Technicien > 0) {
                        $user = new User($db);
                        $user->fetch($ligne->Technicien);
                        if ($user->statut == 1 && $user->email != "")
                            $mailTech = $user->email;
                    }
                    BimpObject::loadClass('bimpcore', 'Bimp_User');
                    $shipToUsers = Bimp_User::getUsersByShipto($this->repair->getData('ship_to'));
                    if (!empty($shipToUsers)) {
                        foreach ($shipToUsers as $u) {
                            if (isset($u['email']) && $u['email']) {
                                $mailTech .= ($mailTech ? ',' : '') . $u['email'];
                            }
                        }
                    }
                    $mailTech = BimpTools::cleanEmailsStr($mailTech);

                    if ($this->repair->repairLookUp['repairStatus'] == "RFPU" || $this->repair->getData('ready_for_pick_up')) {
                        echo "Passage dans GLE a RFPU<br/>";
                        $this->repair->readyForPickUp = 1;
                        $this->repair->update();
                        $this->nbOk++;
                    } elseif ($this->repair->repairLookUp['repairStatus'] == "USHP") {
                        $erreurSOAP = $this->repair->close(1, 0);
                        if (isset($erreurSOAP['errors']))
                            $erreurSOAP = $erreurSOAP['errors'];

                        if (count($erreurSOAP) == 0) {
                            echo "Semble avoir été fermé en auto<br/>";
                            $this->nbOk++;
                        } else {
                            $this->nbErr++;
                            $messErreur = $this->displayError("N'arrive pas a être fermé", $ligne, $this->repair, $erreurSOAP);
                            echo $messErreur;
//                                $mailTech .= ",tommy@bimp.fr";
                            if (isset($_GET['envoieMail'])) {
                                mailSyn2("Sav non fermé dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                $this->nbMail++;
                            }
                        }
                    } else {
                        $erreurSOAP = $this->repair->updateStatus('RFPU');
                        if (count($erreurSOAP) == 0) {
                            echo "Semble avoir été passer dans GSX a RFPU<br/>";
                            $this->nbOk++;
                        } else {
                            $this->nbErr++;
                            $messErreur = $this->displayError("N'arrive pas a être passé a RFPU dans GSX", $ligne, $this->repair, $erreurSOAP);
                            echo $messErreur;

                            if (isset($_GET['envoieMail'])) {
                                mailSyn2("Sav non RFPU dans GSX", $mailTech, "gle_suivi@bimp.fr", "Bonjour le SAV " . $messErreur);
                                $this->nbMail++;
                            }
                        }
                    }
                } else {
                    $this->nbErr++;
                    $messErreur = $this->displayError("Echec de la recup dans GSX ", $ligne, null, $erreurSOAP);
                    echo $messErreur;
                    $_SESSION['idRepairIncc'][$ligne->rid] = $ligne->ref;
                }
            } else {
                $this->nbErr++;
                $messErreur = $this->displayError("Echec de la recup dans GSX (en cache)", $ligne, null, $erreurSOAP);
                echo $messErreur;
            }
        }
    }

//    function mailNonFerme() {
//        global $db;
//        $nbJ = (isset($_GET['nbJ'])) ? $_GET['nbJ'] : 60;
//        $sql = $db->query("SELECT DATEDIFF(now(), c.tms) as nbJ, c.id as cid, Etat, `fk_user_modif` as user, fk_user_author as user2,
//
//c.ref FROM `".MAIN_DB_PREFIX."synopsischrono` c, ".MAIN_DB_PREFIX."synopsischrono_chrono_105 cs
//
//WHERE c.id = cs.id AND cs.Etat != 999 AND cs.Etat != 2 AND cs.Etat != 9 AND DATEDIFF(now(), c.tms) > " . $nbJ . " ORDER BY user");
//        $user = new User($db);
//        $tabUser = array();
//        while ($ligne = $db->fetch_object($sql)) {
//            if ($ligne->user > 0)
//                $userId = $ligne->user;
//            elseif ($ligne->user2 > 0)
//                $userId = $ligne->user2;
//            else
//                $userId = 0;
//
//
//
//            if (!isset($tabUser[$userId])) {
//                $user = new User($db);
//                $user->fetch($userId);
//                $tabUser[$userId] = $user;
//            }
//
//            echo "SAV Non fermé depuis : " . $ligne->nbJ . " jours || " . $this->getNomUrlChrono($ligne->cid, $ligne->ref) . "   par : " . $tabUser[$userId]->getNomUrl(1) . " </br>";
//        }
//    }

    function getNomUrlChrono($id, $ref)
    {
        global $db;
        return "<a href='" . DOL_URL_ROOT . "/bimpsupport/index.php?fc=sav&id=" . $id . "'>" . $ref . "</a>";
    }

    function fetchImeiPetit($nbParUser = 10)
    {
        global $db;

        $sql = $db->query("SELECT MAX(u.rowid) as idUser, gsx_acti_token FROM `llx_user` u, llx_user_extrafields ue WHERE u.rowid = ue.`fk_object` and gsx_acti_token != '' GROUP by `gsx_acti_token`");
        $nbcompte = $db->num_rows($sql);
        while ($ln = $db->fetch_object($sql)) {
            $this->initGsx($ln->idUser);

            $this->fetchEquipmentsImei($nbParUser);
        }

        global $user;
        $user->fetch(1);

        $this->output .= ' ' . $this->nbImei . ' compte réactivé sur ' . $nbcompte;

        return 0;
    }

    function fetchEquipmentsImei($nb = 1, $modeLabel = 0)
    {
        $errors = array();
        if (!class_exists('GSX_v2')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        }

        $gsx = GSX_v2::getInstance(true);
        $gsx->authenticate();

        if ($nb < 1)
            $nb = 1;

        if ($gsx->logged) {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

            if ($modeLabel)
                $filtre = array(
                    'id_product'    => 0,
                    'product_label' => '',
                    'status_gsx'    => array('operator' => '!=', 'value' => 2)
                );
            else
                $filtre = array(
                    'serial' => array(
                        'operator' => '!=',
                        'value'    => '0'
                    )
                );
            if ($nb > 5) {//sinon c'est un test de reconnexion
//                $filtre['status_gsx'] = array(0,3);
                if (!$modeLabel)
                    $filtre['custom'] = array('custom' => '(status_gsx = 0 || (status_gsx = 3 AND id IN (SELECT a.id_equipment FROM llx_bs_sav a WHERE status IN (-1,0,1,2,3,4,5,6,7))))');
                $rows = $equipment->getList($filtre, $nb, 1, 'id', 'desc', 'array', array('id', 'serial'));
            } else {
                $rows = $equipment->getList($filtre, $nb, 1, 'rand', 'asc', 'array', array('id', 'serial'));
            }

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    if (!$gsx->logged) {
                        break;
                    }

                    if (!$r['serial']) {
                        continue;
                    }

                    $equipment->fetch($r['id']);
                    $errors = BimpTools::merge_array($errors, $equipment->majWithGsx());

                    if (count($errors)) {
//                        print_r($errors);
                        $this->output .= 'Erreurs: <pre>' . print_r($errors, 1) . '</pre>';
                        break;
                    }

                    $this->nbImei++;
                }
            }
        }

        if ($this->nbImei) {
            $this->output .= ' ' . $this->nbImei . ' n° IMEI corrigé(s).';
        }

        return 0;
    }
}
