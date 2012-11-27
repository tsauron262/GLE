<?php

class SynopsisRevisionPropal {

    function SynopsisRevisionPropal($propal) {
        global $db;
        $this->propal = $propal;
        $result = $db->query("SELECT import_key as pre, extraparams as sui FROM " . MAIN_DB_PREFIX . "propal WHERE rowid = " . $propal->id);
        $obj = $db->fetch_object($result);

        $this->propalPre = new propal($db);
        $this->propalPre->fetch($obj->pre);

        $this->propalSui = new propal($db);
        $this->propalSui->fetch($obj->sui);
    }

    function reviserPropal() {
        global $conf, $db;
        $propal = $this->propal;
        $socid = $propal->socid;
        $parameters = array('socid' => $socid);
        $oldRef = $propal->ref;
        $oldId = $propal->id;
        $object = $propal;
        $actionHook = 'confirm_clone';
        include_once(DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php');
        $hookmanager = new HookManager($db);
        $hookmanager->initHooks(array('propalcard'));
        $hookmanager->executeHooks('doActions', $parameters, $object, $actionHook);    // Note that $action and $object may have been modified by some hooks

        $result = $object->createFromClone($socid, $hookmanager);
        if ($result > 0) {
            echo $oldRef; //.print_r($propal, true);
            $tabT = explode("-", $oldRef);
//        die($tabT[]);
            if (!isset($tabT[1]))
                $tabT[1] = 0;
            if (!is_numeric($tabT[1]))
                $tabT[1] = self::alpha2num($tabT[1]);
            $orgRef = $tabT[0];
            if ($conf->global->PROPAL_REVISION_MODEL . "x" != 'x' && is_file(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $conf->global->PROPAL_REVISION_MODEL . ".class.php")) {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $conf->global->PROPAL_REVISION_MODEL . ".class.php");
                $tmp = $conf->global->PROPAL_REVISION_MODEL;
                $revMod = new $tmp($db);
                $numRevision = $tabT[1];
                echo "|" . $numRevision;
                $numRevision++;
                echo "|" . $numRevision;
                $newRef = $orgRef . "-" . $revMod->convert_revision($numRevision);
            } else {
                $numRevision = intval($tabT[1]) + 1;
                $newRef = $orgRef . "-" . $numRevision;
            }

            $requete = "UPDATE " . MAIN_DB_PREFIX . "propal set ref = '" . $newRef . "', import_key = " . $oldId . " WHERE rowid = " . $result;
            $db->query($requete);
            $requete = "UPDATE " . MAIN_DB_PREFIX . "propal set extraparams = " . $result . ", fk_statut = 3 WHERE rowid = " . $oldId;
            $db->query($requete);


            header("Location: " . '../comm/propal.php?id=' . $result);
            exit;
        } else {
            $mesg = $object->error;
        }
        print ($mesg . " Erreur" . $socid);
    }

    private static function alpha2num($a) {
        if ($a == "0")
            $a = "A";
        $r = 0;
        $l = strlen($a);
        for ($i = 0; $i < $l; $i++) {
            $r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
        }
        return $r - 1;
    }

    public function getPropalPrec() {
        return $this->propalPre;
    }

    public function getPropalSuiv() {
        return $this->propalSui;
    }

}