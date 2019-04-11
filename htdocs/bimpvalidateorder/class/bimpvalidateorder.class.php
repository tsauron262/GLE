<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpValidateOrder {

    private $db;
    public $errors;
    private $tabValideComm = array(62 => 100, 201 => 100);
    private $tabValideCommEduc = array(51 => 100, 201 => 100);
    private $tabValideMontant = array(81 => array(0, 1000000000000), 68 => array(0000, 100000000000));
    private $tabValideMontantPart = array(7 => array(0, 100000), 81 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
    private $tabValideMontantEduc = array(201 => array(0,100000), 51 => array(0, 100000), 81 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
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
        
        if (defined('MOD_DEV') && (int) MOD_DEV) {
            return 1;
        }
        
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
                    setEventMessages("Un mail a été envoyé à un responsable pour qu'il valide cette commande financièrement.", null, 'warnings');
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
                $error2 = false;
                foreach ($id_responsiblesComm as $id_responsible) {
                    if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true)
                        $error2 = true;
                }
                if (!$error2) {
                    setEventMessages("Un mail a été envoyé à un responsable pour qu'il valide cette commande commercialement.", null, 'warnings');
                }
                else
                        $this->errors[] = 'Envoi d\'email impossible';
            }
        }
        
        
        if($error || $error2)
            setEventMessages("Validation non permise", null, "errors");

     
        
        
       
        
        
        
        
        if(!$ok){
                $this->db->rollback();
                global $conf;
                if(isset($conf->global->MAIN_MODULE_BIMPTASK)){
                    $task = BimpObject::getInstance("bimptask", "BIMP_Task");
                    $test = "commande:rowid=".$order->id." && fk_statut>0";
                    $tasks = $task->getList(array('test_ferme' => $test));
                    if(count($tasks) == 0){
                        $tab = array("src"=>$user->email, "dst"=>"validationcommande@bimp.fr", "subj"=>"Validation commande ".$order->ref, "txt"=>"Merci de validé la commande ".$order->getNomUrl(1), "test_ferme"=>$test);
                        $this->errors = array_merge($this->errors, $task->validateArray($tab));
                        $this->errors = array_merge($this->errors, $task->create());
                    }
                }
        }
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
        
        $contacts = $order->liste_contact(-1, 'internal', 0, 'SALESREPFOLL');
        foreach($contacts as $contact)
                mailSyn2("Commande Validée", $contact['email'], "gle@bimp.fr", "Bonjour, vottre commande ".$order->getNomUrl(1). " est validée.");
        
        
        
        foreach($order->lines as $line){
            if(stripos($line->ref, "REMISECRT") !== false){
                $order->array_options['options_crt'] = 2;
                $order->updateExtraField('crt');
            }
            if(stripos($line->desc, "Applecare") !== false){
                $order->array_options['options_apple_care'] = 2;
                $order->updateExtraField('apple_care');
            }
        }
        

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
