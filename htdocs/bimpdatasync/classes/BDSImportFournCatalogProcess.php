<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportProcess.php';

class BDSImportFournCatalogProcess extends BDSImportProcess
{

    /* Champs pris en charge pour les prix fournisseurs: 
     * 
     *  ref_prod: Ref produit (telle que dans la table product) 
     *  brand: Marque (Fabricant) 
     *  ean: Code ean produit
     *  ref_manuf: ref fabricant (celle qui apparaît dans la ref_prod après le préfixe de la marque) 
     *  ref_fourn: ref fournisseur (telle que dans la table produt_fournisseur_price)
     *  pa_ht 
     *  pu_ht
     *  pu_ttc
     *  tva_tx
     *  stock : stock chez le fourn. (fac.) 
     *  lib: Libellé produit.
     *  brand: marque.
     *  is_actif: produit actif. 
     *  is_sleep: produit inactif
     *  is_delete: produit supprimé
     */

    public static $brands_exceptions = array(
        'V7'           => 'VSE',
        'GÉNÉRIQUE-HP' => 'HEW',
        'HP'           => 'HEW'
    );
    protected $refProdToIdProd = array();
    protected $refProdFournToIdPriceFourn = array();
    protected $infoProdBimp = array();
    protected $fournPrices = array();
    public $local_dir = '';
    public $ftp_dir = '';
    public $updateSql = true;
    public static $price_keys = array();
    public $pfp_instance = null;
    public $prod_import_instance = null;

    public function __construct(BDS_Process $process, $options = array(), $references = array())
    {
        parent::__construct($process, $options, $references);

        if (isset($this->params['local_dir']) && $this->params['local_dir']) {
            $this->local_dir = PATH_TMP . '/' . $this->params['local_dir'];

            if (!is_dir($this->local_dir)) {
                BimpTools::makeDirectories($this->params['local_dir'], PATH_TMP);
            }
        }

        if (isset($this->params['ftp_dir'])) {
            $this->ftp_dir = $this->params['ftp_dir'];
        }

        if (!isset($this->params['delimiter'])) {
            $this->params['delimiter'] = ';';
        }

        if (isset($this->options['update_sql'])) {
            $this->updateSql = (int) $this->options['update_sql'];
        }

        $this->pfp_instance = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
        $this->prod_import_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product_Ldlc');
    }

    // Opérations:

    public function initOperation($id_operation, &$errors)
    {
        // check des params: 

        if (!isset($this->params['id_fourn']) || !(int) $this->params['id_fourn']) {
            $errors[] = 'Paramètre "ID du fournisseur" absent';
            $this->params_ok = false;
        }

        if (!$this->local_dir) {
            $errors[] = 'Paramètre "Dossier local" absent';
            $this->params_ok = false;
        }

        return parent::initOperation($id_operation, $errors);
    }

    public function initFtpTest(&$data, &$errors = array())
    {
        BimpObject::loadClass('bimpdatasync', 'BDS_Report');

        $host = BimpTools::getArrayValueFromPath($this->params, 'ftp_host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'ftp_login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'ftp_pwd', '');
        $port = BimpTools::getArrayValueFromPath($this->params, 'ftp_port', 21);
        $passive = (int) BimpTools::getArrayValueFromPath($this->params, 'ftp_passive', 0);

        if (!$host) {
            $errors[] = 'Hôte absent';
        }

        if (!$login) {
            $errors[] = 'Login absent';
        }

        if (!$pword) {
            $errors[] = 'Mot de passe absent';
        }

        if (!count($errors)) {
            $ftp = $this->ftpConnect($host, $login, $pword, $port, $passive, $errors);

            if ($ftp !== false) {
                $data['result_html'] = BimpRender::renderAlerts('Connection FTP réussie', 'success');
            }
        }
    }

    // Traitements:

