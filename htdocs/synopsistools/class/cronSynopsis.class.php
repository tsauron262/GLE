<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/divers.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");

class CronSynopsis {

    var $nbErreur = 0;
    var $sortie = '';
    var $output = "";

    public function __construct($db) {
        $this->db = $db;
    }

    public function netoyage() {
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE  `sourcetype` LIKE  'resa'");
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User WHERE  `tms` <  '" . $this->db->idate(strtotime("-3 day")) . "'");
    }
    
    
    public function mailFiOuvert(){
        
        
    }

    public function testGlobal() {
        $this->verifCompteFermer();
        $this->sauvBdd();

        $this->netoyage();
//        $this->majChrono();
//        $this->majSav();
//        $this->verif();
//        $this->sortieMail();




        $this->output .= "FIN";
        echo 1;
    }

    public function sauvBdd($table = "") {
        require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/maj.class.php");
        $this->sortie .= maj::sauvBdd($table);
    }
    
    

    public function extractFact($debug = false) {
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importBl.class.php");
        $import = new importBl($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output = $import->output;
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importCommande.class.php");
        $import = new importCommande($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output = $import->output;
        
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importProd.class.php");
        $import = new importProd($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
        
        
        
        
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importDepot.class.php");
        $import = new importDepot($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
//        
//        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importStock.class.php");
        $import = new importStock($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importFourn.class.php");
        $import = new importFourn($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importProdFourn.class.php");
        $import = new importProdFourn($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
        
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/importCat.class.php");
        $import = new importCat($this->db);
        $import->debug = $debug;
        $import->go(); 
        $this->output .= $import->output;
        
        
        
        
//        require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/synopsisexport.class.php");
//        $export = new synopsisexport($this->db, 'file');
//        $export->exportFactureSav(false);
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/exportfacture.class.php");
        $export = new exportfacture($this->db);
        $export->debug = $debug;
        $export->exportTout(); 
        $this->output = $export->output;
        
        
        
        require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/exportCommande.class.php");
        $export = new exportCommande($this->db);
        $export->debug = $debug;
        $export->exportTout(); 
        $this->output .= $export->output;
        


        echo "fin";
        
        return "End";
    }

    public function sortieMail() {
        mailSyn("Tommy@drsi.fr", "Rapport netoyage", $this->sortie . "<br/><br/>" . $this->erreurs());
    }

//global $oldPref, $nbIframeMax, $nbIframe, $nbErreur;


    private function verifOBj($objT, $text, $sqlReq, $option = '') {
        global $totalFact, $tabTech;
        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
        $req = $this->db->query($sqlReq);
        $obj = new $objT($this->db);
        $this->titre("<h2>" . $objT . "</h2>");

        while ($result = $this->db->fetch_object($req)) {
            $obj->fetch($result->rowid);
            $erreur = true;


            if ($option == 'ligneSav' && ($objT != "Propal" || $objT != "Facture")) {
                $erreur = false;
                foreach ($obj->lines as $ligne) {
                    if (stripos($ligne->desc, "SAV") !== false)
                        $erreur = true;
                }
            }

            if ($erreur) {
                $this->erreur($text . " : " . $obj->getNomUrl(1));
                if ($objT == "Propal") {
                    $tabT = getElementElement("propal", "facture", $result->rowid);
                    $obj2 = new Facture($this->db);
                    foreach ($tabT as $ligne) {
                        $obj2->fetch($ligne['d']);
                        if (is_null($obj2->close_code)) {
                            $this->titre("- Fact" . $obj2->getNomUrl(1) . "</br>");
                            $totalFact[$obj2->id] = $obj2->total_ttc;
                            $tabTech[$obj2->user_author][$obj2->id] = $text . " : " . $obj->getNomUrl(0) . " : " . $obj2->getNomUrl(0);
                        }
                    }
                }

                if ($objT == "Facture") {
                    $totalFact[$obj->id] = $obj->total_ttc;
                    $tabTech[$obj->user_author][$obj->id] = $text . " : " . $obj->getNomUrl(0);
                }
                $this->titre("</br>");
            }
        }
        $this->titre("</br>");
        $this->titre("</br>");
    }

    function majSAv($mailTech = false) {
        global $totalFact, $tabTech;
        $totalFact = $tabTech = array();

        $this->verifOBj("Propal", "Propal avec deux Facture FAxx", "SELECT p.rowid, count(f.rowid) as nb from " . MAIN_DB_PREFIX . "facture f, " . MAIN_DB_PREFIX . "element_element ee, " . MAIN_DB_PREFIX . "propal p WHERE p.rowid = ee.fk_source AND f.rowid = ee.fk_target AND ee.sourcetype = 'propal' AND ee.targettype = 'facture' AND f.facnumber LIKE 'FA%'  AND close_code is null group by p.rowid having nb > 1");
        $this->verifOBj("Propal", "Propal avec deux accompte ACxx", "SELECT p.rowid, count(f.rowid) as nb from " . MAIN_DB_PREFIX . "facture f, " . MAIN_DB_PREFIX . "element_element ee, " . MAIN_DB_PREFIX . "propal p WHERE p.rowid = ee.fk_source AND f.rowid = ee.fk_target AND ee.sourcetype = 'propal' AND ee.targettype = 'facture' AND f.facnumber LIKE 'AC%'  AND close_code is null group by p.rowid having nb > 1");
        $this->verifOBj("Propal", "Propal avec trois facture", "SELECT p.rowid, count(f.rowid) as nb from " . MAIN_DB_PREFIX . "facture f, " . MAIN_DB_PREFIX . "element_element ee, " . MAIN_DB_PREFIX . "propal p WHERE p.rowid = ee.fk_source AND f.rowid = ee.fk_target AND ee.sourcetype = 'propal' AND ee.targettype = 'facture' AND close_code is null group by p.rowid having nb > 2");
        $this->verifOBj("Propal", "Propal sans Sav", "SELECT rowid from " . MAIN_DB_PREFIX . "propal where rowid not in (select propalid from " . MAIN_DB_PREFIX . "synopsischrono) AND extraparams is null AND ref LIKE 'PR%'", 'ligneSav');
        $this->verifOBj("Facture", "Facture sans propal", "select rowid from " . MAIN_DB_PREFIX . "facture where rowid not in (SELECT f.rowid from " . MAIN_DB_PREFIX . "facture f, " . MAIN_DB_PREFIX . "element_element ee, " . MAIN_DB_PREFIX . "propal p WHERE p.rowid = ee.fk_source AND f.rowid = ee.fk_target AND ee.sourcetype = 'propal' AND ee.targettype = 'facture') AND close_code is null ", "ligneSav");
//    $this->verifOBj("Propal", "", );
//    $this->verifOBj("Propal", "", );
//    $this->verifOBj("Propal", "", );


        $totG = 0;
        foreach ($totalFact as $id => $totI)
            $totG += $totI;


        $i = 0;
        $tech = new User($this->db);
        foreach ($tabTech as $idTech => $tabFact) {
            if ($idTech < 1)
                $idTech = 1;

            $html = "Bonjour, BIMP-ERP a détécté des problèmes avec certaines factures, merci de vérifier ces factures et de 'Classer Abandonnée' éventuellement les factures qui sont remplacées ou inutiles "
                    . "(seul les factures positives peuvent étre fermées, ajoutez une ligne à 1€ pour les factures à 0 avant de les 'Classer Abandonnée'). "
                    . "<br/>Si les factures sont classées payées merci de cliquer sur 'Demande Annulation'"
                    . "<br/>Si le SAV n'est pas lié à la bonne propal, merci de modifier ce dernier pour choisir la propal.<br/><br/> Merci.<br/><br/>";

            $tech->fetch($idTech);
            $this->titre("<br/>" . $tech->getNomUrl(1) . "</br>");
            foreach ($tabFact as $nom) {
                if (stripos($nom, "FA1410-0075") === false)
                    $i++;
                $html .= "<br/>" . $nom;
            }

            if ($mailTech && $tech->email != '') {
                sleep(1);
                mailSyn2("BIMP-ERP problémes factures", $tech->email, "Application BIMP-ERP <tommy@drsi.fr>", $html);
            }
            $this->titre($html);
        }
        $this->titre("<br/>");
        $this->titre('<form action="" method="post"><input type="hidden" name="mail" value="true"/><input type="hidden" name="action" value="majSav"/><input type="submit" value="Envoie mail" class="butAction"/></form>');

        $this->titre("Total : " . $totG . " € " . $i . " factures</br></br>");

        $this->titre("Fin maj");
    }

    function majChrono() {
//        $finReq = "" . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid NOT IN (SELECT id FROM " . MAIN_DB_PREFIX . "synopsischrono)";
//        $sqlValueChronoSansParent = $this->db->query("SELECT * FROM " . $finReq);
//        while ($resultValueChronoSansParent = $this->db->fetch_object($sqlValueChronoSansParent))
//            $this->erreur("Valeur chrono sans lien a un chrono. " . $resultValueChronoSansParent->id . "|" . $resultValueChronoSansParent->chrono_refid);
//        $delSansParent = $this->db->query("DELETE FROM " . $finReq);
//        if ($this->db->affected_rows($delSansParent) > 0)
//            $this->erreur($this->db->affected_rows($delSansParent) . " lignes supprimé dans la table chrono_value</br></br>");
//
//
//
////    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsischrono";
//        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_value";
//        $tabFusion = array();
//        $sql = $this->db->query($requete);
//        while ($result = $this->db->fetch_object($sql)) {
//            if (isset($tabChrono[$result->chrono_refid][$result->key_id])) {
//                if ($tabChrono[$result->chrono_refid][$result->key_id]['val'] == $result->value) {
//                    $this->supprLigneChronoValue($result->id, "identique " . $tabChrono[$result->chrono_refid][$result->key_id]['id'] . "|" . $result->id);
//                    continue;
//                } else {
//                    if ($tabChrono[$result->chrono_refid][$result->key_id]['val'] == null) {
//                        $this->supprLigneChronoValue($tabChrono[$result->chrono_refid][$result->key_id]['id'], "1er null " . $tabChrono[$result->chrono_refid][$result->key_id]['id'] . "|" . $result->id);
//                        continue;
//                    } elseif ($result->value == null || $result->value == "") {
//                        $this->supprLigneChronoValue($result->id, "deuxieme null " . $tabChrono[$result->chrono_refid][$result->key_id]['id'] . "|" . $result->id);
//                        continue;
//                    } else {
//                        $tabDay = explode("-", $result->value);
//                        $tabHour = explode(":", $result->value);
//                        if (isset($tabDay[2]) && isset($tabHour[2])) {
//                            $dateF = new DateTime($tabChrono[$result->chrono_refid][$result->key_id]['val']);
//                            $dateActu = new DateTime($result->value);
//                            $interval = date_diff($dateF, $dateActu);
//                            if ($interval->format('%R%a') > -2 && $interval->format('%R%a') < 2) {
//                                $this->supprLigneChronoValue($result->id, "date moins de 24h de diff " . $tabChrono[$result->chrono_refid][$result->key_id]['id'] . "|" . $result->id);
//                                continue;
//                            }
//                        }
//                    }
//                }
//                $this->erreur("<br/>gros gros gros probléme deux clef pour meme champ diferent." . $tabChrono[$result->chrono_refid][$result->key_id]['val'] . "  |  " . $result->value);
//                continue;
//            }
//            $tabChrono[$result->chrono_refid][$result->key_id] = array('val' => $result->value, 'id' => $result->id);
//            if ($result->key_id == "1011" && stripos($result->value, "clients mac") === false && stripos($result->value, "Postes clients Mac") === false && stripos($result->value, "Postes Apple") === false && stripos($result->value, "clients pc") === false && stripos($result->value, "Serveur mac") === false && stripos($result->value, "Postes Mac") === false && stripos($result->value, "Postes clients Apple") === false && stripos($result->value, "NC") === false) {
//                if (!isset($tabSN[$result->value]))//Tous vas bien c'est la premiere fois que on a cette sn
//                    $tabSN[$result->value] = $result->chrono_refid;
//                else {
//                    $oldId = $tabSN[$result->value];
//                    $newId = $result->chrono_refid;
//                    $idOldProd = $tabChrono[$oldId][1010]['val'];
//                    $idNewProd = $tabChrono[$result->chrono_refid][1010]['val'];
//                    $tabProdIgnore = array(0, 559, 561, 560);
//                    if ($oldId == $newId)
//                        die("Oups");
//                    if ($idOldProd == $idNewProd)//Identique que un autre fusion
//                        $tabFusion[$oldId][$newId] = $newId;
//                    elseif (in_array($idNewProd, $tabProdIgnore))
//                        $tabFusion[$oldId][$newId] = $newId;
//                    elseif (in_array($idOldProd, $tabProdIgnore))
//                        $tabFusion[$newId][$oldId] = $oldId;
//                    else { //Meme num de serie mais pas meme product //probleme
//                        $this->erreur("<br/>" . $result->value . "probléme" . $oldId . "|" . $newId . "||||" . $idOldProd . "|" . $idNewProd);
//                        $this->titre($this->lienFusion($oldId, $newId));
//                    }
//                }
//            }
//        }
//        foreach ($tabFusion as $idMettre => $tabIdFaible) {
//            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $idMettre);
//            $chrono_maitre = $this->db->fetch_object($sql);
//            foreach ($tabIdFaible as $idFaible) {
//                $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $idFaible);
//                $chrono_faible = $this->db->fetch_object($sql);
//                if ($chrono_maitre->fk_soc != $chrono_faible->fk_soc) {
//                    $this->erreur("<br/><br/>Gros probléme, mem ref, mem prode mais pas meme soc " . $chrono_maitre->fk_soc . "|" . $chrono_faible->fk_soc);
//                    $this->titre($this->lienFusion($idMettre, $idFaible));
//                } elseif ($idMettre == $idFaible)
//                    $this->erreur("<br/><br/>Meme id " . $idMettre);
//                else
//                    $this->fusionChrono($idMettre, $idFaible);
//            }
//        }
    }

    function verifCompteFermer() {
        global $user;
        $str = "";
        if (array_key_exists('options_date_s', $user->array_options)) {
            $mails = "tommy@bimp.fr, grh@bimp.fr";
            $mails2 = $mails .", f.poirier@bimp.fr, j.belhocine@bimp.fr";
            $sql = $this->db->query("SELECT u.login, u.rowid, u2.email  FROM `" . MAIN_DB_PREFIX . "user_extrafields` ue, " . MAIN_DB_PREFIX . "user u LEFT JOIN llx_user u2 ON u2.rowid = u.fk_user  WHERE `date_s` <= now() AND fk_object = u.rowid AND u.statut = 1");
            while ($result = $this->db->fetch_object($sql)) {
                $userF = new User($this->db);
                $userF->fetch($result->rowid);
                $userF->setstatus(0);
                $str2 = "Bonjour le compte de " . $result->login . " viens d'être fermé. Cordialement.";
                $str .= $str2."<br/>";
                mailSyn2("Fermeture compte " . $result->login, $mails2.($result->email != "" ? ",".$result->email :""), null, $str2);
            }
            
            foreach(array(14, 7) as $nbDay){
                $sql = $this->db->query("SELECT u.login, u.rowid, u2.email  FROM `" . MAIN_DB_PREFIX . "user_extrafields` ue, " . MAIN_DB_PREFIX . "user u LEFT JOIN llx_user u2 ON u2.rowid = u.fk_user WHERE `date_s` = DATE(DATE_ADD(now(), INTERVAL ".$nbDay." DAY)) AND fk_object = u.rowid AND u.statut = 1");
                while ($result = $this->db->fetch_object($sql)) {
                    $str2 = "Bonjour le compte de " . $result->login . " sera fermé dans ".$nbDay." jours. Cordialement.";
                    $str .= $str2."<br/>";
                    mailSyn2("Fermeture compte " . $result->login. " dans ".$nbDay." jours", $mails.($result->email != "")? ",".$result->email :"", null, $str2);
                }
            }
        echo $str." Comptes fermés";
        }
        else
            echo "Pas d'info sur la date de sortie.";
    }

//else if (isset($_GET['action']) && $_GET['action'] == "verif") {
    function verif() {
        $tabSuppr = $tabSuppri = array();
        //Test des chrono
        $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE type_valeur = 10");
        while ($result = $this->db->fetch_object($sql)) {
            $tabValOk = array();
            $sql2 = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_lien WHERE rowid = " . $result->type_subvaleur);
            $result2 = $this->db->fetch_object($sql2);
            if ($result2->sqlFiltreSoc != "") {
                $champId = $result2->champId;
                $sqlChrono = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE model_refid = " . $result->model_refid);
                while ($resultChrono = $this->db->fetch_object($sqlChrono)) {
                    $idSoc = $resultChrono->fk_soc;
//                if (!$idSoc > 0)
//                    die("kkkk");
                    if (!isset($tabSoc[$idSoc]) && $idSoc > 0)
                        $tabSoc[$idSoc] = array();
                    if ($idSoc > 0) {
                        $sql4 = $this->db->query("SELECT " . $champId . " FROM " . $result2->table . " WHERE " . str_replace("[id]", $idSoc, $result2->sqlFiltreSoc));
                        while ($result4 = $this->db->fetch_object($sql4)) {
                            $tabSoc[$idSoc][] = $result4->$champId;
                        }
                    }
                    if (!isset($tabValOK[$result2->table])) {
                        $tabValOK[$result2->table] = array();
                        $sql4 = $this->db->query("SELECT " . $champId . " FROM " . $result2->table);
                        while ($result4 = $this->db->fetch_object($sql4)) {
                            $tabValOK[$result2->table][] = $result4->$champId;
                        }
                    }

                    $tabLien = getElementElement($result2->nomElem, getParaChaine($result->extraCss, "type:"), null, $resultChrono->id);
                    foreach ($tabLien as $lien) {
                        if (!in_array($lien['s'], $tabValOK[$result2->table])) {
                            $tabSuppr['elementElement'][$result2->nomElem][$lien['s']] = $lien['s'];
                            $this->erreur("[AUTOCORRECT] Lien vers element existant plus." . ($result2->nomElem . "|" . getParaChaine($result->extraCss, "type:") . "|" . $lien['s'] . "|" . $resultChrono->id) . "</br>");
                        }
                    }
                    foreach ($tabLien as $lien) {
                        if ($idSoc > 0 && !in_array($lien['s'], $tabSoc[$idSoc]) && !isset($tabSuppr['elementElement'][$result2->nomElem][$lien['s']]))
                            $this->erreur("Contrainte non respecté." . ($result2->nomElem . "|" . getParaChaine($result->extraCss, "type:") . "|" . $lien['s'] . "|" . $resultChrono->id) . " (Soc " . $idSoc . ")</br>");
                    }
                }
            }
        }

        foreach (array("FI" => array("table" => "fichinter"),
    "DI" => array("table" => "synopsisdemandeinterv"),
    "commande" => array("table" => "commande"),
    "contrat" => array("table" => "contrat"),
    "contratdet" => array("table" => "contratdet"),
    "idUserGle" => array("table" => "user"),
    "userTech" => array("table" => "user")) as $elem => $para) {
            $result = getElementElement($elem);
            foreach ($result as $ligne) {
                $idT = $ligne['s'];
                $nomId = (isset($para['nomId']) ? $para['nomId'] : "rowid");
                $sql = $this->db->query("SELECT " . $nomId . " FROM " . MAIN_DB_PREFIX . $para['table'] . " WHERE " . $nomId . " = " . $idT);
                if ($this->db->num_rows($sql) < 1) {
                    $tabSuppr['elementElement'][$elem][] = $idT;
                    $this->erreur("[AUTOCORRECT] Lien vers element inexistant. " . $elem . " | " . $idT);
                }
            }

            $result = getElementElement(null, $elem);
            foreach ($result as $ligne) {
                $idT = $ligne['d'];
                $nomId = (isset($para['nomId']) ? $para['nomId'] : "rowid");
                $sql = $this->db->query("SELECT " . $nomId . " FROM " . MAIN_DB_PREFIX . $para['table'] . " WHERE " . $nomId . " = " . $idT);
                if ($this->db->num_rows($sql) < 1) {
                    $tabSupprI['elementElement'][$elem][] = $idT;
                    $this->erreur("[AUTOCORRECT] Lien vers element inexistant. " . $elem . " | " . $idT);
                }
            }
        }


        if (isset($tabSuppr['elementElement']))
            foreach ($tabSuppr['elementElement'] as $element => $tabValSuppr)
                foreach ($tabValSuppr as $idSuppr)
                    delElementElement($element, null, $idSuppr);
        if (isset($tabSupprI['elementElement']))
            foreach ($tabSupprI['elementElement'] as $element => $tabValSuppr)
                foreach ($tabValSuppr as $idSuppr)
                    delElementElement(null, $element, null, $idSuppr);

        $this->netoyeDet("propal");
        $this->netoyeDet("commande");
        $this->netoyeDet("contrat");
        $this->netoyeDet("propal");
        $this->netoyeDet("usergroup", MAIN_DB_PREFIX . "usergroup_user");
        $this->netoyeDet("user", MAIN_DB_PREFIX . "usergroup_user");
        $this->netoyeDet("user", MAIN_DB_PREFIX . "user_rights");
//        $this->netoyeDet("product", "babel_categorie_product", "babel_");


        $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "socpeople");
        $tabFusion = array();
        while ($result = $this->db->fetch_object($sql)) {
            $clef = $result->fk_soc . $result->zip . $result->town . $result->poste . $result->phone . $result->phone_perso . $result->phone_mobile . $result->fax . $result->emil;
            if (isset($tab[$clef][$result->lastname])) {
                $tabFusion[$tab[$clef][$result->lastname]][$result->rowid] = true;
            } elseif (isset($tab[$clef])) {
//            $soc = new Societe($this->db);
//            $soc->fetch($result->fk_soc);
//            if (stripos($result->lastname, $soc->name) !== false) {
//                foreach ($tab[$clef] as $name => $id)
//                    if (stripos($name, $soc->name) !== false) {
//                        $tabFusion[$id][$result->rowid] = true;
//                        break;
//                    }
//            } else {
//                $tabNom = explode(" - ", $result->lastname);
//                if (isset($tabNom))
//                    foreach ($tab[$clef] as $name => $id)
//                        if (stripos($name, $tabNom[0]) !== false) {
//                            $tabFusion[$id][$result->rowid] = true;
//                            break;
//                        }
//            }
                $tabNom = explode(" - ", $result->lastname);
                if (isset($tabNom[1])) {
                    foreach ($tab[$clef] as $name => $id) {
                        $tabFusion[$id][$result->rowid] = true;
                        break;
                    }
                } else {
                    $tab[$clef][$result->lastname] = $result->rowid;
                }
            } else {
                $tab[$clef][$result->lastname] = $result->rowid;
            }
        }
        $nbFusion = 0;
        foreach ($tabFusion as $idM => $tab) {
            foreach ($tab as $idF => $inut) {
                $this->db->query("UPDATE " . MAIN_DB_PREFIX . "element_contact SET fk_socpeople =" . $idM . " WHERE fk_socpeople = " . $idF);
                $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "socpeople WHERE rowid = " . $idF);
                $this->erreur("contact  " . $idM . " et " . $idF . " fusionné. </br>");
                $nbFusion++;
            }
        }
        $this->titre($nbFusion . " Contact fusionné.<br/>");
    }

    function sortieHtml() {
        echo $this->sortie . "<br/><br/>" . $this->erreurs();
        ;
    }

    function erreurs() {
        if ($this->nbErreur == 0)
            return "Succés";
        else
            return "Finit avec des erreurs : " . $this->nbErreur;
    }

    private function lienFusion($id1, $id2) {
        global $nbIframeMax, $nbIframe;
        $return = "<br/>";
        $lien = DOL_URL_ROOT . '/synopsischrono/card.php?id=' . $id1;
        $return .= '<a href="' . $lien . '">Prod 1</a>';
        if ($nbIframe < $nbIframeMax) {
            $nbIframe++;
            $return .= '<iframe width="600" height="600" src="' . $lien . '&nomenu=true"></iframe>';
        }
        $return .= '<form action=""><input type="hidden" name="action" value="fusionChrono"/><input type="hidden" name="id1" value="' . $id1 . '"/><input type="hidden" name="id2" value="' . $id2 . '"/><input type="submit" value="Garder 1" class="butAction"/></form>';
        $lien = DOL_URL_ROOT . '/synopsischrono/card.php?id=' . $id2;

        $return .= '<a href="' . $lien . '">Prod 2</a>';
        $return .= '<form action=""><input type="hidden" name="action" value="fusionChrono"/><input type="hidden" name="id1" value="' . $id2 . '"/><input type="hidden" name="id2" value="' . $id1 . '"/><input type="submit" value="Garder 2" class="butAction"/></form>';
        if ($nbIframe < $nbIframeMax) {
            $nbIframe++;
            $return .= '<iframe width="600" height="600" src="' . $lien . '&nomenu=true"></iframe>';
            $return .= "<br/><br/>";
        }
        $return .= "<br/><br/>";
        return $return;
    }

//    private function supprLigneChronoValue($id, $text) {
//        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE id =" . $id);
//        $this->erreur("<br/>1 ligne supprimer " . $text . "<br/>");
//        ;
//    }

//    private function fusionChrono($idMaitre, $idFaible) {
//        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "element_element SET fk_target = " . $idMaitre . " WHERE targettype = 'productCli' AND fk_target = " . $idFaible);
//        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id=" . $idFaible);
//        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid=" . $idFaible);
//        $this->erreur("<br/>FUSION OK :" . $idMaitre . "|" . $idFaible);
//    }

    private function erreur($text) {
        global $nbErreur;
        $this->sortie .= $text;
        $this->sortie .= "<br/>";
        $this->nbErreur++;
    }

    private function titre($text) {
        $this->sortie .= $text;
        $this->sortie .= "<br/>";
    }

    private function netoyeDet($table, $table2 = null, $prefTab = null) {
        if ($prefTab)
            $nomTable = $prefTab . $table;
        else
            $nomTable = MAIN_DB_PREFIX . $table;
        if ($table2)
            $nomTable2 = $table2;
        else
            $nomTable2 = $nomTable . "det";
        $requete = "DELETE FROM " . $nomTable2 . " WHERE fk_" . $table . " NOT IN (SELECT DISTINCT(rowid) FROM " . $nomTable . " WHERE 1);";
        $result = $this->db->query($requete);
        if (!$result)
            $this->erreur("requete incorrecte" . $requete);
//        else echo mysqli_affected_rows($this->db).$requete;
        if ($this->db->affected_rows($result) > 0)
            $this->erreur($this->db->affected_rows($result) . " lignes supprimé dans la table " . $table . "</br></br>");
//        else
//            echo "Pas de suppressio";
//        $requete = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal NOT IN (SELECT DISTINCT(rowid) FROM ".MAIN_DB_PREFIX."propal WHERE 1);";
//        $this->queryS($requete);
    }

}
