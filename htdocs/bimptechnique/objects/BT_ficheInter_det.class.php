<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BT_ficheInter_det extends BimpDolObject
{

    CONST TYPE_INTER = 0;
    CONST TYPE_IMPON = 1;
    CONST TYPE_LIBRE = 2;
    CONST TYPE_DEPLA = 3;
    CONST TYPE_PLUS = 4;
    CONST TYPE_DEPLACEMENT_CONTRAT = 5;
    CONST TYPE_DEPLACEMENT_VENDU = 6;
    const TYPE_INTERNE = 7;

    public static $types = [
        self::TYPE_INTER               => ['label' => "Intervention vendue", 'icon' => 'fas_check', 'classes' => ['success']],
        self::TYPE_DEPLACEMENT_VENDU   => ['label' => "Déplacement vendu", 'icon' => 'fas_car', 'classes' => ['success']],
        self::TYPE_PLUS                => ['label' => "Intervention non vendue", 'icon' => 'fas_plus', 'classes' => ['warning']],
        self::TYPE_DEPLA               => ['label' => "Déplacement non vendu", 'icon' => 'fas_car', 'classes' => ['warning']],
        self::TYPE_DEPLACEMENT_CONTRAT => ['label' => "Déplacement sous contrat", 'icon' => 'fas_car', 'classes' => ['success']],
        self::TYPE_IMPON               => ['label' => "Impondérable", 'icon' => 'fas_cogs', 'classes' => ['important']],
        self::TYPE_LIBRE               => ['label' => "Ligne libre", 'icon' => 'fas_paper-plane', 'classes' => ['info']],
        self::TYPE_INTERNE             => ['label' => "Intervention en interne", 'icon' => 'fas_inbox', 'classes' => ['success']]
    ];

    CONST MODE_FACT_AUCUN__ = 0;
    CONST MODE_FACT_FORFAIT = 1;
    CONST MODE_FACT_TEMPS_P = 2;

    public static $modes_facturation = [
        self::MODE_FACT_AUCUN__ => ['label' => 'Aucun', 'icon' => 'fas_times'],
        self::MODE_FACT_FORFAIT => ['label' => 'Forfait', 'icon' => 'fas_euro-sign'],
        self::MODE_FACT_TEMPS_P => ['label' => 'Temps passé', 'icon' => 'fas_clock']
    ];

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = [])
    {
        $fi = $this->getParentInstance();

        if (BimpObject::objectLoaded($fi)) {
            if ((int) $fi->getData('fk_statut') !== 0) {
                $errors[] = 'la fiche inter n\'est plus au statut brouillon';
                return 0;
            }

            return 1;
        }

        $errors[] = 'Fiche Inter absente';
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        $fi = $this->getParentInstance();

        if (BimpObject::objectLoaded($fi)) {
            if ((int) $fi->getData('fk_statut') !== 0) {
                return 0;
            }
        }

        return 1;
    }
    
    public function canEditField($field_name) {
        
        global $user;
        switch($field_name) {
            case 'forfait':
                return (int) $user->rights->bimptechnique->modif_apres_validation;
        break;
    
        }
        
        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        global $user;
        $fi = $this->getParentInstance();

        if (BimpObject::objectLoaded($fi)) {
            if ((int) $fi->getData('fk_statut') === 0) {
                return 1;
            }
            
            if((int)$user->rights->bimptechnique->modif_apres_validation && (int) $fi->getData('fk_statut') != 0) {
                return 1;
            }

            if (in_array($field, array('pourcentage_commercial'))) {
                return 1;
            }
            
            return 0;
        }

        return 1;
    }

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
        if (in_array((int) $this->getData('type'), array(5, 3, 6))) {
            return 1;
        }

        return 0;
    }
    
    
    
    public function isAmPm()
    {
        if (!$this->isLoaded() || (string) $this->getData('arrived') || (string) $this->getData('departure')) {
            return 0;
        }

        return 1;
    }

    // Getters params:

    public function getListExtraButtons()
    {
        $buttons = Array();

//        if ($parent->getData('fk_statut') == 1) {
//            $buttons[] = array(
//                'label'   => "Appliquer une remise",
//                'icon'    => 'fas_percent',
//                'onclick' => $this->getJsActionOnclick('addRemise', array(), array(
//                    'form_name' => "addRemise" // Formulaire non fonctionnel. 
//                ))
//            );
//        }
        
        //if($this->getData(''))
        
        return $buttons;
    }

    public function getNameProperties()
    {
        return array();
    }

    public function getName($withGeneric = true)
    {
        return 'Inter #' . $this->id;
    }

    // Getters Array:

    public function getInputTypesArray()
    {
        $fi = $this->getParentInstance();

        if (BimpObject::objectLoaded($fi)) {
            $array_serv_interne = explode(",", BimpCore::getConf('bimptechnique_id_societe_auto_terminer', ''));
            if (in_array((int) $fi->getData('fk_soc'), $array_serv_interne)) {
                return Array(
                    (int) self::TYPE_INTERNE => self::$types[self::TYPE_INTERNE]
                );
            }
        }

        $types = self::$types;
        unset($types[self::TYPE_INTERNE]);

        return $types;
    }

    public function getCommandesWithDeplacementArray()
    {
        $array = [];
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            $commandes = $parent->getData('commandes');

            if (is_array($commandes)) {
                foreach ($commandes as $id_commande) {
                    $commande = BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_Commande", $id_commande);
                    if (BimpObject::objectLoaded($commande)) {
                        $lines = $commande->getLines('not_text');
                        foreach ($lines as $line) {
                            if (!(int) $line->id_product) {
                                continue;
                            }

                            $product = $line->getProduct();

                            if (BimpObject::objectLoaded($product)) {
                                if ($product->isDep()) {
                                    $line_inters = $this->getChildrenList("inters", ['id_line_commande' => (int) $line->id]);
                                    if (!count($line_inters)) {
                                        $array[$id_commande] = $commande->getRef() . " - " . $commande->getData('libelle');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $array;
    }

    public function getServicesArray()
    {
        $fi = $this->getParentInstance();

        if (!BimpObject::objectLoaded($fi)) {
            return array();
        }

        $services = [];
        $tp = [];

        foreach (explode(',', BimpCore::getConf('bimptechnique_ref_temps_passe', '')) as $code) {
            $tp[$code] = "Temps passé de niveau " . substr($code, -1, 1);
        }

        $fi_commandes = $fi->getData('commandes');

        if (is_array($fi_commandes) && !empty($fi_commandes)) {
            foreach ($fi_commandes as $id_commande) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                if (BimpObject::objectLoaded($commande)) {
                    $lines = $commande->getLines('not_text');

                    foreach ($lines as $line) {
                        if ((int) $line->id_product) {
                            //$product = $line->getProduct(); // Supprimé sinon les  produits à 0 ne sortent pas (On en a quand même besoin)
                            $product  = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Product", $line->id_product);
                            if (BimpObject::objectLoaded($product)) {
                                if (!$product->isDep() && $product->isTypeService()) {
                                    if (array_key_exists($product->getRef(), $tp)) {
                                        $services['commande_' . $line->id] = $tp[$product->getRef()] . ' (' . price($line->getTotalHT(true)) . ' € HT) - <b>' . $commande->getRef() . '</b> <br />' . $line->desc;
                                    } elseif ($product->getData('price') != 0) {
                                        $services['commande_' . $line->id] = $product->getRef() . ' (' . price($line->getTotalHT(true)) . ' € HT) - <b>' . $commande->getRef() . '</b> <br />' . $line->desc;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ((int) $fi->getData('fk_contrat')) {
            $contrat = $fi->getChildObject('contrat');

            foreach ($contrat->dol_object->lines as $line) {
                $bimpline = $contrat->getChildObject('lines', $line->id);
                if ($bimpline->getData('product_type') == 1 && $bimpline->getData('statut') == 4) {
                    $services['contrat_' . $line->id] = 'Intervention sous contrat (' . price($bimpline->getData('total_ht')) . '€) - <strong>' . $contrat->getRef() . '</strong> - ' . $line->description;
                }
            }
        }

        return $services;
    }
    
    private function getTypePlanningCode():int {
        
        $code = "";
        $parent = $this->getParentInstance();
        $fk_soc = (int) $parent->getData('fk_soc');
        $type = (int) $this->getData('type');
        
        switch ($this->getData('type')) {
            case 0:
            case 4:
                $code = "INTER";
                break;
            case 1:
            case 2:
                $code = "AC_OTH";
                break;
            case self::TYPE_INTERNE:
                $code = "REU_INT";
        }
        
        if(empty($code)) {
            $code = "AC_RDV";
        }
        
        return (int) $this->db->getValue("c_actioncomm", "id", "code = '$code'");
        
    }

    // Getters données: 

    public function getTimeInputValue($field)
    {
        if ((string) $this->getData($field)) {
            $t = new DateTime($this->getData($field));
            return($t->format('H:i'));
        }

        switch ($field) {
            case 'arrived':
            case 'arriverd_am':
                return '08:00';

            case 'departure':
            case 'departure_pm':
                return '18:00';

            case 'departure_am':
                return '12:00';

            case 'arriverd_pm':
                return '14:00';
        }

        return '';
    }

    // Affichages:

    public function displayTime($field, $with_secondes = false)
    {
        $t = new DateTime($this->getData($field));
        return($t->format('H:i' . ($with_secondes ? ':s' : '')));
    }
    
    public function displayTypeInList() {
        $type  = $this->getData('type');
        if(array_key_exists($type,self::$types)) {
            return $this->displayData("type");
        }
        $parent = $this->getParentInstance();
        $fk_soc = $parent->getData('fk_soc');
        $soc = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $fk_soc);
        if($type == $fk_soc) {
            return "<b>Intervention interne (".$soc->getName().")</b>";
        }
        
    }

    public function display_service_ref($with_details_commande_line = true)
    {
        $html = '';

        $fk_product = 0;
        $element = '';
        $valeur = '';

        if ($this->getData('id_line_contrat') > 0) {
            $contrat_line = $this->getChildObject('contrat_line');
            if (BimpObject::objectLoaded($contrat_line)) {
                $fk_product = (int) $contrat_line->getData('fk_product');

                if ($with_details_commande_line) {
                    $valeur = $contrat_line->getData('subprice') * $contrat_line->getData('qty');

                    $contrat = $contrat_line->getParentInstance();
                    if (BimpObject::objectLoaded($contrat)) {
                        $element = "Contrat: " . $contrat->getLink();
                    }
                }
            }
        } elseif ($this->getData('id_line_commande') > 0 || $this->getData('id_dol_line_commande') > 0) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine');
            if($this->getData('id_dol_line_commande') > 0)
                $line->find(Array('id_line' => $this->getData('id_dol_line_commande')), 1);
            elseif($this->getData('id_line_commande') > 0)
                $line->fetch($this->getData('id_line_commande'));
            if (BimpObject::objectLoaded($line)) {
                
                $fk_product = $line->id_product;

                if ($with_details_commande_line) {
                    $valeur = $line->getTotalHT(1);

                    $commande = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande)) {
                        $element = "Commande: " . $commande->getLink();
                    }
                }
            }
        }

        if ($fk_product > 0) {
            if ($this->getData('type') == 6) {
                $html = '<span class="success">' . BimpRender::renderIcon("fas_car", 'iconLeft') . 'Déplacement vendu</span>';
            } else {
                $product = $this->getInstance('bimpcore', 'Bimp_Product', $fk_product);

                if (BimpObject::objectLoaded($product)) {
                    $html = $product->getLink();
                }
            }

            if ($with_details_commande_line) {
                if ($element) {
                    $html .= ($html ? '<br/>' : '') . '<b>' . $element . '</b>';
                }

                if ($valeur !== '') {
                    $html .= ($html ? '<br/>' : '') . '<b>Vendu: ' . price($valeur) . '€ HT</b>';
                }
            }
        } else {
            $parent = $this->getParentInstance();
            if ($this->getData('type') == self::TYPE_INTERNE) {
                $html .= '<span class="success">Service en interne</span>';

                if (BimpObject::objectLoaded($parent)) {
                    $client = $parent->getChildObject('client');

                    if (BimpObject::objectLoaded($client)) {
                        $html .= '<br/>Client: ' . $client->getLink();
                    }
                }
            } elseif ($this->getData('type') == self::TYPE_DEPLACEMENT_CONTRAT) {
                $html .= '<span class="success">' . BimpRender::renderIcon("fas_car", 'iconLeft') . 'Déplacement sous contrat</span>';

                if (BimpObject::objectLoaded($parent)) {
                    $contrat = $parent->getChildObject('contrat');

                    if (BimpObject::objectLoaded($contrat)) {
                        $html .= '<br/>Contrat: ' . $contrat->getLink();
                        $html .= '<br/>Total vendu: <b>' . price($contrat->getTotalContrat()) . ' €</b>';
                    }
                }
            }
        }
        
        if($this->getData('forfait') == 1) {
            $html .= '<br /><span class=\'danger\'>Au forfait</span>';
        }
        
        return $html;
    }
    
    public function displayDescriptifPrestationDemande() {
        $description = '';
        $orderLine = null;
        
        if($this->getData('id_line_contrat') > 0) {
            $obj = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            $description = $obj->getData('description');
        } elseif($this->getData('id_line_commande') > 0 || $this->getData('id_dol_line_commande') > 0) {
            BimpTools::loadDolClass('commande', 'commande', 'OrderLine');
            if($this->getData('id_line_commande') > 0){
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $this->getData('id_line_commande'));
                $orderLine = new OrderLine($this->db->db);
                $orderLine->fetch($obj->getData('id_line'));
            }   
            elseif($this->getData('id_dol_line_commande') > 0) {
                $orderLine = new OrderLine($this->db->db);
                $orderLine->fetch($this->getData('id_dol_line_commande'));
            }
            
            $description = $orderLine->desc;
            
        }
        
        return $description;
    }

    public function displayClient()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            $client = $this->getInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));

            if (BimpObject::objectLoaded($client)) {
                $card = new BC_Card($client);
                return $card->renderHtml();
            }
        }

        return '';
    }
    
    public function isAuForfait() {
        if($this->getData('id_line_contrat')) return 1;
        if($this->getData('id_line_commande') || $this->getData('id_dol_line_commande')) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine');
            if($this->getData('id_line_commande')){
                $line->fetch($this->getData('id_line_commande'));
            } else {
                $line->find(['id_line' => $this->getData('id_dol_line_commande')], 1);
            }
            return $line->getData('force_qty_1');
        }
        return 0;
    }

    public function displayDuree()
    {
        $t = $this->getData('duree');
        $parent = $this->getParentInstance();
        return $parent->timestamp_to_time($t);
    }

    public function displayDateWithDetails()
    {
        $html = '';
        $popover = '';

        $date = new DateTime($this->getData('date'));
        $html .= "<b>" . $date->format('d / m / Y') . "</b>";

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

        if (!in_array($this->getData('type'), $excludeTypeInfos)) {
            if ($popover) {
                $html .= " <small class='bs-popover' " . BimpRender::renderPopoverData($popover, 'top', 'true') . " >" . BimpRender::renderIcon('fas_info-circle') . "</small>";
            } else {
//                $html .= " <small>" . BimpRender::renderIcon('info-circle') . "</small>";
            }
        }
        
        return $html;
    }

    public function displayDescLight()
    {
        $html = "";

        $desc = $this->getData('description');

        if (strlen(strip_tags($desc)) > 450) {
            $html .= '<div class="bs-popover"' . BimpRender::renderPopoverData($desc, 'bottom', 'true') . '>';
            $desc_light = substr(strip_tags(BimpTools::replaceBr($desc)), 0, 450);
            $desc_light = str_replace("\n", '<br/>', $desc_light);
            $html .= $desc_light . '...';
            $html .= '</div>';
            $html .= '<div style="margin-top: 10px">';
            $html .= '<span class="small info" style="margin-top: 10px">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Survoler pour afficher la description complète</span>';
            $html .= '</div>';
        } else {
            $html = $desc;
        }

        return $html;
    }

    // Traitements: 

    private function adjustCalendar($delete = false) {
        $errors = array();
        $parent = $this->getParentInstance();
        BimpTools::loadDolClass("comm/action", "actioncomm");
        BimpTools::loadDolClass("user");
        $admin = new User($this->db->db);
        $admin->fetch(1);
        $actionCommList = ($this->getData('actioncomm') != "") ? json_decode($this->getData('actioncomm')) : [];
        $actionCommClass = new ActionComm($this->db->db);
        
        if(count($actionCommList) > 0) {
            foreach ($actionCommList as $id_actionComm) {
                if($id_actionComm > 0){
                    $actionCommClass->fetch($id_actionComm);
                    $actionCommClass->delete();
                }
            }
        }
        
        if(!$delete) {
            $actionCommClass->label = $parent->getRef() . " - " . BT_ficheInter_det::$types[$this->getData('type')]['label'];
            $actionCommClass->note = addslashes($this->getData('description'));
            $actionCommClass->punctual = 1;
            $actionCommClass->userownerid = (int) $parent->getData('fk_user_tech');
            $actionCommClass->elementtype = 'fichinter';
            $actionCommClass->type_id = $this->getTypePlanningCode();
            $actionCommClass->percentage = 100;
            $actionCommClass->socid = $parent->getData('fk_soc');
            $actionCommClass->fk_element = $parent->id;

            if($this->getData('arrived')) {
                $actionCommClass->datep = strtotime($this->getData('arrived'));
                $actionCommClass->datef = strtotime($this->getData('departure'));
                if($actionCommClass->create($admin) < 1)
                    $errors = BimpTools::getErrorsFromDolObject($actionCommClass, $errors);
                else
                    BimpTools::merge_array($errors, $this->set('actioncomm', [$actionCommClass->id]));
            } else {
                $actionCommClass2 = clone $actionCommClass;
                $actionCommClass->datep = strtotime($this->getData('arriverd_am'));
                $actionCommClass->datef = strtotime($this->getData('departure_am'));
                if($actionCommClass->create($admin) < 1)
                    $errors = BimpTools::getErrorsFromDolObject($actionCommClass, $errors);
                $actionCommClass2->datep = strtotime($this->getData('arriverd_pm'));
                $actionCommClass2->datef = strtotime($this->getData('departure_pm'));
                if($actionCommClass2->create($admin) < 1)
                    $errors = BimpTools::getErrorsFromDolObject($actionCommClass, $errors);
                else
                    BimpTools::merge_array($errors, $this->set('actioncomm', [$actionCommClass->id, $actionCommClass2->id]));
            }
        }

        return $errors;
        
    }
    
    public function onSave(&$errors = [], &$warnings = [])
    {
        $parent = $this->getParentInstance();
        
        if (BimpObject::objectLoaded($parent)) {
            $tt = $this->db->getSum('fichinterdet', 'duree', 'fk_fichinter = ' . $this->getData('fk_fichinter'));
            $parent->set('duree', $tt);
            $w = array();
            $parent->update($w, true);
        }

        parent::onSave($errors, $warnings);
        
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

    // Overrides: 
    
    public function delete(&$warnings = array(), $force_delete = false) {
        $this->adjustCalendar(true);
        return parent::delete($warnings, $force_delete);
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            $date = $this->getData('date');
            $type = (int) $this->getData('type');
            $duree = 0;

            // Calcul durée: 
            if (in_array($type, array(5, 3, 6))) {
                // Si type déplacement
                $duree = (int) BimpTools::getValue('temps_trajet', 0);
                $this->set('arrived', null);
                $this->set('departure', null);
                $this->set('arriverd_am', null);
                $this->set('departure_am', null);
                $this->set('arriverd_pm', null);
                $this->set('departure_pm', null);
            } elseif ($type != 2) {
                // Si type non déplacement et non libre
                if ((int) BimpTools::getValue('am_pm', 0)) {
                    // Si matin et après-midi: 
                    $arrived_am = BimpTools::getValue('arrived_am_time', '');
                    $departure_am = BimpTools::getValue('departure_am_time', '');
                    $arrived_pm = BimpTools::getValue('arrived_pm_time', '');
                    $departure_pm = BimpTools::getValue('departure_pm_time', '');

                    if (!$arrived_am || !$departure_am) {
                        $errors[] = 'Veuillez sélectionner un heure d\'arrivée et de départ pour le matin';
                    } elseif ($arrived_am > $departure_am) {
                        $errors[] = 'L\'heure d\'arrivée du matin ne peut pas être supérieure à l\'heure de départ du matin';
                    }

                    if (!$arrived_pm || !$departure_pm) {
                        $errors[] = 'Veuillez sélectionner un heure d\'arrivée et de départ pour l\'après-midi';
                    } elseif ($arrived_pm > $departure_pm) {
                        $errors[] = 'L\'heure d\'arrivée de l\'après-midi ne peut pas être supérieure à l\'heure de départ de l\'après-midi';
                    }

                    if (!count($errors)) {
                        if ($departure_am > $arrived_pm) {
                            $errors[] = 'L\'heure de départ du matin ne peut pas être supérieure à l\'heure d\'arrivée de l\'après-midi';
                        }
                    }

                    if (!count($errors)) {
                        $this->set('arriverd_am', $date . ' ' . $arrived_am);
                        $this->set('departure_am', $date . ' ' . $departure_am);

                        $this->set('arriverd_pm', $date . ' ' . $arrived_pm);
                        $this->set('departure_pm', $date . ' ' . $departure_pm);

                        $this->set('arrived', null);
                        $this->set('departure', null);

                        $duree_am = strtotime($departure_am) - strtotime($arrived_am);
                        $duree_pm = strtotime($departure_pm) - strtotime($arrived_pm);

                        $duree = $duree_am + $duree_pm;
                    }
                } else {
                    $arrived = BimpTools::getValue('arrived_time', '');
                    $departure = BimpTools::getValue('departure_time', '');

                    if ($arrived && $departure) {
                        $this->set('arrived', $date . ' ' . $arrived);
                        $this->set('departure', $date . ' ' . $departure);

                        $this->set('arriverd_am', null);
                        $this->set('departure_am', null);
                        $this->set('arriverd_pm', null);
                        $this->set('departure_pm', null);

                        $duree = strtotime($departure) - strtotime($arrived);
                    } else {
                        $errors[] = 'Veuillez sélectionner une heure de départ et d\'arrivée';
                    }
                }
            }

            if (!count($errors)) {
                if ($duree < 60 && $type !== 2) {
                    $errors[] = 'Il semble y avoir une erreur dans vos horaires. La durée minimal de l\'intervention est de 1 minute (Durée actuelle: ' . $duree . 'secondes)';
                } else {
                    $this->set('duree', $duree);
                }
            }

            // Ligne commande / contrat liée: 
            $id_commande_line = 0;
            $id_contrat_line = 0;

            if ($duree >= 60 || $type == 2) {
                if ($type == 0) {
                    $service = BimpTools::getValue('service', '');
                    if ($service) {
                        if (preg_match('/^(contrat|commande)_(\d+)$/', $service, $matches)) {
                            switch ($matches[1]) {
                                case 'contrat':
                                    $id_contrat_line = (int) $matches[2];
                                    break;

                                case 'commande':
                                    $id_commande_line = (int) $matches[2];
                                    break;
                            }
                        }
                    }
                } elseif ($type == 6) {
                    $id_commande = (int) BimpTools::getValue('id_commande_depl', 0);

                    if ($id_commande) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                        if (!BimpObject::objectLoaded($commande)) {
                            $errors[] = 'La commande sélectionnée pour le déplacement vendu n\'existe plus';
                        } else {
                            $lines = $commande->getLines('not_text');
                            foreach ($lines as $line) {
                                $produit = $line->getProduct();

                                if (BimpObject::objectLoaded($produit) && $produit->isDep()) {
                                    $id_commande_line = $line->id;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $this->set('id_line_commande', $id_commande_line);
            $this->set('id_line_contrat', $id_contrat_line);
        }

        return $errors;
    }
    
    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $fi = $this->getParentInstance();

            if (!BimpObject::objectLoaded($fi)) {
                $errors[] = 'ID de la Fiche Inter absent';
            } else {
                $type = (int) $this->getData('type');
                $fk_contrat = (int) $fi->getData('fk_contrat');
                $commandes = $fi->getData('commandes');
                $tickets = $fi->getData('tickets');

                if ($type == 0) {
                    if (!$fk_contrat && empty($commandes) && empty($tickets)) {
                        $errors[] = 'Il n\'est pas possible d\'ajouter une intervention vendue sans objet lié à la fiche inter';
                    }
                    if (!(int) $this->getData('id_line_commande') && !(int) $this->getData('id_line_contrat') && !(int) $this->getData('id_dol_line_commande')) {
                        $errors[] = 'Vous ne pouvez pas faire une intervention vendue sans code service, si ceci est une erreur merci d\'envoyer un e-mail à: support-fi@bimp.fr';
                    }
                } elseif ($type == 5) {
                    if (!$fk_contrat) {
                        $errors[] = 'Il n\'est pas possible d\'ajouter un déplacement sous contrat sans contrat lié à la fiche inter';
                    }
                } elseif ($type == 6) {
                    if (!(int) $this->getData('id_line_commande')) {
                        $errors[] = 'Aucune ligne de commande sélectionnée pour le déplacement vendu';
                    }
                }

                $forfait = 0;
                $facturable = 0;

                $type = (int) $this->getData('type');
                if(($this->getData('id_line_commande') != $this->getInitData('id_line_commande')) || 
                        ($this->getData('id_dol_line_commande') != $this->getInitData('id_dol_line_commande')) || 
                        ($this->getData('id_line_contrat') != $this->getInitData('id_line_contrat'))) {
                    switch ($type) {
                        case 0:
                            if((int) $this->getData('id_line_contrat')) {
                                $forfait = self::MODE_FACT_FORFAIT;
                            } else {
                                $line = BimpObject::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine');
                                if($this->getData('id_line_commande') > 0) {
                                    $line->fetch((int) $this->getData('id_line_commande'));
                                }  
                                elseif($this->getData('id_dol_line_commande') > 0) {
                                    $line->find(Array('id_line' => $this->getData('id_dol_line_commande')), 1);
                                }
                                $forfait = ($line->getData('force_qty_1')) ? self::MODE_FACT_FORFAIT : self::MODE_FACT_TEMPS_P;
                            }
                            break;
                        case 1:
                        case 2:
                            $forfait = self::MODE_FACT_AUCUN__;
                            break;
                        case 6:
                             $forfait = self::MODE_FACT_FORFAIT;
                            break;
                        case 5:
                            $facturable = 1;
                            if ($type == 5 || (int) $this->getData('id_line_contrat')) {
                                $forfait = self::MODE_FACT_FORFAIT;
                            } else {
                                $forfait = self::MODE_FACT_TEMPS_P;
                            }
                            break;
                        case 3:
                            $forfait = self::MODE_FACT_FORFAIT;
                            break;
                        case 4:
                            $forfait = self::MODE_FACT_TEMPS_P;
                            break;
                    }
                    $this->set('forfait', $forfait);
                    $this->set('facturable', $facturable);
                }                
                
                if(!count($errors) && 
                        $this->getData('type') != self::TYPE_DEPLA && 
                        $this->getData('type') != self::TYPE_DEPLACEMENT_VENDU && 
                        $this->getData('type') != self::TYPE_DEPLACEMENT_CONTRAT &&
                        $this->getData('type') != self::TYPE_LIBRE
                ) {
                    $errors = BimpTools::merge_array($errors, $this->adjustCalendar());
                }
            }
        }
        return $errors;
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
    
    // Filters
    
    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false){
        switch($field_name) {
            case 'type_of':
                $in = [];
                $sql = "SELECT rowid FROM llx_fichinterdet";
                if(count($values) > 0) {
                    $sql .= " WHERE ";
                    $for_or = Array();
                    foreach($values as $value) {
                        if($value != 7){
                            $for_or[] = $value;
                        }
                        else {
                            $intern_societe = explode(',', BimpCore::getConf("bimptechnique_id_societe_auto_terminer"));
                            foreach($intern_societe as $id) {
                                $for_or[] = $id;
                            }
                        }
                    }
                }
                $first_loop = true;
                foreach($for_or as $type_of) {
                    $sql .= ($first_loop) ? "type = $type_of" : " OR type = $type_of";
                    $first_loop = false;
                }
                
                if ($sql != "") {
                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                }
                $filters['a.rowid'] = ['in' => $in];
                break;
        }
    }
}
