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
    const TYPE_ENCOURS = 0;
    const TYPE_COMMERCIAL = 1;
    const TYPE_IMPAYE = 2;
    
    public static $types = Array(
        self::TYPE_ENCOURS    => Array('label' => 'Encours',     'icon' => 'fas_search-dollar'),
        self::TYPE_COMMERCIAL => Array('label' => 'Commerciale', 'icon' => 'fas_hand-holding-usd'),
        self::TYPE_IMPAYE     => Array('label' => 'Impayé',      'icon' => 'fas_dollar-sign')
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
    const NO_VAL_COMM = -3; // Validé toute seule (ex: retrait des remise)
   
    public function canDelete() {
        global $user;
        
        if((int) $user->admin or (int) $user->rights->bimpvalidateorder->demande_validation->delete)
            return 1;
        
        return 0;
    }
    
    public function getThisObject(){
        $obj = (int) $this->getData('type_de_piece');
        $id_obj = (int) $this->getData('id_piece');
        if (!is_null($id_obj)) {
            switch ($obj) {
                case self::OBJ_DEVIS:
                    $devis = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_obj);
                    return $devis;

                case self::OBJ_FACTURE:
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_obj);
                    return $facture;
                
                case self::OBJ_COMMANDE:
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_obj);
                    return $commande;
            }
        } 
        return null;
    }
    
    public function displayClient(){
        $html = '';
        $obj = $this->getThisObject();
        if($obj){
            $client = $obj->getChildObject('client');
            $html .= $client->getLink();
        }
        
        return $html;
    }
    
    public function displayObject() {
        $html = '';
        $obj = $this->getThisObject();
        if($obj)
            $html .= $obj->getLink();
        
        return $html;
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        if($this->getData('id_user_affected') == 330)
            return array("Olivier de la CLERGERIE n'est pas concerné par les demandes de validation");
        
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
        $message = "Merci de valider " . $bimp_object->getLabel('the') . ' ' . str_replace("fc=commande","fc=commande&navtab-maintabs=content", $bimp_object->getProvLink(1));

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
        
            switch ($this->getData('type')) {
                case self::TYPE_COMMERCIAL:
                    $type = 'commerciale';
                    break;
                case self::TYPE_ENCOURS:
                    $type = 'encours';
                    break;
                case self::TYPE_IMPAYE:
                    $type = 'de retard de paiement';
                    break;
            }
        
        $subject_mail = "Demande de validation $type";
                
        if ((int) $bimp_object->getData('fk_soc')) {
            
            if(method_exists($bimp_object, 'getClientFacture'))
                $client = $bimp_object->getClientFacture();
            else
                $client = $bimp_object->getChildObject('client');
            
            if (BimpObject::objectLoaded($client))
                $subject_mail .= ' - ' . $client->getData('code_client') . ' - ' . $client->getData('nom');
            else
                $subject_mail .= ', client inconnu';
        }
        
        
        mailSyn2($subject_mail, $user_affected->getData('email'), null, $message_mail);
        
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

    public function beforeDelete(&$warnings) {        
        $task = BimpCache::findBimpObjectInstance('bimptask', 'BIMP_Task', array('test_ferme' => $this->getTestFerme()));
        if(!is_null($task) and $task->isLoaded())
            return $task->delete($warnings, true);
        
        return array();
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
                
                $this->onValidate();

            } else {
                if (class_exists('BimpCore')) {
                    BimpCore::addlog('Echec envoi email lors de validation commerciale/encours/impayé', Bimp_Log::BIMP_LOG_ALERTE, 'bimpvalidateorder', NULL, array(
                        'id_user_ask' => $this->getData('id_user_ask')
                    ));
                }
            }
        }

        return $errors;
    }
    
    public function onValidate() {
        
        if(in_array($this->getData('type_de_piece'), array(self::OBJ_DEVIS, self::OBJ_FACTURE, self::OBJ_COMMANDE))) {
            return 1;
        
        } else {
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

                switch ($this->getData('type')) {
                    case self::TYPE_COMMERCIAL:
                        $message_mail .= ' commercialement';break;
                    case self::TYPE_ENCOURS:
                        $message_mail .= ' malgré le dépassement d\'encours du client';break;
                    case self::TYPE_IMPAYE:
                        $message_mail .= ' malgré les retards de paiement du client';break;
                }

                mailSyn2($subject, $user_ask->getData('email'), null, $message_mail);
            }
        }
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

                if((int) $d->getData('type') == (int) self::TYPE_ENCOURS)
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
        
        if(!isset($demandes['content']))
            $demandes['content'] = array();
        
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
    
    
    public function getDemandeValidCommExtraButtons() {
        $buttons = array();
        
        if($this->getData('val_comm_demande') or $this->getData('val_comm_validation')) {
            if ($this->isLoaded()) {
                            $buttons[] = array(
                    'label'   => 'Validation',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsLoadModalView('valid_comm', 'Règle de validation appliquée pour cette demande')
                );
            }
        }
        
        return $buttons;
    }
    
    
    public function renderValidComm() {
        if($this->getData('val_comm_demande') or $this->getData('val_comm_validation')) {
            
            // Demande
            $valid_comm_demande = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm', (int) $this->getData('val_comm_demande'));
            
            if(!$valid_comm_demande->isLoaded())
                $html .= BimpRender::renderAlerts("Erreur lors du chargement de la règle de validation (lors de la demande)", 'danger');
            else {
                if($valid_comm_demande->getData('date_update') > $this->getData('date_create'))
                    $html .= BimpRender::renderAlerts("Cette règle de validation a été éditée après la création de cette demande", 'danger');
                else {
                    $list = new BC_ListTable($valid_comm_demande, 'default', 1, null, "Règle lors de la demande");
                    $list->addFieldFilterValue('id', $valid_comm_demande->id);
                    $html .= $list->renderHtml();
                }
            }
            
            // Validation
            if($this->getData('date_valid')) {
                
                if($this->getData('val_comm_validation') == -1)
                    $html .= BimpRender::renderAlerts("La demande a été validée automatiquement car le compte client ne présente plus de retards de paiement.", 'info');
                elseif($this->getData('val_comm_validation') == -2)
                    $html .= BimpRender::renderAlerts("L'encours du client a été modifié entre la création et la validation de la demande.", 'info');
                elseif($this->getData('val_comm_validation') == self::NO_VAL_COMM) {
                    $html .= BimpRender::renderAlerts("La demande n'a plus lieu d'être (exemple: suppression de remise, retrait de ligne).", 'info');
                } else {
                    $valid_comm_validation = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm', (int) $this->getData('val_comm_validation'));

                    if(!$valid_comm_validation->isLoaded())
                        $html .= BimpRender::renderAlerts("Erreur lors du chargement de la règle de validation (lors de la validation)", 'danger');
                    else {
                        if($valid_comm_validation->getData('date_update') > $this->getData('date_valid'))
                            $html .= BimpRender::renderAlerts("Cette règle de validation a été éditée après la validation de cette demande", 'danger');
                        else {
                            $list = new BC_ListTable($valid_comm_validation, 'default', 1, null, "Règle lors de la validation");
                            $list->addFieldFilterValue('id', $valid_comm_validation->id);
                            $html .= $list->renderHtml();
                        }
                    }
                }
            }

            return $html;
        }
        
        return BimpRender::renderAlerts("Erreur demande/validation", 'danger');
    }
    
}
