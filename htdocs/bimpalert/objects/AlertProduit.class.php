<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class AlertProduit extends BimpObject
{

    // Type de pièce
    const TYPE_DEVIS = 0;
    const TYPE_FACTURE = 1;
    const TYPE_COMMANDE = 2;
    const TYPE_CONTRAT = 3;
    static $warnings = array();
    static $errors = array();
    
    const TYPE_DEVIS_LIGNE = 101;

    public static $type_piece = Array(
        self::TYPE_DEVIS       => Array('label' => 'Devis',   'icon' => 'fas_file-invoice',        'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Propal',       'table' => 'propal'),
        self::TYPE_DEVIS_LIGNE => Array('label' => 'Ligne de Devis',   'icon' => 'fas_file-invoice',        'module' => 'bimpcommercial', 'obj_name' => 'Bimp_PropalLine',       'table' => 'propaldet'),
        self::TYPE_FACTURE     => Array('label' => 'Facture', 'icon' => 'fas_file-invoice-dollar', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Facture',      'table' => 'facture'),
        self::TYPE_COMMANDE    => Array('label' => 'Commande', 'icon' => 'fas_dolly',              'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Commande',     'table' => 'commande'),
        self::TYPE_CONTRAT     => Array('label' => 'Contrat',  'icon' => 'fas_retweet',            'module' => 'bimpcontract',   'obj_name' => 'BContract_contrat', 'table' => 'contrat'),
    );
    

    // Type de pièce
    const ACTION_CREATE     = 'CREATE';
    const ACTION_VALIDATE   = 'VALIDATE';
    const ACTION_UNVALIDATE = 'UNVALIDATE';
    const ACTION_DELETE     = 'DELETE';

    public static $type_action = Array(
        self::ACTION_CREATE     => Array('label' => 'Création',     'classes' => array('info'),    'icon' => 'fas_plus'),
        self::ACTION_VALIDATE   => Array('label' => 'Validation',   'classes' => array('success'), 'icon' => 'fas_check'),
        self::ACTION_UNVALIDATE => Array('label' => 'Dévalidation', 'classes' => array('danger'),  'icon' => 'fas_undo'),
        self::ACTION_DELETE     => Array('label' => 'Suppression',  'classes' => array('danger'),  'icon' => 'fas_trash'),
    );
    
    public static $type_notif = array(
        0   => 'Message',
        1   => 'Warnings',
        2   => 'Erreur'
    );
    
    // charge toutes les alerte active de ce type d'objet et ce type de trigger
    // et appel traiteAlerte sur chaque instance
    public static function traiteAlertes($object, $name_trigger, $errors, $warnings) {
        
        
        $id_type = null;
        foreach(self::$type_piece as $k => $t) {
            if($t['obj_name'] == $object->object_name)
                $id_type = $k;
        }

        
        if(isset($id_type)) {
            $alerts = BimpCache::getBimpObjectObjects ('bimpalert', 'AlertProduit', array('type_piece' => $id_type, 'type_action' => $name_trigger));

            foreach($alerts as $a) {
                $a->traiteAlerte($object, $errors, $warnings);
            }
        }

        return 1;
    }
    
    
    // Appel isObjectQualified($object) si oui créer un note sur l'objet en question
    public function traiteAlerte($object, &$errors = array(), &$warnings = array()) {
        
        if($this->isObjectQualified($object)){
            if($this->getData('type_notif') == 0){
                $this->sendMessage($object, $errors, $warnings);
            }
            else{
                $this->sendAlert($errors, $warnings);
            }
        }
        
    }
    
    // qui test est renvoie vrai ou faux
    public function isObjectQualified($object) {
        
        $filtre = $this->getData('filtre_piece');
        
        if(!isset($filtre[$object->getPrimary()]['values'])) {
            $filtre[$object->getPrimary()]['values'] = array();
        }
        
        $filtre[$object->getPrimary()]['values'][] = array(
            'value' => $object->id,
            'part_type' => 'full'
        );
//        echo get_class($object).'<pre>';
//        print_r($filtre);die;
        return count(BC_FiltersPanel::getObjectListIdsFromFilters($object, $filtre));
    }
    
    public function getObjectInfo($key) {
        $type = $this->getData('type_piece');
        if(BimpTools::getValue('type_piece')) {
            $type = BimpTools::getValue('type_piece');
        }
        
        return self::$type_piece[$type][$key];
    }
    
    public function sendMessage($object, &$errors = array(), &$warnings = array()) {
        BimpObject::loadClass('bimpcore', 'BimpNote');

        // Création des note user
        foreach ($this->getData('notified_user') as $id_user) {
                $object->addNote($this->getData('message_notif'),
                               BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                               BimpNote::BN_DEST_USER, 0, (int) $id_user);
        }
        
        
        
        // Création des note user
        foreach ($this->getData('notified_group') as $id_group) {
                $object->addNote($this->getData('message_notif'),
                               BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                               BimpNote::BN_DEST_GROUP, (int) $id_group, 0);
        }
        
    }
    
    public function sendAlert(&$errors = array(), &$warnings = array()) {
        BimpObject::loadClass('bimpcore', 'BimpNote');

        if($this->getData('type_notif') == 1)
            static::$warnings[] = $this->getData('message_notif');
        elseif($this->getData('type_notif') == 2)
            static::$errors[] = $this->getData('message_notif');
        
    }
    
    public static function getAlertes(&$errors, &$warnings){
        $errors = BimpTools::merge_array($errors, static::$errors);
        $warnings = BimpTools::merge_array($warnings, static::$warnings);
    }
}
