<?php

// Allow cross-domain requests
header("Access-Control-Allow-Origin: *");

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');
require_once(dirname(__FILE__) . '/param.inc.php');


$action = Tools::getValue('action');


switch ($action) {
    case 'createPrestashopProduct' : {

            $defaultLanguage = new Language((int) (Configuration::get('PS_LANG_DEFAULT')));
            $product = new Product();
            $product->id_tax_rules_group = (int) $_POST['id_tax'];
            // définition du produit
            $product->name = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['label']);
            $product->price = $_POST['price'];
            $product->category = array($_POST['id_categ_extern']);
            $product->id_category_default = $_POST['id_categ_extern'];
            $product->description_short = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['label']);
            $product->quantity = intVal($_POST['number_place']);
            $product->redirect_type = '404';
            $product->link_rewrite = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $_POST['label']);
            $return = $product->add();
            $product->updateCategories($product->category, true);
            if ($product->id > 0) {
                StockAvailable::setQuantity((int) $product->id, 0, intVal($_POST['number_place']));
                $image = new Image();
                $image->id_product = (int) $product->id;
                $image->position = Image::getHighestPosition($product->id) + 1;
                $image->cover = true;
                $image->add();
                if (!copyImg($product->id, $image->id, URL_CHECK . 'img/event/' . $_POST['image_name'], 'products', !Tools::getValue('regenerate'))) {
                    $image->delete();
                }
            }
            die(Tools::jsonEncode(array('id_inserted' => $product->id, 'errors' => array())));
            break;
        }

    case 'createPrestashopCategory' : {
            $category = new Category();
            $category->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $_POST['label']);
            $category->id_parent = Configuration::get('PS_HOME_CATEGORY');
            $category->link_rewrite = array((int) Configuration::get('PS_LANG_DEFAULT') => str_replace(' ', '_', $_POST['label']));
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
            die(Tools::jsonEncode(array('toggled' => $res, 'errors' => array())));
            break;
        }
        
    case 'toggleCategActive' : {
            $categ = new Category((int) $_POST['id_categ']);
            if ($categ->active == true)
                $categ->active = false;
            else
                $categ->active = true;
            $res = $categ->update();
            die(Tools::jsonEncode(array('toggled' => $res, 'errors' => array())));
            break;
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
