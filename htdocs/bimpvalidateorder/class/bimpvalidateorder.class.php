<?php

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpValidateOrder
{

    private $db;
    public $errors;
    public $validation_errors = array();
//    private $tabValideComm = array(62 => 100, 201 => 100, 7 => 100);
//    private $tabValideCommEduc = array(51 => 100, 201 => 100);
//    private $tabValideMontant = array(81 => array(0, 1000000000000), 68 => array(0000, 100000000000), 284 => array(0000, 100000000000), 285 => array(0000, 100000000000));
//    private $tabValideMontantPart = array(7 => array(0, 100000), 81 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
//    private $tabValideMontantEduc = array(201 => array(0, 100000), 51 => array(0, 100000), 81 => array(100000, 1000000000000), 68 => array(100000, 100000000000));
//    private $tabSecteurEduc = array("E", "ENS", "EBTS");
    private $tabValidation = array(
        "E"    => array(
            "comm" => array(51 => 100, 201 => 100),
            "fi"   => array(201 => array(0, 100000), 51 => array(0, 100000), 68 => array(100000, 100000000000)),
        ),
        "EBTS" => array(
            "comm" => array(51 => 100, 201 => 100),
            "fi"   => array(201 => array(0, 100000), 51 => array(0, 100000), 68 => array(100000, 100000000000)),
        ),
        "ENS"  => array(
            "comm" => array(51 => 100, 201 => 100),
            "fi"   => array(201 => array(0, 100000), 51 => array(0, 100000), 68 => array(100000, 100000000000)),
        ),
        "BP"   => array(
            "comm" => array(7 => 100),
            "fi"   => array(7 => array(0, 10000), 232 => array(10000, 100000000000), 68 => array(100000, 100000000000)),
        ),
        "C"    => array(
            "comm" => array(62 => 100), // Franck Pineri
//            "comm" => array(201 => 100), // Philippe Fonseca
            "fi"   => array(232 => array(0, 10000), 232 => array(9900, 100000000000), 68 => array(100000, 100000000000))
//            "fi"   => array(201 => 100) // Philippe Fonseca
//            "fi"   => array(62 => 100), // Franck Pineri
        ),
        "M"    => array(
            "comm_mini" => 30,
            "fi_mini"   => 6000,
            "comm"      => array(171 => 100, 89 => 100, 283 => 100, 62 => 100),
            "fi"        => array(171 => array(0, 1000000000000), 89 => array(0, 1000000000000), 283 => array(0000, 100000000000), 65 => array(100000, 100000000000)),
        )
    );

    function __construct($db)
    {
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
    public function checkValidateRights($user, $order)
    {

//        if (defined('MOD_DEV') && (int) MOD_DEV) {
//            return 1;
//        }

        $updateValFin = $updateValComm = false;
        $ok = true;
        $id_responsiblesFin = $id_responsiblesComm = array();
        $sql = $this->db->query("SELECT `validFin`, `validComm` FROM `" . MAIN_DB_PREFIX . "commande` WHERE `rowid` = " . $order->id);
        $result = $this->db->fetch_object($sql);

        $tabUserValidAuto = array(68, 65, 232, 7); // Virer le 7
        if (!in_array($user->id, $tabUserValidAuto)) {
            if ($result->validFin < 1) {
                $id_responsiblesFin = $this->checkAutorisationFinanciere($user, $order);
                if (count($id_responsiblesFin) == 0) {
                    $updateValFin = true;
                } else {
                    $ok = false;
                    $error = false;


                    foreach ($id_responsiblesFin as $id_responsible) {
                        if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true)
                            $error = true;
                    }
                    if (!$error) {
                        $this->validation_errors[] = 'Cette commande n\'est pas validée fincancièrement';
                        setEventMessages("Un mail a été envoyé à un responsable pour qu'il valide cette commande financièrement.", null, 'warnings');
                    } else
                        $this->errors[] = '2 Envois d\'email impossibles ' . $id_responsible;
                }
            }

            if ($result->validComm < 1) {
                $id_responsiblesComm = $this->checkAutorisationCommmerciale($user, $order);
                if (count($id_responsiblesComm) == 0) {
                    $updateValComm = true;
                } else {
                    $ok = false;
                    $error2 = false;
                    foreach ($id_responsiblesComm as $id_responsible) {
                        if (!$this->sendEmailToResponsible($id_responsible, $user, $order) == true)
                            $error2 = true;
                    }
                    if (!$error2) {
                        $this->validation_errors[] = 'Cette commande n\'est pas validée commercialement';
                        setEventMessages("Un mail a été envoyé à un responsable pour qu'il valide cette commande commercialement.", null, 'warnings');
                    } else
                        $this->errors[] = '1 Envoi d\'email impossible ' . $id_responsible;
                }
            }
        } else {
            if ($result->validFin < 1) {
                $updateValFin = true;
            }
            if ($result->validComm < 1) {
                $updateValComm = true;
            }
        }


        if ($error || $error2) {
            $this->validation_errors[] = 'Validation non permise';
            setEventMessages("Validation non permise", null, "errors");
        }


        if (!$ok) {
            $this->db->rollback();
            global $conf;
            if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
                $task = BimpObject::getInstance("bimptask", "BIMP_Task");
                $test = "commande:rowid=" . $order->id . " && fk_statut>0";
                $tasks = $task->getList(array('test_ferme' => $test));
                if (count($tasks) == 0) {
                    $tab = array("src" => $user->email, "dst" => "validationcommande@bimp-groupe.net", "subj" => "Validation commande " . $order->ref, "txt" => "Merci de valider la commande " . $order->getNomUrl(1), "test_ferme" => $test);
                    $this->errors = BimpTools::merge_array($this->errors, $task->validateArray($tab));
                    $this->errors = BimpTools::merge_array($this->errors, $task->create());
                }
            }
        }
        if ($updateValFin) {
            $this->db->query("UPDATE " . MAIN_DB_PREFIX . "commande SET validFin = 1 WHERE rowid = " . $order->id);
            setEventMessages('Validation Financiére OK', array(), 'mesgs');
        }
        if ($updateValComm) {
            $this->db->query("UPDATE " . MAIN_DB_PREFIX . "commande SET validComm = 1 WHERE rowid = " . $order->id);
            setEventMessages('Validation Commerciale OK', array(), 'mesgs');
        }

        if (!$ok)
            $this->db->commit();

        if (sizeof($this->errors) > 0) {
            setEventMessages(null, $this->errors, 'errors');
            return -5;
        }

        if (!$ok)
            return -1;

        return 1;
    }
    /**
     * Other functions
     */

    /**
     * Get the maximum price a user can validate
     */
    private function getMaxPriceOrder($user, $order, $tabValidation)
    {
        if ($user->id < 0) {
            $this->errors[] = "Identifiant utilisateur inconnu.";
            return -1;
        }

        $depassementPossible = 0;
        foreach ($tabValidation as $userId => $tabM)
            if ($userId == $user->id)
                $depassementPossible = $tabM[1];


        if (isset($tabValidation['fi_mini']))//Ajout du fi_mini au max_price
            $depassementPossible += $tabValidation['fi_mini'];



        $bimpCommande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $order->id);
        if (BimpObject::objectLoaded($bimpCommande)) {
            $client = $bimpCommande->getClientFacture();
        } else {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $order->socid);
        }


        // Vérif de l'encours client:

        $tmp = $client->dol_object->getOutstandingBills();
        $actuel = $tmp['opened'];