    public function getFileData($fileName, $keys, &$errors = array(), $headerRowIdx = -1, $firstDataRowIdx = 0, $params = array())
    {
        if (!$fileName) {
            $errors[] = 'Nom du fichier absent';
            return array();
        }

        $params = BimpTools::overrideArray(array(
                    'utf8_decode'   => true,
                    'clean_file'    => true,
                    'clean_value'   => false,
                    'part_file_idx' => 0
                        ), $params);

        if (!file_exists($this->local_dir . $fileName)) {
            $errors[] = BimpRender::renderAlerts('Aucun fichier ' . $fileName . ' trouvé dans le dossier "' . $this->local_dir . '"');
            return array();
        }

        $file = $this->local_dir . $fileName;

        $file_errors = array();
        $rows = $this->getCsvFileDataByKeys($file, $keys, $file_errors, $this->params['delimiter'], $headerRowIdx, $firstDataRowIdx, $params);

//        $this->DebugData($rows, 'Données fichier "'.$fileName.'"');

        if (count($file_errors)) {
            $errors = array_merge($errors, $file_errors);
        } elseif (empty($rows)) {
            $errors[] = 'Aucune donnée trouvée dans le fichier "' . $fileName . '"';
        } else {
            $refFournTraite = array();

            $fournPrice = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
            $this->setCurrentObject($fournPrice);

            $this->fetchProducts();

            $data = array();
            $doublon = 0;

            foreach ($rows as $r) {
                // Check doublon
                if (isset($refFournTraite[(string) $r['ref_fourn']])) {
                    $doublon++;
                    continue;
                } else {
                    $refFournTraite[$r['ref_fourn']] = 1;
                }

                $r['is_actif'] = true;
                if (isset($r['is_sleep']) && $r['is_sleep'] != 'false') {
                    $r['is_actif'] = false;
                }
                if (isset($r['is_delete']) && $r['is_delete'] != 'false') {
                    $r['is_actif'] = false;
                }

                foreach ($r as $key => $val) {
                    $r[$key] = trim($val);
                }

                if (isset($r['ref_fourn'])) {
                    $data[$r['ref_fourn']] = $r;
                } else {
                    $data[] = $r;
                }
            }

            if ($doublon) {
                $this->Msg('Doublons: ' . $doublon);
            }
        }

//        $this->DebugData($data, 'Données fichier "'.$fileName.'"');

        return $data;
    }

