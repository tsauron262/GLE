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
        
        $message = '[ERREUR TECHNIQUE] '.$object.'::'.$method.'() - '.$msg;
        dol_syslog($message, LOG_ERR);
    }
}
