<?php

class Event {

    public $errors;
    private $db;
    private $id;
    private $label;
    private $date_creation;
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

        $sql = 'SELECT id, label, date_creation, date_start, date_end';
        $sql .= ' FROM event';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->label = $obj->label;
                $this->date_creation = $obj->date_creation;
                $this->date_start = $obj->date_start;
                $this->date_end = $obj->date_end;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun évènement n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $date_start, $date_end) {

        // TODO test param

        $date_start_obj = DateTime::createFromFormat('d/m/Y', $date_start);
        $date_end_obj = DateTime::createFromFormat('d/m/Y', $date_end);
        
        if ($date_start_obj > $date_end_obj) {
            $this->errors[] = "Date de début postérieur à date de fin";
            return -3;
        }

        $sql = 'INSERT INTO `event` (';
        $sql.= '`label`';
        $sql.= ', `date_creation`';
        $sql.= ', `date_start`';
        $sql.= ', `date_end`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $label . '"';
        $sql.= ', now()';
        $sql.= ', "' . $date_start_obj->format("Y-m-d") . '"';
        $sql.= ', "' . $date_end_obj->format("Y-m-d") . '"';
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer l'évènement. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
