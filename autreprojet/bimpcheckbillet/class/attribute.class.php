<?php

class Attribute {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $id_attribute_extern;
    public $type;
    public $values;

    const TYPE_LIST = 'select';
    const TYPE_RADIO = 'radio';

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->values = array();
    }

    public function fetch($id, $fetch_values = false) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, id_attribute_extern, type';
        $sql .= ' FROM attribute';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = (int) $id;
                $this->label = stripslashes($obj->label);
                $this->type = $obj->type;
                $this->id_attribute_extern = (int) $obj->id_attribute_extern;
                if ($fetch_values) {
                    $attribute_value = new AttributeValue($this->db);
                    $attribute_value->fetchByParent($this->id);
                    $this->values[] = $attribute_value;
                }
                return 1;
            }
        } else {
            $this->errors[] = "Aucun attribut n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $type, $id_attribute_extern) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($type == '')
            $this->errors[] = "Le champ type est obligatoire";
        if ($id_attribute_extern == '')
            $this->errors[] = "Le champ id attribut externe est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `attribute` (';
        $sql.= '`label`,';
        $sql.= '`type`,';
        $sql.= '`id_attribute_extern`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . addslashes($label) . '"';
        $sql .= ', "' . $type . '"';
        $sql .= ', ' . $id_attribute_extern;
        $sql.= ')';

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la attribut. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function createTariffAttribute($id_tariff, $id_attribute) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ identifiant tarif est obligatoire";
        if ($id_attribute == '')
            $this->errors[] = "Le champ identifiant attributest obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `tariff_attribute` (';
        $sql.= '`fk_tariff`';
        $sql.= ', `fk_attribute`';
        $sql.= ') ';
        $sql.= 'VALUES (' . $id_tariff;
        $sql.= ', ' . $id_attribute;
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la liaison tarif - attribut. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    
    public function deleteTariffAttribute($id_tariff, $id_attribute) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ identifiant tarif est obligatoire";
        if ($id_attribute == '')
            $this->errors[] = "Le champ identifiant attribut est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'DELETE FROM `tariff_attribute`';
        $sql.= ' WHERE `id_tariff`=' . $id_tariff;
        $sql.= ' AND `id_attribute`=' . $id_attribute;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison tarif - attribut. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function getAllAttribute() {

        $attributes = array();

        $sql = 'SELECT id';
        $sql .= ' FROM attribute';


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $attribute = new Attribute($this->db);
                $attribute->fetch($obj->id);
                $attributes[] = $attribute;
            }
            return $attributes;
        } elseif ($result) {
            $this->errors[] = "Aucun attribut n'a été créée.";
            return -2;
        } else {
            $this->errors[] = "Erreur SQL 3481.";
            return -3;
        }
        return -1;
    }

    public function getAttributeByTariff($id_tariff) {
        $attributes = array();

        $sql = 'SELECT fk_attribute';
        $sql .= ' FROM tariff_attribute';
        $sql .= ' WHERE fk_tariff=' . $id_tariff;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $attribute = new Attribute($this->db);
                $attribute->fetch($obj->fk_attribute);
                $attributes[] = $attribute;
            }
            return $attributes;
        } elseif ($result) {
            $this->errors[] = "Aucun attribut n'a été créée.";
            return -2;
        } else {
            $this->errors[] = "Erreur SQL 6482.";
            return -3;
        }
        return -1;
    }

}
