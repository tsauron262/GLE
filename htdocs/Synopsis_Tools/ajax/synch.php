<?php

require_once('../../main.inc.php');

//function boucle(
//}

class Boucle /* extends Thread */ {

    public function run($idMax, $nbBoucle = 0) {
        $nbBoucle ++;
        global $db;
        $changement = false;
        $et = ">";
        $return = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $return .= "<ajax-response>";
        $return .= "<res>";
        $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "actioncomm where id > " . $idMax);
//        $sql2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax WHERE vue = 0 AND idPagePrinc =".$_REQUEST['idPagePrinc']);
        if ($db->num_rows($sql) > 0) {
            require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
            $result = $db->fetch_object($sql);
            $object = new ActionComm($db);
            $object->fetch($result->id);
//            $return .= "<echo>" . $result->label . "hihi</echo>";
            $return .= "<idAction>" . $result->id . "</idAction>";
            $return .= "<titreAction>" . $object->getNomUrl(1) . "</titreAction>";
            $note1 = str_replace("\n", "<br/>", $object->note);
            $note2 = $object->getNomUrl($object->fk_element, $object->elementtype, 1);
            if($note != '' && $note2 != '')
                $note1 .= "<br/>";
            $return .= "<msgAction>" .$note1.$note2. "</msgAction>";
//            $return .= "<msgAction><a href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $result->id . "'>" . $result->label . "</a><br/>" . $result->note . "</msgAction>";
            $changement = true;
        } /*elseif ($db->num_rows($sql2) > 0) {
            $result = $db->fetch_object($sql2);
            if ($result->typeElem == "chrono") {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
                $object = new Chrono($db);
            }
            elseif ($result->typeElem == "commande") {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/divers.class.php");
                $object = new Synopsis_Commande($db);
            }
                $object->fetch($result->idElem);
//            $return .= "<echo>" . $result->idElem . "hihi</echo>";
                $return .= "<idNotif>" . $result->id . "</idNotif>";
                $return .= "<idElemNotif>" . $result->idElem . "</idElemNotif>";
                $return .= "<typeElemNotif>" . $result->typeElem . "</typeElemNotif>";
                $return .= "<titreNotif>" . $object->getNomUrl(1) . "</titreNotif>";
                $db->query("UPDATE " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax SET vue = 1 WHERE id =" . $result->id);
                $changement = true;
        }*/
        if ($nbBoucle < 2 && $changement == false && !connection_aborted()) {
            usleep(500000);
            $this->run($idMax, $nbBoucle);
        } else {
//        echo $idMax;
            $return .= "</res>";
            $return .= "</ajax-response>";
            echo $return;
        }
    }

}

if (isset($_REQUEST['idMax'])) {
//boucle();
    $boucle = new Boucle();
    $boucle->run($_REQUEST['idMax']);
}
if (isset($_REQUEST['idVue'])) {
//boucle();
    die();
}


if (isset($_REQUEST['callBack'])) {
    $db->query("UPDATE " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax SET callBack = 1 WHERE id =" . $_REQUEST['callBack']);
}
if (isset($_REQUEST['ouvreId']) && isset($_REQUEST['ouvreType'])) {
    $db->query("INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax (idPagePrinc, typeElem, idElem) VALUES ('".$_REQUEST['idPagePrinc']."', '" . $_REQUEST['ouvreType'] . "', '" . $_REQUEST['ouvreId'] . "')");
    boucleCallBack($db->last_insert_id(MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax"));
}

function boucleCallBack($id) {
//    global $db;
//    sleep(1);
//    $sql2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationAjax WHERE callBack = 1 AND id =" . $id);
//    if ($db->num_rows($sql) > 0)
//        die($_REQUEST['ouvreChrono']);
//    else
//        boucleCallBack($id);
}

?>
