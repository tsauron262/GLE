<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php';

abstract class BDSImportProcess extends BDSProcess
{

    const BDS_STATUS_IMPORTED = 0;
    const BDS_STATUS_IMPORTING = 1;
    const BDS_STATUS_IMPORT_FAIL = -1;

    public static $status_labels = array(
        0  => 'importé',
        1  => 'en cours d\'import',
        -1 => 'échec import'
    );

    public static function getClassName()
    {
        return 'BDSImportProcess';
    }

    // Traitements des données: 

    public function getElementsFromData($data, $ref_key = 'ref')
    {
        $elements = array();

        if (is_array($data)) {
            foreach ($data as $line) {
                if (is_array($line)) {
                    if (isset($line[$ref_key])) {
                        $elements[] = $line[$ref_key];
                    }
                }
            }
        }

        return $elements;
    }

    public function createBimpObjects($module, $object_name, $objects_data, &$errors = array(), $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'check_refs'       => true,
                    'update_if_exists' => false, // Si check_refs == true ou si primary dans les data
                    'report_success'   => true,
                    'report_warning'   => true,
                    'report_error'     => true
                        ), $params);

        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            $errors[] = 'Création des objets de type "' . $object_name . '" (module "' . $module . '") impossible: ce type d\'objet n\'existe pas';
        } else {
            $primary = $instance->getPrimary();
            $ref_prop = $instance->getRefProperty();
            $this->setCurrentObject($instance);

            $cleanCache = (count($objects_data > 1000) ? true : false);
            foreach ($objects_data as $idx => $data) {
                $obj = null;
                $this->incProcessed();

                if ($ref_prop) {
                    $ref = BimpTools::getArrayValueFromPath($data, $ref_prop, '');
                }

                // Vérification de l'existance de la référence: 
                if ($primary && isset($data[$primary])) {
                    if ((int) $data[$primary]) {
                        $obj = BimpCache::getBimpObjectInstance($module, $object_name, (int) $data[$primary]);

                        if (!BimpObject::objectLoaded($obj)) {
                            if ($params['report_warning']) {
                                $this->Alert(BimpTools::ucfirst($obj->getLabel('the')) . ' d\'ID ' . $data[$primary] . ' n\'existe pas', $obj, $ref);
                            }
                            $this->incIgnored($instance);
                            continue;
                        }
                    }
                }

                if (!BimpObject::objectLoaded($obj) && $params['check_refs'] && $ref_prop) {
                    if (isset($data[$ref_prop]) && !empty($data[$ref_prop])) {
                        $obj = BimpCache::findBimpObjectInstance($module, $object_name, array(
                                    $ref_prop => $data[$ref_prop]
                                        ), true);

                        if (BimpObject::objectLoaded($obj)) {
                            if (!$params['update_if_exists']) {
                                $msg = BimpTools::ucfirst($obj->getLabel('a')) . ' existe déjà pour la référence "' . $data[$ref_prop] . '":<br/>' . $obj->getLink();
                                if ($params['report_warning']) {
                                    $this->Alert($msg, $instance, $data[$ref_prop]);
                                }
                                $this->incIgnored($instance);
                                continue;
                            }
                        }
                    } else {
                        $msg = 'Ligne n°' . ($idx + 1) . ': référence absente';
                        if ($params['report_warning']) {
                            $this->Alert($msg, $instance, '');
                        }
                        $this->incIgnored();
                        continue;
                    }
                }

                if (BimpObject::objectLoaded($obj) && !$params['update_if_exists']) {
                    $this->incIgnored($instance);
                    continue;
                }

                $obj_errors = array();
                $obj_warnings = array();

                if (!BimpObject::objectLoaded($obj)) {
                    // Création de l'objet: 
                    $obj = BimpObject::createBimpObject($module, $object_name, $data, true, $obj_errors, $obj_warnings);

                    if (count($obj_errors)) {
                        if ($params['report_error']) {
                            $this->Error($obj_errors, $instance, isset($data[$ref_prop]) ? $data[$ref_prop] : '');
                        }
                    } else {
                        if ($params['report_success']) {
                            $this->Success('Création effectuée avec succès', $obj, $obj->getRef());
                        }
                        $this->incCreated();
                    }

                    if (count($obj_warnings) && $params['report_warning']) {
                        $this->Alert($obj_warnings, $obj, $obj->getRef());
                    }
                } else {
                    // Mise à jour de l'objet: 
                    $obj_errors = $obj->validateArray($data);

                    if (!count($obj_errors)) {
                        $obj_errors = $obj->update($obj_warnings, true);
                    }

                    if (count($obj_errors)) {
                        if ($params['report_error']) {
                            $this->Error(BimpTools::getMsgFromArray($obj_errors, 'Echec de la mise à jour'), $obj, $obj->getRef());
                        }
                    } else {
                        if ($params['report_success']) {
                            $this->Success('Mise à jour effectuée avec succès', $obj, $obj->getRef());
                        }
                        $this->incUpdated();
                    }

                    if (count($obj_warnings) && $params['report_warning']) {
                        $this->Alert(BimpTools::getMsgFromArray($obj_warnings, 'Erreur(s) lors de la mise à jour'), $obj, $obj->getRef());
                    }
                }

                if ($cleanCache) {
                    BimpCache::$cache = array();
                }
            }
        }
    }

    // Traitement des fichiers: 

    public function cleanTxtFile($file, $fromFormat = '', $utf8_decode = false)
    {
        if (file_exists($file)) {
            $str = file_get_contents($file);

            if ($fromFormat) {
                $this->Msg('Conversion depuis le format "' . $fromFormat . '" de "' . pathinfo($file, PATHINFO_FILENAME) . '"');
                $str = iconv($fromFormat, 'UTF-8', $str);
            }

            if ($utf8_decode) {
                $this->Msg('Décodage UTF-8 de "' . pathinfo($file, PATHINFO_FILENAME) . '"');
                $str = utf8_decode($str);
            }

            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\r", "\n", $str);

            file_put_contents($file, $str);
        }
    }

    public function getCsvFileDataByKeys($file, $keys, &$errors = array(), $delimiter = ';', $headerRowIndex = 0, $firstDataRowIndex = 1, $params = array())
    {
        // $headerRowIndex : mettre -1 si pas de header ($keys doit alors être sous la forme:  array(index_colonne => nom_champ)

        if ($delimiter === '\t') {
            $delimiter = "\t";
        }

//        $this->debug_content .= 'Del: "' . $delimiter . '" <br/>';

        $params = BimpTools::overrideArray(array(
                    'ref_key'       => 'ref', // Nom du champ contenant la ref. 
                    'filter_by_ref' => true, // Filtrer selon $this->references
                    'from_format'   => '', // Format d'origine du fichier pour conversion. 
                    'utf8_decode'   => false, // Faire un utf8-decode()
                    'clean_file'    => true, // Nettoyer le ficheir
                    'clean_value'   => false, // Nettoyer les valeurs
                    'part_file_idx' => 0 // Index du sous-fichier
                        ), $params);

        if ((int) $params['part_file_idx']) {
            $pathinfo = pathinfo($file);
            $partDir = $this->getFilePartsDirname($pathinfo['basename']);
            if (is_dir($pathinfo['dirname'])) {
                $file = $pathinfo['dirname'] . '/' . $partDir . '/' . $pathinfo['filename'] . '_part' . $params['part_file_idx'] . '.' . $pathinfo['extension'];
            } else {
                $errors[] = 'Le dossier "' . $pathinfo['dirname'] . '/' . $partDir . '" n\'existe pas';
                return array();
            }
        }
        $this->Success("Fichier utilisée : ".$file);

        $data = array();

        if (file_exists($file)) {
            if ($params['clean_file']) {
                $this->cleanTxtFile($file, $params['from_format'], $params['utf8_decode']);
            }

            $this->debug_content .= 'Fichier traité: ' . $file . '<br/>';

            $rows = file($file, FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES);

//            $this->DebugData($rows, 'Lignes fichier');

            $cols_idx = array();

            if ($headerRowIndex >= 0) {
                // Récupération des indexes colonnes via les codes en en-tête: 
                if (isset($rows[$headerRowIndex])) {
                    $header_row = explode($delimiter, $rows[$headerRowIndex]);

                    // Récupération des indexes des champs: 
                    foreach ($header_row as $idx => $key) {
                        $key = trim($key);
                        if (isset($keys[$key])) {
                            if (is_array($keys[$key]) && isset($keys[$key]['name'])) {
                                $cols_idx[$keys[$key]['name']] = $idx;
                            } else {
                                $cols_idx[$keys[$key]] = $idx;
                            }
                        }
                    }

                    // Vérification de la présence de tous les champs: 
                    foreach ($keys as $key => $field) {
                        if (is_array($field)) {
                            $field_name = BimpTools::getArrayValueFromPath($field, 'name', $key);
                            $field_label = BimpTools::getArrayValueFromPath($field, 'label', $key);
                            $required = BimpTools::getArrayValueFromPath($field, 'required', 1);
                        } else {
                            $field_name = $field;
                            $field_label = $field;
                            $required = 0;
                        }
                        if (!isset($cols_idx[$field_name]) && $required) {
                            $errors[] = 'Le champ "' . $key . '" ' . ($field_label != $key ? ' (' . $field_label . ')' : '') . ' est absent du fichier "' . pathinfo($file, PATHINFO_FILENAME) . '"';
                        }
                    }
                } else {
                    $errors[] = 'Le fichier "' . pathinfo($file, PATHINFO_FILENAME) . '" n\'est pas formaté correctement (en-tête codes champs absent)';
                }
            } else {
                foreach ($keys as $col_idx => $field) {
                    if (is_array($field) && isset($field['name'])) {
                        $cols_idx[$field['name']] = $col_idx;
                    } else {
                        $cols_idx[$field] = $col_idx;
                    }
                }
            }

//            $this->DebugData($cols_idx, 'Indexes colonnes');

            if (!count($errors)) {
                for ($i = $firstDataRowIndex; $i < count($rows); $i++) {
                    $row = explode($delimiter, $rows[$i]);

//                    $this->DebugData($row, 'LIGNE ' . $i);

                    $row_data = array();
                    foreach ($keys as $key => $field) {
                        if (is_array($field)) {
                            $field_name = BimpTools::getArrayValueFromPath($field, 'name', $key);
                        } else {
                            $field_name = $field;
                        }
                        if (isset($row[$cols_idx[$field_name]])) {
                            $value = $row[$cols_idx[$field_name]];
                            if ($params['clean_value']) {
                                // On enlèvre les guillements avant et après la valeur: 
                                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                                    $value = $matches[1];
                                }
                                // On enlève tout espace ou saut de ligne avant et après la valeur: 
                                if (preg_match('/^[ \t\s\r\n]*(.*)[ \t\s\r\n]*$/U', $value, $matches)) {
                                    $value = $matches[1];
                                }
                            }
                            $row_data[$field_name] = trim($value);
                        }
                    }

                    if (!empty($this->references) && $params['filter_by_ref'] && $params['ref_key'] && isset($row_data[$params['ref_key']])) {
                        if (!in_array($row_data[$params['ref_key']], $this->references)) {
                            continue;
                        }
                    }

                    if ($params['ref_key'] && isset($row_data[$params['ref_key']])) {
                        $data[$row_data[$params['ref_key']]] = $row_data;
                    } else {
                        $data[] = $row_data;
                    }
                }
            }
        } else {
            $errors[] = 'Le fichier "' . pathinfo($file, PATHINFO_FILENAME) . '" n\'existe pas : '.$file;
        }

