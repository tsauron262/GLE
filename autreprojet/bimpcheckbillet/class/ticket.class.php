<?php

class Ticket {

    public $errors;
    private $db;
    private $id;
    private $date_creation;
    private $id_tariff;
    private $id_client;
    private $id_event;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, fk_tariff, date_creation, fk_client, fk_event';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->id_tariff = $obj->fk_tariff;
                $this->date_creation = $obj->date_creation;
                $this->id_client = $obj->fk_client;
                $this->id_event = $obj->fk_event;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun ticket n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($id_tariff, $id_client, $id_event) {

        // TODO test param


        $sql = 'INSERT INTO `ticket` (';
        $sql.= '`fk_tariff`';
        $sql.= ', `date_creation`';
        $sql.= ', `fk_client`';
        $sql.= ', `fk_event`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $id_tariff . '"';
        $sql.= ', now()';
        $sql.= ', "' . $id_client . '"';
        $sql.= ', "' . $id_event . '"';
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer le tarif. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function check($barcode) {

        $sql = 'SELECT t.id as id_ticket';
        $sql .= ' FROM ticket as t';
        $sql .= ' LEFT JOIN event as e ON e.id = t.fk_event';
        $sql .= ' WHERE barcode="' . $barcode . '"';
        $sql .= ' AND   DATE(NOW()) >= DATE(e.date_start)';
        $sql .= ' AND   DATE(NOW()) <= DATE(e.date_end)';

//        echo $sql;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return $obj->id_ticket;
            }
        } elseif ($result) {
            $sql = 'SELECT id';
            $sql .= ' FROM ticket';
            $sql .= ' WHERE barcode="' . $barcode . '"';

            $result = $this->db->query($sql);
            if ($result and $result->rowCount() > 0) {
                while ($obj = $result->fetchObject()) {
                    $this->errors[] = "Ticket dépassé.";
                    return -3;
                }
            }
            $this->errors[] = "Aucun ticket n'a le code barre : " . $barcode;
            return -2;
        }
        return -1;
    }

}
