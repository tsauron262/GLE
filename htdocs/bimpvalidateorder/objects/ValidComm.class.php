<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


class ValidComm extends BimpObject
{
    
    // User
    const USER_ALL = -1;
    const USER_SUP = -2;

    // Type
    const TYPE_FINANCE = 0;
    const TYPE_COMMERCIAL = 1;
    
    public static $types = Array(
        self::TYPE_FINANCE    => Array('label' => 'Financière',  'icon' => 'fas_search-dollar'),
        self::TYPE_COMMERCIAL => Array('label' => 'Commerciale', 'icon' => 'fas_hand-holding-usd')
    );
  
    // Piece
    const OBJ_ALL = -1;
    const OBJ_DEVIS = 0;
    const OBJ_FACTURE = 1;
    const OBJ_COMMANDE = 2;
    
    public static $objets = Array(
        self::OBJ_ALL      => Array('label' => 'Tous'),
        self::OBJ_DEVIS    => Array('label' => 'Devis',    'icon' => 'fas_file-invoice'),
        self::OBJ_FACTURE  => Array('label' => 'Facture',  'icon' => 'fas_file-invoice-dollar'),
        self::OBJ_COMMANDE => Array('label' => 'Commande', 'icon' => 'fas_dolly')
    );
    
    // Only child
    const USER_ASK_ALL = 0;
    const USER_ASK_CHILD = 1;
    
    public function canEdit() {
        global $user;
        if($user->id == 330)
            return 1;
        $right = 'validationcommande@bimp-groupe.net';
        return $user->rights->bimptask->$right->write;
    }
    
    public function getUnity() {
        
        if((int) $this->getData('type') === self::TYPE_FINANCE)
            return 'float';
        
        return 'float';
        return 'percent';
    }
    
    public function getUserArray() {
        
        return  array(
            self::USER_ALL => 'Tout le monde',
            self::USER_SUP => 'Supérieur hiérarchique')
            + BimpCache::getUsersArray();
    }
    
    private function checkMinMax() {
        
        $errors = array();
        
        if($this->getData('val_max') < $this->getData('val_min'))
            $errors[] = "Valeur min supérieur à valeur max.";
        
        return $errors;
    }


    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = $this->checkMinMax();
        
        if(empty($errors))
            $errors =  parent::create($warnings, $force_create);
        
