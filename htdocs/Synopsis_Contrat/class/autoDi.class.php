<?php

require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");

class autoDi {

    public $display = false;

    function autoDi($processDet = null, $idContrat = null) {
        global $db;
        $db->commit();
        $this->db = $db;
        $this->processDet = $processDet;

        if (!$idContrat)
            $idContrat = $processDet->element_refid;


        $this->idContrat = $idContrat;
        $this->contrat = new Synopsis_Contrat($this->db);
        $this->contrat->fetch($this->idContrat);
        $this->contrat->fetch_lines();

        $tabT = getElementElement("soc", "userTech", $this->contrat->socid);
        foreach ($tabT as $elem)
            $this->idTech[] = $elem['d'];
//        if(!isset($tabT[0]['d']))
//            $this->idTech = 0;
    }

    function verif() {
        $this->display = true;
        if ($this->processDet->statut != 3) {

            if (isset($_REQUEST['creerDiPrev']))
                $this->getTabSecteur();
            elseif (isset($_REQUEST['creerDiCur']))
                $this->getTabSecteur(1, "Cur");
            elseif (isset($_REQUEST['validContrat'])) {
                $this->processDet->validate("1");
                $this->processDet->fetch($this->processDet->id);
                $this->contrat->activeAllLigne();
                echo "Contrat activé.";
            } else {
                echo "<form action='' method='post'>";
                echo "<input type='submit' class='butAction' name='creerDiPrev' value='Di Préventive'><br/><br/>";
                echo "<input type='submit' class='butAction' name='creerDiCur' value='Di Curative'><br/><br/>";
                echo "<input type='submit' class='butAction' name='validContrat' value='Activer le contrat'><br/><br/>";
                echo "</form>";
            }
        } else {
            $sql = $this->db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv WHERE fk_contrat =" . $this->idContrat);
            while ($ligne = $this->db->fetch_object($sql)) {
                $di = new Synopsisdemandeinterv($this->db);
                $di->fetch($ligne->rowid);
                echo("<br/>" . $di->getNomUrl(1) . " : " . dol_print_date($di->date, 'day') . " " . $di->description);
            }
        }
    }

    function getTabSecteur($affiche = true, $typeDi = "") {
        $this->tabType = array('visite', 'tele');
        $tabDatePrise = array();
        $tabSite = array();
//      

        foreach ($this->contrat->lines as $lignes) {
            foreach ($this->tabType as $type) {
                $nbVisite = 0;
//                print_r($lignes->GMAO_Mixte);
                if ($type == 'visite')
                    $nbVisite = $lignes->GMAO_Mixte['nbVisiteAn' . $typeDi] * $lignes->qty;
                if ($type == 'tele' && $lignes->GMAO_Mixte['telemaintenance' . $typeDi] && $lignes->qty > 1)
                    $nbVisite = $lignes->qty;
                if ($nbVisite > 0) {
                    $lien = "ligne contrat ID : " . $lignes->id . " Nom : ".$lignes->description." .<br/><a href='" . DOL_URL_ROOT . "/Synopsis_Contrat/contratDetail.php?id=" . $lignes->id . "'>Cliquer ici pour réparer</a><br/><br/>";
                    if (count($lignes->tabProdCli) == 0) {
                        if ($affiche)
                            echo("Pas de produit client en lien avec ".$lien);

                        $lignes->tabProdCli = array(0);
                    }
                        if ($lignes->qty != count($lignes->tabProdCli))
                            echo "La quantité n'est pas egale au nombres de produits sous contrat. ".$lignes->qty."|". count($lignes->tabProdCli) . $lien . "<br/>";
//                    print_r($lignes);

                    foreach ($lignes->tabProdCli as $prod) {
                        $site = 0;
                        $tabT = getElementElement("site", "productCli", null, $prod);
                        if (isset($tabT[0]))
                            $site = $tabT[0]['s'];
                        if (!isset($tabSite[$site]))
                            $tabSite[$site] = array('visite' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()), 'tele' => array('prod' => array(), 'visiteMax' => 0, 'tabVisite' => array()));
                        $tabSite[$site][$type]['prod'][] = array('idProd' => $prod, 'nbVisite' => $nbVisite, 'fkProdContrat' => $lignes->fk_product);
                        if ($typeDi == "Cur")
                            $tabSite[$site][$type]['visiteMax'] += $nbVisite;
                        else
                        if ($tabSite[$site][$type]['visiteMax'] < $nbVisite)
                            $tabSite[$site][$type]['visiteMax'] = $nbVisite;
                    }
                }
            }
        }