//        if ($this->object_name === 'Bimp_Facture') {
//            $actuel -= $this->dol_object->total_ttc;
//        }
        $necessaire = $order->total_ttc;

        $max = $client->dol_object->outstanding_limit;
        if ($max == 0)
            $max = 4000;

        $max_price = $max - $actuel + $depassementPossible;

        $futur = $actuel + $necessaire - $depassementPossible;


        if ($necessaire > 0 && $max_price < $necessaire) {
            $this->extraMail[] = "Montant encours client dépassé. <br/>Encours autorisé : " . price($max) . "  <br/>Possibilité de dépassement de l'User " . price($depassementPossible) . " €. <br/>Encours actuel :" . price($actuel) . " €. <br/>Encours necessaire : " . price($futur) . " €.";
        }



//        $sql = 'SELECT maxpriceorder';
//        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
//        $sql .= ' WHERE fk_object=' . $user->id;
//
//        $result = $this->db->query($sql);
//        if ($result and mysqli_num_rows($result) > 0) {
//            while ($obj = $this->db->fetch_object($result)) {
//                if ($obj->maxpriceorder > $max_price)
//                    $max_price = $obj->maxpriceorder;
//            }
//        } elseif (!$result) {
//            $this->errors[] = "La requête SQL pour la recherche du prix maximum a échouée.";
//            return -2;
//        }
//        if ($max_price < 0 or $max_price == '') {
//            $this->errors[] = "Prix maximum de validation de commande pour l'utilisateur non définit.";
//            return -3;
//        }

        return $max_price;
    }

    private function checkRemise($order, $user, $maxValid = 5)
    {
        $ko = 0;
        foreach ($order->lines as $line)
            if ($line->remise_percent > $maxValid) {
                $this->extraMail[] = "Ligne " . $line->desc . " avec un réduction de " . $line->remise_percent . "%";
                $ko = $line->remise_percent;
            }
        return $ko;
    }

    private function checkAutorisationFinanciere($user, $order)
    {
        $price = $order->total_ttc;


        if (isset($this->tabValidation[$order->array_options['options_type']]["fi"]))
            $tabValidation = $this->tabValidation[$order->array_options['options_type']];
        else
            $tabValidation = $this->tabValidation["C"];

        $max_price = $this->getMaxPriceOrder($user, $order, $tabValidation);




        $tabUserOk = array();
        if ($max_price <= $price) {
            foreach ($tabValidation["fi"] as $idUser => $tabMont) {
                if ($price > $tabMont[0] && $price <= $tabMont[1]) {
                    $tabUserOk[] = $idUser;
                    if ($idUser == $user->id)
                        return array();
                }
            }
        }
        return $tabUserOk;
    }

    private function checkAutorisationCommmerciale($user, $order)
    {
        $price = $order->total_ht;
        $tabUserOk = array();
        $maxValid = 5;



        if (isset($this->tabValidation[$order->array_options['options_type']]["comm"]))
            $tabValidation = $this->tabValidation[$order->array_options['options_type']];
        else
            $tabValidation = $this->tabValidation["C"];


        if (isset($tabValidation['comm_mini']))
            $maxValid = $tabValidation['comm_mini'];

        $remiseRefu = $this->checkRemise($order, $user, $maxValid);

        if ($remiseRefu > 0) {


            foreach ($tabValidation["comm"] as $idUser => $tabMont) {
                if ($tabMont > $remiseRefu) {
                    $tabUserOk[] = $idUser;
                    if ($idUser == $user->id)//on peut validé
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

    private function sendEmailToResponsible($id_responsible, $user, $order)
    {

        $doli_user_responsible = new User($this->db);
        $doli_user_responsible->fetch($id_responsible);

        $subject = "BIMP ERP - Demande de validation de commande client";



        $msg = "Bonjour, \n\n";
        $msg .= "L'utilisateur $user->firstname $user->lastname souhaite que vous validiez la commande suivante : ";
        $msg .= $order->getNomUrl();
        if ($order->socid > 0) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $order->socid);
            $msg .= ' du client ' . $soc->getNomUrl();
            $subject .= ' du client ' . $soc->getData('code_client') . " : " . $soc->getData('nom');
        }
        foreach ($this->extraMail as $extra) {
            $msg .= "\n\n" . $extra;
        }

        return mailSyn2($subject, $doli_user_responsible->email, $user->email, $msg);
    }
}
