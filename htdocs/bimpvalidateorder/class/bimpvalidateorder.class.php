<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpValidateOrder {

    private $db;
    public $errors;
    private $tabValideComm = array(62 => 100);
    private $tabValideCommEduc = array(51 => 100);
    private $tabValideMontant = array(2 => array(0, 1000000000000), 68 => array(50000, 100000000000));
    private $tabValideMontantPart = array(7 => array(0, 100000), 2 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
    private $tabValideMontantEduc = array(51 => array(0, 100000), 2 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
    private $tabSecteurEduc = array("E", "ENS", "EBTS");

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
        
        
        $updateValFin = $updateValComm = false;
        $ok = true;
        $id_responsiblesFin = $id_responsiblesComm = array();
        $sql = $this->db->query("SELECT `validFin`, `validComm` FROM `llx_commande` WHERE `rowid` = ".$order->id);
        $result = $this->db->fetch_object($sql);
        
        if($result->validFin < 1){
            $id_responsiblesFin = $this->checkAutorisationFinanciere($user, $order);
            if(count($id_responsiblesFin) == 0){
                $updateValFin = true;
            }
            else{
                $ok = false;
                $error = false;
                foreach ($id_responsiblesFin as $id_responsible) {
                    if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true)
                        $error = true;
                }
                if (!$error) {
                    setEventMessages("Un mail à été envoyé à un responsable pour qu'il valide cette commande financiérement.", null, 'warnings');
                }
                else
                        $this->errors[] = 'Envoi d\'email impossible';
            }
        }
        
        
        
        
        if($result->validComm < 1){
            $id_responsiblesComm = $this->checkAutorisationCommmerciale($user, $order);
            if(count($id_responsiblesComm) == 0){
                $updateValComm = true;
            }
            else{
                $ok = false;
                $error = false;
                foreach ($id_responsiblesComm as $id_responsible) {
                    if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true)
                        $error = true;
                }
                if (!$error) {
                    setEventMessages("Un mail à été envoyé à un responsable pour qu'il valide cette commande commercialement.", null, 'warnings');
                }
                else
                        $this->errors[] = 'Envoi d\'email impossible';
            }
        }
        
        
        

     
        
        
       
        
        
        
        
        if(!$ok)
                $this->db->rollback();
        if($updateValFin){
                $this->db->query("UPDATE llx_commande SET validFin = 1 WHERE rowid = ".$order->id);
                setEventMessages('Validation Financiére OK', array(), 'mesgs');
        }
        if($updateValComm){
                $this->db->query("UPDATE llx_commande SET validComm = 1 WHERE rowid = ".$order->id);
                setEventMessages('Validation Commerciale OK', array(), 'mesgs');
        }
        if(!$ok)
                $this->db->commit();
        
        


        if (sizeof($this->errors) > 0){
            setEventMessages(null, $this->errors, 'errors');
            return -5;
        }
        
        if(!$ok)
            return   -1;
        return 1;
    }

    /**
     * Other functions
     */

    /**
     * Get the maximum price a user can validate
     */
    private function getMaxPriceOrder($user, $order) {
        $max_price = 0;
        if ($order->array_options['options_type'] == "P") {
            foreach ($this->tabValideMontantPart as $userId => $tabM)
                if ($userId == $user->id)
                    $max_price = $tabM[1];
        }
        if (in_array($order->array_options['options_type'], $this->tabSecteurEduc)) {
            foreach ($this->tabValideMontantEduc as $userId => $tabM)
                if ($userId == $user->id)
                    $max_price = $tabM[1];
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
                if ($obj->maxpriceorder > $max_price)
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

    private function checkRemise($order, $user) {
        $ok = true;
        if (in_array($order->array_options['options_type'], $this->tabSecteurEduc)) {
            if (!in_array($user->id, $this->tabValideRemise)) {
                foreach ($order->lines as $line)
                    if ($line->remise_percent > 6) {
                        $this->extraMail[] = "Ligne " . $line->desc . " avec un réduction de " . $line->remise_percent . "%";
                        $ok = false;
                    }
            }
        }
        else{
            if (!in_array($user->id, $this->tabValideRemise)) {
                foreach ($order->lines as $line)
                    if ($line->remise_percent > 5) {
                        $this->extraMail[] = "Ligne " . $line->desc . " avec un réduction de " . $line->remise_percent . "%";
                        $ok = false;
                    }
            }
        }
        return $ok;
    }

    private function checkAutorisationFinanciere($user, $order) {
        $price = $order->total_ht;

        $max_price = $this->getMaxPriceOrder($user, $order);
        
        $tabUserOk = array();
        if ($max_price <= $price) {
            if ($order->array_options['options_type'] == "P" && $price < 100000) {//Aurelie
                foreach ($this->tabValideMontantPart as $idUser => $tabMont) {
                    if($price > $tabMont[0] && $price <= $tabMont[1]){
                        $tabUserOk[] = $idUser;
                        if($idUser == $user->id)
                            return array();
                    }
                }
            } elseif (in_array($order->array_options['options_type'], $this->tabSecteurEduc) && $price < 100000) {//Joana
                foreach ($this->tabValideMontantEduc as $idUser => $tabMont) {
                    if($price > $tabMont[0] && $price <= $tabMont[1]){
                        $tabUserOk[] = $idUser;
                        if($idUser == $user->id)
                            return array();
                    }
                }
            } else {
                foreach ($this->tabValideMontant as $idUser => $tabMont) {
                    if($price > $tabMont[0] && $price <= $tabMont[1]){
                        $tabUserOk[] = $idUser;
                        if($idUser == $user->id)
                            return array();
                    }
                }
            }
        }
        return $tabUserOk;
    }
    
    
    
    
    private function checkAutorisationCommmerciale($user, $order) {
        $price = $order->total_ht;
        $tabUserOk = array();
        $okRemise = ($order->array_options['options_type'] != "P" ? $this->checkRemise($order, $user) : 1);
        if (!$okRemise){
            if (in_array($order->array_options['options_type'], $this->tabSecteurEduc)) {
                foreach ($this->tabValideCommEduc as $idUser => $tabMont) {
                    $tabUserOk[] = $idUser;
                    if($idUser == $user->id)//on peut validé
                        return array();
                }
            }
            else{
                foreach ($this->tabValideComm as $idUser => $tabMont) {
                    $tabUserOk[] = $idUser;
                    if($idUser == $user->id)//on peut validé
                        return array();
                }
            }
        }
        return $tabUserOk; 
    }

//    private function getFirstResponsibleId($price) {
//
//        $sql = 'SELECT fk_object';
//        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
//        $sql .= ' WHERE maxpriceorder >=' . $price;
//        $sql .= ' ORDER BY maxpriceorder ASC';
//        $sql .= ' LIMIT 1';
//
//        $result = $this->db->query($sql);
//        if ($result and mysqli_num_rows($result) > 0) {
//            $obj = $this->db->fetch_object($result);
//            $id_responsible = $obj->fk_object;
//        } elseif (!$result) {
//            $this->errors[] = "La requête SQL pour la recherche du responsable.";
//            return -1;
//        }
//
//        return $id_responsible;
//    }

    private function sendEmailToResponsible($id_responsible, $user, $order) {

        $doli_user_responsible = new User($this->db);
        $doli_user_responsible->fetch($id_responsible);

        $subject = "BIMP ERP - Demande de validation de commande client";

        $msg = "Bonjour, \n\n";
        $msg .= "L'utilisateur $user->firstname $user->lastname souhaite que vous validiez la commande suivante : ";
        $msg .= $order->getNomUrl();
        foreach ($this->extraMail as $extra) {
            $msg .= "\n\n" . $extra;
        }
        
        return mailSyn2($subject, $doli_user_responsible->email, $user->email, $msg);
    }

}
