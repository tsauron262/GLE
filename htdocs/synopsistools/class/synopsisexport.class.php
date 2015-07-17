<?php

class synopsisexport {

    public $info = array();

    public function __construct($db, $sortie = 'html') {
        $this->db = $db;
        $this->sortie = $sortie;

        if ($this->sortie == "html") {
            $this->sautDeLigne = "</td></tr><tr><td>";
            $this->separateur = "</td><td>";
            $this->sortie = "html";
        } elseif ($this->sortie == "file") {
            $this->sautDeLigne = "\n";
            $this->separateur = "\t";
            $this->sortie = "file";
        }
    }

    public function exportFactureSav($print = true) {
        echo "Debut export : <br/>";
//        $result = $this->db->query("SELECT code_client, nom, phone, address, zip, town, facnumber, DATE_FORMAT(fact.datec, '%d-%m-%Y') as date, fact.rowid as factid 
//, email , total, total_ttc, id8Sens FROM  `llx_facture` fact, llx_societe soc
//LEFT JOIN llx_element_element el, llx_user_extrafields ue, llx_synopsischrono_view_105 chrono 
// WHERE `fk_object` = IF(chrono.Technicien > 0, chrono.Technicien, fact.fk_user_author) AND el.targettype = 'facture' AND el.sourcetype = 'propal' AND el.fk_source = chrono.propalid AND fk_target = fact.rowid
//  AND  fk_soc = soc.rowid AND `extraparams` IS NULL AND fact.fk_statut = 2 AND  close_code is null AND paye = 1 AND extraparams is null GROUP BY fact.rowid");


        $result = $this->db->query("SELECT code_client, nom, phone, address, zip, town, facnumber, DATE_FORMAT(fact.datec, '%d-%m-%Y') as date, fact.rowid as factid 
, email , total, total_ttc, idtech8sens as id8Sens, chronoT.Centre FROM  `llx_facture` fact


LEFT JOIN llx_element_element el ON  el.targettype = 'facture' AND el.sourcetype = 'propal' AND fk_target = fact.rowid
LEFT JOIN llx_synopsischrono chrono ON el.fk_source = chrono.propalid
LEFT JOIN llx_synopsischrono_chrono_105 chronoT ON chronoT.id = chrono.id
LEFt JOIN llx_user_extrafields ue ON `fk_object` = IF(chronoT.Technicien > 0, chronoT.Technicien, fact.fk_user_author)

, llx_societe soc
WHERE   fk_soc = soc.rowid AND `extraparams` IS NULL AND fact.fk_statut > 0 AND  close_code is null "/* AND paye = 1 */ . " AND extraparams is null AND total != 0 GROUP BY fact.rowid");


        while ($ligne = $this->db->fetch_object($result)) {
            $this->annulExport = false;
            $return1 = $return2 = "";
            $return1 .= $this->textTable($ligne, $this->separateur, $this->sautDeLigne, 'E', true);
            $return2 .= $this->textTable($ligne, $this->separateur, $this->sautDeLigne, 'E', false);
            $result2 = $this->db->query("SELECT ref, if(fd.description = 'Acompte', -100, fd.product_type) as product_type, fd.qty, fd.subprice, fd.description, fd.buy_price_ht, fd.tva_tx, fd.remise_percent FROM  `llx_facturedet` fd left join llx_product p ON p.rowid = fd.fk_product WHERE  `fk_facture` =  " . $ligne->factid);

            $i = 0;
            while ($ligne2 = $this->db->fetch_object($result2)) {
                $i++;
                if ($i == 1)
                    $return1 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "L", true);
                $return2 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "L", false);
            }
            $return = $return1 . $return2;
//            echo $return;
            if (!$this->annulExport) {
                $this->sortie($return, $ligne->facnumber, "factureSav", $ligne->factid, $print);

                if ($print)
                    echo "<br/>Facture : " . $ligne->facnumber . " exporté.<br/>";
            }
            else 
                echo "Export de " . $ligne->facnumber . " annule.</br>";
        }
        echo "Fin export : <br/>";
    }

    public function exportChronoSav($centre = null, $typeAff = null, $typeAff2 = null, $paye = false, $dateDeb = null, $dateFin = null, $blockCentre = null) {
//        echo "Momentanément indisponible";return "";
        global $user, $tabVal;
        $tabVal = array();

        if ($typeAff2 != "fact")
            $where = " (`revisionNext` = 0 || `revisionNext` is NULL) ";
        else {
            $where = " 1 ";
        }

        if ($blockCentre) {
//            if ($typeAff2 == "fact") {
//                accessforbidden("", 0, 0);
//                return 1;
//            }
            $where .= " AND Centre IN ('" . implode("','", $blockCentre) . "')";
        }

        $champDate = "fact.datec";
        if ($paye) {
            $where .= " AND fact.fk_statut = 2 AND fact.paye = '1'";
        } elseif ($typeAff2 != "fact") {
            $champDate = "propal.datec";
        }

        if ($typeAff2 == "nb")
            $champDate = "chrono.date_create";

        if ($typeAff2 == "sav")
            $champDate = "chrono.date_create";

        if ($typeAff2 == "fact" && $typeAff != "parCentre") {
            $typeAff = null;
        }

        if ($dateDeb)
            $where .= " AND " . $champDate . " > STR_TO_DATE('" . $dateDeb . " 00:00','%d/%m/%Y %H:%i') ";
        if ($dateFin)
            $where .= " AND " . $champDate . " < STR_TO_DATE('" . $dateFin . " 23:59','%d/%m/%Y %H:%i') ";
//die($where);
//        $partReq1 = "SELECT prod.ref, prod.label, SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht) as Total_Achat";
//        $partReqFin = "";
//
//        $partReq1 = "SELECT prod.ref as ref, prod.label, SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht) as Total_Achat";
//        $partReqFin = " Group BY factdet.fk_product LIMIT 0,1000";
//        $partReq5 = " FROM  `llx_facture` fact, llx_propal prop, llx_element_element el1, llx_synopsischrono_view_105 chrono, " .
////                "llx_synopsischrono_view_101 chrono2, llx_element_element el2, ".
////                "llx_synopsischrono_view_101 chrono2, ".
//                "llx_facturedet factdet left join llx_product prod on factdet.fk_product = prod.rowid 
//WHERE fact.rowid = el1.fk_target AND prop.rowid = el1.fk_source AND el1.sourcetype='propal' AND el1.targettype='facture'
//AND chrono.propalid = prop.rowid AND factdet.fk_facture = fact.Rowid
//AND  fact.close_code is null " .
////"AND chrono.id = el2.fk_source AND chrono2.id = el2.fk_target AND el2.sourcetype = 'SAV' AND el2.targettype='productCli' ".
////"AND chrono2.id = (SELECT FIRST(fk_target) FROM llx_element_element WHERE sourcetype = 'SAV' AND chrono.id = fk_source  AND targettype='productCli') ".
//                "AND factdet.total_ht != 0 AND ";

        $tableSus = "";
        $chargeAccompte = true;
        if ($typeAff2 == "ca") {
            $partReq1 = "SELECT IF(prod.ref is null, factdet.description, prod.ref) as ref, concat(prod.label,concat(' ',factdet.description)) as label, SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht*factdet.qty) as Total_Achat, SUM(factdet.total_ht - (factdet.buy_price_ht*factdet.qty)) as Total_Marge";
            $partReqFin = " Group BY factdet.fk_product, factdet.description LIMIT 0,10000";
            $tableSus = "left join llx_product prod on factdet.fk_product = prod.rowid";
        } elseif ($typeAff2 == "nb") {
            $partReq1 = "SELECT COUNT(DISTINCT(chrono.id)) as NB_PC";
            $partReqFin = " LIMIT 0,10000";
            $chargeAccompte = false;
//            $partReq5 = " FROM  llx_synopsischrono_view_105 chrono LEFT JOIN llx_propal propal on chrono.propalId = propal.rowid LEFT JOIN  llx_element_element on sourcetype = 'propal' AND targettype = 'facture' AND fk_source = propal.rowid LEFT JOIN llx_facture fact ON fact.rowid = fk_target AND fact.facnumber LIKE 'FA%' WHERE fact.close_code is null AND ";
        } else {
            $totalAchat = "SUM((factdet.buy_price_ht*factdet.qty))";
            $totalVendu = "SUM(factdet.total_ht)";
            $partReq1 = "SELECT CONCAT(soc.nom, CONCAT('|', soc.rowid)) as objSoc, chrono.ref as refSav, chronoT.Centre, propal.total_ht as Total_Propal, " . $totalVendu . " as Total_Facture, " . $totalAchat . " as Total_Achat, " . $totalVendu . " - " . $totalAchat . " as Total_Marge, MAX(chrono.date_create) as Date, MAX(fact.paye) as Paye";
//            if ($paye)
//                $partReqFin = "  Group BY fact.rowid, chrono.id LIMIT 0,10000";
//            else
            $partReqFin = "  Group BY chrono.id LIMIT 0,100000";
        }

        if ($typeAff2 != "fact")
            $where .= " AND chronoT.id = chrono.id ";
        $partReq5 = " FROM  llx_synopsischrono_chrono_105 chronoT, llx_synopsischrono chrono LEFT JOIN llx_propal propal on chrono.propalId = propal.rowid AND propal.extraparams is null ";
        $partReq5 .= " LEFT JOIN  llx_societe soc on  soc.rowid = propal.fk_soc ";
//        $partReq5 .= " LEFT JOIN  llx_element_element el on  el.sourcetype = 'propal' AND el.targettype = 'facture' AND el.fk_source = propal.rowid ";
        $partReq5 .= " LEFT JOIN  llx_element_element el2 on  el2.sourcetype = 'propal' AND el2.targettype = 'facture' AND el2.fk_source = propal.rowid ";
        $partReq5 .= " LEFT JOIN llx_facture fact2 ON fact2.close_code is null AND fact2.rowid = el2.fk_target AND (fact2.facnumber LIKE 'AC%' || fact2.facnumber LIKE 'FA%' || fact2.facnumber LIKE 'AV%')";
        $partReq5 .= " LEFT JOIN llx_facture fact ON fact.close_code is null AND fact.rowid = el2.fk_target AND fact.facnumber LIKE 'FA%' ";
        $partReq5 .= " LEFT JOIN llx_facturedet factdet ON factdet.fk_facture = fact2.rowid  AND (factdet.subprice != 0 || factdet.buy_price_ht != 0) " . $tableSus . " WHERE ";


        if ($typeAff2 == "fact") {
            $partReq1 = "SELECT CONCAT(soc.nom, CONCAT('|', soc.rowid)) as objSoc, "
                    . "CONCAT(facnumber,CONCAT('|', fact.rowid)) as objFact, "
                    . "fact.total,"
                    . "SUM(det.total_ht - (det.buy_price_ht * det.qty)) as total_marge, "
                    . "fact.fk_statut";
            $partReq5 = " FROM llx_societe soc, llx_facturedet det, llx_facture fact ";
            $partReq5 .= " LEFT JOIN  llx_element_element el on  el.sourcetype = 'propal' AND el.targettype = 'facture' AND el.fk_target = fact.rowid ";
            $partReq5 .= " LEFT JOIN  llx_propal propal on  propal.rowid = el.fk_source ";
            $partReq5 .= " LEFT JOIN  llx_synopsischrono chrono1 ON chrono1.revisionNext < 1 AND chrono1.propalId = el.fk_source ";
            $partReq5 .= " LEFT JOIN llx_synopsischrono_chrono_105 chrono on  chrono1.id = chrono.id ";
            $partReq5 .= " WHERE soc.rowid = fact.fk_soc AND det.fk_facture = fact.rowid AND fact.close_code is null AND (propal.fk_statut < 3 || propal.fk_statut IS NULL || propal.fk_statut = 4) AND ";
            $partReqFin = " GROUP BY fact.rowid LIMIT 0,200000";
            $chargeAccompte = false;
//            $partReq5 = " FROM  llx_synopsischrono_view_105 chrono LEFT JOIN llx_propal propal on chrono.propalId = propal.rowid LEFT JOIN  llx_element_element on sourcetype = 'propal' AND targettype = 'facture' AND fk_source = propal.rowid LEFT JOIN llx_facture fact ON fact.rowid = fk_target AND fact.facnumber LIKE 'FA%' WHERE fact.close_code is null AND ";
        }





//die($partReq1 . $partReq5 . $where . $partReqFin);



        if ($typeAff == "parTypeMat") {
            $result = $this->db->query("SELECT description, c.id FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101 ct," . MAIN_DB_PREFIX . "synopsischrono c WHERE c.id = ct.id;");

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                $tabT = explode("(", $ligne->description);
                $description = traiteCarac(trim($tabT[0]), " ");
                $tabT = getElementElement("SAV", "productCli", null, $ligne->id);
                if (count($tabT) > 0)
                    $tabMateriel[strtoupper($description)][] = $tabT[0]['s'];
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
            foreach ($tabMateriel as $titre => $tabChrono) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND chrono.id in (" . implode(",", $tabChrono) . ") " . $partReqFin);


//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } elseif ($typeAff == "parTypeGar") {
            $result = $this->db->query("SELECT Type_garantie as description, id FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101");

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                $tabT = explode("(", $ligne->description);
                $description = traiteCarac(trim($tabT[0]), " ");
                $tabT = getElementElement("SAV", "productCli", null, $ligne->id);
                if (count($tabT) > 0)
                    $tabMateriel[strtoupper($description)][] = $tabT[0]['s'];
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
            foreach ($tabMateriel as $titre => $tabChrono) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND chrono.id in (" . implode(",", $tabChrono) . ") " . $partReqFin);


//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } elseif ($typeAff == "parCentre" || $centre) {
//            $req = "SELECT label, valeur, propalid
//FROM  `".MAIN_DB_PREFIX."Synopsis_Process_form_list_members` ls, ".MAIN_DB_PREFIX."synopsischrono_view_105 chrono
//WHERE  `list_refid` =11 AND chrono.CentreVal = ls.valeur";
            $req = "SELECT label, valeur, propalid
FROM  `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` ls, " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 ct , " . MAIN_DB_PREFIX . "synopsischrono chrono
WHERE  `list_refid` =11 AND ct.Centre = ls.valeur AND ct.id = chrono.id";
//            $req = "SELECT label, valeur, propalid
//FROM  llx_synopsischrono_view_105 chrono LEFT JOIN `llx_Synopsis_Process_form_list_members` ls ON `list_refid` =11 AND chrono.CentreVal = ls.valeur WHERE 1";
            if ($centre) {
                $req .= " AND centre = '" . $centre . "'";
                $blockCentre = true;
            }
            $result = $this->db->query($req);

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                $tabMaterielTot[$ligne->propalid] = $ligne->propalid;
                $tabMateriel[strtoupper($ligne->label)][$ligne->propalid] = $ligne->propalid;
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
//            echo $partReq1 . $partReq5 . $where . " AND (propal.fk_statut != 3 OR propal.fk_statut is NULL) AND (propal.rowid Is NULL OR (propal.rowid NOT IN ('" . implode("','", $tabMaterielTot) . "'))) " . $partReqFin;
            if (is_null($blockCentre))
                $this->statLigneFacture("N/C", $partReq1 . $partReq5 . $where . " AND (propal.fk_statut != 3 OR propal.fk_statut is NULL) AND (propal.rowid Is NULL OR (propal.rowid NOT IN ('" . implode("','", $tabMaterielTot) . "'))) " . $partReqFin);
            foreach ($tabMateriel as $titre => $val) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND propal.fk_statut != 3 AND propal.rowid IN ('" . implode("','", $val) . "') " . $partReqFin);
//                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND CentreVal = '" . $val . "' " . $partReqFin);
//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } else {
            $this->statLigneFacture("Stat", $partReq1 . $partReq5 . $where . $partReqFin);
        }


        if ($this->sortie != 'file') {
            foreach ($tabVal as $val => $nb)
                if ($nb > 1)
                    echo "<br/>Facture en double : " . $val;
        }

        $this->sortie("", "statSav");
    }

    private function statLigneFacture($titre, $req) {
        $return1 = $return2 = "";
        $result2 = $this->db->query($req);
//
        if ($this->db->num_rows($result2) > 0 && (1)) {
            $i = 0;

            while ($ligne2 = $this->db->fetch_object($result2)) {
                $i++;
                if ($i == 1) {
                    if (isset($ligne2->NB_PC) && $ligne2->NB_PC == 0 && $this->db->num_rows($result2) == 1)
                        return '';
                    $this->textSortie($titre, "titre");
                    $return1 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "", true);
                }

                $return2 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, $titre, false);
                $oldLigne = $ligne2;
            }
            if ($i > 1)
                $return2 .= $this->textTable($oldLigne, $this->separateur, $this->sautDeLigne, "", "Total");

            $this->textSortie($return1 . $return2);
        }
    }

    public function textSortie($text, $type = "tab") {
        if ($this->sortie == 'html' && $type == "tab")
            $this->textSortie .= "<table><tr><td>" . $text . "</td></tr></table>";
        elseif ($this->sortie == 'html' && $type == "titre")
            $this->textSortie .= "<h3>" . $text . "</h3>";
        else
            $this->textSortie .= $text;
    }

    public function sortie($text, $nom = "temp", $type = "n/c", $idObj = null, $print = true) {
        global $user;
        $text .= $this->textSortie;

        if ($this->sortie == 'file') {
            $folder2 = "exportGle";
            $folder2 .= "/";

            if ($type == "factureSav") {
                $folder2 = "extractFactGle/";
            }

            $folder1 = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/";
            if (!is_dir($folder1))
                mkdir($folder1);
            if (!is_dir($folder1 . $folder2))
                mkdir($folder1 . $folder2);
            $nom = str_replace(" ", "_", $nom); //die($folder . $nom . ".txt");
            $file = $folder2 . $nom . ".txt";
            if (file_put_contents($folder1 . $file, $text)) {
                if ($type == "factureSav") {
                    if ($idObj > 0)
                        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET extraparams = 1 WHERE rowid = " . $idObj);
                    $folder2 = "extractFactGle";
                }
                if ($print)
                    echo "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=synopsischrono&file=/export/" . $file . "' class='butAction'>Fichier</a>";
            }
            else {
                if ($print)
                    echo "<span style='color:red;'>Impossible d'exporté " . $file . "</span>";
            }
        } else {
            echo "<style>"
            . "td{"
            . "border: 1px black solid;"
            . "}"
            . "</style>";
            echo $text;
        }
        $this->textSortie = "";
    }

    private function textTable($ligne, $separateur, $sautDeLigne, $prefLigne = '', $afficheTitre = true) {
        global $tabVal;
        $return = "";
        $tabCacher = array('factid', 'rowid');
        if ($afficheTitre === "Total") {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
                if($nom == "Centre")
                    continue;
//            if($nom == 'product_type')
//                $nom = 'ref_prod';


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", (isset($this->tabTot[$nom]) ? str_replace(" ", "", price($this->tabTot[$nom])) : "TOTAL")) . $separateur;
            }
            $return .= $sautDeLigne;
        }
        elseif ($afficheTitre) {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
                if($nom == "Centre")
                    continue;
//            if($nom == 'product_type')
//                $nom = 'ref_prod';


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", $nom) . $separateur;
            }
            $return .= $sautDeLigne;
            $this->tabTot = array();
        }
        else {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
                if($nom == "Centre")
                    continue;
                if ($nom == 'product_type') {
                    if ($valeur == -100)
                        $valeur = "GEN-SAV-ACOMPTE";
                    elseif ($valeur == 1)
                        $valeur = "GEN-SAV-MO";
                    elseif ($valeur == 0)
                        $valeur = "GEN-SAV-PIECES";
                    else
                        $valeur = "";
                }

                if ($nom == "objFact") {
                    if (isset($tabVal[$valeur]))
                        $tabVal[$valeur] = $tabVal[$valeur] + 1;
                    else
                        $tabVal[$valeur] = 1;
                }

                if ($nom == "id8Sens") {
                    if ($valeur <= 1) {
                        if (isset($ligne->Centre) && $ligne->Centre != "") {
                            require_once(DOL_DOCUMENT_ROOT."/synopsisapple/centre.inc.php");
                            global $tabCentre;
                            if (isset($tabCentre[$ligne->Centre][3]) && $tabCentre[$ligne->Centre][3] > 0)
                                $valeur = $tabCentre[$ligne->Centre][3];
                            else{
                                dol_syslog("Pas d'id tech, pas de tech referent DANS centre pour export facture " . print_r($ligne, 1)." \n\n   |    \n\n ".print_r($tabCentre[$ligne->Centre],1), 3);
                                $this->annulExport = true;
                            }
                        } else{
                            mailSyn("jc.cannet@bimp.fr", "Facture sans Centre", "Bonjour, la facture ".$ligne->facnumber." na pas de centre, elle ne peut donc pas étre exporté vers 8Sens. Cordialement.");
                            dol_syslog("Pas d'id tech, pas de centre pour export facture " . print_r($ligne, 1), 3);
                            $this->annulExport =true;
                        }
                    }
                }


                if ($nom == 'objSoc') {
                    $tabT = explode("|", $valeur);
                    if ($this->sortie == "html") {
                        require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
                        $socStat = new Societe(null);
                        $socStat->nom = $tabT[0];
                        $socStat->id = $tabT[1];
                        $valeur = $socStat->getNomUrl(1);
                    } else {
                        $valeur = $tabT[0];
                    }
                }

                if ($nom == 'objFact') {
                    $tabT = explode("|", $valeur);
                    if ($this->sortie == "html") {
                        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                        $socStat = new Facture(null);
                        $socStat->ref = $tabT[0];
                        $socStat->id = $tabT[1];
                        $valeur = $socStat->getNomUrl(1);
                    } else {
                        $valeur = $tabT[0];
                    }
                }

                if ($nom == "refSav" && $this->sortie == "html")
                    $valeur = "<a href='" . DOL_URL_ROOT . "/synopsischrono/card.php?ref=" . $valeur . "'>" . $valeur . "</a>";

                if ((stripos($nom, "subprice") !== false || stripos($nom, "_ht") !== false || stripos($nom, "_ttc") !== false || stripos($nom, "Total") !== false
                        ) && is_numeric($valeur)) {
                    $this->tabTot[$nom] += $valeur;
                    $valeur = str_replace(" ", "", price($valeur));
                }


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", $valeur) . $separateur;
            }



            $return .= $sautDeLigne;
        }
        return $return;
    }

}