    public function processFournPrices($lines, &$errors = array())
    {
        if (is_array($lines) && !empty($lines)) {
            $this->fetchProducts();

            $default_tva_tx = (float) BimpTools::getTaxeRateByCode('TN');
            $fourn_prices = array();

            foreach ($lines as $line) {
                $this->pfp_instance->id = 0;
                $this->incProcessed($this->pfp_instance);

                // Check actif: 
                if (isset($line['is_actif']) && !(int) $line['is_actif']) {
                    $this->incIgnored($this->pfp_instance);
                    continue;
                }

                $refFourn = BimpTools::getArrayValueFromPath($line, 'ref_fourn', null);

                if (is_null($refFourn) || !(string) $refFourn) {
                    $this->Alert('Référence fournisseur absente', $this->pfp_instance, null);
                    $this->incIgnored($this->pfp_instance);
                    continue;
                }

                // Check du prix d'achat: 
                if (isset($line['pa_ht'])) {
                    $pa_ht = (float) $line['pa_ht'];
                } else {
                    $this->Alert('Prix d\'achat absent', $this->pfp_instance, $refFourn);
                    $this->incIgnored($this->pfp_instance);
                    continue;
                }

                if ($pa_ht < 0.10) {
                    $this->Alert('Prix fournisseur non traité car inférieur à 0,10 €', $this->pfp_instance, $refFourn);
                    $this->incIgnored($this->pfp_instance);
                    continue;
                }

                // Check tx TVA: 
                $tva_tx = BimpTools::getArrayValueFromPath($line, 'tva_tx', null);

                if (is_null($tva_tx)) {
                    if (isset($line['pu_ht']) && (float) $line['pu_ht'] > 0 &&
                            isset($line['pu_ttc']) && (float) $line['pu_ttc'] > 0) {
                        $tva_tx = BimpTools::getTvaRateFromPrices((float) $line['pu_ht'], (float) $line['pu_ttc']);
                    } else {
                        $tva_tx = $default_tva_tx;
                    }
                }

                // Recherche du produit: 
                $id_product = $this->findIdProductFromLineData($line);
                if ($id_product) {
                    $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                    if($line['url'] && $prod->getData('url') != $line['url']){
                        $prod->updateField('url', $line['url']);
                    }
                    if($line['ean'] && $prod->getData('barcode') != $line['ean']){
                        $prod->updateField('barcode', $line['ean']);
                    }
                    // recherche d'un pfp existant et check de la ref fourn: 
                    $id_pfp = 0;
                    if (!empty($this->infoProdBimp[$id_product]['fourn_prices'])) {
                        foreach ($this->infoProdBimp[$id_product]['fourn_prices'] as $id) {
                            if (isset($this->fournPrices[$id])) {
                                if ($refFourn == $this->fournPrices[$id]['ref_fourn']) {
                                    $id_pfp = (int) $id;
                                    break;
                                }
                            }
                        }

                        if (!$id_pfp) {
                            if (count($this->infoProdBimp[$id_product]['fourn_prices']) === 1) {
                                if (isset($this->fournPrices[$this->infoProdBimp[$id_product]['fourn_prices'][0]])) {
                                    $id_pfp = (int) $this->infoProdBimp[$id_product]['fourn_prices'][0];
                                }
                            } else {
                                $this->Error('Plusieurs prix d\'achat enregistrés pour le produit #' . $id_product . ' mais aucun ne correspond à la ref "' . $refFourn . '"', $this->pfp_instance, $refFourn);
                                $this->incIgnored($this->pfp_instance);
                                continue;
                            }
                        }
                    }

                    if ($id_pfp) {
                        $this->pfp_instance->id = $id_pfp;
                    }

                    $stock = BimpTools::getArrayValueFromPath($line, 'stock', null);

                    // Check changement du pa, stock et/ou ref_fourn
                    if ($id_pfp) {
//                        if ((int) $this->fournPrices[$id_pfp]['price']*100 === (int) $pa_ht*100 
                        if (round((float)$this->fournPrices[$id_pfp]['price'],2) === round((float) $pa_ht,2) 
                                && (float) $this->fournPrices[$id_pfp]['tva_tx'] === (float) $tva_tx &&
                                (is_null($stock) || (float) $stock === (float) $this->fournPrices[$id_pfp]['stock']) &&
                                $refFourn == $this->fournPrices[$id_pfp]['ref_fourn']) {
                            // Pas de màj nécessaire: 
                            $this->incIgnored($this->pfp_instance);
                            continue;
                        }
                    }

                    $fourn_data = array(
                        'rowid'      => $id_pfp,
                        'ref_fourn'  => $refFourn,
                        'fk_product' => $id_product,
                        'fk_soc'     => (int) $this->params['id_fourn'],
                        'price'      => $pa_ht,
                        'tva_tx'     => $tva_tx
                    );

                    if (!is_null($stock)) {
                        $fourn_data['stockFourn'] = $stock;
                    }

                    $fourn_prices[] = $fourn_data;
                } else {
                    $this->incIgnored($this->pfp_instance);

                    // Ajout à la table des produits importables: 
                    $ref_fourn = BimpTools::getArrayValueFromPath($line, 'ref_fourn', '');
                    $ref_manuf = BimpTools::getArrayValueFromPath($line, 'ref_manuf', '');
                    $marque = BimpTools::getArrayValueFromPath($line, 'brand', '');
                    $lib = BimpTools::getArrayValueFromPath($line, 'lib', '');
                    $code_fourn = ''; // ??
                    $pu_ht = (float) BimpTools::getArrayValueFromPath($line, 'pu_ht', 0);

                    $this->debug_content .= 'Ajout import prod "' . $ref_fourn . '"<br/>';

                    $this->addTableProdFourn($ref_fourn, $code_fourn, $pu_ht, $tva_tx, $pa_ht, $marque, $lib, $ref_manuf, $line);
                }
            }

//            $this->DebugData($fourn_prices, 'Fourn Prices');

            if (!empty($fourn_prices)) {
                $this->createBimpObjects('bimpcore', 'Bimp_ProductFournisseurPrice', $fourn_prices, $errors, array(
                    'check_refs'       => false,
                    'update_if_exists' => true
                ));
            }
        }
    }

