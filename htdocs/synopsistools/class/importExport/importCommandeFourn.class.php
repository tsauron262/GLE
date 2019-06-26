<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/importCat.class.php";

class importCommandeFourn extends import8sens {

    var $tabCommande = array();

    public function __construct($db) {
        $this->mode = 2;
        parent::__construct($db);
        $this->path .= "commFournEnCours/";
        $this->sepCollone = "	";
    }

//    function traiteLn($ln) {
//        $this->tabResult["total"] ++;
//        
//        $this->tabCommande[$ln['PlaCodePca']][] = $ln;
//        
//        
//        
//    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;

        $ref = "";
        $newLines = array();
        foreach ($ln['lignes'] as $ln2) {
            if ($ln2['PlaCodePca'] != "")
                $ref = $ln2['PlaCodePca'];
            if ($ln2['PlaQteUA'] != 0 && $ln2['PlaQteUA'] != "")
                $newLines[] = $ln2;
        }

        if ($ref != "" && count($newLines) > 0) {
            if (isset($this->tabCommande[$ref]['lignes'])) {
                foreach ($newLines as $lnT)
                    $this->tabCommande[$ref]['lignes'][] = $lnT;
            } else {
                $ln['lignes'] = $newLines;
                $this->tabCommande[$ref] = $ln;
            }
//            echo "<pre>";print_r($this->tabCommande[$ref]);
        }
    }

    function go() {
        parent::go();


        error_reporting(E_ERROR);
        ini_set('display_errors', 1);

        $tabFinal = array();
        foreach ($this->tabCommande as $ref => $tabLn) {
                foreach ($tabLn["lignes"] as $dataLn) {
                        $tabFinal[$ref][] = array("ref" => ($dataLn['PlaGArtCode']? $dataLn['PlaGArtCode']: $dataLn['PlaCode']), "qty" => $dataLn['PlaQteUA'], "qtyEnBl" => $dataLn['PlaQteTr'], "soc" => $dataLn["PlaGFouCode"], "pv" => $dataLn['PlaPUNet'], "pa" => $dataLn['PlaPUNet'], 'dep' => $dataLn['PlaADepCode'], 'soc2' => $dataLn['CliFree3']);
                }
        }



        global $tempDataBl;

        foreach ($tempDataBl as $ref => $data) {
            foreach ($data['lignes'] as $ln) {
                $find = $find2 = false;
                if (isset($tabFinal[$ref])) {
                    foreach ($tabFinal[$ref] as $idT => $ln2) {
                        if ($ln['PlaPPlaCodePca'] == $ln2['ref']) {//ligne identique
                            $find2 = true;
                            $qteTotal = $tabFinal[$ref][$idT]['qty'];
                            $qteEnBl = $tabFinal[$ref][$idT]['qtyEnBl'];
                            $qteEnBlNonFact = (isset($tabFinal[$ref][$idT]['qteBlNonFact']) ? $tabFinal[$ref][$idT]['qteBlNonFact'] : 0);

                            $newqteEnBlNnFact = $ln['PlvQteATran'] + $qteEnBlNonFact;
                            if (($newqteEnBlNnFact <= $qteEnBl && $qteEnBl <= $qteTotal) ||
                                    ($qteTotal < 0 && $newqteEnBlNnFact >= $qteEnBl && $qteEnBl >= $qteTotal) ||
                                    ($qteTotal == "nc" && $qteEnBl == "nc")) {
                                $find = true;
                                $tabFinal[$ref][$idT]['br'][$ln['PlaCodePca']]['qteBlNonFact'] = $newqteEnBlNnFact;
                                $tabFinal[$ref][$idT]['pa'] = $ln['PlvPA'];
                                $tabFinal[$ref][$idT]['pv'] = $ln['PlaPUNet'];
                                break;
                            }
                        }
                    }
                }
                if ($find2 && !$find) {
                    echo "ilogic " . $ref . "<br/>";
                }
                if (!$find) {

                    $qty = $ln['PlaQteUA'] - $ln['PlaQteTr'];
                    $lnTemp = array("ref" => $ln['PlaGArtCode'], "soc" => $ln['PlaGFouCode'], 'dep' => $data['PcaADepCode'], "qty" => "nc", "qtyEnBl" => "nc", "pv" => $ln['PlaPUNet'], "pa" => $ln['PlvPA']);
                    $lnTemp['br'][$ln['PlaCodePca']]['qteBlNonFact'] = $qty;
                    $tabFinal[$ref][] = $lnTemp;
                }
            }
        }

        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }


        $prefixe = "zzzaa";
        $tabFinal2 = array();
        foreach ($tabFinal as $ref => $data) {
            $ref = $prefixe . $ref;
            foreach ($data as $idT => $line) {

                $nbBlNonFact = 0;
                foreach ($data[$idT]['br'] as $dataT)
                    $nbBlNonFact += $dataT["qteBlNonFact"];
                if ($data[$idT]["qty"] == "nc") {
                    $data[$idT]["qty"] = $data[$idT]["qtyEnBl"] = $data[$idT]["qteNonFact"] = $nbBlNonFact;
                    $data[$idT]["qtyFact"] = 0;
                }
                else{
                    $nbFact = $data[$idT]["qtyEnBl"] - $nbBlNonFact;
                    $data[$idT]["qtyFact"] = $nbFact;
                    $data[$idT]["qteNonFact"] = $data[$idT]["qty"] - $nbFact;
                }
                    
            }


            $tabFinal2[$ref] = $data;
        }

        $commandes = $tabFinal2;
