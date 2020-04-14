<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT PRODUCTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$maj_comm_fourn = false;

$dir = DOL_DATA_ROOT . '/bimpcore/imports/' . date('Y-m-d') . '/';

$actions = array(
    'import_apple_products'  => 'Import produits Apple',
    'import_apple_prices'    => 'Import prix Apple',
    'import_td_prices'       => 'Import prix TechData',
    'import_ingram_prices'   => 'Import prix Ingram',
    'validate_apple_producs' => 'Valider les produits Apple importés',
    'import_ldlc_products'   => 'Import produits LDLC'
);

global $action;
$action = BimpTools::getValue('action', '');

if (!$action) {
    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }

    exit;
}

switch ($action) {
    case 'import_apple_products':
        importAppleProducts($dir . 'products.txt');
        break;

    case 'import_apple_prices':
        importFournPrices($dir . 'pa_apple.txt', 261968, $maj_comm_fourn);
        break;

    case 'import_td_prices':
        importFournPrices($dir . 'pa_td.txt', 229890, $maj_comm_fourn);
        break;

    case 'import_ingram_prices':
        importFournPrices($dir . 'pa_ingram.txt', 230496, $maj_comm_fourn);
        break;

    case 'validate_apple_producs':
        validateProducts($dir . 'products.txt', 0, $bdb);
        break;

    case 'import_ldlc_products':
        importLdlcProducts();
        break;

    default:
        echo 'Action invalide';
        break;
}

