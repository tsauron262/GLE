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
    
    const LIMIT_DEMANDE = 10;
    
    public function canDelete() {
        global $user;
        
        if((int) $user->admin)
            return 1;
        
        return 0;
    }
    
    public function displayObject() {
        
        $html = '';
        $obj = (int) $this->getData('object');
        $id_obj = (int) $this->getData('id_object');
        if (!is_null($id_obj)) {
            switch ($obj) {
                case self::OBJ_DEVIS:
                    $devis = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_obj);
                    $html .= $devis->getNomUrl();
                    break;

                case self::OBJ_FACTURE:
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_obj);
                    $html .= $facture->getNomUrl();
                    break;
                
                case self::OBJ_COMMANDE:
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_obj);
                    $html .= $commande->getNomUrl();
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
        
//        if((int) $this->getData('id_user_affected') == (int) $user->id)
//            return $errors;
        
        switch ($this->getData('object')) {
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
        
        $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', $class, (int) $this->getData('id_object'));

        $task = BimpObject::getInstance("bimptask", "BIMP_Task");
        $tasks = $task->getList(array('test_ferme' => $this->getTestFerme()));
        if (count($tasks) == 0) {
            $data = array(
                "src"  => $user->email,
                "dst"  => "validationcommande@bimp-goupe.net",
                "subj" => "Validation commande " . $bimp_object->getData('ref'),
                "txt"  => "Merci de valider " . $bimp_object->getLabel('the')
                    . ' ' . $bimp_object->getNomUrl(1),
                "id_user_owner"  => $this->getData('id_user_affected'),
                "test_ferme" => $this->getTestFerme());
            $errors = BimpTools::merge_array($errors, $task->validateArray($data));
            $errors = BimpTools::merge_array($errors, $task->create());
        }
        
        return $errors;

        // Version basée sur les table des object
//        switch ($this->getData('object')) {
//            case self::OBJ_DEVIS:
//                $table = 'propal';
//                break;
//            case self::OBJ_FACTURE:
//                $table = 'facture';
//                break;
//            case self::OBJ_COMMANDE:
//                $table = 'commande';
//                break;
//            default:
//                $errors[] = "Type d'objet non reconnu ";
//                break;
//        }
//
//        $task = BimpObject::getInstance("bimptask", "BIMP_Task");
//        $test = $table . ":rowid=" . $this->getData('id') . " && fk_statut>0";
//        $tasks = $task->getList(array('test_ferme' => $test));
//        if (count($tasks) == 0) {
//            $tab = array(
//                "src"  => $user->email,
//                "dst"  => "validationcommande@bimp-goupe.net",
//                "subj" => "Validation commande " . $order->ref,
//                "txt"  => "Merci de valider la commande " . $order->getNomUrl(1),
//                "test_ferme" => $test);
//            $errors = BimpTools::merge_array($errors, $task->validateArray($tab));
//            $errors = BimpTools::merge_array($errors, $task->create());
//        }        
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
        if(is_a($task, 'BIMP_Task'))
            return $task->delete();
//        $warnings[] = 'Aucune tâche supprimée';
//        else
//            return array('Aucune tache ne correspond à ' . $this->getTestFerme());
    }
}