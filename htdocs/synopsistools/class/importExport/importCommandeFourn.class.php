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


        $idFactureDef = 138;
        $prefixe = "nzzzaaaauuuuud";
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
                
                
                
                
                
                
                if($data[$idT]["qty"]< 0){
                    $data[$idT]["qty"] = -BimpTools::stringToFloat($data[$idT]["qty"]);
                    $data[$idT]["qtyEnBl"] = -BimpTools::stringToFloat($data[$idT]["qtyEnBl"]);
                    $data[$idT]["pv"] = -BimpTools::stringToFloat($data[$idT]["pv"]);
                    $data[$idT]["pa"] = -BimpTools::stringToFloat($data[$idT]["pa"]);
                    $data[$idT]["qtyFact"] = -BimpTools::stringToFloat($data[$idT]["qtyFact"]);
                    $data[$idT]["qteNonFact"] = -BimpTools::stringToFloat($data[$idT]["qteNonFact"]);
                    foreach ($data[$idT]['br'] as $idT2 => $dataT)
                        $data[$idT]['br'][$idT2]['qteBlNonFact'] = -BimpTools::stringToFloat($data[$idT]['br'][$idT2]['qteBlNonFact']);
                }
                
                
                
                if($data[$idT]["qtyFact"] > 0){
                    $data[$idT]['br']['DEJAFACTURE'] = array('qteBlNonFact' => $data[$idT]["qtyFact"], 'facture' => 1);
                }
                    
            }


            $tabFinal2[$ref] = $data;
        }

        $commandes = $tabFinal2;
        $commandes = array($prefixe."CF-1906446"=> $tabFinal2[$prefixe."CF-1906446"]);
        echo "<pre>"; 
        print_r($commandes);
//        die;
          
        global $db;
        $bdb = new BimpDb($db);

        $errors = array();

        foreach ($commandes as $comm_ref => $lines) {

            $commande = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', array(
                        'ref' => $comm_ref
            ));

            if (!BimpObject::objectLoaded($commande)) {
                echo 'Création commande "' . $comm_ref . '": ';

                $id_entrepot = 0;
                $id_fourn = 0;
                $entrepot_ref = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['dep'])) {
                        $entrepot_ref = $line_data['dep'];
                        break;
                    }
                }

                if (!$entrepot_ref) {
                    echo BimpRender::renderAlerts('Entrepôt absent');
                    $errors[] = 'Commande ' . $comm_ref . ': entrepôt absent';
//                    $prob[] = str_replace($prefixe, "", $comm_ref);
                    continue;
                } else {
                    $id_entrepot = (int) $bdb->getValue('entrepot', 'rowid', '`ref` = \'' . $entrepot_ref . '\'');
                    if (!$id_entrepot) {
                        echo BimpRender::renderAlerts('Aucun entrepôt trouvé pour la réference "' . $entrepot_ref . '"');
                        $errors[] = 'Commande ' . $comm_ref . ': Aucun entrepôt trouvé pour la réference "' . $entrepot_ref . '"';
//                        $prob[] = str_replace($prefixe, "", $comm_ref);
                        continue;
                    }
                }

                $fourn_ref = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['soc'])) {
                        $fourn_ref = $line_data['soc'];
                        break;
                    }
                }

                $fourn_ref2 = '';

                foreach ($lines as $line_data) {
                    if (isset($line_data['soc2'])) {
                        $fourn_ref2 = $line_data['soc2'];
                        break;
                    }
                }

                $id_fourn = (int) $bdb->getValue('societe', 'rowid', '`code_fournisseur` = \'' . $fourn_ref . '\'');
                if (!$id_fourn) {
                    $id_fourn = (int) $bdb->getValue('societe', 'rowid', '`code_fournisseur` = \'' . $fourn_ref2 . '\'');
                    if (!$id_fourn) {
                        $id_fourn = (int) $bdb->getValue('commande_fournisseur', 'fk_soc', '`ref` = \'' . str_replace($prefixe, "", $comm_ref) . '\'');
                        if (!$id_fourn) {
                            $prob[] = str_replace($prefixe, "", $comm_ref);
                            echo BimpRender::renderAlerts('Aucun fournisseur trouvé pour la réference "' . $fourn_ref . '"');
                            $errors[] = 'Commande ' . $comm_ref . ': Aucun fournisseur trouvé pour la réference "' . $fourn_ref . '"';
                            continue;
                        }
                    }
                }

                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
                $comm_errors = $commande->validateArray(array(
                    'ref'               => $comm_ref,
                    'entrepot'          => $id_entrepot,
                    'fk_soc'            => $id_fourn,
                    'ef_type'           => 'C',
                    'fk_cond_reglement' => 1
                ));

                if (!count($comm_errors)) {
                    $warnings = array();
                    $comm_errors = $commande->create($warnings, true);
                }

                if (count($comm_errors)) {
                    echo '<span class="danger">[ECHEC]</span>';
                    echo BimpRender::renderAlerts($comm_errors);
                    $errors[] = BimpTools::getMsgFromArray($comm_errors, 'Commande ' . $comm_ref, 1);
//                    $prob[] = str_replace($prefixe, "", $comm_ref);
                    continue;
                } else {
                    echo '<span class="success">[OK]</span><br/>';
                    if ($bdb->update('commande_fournisseur', array(
                                'ref'             => $comm_ref,
                                'date_commande'   => date('Y-m-d'),
                                'fk_statut'       => 3,
                                'date_valid'      => date('Y-m-d'),
                                'date_approve'    => date('Y-m-d'),
                                'fk_user_valid'   => 1,
                                'fk_user_approve' => 1,
                                    ), '`rowid` = ' . (int) $commande->id) <= 0) {
                        echo ' <span class="danger">[ECHEC MAJ DES DONNEES] ' . $bdb->db->lasterror() . '</span>';
                        $errors[] = 'Commande ' . $comm_ref . ': ' . '[ECHEC MAJ DES DONNEES] ' . $bdb->db->lasterror();
//                        $prob[] = str_replace($prefixe, "", $comm_ref);
                        continue;
                    }
                }
                echo '<br/>';
            }

            if (BimpObject::objectLoaded($commande)) {
                echo '*** Traitement commande "' . $comm_ref . '" ***<br/>';
                $commande->checkLines();
                $commRec = null;

                $i = 0;
                foreach ($lines as $line_data) {
                    $line_data['qty'] = BimpTools::stringToFloat($line_data['qty']);
                    $line_data['pv'] = BimpTools::stringToFloat($line_data['pv']);
                    $line_data['pa'] = BimpTools::stringToFloat($line_data['pa']);
                    $line_data['qtyEnBl'] = BimpTools::stringToFloat($line_data['qtyEnBl']);
                    $line_data['qtyFact'] = BimpTools::stringToFloat($line_data['qtyFact']);

                    $i++;
                    echo $i . ' - ' . $line_data['ref'] . ': <br/>';

                    // Recherche produit: 
                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                'ref' => $line_data['ref']
                    ));

                    // Pour les fourn, on peut pas gérer les qtés facturées...