function importAppleProducts($file)
{
    if (!file_exists($file)) {
        echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas');
        return;
    }

    $rows = file($file, FILE_IGNORE_NEW_LINES);

    if (!count($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne à traiter');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }


    $keys = array(
        0  => 'ref',
        1  => 'label',
        3  => 'barcode',
        4  => 'serialisable',
        5  => 'price',
        7  => 'tva_tx',
        8  => 'price_ttc',
        9  => 'gamme',
        10 => 'categorie',
        11 => 'collection',
        12 => 'nature',
        13 => 'famille',
        15 => 'deee',
        16 => 'cto',
        17 => 'cur_pa_ht',
        21 => 'crt'
    );

    $categories = BimpCache::getProductsTagsByTypeArray('categorie', false);
    $collections = BimpCache::getProductsTagsByTypeArray('collection', false);
    $natures = BimpCache::getProductsTagsByTypeArray('nature', false);
    $familles = BimpCache::getProductsTagsByTypeArray('famille', false);
    $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false);

//    $instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//    $categories = $instance->getValues8sens('categorie');
//    $collections = $instance->getValues8sens('collection');
//    $natures = $instance->getValues8sens('nature');
//    $familles = $instance->getValues8sens('famille');
//    $gammes = $instance->getValues8sens('gamme');

    $refs_done = array();
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        $data = explode("\t", $r);

        $values = array();
        foreach ($keys as $key => $field) {
            if (isset($data[$key]) && (string) $data[$key]) {
                $value = $data[$key];

                switch ($field) {
                    case 'serialisable':
                        if ($value === 'NUFARTSTKSERIE') {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                        break;

                    case 'price':
                    case 'tav_tx':
                    case 'price_ttc':
                    case 'ecotaxe':
                    case 'cur_pa':
                    case 'crt':
                        $value = (float) str_replace(',', '.', $value);
                        break;

                    case 'gamme':
                        $value = 0;
                        foreach ($gammes as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La gamme "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'categorie':
                        $value = 0;
                        foreach ($categories as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La catégorie "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'collection':
                        $value = 0;
                        foreach ($collections as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La collection "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'nature':
                        $value = 0;
                        foreach ($natures as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La nature "' . $data[$key] . '" n\'existe pas');
                        }
                        break;

                    case 'famille':
                        $value = 0;
                        foreach ($familles as $id => $label) {
                            if ((string) $data[$key] == $label) {
                                $value = $id;
                                break 2;
                            }
                        }
                        if (!$value) {
                            echo BimpRender::renderAlerts('La famille "' . $data[$key] . '" n\'existe pas');
                        }
                        break;
                }
                $values[$field] = $value;
            }
        }

        $values['fk_product_type'] = 0;

        if (in_array($values['ref'], $refs_done)) {
            echo 'Ref déjà traitée: ' . $values['ref'] . '<br/>';
            continue;
        } else {
            $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                        'ref' => $values['ref']
            ));
            if (BimpObject::objectLoaded($product)) {
                echo 'La ref ' . $values['ref'] . ' existe déjà <br/>';
                continue;
            } else {
                $refs_done[] = $values['ref'];
            }
        }

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $errors = $product->validateArray($values);
        if (!count($errors)) {
            $errors = $product->create($warnings, true);
        }

        if (count($errors)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Ligne ' . $i . ' - ' . $values['ref']));
        }
    }
}

function validateProducts($file, $ref_key, BimpDb $bdb)
{
    $rows = file($file, FILE_IGNORE_NEW_LINES);
    $refs = array();

    foreach ($rows as $r) {
        $data = explode("\t", $r);
        if (isset($data[$ref_key]) && (string) $data[$ref_key]) {
            $refs[] = "'" . $data[$ref_key] . "'";
        }
    }

    $ids = array();
    $result = $bdb->getRows('product', '`ref` IN (' . implode(',', $refs) . ')');
    foreach ($result as $res) {
        $ids[] = (int) $res->rowid;
    }

    if (!empty($ids)) {
        if ($bdb->update('product_extrafields', array(
                    'validate' => 1
                        ), 'fk_object IN (' . implode(',', $ids) . ')') <= 0) {
            echo $bdb->db->lasterror() . '<br/>';
        } else {
            echo 'OK';
        }
    } else {
        echo $bdb->db->lasterror() . '<br/>';
    }
}

function importFournPrices($file, $id_fourn, $maj_comm_fourn = false)
{
    if ($maj_comm_fourn) {
        $_POST['update_comm_fourn'] = 1;
    }

    if (!file_exists($file)) {
        echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas');
        return;
    }

    $rows = file($file, FILE_IGNORE_NEW_LINES);

    if (!count($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne à traiter');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }

    $keys = array(
        'id_fourn'    => 0,
        'ref_fourn'   => 1,
        'price'       => 5,
        'ref_product' => 7,
        'ean'         => 14
    );
    foreach ($rows as $r) {
        $data = explode("\t", $r);

//        if ($data[$keys['ref_product']] !== 'APP-MRT32FN/A') { // POURT TESTS
//            continue;
//        }
//        echo '<pre>';
//        print_r($data);
//        echo '</pre>';
//        
//        continue;

        $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                    'ref' => $data[$keys['ref_product']]
        ));

        if (!BimpObject::objectLoaded($product)) {
            echo BimpRender::renderAlerts('Aucun produit trouvé pour la référence "' . $data[$keys['ref_product']] . '"');
            continue;
        } else {
            echo $data[$keys['ref_product']] . ': ' . $product->id . '<br/>';
        }

        $pfp = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
        $errors = $pfp->validateArray(array(
            'fk_product' => (int) $product->id,
            'fk_soc'     => $id_fourn,
            'ref_fourn'  => $data[$keys['ref_fourn']],
            'price'      => (float) str_replace(',', '.', $data[$keys['price']]),
            'tva_tx'     => (float) $product->getData('tva_tx')
        ));

        if (!count($errors)) {
            $errors = $pfp->create($warnings, true);
        }

        if (count($errors)) {
            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Produit "' . $data[$keys['ref_product']] . '"'));
        }

        BimpCache::$cache = array();
    }
}

function importLdlcProducts()
{
    $dir = '/data/importldlc/';
//    $dir = DOL_DATA_ROOT.'/importldlc/';

    $file = date('Ymd') . '_catalog_ldlc_to_bimp.csv';

    $errors = $msgOk = array();

    if (!file_exists($dir . $file)) {
        $file = '';
        if (file_exists($dir) && is_dir($dir)) {
            $files = scandir($dir);
            arsort($files);

            foreach ($files as $f) {
                if (preg_match('/^[0-9]{8}_catalog_ldlc_to_bimp\.csv$/', $f)) {
                    $file = $f;
                    break;
                }
            }
        } else {
            echo BimpRender::renderAlerts('Dossier "' . $dir . '" absent');
        }
    }

    if (!$file) {
        echo BimpRender::renderAlerts('Aucun fichier trouvé dans le dossier "' . $dir . '"');
        return;
    }

    echo 'Fichier: ' . $file . '<br/><br/>';


    $rows = file($dir . $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($rows)) {
        echo BimpRender::renderAlerts('Aucune ligne trouvée');
        return;
    }

    if (!(int) BimpTools::getValue('exec', 0)) {
        global $action;
        $path = pathinfo(__FILE__);

        echo '<div style="margin-bottom: 30px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $action . '&exec=1" class="btn btn-default">';
        echo 'Exécuter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';

        echo '<pre>';
        print_r($rows);
        echo '</pre>';

        return;
    }

    $class = new importCatalogueLdlc();
    $class->initProdBimp();


    $idProdTrouve = $idProdTrouveActif = array();

    $ok = $bad = 0;
    $total = $aJour = $nonActifIgnore = 0;
    foreach ($rows as $idx => $r) {
        if (!$idx) {
            continue;
        }

        $total++;

        $r = utf8_encode($r);
        $data = explode(';', $r);


        if ($data[$class->keys['ManufacturerRef']] == "N/A")
            $data[$class->keys['ManufacturerRef']] = '';

        $data['BIMP_idPrixAchatBimp'] = '';
        $data['BIMP_isActif'] = ($data[$class->keys['isSleep']] == "false" && $data[$class->keys['isDelete']] == "false");


        $idProdBimp = $idPrixAchat = 0;

        $idPrixAchat = $class->getIdPrixLdlc($data[$class->keys['ref']]);
//        
        if ($idPrixAchat) {
            $idProdBimp = $class->idProdFournToIdProdBimp[$idPrixAchat];
            $data['BIMP_idPrixAchatBimp'] = $idPrixAchat;
        } else {
            $refs = $class->getPossibleRefs($data[$class->keys['ref']], $data[$class->keys['ManufacturerRef']], $data[$class->keys['Brand']]);
            $idProdBimp = $class->getProduct($refs);
        }


        if ($idProdBimp) {
            if (!isset($idProdTrouve[$idProdBimp]) && !isset($idProdTrouveActif[$idProdBimp]))
                $idProdTrouve[$idProdBimp] = $data;
            if ($data['BIMP_isActif']) {
                if (isset($idProdTrouveActif[$idProdBimp])) {

                    if ($idProdTrouveActif[$idProdBimp]['BIMP_idPrixAchatBimp'] && !$data['BIMP_idPrixAchatBimp']) {//L'ancien est liée a un prix d'achat on ne fait rien
                    } elseif (!$idProdTrouveActif[$idProdBimp]['BIMP_idPrixAchatBimp'] && $data['BIMP_idPrixAchatBimp']) {//Le nouveau est li a un prix d'achat on prend celui la
                        $idProdTrouveActif[$idProdBimp] = $data;
                        $idProdTrouve[$idProdBimp] = $data; //pour être sur au cas ou ceux d'avant ne sont pas actif
                    } else {
                        $class->errors[] = "Attention deux ligne LDLC pour le même prod " . $idProdBimp . " | " . $data[$class->keys['ref']] . "(" . $data['BIMP_idPrixAchatBimp'] . ")et " . $idProdTrouveActif[$idProdBimp][$class->keys['ref']] . "(" . $idProdTrouveActif[$idProdBimp]['BIMP_idPrixAchatBimp'] . ")";
                        if (isset($idProdTrouve[$idProdBimp])) {
                            unset($idProdTrouve[$idProdBimp]);
                        }
                    }
                } else {
                    $idProdTrouveActif[$idProdBimp] = $data;
                    $idProdTrouve[$idProdBimp] = $data; //pour être sur au cas ou ceux d'avant ne sont pas actif
                }
            }
        } elseif ($data['BIMP_isActif']) {
            //ajout a la table de creation
            $pu_ht = $class->calcPrice($data[$class->keys['puHT']]);
            $pu_ttc = $class->calcPrice($data[$class->keys['puTTC']]);
            $pa_ht = $class->calcPrice($data[$class->keys['prixBase']]);
            $class->addTableLDlc($data[$class->keys['ref']], $data[$class->keys['code']], $pu_ht, $pu_ttc, $pa_ht, $data[$class->keys['Brand']], $lib, $data[$class->keys['ManufacturerRef']], $data);
        }


        if ($idProdBimp)
            $ok++;
        else
            $bad++;
    }




    foreach ($idProdTrouve as $idProd => $data) {
        $prix = $class->calcPrice($data[$class->keys['prixBase']]);
        $updatePrice = $updateRef = false;

        if (!$data['BIMP_idPrixAchatBimp'] && isset($class->infoProdBimp[$idProd]['idProdFournisseur']) && $class->infoProdBimp[$idProd]['idProdFournisseur'] > 0) {
            $data['BIMP_idPrixAchatBimp'] = $class->infoProdBimp[$idProd]['idProdFournisseur'];
            $updateRef = true;
        }

        if ($data['BIMP_idPrixAchatBimp']) {
            $prixActuel = $class->idProdFournToPrice[$data['BIMP_idPrixAchatBimp']];

            if (round($prix, 2) != round($prixActuel, 2))
                $updatePrice = true;

            if ($updateRef)
                $class->majPriceFourn($data['BIMP_idPrixAchatBimp'], $prix, $data[$class->keys['ref']]);
            elseif ($updatePrice)
                $class->majPriceFourn($data['BIMP_idPrixAchatBimp'], $prix);
            else
                $aJour++;
        }
        else {
            if ($data['BIMP_isActif']) {
                $class->addPriceFourn($idProd, $prix, $data[$class->keys['ref']]);
            } else
                $nonActifIgnore++;
        }
    }

    echo "<br/><br/><h3>" . $ok . " ok " . $bad . " bad " . " total " . $total . " lienOk " . count($idProdTrouve) . " a jour : " . $aJour . " nonActifIgnore : " . $nonActifIgnore . "</h3<br/><br/>fin<br/>";

    $class->displayResult();

//echo $html;
}

class importCatalogueLdlc
{

    public $idFournLdlc = 230880;
    public $infoProdBimp = array();
    public $refProdFournToIdPriceFourn = array();
    public $refProdToIdProd = array();
    public $idProdFournToIdProdBimp = array();
    public $errors = array();
    public $msgOk = array();
    public $keys = array(
        'ref'             => 0,
        'code'            => 1,
        'ManufacturerRef' => 9,
        'Brand'           => 2,
        'isSleep'         => 12,
        'isDelete'        => 13,
        'puHT'            => 14,
        'puTTC'           => 15,
        'prixBase'        => 18
    );

    function truncTableLdlc()
    {
        global $db;
        $db->query("TRUNCATE " . MAIN_DB_PREFIX . "bimp_product_ldlc");
    }

    function addTableLDlc($refLdlc, $codeLdlc, $pu_ht, $pu_ttc, $pa_ht, $marque, $lib, $refFabriquant, $data)
    {
        global $db;

        $data = addslashes(json_encode($data, JSON_UNESCAPED_UNICODE));

        $tva_tx = BimpTools::getTvaRateFromPrices($pu_ht, $pu_ttc);

        $marque = addslashes($marque);
        $lib = addslashes($lib);
        $refFabriquant = addslashes($refFabriquant);
        $db->query("INSERT INTO `" . MAIN_DB_PREFIX . "bimp_product_ldlc`(`refLdLC`, `codeLdlc`, `pu_ht`, `tva_tx`, `pa_ht`, `marque`, `libelle`, `refFabriquant`, `data`) "
                . "VALUES ('" . $refLdlc . "','" . $codeLdlc . "','" . $pu_ht . "','" . $tva_tx . "','" . $pa_ht . "','" . $marque . "','" . $lib . "','" . $refFabriquant . "','" . $data . "')");
    }

    function majPriceFourn($id, $prix, $ref = null)
    {
        echo '<br/>Update PRICE ' . $id . " | " . round($prix, 2) . " ANCIEN " . round($this->idProdFournToPrice[$id], 2) . "|" . $ref;

//        global $db;
//        $db->query("UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price SET price = '".$prix."'".($ref? ", ref_fourn = '".$ref."'" : "")." WHERE fk_soc = ".$this->idFournLdlc." AND rowid = ".$id);
    }

    function calcPrice($price)
    {
        return $price / 0.97;
    }

    function addPriceFourn($idProd, $prix, $ref)
    {
        echo '<br/>INSERT PRICE' . $idProd . " | " . round($prix, 2) . "|" . $ref;

//        global $db;
//        $db->query("INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price (price, fk_product, ref_fourn, fk_soc) VALUES('".$prix."','".$idProd."','".$ref."',".$this->idFournLdlc.")");
    }

    function displayResult()
    {
        if (count($this->errors)) {
            echo '<pre>';
            print_r($this->errors);
        }
        if (count($this->msgOk)) {
            echo '<pre>';
            print_r($this->msgOk);
        }

//        $id_fp = (int) $bdb->getRow('');
    }

    function initProdBimp()
    {
        $bdb = BimpCache::getBdb();

        $result = $bdb->getRows('product', '1', null, 'array', array('ref', 'rowid'));

        if (!is_null($result)) {
            foreach ($result as $res) {
                $this->infoProdBimp[$res['rowid']]['refBimp'] = $res['ref'];
                $this->refProdToIdProd[$res['ref']] = $res['rowid'];
            }
        }

        $result2 = $bdb->getRows('product_fournisseur_price', '`fk_soc` = ' . $this->idFournLdlc, null, 'array', array('rowid', 'fk_product', 'ref_fourn', 'price'));

        if (!is_null($result2)) {
            foreach ($result2 as $res) {
                if (isset($this->infoProdBimp[$res['fk_product']])) {
                    $this->infoProdBimp[$res['fk_product']]['idProdFournisseur'] = $res['rowid'];
                    $this->infoProdBimp[$res['fk_product']]['refFourn'] = $res['ref_fourn'];
                    $this->refProdFournToIdPriceFourn[$res['ref_fourn']] = $res['rowid'];
                    $this->idProdFournToIdProdBimp[$res['rowid']] = $res['fk_product'];
                    $this->idProdFournToPrice[$res['rowid']] = $res['price'];
                }
            }
        }

        $this->truncTableLdlc();
    }

    function getIdPrixLdlc($ref)
    {
        if (isset($this->refProdFournToIdPriceFourn[$ref]))
            return $this->refProdFournToIdPriceFourn[$ref];
    }

    function getPossibleRefs($refLdlc, $refConstructeur, $marque)
    {
        $tabRef = array();

        $tabBrandBad = array('AIRIS');
        if (in_array($marque, $tabBrandBad))
            $marque = '';

        if (isset($refLdlc) && $refLdlc != '')
            $tabRef[] = $refLdlc;
        if(isset($refConstructeur) && $refConstructeur != ''){
            $prefixe = (isset($marque) && $marque != "") ? substr($marque, 0,3)."-" : "";
            $tabRef[] = $prefixe.$refConstructeur;
            if($marque == "GÉNÉRIQUE-HP")
                $tabRef[] = "HEW-".$refConstructeur;
            if($marque == "HP")
                $tabRef[] = "HEW-".$refConstructeur;
        }
        return $tabRef;
    }

    function getProduct($tabRef)
    {
        $tabOk = array();
        foreach ($tabRef as $ref) {
            if ($ref) {
                if (isset($this->refProdToIdProd[$ref]))
                    $tabOk[] = $this->refProdToIdProd[$ref];
//                    foreach($this->infoProdBimp as $idProdBimp => $data){
//                        if($data['refBimp'] == $ref)
//                            $tabOk[] = $idProdBimp;
//                    }
            }
        }
        if (count($tabOk) == 1)
            return $tabOk[0];
        elseif (count($tabOk) > 1)
            $this->errors[] = "Plusisuers résultat coté BIMP pour les réf : " . print_r($tabRef, 1);
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
