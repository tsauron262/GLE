<?php

class BimpValidation
{

//    Processus de validation : 
//
//    - Récupération de la liste des règles de validation pour un type d’objet et un secteur donnés. 
//    Les règles sont classées par type de validation 
//    - Elles sont triées par val_max croissant pour les valeurs positives et par val_min décroissant 
//    pour les valeurs négatives (de manière à adresser les demandes en priorité aux utilisateurs 
//    associés aux règles les plus restrictives)
//    - Si une règle existe pour un type de validation donné, c’est qu’une validation est nécessaire pour ce type-là. 
//
//    - Si l’utilisateur connecté n’est pas en mesure de valider lui-même, on créé une demande de validation attribué 
//    à tous les utilisateurs associé à la première règle correspondant aux valeurs de l’objet.
//    - Les validations apparaissent sur l’ERP pour chacun de ces users mais un e-mail est envoyé uniquement 
//    au premier user dispo (valideur principal) selon l’ordre dans lequel il sont enregistrés dans la règle de validation. 
//    - Si aucun user dispo, on envoi un mail au premier supérieur hierarchique dispo. 
//    Via cron, à tous les changements de demie-journée on vérifie les dispos des users pour toutes les demandes 
//    en attente et on modifie le valideur principal si nécessaire (avec notif par mail) 
    // Traitements: 

    public static function tryToValidate($object, &$errors = array(), &$infos = array(), &$warnings = array())
    {
        if (defined('DISABLE_BIMP_VALIDATIONS') && DISABLE_BIMP_VALIDATIONS) {
            return 1;
        }

        $object_params = self::getObjectParams($object, $errors);

        $secteur = $object_params['secteur'];
        $object_type = $object_params['type'];

        if (!$object_type) {
            return 1;
        }

        // Vérif de l'existance d'une demande refusée pour cet objet:
        $nb_refused = 0;
        BimpObject::loadClass('bimpvalidation', 'BV_Demande');
        if (BV_Demande::objectHasDemandesRefused($object, $nb_refused)) {
            $errors[] = $nb_refused . ' demande(s) de validation refusée(s)';
            return 0;
        }



        $rules = self::getValidationsRulesByTypes($object_type, $secteur);

        if (empty($rules)) {
            return 1;
        }

        global $user;
        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
            return 0;
        }

        $debug = '';
        $debug .= 'PARAMS OBJET : <pre>';
        $debug .= print_r($object_params, 1);
        $debug .= '</pre><br/>';

