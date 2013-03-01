<?php

/*
 * $% signifie que l'element est un texte fixe est nom le nom d'une collone de la base
 */

class maj {

    private $maxLigne = 500;
    private $maxTime = 100;
    private $maxErreur = 5;
    private $erreur = 0;
    private $tabNonImport = array();

    function maj($dbS, $dbD) {
        $this->dbS = $dbS;
        $this->dbD = $dbD;
        $this->timeDeb = microtime(true);
    }
    
    public function req($req){
        $this->queryD($req);
    }

    public function rectifId($tabId) {
        $i = 0;
        while ($i + 1 < count($tabId)) {
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono_value SET chrono_refid=" . $tabId[$i + 1] . " WHERE id = " . $tabId[$i];
            $result = $this->queryD($requete);
            if (!$result) {
                $this->erreurL("Impossible de modifier l'id . Requete : " . $requete);
            }

            $i = $i + 2;
        }
        $this->infoL("Succes !!!!!!");
    }

    private function erreurL($text) {
        $this->erreur++;
        $text = "<br/><br/>" . $this->getTime() . " | Erreur : " . $text . "<br/>";
        if ($this->erreur > $this->maxErreur)
            die($text . "<br/><br/><br/>Max erreur !!!!!");
        else
            echo($text);
    }

    private function infoL($text) {
        echo "<br/>" . $this->getTime() . " | Info : " . $text . "<br/>";
    }

