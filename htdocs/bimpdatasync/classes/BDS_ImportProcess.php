<?php

abstract class BDS_ImportProcess extends BDS_Process
{

    public $soap_server = null;
    public static $import_datas = array(
        'id_process'       => 'Nom du processus d\'import',
        'reference'        => 'Référence d\'import',
        'images'       => 'Fichier image à importer',
        'images_imported'   => 'Image importée',
        'last_import_date' => 'Date du dernier import',
        'processed'        => 'Traité lors du dernier import'
    );

    protected function saveObjectImportData($object, $import_reference = '', $images = '', $processed = 1)
    {
        if (!isset($object->id) || !$object->id) {
            return false;
        }
        $object_name = get_class($object);
        $where = '`object` = \'' . $object_name . '\'';
        $where .= ' AND `id_process` = ' . $this->id;
        $where .= ' AND `id_object` = ' . (int) $object->id;

        $row = $this->db->getRow('bds_object_import_data', $where);
        if ($row && count($row)) {
            $data = array(
                'last_import_date' => date('Y-m-d H:i:s'),
                'reference'        => (isset($row['reference']) ? $row['reference'] : ''),
                'images'           => (isset($row['images']) ? $row['images'] : $row['images']),
                'processed'        => $processed
            );
            if ($import_reference) {
                $data['reference'] = $import_reference;
            }
            if ($images) {
                $data['images'] = $images;
            }
            if (!$this->db->update('bds_object_import_data', $data, $where)) {
                $msg = 'Echec de la mise à jour des données d\'importation';
                $this->SqlError($msg, $object_name, $object->id, $import_reference);
                return false;
            }
        } else {
            if (!$this->db->insert('bds_object_import_data', array(
                        'id_object'        => (int) $object->id,
                        'object'           => $object_name,
                        'id_process'       => (int) $this->id,
                        'reference'        => $import_reference,
                        'images'           => $images,
                        'images_imported'  => '',
                        'last_import_date' => date('Y-m-d H:i:s'),
                        'processed'        => $processed
                    ))) {
                $msg = 'Echec de l\'enregistrement des données d\'importation';
                $this->SqlError($msg, $object_name, $object->id, $import_reference);
                return false;
            }
        }
        return true;
    }

    protected function getObjectImportData($object)
    {
        if (!isset($object->id) || !$object->id) {
            return false;
        }

        $where .= '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $this->getObjectClass($object) . '\'';
        $where .= ' AND `id_object` = ' . (int) $object->id;

        $row = $this->db->getRow('bds_object_import_data', $where);
        if (is_null($row)) {
            $msg = 'Echec de la récupération des données d\'importation';
            $this->SqlError($msg, $this->getObjectClass($object), $object->id);
            return false;
        }
        return $row;
    }

    protected function getObjectImportDataByReference($object_class, $reference)
    {
        if (is_null($reference) || !$reference) {
            return false;
        }

        $where .= '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_class . '\'';
        $where .= ' AND `reference` = \'' . $reference . '\'';

        $row = $this->db->getRow('bds_object_import_data', $where);
        if (is_null($row)) {
            $msg = 'Echec de la récupération des données d\'importation';
            $this->SqlError($msg, $object_class, '', $reference);
            return false;
        }
        return $row;
    }

    protected function getCurrentObjectsImportData($object_name, $key_property = 'id')
    {
        $where .= ' `id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';

        $objects = array();

        $rows = $this->db->getRows('bds_object_import_data', $where);

        if (is_null($rows)) {
            $msg = 'Echec de la récupération des données d\'importation des objets de type "' . $object_name . '"';
            $this->SqlError($msg);
        } elseif (count($rows)) {
            foreach ($rows as $r) {
                if (isset($r[$key_property])) {
                    $objects[$r[$key_property]] = $r;
                } else {
                    $objects[$r['id']] = $r;
                }
            }
        }
        return $objects;
    }