//        $commandes = array($prefixe."CO1904-8050"=> $tabFinal2[$prefixe."CO1904-8050"]);
        echo "<pre>"; print_r($commandes );die;
          
        global $db;
        $bdb = new BimpDb($db);

        foreach ($commandes as $comm_ref => $lines) {
            $commande = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Commande', array(
                        'ref' => $comm_ref
            ));

            if (!BimpObject::objectLoaded($commande)) {
                echo 'Création commande "' . $comm_ref . '": ';

                $id_entrepot = 0;
                $id_client = 0;

                $entrepot_ref = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['dep'])) {
                        $entrepot_ref = $line_data['dep'];
                        break;
                    }
                }

                if (!$entrepot_ref) {
                    echo BimpRender::renderAlerts('Entrepôt absent');
                    continue;
                } else {
                    $id_entrepot = (int) $bdb->getValue('entrepot', 'rowid', '`ref` = \'' . $entrepot_ref . '\'');
                    if (!$id_entrepot) {
                        echo BimpRender::renderAlerts('Aucun entrepôt trouvé pour la réference "' . $entrepot_ref . '"');
                        continue;
                    }
                }

                $client_ref = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['soc'])) {
                        $client_ref = $line_data['soc'];
                        break;
                    }
                }

                $client_ref2 = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['soc2'])) {
                        $client_ref2 = $line_data['soc2'];
                        break;
                    }
                }

                $id_client = (int) $bdb->getValue('societe', 'rowid', '`code_client` = \'' . $client_ref . '\'');
                if (!$id_client) {
                    $id_client = (int) $bdb->getValue('societe', 'rowid', '`code_client` = \'' . $client_ref2 . '\'');
                    if (!$id_client) {
                        $id_client = (int) $bdb->getValue('commande', 'fk_soc', '`ref` = \'' . str_replace($prefixe, "", $comm_ref) . '\'');
                        if (!$id_client) {
                            $prob[] = str_replace($prefixe, "", $comm_ref);
                            echo BimpRender::renderAlerts('Aucun client trouvé pour la réference "' . $client_ref . '"');
                            continue;
//                                $id_client = 340002;
                        }
                    }
                }

                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                $errors = $commande->validateArray(array(
                    'ref' => $comm_ref,
                    'entrepot' => $id_entrepot,
                    'fk_soc' => $id_client,
                    'ef_type' => 'C',
                    'validComm' => 1,
                    'validFin' => 1,
                    'date_commande' => date('Y-m-d'),
                    'fk_cond_reglement' => 1
                ));

                if (!count($errors)) {
                    $warnings = array();
                    $errors = $commande->create($warnings, true);
                }

                if (count($errors)) {
                    echo '<span class="danger">[ECHEC]</span>';
                    echo BimpRender::renderAlerts($errors);
                    continue;
                } else {
                    echo '<span class="success">[OK]</span>';
                    if ($bdb->update('commande', array(
//                                'ref'           => $comm_ref,
                                'fk_statut' => 1,
                                'date_valid' => date('Y-m-d'),
                                'fk_user_valid' => 1
                            )) <= 0) {
                        echo ' <span class="danger">[ECHEC MAJ DES DONNEES] ' . $bdb->db->lasterror() . '</span>';
                        continue;
                    }
                }

                echo '<br/>';
            }

            
