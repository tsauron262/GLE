<?php

// pdf lib
include_once 'billet/phpqrcode/qrlib.php';
include_once 'billet/codebar/php-barcode.php';
include_once 'billet/pdf/fpdf.php';
include_once 'billet/pdf/PDF_Code128.php';

class Ticket {

    public $errors;
    private $db;
    public $id;
    private $date_creation;
    public $id_tariff;
    public $id_user;
    private $id_event;
    // option
    public $price;
    // extra
    public $extra_1;
    public $extra_2;
    public $extra_3;
    public $extra_4;
    public $extra_5;
    public $extra_6;
    private $id_order;
    // used for ticket's pdf
    private $pdf;
    private $i = 0;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT date_creation, fk_event, fk_tariff, fk_user, date_scan, ';
        $sql .= 'barcode, first_name, last_name, price, extra_1, extra_2, extra_3, extra_4, extra_5, extra_6, id_order';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->date_creation = $obj->date_creation;
                $this->id_event = $obj->fk_event;
                $this->id_tariff = $obj->fk_tariff;
                $this->id_user = $obj->fk_user;
                $this->date_scan = $obj->date_scan;
                $this->barcode = $obj->barcode;
                $this->first_name = $obj->first_name;
                $this->last_name = $obj->last_name;
                $this->price = $obj->price;
                $this->extra_1 = $obj->extra_1;
                $this->extra_2 = $obj->extra_2;
                $this->extra_3 = $obj->extra_3;
                $this->extra_4 = $obj->extra_4;
                $this->extra_5 = $obj->extra_5;
                $this->extra_6 = $obj->extra_6;
                $this->id_order = $obj->id_order;
                return 1;
            }
        } elseif ($result) {
            $this->errors[] = "Aucun ticket n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($id_tariff, $id_user, $id_event, $price, $first_name, $last_name, $extra_1, $extra_2, $extra_3, $extra_4, $extra_5, $extra_6, $id_order = '') {

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
        $sql.= ($price != '') ? ', `price`' : '';
        $sql.= ($first_name != '') ? ', `first_name`' : '';
        $sql.= ($last_name != '') ? ', `last_name`' : '';
        $sql.= ($extra_1 != '') ? ', `extra_1`' : '';
        $sql.= ($extra_2 != '') ? ', `extra_2`' : '';
        $sql.= ($extra_3 != '') ? ', `extra_3`' : '';
        $sql.= ($extra_4 != '') ? ', `extra_4`' : '';
        $sql.= ($extra_5 != '') ? ', `extra_5`' : '';
        $sql.= ($extra_6 != '') ? ', `extra_6`' : '';
        $sql.= ($id_order != '') ? ', `id_order`' : '';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $id_tariff . '"';
        $sql.= ', now()';
        $sql.= ', "' . $id_user . '"';
        $sql.= ', "' . $id_event . '"';
        $sql.= ', "' . $this->getRandomString() . '"';
        $sql.= ($price != '') ? ', "' . $price . '"' : '';
        $sql.= ($first_name != '') ? ', "' . $first_name . '"' : '';
        $sql.= ($last_name != '') ? ', "' . $last_name . '"' : '';
        $sql.= ($extra_1 != '') ? ', "' . $extra_1 . '"' : '';
        $sql.= ($extra_2 != '') ? ', "' . $extra_2 . '"' : '';
        $sql.= ($extra_3 != '') ? ', "' . $extra_3 . '"' : '';
        $sql.= ($extra_4 != '') ? ', "' . $extra_4 . '"' : '';
        $sql.= ($extra_5 != '') ? ', "' . $extra_5 . '"' : '';
        $sql.= ($extra_6 != '') ? ', "' . $extra_6 . '"' : '';
        $sql.= ($id_order != '') ? ', ' . $id_order . '' : '';
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

        $this->setTicketByBarcode($barcode);

        if (!($this->id > 0)) {
            $this->errors[] = "Identifiant ticket inconnu : " . $this->id;
            return -4;
        }

        $sql = 'SELECT ti.id as id_ticket, (DATE(NOW()) >= DATE(x.date_start)) as constr_date_start, (DATE(NOW()) <= DATE(x.date_end)) as constr_date_end, ti.date_scan as date_scan, ti.fk_event as fk_event';
        $sql .= ' FROM ticket as ti';

        $tariff = new Tariff($this->db);
        $tariff->fetch($this->id_tariff);
        if ($tariff->hasItsOwnDate()) {
            $sql .= ' LEFT JOIN tariff as x ON x.id = ti.fk_tariff';
        } else {
            $sql .= ' LEFT JOIN event as x ON x.id = ti.fk_event';
        }
        $sql .= ' WHERE ti.id=' . $this->id;



        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                if ($obj->fk_event != $id_event) {
                    $event = new Event($this->db);
                    $event->fetch($obj->fk_event);
                    $this->errors[] = "Ce ticket appartient à l'évènement: <strong>" . $event->label . "</strong>";
                    return -6;
                }
                if ($obj->date_scan != NULL) {
                    $date_scan_obj = strtotime($obj->date_scan);
                    $this->errors[] = "Ticket déjà scanné le: <strong>" . date('d/m/Y H:i:s', $date_scan_obj) . "</strong>";
                    return -8;
                }
                if ($obj->constr_date_start != true) {
                    $this->errors[] = "Date de début de validité n'est pas encore atteinte.";
                    return -9;
                }
                if ($obj->constr_date_end != true) {
                    $this->errors[] = "Date de fin de validité atteinte.";
                    return -7;
                }

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

    public function delete($id) {

        if ($id == '')
            $this->errors[] = "Le champ id est obligatoire";

        $this->fetch($id);

        // Delete pdf tariff
        $file = PATH . '/img/tickets/ticket' . base64_encode($this->id_order) . '.pdf';
        if (file_exists($file)) {
            $delete_file_ok = unlink($file);
            if (!$delete_file_ok) {
                $this->errors[] = "Problème lors de la suppression du pdf du ticket.";
                return -3;
            }
        }

        // Delete qrcode
        $file_name_qrcode = PATH . '/img/qrcode/qrcode' . $id . '.png';
        if (file_exists($file_name_qrcode)) {
            $delete_file_ok = unlink($file_name_qrcode);
            if (!$delete_file_ok) {
                $this->errors[] = "Problème lors de la suppression du QR code du ticket.";
                return -4;
            }
        }

        $sql = 'DELETE';
        $sql.= ' FROM ticket';
        $sql.= ' WHERE id=' . $id;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de supprimer le ticket. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function setTicketByBarcode($barcode) {

        $sql = 'SELECT id';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE barcode="' . $barcode . '"';

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return $this->fetch(intVal($obj->id));
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

    private function getRandomString($length = 9) {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

    public function addExtension($file_with_path) {
        $exts = array('bmp', 'png', 'jpg');
        foreach ($exts as $ext) {
            if (file_exists($file_with_path . "." . $ext)) {
                return $path = $file_with_path . "." . $ext;
            }
        }
    }

    public function getNumberTicketByOrder($id_order) {

        $sql = 'SELECT COUNT(*) as nb_ticket_sold';
        $sql.= ' FROM ticket';
        $sql.= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return intVal($obj->nb_ticket_sold);
            }
        } else {
            $this->errors[] = "Id commande inconnu.";
            return -3;
        }
        return -1;
    }

    public function getTicketsByOrder($id_order) {

        $tickets = array();

        $sql = 'SELECT id';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $ticket = new Ticket($this->db);
                $ticket->fetch($obj->id);
                $tickets[] = $ticket;
            }
            return $tickets;
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 2567.";
            return -2;
        }
        return -1;
    }

    public function getTicketsByTariff($id_tariff) {

        $tickets = array();

        $sql = 'SELECT id';
        $sql .= ' FROM ticket';
        $sql .= ' WHERE fk_tariff=' . $id_tariff;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $ticket = new Ticket($this->db);
                $ticket->fetch($obj->id);
                $tickets[] = $ticket;
            }
            return $tickets;
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 6451.";
            return -2;
        }
        return -1;
    }

    public function createPdf($id_ticket, $x, $y, $is_first, $is_last, $set_to_left, $id_order) {
        $this->i++;
        $ticket_width = 140;
        $ticket_height = 64;
        $margin = 5;

        $ticket = new Ticket($this->db);
        $ticket->fetch($id_ticket);

        $tariff = new Tariff($this->db);
        $tariff->fetch($ticket->id_tariff);

        $event = new Event($this->db);
        $event->fetch($ticket->id_event);

        $file_name_qrcode = PATH . '/img/qrcode/qrcode' . $ticket->id . '.png';


        $image_zoom = PATH . '/img/zoomdici.jpg';
        $image_tariff_custom = $this->addExtension(PATH . '/img/tariff_custom/' . $ticket->id_event . '_' . $ticket->id_tariff);

        if ($is_first) {
            $this->pdf = new PDF_Code128('L');
            $this->pdf->AddPage();
            $this->pdf->SetFont('times', '', 12);
        }
        if ($this->i == 7) {
            $this->i = 0;
            $this->pdf->AddPage();
            $x = $y = $margin;
        }
        if ($is_first) {
            $this->pdf = new PDF_Code128('L');
            $this->pdf->AddPage();
            $this->pdf->SetFont('times', '', 12);
        }

        $this->pdf->SetX($x);
        $this->pdf->SetY($y);

        if ($tariff->filename_custom != null) {
            $this->pdf->Image($image_tariff_custom, $x + $margin + 2, $y + $margin - 1, 88, 57);
        } else {
            $this->pdf->Image($image_zoom, $x + $margin + 2, $y + $margin + 5, 50, 50);
            $this->pdf->SetY($y + 8);
            $this->pdf->SetX($x + 65);

            $max_width = 23;

            $this->pdf->MultiCell(40, 4, mb_strimwidth($event->label, 0, $max_width, "...") . "\n" .
                    mb_strimwidth($tariff->label, 0, $max_width, "...") . "\n" .
                    mb_strimwidth(($ticket->first_name == null ? '' : $ticket->first_name), 0, $max_width, "...") . "\n" .
                    mb_strimwidth(($ticket->last_name == null ? '' : $ticket->last_name), 0, $max_width, "..."));
        }

        $this->pdf->Code128($x + 98, $y + 3, $ticket->barcode, 58, 12);
        QRcode::png(URL_PRESTA . "/index.php?id_product=" . $tariff->id_prod_extern . "&id_product_attribute=0&rewrite=&controller=product&num=" . $ticket->barcode, $file_name_qrcode, 0, 3);
        $this->pdf->Image($file_name_qrcode, $x + 110, $y + 17, 29, 29);


        $this->pdf->Image(PATH . '/img/ticket_border.png', $x, $y, $ticket_width, $ticket_height);

        if ($is_last)
            $this->pdf->Output(PATH . '/img/tickets/ticket' . base64_encode($id_order) . '.pdf', 'F');

        if ($set_to_left)
            return array('x' => $x + $ticket_width + $margin, 'y' => $y);
        else
            return array('x' => $x - $ticket_width - $margin, 'y' => $y + $ticket_height + $margin);
    }

    public function createPdfFromCheck($ids_inserted, $id_event, $id_tariff, $with_num, $num_start, $format, $souche) {
        $current_num = $num_start;
        $i = 0;

        $x = $y = $margin = 1;

        $ticket_width = 147;
        $ticket_height = 68;


        if ($souche == 1) {
            $souche_width = 48;
            $ticket_width -= $souche_width;
        }


        if ($format == 'A4') {
            $nb_ticket_page = 6;
            $nb_ticket_width = 2;
            $orientation = 'L';
        } elseif ($format == 'TICKET') {
            $nb_ticket_page = 1;
            $nb_ticket_width = 1;
            $width = 148;  
            $height = 70; 
            $format = array($width, $height);
            $orientation = ($height>$width) ? 'P' : 'L'; 
        } elseif ($format == 'A3') {
            $nb_ticket_page = 12;
            $nb_ticket_width = 2;
            $orientation = 'P';
        }

        $pdf = new PDF_Code128($orientation, 'mm', $format);
        $pdf->SetFont('times', '', 12);
        
        $event = new Event($this->db);
        $event->fetch($id_event);

        $tariff = new Tariff($this->db);
        $tariff->fetch($id_tariff);

        foreach ($ids_inserted as $id) {
            // Position
            if ($i % $nb_ticket_page == 0) { // change page
                $pdf->AddPage();
                $x = $y = $margin;
            } else { // change position in page
                if ($i % $nb_ticket_width == 0) { // end of line
                    $x = $margin;
                    $y += $margin + $ticket_height;
                } else { // add at the same height
                    $x += $margin + $ticket_width;
                }
            }

            if ($with_num == 1)
                $pdf->SetXY($x + 131, $y + 10);

            if ($souche == 1)
                $pdf->SetXY($x + 10, $y + 20);

            $ticket = new Ticket($this->db);
            $ticket->fetch($id);

            if ($tariff->filename_custom != null) {
                $image_tariff = PATH . '/img/tariff_custom/' . $tariff->filename_custom;
            } else {
                $image_tariff = PATH . '/img/event/' . $tariff->filename;
            }
            $file_name_qrcode = PATH . '/img/qrcode/qrcode' . $ticket->id . '.png';
            QRcode::png(URL_PRESTA . "/index.php?id_product=" . $tariff->id_prod_extern . "&id_product_attribute=0&rewrite=&controller=product&num=" . $ticket->barcode, $file_name_qrcode, 0, 3);


            // Add in pdf
            if ($souche == 1) { // write souche
                $pdf->Image(PATH . '/img/ticket_border.png', $x, $y, $souche_width, $ticket_height);
                $pdf->MultiCell(30, 4, ($with_num == 1 ? $current_num . "\n" : '') . utf8_decode($event->label)."\n".$tariff->label);

                $x += $souche_width;

                $pdf->SetXY($x + 83, $y + 10);

                // write common
                if ($with_num == 1)
                    $pdf->MultiCell(20, 4, $current_num);

                $pdf->Image($file_name_qrcode, $x + 74, $y + 22, 25, 25);
                $pdf->Code128($x + 67, $y + 3, $ticket->barcode, 63, 8);
                $pdf->Image($image_tariff, $x + 2, $y + 5, 64, 59);
                $pdf->Image(PATH . '/img/ticket_border.png', $x, $y, $ticket_width, $ticket_height);
            } else {
                if ($with_num == 1)
                    $pdf->MultiCell(10, 4, $current_num);

                $pdf->Image($file_name_qrcode, $x + 122, $y + 21, 25, 25);
                $pdf->Code128($x + 114, $y + 2, $ticket->barcode, 63, 8);
                $pdf->Image($image_tariff, $x + 7, $y + 4, 105, 60);
                $pdf->Image(PATH . '/img/ticket_border.png', $x, $y, $ticket_width, $ticket_height);
            }

            // Counters
            $current_num++;
            $i++;
        }
        $pdf->Output(PATH . '/img/multiple_print.pdf', 'F');
        return 1;
    }

}