    protected function getCurrentObjectsIds($object_name)
    {
        $ids = array();

        $where .= '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';

        $values = $this->db->getValues('bds_object_import_data', 'id_process', $where);
        if (is_null($values)) {
            $msg = 'Echec de la récupération de la liste des objets déjà importés de type "' . $object_name . '"';
            $this->SqlError($msg);
        } elseif ($values && count($values)) {
            foreach ($values as $id) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    protected function getCurrentObjectsImportReferences($object_name)
    {
        $references = array();

        $where .= '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';

        $values = $this->db->getValues('bds_object_import_data', 'reference', $where);
        if (is_null($values)) {
            $msg = 'Echec de la récupération de la liste des objets déjà importés de type "' . $object_name . '"';
            $this->SqlError($msg);
        } elseif ($values && count($values)) {
            foreach ($values as $id) {
                $references[] = $id;
            }
        }
        return $references;
    }

    protected function getObjectIdByImportReference($object_name, $import_reference)
    {
        $where .= ' `id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';
        $where .= ' AND `reference` = \'' . $import_reference . '\'';

        $id = $this->db->getValue('bds_object_import_data', 'id_object', $where);
        if (!is_null($id) && !$id) {
            return null;
        }
        return $id;
    }

    protected function saveObjectImagesImported($object, $images, $delimiter = '|')
    {
        if (!isset($object->id) || !$object->id) {
            return false;
        }

        if (is_array($images)) {
            $images = implode($delimiter, $images);
        }

        $where = '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $this->getObjectClass($object) . '\'';
        $where .= ' AND `id_object` = ' . (int) $object->id;

        if (!$this->db->update('bds_object_import_data', array(
                    'images_imported' => $images
                        ), $where)) {
            $msg = 'Echec de l\'enregistrement de la liste des images importées';
            $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
            return false;
        }
        return true;
    }

    protected function getObjectImagesImported($object, $delimiter = '|')
    {
        if (!isset($object->id) || !$object->id) {
            return null;
        }

        $where = '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $this->getObjectClass($object) . '\'';
        $where .= ' AND `id_object` = ' . (int) $object->id;

        $images = $this->db->getValue('bds_object_import_data', 'images_imported', $where);

        if (!is_null($images)) {
            return explode($delimiter, $images);
        }
        return null;
    }

    protected function getObjectMissingImages($object, $delimiter = '|')
    {
        $missings = array();
        if (!isset($object->id) || !$object->id) {
            return $missings;
        }

        $where .= '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` =  \'' . $this->getObjectClass($object) . '\'';
        $where .= ' AND `id_object` = ' . (int) $object->id;

        $row = $this->db->getRow('bds_object_import_data', $where, array('images', 'images_imported'));

        if (is_null($row)) {
            $msg = 'Echec de la récupération des images manquantes';
            $this->SqlError($msg, $this->getObjectClass($object), $object->id);
        } elseif (isset($row['images']) && isset($row['images_imported'])) {
            $images = explode($delimiter, $row['images']);
            $imported = explode($delimiter, $row['images_imported']);
            foreach ($images as $image) {
                if (!in_array($image, $imported)) {
                    $missings[] = $image;
                }
            }
        }
        return $missings;
    }
    
    protected function getObjectImagesData($object, $delimiter = '|')
    {
        $where = '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $this->getObjectClass($object) . '\'';
        $where .= ' AND `id_object` = ' . (int) $object->id;
        
        $row = $this->db->getRow('bds_object_import_data', $where, array('images', 'images_imported'));
        
        $data = array(
            'images' => array(),
            'imported' => array(),
            'missings' => array()
        );
        
        if (!is_null($row)) {
            $data['images'] = explode($delimiter, $row['images']);
            $data['imported'] = explode($delimiter, $row['images_imported']);
            
            foreach ($data['images'] as $img) {
                if (!in_array($img, $data['imported'])) {
                    $data['missings'][] = $img;
                }
            }
        }
        
        return $data;
    }

    protected function deleteObjectImportData($object_name, $id_object)
    {
        $where = '`process` = ' . (int) $this->processDefinition->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';
        $where .= ' AND `id_object` = ' . (int) $id_object;

        return $this->db->delete('bds_object_import_data', $where);
    }

    protected function cleanAllObjects($object_name)
    {
        $this->report->addObjectData($object_name);

        $objects = $this->getCurrentObjectsImportData($object_name);

        if (!count($objects)) {
            $this->Alert('Il n\'y a aucun objet de type "' . $object_name . '" à supprimer');
            return;
        }

        foreach ($objects as $id_object => $data) {
            $this->setCurrentPsObject($object_name, $id_object, $data['reference']);
            $instance = new $object_name($id_object);
            if (isset($instance->id) && $instance->id) {
                $this->deleteObject($instance);
            }
            $this->deleteObjectImportData($object_name, $id_object);
        }
    }

    protected function initObjectImport($object_name)
    {
        $where = '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';

        return $this->db->update('bds_object_import_data', array(
                    'processed' => 0
                        ), $where);
    }

    protected function setObjectProcessed($object_name, $id_object)
    {
        $where = '`id_process` = ' . (int) $this->id;
        $where .= ' AND `object` = \'' . $object_name . '\'';
        $where .= ' AND `id_object` = ' . (int) $id_object;

        return $this->db->update('bds_object_import_data', array(
                    'processed' => 1
                        ), $where);
    }

    protected function saveObject($id_object, &$object, $label = null, $display_success = false)
    {
        $isCurrentObject = $this->isCurrent($object);

        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $this->getObjectClass($object) . '"';
        }

        $result = 0;
        if (is_null($id_object)) {
            if (method_exists($object, 'create')) {
                $result = $object->create($this->user);
            }
            if ($result > 0) {
                if ($isCurrentObject) {
                    $this->incCreated();
                }
                if ($display_success || $isCurrentObject) {
                    $this->current_object['id'] = $object->id;
                    $msg = 'Création ' . $label . ' effectuée avec succès';
                    $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                }
                return true;
            } else {
                $msg = 'Echec de la création ' . $label;
                $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
            }
        } else {
            if (method_exists($object, 'update')) {
                $result = $object->update($id_object, $this->user);
            }
            if ($result > 0) {
                if ($isCurrentObject) {
                    $this->incUpdated();
                }
                if ($display_success || $isCurrentObject) {
                    $msg = 'Mise à jour ' . $label . ' effectuée avec succès';
                    $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                }
                return true;
            } else {
                $msg = 'Echec de la mise à jour ' . $label;
                if (!$isCurrentObject) {
                    $msg .= ' d\'ID: ' . $id_object;
                }
                $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
            }
        }

        return false;
    }
}
