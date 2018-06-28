<?php

class AttributeValue {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $id_attribute_parent;
    public $id_attribute_value_extern;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, id_attribute_parent, id_attribute_value_extern';
        $sql .= ' FROM attribute_value';
        $sql .= ' WHERE id=' . $id;

//        echo $sql."\n";


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = (int) $id;
                $this->label = stripslashes($obj->label);
                $this->id_attribute_parent = (int) $obj->id_attribute_parent;
                $this->id_attribute_value_extern = (int) $obj->id_attribute_value_extern;
                return 1;
            }
        } else {
            $this->errors[] = "Aucune valeur d'attribut n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function fetchByParent($id_parent) {

        if ($id < 0) {
            $this->errors[] = "Identifiant parent invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, id_attribute_parent, id_attribute_value_extern';
        $sql .= ' FROM attribute_value';
        $sql .= ' WHERE id_attribute_parent=' . $id_parent;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = (int) $obj->id;
                $this->label = stripslashes($obj->label);
                $this->id_attribute_parent = (int) $obj->id_attribute_parent;
                $this->id_attribute_value_extern = (int) $obj->id_attribute_value_extern;
                return 1;
            }
        } else {
            $this->errors[] = "Aucune valeur d'attribut n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $id_attribute_parent, $id_attribute_value_extern) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($id_attribute_parent == '')
            $this->errors[] = "Le champ identifiant d'attribut du parent est obligatoire";
        if ($id_attribute_value_extern == '')
            $this->errors[] = "Le champ id attribut externe est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `attribute_value` (';
        $sql.= '`label`,';
        $sql.= '`id_attribute_parent`,';
        $sql.= '`id_attribute_value_extern`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . addslashes($label) . '"';
        $sql .= ', "' . $id_attribute_parent . '"';
        $sql .= ', ' . $id_attribute_value_extern;
        $sql.= ')';

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la valeur d'attribut. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function getAllByParentId($id_attribute_parent) {

        $attribute_values = array();

        if ($id_attribute_parent == '') {
            $this->errors[] = "Le champ id attribut parent est obligatoire";
            return -3;
        }

        $sql = 'SELECT id';
        $sql .= ' FROM attribute_value';
        $sql .= ' WHERE id_attribute_parent=' . $id_attribute_parent;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $attribute_value = new AttributeValue($this->db);
                $attribute_value->fetch($obj->id);
                $attribute_values[] = $attribute_value;
            }
            return $attribute_values;
        } else {
            $this->errors[] = "Aucune attribut parent n'a l'identifiant " . $id_attribute_parent;
            return -2;
        }
        return -1;
    }

    public function createTariffAttributeValue($id_tariff, $id_attribute_value, $price, $number_place) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ identifiant tarif est obligatoire";
        if ($id_attribute_value == '')
            $this->errors[] = "Le champ identifiant attribut value est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix attribut value est obligatoire";
        if ($number_place == '')
            $this->errors[] = "Le champ nombre de place attribut value est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `attribute_value_tariff` (';
        $sql.= '`fk_tariff`';
        $sql.= ', `fk_attribute_value`';
        $sql.= ', `price`';
        $sql.= ', `number_place`';
        $sql.= ') ';
        $sql.= 'VALUES (' . $id_tariff;
        $sql.= ', ' . $id_attribute_value;
        $sql.= ', ' . $price;
        $sql.= ', ' . $number_place;
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

}
