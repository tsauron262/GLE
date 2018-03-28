<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/main.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpValidateOrder {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    /**
     * Triggered when validating an order
     * 
     * @param type $user        the user who try to validate the order
     * @param type $price_order the price of the order
     * @param type $order       the order object
     * @return int 1  => the user can validate it by himself
     *             -1 => an email is sent to the responsible
     *             -2 => error while sending email
     *             -3 => error max price undefined
     *             -4 => error other
     */
    public function checkValidateRights($user, $order) {

        $price_order = $order->total_ht;

        $max_price = $this->getMaxPriceOrder($user);

        if (sizeof($this->errors) != 0) {
            setEventMessages(null, $this->errors, 'errors');
            return -3;
        }

        if ($max_price < $price_order) {
            $id_responsible = $this->getFirstResponsibleId($price_order);
            if ($this->sendEmailToResponsible($id_responsible, $user, $order) == true) {
                setEventMessages("Un mail à été envoyé à un responsable pour qu'il valide cette commande.", null, 'warnings');
                return -1;
            } else {
                setEventMessages(null, $this->errors, 'errors');
                return -2;
            }
        }

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $this->errors = array_merge($this->errors, $reservation->createReservationsFromCommandeClient($order->entrepot, $order->id));

        if (sizeof($this->errors) != 0) {
            setEventMessages(null, $this->errors, 'errors');
        }


        if (sizeof($this->errors) == 0)
            return 1;
        else {
            setEventMessages(null, $this->errors, 'errors');
            return -5;
        }
    }

    /**
     * Other functions
     */

    /**
     * Get the maximum price a user can validate
     */
    private function getMaxPriceOrder($user) {

        if ($user->id < 0) {
            $this->errors[] = "Identifiant utilisateur inconnu.";
            return -1;
        }

        $sql = 'SELECT maxpriceorder';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql.= ' WHERE fk_object=' . $user->id;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $max_price = $obj->maxpriceorder;
            }
        } elseif (!$result) {
            $this->errors[] = "La requête SQL pour la recherche du prix maximum a échouée.";
            return -2;
        }

        if ($max_price < 0 or $max_price == '') {
            $this->errors[] = "Prix maximum de validation de commande pour l'utilisateur non définit.";
            return -3;
        }

        return $max_price;
    }

    private function getFirstResponsibleId($price) {

        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql .= ' WHERE maxpriceorder >=' . $price;
        $sql .= ' ORDER BY maxpriceorder ASC';
        $sql .= ' LIMIT 1';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $this->db->fetch_object($result);
            $id_responsible = $obj->rowid;
        } elseif (!$result) {
            $this->errors[] = "La requête SQL pour la recherche du responsable.";
            return -1;
        }

        return $id_responsible;
    }

    private function sendEmailToResponsible($id_responsible, $user, $order) {

        $doli_user_responsible = new User($this->db);
        $doli_user_responsible->fetch($id_responsible);

        $subject = "BIMP ERP - Demande de validation de commande client";

        $msg = "Bonjour, \n\n";
        $msg.= "L'utilisateur $user->firstname $user->lastname souhaite que vous validiez la commande suivante : ";
        $msg.= $order->getNomUrl();
        return mailSyn2($subject, $doli_user_responsible->email, '', $msg);
    }

}
