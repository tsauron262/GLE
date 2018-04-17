<?php

class Event {

    public $errors;
    private $db;
    private $id;
    public $label;
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
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
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

    public function create($label, $date_start, $date_end, $id_user, $file) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($date_start == '')
            $this->errors[] = "Le champ date de début est obligatoire";
        if ($date_end == '')
            $this->errors[] = "Le champ date de fin est obligatoire";
        if ($file['error'] != 0)
            $this->errors[] = "Erreur lors du chargement de l'image";
        if (sizeof($this->errors) != 0)
            return -4;

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
            if ($last_insert_id > 0 and $this->createEventAdmin($last_insert_id, $id_user) > 0) {
                $source = $file['tmp_name'];
                $destination = PATH . '/img/event/' . $last_insert_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($source, $destination) == true)
                    return $last_insert_id;
                else {
                    $this->errors[] = "Erreur lors du déplacement de l'image";
                    return -6;
                }
            } else {
                return -4;
            }
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer l'évènement. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function getEvents($id_user = null, $with_tariff = false, $is_super_admin = false) {

        $events = array();
        $tariff = new Tariff($this->db);

        $sql = 'SELECT e.id as id, e.label as label, e.date_creation as date_creation,'
                . ' e.date_start as date_start, e.date_end as date_end';
        $sql .= ' FROM event as e';
        if ($id_user != null and ! $is_super_admin) {
            $sql .= ' LEFT JOIN event_admin as e_a ON e_a.fk_event=e.id';
            $sql .= ' WHERE e_a.fk_user=' . $id_user;
        }

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                if ($with_tariff)
                    $events[] = array(
                        'id' => $obj->id,
                        'label' => $obj->label,
                        'date_creation' => $obj->date_creation,
                        'date_start' => $obj->date_start,
                        'date_end' => $obj->date_end,
                        'tariffs' => $tariff->getTariffsForEvent($obj->id),
                    );
                else
                    $events[] = array(
                        'id' => $obj->id,
                        'label' => $obj->label,
                        'date_creation' => $obj->date_creation,
                        'date_start' => $obj->date_start,
                        'date_end' => $obj->date_end
                    );
            }
            if ($with_tariff)
                $this->errors = array_merge($this->errors, $tariff->errors);
            return $events;
        } else if (!$result) {
            $this->errors[] = "Erreur SQL";
            return -2;
        }
        return array();
    }

    private function createEventAdmin($id_event, $id_user) {

        if ($id_event == '')
            $this->errors[] = "Le champ identifiant évènement est obligatoire";
        if ($id_user == '')
            $this->errors[] = "Le champ identifiant utilisateur est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'INSERT INTO `event_admin` (';
        $sql.= '`fk_event`';
        $sql.= ', `fk_user`';
        $sql.= ') ';
        $sql.= 'VALUES (' . $id_event;
        $sql.= ', ' . $id_user;
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de créer la liaison évènement - utilisateur. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
