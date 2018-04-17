<?php

class Tariff {

    public $errors;
    private $db;
    private $id;
    private $label;
    private $date_creation;
    private $price;
    private $fk_event;
    private $date_start;
    private $date_end;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, date_creation, price, fk_event, date_start, date_end';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->label = $obj->label;
                $this->date_creation = $obj->date_creation;
                $this->price = $obj->price;
                $this->fk_event = $obj->fk_event;
                $this->date_start = $obj->date_start;
                $this->date_end = $obj->date_end;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun tariff n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $price, $id_event, $file, $date_start = null, $date_end = null) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix est obligatoire";
        if ($id_event == '')
            $this->errors[] = "Le champ évènement est obligatoire";
        if ($file['error'] != 0)
            $this->errors[] = "Erreur lors du chargement de l'image";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($date_start != null)
            $date_start_obj = DateTime::createFromFormat('d/m/Y', $date_start);
        if ($date_end != null)
            $date_end_obj = DateTime::createFromFormat('d/m/Y', $date_end);


        $sql = 'INSERT INTO `tariff` (';
        $sql.= '`label`';
        $sql.= ', `date_creation`';
        $sql.= ', `price`';
        $sql.= ', `fk_event`';
        if ($date_start != null)
            $sql.= ', `date_start`';
        if ($date_end != null)
            $sql.= ', `date_end`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $label . '"';
        $sql.= ', now()';
        $sql.= ', "' . $price . '"';
        $sql.= ', "' . $id_event . '"';
        if ($date_start != null)
            $sql.= ', "' . $date_start_obj->format("Y-m-d") . '"';
        if ($date_end != null)
            $sql.= ', "' . $date_end_obj->format("Y-m-d") . '"';
        $sql.= ')';


//        echo $sql;
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            if ($last_insert_id > 0) {
                $source = $file['tmp_name'];
                $destination = PATH . '/img/event/' . $id_event . '_' . $last_insert_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($source, $destination) == true)
                    return $last_insert_id;
                else {
                    $this->errors[] = "Erreur lors du déplacement de l'image";
                    return -5;
                }
            } else {
                return -4;
            }
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer le tarif. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function getTariffsForEvent($id_event) {

        $tariffs = array();

        $sql = 'SELECT id, label, date_creation, price, fk_event';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE fk_event=' . $id_event;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $tariffs[] = array(
                    'id' => $obj->id,
                    'label' => $obj->label,
                    'date_creation' => $obj->date_creation,
                    'price' => $obj->price,
                    'fk_event' => $obj->fk_event,
                );
            }
            return $tariffs;
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 1567.";
            return -2;
        }
        return -1;
    }

    public function hasItsOwnDate() {
        return $this->date_start != NULL and $this->date_end != NULL;
    }

}
