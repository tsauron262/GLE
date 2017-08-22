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

    public static function makeObjectLabel($label, $type = 'the', $isFemale = false, $label_plurial = null)
    {
        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $label)) {
            $vowel_first = true;
        }

        if (is_null($label_plurial)) {
            if (preg_match('/^.*au$/', $label)) {
                $label_plurial = $label . 'x';
            } elseif (preg_match('/^.*ou$/', $label)) {
                $label_plurial = $label . 'x';
            } else {
                $label_plurial = $label . 's';
            }
        }

        $label = strtolower($label);

        switch ($type) {
            case 'the':
                if ($vowel_first) {
                    return 'l\'' . $label;
                } elseif ($isFemale) {
                    return 'la ' . $label;
                } else {
                    return 'le ' . $label;
                }

            case 'the_plur':
                return 'les ' . $label_plurial;

            case 'a':
                if ($isFemale) {
                    return 'une ' . $label;
                } else {
                    return 'un ' . $label;
                }

            case 'this':
                if ($isFemale) {
                    return 'cette ' . $label;
                } elseif ($vowel_first) {
                    return 'cet ' . $label;
                } else {
                    return 'ce ' . $label;
                }

            case 'of':
                if ($vowel_first) {
                    return 'd\'' . $label;
                } else {
                    return 'de ' . $label;
                }

            case 'of_plur':
                if ($vowel_first) {
                    return 'd\'' . $label_plurial;
                } else {
                    return 'de ' . $label_plurial;
                }

            case 'of_the':
                if ($vowel_first) {
                    return 'de l\'' . $label;
                } elseif ($isFemale) {
                    return 'de la ' . $label;
                } else {
                    'du ' . $label;
                }

            case 'of_the_plur':
                return 'des ' . $label_plurial;

            case 'of_this':
                if ($isFemale) {
                    return 'de cette ' . $label;
                } elseif ($vowel_first) {
                    return 'de cet ' . $label;
                } else {
                    return 'de ce ' . $label;
                }

            case 'of_those':
                return 'de ces ' . $label;
        }
        return $label;
    }

    public static function makeObjectUrl($object_name, $id_object)
    {
        if ($object_name && $id_object) {
            switch ($object_name) {
                case 'Product':
                    return DOL_URL_ROOT . '/product/card.php?id=' . $id_object;
            }
        }

        return '';
    }

    public static function makeObjectName(BimpDb $bdb, $object_name, $id_object)
    {
        if (is_null($object_name) || !$object_name) {
            return '';
        }
        if (array_key_exists($object_name, BDS_Report::$objectsLabels)) {
            $objectLabel = ucfirst(BDS_Report::getObjectLabel($object_name));
        } else {
            $objectLabel = ucfirst($object_name);
        }

        if (!is_null($id_object) && $id_object) {
            $objectLabel .= ' n° ' . $id_object;
            switch ($object_name) {
                case 'Product':
                    $ref = $bdb->getValue('product', 'ref', '`rowid` = ' . (int) $id_object);
                    if (!is_null($ref) && $ref) {
                        $objectLabel .= ' - ' . $ref;
                    }
                    break;
            }
        }
        return $objectLabel;
    }
}