//        $this->DebugData($data, 'Données fichier');

        return $data;
    }

    public function getFilePartsDirname($fileName)
    {
        $pathinfo = pathinfo($fileName);
        return $pathinfo['filename'] . '_' . $pathinfo['extension'] . '_parts';
    }

    public function makeCsvFileParts($dir, $fileName, &$errors = array(), $nbRowsPerFile = 1000, $nbHeaderRows = 1)
    {
        if (!file_exists($dir . '/' . $fileName)) {
            $errors[] = 'Le fichier "' . $dir . '/' . $fileName . '" n\'existe pas';
            return;
        }

        $this->cleanTxtFile($dir . '/' . $fileName);

        $rows = file($dir . '/' . $fileName, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        $pathinfo = pathinfo($fileName);
        $partsDir = $dir . '/' . $this->getFilePartsDirname($fileName);

        if (!is_dir($partsDir)) {
            // Création du dossier: 
            if (!mkdir($partsDir)) {
                $errors[] = 'Echec de la création du dossier "' . $partsDir . '"';
                return;
            }
        } else {
            // On vide le dossier s'il existe: 
            $files = scandir($partsDir);

            foreach ($files as $f) {
                if (!in_array($f, array('.', '..'))) {
                    unlink($partsDir . '/' . $f);
                }
            }
        }

        $header_str = '';
        $file_str = '';
        $file_idx = 1;
        $i = 0;
        $n = 0;

        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        // Création des fichiers: 
        foreach ($rows as $r) {
            if ($i < $nbHeaderRows) {
                $header_str .= $r . "\n";
            } else {
                if (!$n) {
                    $file_str = $header_str;
                }

                $file_str .= $r . "\n";

                $n++;
                $i++;

                if ($n >= $nbRowsPerFile) {
                    // Création du fichier: 
                    if (!file_put_contents($partsDir . '/' . $pathinfo['filename'] . '_part' . $file_idx . '.' . $pathinfo['extension'], $file_str)) {
                        $errors[] = 'Echec de la création du fichier n°' . $file_idx;
                    } else {
                        $this->Msg('Créa fichier "' . $pathinfo['filename'] . '_part' . $file_idx . '.' . $pathinfo['extension'] . '" OK', 'success');
                    }

                    $file_str = '';
                    $file_idx++;
                    $n = 0;
                }
            }

            $i++;
        }

        // Création du dernier fichier: 
        if ($file_str) {
            if (!file_put_contents($partsDir . '/' . $pathinfo['filename'] . '_part' . $file_idx . '.' . $pathinfo['extension'], $file_str)) {
                $errors[] = 'Echec de la création du fichier n°' . $file_idx;
            } else {
                $this->Msg('Créa fichier "' . $pathinfo['filename'] . '_part' . $file_idx . '.' . $pathinfo['extension'] . '" OK', 'success');
            }
        }
    }

    public function getPartsFilesIndexes($partsDir, &$errors = array())
    {
        if (!is_dir($partsDir)) {
            $errors[] = 'Le dossier "' . $partsDir . '" n\'existe pas';
            return array();
        }

        $files = scandir($partsDir);
        $idx = array();

        foreach ($files as $f) {
            if (!in_array($f, array('.', '..'))) {
                if (preg_match('/^.+_part(\d+)(\..+)*$/', $f, $matches)) {
                    $idx[] = (int) $matches[1];
                }
            }
        }

        sort($idx);

        return $idx;
    }
}
