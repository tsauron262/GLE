<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpvalidateorder/objects/DemandeValidComm.class.php';

class ValidComm extends BimpObject
{

    // User
    const USER_ALL = -1;
    const USER_SUP = -2;
    // Type
    const TYPE_ENCOURS = 0;
    const TYPE_COMMERCIAL = 1;
    const TYPE_IMPAYE = 2;

    public static $types = Array(
        self::TYPE_ENCOURS    => Array('label' => 'Encours', 'icon' => 'fas_search-dollar'),
        self::TYPE_COMMERCIAL => Array('label' => 'Commerciale', 'icon' => 'fas_hand-holding-usd'),
        self::TYPE_IMPAYE     => Array('label' => 'Impayé', 'icon' => 'fas_dollar-sign')
    );

    // Piece
    const OBJ_ALL = -1;
    const OBJ_DEVIS = 0;
    const OBJ_FACTURE = 1;
    const OBJ_COMMANDE = 2;
    const OBJ_CONTRAT = 3;
//    public static $objets = Array(
//        self::OBJ_ALL      => Array('label' => 'Tous'),
//        self::OBJ_DEVIS    => Array('label' => 'Devis',    'icon' => 'fas_file-invoice'),
//        self::OBJ_FACTURE  => Array('label' => 'Facture',  'icon' => 'fas_file-invoice-dollar'),
//        self::OBJ_COMMANDE => Array('label' => 'Commande', 'icon' => 'fas_dolly'),
//        self::OBJ_CONTRAT  => Array('label' => 'Contrat', 'icon' => 'retweet')
//    );
    // Only child
    const USER_ASK_ALL = 0;
    const USER_ASK_CHILD = 1;

    private $valideur = array();
    private $isContrat = false;

    public function canEdit()
    {
        global $user;
        if ($user->admin)
            return 1;
        $right = 'validationcommande@bimp-groupe.net';
        return $user->rights->bimptask->$right->write;
    }

    public function getUnity()
    {

        if ((int) $this->getData('type') === self::TYPE_ENCOURS)
            return 'float';

        return 'float';
        return 'percent';
    }

    public function getUserArray()
    {

        return array(
            self::USER_ALL => 'Tout le monde',
            self::USER_SUP => 'Supérieur hiérarchique') + BimpCache::getUsersArray();
    }

    private function checkMinMax()
    {

        $errors = array();

        if ($this->getData('val_max') < $this->getData('val_min'))
            $errors[] = "Valeur min supérieur à valeur max.";

        return $errors;
    }