//            continue;//vire
            if (BimpObject::objectLoaded($commande)) {
                echo '*** Traitement commande "' . $comm_ref . '" ***<br/>';
                $commande->checkLines();
                $commShipment = null;

                $id_user_resp = (int) $commande->getData('id_user_resp');
                if (!$id_user_resp || !$commande->isLogistiqueActive()) {

                    // Activation de la logistique: 

                    echo 'Activation de la logistique: ';
                    if (!(int) $commande->getData('id_user_resp')) {
                        $commande->updateField('id_user_resp', (int) $id_user_resp);
                    }
                    if (!count($errors)) {
                        $errors = $commande->updateField('logistique_status', 1);
                    }

                    if (count($errors)) {
                        echo BimpRender::renderAlerts($errors);
                        echo '<br/>';
                        continue;
                    }

                    $errors = $commande->createReservations();
                    if (count($errors)) {
                        echo '[ECHEC CREATION DES RESERVATIONS]';
                        echo BimpRender::renderAlerts($errors);
                        echo '<br/>';
                        continue;
                    }

                    echo '<span class="success">[OK]</span><br/>';
                } else {
                    echo '<span class="success">Logistique OK</span><br/>';
                }
                echo '<br/>';

                $i = 0;
                foreach ($lines as $line_data) {
                    $line_data['qty'] = BimpTools::stringToFloat($line_data['qty']);
                    $line_data['pv'] = BimpTools::stringToFloat($line_data['pv']);
                    $line_data['pa'] = BimpTools::stringToFloat($line_data['pa']);
                    $line_data['qtyEnBl'] = BimpTools::stringToFloat($line_data['qtyEnBl']);
                    $line_data['qteNonFact'] = BimpTools::stringToFloat($line_data['qteNonFact']);

                    $i++;
                    echo $i . ' - ' . $line_data['ref'] . ': <br/>';

                    // Recherche BimpLine correspondante: 
                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                'ref' => $line_data['ref']
                    ));
                    $qty_fac = (float) $line_data['qtyEnBl'] - (float) $line_data['qteNonFact'];

                    if (BimpObject::objectLoaded($product)) {
                        $where = ' fk_commande = ' . (int) $commande->id;
                        $where .= ' AND fk_product = ' . (int) $product->id;
                        $where .= ' AND qty = ' . (float) $line_data['qty'];

                        $rows = $bdb->getRows('commandedet', $where);

                        if (is_null($rows) || empty($rows)) {
                            // Si aucune ligne trouvée: 
                            echo 'Création de la ligne: ';

                            $BimpLine = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
                            $BimpLine->validateArray(array(
                                'id_obj' => (int) $commande->id,
                                'type' => ObjectLine::LINE_PRODUCT
                            ));

                            $BimpLine->id_product = (int) $product->id;
                            $BimpLine->pu_ht = $line_data['pv'];
                            $BimpLine->pa_ht = $line_data['pa'];
                            $BimpLine->qty = $line_data['qty'];

                            $warnings = array();
                            $errors = $BimpLine->create($warnings, true);

                            if (count($errors)) {
                                echo '<span class="danger">[ECHEC]</span><br/>';
                                echo BimpRender::renderAlerts($errors);
                                continue;
                            } else {
                                echo '<span class="success">[OK]</span><br/>';
                            }
                        } elseif (count($rows) > 1) {
                            // Si plusieurs lignes trouvées: 
                            foreach ($rows as $r) {
                                $BimpLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                                            'id_line' => (int) $r->rowid
                                ));
                                if (((float) $line_data['qtyEnBl'] && !(float) $BimpLine->getShippedQty(null, true)) ||
                                        ($qty_fac && !(float) $BimpLine->getBilledQty())) {
                                    // On considère que la ligne n'a pas été traité: 
                                    break;
                                }

                                unset($BimpLine);
                                $BimpLine = null;
                            }
                        } else {
                            // Si une seule ligne trouvée: 
                            $BimpLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                                        'id_line' => (int) $rows[0]->rowid
                            ));
                        }

                        if (!BimpObject::objectLoaded($BimpLine)) {
                            echo '<span class="danger">BIMP LINE CORRESPONDANTE NON TROUVEE</span><br/>';
                        } else {
                            echo '<span class="success">LIGNE TROUVEE: ' . $BimpLine->id . '</span><br/>';

                            $BimpLine->checkReservations();

                            // Traitement qtés expédiées: 
                            if (isset($line_data['br']) && !empty($line_data['br'])) {
                                $total_diff = 0;
                                foreach ($line_data['br'] as $bl_ref => $bl_data) {
                                    $bl_data['qteBlNonFact'] = BimpTools::stringToFloat($bl_data['qteBlNonFact']);
                                    if (!(float) $bl_data['qteBlNonFact']) {
                                        continue;
                                    }

                                    // Recherche de l'expé:
                                    $commShipment = BimpCache::findBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', array(
                                                'id_commande_client' => $commande->id,
                                                'ref'                => $bl_ref
                                    ));

                                    if (!BimpObject::objectLoaded($commShipment)) {
                                        echo 'Insertion expé: "' . $bl_ref . '"';

                                        $sql = 'SELECT MAX(num_livraison) as num FROM ' . MAIN_DB_PREFIX . 'br_commande_shipment ';
                                        $sql .= 'WHERE `id_commande_client` = ' . (int) $commande->id;

                                        $result = $bdb->executeS($sql);
                                        if (isset($result[0])) {
                                            $num = (int) $result[0]->num + 1;
                                        } else {
                                            $num = 1;
                                        }

                                        $id_shipment = $bdb->insert('br_commande_shipment', array(
                                            'id_commande_client' => (int) $commande->id,
                                            'id_entrepot'        => (int) $commande->getData('entrepot'),
                                            'num_livraison'      => $num,
                                            'ref'                => $bl_ref,
                                            'status'             => 2,
                                            'date_shipped'       => date('Y-m-d'),
                                            'id_contact'         => 0,
                                            'signed'             => 1,
                                            'id_user_resp'       => $id_user_resp
                                                ), true);

                                        if (!(int) $id_shipment) {
                                            echo '<span class="danger">[ECHEC]</span> ';
                                            echo $bdb->db->lasterror();
                                        } else {
                                            echo '<span class="success">[OK]</span>';
                                            $commShipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_shipment);
                                            if (!BimpObject::objectLoaded($commShipment)) {
                                                echo '<br/><span class="danger">ERREUR: L\'expé #' . $id_shipment . ' n\'existe pas</span>';
                                            }
                                        }
                                        echo '<br/>';
                                    }

                                    if (!BimpObject::objectLoaded($commShipment)) {
                                        continue;
                                    }

                                    $diff = (float) $bl_data['qteBlNonFact'] - (float) $BimpLine->getShippedQty((int) $commShipment->id, true);
                                    if ($diff > 0) {
                                        echo 'Ajout de ' . $diff . ' unité(s) expédiée(s): ';
                                        if (!BimpObject::objectLoaded($commShipment)) {
                                            echo BimpRender::renderAlerts('Expédition non trouvée');
                                        } else {
                                            $shipment_data = $BimpLine->getShipmentData((int) $commShipment->id);
                                            $shipment_data['qty'] += $diff;
                                            $shipment_data['shipped'] = 1;

                                            $line_shipments = $BimpLine->getData('shipments');
                                            $line_shipments[(int) $commShipment->id] = $shipment_data;
                                            $errors = $BimpLine->updateField('shipments', $line_shipments);
                                            if (count($errors)) {
                                                echo '<span class="danger">[ECHEC]</span>';
                                                echo BimpRender::renderAlerts($errors);
                                            } else {
                                                if (BimpObject::objectLoaded($commShipment)) {
                                                    $commShipment->onLinesChange();
                                                }
                                                $total_diff += $diff;
                                                echo '<span class="success">[OK]</span>';
                                            }
                                        }
                                        echo '<br/>';
                                    }
                                }

                                if ($total_diff) {
                                    if ($product->isTypeProduct() && $BimpLine->getFullQty() > 0) {
                                        // Passage des résas "à traiter" à "expédié" pour qté $diff. 
                                        echo '<br/>Retrait de ' . $total_diff . ' qté réservée(s) à traiter: ';
                                        $line_reservations = $BimpLine->getReservations('status', 'asc', 0);
                                        $remain_qty = (int) $total_diff;

                                        $errors = array();
                                        $ref_resrvations = '';
                                        foreach ($line_reservations as $res) {
                                            $ref_resrvations = $res->getData('ref');
                                            if ($remain_qty > 0) {
                                                $new_qty = (int) $res->getData('qty') - $remain_qty;
                                                if ($new_qty < 0) {
                                                    $remain_qty = abs($new_qty);
                                                    $new_qty = 0;
                                                } else {
                                                    $remain_qty = 0;
                                                }

                                                if ($new_qty > 0) {
                                                    $errors = $res->updateField('qty', $new_qty);
                                                    if (count($errors)) {
                                                        echo '<span class="danger">[ECHEC MAJ RES #' . $res->id . ']</span>';
                                                        echo BimpRender::renderAlerts($errors);
                                                        break;
                                                    }
                                                } else {
                                                    $del_warnings = array();
                                                    $errors = $res->delete($del_warnings, true);
                                                }
                                            }
                                        }
                                        if (!count($errors)) {
                                            if (!$remain_qty) {
                                                echo '<span class="success">[OK]</span>';
                                            } else {
                                                echo '<span class="danger">[ERREUR] ' . $remain_qty . ' unité(s) n\'ont pas été traitée(s)</span>';
                                            }
                                        }
                                        echo '<br/>';

                                        $line_reservations = $BimpLine->getReservations('status', 'asc', 300);
                                        if (!empty($line_reservations)) {
                                            echo 'Ajout de ' . $total_diff . ' qté aux réservations "expédiées": ';
                                            $errors = array();
                                            foreach ($line_reservations as $res) {
                                                $errors = $res->updateField('qty', (int) $res->getData('qty') + (int) $total_diff);
                                                if (count($errors)) {
                                                    echo '<span class="danger">[ECHEC MAJ RES #' . $res->id . ']</span>';
                                                    echo BimpRender::renderAlerts($errors);
                                                } else {
                                                    echo '<span class="success">[OK]</span>';
                                                }
                                                echo '<br/>';
                                            }
                                        } else {
                                            echo 'Insertion d\'une réservation "Expédiée" pour ' . $total_diff . ' unité(s): ';
                                            $id_res = (int) $bdb->insert('br_reservation', array(
                                                        'ref'                     => $ref_resrvations,
                                                        'id_entrepot'             => (int) $commande->getData('entrepot'),
                                                        'type'                    => 1,
                                                        'status'                  => 300,
                                                        'id_product'              => (int) $product->id,
                                                        'id_commande_client'      => (int) $commande->id,
                                                        'id_commande_client_line' => (int) $BimpLine->id,
                                                        'qty'                     => $total_diff,
                                                        'user_create'             => (int) $id_user_resp
                                            ));

                                            if (!$id_res) {
                                                echo '<span class="danger">[ECHEC] ' . $bdb->db->lasterror() . '</span>';
                                            } else {
                                                echo '<span class="success">[OK]</span>';
                                            }
                                            echo '<br/>';
                                        }
                                    }
                                }
                            }

                            // Traitement qtés facturées: 
                            $qty_fac = (float) $line_data['qtyFact'];
                            $diff = (float) $qty_fac - (float) $BimpLine->getBilledQty();
                            if ($diff > 0) {
                                echo 'Ajout de ' . $diff . ' unité(s) facturée(s): ';
                                $fac_data = $BimpLine->getFactureData(-1);
                                $fac_data['qty'] += $diff;

                                $factures = $BimpLine->getData('factures');
                                $factures[-1] = $fac_data;

                                $errors = $BimpLine->updateField('factures', $factures);
                                if (count($errors)) {
                                    echo '[ECHEC]';
                                    echo BimpRender::renderAlerts($errors);
                                } else {
                                    echo '<span class="success">[OK]</span>';
                                }
                                echo '<br/>';
                            }
                        }
                    } else {
                        echo '<span class="danger">PRODUIT NON TROUVE: ' . $line_data['ref'] . '</span><br/>';
                    }

                    echo '<br/>';
                }

                // Check des status commande: 
                $commande->checkLogistiqueStatus();
                $commande->checkShipmentStatus();
                $commande->checkInvoiceStatus();

                echo '<br/><br/>';
            }
        }
        
        echo "fin<br/>".implode("+", $prob);
        
    }

    function getProdId($ref) {
        $sql = $this->db->query("SELECT `rowid` FROM `llx_product` WHERE `ref` LIKE '" . $ref . "'");
        if ($this->db->num_rows($sql) > 0) {
            $ln = $this->db->fetch_object($sql);
            return $ln->rowid;
        }
        return "";
    }

}