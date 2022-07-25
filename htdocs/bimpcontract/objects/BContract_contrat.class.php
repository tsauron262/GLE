<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

    /*
     * *** Mémo ajout signature pour un objet: ***

     * - Gérer l'enregistrement des paramètres de position de la signature sur le PDF au moment de sa génération (Si besoin) / ou régler par défaut pour les PDF fixes
     * - Intégrer selon le context: marqueur signé (champ booléen ou statut) / indicateur signature dans l'en-tête / etc. 
     * - Gérer Annulation signature si besoin
     * - Gérer Duplication / Révision / Etc. 
     * - Gérer la visualisation du docuement sur l'interface publique (bimpinterfaceclient > docController) 
     * - Gérer le droit canClientView() pour la visualisation du document sur l'espace public. 
     */

class BContract_contrat extends BimpDolObject
{

    //public $redirectMode = 4;
    public static $email_type = 'contract';
    public $email_group = "";
    public $email_facturation = "";
    public static $element_name = "contract";

    // Les status
    CONST CONTRAT_STATUT_ABORT = -1;
    CONST CONTRAT_STATUS_BROUILLON = 0;
    CONST CONTRAT_STATUS_VALIDE = 1;
    CONST CONTRAT_STATUS_CLOS = 2;
    CONST CONTRAT_STATUT_WAIT_ACTIVER = 3;
    CONST CONTRAT_STATUS_REFUSE = 4;
    CONST CONTRAT_STATUS_WAIT = 10;
    CONST CONTRAT_STATUS_ACTIVER = 11;
    CONST CONTRAT_STATUS_ACTIVER_TMP = 12;
    CONST CONTRAT_STATUS_ACTIVER_SUP = 13;
    // Les périodicitées
    CONST CONTRAT_PERIOD_AUCUNE = 0;
    CONST CONTRAT_PERIOD_MENSUELLE = 1;
    CONST CONTRAT_PERIOD_BIMENSUELLE = 2;
    CONST CONTRAT_PERIOD_TRIMESTRIELLE = 3;
    CONST CONTRAT_PERIOD_SEMESTRIELLE = 6;
    CONST CONTRAT_PERIOD_ANNUELLE = 12;
    CONST CONTRAT_PERIOD_TOTAL = 1200;
    // Les délais d'intervention
    CONST CONTRAT_DELAIS_0_HEURES = 0;
    CONST CONTRAT_DELAIS_4_HEURES = 4;
    CONST CONTRAT_DELAIS_8_HEURES = 8;
    CONST CONTRAT_DELAIS_16_HEURES = 16;
    // Les renouvellements
    CONST CONTRAT_RENOUVELLEMENT_NON = 0; // 100
    CONST CONTRAT_RENOUVELLEMENT_1_FOIS = 1; // 101
    CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 3; // 102
    CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 6; // 103
    CONST CONTRAT_RENOUVELLEMENT_4_FOIS = 4; // 104
    CONST CONTRAT_RENOUVELLEMENT_5_FOIS = 5; // 105
    CONST CONTRAT_RENOUVELLEMENT_6_FOIS = 7; // 106
    CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12; // 112
    CONST CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM = 666; 
    // Contrat dénoncé
    CONST CONTRAT_DENOUNCE_NON = 0;
    CONST CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS = 1;
    CONST CONTRAT_DENOUNCE_OUI_HORS_DELAIS = 2;
    CONST CONTRAT_GLOBAL = "CT";
    CONST CONTRAT_DE_MAINTENANCE = 'CMA';
    CONST CONTRAT_SUPPORT_TELEPHONIQUE = 'CST';
    CONST CONTRAT_MONITORING = 'CMO';
    CONST CONTRAT_DE_SPARE = 'CSP';
    CONST CONTRAT_DE_DELEGATION_DE_PERSONEL = 'CDP';
    // Type mail interne
    CONST MAIL_DEMANDE_VALIDATION = 1;
    CONST MAIL_VALIDATION = 2;
    CONST MAIL_ACTIVATION = 3;
    CONST MAIL_SIGNED = 4;
    CONST MAIL_TEMPORAIRE = 5;
    CONST PRORATA_PERIODE = false;

    public static $status_list = Array(
        self::CONTRAT_STATUT_ABORT        => Array('label' => 'Abandonné', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUS_BROUILLON    => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::CONTRAT_STATUS_VALIDE       => Array('label' => 'Attente signature client', 'classes' => Array('success'), 'icon' => 'fas_retweet'),
        self::CONTRAT_STATUS_CLOS         => Array('label' => 'Clos', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUS_REFUSE       => Array('label' => 'Refusé', 'classes' => Array('danger'), 'icon' => 'fas_times'),
        self::CONTRAT_STATUT_WAIT_ACTIVER => Array('label' => 'Attente d\'activation', 'classes' => Array('important'), 'icon' => 'fas_retweet'),
        self::CONTRAT_STATUS_WAIT         => Array('label' => 'En attente de validation', 'classes' => Array('warning'), 'icon' => 'fas_refresh'),
        self::CONTRAT_STATUS_ACTIVER      => Array('label' => 'Actif', 'classes' => Array('important'), 'icon' => 'fas_play'),
        self::CONTRAT_STATUS_ACTIVER_TMP  => Array('label' => 'Activation provisoire', 'classes' => Array('important'), 'icon' => 'fas_history'),
        self::CONTRAT_STATUS_ACTIVER_SUP  => Array('label' => 'Activation suspendue pour cause de non signature', 'classes' => Array('danger'), 'icon' => 'fas_stop')
    );
    public static $denounce = Array(
        self::CONTRAT_DENOUNCE_NON                => Array('label' => 'Non', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS => Array('label' => 'OUI, DANS LES TEMPS', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_HORS_DELAIS    => Array('label' => 'OUI, HORS DELAIS', 'classes' => Array('danger'), 'icon' => 'fas_times'),
    );
    public static $period = Array(
        self::CONTRAT_PERIOD_MENSUELLE     => 'Mensuelle',
        self::CONTRAT_PERIOD_BIMENSUELLE   => 'Bimestrielle',
        self::CONTRAT_PERIOD_TRIMESTRIELLE => 'Trimestrielle',
        self::CONTRAT_PERIOD_SEMESTRIELLE  => 'Semestrielle',
        self::CONTRAT_PERIOD_ANNUELLE      => 'Annuelle',
        self::CONTRAT_PERIOD_TOTAL         => 'Une fois',
        self::CONTRAT_PERIOD_AUCUNE        => 'Aucune',
    );
    public static $gti = Array(
        self::CONTRAT_DELAIS_0_HEURES  => '',
        self::CONTRAT_DELAIS_4_HEURES  => '4 heures ouvrées',
        self::CONTRAT_DELAIS_8_HEURES  => '8 heures ouvrées',
        self::CONTRAT_DELAIS_16_HEURES => '16 heures ouvrées'
    );
    public static $renouvellement = Array(
        self::CONTRAT_RENOUVELLEMENT_1_FOIS          => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS          => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS          => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS          => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS          => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS          => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM=> 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION => 'Sur proposition',
        self::CONTRAT_RENOUVELLEMENT_NON             => 'Non',
    );
    public static $renouvellement_create = Array(
        self::CONTRAT_RENOUVELLEMENT_NON             => "Choix du renouvellement",
        self::CONTRAT_RENOUVELLEMENT_1_FOIS          => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS          => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS          => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS          => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS          => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS          => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM=> 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION => 'Sur proposition'
    );
    public static $renouvellement_edit = Array(
        self::CONTRAT_RENOUVELLEMENT_NON             => 'Aucun',
        self::CONTRAT_RENOUVELLEMENT_1_FOIS          => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS          => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS          => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_4_FOIS          => 'Tacite 4 fois',
        self::CONTRAT_RENOUVELLEMENT_5_FOIS          => 'Tacite 5 fois',
        self::CONTRAT_RENOUVELLEMENT_6_FOIS          => 'Tacite 6 fois',
        self::CONTRAT_RENOUVELLEMENT_AD_VITAM_ETERNAM=> 'Durée indéterminée',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION => 'Sur proposition'
    );
    public static $objet_contrat = [
        self::CONTRAT_GLOBAL                    => ['label' => "Contrat global", 'classes' => [], 'icon' => 'globe'],
        self::CONTRAT_DE_MAINTENANCE            => ['label' => "Contrat de maintenance", 'classes' => [], 'icon' => 'cogs'],
        self::CONTRAT_SUPPORT_TELEPHONIQUE      => ['label' => "Contrat de support téléphonique", 'classes' => [], 'icon' => 'phone'],
        self::CONTRAT_MONITORING                => ['label' => "Contrat de monitoring", 'classes' => [], 'icon' => 'terminal'],
        self::CONTRAT_DE_SPARE                  => ['label' => "Contrat de spare", 'classes' => [], 'icon' => 'share'],
        self::CONTRAT_DE_DELEGATION_DE_PERSONEL => ['label' => "Contrat de délégation du personnel", 'classes' => [], 'icon' => 'male'],
    ];
    public static $true_objects_for_link = [
        'commande'      => 'Commande',
        'facture_fourn' => 'Facture fournisseur',
            //'propal' => 'Proposition commercial'
    ];
    public static $dol_module = 'contract';
    
    // Recherche auto dans DocuSign
    public static $default_signature_params = array(
        'x_pos'             => 0,
        'width'             => 0,
        'date_x_offset'     => 0,
        'date_y_offset'     => 0,
        'nom_x_offset'      => 0,
        'nom_y_offset'      => 0,
        'nom_width'         => 0,
        'fonction_x_offset' => 0,
        'fonction_y_offset' => 0,
        'fonction_width'    => 0
    );

    function __construct($module, $object_name)
    {
        $this->redirectMode = 4;
        $this->email_group = BimpCore::getConf('email_groupe', '', 'bimpcontract'); // A éviter, faire getConf() à chaque fois que nécessaire
        $this->email_facturation = BimpCore::getConf('email_facturation', '', 'bimpcontract');
        return parent::__construct($module, $object_name);
    }
    
    public function isContratDelegation():bool {
        
        return (substr($this->getRef(), 0, 3) == 'CDP') ? 1 : 0;
                
    }
        
    public function getTotalHeureDelegation():array {
        
        $return = Array();
        
        $hourInDay = 7;
        $services = Array('SERV19-DP1', 'SERV19-DP2', 'SERV19-DP3', 'SAV-NIVEAU_5', 'SERV22-DPI-AAPEI');
        $instance = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product');
        $children = $this->getChildrenList('lines');
        
        foreach($children as $id_child) {
            
            $child = $this->getChildObject('lines', $id_child);
            $instance->fetch($child->getData('fk_product'));            
            
            if(in_array($instance->getRef(), $services)) {
                
                $return[$instance->getRef() . '_' . $id_child] += (float) $child->getData('qty') * $hourInDay;
            }
                        
        }
        
        return $return;
    }
    
    public function getHeuresDelegationFromInterByService():array {
        $return = Array();
        
        $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        $list = $instance->getList(Array('fk_contrat' => $this->id));
        
        if(count($list) > 0) {
            
            foreach($list as $index) {
                $child = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter_det');
                $children = $child->getList(array('fk_fichinter' => $index['rowid']));
                if(count($children) > 0) {
                    foreach($children as $i) {
                        if($index['fk_statut'] > 0) {
                            $childContrat = $this->getChildObject('lines', $i['id_line_contrat']);
                            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $childContrat->getData('fk_product'));
                            $return[$product->getRef() . '_' . $childContrat->id] += $i['duree'] / 3600;
                        }
                    }                    
                }
            }
            
        }
        
        return $return;
    }
    
    public function displayTotalHeureDelegationVendu():string {
        
        $array = $this->getTotalHeureDelegation();
        $html = '<table class="objectlistTable" style="border: none; min-width: 640px" width="100%">'
                . '<thead class="listTableHead">'
                . '<tr class="headerRow">'
                . '<th style="" data-col_name="type" data-field_name="type">Service</th>'
                . '<th style="" data-col_name="type" data-field_name="type">heures vendues</th>'
                . '<th style="" data-col_name="type" data-field_name="type">heures consommées</th>'
                . '<th style="" data-col_name="type" data-field_name="type">Reste</th>'
                . '</tr>'
                . '</thead>'
                . '<tbody class="listRows" >';
        $total = 0;
        
        $inInters = $this->getHeuresDelegationFromInterByService();
        
        foreach($array as $code => $temps) {
            
            $html .= '<tr class="objectListItemRow">';
            $html .= '<td>'.$code.'</td>';
            $html .= '<td>'.$temps.' heures</td>';
            $html .= '<td>'.$inInters[$code].' heures</td>';
            
            $balance = $temps - $inInters[$code];
            
            $class = 'warning';
            $icon = 'equal';
            
            if($balance < 0) {
                $class = 'danger';
                $icon = 'arrow-down';
            } elseif($balance > 0) {
                $class = 'success';
                $icon   = 'arrow-up';
            }
            
            $html .= '<td class="'.$class.'" >' . BimpRender::renderIcon($icon) . ' ' .$balance.' heures</td>';
            
            $html .= '</tr>';
            
        }
        
        $html .= '</tbody>'
                . '</table>';
                
        return $html;
        
    }
    
    public function tryToValidate(&$errors) {
        
        global $user;
        $success = [];
        $validComm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
        $validComm->tryToValidate($this, $user, $errors, $success);

        return $errors;
     
    }
    
    public function getProvLink() {
        return str_replace($this->getRef(), '(PROV' . $this->id . ')', $this->getLink());
    }
    
    public function getClientFacture()
    {
        if ((int) $this->getData('fk_soc_facturation')) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc_facturation'));
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        if ((int) $this->getData('fk_soc')) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc'));
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        return null;
    }
    
    public function getDurreeVendu(){
        $tot = 0;
        $lines = $this->getChildrenObjects('lines');
        foreach($lines as $line){
            $prod = $line->getChildObject('produit');
            $tot += $prod->getData('duree_i') * $line->getData('qty');
        }
        return $tot;
    }
    
    
    public function canShowAdmin()
    {
        global $user;
        if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER || $this->getData('statut') == self::CONTRAT_STATUT_ABORT || $this->getData('statut') == self::CONTRAT_STATUS_CLOS)
            if ($user->admin == 1)
                return 1;
        return 0;
    }

    public function getSecteursContrat()
    {
        $sql = $this->db->getRows('bimp_c_secteur', 'clef = "CTC" OR clef = "CTE"');
        $return = Array();
        foreach ($sql as $index => $i) {
            $return[$i->clef] = $i->valeur;
        }
        return $return;
    }

    public function showValueSecteurInPropal($type)
    {
        $type_eduction = ["E", "CTE"];
        if (in_array($type, $type_eduction)) {
            return "CTE";
        }
        return "CTC";
    }

    public function getTotalPa($line_type = -1)
    {
        $total_PA = 0;
        $children_list = $this->getChildrenList('lines');
        foreach ($children_list as $nb => $id) {
            $child = $this->getChildObject('lines', $id);
            $total_PA += $child->getData('buy_price_ht') * $child->getData('qty');
        }
        return $total_PA;
    }

    public function getTitreAvenantSection()
    {

        $titre = "Avenants";

        $instance = $this->getInstance('bimpcontract', 'BContract_avenant');
        $list = $instance->getList(["id_contrat" => $this->id]);

        $titre .= '<span style="margin-left: 10px" class="badge badge-primary">' . count($list) . '</span>';

        return $titre;
    }

    public function getAllSerialsForAvenant()
    {

        $html = "";
        foreach ($this->dol_object->lines as $line) {
            $serials = [];
            $p = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
            $l = $this->getInstance('bimpcontract', 'BContract_contratLine', $line->id);

            $html .= "<b>" . $p->getData('ref') . '</b><br />';

            $theSerials = json_decode($l->getData('serials'));
            foreach ($theSerials as $theSerial) {
                $serials[$theSerial] = $theSerial;
            }
            $html .= BimpInput::renderInput('check_list', 'delserials_' . $l->id, '', ['items' => $serials]);
        }

        return $html;
    }

    public function renderInitialRenouvellement()
    {
        //$this->updateRenouvellementInitial();
        return self::$renouvellement[$this->getData('initial_renouvellement')];
    }

    public function updateRenouvellementInitial()
    {
        if ($this->getData('initial_renouvellement') != $this->getData('tacite')) {
            $this->updateField('initial_renouvellement', $this->getData('tacite'));
        }
    }

    public function renderFi()
    {
        $html = BimpRender::renderPanel('Stats des Fi ' . $this->getLabel('of_the'), $this->renderThisStatsFi(), '', array(
                    'icon'     => 'fas_file',
                    'type'     => 'secondary',
                    'foldable' => true
        ));
//        $objects = $this->getInstance('bimptechnique', 'BT_ficheInter');
//        $html .= $objects->renderList('contrat');

        return $html;
    }

    public function renderAvenant()
    {

        $html = "";
        $av = $this->getInstance('bimpcontract', 'BContract_avenant');
        $list = $av->getList(['id_contrat' => $this->id, 'statut' => 0]);
        $errors = [];
        $buttons = [];

        if (count($list) > 0) {
            $html .= $av->renderList('avenant_brouillon');
        } else {
            $buttons[] = array(
                'label'   => 'Ajouter un avenant à ce contrat',
                'icon'    => 'fas_plus',
                'onclick' => $this->getJsActionOnclick('avenant', array(), array())
            );
            $html .= BimpRender::renderButtonsGroup($buttons, $params);
        }


        return $html;
    }

    public function useEntrepot()
    {
        return (int) BimpCore::getConf('USE_ENTREPOT');
    }

    public function getPeriodeString()
    {
        return self::$period[$this->getData('periodicity')];
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->contrat->dir_output;
    }

    public function actionCreateFi($data, &$success)
    {
        $errors = [];
        $warnings = [];
        $callback = "";
        if ($data['nature_inter'] == 0 || $data['type_inter'] == 0) {
            $errors[] = "Vous ne pouvez pas créer un fiche d'intervention avec comme Nature/Type 'FI ancienne version', Merci";
        }
        if (!count($errors)) {
            $fi = $this->getInstance('bimptechnique', 'BT_ficheInter');
            $id_new_fi = $fi->createFrom('contrat', $this, $data);
        }
        if ($id_new_fi > 0) {
            $callback = 'window.open("' . DOL_URL_ROOT . '/bimpfi/index.php?fc=fi&id=' . $id_new_fi . '")';
        } else {
            $errors[] = "La FI n'a pas été créée";
        }
        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function renderTechsInput()
    {
        global $user, $langs;
        $html = '';
        $values = "";
        $input = BimpInput::renderInput('search_user', 'techs_add_value');
        $content = BimpInput::renderMultipleValuesInput($this, 'techs', $input, $values);
        $html .= BimpInput::renderInputContainer('techs', '', $content, '', 0, 1, '', array('values_field' => 'techs'));

        return $html;
    }

    public function getTotalFi($tms)
    {
        $ficheInter = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        return $ficheInter->time_to_qty($ficheInter->timestamp_to_time($tms)) * BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique');
    }

    public function getMargePrevisionnel($total_fis)
    {
        return $total_fis / ($this->getJourTotal() - $this->getJourRestant()) * $this->getJourTotal();
    }

    public function getTmsArray()
    {
        $fis = BimpCache::getBimpObjectObjects('bimptechnique', 'BT_ficheInter', array('fk_contrat' => $this->id));
        $tms_in_contrat = 0;
        $tms_out_contrat = 0;

        foreach ($this->getListFi() as $ficheInter) {
            $childrenFiche = $ficheInter->getChildrenList("inters");
            foreach ($childrenFiche as $id_child) {
                $child = $ficheInter->getChildObject('inters', $id_child);
                $duration = $child->getData('duree');
                if ($child->getData('id_line_contrat') || $child->getData('type') == 5 || $ficheInter->getData(('new_fi')) < 1) {
                    $tms_in_contrat += $duration;
                } else {
                    $tms_out_contrat += $duration;
                }
            }
        }

        return (object) Array(
                    'in'  => $tms_in_contrat,
                    'out' => $tms_out_contrat
        );
    }

    public function getMargeInter()
    {
        if ($this->isLoaded()) {
            $total_contrat = $this->getTotalContrat();
            $in_out_tms = $this->getTmsArray();
            $total_fis = $this->getTotalFi($in_out_tms->in);

            return $total_contrat - $total_fis;
        }



        return null;
    }

    public function displayMargeInter()
    {
        return BimpTools::displayMoneyValue($this->getMargeInter(), "EUR", true);
    }

    public function getListFi()
    {
        if ($this->isLoaded()) {
            return BimpCache::getBimpObjectObjects('bimptechnique', 'BT_ficheInter', ['fk_contrat' => $this->id]);
        }
        return Array();
    }

    public function renderThisStatsFi($display = true, $in_contrat = true)
    {
        $html = "";

        $fis = $this->getListFi();
        $in_out_tms = $this->getTmsArray();
        $ficheInter = $this->getInstance('bimptechnique', 'BT_ficheInter');
        $total_fis = 0;
        $total_tms = $in_out_tms->in;
        $total_tms_not_contrat = $in_out_tms->out;
        $total_fis = $this->getTotalFi($total_tms);
        $previsionelle = 0;

        if ($this->getJourTotal() > 0 && $this->getJourTotal() > $this->getJourRestant())
            $previsionelle = $this->getMargePrevisionnel($total_fis);

        $marge = $this->getMargeInter();
        $marge_previsionelle = ($this->getTotalContrat() - $previsionelle);

        $class = 'warning';
        $icone = 'arrow-right';

        if ($marge > 0) {
            $class = 'success';
            $icone = 'arrow-up';
        } elseif ($marge < 0) {
            $class = 'danger';
            $icone = 'arrow-down';
        }

        $html .= "<strong>";
        if ($in_contrat) {

            $html .= "Nombre de FI: " . count($fis) . '<br />';
            $html .= "Nombre d'heures dans le contrat: " . $ficheInter->timestamp_to_time($total_tms) . '<br />';
            $html .= "Nombre d'heures hors du contrat: " . $ficheInter->timestamp_to_time($total_tms_not_contrat) . ' (non pris en compte)<br />';
            $html .= "Coût technique: " . price($total_fis) . " € (" . BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique') . " €/h * " . $ficheInter->timestamp_to_time($total_tms) . ")<br />";
            $html .= "Coût prévisionel: " . price($previsionelle) . " €<br />";
            $html .= "Vendu: " . "<strong class='warning'>" . price($this->getTotalContrat()) . "€</strong><br />";
            $html .= "Marge: " . "<strong class='$class'>" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong><br />";
            $html .= "Marge Prévisionelle: " . "<strong class='$class'>" . BimpRender::renderIcon($icone) . " " . price($marge_previsionelle) . "€</strong><br />";
        } else {
            $html .= "Contrat: " . "<strong class='$class'>" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong><br />";
        }
        $html .= '</strong>';

        if ($display)
            return $html;
        else
            return $marge;
    }

    public function getTotalInterTime()
    {
        $temps = 0;
        $fiche = $this->getInstance('bimptechnique', 'BT_ficheInter');
        $fiches = $fiche->getList(['fk_contrat' => $this->id]);
        foreach ($fiches as $index => $infos) {
            $fiche->fetch($infos['rowid']);
            $allInters = $fiche->getChildrenList('inters');
            foreach ($allInters as $id) {
                $inter = $fiche->getChildObject('inters', $id);
                $temps += $inter->getData('duree');
            }
        }
        return $temps;
    }

    public function getAllServices($field = 'fk_product')
    {
        $servicesId = [];
        foreach ($this->dol_object->lines as $line) {
            $servicesId[] = $line->$field;
        }
        return $servicesId;
    }

    public function addLog($text)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong> Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }

        return $errors;
    }

    public function actionAnticipateClose($data, &$success)
    {
        global $user;

        $errors = [];

        if ($data['have_courrier'] == 0) {
            $errors[] = "Vous ne pouvez pas anticiper la clôture de ce contrat sans lettre de résiliation";
        }

        if ($this->isLoaded()) {
            $warnings = [];

            if (!count($errors)) {
                $this->updateField("end_date_reel", $data['end_date_reel']);
                $this->updateField('anticipate_close_note', $data['note_close']);
                $this->updateField('relance_renouvellement', 0);
                $success = "Date de fin défini avec succès";
                $dateClose = new DateTime($date['end_date_reel']);
                $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                $sujet = "Clôture anticipée du contrat " . $this->getRef() . " [" . $client->getRef() . "] - " . $client->getName();
                $message = "Bonjour,<br />La date du " . $dateClose->format('d/m/Y') . " a été choisie par " . $user->getNomUrl() . " "
                        . "comme date de fin anticipée du contrat " . $this->getNomUrl() . " pour le client "
                        . $client->getNomUrl() . ' - ' . $client->getName() . ' pour la raison suivante:';
                $message .= "<br /><br />" . $data['note_close'] . "<br /><br />Ce contrat ce clôturera automatiquement à cette date.";
                $addr_cc = ($commercial->getData('email') == $user->email) ? '' : $user->email;
                $bimpMail = New BimpMail($this, $sujet, $commercial->getData('email'), null, $message, null, $addr_cc);
                $bimpMail->send($errors);
            }
        } else {
            $errors[] = "ID du contrat absent";
        }

        return [
            'warnings' => $warnings,
            'errors'   => $errors,
            'success'  => $success
        ];
    }

    public function actionActivateContrat($data, &$success)
    {
        global $user;
        $errors = [];

        $dateEffecte = new DateTime($this->getData('date_start'));
        $date_now = new DateTime();

        $diff = $date_now->diff($dateEffecte);

        //$errors[] = print_r($diff,1);

        if (($diff->days > 10) && !$diff->invert) {
            $errors[] = "Ce contrat ne peut pas être activé car sa date d'effet est trop éloignée. Le groupe contrat recevra une demande d'activation 10 jours avant cette date";
        }

        if ($this->isLoaded() && !count($errors)) {



            $signed_doc = ($data['have_contrat_signed']) ? true : false;
            if ($signed_doc) {
                $contratChhild = $this->getContratChild();

                $this->closeContratChildWhenActivateRenewManual();

                $this->updateField('statut', self::CONTRAT_STATUS_ACTIVER);

                $success = "Le contrat " . $this->getData('ref') . ' a été activé avec succès';
                $this->addLog('Contrat activé');
                if ($this->getEndDate() != '') {
                    $this->updateField('end_date_contrat', $this->getEndDate()->format('Y-m-d'));
                }
                $this->dol_object->activateAll($user);
                $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');

                $commercial = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

                if ($commercial->isLoaded() && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE) {
                    $this->mail($this->email_facturation, self::MAIL_ACTIVATION, $commercial->getData('email'));
                } else {
                    $warnings[] = "Le mail n'a pas pu être envoyé, merci de contacter directement la personne concernée";
                }
                if (!$echeancier->find(['id_contrat' => $this->id]) && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE) {
                    $this->createEcheancier();
                }
            } else {
                if ($this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_TMP) {
                    $this->dol_object->activateAll($user);
                    $this->updateField('statut', self::CONTRAT_STATUS_ACTIVER_TMP);
                    $this->updateField('date_start_provisoire', date('Y-m-d'));
                    $dateForCloseNoSigned = new DateTime();
                    $dateForCloseNoSigned->add(new DateInterval("P14D"));
                    $this->addLog('Activation provisoire');
                    $commercialContrat = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                    $msg = "Votre contrat " . $this->getNomUrl() . " pour le client " . $client->getNomUrl() . " " . $client->getName() . " est activé provisoirement car il n'est pas revenu signé. Il sera automatiquement désactivé le " . $dateForCloseNoSigned->format('d / m / Y') . " si le nécessaire n'a pas été fait.";
                    //$errors[] = $msg;
                    mailSyn2("[CONTRAT] - Activation provisoire", $commercialContrat->getData('email'), null, $msg);
                    $this->addLog('Activation provisoire');
                } else {
                    $errors[] = "Ce contrat est déjà en activation provisoire";
                }
            }
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionAddAcompte($data, &$success)
    {
        $errors = [];
        $warnings = [];
        $success = "";
        if (addElementElement('contrat', 'facture', $this->id, $data['acc'])) {
            $success = "Acompte lié avec succès";
        }
        return [
            "success"  => $success,
            "warnings" => $warnings,
            "errors"   => $errors
        ];
    }

    public function getAcomptesClient()
    {

        $client = $this->getInstance('bimpcore', 'Bimp_Client', $this->getData('fk_soc'));
        $acc = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $liste = $acc->getList(['fk_soc' => $this->getData('fk_soc'), 'type' => 3]);
        $array_acc = [];
        foreach ($liste as $nb => $facture) {
            $acc->fetch($facture['rowid']);
            // Si l'accompte n'est pas déjà lier au contrat
            if (!count(getElementElement('contrat', 'facture', $this->id, $acc->id))) {
                $array_acc[$acc->id] = $acc->getData('facnumber');
            }
        }

        return $array_acc;
    }

    public function createEcheancier()
    {
        if ($this->isLoaded()) {
            $date = new DateTime($this->getData('date_start'));
            $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
            $instance->set('id_contrat', $this->id);
            $instance->set('next_facture_date', $date->format('Y-m-d H:i:s'));
            $instance->set('next_facture_amount', $this->reste_a_payer());
            $instance->set('validate', 1);
            $instance->set('client', $this->getData('fk_soc'));
            $instance->set('commercial', $this->getData('fk_commercial_suivi'));
            $instance->set('statut', 1);
            return $instance->create();
        }
    }

    public function displayCommercialClient()
    {

        if ($this->isLoaded()) {
            $id_commercial = $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $this->getData('fk_soc'));

            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $id_commercial);

            return $commercial->dol_object->getNomUrl();
        }
    }

    public static function getSearchRenouvellementInputArray()
    {
        return [0 => "Aucun", 1 => "Proposition", 2 => "Tacite"];
    }

    public function getRenouvellementNumberFromDate($date)
    {
        $datef = new DateTime();
        $datef->setTimestamp(strtotime($date));

        $debut = new DateTime();
        $fin = new DateTime();
        $Timestamp_debut = strtotime($this->getData('date_start'));
//            echo $datef->format('d / m / Y').'<br/>';
        $renouvellement = 0;
        if ($Timestamp_debut > 0 && $this->getData('duree_mois') > 0) {
            $debut->setTimestamp($Timestamp_debut);
            $fin->setTimestamp($Timestamp_debut);
            for ($i = 0; $i < 5; $i++) {
                $fin = $fin->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
                $fin = $fin->sub(new DateInterval("P1D"));
//                    echo($debut->format('d / m / Y').' '.$fin->format('d / m / Y').' '.$i.'av<br/>');
                if ($datef > $debut && $datef < $fin) {
                    $renouvellement = $i;
                    break;
                }
                $debut = $debut->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
//                    $fin = $fin->add(new DateInterval("P1D"));
            }
        }
        return $renouvellement;
    }

    public function displayRenouvellement()
    {
        if (($this->isLoaded())) {
            switch ($this->getData("tacite")) {
                case self::CONTRAT_RENOUVELLEMENT_NON:
                    $return = "Aucun";
                    break;
                case self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION:
                    $return = "Proposition";
                    break;
                case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                    $return = "Tacite";
                    break;
            }
            return '<b>' . $return . '</b>';
        }
    }

    public function getCommercialClient($object = false)
    {
        if ($this->isLoaded()) {
            $id_commercial = $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $this->getData('fk_soc'));

            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $id_commercial);
            if (!$object)
                return $commercial->id;
            elseif ($object)
                return $commercial;
        }
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'negatif_positif':
                if (count($values) > 0) {

                    $filters = Array('statut' => 11);

                    $list = BimpCache::getBimpObjectObjects("bimpcontract", 'BContract_contrat', $filters);

                    $in = [];

                    foreach ($list as $contrat) {
                        $marge = $contrat->getMargeInter();
                        if (in_array('0', $values)) {
                            if ($marge < 0)
                                $in[] = $contrat->id;
                        }
                        if (in_array('1', $values)) {
                            if ($marge > 0)
                                $in[] = $contrat->id;
                        }
                        if (in_array('2', $values)) {
                            if ($marge == 0)
                                $in[] = $contrat->id;
                        }
                    }

                    $filters[$main_alias . '.rowid'] = ['in' => $in];
                }
                break;

