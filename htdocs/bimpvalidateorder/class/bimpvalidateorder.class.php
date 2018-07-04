<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

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

        $max_price = $this->getMaxPriceOrder($user, $order);
        if (sizeof($this->errors) != 0) {
            setEventMessages(null, $this->errors, 'errors');
            return -3;
        }
        $tropRemise = ($order->array_options['options_type'] == "C" ? $this->checkRemise($order) : 0);

die($tropRemise);
        if ($max_price <= $price_order || $tropRemise) {
            $id_responsibles = $this->getResponsiblesIds($price_order, $order);
            $error = false;
            foreach ($id_responsibles as $id_responsible) {
                if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true){
                    $error = true;
                    $this->errors[] = 'Envoie d\'email impossible';
                }
            }
            if (!$error) {
                setEventMessages("Un mail à été envoyé à un responsable pour qu'il valide cette commande.", null, 'warnings');
                return -1;
            } else {
                setEventMessages(null, $this->errors, 'errors');
                return -2;
            }
        }
        $idEn = $order->array_options['options_entrepot'];
        if ($idEn < 1) {
            setEventMessages("Pas d'entrepot associé", null, 'errors');
            return -2;
        }
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $this->errors = array_merge($this->errors, $reservation->createReservationsFromCommandeClient($idEn, $order->id));

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
    private function getMaxPriceOrder($user, $order) {
        if ($order->array_options['options_type'] == "E" && $user->id == 7) {
            return 100000;
        }

        if ($user->id < 0) {
            $this->errors[] = "Identifiant utilisateur inconnu.";
            return -1;
        }

        $sql = 'SELECT maxpriceorder';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql .= ' WHERE fk_object=' . $user->id;

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

    function checkRemise($order) {
        $ok = true;
        foreach ($order->lines as $line)
            if ($line->remise_percent > 5) {
                $this->extraMail[] = "Ligne " . $line->desc . " avec un réduction de " . $line->remise_percent . "%";
                $ok = false;
            }

        return $ok;
    }

    private function getResponsiblesIds($price, $order) {
        if ($order->array_options['options_type'] == "E" && $price < 100000) {
            return array(7);
        } else {
            if ($price < 50000)
                return array(2);
            else
                return array(2, 68);
        }
    }

    private function getFirstResponsibleId($price) {

        $sql = 'SELECT fk_object';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql .= ' WHERE maxpriceorder >=' . $price;
        $sql .= ' ORDER BY maxpriceorder ASC';
        $sql .= ' LIMIT 1';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $this->db->fetch_object($result);
            $id_responsible = $obj->fk_object;
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
        $msg .= "L'utilisateur $user->firstname $user->lastname souhaite que vous validiez la commande suivante : ";
        $msg .= $order->getNomUrl();
        foreach($this->extraMail as $extra){
            $msg .= "\n\n".$extra;
        }
        echo $msg;
        return mailSyn2($subject, $doli_user_responsible->email, $user->email, $msg);
    }

}
