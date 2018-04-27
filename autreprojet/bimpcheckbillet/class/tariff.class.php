<?php

class Tariff {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $date_creation;
    public $price;
    private $fk_event;
    public $date_start;
    public $date_end;
    public $require_names;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT label, date_creation, require_names, date_start, date_end, price, type_extra_1, type_extra_2, type_extra_3, type_extra_4, type_extra_5, type_extra_6, name_extra_1, name_extra_2, name_extra_3, name_extra_4, name_extra_5, name_extra_6, id_prod_extern, fk_event';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = intVal($id);
                $this->label = $obj->label;
                $this->date_creation = $obj->date_creation;
                $this->price = floatVal($obj->price);
                $this->fk_event = $obj->fk_event;
                $this->require_names = intVal($obj->require_names);
                $this->date_start = $obj->date_start;
                $this->date_end = $obj->date_end;
                $this->type_extra_1 = intVal($obj->type_extra_1);
                $this->type_extra_2 = intVal($obj->type_extra_2);
                $this->type_extra_3 = intVal($obj->type_extra_3);
                $this->type_extra_4 = intVal($obj->type_extra_4);
                $this->type_extra_5 = intVal($obj->type_extra_5);
                $this->type_extra_6 = intVal($obj->type_extra_6);
                $this->name_extra_1 = $obj->name_extra_1;
                $this->name_extra_2 = $obj->name_extra_2;
                $this->name_extra_3 = $obj->name_extra_3;
                $this->name_extra_4 = $obj->name_extra_4;
                $this->name_extra_5 = $obj->name_extra_5;
                $this->name_extra_6 = $obj->name_extra_6;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun tariff n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $price, $id_event, $file, $require_names, $id_prod_extern, $date_start, $time_start, $date_end, $time_end, $type_extra_1, $name_extra_1, $type_extra_2, $name_extra_2, $type_extra_3, $name_extra_3, $type_extra_4, $name_extra_4, $type_extra_5, $name_extra_5, $type_extra_6, $name_extra_6) {

        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix est obligatoire";
        if ($id_event == '')
            $this->errors[] = "Le champ évènement est obligatoire";
        if ($file['error'] != 0)
            $this->errors[] = "Erreur lors du chargement de l'image";
        if ($require_names == '')
            $this->errors[] = "Le champ exiger nom et prénom est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        if ($date_start != '')
            $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        if ($date_end != '')
            $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);


        $sql = 'INSERT INTO `tariff` (';
        $sql.= '`label`';
        $sql.= ', `date_creation`';
        $sql.= ', `price`';
        $sql.= ', `fk_event`';
        $sql.= ', `require_names`';
        $sql.= ', `id_prod_extern`';
        if ($date_start != '')
            $sql.= ', `date_start`';
        if ($date_end != '')
            $sql.= ', `date_end`';
        if ($type_extra_1 != '' and $name_extra_1 != '')
            $sql .= ', `type_extra_1`, `name_extra_1`';
        if ($type_extra_2 != '' and $name_extra_2 != '')
            $sql .= ', `type_extra_2`, `name_extra_2`';
        if ($type_extra_3 != '' and $name_extra_3 != '')
            $sql .= ', `type_extra_3`, `name_extra_3`';
        if ($type_extra_4 != '' and $name_extra_4 != '')
            $sql .= ', `type_extra_4`, `name_extra_4`';
        if ($type_extra_5 != '' and $name_extra_5 != '')
            $sql .= ', `type_extra_5`, `name_extra_5`';
        if ($type_extra_6 != '' and $name_extra_6 != '')
            $sql .= ', `type_extra_6`, `name_extra_6`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $label . '"';
        $sql.= ', now()';
        $sql.= ', "' . $price . '"';
        $sql.= ', "' . $id_event . '"';
        $sql.= ', "' . $require_names . '"';
        $sql.= ', ' . ($id_prod_extern != '' ? $id_prod_extern : 'NULL');
        if ($date_start != '')
            $sql.= ', "' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        if ($date_end != '')
            $sql.= ', "' . $date_end_obj->format('Y-m-d H:i:s') . '"';
        if ($type_extra_1 != '' and $name_extra_1 != '')
            $sql.= ', "' . $type_extra_1 . '", "' . $name_extra_1 . '"';
        if ($type_extra_2 != '' and $name_extra_2 != '')
            $sql.= ', "' . $type_extra_2 . '", "' . $name_extra_2 . '"';
        if ($type_extra_3 != '' and $name_extra_3 != '')
            $sql.= ', "' . $type_extra_3 . '", "' . $name_extra_3 . '"';
        if ($type_extra_4 != '' and $name_extra_4 != '')
            $sql.= ', "' . $type_extra_4 . '", "' . $name_extra_4 . '"';
        if ($type_extra_5 != '' and $name_extra_5 != '')
            $sql.= ', "' . $type_extra_5 . '", "' . $name_extra_5 . '"';
        if ($type_extra_6 != '' and $name_extra_6 != '')
            $sql.= ', "' . $type_extra_6 . '", "' . $name_extra_6 . '"';
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

    public function update($id_tariff, $label, $price, $require_names, /* $file, */ $date_start, $time_start, $date_end, $time_end, $type_extra_1, $name_extra_1, $type_extra_2, $name_extra_2, $type_extra_3, $name_extra_3, $type_extra_4, $name_extra_4, $type_extra_5, $name_extra_5, $type_extra_6, $name_extra_6) {

        if (!($id_tariff > 0))
            $this->errors[] = "Le champ identifiant est obligatoire";
        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix est obligatoire";
//        if ($file['error'] != 0)
//            $this->errors[] = "Erreur lors du chargement de l'image";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        if ($date_start != '')
            $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        if ($date_end != '')
            $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);


        $sql = 'UPDATE `tariff` SET';
        $sql.= ' `label`="' . $label . '"';
        $sql.= ', `price`=' . $price;
        $sql.= ', `require_names`=' . $require_names;
        if ($date_start != '')
            $sql.= ', `date_start`="' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        if ($date_end != '')
            $sql.= ', `date_end`="' . $date_end_obj->format('Y-m-d H:i:s') . '"';

        $sql.= ($type_extra_1 != '' and $name_extra_1 != '') ? ', `type_extra_1`=' . $type_extra_1 . ', `name_extra_1`="' . $name_extra_1 . '"' : ', `type_extra_1`= NULL, `name_extra_1`= NULL';
        $sql.= ($type_extra_2 != '' and $name_extra_2 != '') ? ', `type_extra_2`=' . $type_extra_2 . ', `name_extra_2`="' . $name_extra_2 . '"' : ', `type_extra_2`= NULL, `name_extra_2`= NULL';
        $sql.= ($type_extra_3 != '' and $name_extra_3 != '') ? ', `type_extra_3`=' . $type_extra_3 . ', `name_extra_3`="' . $name_extra_3 . '"' : ', `type_extra_3`= NULL, `name_extra_3`= NULL';
        $sql.= ($type_extra_4 != '' and $name_extra_4 != '') ? ', `type_extra_4`=' . $type_extra_4 . ', `name_extra_4`="' . $name_extra_4 . '"' : ', `type_extra_4`= NULL, `name_extra_4`= NULL';
        $sql.= ($type_extra_5 != '' and $name_extra_5 != '') ? ', `type_extra_5`=' . $type_extra_5 . ', `name_extra_5`="' . $name_extra_5 . '"' : ', `type_extra_5`= NULL, `name_extra_5`= NULL';
        $sql.= ($type_extra_6 != '' and $name_extra_6 != '') ? ', `type_extra_6`=' . $type_extra_6 . ', `name_extra_6`="' . $name_extra_6 . '"' : ', `type_extra_6`= NULL, `name_extra_6`= NULL';

        $sql .= ' WHERE id=' . $id_tariff;

