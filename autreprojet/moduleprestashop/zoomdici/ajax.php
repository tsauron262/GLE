<?php

// Allow cross-domain requests
header("Access-Control-Allow-Origin: *");

require_once('./param.inc.php');

//var_dump(PATH_TO_MODULE);
//require_once(PATH_TO_MODULE . '../../config/config.inc.php');
//require_once(PATH_TO_MODULE . '../../init.php');

require_once(PATH_TO_PRESTA . 'config/config.inc.php');
require_once(PATH_TO_PRESTA . 'init.php');


$action = Tools::getValue('action');


switch ($action) {
    case 'createPrestashopProduct' : {

            // Pre-processing
            $date_start_simple = substr($_POST['date_start'], 0, -9); // yyyy-mm-dd
            $date_end_simple = substr($_POST['date_end'], 0, -9); // yyyy-mm-dd
            $date_stop_sale_parsed = 'Le ' . substr($_POST['date_stop_sale'], 0, -8) . ' à ' . substr($_POST['date_stop_sale'], -8, -6) . 'h'; // yyyy-mm-dd hh
            $description = 'Du <strong>' . $date_start_simple . '</strong> au <strong>' . $date_end_simple . '</strong>';

            // features date start
            $month_start = (int) substr($_POST['date_start'], 5, 7);
            $id_start_feature_value = ARRAY_MONTH[$month_start];
            // features date end
            $month_end = (int) substr($_POST['date_end'], 5, 7);
            $id_end_feature_value = ARRAY_MONTH[$month_end];

            $defaultLanguage = new Language((int) (Configuration::get('PS_LANG_DEFAULT')));
            $product = new Product();
            $product->id_tax_rules_group = (int) $_POST['id_tax'];
            $product->price = $_POST['price'];

            // Extrafields
            $product->date_from = $_POST['date_start'];
            $product->date_to = $_POST['date_end'];
            $product->email_text = $_POST['email_text'];
            $product->place = $_POST['place'];
//            $product->place = str_replace('<br/>', '\n', $_POST['place']);
            $product->address = $_POST['address'];
//            $product->address = str_replace('<br/>', '\n', $_POST['address']);

            $tabTaxe = TaxRuleCore::getTaxRulesByGroupId(Configuration::get('PS_LANG_DEFAULT'), $product->id_tax_rules_group);
            if (isset($tabTaxe[0])) {
                $product->price = number_format($product->price / (100 + $tabTaxe[0]['rate']) * 100, 5);
            }
            // définition du produit
            $product->name = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['label']);
            $product->description = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $description);
            $product->category = array($_POST['id_categ_extern']);
            $product->id_category_default = $_POST['id_categ_extern'];
            $product->description_short = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $description);
            $product->quantity = intVal($_POST['number_place']);
            $product->redirect_type = '404';
            $product->link_rewrite = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['id_tariff']);
            $return = $product->add();
            $product->updateCategories($product->category, true);

            if ($product->id > 0) {
                // Qty
                StockAvailable::setQuantity((int) $product->id, 0, intVal($_POST['number_place']));

                // Image
                $image = new Image();
                $image->id_product = (int) $product->id;
                $image->position = Image::getHighestPosition($product->id) + 1;
                $image->cover = true;
                $image->add();
                if (!copyImg($product->id, $image->id, URL_CHECK . 'img/event/' . $_POST['image_name'], 'products', !Tools::getValue('regenerate'))) {
                    $image->delete();
                }

                // Feature
                $product::addFeatureProductImport($product->id, ID_FEATURE_MONTH, $id_start_feature_value);
                if ($id_start_feature_value != $id_end_feature_value) // if product (tariff) overlap 2 months
                    $product::addFeatureProductImport($product->id, ID_FEATURE_MONTH, $id_end_feature_value);

                // Create new feature value (date end sale) and link with the new product
                $id_feature_value_date_stop_sale = FeatureValue::addFeatureValueImport(ID_FEATURE_DATE_END_SALE, $date_stop_sale_parsed);
                $product::addFeatureProductImport($product->id, ID_FEATURE_DATE_END_SALE, $id_feature_value_date_stop_sale);
            }
            die(Tools::jsonEncode(array('id_inserted' => $product->id, 'errors' => array())));
            break;
        }

    case 'createPrestashopCategory' : {

            $description = $_POST['description'] . '<br/>';
            $description.= 'Adresse : <br/>' . $_POST['place']; // . $_POST['address'];

            $category = new Category();
            $category->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $category->link_rewrite = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['id_event']);
            $category->id_parent = (int) $_POST['id_categ_parent'];
            $category->description = $description;
            $category->add();
            die(Tools::jsonEncode(array('id_inserted' => $category->id, 'errors' => array())));
            break;
        }

    case 'toggleProductActive' : {
            $product = new Product((int) $_POST['id_prod_extern']);
            if ($product->active == true)
                $product->active = false;
            else
                $product->active = true;
            $res = $product->update();
            die(Tools::jsonEncode(array('toggled' => $res, 'active' => $product->active, 'errors' => array())));
            break;
        }

    case 'toggleCategActive' : {
            $categ = new Category((int) $_POST['id_categ']);
            if ($categ->active == true)
                $categ->active = false;
            else
                $categ->active = true;
            $res = $categ->update();
            die(Tools::jsonEncode(array('toggled' => $res, 'active' => $categ->active, 'errors' => array())));
            break;
        }

    case 'createAttributeGroup' : {
            $attribute_group = new AttributeGroup();
            $attribute_group->group_type = $_POST['type'];
            $attribute_group->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $attribute_group->public_name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $attribute_group->add();
            die(Tools::jsonEncode(array('id_inserted' => $attribute_group->id, 'errors' => array())));
            break;
        }

    case 'createAttributeValue' : {
            $attribute = new Attribute();
            $attribute->id_attribute_group = (int) $_POST['id_attribute_parent'];
            $attribute->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $attribute->add();
            $attribute_group = new AttributeGroup((int) $_POST['id_attribute_parent']);
            $attribute_group->update();
            die(Tools::jsonEncode(array('id_inserted' => $attribute->id, 'errors' => array())));
            break;
        }

    case 'linkProductAttributeValue': {

            $product = new Product($_POST['id_prod_extern'], true, (int) Configuration::get('PS_LANG_DEFAULT'));

            if (!((int) $product->id > 0))
                die(Tools::jsonEncode(array('errors' => "Auncun produit ne correspond dans le serveur prestashop, avez-vous importé le tariff auparavant ?")));

            $combinationAttributes = array();
            $combinationAttributes[] = $_POST['id_attribute_value_extern'];


            if (!$product->productAttributeExists($combinationAttributes)) {
                $price = $_POST['price'];
                $weight = 0;
                $unit_price_impact = "";
                $ecotax = 0;
                $quantity = $_POST['qty'];
                $id_image = Db::getInstance()->executeS('
			SELECT `id_image`
			FROM `' . _DB_PREFIX_ . 'image`
			WHERE `id_product` = ' . (int) $product->id . '
			ORDER BY `position`');
                $reference = "";
                $id_supplier = 1;
                $ean13 = "";
                $default = false;
                $location = "";
                $upc = "";
                $minimal_quantity = 1;
                $isbn = "";


                $idProductAttribute = $product->addProductAttribute(
                        (float) $price, (float) $weight, $unit_price_impact, (float) $ecotax, (int) $quantity, $id_image, $reference, $id_supplier, $ean13, $default, $location, $upc, $minimal_quantity, $isbn);
                $product->addAttributeCombinaison($idProductAttribute, $combinationAttributes);
                die(Tools::jsonEncode(array('errors' => array(), 'is_ok' => true)));
            } else {
                die(Tools::jsonEncode(array('errors' => "", 'is_ok' => false)));
            }
            break;
        }

    case 'deleteCategAndItsProduct': {
            $category = new Category($_POST['id_event']);
            $p = 1; // "Page number"
            $n = 10000; // "Number of products per page"
            $products = $category->getProducts((int) Configuration::get('PS_LANG_DEFAULT'), $p, $n);
//            var_dump($products);
//            die('Fin');
            if ($products != false) {
                foreach ($products as $arr_product) {
                    $product = new Product((int) $arr_product['id_product']);
                    $is_ok = $product->delete();
                    if (!$is_ok)
                        die(Tools::jsonEncode(array('is_ok' => $is_ok, 'errors' => "Problème lors de la suppression d'un tarif.")));
                }
            }
            $is_ok = $category->delete();
            if ($is_ok)
                die(Tools::jsonEncode(array('is_ok' => $is_ok, 'errors' => array())));
            else
                die(Tools::jsonEncode(array('is_ok' => $is_ok, 'errors' => "Problème lors de la suppression de l'évènement, celà peut-être dû à une suppression antérieur de cet évènement.")));
            break;
        }

    case 'deleteProduct': {
            $product = new Product((int) $_POST['id_prod_extern']);
            $is_ok = $product->delete();
            if ($is_ok)
                die(Tools::jsonEncode(array('is_ok' => $is_ok, 'errors' => array())));
            else
                die(Tools::jsonEncode(array('is_ok' => $is_ok, 'errors' => "Problème lors de la suppression du tarif, celà peut-être dû à une suppression antérieur de ce tarif.")));
            break;
        }

    case 'updatecateg': {
            $description = $_POST['description'] . '<br/>';
            $description.= 'Adresse : <br/>' . $_POST['place']; // . $_POST['address'];

            $category = new Category((int) $_POST['id_categ']);
            $category->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $category->description = $description;
            $result = $category->update();
            die(Tools::jsonEncode(array('is_ok' => $result, 'errors' => array())));
        }

    case 'updateProduct': {
            $product = new Product((int) $_POST['id_prod_extern']);
            $product->name = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['label']);
            $product->price = $_POST['price'];
            $product->email_text = $_POST['email_text'];

            $result = $product->update();
            die(Tools::jsonEncode(array('is_ok' => $result, 'errors' => array())));
        }

    default: {
            die(Tools::jsonEncode(array('errors' => "Echec : aucune action ne correspond à " . $action)));
            break;
        }
}
exit;









/* Function */

function copyImg($id_entity, $id_image, $url, $entity = 'products', $regenerate = true) {
    $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
    $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));


    switch ($entity) {
        default:
        case 'products':
            $image_obj = new Image($id_image);
            $path = $image_obj->getPathForCreation();
            break;
        case 'categories':
            $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
            break;
        case 'manufacturers':
            $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
            break;
        case 'suppliers':
            $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
            break;
    }
    $url = str_replace(' ', '%20', trim($url));


    // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
    if (!ImageManager::checkImageMemoryLimit($url))
        return false;


    // 'file_exists' doesn't work on distant file, and getimagesize makes the import slower.
    // Just hide the warning, the processing will be the same.
    if (Tools::copy($url, $tmpfile)) {
        ImageManager::resize($tmpfile, $path . '.jpg');
        $images_types = ImageType::getImagesTypes($entity);


        if ($regenerate)
            foreach ($images_types as $image_type) {
                ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height']);
                if (in_array($image_type['id_image_type'], $watermark_types))
                    Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
            }
    }
    else {
        unlink($tmpfile);
        return false;
    }
    unlink($tmpfile);
    return true;
}
