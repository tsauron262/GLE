<?php

class Event {

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

        $this->db->begin();
        $sql = 'INSERT INTO event (';
        $sql.= 'label';
        $sql.= ', date_creation';
        $sql.= ', date_start';
        $sql.= ', date_end';
        $sql.= ') ';
        $sql.= 'VALUES (' . $label;
        $sql.= ', now()';
        $sql.= ', ' . $date_start;
        $sql.= ', ' . $date_end;
        $sql.= ')';

        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id('event');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer l'évènement.";
            $this->db->rollback();
            return -1;
        }
    }

}
