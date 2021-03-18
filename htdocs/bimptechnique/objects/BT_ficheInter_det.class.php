<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

class BT_ficheInter_det extends BT_ficheInter {
    
    CONST TYPE_INTER = 0;
    CONST TYPE_IMPON = 1;
    CONST TYPE_LIBRE = 2;
    CONST TYPE_DEPLA = 3;
    CONST TYPE_PLUS = 4;
    CONST TYPE_DEPLACEMENT_CONTRAT = 5;
    CONST MODE_FACT_AUCUN__ = 0;
    CONST MODE_FACT_FORFAIT = 1;
    CONST MODE_FACT_TEMPS_P = 2;
    
    public static $mode_facturation = [
        self::MODE_FACT_AUCUN__ => [
            'classes' => [
                'important'
            ], 'label' => "Auncun"
        ],
        self::MODE_FACT_FORFAIT => [
            'classes' => [
                'success'
            ], 'label' => "Forfait"
        ],
        self::MODE_FACT_TEMPS_P => [
            'classes' => [
                'important'
            ], 'label' => "Temps passé"
        ]
    ];


    public static $type = [
        self::TYPE_INTER => [
            'classes' => [
                'success'
            ], 'label' => "Intervention vendue", 'icon' => 'check'
        ],
        self::TYPE_IMPON => [
            'classes' => [
                'danger'
            ], 'label' => "Impondérable", 'icon' => 'cogs'
        ],
        self::TYPE_LIBRE => [
            'classes' => [
                'important'
            ], 'label' => "Ligne libre", 'icon' => 'paper-plane'
        ],
        self::TYPE_DEPLA => [
            'classes' => [
                'important'
            ], 'label' => "Déplacement non vendu", 'icon' => 'car'
        ],
        self::TYPE_PLUS => [
            'classes' => [
                'info'
            ], 'label' => "Intervention non prévue", 'icon' => 'plus'
        ],
        self::TYPE_DEPLACEMENT_CONTRAT => [
            'classes' => [
                'important',
            ], 'label' => "Déplacement sous contrat", 'icon' => 'car'
        ]
    ];
    
    public $coup_horaire_tech = 0;
    public $ref_deplacement = "";
    public $lastMargeLine = [];
    
    
    public function __construct($module, $object_name) {
        $this->coup_horaire_tech = BimpCore::getConf('bimptechnique_coup_horaire_technicien');
        $this->ref_deplacement = BimpCore::getConf('bimptechnique_ref_deplacement');
        return parent::__construct($module, $object_name);
    }
    
    public function getDescRapide() {
        $html = "";
        
        if($this->getData('description') && $this->getData('description') != "<br>") {
            
            $html .= "<h6 class='bs-popover' ".BimpRender::renderPopoverData(html_entity_decode(str_replace('<br>', "\n", $this->getData('description'))))." ><b class='success'>" . BimpRender::renderIcon("check") . "</b> Survoler pour voir la description</h6>";
            
        } else {
            $html .= "<b class='danger'>" . BimpRender::renderIcon("times") . "</b>" . " Il n'y a pas de description";
        }
        
        return $html;
    }
    
    public function getTypeArray() {
        $parent = $this->getInstance('bimptechnique', 'BT_ficheInter', $_REQUEST['id']);
        $array_serv_interne = explode(",", BimpCore::getConf('bimptechnique_id_societe_auto_terminer'));
        $this_soc = $parent->getData('fk_soc');
        if(in_array($this_soc, $array_serv_interne)) {
            return Array(
                $this_soc => Array('label' => "Intervention en interne", 'icon' => "fas_check", 'classes' => Array('success'))
            );
        } else {
            return self::$type;
        }
    }
    
    
    public function getTotalLineSell($type_line) {
        switch($type_line) {
            case 'commande':
                BimpTools::loadDolClass('commande');
                $line = new OrderLine($this->db->db);
                $line->fetch($this->getData('id_line_commande'));
                return $line->subprice * $line->qty;
                break;
            case 'contrat':
                $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
                return $line->getData('subprice') * $line->getData('qty');
                break;
            default:
                return 0;
                break;
        }
    }
    
    public function canCreate() {
        return 1;
    }
    
    public function displayValidationCommercial() {
        $id = $this->getData('id_set_facturable');
        if($id) {
            $u = $this->getInstance('bimpcore', 'Bimp_User', $id);
            return $u->getName();
        }
        return "Pas encore validée";
        
    }
    
    public function displayTypeInter() {
        global $db;
        if($this->getData('id_line_commande')) {
            BimpTools::loadDolClass('commande');
            $orderLine = new OrderLine($db);
            $orderLine->fetch($this->getData('id_line_commande'));
            
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $orderLine->fk_product);
            if($product->getRef() == BimpCore::getConf("bimptechnique_ref_deplacement")) {
                return "<strong class='important' >".BimpRender::renderIcon('car')." Déplacement</strong>";
            }
        }
        
