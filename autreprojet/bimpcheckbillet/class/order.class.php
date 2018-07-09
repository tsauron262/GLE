<?php

class Order {

    private $db;
    public $errors;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function check($id_order, $tickets, $ticket_obj) {

        $code_return = $this->checkQty($id_order, $tickets, $ticket_obj);

        if ($code_return < 0) {
            $this->errors[] = "Plus de ticket envoyé que de commandé";
            return -5;
        }

        $prod_qty = array();
        foreach ($tickets as $ticket) {
            $key = $ticket['id_product'];
            if (!isset($prod_qty[$key]))
                $prod_qty[$key] = 1;
            else
                $prod_qty[$key] ++;
        }

        $sum_ticket = 0;
        $sum_order = 0;
        foreach ($tickets as $ticket) {
            $sum_ticket += $ticket['price'];
        }

        $sql = 'SELECT product_id, product_price, product_quantity';
        $sql.= ' FROM ' . PRESTA_PREF . 'order_detail';
        $sql.= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                if (intVal($prod_qty[$obj->product_id]) > intVal($obj->product_quantity)) { // check qty
                    $this->errors[] = "Quantité trop grande.";
                    return -3;
                }
                $sum_order += ($obj->product_price * $obj->product_quantity);
            }
            if (number_format($sum_ticket, 2) <= number_format($sum_order, 2)) // check price
                return 1;
            else {
                $this->errors[] = "Prix différent.";
                return -4;
            }
        } else {
            $this->errors[] = "Pas de résultat lors des vérification";
            return -2;
        }
        return -1;
    }

    private function getNumberTicketSold($id_order) {

        $sql = 'SELECT SUM(product_quantity) as qty';
        $sql.= ' FROM ' . PRESTA_PREF . 'order_detail';
        $sql.= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return intVal($obj->qty);
            }
        } else {
            $this->errors[] = "Id commande inconnu : " . $id_order;
            return -2;
        }
    }

    private function checkQty($id_order, $tickets, $ticket_obj) {

        $qty_total = $this->getNumberTicketSold($id_order);

        $qty_sold = $ticket_obj->getNumberTicketByOrder($id_order);

        if ($qty_total < $qty_sold + sizeof($tickets))
            return -2;
        return 1;
    }

    /**
     * @return int 1 => order fullfilled, -1 other
     */
    public function checkOrderStatus($id_order, $ticket_obj) {

        $sql = ' SELECT valid';
        $sql .= ' FROM ' . PRESTA_PREF . 'orders';
        $sql .= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return intVal($obj->valid);
            }
        } else {
            $this->errors[] = "Id commande inconnu : " . $id_order;
            return -2;
        }
        return -1;

//        $qty_total = $this->getNumberTicketSold($id_order);
//
//        $qty_sold = $ticket_obj->getNumberTicketByOrder($id_order);
//
//        if ($qty_total == $qty_sold)
//            return 1;
//        return -1;
    }

    public function generateTicket($id_order, $ticket, $tariff) {

        $generated_tickets = 0;
        $prod_qty = array(); // id_prod => qty

        $sql = 'SELECT product_id, product_quantity';
        $sql .= ' FROM ' . PRESTA_PREF . 'order_detail';
        $sql .= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $prod_qty[$obj->product_id] = $obj->product_quantity;
            }
        } elseif ($result) {
            $this->error[] = "Aucun produit n'est enregistré dans la base prestashop pour cet identifiant de commande";
            return -1;
        } else {
            $this->errors[] = "Erreur SQL 3471";
        }

        $tickets = $ticket->getTicketsByOrder($id_order);

        $tariffs = $tariff->getTariffByProdsExtern(array_keys($prod_qty));
//var_dump($tariffs);
        
        foreach ($prod_qty as $id => $qty) {
            // count existing tickets for that tariff in the order
            $cnt_ticket = 0;
            $current_tariff;
            $has_requirement = false;
            foreach ($tariffs as $ta) {
                if ($ta->id_prod_extern == $id) {
                    if (is_array($tickets)) {
                        foreach ($tickets as $ti) {
                            if ($ti->id_tariff == $ta->id)
                                $cnt_ticket++;
                        }
                    }

                    if ($ta->type_extra_1 or
                            $ta->type_extra_2 or
                            $ta->type_extra_3 or
                            $ta->type_extra_4 or
                            $ta->type_extra_5 or
                            $ta->type_extra_6 or
                            $ta->require_names) {
                        $has_requirement = true;
                    }
                    $current_tariff = $ta;
                    break;
                }
            }
//            echo 'déjà généré :' . $cnt_ticket;
//            echo 'recqui :' . $qty;
            // create missing tickets without field required
            if (!$has_requirement) {
                while ($cnt_ticket < $qty) {
                    $ticket->create($current_tariff->id, EXTERN_USER, $current_tariff->fk_event, $current_tariff->price, '', '', '', '', '', '', '', '', $id_order);
                    $generated_tickets++;
                    $cnt_ticket++;
                }
            }
        }
        return $generated_tickets;
    }

}