    private function checkSurMarge()
    {

        $errors = array();

        if ($this->getData('sur_marge') == 1 and $this->getData('type') != self::TYPE_COMMERCIAL)
            $errors[] = "Les validations sur la marge ne peuvent être effectuées que sur le type \"commercial\".";

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {

        $errors = $this->checkMinMax();
        $errors = $this->checkSurMarge();

        if (empty($errors))
            $errors = parent::create($warnings, $force_create);

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {

        $errors = $this->checkMinMax();
        $errors = $this->checkSurMarge();

        if (empty($errors))
            $errors = parent::update($warnings, $force_update);
        return $errors;
    }

    /**
     * Tente de valider un objet BimpComm
     * Si l'utilisateur ne peut pas, contacte quelqu'un de disponible
     * pour valider cet objet
     */
    public function tryToValidate($bimp_object, $user, &$errors, &$success, $validations = array())
    {
        if (defined('NO_VALID_COMM') && NO_VALID_COMM) {
            return 1;
        }

        $valid_comm = 1;
        $valid_encours = 1;
        $valid_impaye = 1;

        // Si on ne précise pas le type de validations a effectué => on les fait toutes
        if (empty($validations))
            $validations = array_keys(self::$types);

        $this->isContrat = ($bimp_object->object_name == 'BContract_contrat') ? true : false;

        // Object non géré
        if ($this->getObjectClass($bimp_object) == -2)
            return 1;

        if (method_exists($bimp_object, 'getClientFacture'))
            $client = $bimp_object->getClientFacture();
        else {
            if (!is_a($object, 'BContract_contrat'))
                $client = $bimp_object->getChildObject('client');
            else
                $bimp_object->getData('fk_soc');
        }


        global $conf;
        $this->db2 = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $this->db->db->database_pass, $conf->db->name, $conf->db->port);

        // Création contact
        /* TODO Pourquoi ici ? TODO */
        $bimp_object->dol_object->db = $this->db2;
        $errors = BimpTools::merge_array($errors, $bimp_object->checkContacts());
        $bimp_object->dol_object->db = $this->db->db;

        list($secteur, $class, $percent_pv, $percent_marge, $val_euros, $rtp) = $this->getObjectParams($bimp_object, $errors);

        if (!empty($errors))
            return 0;


        // Validation commerciale
        if (!is_a($object, 'BContract_contrat')) {
            if (($percent_pv != 0 or $percent_marge != 0) and in_array(self::TYPE_COMMERCIAL, $validations)) {
                $valid_comm = (int) $this->tryValidateByType($user, self::TYPE_COMMERCIAL, $secteur, $class, $percent_pv, $bimp_object, $errors, array('sur_marge' => $percent_marge));
            } elseif (is_a($this->demandeExists($class, (int) $bimp_object->id, self::TYPE_COMMERCIAL), 'DemandeValidComm')) {
                $this->updateDemande($user->id, $class, $bimp_object->id, self::TYPE_COMMERCIAL, DemandeValidComm::STATUS_VALIDATED, DemandeValidComm::NO_VAL_COMM);
            }
        }

        // Validation encours
        if ($val_euros != 0 && $this->getObjectClass($bimp_object) != self::OBJ_DEVIS and in_array(self::TYPE_ENCOURS, $validations)) {

            if ($bimp_object->field_exists('paiement_comptant') and $bimp_object->getData('paiement_comptant')) {
                $success[] = "Validation encours forcée par le champ \"Paiement comptant\".";
                $valid_encours = 1;
            } else {

                if (!$client->getData('validation_financiere')) {
                    $success[] = "Validation encours forcée par le champ \"Validation encours\".";
                    $valid_encours = 1;
                } else
                    $valid_encours = (int) $this->tryValidateByType($user, self::TYPE_ENCOURS, $secteur, $class, $val_euros, $bimp_object, $errors);
            }
        }

        // Validation impayé
        if ($rtp != 0 && $this->getObjectClass($bimp_object) != self::OBJ_DEVIS and in_array(self::TYPE_IMPAYE, $validations)) {

            if (!$client) {
                if (method_exists($bimp_object, 'getClientFacture'))
                    $client = $bimp_object->getClientFacture();
                else
                    $client = $bimp_object->getChildObject('client');
            }

            if (!$client->getData('validation_impaye')) {
                $success[] = "Validation des retards de paiement forcée par le champ \"Validation impayé\".";
                $valid_impaye = 1;
            } else
                $valid_impaye = (int) $this->tryValidateByType($user, self::TYPE_IMPAYE, $secteur, $class, $rtp, $bimp_object, $errors);
        } elseif (!$rtp)
            $this->validatePayed($class, $bimp_object);


        // Ajout des erreurs/success
        // Commerciales
        if (!$valid_comm)
            $errors[] = "Vous ne pouvez pas valider commercialement "
                    . $bimp_object->getLabel('this') . '. La demande de validation commerciale a été adressée à ' . $this->valideur[self::TYPE_COMMERCIAL] . '.<br/>';
        elseif (in_array(self::TYPE_COMMERCIAL, $validations))
            $success[] = "Validation commerciale effectuée.";

        // Encours
        if (!$valid_encours)
            $errors[] = $this->getErrorEncours($user, $bimp_object);
        elseif (in_array(self::TYPE_ENCOURS, $validations))
            $success[] = "Validation encours effectuée.";

        // Impayé
        if (!$valid_impaye)
            $errors[] = "Votre " . $bimp_object->getLabel() .
                    " n'est pas encore validée car le compte client présente des retards de paiement " .
                    '. La demande de validation d\'impayé a été adressée à ' . $this->valideur[self::TYPE_IMPAYE] . '.<br/>';
        elseif (in_array(self::TYPE_IMPAYE, $validations))
            $success[] = "Validation d'impayé effectuée.";

        if (!is_a($object, 'BContract_contrat'))
            $ret = ($valid_comm == 1 and $valid_encours == 1 and $valid_impaye == 1);
        else
            $ret = ($valid_encours == 1 && $valid_impaye == 1);


        // Mail si il y a eu au moins une demande de validation traitée
        if ($this->nb_validation > 0)
            $this->sendMailValidation($bimp_object);

        return $ret;
    }

    public function tryValidateByType($user, $type, $secteur, $class, $val, $bimp_object, &$errors, $options = array())
    {
        global $conf;
        if (!isset($conf->global->MAIN_MODULE_BIMPVALIDATEORDER)) // Utiliser bimpcore_conf pour les vars de conf des modules BIMP !! 
            return 1;

        $demande = $this->demandeExists($class, (int) $bimp_object->id, $type);

        if (is_a($demande, 'DemandeValidComm')) {

            if ((int) $demande->getData('status') == (int) DemandeValidComm::STATUS_VALIDATED)
                return 1;
            else {
                $user_aff = BimpCache::getBimpObjectInstance("bimpcore", 'Bimp_User', $demande->getData('id_user_affected'));
                $this->valideur[$type] = ucfirst($user_aff->getData('firstname')) . ' ' . ucfirst($user_aff->getData('lastname'));
            }

            // Je peux valider 
            if ($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val, $bimp_object, $val_comm_validation, $options)) {
                $this->updateDemande((int) $user->id, $class, (int) $bimp_object->id, $type, (int) DemandeValidComm::STATUS_VALIDATED, $val_comm_validation);
                return 1;
            }

            // Pas de demande existante
        } else {

            // Dépendant d'un autre object déja validé/fermé (avec même montant ou remise)
            if ($this->linkedWithValidateObject($bimp_object, $type, $val, $errors)) {
                return 1;
            } elseif ($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val, $bimp_object, $val_comm_validation, $options))
                return 1;

            else {
                $this->createDemande($user, $bimp_object, $type, $class, $val, $secteur, $errors, $options);
                return 0;
            }
        }

