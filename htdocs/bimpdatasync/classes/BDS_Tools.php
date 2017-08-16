<?php

class BDS_Tools
{

    public static function GetDataFromCSV($file, $delimiter = ';')
    {
        if (!file_exists($file)) {
            return 'Fichier CSV ' . $file . ' absent';
        }

        $data = array();
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($rows as $row) {
            $data[] = explode($delimiter, $row);
        }
        return $data;
    }

    public static function writeDataInCSV($file, $data, $delimiter = ';')
    {
        if (is_array($data) && count($data)) {
            $txt = '';
            $firstRow = true;
            foreach ($data as $row) {
                if (!$firstRow) {
                    $txt .= "\n";
                } else {
                    $firstRow = false;
                }
                $firstItem = true;
                foreach ($row as $item) {
                    if (!$firstItem) {
                        $txt .= $delimiter;
                    } else {
                        $firstItem = false;
                    }
                    $txt .= $item;
                }
            }
            if (file_put_contents($file, $txt)) {
                return true;
            }
        }
        return false;
    }

    public static function cleanString($str)
    {
        $str = str_replace('#', '', $str);
        $str = str_replace('{', '(', $str);
        $str = str_replace('}', ')', $str);
        $str = str_replace(';', ',', $str);
        $str = str_replace('>', ':', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('=', ':', $str);

        return $str;
    }

    public static function getCategorieParent(BimpDb $db, $id_categorie)
    {
        $cat_id_parent = $db->getValue('categorie', 'fk_parent', '`rowid` = ' . (int) $id_categorie);
        if (is_null($cat_id_parent)) {
            return 0;
        }
        return $cat_id_parent;
    }

    public static function isCategorieChildOf(BimpDb $db, $id_categorie, $id_parent, $can_be_parent = true)
    {
        if ((int) $id_categorie === (int) $id_parent) {
            if ($can_be_parent) {
                return true;
            } else {
                return false;
            }
        }

        $id_cat = (int) $id_categorie;
        $cat_id_parent = (int) self::getCategorieParent($db, $id_cat);
        while ($cat_id_parent > 0) {
            if ((int) $cat_id_parent === (int) $id_parent) {
                return true;
            }
            $id_cat = $cat_id_parent;
            $cat_id_parent = self::getCategorieParent($db, $id_cat);
        }

        return false;
    }

    public static function getProductImagesDir($product)
    {
        global $conf;

        if (!is_a($product, 'Product')) {
            return null;
        }

        if (!isset($product->id) || !$product->id) {
            return null;
        }

        $dir = $conf->product->multidir_output[$conf->entity] . '/';

        if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
            if (DOL_VERSION < '3.8.0') {
                $dir .= get_exdir($product->id, 2) . $product->id . "/photos/";
            } else {
                $dir .= get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . "/photos/";
            }
        } else {
            dol_sanitizeFileName($product->reference);
        }

        return $dir;
    }
}
