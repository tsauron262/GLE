<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

class BT_ficheInter_det extends BimpDolObject
{

    CONST TYPE_INTER = 0;
    CONST TYPE_IMPON = 1;
    CONST TYPE_LIBRE = 2;
    CONST TYPE_DEPLA = 3;
    CONST TYPE_PLUS = 4;
    CONST TYPE_DEPLACEMENT_CONTRAT = 5;
    CONST TYPE_DEPLACEMENT_VENDU = 6;
    CONST MODE_FACT_AUCUN__ = 0;
    CONST MODE_FACT_FORFAIT = 1;
    CONST MODE_FACT_TEMPS_P = 2;

    public static $mode_facturation = [
        self::MODE_FACT_AUCUN__ => [
            'classes' => [
                'important'
            ], 'label'   => "Auncun"
        ],
        self::MODE_FACT_FORFAIT => [
            'classes' => [
                'success'
            ], 'label'   => "Forfait"
        ],
        self::MODE_FACT_TEMPS_P => [
            'classes' => [
                'important'
            ], 'label'   => "Temps passé"
        ]
    ];
    public static $type = [
        self::TYPE_INTER               => [
            'classes' => [
                'success'
            ], 'label'   => "Intervention vendue", 'icon'    => 'check'
        ],
        self::TYPE_DEPLACEMENT_VENDU   => [
            'classes' => [
                'success'
            ], 'label'   => "Déplacement vendu", 'icon'    => 'car'
        ],
        self::TYPE_PLUS                => [
            'classes' => [
                'info'
            ], 'label'   => "Intervention non prévue", 'icon'    => 'plus'
        ],
        self::TYPE_DEPLA               => [
            'classes' => [
                'important'
            ], 'label'   => "Déplacement non prévu", 'icon'    => 'car'
        ],
        self::TYPE_DEPLACEMENT_CONTRAT => [
            'classes' => [
                'success',
            ], 'label'   => "Déplacement sous contrat", 'icon'    => 'car'
        ],
        self::TYPE_IMPON               => [
            'classes' => [
                'danger'
            ], 'label'   => "Impondérable", 'icon'    => 'cogs'
        ],
        self::TYPE_LIBRE               => [
            'classes' => [
                'important'
            ], 'label'   => "Ligne libre", 'icon'    => 'paper-plane'
        ],
    ];
    public $coup_horaire_tech = 0;
    public $lastMargeLine = [];

    public function __construct($module, $object_name)
    {
        $this->coup_horaire_tech = BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique');
        return parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canDelete();
    }

    public function canDelete()
    {
        $parent = $this->getParentInstance();
        if ($parent->getData('fk_statut') == 0) {
            return 1;
        }
        return 0;
    }

    public function canEditField($field_name)
    {
        $parent = $this->getParentInstance();
        if ($parent->getData('fk_statut') == 0)
            return 1;

        switch ($field_name) {
            case "pourcentage_commercial":
                return 1;
                break;
            default:
                return 0;
                break;
        }
    }

    // Getters booléens: 

    public function showOneHoraire()
    {
        if (($this->getData('arrived') && $this->getData('departure')) && $this->getData('type') != 2) {
            return 1;
        }
        return 0;
    }

    public function showTwoHoraire()
    {
        if ($this->getData('arriverd_am') && $this->getData('departure_am') && $this->getData('arriverd_pm') && $this->getData('departure_pm') && $this->getData('type') != 2) {
            return 1;
        }
        return 0;
    }

    public function showTimerDeplacement()
    {
        if ($this->getData('type') == 5 || $this->getData('type') == 3 || $this->getData('type') == 6) {
            return 1;
        }
        return 0;
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();
        $parent = $this->getParentInstance();
        $facturable = ($this->getData('facturable')) ? true : false;

//        if($parent->getData('fk_statut') != 0) {
//            $buttons[] = array(
//                'label' => "Approuver commercialement la prestation",
//                'icon' => 'check',
//                'onclick' => $this->getJsActionOnclick('aprovFacturable', array(), array(
//                ))
//            );
//        }

        if ($parent->getData('fk_statut') == 1) {
            $buttons[] = array(
                'label'   => "Appliquer une remise",
                'icon'    => 'fas_percent',
                'onclick' => $this->getJsActionOnclick('addRemise', array(), array(
                    'form_name' => "addRemise"
                ))
            );
        }

        return $buttons;
    }

    // Getters array: 

    public function getTypeArray()
    {
        $parent = $this->getInstance('bimptechnique', 'BT_ficheInter', $_REQUEST['id']);
        $array_serv_interne = explode(",", BimpCore::getConf('id_societe_auto_terminer', '', 'bimptechnique'));
        $this_soc = $parent->getData('fk_soc');
        if (in_array($this_soc, $array_serv_interne)) {
            return Array(
                $this_soc => Array('label' => "Intervention en interne", 'icon' => "fas_check", 'classes' => Array('success'))
            );
        } else {
            return self::$type;
        }
    }