//                    $qty_fac = (float) $line_data['qtyEnBl'] - (float) $line_data['qteNonFact'];

                    if (!BimpObject::objectLoaded($product)) {
                        echo '<span class="danger">PRODUIT NON TROUVE: ' . $line_data['ref'] . '</span><br/>';
                        $errors[] = 'Commande ' . $comm_ref . ' - ligne ' . $i . 'PRODUIT NON TROUVE: ' . $line_data['ref'];
                        continue;
                    }

                    echo 'Création de la ligne: ';

                    $BimpLine = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                    $BimpLine->validateArray(array(
                        'id_obj' => (int) $commande->id,
                        'type'   => ObjectLine::LINE_PRODUCT
                    ));

                    $BimpLine->id_product = (int) $product->id;
                    $BimpLine->pu_ht = $line_data['pv'];
                    $BimpLine->tva_tx = (float) $product->getData('tva_tx');
                    $BimpLine->pa_ht = $line_data['pa'];
                    $BimpLine->qty = $line_data['qty'];

                    $warnings = array();
                    $line_errors = $BimpLine->create($warnings, true);

                    if (count($line_errors)) {
                        echo '<span class="danger">[ECHEC]</span><br/>';
                        echo BimpRender::renderAlerts($line_errors);
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Commande ' . $comm_ref . ' - Ligne ' . $i . ' (' . $line_data['ref'] . ') - échec création', 1);
                        continue;
                    } else {
                        echo '<span class="success">[OK]</span><br/>';
                    }

                    if (!BimpObject::objectLoaded($BimpLine)) {
                        echo '<span class="danger">BIMP LINE CORRESPONDANTE NON TROUVEE</span><br/>';
                        $errors[] = 'Commande ' . $comm_ref . ' - Ligne ' . $i . ' (' . $line_data['ref'] . ') - ligne correspondante non trouvée';
                    } else {
                        echo '<span class="success">LIGNE TROUVEE: ' . $BimpLine->id . '</span><br/>';

                        // Traitement qtés réceptionnées: 
                        if (isset($line_data['br']) && !empty($line_data['br'])) {
                            $total_diff = 0;
                            foreach ($line_data['br'] as $br_ref => $br_data) {
                                $br_data['qteBlNonFact'] = BimpTools::stringToFloat($br_data['qteBlNonFact']);
                                if (!(float) $br_data['qteBlNonFact']) {
                                    continue;
                                }

                                // Recherche de la réception:
                                $commRec = BimpCache::findBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', array(
                                            'id_commande_fourn' => $commande->id,
                                            'ref'               => $br_ref
                                ));

                                if (!BimpObject::objectLoaded($commRec)) {
                                    echo 'Insertion recept: "' . $br_ref . '"';

                                    $sql = 'SELECT MAX(num_reception) as num FROM ' . MAIN_DB_PREFIX . 'bl_commande_fourn_reception ';
                                    $sql .= 'WHERE `id_commande_fourn` = ' . (int) $commande->id;

                                    $result = $bdb->executeS($sql);
                                    if (isset($result[0])) {
                                        $num = (int) $result[0]->num + 1;
                                    } else {
                                        $num = 1;
                                    }

                                    $dataT = array(
                                        'id_commande_fourn' => (int) $commande->id,
                                        'id_entrepot'       => (int) $commande->getData('entrepot'),
                                        'num_reception'     => $num,
                                        'ref'               => $br_ref,
                                        'status'            => 1,
                                        'date_received'     => date('Y-m-d'),
                                        'id_user_resp'      => 1
                                            );
                                    if($br_data['facture'])
                                        $dataT['id_facture'] = $idFactureDef;
                                    $id_rec = $bdb->insert('bl_commande_fourn_reception', $dataT, true);

                                    if (!(int) $id_rec) {
                                        echo '<span class="danger">[ECHEC]</span> ';
                                        echo $bdb->db->lasterror();
                                        $errors[] = 'Commande ' . $comm_ref . ' - Echec insertion réception "' . $br_ref . '" - ' . $bdb->db->lasterror();
                                    } else {
                                        echo '<span class="success">[OK]</span>';
                                        $commRec = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeFournReception', (int) $id_rec);
                                        if (!BimpObject::objectLoaded($commRec)) {
                                            echo '<br/><span class="danger">ERREUR: La réception #' . $id_rec . ' n\'existe pas</span>';
                                            $errors[] = 'Commande ' . $comm_ref . ' - La réception #' . $id_rec . ' n\'existe pas';
                                        }
                                    }
                                    echo '<br/>';
                                }

                                if (!BimpObject::objectLoaded($commRec)) {
                                    continue;
                                }

                                $diff = (float) $br_data['qteBlNonFact'] - (float) $BimpLine->getReceivedQty((int) $commRec->id, true);
                                if ($diff > 0) {
                                    echo 'Ajout de ' . $diff . ' unité(s) réceptionnée(s): ';
                                    if (!BimpObject::objectLoaded($commRec)) {
                                        echo BimpRender::renderAlerts('Réception non trouvée');
                                    } else {
                                        $rec_data = $BimpLine->getReceptionData((int) $commRec->id);
                                        $rec_data['qty'] += $diff;
                                        $rec_data['qties'] = array(
                                            array(
                                                'qty'    => $rec_data['qty'],
                                                'pu_ht'  => $BimpLine->pu_ht,
                                                'tva_tx' => $BimpLine->tva_tx
                                            )
                                        );
                                        $rec_data['received'] = 1;

                                        $line_recs = $BimpLine->getData('receptions');
                                        $line_recs[(int) $commRec->id] = $rec_data;
                                        $line_errors = $BimpLine->updateField('receptions', $line_recs);
                                        if (count($line_errors)) {
                                            echo '<span class="danger">[ECHEC]</span>';
                                            echo BimpRender::renderAlerts($line_errors);
                                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Commande ' . $comm_ref . ': ligne ' . $i . ' échec màj qtés reception "' . $br_ref . '"', 1);
                                        } else {
                                            if (BimpObject::objectLoaded($commRec)) {
                                                $commRec->onLinesChange();
                                            }
                                            $total_diff += $diff;
                                            echo '<span class="success">[OK]</span>';
                                        }
                                    }
                                    echo '<br/>';
                                }
                            }
                        }