        $global_check = 1;
        $demandes = array();
        foreach ($rules as $type => $type_rules) {
            $debug .= ($debug ? '<br/><br/>' : '') . '<b>Validation de type "' . $type . '" :</b> ' . count($type_rules) . ' règle(s) correspondantes.<br/>';
            $type_errors = array();
            $type_check = 0;

            // Vérif de l'existence d'une demande pour ce type de validation: 
            $demande = BimpCache::findBimpObjectInstance('bimpvalidation', 'BV_Demande', array(
                        'type_validation' => $type,
                        'type_object'     => $object_type,
                        'id_object'       => $object->id,
                        'status'          => array(
                            'operator' => '!=',
                            'value'    => -2
                        )
                            ), true);

            if (BimpObject::objectLoaded($demande)) {
                if ((int) $demande->getData('status') > 0) {
                    // La demande est déjà acceptée. 
                    $debug .= 'Demande existante #' . $demande->id . ' déjà acceptée.';
                    continue;
                } else {
                    if ($demande->canProcess()) {
                        $debug .= 'La demande existante #' . $demande->id . ' peut être acceptée par cet utilisateur.';
                        $type_check = 1;
                    } else {
                        $debug .= 'La demande existante #' . $demande->id . ' ne peut pas être acceptée par cet utilisateur.';
                    }
                }
            }

            if (!$type_check) {
                // Récupération des données de l'objet pour ce type de validation: 
                $object_data = self::getObjectData($type, $object, $type_errors, $debug);

                $debug .= '<br/>DONNEES OBJET : <pre>';
                $debug .= print_r($object_data, 1);
                $debug .= '</pre><br/>';

                if (count($type_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($type_errors, 'Validation ' . BV_Rule::$types[$type]['label2']);
                    $global_check = 0;
                    continue;
                }

                $val = $object_data['val'];
                $extra_data = $object_data['extra_data'];

                if (!$val) {
                    $debug .= 'Validation non nécessaire car la valeur est à 0.<br/>';
                    $type_check = 1;
                } else {
                    // Vérif d'un objet lié déjà validé pour ce type de validation:
                    if (self::checkValidatedLinkedObjects($type, $object, $val, $extra_data, $infos)) {
                        $debug .= 'Demande de validation non nécessaire car déjà effectuée pour un objet lié.<br/>';
                        $type_check = 1;
                    } else {
                        $valid_rules = array();

                        // Trie des règles selon leur priorité: 
                        $type_rules = self::sortValidationTypeRules($type_rules, $type, $val, $extra_data);
                        foreach ($type_rules as $rule) {
                            $debug .= 'Règle #' . $rule->id . ' (min : ' . $rule->getData('val_min') . ' - max : ' . $rule->getData('val_max') . ') : ';
                            if (self::checkRule($rule, $object_type, $secteur, $val, $extra_data, $debug)) {
                                $debug .= 'OK.<br/>';
                                $valid_rules[] = $rule;
                                if ($rule->isUserAllowed($user->id)) {
                                    $debug .= 'L\'utilisateur peut valider la règle #' . $rule->id . '<br/>';
                                    $type_check = 1;
                                    break;
                                }
                            } else {
                                $debug .= '.<br/>Règle non valide.<br/>';
                            }
                        }

                        if (empty($valid_rules)) {
                            $debug .= 'Aucune règle valide correspondante<br/>';
                            $type_check = 1;
                        }
                    }
                }
            }

            if ($type_check) {
                if (BimpObject::objectLoaded($demande)) {
                    $demandes[] = $demande;
                    $demande->useNoTransactionsDb();
                    $debug .= 'Acceptation de la demande déjà existante #' . $demande->id . ' : ';
                    // Acceptation directe de la demande :
                    $demande_errors = $demande->setAccepted(false, $warnings);

                    if (count($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de l\'acceptation de la demande pour la validation ' . BV_Rule::$types[$demande->getData('type')]['label2']);
                        $global_check = 0;
                    } else {
                        $debug .= 'OK.<br/>';
                        $infos[] = 'Validation de type "' . BV_Rule::$types[$type]['label'] . '" effectuée';
                    }
                }
            } else {
                $global_check = 0;
                if (BimpObject::objectLoaded($demande)) {
                    // Une demande existe déjà pour ce type et ne peut être acceptée
                    $debug .= 'La demande existante #' . $demande->id . ' ne peut toujours pas être acceptée.';
                    $infos[] = 'Une demande de validation ' . $demande->displayValidationType() . ' a déjà été effectuée et est en attente de traitement';
                } else {
                    // Création d'une demande de validation: 
                    $demande_errors = array();
                    $users = array();

                    foreach ($valid_rules as $rule) {
                        foreach ($rule->getValidationUsers() as $id_user) {
                            if (!in_array($id_user, $users)) {
                                $users[] = $id_user;
                            }
                        }
                    }

                    if (!empty($users)) {
                        $debug .= 'Création d\'une nouvelle demande.<br/>';
                        $debug .= 'Utilisateurs : <pre>' . print_r($users, 1) . '</pre>';

                        $demande_warnings = array();
                        $demande = BimpObject::createBimpObject('bimpvalidation', 'BV_Demande', array(
                                    'type_validation'  => $type,
                                    'type_object'      => $object_type,
                                    'id_object'        => $object->id,
                                    'id_user_demande'  => $user->id,
                                    'validation_users' => $users
                                        ), true, $demande_errors, $demande_warnings, true);
                    } else {
                        $msg = 'Aucun utilisateur n\'a la possibilité d\'effectuer cette validation';
                        $demande_errors[] = $msg;
                        $debug .= $msg . '<br/>';
                    }

                    if (count($demande_errors)) {
                        $debug .= 'Erreurs : <pre>' . print_r($demande_errors, 1) . '</pre>';
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Echec de la création d\'une demande de validation de type "' . $demande->displayDataDefault('type') . '"');
                    } else {
                        $demandes[] = $demande;
                        $debug .= 'OK (Demande #' . $demande->id . ').<br/>';
                    }
                }
            }
        }