            case 'commercialclient':
                $alias = $main_alias . '___sc';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'societe_commerciaux',
                    'on'    => $alias . '.fk_soc = ' . $main_alias . '.fk_soc'
                );
                $filters[$alias . '.fk_user'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;

            case 'use_syntec':
                if (count($values) == 1) {
                    $alias = $main_alias . '___ce';
                    $joins[$alias] = array(
                        'alias' => $alias,
                        'table' => 'contrat_extrafields',
                        'on'    => $alias . '.fk_object = ' . $main_alias . '.rowid'
                    );
                    if (in_array('0', $values)) {
                        $sql = '(' . $alias . '.syntec = 0 OR ' . $alias . '.syntec IS NULL)';
                        $filters[$alias . '___custom_syntec'] = array(
                            'custom' => $sql
                        );
                    }
                    if (in_array('1', $values)) {
                        $filters[$alias . '.syntec'] = array(
                            '>' => '0'
                        );
                    }
                }
                break;

            case 'have_fi':
                if (count($values) == 1) {
                    $sql = "SELECT DISTINCT c.rowid FROM llx_contrat as c, llx_fichinter as f WHERE c.rowid = f.fk_contrat";
                    $res = $this->db->executeS($sql, 'array');
                    $in = [];
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                    if (in_array('1', $values)) {
                        $filters[$main_alias . '.rowid'] = [
                            'in' => $in
                        ];
                    }
                    if (in_array('0', $values)) {
                        $filters[$main_alias . '.rowid'] = [
                            'not_in' => $in
                        ];
                    }
                }
                break;
//            case 'end_date':
//                $in = [];
//                $borne = (object) $values[0];
//                $sql = "SELECT rowid FROM llx_contrat";
//                $all = $this->db->executeS($sql, 'array');
//                
//                $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
//                
//                foreach($all as $nb => $i) {
//                    $contrat->fetch($i['rowid']);
//                }
//                
//                echo '<pre>';
//                echo count($all);
//                
//                $filters['a.rowid'] = ['in' => $in];
//                break;
            case 'reconduction':
                $in = [];
                $included = [];
                $sql = "SELECT c.rowid FROM llx_contrat as c, llx_contrat_extrafields as e WHERE e.fk_object = c.rowid ";

                if (count($values) > 0) {

                    if (in_array('0', $values)) {
                        // Pas de reconduction
                        $included[] = 0;
                    }
                    if (in_array('1', $values)) {
                        $included[] = self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION;
                    }
                    if (in_array('2', $values)) {
                        foreach (self::$renouvellement as $code => $text) {
                            if ($code != self::CONTRAT_RENOUVELLEMENT_NON && $code != self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION)
                                $included[] = $code;
                        }
                    }

                    $sql .= ' AND e.tacite IN(' . implode(',', $included) . ')';

                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                    $filters[$main_alias . '.rowid'] = ['in' => $in];
                }
                break;
        }



        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getCommercialclientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $alias = 'sc';
        $joins[$alias] = array(
            'alias' => $alias,
            'table' => 'societe_commerciaux',
            'on'    => $alias . '.fk_soc = ' . $main_alias . '.fk_soc'
        );
        $filters[$alias . '.fk_user'] = $value;
    }

    public function isClosDansCombienDeTemps()
    {

        $aujourdhui = new DateTime();
        $finContrat = new DateTime($this->displayRealEndDate("Y-m-d"));
        $diff = $aujourdhui->diff($finContrat);
        if (!$diff->invert) {
            return $diff->d;
        }
        return 0;
    }

    public function closeFromCron($reason = "Contrat clos automatiquement")
    {
        $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
        global $user;
        if ($this->dol_object->closeAll($user) >= 1) {
            $this->updateField('statut', self::CONTRAT_STATUS_CLOS);
            $this->updateField('date_cloture', date('Y-m-d H:i:s'));
            $this->updateField('fk_user_cloture', 1);
            $this->addLog($reason);
            if ($echeancier->fetchBy('id_contrat', $this->id)) {
                $echeancier->updateField('statut', 0);
            }
        }
    }

    
    
    
    /*
     * 
     * test
     * 
     */
    
    public function checkContacts()
    {
        $errors = array();

        if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
            global $user;
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                // Vérif commercial suivi: 
                $tabContact = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
                if (count($tabContact) < 1) {
                    $ok = false;
                    $tabComm = $client->dol_object->getSalesRepresentatives($user);

                    // Il y a un commercial pour ce client
                    if (count($tabComm) > 0) {
                        $this->dol_object->add_contact($tabComm[0]['id'], 'SALESREPFOLL', 'internal');
                        $ok = true;

                        // Il y a un commercial définit par défaut (bimpcore)
                    } elseif ((int) BimpCore::getConf('user_as_default_commercial', null, 'bimpcommercial')) {
                        $this->dol_object->add_contact($user->id, 'SALESREPFOLL', 'internal');
                        $ok = true;
                        // L'objet est une facture et elle a une facture d'origine
                    } elseif ($this->object_name === 'Bimp_Facture' && (int) $this->getData('fk_facture_source')) {
                        $fac_src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('fk_facture_source'));
                        if (BimpObject::objectLoaded($fac_src)) {
                            $contacts = $fac_src->dol_object->getIdContact('internal', 'SALESREPFOLL');
                            if (count($contacts) > 0) {
                                $this->dol_object->add_contact($contacts[0]['id'], 'SALESREPFOLL', 'internal');
                                $ok = true;
                            }
                        }
                    }

                    if (!$ok) {
                        $errors[] = 'Pas de Commercial Suivi';
                    }
                }

                // Vérif contact signataire: 
                $tabContact = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
                if (count($tabContact) < 1) {
                    $this->dol_object->add_contact($user->id, 'SALESREPSIGN', 'internal');
                }
            }
        }
        return $errors;
    }
    
    public function actionTestContrat($data, &$success)
    {
        $errors = [];
        $warnings = [];

        $this->tryToValidate($errors);
        
        

        return Array('errors' => $errors, 'warnings' => $warnings, 'success' => $success);
    }

    public function closeContratChildWhenActivateRenewManual($fromCron = false):bool
    {

        global $user;

        $beforeContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');

        if ($beforeContrat->find(Array('next_contrat' => $this->id))) {

            if ($beforeContrat->isLoaded()) {

                if (!in_array($this->getData('statut'), Array(self::CONTRAT_STATUS_ACTIVER, self::CONTRAT_STATUS_ACTIVER_SUP, self::CONTRAT_STATUS_ACTIVER_TMP)) && $fromCron) {
                    return 0;
                }

                $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');

                if ($echeancier->find(Array('id_contrat' => $beforeContrat->id))) {

                    $dateFinNow = new DateTime();
                    $dateFinBefore = $this->displayRealEndDate('Y-m-d');

                    if ($dateFinBefore > $dateFinNow) {
                        if ($beforeContrat->dol_object->closeAll($user) >= 1) {

                            $beforeContrat->updateField('statut', self::CONTRAT_STATUS_CLOS);
                            $beforeContrat->updateField('date_cloture', date('Y-m-d H:i:s'));
                            $beforeContrat->updateField('fk_user_cloture', $user->id);
                            $echeancier->updateField('statut', 0);
                            $echeancier->updateField('validate', 0);

                            $beforeContrat->addLog('Clos car contrat de renouvellement ' . $this->getRef());

                            if (!$fromCron)
                                $this->addLog($beforeContrat->getRef() . ' clos suite à l\'activation de ce contrat');
                            else
                                $this->addLog($beforeContrat->getRef() . ' clos automatiquement car ce contrat en est le renouvellement');

                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public function actionClose($data, &$success)
    {
        global $user;
        $errors = [];
        $warnings = [];
        $success = 'Contrat clos avec succès';
        $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
        if ($this->dol_object->closeAll($user) >= 1) {
            $this->updateField('statut', self::CONTRAT_STATUS_CLOS);
            $this->updateField('date_cloture', date('Y-m-d H:i:s'));
            $this->updateField('fk_user_cloture', $user->id);
            $this->addLog('Contrat clos');
            if ($echeancier->find(['id_contrat' => $this->id])) {
                $echeancier->updateField('statut', 0);
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionUpdateSyntec()
    {
        $syntec = file_get_contents("https://syntec.fr/");
        if (preg_match('/<div class="indice-number"[^>]*>(.*)<\/div>/isU', $syntec, $matches)) {
            $indice = str_replace(' ', "", strip_tags($matches[0]));
            BimpCore::setConf('current_indice_syntec', str_replace(' ', "", strip_tags($matches[0])));
            $success = "L'indice Syntec s'est mis à jours avec succès";
        } else {
            return "Impossible de récupérer l'indice Syntec automatiquement, merci de le rensseigner manuellement";
        }
        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function getCurrentSyntecFromSyntecFr()
    {
        $syntec = file_get_contents("https://syntec.fr/");
        if (preg_match('/<div class="indice-number"[^>]*>(.*)<\/div>/isU', $syntec, $matches)) {
            $indice = str_replace(' ', "", strip_tags($matches[0]));
            return str_replace("\n", "", $indice);
        } else {
            return 0;
        }
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (BimpTools::getValue('type_piece')) {
            $id = 0;
            switch (BimpTools::getValue('type_piece')) {
                case 'propal':
                    $id = BimpTools::getValue('propal_client');
                    break;
                case 'commande':
                    $id = BimpTools::getValue('commande_client');
                    break;
                case 'facture_fourn':
                    $id = BimpTools::getValue('facture_fourn_client');
                    break;
            }
            if ($id == 0) {
                return "Il n'y à pas de pièce " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' pour ce client';
            } else {
                if (getElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id)) {
                    return "La piece " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' que vous avez choisi est déjà liée à ce contrat';
                } else {
                    addElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id);
                    $success = "La " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . " a été liée au contrat avec succès";
                }
            }
            return ['success' => $success, 'warnings' => $warnings, 'errors' => $errors];
        } else {

            $relance_renouvellement = BimpTools::getValue('relance_renouvellement');

            if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER && (BimpTools::getValue('periodicity') != $this->getInitData('periodicity'))) {
                $log = "Changement de la périodicitée de facturation de <strong>" . self::$period[$this->getInitData('periodicity')] . "</strong> à <strong>";
                $log .= self::$period[BimpTools::getValue('periodicity')] . "</strong>";
                $this->addLog($log);
            }

            if (BimpTools::getValue('relance_renouvellement') != $this->getInitData('relance_renouvellement') && $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
                $new_state = (BimpTools::getValue('relance_renouvellement') == 0) ? 'NON' : 'OUI';
                $this->addLog('Changement statut relance renouvellement à : ' . $new_state);
            }
            if (BimpTools::getValue('facturation_echu') != $this->getInitData('facturation_echu') && $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
                $new_state = (BimpTools::getValue('facturation_echu') == 0) ? 'NON' : 'OUI';
                $this->addLog('Changement statut facturation à terme échu à : ' . $new_state);
            }
            if (BimpTools::getValue('label') != $this->getInitData('label') && $this->getData(('statut')) != self::CONTRAT_STATUS_BROUILLON) {
                $this->addLog('Nouveau label contrat: ' . BimpTools::getValue('label'));
            }
            if (BimpTools::getValue('date_start') != $this->getInitData('date_start') && $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) {
                $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
                if ($echeancier->find(['id_contrat' => $this->id], 1)) {
                    $errors[] = $echeancier->updateField("next_facture_date", BimpTools::getValue('date_start') . ' 00:00:00');
                }
                $this->addLog("Date d'effet du contrat changer à " . BimpTools::getValue('date_start'));
            }

            return parent::update($warnings, $force_update);
        }
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        global $langs;
        //$this->dol_object->generateDocument('contrat_BIMP_maintenance', $langs);
    }

    public function getListClient($object)
    {

        $list = $this->db->getRows($object, 'fk_soc = ' . $this->getData('fk_soc'));
        $return = [];

        foreach ($list as $l) {
            $instance = $this->getInstance('bimpcommercial', 'Bimp_' . ucfirst($object), $l->rowid);
            $return[$instance->id] = $instance->getData('ref') . " - " . $instance->getData('libelle');
        }
        //print_r($return);
        return $return;
    }
    /* GETTERS */

    public function getModeReglementClient()
    {
        global $db;
        BimpTools::loadDolClass('societe');
        $client = new Societe($db);
        $client->fetch($this->getData('fk_soc'));
        return $client->mode_reglement_id;
    }
    
    public function getConditionReglementClient() {
        global $db;
        BimpTools::loadDolClass('societe');
        $client = new Societe($db);
        $client->fetch($this->getData('fk_soc'));
        return $client->cond_reglement_id;
    }
    
    public function getConditionReglementsArray() {
        
        
        
        return $cond;
    }

    public function getEndDate()
    {
        $debut = new DateTime();
        $fin = new DateTime();
        $Timestamp_debut = strtotime($this->getData('date_start'));
        if ($Timestamp_debut > 0) {
            $debut->setTimestamp($Timestamp_debut);
            $fin->setTimestamp($Timestamp_debut);
            if ($this->getData('duree_mois') > 0)
                $fin = $fin->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
            $fin = $fin->sub(new DateInterval("P1D"));
            return $fin;
        }
        return '';
    }

    public function displayDateNextFacture()
    {
        if ($this->isLoaded()) {
            $echeancier = $this->getInstance('bimpcontract', "BContract_echeancier");
            $fin = false;
            if ($echeancier->find(['id_contrat' => $this->id])) {
                $next_facture_date = $echeancier->getData('next_facture_date');
                if ($next_facture_date == 0) {
                    $fin = true;
                    $return = "<b class='important'>&Eacute;chéancier totalement facturé</b>";
                } else {
                    $return = $next_facture_date;
                }
            } else {
                $return = $this->getData('date_start');
            }
            if (!$fin) {
                $return = new DateTime($return);
                $return = $return->format('d / m / Y');
            }
            return $return;
        }
    }

    public function displayRef()
    {
        return $this->getData('ref') . ' - ' . $this->getName();
    }

    public function getTitleEcheancier()
    {
        return '&Eacute;ch&eacute;ancier du contrat N°' . $this->displayRef();
    }

    public function displayEndDate()
    {
        $fin = new DateTime($this->displayRealEndDate("Y-m-d"));
        if ($fin > 0)
            return $fin->format('d/m/Y');
    }

    public function displayRealEndDate($format = "d / m / Y")
    {
        $fin = null;
        $suup_all = false;

        if ($this->getData('end_date_reel') && $this->getData('anticipate_close_note')) {
            $suup_all = true;
            $fin = new DateTime($this->getData('end_date_reel'));
        }

        if (!$suup_all) {
            if (!$this->getData('date_end_renouvellement')) {
                if ($this->getData('end_date_reel')) {
                    $fin = new DateTime($this->getData('end_date_reel'));
                } elseif ($this->getData('end_date_contrat')) {
                    $fin = new DateTime($this->getData('end_date_contrat'));
                } else {
                    $fin = $this->getEndDate();
                }
            } else {
                $fin = new DateTime($this->getData('date_end_renouvellement'));
            }
        }

        if (is_object($fin))
            return $fin->format($format);
        else
            return '';
    }

    public function getName($withGeneric = true)
    {
        // getName() doit renvoyer le nom sans aucun formatage html
        $objet = $this->getData('objet_contrat');
//        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        return "<span><i class='fas fa-" . self::$objet_contrat[$objet]['icon'] . "' ></i> " . self::$objet_contrat[$objet]['label'] . "</span>";

//        return self::$objet_contrat[$this->getData('objet_contrat')];
    }

    public function getIndiceSyntec()
    {
        // Eviter ce genre de fonction inutile (on ne va pas créer une fonction pour chaque param de conf!)
        // Pour rappel : pas besoin de callback dans les yml, utiliser:
        //   conf: 
        //      module: ...
        //      name: ...
        return BimpCore::getConf('current_indice_syntec');
    }

    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }

    public function getClientContactsArray()
    {
        $id_client = $this->getAddContactIdClient();
        return self::getSocieteContactsArray($id_client, false);
    }

    public function actionReopen($data, &$success)
    {
        if (count(getElementElement('contrat', 'facture', $this->id))) {
            $errors[] = "Vous ne pouvez pas supprimer cet échéancier car il y à une facture dans celui-ci";
        }
        if (!count($errors)) {
            $success = "Contrat ré-ouvert avec succès";
            $this->updateField('statut', self::CONTRAT_STATUS_WAIT);
            $this->addLog('Contrat ré-ouvert');
            foreach ($this->dol_object->lines as $line) {
                $the_line = $this->getInstance('bimpcontract', 'BContract_contratLine', $line->id);
                $the_line->updateField('statut', $the_line->LINE_STATUT_INIT);
            }
            $this->db->delete('bcontract_prelevement', 'id_contrat = ' . $this->id);
        }
        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function turnOffEcheancier()
    {
        $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
        if ($echeancier->find(['id_contrat' => $this->id])) {
            $echeancier->updateField('statut', 0);
        }
    }

    public function actionAbort($data = [], &$success)
    {

        if ($this->isLoaded()) {

            $errors = $this->updateField('statut', self::CONTRAT_STATUT_ABORT);

            if (!count($errors)) {
                $this->turnOffEcheancier();
                $this->addLog("Contrat abandonné");
                $success = "Le contrat à bien été abandoné";
            }

            return [
                'errors'   => $errors,
                'warnings' => [],
                'success'  => $success
            ];
        }
    }

    public function actionRefuse($data = [], &$success)
    {

        if ($this->isLoaded()) {

            $errors = $this->updateField('statut', self::CONTRAT_STATUS_REFUSE);

            if (!count($errors)) {
                $this->turnOffEcheancier();
                $this->addLog("Contrat refusé par le client");
                $success = "Le contrat à bien été notifié comme refusé";
            }

            return [
                'errors'   => $errors,
                'warnings' => [],
                'success'  => $success
            ];
        }
    }

    public function isFactAuto()
    {

        $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        if ($instance->find(['id_contrat' => $this->id])) {

            if ($instance->getData('validate') == 1)
                return 1;
        }

        return 0;
    }

    public function actionAutoFact($data, &$success)
    {

        $warnings = [];
        $success = "";
        $errors = [];

        if (!$this->getData('entrepot') && $this->useEntrepot())
            $errors[] = "La facturation automatique ne peut être activée car le contrat n'a pas d'entrepot";

        if (!count($errors)) {
            $instance = $this->getInstance('bimpcontract', 'BContract_echeancier', $data['e']);
            $errors = $instance->updateField('validate', $data['to']);

            if (!count($errors)) {
                if ($data['to'] == 1) {
                    // Le contrat passe en facturation auto ON
                    $success = 'La facturation automatique a été activée';
                } else {
                    // Le contrat passe en facturation auto OFF
                    $success = 'La facturation automatique a été désactivée';
                }
                $this->addLog($success);
            }
        }


        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionPlanningInter($data, &$success = '')
    {
        $errors = [];
        $success = 'Fiche inter créée avec succès';

        if (count($data['techs'])) {
            $errors[] = "Vous ne pouvez pas plannifier une intervention sans au moins un techhnicien";
        } else {
            $instance = $this->getInstance('bimptechnique', 'BT_ficheInter');
            $errors = $instance->createFromContrat($this, $data);
        }

        return array(
            'errors'   => $errors,
            'warnings' => array()
        );
    }

    public function getTicketsSupportClientArray()
    {
        $tickets = [];

        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        $list = $ticket->getList(['id_client' => $this->getData('fk_soc')]);

        foreach ($list as $nb => $infos) {
            $ticket->fetch($infos['id']);
            $statut = $ticket->getData('status');

            $display_statut = "<strong class='" . BS_Ticket::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
            $display_statut .= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";

            $tickets[$ticket->id] = $ticket->getRef() . " (" . $display_statut . ") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>';
        }

        return $tickets;
    }

    public function getMsgsPlanningFi()
    {
        $html = '';

        $html .= "<b>" . BimpRender::renderIcon('warning') . ' Informations sur la création des fiches d\'interventions via ce formulaire</b>';
        $html .= "<p>Si une FI existe déjà pour le ou les techniciens choisis dans la formaulaire, alors la fiche d'intervention ne sera pas créer.</p>";
        $html .= "<p>Par contre la description et les eventuelles commandes et tickets seront rajoutés à ces FI</p>";
        return $html;
    }

    public function getTypeActionCommArray()
    {

        $actionComm = [];
        $acceptedCode = ['ATELIER', 'DEP_EXT', 'HOT', 'INTER', 'INTER_SG', 'AC_INT', 'LIV', 'RDV_INT', 'RDV_EXT', 'AC_RDV', 'TELE', 'VIS_CTR'];
        $list = $this->db->getRows('c_actioncomm', 'active = 1');
        foreach ($list as $nb => $stdClass) {
            if (in_array($stdClass->code, $acceptedCode)) {
                $actionComm[$stdClass->id] = $stdClass->libelle;
            }
        }
        return $actionComm;
    }

    public function getCommandesClientArray()
    {
        $commandes = [];

        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        $list = $commande->getList(['fk_soc' => $this->getData('fk_soc')]);

        foreach ($list as $nb => $infos) {
            $commande->fetch($infos['rowid']);
            $statut = $commande->getData('fk_statut');

            $display_statut = "<strong class='" . Bimp_Commande::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(Bimp_Commande::$status_list[$statut]['icon']);
            $display_statut .= " " . Bimp_Commande::$status_list[$statut]['label'] . "</strong>";

            $commandes[$commande->id] = $commande->getRef() . " (" . $display_statut . ") ";
        }
        return $commandes;
    }

    public function actionTacite($data, &$success)
    {

        return $this->tacite(false);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {

        $un_contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');

        if ($un_contrat->find(['next_contrat' => $this->id])) {
            $warnings = $un_contrat->updateField('next_contrat', null);
            $un_contrat->addLog("Contrat de renouvellement " . $this->getRef() . " supprimé");
        }

        return parent::delete($warnings, $force_delete);
    }

    public function actionManuel($data, &$success)
    {
        return $this->manuel();
    }

    public function manuel()
    {

        $errors = Array();
        $warnings = Array();

        $callback = "";
        $this->actionUpdateSyntec();
        $for_date_end = new DateTime($this->displayRealEndDate("Y-m-d"));
        $new_contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        if ((int) BimpCore::getConf('USE_ENTREPOT'))
            $new_contrat->set('entrepot', $this->getData('entrepot'));
        $new_contrat->set('fk_soc', $this->getData('fk_soc'));
        $new_contrat->set('date_contrat', null);
        $new_contrat->set('date_start', $for_date_end->add(New DateInterval('P1D'))->format('Y-m-d'));
        $new_contrat->set('objet_contrat', $this->getData('objet_contrat'));
        $new_contrat->set('fk_commercial_signature', $this->getData('fk_commercial_signature'));
        $new_contrat->set('fk_commercial_suivi', $this->getdata('fk_commercial_suivi'));
        $new_contrat->set('periodicity', $this->getData('periodicity'));
        $new_contrat->set('gti', $this->getData('gti'));
        $new_contrat->set('duree_mois', $this->getData('duree_mois'));
        $new_contrat->set('tacite', $this->getData('initial_renouvellement'));
        $new_contrat->set('initial_renouvellement', $this->getData('initial_renouvellement'));
        $new_contrat->set('moderegl', $this->getData('moderegl'));
        $new_contrat->set('note_public', $this->getData('note_public'));
        $new_contrat->set('note_private', $this->getData('note_private'));
        $new_contrat->set('ref_ext', $this->getData('ref_ext'));
        $new_contrat->set('ref_customer', $this->getData('ref_customer'));
        if (/* $this->getData('syntec') > 0 && */ BimpTools::getValue('use_syntec')) {
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        } else {
            $new_contrat->set('syntec', 0);
        }

        $addLabel = "";
        if ($this->getData('label')) {
            $addLabel = " - " . $this->getData('label');
        }

        $new_contrat->set('label', "Renouvellement contrat: " . $this->getRef() . $addLabel);
        $new_contrat->set('relance_renouvellement', 1);
        $new_contrat->set('secteur', $this->getData('secteur'));

        $errors = $new_contrat->create($warnings);

        if (!count($errors)) {

            $callback = "window.open('" . DOL_URL_ROOT . "/bimpcontract/?fc=contrat&id=" . $new_contrat->id . "')";
            $count = $this->db->getCount('contrat', 'ref LIKE "' . $this->getRef() . '%"', 'rowid');
            $new_contrat->updateField('ref', $this->getRef() . '-' . $count);
            $this->addLog("Création du contrat de renouvellement numéro " . $new_contrat->getData('ref'));
            addElementElement('contrat', 'contrat', $this->id, $new_contrat->id);
            $new_contrat->copyContactsFromOrigin($this);
            $this->updateField('next_contrat', $new_contrat->id);
            $children = $this->getChildrenList("lines", Array("renouvellement" => 0));
            foreach ($children as $id_child) {

                $child = $this->getChildObject("lines", $id_child);

                $neew_price = $child->getData('subprice');
                if ($this->getData('syntec') > 0 && BimpTools::getValue('use_syntec')) {
                    $neew_price = $child->getData('subprice') * (BimpCore::getConf('current_indice_syntec') / $this->getData('syntec'));
                }
                $new_contrat->dol_object->pa_ht = $child->getData('buy_price_ht'); // BUG DéBILE DOLIBARR
                $createLine = $new_contrat->dol_object->addLine(
                        $child->getData('description'), $neew_price, $child->getData('qty'), $child->getData('tva_tx'), 0, 0, $child->getData('fk_product'), $child->getData('remise_percent'), $for_date_end->add(new DateInterval("P1D"))->format('Y-m-d'), $for_date_end->add(new DateInterval('P' . $this->getData('duree_mois') . "M"))->format('Y-m-d'), 'HT', 0.0, 0, null, $child->getData('buy_price_ht'), Array('fk_contrat' => $new_contrat->id)
                );

                if ($createLine > 0) {
                    $new_line = $new_contrat->getChildObject('lines', $createLine);
                    $new_line->updateField('serials', $child->getData('serials'));
                } else {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_contrat));
                }
            }
        }

        return Array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $callback);
    }

    public function tacite($auto)
    {
        $errors = [];
        $warnings = [];

        $this->actionUpdateSyntec();
        $current_indice_syntec = $this->getData('syntec');
        $new_indice_syntec = BimpCore::getConf('current_indice_syntec');
        $current_renouvellement = $this->getData('current_renouvellement');
        $next_renouvellement = ($current_renouvellement + 1);
        $syntec_for_use_this_renouvellement = ($current_renouvellement == 0) ? $this->getData('syntec') : $this->getData('syntec_renouvellement');
        $duree_contrat = $this->getData('duree_mois');

        $new_date_start = new DateTime($this->displayRealEndDate("Y-m-d"));

        $new_date_start->add(new DateInterval("P1D"));
        $new_date_end = new dateTime($new_date_start->format('Y-m-d'));
        $new_date_end->add(new DateInterval("P" . $duree_contrat . "M"));
        $new_date_end->sub(new DateInterval('P1D'));

        $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_NON;

        switch ($this->getData('tacite')) {
            case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_NON;
                break;
            case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_1_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_2_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_3_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_4_FOIS;
                break;
            case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                $new_renouvellementTacite = self::CONTRAT_RENOUVELLEMENT_5_FOIS;
                break;
        }
        $errors[] = "SYNTEC: " . $current_indice_syntec;
        $errors[] = "NEW SYNTEC: " . $new_indice_syntec;
        $errors[] = "USE SYNTEC: " . $syntec_for_use_this_renouvellement;
        $errors[] = "CURRENT Renouvellement: " . $current_renouvellement;
        $errors[] = "NEXT Renouvellement: " . $next_renouvellement;
        $errors[] = "NEW DATE START: " . $new_date_start->format('d / m / Y');
        $errors[] = "NEW DATE END: " . $new_date_end->format('d / m / Y');
        $errors[] = '';

        $children = $this->getChildrenList("lines", ['renouvellement' => $current_renouvellement]);
        foreach ($children as $id_child) {
            $child = $this->getChildObject("lines", $id_child);
            if ($current_indice_syntec > 0) {
                $new_price = ($child->getData('subprice') * ($new_indice_syntec / $current_indice_syntec));
            } else {
                $new_price = $child->getData('subprice');
            }

            $errors[] = "NEW PRICE LINE: " . price($new_price);
            $this->dol_object->pa_ht = $child->getData('buy_price_ht'); // BUG DéBILE DOLIBARR
            $createLine = $this->dol_object->addLine(
                    $child->getData('description'), $new_price, $child->getData('qty'), $child->getData('tva_tx'), 0, 0, $child->getData('fk_product'), $child->getData('remise_percent'), $new_date_start->format('Y-m-d'), $new_date_end->format('Y-m-d'), 'HT', 0.0, 0, null, $child->getData('buy_price_ht'), Array('fk_contrat' => $this->id)
            );

            if ($createLine > 0) {
                $tmpChild = $this->getChildObject("lines", $createLine);
                $tmpChild->updateField('serials', $child->getData('serials'));
                $tmpChild->updateField('renouvellement', $next_renouvellement);
                $child->updateField('statut', 5);
                $tmpChild->updateField('statut', 4);
                $errors = [];
                $success = "Contrat renouvellé avec succès";
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this));
            }
        }

        if (!count($errors)) {
            $this->updateField('tacite', $new_renouvellementTacite);
            $this->updateField('current_renouvellement', $next_renouvellement);
            $this->updateField('syntec_renouvellement', $new_indice_syntec);
            $this->updateField('relance_renouvellement', 1);
            $this->addLog('Renouvellement tacite N°' . $next_renouvellement);
            $this->updateField('date_end_renouvellement', $new_date_end->format('Y-m-d'));

            $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
            $echeancier->fetchBy('id_contrat', $this->id);
            $echeancier->updateField('next_facture_date', $new_date_start->format('Y-m-d') . "  00:00:00");
        }

        if ($auto) {
            return 1;
        } else {
            return [
                'success'  => $success,
                'warnings' => $warnings,
                'errors'   => $errors
            ];
        }
    }

    public function actionFactureSupp($data, &$success)
    {
        $warnings = [];
        $errors = [];

        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $facture->set('libelle', "Facture supplémentaire de votre contrat numéro " . $this->getRef());
        $facture->set('type', 0);
        $facture->set('fk_soc', $client->id);

        if (!$this->getData('entrepot') && $this->useEntrepot()) {
            return array("La facture ne peut pas être crée car le contrat n'a pas d'entrepôt");
        }

        if ($this->useEntrepot())
            $facture->set('entrepot', $this->getData('entrepot'));

        $facture->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $facture->set('fk_mode_reglement', ($this->getData('moderegl')) ? $this->getData('moderegl') : 2);
        $facture->set('datef', date('Y-m-d H:i:s'));
        $facture->set('ef_type', $this->getData('secteur'));
        $facture->set('model_pdf', 'bimpfact');
        $facture->set('ref_client', $this->getData('ref_customer'));
        $errors = $facture->create($warnings, true);

        if (!count($errors)) {
            if ($facture->dol_object->addLine(
                            "Facturation du reste à payer de votre contrat numéro " . $this->getRef(),
                            $this->reste_a_payer(),
                            1, 20, 0, 0, 0, 0, '', '', 0, 0, '', 'HT', 0, 0
                    )) {
                addElementElement("contrat", "facture", $this->id, $facture->id);
                $success = "Facture " . $facture->getRef() . " créée avec succès";
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionStopTacite($data, &$success)
    {

        $errors = [];
        $warnings = [];

        $this->set("tacite", 0);
        $this->set("relance_renouvellement", 0);
        $this->set('initial_renouvellement', 0);
        if ($this->update($warnings)) {
            $success = "La reconduction tacite a été annulée";
        }

        return Array('errors' => $errors, 'warnings' => $warnings, 'success' => $success);
    }

    public function actionRedefineEcheancier($data, &$success)
    {
        $errors = [];
        $warnings = [];

        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        $echeancier->find(['id_contrat' => $this->id]);

        $idForDelete = [];
        $id_forUpdate = [];
        foreach ($data['lines'] as $id_line) {
            $idForDelete[] = $id_line;
        }
        if (count($idForDelete) > 0)
            $errors = $this->db->delete("contratdet", 'rowid IN (' . implode(",", $idForDelete) . ')');

        foreach ($data['lines_activate'] as $id_line) {
            $id_forUpdate[] = $id_line;
        }
        if (count($id_forUpdate) > 0)
            $errors = $this->db->update('contratdet', Array('statut' => 4, 'renouvellement' => $data['current_renouvellement']), 'rowid IN(' . implode(",", $id_forUpdate) . ')');

        $errors = $this->updateField('date_end_renouvellement', $data['date_end_renouvellement']);
        $errors = $this->updateField('end_date_contrat', $data['date_end_renouvellement']);
        $errors = $echeancier->updateField('next_facture_date', '0000-00-00 00:00:00');
        $errors = $this->updateField('current_renouvellement', $data['current_renouvellement']);
        $errors = $this->updateField('initial_renouvellement', $data['initial_renouvellement']);
        $errors = $this->updateField('tacite', $data['tacite']);
        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function getLinesForList()
    {
        $lines = Array();

        $children = $this->getChildrenList("lines");

        foreach ($children as $id_child) {
            $child = $this->getChildObject("lines", $id_child);
            $lines[$id_child] = "Renouvellement: " . $child->getData('renouvellement') . " -> " . $child->displayData('fk_product');
        }

        return $lines;
    }

    public function getTotalContratAll()
    {
        
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();

        if ($user->admin) {
            $buttons[] = array(
                'label'   => 'TEST EN COURS',
                'icon'    => 'fas_retweet',
                'onclick' => $this->getJsActionOnclick('testContrat', array(), array(
                ))
            );
        }

        if ($this->isLoaded() && BimpTools::getContext() != 'public') {

            $status = $this->getData('statut');
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

            if ($user->admin) {
                $buttons[] = array(
                    'label'   => 'Annulé renew(ADMIN)',
                    'icon'    => 'fas_retweet',
                    'onclick' => $this->getJsActionOnclick('redefineEcheancier', array(), array(
                        'form_name' => 'redefineEcheancier'
                    ))
                );
            }

            if (BT_ficheInter::isActive() && $status == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimptechnique->plannified) {
                if ($user->admin == 1 || $user->id == 375) { // Pour les testes 
                    $buttons[] = array(
                        'label'   => 'Plannifier une intervention',
                        'icon'    => 'fas_calendar',
                        'onclick' => $this->getJsActionOnclick('planningInter', array(), array(
                            'form_name' => 'planningInter'
                        ))
                    );
                }
            }



            if ($status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUT_WAIT_ACTIVER) {
                $buttons[] = array(
                    'label'   => 'Contrat refusé par le client',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                        'confirm_msg' => "Cette action est irréverssible, continuer ?",
                    ))
                );
            }

            if ($status == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimpcontract->auto_billing) {
                if ($this->is_not_finish() && $this->reste_a_payer() > 0) {
                    $buttons[] = array(
                        "label"   => 'Facturation supplémentaire',
                        'icon'    => "fas_file-invoice",
                        'onclick' => $this->getJsActionOnclick('factureSupp', array(), array())
                    );
                }
            }
            if (($user->admin || $user->rights->bimpcontract->to_validate) && $this->getData('tacite') != 12 && $this->getData('tacite') != 0) {
                $buttons[] = array(
                    "label"   => 'Annuler la reconduction tacite',
                    'icon'    => "fas_hand-paper",
                    'onclick' => $this->getJsActionOnclick('stopTacite', array(), array(
                        'confirm_msg' => "Etes-vous sûr ? Cette action est  irréversible"
                    ))
                );
            }
            if (/* ($this->getData('tacite') == 12 || $this->getData('tacite') == 0) && */!$this->getData('next_contrat') && ($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_CLOS)) {
                $buttons[] = array(
                    'label'   => 'Renouveler par clonage du contrat (SN et sites inclus)',
                    'icon'    => 'fas_retweet',
                    'onclick' => $this->getJsActionOnclick('manuel', array(), array(
                        'form_name' => 'use_syntec'
                    ))
                );
            }

            $linked_factures = getElementElement('contrat', 'facture', $this->id);
            $e = $this->getInstance('bimpcontract', 'BContract_echeancier');

            if (!$this->getData('periodicity') && $this->getData('statut') == 1) {
                $buttons[] = array(
                    'label'   => 'Ancienne vers Nouvelle version',
                    'icon'    => 'fas_info',
                    'onclick' => $this->getJsActionOnclick('oldToNew', array(), array(
                        'form_name' => 'old_to_new'
                    ))
                );
            }

            if (($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER || $this->getData('statut') == self::CONTRAT_STATUS_CLOS) && !$this->getContratChild()) {
                if ($this->getData('tacite') == 12 || $this->getData('tacite') == 0) {
                    $button_label = "Renouveler par clonage du devis";
                    $button_icone = "fas_file-invoice";
                    $button_form = array();
                    $button_action = "createProposition";
                    $buttons[] = array(
                        'label'   => $button_label,
                        'icon'    => $button_icone,
                        'onclick' => $this->getJsActionOnclick($button_action, array(), $button_form)
                    );
                }
            }


            if ($e->find(['id_contrat' => $this->id])) {
                if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER && $user->rights->bimpcontract->auto_billing) {
                    $for_action = ($e->getData('validate') == 1) ? 0 : 1;
                    $label = ($for_action == 1) ? "Activer la facturation automatique" : "Désactiver la facturation automatique";

                    $buttons[] = array(
                        'label'   => $label,
                        'icon'    => "fas_play",
                        'onclick' => $this->getJsActionOnclick('autoFact', array('to' => $for_action, 'e' => $e->id), array(
                        ))
                    );
                }
            }
            if (($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) && $user->rights->bimpcontract->to_anticipate) {
                if (!$this->getData('end_date_reel') && !$this->getData('anticipate_close_note')) {
                    $buttons[] = array(
                        'label'   => 'Anticiper la cloture du contrat',
                        'icon'    => 'fas_clock',
                        'onclick' => $this->getJsActionOnclick('anticipateClose', array(), array(
                            'form_name' => 'anticipate'
                        ))
                    );
                }
            }
            if (($user->rights->bimpcontract->to_validate || $user->admin) && $this->getData('statut') != self::CONTRAT_STATUT_ABORT && $this->getData('statut') != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_REFUSE) {
                $buttons[] = array(
                    'label'   => 'Abandonner le contrat',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('abort', array(), array(
                        'confirm_msg' => "Cette action est irréverssible, continuer ?",
                    ))
                );
            }

            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER) && $user->rights->contrat->desactiver) {
                $buttons[] = array(
                    'label'   => 'Clore le contrat',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('close', array(), array(
                        'confirm_msg' => "Voulez vous clore ce contrat ?",
                )));
            }
            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER)) {
                $buttons[] = array(
                    'label'   => 'Envoyer par e-mail',
                    'icon'    => 'envelope',
                    'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ))
                );
            }
            if (($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER) && $user->rights->bimpcontract->to_reopen) {
                $buttons[] = array(
                    'label'   => 'Réouvrir le contrat',
                    'icon'    => 'fas_folder-open',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array())
                );

                $buttons[] = array(
                    'label'   => 'Mettre à jours l\'indice Syntec',
                    'icon'    => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('updateSyntec', array(), array())
                );
            }
            if (($status == self::CONTRAT_STATUS_WAIT || $status == self::CONTRAT_STATUS_ACTIVER_SUP || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUT_WAIT_ACTIVER || $status == self::CONTRAT_STATUS_VALIDE) && $user->rights->bimpcontract->to_validate && $status != self::CONTRAT_STATUS_WAIT) {
                $buttons[] = array(
                    'label'   => 'Activer le contrat',
                    'icon'    => 'fas_play',
                    'onclick' => $this->getJsActionOnclick('activateContrat', array(), array(
                        'form_name' => "have_signed",
                )));
            }
            if ($status == self::CONTRAT_STATUS_WAIT && $user->rights->bimpcontract->to_validate) {
                $buttons[] = array(
                    'label'   => 'Valider la conformitée du contrat',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validation', array(), array())
                );
            }
            if (!is_null($this->getData('date_contrat')) && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUT_WAIT_ACTIVER && $status != self::CONTRAT_STATUS_REFUSE) {
                $buttons[] = array(
                    'label'   => 'Dé-signer le contrat',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('unSign', array(), array())
                );
            }
            if (is_null($this->getData('date_contrat')) &&
                    ($status == self::CONTRAT_STATUS_ACTIVER || $status == self::CONTRAT_STATUS_ACTIVER_SUP || $status == self::CONTRAT_STATUS_ACTIVER_TMP || $status == self::CONTRAT_STATUS_VALIDE || $status == self::CONTRAT_STATUT_WAIT_ACTIVER)) {
                $buttons[] = array(
                    'label'   => 'Contrat signé',
                    'icon'    => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick('signed', array(), array(
                        'confirm_msg'      => "Voulez vous identifier ce contrat comme signé ?",
                        'success_callback' => $callback
                )));
            }

            if ($status == self::CONTRAT_STATUS_BROUILLON || $user->id == 460) {
                $buttons[] = array(
                    'label'   => 'Générer le PDF du contrat',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
                );
                $buttons[] = array(
                    'label'   => 'Générer le PDF du courrier',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
                );
            }

            if (($status != self::CONTRAT_STATUS_BROUILLON || $status == self::CONTRAT_STATUS_WAIT ) && ($user->rights->bimpcontract->to_generate)) {

                if ($status != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUS_ACTIVER_TMP && $status != self::CONTRAT_STATUS_ACTIVER_SUP) {
                    $buttons[] = array(
                        'label'   => 'Générer le PDF du contrat',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
                    );
                }

                if ($status != self::CONTRAT_STATUS_CLOS && $status != self::CONTRAT_STATUS_ACTIVER && $status != self::CONTRAT_STATUS_ACTIVER_TMP && $status != self::CONTRAT_STATUS_ACTIVER_SUP) {
                    $buttons[] = array(
                        'label'   => 'Générer le PDF du courrier',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
                    );
                }
            }

            if ($user->rights->contrat->creer && $status == self::CONTRAT_STATUS_BROUILLON) {
                $buttons[] = array(
                    'label'   => 'Demander la validation du contrat',
                    'icon'    => 'fas_share',
                    'onclick' => $this->getJsActionOnclick('demandeValidation', array(), array())
                );
            }
        }

        if ($user->id == 460 && $status == self::CONTRAT_STATUS_ACTIVER) {
            $buttons[] = array(
                'label'   => 'Ajouter un accompte',
                'icon'    => 'euro',
                'onclick' => $this->getJsActionOnclick('addAcompte', array(), array("form_name" => "addAcc"))
            );
        }
        
        
        $buttons[] = array(
            'label'   => 'Signer electroniquement le contrat',
            'icon'    => 'fas_play',
            'onclick' => $this->getJsActionOnclick('createSignature', array(), array(
                'form_name' => 'create_signature'
            )));
        
        return $buttons;
    }
    
    public function actionSignContrat($data, &$success)
    {
        
        
        // TODO Test des conditions de validation du crontrat
        
        $errors = array();
        $warnings = array();
        $success = 'Signature créée avec succès';
        $url = '';

        $open_public_access = (int) BimpTools::getArrayValueFromPath($data, 'open_public_access', 1);
        $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent());

        if ($open_public_access && !$id_contact) {
            $errors[] = 'Contact signataire obligatoire pour ouvrir l\'accès à la signature à distance';
        }

        if (!count($errors)) {
            $errors = $this->createSignature($open_public_access, $id_contact, $email_content, $warnings);

            if (!count($errors)) {
                $signature = $this->getChildObject('signature');

                if (BimpObject::objectLoaded($signature)) {
                    $url = $signature->getUrl();
                }
            }
        }
        

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        );
    }

    public function getLinesContrat()
    {
        $return = [];
        $lines = $this->getChildrenList('lines');
        foreach ($lines as $id) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $id);
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $line->getData('fk_product'));
            if ($product->getData('fk_product_type') == 1) {
                $content = $product->getData('ref') . " - " . $product->getData('label');
                if (count(json_decode($line->getData('serials')))) {
                    $content .= '<br />Numéros de série: ' . implode(', ', json_decode($line->getData('serials')));
                }
                $content .= "<br />Vendu HT: " . $line->getData('subprice') * $line->getData('qty') . "€";
                $return[$line->id] = $content;
            }
        }
        return $return;
    }

    public function actionCreateDI($data, &$success)
    {
        global $user;
        if ($data['lines'] == 0)
            $errors[] = "Il doit y avoir au moin une ligne de selectionnée";
        $techs = null;
        $lines = json_encode($data['lines']);
        $today = new DateTime();

        if (!count($errors)) {
            if ($data['techs'])
                $techs = json_encode($data['techs']);

            $di = $this->getInstance('bimptechnique', 'BT_demandeInter');
            $di->set("fk_soc", $this->getData('fk_soc'));
            $di->set("fk_contrat", $this->id);
            BimpTools::loadDolClass('synopsisdemandeinterv');
            $tmp_di = new Synopsisdemandeinterv($this->db->db);
            $di->set("ref", $tmp_di->getNextNumRef($this->getData('fk_soc')));
            $tmp_di = null;
            $datei = new DateTime($data['date']);
            $di->set("datei", $datei->getTimestamp());
            $di->set("datec", $today->format('Y-m-d H:i:s'));
            $di->set("fk_user_author", $user->id);
            $di->set('fk_statut', 0);
            $di->set('duree', $data['duree']);
            $di->set('description', $data['titre']);
            $di->set('techs', $techs);
            $di->set('contratLine', $lines);
            $di->set('fk_user_target', $data['tech']);
            $di->set('description', "");

            $errors = $di->create();

            if (!count($errors)) {
                $callback = 'window.open("' . DOL_URL_ROOT . '/bimptechnique/index.php?fc=di&id=' . $di->id . '")';
            }
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function isConformWithDate()
    {
        if (!$this->getData('end_date_contrat') && $this->getEndDate() == "") {
            return 0;
        }
        return 0;
    }

    public function actionCreateProposition($data, &$success)
    {
        global $user, $langs;
        $errors = [];
        $warnings = [];

        $callback = "";

        $date_livraison = new dateTime($this->getData('end_date_contrat'));
        $date_livraison->add(new DateInterval("P1D"));

        $propal = $this->getInstance('bimpcommercial', 'Bimp_Propal');
        $propal->set('fk_soc', $this->getData('fk_soc'));
        $propal->set('entrepot', $this->getData('entrepot'));
        $propal->set('ef_type', $this->getData('secteur'));
        $propal->set('fk_cond_reglement', 1);
        $propal->set('fk_mode_reglement', $this->getData('moderegl'));
        $propal->set('datep', date('Y-m-d'));
        if ($this->getData('label'))
            $propal->set('libelle', $this->getData('label'));
        else
            $propal->set('libelle', 'Renouvellement du contrat N°' . $this->getRef());
        $propal->set('date_livraison', $date_livraison->format('Y-m-d'));
        $oldSyntec = $this->getData('syntec');
        $this->actionUpdateSyntec();
        $newSyntec = BimpCore::getConf('current_indice_syntec');

        $errors = $propal->create();

        if (!count($errors)) {
            foreach ($this->dol_object->lines as $line) {
                $new_price = ($oldSyntec == 0) ? $line->subprice : ($line->subprice * ($newSyntec / $oldSyntec));
                $propal->dol_object->addLine(
                        $line->desc, $new_price, $line->qty, 20, 0, 0, $line->fk_product, $line->remise_percent, "HT", 0, 0, 0, -1, 0, 0, 0, $line->pa_ht
                );
            }
            $callback = 'window.open("' . DOL_URL_ROOT . '/bimpcommercial/index.php?fc=propal&id=' . $propal->id . '")';
            $propal->copyContactsFromOrigin($this);
            setElementElement('contrat', 'propal', $this->id, $propal->id);
            $success = "Creation du devis de renouvellement avec succès";
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionRenouvellementWithSyntecPropal($data, &$success)
    {
        global $user, $langs;
        $errors = [];
        $warnings = [];

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionRenouvellementWithSyntec($data, &$success)
    {

        global $user, $langs;
        $errors = [];
        $warnings = [];

        $canRenew = true;
        $renovTaciteReconduction = 0;
        switch ($this->getData('tacite')) {
            case self::CONTRAT_RENOUVELLEMENT_1_FOIS:
                $renovTaciteReconduction = 1;
                break;
            case self::CONTRAT_RENOUVELLEMENT_2_FOIS:
                $renovTaciteReconduction = 2;
                break;
            case self::CONTRAT_RENOUVELLEMENT_3_FOIS:
                $renovTaciteReconduction = 3;
                break;
            case self::CONTRAT_RENOUVELLEMENT_4_FOIS:
                $renovTaciteReconduction = 4;
                break;
            case self::CONTRAT_RENOUVELLEMENT_5_FOIS:
                $renovTaciteReconduction = 5;
                break;
            case self::CONTRAT_RENOUVELLEMENT_6_FOIS:
                $renovTaciteReconduction = 6;
                break;
        }

        if ($canRenew) {
            $oldSyntec = $this->getData('syntec');
            $this->actionUpdateSyntec();
            $newSyntec = BimpCore::getConf('current_indice_syntec');

            $id_for_source = $this->id;
            $ref_for_count = $this->getData('ref');
            if ($this->getData('contrat_source') > 0) {
                $contrat_source = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('contrat_source'));
                $ref_for_count = $contrat_source->getData('ref');
                $id_for_source = $contrat_source->id;
            }

            $count = $this->db->getCount('contrat', 'ref LIKE "' . $ref_for_count . '%"', 'rowid');

            $new = clone $this;
            $new->set('statut', self::CONTRAT_STATUS_BROUILLON);
            $new->set('ref', $this->getData('ref') . "_" . $count);
            $new->set('contrat_source', $id_for_source);
            $new->set('syntec', $newSyntec);
            $new->set('relance_renouvellement', 1);
            $new->set('date_contrat', null);
            $new->set('label', "RENOUVELLEMENT DU CONTRAT N°" . $this->getData('ref'));
            $date_for_dateTime = ($this->getData('end_date_contrat')) ? $this->getData('end_date_contrat') : $this->getEndDate()->format('Y-m-d');
            $date_start = new DateTime($date_for_dateTime);
            $date_start->add(new DateInterval("P1D"));
            $new->set('date_start', $date_start->format('Y-m-d'));
            $new->set('logs', "Contrat renouvellé TACITEMENT le <strong>" . date('d/m/Y') . "</strong> à <strong>" . date('H:i:s') . "</strong> par <strong>" . $user->getFullName($langs) . "</strong>");
            if ($this->getData('tacite') == 1) {
                $new->set('tacite', 12);
            } else {
                $new_renovTaciteReconduction = $renovTaciteReconduction - 1;
                switch ($new_renovTaciteReconduction) {
                    case 1:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_1_FOIS;
                        break;
                    case 2:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_2_FOIS;
                        break;
                    case 3:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_3_FOIS;
                        break;
                    case 4:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_4_FOIS;
                        break;
                    case 5:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_5_FOIS;
                        break;
                    case 6:
                        $to_tacite = self::CONTRAT_RENOUVELLEMENT_6_FOIS;
                        break;
                }
                $new->set('tacite', $to_tacite);
            }



            if ($new->create() > 0) {
                $callback = 'window.location.href = "' . DOL_URL_ROOT . '/bimpcontract/index.php?fc=contrat&id=' . $new->id . '"';
                foreach ($this->dol_object->lines as $line) {
                    $new_price = ($oldSyntec == 0) ? $line->subprice : (($line->subprice * ($newSyntec / $oldSyntec)));
                    $new->dol_object->pa_ht = $line->pa_ht; // BUG DéBILE DOLIBARR
                    $newLineId = $new->dol_object->addLine($line->desc, $new_price, $line->qty, $line->tva_tx, 0, 0, $line->fk_product, $line->remise_percent, $date_start->format('Y-m-d'), $new->getEndDate()->format('Y-m-d'), 'HT', 0.0, 0, null, (float) $line->pa_ht, 0, null, $line->rang);
                    $old_line = $this->getInstance('bimpcontract', 'BContract_contratLine', $line->id);
                    $new_line = $this->getInstance('bimpcontract', 'BContract_contratLine', $newLineId);
                    $new_line->updateField('serials', $old_line->getData('serials'));
                }
                $new->updateField('ref', $this->getData('ref') . "_" . $count);
                $new->copyContactsFromOrigin($this);
                setElementElement('contrat', 'contrat', $this->id, $new->id);
            }
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function renouvellementTaciteCron()
    {
        return 0;
    }

    public function actionDuplicate($data, &$success = Array())
    {
        $success = "Contrat cloner avec succès";
        $warnings = [];
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $new_contrat = clone $this;
        $new_contrat->id = null;
        $new_contrat->id = 0;
        $new_contrat->set('id', 0);
        $new_contrat->set('fk_statut', 1);
        $new_contrat->set('ref', '');
        $new_contrat->set('date_contrat', null);

        $errors = $new_contrat->create();

        return Array(
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUnSign($data, &$success = Array())
    {

        $warnings = [];
        $errors = [];

        if ($this->updateField('date_contrat', null)) {
            $this->addLog('Contrat marqué comme non-signé');
            $success = 'Contrat dé-signer';
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function getSyntecSite()
    {
        return "Pour connaitre l'indice syntec en vigueur, veuillez vous rendre sur le site internet <a href='https://www.syntec.fr' target='_blank'>https://www.syntec.fr</a>";
    }
    /* DISPLAY */

    public function display_card()
    {
        $card = "";

        $card .= '<div class="col-md-4">';

        $card .= "<div class='card_interface'>";
        //$card .= "<img src='".DOL_URL_ROOT."/viewimage.php?modulepart=societe&entity=1&file=381566%2F%2Flogos%2Fthumbs%2F".$societe->dol_object->logo."&cache=0' alt=''><br />";
        $card .= "<div class='img' ><i class='fas fa-" . self::$objet_contrat[$this->getData('objet_contrat')]['icon'] . "' ></i></div>";

        $card .= "<h1>" . $this->getRef() . "</h1>";

        if ($this->getData('label') != "")
            $card .= "<h1>" . $this->getData('label') . "</h1>";
        else {
            $card .= "<h1>" . self::$objet_contrat[$this->getData('objet_contrat')]['label'] . "</h1>";
        }
        $card .= '<h2>Durée du contrat : ' . $this->getData('duree_mois') . ' mois</h2>';
        if ($this->getData('periodicity')) {
            $card .= '<h2>Facturation : ' . self::$period[$this->getData('periodicity')] . '</h2>';
        }

        $card .= '<div>';
        if ($this->canClientViewDetail())
            $card .= '<a tool="Voir le contrat" flow="down" class="button" href="' . self::getPublicBaseUrl() . 'tab=contrats&content=card&id_contrat=' . $this->id . '"><i class="fas fa-eye"></i></a>';
        if ($this->isValide()) {
            $card .= '<span tool="Nouveau ticket support" flow="down" class="button" onclick="' . $this->getNewTicketSupportOnClick() . '"><i class="fas fa-plus"></i></span>';
        }
        $card .= '</div>';

        //$card .= '<a tool="Statistiques du contrat" flow="down" class="button" href="https://instagram.com/chynodeluxe"><i class="fas fa-leaf"></i></a>';
        $card .= '</div></div>';

        return $card;
    }
    /* RIGHTS */

    public function canEditField($field_name)
    {
        global $user;

        if ($this->getData('statut') == self::CONTRAT_STATUS_REFUSE)
            return 0;

        if ($this->getData('statut') == self::CONTRAT_STATUS_CLOS)
            return 0;

        if ($this->getData('statut') == self::CONTRAT_STATUS_WAIT && $user->rights->bimpcontract->to_validate)
            return 1;

        if ($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON)
            return 1;

        switch ($field_name) {
            case 'current_renouvellement':
            case 'tacite':
            case 'initial_renouvellement':
            case 'date_end_renouvellement':
                if ($user->admin)
                    return 1;
                break;
            case 'show_fact_line_in_pdf':
                if ($user->rights->bimpcontract->to_validate && ($this->getData("statut") != self::CONTRAT_STATUS_ACTIVER && $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_TMP && $this->getData('statut') != self::CONTRAT_STATUS_ACTIVER_SUP))
                    return 1;
                return 0;
                break;
            case 'periodicity':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->rights->bimpcontract->change_periodicity && !count($linked_factures))
                    return 1;
                else
                    return 0;
                break;
            case 'date_start':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->rights->bimpcontract->change_periodicity && !count($linked_factures))
                    return 1;
                else
                    return 0;
                break;
            case 'duree_mois':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->admin && !count($linked_factures))
                    return 1;
                break;
            case 'syntec':
                $linked_factures = getElementElement('contrat', 'facture', $this->id);
                if ($user->admin && !count($linked_factures))
                    return 1;
                break;
            case 'entrepot':
            case 'note_private':
            case 'fk_soc_facturation':
            case 'denounce':
            case 'fk_commercial_suivi':
            case 'fk_commercial_signature':
            case 'moderegl':
            case 'objet_contrat':
            case 'ref_customer':
            case 'relance_renouvellement':
            case 'facturation_echu':
            case 'label':
                return 1;
                break;
            default:
                return 0;
                break;
        }
    }

    public function canEdit()
    {
        return 1;
    }

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') !== (int) $this->getData('fk_soc')) {
                return 0;
            }

            if ($userClient->isAdmin()) {
                return 1;
            }

            if (in_array($this->id, $userClient->getAssociatedContratsList())) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientViewDetail()
    {
        global $userClient;
        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }
        return 0;
    }

    public function canDelete()
    {
        if ($this->getData('statut') != self::CONTRAT_STATUS_BROUILLON)
            return 0;
        return 1;
    }
    /* ACTIONS */

    public function actionSigned($data, &$success)
    {
        $success = 'Contrat signé avec succes';
        $warnings = [];
        $errors = [];

        $this->addLog('Contrat marqué comme signé');
        $this->updateField('date_contrat', date('Y-m-d HH:ii:ss'));

        if ($this->getData('statut') == self::CONTRAT_STATUS_VALIDE) {
            $this->updateField("statut", self::CONTRAT_STATUT_WAIT_ACTIVER);
        }

        $now = new DateTime();
        $effect = new dateTime($this->getData('date_start'));
        $sendMail = (strtotime($effect->format('Y-m-d')) > strtotime($now->format('Y-m-d'))) ? false : true;

        if ($this->getData('statut') != self::CONTRAT_STATUS_ACTIVER && $sendMail) {
            $this->mail($this->email_group, self::MAIL_SIGNED);
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionDemandeValidation($data, &$success)
    {
        $errors = [];
        
        
        
        if(!count($errors)) {
            $id_contact_type = $this->db->getValue('c_type_contact', 'rowid', 'code = "SITE" AND element = "contrat"');
            $id_contact_suivi_contrat = $this->db->getValue('c_type_contact', 'rowid', 'code = "CUSTOMER" AND element = "contrat"');
            $id_contact_facturation_email = $this->db->getValue('c_type_contact', 'rowid', 'code = "BILLING2" AND element = "contrat"');

            $have_contact = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_type)) ? true : false;
            $have_contact_suivi = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_suivi_contrat)) ? true : false;
            $have_facturation_email = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_facturation_email)) ? true : false;
            $verif_contact_suivi = true;

            if (!$have_contact) {
                $errors[] = "Il doit y avoir au moin un site d'intervention associé au contrat";
            } else {
                $liste_contact_site = $this->db->getRows('element_contact', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_type);
                foreach ($liste_contact_site as $contact => $infos) {
                    $contact_site = $this->getInstance('bimpcore', 'Bimp_Contact', $infos->fk_socpeople);
                    if (!$contact_site->getData('address'))
                        $errors[] = "Il n'y a pas d'adresse pour le site d'intervention. Merci d'en renseigner une. <br /> Contact: <a target='_blank' href='" . $contact_site->getUrl() . "'>#" . $contact_site->id . "</a>";
                }
            }
            if (!$have_facturation_email) {
                $errors[] = "Le contrat ne compte pas de contact facturation email";
            }
            if (!$have_contact_suivi) {
                $verif_contact_suivi = false;
                $errors[] = "Le contrat ne compte pas de contact client de suivi du contrat";
            }

            if ($verif_contact_suivi) {
                $contact = $this->getInstance('bimpcore', 'Bimp_Contact', $this->db->getValue('element_contact', 'fk_socpeople', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_suivi_contrat));
                if (!$contact->getData('email') || (!$contact->getData('phone') && !$contact->getData('phone_mobile'))) {
                    $errors[] = "L'email et le numéro de téléphone du contact est obligatoire pour demander la validation du contrat <br />Contact: <a target='_blank' href='" . $contact->getUrl() . "'>#" . $contact->id . "</a>";
                }
            }

    //        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
    //        if(!$client->getData('email') || !$client->getData('phone')) {
    //            $errors[] = "L'email et le numéro de téléphone du client sont obligatoire pour demander la validation du contrat <br /> Contact: <a target='_blank' href='".$client->getUrl()."'>#".$client->getData('code_client')."</a>";
    //        }
    //        if($this->dol_object->add_contact(1, 'SALESREPFOLL', 'internal') <= 0) {
    //            $errors[] = "Impossible d'ajouter un contact principal au contrat";
    //        }

            $have_serial = false;
            $serials = [];

            $contrat_lines = $this->getInstance('bimpcontract', 'BContract_contratLine');
            $lines = $contrat_lines->getList(['fk_contrat' => $this->id]);

            foreach ($lines as $line) {

                $serials = BimpTools::json_decode_array($line['serials']);

                if (count($serials))
                    $have_serial = true;
            }

            if (!$have_serial)
                $errors[] = "Il doit y avoir au moin un numéro de série dans une des lignes du contrat";
            if (!$this->getData('entrepot') && (int) BimpCore::getConf("USE_ENTREPOT"))
                $errors[] = "Il doit y avoir un entrepot pour le contrat";
            
            $modeReglementId = $this->db->getValue('c_paiement', 'id', 'code = "PRE"');

            if(!count($errors) && $this->getData('periodicity') != self::CONTRAT_PERIOD_AUCUNE && $this->getData('moderegl') != $modeReglementId) {
                $this->tryToValidate($errors);
            }
                
            
            if (!count($errors)) {
                $success = 'Validation demandée';
                $this->updateField('statut', self::CONTRAT_STATUS_WAIT);
                $msg = "Un contrat est en attente de validation de votre part. Merci de faire le nécessaire <br />Contrat : " . $this->getNomUrl();
                $this->addLog("Demande de validation");
                $this->mail($this->email_group, self::MAIL_DEMANDE_VALIDATION);
            }
        }
        
        

        return [
            'success'  => $success,
            'warnings' => $warnings,
            'errors'   => $errors
        ];
    }

    public function actionValidation($data, &$success)
    {
        global $user, $langs, $conf;
        if (preg_match('/^[\(]?PROV/i', $this->getData('ref'))) {
            $ref = BimpTools::getNextRef('contrat', 'ref', $this->getData('objet_contrat') . '{AA}{MM}-00');
        } else {
            $ref = $this->getData('ref');
        }
        $errors = $this->updateField('statut', self::CONTRAT_STATUS_VALIDE);

        if (!count($errors)) {
            if ($this->getData('contrat_source') && $this->getData('ref_ext')) {
                $annule_remplace = $this->getInstance('bimpcontract', 'BContract_contrat');
                if ($annule_remplace->find(['ref' => $this->getData('ref_ext')])) {
                    if ($annule_remplace->dol_object->closeAll($user)) {
                        $annule_remplace->updateField('statut', self::CONTRAT_STATUS_CLOS);
                    } else {
                        return "Impossible de fermé les lignes du contrat annulé et remplacé";
                    }
                } else {
                    return "Impossible de charger le contrat annulé et remplacé";
                }
            }

            // Changement de nom du répertoir pour les fichier
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            $oldref = $this->getData('ref');
            $newref = $ref;
            $dirsource = $conf->contract->dir_output . '/' . $oldref;
            $dirdest = $conf->contract->dir_output . '/' . $newref;

            // Pas génial, repris de la validation des contrats car impossible de valider un contrat avec un statut autre que 0 avec la fonction validate de la class contrat
            if (file_exists($dirsource) && $dirsource != $dirdest) {
                dol_syslog(get_class($this) . "::actionValidation Renomer => " . $dirsource . " => " . $dirdest);
                if (rename($dirsource, $dirdest)) {
                    dol_syslog("Renomer avec succès");
                    if (file_exists($dirdest . '/Contrat_' . $dirdest . '_Ex_OLYS.pdf')) {
                        unlink($dirdest . '/Contrat_' . $dirdest . '_Ex_OLYS.pdf');
                    }
                    if (file_exists($dirdest . '/Contrat_' . $dirdest . '_Ex_Client.pdf')) {
                        unlink($dirdest . '/Contrat_' . $dirdest . '_Ex_Client.pdf');
                    }

                    $listoffiles = dol_dir_list($conf->contract->dir_output . '/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
                    foreach ($listoffiles as $fileentry) {
                        $dirsource = $fileentry['name'];
                        $dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
                        $dirsource = $fileentry['path'] . '/' . $dirsource;
                        $dirdest = $fileentry['path'] . '/' . $dirdest;
                        rename($dirsource, $dirdest);
                    }
                }
            }

            $this->updateField('ref', $ref);
            $this->updateField('initial_renouvellement', $this->getData('tacite'));
            $this->addLog('Contrat validé');
            $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
            $commercial = $this->getInstance("bimpcore", 'Bimp_User', $this->getData('fk_commercial_suivi'));

            //mailSyn2("Contrat " . $this->getData('ref'), $commercial->getData('email'), null, $body_mail);

            $this->mail($commercial->getData('email'), self::MAIL_VALIDATION);

            $success = 'Le contrat ' . $ref . " a été validé avec succès";
            if (!BimpTools::getValue('use_syntec')) {
                $this->updateField('syntec', null);
            }

            $this->fetch($this->id);
            $this->actionGeneratePdf([], $success);
            //$this->dol_object->generateDocument('contrat_BIMP_maintenance', $langs);
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionMultiFact($data, &$success)
    {

        $errors = [];
        $warnings = [];
        $success = "";

        $ids = $data['id_objects'];

        foreach ($ids as $id) {
            $have_echeancier = true;
            $can_factured = true;
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $id);
            $statut = $contrat->getData('statut');
            if ($statut == self::CONTRAT_STATUS_BROUILLON) {
                $warnings[] = "Le contrat " . $contrat->getRef() . ' ne peut être facturé car il est en statut brouillon';
                $can_factured = false;
            }
            if ($statut == self::CONTRAT_STATUS_CLOS && $can_factured) {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il est en statut clos";
                $can_factured = false;
            }
            if (($statut == self::CONTRAT_STATUS_VALIDE || $statut == self::CONTRAT_STATUS_WAIT) && $can_factured) {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il n'est pas encore actif";
                $can_factured = false;
            }

            $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
            if ($echeancier->find(['id_contrat' => $contrat->id]) && $can_factured) {
                $next_facture_date = $echeancier->getData('next_facture_date');
                $date = new DateTime($next_facture_date);
                $today = new DateTime();
                if ($date->getTimestamp() <= $today->getTimestamp()) {
                    $forBilling = $contrat->renderEcheancier(false);
                    $id_facture = $echeancier->actionCreateFacture($forBilling);
                    if ($id_facture) {
                        $f = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        $s = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
                        $comm = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrat->getData('fk_commercial_suivi'));
                        $msg = "Une facture a été créée sur le conntrat " . $contrat->getRef() . ". Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider.<br />";
                        $msg .= "Client : " . $s->dol_object->getNomUrl() . '<br />';
                        $msg .= "Contrat : " . $contrat->dol_object->getNomUrl() . "<br/>Commercial : " . $comm->getNomUrl() . "<br />";
                        $msg .= "Facture : " . $f->dol_object->getNomUrl();
                        mailSyn2("Facturation Contrat [" . $contrat->getRef() . "]", $this->email_facturation, BimpCore::getConf('devs_email'), $msg);
                        $success = "Le contrat " . $contrat->getRef() . " a été facturé avec succès";
                    }
                } else {
                    $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car la période de facturation n'est ps encore arrivée";
                }
            } elseif ($can_factured) {
                $warnings[] = "Le contrat " . $contrat->getRef() . " ne peut être facturé car il n'a pas d'échéancier";
            }
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionFusion($data, &$success)
    {

        $errors = [];

        $ids_selected_contrats = $data['id_objects'];
        $success = "Les contrats ont bien été fusionnés";
        $last_socid = 0;

        if (count($ids_selected_contrats) == 1) {
            $errors[] = "Vous ne pouvez pas fusionner qu'un seul contrat";
        }

        foreach ($ids_selected_contrats as $id) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $id);

            if ($contrat->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $errors[] = 'Le contrat ' . $contrat->getRef() . ' ne peut être fusionné car il est en statut brouillon';
            }

            if ($contrat->getData('fk_soc') != $last_socid && $last_socid > 0) {
                $errors[] = 'Les contrat ne peuvent êtres fusionné car ce n\'est pas le même client';
            }

            $last_socid = $contrat->getData('fk_soc');
        }

        if (!count($errors)) {
            
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function getBulkActions()
    {
        $actions = array(
//            [
//                'label' => 'Fusionner les contrats sélectionnés',
//                'icon' => 'fas_sign-in-alt',
//                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'fusion\', {}, null, null, true)',
//                'btn_class' => 'setSelectedObjectsAction'
//            ],
            [
                'label'     => 'Facturer les contrats sélectionnés',
                'icon'      => 'fas_sign-in-alt',
                'onclick'   => 'setSelectedObjectsAction($(this), \'list_id\', \'multiFact\', {}, null, null, true)',
                'btn_class' => 'setSelectedObjectsAction'
            ]
        );
        if (1 || $this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Fichiers PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateBulkPdf', array(), array('single_action' => true))
            );
            $actions[] = array(
                'label'   => 'Fichiers Zip des PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateZipPdf', array(), array('single_action' => true))
            );
        }
        return $actions;
    }

    public function getPdfNamePrincipal($signed = false)
    {
        if($signed)
            return 'Contrat_' . $this->getRef() . '_Ex_OLYS_signed.pdf';
        
        return 'Contrat_' . $this->getRef() . '_Ex_OLYS.pdf';
    }

    public function isCommercialOfContrat()
    {

        global $user;

        $searchComm = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));

        if ($user->admin)
            return 1;

        if ($user->id == $searchComm->id)
            return 1;

        if ($searchComm->getData('statut') == 0) {
            if ($user->id == $this->getCommercialClient())
                return 1;
        }

        if (isset($user->rights->synopsiscontrat->renouveller) && $user->rights->synopsiscontrat->renouveller)
            return 1;

        return 0;
    }

    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array())
    {
        global $langs;

        $success = "PDF contrat généré avec Succes";
            $this->dol_object->generateDocument('contrat_BIMP_maintenance', $langs);

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            '"success' => $success
        ];
    }

    public function actionGeneratePdfCourrier($data, &$success)
    {
        global $langs;
        $errors = $warnings = array();
        $success = "PDF courrier généré avec Succes";
        $this->dol_object->generateDocument('contrat_courrier_BIMP_renvois', $langs);
        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function actionGeneratePdfEcheancier($data, &$success)
    {
        global $langs;
        $success = "PDF de l'échéancier généré avec succès";
    }
    /* OTHERS FUNCTIONS */

    public function create(&$warnings = array(), $force_create = false)
    {

        $errors = [];

        if (BimpTools::getValue('use_syntec') && !BimpTools::getValue('syntec')) {
            $errors[] = 'Vous devez rensseigner un indice syntec';
        }

        if ((BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 3)) {
            if (BimpTools::getValue('duree_mois') != 12 && BimpTools::getValue('duree_mois') != 24 && BimpTools::getValue('duree_mois') != 36) {
                $errors[] = 'Vous ne pouvez pas demander un renouvellement TACITE pour des périodes différentes de (12, 24 ou 36 mois)';
            }
        }
        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);

            if (!count($errors)) {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Contrat:' . $this->id . '}}');
                }
            }
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        $return = parent::fetch($id, $parent);
        //$this->autoClose();
        //verif des vieux fichiers joints
        $dir = DOL_DATA_ROOT . "/bimpcore/bimpcontract/BContract_contrat/" . $this->id . "/";
        $newdir = DOL_DATA_ROOT . "/contract/" . str_replace("/", "_", $this->getData('ref')) . "/";
        if (!is_dir($newdir))
            mkdir($newdir);

        if (is_dir($dir) && is_dir($newdir)) {
            $ok = true;
            $res = scandir($dir);
            foreach ($res as $file) {
                if (!in_array($file, array(".", "..")))
                    if (!rename($dir . $file, $newdir . $file))
                        $ok = false;
            }
            if (!$ok)
                mailSyn2("Probléme déplacement fichiers", 'tommy@bimp.fr', null, 'Probléme dep ' . $dir . $file . " to " . $newdir . $file);
            else
                rmdir($dir);
        }

        return $return;
    }

    public function autoClose()
    {//passer les contrat au statut clos quand toutes les enssiéne ligne sont close
        if ($this->id > 0 && $this->getData("statut") == 1 && new DateTime($this->displayRealEndDate("Y-m-d")) < new DateTime()) {
            $sql = $this->db->db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "contratdet` WHERE statut != 5 AND `fk_contrat` = " . $this->id);
            if ($this->db->db->num_rows($sql) == 0) {
                $this->updateField("statut", 2);
            }
        }
    }

    public function isValide()
    {
        if ($this->getData('statut') == 11) { // On est dans les nouveaux contrats
            return true;
        }
        return false;
    }
    /*     * ******* */
    /* RENDER */
    /*     * ******* */

    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;
            $ref = $this->getRef();

            $dir = $this->getFilesDir();

            if (!function_exists('dol_dir_list')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            }

            $files_list = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($files_list)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . $ref . urlencode('/');
                foreach ($files_list as $file) {
                    $html .= '<tr>';

                    $html .= '<td><a class="btn btn-default" href="' . $url . $file['name'] . '" target="_blank">';
                    $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($file['name'])) . ' iconLeft"></i>';
                    $html .= $file['name'] . '</a></td>';

                    $html .= '<td>';
                    if (isset($file['size']) && $file['size']) {
                        $html .= $file['size'];
                    } else {
                        $html .= 'taille inconnue';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ((int) $file['date']) {
                        $html .= date('d / m / Y H:i:s', $file['date']);
                    }
                    $html .= '</td>';

                    $html .= '<td class="buttons">';
                    $html .= BimpRender::renderRowButton('Aperçu', 'search', '', 'documentpreview', array(
                                'attr' => array(
                                    'target' => '_blank',
                                    'mime'   => dol_mimetype($file['name'], '', 0),
                                    'href'   => $url . $file['name'] . '&attachment=0'
                                )
                                    ), 'a');

                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
                        'confirm_msg'      => 'Veuillez confirmer la suppression de ce fichier',
                        'success_callback' => 'function() {bimp_reloadPage();}'
                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info', false);
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Documents PDF ' . $this->getLabel('of_the'), $html, '', array(
                        'icon'     => 'fas_file',
                        'type'     => 'secondary',
                        'foldable' => true
            ));
        }

        return $html;
    }

    public function renderEcheancier($display = true)
    {

        if ($this->isLoaded()) {

            $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');

            if ($display) {
                if ($this->getData('statut') != self::CONTRAT_STATUS_ACTIVER && !$instance->find(['id_contrat' => $this->id])) {
                    return BimpRender::renderAlerts('Le contrat n\'est pas activé', 'danger', false);
                }
                if (!$this->getData('date_start') || !$this->getData('periodicity') || !$this->getData('duree_mois')) {
                    return BimpRender::renderAlerts("Le contrat a été facturé avec l'ancienne méthode donc il ne comporte pas d'échéancier", 'warning', false);
                }
            }

            $data = $this->action_line_echeancier();
            //echo "<pre>" . print_r($data, 1);
            if ($instance->find(['id_contrat' => $this->id])) {
                $html .= $instance->displayEcheancier($data, $display);
            }

            return $html;
        }
    }

    public function is_not_finish()
    {
        if ($this->reste_periode() == 0) {
            return 0;
        }
        return 1;
    }

    public function action_line_echeancier($num_renouvellement = 0)
    {

        $returnedArray = Array(
            'factures_send' => getElementElement('contrat', 'facture', $this->id),
            'reste_a_payer' => $this->reste_a_payer(),
            'reste_periode' => $this->reste_periode($num_renouvellement),
            'periodicity'   => $this->getData('periodicity')
        );

        return (object) $returnedArray;
    }

    public function display_reste_a_payer()
    {
        return "<b>" . $this->reste_a_payer() . "€</b>";
    }

    public function getStartDateForOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        return $line->getData('date_ouverture_prevue');
    }

    public function getEndDateForOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        return $line->getData('date_fin_validite');
    }

    public function getDureeForOldToNew()
    {
        $start = new DateTime($this->getStartDateForOldToNew());
        $end = new DateTime($this->getEndDateForOldToNew());
        $interval = $start->diff($end);
        $total = ($interval->y * 12) + $interval->m;
        return $total;
    }

    public function verifDureeForOldToNew()
    {
        $can_merge = 1;
        $most_end = 0;
        $lines = $this->getChildrenList('lines');
        if (count($lines) > 1) {
            foreach ($lines as $id) {
                $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $id);
                $end = new DateTime($line->getData('date_fin_validite'));
                if ($can_merge == 1 && ($end->getTimestamp() == $most_end || $most_end == 0)) {
                    $most_end = $end->getTimestamp();
                } else {
                    $can_merge = 0;
                }
            }
        }
        return $can_merge;
    }

    public function actionOldToNew($data, &$success)
    {
        global $user;
        if (!$this->verifDureeForOldToNew())
            return "Ce contrat ne peut pas être transféré à la nouvelle version";

        if ($data['total'] == 0) {
            $date_start = new DateTime($data['date_start']);
//            $this->set('date_start', $date_start->format('Y-m-d'));
//            $this->set('periodicity', $data['periode']);
//            $this->set('duree_mois', $data['duree']);
            $this->dol_object->array_options['options_duree_mois'] = $data['duree'];
            $this->dol_object->array_options['options_date_start'] = $date_start->getTimestamp();
            $this->dol_object->array_options['options_periodicity'] = $data['periode'];
            $this->dol_object->array_options['options_entrepot'] = 8;
            $this->dol_object->update($user);
            $this->updateField('statut', 11);
            $echeancier = $this->getInstance('bimpcontract', 'BContract_echeancier');
            $echeancier->set('id_contrat', $this->id);
            $next = new DateTime($data['date_facture_date']);
            $echeancier->set('next_facture_date', $next->format('Y-m-d 00:00:00'));
            $echeancier->set('validate', 0);
            $echeancier->set('statut', 1);
            $echeancier->set('commercial', $this->getData('fk_commercial_suivi'));
            $echeancier->set('client', $this->getData('fk_soc'));
            $echeancier->set('old_to_new', 1);
            $echeancier->create();
        }
    }

    public function getNextDateFactureOldToNew()
    {
        $lines = $this->getChildrenList('lines');
        print_r($lines, 1) . " hucisduchids";
        $today = new DateTime();
        $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $lines[0]);
        $start = new DateTime($line->getData('date_ouverture_prevue'));

        if ($today->format('m') < 10) {
            $mois = '0' . ($today->format('m') + 1);
        } else {
            $mois = $today->format('m');
        }

        return $today->format('Y') . '-' . $mois . '-' . $start->format('d');
    }

    public function getTotalHtForOldToNew()
    {
        $total = 0;
        $factures = getElementElement('contrat', 'facture', $this->id);
        foreach ($factures as $nb => $infos) {

            $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $infos['d']);
            if ($facture->getData('fk_statut') == 1 || $facture->getData('fk_statut') == 2) {
                if ($facture->getData('type') == 0) {
                    $total += $facture->getData('total');
                }
            }
        }
        return $total;
    }

    public function resteMoisForOldToNew()
    {
        $today = date('Y-m-d');
        $end = new DateTime($this->getEndDateForOldToNew());
        $today = new DateTime($today);
        $interval = $today->diff($end);
        return ($interval->y * 12) + $interval->m;
    }

    public function infosForOldToNew()
    {
        $content = "";

        $content .= "Déjà facturé: " . $this->getTotalHtForOldToNew() . "€ <br />";
        $content .= "Total du contrat: " . $this->getTotalContrat() . "€";

        return $content;
    }

    public function reste_a_payer($num_renouvellement = 0)
    {
//        $duree_mois = $this->getData('duree_mois');
//        $periodicity = $this->getData('periodicity');
//        $nombre_periode = $duree_mois / $periodicity;
        $facture_delivred = getElementElement('contrat', 'facture', $this->id);
        if ($facture_delivred) {
            foreach ($facture_delivred as $link) {
                $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $link['d']);
                if ($instance->getData('type') == 0)
                    $montant += $instance->getData('total');
            }
            $return = $this->getTotalContrat() - $montant;
        } else {
            $return = $this->getTotalContrat();
        }
        return $return;
    }

    public function reste_days()
    {

        $duree_total = $this->getData('duree_mois');
        $today = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $reste = $today->diff($diff);

        return $reste->days;
    }

    public function reste_periode($num_renouvellement = 0)
    {

        if ($this->isLoaded()) {
            $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
            $instance->find(array('id_contrat' => $this->id));
            $date_1 = new DateTime($instance->getData('next_facture_date'));

            $date_2 = new DateTime($this->displayRealEndDate("Y-m-d"));
//            if ($date_1->format('Y-m-d') == $this->getData('date_start')) {
//                $return = $this->getData('duree_mois') / $this->getData('periodicity');
//            } else {
            $date_1->sub(new DateInterval('P1D'));
            $interval = $date_1->diff($date_2);

            $totalReste = $interval->m + $interval->y * 12;
            if (!self::PRORATA_PERIODE) {
                if ($interval->d >= 15) {
                    $totalReste += 1;
                }
            } else {
                $totalReste += $interval->d / 30;
            }
//                dol_syslog('contrat d '.$interval->d,3);
            if ($this->getData('periodicity') > 0)
                $return = ($totalReste / $this->getData('periodicity'));
            else
                $return = $totalReste;
            //}

            return $return;
        }
    }

    public function getTotalContrat()
    {
        $montant = 0;
        foreach ($this->dol_object->lines as $line) {
            $child = $this->getChildObject("lines", $line->id);
            //if($child->getData('renouvellement') == $this->getData('current_renouvellement')) {
            $montant += $line->total_ht;
            //}
        }

        return $montant;
    }

    public function getCurrentTotal()
    {
        return $this->getTotal($this->getData('current_renouvellement'));
    }

    public function getTotal($renouvellement)
    {
        $montant = 0;
        foreach ($this->dol_object->lines as $line) {
            $child = $this->getChildObject("lines", $line->id);
            if ($child->getData('renouvellement') == $renouvellement) {
                $montant += $line->total_ht;
            }
        }

        return $montant;
    }

    public function getAddAmountAvenantProlongation()
    {

        $now = new DateTime();

        $total = 0;

        $filters = [
            'statut'        => 2,
            'type'          => 1,
            'want_end_date' => [
                'operator' => '>=',
                'value'    => $now->format('Y-m-d')
            ]
        ];

        $children = $this->getChildrenList('avenant', $filters);

        foreach ($children as $id_child) {

            $total += $this->getCurrentTotal();
        }

        return $total;
    }

    public function getTotalBeforeRenouvellement()
    {
        $montant = 0;
        foreach ($this->dol_object->lines as $line) {
            $child = $this->getChildObject("lines", $line->id);
            if ($child->getData('renouvellement') == ($this->getData('current_renouvellement') - 1)) {
                $montant += $line->total_ht;
            }
        }

        return $montant;
    }

    public function getTotalDejaPayer($paye_distinct = false, $field = 'total')
    {
        $element_factures = getElementElement('contrat', 'facture', $this->id);
        $montant = 0;
        if (count($element_factures)) {
            foreach ($element_factures as $element) {
                $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element['d']);
                if ($paye_distinct) {
                    if ($instance->getData('paye')) {
                        if ($field == 'total')
                            $montant += $instance->getData('total');
                        elseif ($field == 'pa')
                            $montant += $instance->getData('total_achat_reval_ok');
                    }
                } else {
                    if ($instance->getData('type') == 0) {
                        if ($field == 'total')
                            $montant += $instance->getData('total');
                        elseif ($field == 'pa')
                            $montant += $instance->getData('total_achat_reval_ok');
                    }
                }
            }
        }
        return $montant;
    }

    public function renderContacts()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList();

        $html .= '</tbody>';

        $html .= '</table>';

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', array('id_client' => (int) $this->getData('fk_soc')), array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function displayNumberRenouvellement()
    {
        $html = "";
        if ($this->getData('current_renouvellement') > 0) {
            if ($this->getData('tacite') != 12 && $this->getData('tacite') != 0) {
                
            } else {
                $html .= "<strong>Pas de renouvellement</strong>";
            }
        }
        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $extra = '';
        $notes = $this->getNotes();
        $nb = count($notes);

        if ($this->getData('statut') == self::CONTRAT_STATUS_ACTIVER_TMP) {
            $date = new DateTime($this->getData('date_start_provisoire'));
            $extra .= " depuis le " . $date->format('d/m/Y');
            $end = new DateTime($this->getData('date_start_provisoire'));
            $end->add(New DateInterval("P14D"));
            $extra .= "<br />Si le contrat signé par le client ne nous parvient pas avant le <b class='bs-popover' " . BimpRender::renderPopoverData($date->format('d/m/Y') . " + 14 Jours", "top") . " >" . $end->format('d/m/Y') . "</b> l’activation provisoire de ce contrat sera suspendue.";
        }

        if ($nb > 0)
            $extra .= '<br/><span class="warning"><span class="badge badge-warning">' . $nb . '</span> Note' . ($nb > 1 ? 's' : '') . '</span>';

        if (!is_null($this->getData('date_contrat'))) {
            $date = new DateTime($this->getData('date_contrat'));
            $extra .= '<br/><span class="important">' . BimpRender::renderIcon('fas_signature', 'iconLeft') . 'Contrat marqué comme signé</span> depuis le ' . $date->format('d/m/Y');
        }
//        if (!is_null($this->getData('end_date_reel')) && !is_null($this->getData('anticipate_close_note'))) {
//            $date = new DateTime($this->getData('end_date_reel'));
//            $extra .= "<br /><span>Cloture anticipée en date du <strong>" . $date->format('d/m/Y') . "</strong></span>";
//        }

        if ($this->isFactAuto()) {
            $extra .= "<br /><span class='info' >Facturation automatique activée</strong></span>";
        }

        if ($this->getData('current_renouvellement') > 0) {
            $arrayTacite = Array(
                self::CONTRAT_RENOUVELLEMENT_1_FOIS => "1",
                self::CONTRAT_RENOUVELLEMENT_2_FOIS => "2",
                self::CONTRAT_RENOUVELLEMENT_3_FOIS => "3",
                self::CONTRAT_RENOUVELLEMENT_4_FOIS => "4",
                self::CONTRAT_RENOUVELLEMENT_5_FOIS => "5",
                self::CONTRAT_RENOUVELLEMENT_6_FOIS => "6",
            );
            $extra .= "<br /><strong>Renouvellement N°</strong><strong>" . $this->getData('current_renouvellement') . "/" . $arrayTacite[$this->getData('initial_renouvellement')] . "</strong>";
        }

        return $extra;
    }

    public function actionCreateAvenant($data, &$success)
    {

        $avLetters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O"];

        $contrat_source = ($this->getData('contrat_source') ? $this->getData('contrat_source') : $this->id);
        $count = count($this->db->getRows('contrat_extrafields', 'contrat_source = ' . $contrat_source));
        $explodeRef = explode("_", $this->getData('ref'));
        $next_ref = $explodeRef[0] . '_' . $avLetters[$count];

        if ($clone = $this->dol_object->createFromClone($this->getData('fk_soc'))) {
            $next_contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $clone);
            addElementElement('contrat', 'contrat', $this->id, $next_contrat->id);
            $next_contrat->updateField('contrat_source', $contrat_source);
            $next_contrat->updateField('date_contrat', NULL);
            $next_contrat->updateField('ref_ext', $this->getData('ref'));
            $next_contrat->updateField('ref', $next_ref);
            $success = "L'avenant N°" . $next_ref . " a été créé avec succes";
        }
    }

    public function actionAvenant($data, &$success)
    {

        $data = (object) $data;
        $errors = [];
        $warnings = [];

        $new = $this->getInstance('bimpcontract', 'BContract_avenant');
        $new->set('id_contrat', $this->id);
        $new->set('number_in_contrat', (int) $this->getData('nb_avenant') + 1);
        $this->updateField('nb_avenant', (int) $this->getData('nb_avenant') + 1);
        $new->create();

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function displayContratSource()
    {
        $obj = $this->getContratSource();
        if ($obj) {
            return $obj->getNomUrl();
        }

        return 'Ce contrat est le contrat initial';
    }

    public function getContratSource()
    {

        if (!is_null($this->getData('contrat_source'))) {
            $source = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('contrat_source'));
            return $source;
        }

        return null;
    }

    public function displayContratChild()
    {
        $obj = $this->getContratChild();
        if ($obj) {
            return $obj->getNomUrl();
        }

        return 'Ce contrat n\'a pas de contrat de remplacement';
    }

    public function getContratChild()
    {
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat');
        $instance->find(array('contrat_source' => $this->id));
        if ($instance && is_object($instance) && $instance->isLoaded()) {
            return $instance;
        }

        return null;
    }

    public function createFromClient($data)
    {
        global $user;

        $serials = explode("\n", $data->note);

        $nombreServices = count($data->services);
        $tmpCountServices = 0;
        $mostHightDuring = 0;

        foreach ($data->services as $nb => $infos) {
            if ($infos['value'] == 'Non') {
                $tmpCountServices++;
            } else {
                $mostHightDuring = ($infos['value'] > $mostHightDuring) ? $infos['value'] : $mostHightDuring;
            }
        }
        $missService = ($tmpCountServices == $nombreServices) ? true : false;

        if ($missService)
            return "Il doit y avoir au moin un service";
        else {
            $date = new DateTime();
            $date->setTimestamp($data->dateDeb);
            $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');

            // Data du contrat
            $contrat->set('fk_soc', $data->socid);
            $contrat->set('date_contrat', null);
            $contrat->set('date_start', $date->format('Y-m-d'));
            $contrat->set('objet_contrat', 'CMA');
            $contrat->set('duree_mois', $mostHightDuring);
            $contrat->set('fk_commercial_suivi', $user->id);
            $contrat->set('fk_commercial_signature', $user->id);
            $contrat->set('gti', 16);
            $contrat->set('moderegle', 60);
            $contrat->set('tacite', 12);
            $contrat->set('periodicity', 12);
            $contrat->set('note_public', '');
            $contrat->set('note_private', '');
            $contrat->set('ref_ext', '');
            $contrat->set('ref_customer', '');
            $contrat->set('label', '');
            $contrat->set('relance_renouvellement', 1);
            $contrat->set('syntec', 0);

            $errors = $contrat->create();

            if (!count($errors)) {
                $service = BimpObject::getInstance('bimpcore', 'Bimp_Product');
                foreach ($data->services as $nb => $infos) {
                    if ($infos['value'] != 'Non') {
                        $service->fetch($infos['id']);
                        $end_date = new DateTime($date->format('Y-m-d'));
                        $end_date->add(new DateInterval("P" . $mostHightDuring . "M"));
                        $idLine = $contrat->dol_object->addLine(
                                $service->getData('description'), $service->getData('price'), 1, 20, 0, 0, $infos['id'], 0, $date->format('Y-m-d'), $end_date->format('Y-m-d'), 'HT', 0.0, 0, null, 0, 0, null, $nb);
                    }
                    $line = BimpObject::getInstance('bimpcontract', 'BContract_contratLine', $idLine);
                    $line->updateField('serials', json_encode($serials));
                }
                $contrat->addLog('Contrat créé avec BimpContratAuto');
                return $contrat->id;
            }
        }
    }

    public function createFromPropal($propal, $data)
    {
        global $user;
        $errors = [];
        //echo '<pre>';
        $propalIsRenouvellement = (!$propal->isNotRenouvellementContrat()) ? true : false;
        $elementElement = getElementElement("contrat", "propal", null, $propal->id);
        if ($propalIsRenouvellement) {

            $serials = [];
            $source = $this->getInstance('bimpcontract', 'BContract_contrat', $elementElement[0]['s']);

            $objet_contrat = $source->getData('objet_contrat');
            $fk_soc = $source->getData('fk_soc');
            $commercial_signature = $source->getData('fk_commercial_signature');
            $commercial_suivi = $source->getData('fk_commercial_suivi');
            $periodicity = $source->getData('periodicity');
            $gti = $source->getData('gti');
            $duree_mois = $source->getData('duree_mois');
            $tacite = 12;
            $mode_reglement = $source->getData('moderegl');
            $cond_reglement = $source->getData('condregl');
            $note_public = $source->getData('note_public') . "\n" . $data['note_public'];
            $note_private = $source->getData('note_private') . "\n" . $data['note_private'];
            $ref_ext = $source->getData('ref_ext');
            $secteur = $source->getData('secteur');
            $ref_customer = $source->getData('ref_customer');

            $lines = $propal->getChildrenList("lines");
            $lines_of_contrat = $source->getChildrenList("lines");

            foreach ($lines as $id_child) {
                $child = $propal->getChildObject('lines', $id_child);
            }

            //echo print_r($lines,1);
        } else {
            $fk_soc = $data['fk_soc'];
            $objet_contrat = $data['objet_contrat'];
            $commercial_signature = $data['commercial_signature'];
            $commercial_suivi = $data['commercial_suivi'];
            $periodicity = $data['periodicity'];
            $gti = $data['gti'];
            $duree_mois = $data['duree_mois'];
            $tacite = $data['re_new'];
            $mode_reglement = $data['fk_mode_reglement'];
            $cond_reglement = $data['fk_cond_reglement'];
            $note_public = $data['note_public'];
            $note_private = $data['note_private'];
            $ref_ext = $data['ref_ext'];
            $ref_customer = $data['ref_customer'];
            $secteur = $data['secteur_contrat'];
        }

        $commercial_for_entrepot = $this->getInstance('bimpcore', 'Bimp_User', $data['commercial_suivi']);

        $new_contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        if ((int) BimpCore::getConf('USE_ENTREPOT'))
            $new_contrat->set('entrepot', ($commercial_for_entrepot->getData('defaultentrepot')) ? $commercial_for_entrepot->getData('defaultentrepot') : $propal->getData('entrepot'));
        $new_contrat->set('fk_soc', $fk_soc);
        $new_contrat->set('date_contrat', null);
        $new_contrat->set('date_start', $data['valid_start']);
        $new_contrat->set('objet_contrat', $objet_contrat);
        $new_contrat->set('fk_commercial_signature', $commercial_signature);
        $new_contrat->set('fk_commercial_suivi', $commercial_suivi);
        $new_contrat->set('periodicity', $periodicity);
        $new_contrat->set('gti', $gti);
        $new_contrat->set('duree_mois', $duree_mois);
        $new_contrat->set('tacite', $tacite);
        $new_contrat->set('moderegl', $mode_reglement);
        $new_contrat->set('condregl', $cond_reglement);
        $new_contrat->set('note_public', $note_public);
        $new_contrat->set('note_private', $note_private);
        $new_contrat->set('ref_ext', $ref_ext);
        $new_contrat->set('ref_customer', $ref_customer);
        $new_contrat->set('label', $data['label']);
        $new_contrat->set('relance_renouvellement', 1);
        $new_contrat->set('secteur', $secteur);
        if ($propalIsRenouvellement)
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        if (isset($data['use_syntec']) && $data['use_syntec'] == 1) {
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        }
        if (!count($errors)) {
            $errors = $new_contrat->create();
        }
        if (!count($errors)) {
            foreach ($propal->dol_object->lines as $line) {
                $produit = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                if ($produit->getData('fk_product_type') == 1 || !BimpCore::getConf('just_code_service', null, 'bimpcontract') || $line->pa_ht == 0) {
                    $description = ($line->desc) ? $line->desc : $line->libelle;
                    $end_date = new DateTime($data['valid_start']);
                    $end_date->add(new DateInterval("P" . $duree_mois . "M"));
                    $new_contrat->dol_object->pa_ht = $line->pa_ht; // BUG DéBILE DOLIBARR
                    $new_contrat->dol_object->addLine($description, $line->subprice, $line->qty, $line->tva_tx, 0, 0, $line->fk_product, $line->remise_percent, $data['valid_start'], $end_date->format('Y-m-d'), 'HT', 0.0, 0, null, (float) $line->pa_ht, 0, null, $line->rang);
                }
            }

            $contacts_suivi = $new_contrat->dol_object->liste_contact(-1, 'external', 0, 'BILLING2');
            if (count($contacts_suivi) == 0) {
                // Get id of the default contact
                global $db;
                $id_client = $data['fk_soc'];
                if ($id_client > 0) {

                    $soc = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $id_client);
                    $contact_default = $soc->getData('contact_default');

                    if (!count($errors) && $contact_default > 0) {
                        if ($new_contrat->dol_object->add_contact($contact_default, 'BILLING2', 'external') <= 0)
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_contrat->dol_object), 'Echec de l\'ajout du contact');
                    }
                }
            }
            $new_contrat->copyContactsFromOrigin($propal);
            addElementElement('propal', 'contrat', $propal->id, $new_contrat->id);
            $elementListPropal = getElementElement('propal', 'facture', $propal->id);
            $fact = $this->getInstance('bimpcommercial', 'Bimp_Facture');
            foreach ($elementListPropal as $element => $type) {
                $fact->fetch($type['d']);
                if ($fact->getData('type') == 3) {
                    addElementElement('contrat', 'facture', $new_contrat->id, $type['d']);
                }
            }
            return $new_contrat->id;
        } else {
            return -1;
        }
    }

    public function getIdAvenantActif()
    {
        $avs = $this->getChildrenList('avenant', ['statut' => 2]);
        if (count($avs) > 0)
            return $avs[0];
        return 0;
    }

    public function showInFieldsTable($field)
    {
        if ($this->getData($field))
            return 1;
        return 0;
    }

    public function getJourRestant()
    {
        $now = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $interval = $now->diff($diff);
        //print_r($interval);
        return $interval->days;
    }

    public function facturationIsDemainCron($heure_cron = 23)
    {
        $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
        if ($echeancier->find(['id_contrat' => $this->id], true)) {
            $today = new DateTime(date('Y-m-d ' . $heure_cron . ':00:00'));
            $nextFacturation = new DateTime($echeancier->getData('next_facture_date'));

            $diff = $today->diff($nextFacturation);

            if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && $diff->h == 1) {
                return 1;
            }
        }

        return 0;
    }

    public function getJourRestantReel()
    {
        $now = new DateTime();
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $interval = $now->diff($diff);
        //print_r($interval);

        $signe = ($interval->invert == 1) ? "-" : "";

        return $signe . $interval->days;
    }

    public function getJourTotal()
    {
        $debut = new DateTime($this->getData('date_start'));
        $diff = new DateTime($this->displayRealEndDate("Y-m-d"));
        $interval = $debut->diff($diff);
        //print_r($interval);
        return $interval->days;
    }

    public function renderHeaderExtraLeft()
    {

        $html = '';

        if ($this->isLoaded()) {
            if ($this->getData('ref_ext')) {
                $html .= '<div style="margin-bottom: 8px">';
                $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('ref_ext') . '"</span>';
                $html .= '</div>';
            }

            if ($this->dol_object->element == 'contrat' && BimpTools::getContext() != 'public') {
                $userCreationContrat = new User($this->db->db);
                $userCreationContrat->fetch((int) $this->getData('fk_user_author'));
                $html .= '<div class="object_header_infos">';
                if ($this->getData('end_date_reel') && $this->getData('anticipate_close_note')) {
                    $dateAnticipateClose = new DateTime($this->getData('end_date_reel'));

                    $html .= "<strong class='danger' ><h3>Date de clôture anticipée pour ce contrat: <i>" . $dateAnticipateClose->format('d/m/Y') . "</i>"
                            . " <span class='rowButton bs-popover' " . BimpRender::renderPopoverData($this->getData('anticipate_close_note'), 'right', true) . "> " . BimpRender::renderIcon('fas fa-info') . "</span></h3></strong>";
                }

                $create = new DateTime($this->getData('datec'));
                if ($this->getdata('statut') == self::CONTRAT_STATUS_ACTIVER) {
                    $idAvenantActif = $this->getIdAvenantActif();
                    if ($idAvenantActif > 0) {
                        $child = $this->getChildObject('avenant', $idAvenantActif);
                        $message = "Ce contrat fait l'objet d'un avenant actif, Merci de faire les vérifications nécessaires avant toute intervention. Merci";
                        $html .= '<h4><b class="warning" ><i class="fas fa-warning" ></i> ' . $message . '</b></h4>';
                    }
                }
                $html .= 'Créé le <strong >' . $create->format('d / m / Y') . '</strong>';
                $html .= ' par <strong >' . $userCreationContrat->getNomUrl(1) . '</strong>';

                if ($this->getData('fk_user_cloture')) {

                    $dateCloture = new DateTime($this->getData('date_cloture'));
                    $userCloture = new User($this->db->db);
                    $userCloture->fetch($this->getData('fk_user_cloture'));

                    $html .= '<br />Clos le <strong >' . $dateCloture->format('d / m / Y') . '</strong>';
                    $html .= ' par <strong >' . $userCloture->getNomUrl(1) . '</strong>';
                }

                $client = $this->getChildObject('client');
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Client: </strong>';
                $html .= BimpObject::getInstanceNomUrlWithIcons($client);
                $html .= '</div>';
                $html .= '</div>';
            }
            if ($this->getData('statut') == self::CONTRAT_STATUS_VALIDE || $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) {


                $intervale_days = $this->getJourRestantReel();
                //$intervale_days = 14;
                $renderAlert = true;
                $hold = false;

                $html .= '<div class="object_header_infos">';
                if ($intervale_days > 0 || $intervale_days == 0) {
                    $html .= '<strong>Ce contrat expire dans ' . $intervale_days . ' jours</strong>';
                } else {
                    $html .= BimpRender::renderAlerts("Ce contrat est expiré depuis " . abs($intervale_days) . " jour.s, merci de le clore", 'danger', false);
                }
                $html .= '</div>';

                $dt_today = New DateTime();
                $dt_start = New DateTime($this->getData('date_start'));

                $tms_today = strtotime($dt_today->format('Y-m-d'));
                $tms_start = strtotime($dt_start->format('Y-m-d'));

                if ($tms_start > $tms_today && $this->getData('statut') == self::CONTRAT_STATUS_ACTIVER) {
                    $html .= '<div class="object_header_infos">';
                    $html .= BimpRender::renderAlerts("<h3><b>Ce contrat a été activé par avance mais sa date de prise d'effet n'est pas encore atteinte</b><br />Date visible ci-dessous dans \"Information sur la durée de validité du contrat\"</h3>", 'danger', false);

                    $html .= '</div>';
                }



//                if ($intervale_days < 365 && $interval->invert == 0) {
//                    $html .= '<div class="object_header_infos">';
//                    if ($intervale_days <= 365 && $intervale_days > 90) {
//                        $renderAlert = false;
//                        $alerte_type = 'info';
//                    } elseif ($intervale_days <= 90 && $intervale_days > 30) {
//                        $alerte_type = 'info';
//                    } elseif ($intervale_days <= 30 && $intervale_days > 15) {
//                        $alerte_type = 'warning';
//                    } else {
//                        $alerte_type = 'danger';
//                    }
//
                if (!$this->getData('duree_mois') || !$this->getData('date_start')) {

                    $val = $this->db->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $this->id);

                    $date_fin = new DateTime($val);

                    $html .= BimpRender::renderAlerts('<h5>Ceci est un ancien contrat dont la date d\'expiration est le : <b> ' . $date_fin->format('d / m / Y') . ' </b></h5> ', 'info', false);
                    $renderAlert = false;
                    $hold = true;
                }
//
//                    if ($renderAlert)
//                        $html .= BimpRender::renderAlerts('Ce contrat expire dans <strong>' . $intervale_days . ' jours</strong>', $alerte_type, false);
//                    elseif (!$hold)
//                        $html .= 'Ce contrat expire dans <strong>' . $intervale_days . ' jours</strong>';
//                    $html .= '</div>';
//                } else {
//                    if ($this->getData('statut') == 11 && $interval->invert == 1) {
//                        $html .= '<div class="object_header_infos">';
//                        $html .= BimpRender::renderAlerts("Ce contrat est expiré, merci de le clore", 'danger', false);
//                        $html .= '</div>';
//                    }
//                }
            }
        }
        return $html;
    }

    public function displayMessagesFormActivate()
    {
        $msgs = [];

        $date = new DateTime($this->getData('date_start'));
        $now = new DateTime();

        $diff = $date->diff($now);

        if ($diff->invert) {
            $msgs[] = Array(
                'type'    => 'danger',
                'content' => "Le contrat dûment signé doit être obligatoirement présent dans les fichiers pour une activation maintenant"
            );
        }


        return $msgs;
    }

    public function displayCommercial()
    {

        BimpTools::loadDolClass('user');
        $commercial = new User($this->db->db);
        $commercial->fetch($this->getData('fk_commercial_suivi'));

        return $commercial->getNomUrl(1);
    }

    public function isSigned($display = null)
    {

        if (!is_null($this->getData('date_contrat'))) {
            return (is_null($display) ? 1 : "<b class='success'>OUI</b>");
        } else {
            return (is_null($display) ? 0 : "<b class='danger'>NON</b>");
        }
    }

    public function relance_renouvellement_commercial()
    {
        
    }