    public function processFournStocks($lines, &$errors = array())
    {
        if (is_array($lines) && !empty($lines)) {
            $this->fetchProducts();

            foreach ($lines as $line) {
                $this->pfp_instance->id = 0;

                // Check actif: 
                if (isset($line['is_actif']) && !(int) $line['is_actif']) {
                    continue;
                }

                $stock = (float) BimpTools::getArrayValueFromPath($line, 'stock', null);
                if (is_null($stock)) {
                    continue;
                }

                $refFourn = BimpTools::getArrayValueFromPath($line, 'ref_fourn', null);
                if (is_null($refFourn) || !(string) $refFourn) {
                    $this->Alert('Référence fournisseur absente pour la màj du stock', $this->pfp_instance, null);
                    continue;
                }

                // Recherche ref fourn: 
                if (isset($this->refProdFournToIdPriceFourn[$refFourn])) {
                    $id_pfp = (int) $this->refProdFournToIdPriceFourn[$refFourn];

                    if (isset($this->fournPrices[$id_pfp])) {
                        if ((float) $this->fournPrices[$id_pfp]['stock'] !== (float) $stock) {
                            $this->pfp_instance->id = $id_pfp;

                            if ($this->db->update('product_fournisseur_price', array(
                                        'stockFourn' => $stock
                                            ), '`rowid` = ' . (int) $id_pfp) <= 0) {
                                $this->SqlError('Echec de la màj du stock', $this->pfp_instance, $refFourn);
                            } else {
                                $this->Success('Nouveau stock fourn: ' . $stock . ' - ancien: ' . $this->fournPrices[$id_pfp]['stock'], $this->pfp_instance, $refFourn);
                            }
                        }
                    }
                }
//                else
//                    $this->Alert('Référence fournisseur inconnue '.$refFourn);
            }
        }
    }

    // Outils:

    public function downloadFtpFile($fileName, &$errors = array(), $mode = FTP_BINARY)
    {
        $host = BimpTools::getArrayValueFromPath($this->params, 'ftp_host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'ftp_login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'ftp_pwd', '');
        $port = BimpTools::getArrayValueFromPath($this->params, 'ftp_port', 21);
        $passive = ((int) BimpTools::getArrayValueFromPath($this->params, 'ftp_passive', 0) ? true : false);

        if (!$host) {
            $errors[] = 'Hôte absent';
        }

        if (!$login) {
            $errors[] = 'Login absent';
        }

        if (!$pword) {
            $errors[] = 'Mot de passe absent';
        }

        if (!is_dir($this->local_dir)) {
            $errors[] = 'Le dossier local "' . $this->local_dir . '" n\'existe pas';
        }

        $check = false;

        if (!count($errors)) {
            $ftp = $this->ftpConnect($host, $login, $pword, $port, $passive, $errors);

            if ($ftp !== false && !count($errors)) {
                if ($this->options['debug']) {
                    error_reporting(E_ALL);
                }

                $this->Msg('DOSSIER FTP: ' . $this->ftp_dir);
                $files = ftp_nlist($ftp, $this->ftp_dir);

                $this->DebugData($files, 'LISTE FICHIERS FTP');

                if (ftp_get($ftp, $this->local_dir . $fileName, $this->ftp_dir . $fileName, $mode)) {
                    $this->Success('Téléchargement du fichier "' . $fileName . '" OK', null, $fileName);
                    $check = true;
                }


                if ($this->options['debug']) {
                    error_reporting(E_ERROR);
                }

                if (!$check) {
                    $errors[] = 'Echec du téléchargement du fichier "' . $fileName . '" dans "'.$this->local_dir . $fileName.'"';
                }
            }
        }

        return $check;
    }