        if (!$global_check && !count($errors)) {
            // Check des acceptations auto des demandes: 
            self::checkDemandesAutoAccept($object, $demandes, $errors, $infos, $warnings);

            if (!count($errors)) {
                $where = 'type_object = \'' . $object_type . '\' AND id_object = ' . $object->id;
                $where .= ' AND status <= 0';
                if (!(int) BimpCache::getBdb(true)->getCount('bv_demande', $where)) {
                    // Il ne reste aucune demande de validation à traiter
                    $global_check = 1;
                } else {
                    // Affectation des demandes et notifications:

                    foreach ($demandes as $demande) {
                        if (!(int) $demande->getData('status')) {
                            $type_validation = $demande->getData('type_validation');
                            $demande_errors = $demande->checkAffectedUser();

                            if (count($demande_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($demande_errors, 'Demande de validation ' . BV_Rule::$types[$type_validation]['label2']);
                            } else {
                                $affected_user = $demande->getChildObject('user_affected');
                                if (BimpObject::objectLoaded($affected_user)) {
                                    $infos[] = 'Demande de <b>validation ' . BV_Rule::$types[$type_validation]['label2'] . '</b> effectuée auprès de <b>' . $affected_user->getName() . '</b>';
                                }
                            }
                        }
                    }
                }
            }
        }

        if (BimpDebug::isActive()) {
            BimpDebug::addDebug('validations', 'Validation ' . $object->getLabel('of_the') . ' ' . $object->getRef(), $debug, array(
                'open' => true
            ));
        }

        if ($debug && is_a($object, 'BimpObject')) {
            $object->addObjectLog($debug, 'DEBUG_VALIDATION', true);
        }

        return $global_check;
    }

    public static function checkValidatedLinkedObjects($type_validation, $object, $val, $extra_data, &$infos = array())
    {
        foreach (BimpTools::getDolObjectLinkedObjectsList($object->dol_object) as $item) {
            if (!(int) $item['id_object']) {
                continue;
            }

            $linked_obj = null;
            $is_valid = false;
            switch ($item['type']) {
                case 'propal':
                    $linked_obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                    if (BimpObject::objectLoaded($linked_obj)) {
                        $is_valid = in_array((int) $linked_obj->getData('fk_statut'), array(1, 2, 4));
                    }
                    break;

                case 'commande':
                    $linked_obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                    if (BimpObject::objectLoaded($linked_obj)) {
                        $is_valid = in_array((int) $linked_obj->getData('fk_statut'), array(1, 2, 3));
                    }
                    break;

                case 'facture':
                    $linked_obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);
                    if (BimpObject::objectLoaded($linked_obj)) {
                        $is_valid = in_array((int) $linked_obj->getData('fk_statut'), array(1, 2));
                    }
                    break;
            }

            if (!BimpObject::objectLoaded($linked_obj)) {
                continue;
            }

            $check = 0;
            $linked_data = self::getObjectData($type_validation, $linked_obj);
            $linked_val = BimpTools::getArrayValueFromPath($linked_data, 'val', 0);
            $linked_extra_data = BimpTools::getArrayValueFromPath($linked_data, 'extra_data', 0);

            if (($val >= 0 && $val <= $linked_val) || ($val < 0 && $val >= $linked_val)) {
                $check = 1;
                switch ($type_validation) {
                    case 'comm':
                        // Vérif marge: 
                        $marge = (float) BimpTools::getArrayValueFromPath($extra_data, 'percent_marge', 0);
                        $linked_marge = (float) BimpTools::getArrayValueFromPath($linked_extra_data, 'percent_marge', 0);

                        if (($marge >= 0 && $marge > $linked_marge) || ($marge < 0 && $marge > $linked_marge)) {
                            $check = 0;
                        }
                }
            }

            if ($check) {
                if ($is_valid) {
                    $infos[] = ucfirst($linked_obj->getLabel()) . ' ' . $linked_obj->getRef() . ' déjà entièrement validé' . $linked_obj->e();
                    return 1;
                }

                $demandes = self::getObjectDemandes($linked_obj, array(
                            'operator' => '>',
                            'value'    => 0
                                ), $type_validation);

                if (!empty($demandes)) {
                    $infos[] = 'Validation ' . BV_Rule::$types[$type]['label2'] . ' déjà effectuée pour ' . $linked_obj->getLabel('the') . ' ' . $linked_obj->getRef();
                    return 1;
                }
            }
        }

