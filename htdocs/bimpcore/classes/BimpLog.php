<?php

class BimpLog
{
    public static $h_file = null;

    public static function getHFile()
    {
        if (is_null(self::$h_file)) {
            global $user;
            $dir = DOL_DATA_ROOT . '/bimpcore/logs/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . (isset($user->id) ? $user->id : '0');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            if (file_exists($dir)) {
                $file_name = (isset($user->id) ? $user->id : '0') . '_' . date('H-i-s') . '.txt';
                self::$h_file = fopen($dir . '/' . $file_name, 'a');
            }
        }

        return self::$h_file;
    }

    public static function actionStart($action_name, $desc, $object = null)
    {
        self::writeFile(array(
            'NEW',
            date('H:i:s'),
            $action_name,
            (is_object($object) ? get_class($object) : ''),
            (is_object($object) && isset($object->id) ? $object->id : ''),
            $desc
        ));
    }

    public static function actionErrors($errors)
    {
        if (is_string($errors) && $errors) {
            $errors = array($errors);
        }

        if (is_array($errors) && count($errors)) {
            $text = '';
            foreach ($errors as $e) {
                if ($text) {
                    $text .= '[RC]';
                }
                $text .= $e;
            }

            self::writeFile(array(
                'ERR',
                $text
            ));
        }
    }

    public static function actionWarnings($warnings)
    {
        if (is_string($warnings) && $warnings) {
            $warnings = array($warnings);
        }
        if (is_array($warnings) && count($warnings)) {
            $text = '';
            foreach ($warnings as $w) {
                if ($text) {
                    $text .= '[RC]';
                }
                $text .= $w;
            }
            self::writeFile(array(
                'WRN',
                $text
            ));
        }
    }

    public static function actionInfos($infos)
    {
        if (is_string($infos) && $infos) {
            $infos = array($infos);
        }
        if (is_array($infos) && count($infos)) {
            $text = '';
            foreach ($infos as $i) {
                if ($text) {
                    $text .= '[RC]';
                }
                $text .= $i;
            }
            self::writeFile(array(
                'INF',
                $text
            ));
        }
    }

    public static function actionData($data, $label = '')
    {
        if (!empty($data)) {
            $json = json_encode($data);

            self::writeFile(array(
                'DAT',
                $label,
                $json
            ));
        }
    }

    public static function actionEnd($action_name, $errors = array(), $warnings = array())
    {
        self::actionErrors($errors);
        self::actionWarnings($warnings);

        self::writeFile(array(
            'END',
            $action_name,
            (count($errors) ? '0' : '1')
        ));
    }

    public static function writeFile($data)
    {
        $line = '';
        foreach ($data as $d) {
            if ($line) {
                $line .= ';';
            }
            $d = str_replace(';', '[PV]', $d);
            $d = str_replace("\n", '[RC]', $d);
            
            $line .= $d;
        }
        $line .= "\n";

        fwrite(self::getHFile(), $line);
    }

    public static function loadFile($file)
    {
        $actions = array();
        
        if (file_exists($file)) {
            $rows = file($file);
            
            
        }
        
        return $actions;
    }

    public static function end()
    {
        if (!is_null(self::$h_file)) {
            fclose(self::$h_file);
            self::$h_file = null;
        }
    }
}