    public function findIdProductFromLineData($line)
    {
        // On retourne en priorité le produit correspondant à la ref_fourn: 
        if (isset($line['ref_fourn']) && (string) $line['ref_fourn']) {
            if (isset($this->refProdFournToIdPriceFourn[$line['ref_fourn']])) {
                $id_pfp = (int) $this->refProdFournToIdPriceFourn[$line['ref_fourn']];
                if (isset($this->fournPrices[$id_pfp])) {
                    return (int) $this->fournPrices[$id_pfp]['id_product'];
                }
            }
        }

        $tabRef = $this->getPossibleProductsRefs($line);

        $tabOk = array();
        foreach ($tabRef as $ref) {
            if ($ref) {
                if (isset($this->refProdToIdProd[$ref]))
                    $tabOk[] = $this->refProdToIdProd[$ref];
            }
        }
        if(count($tabOk) == 0 && $line['ean']){//test avec ean
            global $db;
            $sql = $db->query('SELECT rowid FROM llx_product WHERE barcode = "'.$line['ean'].'"');
            while ($ln = $db->fetch_object($sql))
                    $tabOk[] = $ln->rowid;
            if(count($tabOk) > 0)
                $this->Info("Code EAN reconnue ".$line['ean'], null, $line['ref_fourn']);
        }
        
        
        if (count($tabOk) == 1)
            return $tabOk[0];

        elseif (count($tabOk) > 1)
            $this->Alert("Plusieurs résultats côté BIMP pour les refs : " . print_r($tabRef, 1));

        return 0;
    }

    public function getPossibleProductsRefs($line)
    {
        $refs = array();

        if (isset($line['ref_prod']) && (string) $line['ref_prod']) {
            $refs[] = $line['ref_prod'];
        }

        if (isset($line['ref_manuf']) && (string) $line['ref_manuf'] && isset($line['brand']) && (string) $line['brand']) {
            $refs[] = strtoupper(substr(str_replace('-', '', $line['brand']), 0, 3)) . '-' . $line['ref_manuf'];

            if (array_key_exists($line['brand'], self::$brands_exceptions)) {
                $refs[] = self::$brands_exceptions[$line['brand']] . '-' . $line['ref_manuf'];
            }
        }

        return $refs;
    }

    public function fetchProducts()
    {
        if (empty($this->refProdToIdProd)) {
            $result = $this->db->getRows('product', '1', null, 'array', array('ref', 'rowid'));

            if (!is_null($result)) {
                foreach ($result as $res) {
                    $this->refProdToIdProd[$res['ref']] = (int) $res['rowid'];
                    $this->infoProdBimp[(int) $res['rowid']] = array(
                        'ref'          => $res['ref'],
                        'fourn_prices' => array()
                    );
                }
            } else {
                $this->SqlError('Echec récup. liste produits enregistrés');
            }

            if ($this->params_ok) { // Pour être sîr qu'on a bien l'id_fourn. 
                $result2 = $this->db->getRows('product_fournisseur_price', '`fk_soc` = ' . $this->params['id_fourn'], null, 'array', array('rowid', 'fk_product', 'ref_fourn', 'price', 'tva_tx', 'stockFourn'));

                if (!is_null($result2)) {
                    foreach ($result2 as $res) {
                        if (isset($this->infoProdBimp[$res['fk_product']])) {
                            $this->infoProdBimp[$res['fk_product']]['fourn_prices'][] = $res['rowid'];
                            $this->fournPrices[$res['rowid']] = array(
                                'id_product' => $res['fk_product'],
                                'ref_fourn'  => $res['ref_fourn'],
                                'price'      => $res['price'],
                                'tva_tx'     => $res['tva_tx'],
                                'stock'      => $res['stockFourn']
                            );
                            if ((string) $res['ref_fourn']) {
                                $this->refProdFournToIdPriceFourn[$res['ref_fourn']] = (int) $res['rowid'];
                            }
                        }
                    }
                } else {
                    $this->SqlError('Echec récup. prix fourn enregistrés');
                }
            }

//            $this->DebugData($this->refProdToIdProd, 'Refs. prods');
//            $this->DebugData($this->infoProdBimp, 'Données produits');
//            $this->DebugData($this->fournPrices, 'Prix fourn actuels');
        }
    }

