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
//        $str = str_replace(';', ',', $str);
        $str = str_replace('>', ':', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('=', ':', $str);

        return $str;
    }

    public static function getChildrenCategoriesIds(BDSDb $db, $id_parent)
    {
        $sql = 'SELECT `rowid` as id FROM ' . MAIN_DB_PREFIX . 'categorie ';
        $sql .= 'WHERE `fk_parent` = ' . (int) $id_parent . ' AND `type` = 0';

        $rows = $db->executeS($sql, 'array');
        $cats = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $cats[] = $r['id'];
            }
        }
        return $cats;
    }

    public static function getCategorieParent(BDSDb $db, $id_categorie)
    {
        $cat_id_parent = $db->getValue('categorie', 'fk_parent', '`rowid` = ' . (int) $id_categorie);
        if (is_null($cat_id_parent)) {
            return 0;
        }
        return $cat_id_parent;
    }

    public static function isCategorieChildOf(BDSDb $db, $id_categorie, $id_parent, $can_be_parent = true)
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
            $dir .= get_exdir(0, 0, 0, 0, $product, 'product') . dol_sanitizeFileName($product->ref) . '/';
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

                case 'Categorie':
                    return DOL_URL_ROOT . '/categories/viewcat.php?id=' . $id_object;

                case 'Societe':
                    return DOL_URL_ROOT . '/societe/card.php?socid=' . $id_object;

                case 'Contact':
                    return DOL_URL_ROOT . '/contact/card.php?id=' . $id_object;

                case 'Commande':
                    return DOL_URL_ROOT . '/commande/card.php?id=' . $id_object;
            }
        }

        return '';
    }

    public static function makeObjectName(BDSDb $bdb, $object_name, $id_object, $include_id = true)
    {
        if (is_null($object_name) || !$object_name) {
            return '';
        }

        $ref = null;
        if (!is_null($id_object) && $id_object) {
            $objectLabel .= ' n° ' . $id_object;
            $ref = null;
            switch ($object_name) {
                case 'Product':
                    $ref = $bdb->getValue('product', 'ref', '`rowid` = ' . (int) $id_object);
                    break;

                case 'Categorie':
                    $ref = $bdb->getValue('categorie', 'label', '`rowid` = ' . (int) $id_object);
                    break;

                case 'Societe':
                    $ref = $bdb->getValue('societe', 'nom', '`rowid` = ' . (int) $id_object);
                    break;

                case 'Contact':
                    $firstname = $bdb->getValue('socpeople', 'firstname', '`rowid` = ' . (int) $id_object);
                    $lastname = $bdb->getValue('socpeople', 'lastname', '`rowid` = ' . (int) $id_object);
                    $ref = $firstname . ' ' . strtoupper($lastname);
                    break;

                case 'Commande':
                    $ref = $bdb->getValue('commande', 'ref', '`rowid` = ' . (int) $id_object);
                    break;
            }
        }

        if ($include_id) {
            $objectLabel = '';
            if (array_key_exists($object_name, BDS_Report::$objectsLabels)) {
                $objectLabel = ucfirst(BDS_Report::getObjectLabel($object_name));
            } else {
                $objectLabel = ucfirst($object_name);
            }
            if (!is_null($ref) && $ref) {
                $objectLabel .= ' - ' . $ref;
            }
        } elseif (!is_null($ref) && $ref) {
            $objectLabel = $ref;
        } else {
            if (array_key_exists($object_name, BDS_Report::$objectsLabels)) {
                $objectLabel = ucfirst(BDS_Report::getObjectLabel($object_name));
            } else {
                $objectLabel = ucfirst($object_name);
            }
        }

        return $objectLabel . ' ' . $id_object;
    }

    public static function isSubmit($key)
    {
        return (isset($_POST[$key]) || isset($_GET[$key]));
    }

    public static function getValue($key, $default_value = null)
    {
        $value = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default_value));

        if (is_string($value)) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value))));
        }

        return $value;
    }

    public static function makeDirectories($dir_tree, $root_dir = null)
    {
        if (is_null($root_dir)) {
            $root_dir = DOL_DOCUMENT_ROOT . '/bimpdatasync';
        }

        if (!file_exists($root_dir)) {
            if (!mkdir($root_dir, 0777)) {
                return 'Echec de la création du dossier "' . $root_dir . '"';
            }
        }

        chmod($root_dir, 777);

        foreach ($dir_tree as $dir => $sub_dir_tree) {
            if (!file_exists($root_dir . '/' . $dir)) {
                if (!mkdir($root_dir . '/' . $dir, 0777)) {
                    return 'Echec de la création du dossier "' . $root_dir . '/' . $dir . '"';
                } else {
                    chmod($root_dir . '/' . $dir, 777);
                }
            }
            if (!is_null($sub_dir_tree)) {
                if (is_array($sub_dir_tree) && count($sub_dir_tree)) {
                    $result = self::makeDirectories($sub_dir_tree, $root_dir . '/' . $dir);
                    if ($result) {
                        return $result;
                    }
                } elseif (is_string($sub_dir_tree)) {
                    if (!file_exists($root_dir . '/' . $dir . '/' . $sub_dir_tree)) {
                        if (!mkdir($root_dir . '/' . $dir . '/' . $sub_dir_tree, 0777)) {
                            return 'Echec de la création du dossier "' . $root_dir . '/' . $dir . '/' . $sub_dir_tree . '"';
                        } else {
                            chmod($root_dir . '/' . $dir . '/' . $sub_dir_tree, 777);
                        }
                    }
                }
            }
        }

        return 0;
    }

    public static function getDateTimeFromForm($name, $default_date = '')
    {
        if (self::isSubmit($name)) {
            $year = '' . self::getValue($name . 'year', '0000');
            $month = (int) self::getValue($name . 'month', 0);
            if ($month < 10) {
                $month = '0' . $month;
            } else {
                $month = '' . $month;
            }
            $day = (int) self::getValue($name . 'day', 0);
            if ($day < 10) {
                $day = '0' . $day;
            } else {
                $day = '' . $day;
            }
            $hour = (int) self::getValue($name . 'hour', 0);
            if ($hour < 10) {
                $hour = '0' . $hour;
            } else {
                $hour = '' . $hour;
            }
            $min = (int) self::getValue($name . 'min', 0);
            if ($min < 10) {
                $min = '0' . $min;
            } else {
                $min = '' . $min;
            }

            return $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':00';
        }
        return $default_date;
    }

    public static function renameFile($dir, $old_name, $new_name)
    {
        if (!preg_match('/.+\/$/', $dir)) {
            $dir .= '/';
        }

        if (!file_exists($dir . $old_name)) {
            return false;
        }

        if (file_exists($dir . $new_name)) {
            return false;
        }

        if (!rename($dir . $old_name, $dir . $new_name)) {
            return false;
        }
        if (file_exists($dir . 'thumbs/')) {
            $old_path = pathinfo($old_name, PATHINFO_BASENAME | PATHINFO_EXTENSION);
            $new_path = pathinfo($new_name, PATHINFO_BASENAME | PATHINFO_EXTENSION);
            $dir .= 'thumbs/';
            $suffixes = array('_mini', '_small');
            foreach ($suffixes as $suffix) {
                $old_thumb = $dir . $old_path['basename'] . $suffix . '.' . $old_path['extension'];
                if (file_exists($old_thumb)) {
                    $new_thumb = $dir . $new_path['basename'] . $suffix . '.' . $new_path['extension'];
                    rename($old_thumb, $new_thumb);
                }
            }
        }
        return true;
    }
}
