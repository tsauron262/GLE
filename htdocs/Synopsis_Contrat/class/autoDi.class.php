<?php
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_DemandeInterv/demandeInterv.class.php");

class autoDi {

    function autoDi($idContrat) {
        global $db;
        $this->db = $db;
        $this->idContrat = $idContrat;

        $this->getTabSecteur();
    }

    function getTabSecteur() {
        $tabType = array('visite', 'tele');
        $tabDatePrise = array();
//        $sql = $this->db->query("SELECT ee.fk_target as prodCli, c.rowid as contratdet FROM ".MAIN_DB_PREFIX."contratdet c, 
//            ".MAIN_DB_PREFIX."element_element ee 
//                WHERE ee.fk_source = c.rowid
//                AND ee.sourcetype='contratdet' AND ee.targettype='productCli'
//                AND c.fk_contrat =".$idContrat);

        $contrat = new Synopsis_Contrat($this->db);
        $contrat->fetch($this->idContrat);
        $contrat->fetch_lines();

        $tabSite = array();
        foreach ($contrat->lines as $lignes) {
            foreach ($tabType as $type) {
            $nbVisite = 0;
                if ($type == 'visite')
                    $nbVisite = $lignes->GMAO_Mixte['nbVisiteAn'];
                if ($type == 'tele' && $lignes->GMAO_Mixte['telemaintenance'])
                    $nbVisite = $lignes->qty;
                if ($nbVisite > 0) {
                    if (count($lignes->tabProdCli) == 0)
                        die("Pas de produit client en lien avec ligne contrat.<br/><a href='" . DOL_URL_ROOT . "/Synopsis_Contrat/contratDetail.php?id=" . $lignes->id . "'>Ligne</a>");
                    foreach ($lignes->tabProdCli as $prod) {
                        $site = 0;
                        if (!isset($tabSite[$site]))
                            $tabSite[$site] = array('visite' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()), 'tele' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()));
                        $tabSite[$site][$type]['prod'][$prod] = $nbVisite;
                        if ($tabSite[$site][$type]['visiteMax'] < $nbVisite)
                            $tabSite[$site][$type]['visiteMax'] = $nbVisite;
                    }
                }
            }
        }
        if(!isset($lignes))
            die("Pas de ligne ds le contrat");
        $ligneFak = $lignes;


        foreach ($tabSite as $numSite => $site) {
            foreach ($tabType as $type) {
                $nbVisiteSite = $site[$type]['visiteMax'];
                foreach ($site[$type]['prod'] as $prod => $nbVisiteProd) {
                    for ($i = 0; $i < $nbVisiteProd; $i++) {
                        $numVisite = round($i * $nbVisiteSite / $nbVisiteProd, 0, PHP_ROUND_HALF_DOWN);
                        $tabSite[$numSite][$type]['tabVisite'][$numVisite]['prod'][] = $prod;
                    }
                }
                ksort($tabSite[$numSite][$type]['tabVisite']);
            }
        }

        echo "<pre>";
        print_r($tabSite);


        foreach ($tabSite as $numSite => $site) {
            foreach ($tabType as $type) {
                echo "SITE " . ($numSite + 1) . ": " . $type . "<br/><br/>";
                foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                    $delai = round(365 / count($site[$type]['tabVisite']) * $numVisiste);
                    $date = date_add(new DateTime(), date_interval_create_from_date_string($delai . " day"));
                    $decale = 0;
                    for ($i = 0; $i < 100; $i++) {
                        if (date_format($date, "w") != 0 && date_format($date, "w") != "6"
                                && !isset($tabDatePrise[date_format($date, "d-m-Y")])) {
                            $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "actioncomm` WHERE `datep` <= '" . date_format($date, "Y-m-d") . " 23:59:59' AND `datep2` >= '" . date_format($date, "Y-m-d") . "'";
                            $sql = $this->db->query($requete);
                            if ($this->db->num_rows($sql) == 0){
                                $tabDatePrise[date_format($date, "d-m-Y")] = date_format($date, "d-m-Y");
                                break;
                            }
                        }
                        $decale++;
                        $date = date_add($date, date_interval_create_from_date_string("1 day"));
                    }

                    $tabSite[$numSite][$type]['tabVisite'][$numVisiste]['date'] = date_format($date, "Y-m-d");
                    echo "Visite " . ($numVisiste + 1) . "/" . count($site[$type]['tabVisite']) . " le " . date_format($date, "d-m-Y") . (($decale > 0) ? " Decaler de " . $decale . " jours" : "") . "<br/>";
                    foreach ($visite['prod'] as $prod)
                        echo " - Matériel a visiter : " . $ligneFak->getInfoOneProductCli($prod) . "<br/>";
                }
                echo "<br/><br/>";
            }
        }
        
        global $user;
        foreach($tabSite as $numSite => $site){
            
            foreach ($tabType as $type) {
                foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                    $di = new demandeInterv($this->db);
                    $di->date = $visite['date'];
                    $di->socid = $contrat->socid;
                    $di->author = $user->id;
                    $di->create();
                }
                
            }
        }
    }

}

?>
