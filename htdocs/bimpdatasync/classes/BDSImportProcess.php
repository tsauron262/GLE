<?php

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
                    'update_if_exists' => false // Si check_refs == true
                        ), $params);

        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            $errors[] = 'Création des objets de type "' . $object_name . '" (module "' . $module . '") impossible: ce type d\'objet n\'existe pas';
        } else {
            $ref_prop = $instance->getRefProperty();
            $this->setCurrentObject($instance);

            foreach ($objects_data as $idx => $data) {
                $this->incProcessed();

                $obj = null;

                // Vérification de l'existance de la référence: 
                if ($params['check_refs'] && $ref_prop) {
                    if (isset($data[$ref_prop]) && !empty($data[$ref_prop])) {
                        $obj = BimpCache::findBimpObjectInstance($module, $object_name, array(
                                    $ref_prop => $data[$ref_prop]
                                        ), true);

                        if (BimpObject::objectLoaded($obj)) {
                            if (!$params['update_if_exists']) {
                                $msg = BimpTools::ucfirst($obj->getLabel('a')) . ' existe déjà pour la référence "' . $data[$ref_prop] . '":<br/>' . $obj->getLink();
                                $this->Alert($msg, $instance, $data[$ref_prop]);
                                $this->incIgnored();
                                continue;
                            }
                        }
                    } else {
                        $msg = 'Ligne n°' . ($idx + 1) . ': référence absente';
                        $this->Alert($msg, $instance, '');
                        $this->incIgnored();
                        continue;
                    }
                }

                $obj_errors = array();
                $obj_warnings = array();

                if (!BimpObject::objectLoaded($obj)) {
                    // Création de l'objet: 
                    $obj = BimpObject::createBimpObject($module, $object_name, $data, true, $obj_errors, $obj_warnings);

                    if (count($obj_errors)) {
                        $this->Error($obj_errors, $instance, isset($data[$ref_prop]) ? $data[$ref_prop] : '');
                    } else {
                        $this->Success('Création effectuée avec succès', $obj, $obj->getRef());
                        $this->incCreated();
                    }

                    if (count($obj_warnings)) {
                        $this->Alert($obj_warnings, $obj, $obj->getRef());
                    }
                } else {
                    // Mise à jour de l'objet: 
                    $obj_errors = $obj->validateArray($data);

                    if (!count($obj_errors)) {
                        $obj_errors = $obj->update($obj_warnings, true);
                    }

                    if (count($obj_errors)) {
                        $this->Error(BimpTools::getMsgFromArray($obj_errors, 'Echec de la mise à jour'), $obj, $obj->getRef());
                    } else {
                        $this->Success('Mise à jour effectuée avec succès', $obj, $obj->getRef());
                        $this->incUpdated();
                    }

                    if (count($obj_warnings)) {
                        $this->Alert(BimpTools::getMsgFromArray($obj_warnings, 'Erreur(s) lors de la mise à jour'), $obj, $obj->getRef());
                    }
                }
            }
        }
    }

    // Traitement des fichiers: 

    public function cleanTxtFile($file, $fromFormat = '')
    {
        if (file_exists($file)) {
            $str = file_get_contents($file);

            if ($fromFormat) {
                $str = iconv($fromFormat, 'UTF-8', $str);
            }

            $str = str_replace("\r", "\n", $str);

            file_put_contents($file, $str);
        }
    }

    public function getCsvFileDataFromHeaderCodes($file, $codes, &$errors = array(), $codesRowIndex = 0, $delimiter = ';', $firstDataRowIndex = 1)
    {
        $data = array();

        if (file_exists($file)) {
            $this->cleanTxtFile($file);

            $rows = file($file, FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES);

            if (isset($rows[$codesRowIndex])) {
                $codes_row = explode($delimiter, $rows[$codesRowIndex]);
                $indexes = array();

                // Récupération des indexes des champs: 
                foreach ($codes_row as $idx => $code) {
                    if (isset($codes[$code])) {
                        $indexes[$codes[$code]] = $idx;
                    }
                }

                // Vérification de la présence de tous les champs: 
                foreach ($codes as $code => $field) {
                    if (is_array($field)) {
                        $field_name = BimpTools::getArrayValueFromPath($field, 'name', $code);
                        $field_label = BimpTools::getArrayValueFromPath($field, 'label', $code);
                        $required = BimpTools::getArrayValueFromPath($field, 'required', 1);
                    } else {
                        $field_name = $field;
                        $field_label = $field;
                        $required = 1;
                    }
                    if (!isset($indexes[$field_name]) && $required) {
                        $errors[] = 'Le champ "' . $code . '" ' . ($field_label != $code ? ' (' . $field_label . ')' : '') . ' est absent du fichier "' . pathinfo($file, PATHINFO_FILENAME) . '"';
                    }
                }

                if (!count($errors)) {
                    for ($i = $firstDataRowIndex; $i < count($rows); $i++) {
                        $row = explode($delimiter, $rows[$i]);

                        $row_data = array();
                        foreach ($codes as $code => $field) {
                            if (is_array($field)) {
                                $field_name = BimpTools::getArrayValueFromPath($field, 'name', $code);
                            } else {
                                $field_name = $field;
                            }
                            if (isset($row[$indexes[$field_name]])) {
                                $row_data[$field_name] = $row[$indexes[$field_name]];
                            }
                        }
                        $data[] = $row_data;
                    }
                }
            } else {
                $errors[] = 'Le fichier "' . pathinfo($file, PATHINFO_FILENAME) . '" n\'est pas formaté correctement (en-tête codes champs absent)';
            }
        } else {
            $errors[] = 'Le fichier "' . pathinfo($file, PATHINFO_FILENAME) . '" n\'existe pas';
        }

        return $data;
    }
}
