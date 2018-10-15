<?php

class Event {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $description;
    public $date_creation;
    public $date_start;
    public $date_end;
    public $status;
    public $id_categ;
    public $id_categ_parent;
    public $place;
    private $filename;

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

        $sql = 'SELECT id, label, description, date_creation, date_start, date_end, status, id_categ, id_categ_parent, place';
        $sql .= ' FROM event';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = intVal($id);
                $this->label = stripslashes($obj->label);
                $this->description = stripslashes($obj->description);
                $this->date_creation = $obj->date_creation;
                $this->date_start = $obj->date_start;
                $this->date_end = $obj->date_end;
                $this->status = $obj->status;
                $this->id_categ = intVal($obj->id_categ);
                $this->id_categ_parent = intVal($obj->id_categ_parent);
                $this->place = stripslashes($obj->place);
                $exts = array('bmp', 'png', 'jpg');
                foreach ($exts as $ext) {
                    if (file_exists(PATH . '/img/event/' . $id . "." . $ext)) {
                        $this->filename = $id . "." . $ext;
                    }
                }
                return 1;
            }
        } else {
            $this->errors[] = "Aucun évènement n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $description, $place, $date_start, $time_start, $date_end, $time_end, $id_user, $file, $categ_parent, $id_categ = '') {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($categ_parent == '')
            $this->errors[] = "Le champ catégorie parent est obligatoire";
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
        $sql.= ', `description`';
        $sql.= ', `place`';
        $sql.= ', `date_creation`';
        $sql.= ', `date_start`';
        $sql.= ', `date_end`';
        $sql.= ', `id_categ`';
        $sql.= ', `id_categ_parent`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . addslashes($label) . '"';
        $sql.= ', "' . addslashes($description) . '"';
        $sql.= ', "' . addslashes($place) . '"';
        $sql.= ', now()';
        $sql.= ', "' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ', "' . $date_end_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ', ' . ($id_categ != '' ? $id_categ : 'NULL');
        $sql.= ', ' . $categ_parent;
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

    function update($id_event, $label, $description, $place, $date_start, $time_start, $date_end, $time_end, $id_user) {
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
        $sql.= ', `description`="' . addslashes($description) . '"';
        $sql.= ', `place`="' . addslashes($place) . '"';
        $sql.= ', `date_start`="' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ', `date_end`="' . $date_end_obj->format('Y-m-d H:i:s') . '"';
        $sql.= ' WHERE id=' . $id_event;

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

    public function delete($id_event) {

        $this->fetch($id_event);

        // Delete tariffs
        $tariff_static = new Tariff($this->db);
        $tariffs = $tariff_static->getTariffsForEvent($id_event);

//        print_r($tariffs);
        if (is_array($tariffs)) {
            foreach ($tariffs as $tariff) {
                $res_delete_tariff = $tariff->delete();
                if ($res_delete_tariff != 1) {
                    $this->errors = array_merge($this->errors, $tariff->errors);
                    return -4;
                }
            }
        }

        // Delete event_admin
        $sql = 'DELETE FROM `event_admin`';
        $sql.= ' WHERE `fk_event`=' . $id_event;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison évènement - administrateur. " . $e;
            $this->db->rollBack();
            return -5;
        }


        // Delete event
        $sql = 'DELETE FROM `event`';
        $sql.= ' WHERE `id`=' . $id_event;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer l'évènement. " . $e;
            $this->db->rollBack();
            return -6;
        }


        // Delete image event
        $file_event_prefix = PATH . '/img/event/' . $this->filename;
        if (file_exists($file_event_prefix)) {
            $delete_event_file_ok = unlink($file_event_prefix);
            if (!$delete_event_file_ok) {
                $this->errors[] = "Problème lors de la suppression de l'image de l'évènement";
                return -7;
            }
        }
        return true;
    }

    public function updateStatus($id_event, $status) {

        if ($status != $this::STATUS_DRAFT and $status != $this::STATUS_VALIDATE and $status != $this::STATUS_CLOSED)
            $this->errors[] = "Status évènement invalide.";
        if ($id_event == '')
            $this->errors[] = "Le champ identifiant est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'UPDATE `event` SET';
        $sql.= ' `status`=' . $status;
        $sql.= ' WHERE id=' . $id_event;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier le statut de l'évènement. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function getEvents($id_user = null, $with_tariff = false, $is_super_admin = false) {

        $events = array();
        $tariff = new Tariff($this->db);

        $sql = 'SELECT e.id as id, e.label as label, e.description as description, e.date_creation as date_creation,'
                . ' e.date_start as date_start, e.date_end as date_end, e.status as status, e.id_categ as id_categ, e.id_categ_parent as id_categ_parent, e.place as place';
        $sql .= ' FROM event as e';
        if ($id_user != null and ! $is_super_admin) {
            $sql .= ' LEFT JOIN event_admin as e_a ON e_a.fk_event=e.id';
            $sql .= ' WHERE e_a.fk_user=' . $id_user;
        }

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $exts = array('bmp', 'png', 'jpg', 'jpeg');
                $filename = "";
                foreach ($exts as $ext) {
                    if (file_exists(PATH . '/img/event/' . $obj->id . "." . $ext)) {
                        $filename = $obj->id . "." . $ext;
                    }
                }
                if ($with_tariff)
                    $events[] = array(
                        'id' => $obj->id,
                        'label' => stripslashes($obj->label),
                        'description' => stripslashes($obj->description),
                        'date_creation' => $obj->date_creation,
                        'date_start' => $obj->date_start,
                        'date_end' => $obj->date_end,
                        'status' => $obj->status,
                        'id_categ' => $obj->id_categ,
                        'id_categ_parent' => $obj->id_categ_parent,
                        'filename' => $filename,
                        'tariffs' => $tariff->getTariffsForEvent($obj->id),
                        'place' => $obj->place
                    );
                else
                    $events[] = array(
                        'id' => $obj->id,
                        'label' => stripslashes($obj->label),
                        'description' => stripslashes($obj->description),
                        'date_creation' => $obj->date_creation,
                        'date_start' => $obj->date_start,
                        'date_end' => $obj->date_end,
                        'status' => $obj->status,
                        'id_categ' => $obj->id_categ,
                        'id_categ_parent' => $obj->id_categ_parent,
                        'filename' => $filename,
                        'place' => $obj->place
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
                $ticket['id'] = $obj->id_ticket;
                $ticket['id_tariff'] = $obj->id_tariff;
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

    public function getTicketList($id_event) {

        if ($id_event < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $tariff = new Tariff($this->db);
        $tariffs = $tariff->getTariffsForEvent($id_event);

        foreach ($tariffs as $key => $tariff) {

            $tickets = array();

            $sql = 'SELECT id';
            $sql .= ' FROM ticket';
            $sql .= ' WHERE fk_tariff=' . $tariff->id;

            $result = $this->db->query($sql);
            if ($result and $result->rowCount() > 0) {
                while ($obj = $result->fetchObject()) {
                    $ticket = new Ticket($this->db);
                    $ticket->fetch($obj->id);
                    $tickets[] = $ticket;
                }
                $tariff->tickets = $tickets;
            } elseif (!$result) {
                $this->errors[] = "Erreur SQL 6567";
                return -2;
            }
        }
        return $tariffs;
    }

    public function setIdCateg($id_event, $id_categ) {

        if ($id_event == '')
            $this->errors[] = "Le champ identifiant est obligatoire";
        if ($id_categ == '')
            $this->errors[] = "Le champ identifiant de catégorie est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'UPDATE `event` SET';
        $sql.= ' `id_categ`=' . $id_categ;
        $sql.= ' WHERE id=' . $id_event;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier le la catégorie externe. ";
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
