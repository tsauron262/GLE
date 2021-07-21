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
        self::TYPE_PLUS                => ['label' => "Intervention non vendu", 'icon' => 'fas_plus', 'classes' => ['warning']],
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

    public function isFieldEditable($field, $force_edit = false)
    {
        $fi = $this->getParentInstance();

        if (BimpObject::objectLoaded($fi)) {
            if ((int) $fi->getData('fk_statut') === 0) {
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
                            $product = $line->getProduct();

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

        if ((int) $this->getData('fk_contrat')) {
            $contrat = $this->getChildObject('contrat');
            foreach ($contrat->dol_object->lines as $line) {
                $bimpline = $contrat->getChildObject('lines', $line->id);
                if ($bimpline->getData('product_type') == 1 && $bimpline->getData('statut') == 4) {
                    $services['contrat_' . $line->id] = 'Intervention sous contrat (' . price($bimpline->getData('total_ht')) . '€) - <strong>' . $contrat->getRef() . '</strong> - ' . $line->description;
                }
            }
        }

        return $services;
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
        } elseif ($this->getData('id_line_commande') > 0) {
            $line = $this->getChildObject('commande_line');

            if (BimpObject::objectLoaded($line)) {
                $fk_product = $line->id_product;

                if ($with_details_commande_line) {
                    $valeur = $line->getTotalHt(1);

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

        return $html;
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

    public function onSave(&$errors = [], &$warnings = [])
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            $tt = $this->db->getSum('fichinterdet', 'duree', 'fk_fichinter = ' . $this->getData('fk_fichinter'));
            $parent->set('duree', $tt);
            $w = array();
            $parent->update($w, true);
        }

        return parent::onSave($errors, $warnings);
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
                                    echo 'trouvée: ' . $id_commande_line . '<br/>';
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
                    if (!(int) $this->getData('id_line_commande') && !(int) $this->getData('id_line_contrat')) {
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

                switch ($type) {
                    case 0:
                    case 1:
                    case 6:
                    case 5:
                        $facturable = 1;
                        if ($type == 5 || (int) $this->getData('id_line_contrat')) {
                            $forfait = 1;
                        } else {
                            $forfait = 2;
                        }
                        break;
                    case 3:
                    case 4:
                        $forfait = 2;
                        break;
                }

                $this->set('forfait', $forfait);
                $this->set('facturable', $facturable);
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
}