        return $errors;        

    }
    
    public function update(&$warnings = array(), $force_update = false) {
        
        $errors = $this->checkMinMax();
        
        if(empty($errors))
            $errors =  parent::update($warnings, $force_update);
        return $errors;
    }

    /**
     * Tente de valider un objet BimpComm
     * Si l'utilisateur ne peut pas, contacte quelqu'un de disponible
     * pour valider cet objet
     */
    public function tryToValidate($bimp_object, $user, &$errors, &$success) {
        if (BimpCore::isModeDev()) {
            return 1;
        }
        
        $valid_comm = 1;
        $valid_finan = 1;
        
//        return 1;
                       
        // Object non géré
        if($this->getObjectClass($bimp_object) == -2)
            return 1;
        
        $this->db2 = new DoliDBMysqli('mysql', $this->db->db->database_host,
                $this->db->db->database_user, $this->db->db->database_pass,
                $this->db->db->database_name, $this->db->db->database_port);
        
//        echo'<pre>';
//        print_r($bimp_object->dol_object->db);
//        echo'OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO';
        // Création contact
//        $bimp_object->dol_object->db = $this->db2;
////        print_r($bimp_object->dol_object->db);
//
//        $errors = BimpTools::merge_array($errors, $bimp_object->checkContacts());
//        $bimp_object->db = $this->db;
        
        list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($bimp_object, $errors);
        
        if(!empty($errors))
            return 0;
                
        // validation commerciale
        if($percent != 0)
            $valid_comm = (int) $this->tryValidateByType($user, self::TYPE_COMMERCIAL, $secteur, $class, $percent, $bimp_object, $errors);
        
        // Validation financière
        if($val_euros != 0 && $this->getObjectClass($bimp_object) != self::OBJ_DEVIS)
            $valid_finan = (int) $this->tryValidateByType($user, self::TYPE_FINANCE, $secteur, $class, $val_euros, $bimp_object, $errors);

        if(!$valid_comm)
                $errors[] = "Vous ne pouvez pas valider commercialement " 
                . $bimp_object->getLabel('this') . '. La demande de validation commerciale a été adressée au valideur attribué.<br/>';
        else
            $success[] = "Validation commerciale effectuée.";
        
        if(!$valid_finan)
                $errors[] = $this->getErrorFinance($user, $bimp_object);
        else
            $success[] = "Validation financière effectuée.";
        
        return $valid_comm and $valid_finan;
    }
    
    
    private function tryValidateByType($user, $type, $secteur, $class, $val, $bimp_object, &$errors) {
//return 1; TODO
        $demande = $this->demandeExists($class, (int) $bimp_object->id, $type);

        if(is_a($demande, 'DemandeValidComm')) {

            if((int) $demande->getData('status') == (int) DemandeValidComm::STATUS_VALIDATED)
                return 1;

            // Je suis le valideur
            elseif ((int) $demande->getData('id_user_affected') == (int) $user->id) {
                $this->updateDemande ((int) $user->id, $class, (int) $bimp_object->id, $type, (int) DemandeValidComm::STATUS_VALIDATED);
                return 1;
                
            // Je peux valider (sans être le valideur)
            } elseif($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val, $bimp_object)) {
                $this->updateDemande ((int) $user->id, $class, (int) $bimp_object->id, $type, (int) DemandeValidComm::STATUS_VALIDATED);
                return 1;
            }
        
        // Pas de demande existante
        } else {

            // Dépendant d'un autre object déja validé/fermé (avec même montant ou remise)
            if($this->linkedWithValidateObject($bimp_object, $type, $val)) {
                return 1;
            }
            
            elseif($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val, $bimp_object))
                return 1;
            
            else {
                $this->createDemande($user, $bimp_object, $type, $class, $val, $secteur, $errors);
                return 0;
            }
        }
        
        return 0;
    }

    
    private function userCanValidate($id_user, $secteur, $type, $object, $val, $bimp_object) {
        
        if($type == self::TYPE_FINANCE) {
            $depassement_actuel = $this->getEncours($bimp_object);
            $val_max = $val + $depassement_actuel;
            
            // Dans le cas des avoir on utilise comme valeur max dans les filtre la valeur de l'objet
            if($val < 0)
                $val_max = $val;
            
            elseif($val_max < 0)
                return 1;

        }
        
        $user_groups = array($id_user, self::USER_ALL);
        if($this->isSupHierarchique($id_user))
            $user_groups[] = self::USER_SUP;

        $filters = array(
            'user'    => array(
                'in' => $user_groups
            ),
            'secteur' => $secteur,
            'type'    => $type,
            'type_de_piece'  => array(
                'in' => array($object, self::OBJ_ALL)
            ),
            'val_max' => array(
                'operator' => '>=',
                'value'    => (isset($val_max)) ? $val_max : $val
            ),
            'val_min' => array(
                'operator' => '<=',
                'value'    => $val
            )
        );
        
        
        $valid_comms = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'ValidComm', $filters);

        foreach($valid_comms as $vc)
            return 1;

        return 0;
    }
    
    private function getEncours($bimp_object){

        $client = $bimp_object->getChildObject('client');
        $max = $client->getData('outstanding_limit');
        
        $actuel = $client->getEncours();

        return $actuel - $max;
        
    }
    
    
    private function getErrorFinance($user, $bimp_object) {
        $id_user = (int) $user->id;
        list($secteur, $class, $percent, $montant_piece) = $this->getObjectParams($bimp_object);
        $error = '';
        
        $depassement_actuel = $this->getEncours($bimp_object);
        $depassement_futur = $montant_piece + $depassement_actuel;
        
        $user_groups = array($id_user, self::USER_ALL);
        if($this->isSupHierarchique($id_user))
            $user_groups[] = self::USER_SUP;
        

        $filters = array(
            'user'    => array(
                'in' => $user_groups
            ),
            'secteur' => $secteur,
            'type'    => self::TYPE_FINANCE,
            'type_de_piece'  => array(
                'in' => array($class, self::OBJ_ALL)
            )
        );
        
        $valid_comms = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'ValidComm', $filters, 'val_max', 'DESC');
        
        foreach($valid_comms as $vc) {
            $error .= 'Votre validation max ' . $vc->getData('val_max') . '€<br/>';
            $error .= 'Dépassement de l\'encours du client ' . $depassement_actuel . '€<br/>';
            $error .= 'Montant ' . $bimp_object->getLabel('the') . ' ' . $montant_piece . '€<br/>';
            $error .= 'Dépassement après la validation ' . $depassement_futur . '€<br/>';
            $error .= 'La demande de validation financière a été adressée au valideur attribué.<br/>';
            
            return $error;
        }
        
        $error .= 'Dépassement de l\'encours du client ' . $depassement_actuel . '€<br/>';
        $error .= 'Montant ' . $bimp_object->getLabel('the') . ' ' . $montant_piece . '€<br/>';
        $error .= 'Dépassement après la validation ' . $depassement_futur . '€<br/>';
        $error .= 'La demande de validation financière a été adressée au valideur attribué.<br/>';

        return $error;
    }

    private function isSupHierarchique($id_user) {
        
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
    
    public function userIsAvaible($id_user) {
        
        // L'utilisateur est actif ?
        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
        $ok_user = (int) $user->getData('statut');
        
        
        // L'utilisateur n'a pas une liste de demande de validation trop longue ?
        BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm');
        
        $filters = array(
            'id_user_affected' => (int) $id_user,
            'status'        => DemandeValidComm::STATUS_PROCESSING
        );
        
        $l_demande = BimpCache::getBimpObjectList('bimpvalidateorder', 'DemandeValidComm', $filters);
        $ok_list = (int) sizeof($l_demande) < DemandeValidComm::LIMIT_DEMANDE;

        return $ok_user and $ok_list;
    }


    public function getObjectParams($object, &$errors = array()) {
        
        // Secteur
        $secteur = $object->getData('ef_type');
        
        // Piece
        $class = self::getObjectClass($object);
        
        // remise %
        $infos_remises = $object->getRemisesInfos();
        $percent = (float) $infos_remises['remise_total_percent'];

        // Valeur €
        if((int) $object->getData('total_ht') > 0)
            $val = (float) $object->getData('total_ht');
        else
            $val = (float) $object->getData('total');
        
        return array($secteur, $class, $percent, $val);
    }
    
    public static function getObjectClass($object) {

        switch (get_class($object)) {
            case 'Bimp_Propal':
                return self::OBJ_DEVIS;            
//            case 'Bimp_Facture':
//                return self::OBJ_FACTURE;             
            case 'Bimp_Commande':
                return self::OBJ_COMMANDE;            
        }
        
        return -2;
    }
    
    public function createDemande($user_ask, $bimp_object, $type, $object, $val, $secteur, &$errors) {
        
        $d = $this->demandeExists($object, (int) $bimp_object->id, $type);
        
        // Déjà créer
        if($d)
            return 2;
        
        $id_user_affected = $this->findValidator($type, $val, $secteur, $object, $user_ask);
        
        // Personne ne peut valider
        if(!$id_user_affected) {
            
            $type_nom = (($type == self::TYPE_COMMERCIAL) ? 'commercialement': 'financièrement');
            $val_nom = (($type == self::TYPE_COMMERCIAL) ? ' remise de ' . $val . '%' : 'montant HT de ' . $val . '€');
            $secteur_nom = BimpCache::getSecteursArray()[$secteur];
            
            $message =  'Aucun utilisateur ne peut valider ' . $type_nom
                . ' ' . $bimp_object->getLabel('the') . ' (pour le secteur ' . $secteur_nom
                . ', ' . $val_nom . ', utilisateur ' . $user_ask->firstname . ' ' . $user_ask->lastname . ')';
            
            $errors[] = $message;
                      
            $lien = DOL_MAIN_URL_ROOT . '/' . $this->module;
            $message_mail = "Bonjour,<br/>" . $message;
            $message_mail .= "<br/>Liens de l'objet " . $bimp_object->getNomUrl();
            $message_mail .= "<br/><a href='$lien'>Module de validation</a>";
            $message_mail .= "<br/>Demandeur : " . $user_ask->firstname . ' ' . $user_ask->lastname;
  
            mailSyn2("Droits validation commerciale recquis", "dev@bimp.fr", "admin@bimp.fr", $message_mail);
            return 0;
        }
                    
        if((int) $bimp_object->id > 0) {

            $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
            $demande->db->db = $this->db2;
            $errors = BimpTools::merge_array($errors, $demande->validateArray(array(
                'type_de_piece' =>    (int) $object,
                'id_piece' =>         (int) $bimp_object->id,
                'id_user_ask' =>      (int) $user_ask->id,
                'id_user_affected' => (int) $id_user_affected,
                'type' =>             (int) $type
            )));

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
    private function findValidator($type, $val, $secteur, $object, $user_ask) {
        
        $can_valid_not_avaible = 0;
        $can_valid_avaible = 0;

        $filters = array(
            'secteur' => $secteur,
            'type'    => $type,
            'type_de_piece'  => array(
                'in' => array($object, self::OBJ_ALL)
            ),
            'val_max' => array(
                'operator' => '>=',
                'value'    => intval($val)
            ),
            'val_min' => array(
                'operator' => '<=',
                'value'    => intval($val)
            ),
//            'and' => array(
//                'or' => array(
//                    'only_child' => self::USER_ASK_ALL,
//                    'and' => array(
//                        'only_child' => self::USER_ASK_CHILD,
//                        'user'       => $user_ask->fk_user
//                    )
//                )
//            )
        );
        
        $sql = BimpTools::getSqlSelect(array('user', 'val_max'));
        $sql .= BimpTools::getSqlFrom($this->getTable());
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= ' AND (only_child=' . self::USER_ASK_ALL . ' OR (only_child=' . self::USER_ASK_CHILD . ' AND user=' . $user_ask->fk_user . '))';
        $sql .= BimpTools::getSqlOrderBy('date_create', 'DESC');
//        die($sql);
        $rows = self::getBdb()->executeS($sql, 'array');

        
        if (is_array($rows)) {
            foreach ($rows as $r) {
        
                if($r['user'] == self::USER_SUP and $this->userIsAvaible($user_ask->fk_user))
                    $can_valid_avaible = $user_ask->fk_user;
                
                elseif($can_valid_avaible == 0 and $this->userIsAvaible($r['user']))
                    $can_valid_avaible = $r['user'];
                
                elseif($can_valid_not_avaible == 0)
                    $can_valid_not_avaible = $r['user'];
                    
            }
        }
        
        if($can_valid_avaible != 0)
            return $can_valid_avaible;
        
        return $can_valid_not_avaible;
    }
    
    public function demandeExists($class, $id_object, $type) {
        
        $filters = array(
            'type_de_piece' => $class,
            'id_piece'      => $id_object,
            'type'          => $type
        );
        
        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

        foreach($demandes as $key => $val)
            return $demandes[$key];
        
        return 0;
    }
    
    public function updateDemande($id_user, $class, $id_object, $type, $status) {
        
        $filters = array(
            'type_de_piece' => $class,
            'id_piece'      => $id_object,
            'type'          => $type
        );
        
        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);
        foreach($demandes as $d) {
            $d->db->db = $this->db2;
            $now = date('Y-m-d H:i:s');
            $d->updateField('id_user_valid', $id_user);
            $d->updateField('date_valid', $now);
            $d->updateField('status', $status);
            return 1;
        }
        
        return 0;
    }
    
    public function linkedWithValidateObject($current_bimp_object, $current_type, $current_val) {        
        
        foreach (BimpTools::getDolObjectLinkedObjectsList($current_bimp_object->dol_object, $this->db) as $item) {
            
            if(0 < (int) $item['id_object'] and $item['type'] == 'propal') {
                $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);

                list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($propal);

                /*if((int) $current_type == self::TYPE_FINANCE and $current_val <= $val_euros and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
                    return 1;
                else*/if((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
                    return 1;
                
            } elseif(0 < (int) $item['id_object'] and $item['type'] == 'facture') {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);

                list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($facture);
                
                if((int) $current_type == self::TYPE_FINANCE  and $current_val <= $val_euros and in_array((int) $facture->getData('fk_statut'), array(1, 2)))
                    return 1;
                elseif((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent and in_array((int) $facture->getData('fk_statut'), array(1, 2)))
                    return 1;
                
            } elseif(0 < (int) $item['id_object'] and $item['type'] == 'commande') {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);

                list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($commande);
                
                if((int) $current_type == self::TYPE_FINANCE  and $current_val <= $val_euros and in_array((int) $commande->getData('fk_statut'), array(1, 3)))
                    return 1;
                elseif((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent and in_array((int) $commande->getData('fk_statut'), array(1, 3)))
                    return 1;                
            } 
                
        }
        
        return 0;
    }
    
}

class DoliValidComm extends CommonObject {
    
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
    public function sendRappel() {
        
        $nb_mail_envoyer = 0;
        $nb_validation_rappeler = 0;
        $now = new DateTime();
        
        $errors = array();
        $user_demands = array();
        if(!BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm')) {
            $errors[] = "Impossile de charger la classe DemandeValidComm";
            return '<pre>'. print_r($errors, 1);
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
                $r['diff'] =  $interval->format('%d');
                
                $r['date_create'] = $date_create->format('d/m/yy H:i:s');
                if(!isset($user_demands[$r['id_user_affected']])) {
                    $user_demands[$r['id_user_affected']] = array();
                }
                
                // Cet utilisateur doit recevoir un mail même si il n'a pas beaucoup 
                // de demande en cours, car l'un d'entre elles est trop ancienne
                if(self::LIMIT_DAYS < $r['diff']) {
                    $user_demands[$r['id_user_affected']]['urgent'] = 1;
                    $r['urgent'] = 1;
                }
                
                $user_demands[$r['id_user_affected']][$key] = $r;

                
            }   
        }
        
        // Foreach sur users
        foreach($user_demands as $id_user => $tab_demand) {
            $s = '';
            $nb_demand = (int) sizeof($tab_demand);
            if(isset($tab_demand['urgent']))
                $nb_demand--;
            
            // Il y a plus de demande que toléré ou il y a une demande très ancienne
            if(self::LIMIT_OBJECT <= $nb_demand or isset($tab_demand['urgent'])) {

                if(1 < $nb_demand)
                    $s = 's';
                
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                $subject = $nb_demand . " demande$s de validation en cours";
                $message = "Bonjour " . $user->getData('firstname') . ",<br/>";
                $message .="Vous avez $nb_demand demande$s de validation en cours, voici le$s lien$s<br/>";
                
                foreach($tab_demand as $key => $demand) {
                    
                    // Ignorer l'entré pour signaler que cet utilisateur a des demandes urgente à traiter
                    if($key == 'urgent')
                        continue;
                    
                    $obj = DemandeValidComm::getObject($demand['type_de_piece'], $demand['id_piece']);
                    $message .= $obj->getNomUrl() . ' (demande: ' . $demand['date_create'] . ', ';
                    
                    if(isset($demand['urgent']))
                        $message .= '<strong color="red">' . $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' :'' ). ')</strong><br/>';
                    else
                        $message .= $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' :'' ). ')<br/>';
                }
                

                mailSyn2($subject, $user->getData('email'), null, $message);
                
                $nb_validation_rappeler += $nb_demand;
                ++$nb_mail_envoyer;
            } else
                $nb_validation_ignorer += $nb_demand;
            
        }
        
        
        $this->output =  "Nombre de mails envoyés " . $nb_mail_envoyer . "<br/>";
        $this->output .= "Nombre de validations rappelés " . $nb_validation_rappeler . "<br/>";        
        $this->output .= "Nombre de validations ignorés " . $nb_validation_ignorer . "<br/>";        
        
        return print_r($errors, 1);
    }

    
}
