<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


class DemandeValidComm extends BimpObject
{
    CONST STATUS_PROCESSING = 0;
    CONST STATUS_VALIDATED = 1;
    CONST STATUS_REFUSED = 2;

    public static $status_list = Array(
        self::STATUS_PROCESSING   => Array('label' => 'En cours', 'classes' => Array('warning'), 'icon' => 'fas_cogs'),
        self::STATUS_VALIDATED => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'check'),
        self::STATUS_REFUSED    => Array('label' => 'Refusé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

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
        self::OBJ_ALL      => Array('label' => 'Tous'/*,     'icon' => 'fas_file-invoice'*/),
        self::OBJ_DEVIS    => Array('label' => 'Devis',    'icon' => 'fas_file-invoice'),
        self::OBJ_FACTURE  => Array('label' => 'Facture',  'icon' => 'fas_file-invoice-dollar'),
        self::OBJ_COMMANDE => Array('label' => 'Commande', 'icon' => 'fas_dolly')
    );
    
    const LIMIT_DEMANDE = 10;
    
    public function canDelete() {
        global $user;
        
        if((int) $user->admin)
            return 1;
        
        return 0;
    }
    
    public function displayObject() {
        
        $html = '';
        $obj = (int) $this->getData('type_de_piece');
        $id_obj = (int) $this->getData('id_piece');
        if (!is_null($id_obj)) {
            switch ($obj) {
                case self::OBJ_DEVIS:
                    $devis = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_obj);
                    $html .= $devis->getNomUrl(true, true, true, '', 'default');
                    break;

                case self::OBJ_FACTURE:
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_obj);
                    $html .= $facture->getNomUrl(true, true, true, '', 'default');
                    break;
                
                case self::OBJ_COMMANDE:
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_obj);
                    $html .= $commande->getNomUrl(true, true, true, '', 'default');
                    break;
            }
        } else {
            $html .= 'Id pièce non définit';
        }
        
        return $html;
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = parent::create($warnings, $force_create);
        
        if(!empty($errors))
            return $errors;
            
        return $this->onCreate();
    }
    
    public function onCreate() {
        global $user;
        $errors = array();
        
        switch ($this->getData('type_de_piece')) {
            case self::OBJ_DEVIS:
                $class = 'Bimp_Propal';
                break;
            case self::OBJ_FACTURE:
                $class = 'Bimp_Facture';
                break;
            case self::OBJ_COMMANDE:
                $class = 'Bimp_Commande';
                break;
            default:
                $errors[] = "Type d'objet non reconnu ";
                break;
        }
        
        $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', $class, (int) $this->getData('id_piece'));
        
        $subject = "Validation " . $bimp_object->getLabel() . ' '. $bimp_object->getData('ref');
        $message = "Merci de valider " . $bimp_object->getLabel('the') . ' ' . $bimp_object->getNomUrl(1);

        $task = BimpObject::getInstance("bimptask", "BIMP_Task");
        $tasks = $task->getList(array('test_ferme' => $this->getTestFerme()));
        if (count($tasks) == 0) {
            $data = array(
                "src"  => $user->email,
                "dst"  => "validationcommande@bimp-goupe.net",
                "subj" => $subject,
                "txt"  => $message,
                "id_user_owner"  => $this->getData('id_user_affected'),
                "test_ferme" => $this->getTestFerme());
            $errors = BimpTools::merge_array($errors, $task->validateArray($data));
            $errors = BimpTools::merge_array($errors, $task->create());
        }
        
        $user_affected = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('id_user_affected'));
        $message_mail = 'Bonjour ' . $user_affected->getData('firstname') . ',<br/><br/>' . $message;
        
        $type = ($this->getData('type') == self::TYPE_FINANCE) ? 'financière' : 'commerciale';
        $subject_mail = "Demande de validation $type";
        
        if ((int) $bimp_object->getData('fk_soc')) {
            $client = $bimp_object->getChildObject('client');
            if (BimpObject::objectLoaded($client)) 
                $subject_mail .= ' - ' . $client->getData('code_client') . ' - ' . $client->getData('nom');
            else
                $subject_mail .= ', client inconnu';
        }
        
        
        mailSyn2($subject_mail, $user_affected->getData('email'), "admin@bimp.fr", $message_mail);
        
        return $errors;
    }
    
    
    public function delete(&$warnings = array(), $force_delete = false) {
        $errors = $this->beforeDelete($warnings);
        $errors = BimpTools::merge_array($errors, parent::delete($warnings, $force_delete));
        
        return $errors;
    }

    public function getTestFerme() {
        return $this->getTable() . ":id=" . $this->id . " && status>" . self::STATUS_PROCESSING;
    }

    public function beforeDelete(&$warnings, $force_delete = false) {        
        $task = BimpCache::findBimpObjectInstance('bimptask', 'BIMP_Task', array('test_ferme' => $this->getTestFerme()));
        if(is_a($task, 'BIMP_Task'))
            return $task->delete($warnings, $force_delete);
    }
    
    
    public static function getObject($object, $id_object) {
        $class = '';
        switch ($object) {
            case self::OBJ_DEVIS:
                $class = 'Bimp_Propal';
                break;
            case self::OBJ_FACTURE:
                $class = 'Bimp_Facture';
                break;
            case self::OBJ_COMMANDE:
                $class = 'Bimp_Commande';
                break;
            default:
                break;
        }
        if($class != '' && $id_object > 0)
            return  BimpCache::getBimpObjectInstance('bimpcommercial', $class, (int) $id_object);
        return null;
        
    }
    
    
    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false) {
        
        $errors = parent::updateField($field, $value, $id_object, $force_update, $do_not_validate);
        
        if($field == 'status') {
            
            
            if(0 < (int) $this->getData('id_user_ask')) {
                $user_ask = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('id_user_ask'));
                
                $bimp_obj = $this->getObject($this->getData('type_de_piece'), $this->getData('id_piece'));
                $soc = $bimp_obj->getChildObject('client');
                
                if(is_object($bimp_obj) && $bimp_obj->isLoaded()){
                    $subject = ((int) $value == self::STATUS_VALIDATED) ? 'Validation' : 'Refus';
                    $subject .= ' ' . $bimp_obj->getRef();

                    $message_mail = 'Bonjour ' . $user_ask->getData('firstname') .',<br/><br/>';
                    $message_mail .= ucfirst($bimp_obj->getLabel('the')) . ' ' . $bimp_obj->getNomUrl() . ' ';
                    if($soc->isLoaded())
                        $message_mail .= $soc->getRef() . ' - ' . $soc->getName() . ' ';
                    else
                        $message_mail .= ', client inconnu ';;
                    $message_mail .= ' a été ' . lcfirst(self::$status_list[(int) $value]['label']);
                    $message_mail .= ($bimp_obj->isLabelFemale()) ? 'e' : '';
                    $message_mail .= ' ' . lcfirst(self::$types[(int) $this->getData('type')]['label']) . 'ment.';

                    mailSyn2($subject, $user_ask->getData('email'), "admin@bimp.fr", $message_mail);
                }
            } else {
                if (class_exists('BimpCore')) {
                    BimpCore::addlog('Echec envoi email lors de validation commerciale ou financière', Bimp_Log::BIMP_LOG_ALERTE, 'bimpvalidateorder', NULL, array(
                        'id_user_ask' => $this->getData('id_user_ask')
                    ));
                }
            }
        }

        return $errors;
    }
    
    
    public function getDemandeForUser($id_user, $id_max, &$errors = array()) {
        
        $demandes = array();
        
        $filters = array(
            'id' => array(
                'operator' => '>',
                'value'    => (int) $id_max
            ),
            'status'           => (int) self::STATUS_PROCESSING,
            'id_user_affected' => (int) $id_user
        );
        
        $demande_en_cours = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);
        $valid_comm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
        
        $secteurs = BimpCache::getSecteursArray();
        
        foreach($demande_en_cours as $d) {
            
            $bimp_object = self::getObject($d->getData('type_de_piece'), $d->getData('id_piece'));
            
            if($bimp_object->isLoaded()) {
                list($secteur, , $percent, $montant_piece) = $valid_comm->getObjectParams($bimp_object);
                
                $soc = $bimp_object->getChildObject('client');

                $new_demande = array(
                    'type'        => lcfirst(self::$types[(int) $d->getData('type')]['label']),
                    'client'      => $soc->getRef() . ' - ' . $soc->getName(),
                    'secteur'     => lcfirst($secteurs[$secteur]),
                    'ref'         => $d->getRef(),
                    'url'         => $bimp_object->getUrl(),
                    'id'          => $d->id,
                    'date_create' => $d->getData('date_create')
                );

                if((int) $d->getData('type') == (int) self::TYPE_FINANCE)
                    $new_demande['montant'] = $montant_piece;
                else
                    $new_demande['remise'] = $percent;
                
            } else
                $new_demande = array();
            
            
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $d->getData('id_user_ask'));
            
            if($user->isLoaded()) {
                $new_demande['user_firstname'] = $user->getData('firstname');
                $new_demande['user_lastname'] = $user->getData('lastname');
            }

            
            $demandes['content'][] = $new_demande;
        }
        
        $demandes['nb_demande'] = (int) sizeof($demande_en_cours);
        
        return $demandes;
    }
    
    public function getRef($withGenerique = true) {
        $obj = $this->getObject($this->getData('type_de_piece'), $this->getData('id_piece'));
        if(is_object($obj) && $obj->isLoaded())
            return $obj->getRef($withGenerique);
        return '';
    }

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '', $card = '') {
        $obj = $this->getObject($this->getData('type_de_piece'), $this->getData('id_piece'));
        if(is_object($obj) && $obj->isLoaded())
            return $obj->getNomUrl($withpicto, $ref_only, $page_link, $modal_view, $card);
        return '';
    }
    
}
