<?php

class Ticket {

    public $errors;
    private $db;
    private $id;
    private $date_creation;
    private $id_tariff;
    private $id_user;
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

        $sql = 'SELECT id, date_creation, fk_event, fk_tariff, fk_user, date_scan';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE id=' . $id;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->date_creation = $obj->date_creation;
                $this->id_user = $obj->fk_user;
                $this->id_tariff = $obj->fk_tariff;
                $this->id_event = $obj->fk_event;
                return 1;
            }
        } elseif ($result) {
            $this->errors[] = "Aucun ticket n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($id_tariff, $id_user, $id_event) {

        if ($id_tariff == '')
            $this->errors[] = "Le champ id tariff est obligatoire";
        if ($id_user == '')
            $this->errors[] = "Le champ id user est obligatoire";
        if ($id_event == '')
            $this->errors[] = "Le champ évènement est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `ticket` (';
        $sql.= '`fk_tariff`';
        $sql.= ', `date_creation`';
        $sql.= ', `fk_user`';
        $sql.= ', `fk_event`';
        $sql.= ', `barcode`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $id_tariff . '"';
        $sql.= ', now()';
        $sql.= ', "' . $id_user . '"';
        $sql.= ', "' . $id_event . '"';
        $sql.= ', "' . substr(md5(mt_rand()), 0, 31) . '"';
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

    public function check($barcode, $id_event) {

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
        $sql .= ' AND DATE(NOW()) >= DATE(x.date_start)';
        $sql .= ' AND DATE(NOW()) <= DATE(x.date_end)';
        $sql .= ' AND ti.date_scan IS NULL';
        $sql .= ' AND ti.fk_event=' . $id_event;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $out = $this->setScanned();
                if ($out > 0)
                    return $this->id;
                else
                    return $out;
            }
        } elseif ($result) {
            $this->errors[] = "Ticket dépassé ou déjà scanné ou correspondant à un autre évènement";
            return -3;
        }
        $this->errors[] = "Erreur SQL.";
        return -1;
    }

    public function setTicketByBarcode($barcode) {

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

    private function setScanned() {

        if ($this->id < 0) {
            $this->errors[] = "Identifiant ticket inconnu : " . $this->id;
            return -1;
        }

        $sql = 'UPDATE ticket';
        $sql.= ' SET date_scan=now()';
        $sql.= ' WHERE id=' . $this->id;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors du setting de date_scan " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