//        $this->errors[] = $sql;
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
//            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return 1;
//            if ($last_insert_id > 0) {
//                $source = $file['tmp_name'];
//                $destination = PATH . '/img/event/' . $id_event . '_' . $last_insert_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
//                if (move_uploaded_file($source, $destination) == true)
//                    return $last_insert_id;
//                else {
//                    $this->errors[] = "Erreur lors du déplacement de l'image";
//                    return -5;
//                }
//            } else {
//                return -4;
//            }
//            return $last_insert_id;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier le tarif. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

//    public function getTariffsForEvent($id_event) {
//
//        $tariffs = array();
//
//        $sql = 'SELECT id, label, date_creation, price, fk_event';
//        $sql .= ' FROM tariff';
//        $sql .= ' WHERE fk_event=' . $id_event;
//
//
//        $result = $this->db->query($sql);
//        if ($result and $result->rowCount() > 0) {
//            while ($obj = $result->fetchObject()) {
//                $tariffs[] = array(
//                    'id' => intVal($obj->id),
//                    'label' => $obj->label,
//                    'date_creation' => $obj->date_creation,
//                    'price' => intVal($obj->price),
//                    'fk_event' => intVal($obj->fk_event)
//                );
//            }
//            return $tariffs;
//        } elseif (!$result) {
//            $this->errors[] = "Erreur SQL 1567.";
//            return -2;
//        }
//        return -1;
//    }

    public function getTariffsForEvent($id_event) {

        $tariffs = array();

        $sql = 'SELECT id';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE fk_event=' . $id_event;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $tariff = new Tariff($this->db);
                $tariff->fetch($obj->id);
                $tariffs[] = $tariff;
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

    private function getRandomString($length = 32) {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

}
