<?php

class BimpTools
{

    public static $currencies = array(
        'EUR' => array(
            'label' => 'euro',
            'icon'  => 'euro',
            'html'  => '&euro;'
        ),
        'USD' => array(
            'label' => 'dollar',
            'icon'  => 'dollar',
            'html'  => '&#36;'
        )
    );

    // Gestion GET / POST

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

    // Gestion des objects Dolibarr:

    public static function getDolObjectList($instance, $filters = array())
    {
        //todo
        return array();
    }

    public static function getDolObjectUrl($object)
    {
        $file = strtolower(get_class($object)) . '/card.php';
        $primary = 'id';
        if (is_a($object, 'Societe')) {
            $primary = 'socid';
        }
        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
            return DOL_URL_ROOT . '/' . $file . (isset($object->id) && $object->id ? '?' . $primary . '=' . $object->id : '');
        }
        return '';
    }

    // Gestion générique des objets: 

    public static function getObjectTable(BimpObject $parent, $id_object_field, $object = null)
    {
        if (is_null($parent) || !$parent) {
            return null;
        }

        if (is_null($object) || !$object || !is_object($object)) {
            $object = $parent->config->getObject('fields/' . $id_object_field . '/object', null, true, 'object');
            if (is_null($object) || !is_object($object)) {
                return null;
            }
        }

        if (is_a($object, 'BimpObject')) {
            return $object->getTable();
        }

        if (property_exists($object, 'table_element')) {
            return $object->table_element;
        }

        $instance = $parent->getConf('fields/' . $id_object_field . '/object', null, true, 'any');
        if (is_string($instance)) {
            return $parent->getConf('objects/' . $instance . '/table', null, false);
        }
        return $parent->getConf('fields/' . $id_object_field . '/object/table', null, false);
    }

    public static function getObjectPrimary(BimpObject $parent, $id_object_field, $object = null)
    {
        if (is_null($parent) || !$parent) {
            return null;
        }

        if (is_null($object) || !$object || !is_object($object)) {
            $object = $parent->config->getObject('fields/' . $id_object_field . '/object', null, true, 'object');
            if (is_null($object) || !is_object($object)) {
                return null;
            }
        }

        if (is_a($object, 'BimpObject')) {
            return $object->getPrimary();
        }

        if (property_exists($object, 'id')) {
            return 'rowid';
        }

        $instance = $parent->getConf('fields/' . $id_object_field . '/object', null, true, 'any');
        if (is_string($instance)) {
            return $parent->getConf('objects/' . $instance . '/primary', null, false);
        }
        return $parent->getConf('fields/' . $id_object_field . '/object/primary', null, false);
    }

    // Gestion fichiers:

    public static function makeDirectories($dir_tree, $root_dir = null)
    {
        if (is_null($root_dir)) {
            $root_dir = DOL_DATA_ROOT . '/bimpdatasync';
        }

        if (!file_exists($root_dir)) {
            if (!mkdir($root_dir, 0777)) {
                return 'Echec de la création du dossier "' . $root_dir . '"';
            }
        }

        foreach ($dir_tree as $dir => $sub_dir_tree) {
            if (!file_exists($root_dir . '/' . $dir)) {
                if (!mkdir($root_dir . '/' . $dir, 0777)) {
                    return 'Echec de la création du dossier "' . $root_dir . '/' . $dir . '"';
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
                        }
                    }
                }
            }
        }

        return 0;
    }

    public static function renameFile($dir, $old_name, $new_name)
    {
        if (!preg_match('/.+\/$/', $dir)) {
            $dir .= '/';
        }

        if (!file_exists($dir . $old_name)) {
            return 'Le fichier "' . $old_name . '" n\'existe pas';
        }

        if (file_exists($dir . $new_name)) {
            return 'Le fichier "' . $new_name . '" existe déjà';
        }

        if (!rename($dir . $old_name, $dir . $new_name)) {
            return 'Echec du renommage du fichier';
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
        return '';
    }

    // Gestion Logs: 

    public static function logTechnicalError($object, $method, $msg)
    {
        if (is_object($object)) {
            $object = get_class($object);
        }

        $message = '[ERREUR TECHNIQUE] ' . $object . '::' . $method . '() - ' . $msg;
        dol_syslog($message, LOG_ERR);
    }

    // Gestion SQL:

    public static function getSqlSelect($return_fields = null, $default_alias = 'a')
    {
        $sql = 'SELECT ';

        if (!is_null($return_fields) && is_array($return_fields) && count($return_fields)) {
            $first_loop = true;
            foreach ($return_fields as $field) {
                if (!$first_loop) {
                    $sql .= ', ';
                } else {
                    $first_loop = false;
                }
                if (preg_match('/\./', $field)) {
                    $sql .= $field;
                } elseif (!is_null($default_alias) && $default_alias) {
                    $sql .= $default_alias . '.' . $field;
                }
            }
        } else {
            $sql .= '*';
        }

        return $sql;
    }

    public static function getSqlFrom($table, $joins = null, $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($table) && $table) {
            if (is_null($default_alias)) {
                $default_alias = '';
            }

            if (!$default_alias && (!is_null($joins) && is_array($joins) && count($joins))) {
                $default_alias = 'a';
            }
            $sql = ' FROM ' . MAIN_DB_PREFIX . $table . ($default_alias ? ' ' . $default_alias : '');

            if (!is_null($joins) && is_array($joins) && count($joins)) {
                foreach ($joins as $join) {
                    if (isset($join['table']) && isset($join['alias']) && isset($join['on'])) {
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join['table'] . ' ' . $join['alias'] . ' ON ' . $join['on'];
                    }
                }
            }
        }
        return $sql;
    }

    public static function getSqlWhere($filters, $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($filters) && is_array($filters) && count($filters)) {
            $sql .= ' WHERE ';
            $first_loop = true;
            foreach ($filters as $field => $filter) {
                if (!$first_loop) {
                    $sql .= ' AND ';
                } else {
                    $first_loop = false;
                }

                $sql .= self::getSqlFilter($field, $filter, $default_alias);
            }
        }
        return $sql;
    }

    public static function getSqlFilter($field, $filter, $default_alias)
    {
        $sql = '';

        if (is_array($filter) && isset($filter['or'])) {
            $sql .= ' (';
            $fl = true;
            foreach ($filter['or'] as $or_field => $or_filter) {
                if (!$fl) {
                    $sql .= ' OR ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($or_field, $or_filter, $default_alias);
            }
            $sql .= ')';
        } elseif (is_array($filter) && isset($filter['and'])) {
            $fl = true;
            foreach ($filter['and'] as $and_filter) {
                if (!$fl) {
                    $sql .= ' AND ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($field, $and_filter, $default_alias);
            }
        } else {
            if (preg_match('/\./', $field)) {
                $sql .= $field;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql .= $default_alias . '.' . $field;
            } else {
                $sql .= '`' . $field . '`';
            }

            if (is_array($filter)) {
                if (isset($filter['min']) && isset($filter['max'])) {
                    $sql .= ' BETWEEN ' . (is_string($filter['min']) ? '\'' . $filter['min'] . '\'' : $filter['min']);
                    $sql .= ' AND ' . (is_string($filter['max']) ? '\'' . $filter['max'] . '\'' : $filter['max']);
                } elseif (isset($filter['operator']) && isset($filter['value'])) {
                    $sql .= ' ' . $filter['operator'] . ' ' . (is_string($filter['value']) ? '\'' . $filter['value'] . '\'' : $filter['value']);
                } elseif (isset($filter['part_type']) && isset($filter['part'])) {
                    $sql .= ' LIKE \'';
                    switch ($filter['part_type']) {
                        case 'beginning':
                            $sql .= $filter['part'] . '%';
                            break;

                        case 'end':
                            $sql .= '%' . $filter['part'];
                            break;

                        default:
                        case 'middle':
                            $sql .= '%' . $filter['part'] . '%';
                            break;
                    }
                    $sql .= '\'';
                } elseif (isset($filter['in'])) {
                    if (is_array($filter['in'])) {
                        $sql .= ' IN (' . implode(',', $filter['in']) . ')';
                    } else {
                        $sql .= ' IN (' . $filter['in'] . ')';
                    }
                } elseif (isset($filter['not_in'])) {
                    if (is_array($filter['not_in'])) {
                        $sql .= ' NOT IN (' . implode(',', $filter['not_in']) . ')';
                    } else {
                        $sql .= ' NOT IN (' . $filter['not_in'] . ')';
                    }
                } else {
                    $sql .= ' IN (' . implode(',', $filter) . ')';
                }
            } elseif ($filter === 'IS_NULL') {
                $sql .= ' IS NULL';
            } elseif ($filter === 'IS_NOT_NULL') {
                $sql .= ' IS NOT NULL';
            } else {
                $sql .= ' = ' . (is_string($filter) ? '\'' . $filter . '\'' : $filter);
            }
        }
        return $sql;
    }

    public static function getSqlOrderBy($order_by = null, $order_way = 'ASC', $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($order_by) && $order_by) {
            $sql .= ' ORDER BY ';
            if (preg_match('/\./', $order_by)) {
                $sql .= $order_by;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql .= $default_alias . '.' . $order_by;
            } else {
                $sql .= '`' . $order_by . '`';
            }

            if (is_null($order_way) || !$order_way) {
                $order_way = 'ASC';
            }

            $sql .= ' ' . strtoupper($order_way);
        }

        return $sql;
    }

    public static function getSqlLimit($n = 0, $p = 1)
    {
        $sql = '';
        if (!is_null($n) && $n > 0) {
            if (is_null($p) || !$p) {
                $p = 1;
            }

            if ($p > 1) {
                $offset = ($n * ($p - 1));
            } else {
                $offset = 0;
            }
            $sql .= ' LIMIT ' . $offset . ', ' . $n;
        }
        return $sql;
    }

    // Gestion de données:

    public static function checkValueByType($type, &$value)
    {
        if (is_null($value)) {
            return false;
        }

        switch ($type) {
            case 'any':
                return true;

            case 'string':
                return is_string($value) || is_numeric($value);

            case 'array':
                if (is_string($value)) {
                    $rows = explode(',', $value);
                    $value = array();
                    foreach ($rows as $r) {
                        if (preg_match('/^(.+)=>(.+)$/', $r, $matches)) {
                            $value[$matches[1]] = $matches[2];
                        } else {
                            $value[] = $r;
                        }
                    }
                    return true;
                }
                return is_array($value);

            case 'id':
            case 'id_object':
            case 'int':
                if (is_string($value)) {
                    if (preg_match('/^\-?[0-9]+$/', $value)) {
                        $value = (int) $value;
                    }
                }
                return is_int($value);

            case 'bool':
                if (is_string($value)) {
                    if (in_array(strtolower($value), array('true', 'oui', 'yes', 'vrai', '1'))) {
                        $value = 1;
                    }
                    if (in_array(strtolower($value), array('false', 'non', 'no', 'faux', '0'))) {
                        $value = 0;
                    }
                }

                if (is_numeric($value)) {
                    if ($value !== 0) {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                }
                return is_int($value);

            case 'float':
                if (is_string($value)) {
                    $value = str_replace(',', '.', $value);
                    if (preg_match('/^\-?[0-9]+\.?[0-9]*$/', $value)) {
                        $value = (float) $value;
                    }
                }

                if (is_numeric($value)) {
                    $value = (float) $value;
                }

                return is_float($value);

            case 'object':
                return is_object($value);

            case 'date':
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $value)) {
                    return true;
                }
                return false;

            case 'time':
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                    return true;
                }
                return false;

            case 'datetime':
                if (preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
                    return true;
                }
                return false;
        }
        return true;
    }

    // Gestion des durées:

    public static function getTimeDataFromSeconds($total_seconds)
    {
        $return = array(
            'total_seconds' => $total_seconds,
            'total_minutes' => 0,
            'total_hours'   => 0,
            'total_days'    => 0,
            'seconds'       => 0,
            'minutes'       => 0,
            'hours'         => 0,
            'days'          => 0
        );

        if ($total_seconds >= 60) {
            $return['total_minutes'] = (int) floor($total_seconds / 60);
            $return['secondes'] = (int) ($total_seconds - ($return['total_minutes'] * 60));
        } else {
            $return['secondes'] = $total_seconds;
        }

        if ($return['total_minutes'] >= 60) {
            $return['total_hours'] = (int) floor($return['total_minutes'] / 60);
            $return['minutes'] = (int) ($return['total_minutes'] - ($return['total_hours'] * 60));
        } else {
            $return['minutes'] = $return['total_minutes'];
        }

        if ($return['total_hours'] >= 24) {
            $return['total_days'] = (int) floor($return['total_hours'] / 24);
            $return['hours'] = (int) ($return['total_hours'] - ($return['total_days'] * 24));
        } else {
            $return['hours'] = $return['total_hours'];
        }

        $return['days'] = $return['total_days'];

        return $return;
    }

    public static function displayTimefromSeconds($total_seconds)
    {
        $timer = self::getTimeDataFromSeconds((int) $total_seconds);
        $html = '<span class="timer">';
        if ($timer['days'] > 0) {
            $html .= $timer['days'] . ' j ';
            $html .= $timer['hours'] . ' h ';
            $html .= $timer['minutes'] . ' min ';
        } elseif ($timer['hours'] > 0) {
            $html .= $timer['hours'] . ' h ';
            $html .= $timer['minutes'] . ' min ';
        } elseif ($timer['minutes'] > 0) {
            $html .= $timer['minutes'] . ' min ';
        }
        $html .= $timer['secondes'] . ' sec ';
        $html .= '</span>';
        return $html;
    }

    // Devises / prix: 

    public static function getCurrencyIcon($currency)
    {
        if (array_key_exists(strtoupper($currency), self::$currencies)) {
            return self::$currencies[$currency]['icon'];
        }

        return 'euro';
    }

    public static function getCurrencyHtml($currency)
    {
        if (array_key_exists(strtoupper($currency), self::$currencies)) {
            return self::$currencies[$currency]['html'];
        }

        return '&euro;';
    }

    public static function displayMoneyValue($value, $currency)
    {
        if (is_numeric($value)) {
            $value = (float) $value;
        }

        if (!is_float($value)) {
            return $value;
        }

        $value = round($value, 2);

        return price($value, 1, '', 1, -1, -1, $currency);
    }

    public static function getTaxes($id_country = 1)
    {
        global $db;
        $bdb = new BimpDb($db);

        $taxes = array();
        $rows = $bdb->getRows('c_tva', '`fk_pays` = ' . $id_country . ' AND `active` = 1', null, 'array', array('rowid', 'taux'));
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $taxes[$r['rowid']] = $r['taux'];
            }
        }

        return $taxes;
    }

    public static function getTaxeRateById($id_tax)
    {
        global $db;
        $bdb = new BimpDb($db);
        return $bdb->getValue('c_tva', 'taux', '`rowid` = ' . (int) $id_tax);
    }

    public static function calculatePriceTaxEx($amout_tax_in, $tax_rate_percent, $precision = 6)
    {
        $rate = 1 + ($tax_rate_percent / 100);
        return (float) round($amout_tax_in / $rate, $precision);
    }

    public static function calculatePriceTaxIn($amout_tax_ex, $tax_rate_percent, $precision = 6)
    {
        $rate = 1 + ($tax_rate_percent / 100);
        return (float) round($amout_tax_ex * $rate, $precision);
    }

    // Divers:

    public static function ucfirst($str)
    {
        if (preg_match('/^[éèêëàâäïîöôùûüŷÿ].*/', $str)) {
            $first = substr($str, 1, 1);
            $first = preg_replace('/[éèëê]/', 'e', $first);
            $first = preg_replace('/[àâä]/', 'a', $first);
            $first = preg_replace('/[îï]/', 'i', $first);
            $first = preg_replace('/[ôö]/', 'o', $first);
            $first = preg_replace('/[ùûü]/', 'i', $first);
            $first = preg_replace('/[ŷÿ]/', 'y', $first);
            $str = substr_replace($str, $first, 0, 2);
        }


        return ucfirst($str);
    }

    public static function lcfirst($str)
    {
        if (preg_match('/^[éèêëàâäïîöôùûüŷÿ].*/', $str)) {
            $first = substr($str, 1, 1);
            $first = preg_replace('/[éèëê]/', 'e', $first);
            $first = preg_replace('/[àâä]/', 'a', $first);
            $first = preg_replace('/[îï]/', 'i', $first);
            $first = preg_replace('/[ôö]/', 'o', $first);
            $first = preg_replace('/[ùûü]/', 'i', $first);
            $first = preg_replace('/[ŷÿ]/', 'y', $first);
            $str = substr_replace($str, $first, 0, 2);
        }

        return lcfirst($str);
    }

    public static function getAlertColor($class)
    {
        switch ($class) {
            case 'success':
                return '348C41';
            case 'info':
                return '3B6EA0';
            case 'warning':
                return 'E69900';
            case 'danger':
                return 'A00000';
            default:
                return '636363';
        }
    }

    public static function getDolEventsMsgs($types = array('mesgs, errors, warnings'), $clean = true)
    {
        $return = array();
        foreach ($types as $type) {
            if (isset($_SESSION['dol_events'][$type])) {
                $return = array_merge($_SESSION['dol_events'][$type]);
                if ($clean) {
                    unset($_SESSION['dol_events'][$type]);
                }
            }
        }

        return $return;
    }

    public static function changeColorLuminosity($color_code, $percentage_adjuster = 0)
    {
        $percentage_adjuster = round($percentage_adjuster / 100, 2);
        if (is_array($color_code)) {
            $r = $color_code["r"] + (round($color_code["r"]) * $percentage_adjuster);
            $g = $color_code["g"] + (round($color_code["g"]) * $percentage_adjuster);
            $b = $color_code["b"] + (round($color_code["b"]) * $percentage_adjuster);

            return array("r" => round(max(0, min(255, $r))),
                "g" => round(max(0, min(255, $g))),
                "b" => round(max(0, min(255, $b))));
        } else if (preg_match("/#/", $color_code)) {
            $hex = str_replace("#", "", $color_code);
            $r = (strlen($hex) == 3) ? hexdec(substr($hex, 0, 1) . substr($hex, 0, 1)) : hexdec(substr($hex, 0, 2));
            $g = (strlen($hex) == 3) ? hexdec(substr($hex, 1, 1) . substr($hex, 1, 1)) : hexdec(substr($hex, 2, 2));
            $b = (strlen($hex) == 3) ? hexdec(substr($hex, 2, 1) . substr($hex, 2, 1)) : hexdec(substr($hex, 4, 2));
            $r = round($r + ($r * $percentage_adjuster));
            $g = round($g + ($g * $percentage_adjuster));
            $b = round($b + ($b * $percentage_adjuster));

            return "#" . str_pad(dechex(max(0, min(255, $r))), 2, "0", STR_PAD_LEFT)
                    . str_pad(dechex(max(0, min(255, $g))), 2, "0", STR_PAD_LEFT)
                    . str_pad(dechex(max(0, min(255, $b))), 2, "0", STR_PAD_LEFT);
        }
    }
}
