<?php

require_once (DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");

require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/export8sens.class.php");

class exportfacture extends export8sens {

//    var $sep = "    -   ";
//    var $saut = "<br/>";   
    public $info = array();
    public $type = "";
    public $id8sens = 0;
    public $nbE = 0;
    public $debug = false;
    public $output = "Rien";
    public $error = "";
    public $tabIgnore = array();
    private $where = " AND fact.fk_statut > 0 AND close_code is null AND (fact.extraparams < 1 || fact.extraparams is NULL) AND fact.total != 0  AND facnumber NOT LIKE '%PROV%' GROUP BY fact.rowid";

    public function __construct($db, $sortie = 'html') {
        parent::__construct($db);
        $this->pathExport = $this->path."fact/";
        $this->pathI = $this->path."../export/factures/";
        $tabFiles = scandir($this->pathExport);
        $nbFiles = $nbFilesErr = 0;
        foreach($tabFiles as $file)
            if(stripos($file, ".txt"))
                    $nbFiles++;
        foreach($tabFiles as $file)
            if(stripos($file, ".ER8"))
                    $nbFilesErr++;
        if($nbFiles > 5){
//            mailSyn2("Synchro 8Sens OFF", "dev@bimp.fr, gsx@bimp.fr", null, "Dossier : ".$this->pathExport." <br/><br/>Nb files : ".$nbFiles);
            $this->addTaskAlert("facture import OFF");
        }
        if($nbFilesErr > 0){
//            mailSyn2("Synchro 8Sens FICHIER ERREURS", "tommy@bimp.fr", null, "Dossier : ".$this->pathExport." <br/><br/>Nb files : ".$nbFilesErr);
            $this->addTaskAlert("facture import erreurs");
        }
    }

    public function exportTout() {
        if(defined("MODE_TEST")){
            require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importFacture.class.php");
            $importFact = new importFacture($this->db);
            $importFact->importFact();
            require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/exportpaiement.class.php");
            $exp = new exportpaiement($this->db);
            $exp->exportTout();
        }


        $this->exportFactureNewSav();
//        $this->exportFactureSav();
        $this->exportFactureSavSeul();
        $this->exportFactureReseau();
        $this->exportFactureNormal();
        $this->getFactDontExport();
        if ($this->error == "") {
            $this->output = trim($this->nbE . " facture(s) exportée(s)");
            return 0;
        } else {
            $this->output = trim($this->error);
            return 1;
        }
    }

    private function getId8sensByCentreSav($centre) {
        require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/centre.inc.php");
        global $tabCentre;
        if (isset($tabCentre[$centre][3]) && $tabCentre[$centre][3] > 0)
            return $tabCentre[$centre][3];
        if(!defined("MODE_TEST"))
            mailSyn2("Impossible de trouvé un id8sens", "dev@bimp.fr, jc.cannet@bimp.fr", null, "Bonjour impossible de trouver d'id 8sens Centre : " . $centre);
        return 0;
    }

    private function getId8sensByCentreNewSav($centre) {
        require_once(DOL_DOCUMENT_ROOT . "/bimpsupport/centre.inc.php");
        global $tabCentre;
        if (isset($tabCentre[$centre][3]) && $tabCentre[$centre][3] > 0)
            return $tabCentre[$centre][3];
        if(!defined("MODE_TEST"))
            mailSyn2("Impossible de trouvé un id8sens", "dev@bimp.fr, jc.cannet@bimp.fr", null, "Bonjour impossible de trouver d'id 8sens Centre : " . $centre);
        return 0;
    }

//    public function exportFactureSav() {
//        $this->type = "sav";
//        $result = $this->db->query("SELECT fact.rowid as id, idtech8sens as id8Sens, chronoT.Centre "
//                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe, " . MAIN_DB_PREFIX . "element_element el , " . MAIN_DB_PREFIX . "propal prop, " . MAIN_DB_PREFIX . "synopsischrono chrono , " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 chronoT , " . MAIN_DB_PREFIX . "user_extrafields ue "
//                . "WHERE fe.fk_object = fact.rowid AND fe.`type` = 'S' AND el.targettype = 'facture' AND el.sourcetype = 'propal' AND fk_target = fact.rowid AND prop.rowid = el.fk_source " . /* AND prop.fk_statut != 3 je ne sais pas trop pourquoi */" AND prop.rowid = chrono.propalid AND chronoT.id = chrono.id AND ue.`fk_object` = IF(chronoT.Technicien > 0, chronoT.Technicien, fact.fk_user_author) "
//                . $this->where);
//
//        while ($ligne = $this->db->fetch_object($result)) {
//            $this->id8sens = $ligne->id8Sens;
//            if ($ligne->id8Sens < 1 && isset($ligne->Centre) && $ligne->Centre != "") {
//                $this->id8sens = $this->getId8sensByCentreSav($ligne->Centre);
//            }
//            $this->tabIgnore[] = $ligne->id;
//            $this->extract($ligne->id);
//        }
//    }

    public function exportFactureNewSav() {
        $this->type = "sav";
        $result = $this->db->query("SELECT fact.rowid as id, idtech8sens as id8Sens, sav.code_centre as Centre 
FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe, " . MAIN_DB_PREFIX . "element_element el , " . MAIN_DB_PREFIX . "propal prop, " . MAIN_DB_PREFIX . "bs_sav sav , " . MAIN_DB_PREFIX . "user_extrafields ue 
WHERE fe.fk_object = fact.rowid AND fe.`type` = 'S' AND el.targettype = 'facture' AND el.sourcetype = 'propal' AND fk_target = fact.rowid AND prop.rowid = el.fk_source AND prop.rowid = sav.id_propal AND  ue.`fk_object` = IF(id_user_tech > 0, id_user_tech, fact.fk_user_author) "
                . $this->where);

        while ($ligne = $this->db->fetch_object($result)) {
            $this->id8sens = $ligne->id8Sens;
            if ($ligne->id8Sens < 1 && isset($ligne->Centre) && $ligne->Centre != "") {
                $this->id8sens = $this->getId8sensByCentreNewSav($ligne->Centre);
            }
            $this->tabIgnore[] = $ligne->id;
            $this->extract($ligne->id);
        }
    }

    public function exportFactureReseau() {
        $this->type = "R";
        $result = $this->db->query("SELECT fact.rowid as id "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe "
                . "WHERE fe.fk_object = fact.rowid AND fe.`type` = 'R' " . $this->where);
        while ($ligne = $this->db->fetch_object($result)) {
            $this->id8sens = 239;
            $this->extract($ligne->id);
        }
    }

    public function exportFactureSavSeul() {
        $this->type = "sav";
        $result = $this->db->query("SELECT fact.rowid as id, fe.centre "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe "
                . "WHERE fe.fk_object = fact.rowid AND fe.`type` = 'S' " . $this->where);
        while ($ligne = $this->db->fetch_object($result)) {
            if (!in_array($ligne->id, $this->tabIgnore)) {
                $this->id8sens = $this->getId8sensByCentreSav($ligne->centre);
                $this->extract($ligne->id);
            }
        }
    }

    public function exportFactureNormal() {
        $this->type = "sav";
        $result = $this->db->query("SELECT fact.rowid as id, fk_user_author, fe.centre "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe "
                . "WHERE fe.fk_object = fact.rowid AND fe.`type` NOT IN ('S') " . $this->where);
        while ($ligne = $this->db->fetch_object($result)) {
            if (!in_array($ligne->id, $this->tabIgnore)) {
                $this->getId8sensByFact($ligne->id, $ligne->fk_user_author);
                $this->extract($ligne->id);
            }
        }
    }

    public function getId8sensByFact($id, $userCr) {
        $this->id8sens = 0;
        $sql = $this->db->query("SELECT * FROM `llx_element_contact` WHERE `element_id` = " . $id . " AND `fk_c_type_contact` = 50 ORDER BY `rowid` DESC");
        if ($this->db->num_rows($sql) > 0) {
            $ligne = $this->db->fetch_object($sql);
            $userC = new User($this->db);
            $userC->fetch($ligne->fk_socpeople);
            $this->id8sens = $userC->array_options['options_id8sens'];
            if ($this->id8sens < 1) {
                if ($this->debug)
                    echo "<br/>Comm pas de comm<br/>";
                if(!defined("MODE_TEST"))
                    mailSyn2("Exportation facture", $userC->email, null, "Bonjour vos factures ne peuvent être exporté car vous n'avez pas d'identifiant 8Sens dans vottre profil <a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php?id=" . $userC->id . "'>Voir</a>");
            }
        }
        else {
            if ($userCr < 1)
                $userCr = 1;
            $userM = new User($this->db);
            $userM->fetch($userCr);
            if ($this->debug)
                echo "<br/>Pas de comm<br/>";
            if(!defined("MODE_TEST"))
                mailSyn2("Exportation facture", $userM->email, null, "Bonjour vos factures ne peuvent être exportées car il n'y a pas de commercial rataché <a href='" . DOL_URL_ROOT . "/compta/facture/card.php?facid=" . $id . "'>Voir</a>");
        }
    }

    public function getFactDontExport() {
        $this->pathExport = DOL_DATA_ROOT . "/test/";
        $result = $this->db->query("SELECT fact.rowid as id, facnumber "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact "
                . "WHERE 1 " . $this->where);
        $facts = "";
        while ($ligne = $this->db->fetch_object($result)) {
            $facts .= $ligne->facnumber . " - ";
        }
        if ($facts != "")
            if(!defined("MODE_TEST"))
                mailSyn2("Facture non export", "dev@bimp.fr, jc.cannet@bimp.fr", null, "Bonjour voici les facture non exporté " . $facts);
    }
    
    function extract($id) {
        if ($this->type == "") {
            $this->error("Pas de type pour export " . $id);
        } elseif ($this->id8sens == 0) {
            $this->error("Pas d'id collaborateur 8sens " . $id);
        } else {
            $this->exportOk = true;



            $facture = new Facture($this->db);
            $facture->fetch($id);
            $societe = new Societe($this->db);
            $societe->fetch($facture->socid);


            if ($this->debug)
                echo "Tentative export facture " . $facture->getNomUrl(1);


            $tabFact = $tabFactDet = array();
            $tabFact[] = array("E" => "E", "code_client" => $societe->code_client, "nom" => $societe->name, "phone" => $societe->phone, "address" => $societe->address, "zip" => $societe->zip, "town" => $societe->town, "facnumber" => $facture->ref, "date" => dol_print_date($facture->date, "%d-%m-%Y"), "email" => $societe->email, "total" => price($facture->total_ht), "total_ttc" => price($facture->total_ttc), "id8Sens" => $this->id8sens);
            $facture->fetch_lines();
            foreach ($facture->lines as $line) {
                $type = $this->getRef($line);
                $ref = "";
                if ($line->fk_product > 0) {
                    $prod = new Product($this->db);
                    $prod->fetch($line->fk_product);
                    $ref = $prod->ref;
                }

                if ($line->pa_ht < 0)
                    $line->pa_ht = -$line->pa_ht;
                if ($line->subprice < 0)
                    $line->pa_ht = -$line->pa_ht;

                $tabCodeTva = array(
                    "20" => 1,
                    "5.500" => 7,
                    "0" => 0
                );
                $tvaCode = $tabCodeTva[$line->tva_tx];
                $line->desc = $this->traiteStr($line->desc);
                $tabFactDet[] = array("L" => "L", "ref" => $ref, "product_type" => $type, "qty" => $line->qty, "subprice" => price($line->subprice), "description" => $line->desc, "buy_price_ht" => price($line->pa_ht), "tva_code" => $tvaCode, "remise_percent" => $line->remise_percent, "tva_tx" => $tvaCode);
            }




            if ($this->exportOk) {
                //header("Content-Disposition: attachment; filename=\"test.txt\"");
                $text = $this->getTxt($tabFact, $tabFactDet);
                if (file_put_contents($this->pathExport . $facture->ref . ".txt", $text)) {
                    if ($this->debug)
                        echo "<br/>Facture " . $facture->getNomUrl(1) . " exporté<br/>";
                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET extraparams = 1 WHERE rowid = " . $facture->id);
                    $this->nbE++;
                    return 1;
                } else
                    $this->error("Impossible d'exporté le fichier " . $this->pathExport . $facture->ref . ".txt");
            }
            return 0;
        }
    }

    function getRef($line) {
        $tabCatProd = array(1202 => "GEN-ABO", 1203 => "GEN-CERTIF", 1204 => "GEN-HEBERG", 1206 => "GEN-HEBERG", 1205 => "GEN-LOC",
            1100 => "GEN-TELECOM", 1176 => "GEN-CONSO", 1216 => "GEN-SAV-PIECES", 1079 => "GEN-MAT", 1072 => "GEN-LOG", 1227 => "GEN-MO-EXT", 1156 => "GEN-MO-INT", 1225 => "GEN-MAINT-INT", 1140 => "GEN-MAINT-EXT", 1214 => "GEN-MAT-OCCAS", 1215 => "GEN-ACCES", 1207 => "GEN-PORT", 1217 => "GEN-DEP-INT", 1228 => "GEN-DEP-EXT", 1135 => "GEN-TEXTIL", 1233 => "GEN-ZZ");
        $valeur = "";

        if ($line->total_ht == 0 && $line->pa_ht == 0)
            return "GEN-DIV-INFO";


        if ($this->type == "sav") {
            if ($line->tva_tx == 0)
                $valeur = "GEN-SAV-HTVA";
            elseif ($line->desc == "Acompte")
                $valeur = "GEN-SAV-ACOMPTE";
            elseif ($line->fk_product_type == 1)
                $valeur = "GEN-SAV-MO";
            elseif ($line->fk_product_type == 0)
                $valeur = "GEN-SAV-PIECES";
        }
        else {
            $idP = $line->fk_product;
            if ($idP > 0) {
                $catId = 0;
                $sql = $this->db->query("SELECT fk_categorie FROM `" . MAIN_DB_PREFIX . "categorie_product` WHERE fk_categorie IN (SELECT rowid FROM `" . MAIN_DB_PREFIX . "categorie` WHERE `fk_parent` = 932 ORDER BY `type` DESC) AND `fk_product` = " . $idP);
                if ($this->db->num_rows($sql) > 0)
                    while ($ligne = $this->db->fetch_object($sql))
                        $catId = $ligne->fk_categorie;
                if ($catId > 0) {
                    if (isset($tabCatProd[$catId]))
                        $valeur = $tabCatProd[$catId];
                    else
                        $this->error("Pas de Code 8sens pour la catégorie " . $catId, 0, $catId);
                } else
                    $this->error("Pas de categorie pour le prod " . $idP, $idP);
            }
            else {
                if ($line->desc == "Acompte")
                    $valeur = "GEN-RES-ACOMPTE";
                elseif ($line->fk_product_type == 1)
                    $valeur = "GEN-DIV-SERV";
                elseif ($line->fk_product_type == 0)
                    $valeur = "GEN-DIV-MAT";
            }
        }

        if ($valeur == "")
            $this->exportOk = false;

        return $valeur;
    }

    function error($msg, $idProd = 0, $idCat = 0) {
        $this->error = $msg;
        dol_syslog($msg, 3, 0, "_extract");
        $to = "";

        if ($idProd > 0) {
            $prod = new Product($this->db);
            $prod->fetch($idProd);
            $msg .= "<br/>" . $prod->getNomUrl(1);
            $to = "a.delauzun@bimp.fr, tommy@bimp.fr";
        }
        if ($idCat > 0) {
            $cat = new Categorie($this->db);
            $cat->fetch($idCat);
            $msg .= $cat->getNomUrl(1);
            $to = "tommy@bimp.fr";
        }
        if ($to != "")
            if(!defined("MODE_TEST"))
                mailSyn2("Produit non catégorisé", $to, null, "Bonjour ceci est un message automatique des export vers 8sens <br/>" . $msg);
        if ($this->debug)
            echo "<span class='red'>" . $msg . "</span><br/>";
    }

}
