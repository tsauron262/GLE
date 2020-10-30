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
        self::TYPE_COMMERCIAL => Array('label' => 'Commerciale', 'icon' => 'fas_hand-holding-usd')//fas_exchange-alt
    );
  
    // Piece
    const OBJ_ALL = -1;
    const OBJ_DEVIS = 0;
    const OBJ_FACTURE = 1;
    const OBJ_COMMANDE = 2;
    
    public static $objets = Array(
        self::OBJ_ALL      => Array('label' => 'Tous'/*,     'icon' => 'fas_file-invoice'*/),
        self::OBJ_DEVIS    => Array('label' => 'Devis',    'icon' => 'fas_file-invoice'),
        self::OBJ_FACTURE  => Array('label' => 'Facture',  'icon' => 'fas_file-invoice-dollar'),
        self::OBJ_COMMANDE => Array('label' => 'Commande', 'icon' => 'fas_dolly')
    );
    

    public function canEdit() {
        global $user;
        
        $right = 'validationcommande@bimp-groupe.net';
        return $user->rights->bimptask->$right->write;
    }
    
    public function getUnity() {
        
        if((int) $this->getData('type') === self::TYPE_FINANCE)
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
    
    
//    TODO
//     Pour info, cela ne concerne que le secteur C.
//> Franck Pinéri souhaite que nous affinions les validations commerciales
//>
//> Il faudrait que :
//> - si la remise est inférieure à 3% : pas de validation commerciale
//> - si la remise est comprise entre 3 et 5% : la validation commerciale soit adressée au N+1
//> - si la remise dépasse les 5% : la validation commerciale soit adressées à Franck Pinéri
//>
//> Seule Aurélie Plantard reste autonome sur les remises à accorder à ses clients

    /**
     * Tente de valider un objet BimpComm
     * Si l'utilisateur ne peut pas, contacte quelqu'un de disponible
     * pour valider cet objet
     */
    public function tryToValidate($bimp_object, $user, &$errors) {
        $valid_comm = 1;
        $valid_finan = 1;
        
//        return 1;
        
        $this->db2 = new DoliDBMysqli('mysql', $this->db->db->database_host,
                $this->db->db->database_user, $this->db->db->database_pass,
                $this->db->db->database_name, $this->db->db->database_port);
                
        list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($bimp_object, $errors);
        
        if(!empty($errors))
            return 0;
                
        // validation commerciale
        if($percent != 0)
            $valid_comm = (int) $this->tryValidateByType($user, self::TYPE_COMMERCIAL, $secteur, $class, $percent, $bimp_object, $errors);
        
        // Validation financière
        if($val_euros != 0)
            $valid_finan = (int) $this->tryValidateByType($user, self::TYPE_FINANCE, $secteur, $class, $val_euros, $bimp_object, $errors);

        if(!$valid_comm) {
            // On vérifie que celui qui a fait la demande pour l'autre type n'ai pas
            // validé pour ce type
//            $demande_autre_type = $this->demandeExists($class, (int) $bimp_object->id, self::TYPE_FINANCE);
////            $errors[] =  print_r($demande_autre_type->data, 1);
//
//            if((is_a($demande_autre_type, 'DemandeValidComm') and 
//                    !$this->userCanValidate((int) $demande_autre_type->getData('id_user_ask'), 
//                    $secteur, self::TYPE_FINANCE, $class, $val_euros))
//                or !$demande_autre_type)
                $errors[] = "Vous ne pouvez pas valider commercialement " 
                . $bimp_object->getLabel('this') . ' une demande a été envoyée';

        }
        
        if(!$valid_finan) {
            // On vérifie que celui qui a fait la demande pour l'autre type n'ai pas
            // validé pour ce type
//            $demande_autre_type = $this->demandeExists($class, (int) $bimp_object->id, self::TYPE_COMMERCIAL);
////            $errors[] =  print_r($demande_autre_type->data, 1);
//            if((is_a($demande_autre_type, 'DemandeValidComm') and 
//                    !$this->userCanValidate((int) $demande_autre_type->getData('id_user_ask'), 
//                    $secteur, self::TYPE_COMMERCIAL, $class, $percent))
//                or !$demande_autre_type)
                $errors[] = "Vous ne pouvez pas valider financièrement " 
                . $bimp_object->getLabel('this') . ' une demande a été envoyée';
        }

        return $valid_comm and $valid_finan;
    }
    
    
    private function tryValidateByType($user, $type, $secteur, $class, $val, $bimp_object, &$errors) {

        $demande = $this->demandeExists($class, (int) $bimp_object->id, $type);

        if(is_a($demande, 'DemandeValidComm')) {

            if((int) $demande->getData('status') == (int) DemandeValidComm::STATUS_VALIDATED)
                return 1;

            // Je suis le valideur
            elseif ((int) $demande->getData('id_user_affected') == (int) $user->id) {
                $this->updateDemande ((int) $user->id, $class, (int) $bimp_object->id, $type, (int) DemandeValidComm::STATUS_VALIDATED);
                return 1;
                
            // Je peux valider (sans être le valideur)
            } elseif($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val)) {
                $this->updateDemande ((int) $user->id, $class, (int) $bimp_object->id, $type, (int) DemandeValidComm::STATUS_VALIDATED);
                return 1;
            }
        
        // Pas de demande existante
        } else {
            
            // Dépendant d'un autre object déja validé/fermé (avec même montant ou remise)
            if($this->linkedWithValidateObject($bimp_object, $type, $val)) {
                return 1;
            }
            
            elseif($this->userCanValidate((int) $user->id, $secteur, $type, $class, $val))
                return 1;
            
            else {
                $this->createDemande($user, $bimp_object, $type, $class, $val, $secteur, $errors);
                return 0;
            }
        }
        
        return 0;
    }

    
    private function userCanValidate($id_user, $secteur, $type, $object, $val) {
        
        $user_groups = array($id_user, self::USER_ALL);
        if($this->isSupHierarchique($id_user))
            $user_groups[] = self::USER_SUP;

        $filters = array(
            'user'    => array(
                'in' => $user_groups
            ),
            'secteur' => $secteur,
            'type'    => $type,
            'object'  => array(
                'in' => array($object, self::OBJ_ALL)
            ),
            'val_max' => array(
                'operator' => '>',
                'value'    => $val
            ),
            'val_min' => array(
                'operator' => '<',
                'value'    => $val
            )
        );
        
        $valid_comms = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'ValidComm', $filters);

        foreach($valid_comms as $vc)
            return 1;

        return 0;
    }
    
    private function isSupHierarchique($id_user) {
        
//        $cache_key = 'is_superieur_hierarchique_' . $id_user;
//        $is_sup = BimpCache::cacheExists($cache_key);
//        echo 'cache dit ' . $is_sup . ' <br/>';
//        if($is_sup == 1) {
//            echo 'cache: je suis sup hiéar<br/>';
//            return 1;
//        } elseif($is_sup == -1) {
//            echo 'cache: je NE suis PAS sup hiéar<br/>';
//            return 0;
//        }
        
        $filters = array(
            'fk_user' => $id_user
        );
        
        $sql = BimpTools::getSqlSelect(array('rowid'));
        $sql .= BimpTools::getSqlFrom('user');
        $sql .= BimpTools::getSqlWhere($filters);
        $rows = self::getBdb()->executeS($sql, 'array');
        
        if (is_array($rows) and count($rows)) {
//            BimpCache::$cache[$cache_key] = 1;
//            echo 'je suis sup hiéar<br/>';
            return 1;
        }
        
        // Pas sup hiérarchique => définition de la valeur dans bimp cache
//        BimpCache::$cache[$cache_key] = -1;
//            echo ' je NE suis PAS sup hiéar<br/>';
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


    private function getObjectParams($object, &$errors = array()) {
        
        // Secteur
        $secteur = $object->getData('ef_type');
        
        // Piece
        $class = self::getObjectClass($object);
        if(is_array($class)) {
            $errors[] = $class;
            return 0;
        }
        
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
            case 'Bimp_Facture':
                return self::OBJ_FACTURE;             
            case 'Bimp_Commande':
                return self::OBJ_COMMANDE;            
        }
        
        return array("Classe de l'objet non définit " . get_class($object));
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
                . ', ' . $val_nom . ')';
            
            $errors[] = $message;
                      
            $lien = DOL_MAIN_URL_ROOT . '/' . $this->module;
            $message_mail = "Bonjour,<br/>" . $message;
            $message_mail .= "<br/>Liens de l'objet " . $bimp_object->getNomUrl();
            $message_mail .= "<br/><a href='$lien'>Module de validation</a>";
  
            mailSyn2("Droits validation commerciale recquis", "dev@bimp.fr", "admin@bimp.fr", $message_mail);
            return 0;
        }
                    
        if((int) $bimp_object->id > 0) {

            $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
            $demande->db->db = $this->db2;
            $errors = BimpTools::merge_array($errors, $demande->validateArray(array(
                'object' =>           (int) $object,
                'id_object' =>        (int) $bimp_object->id,
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
            'object'  => array(
                'in' => array($object, self::OBJ_ALL)
            ),
            'val_max' => array(
                'operator' => '>',
                'value'    => $val
            ),
            'val_min' => array(
                'operator' => '<',
                'value'    => $val
            )
        );
        
        $sql = BimpTools::getSqlSelect(array('user', 'val_max'));
        $sql .= BimpTools::getSqlFrom($this->getTable());
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy('val_max');
        $rows = self::getBdb()->executeS($sql, 'array');
       // SELECT a.user, a.val_max FROM llx_validate_comm a WHERE a.secteur = 'BP'
       // AND a.type = 1 AND a.object IN ("1","-1") AND a.val_max > 8 AND a.val_min < 8 ORDER BY a.val_max ASC
        
        if (is_array($rows)) {
            foreach ($rows as $r) {
        
                if($can_valid_avaible == 0 and $r['user'] == self::USER_SUP and $this->userIsAvaible($user_ask->fk_user))
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
            'object'        => $class,
            'id_object'     => $id_object,
            'type'          => $type
        );
        
        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

        foreach($demandes as $key => $val)
            return $demandes[$key];
        
        return 0;
    }
    
    public function updateDemande($id_user, $class, $id_object, $type, $status) {
        
        $filters = array(
            'object'        => $class,
            'id_object'     => $id_object,
            'type'          => $type
        );
        
        $demandes = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);
        foreach($demandes as $d) {
            $d->db->db = $this->db2;
            $now = date('Y-m-d H:i:s');
            $d->updateField('status', $status);
            $d->updateField('id_user_valid', $id_user);
            $d->updateField('date_valid', $now);
            return 1;
        }
        
        return 0;
    }
    
    public function linkedWithValidateObject($current_bimp_object, $current_type, $current_val) {        
        
        foreach (BimpTools::getDolObjectLinkedObjectsList($current_bimp_object->dol_object, $this->db) as $item) {
            
            if(0 < (int) $item['id_object'] and $item['type'] == 'propal') {
                $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);

                list($secteur, $class, $percent, $val_euros) = $this->getObjectParams($propal);

                if((int) $current_type == self::TYPE_FINANCE and $current_val <= $val_euros and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
                    return 1;
                elseif((int) $current_type == self::TYPE_COMMERCIAL and $current_val <= $percent and in_array((int) $propal->getData('fk_statut'), array(1, 2, 4)))
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
