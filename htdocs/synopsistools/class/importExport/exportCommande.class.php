<?php

require_once (DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");

require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/export8sens.class.php");

class exportCommande extends export8sens {

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
    private $where = " AND comm.fk_statut > 0  AND (comm.extraparams < 1 || comm.extraparams is NULL)  AND ref NOT LIKE '%PROV%' GROUP BY comm.rowid";

    public function __construct($db, $sortie = 'html') {
        parent::__construct($db);
        $this->pathExport = $this->path."commande/";
        $tabFiles = scandir($this->pathExport);
        $nbFiles = $nbFilesErr = 0;
        foreach($tabFiles as $file)
            if(stripos($file, ".txt"))
                    $nbFiles++;
        foreach($tabFiles as $file)
            if(stripos($file, ".ER8"))
                    $nbFilesErr++;
        if($nbFiles > 5){
            $this->addTaskAlert("commande import OFF");
//            mailSyn2("Synchro 8Sens OFF", "dev@bimp.fr, gsx@bimp.fr", null, "Dossier : ".$this->pathExport." <br/><br/>Nb files : ".$nbFiles);
        }
        if($nbFilesErr > 0){
//            mailSyn2("Synchro 8Sens FICHIER ERREURS", "tommy@bimp.fr", null, "Dossier : ".$this->pathExport." <br/><br/>Nb files : ".$nbFilesErr);
            $this->addTaskAlert("commande import erreur");
        }
    }

    public function exportTout() {
        $this->exportCommandeNormal();
        $this->getCommandeDontExport();
        if ($this->error == "") {
            $this->output = trim($this->nbE . " commande(s) exportée(s)");
            return 0;
        } else {
            $this->output = trim($this->error);
            return 1;
        }
    }



    public function exportCommandeNormal() {
        $this->type = "sav";
        $result = $this->db->query("SELECT comm.rowid as id, fk_user_author "
                . "FROM `" . MAIN_DB_PREFIX . "commande` comm, `" . MAIN_DB_PREFIX . "commande_extrafields` fe "
                . "WHERE fe.fk_object = comm.rowid " . $this->where);
        while ($ligne = $this->db->fetch_object($result)) {
            if (!in_array($ligne->id, $this->tabIgnore)) {
                $this->getId8sensByCommande($ligne->id, $ligne->fk_user_author);
                $this->extract($ligne->id);
            }
        }
    }

    public function getId8sensByCommande($id, $userCr) {
        $this->id8sens = 0;
        if ($userCr < 1)
            $userCr = 1;
        $userM = new User($this->db);
        $userM->fetch($userCr);
        $sql = $this->db->query("SELECT * FROM `llx_element_contact` WHERE `element_id` = " . $id . " AND `fk_c_type_contact` = 91 ORDER BY `rowid` DESC");
        if ($this->db->num_rows($sql) > 0) {
            $ligne = $this->db->fetch_object($sql);
            $userC = new User($this->db);
            $userC->fetch($ligne->fk_socpeople);
            $userC->fetch_optionals();
            $this->id8sens = $userC->array_options['options_id8sens'];
            if ($this->id8sens < 1) {
                if ($this->debug)
                    echo "<br/>Comm pas de comm<br/>";
                if(!defined("MODE_TEST"))
                    mailSyn2("Exportation commande", $userCr->email, null, "Bonjour vos commandes ne peuvent être exporté car le commerciale na pas d'identifiant 8Sens dans son profil <a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php?id=" . $userC->id . "'>Voir</a>");
            }
        }
        else {
            if ($this->debug)
                echo "<br/>Pas de comm<br/>";
            if(!defined("MODE_TEST"))
                mailSyn2("Exportation commande", $userM->email, null, "Bonjour vos commandes ne peuvent être exportées car il n'y a pas de commercial rataché <a href='" . DOL_URL_ROOT . "/compta/commande/card.php?facid=" . $id . "'>Voir</a>");
        }
    }

    public function getCommandeDontExport() {
        $this->pathExport = DOL_DATA_ROOT . "/test/";
        $result = $this->db->query("SELECT comm.rowid as id, ref "
                . "FROM `" . MAIN_DB_PREFIX . "commande` comm, `" . MAIN_DB_PREFIX . "commande_extrafields` fe "
                . "WHERE fe.fk_object = comm.rowid " . $this->where);
        $comms = "";
        while ($ligne = $this->db->fetch_object($result)) {
            $comms .= $ligne->ref . " - ";
        }
        if ($comms != "")
            if(!defined("MODE_TEST"))
                mailSyn2("Commande non export", "dev@bimp.fr, jc.cannet@bimp.fr", null, "Bonjour voici les commande non exporté " . $comms);
    }
    
    function extract($id) {
        if ($this->type == "") {
            $this->error("Pas de type pour export " . $id);
        } elseif ($this->id8sens == 0) {
            $this->error("Pas d'id collaborateur 8sens " . $id);
        } else {
            $this->exportOk = true;



            $commande = new Commande($this->db);
            $commande->fetch($id);
            $societe = new Societe($this->db);
            $societe->fetch($commande->socid);
            
            $secteur = "INC";
            if(isset($commande->array_options['options_type']))
                $secteur = $commande->array_options['options_type'];


            if ($this->debug)
                echo "Tentative export commande " . $commande->getNomUrl(1);

            
            if($commande->array_options['options_entrepot'] < 1){
                $this->error("Pas d'entrepot... comm : ".$commande->ref);
                return -1;
            }
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch($commande->array_options['options_entrepot']);


            $tabCommande = $tabCommandeDet = array();
            
            
            $PcvLAdpTitleEnu = $PcvPAdpTitleEnu = "";
            $PcvLAdpLib = $PcvPAdpLib = $societe->name;
            $PcvLAdpRue1 = $PcvPAdpRue1 = $societe->address;
            $PcvLAdpZip = $PcvPAdpZip = $societe->zip;
            $PcvLAdpCity = $PcvPAdpCity = $societe->town;
            $listContactLiv = $commande->liste_contact(-1, 'external', 0, 'SHIPPING');
            if(count($listContactLiv)){
                $contactLiv = $listContactLiv[0];
                $contact = new Contact($this->db);
                $contact->fetch($contactLiv['id']);
                $PcvLAdpTitleEnu = $contactLiv['civility'];
                if($contactFact['lastname'] != $PcvLAdpLib && $PcvLAdpLib != $contactFact['firstname'] && $PcvLAdpLib != $contactLiv['lastname']. " ".$contactLiv['firstname'])
                    $PcvLAdpLib .= $contactLiv['lastname']. " ".$contactLiv['firstname'];
                $PcvLAdpRue1 = ($contact->address != "") ? $contact->address : $societe->address;
                $PcvLAdpZip = ($contact->zip != "") ? $contact->zip : $societe->zip;
                $PcvLAdpCity = ($contact->town != "") ? $contact->town : $societe->town;
            }
            
            $listContactFact = $commande->liste_contact(-1, 'external', 0, 'BILLING');
            if(count($listContactFact)){
                $contactFact = $listContactFact[0];
                $contact = new Contact($this->db);
                $contact->fetch($contactFact['id']);
                $PcvPAdpTitleEnu = $contactFact['civility'];
                if($contactFact['lastname'] != $PcvPAdpLib && $PcvPAdpLib != $contactFact['firstname'] && $PcvPAdpLib != $contactFact['lastname']. " ".$contactFact['firstname'])
                    $PcvPAdpLib .= " ".$contactFact['lastname']. " ".$contactFact['firstname'];
                $PcvPAdpRue1 = ($contact->address != "") ?$contact->address : $societe->address;
                $PcvPAdpZip = ($contact->zip != "") ?$contact->zip : $societe->zip;
                $PcvPAdpCity = ($contact->town != "") ?$contact->town : $societe->town;
            }
            $PcvLAdpRue1 = str_replace("\n", "", $PcvLAdpRue1);
            $PcvPAdpRue1 = str_replace("\n", "", $PcvPAdpRue1);
            
//            echo "<pre>";
//            print_r($contact);
//            
//            die();
            $tabCommande[] = array("E" => "E", "code_client" => $societe->code_client, "nom" => $PcvPAdpLib, "phone" => $societe->phone, "address" => $PcvPAdpRue1, "zip" => $PcvPAdpZip, "town" => $PcvPAdpCity, "ref" => $commande->ref, "date" => dol_print_date($commande->date, "%d-%m-%Y"), "email" => $societe->email, "total" => price($commande->total_ht), "total_ttc" => price($commande->total_ttc), "id8Sens" => $this->id8sens, "codeDepot" => $entrepot->label, "secteur" => $secteur, "CodeCli"=>"",
                "PcvPAdpTitleEnu"=>$PcvPAdpTitleEnu,
                "PcvLAdpTitleEnu"=>$PcvLAdpTitleEnu, "PcvLAdpLib" => $PcvLAdpLib, "PcvLAdpRue1"=> $PcvLAdpRue1, "PcvLAdpZip" => $PcvLAdpZip, "PcvLAdpCity" => $PcvLAdpCity);
            
            
            
            if(isset($commande->ref_client) && $commande->ref_client != "")
                $tabCommandeDet[] = array("L" => "L", "ref" => '', "product_type" => 'GEN-DIV-INFO', "qty" => "1", "subprice" => '0', "description" => "Ref client : ".$commande->ref_client, "buy_price_ht" => '0', "tva_code" => '0', "remise_percent" => '0', "tva_tx" => '0', "codeDepot" => $entrepot->label);
            
            
            $commande->fetch_lines();
            foreach ($commande->lines as $line) {
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
                $tabCommandeDet[] = array("L" => "L", "ref" => $ref, "product_type" => $type, "qty" => $line->qty, "subprice" => price($line->subprice), "description" => $line->desc, "buy_price_ht" => price($line->pa_ht), "tva_code" => $tvaCode, "remise_percent" => $line->remise_percent, "tva_tx" => $tvaCode, "codeDepot" => $entrepot->label);
            }




            if ($this->exportOk) {
                //header("Content-Disposition: attachment; filename=\"test.txt\"");
                $text = $this->getTxt($tabCommande, $tabCommandeDet);
                if (file_put_contents($this->pathExport . $commande->ref . ".txt", $text)) {
                    if ($this->debug)
                        echo "<br/>Commande " . $commande->getNomUrl(1) . " exporté<br/>";
                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . "commande SET extraparams = 1 WHERE rowid = " . $commande->id);
                    $this->nbE++;
                    return 1;
                } else
                    $this->error("Impossible d'exporté le fichier " . $this->pathExport . $commande->ref . ".txt");
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
            if ($line->desc == "Acompte")
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