//                        // Traitement qtés facturées: 
//                        $qty_fac = (float) $line_data['qtyFact'];
//                        $diff = (float) $qty_fac - (float) $BimpLine->getBilledQty();
//                        if ($diff > 0) {
//                            echo 'Ajout de ' . $diff . ' unité(s) facturée(s): ';
////                            $fac_data = $BimpLine->getFactureData(-1);
//                            $fac_data = array(
//            'qty'        => 0,
//            'equipments' => array()
//        );
//                            $fac_data['qty'] += $diff;
//
//                            $factures = $BimpLine->getData('factures');
//                            $factures[-1] = $fac_data;
//
//                            $errors = $BimpLine->updateField('factures', $factures);
//                            if (count($errors)) {
//                                echo '[ECHEC]';
//                                echo BimpRender::renderAlerts($errors);
//                            } else {
//                                echo '<span class="success">[OK]</span>';
//                            }
//                            echo '<br/>';
//                        }
                    }

                    echo '<br/>';
                }

                // Check des status commande: 
                $commande->fetch($commande->id);
                $commande->checkReceptionStatus();
                $commande->checkInvoiceStatus();

                echo '<br/><br/>';
            }
        }

        if (count($errors)) {
            $str = count($errors) . ' erreurs' . "\n\n";

            $i = 0;
            foreach ($errors as $e) {
                $i++;
                $str .= $i . ' - ' . $e . "\n\n";
            }

            $dir = DOL_DATA_ROOT . '/bimpcore/imports_reports';
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            file_put_contents($dir . '/commandes_fourn_' . date('Y-m-d_H-i-s') . '.txt', $str);
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