        return 0;
    }

    public function userCanValidate($id_user, $secteur, $type, $object, $val, $bimp_object, &$valid_comm = 0, $options = array())
    {

        if ($type == self::TYPE_ENCOURS) {
            $depassement_actuel = $this->getEncours($bimp_object);
            $val_max = $val + $depassement_actuel;

            // Dans le cas des avoir on utilise comme valeur max dans les filtre la valeur de l'objet
            if ($val < 0)
                $val_max = $val;

            elseif ($val_max < 0) {
                $valid_comm = -2;
                return 1;
            }
        }

        $user_groups = array($id_user, self::USER_ALL);
        if ($this->isSupHierarchique($id_user))
            $user_groups[] = self::USER_SUP;

        $v = (isset($val_max)) ? $val_max : $val;

        $filters = array(
            'user'          => array(
                'in' => $user_groups
            ),
            'secteur'       => array(
                'in' => array($secteur, 'ALL')
            ),
            'type'          => $type,
            'type_de_piece' => array(
                'in' => array($object, self::OBJ_ALL)
            )
        );

        $filter_pv = '(sur_marge = 0 AND val_max >= ' . $v . ')';

        if (!isset($options['sur_marge'])) {
            $filters['sur_marge'] = array('custom' => $filter_pv);
        } else {
            $filters['sur_marge'] = array('custom' => '(' . $filter_pv . ' OR (' .
                'sur_marge = 1 AND val_max >= ' . $options['sur_marge'] . '))');
        }

        $valid_comms = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'ValidComm', $filters);

        foreach ($valid_comms as $vc) {
            $valid_comm = $vc->id;
            return 1;
        }

        return 0;
    }

    private function getEncours($bimp_object)
    {

        if (method_exists($bimp_object, 'getClientFacture'))
            $client = $bimp_object->getClientFacture();
        else
            $client = $bimp_object->getChildObject('client');
        $max = $client->getData('outstanding_limit') * 1.2;

        $actuel = $client->getEncours();
        $actuel += $client->getEncoursNonFacture();

        return $actuel - $max;
    }

    public function getTypePieceArray()
    {
        return DemandeValidComm::$objets;
    }

    private function getErrorEncours($user, $bimp_object)
    {
        $id_user = (int) $user->id;
        list($secteur, $class, $percent_pv, $percent_marge, $montant_piece, $rtp) = $this->getObjectParams($bimp_object, $errors);
        $error = '';

        if (!empty($errors))
            $error .= print_r($errors);

        $depassement_actuel = $this->getEncours($bimp_object);
        $depassement_futur = $montant_piece + $depassement_actuel;

        $user_groups = array($id_user, self::USER_ALL);
        if ($this->isSupHierarchique($id_user))
            $user_groups[] = self::USER_SUP;


        $filters = array(
            'user'          => array(
                'in' => $user_groups
            ),
            'secteur'       => array(
                'in' => array($secteur, 'ALL')
            ),
            'type'          => self::TYPE_ENCOURS,
            'type_de_piece' => array(
                'in' => array($class, self::OBJ_ALL)
            )
        );

        $maxUser = 0;
        $valid_comms = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'ValidComm', $filters, 'val_max', 'DESC');
        foreach ($valid_comms as $vc) {
            $maxUser = $vc->getData('val_max');
        }


        $error .= 'Votre validation max ' . price($maxUser) . '€<br/>';
        $error .= 'Dépassement de l\'encours du client ' . price($depassement_actuel) . '€<br/>';
        $error .= 'Montant ' . $bimp_object->getLabel('the') . ' ' . price($montant_piece) . '€<br/>';
        $error .= 'Dépassement après la validation ' . price($depassement_futur) . '€<br/>';
        $error .= 'La demande de validation d\'encours a été adressée à ' . $this->valideur[self::TYPE_ENCOURS] . '.<br/>';

        return $error;
    }

    private function isSupHierarchique($id_user)
    {

        $filters = array(
            'fk_user' => $id_user
        );

        $sql = BimpTools::getSqlSelect(array('rowid'));
        $sql .= BimpTools::getSqlFrom('user');
        $sql .= BimpTools::getSqlWhere($filters);
        $rows = self::getBdb()->executeS($sql, 'array');

        if (is_array($rows) and count($rows)) {
            return 1;
        }


        return 0;
    }

    public function userIsAvaible($id_user)
    {

        $errors = array();
        $ok_user = Bimp_User::isUserAvaible($id_user, $errors);

        // L'utilisateur n'a pas une liste de demande de validation trop longue ?
        BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm');

        $filters = array(
            'id_user_affected' => (int) $id_user,
            'status'           => DemandeValidComm::STATUS_PROCESSING
        );

        $l_demande = BimpCache::getBimpObjectList('bimpvalidateorder', 'DemandeValidComm', $filters);
        $ok_list = (int) sizeof($l_demande) < DemandeValidComm::LIMIT_DEMANDE;

        return $ok_user and $ok_list;
    }

    public function getObjectParams($object, &$errors = array(), $withRtp = true)
    {

        // Secteur
        $secteur = (!is_a($object, 'BContract_contrat')) ? $object->getData('ef_type') : $object->getData('secteur');

        // Piece
        $class = self::getObjectClass($object);

        // Valeur €
        if (!is_a($object, 'BContract_contrat')) {
            if ((int) $object->getData('total_ht') > 0)
                $val = (float) $object->getData('total_ht');
            else
                $val = (float) $object->getData('total');
        } else {
            $val = (float) $object->getCurrentTotal();
        }


        $infos_remises = (!is_a($object, 'BContract_contrat')) ? $object->getRemisesInfos() : [];

        // Percent prix de vente %
        $percent_pv = (float) $infos_remises['remise_total_percent'];

        // CRT
        if (!is_a($object, 'BContract_contrat')) {
            $lines = $object->getLines('not_text');
            $remises_crt = 0;
            foreach ($lines as $line) {
                $remises_crt += (float) $line->getRemiseCRT() * (float) $line->qty;
            }

            // Percent de marge
            $margin_infos = $object->getMarginInfosArray();
            $marge_ini = $infos_remises['remise_total_amount_ht'] + $margin_infos['margin_on_products'] + $remises_crt;
            if ($infos_remises['remise_total_amount_ht'] == 0)
                $percent_marge = 0;
            else
                $percent_marge = 100 * $infos_remises['remise_total_amount_ht'] / $marge_ini;
            if ($percent_marge > 100)
                $percent_marge = 100;
        }

        // Impayé
        if (method_exists($object, 'getClientFacture')) {
            $client = $object->getClientFacture();
        } else
            $client = $object->getChildObject('client');

        if (is_null($client)) {
            $errors[] = "Le client de cette pièce a mal été chargé, merci de réitéré la requête";
            return;
        }

        if ($withRtp) {
            if (isset($this->client_rtp))
                $rtp = $this->client_rtp;
            else
                $rtp = $client->getTotalUnpayedTolerance();
        }
        if ($rtp < 0)
            $rtp = 0;
        //die(print_r(array($secteur, $class, $percent_pv, $percent_marge, $val, $rtp)));
        return array($secteur, $class, $percent_pv, $percent_marge, $val, $rtp);
    }

    public static function getObjectClass($object)
    {

        if(is_a($object, 'Bimp_Propal'))
            return self::OBJ_DEVIS;
        elseif(is_a($object, 'Bimp_Commande'))
            return self::OBJ_COMMANDE;
        elseif(is_a($object, 'BContract_contrat'))
            return self::OBJ_CONTRAT;
//        elseif(is_a($object, 'Bimp_Facture'))
//            return self::OBJ_FACTURE;

        return -2;
    }

    public function createDemande($user_ask, $bimp_object, $type, $object, $val, $secteur, &$errors, $options = array())
    {

        $d = $this->demandeExists($object, (int) $bimp_object->id, $type);

        // Déjà créer
        if ($d)
            return 2;

        $id_user_affected = $this->findValidator($type, $val, $secteur, $object, $user_ask, $bimp_object, $val_comm_demande, $options);

        // Personne ne peut valider
        if (!$id_user_affected) {

            $type_nom = lcfirst(self::$types[$type]['label']);

            switch ($type) {
                case self::TYPE_COMMERCIAL:
                    $val_nom = 'remise de ' . $val . '%';
                    $type_nom = 'commercialement';
                    break;
                case self::TYPE_ENCOURS:
                    $val_nom = 'montant HT de ' . $val . '€';
                    $type_nom = 'financièrement';
                    break;
                case self::TYPE_IMPAYE:
                    $val_nom = 'montant HT de ' . $val . '€';
                    $type_nom = 'les impayés de ';
                    break;
            }
            $secteur_nom = BimpCache::getSecteursArray()[$secteur];

            $message = 'Aucun utilisateur ne peut valider ' . $type_nom
                    . ' ' . $bimp_object->getLabel('the') . ' (pour le secteur ' . $secteur_nom
                    . ', ' . $val_nom . ', utilisateur ' . $user_ask->firstname . ' ' . $user_ask->lastname . ')';

            $errors[] = $message . ". L'équipe de débug est informée et va nommer un chargé de validation.";

            $lien = DOL_MAIN_URL_ROOT . '/' . $this->module;
            $message_mail = "Bonjour,<br/>" . $message;
            $message_mail .= "<br/>Liens de l'objet " . $bimp_object->getNomUrl();
            $message_mail .= "<br/><a href='$lien'>Module de validation</a>";
            $message_mail .= "<br/>Demandeur : " . $user_ask->firstname . ' ' . $user_ask->lastname;

            if ($val_comm_demande != 0)
                $message_mail .= "Debug: pourtant la règle de validation $val_comm_demande ";

            mailSyn2("Droits validation commerciale requis", "debugerp@bimp.fr", null, $message_mail);
            return 0;
        }

        if ((int) $bimp_object->id > 0) {

            $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
//            $demande->db->db = $this->db2;
            $errors = BimpTools::merge_array($errors, $demande->validateArray(array(
                                'type_de_piece'    => (int) $object,
                                'id_piece'         => (int) $bimp_object->id,
                                'id_user_ask'      => (int) $user_ask->id,
                                'id_user_affected' => (int) $id_user_affected,
                                'val_comm_demande' => (int) $val_comm_demande,
                                'type'             => (int) $type
            )));

            $user_aff = BimpCache::getBimpObjectInstance("bimpcore", 'Bimp_User', $id_user_affected);
            $this->valideur[$type] = ucfirst($user_aff->getData('firstname')) . ' ' . ucfirst($user_aff->getData('lastname'));

            $errors = BimpTools::merge_array($errors, $demande->create());
            return 1;
        } else {
            $errors[] = "ID " . $bimp_object->getLabel('of_the') . " non valide.";
        }

        return 0;
    }

    /**
     * Trouve le premier valideur disponible
     */
    private function findValidator($type, $val, $secteur, $object, $user_ask, $bimp_object, &$val_comm_demande = 0, $options = array())
    {

        if ($type == self::TYPE_ENCOURS)
            $val += $this->getEncours($bimp_object);

        $can_valid_not_avaible = 0;
        $can_valid_avaible = 0;

        $filters = array(
            'secteur'       => array(
                'in' => array($secteur, 'ALL')
            ),
            'type'          => $type,
            'type_de_piece' => array(
                'in' => array($object, self::OBJ_ALL)
            ),
        );
        $jourSemaine = BimpTools::getDayOfTwoWeeks();

        $filter_pv = '(sur_marge = 0 AND val_min <= ' . $val . ' AND ' . $val . ' <= val_max)';

        if (!isset($options['sur_marge'])) {
            $filters['sur_marge'] = array('custom' => $filter_pv);
        } else {
            $filters['sur_marge'] = array('custom' => '(' . $filter_pv . ' OR (' .
                'sur_marge = 1 AND val_min <= ' . $options['sur_marge'] . ' AND ' . $options['sur_marge'] . ' <= val_max))');
        }

        $sql = BimpTools::getSqlSelect(array('id', 'user', 'val_max'));
        $sql .= BimpTools::getSqlFrom($this->getTable(),
                                      array('user' => array(
                                'table' => 'user',
                                'on'    => 'a.user = user.rowid',
                                'alias' => 'user')
        ));
        $filters['user.day_off'] = array('part_type' => 'middle', 'part' => '[' . $jourSemaine . ']', 'not' => 1);

        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= ' AND (only_child=' . self::USER_ASK_ALL;
        if ($user_ask->fk_user > 0)
            $sql .= ' OR (only_child=' . self::USER_ASK_CHILD . ' AND user=' . $user_ask->fk_user . ')';
        $sql .= ')';
        $sql .= ' ORDER BY sur_marge DESC, val_min ASC, val_max ASC';
//        $sql .= BimpTools::getSqlOrderBy('date_create', 'DESC');
//        echo($sql);die;
        $rows = self::getBdb()->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if ((int) $r['user'] == $user_ask->fk_user) {
                    if ($this->userIsAvaible($user_ask->fk_user)) {
                        $can_valid_avaible = $user_ask->fk_user;
                        $val_comm_demande = $r['id'];
                    } else {
                        $can_valid_not_avaible = $user_ask->fk_user;
                        $val_comm_demande_not_avaible = $r['id'];
                    }
                } elseif ($can_valid_avaible == 0 and $this->userIsAvaible($r['user'])) {
                    $can_valid_avaible = $r['user'];
                    $val_comm_demande = $r['id'];
                } elseif ($can_valid_not_avaible == 0) {
                    $can_valid_not_avaible = $r['user'];
                    $val_comm_demande_not_avaible = $r['id'];
                }
            }
        }


        if ($can_valid_avaible != 0)
            return $can_valid_avaible;

        $val_comm_demande = $val_comm_demande_not_avaible;
        return $can_valid_not_avaible;
    }

    public function demandeExists($class, $id_object, $type = null, $status = null, $return_all = false)
    {

        $filters = array(
            'type_de_piece' => $class,
            'id_piece'      => $id_object
        );

        if ($type !== null)
            $filters['type'] = $type;

        if ($status !== null)
            $filters['status'] = $status;

        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

//        if($type == self::TYPE_IMPAYE)
//            die(BimpObject::getBdb()->db->lastquery);

        if (!$return_all) {
            foreach ($demandes as $key => $val)
                return $demandes[$key];
        } elseif (count($demandes))
            return $demandes;

        return 0;
    }

    public function updateDemande($id_user, $class, $id_object, $type, $status, $val_comm_validation = 0)
    {

        $filters = array(
            'type_de_piece' => $class,
            'id_piece'      => $id_object,
            'type'          => $type
        );

        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);
        foreach ($demandes as $d) {
//            $d->db->db = $this->db2;
            $now = date('Y-m-d H:i:s');
            $d->updateField('id_user_valid', $id_user);
            $d->updateField('date_valid', $now);
            $d->updateField('status', $status);
            $d->updateField('val_comm_validation', $val_comm_validation);
            $this->nb_validation++;
            return 1;
        }

        return 0;
    }

    public function linkedWithValidateObject($current_bimp_object, $current_type, $current_val, &$errors)
    {

        foreach (BimpTools::getDolObjectLinkedObjectsList($current_bimp_object->dol_object, $this->db) as $item) {

            if (0 < (int) $item['id_object'] and $item['type'] == 'propal') {
                $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);

                list($secteur, $class, $percent_pv, $percent_marge, $val_euros, $rtp) = $this->getObjectParams($propal, $errors);

                if (count($errors))
                    return 0;

                /* if((int) $current_type == self::TYPE_ENCOURS and $current_val <= $val_euros and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
                  return 1;
                  else */if ((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent_pv and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
                    return 1;
            } elseif (0 < (int) $item['id_object'] and $item['type'] == 'facture') {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);

                list($secteur, $class, $percent_pv, $percent_marge, $val_euros, $rtp) = $this->getObjectParams($facture, $errors);

                if (count($errors))
                    return 0;

                if ((int) $current_type == self::TYPE_ENCOURS and $current_val <= $val_euros and in_array((int) $facture->getData('fk_statut'), array(1, 2)))
                    return 1;
                elseif ((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent_pv and in_array((int) $facture->getData('fk_statut'), array(1, 2)))
                    return 1;
            } elseif (0 < (int) $item['id_object'] and $item['type'] == 'commande') {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);

                list($secteur, $class, $percent_pv, $percent_marge, $val_euros, $rtp) = $this->getObjectParams($commande, $errors);

                if (count($errors))
                    return 0;

                if ((int) $current_type == self::TYPE_ENCOURS and $current_val <= $val_euros and in_array((int) $commande->getData('fk_statut'), array(1, 3)))
                    return 1;
                elseif ((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent_pv and in_array((int) $commande->getData('fk_statut'), array(1, 3)))
                    return 1;
            }
        }

        return 0;
    }

    public function validatePayed($class, $bimp_object)
    {
        $demande = $this->demandeExists($class, $bimp_object->id, self::TYPE_IMPAYE);

        if ($demande) {
            $this->updateDemande($demande->getData('id_user_ask'), $class, $bimp_object->id,
                                                   self::TYPE_IMPAYE, DemandeValidComm::STATUS_VALIDATED, -1);
            return 1;
        }

        return 0;
    }

    public function sendMailValidation($bimp_object)
    {

        $filters = array(
            'type_de_piece' => $this->getObjectClass($bimp_object),
            'id_piece'      => $bimp_object->getData('id'),
            'status'        => 1,
        );

        // Client
        if (method_exists($bimp_object, 'getClientFacture'))
            $client = $bimp_object->getClientFacture();
        else
            $client = $bimp_object->getChildObject('client');

        $demandes_valider = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

        // Validé
        if (!empty($demandes_valider)) {

            foreach ($demandes_valider as $d) {

                if ($m == '') {

                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $d->getData('id_user_ask'));
                    $client = $bimp_object->getChildObject('client');
//                    $m .= "Bonjour " . $user->getData('firstname') . ",<br/><br/>";
                    $m .= "Liste des demandes validées pour " . $bimp_object->getNomUrl() . " : du client " . $client->getLink() . "<br/>";
                }

                switch ($d->getData('type')) {
                    case self::TYPE_ENCOURS:
                        $m .= "- validation d'encours<br/>";
                        break;
                    case self::TYPE_COMMERCIAL:
                        $m .= "- validation commerciale<br/>";
                        break;
                    case self::TYPE_IMPAYE:
                        $m .= "- validation des retards de paiement du client<br/>";
                        break;
                }
            }
        } else
            return 0;

        // En cours
        $filters['status'] = 0;
        $demandes_en_cours = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

        if (!empty($demandes_en_cours)) {
            $m .= "<br/>Liste des demandes en attente de validation:<br/>";

            foreach ($demandes_en_cours as $d) {

                switch ($d->getData('type')) {
                    case self::TYPE_ENCOURS:
                        $m .= "- validation d'encours<br/>";
                        break;
                    case self::TYPE_COMMERCIAL:
                        $m .= "- validation commerciale<br/>";
                        break;
                    case self::TYPE_IMPAYE:
                        $m .= "- validation des retards de paiement du client<br/>";
                        break;
                }
            }
        } else {
            if (is_a($bimp_object, 'BContract_contrat'))
                $m .= '<br /> L\'encours sur ce client a été accordé ou révisé, le contrat est passé au statut "en attente de validation"';
            else
                $m .= '<br/>' . ucfirst($bimp_object->getLabel('the')) . " est maintenant validé" . ($bimp_object->isLabelFemale() ? 'e' : '');
        }

        $subject = "Validation " . count($demandes_valider) . '/' . (count($demandes_en_cours) + count($demandes_valider)) . ' ';
        $subject .= $bimp_object->getRef() . ' - ' . $client->getData('code_client') . ' - ' . $client->getData('nom');
        ;

        mailSyn2($subject, $user->getData('email'), null, $m);
        return 1;
    }

    private function updateCreditSafe($bimp_object)
    {

        $errors = array();

        // Client
        if (method_exists($bimp_object, 'getClientFacture'))
            $client = $bimp_object->getClientFacture();
        else
            $client = $bimp_object->getChildObject('client');

        // Non solvable
        if ($client->getData('solvabilite_status') == Bimp_Societe::SOLV_INSOLVABLE) {
            $errors[] = "Client insolvable";
            return $errors;
        }

        // Créer après le 1er mai 2021
        if ('2021-05-1' < $client->getData('datec'))
            return $errors;

        // Avec retard de paiement
        if (isset($this->client_rtp))
            $rtp = $this->client_rtp;
        else
            $rtp = $client->getTotalUnpayedTolerance();

        if ($rtp != 0)
            return $errors;

        // Les 3 conditions sont satifaites, update limite

        if ($client->field_exists('date_check_credit_safe')) {

            if (strtotime('-30 days') < strtotime($client->getData('date_check_credit_safe')))
                return $errors;
        }

        // data Crédit Safe
//        if($client->isSirenRequired()) {
//            $client->useNoTransactionsDb();
//            $errors = BimpTools::merge_array($errors, $client->majEncourscreditSafe(true));
//            $client->useTransactionsDb();
//        }

        return $errors;
    }
}

