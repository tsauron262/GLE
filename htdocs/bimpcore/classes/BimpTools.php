<?php

class BimpTools
{

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
        if (file_exists(DOL_DOCUMENT_ROOT . $file)) {
            return DOL_URL_ROOT . '/' . $file . (isset($object->id) && $object->id ? '?' . $primary . '=' . $object->id : '');
        }
        return '';
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
                        $value = true;
                    }
                    if (in_array(strtolower($value), array('false', 'non', 'no', 'faux', '0'))) {
                        $value = false;
                    }
                }

                if (is_numeric($value)) {
                    if ($value !== 0) {
                        $value = true;
                    } else {
                        $value = false;
                    }
                }
                return is_bool($value);

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
                if (preg_match('/^\d{4}\-\d{2}\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
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
}
