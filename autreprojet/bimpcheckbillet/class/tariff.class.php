<?php

class Tariff {

    public $errors;
    private $db;
    public $id;
    public $label;
    public $date_creation;
    public $price;
    public $number_place;
    public $fk_event;
    public $date_stop_sale;
    public $date_start;
    public $date_end;
    public $require_names;
    public $id_prod_extern;
    public $filename;
    public $filename_custom;
    public $attributes;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->attributes = array();
    }

    public function fetch($id, $fetch_attribute = false) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT label, date_creation, require_names, date_stop_sale, date_start, date_end, price, number_place, type_extra_1, type_extra_2, type_extra_3, type_extra_4, type_extra_5, type_extra_6, name_extra_1, name_extra_2, name_extra_3, name_extra_4, name_extra_5, name_extra_6, require_extra_1, require_extra_2, require_extra_3, require_extra_4, require_extra_5, require_extra_6, id_prod_extern, fk_event';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE id=' . $id;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = intVal($id);
                $this->label = $obj->label;
                $this->date_creation = $obj->date_creation;
                $this->price = floatVal($obj->price);
                $this->number_place = floatVal($obj->number_place);
                $this->fk_event = $obj->fk_event;
                $this->require_names = intVal($obj->require_names);
                $this->date_stop_sale = $obj->date_stop_sale;
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
                $this->require_extra_1 = intVal($obj->require_extra_1);
                $this->require_extra_2 = intVal($obj->require_extra_2);
                $this->require_extra_3 = intVal($obj->require_extra_3);
                $this->require_extra_4 = intVal($obj->require_extra_4);
                $this->require_extra_5 = intVal($obj->require_extra_5);
                $this->require_extra_6 = intVal($obj->require_extra_6);
                $this->id_prod_extern = intVal($obj->id_prod_extern);
                $exts = array('bmp', 'png', 'jpg');
                foreach ($exts as $ext) {
                    if (file_exists(PATH . '/img/event/' . $obj->fk_event . '_' . $id . "." . $ext)) {
                        $filename = $obj->fk_event . '_' . $id . "." . $ext;
                    }
                }
                if ($filename == null) {
                    foreach ($exts as $ext) {
                        if (file_exists(PATH . '/img/event/' . $obj->fk_event . "." . $ext)) {
                            $filename = $obj->fk_event . "." . $ext;
                        }
                    }
                }
                foreach ($exts as $ext) {
                    if (file_exists(PATH . '/img/tariff_custom/' . $obj->fk_event . '_' . $id . "." . $ext)) {
                        $filename_custom = $obj->fk_event . '_' . $id . "." . $ext;
                    }
                }
                @$this->filename = $filename;
                @$this->filename_custom = $filename_custom;
                if ($fetch_attribute) {
                    $this->fetchAttribute();
                }
                return 1;
            }
        } else {
            $this->errors[] = "Aucun tariff n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($label, $price, $number_place, $id_event, $file, $custom_img, $use_custom_img, $require_names, $id_prod_extern, $date_stop_sale, $time_end_sale, $date_start, $time_start, $date_end, $time_end, $type_extra_1, $name_extra_1, $require_extra_1, $type_extra_2, $name_extra_2, $require_extra_2, $type_extra_3, $name_extra_3, $require_extra_3, $type_extra_4, $name_extra_4, $require_extra_4, $type_extra_5, $name_extra_5, $require_extra_5, $type_extra_6, $name_extra_6, $require_extra_6) {

        if ($date_stop_sale == '')
            $this->errors[] = "Le champ date de fin de vente est obligatoire";
        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix est obligatoire";
        if ($number_place == '')
            $this->errors[] = "Le champ nombre de place";
        if ($id_event == '')
            $this->errors[] = "Le champ évènement est obligatoire";
        if ($file['error'] != 0)
            $this->errors[] = "Erreur lors du chargement de l'image";
        if ($require_names == '')
            $this->errors[] = "Le champ exiger nom et prénom est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($time_end_sale == '')
            $time_end_sale = '00:00';
        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_stop_sale = $date_stop_sale . ' ' . $time_end_sale . ':00';
        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        $date_stop_sale_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_stop_sale);
        if ($date_start != '')
            $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        if ($date_end != '')
            $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);


        $sql = 'INSERT INTO `tariff` (';
        $sql.= '`label`';
        $sql.= ', `date_creation`';
        $sql.= ', `price`';
        $sql.= ', `number_place`';
        $sql.= ', `fk_event`';
        $sql.= ', `require_names`';
        $sql.= ', `id_prod_extern`';
        $sql.= ', `date_stop_sale`';
        if ($date_start != '')
            $sql.= ', `date_start`';
        if ($date_end != '')
            $sql.= ', `date_end`';
        if ($type_extra_1 != '' and $name_extra_1 != '' and $require_extra_1 != '')
            $sql .= ', `type_extra_1`, `name_extra_1`, `require_extra_1`';
        if ($type_extra_2 != '' and $name_extra_2 != '' and $require_extra_2 != '')
            $sql .= ', `type_extra_2`, `name_extra_2`, `require_extra_2`';
        if ($type_extra_3 != '' and $name_extra_3 != '' and $require_extra_3 != '')
            $sql .= ', `type_extra_3`, `name_extra_3`, `require_extra_3`';
        if ($type_extra_4 != '' and $name_extra_4 != '' and $require_extra_4 != '')
            $sql .= ', `type_extra_4`, `name_extra_4`, `require_extra_4`';
        if ($type_extra_5 != '' and $name_extra_5 != '' and $require_extra_5 != '')
            $sql .= ', `type_extra_5`, `name_extra_5`, `require_extra_5`';
        if ($type_extra_6 != '' and $name_extra_6 != '' and $require_extra_6 != '')
            $sql .= ', `type_extra_6`, `name_extra_6`, `require_extra_6`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $label . '"';
        $sql.= ', now()';
        $sql.= ', "' . $price . '"';
        $sql.= ', "' . $number_place . '"';
        $sql.= ', "' . $id_event . '"';
        $sql.= ', "' . $require_names . '"';
        $sql.= ', ' . ($id_prod_extern != '' ? $id_prod_extern : 'NULL');
        $sql.= ', "' . $date_stop_sale_obj->format('Y-m-d H:i:s') . '"';
        if ($date_start != '')
            $sql.= ', "' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        if ($date_end != '')
            $sql.= ', "' . $date_end_obj->format('Y-m-d H:i:s') . '"';
        if ($type_extra_1 != '' and $name_extra_1 != '' and $require_extra_1 != '')
            $sql.= ', "' . $type_extra_1 . '", "' . $name_extra_1 . '", ' . $require_extra_1;
        if ($type_extra_2 != '' and $name_extra_2 != '' and $require_extra_2 != '')
            $sql.= ', "' . $type_extra_2 . '", "' . $name_extra_2 . '", ' . $require_extra_2;
        if ($type_extra_3 != '' and $name_extra_3 != '' and $require_extra_3 != '')
            $sql.= ', "' . $type_extra_3 . '", "' . $name_extra_3 . '", ' . $require_extra_3;
        if ($type_extra_4 != '' and $name_extra_4 != '' and $require_extra_4 != '')
            $sql.= ', "' . $type_extra_4 . '", "' . $name_extra_4 . '", ' . $require_extra_4;
        if ($type_extra_5 != '' and $name_extra_5 != '' and $require_extra_5 != '')
            $sql.= ', "' . $type_extra_5 . '", "' . $name_extra_5 . '", ' . $require_extra_5;
        if ($type_extra_6 != '' and $name_extra_6 != '' and $require_extra_6 != '')
            $sql.= ', "' . $type_extra_6 . '", "' . $name_extra_6 . '", ' . $require_extra_6;
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            if ($last_insert_id > 0) {
                $source = $file['tmp_name'];
                $destination = PATH . '/img/event/' . $id_event . '_' . $last_insert_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($source, $destination) == true) {
                    if ($use_custom_img) {
                        $source = $custom_img['tmp_name'];
                        $destination = PATH . '/img/tariff_custom/' . $id_event . '_' . $last_insert_id . '.' . pathinfo($custom_img['name'], PATHINFO_EXTENSION);
                        if (move_uploaded_file($source, $destination) == true) {
                            return $last_insert_id;
                        } else {
                            $this->db->rollBack();
                            $this->errors[] = "Erreur lors du déplacement de l'image personnalisée sur les tickets, création du tariff annulé";
                            return -5;
                        }
                    } else
                        return $last_insert_id;
                } else {
                    $this->db->rollBack();
                    $this->errors[] = "Erreur lors du déplacement de l'image du tariff, création du tariff annulé";
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

    public function update($id_tariff, $label, $price, $number_place, $require_names, /* $file, */ $date_stop_sale, $time_stop_sale, $date_start, $time_start, $date_end, $time_end, $type_extra_1, $name_extra_1, $require_extra_1, $type_extra_2, $name_extra_2, $require_extra_2, $type_extra_3, $name_extra_3, $require_extra_3, $type_extra_4, $name_extra_4, $require_extra_4, $type_extra_5, $name_extra_5, $require_extra_5, $type_extra_6, $name_extra_6, $require_extra_6) {

        if ($date_stop_sale == '')
            $this->errors[] = "Le champ date de fin de vente est obligatoire";
        if (!($id_tariff > 0))
            $this->errors[] = "Le champ identifiant est obligatoire";
        if ($label == '')
            $this->errors[] = "Le champ label est obligatoire";
        if ($price == '')
            $this->errors[] = "Le champ prix est obligatoire";
        if ($number_place == '')
            $this->errors[] = "Le champ nombre de places est obligatoire";
//        if ($file['error'] != 0)
//            $this->errors[] = "Erreur lors du chargement de l'image";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($time_stop_sale == '')
            $time_stop_sale = '00:00';
        if ($time_start == '')
            $time_start = '00:00';
        if ($time_end == '')
            $time_end = '00:00';

        $full_date_stop_sale = $date_stop_sale . ' ' . $time_stop_sale . ':00';
        $full_date_start = $date_start . ' ' . $time_start . ':00';
        $full_date_end = $date_end . ' ' . $time_end . ':00';

        $date_stop_sale_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_stop_sale);
        if ($date_start != '')
            $date_start_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_start);
        if ($date_end != '')
            $date_end_obj = DateTime::createFromFormat('d/m/Y H:i:s', $full_date_end);


        $sql = 'UPDATE `tariff` SET';
        $sql.= ' `label`="' . $label . '"';
        $sql.= ', `price`=' . $price;
        $sql.= ', `number_place`=' . $number_place;
        $sql.= ', `require_names`=' . $require_names;
        $sql.= ', `date_stop_sale`="' . $date_stop_sale_obj->format('Y-m-d H:i:s') . '"';
        if ($date_start != '')
            $sql.= ', `date_start`="' . $date_start_obj->format('Y-m-d H:i:s') . '"';
        if ($date_end != '')
            $sql.= ', `date_end`="' . $date_end_obj->format('Y-m-d H:i:s') . '"';

        $sql.= ($type_extra_1 != '' and $name_extra_1 != '') ? ', `type_extra_1`=' . $type_extra_1 . ', `name_extra_1`="' . $name_extra_1 . '", `require_extra_1`=' . $require_extra_1 : ', `type_extra_1`= NULL, `name_extra_1`= NULL, `require_extra_1`= NULL';
        $sql.= ($type_extra_2 != '' and $name_extra_2 != '') ? ', `type_extra_2`=' . $type_extra_2 . ', `name_extra_2`="' . $name_extra_2 . '", `require_extra_2`=' . $require_extra_2 : ', `type_extra_2`= NULL, `name_extra_2`= NULL, `require_extra_2`= NULL';
        $sql.= ($type_extra_3 != '' and $name_extra_3 != '') ? ', `type_extra_3`=' . $type_extra_3 . ', `name_extra_3`="' . $name_extra_3 . '", `require_extra_3`=' . $require_extra_3 : ', `type_extra_3`= NULL, `name_extra_3`= NULL, `require_extra_3`= NULL';
        $sql.= ($type_extra_4 != '' and $name_extra_4 != '') ? ', `type_extra_4`=' . $type_extra_4 . ', `name_extra_4`="' . $name_extra_4 . '", `require_extra_4`=' . $require_extra_4 : ', `type_extra_4`= NULL, `name_extra_4`= NULL, `require_extra_4`= NULL';
        $sql.= ($type_extra_5 != '' and $name_extra_5 != '') ? ', `type_extra_5`=' . $type_extra_5 . ', `name_extra_5`="' . $name_extra_5 . '", `require_extra_5`=' . $require_extra_5 : ', `type_extra_5`= NULL, `name_extra_5`= NULL, `require_extra_5`= NULL';
        $sql.= ($type_extra_6 != '' and $name_extra_6 != '') ? ', `type_extra_6`=' . $type_extra_6 . ', `name_extra_6`="' . $name_extra_6 . '", `require_extra_6`=' . $require_extra_6 : ', `type_extra_6`= NULL, `name_extra_6`= NULL, `require_extra_6`= NULL';

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

    public function getTariffsForEvent($id_event, $fetch_attribute = false) {

        $tariffs = array();

        $sql = 'SELECT id';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE fk_event=' . $id_event;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $tariff = new Tariff($this->db);
                $tariff->fetch($obj->id, $fetch_attribute);
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

    public function getTariffByProdsExtern($ids_prods_extern) {
        $tariffs = array();

        $sql = 'SELECT id';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE id_prod_extern  IN(' . implode(',', $ids_prods_extern) . ')';

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $tariff = new Tariff($this->db);
                $tariff->fetch($obj->id);
                $tariffs[] = $tariff;
            }
            return $tariffs;
        } elseif (!$result) {
            $this->errors[] = "Id produit extern inconnu";
            return -2;
        }
        return -1;
    }

    public function getIdsEventsByIdsTariffs($ids_tariff) {
        $ids_events = array();

        $sql = 'SELECT id, fk_event';
        $sql .= ' FROM tariff';
        $sql .= ' WHERE id  IN(' . implode(',', $ids_tariff) . ')';

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $ids_events[intVal($obj->id)] = intVal($obj->fk_event);
            }
            return $ids_events;
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 1581.";
            return -2;
        }
        return -1;
    }

    public function getRemainingPlace($id_tariff) {

        $sql = 'SELECT ta.number_place as number_place, COUNT(ti.id) as number_ticket';
        $sql .= ' FROM tariff as ta';
        $sql .= ' LEFT JOIN ticket as ti ON ta.id = ti.fk_tariff';
        $sql .= ' WHERE ta.id=' . $id_tariff;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return intVal($obj->number_place) - intVal($obj->number_ticket);
            }
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 1581.";
            return -2;
        }
        return -1;
    }

    public function setIdProdExtern($id_tariff, $id_prod_extern) {

        if (!($id_tariff > 0))
            $this->errors[] = "Le champ identifiant tariff est obligatoire";
        if (!($id_prod_extern > 0))
            $this->errors[] = "Le champ identifiant produit externe est obligatoire";


        $sql = 'UPDATE `tariff` SET';
        $sql.= ' `id_prod_extern`=' . $id_prod_extern;
        $sql .= ' WHERE id=' . $id_tariff;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Erreur SQL 6788";
        }
        return -1;
    }

    public function getImageName($id_tariff) {
        $tariff = new Tariff($this->db);
        $tariff->fetch($id_tariff);
        $exts = array('bmp', 'png', 'jpg');

        $file = $tariff->fk_event . '_' . $tariff->id;
        foreach ($exts as $ext) {
            if (file_exists(PATH . '/img/event/' . $file . "." . $ext)) {
                return $file . "." . $ext;
            }
        }

        $file = $tariff->fk_event;
        foreach ($exts as $ext) {
            if (file_exists(PATH . '/img/event/' . $file . "." . $ext)) {
                return $file . "." . $ext;
            }
        }

        $this->errors[] = "Aucune image trouvé";
        return -1;
    }

    public function fetchAttribute() {
        $sql = 'SELECT fk_attribute';
        $sql .= ' FROM tariff_attribute';
        $sql .= ' WHERE fk_tariff=' . $this->id;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $attribute = new Attribute($this->db);
                $attribute->fetch($obj->fk_attribute, true);
                $this->attributes[] = $attribute;
            }
        } elseif (!$result) {
            $this->errors[] = "Id produit extern inconnu";
            return -2;
        }
        return -1;
    }

    public function delete() {

        // Delete tickets
        $static_ticket = new Ticket($this->db);
        $tickets = $static_ticket->getTicketsByTariff($this->id);
        foreach ($tickets as $ticket) {
            if ($ticket->delete($ticket->id) != 1) {
                $this->errors = array_merge($this->errors, $ticket->errors);
                return -1;
            }
        }


        // Delete attribute_value_tariff
        $sql = 'DELETE FROM `attribute_value_tariff`';
        $sql.= ' WHERE `fk_tariff`=' . $this->id;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison tarif - valeur d'attribut. " . $e;
            $this->db->rollBack();
            return -2;
        }


        // Delete attribute_value_tariff
        $sql = 'DELETE FROM `tariff_attribute`';
        $sql.= ' WHERE `fk_tariff`=' . $this->id;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer la liaison tarif - attribut. " . $e;
            $this->db->rollBack();
            return -3;
        }


        // Delete tariff
        $sql = 'DELETE FROM `tariff`';
        $sql.= ' WHERE `id`=' . $this->id;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer le tarif. " . $e;
            $this->db->rollBack();
            return -4;
        }


        // Delete image tariff
        $file_tariff = PATH . '/img/event/' . $this->filename;
        if (file_exists($file_tariff)) {
            $delete_tariff_file_ok = unlink($file_tariff);
            if (!$delete_tariff_file_ok) {
                $this->errors[] = "Problème lors de la suppression d'image de tarif.";
                return -5;
            }
        }

        // Delete image custom tariff
        if (isset($this->filename_custom)) {
            $file_tariff_custom = PATH . '/img/tariff_custom/' . $this->filename_custom;
            if (file_exists($file_tariff_custom)) {
                $delete_tariff_file_ok = unlink($file_tariff_custom);
                if (!$delete_tariff_file_ok) {
                    $this->errors[] = "Problème lors de la suppression d'image de ticket correspondant à cet tarif.";
                    return -5;
                }
            }
        }

        return 1;
    }

}