        return $this->displaydata('type');
    }
    
    public function canDelete() {
        $parent = $this->getParentInstance();
        if($parent->getData('fk_statut') == 0) {
            return 1;
        }
        return 0;
    }
    
    public function canEdit() {
        return $this->canDelete();
    }
    
    public function display_service_ref() {
        if($this->getData('id_line_contrat') > 0) {
            $obj = $this->getInstance("bimpcontract", "BContract_contratLine", $this->getData('id_line_contrat'));
            $fk_product = $obj->getData('fk_product');
            $parent = $obj->getParentInstance();
            $element = "Contrat: " . $parent->getData('ref');
            $valeur = $obj->getData('subprice') * $obj->getData('qty');
        }elseif($this->getData('id_line_commande') > 0) {
            BimpTools::loadDolClass('commande', 'commande', 'OrderLine');
            $obj = new OrderLine($this->db->db); $obj->fetch($this->getData('id_line_commande'));
            $parent = new Commande($this->db->db);
            $parent->fetch($obj->fk_commande);
            $valeur = $obj->subprice * $obj->qty;
            $fk_product = $obj->fk_product;
            $element = "Commande: " . $parent->ref;
        } else {
            $fk_product = 0;
        }
        if($fk_product > 0) {
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $fk_product);
            return $product->getNomUrl() . '<br /><strong>'.$element.'</strong><br /><strong>Vendu: ' . price($valeur) . '€ HT</strong>';
        } else {
            return $this->displayData('type');
        }

    }
    
    public function displayFacturable() {
        if($this->getData('id_set_facturable') > 0) {
            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('id_set_facturable'));
            $par = $commercial->getName();
        } else {
            $par = "Pas encore validée";
        }
        return $par;
    }

    public function deleteDolObject(&$errors) {
        global $user;
        if($this->dol_object->deleteLine($user) > 0) {
            return ['success' => 'Ligne de la FI supprimée avec succès'];
        }
    }
    
    public function getListExtraButtons() {
        global $conf, $langs, $user;
        $buttons = Array();
        $parent = $this->getParentInstance();
        $facturable = ($this->getData('facturable')) ? true : false;
        
        if($parent->getData('fk_statut') != 0) {
            $buttons[] = array(
                'label' => "Approuver commercialement la prestation",
                'icon' => 'check',
                'onclick' => $this->getJsActionOnclick('aprovFacturable', array(), array(
                ))
            );
        }
        
        
        return $buttons;
    }
    
    public function actionChangeFacturable($data, &$success) {
        global $user;
        $errors = [];
        $warnings = [];
        if($this->getData('facturable')) {
            $errors = $this->updateField('facturable', 0);
        } else {
            $errors = $this->updateField('facturable', 1);
        }
        if(!count($errors)) {
            $errors = $this->updateField('id_set_facturable', $user->id);
            $success = "Mis à jour avec succès";
        }
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
    }
    
     public function actionAprovFacturable($data, &$success) {
        global $user;
        $errors = [];
        $warnings = [];
        
        $errors = $this->updateField('id_set_facturable', $user->id);
        $success = "Mis à jour avec succès";
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
    }
    
    public function displayClient() {
        $parent = $this->getParentInstance();
        $client = $this->getInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));
        $card = new BC_Card($client);
        return $card->renderHtml();
    }
    
    public function displayDuree() {
        $t = $this->getData('duree');
        $parent = $this->getParentInstance();
        return $parent->timestamp_to_time($t);
    }
    
    public function time_to_qty($time) {
        $timeArr = explode(':', $time);
        if (count($timeArr) == 3) {
                $decTime = ($timeArr[0]*60) + ($timeArr[1]) + ($timeArr[2]/60);		
        } else if (count($timeArr) == 2) {
                $decTime = ($timeArr[0]) + ($timeArr[1]/60);
        } else if (count($timeArr) == 2) {
                $decTime = $time;	
        }
        return $decTime;
    }
    
    public function renderDescription() {
        if($this->getData('description')) {
            return "<h4>Description de l'intervention</h4><p>" . $this->getData('description') . '</p>';
        } else {
            return BimpRender::renderAlerts("Il n'y a pas de description pour cette ligne", "danger", false);
        }
    }
    
    public function display_total($search = 'HT') {
        return print_r($this->getTotal(), 1) ;
    }
    
    public function getTotal() {
        $mode_facturation = $this->getData('forfait');
        $price = Array();
        if($mode_facturation == 0){ // Aucune
            return "0";
        } else {
            if($this->getData('id_line_commande') || $this->getData('id_line_contrat')) {
                if($this->getData('id_line_commande')) {
                    BimpTools::loadDolClass('commande');
                    $obj = new OrderLine($this->db->db);
                    $obj->fetch($this->getData('id_line_commande'));
                    
                } elseif($this->getData('id_line_contrat')) {
                    $obj = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
                }
                $product = $this->getInstance('bimpcore', 'Bimp_Product', $fk_product);
                if($mode_facturation == 1) { // Forfait
                    //$price['HT'] = 
                }
                return $price;
            } else {
                return 'NULL';
            }
        }
    }
}