<?php

class Combination {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $id_combination_extern;
    public $price;
    public $number_place;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, id_combination_extern, price, number_place';
        $sql .= ' FROM combination';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = intVal($id);
                $this->label = stripslashes($obj->label);
                $this->id_combination_extern = intVal($obj->id_combination_extern);
                return 1;
            }
        } else {
            $this->errors[] = "Aucune combinaison n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `combination` (';
        $sql.= '`label`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . addslashes($label) . '"';
        $sql.= ')';

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la combinaison. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function createTariffCombination($id_tariff, $id_combination) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ identifiant tarif est obligatoire";
        if ($id_combination == '')
            $this->errors[] = "Le champ identifiant combinaisonest obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `tariff_combination` (';
        $sql.= '`id_tariff`';
        $sql.= ', `id_combination`';
        $sql.= ') ';
        $sql.= 'VALUES (' . $id_tariff;
        $sql.= ', ' . $id_combination;
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la liaison tarif - combinaison. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function deleteTariffCombination($id_tariff, $id_combination) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ identifiant tarif est obligatoire";
        if ($id_combination == '')
            $this->errors[] = "Le champ identifiant combinaison est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'DELETE FROM `tariff_combination`';
        $sql.= ' WHERE `id_tariff`=' . $id_tariff;
        $sql.= ' AND `id_combination`=' . $id_combination;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison tarif - combinaison. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