    public function startMAj($tab, $update = false) {
        if (!$update) {
            $this->netoyerTables($tab);

//            $requete = "ALTER TABLE " . MAIN_DB_PREFIX . "commande DROP FOREIGN KEY fk_commande_fk_projet ,
//                ADD FOREIGN KEY (fk_projet) REFERENCES " . MAIN_DB_PREFIX . "Synopsis_projet (rowid) 
//                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
//            $this->queryD($requete);
//            $requete = "ALTER TABLE " . MAIN_DB_PREFIX . "propal DROP FOREIGN KEY fk_propal_fk_projet ,
//                ADD FOREIGN KEY (fk_projet) REFERENCES " . MAIN_DB_PREFIX . "Synopsis_projet (rowid) 
//                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
//            $this->queryD($requete);
//            $requete = "ALTER TABLE " . MAIN_DB_PREFIX . "facture DROP FOREIGN KEY fk_facture_fk_projet ,
//                ADD FOREIGN KEY (fk_projet) REFERENCES " . MAIN_DB_PREFIX . "Synopsis_projet (rowid) 
//                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
//            $this->queryD($requete);
//            $requete = "ALTER TABLE " . MAIN_DB_PREFIX . "categorie DROP KEY uk_categorie_ref;";
//            $this->queryD($requete);
        }
//        $this->netoyeDet("propal");
//        $this->netoyeDet("commande");
//        $this->netoyeDet("propal");
//        $this->netoyeDet("usergroup", MAIN_DB_PREFIX."usergroup_user");
//        $this->netoyeDet("user", MAIN_DB_PREFIX."usergroup_user");
        $this->setTabNonImport("user", MAIN_DB_PREFIX."usergroup_user");
//        $this->netoyeDet("user", MAIN_DB_PREFIX."user_rights");
//        $this->netoyeDet("product", "babel_categorie_product", "babel_");

//        foreach ($tab as $ligne) {
//            $this->traiteSql($ligne[2], $ligne[3], $ligne[0], $ligne[1], $update);
//        }

        if($this->erreur == 0)
            $this->infoL("Succes !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
        else
            $this->infoL("Finit !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! ".$this->erreur." errerus");
    }

//    private function netoyeDet($table, $table2 = null, $prefTab = null) {
//        if ($prefTab)
//            $nomTable = $prefTab . $table;
//        else
//            $nomTable = MAIN_DB_PREFIX . $table;
//        if ($table2)
//            $nomTable2 = $table2;
//        else
//            $nomTable2 = $nomTable . "det";
//        $requete = "DELETE FROM " . $nomTable2 . " WHERE fk_" . $table . " NOT IN (SELECT DISTINCT(rowid) FROM " . $nomTable . " WHERE 1);";
//        $this->queryS($requete);
////        $requete = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal NOT IN (SELECT DISTINCT(rowid) FROM ".MAIN_DB_PREFIX."propal WHERE 1);";
////        $this->queryS($requete);
//    }
    
    private function setTabNonImport($table, $table2 = null, $prefTab = null){
        global $db;
        if ($prefTab)
            $nomTable = $prefTab . $table;
        else
            $nomTable = MAIN_DB_PREFIX . $table;
        if ($table2)
            $nomTable2 = $table2;
        else
            $nomTable2 = $nomTable . "det";
        $requete = "SELECT * FROM " . $nomTable2 . " WHERE fk_" . $table . " NOT IN (SELECT DISTINCT(rowid) FROM " . $nomTable . " WHERE 1);";
        $result = $this->queryS($requete);
        while($ligne = $this->dbS->fetch_object($result))
            $this->tabNonImport[$nomTable2][$ligne->rowid]    = true;    
    }

    public function ajoutDroitGr($tabGr, $tabDroit) {
        $tabVal = array();
        foreach ($tabGr as $gr) {
            foreach ($tabDroit as $droit)
                $tabVal[] = "(" . $gr . "," . $droit . ")";
        }
        $requete = "INSERT into " . MAIN_DB_PREFIX . "usergroup_rights (fk_usergroup, fk_id) VALUES " . implode(",", $tabVal) . ";";
        $result = $this->queryD($requete);
        if ($result)
            $this->infoL("Droit ajouté.");
        else
            $this->erreurL("Erreur ajout de droit");
    }

    private function queryD($query) {
        $query = str_replace("llx_", MAIN_DB_PREFIX, $query);
        $result = $this->dbD->query($query);
        if(!$result)
            $this->erreurL("Erreur SQL D : ".$query);
        return $result;
    }

    private function queryS($query) {
        $result = $this->dbS->query($query);
        if(!$result)
           $this->erreurL("Erreur SQL S : ".$query);
        return $result;
    }

    private function traiteSql($srcCol, $destCol, $tableSrc, $tableDest, $update) {
        $srcCol2 = array();
        foreach ($srcCol as $ligne) {//Supression des champ fixe
            if (stripos($ligne, "$%") === false)
                $srcCol2[] = $ligne;
        }
        $requete = "SELECT " . (isset($srcCol2[0]) ? implode(", ", $srcCol2) : "*") . " FROM " . $tableSrc;
        $data = $this->queryS($requete);
        $i = 0;
        $tabIns = array();
        while ($ligne = $this->dbS->fetch_array($data)) {
            $tabClone = array();
            if (!isset($destCol[0]))
                foreach ($ligne as $cle => $val)
                    if (!is_int($cle))
                        $destCol[$cle] = $cle;
            if (!isset($srcCol[0]))
                $srcCol = $destCol;

            $importOff = false;
            $tabVal = array();
            foreach ($srcCol as $id => $cle) {
                if (stripos($cle, "$%") === false)
                    $val = $ligne[$cle];
                else
                    $val = str_replace("$%", "", $cle);

                //Exception
                $newCle = $destCol[$id];
                if ($cle == "rowid" && $tableDest == MAIN_DB_PREFIX . "user" && $val == "1")//On laisse l'admin de la nouvelle version
                    $importOff = true;
                if ($cle == "rowid" && isset($this->tabNonImport[$tableSrc][$val]))//On ignore les ligne du tableau tabNonImport
                    $importOff = true;
                if ($cle == "fk_user" && $tableDest == MAIN_DB_PREFIX . "user_rights" && $val == "1")//On laisse l'admin de la nouvelle version
                    $importOff = true;
                if (($newCle == "fk_source" || $newCle == "fk_target") &&
                        $tableDest == MAIN_DB_PREFIX . "element_element" && !($val > 0))//La ligne ne sert a rien
                    $importOff = true;
                if (($newCle == "fk_product_type" && preg_match('/' . str_replace('/', '\/', '^YOFD') . '/', $ligne['ref'])) &&
                        $tableDest == MAIN_DB_PREFIX . "product")//Deplacement = service
                    $val = 4;


                if ($cle == "fk_statut" && $tableDest == MAIN_DB_PREFIX . "propal" && $val == "99")//On vire les statue 99 sur les propal
                    $val = "3";
                if ((($cle == "fk_projet")
                        || ($cle == "fk_user_author")
                        || ($cle == "fk_user_valid")
                        ) && $val == "0")//On remplace 0 par null
                    $val = NULL;
                if (((0)//pour bimp user 20 n'existe plus
                        || ($cle == "fk_user_author")
                        || ($cle == "fk_user_valid")
                        ) && $val == "20")//On remplace 0 par null
                    $val = NULL;
                if ($cle == "description" && $tableDest == MAIN_DB_PREFIX . "propaldet")//Merde dans la description surement en rapport avec commandegroupe
                    $val = str_replace(array("[header]", "[desc]"), array("", ""), $val);
                if ($cle == "fk_id" && ($tableDest == MAIN_DB_PREFIX . "user_rights" || $tableDest == MAIN_DB_PREFIX . "usergroup_rights") && (!isset($_REQUEST['type']) || $_REQUEST['type'] == 1)) {//Nouveau num des droit
                    if ($val > 59 && $val < 70){
                        $tabClone[] = array(count($tabVal) => ($val - 60 + 87449), 0 => "null");
                    }
                    if ($val == 22234113){//On utilise e doit pour les droit inexistant admin
                        $tabClone[] = array(count($tabVal) => (87457), 0 => "null");
                        $tabClone[] = array(count($tabVal) => (80880), 0 => "null");  
                    }
                    if ($val > 29 && $val < 40){
                        $tabClone[] = array(count($tabVal) => "5".$val, 0 => "null");
                    }
                    if (stripos($val, "222341") !== false) 
                        $val = str_replace(222341, 2227, $val);
                }
                //Fin exception

                if (is_null($val))
                    $tabVal[] = "NULL";
                else
                    $tabVal[] = "'" . addslashes($val) . "'";
            }

            if (!$importOff) {
                $i++;


                //Exception
                if ($tableDest == MAIN_DB_PREFIX . "propal") {
                    $requete = "SELECT p2.rowid as pre, p3.rowid as sui 
                                FROM " . MAIN_DB_PREFIX . "propal p1 
                                LEFT JOIN " . MAIN_DB_PREFIX . "propal p2 on p1.revision = (p2.revision+1) AND p1.orig_ref = p2.orig_ref
                                LEFT JOIN " . MAIN_DB_PREFIX . "propal p3 on p1.revision = (p3.revision-1) AND p1.orig_ref = p3.orig_ref 
                                WHERE p1.rowid = " . $tabVal[0];
                    $result = $this->queryS($requete);
                    $ligne = $this->dbS->fetch_object($result);
                    $destCol[1000] = "import_key";
                    $tabVal[1000] = ($ligne->pre ? $ligne->pre : 'NULL');
                    $destCol[1001] = "extraparams";
                    $tabVal[1001] = ($ligne->sui ? $ligne->sui : 'NULL');
                }
                $tabIns[] = $tabVal;
                
                foreach($tabClone as $clone){
                    $newTab = $tabVal;
                    foreach($clone as $cle => $newVal)
                        $newTab[$cle] = $newVal;
                    $tabIns[] = $newTab;
                }

                if (isset($tabIns[$this->maxLigne])) { //Si plus grnad que valeur on envoie et vide le tableau
                    $this->envoyerDonnee($tableDest, $destCol, $tabIns, $update);
                    $tabIns = array();
                }
            }
        }
        if (isset($tabIns[0]))
            $this->envoyerDonnee($tableDest, $destCol, $tabIns, $update);
        $this->infoL($i . " lignes importées de la table " . $tableSrc . " vers la table " . $tableDest);
    }

    private function netoyerTables($tab) {
        for ($i = count($tab); $i > 0; $i--) {
            $ligne = $tab[$i - 1];
            $where = "1";
//            if ($ligne[1] == MAIN_DB_PREFIX."element_element")
//                $where = "0";
            if ($ligne[1] == MAIN_DB_PREFIX . "user")
                $where = "rowid != 1";
            if ($ligne[1] == MAIN_DB_PREFIX . "user_rights")
                $where = "fk_user != 1";
            if ($ligne[1] == MAIN_DB_PREFIX . "facture") {
//                $this->queryD("DELETE FROM " . $ligne[1] . " WHERE fk_facture_source IN (SELECT rowid FROM " . $ligne[1] . " WHERE fk_facture_source IS NOT NULL)"); //Suppression des facture de 2eme niveau
                $this->queryD("DELETE FROM " . $ligne[1] . " WHERE fk_facture_source IS NOT NULL"); //Suppression des facture de 1er niveau
            }
            $requete = "DELETE FROM " . $ligne[1] . " WHERE " . $where;
            $result = $this->queryD($requete);
            if (!$result)
                $this->erreurL("Requete SQL : <br/>" . $this->dbD->lasterror . "<br/><br/>" . $requete);
            else
                $this->infoL("Donnees de la table " . $ligne[1] . " supprimees.");
        }
    }

    private function envoyerDonnee($tableDest, $destCol, $tabIns, $update, $rechercheErreur = false) {
        if ($this->getTime() > $this->maxTime)
            die("Temps max attein !!");
        if ($update) {
            foreach ($destCol as $id => $col) {
                if ($col == "rowid" || $col == "id") {
                    $nomId = $col;
                    $idColId = $id;
                }
            }
            foreach ($tabIns as $ligne) {
                $tabSet = array();
                foreach ($ligne as $id => $val) {
                    if ($id != $idColId)
                        $tabSet[] = $destCol[$id] . "=" . $val;
                }
                $requete = "UPDATE " . $tableDest . " SET " . implode(",", $tabSet) . " WHERE " . $nomId . "=" . $ligne[$idColId];
                $result = $this->queryD($requete);
                if (!$result) {
                    $this->erreurL("Requete SQL : <br/>" . $this->dbD->lasterror . "<br/><br/>" . $requete);
                }
            }
        } else {
            $insert = array();
            foreach ($tabIns as $ligne) {
                $insert[] = "(" . implode(",", $ligne) . ")";
            }
            $requete = "INSERT into " . $tableDest . " (" . implode(", ", $destCol) . ") VALUES " . implode(",", $insert) . ";";
            $result = $this->queryD($requete);
            if (!$result) {
                if (!$rechercheErreur)//On re essaye ligne par ligne pour voir le probléme
                    foreach ($tabIns as $ligne)
                        $this->envoyerDonnee($tableDest, $destCol, array($ligne), $update, true);
                else
                    $this->erreurL("Requete SQL : <br/>" . $this->dbD->lasterror . "<br/><br/>" . $requete);
            }
        }
    }

    private function getTime() {
        return number_format((microtime(true) - $this->timeDeb), 2, ',', ' ') . " sec.";
    }

}

?>
