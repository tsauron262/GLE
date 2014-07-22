<?php

class SynopsisRevision {
    
    static $separateur = "–";

    static function alpha2num($a) {
        if ($a == "0")
            $a = "A";
        $r = 0;
        $l = strlen($a);
        for ($i = 0; $i < $l; $i++) {
            $r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
        }
        return $r - 1;
    }
    
    static function updateRef($id, $oldRef, $table){
        global $db;
        $result = self::convertRef($oldRef, $table);
        $db->query("UPDATE ". MAIN_DB_PREFIX . $table ." set ref ='".$result."' WHERE rowid=".$id);
    }

    static function convertRef($oldRef, $table) {
        global $conf;
        $oldRef = self::getRefMax($oldRef, $table);
        if ($oldRef) {
            $tabT = explode(self::$separateur, $oldRef[1]);
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
                $numRevision++;
                $newRef = $orgRef . self::$separateur . $revMod->convert_revision($numRevision);
            } else {
                $numRevision = intval($tabT[1]) + 1;
                $newRef = $orgRef . self::$separateur . $numRevision;
            }
            return $newRef;
        }
        return false;
    }

    static function getRefMAx($ref, $table) {
        global $db;
        $tabT = explode(self::$separateur, $ref);
        $ref = $tabT[0];
        $sql = $db->query("SELECT ref, rowid FROM " . MAIN_DB_PREFIX . $table . " WHERE ref LIKE '" . $ref . "%' ORDER BY rowid DESC");
        if ($db->num_rows($sql) > 0) {
            $result = $db->fetch_object($sql);
            return array($result->rowid, $result->ref);
        }
        return false;
    }

}

class SynopsisRevisionPropal extends SynopsisRevision {
    private static $oldRefCli = "";

    function SynopsisRevisionPropal($propal) {
        global $db;
        $this->propal = $propal;
        $result = $db->query("SELECT import_key as pre, extraparams as sui FROM " . MAIN_DB_PREFIX . "propal WHERE rowid = " . $propal->id);
        $obj = $db->fetch_object($result);

        $this->propalPre = new propal($db);
        if ($obj->pre > 0)
            $this->propalPre->fetch($obj->pre);

        $this->propalSui = new propal($db);
        if ($obj->sui > 0)
            $this->propalSui->fetch($obj->sui);
    }

    function reviserPropal() {
        global $conf, $db;
        $propal = $this->propal;
        $socid = $propal->socid;
        $parameters = array('socid' => $socid);
        $oldRef = $propal->ref;
        $oldId = $propal->id;
        self::$oldRefCli = $propal->ref_client;
        $object = $propal;
        $actionHook = 'confirm_clone';
        include_once(DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php');
        $hookmanager = new HookManager($db);
        $hookmanager->initHooks(array('propalcard'));
        $hookmanager->executeHooks('doActions', $parameters, $object, $actionHook);    // Note that $action and $object may have been modified by some hooks

        $result = $object->createFromClone($socid, $hookmanager);
        if ($result > 0) {
            echo $oldRef; //.print_r($propal, true);
            self::setLienRevision($oldRef, $oldId, $result);


            header("Location: " . '../comm/propal.php?id=' . $result);
            exit;
        } else {
            $mesg = $object->error;
        }
        print ($mesg . " Erreur" . $socid);
    }

    public static function setLienRevision($oldRef, $oldId, $newId, $newRef = null) {
        global $conf, $db;

        if(!isset($newRef))
        $newRef = self::convertRef($oldRef, "propal");
//        die($tabT[]);
        
        $requete = "UPDATE " . MAIN_DB_PREFIX . "propal set ref = '" . $newRef . "', import_key = " . $oldId . ", ref_client = '" . self::$oldRefCli . "' WHERE rowid = " . $newId;
        $db->query($requete);echo $requete;
        $requete = "UPDATE " . MAIN_DB_PREFIX . "propal set extraparams = " . $newId . ", fk_statut = 3 WHERE rowid = " . $oldId;
        $db->query($requete);
    }

    public function getPropalPrec() {
        return $this->propalPre;
    }

    public function getPropalSuiv() {
        return $this->propalSui;
    }

}