//    public function getEmailUsersFromArray()
//    {
//        global $user, $langs, $conf;
//
//        $emails = array();
//
//        // User connecté: 
//
//        if (!empty($user->email)) {
//            $emails[$user->email] = $user->getFullName($langs) . ' (' . $user->email . ')';
//        }
//
//        if (!$user->admin)
//            return $emails;
//
//        if (!empty($user->email_aliases)) {
//            foreach (explode(',', $user->email_aliases) as $alias) {
//                $alias = trim($alias);
//                if ($alias) {
//                    $alias = str_replace('/</', '', $alias);
//                    $alias = str_replace('/>/', '', $alias);
//                    if (!isset($emails[$alias])) {
//                        $emails[$alias] = $user->getFullName($langs) . ' (' . $alias . ')';
//                    }
//                }
//            }
//        }
//
//        // Société: 
//
//        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
//            $emails[$conf->global->MAIN_INFO_SOCIETE_MAIL] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $conf->global->MAIN_INFO_SOCIETE_MAIL . ')';
//        }
//
//        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
//            foreach (explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES) as $alias) {
//                $alias = trim($alias);
//                if ($alias) {
//                    $alias = str_replace('/</', '', $alias);
//                    $alias = str_replace('/>/', '', $alias);
//                    if (!isset($emails[$alias])) {
//                        $emails[$alias] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $alias . ')';
//                    }
//                }
//            }
//        }
//
//        // Contacts pièce: 
//
//        if ($this->isLoaded()) {
//            $c_user = new User($this->db->db);
//            $contacts = $this->dol_object->liste_contact(-1, 'internal');
//            foreach ($contacts as $item) {
//                $c_user->fetch($item['id']);
//                if (BimpObject::objectLoaded($c_user)) {
//                    if (!empty($c_user->email) && !isset($emails[$c_user->email])) {
//                        $emails[$c_user->email] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $c_user->email . ')';
//                    }
//
//                    if (!empty($c_user->email_aliases)) {
//                        foreach (explode(',', $c_user->email_aliases) as $alias) {
//                            $alias = trim($alias);
//                            if ($alias) {
//                                $alias = str_replace('/</', '', $alias);
//                                $alias = str_replace('/>/', '', $alias);
//                                if (!isset($emails[$alias])) {
//                                    $emails[$alias] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $alias . ')';
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        return $emails;
//    }
//    
//    public function renderMailToInputs($input_name)
//    {
//        $emails = $this->getMailsToArray();
//
//        $html = '';
//
//        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
//                    'options'     => $emails,
//                    'extra_class' => 'emails_select principal'
//        ));
//
//
//        $html .= '<p class="inputHelp selectMailHelp">';
//        $html .= 'Sélectionnez une adresse e-mail puis cliquez sur "Ajouter"';
//        $html .= '</p>';
//
//        $html .= '<div class="mail_custom_value" style="display: none; margin-top: 10px">';
//        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
//        $html .= '<p class="inputHelp">Entrez une adresse e-mail valide puis cliquez sur "Ajouter"</p>';
//        $html .= '</div>';
//
//        return $html;
//    }
//    
//    public function getMailsToArray()
//    {
//        global $user, $langs;
//
//        $client = $this->getChildObject('client');
//
//        $emails = array(
//            ""           => "",
//            $user->email => $user->getFullName($langs) . " (" . $user->email . ")"
//        );
//
//        if ($this->isLoaded()) {
//            $contacts = $this->dol_object->liste_contact(-1, 'external');
//            foreach ($contacts as $item) {
//                if (!isset($emails[(int) $item['id']])) {
//                    $emails[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
//                }
//            }
//        }
//
//        if (BimpObject::objectLoaded($client)) {
//            $client_emails = self::getSocieteEmails($client->dol_object);
//            if (is_array($client_emails)) {
//                foreach ($client_emails as $value => $label) {
//                    if (!isset($emails[$value])) {
//                        $emails[$value] = $label;
//                    }
//                }
//            }
//        }
//
//        if ($this->isLoaded()) {
//            $contacts = $this->dol_object->liste_contact(-1, 'internal');
//            foreach ($contacts as $item) {
//                if (!isset($emails[$item['email']])) {
//                    $emails[$item['email']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
//                }
//            }
//        }
//
//        $emails['custom'] = 'Autre';
//
//        return $emails;
//    }
//    
//    public function getDefaultMailTo()
//    {
//        return array();
//    }
//    
//   public function getEmailModelsArray()
//    {
//        if (!static::$email_type) {
//            return array();
//        }
//
//        return self::getEmailTemplatesArray(static::$email_type, true);
//    }
//    
    public function isBySocId()
    {
        if (isset($_REQUEST['socid']) && $_REQUEST['socid'] > 0) {
            return 1;
        }
        return 0;
    }

    public function getJoinFilesValues()
    {
        $values = BimpTools::getValue('fields/join_files', array());

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

        $list = $this->getAllFiles();
//        $idSepa = 0;
//        $idSepaSigne = 0;
        foreach ($list as $id => $elem) {
//            if (stripos($elem, "sepa")) {
//                $idSepa = $id;
//                if (stripos($elem, "signe"))
//                    $idSepaSigne = $id;
//            }
            if (stripos($elem, $this->getRef() . "_Ex_Olys") !== FALSE) {
                $values[] = $id;
            }
            if (stripos($elem, $this->getRef() . "_Ex_Client") !== FALSE) {
                $values[] = $id;
            }
        }



//        if ($idSepa > 0 && $idSepaSigne < 1)
//            $values[] = $idSepa;




        return $values;
    }

    public function mail($destinataire, $type, $cc = "")
    {
        switch ($type) {
            case self::MAIL_DEMANDE_VALIDATION:
                $sujet = "Contrat en attente de validation";
                $action = "Valider la conformité du contrat";
                break;
            case self::MAIL_VALIDATION:
                $sujet = "Contrat validé et signé par la direction";
                $action = "Envoyer le contrat au client par le bouton <b>'Action'</b> puis <b>'Envoyer par e-mail'</b>";
                break;
            case self::MAIL_SIGNED:
                $sujet = "Contrat signé par le client";
                $action = "Activer le contrat";
                break;
            case self::MAIL_ACTIVATION:
                $sujet = "Contrat activé";
                $action = "Facturer le contrat";
                break;
        }

        $commercial = $this->getInstance('bimpcore', 'Bimp_User', $this->getCommercialClient());
        $commercialContrat = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('fk_commercial_suivi'));
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $extra = "<h3 style='color:#EF7D00'><b>BIMP</b><b style='color:black'>contrat</b></h3>";
        $extra .= "Action à faire sur le contrat: <b>" . $action . "</b><br /><br />";
        $extra .= "<u><i>Informations <i></i> </i></u><br />";
        $extra .= "Contrat: <b>" . $this->getNomUrl() . "</b><br />";
        $extra .= "Client: <b>" . $client->dol_object->getNomUrl() . " (" . $client->getNomUrl() . ")</b><br /><br />";
        $extra .= "Commercial du contrat: <b>" . $commercialContrat->dol_object->getNomUrl() . "</b><br />";
        $extra .= "Commercial du client: <b>" . $commercial->dol_object->getNomUrl() . "</b><br />";

        //print_r(['dest' => $destinataire, 'sujet' => $sujet, 'type' => $type, 'msg' => $extra]);
        if ($cc == "")
            mailSyn2($sujet, $destinataire, BimpCore::getConf('devs_email'), $extra);
        else
            mailSyn2($sujet, $destinataire, BimpCore::getConf('devs_email'), $extra, array(), array(), array(), $cc);
    }

    // Public: 

    public function getNewTicketSupportOnClick()
    {
        if ($this->isLoaded()) {
            global $userClient;
            $contact = null;

            if (BimpObject::objectLoaded($userClient) && (int) $userClient->getData('id_contact')) {
                $contact = $userClient->getChildObject('contact');
            }

            $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
            return $ticket->getJsLoadModalForm('public_create_from_contrat', 'Nouveau ticket support (contrat ' . $this->getRef() . ')', array(
                        'fields' => array(
                            'id_contrat'       => (int) $this->id,
                            'id_client'        => (int) $this->getData('fk_soc'),
                            'id_user_client'   => (BimpObject::objectLoaded($userClient) ? (int) $userClient->id : 0),
                            'contact_in_soc'   => (BimpObject::objectLoaded($contact) ? $contact->getName() : ''),
                            'adresse_envois'   => (BimpObject::objectLoaded($contact) ? BimpTools::replaceBr($contact->displayFullAddress()) : ''),
                            'email_bon_retour' => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : '')
                        )
            ));
        }

        return '';
    }

    public function getPublicUrlParams()
    {
        return 'tab=contrats&content=card&id_contrat=' . $this->id;
    }

    public function getPublicListPageUrlParams()
    {
        return 'tab=contrats';
    }

    public function getPublicListExtraButtons()
    {
        $buttons = array();

        if ($this->can('view') && $this->canClientViewDetail()) {
            $url = $this->getPublicUrl();

            if ($url) {
                $buttons[] = array(
                    'label'   => 'Voir le détail',
                    'icon'    => 'fas_eye',
                    'onclick' => 'window.location = \'' . $url . '\''
                );
            }
        }

        return $buttons;
    }

    public function getPublicActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->isValide()) {
            $buttons[] = array(
                'label'   => 'Nouveau ticket support',
                'icon'    => 'fas_headset',
                'onclick' => $this->getNewTicketSupportOnClick()
            );
        }

        return $buttons;
    }
    
    public function renderDemandesList()
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
            $objectName = ValidComm::getObjectClass($this);
            if ($objectName != -2) {
                BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
                $list = new BC_ListTable($demande);
                $list->addFieldFilterValue('type_de_piece', $objectName);
                $list->addFieldFilterValue('id_piece', (int) $this->id);
 
                return $list->renderHtml();
            } else {
                return '';
            }
        }
 
        return BimpRender::renderAlerts('Impossible d\'afficher la liste des demande de validation (ID ' . $this->getLabel('of_the') . ' absent)');
    }
    
    public function getPdfFileName(&$errors = array(), $signed = false)
    {
        $file_name = '';

        

        return $file_name;
    }
    
    
    // TODO
    public function getSignatureDocFileDir($doc_type)
    {
        return $this->getFilesDir();
    }
    // TODO
    public function getSignatureDocFileName($doc_type, $signed = false)
    {
        switch ($doc_type) {
            case 'contrat':
                $errors = array();
                return $this->getPdfNamePrincipal($signed);
        }

        return '';
    }
    // TODO
    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false)
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $context = BimpCore::getContext();

        if ($forced_context) {
            $context = $forced_context;
        }

        $file_name = $this->getSignatureDocFileName($doc_type, $signed);

        if ($file_name) {
            switch ($doc_type) {
                case 'contrat':
                    if ($context === 'public') {
                        return BimpObject::getPublicBaseUrl() . '?modulepart=contract&file=' . $this->getRef() . $this->getPdfNamePrincipal($signed);
                    } else {
                        return $this->getFileUrl($file_name);
                    }
                    break;
            }
        }

        return '';
    }
    // TODO
    public function getSignatureDocRef($doc_type)
    {
        switch ($doc_type) {
            case 'contrat':
                break;
        }

        return $this->getRef();
    }
    // TODO
    public function getSignatureParams($doc_type)
    {
        return BimpTools::overrideArray(self::$default_signature_params, (array) $this->getData('signature_params'));
    }
    // TODO
    public function onSigned($bimpSignature, $data)
    {
        $success = '';
        return $this->actionSigned($data, $success);
    }
    // TODO
    public function onSignatureCancelled($bimpSignature)
    {
        return;
    }
    // TODO
    public function isSignatureReopenable($doc_type, &$errors = array())
    {
        return;
    }
    // TODO
    public function onSignatureReopened($bimpSignature)
    {
        return;
    }

}
