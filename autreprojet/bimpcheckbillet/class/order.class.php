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
        $sql.= ' FROM '.PRESTA_PREF.'order_detail';
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
        $sql.= ' FROM '.PRESTA_PREF.'order_detail';
        $sql.= ' WHERE id_order=' . $id_order;

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return intVal($obj->qty);
            }
        } else {
            $this->errors[] = "Id commande inconnu.";
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
//        return 1;
        $qty_total = $this->getNumberTicketSold($id_order);

        $qty_sold = $ticket_obj->getNumberTicketByOrder($id_order);
        
//        echo $qty_total

        if ($qty_total == $qty_sold)
            return 1;
        return -1;
    }

}