    public function calcPrice($price)
    {
        return $price; // / 0.97;
    }

    // Traitements SQL:

    function truncTableProdFourn(&$errors = array())
    {
        if ($this->updateSql) {
            if (!isset($this->params['id_fourn']) || !(int) $this->params['id_fourn']) {
                $errors[] = 'ID fournisseur absent';
                return false;
            }
            if ($this->db->db->query("DELETE FROM " . MAIN_DB_PREFIX . "bimp_product_import_fourn WHERE id_fourn = " . $this->params['id_fourn']) <= 0) {
                $errors[] = 'Echec de la troncature de la table "bimp_product_import_fourn"';
                return false;
            }
        }

        return true;
    }

    function addPriceFourn($idProd, $prix, $tva_tx, $ref)
    {
        global $db;
        if ($this->updateSql) {
            if ($db->query("INSERT INTO " . MAIN_DB_PREFIX . "product_fournisseur_price (price, tva_tx, fk_product, ref_fourn, fk_soc) VALUES('" . $prix . "','" . $tva_tx . "','" . $idProd . "','" . $ref . "'," . $this->params['id_fourn'] . ")")) {
                $this->incCreated($this->pfp_instance);
            } else {
                $this->SqlError('Echec ajout', $this->pfp_instance, $ref);
            }
        }
    }

    function addTableProdFourn($refLdlc, $codeLdlc, $pu_ht, $tva_tx, $pa_ht, $marque, $lib, $refFabriquant, $data)
    {
        $this->incProcessed($this->prod_import_instance);

        $data = addslashes(json_encode($data, JSON_UNESCAPED_UNICODE));

        $marque = addslashes($marque);
        $lib = addslashes($lib);
        $refFabriquant = addslashes($refFabriquant);

        if ($this->updateSql) {
            if ($this->db->db->query("INSERT INTO `" . MAIN_DB_PREFIX . "bimp_product_import_fourn`(id_fourn, `refLdLC`, `codeLdlc`, `pu_ht`, `tva_tx`, `pa_ht`, `marque`, `libelle`, `refFabriquant`, `data`) "
                            . "VALUES (" . $this->params['id_fourn'] . ", '" . $refLdlc . "','" . $codeLdlc . "','" . $pu_ht . "','" . $tva_tx . "','" . $pa_ht . "','" . $marque . "','" . $lib . "','" . $refFabriquant . "','" . $data . "')") > 0) {
                $this->incCreated($this->prod_import_instance);
                $this->debug_content .= 'Ajout import prod OK<br/>';
                return;
            }
        }

        $this->incIgnored($this->prod_import_instance);
    }

    function majPriceFourn($id, $prix, $tva_tx, $ref = null)
    {
        $text = 'Update PRICE ' . $id . " | " . round($prix, 2) . " ANCIEN " . round($this->fournPrices[$id]['price'], 2) . "|" . $ref;
        $this->pfp_instance->id = $id;

        if (abs($prix) > 0.01) {
            if ($this->updateSql) {


                if ($this->db->db->query("UPDATE " . MAIN_DB_PREFIX . "product_fournisseur_price SET quantity = '1',price = '" . $prix . "',unitprice = '" . $prix . "', tva_tx = '" . $tva_tx . "'" . ($ref ? ", ref_fourn = '" . $ref . "'" : "") . " WHERE fk_soc = " . $this->params['id_fourn'] . " AND rowid = " . $id)) {
                    $this->incUpdated($this->pfp_instance);
                    $this->Success($text, $this->pfp_instance, $ref);
                } else {
                    $this->SqlError($text, $this->pfp_instance, $ref);
                }
            }
        } else {
            $this->Error("Maj avortée " . $text, $this->pfp_instance, $ref);
        }
    }
}