class DoliValidComm extends CommonObject
{

    const LIMIT_OBJECT = 3;
    const LIMIT_DAYS = 1;

    /**
     *  Constructor
     *
     *  @param	  DoliDB		$db	  Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Envoie un rappel aux valideur d'objet commerciaux (devis, commande, facture ...)
     */
    public function sendRappel()
    {

        $nb_mail_envoyer = 0;
        $nb_validation_rappeler = 0;
        $now = new DateTime();

        $errors = array();
        $user_demands = array();
        if (!BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm')) {
            $errors[] = "Impossile de charger la classe DemandeValidComm";
            return $errors;
        }

        $sql = BimpTools::getSqlSelect(array('type_de_piece', 'id_piece', 'id_user_ask', 'id_user_affected', 'type', 'date_create'));
        $sql .= BimpTools::getSqlFrom('demande_validate_comm');
        $sql .= BimpTools::getSqlWhere(array('status' => 0));
        $rows = BimpCache::getBdb()->executeS($sql, 'array');

        // Remplissage d'un tableau id_user => array(demande_validation_1, demande_validation_2)
        if (is_array($rows)) {
            foreach ($rows as $r) {

                $date_create = new DateTime($r['date_create']);
                $key = $r['type'] . '_' . $r['id_piece'];

                $interval = date_diff($date_create, $now);

                // Enregistrement du nombre de jour qui sépare aujourd'hui de
                //  la date de création de la demande
                $r['diff'] = $interval->format('%d');

                $r['date_create'] = $date_create->format('d/m/yy H:i:s');
                if (!isset($user_demands[$r['id_user_affected']])) {
                    $user_demands[$r['id_user_affected']] = array();
                }

                // Cet utilisateur doit recevoir un mail même si il n'a pas beaucoup 
                // de demande en cours, car l'un d'entre elles est trop ancienne
                if (self::LIMIT_DAYS < $r['diff']) {
                    $user_demands[$r['id_user_affected']]['urgent'] = 1;
                    $r['urgent'] = 1;
                }

                $user_demands[$r['id_user_affected']][$key] = $r;
            }
        }

        // Foreach sur users
        foreach ($user_demands as $id_user => $tab_demand) {
            $s = '';
            $nb_demand = (int) sizeof($tab_demand);
            if (isset($tab_demand['urgent']))
                $nb_demand--;

            // Il y a plus de demande que toléré ou il y a une demande très ancienne
            if (self::LIMIT_OBJECT <= $nb_demand or isset($tab_demand['urgent'])) {

                if (1 < $nb_demand)
                    $s = 's';

                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                $subject = $nb_demand . " demande$s de validation en cours";
                $message = "Bonjour " . $user->getData('firstname') . ",<br/>";
                $message .= "Vous avez $nb_demand demande$s de validation en cours, voici le$s lien$s<br/>";

                foreach ($tab_demand as $key => $demand) {

                    // Ignorer l'entré pour signaler que cet utilisateur a des demandes urgente à traiter
                    if ($key == 'urgent')
                        continue;

                    $obj = DemandeValidComm::getObject($demand['type_de_piece'], $demand['id_piece']);
                    $message .= $obj->getNomUrl() . ' (demande: ' . $demand['date_create'] . ', ';

                    if (isset($demand['urgent']))
                        $message .= '<strong color="red">' . $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' : '' ) . ')</strong><br/>';
                    else
                        $message .= $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' : '' ) . ')<br/>';
                }


                mailSyn2($subject, $user->getData('email'), null, $message);

                $nb_validation_rappeler += $nb_demand;
                ++$nb_mail_envoyer;
            } else
                $nb_validation_ignorer += $nb_demand;
        }


        $this->output = "Nombre de mails envoyés " . $nb_mail_envoyer . "<br/>";
        $this->output .= "Nombre de validations rappelés " . $nb_validation_rappeler . "<br/>";
        $this->output .= "Nombre de validations ignorés " . $nb_validation_ignorer . "<br/>";
        if (count($errors))
            $this->output .= "Erreurs " . print_r($errors, 1) . "<br/>";

        return 0;
    }
}