    // Affichages: 

    public function displayTime($field)
    {
        $t = new DateTime($this->getData($field));
        return($t->format('H:i:s'));
    }

    public function displayValidationCommercial()
    {
        $id = $this->getData('id_set_facturable');
        if ($id) {
            $u = $this->getInstance('bimpcore', 'Bimp_User', $id);
            return $u->getName();
        }
        return "Pas encore validée";
    }

    public function displayTypeInter()
    {
        global $db;
        $parent = $this->getParentInstance();
//        if($this->getData('id_line_commande')) {
//            $orderLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
//            BimpTools::loadDolClass('commande');
//            $orderLine = new OrderLine($db);
//            $orderLine->fetch($this->getData('id_line_commande'));
//            
//            $product = $this->getInstance('bimpcore', 'Bimp_Product', $orderLine->fk_product);
//            
//        }

        if ($this->getData('type') == $parent->getData('fk_soc')) {
            return "<b>" . BimpRender::renderIcon("inbox") . " Service en interne</b>";
        }

        return $this->displaydata('type');
    }

    public function display_service_ref($with_details_commande_line = true)
    {
        $parent = $this->getParentInstance();
        if ($this->getData('id_line_contrat') > 0) {
            $obj = $this->getInstance("bimpcontract", "BContract_contratLine", $this->getData('id_line_contrat'));
            $fk_product = $obj->getData('fk_product');
            $parent = $obj->getParentInstance();
            $element = "Contrat: " . $parent->getLink();
            $valeur = $obj->getData('subprice') * $obj->getData('qty');
        } elseif ($this->getData('id_line_commande') > 0) {
//            BimpTools::loadDolClass('commande', 'commande', 'OrderLine');
            $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $this->getData('id_line_commande'));
            $parent = $obj->getParentInstance();

//            $obj = new OrderLine($this->db->db); $obj->fetch($this->getData('id_line_commande'));
//            $parent = new Commande($this->db->db);
//            $parent->fetch($obj->fk_commande);
            $valeur = $obj->getTotalHt(1);
//            die('p'.$obj->printData().print_r($obj,1));
            $fk_product = $obj->id_product;
            $element = "Commande: " . $parent->getLink();
        } else {
            $fk_product = 0;
        }
        if ($fk_product > 0) {
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $fk_product);
            $productName = $product->getNomUrl();
            if ($this->getData('type') == 6) {
                $productName = "<b class='success' >" . BimpRender::renderIcon("car") . " Déplacement vendu</b>";
            }
            if ($with_details_commande_line)
                return $productName . '<br /><strong>' . $element . '</strong><br /><strong>Vendu: ' . price($valeur) . '€ HT</strong>';
            else
                return $productName;
        } else {
            if ($this->getData('type') == $parent->getData('fk_soc')) {
                $interne = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));
                return "<b>" . BimpRender::renderIcon('inbox') . " Service en interne</b><br /><p>Société: " . $interne->getNomUrl() . "</p>";
            }
            if ($this->getData('type') == 5) {
                $render = "<b class='success'>" . BimpRender::renderIcon("car") . " Déplacement sous contrat</b><br />";
                $obj = $this->getInstance("bimpcontract", "BContract_contrat", $parent->getData('fk_contrat'));
                $render .= "<b>Contrat: " . $obj->getRef() . "</b><br /><b>Total vendu: " . price($obj->getTotalContrat()) . "€</b>";

                return $render;
            }
            return $this->displayData('type');
        }
    }

    public function displayFacturable()
    {
        if ($this->getData('id_set_facturable') > 0) {
            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('id_set_facturable'));
            $par = $commercial->getName();
        } else {
            $par = "Pas encore validée";
        }
        return $par;
    }

    public function displayClient()
    {
        $parent = $this->getParentInstance();
        $client = $this->getInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));
        $card = new BC_Card($client);
        return $card->renderHtml();
    }

    public function displayDuree()
    {
        $t = $this->getData('duree');
        $parent = $this->getParentInstance();
        return $parent->timestamp_to_time($t);
    }

    public function displayDateWithDetails()
    {
        $return = "";

        $date = new DateTime($this->getData('date'));
        $return .= "<b>" . $date->format('d / m / Y') . "</b>";

        $startStopUnique = [];
        $startStopX4 = [];

        if ($this->getData('arrived') && $this->getData('departure')) {
            $start = new DateTime($this->getData('arrived'));
            $stop = new DateTime($this->getData('departure'));
            $popover = $start->format("H:i") . " " . BimpRender::renderIcon('arrow-right') . " " . $stop->format("H:i");
        } elseif ($this->getData('arriverd_am')) {
            $start_am = new DateTime($this->getData('arriverd_am'));
            $start_pm = new DateTime($this->getData('arriverd_pm'));
            $stop_am = new DateTime($this->getData('departure_am'));
            $stop_pm = new DateTime($this->getData('departure_pm'));
            $popover = "AM: " . $start_am->format("H:i") . " " . BimpRender::renderIcon('arrow-right') . " " . $stop_am->format("H:i") . "<br />";
            $popover .= "PM: " . $start_pm->format("H:i") . " " . BimpRender::renderIcon('arrow-right') . " " . $stop_pm->format("H:i");
        }
        $excludeTypeInfos = Array(2, 3, 5, 6);

        if (!in_array($this->getData('type'), $excludeTypeInfos))
            $return .= " <small class='bs-popover' " . BimpRender::renderPopoverData($popover, 'top', true) . " >" . BimpRender::renderIcon('info-circle') . "</small>";

        return $return;
    }

    public function getDescRapide()
    {
        $html = "";

        if ($this->getData('description') && $this->getData('description') != "<br>") {

            $html .= "<h6 class='bs-popover' " . BimpRender::renderPopoverData($this->getData('description'), 'top', true) . " ><b class='success'>" . BimpRender::renderIcon("check") . "</b> Survoler pour voir la description</h6>";
        } else {
            $html .= "<b class='danger'>" . BimpRender::renderIcon("times") . "</b>" . " Il n'y a pas de description";
        }

        return $html;
    }

    // Rendus HTML: 
     
    public function renderDescription()
    {
        if ($this->getData('description')) {
            return "<h4>Description de l'intervention</h4><p>" . $this->getData('description') . '</p>';
        }
        
        return BimpRender::renderAlerts("Il n'y a pas de description pour cette ligne", "warning", false);
    }
    
    // Traitements: 

    protected function updateDolObject(&$errors = array(), &$warnings = Array())
    {

        $data = new stdClass();

        $data->description = BimpTools::getPostFieldValue("description");

        if (BimpTools::getPostFieldValue('date')) {
            $data->date = BimpTools::getValue("date");
        } else {
            $errors[] = "Vous devez choisir une date";
        }

        if (!count($errors)) {
            $total_hours = 0;
            if (BimpTools::getPostFieldValue("duree")) {
                $total_hours = BimpTools::getValue('duree');
            }
            
            if (BimpTools::getPostFieldValue("arrived") && BimpTools::getPostFieldValue("departure")) {
                $data->arrived = $data->date . " " . BimpTools::getValue("arrived");
                $data->departure = $data->date . " " . BimpTools::getValue("departure");
                $total_hours = strtotime(BimpTools::getValue("departure")) - strtotime(BimpTools::getValue("arrived"));
            } elseif (BimpTools::getPostFieldValue("arriverd_am") && BimpTools::getPostFieldValue("departure_am") && BimpTools::getPostFieldValue("arriverd_pm") && BimpTools::getPostFieldValue("departure_pm")) {
                $total_hous_am = 0;
                $total_hours_pm = 0;
                $data->arriverd_am = $data->date . " " . BimpTools::getValue("arriverd_am");
                $data->departure_am = $data->date . " " . BimpTools::getValue("departure_am");
                $total_hous_am = strtotime(BimpTools::getValue("departure_am")) - strtotime(BimpTools::getValue("arriverd_am"));
                $data->arriverd_pm = $data->date . " " . BimpTools::getValue("arriverd_pm");
                $data->departure_pm = $data->date . " " . BimpTools::getValue("departure_pm");
                $total_hours_pm = strtotime(BimpTools::getValue("departure_pm")) - strtotime(BimpTools::getValue("arriverd_pm"));
                $total_hours = $total_hous_am + $total_hours_pm;
            }
            if (($total_hours == 0 || $total_hours < 60) && $this->getData('type') != 2) {
                $errors[] = "Il semble y avoir une erreur dans vos horaires. La durée minimal de l'intervention est de 1 minute = " . $total_hours;
            } else {
                $data->duree = $total_hours;
            }
        }

        if (!count($errors)) {
            foreach ($data as $field => $newValue) {
                $this->updateField($field, $newValue);
            }
            $tt = $this->db->getSum('fichinterdet', 'duree', 'fk_fichinter = ' . $this->getData('fk_fichinter'));
            $parent = BimpCache::getBimpObjectInstance('bimptechnique', "BT_ficheInter", $this->getData('fk_fichinter'));
            $parent->set('duree', $tt);
            $parent->update();
        }

        return Array(
            "success"  => "",
            "errors"   => $errors,
            'warnings' => $warnings
        );
    }

    public function deleteDolObject(&$errors)
    {
        global $user;
        if ($this->dol_object->deleteLine($user) > 0) {
            $callback = "window.location.href = '" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $this->id . "'";
            return ['success_callback' => $callback];
        }
    }

    // Actions: 

    public function actionAddRemise($data, &$success = '')
    {
        $errors = [];
        $warnings = [];
        $errors = $this->updateField('pourcentage_commercial', $data["pourcentage_commercial"]);

        if (!count($errors)) {
            $success_callback = 'window.location.href = "' . DOL_URL_ROOT . '/bimptechnique/index.php?fc=fi&id=' . $this->getParentId() . '"';
        }

        return [
            "errors"           => $errors,
            "warnings"         => $warnings,
            "success_callback" => $success_callback
        ];
    }

    public function actionChangeFacturable($data, &$success = '')
    {
        global $user;
        $errors = [];
        $warnings = [];
        if ($this->getData('facturable')) {
            $errors = $this->updateField('facturable', 0);
        } else {
            $errors = $this->updateField('facturable', 1);
        }
        if (!count($errors)) {
            $errors = $this->updateField('id_set_facturable', $user->id);
            $success = "Mis à jour avec succès";
        }
        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionAprovFacturable($data, &$success = '')
    {
        global $user;
        $errors = [];
        $warnings = [];

        $errors = $this->updateField('id_set_facturable', $user->id);
        $success = "Mis à jour avec succès";

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    // Outils: 

    public function time_to_qty($time)
    {
        $timeArr = explode(':', $time);
        if (count($timeArr) == 3) {
            $decTime = ($timeArr[0] * 60) + ($timeArr[1]) + ($timeArr[2] / 60);
        } else if (count($timeArr) == 2) {
            $decTime = ($timeArr[0]) + ($timeArr[1] / 60);
        } else if (count($timeArr) == 2) {
            $decTime = $time;
        }
        return $decTime;
    }
    
//    public function getTotalLineSell($type_line) {
//        switch($type_line) {
//            case 'commande':
//                BimpTools::loadDolClass('commande');
//                $line = new OrderLine($this->db->db);
//                $line->fetch($this->getData('id_line_commande'));
//                return $line->subprice * $line->qty;
//                break;
//            case 'contrat':
//                $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
//                return $line->getData('subprice') * $line->getData('qty');
//                break;
//            default:
//                return 0;
//                break;
//        }
//    }
//    public function displaySurplusFacturation() {
//        $html = "";
//        
//        $surplus = $this->getSurplusFacturationHt();
//        $html .= $surplus . "€";
//        
//        return $html;
//    }
//    public function getSurplusFacturationHt($avecPourcenatge = true) {
//        $parent  = $this->getParentInstance();
//        $array = [];
//        
//        $porcentage = $this->getData('pourcentage_commercial');
//        $product = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Product");
//        if($this->getData('id_line_commande')) {
//            // Service d'une commande
//            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $this->getData('id_line_commande'));
////            $line = new OrderLine($this->db->db);
////            $line->fetch($this->getData('id_line_commande'));
//            $product->fetch($line->fk_product);
//            $tarif = $product->getData('price');
//            $vendu = $line->total_ht;
//            $qty_in_commande = $line->qty;
//            $tms = $parent->timestamp_to_time($this->getData('duree'));
//            $qty = $parent->time_to_qty($tms);
//            
//            if($avecPourcenatge) {
//                if($qty > $qty_in_commande) {
//                    $surplus = ($qty*$tarif) - ($vendu);
//                    $surplus_pourcentage = ($porcentage * $surplus) / 100;
//                    return $surplus - $surplus_pourcentage;
//                }
//            } else {
//                return ($qty*$tarif) - ($vendu);
//            }
//
//        }
//        
//        return 0;
//        
//    }
//    public function display_total($search = 'HT') {
//        return print_r($this->getTotal(), 1) ;
//    }
//    public function getTotal() {
//        $mode_facturation = $this->getData('forfait');
//        $price = Array();
//        if($mode_facturation == 0){ // Aucune
//            return "0";
//        } else {
//            if($this->getData('id_line_commande') || $this->getData('id_line_contrat')) {
//                if($this->getData('id_line_commande')) {
//                    BimpTools::loadDolClass('commande');
//                    $obj = new OrderLine($this->db->db);
//                    $obj->fetch($this->getData('id_line_commande'));
//                    
//                } elseif($this->getData('id_line_contrat')) {
//                    $obj = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
//                }
//                $product = $this->getInstance('bimpcore', 'Bimp_Product', $fk_product);
//                if($mode_facturation == 1) { // Forfait
//                    //$price['HT'] = 
//                }
//                return $price;
//            } else {
//                return 'NULL';
//            }
//        }
//    }
}
