<?php

/*
 * $% signifie que l'element est un texte fixe est nom le nom d'une collone de la base
 */

class maj {

    private $maxLigne = 500;
    private $maxTime = 100;
    private $maxErreur = 5;
    private $erreur = 0;

    function maj($dbS, $dbD) {
        $this->dbS = $dbS;
        $this->dbD = $dbD;
    }
    
    public function rectifId($tabId){
        $i = 0;
        while($i+1<count($tabId)){
            $requete = "UPDATE llx_Synopsis_Chrono_value SET chrono_refid=".$tabId[$i+1]." WHERE id = ".$tabId[$i];
            $result = $this->queryD($requete);
            if(!$result){
                $this->erreurL("Impossible de modifier l'id . Requete : ".$requete);
            }
            
            $i = $i+2;
        }
        $this->infoL("Succes !!!!!!");
    }
    
    private function erreurL($text){
        $this->erreur++;
        $text = "<br/>".$this->getTime()." s | Erreur : ".$text."<br/>";
        if($this->erreur > $this->maxErreur)
            die($text . "<br/><br/><br/>Max erreur !!!!!");
        else
            echo($text);
    }
    
    private function infoL($text){
        echo "<br/>".$this->getTime()." s | Info : ".$text."<br/>";
    }

    public function startMAj($tab) {
        $this->timeDeb = microtime(true);
        $this->netoyerTables($tab);

        $requete = "ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_projet ,
                ADD FOREIGN KEY (fk_projet) REFERENCES llx_Synopsis_projet (rowid) 
                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
        $this->queryD($requete);
        $requete = "ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_projet ,
                ADD FOREIGN KEY (fk_projet) REFERENCES llx_Synopsis_projet (rowid) 
                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
        $this->queryD($requete);
        $requete = "ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_projet ,
                ADD FOREIGN KEY (fk_projet) REFERENCES llx_Synopsis_projet (rowid) 
                ON DELETE RESTRICT ON UPDATE RESTRICT ;";
        $this->queryD($requete);
        $requete = "ALTER TABLE llx_categorie DROP KEY uk_categorie_ref;";
        $this->queryD($requete);
        
//        $this->netoyeDet("propal");
//        $this->netoyeDet("commande");
//        $this->netoyeDet("propal");
//        $this->netoyeDet("usergroup", "llx_usergroup_user");
//        $this->netoyeDet("user", "llx_usergroup_user");
//        $this->netoyeDet("user", "llx_user_rights");
//        $this->netoyeDet("product", "babel_categorie_product", "babel_");

        foreach ($tab as $ligne) {
            $this->traiteSql($ligne[2], $ligne[3], $ligne[0], $ligne[1]);
        }

        $this->infoL("Succes !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
    }

    private function netoyeDet($table, $table2 = null, $prefTab = null) {
        if ($prefTab)
            $nomTable = $prefTab . $table;
        else
            $nomTable = "llx_" . $table;
        if ($table2)
            $nomTable2 = $table2;
        else
            $nomTable2 = $nomTable . "det";
        $requete = "DELETE FROM " . $nomTable2 . " WHERE fk_" . $table . " NOT IN (SELECT DISTINCT(rowid) FROM " . $nomTable . " WHERE 1);";
        $this->queryS($requete);
//        $requete = "DELETE FROM llx_propaldet WHERE fk_propal NOT IN (SELECT DISTINCT(rowid) FROM llx_propal WHERE 1);";
//        $this->queryS($requete);
    }

    public function ajoutDroitGr($tabGr, $tabDroit) {
        $tabVal = array();
        foreach ($tabGr as $gr) {
            foreach ($tabDroit as $droit)
                $tabVal[] = "(" . $gr . "," . $droit . ")";
        }
        $requete = "INSERT into llx_usergroup_rights (fk_usergroup, fk_id) VALUES " . implode(",", $tabVal) . ";";
        $result = $this->queryD($requete);
        if ($result)
            $this->infoL("Droit ajouté.");
        else
            $this->erreurL("Erreur ajout de droit");
    }

    private function queryD($query) {
        $query = str_replace("llx_", MAIN_DB_PREFIX, $query);
        return $this->dbD->query($query);
    }

    private function queryS($query) {
        return $this->dbS->query($query);
    }

    private function traiteSql($srcCol, $destCol, $tableSrc, $tableDest) {
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
                if ($cle == "rowid" && $tableDest == "llx_user" && $val == "1")//On laisse l'admin de la nouvelle version
                    $importOff = true;
                if ($cle == "fk_user" && $tableDest == "llx_user_rights" && $val == "1")//On laisse l'admin de la nouvelle version
                    $importOff = true;
                if (($newCle == "fk_source" || $newCle == "fk_target") &&
                        $tableDest == "llx_element_element" && !($val > 0))//La ligne ne sert a rien
                    $importOff = true;
                
                    
                if ($cle == "fk_statut" && $tableDest == "llx_propal" && $val == "99")//On vire les statue 99 sur les propal
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
                if ($cle == "description" && $tableDest == "llx_propaldet")//On laisse l'admin de la nouvelle version
                    $val = str_replace(array("[header]", "[desc]"), array("", ""), $val);
                //Fin exception

                if (is_null($val))
                    $tabVal[] = "NULL";
                else
                    $tabVal[] = "'" . addslashes($val) . "'";
            }

            if (!$importOff) {
                $i++;


                //Exception
                if ($tableDest == "llx_propal") {
                    $requete = "SELECT p2.rowid as pre, p3.rowid as sui 
                                FROM llx_propal p1 
                                LEFT JOIN llx_propal p2 on p1.revision = (p2.revision+1) AND p1.orig_ref = p2.orig_ref
                                LEFT JOIN llx_propal p3 on p1.revision = (p3.revision-1) AND p1.orig_ref = p3.orig_ref 
                                WHERE p1.rowid = " . $tabVal[0];
                    $result = $this->queryS($requete);
                    $ligne = $this->dbS->fetch_object($result);
                    $destCol[1000] = "import_key";
                    $tabVal[1000] = ($ligne->pre ? $ligne->pre : 'NULL');
                    $destCol[1001] = "extraparams";
                    $tabVal[1001] = ($ligne->sui ? $ligne->sui : 'NULL');
                }
                $tabIns[] = "(" . implode(",", $tabVal) . ")";

                if (isset($tabIns[$this->maxLigne])) { //Si plus grnad que valeur on envoie et vide le tableau
                    $this->envoyerDonnee($tableDest, $destCol, $tabIns);
                    $tabIns = array();
                }
            }
        }
        if (isset($tabIns[0]))
            $this->envoyerDonnee($tableDest, $destCol, $tabIns);
        $this->infoL($i . " lignes importées de la table " . $tableSrc . " vers la table " . $tableDest);
    }

    private function netoyerTables($tab) {
        for ($i = count($tab); $i > 0; $i--) {
            $ligne = $tab[$i - 1];
            $where = "1";
//            if ($ligne[1] == "llx_element_element")
//                $where = "0";
            if ($ligne[1] == "llx_user")
                $where = "rowid != 1";
            if ($ligne[1] == "llx_user_rights")
                $where = "fk_user != 1";
            if ($ligne[1] == "llx_facture") {
                $this->queryD("DELETE FROM " . $ligne[1] . " WHERE fk_facture_source IN (SELECT rowid FROM " . $ligne[1] . " WHERE kf_facture_source IS NOT NULL)"); //Suppression des facture de 2eme niveau
                $this->queryD("DELETE FROM " . $ligne[1] . " WHERE fk_facture_source IS NOT NULL"); //Suppression des facture de 1er niveau
            }
            $requete = "DELETE FROM " . $ligne[1] . " WHERE " . $where;
            $result = $this->queryD($requete);
            if (!$result)
                $this->erreur("Requete SQL : <br/>" . $this->dbD->lasterror . "<br/><br/>" . $requete);
            else
                $this->infoL ("Donnees de la table " . $ligne[1] . " supprimees.");
        }
    }

    private function envoyerDonnee($tableDest, $destCol, $tabIns, $rechercheErreur = false) {
        if ($this->getTime() > $this->maxTime)
            die("Temps max attein !!");
        $requete = "INSERT into " . $tableDest . " (" . implode(", ", $destCol) . ") VALUES " . implode(",", $tabIns) . ";";
        $result = $this->queryD($requete);
        if (!$result) {
            if (!$rechercheErreur)//On re essaye ligne par ligne pour voir le probléme
                foreach ($tabIns as $ligne)
                    $this->envoyerDonnee($tableDest, $destCol, array($ligne), true);
            else
                $this->erreurl("Requete SQL : <br/>" . $this->dbD->lasterror . "<br/><br/>" . $requete);
        }
    }

    private function getTime() {
        return ((microtime(true) - $this->timeDeb)) . "sec.";
    }

}

?>
