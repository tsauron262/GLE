<?php

require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_DemandeInterv/demandeInterv.class.php");

class autoDi {

    public $display = false;

    function autoDi($processDet = null, $idContrat = null) {
        global $db;
        $this->db = $db;
        $this->processDet = $processDet;

        if (!$idContrat)
            $idContrat = $processDet->element_refid;


        $this->idContrat = $idContrat;
        $this->contrat = new Synopsis_Contrat($this->db);
        $this->contrat->fetch($this->idContrat);
        $this->contrat->fetch_lines();

        $tabT = getElementElement("soc", "userTech", $this->contrat->socid);
        $this->idTech = (isset($tabT[0]['d']) ? $tabT[0]['d'] : 0);
    }

    function verif() {
        $this->display = true;
        $this->getTabSecteur();
    }

    function getTabSecteur() {
        $this->tabType = array('visite', 'tele');
        $tabDatePrise = array();
        $tabSite = array();
//      

        foreach ($this->contrat->lines as $lignes) {
            foreach ($this->tabType as $type) {
                $nbVisite = 0;
                if ($type == 'visite')
                    $nbVisite = $lignes->GMAO_Mixte['nbVisiteAn'];
                if ($type == 'tele' && $lignes->GMAO_Mixte['telemaintenance'] && $lignes->qty > 1)
                    $nbVisite = $lignes->qty;
                if ($nbVisite > 0) {
                    if (count($lignes->tabProdCli) == 0)
                        die("Attention !!!!!!! Finalisation impossible : Pas de produit client en lien avec ligne contrat " . $lignes->id . ".<br/><a href='" . DOL_URL_ROOT . "/Synopsis_Contrat/contratDetail.php?id=" . $lignes->id . "'>Clicker ici pour réparer</a>");
                    foreach ($lignes->tabProdCli as $prod) {
                        $site = 0;
                        if (!isset($tabSite[$site]))
                            $tabSite[$site] = array('visite' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()), 'tele' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()));
                        $tabSite[$site][$type]['prod'][] = array('idProd' => $prod, 'nbVisite' => $nbVisite, 'fkProdContrat' => $lignes->fk_product);
                        if ($tabSite[$site][$type]['visiteMax'] < $nbVisite)
                            $tabSite[$site][$type]['visiteMax'] = $nbVisite;
                    }
                }
            }
        }

        if (count($tabSite) == 0) { //Aucune visite.
            if ($this->processDet) {
                $this->processDet->validate("1");
                $this->processDet->fetch($this->processDet->id);
                $this->contrat->activeAllLigne();
            }
        } else {

            if ($this->idTech == 0){
                $tabT = getElementElement("commande", "contrat", null, $this->contrat->id);
                $lien = (isset($tabT[0]['s'])? "<a href='".DOL_URL_ROOT."/Synopsis_PrepaCommande/prepacommande.php?id=".$tabT[0]['s']."&mainmenu=commercial&leftmenu=orders#pppart6a'>Coriger</a>" : "");
                die("Attention !!!!!!! Finalisation imposible : Pas de technicien définit pour le client ".$lien."<br/>");
            }

            if (!isset($lignes))
                die("Pas de ligne ds le contrat");
            $ligneFak = $lignes;


            foreach ($tabSite as $numSite => $site) {
                foreach ($this->tabType as $type) {
                    $nbVisiteSite = $site[$type]['visiteMax'];
                    foreach ($site[$type]['prod'] as $prod) {
                        $nbVisiteProd = $prod['nbVisite'];
                        for ($i = 0; $i < $nbVisiteProd; $i++) {
                            $numVisite = round($i * $nbVisiteSite / $nbVisiteProd, 0, PHP_ROUND_HALF_DOWN);
                            $tabSite[$numSite][$type]['tabVisite'][$numVisite]['prod'][] = $prod;
                        }
                    }
                    ksort($tabSite[$numSite][$type]['tabVisite']);
                }
            }



            foreach ($tabSite as $numSite => $site) {
                foreach ($this->tabType as $type) {
                    $this->sortie("<h2>SITE " . ($numSite + 1) . ": " . $type . "</h2><br/>");
                    foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                        $delai = round(365 / count($site[$type]['tabVisite']) * $numVisiste);
                        $date = date_add(new DateTime(), date_interval_create_from_date_string($delai . " day"));
                        $decale = 0;
                        for ($i = 0; $i < 100; $i++) {
                            if (date_format($date, "w") != 0 && date_format($date, "w") != "6"
                                    && !isset($tabDatePrise[date_format($date, "d-m-Y")])) {
                                $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "actioncomm` WHERE `datep` <= '" . date_format($date, "Y-m-d") . " 23:59:59' AND `datep2` >= '" . date_format($date, "Y-m-d") . "' AND fk_user_action =" . $this->idTech;
                                $sql = $this->db->query($requete);
                                if ($this->db->num_rows($sql) == 0) {
                                    $tabDatePrise[date_format($date, "d-m-Y")] = date_format($date, "d-m-Y");
                                    break;
                                }
                            }
                            $decale++;
                            $date = date_add($date, date_interval_create_from_date_string("1 day"));
                        }

                        $tabSite[$numSite][$type]['tabVisite'][$numVisiste]['date'] = date_format($date, "Y-m-d");
                        $this->sortie("<br/><h3>Visite " . ($numVisiste + 1) . "/" . count($site[$type]['tabVisite']) . " le " . date_format($date, "d-m-Y") . (($decale > 0) ? " Decaler de " . $decale . " jours" : "") . "</h3><br/>");
                        foreach ($visite['prod'] as $prod) {
                            $this->sortie(" - Matériel a visiter : " . $ligneFak->getInfoOneProductCli($prod['idProd']) . "<br/>");
                        }
                    }
                    $this->sortie("<br/><br/>");
                }
            }
        }
        $this->tabSite = $tabSite;
//        $this->creerFi();
    }

    function sortie($str) {
        if ($this->display)
            echo $str;
    }

    function creerFi() {
        $this->getTabSecteur();
        $ligneFak = new Synopsis_ContratLigne($this->db);
        $tabSite = $this->tabSite;
//        echo "<pre>";
//        print_r($tabSite);
        global $user;
        foreach ($tabSite as $numSite => $site) {

            foreach ($this->tabType as $type) {
                foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                    $di = new demandeInterv($this->db);
                    $di->date = $visite['date'];
                    $di->socid = $this->contrat->socid;
                    $di->author = $user->id;
                    $di->description = (($type == "visite") ? "Visite" : "Télémaintenance") . " " . ($numVisiste + 1) . "/" . count($site[$type]['tabVisite']);
                    $newId = $di->create();
                    $tech = new User($this->db);
                    $tech->fetch($this->idTech);
                    $di->fetch($newId);
                    $di->preparePrisencharge($tech);
                    if ($type == "visite")
                        $di->addline($newId, "Déplacement ", $visite['date'], "3600", 4, 1, 50);

                    foreach ($visite['prod'] as $prod) {
                        $product = new Product($this->db);
                        $product->fetch($prod['fkProdContrat']);
                        $di->addline($newId, $product->libelle . " \n Matériel a suivre " . $ligneFak->getInfoOneProductCli($prod['idProd']), $visite['date'], "3600", 1, 1, 95);
                    }
                    $di->valid($user);
                }
            }
        }

        $this->contrat->activeAllLigne();
    }

}

?>