        return 0;
    }

    public static function sortValidationTypeRules($rules, $validation_type, $val, $extra_data)
    {
        // Trie des règles de validation par ordre de priorité: 

        if (in_array($validation_type, array('comm', 'fin', 'rtp'))) {
            $function = '';
            if ($val >= 0) {
                // On trie selon les val_max croissantes
                $function = 'bv_compareValidationRulesAsc';

                if (!function_exists($function)) {

                    function bv_compareValidationRulesAsc($a, $b)
                    {
                        if (is_a($a, 'BV_Rule') && is_a($b, 'BV_Rule')) {
                            $a_val = (float) $a->getData('val_max');
                            $b_val = (float) $b->getData('val_max');
                            return ($a_val > $b_val ? 1 : ($a_val < $b_val ? -1 : 0));
                        }

                        return 0;
                    }
                }
            } else {
                // On trie selon les val_min décroissantes
                $function = 'bv_compareValidationRulesDesc';

                if (!function_exists($function)) {

                    function bv_compareValidationRulesDesc($a, $b)
                    {
                        if (is_a($a, 'BV_Rule') && is_a($b, 'BV_Rule')) {
                            $a_val = (float) $a->getData('val_min');
                            $b_val = (float) $b->getData('val_min');
                            return ($a_val > $b_val ? -1 : ($a_val < $b_val ? 1 : 0));
                        }

                        return 0;
                    }
                }
            }

            usort($rules, $function);
        }

        return $rules;
    }

    public static function checkRule($rule, $object_type, $secteur, $val, $extra_data, &$debug = '')
    {
        if (!(int) $rule->getData('all_objects') && !in_array($object_type, $rule->getData('objects'))) {
            $debug .= 'Le type d\'objet ne correpond pas (' . $object_type . ')';
            return 0;
        }

        if (!(int) $rule->getData('all_secteurs') && !in_array($secteur, $rule->getData('secteurs'))) {
            $debug .= 'Le secteur ne correspond pas';
            return 0;
        }

        // Vérif Val
        $type = $rule->getData('type');
        $extra_params = $rule->getData('extra_params');
        if (in_array($type, array('comm', 'fin'))) {
            $val_min = (float) $rule->getData('val_min');
            $val_max = (float) $rule->getData('val_max');

            if ($type == 'comm' && (int) BimpTools::getArrayValueFromPath($extra_params, 'sur_marge', 0)) {
                $percent_marge = (float) BimpTools::getArrayValueFromPath($extra_data, 'percent_marge', 0);

                if ($percent_marge < $val_min || $percent_marge > $val_max) {
                    $debug .= 'Le pourcentage sur marge ne correspond pas';
                    return 0;
                }
            } else {
                if ($val < $val_min || $val > $val_max) {
                    $debug .= 'La valeur ne correspond pas';
                    return 0;
                }
            }
        }

        return 1;
    }

    public static function checkDemandesAutoAccept($object, $demandes = null, &$errors = array(), &$infos = array(), &$warnings = array())
    {
        if (is_null($demandes)) {
            $demandes = self::getObjectDemandes($object, 0);
        }

        if (!is_array($demandes) || empty($demandes)) {
            return;
        }

        foreach ($demandes as $demande) {
            $demande->useNoTransactionsDb();

            $type = $demande->getData('type_validation');
            switch ($type) {
                case 'fin':
                    if ($object->field_exists('paiement_comptant') && (int) $object->getData('paiement_comptant')) {
                        $accept_errors = $demande->autoAccept('Paiement comptant', false);

                        if (!count($accept_errors)) {
                            $email = BimpCore::getConf('notif_paiement_comptant_email', null, 'bimpvalidation');
                            if ($email) {
                                global $user;
                                $msg = "Bonjour,<br/><br/> " . ucfirst($object->getLabel('the')) . ' ' . $object->getLink();
                                $msg .= " a été validé" . $object->e() . " financièrement par paiement comptant ou mandat SEPA par ";
                                $msg .= ucfirst($user->firstname) . ' ' . strtoupper($user->lastname);
                                $msg .= "<br/>Merci de vérifier le paiement ultérieurement.";
                                mailSyn2('Validation par paiement comptant ou mandat SEPA - ' . $object->getLabel() . ' ' . $object->getLink(), $email, "", $msg);
                            }

                            $infos[] = 'La demande de validation ' . BV_Rule::$types[$type]['label2'] . ' a été acceptée automatiquement pour le motif : "Paiement comptant"';
                        } else {
                            $errors[] = BimpTools::getMsgFromArray($accept_errors, 'Echec accepation auto pour la validation de type ' . BV_Rule::$types[$type]['label']);
                        }
//                        continue;//todo pourquoi ? break aprés le else
                    } else {
                        if (method_exists($object, 'getClientFacture')) {
                            $client = $object->getClientFacture();
                        } else {
                            $client = $object->getData('client');
                        }

                        if (BimpObject::objectLoaded($client)) {
                            if ($client->field_exists('validation_financiere') && !(int) $client->getData('validation_financiere')) {
                                $accept_errors = $demande->autoAccept('Validation financière non requise pour le client ' . $object->getLabel('of_the'), false);
                                if (!count($accept_errors)) {
                                    $infos[] = 'La demande de validation ' . BV_Rule::$types[$type]['label2'] . ' a été acceptée automatiquement pour le motif : "Validation financière non requise pour le client facturation ' . $object->getLabel('of_the') . '"';
                                }
                            }
                        }
                    }
                    break;

                case 'rtp':
                    if (method_exists($object, 'getClientFacture')) {
                        $client = $object->getClientFacture();
                    } else {
                        $client = $object->getData('client');
                    }

                    if (BimpObject::objectLoaded($client)) {
                        if ($client->field_exists('validation_impaye') && !(int) $client->getData('validation_impaye')) {
                            $accept_errors = $demande->autoAccept('Validation des retards de paiement non requise pour le client ' . $object->getLabel('of_the'), false);
                            if (!count($accept_errors)) {
                                if (isset($infos[$type])) {
                                    unset($infos[$type]);
                                }

                                $infos[] = 'La demande de validation ' . BV_Rule::$types[$type]['label2'] . ' a été acceptée automatiquement pour le motif : "Validation des retards de paiement non requise pour le client facturation ' . $object->getLabel('of_the') . '"';
                            }
                        }
                    }
                    break;
            }
        }
    }

    public static function checkObjectValidations($object, $object_type, &$success = '')
    {
        $errors = array();

        if (!is_a($object, 'BimpObject') || !BimpObject::objectLoaded($object)) {
            $errors[] = 'Objet invalide';
            return $errors;
        }

        $obj_status = $object->getStatus();

        if ($obj_status === '') {
            $errors[] = 'Statut ' . $object->getLabel('of_the') . ' non défini';
            return $errors;
        }

        if ($obj_status != 0) {
            // déjà validé ou abandonné
            return array();
        }

        $all_accepted = true;

        $demandes = self::getObjectDemandes($object);

        $users = array();
        if (!empty($demandes)) {
            foreach ($demandes as $demande) {
                $status = (int) $demande->getData('status');
                if ($status == -2) {
                    continue;
                }

                if (!in_array((int) $demande->getData('id_user_demande'), $users)) {
                    $users[] = (int) $demande->getData('id_user_demande');
                }

                if ($status <= 0) {
                    $all_accepted = false;
                }
            }
        }

        if (!$all_accepted) {
            return array();
        }

        $to = '';

        foreach ($users as $id_user) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
            if (BimpObject::objectLoaded($user)) {
                $email = BimpTools::cleanEmailsStr($user->getData('email'));

                if ($email) {
                    $to .= ($to ? ', ' : '') . $email;
                }
            }
        }

        $validated = false;
        if (method_exists($object, 'actionValidate')) {
            $result = $object->actionValidate(array(), $success);

            if (!empty($result['errors'])) {
                $errors = $result['errors'];
            } else {
                $validated = true;
            }
        }

        if ($to) {
            $subject = BimpTools::ucfirst($object->getLabel('')) . ' ' . $object->getRef() . ($validated ? ' validé' . $object->e() : ' - demandes de validation acceptées');
            $msg = 'Bonjour,<br/><br/>';
            $msg .= 'Toutes les demandes de validation ' . $object->getLabel('of_the') . ' ' . $object->getLink() . ' ont été acceptées.<br/><br/>';

            if ($validated) {
                $msg .= ucfirst($object->getLabel('this')) . ' a été validé' . $object->e() . ' automatiquement';
            } else {
                $msg .= 'Vous devez à présent terminer manuellement la validation de ' . $object->getLabel('this');
            }

            if (mailSyn2($subject, $to, '', $msg)) {
                $object->addObjectLog('Notification de validation complète envoyée à "' . $to . '"');
            } else {
                $object->addObjectLog('Echec de l\'envoi de la notification de validation complète à "' . $to . '"');
            }
        }

        return $errors;
    }

    // Getters:

    public static function getObjectParams($object, &$errors = array())
    {
        $obj_type = '';
        $secteur = '';

        BimpObject::loadClass('bimpvalidation', 'BV_Rule');

        foreach (BV_Rule::$objects_list as $type => $obj_def) {
            if (is_a($object, $obj_def['object_name'])) {
                $obj_type = $type;
                break;
            }
        }

        if ($obj_type) {
            if (is_a($object, 'BimpComm') && $object->field_exists('ef_type')) {
                $secteur = $object->getData('ef_type');
            } elseif (is_a($object, 'BContract_contrat' && $object->field_exists('secteur'))) {
                $secteur = $object->getData('secteur');
            }
        }

        return array(
            'type'    => $obj_type,
            'secteur' => $secteur
        );
    }

    public static function getObjectData($type_validation, $object, &$errors = array(), &$debug = '')
    {
        $val = 0;
        $val_str = '';
        $extra_data = array();

        switch ($type_validation) {
            case 'comm':
                $extra_data['percent_marge'] = 0;
                if (is_a($object, 'BimpComm')) {
                    $remises_infos = $object->getRemisesInfos();
                    $val = (float) $remises_infos['remise_total_percent'];
                    $val_str = BimpTools::displayFloatValue($val, 4) . ' %';

                    $lines = $object->getLines('not_text');
                    $remises_arrieres = $remise_service = $service_total_amount_ht = $service_total_ht_without_remises = 0;
                    foreach ($lines as $line) {
                        $remises_arrieres += (float) $line->getTotalRemisesArrieres(false);
                        $product = $line->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            if ((int) $product->getData('fk_product_type') == 1) {
                                $line_remise_service_info = $line->getRemiseTotalInfos();
                                $service_total_amount_ht += $line_remise_service_info['total_amount_ht'];
                                $service_total_ht_without_remises += $line_remise_service_info['total_ht_without_remises'];
                            }
                        }
                    }

                    // Percent de marge
                    $margin_infos = $object->getMarginInfosArray();
                    $marge_ini = $remises_infos['remise_total_amount_ht'] + $margin_infos['margin_on_products'] + $remises_arrieres;
                    if ($marge_ini == 0) {
                        $percent_marge = 0;
                    } else {
                        $percent_marge = 100 * $remises_infos['remise_total_amount_ht'] / $marge_ini;
                    }

                    if ($percent_marge > 100) {
                        $percent_marge = 100;
                    }

                    $extra_data['percent_marge'] = $percent_marge;
                    $extra_data['marge_ini'] = $marge_ini;
                    $extra_data['remise_total_amount_ht'] = $remises_infos['remise_total_amount_ht'];
                }
                break;

            case 'fin':
                if (is_a($object, 'BimpComm')) {
                    if ($object->field_exists('total_ttc')) {
                        $val = (float) $object->getData('total_ttc');
                    } elseif (method_exists($object, 'getCurrentTotal')) {
                        $val = (float) $object->getCurrentTotal(1);
                    }

                    if ($val > 0) {
                        if (method_exists($object, 'getClientFacture')) {
                            $client = $object->getClientFacture();
                        } else {
                            $client = $object->getData('client');
                        }

                        if (BimpObject::objectLoaded($client)) {
                            $val += (float) $client->getEncours(true, $debug) + $client->getEncoursNonFacture(true, $debug) - ((float) $client->getData('outstanding_limit') * 1.2);
                        }

                        if ($val < 0) {
                            $val = 0;
                        }
                    } else {
                        $val = 0;
                    }

                    $val_str = BimpTools::displayMoneyValue($val, 'EUR');
                }
                break;

            case 'rtp':
                if (method_exists($object, 'getClientFacture')) {
                    $client = $object->getClientFacture();
                } else {
                    $client = $object->getData('client');
                }

                if (BimpObject::objectLoaded($client)) {
                    $val = $client->getTotalUnpayedTolerance(null, 31); //pour limiter la casse, passé a 31 jours de retard
                }

                if ($val < 0) {
                    $val = 0;
                }

                $val_str = BimpTools::displayMoneyValue($val, 'EUR');
                break;
        }

        return array(
            'val'        => $val,
            'val_str'    => $val_str,
            'extra_data' => $extra_data
        );
    }

    public static function getValidationsRules($object_type, $secteur = '')
    {
        $filters = array(
            'active' => 1,
            'or_obj' => array(
                'or' => array(
                    'all_objects' => 1,
                    'objects'     => array(
                        'part_type' => 'middle',
                        'part'      => '[' . $object_type . ']'
                    )
                )
            ),
        );

        if ($secteur) {
            $filters['or_secteur'] = array(
                'or' => array(
                    'all_secteurs' => 1,
                    'secteurs'     => array(
                        'part_type' => 'middle',
                        'part'      => '[' . $secteur . ']'
                    )
                )
            );
        }

        return BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Rule', $filters, 'id', 'desc');
    }

    public static function getValidationsRulesByTypes($object_type, $val = 0, $secteur = '')
    {
        $return = array();
        $rules = self::getValidationsRules($object_type, $val, $secteur);

        foreach ($rules as $rule) {
            if (!isset($return[$rule->getData('type')])) {
                $return[$rule->getData('type')] = array();
            }

            $return[$rule->getData('type')][] = $rule;
        }

        return $return;
    }

    public static function getObjectDemandes($object, $status_filter = null, $type_filter = null)
    {
        $obj_params = BimpValidation::getObjectParams($object);
        $obj_type = BimpTools::getArrayValueFromPath($obj_params, 'type', '');

        if ($obj_type) {
            $filters = array(
                'type_object' => $obj_type,
                'id_object'   => $object->id,
            );

            if (!is_null($status_filter)) {
                $filters['status'] = $status_filter;
            }

            if (!is_null($type_filter)) {
                $filters['type'] = $type_filter;
            }

            return BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Demande', $filters);
        }

        return array();
    }

    // Rendus HTML: 

    public static function renderObjectDemandesList($object)
    {
        if (!BimpObject::objectLoaded($object)) {
            return '';
        }

        $obj_params = BimpValidation::getObjectParams($object);
        $obj_type = BimpTools::getArrayValueFromPath($obj_params, 'type', '');

        if ($obj_type) {
            $demande = BimpObject::getInstance('bimpvalidation', 'BV_Demande');
            $title = 'Demandes de validation ' . $object->getLabel('of_the') . ' ' . $object->getRef();

            $list = new BC_ListTable($demande, 'obj', 1, null, $title);
            $list->addFieldFilterValue('type_object', $obj_type);
            $list->addFieldFilterValue('id_object', (int) $object->id);

            return $list->renderHtml();
        }

        return '';
    }
}
