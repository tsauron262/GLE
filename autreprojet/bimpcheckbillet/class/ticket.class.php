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

        if ($id_tariff == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($id_client == '')
            $this->errors[] = "Le champ prix est obligatoire";
        if ($id_event == '')
            $this->errors[] = "Le champ évènement est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

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

        if ($this->setTicketByBarcode($barcode) < 0)
            return -2;

        if ($this->id < 0) {
            $this->errors[] = "Identifiant ticket inconnu : " . $this->id;
            return -4;
        }

        $sql = 'SELECT ti.id as id_ticket';
        $sql .= ' FROM ticket as ti';

        $tariff = new Tariff($this->db);
        $tariff->fetch($this->id_tariff);
        if ($tariff->hasItsOwnDate()) {
            $sql .= ' LEFT JOIN tariff as x ON x.id = ti.fk_event';
        } else {
            $sql .= ' LEFT JOIN event as x ON x.id = ti.fk_event';
        }
        $sql .= ' WHERE ti.id=' . $this->id;
        $sql .= ' AND   DATE(NOW()) >= DATE(x.date_start)';
        $sql .= ' AND   DATE(NOW()) <= DATE(x.date_end)';

//        echo $sql;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return $this->id;
            }
        } elseif ($result) {
            $this->errors[] = "Ticket dépassé.";
            return -3;
        }
        return -1;
    }

    function setTicketByBarcode($barcode) {

        $sql = 'SELECT id';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE barcode="' . $barcode . '"';

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->fetch($obj->id);
                return 1;
            }
        } elseif ($result) {
            $this->errors[] = "Aucun ticket n'a le code barre : " . $barcode;
            return -1;
        }
        return -2;
    }

}
