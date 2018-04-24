<?php

class Event {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $date_creation;
    public $date_start;
    public $date_end;
    public $status;

    const STATUS_DRAFT = 1;
    const STATUS_VALIDATE = 2;
    const STATUS_CLOSED = 3;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, label, date_creation, date_start, date_end, status';
        $sql .= ' FROM event';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = intVal($id);
                $this->label = $obj->label;
                $this->date_creation = $obj->date_creation;
                $this->date_start = $obj->date_start;
                $this->date_end = $obj->date_end;
                $this->status = $obj->status;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun évènement n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $date_start, $time_start, $date_end, $time_end, $id_user, $file) {

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

        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);

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
        $sql.= ', "' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ', "' . $date_end_obj->format('Y-m-d H:i:s') . '"';
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

    function update($id_event, $label, $date_start, $time_start, $date_end, $time_end, $id_user) {
        if ($id_event == '')
            $this->errors[] = "Le champ identifiant est obligatoire";
        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($date_start == '')
            $this->errors[] = "Le champ date de début est obligatoire";
        if ($date_end == '')
            $this->errors[] = "Le champ date de fin est obligatoire";
//        if ($file['error'] != 0)
//            $this->errors[] = "Erreur lors du chargement de l'image";
        if (sizeof($this->errors) != 0)
            return -4;

        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);

        if ($date_start_obj > $date_end_obj) {
            $this->errors[] = "Date de début postérieur à date de fin";
            return -3;
        }

        $sql = 'UPDATE `event` SET';
        $sql.= ' `label`="' . $label . '"';
        $sql.= ', `date_start`="' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ', `date_end`="' . $date_end_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ' WHERE id=' . $id_event;

        echo $sql;
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
//            if ($last_insert_id > 0 and $this->createEventAdmin($last_insert_id, $id_user) > 0) {
//                $source = $file['tmp_name'];
//                $destination = PATH . '/img/event/' . $last_insert_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
//                if (move_uploaded_file($source, $destination) == true)
            return 1;
//                else {
//                    $this->errors[] = "Erreur lors du déplacement de l'image";
//                    return -6;
//                }
//            } else {
//                return -4;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier l'évènement. " . $e;
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

    public function createEventAdmin($id_event, $id_user) {

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

    public function deleteEventAdmin($id_event, $id_user) {

        if ($id_event == '')
            $this->errors[] = "Le champ identifiant évènement est obligatoire";
        if ($id_user == '')
            $this->errors[] = "Le champ identifiant utilisateur est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'DELETE FROM `event_admin`';
        $sql.= ' WHERE fk_event=' . $id_event;
        $sql.= ' AND fk_user=' . $id_user;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison évènement - utilisateur. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    function getStats($id_event) {

        $tab = array();
        $tmp = array();

        if ($id_event < 0) {
            $this->errors[] = "Identifiant d'évènement incorrect: " . $id_event;
            return -4;
        }

        // Event
        $this->fetch($id_event);
        $this->price_total = 0;

        // Tariffs
        $tariff_obj = new Tariff($this->db);
        $tariffs = $tariff_obj->getTariffsForEvent($id_event);

//        print_r($tariffs);

        foreach ($tariffs as $tariff) {
            $tariff->sold = 0;
            $tmp[$tariff->id] = $tariff;
//            unset($tmp[$tariff->id]->id);
//            unset($tmp[$tariff->id]->fk_event);
        }
        $tab['tariffs'] = $tmp;

        // Tickets
        $sql = 'SELECT ti.id as id_ticket, ti.date_creation as date_creation_ticket, ti.price as price_ticket, ti.date_scan as date_scan,';
        $sql .= ' ta.id as id_tariff, ta.price as price_tariff';
        $sql .= ' FROM ticket as ti';
        $sql .= ' LEFT JOIN tariff as ta ON ti.fk_tariff = ta.id';
        $sql .= ' WHERE ti.fk_event=' . $id_event;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $ticket = array();
                $ticket['id'] = $obj->date_creation_ticket;
                $ticket['date_creation'] = $obj->date_creation_ticket;
                $ticket['date_scan'] = $obj->date_scan;
                $ticket['price'] = ($obj->price_ticket != NULL) ? floatVal($obj->price_ticket) : floatVal($obj->price_tariff);
                $tab['tickets'][intVal($obj->id_ticket)] = $ticket;
                $tab['tariffs'][$obj->id_tariff]->sold ++;
                $this->price_total += $ticket['price'];
            }
            $this->price_total = number_format($this->price_total, 2);
            $tab['event'] = $this;
            return $tab;
        } elseif ($result) {
            $tab['event'] = $this;
            return $tab;
        } else {
            $this->errors[] = "Erreur SQL 1564." . $sql;
            return -3;
        }
        return -1;
    }

}