        if (count($tabSite) == 0) { //Aucune visite.
            if ($this->processDet) {
                echo "Contrat activé.";
            }
        } else {

            if (count($this->idTech) == 0) {
                $tabT = getElementElement("commande", "contrat", null, $this->contrat->id);
                $lien = (isset($tabT[0]['s']) ? "<a href='" . DOL_URL_ROOT . "/Synopsis_PrepaCommande/prepacommande.php?id=" . $tabT[0]['s'] . "&mainmenu=commercial&leftmenu=orders#pppart6a'>corriger</a>" : "");
                die("Attention !!!!!!! Finalisation imposible : Pas de technicien définit pour le client " . $lien . "<br/>");
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
                $nomSite = ($numSite + 1);
                if ($numSite > 5)
                    $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $numSite);
                if ($this->db->num_rows($sql) > 0) {
                    $result = $this->db->fetch_object($sql);
                    $nomSite = $result->description;
                }
                foreach ($this->tabType as $type) {
                    $this->sortie("<h2>SITE " . $nomSite . ": " . $type . "</h2><br/>");

                    $tabTech = $this->idTech;
                    $sql = $this->db->query("SELECT Technicien as value FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_104 WHERE id =" . $numSite . " ");
                    if ($this->db->num_rows($sql) > 0) {
                        $tabTech = array();
                        $result = $this->db->fetch_object($sql);
                        if ($result->value > 0) {
                            $tabTech[] = $result->value;
                        }
                    }



                    foreach ($tabTech as $idTech) {
                        foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                            $delai = round(365 / count($site[$type]['tabVisite']) * ($type == "tele" ? (intval($numVisiste) + 0.5) : $numVisiste));
                            $date = date_add(new DateTime($this->db->idate($this->contrat->date_contrat)), date_interval_create_from_date_string($delai . " day"));
                            $decale = 0;
                            for ($i = 0; $i < 100; $i++) {
                                if (date_format($date, "w") != 0 && date_format($date, "w") != "6" && !isset($tabDatePrise[$idTech][date_format($date, "d-m-Y")])) {
                                    $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "actioncomm` WHERE `datep` <= '" . date_format($date, "Y-m-d") . " 23:59:59' AND `datep2` >= '" . date_format($date, "Y-m-d") . "' AND fk_user_action =" . $idTech;
                                    $sql = $this->db->query($requete);
                                    if ($this->db->num_rows($sql) == 0) {
                                        $tabDatePrise[$idTech][date_format($date, "d-m-Y")] = date_format($date, "d-m-Y");
                                        break;
                                    }
                                }
                                $decale++;
                                $date = date_add($date, date_interval_create_from_date_string("1 day"));
                            }

                            $tech = new User($this->db);
                            $tech->fetch($idTech);
                            $tabSite[$numSite][$type]['tabVisite'][$numVisiste]['date'] = date_format($date, "Y-m-d");
                            $this->sortie("<br/><h3>Visite " . ($numVisiste + 1) . "/" . count($site[$type]['tabVisite']) . " le " . date_format($date, "d-m-Y") . (($decale > 0) ? " Decaler de " . $decale . " jours" : "") . "</h3>" . $tech->getNomUrl(1) . "<br/><br/>");
                            foreach ($visite['prod'] as $prod) {
                                $this->sortie(" - Matériel a visiter : " . $ligneFak->getInfoOneProductCli($prod['idProd']) . "<br/>");
                            }
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
        $this->getTabSecteur(false);
        $ligneFak = new Synopsis_ContratLigne($this->db);
        $tabSite = $this->tabSite;
        global $user;
        foreach ($tabSite as $numSite => $site) {
            $nomSite = ($numSite + 1);
            $dureeDep = 0;
            $dureeInt = 2;
            if ($numSite > 5) {
                $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $numSite);
                if ($this->db->num_rows($sql) > 0) {
                    $result = $this->db->fetch_object($sql);
                    $nomSite = $result->description;
                    $sql = $this->db->query("SELECT Duree_deplacement__en_min_ as value FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_104 WHERE id = " . $numSite);
                    if ($this->db->num_rows($sql) > 0) {
                        $result = $this->db->fetch_object($sql);
                        $dureeDep = $result->value;
                    }
                }
            }
            foreach ($this->tabType as $type) {
                $tabTech = $this->idTech;
                $sql = $this->db->query("SELECT Technicien as value FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_104 WHERE id =" . $numSite . "");
                if ($this->db->num_rows($sql) > 0) {
                    $tabTech = array();
                    $result = $this->db->fetch_object($sql);
                    if ($result->value > 0) {
                        $tabTech[] = $result->value;
                    }
                }
                foreach ($tabTech as $idTech) {
                    foreach ($site[$type]['tabVisite'] as $numVisiste => $visite) {
                        $di = new Synopsisdemandeinterv($this->db);
                        $di->date = $visite['date'];
                        $di->socid = $this->contrat->socid;
                        $di->author = $user->id;
                        $di->fk_contrat = $this->idContrat;
                        $textSite = (count($tabSite) > 1 ? " SITE : " . $nomSite : "");
                        $di->description = (($type == "visite") ? "Visite" : "Télémaintenance") . " " . ($numVisiste + 1) . "/" . count($site[$type]['tabVisite']) . $textSite;
                        $newId = $di->create();
                        $di->setExtra(37, ($type == "visite" ? 60 : 61));
//                    die($di->description . $newId);
                        $tech = new User($this->db);
                        $tech->fetch($idTech);
                        $di->fetch($newId);
                        $di->preparePrisencharge($tech);
                        if ($type == "visite") {
                            if ($dureeDep == 0)
                                $dureeDep = 60;
                            $dureeInt = 4;
                            $idType = 20;
                            $di->addline($newId, "Déplacement ", $visite['date'], ("60" * $dureeDep), 4, 1, 50, 1);
                        }
                        else {
                            $idType = 21;
                        }

                        $nbProd = count($visite['prod']);
                        if ($nbProd < 3)
                            $dureeInt = 4 / $nbProd;
                        else
                            $dureeInt = 8 / $nbProd;
                        foreach ($visite['prod'] as $prod) {
                            $product = new Product($this->db);
                            $product->fetch($prod['fkProdContrat']);
                            $di->addline($newId, $product->libelle . " \n Matériel a suivre " . $ligneFak->getInfoOneProductCli($prod['idProd']), $visite['date'], (3600 * $dureeInt), $idType, 1, 100);
                        }
                        $di->valid($user);
                    }
                }
            }
        }

        $this->contrat->activeAllLigne();
    }

}

?>
