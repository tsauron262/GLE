<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/importCat.class.php";

class importCommande extends import8sens {
    var $tabCommande = array();

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "commEnCours/";
        $this->sepCollone = "	";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        
        $this->tabCommande[$ln['OpePcxCode']][] = $ln;
        
        
        
    }
    
    function go() {
        parent::go();
        
        
error_reporting(E_ERROR);
ini_set('display_errors', 1);

        $tabFinal = array();
        $i = 0;
        $errors = array();
        foreach($this->tabCommande as $ref => $tabLn){
            $numImport = "test123";
            
            //Création commande
            /*$ln1 = $tabLn[0];
            $ref= $numImport.$ref;
            $secteur = ($ln1['PcvFree24']!= "" ? $ln1['PcvFree24'] : 'C');
            $comm = BimpObject::getInstance("bimpcommercial", "Bimp_Commande");
            $tab = array("ref" => $ref, "fk_soc" => "2", "fk_cond_reglement"=>3, "date_commande"=>traiteDate($ln1['OpeDate'], "/"), "ef_type"=>$secteur, 'libelle' => $numImport);
            $errors = array_merge($errors, $comm->validateArray($tab));
            $errors = array_merge($errors, $comm->create()); */
            if(!count($errors)){
                foreach($tabLn as $dataLn){
                    //Création de la ligne 
                    /*$commLn = BimpObject::getInstance("bimpcommercial", "Bimp_CommandeLine");
                    $idProd = $this->getProdId($dataLn['ArtCode']);
                    $dataLn['OpePA'] = str_replace(",",".",$dataLn['OpePA']);
                    $dataLn['OpePUNet'] = str_replace(",",".",$dataLn['OpePUNet']);
                    if($idProd > 0){
                        $tab = array("id_obj"=>$comm->id, "type"=>1, "id_product"=>$idProd, "qty"=>$dataLn['PlvQteUS']);
                        $commLn->id_product = $idProd;
                        $qteTrans = $dataLn['PlvQteTr'];
                        $commLn->pu_ht = $dataLn['OpePUNet'];
                        $commLn->qty = $dataLn['PlvQteUS'] - $qteTrans;
                        $commLn->pa_ht = $dataLn['OpePA'];
                        $errors = array_merge($errors, $commLn->validateArray($tab));
                        $errors = array_merge($errors, $commLn->create());
//                        if($qteTrans > 0)
//                            echo "<br/>Partielle : ".$ref . "|".$qteTrans."<br/>";
                        echo "ok ".$ref."<br/>";
                    }
                    else
                        echo "<br/>Pas de prod !!! ".$dataLn['ArtCode']."<br/>";*/
                    
                    $tabFinal[$ref][] = array("ref"=>$dataLn['ArtCode'], "qty"=>$dataLn['PlvQteUS'], "qtyEnBl" =>$dataLn['PlvQteTr'], "pv" => $dataLn['OpeMontant'], "pa" => $dataLn['OpePA'], "qteBlNonFact" => 0);
                }
            }

            $i++;
            if($i > 20000){
                
            print_r($errors); echo "fin anticipé : ".$i."/".count($this->tabCommande);
            die;
            }
            
            
        }
        
        
        
        global $tempDataBl;
        
        foreach($tempDataBl as $ref => $data){
            $find = $find2= false;
            if(isset($tabFinal[$ref])){
                foreach($data['lignes'] as $ln){
                    foreach($tabFinal[$ref] as $idT => $ln2){
                        if($ln['PlvGArtCode'] == $ln2['ref']){//ligne identique
                            $find2 = true;
                            $qteTotal = $tabFinal[$ref][$idT]['qty'];
                            $qteEnBl = $tabFinal[$ref][$idT]['qtyEnBl'];
                            $qteEnBlNonFact = (isset($tabFinal[$ref][$idT]['qteBlNonFact'])? $tabFinal[$ref][$idT]['qteBlNonFact'] : 0);
                            
                            $newqteEnBlNnFact = $ln['PlvQteATran'] + $qteEnBlNonFact;
                            if(($newqteEnBlNnFact <= $qteEnBl && $qteEnBl <= $qteTotal) ||
                                   ($qteTotal < 0 && $newqteEnBlNnFact >= $qteEnBl && $qteEnBl >= $qteTotal)  ){
//                                if($ln['PlvPUNet'] == $ln2['pv'] || $ln['PlvPUNet'] == -$ln2['pv']){
                                    $find = true;
                                    $tabFinal[$ref][$idT]['qteBlNonFact'] = $newqteEnBlNnFact;
                                    $tabFinal[$ref][$idT]['pa'] = $ln['PlvPA'];
                                    $tabFinal[$ref][$idT]['pv'] = $ln['PlvPUNet'];
                                    break;
//                                }
//                                else{
//                                    echo "probléme de prix ".$ln['PlvPUNet']."¬".$ln2['pv'];
//                                }
                            }
                        }
                    }
                }
            }
            if($find2 && !$find){
                                echo "ilogic ".$ref."<br/>";
            }
            if(!$find){
                foreach($data['lignes'] as $lnT){
                    $qty = $lnT['PlvQteATran'];
                    $lnTemp = array("ref"=>$lnT['PlvGArtCode'], "qty"=>$qty, "qtyEnBl"=>$qty, "qteBlNonFact" => $qty, "pv"=>$lnT['PlvPUNet'], "pa"=>$lnT['PlvPA']);
                    $tabFinal[$ref][] = $lnTemp;
                }
                
//                echo "<pre>";print_r($tabFinal[$ref]);die;
            }
        }
        
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
        }
        
        // Données de TEST: 
        $commandes = array(
            'TEST_IMPORT_COMM_4' => array(
                array(
                    'ref'          => 'PRODSERIAL',
                    'qty'          => 10,
                    'qtyEnBl'      => 5,
                    'qteBlNonFact' => 2,
                    'pv'           => 0,
                    'pa'           => 0,
                    'dep'          => 'ACY',
                    'soc'          => 'CLGLE011669'
                ),
                array(
                    'ref'          => 'TES-PRODSERIAL2',
                    'qty'          => 10,
                    'qtyEnBl'      => 5,
                    'qteBlNonFact' => 2,
                    'pv'           => 0,
                    'pa'           => 0,
                ),
                array(
                    'ref'          => 'SERV-BOUT01',
                    'qty'          => 10,
                    'qtyEnBl'      => 5,
                    'qteBlNonFact' => 2,
                    'pv'           => 0,
                    'pa'           => 0,
                ),
                array(
                    'ref'          => 'APP-3A019F/A',
                    'qty'          => 10,
                    'qtyEnBl'      => 5,
                    'qteBlNonFact' => 2,
                    'pv'           => '445,01',
                    'pa'           => '400',
                ),
            )
        );

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

                if (!$client_ref) {
                    echo BimpRender::renderAlerts('Client absent');
                    continue;
                } else {
                    $id_client = (int) $bdb->getValue('societe', 'rowid', '`code_client` = \'' . $client_ref . '\'');
                    if (!$id_client) {
                        echo BimpRender::renderAlerts('Aucun client trouvé pour la réference "' . $client_ref . '"');
                        continue;
                    }
                }

                $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                $errors = $commande->validateArray(array(
                    'ref'               => $comm_ref,
                    'entrepot'          => $id_entrepot,
                    'fk_soc'            => $id_client,
                    'ef_type'           => 'C',
                    'validComm'         => 1,
                    'validFin'          => 1,
                    'date_commande'     => date('Y-m-d'),
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
                                'fk_statut'     => 1,
                                'date_valid'    => date('Y-m-d'),
                                'fk_user_valid' => 1
                            )) <= 0) {
                        echo ' <span class="danger">[ECHEC MAJ DES DONNEES] ' . $bdb->db->lasterror() . '</span>';
                        continue;
                    }
                }
            }

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
                    $line_data['qty'] = self::stringToFloat($line_data['qty']);
                    $line_data['pv'] = self::stringToFloat($line_data['pv']);
                    $line_data['pa'] = self::stringToFloat($line_data['pa']);
                    $line_data['qtyEnBl'] = self::stringToFloat($line_data['qtyEnBl']);
                    $line_data['qteBlNonFact'] = self::stringToFloat($line_data['qteBlNonFact']);

                    $i++;
                    echo $i . ' - ' . $line_data['ref'] . ': <br/>';

                    // Recherche BimpLine correspondante: 
                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                'ref' => $line_data['ref']
                    ));
                    $qty_fac = (float) $line_data['qtyEnBl'] - (float) $line_data['qteBlNonFact'];

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
                                'type'   => ObjectLine::LINE_PRODUCT
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
                            if ((float) $line_data['qtyEnBl']) {
                                $diff = (float) $line_data['qtyEnBl'] - (float) $BimpLine->getShippedQty(null, true);
                                if ($diff > 0) {
                                    if (is_null($commShipment)) {
                                        echo 'Insertion expé: ';

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
                                            echo '<span class="success">[OK]</span>';

                                            if ($product->isTypeProduct() && $BimpLine->getFullQty() > 0) {
                                                // Passage des résas "à traiter" à "expédié" pour qté $diff. 
                                                echo '<br/>Retrait de ' . $diff . ' qté réservée(s) à traiter: ';
                                                $line_reservations = $BimpLine->getReservations('status', 'asc', 0);
                                                $remain_qty = (int) $diff;

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
                                                    echo 'Ajout de ' . $diff . ' qté aux réservations "expédiées": ';
                                                    $errors = array();
                                                    foreach ($line_reservations as $res) {
                                                        $errors = $res->updateField('qty', (int) $res->getData('qty') + (int) $diff);
                                                        if (count($errors)) {
                                                            echo '<span class="danger">[ECHEC MAJ RES #' . $res->id . ']</span>';
                                                            echo BimpRender::renderAlerts($errors);
                                                        } else {
                                                            echo '<span class="success">[OK]</span>';
                                                        }
                                                        echo '<br/>';
                                                    }
                                                } else {
                                                    echo 'Insertion d\'une réservation "Expédiée" pour ' . $diff . ' unité(s): ';
                                                    $id_res = (int) $bdb->insert('br_reservation', array(
                                                                'ref'                     => $ref_resrvations,
                                                                'id_entrepot'             => (int) $commande->getData('entrepot'),
                                                                'type'                    => 1,
                                                                'status'                  => 300,
                                                                'id_product'              => (int) $product->id,
                                                                'id_commande_client'      => (int) $commande->id,
                                                                'id_commande_client_line' => (int) $BimpLine->id,
                                                                'qty'                     => $diff,
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
                                }
                            }

                            // Traitement qtés facturées: 
                            $qty_fac = (float) $line_data['qtyEnBl'] - (float) $line_data['qteBlNonFact'];
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

                if (BimpObject::objectLoaded($commShipment)) {
                    // Màj des totaux expédition: 
                    $commShipment->onLinesChange();
                }

                // Check des status commande: 
                $commande->checkLogistiqueStatus();
                $commande->checkShipmentStatus();
                $commande->checkInvoiceStatus();

                echo '<br/><br/>';
            }
        }
        
//        echo "<pre>";
//        print_r($errors);
//        print_r($tabFinal);
//        die("fin normal");
    }
    
    function getProdId($ref){
        $sql = $this->db->query("SELECT `rowid` FROM `llx_product` WHERE `ref` LIKE '".$ref."'");
        if($this->db->num_rows($sql)>0){
            $ln = $this->db->fetch_object($sql);
            return $ln->rowid;
        }
        return "";
    }

}


function traiteDate($date, $delim= "-"){
    $tab = explode(" ", $date);
    $tab2 = explode($delim, $tab[0]);
    $date = $tab2[2] .$delim. $tab2[1] .$delim. $tab2[0] ." ". $tab[1];
    return $date;
}
