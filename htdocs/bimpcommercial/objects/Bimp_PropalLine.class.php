<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_PropalLine extends ObjectLine
{

    public static $parent_comm_type = 'propal';
    public static $dol_line_table = 'propaldet';
    public static $dol_line_parent_field = 'fk_propal';
    public static $periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        2  => 'Bimensuelle',
        3  => 'Trimestrielle',
        4  => 'Quadrimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel'
    );

    // Getters booléens

    public function isDeletable($force_delete = false, &$errors = array()): int
    {
        if ($this->getData('linked_object_name') == 'discount')
            return 1;
        return parent::isDeletable($force_delete, $errors);
    }

    public function isAbonnement()
    {
        $prod = $this->getProduct();
        if (BimpObject::objectLoaded($prod)) {
            return $prod->isAbonnement();
        }
        else{
            $parentLine = $this->getParentLine();
            if($parentLine){
                return $parentLine->isAbonnement();
            }
        }
    }
    
    public function getParentLine(){
        if($this->getData('id_parent_line')){
            return BimpObject::getInstance($this->module, $this->object_name, $this->getData('id_parent_line'));
        }
        return null;
    }
    
    
    
    public function isFieldEditable($field, $force_edit = false){
//        die('ffffff');
//        $parent = $this->getParentInstance();
//        if(!$parent->getNbAbonnements()){
            $tabOk = array('abo_fac_periodicity', 'abo_duration', 'abo_fac_term', 'abo_nb_renouv');
            if(in_array($field, $tabOk))
                    return true;
//        }
        return parent::isFieldEditable($field, $force_edit);
    }

    public function showMarginsInForms()
    {
        return 1;
    }

    // Getters arrays: 

    public function getNbRenouvellementsArray($max = 10)
    {
        $n = array(
            0  => 'Aucun',
            -1 => 'Illimité',
        );

        for ($i = 1; $i <= $max; $i++) {
            $n[$i] = $i;
        }
        return $n;
    }

    // Getters données: 

    public function getValueByProduct($field)
    {
        if (in_array($field, array('is_abonnement', 'abo_fac_periodicity', 'abo_fac_term'))) {
            $prod = $this->getProduct();

            if (BimpObject::objectLoaded($prod)) {
                switch ($field) {
                    case 'is_abonnement':
                        return $prod->isAbonnement();
                    case 'abo_fac_periodicity':
                        return $prod->getData('fac_def_periodicity');
                    case 'abo_fac_term':
                        return $prod->getData('fac_def_terme');
                }
            }
        }

        return parent::getValueByProduct($field);
    }

    public function getInputValue($field_name)
    {
        if (in_array($field_name, array('abo_fac_periodicity', 'abo_fac_term'))) {
            if (!$this->isLoaded() || $this->id_product != (int) BimpTools::getPostFieldValue('id_product', $this->id_product)) {
                return $this->getValueByProduct($field_name);
            }
        }

        return $this->getData($field_name);
    }

    public function getAboQties()
    {
        $prod = $this->getProduct();
        if(!BimpObject::objectLoaded($prod)){
            $parentLine = $this->getParentLine();
            if($parentLine){
                $prod = $parentLine->getProduct();
            }
        }
        $qties = array(
            'total'           => $this->getFullQty(),
            'fac_periodicity' => (int) $this->getData('abo_fac_periodicity'),
            'duration'        => (int) $this->getData('abo_duration'),
            'prod_duration'   => (BimpObject::objectLoaded($prod) ? $prod->getData('duree') : 0),
            'per_month'       => 0,
            'per_fac_period'  => 0,
            'per_prod_period' => 1
        );

        if ($qties['total'] && $qties['duration']) {
            $qties['per_month'] = $qties['total'] / $qties['duration'];
            $qties['per_fac_period'] = $qties['per_month'] * $qties['fac_periodicity'];
            $qties['per_prod_period'] = $qties['per_month'] * $qties['prod_duration'];
        }

        return $qties;
    }

    // Affichages : 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        if ($field == 'qty' && $this->isAbonnement()) {
            return $this->displayAboQty();
        }

        return parent::displayLineData($field, $edit, $display_name, $no_html);
    }

    public function displayAboQty()
    {
        $html = '';

        if ($this->isAbonnement()) {
            $qties = $this->getAboQties();

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $html .= '<b>' . BimpTools::displayFloatValue((float) $qties['per_prod_period'], 8, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de <b>' . $qties['prod_duration'] . ' mois</b><br/>';
            } else {
                if (!$qties['fac_periodicity']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Périodicité de facturation non définie</span>';
                }
                if (!$qties['duration']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Durée totale de l\'abonnement non définie</span>';
                }
            }

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $html .= '<br/><b>- Durée abonnement : </b>' . $qties['duration'] . ' mois';
                $html .= '<br/><b>- Qté par facturation ' . lcfirst($this->displayDataDefault('abo_fac_periodicity')) . ': </b>';
                $html .= BimpTools::displayFloatValue((float) $qties['per_fac_period'], 8, ',', 0, 0, 0, 0, 1, 1);
            }
            $html .= '<br/><b>- Qté totale : </b>' . parent::displayLineData('qty');
            $html .= '<br/>- Facturation à terme ' . ((int) $this->getData('abo_fac_term') ? 'à échoir' : 'échu');
            $html .= '<br/>- Renouvellement(s) tacite(s) : ' . $this->displayDataDefault('abo_nb_renouv');
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderAboInfos()
    {
        $html = '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
        $html .= 'Ce produit doit être inclus dans un contrat d\'abonnement. Veuillez renseigner les champs "Périodicité de facturation", ';
        $html .= '"Durée de l\'abonnement", "Terme de facturation" et "Nombre de renouvellements tacites"';
        $html .= '</span>';

        return $html;
    }

    public function renderAboDurationInput()
    {
        $html = '';

        $duree_unitaire = 0;
        $id_product = (int) BimpTools::getPostFieldValue('id_product', $this->id_product);

        if ($id_product) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (BimpObject::objectLoaded($prod)) {
                $duree_unitaire = (int) $prod->getData('duree');
            }
        }

        $value = (int) $this->getData('abo_duration');
        $options = array();

        if ($duree_unitaire) {
            $options['data']['min'] = $duree_unitaire;
            $options['min_label'] = 1;
            $options['step'] = $duree_unitaire;

            if ($value < $duree_unitaire) {
                $value = $duree_unitaire;
            }
        }

        $html .= BimpInput::renderInput('qty', 'abo_duration', $value, $options);

        if ($duree_unitaire) {
            $html .= '<div style="margin-top: 10px">';
            $html .= '<b>La durée totale doit être un multiple de la durée unitaire du produit (' . $prod->getData('duree') . ' mois)</b><br/>';
            $html .= '</div>';
        }

        $html .= '<input type="hidden" name="prod_duration" value="' . $duree_unitaire . '"/>';
        return $html;
    }

    public function renderAboQtyInput()
    {
        $html = '';

        if ($this->isFieldEditable('qty') && $this->canEditField('qty')) {
            $qties = $this->getAboQties();
            $options = array(
                'data' => array(
                    'data_type' => 'number',
                    'decimals'  => 8,
                    'unsigned'  => 1
                )
            );

//            $html .= '<span class="small">Veuillez renseigner l\'une des quantités suivantes</span>';

            $html .= '<span class="bold">Nombre d\'unités :</span><br/>';
            $html .= BimpInput::renderInput('qty', 'abo_qty_per_product_period', $qties['per_prod_period'], $options);

            $content = 'Qté totale :<br/>';
            $content .= BimpInput::renderInput('qty', 'abo_total_qty', $qties['total'], $options);
            if ($qties['prod_duration']) {
                $content .= '<br/><span class="small">1 quantité correpond à 1 unité sur ' . $qties['prod_duration'] . ' mois</span>';
            }

            $content .= '<br/><br/>Qté par facturation :<br/>';
            $content .= BimpInput::renderInput('qty', 'abo_qty_per_fac_period', $qties['per_fac_period'], $options);

            $html .= '<br/><div style="margin-top: 15px; display: inline-block">';
            $html .= BimpRender::renderFoldableContainer(BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Avancé', $content, array(
                        'offset_left' => 1,
                        'grey_bk'     => 1,
                        'open'        => 0
            ));
            $html .= '</div>';
        } else {
            $html .= $this->displayAboQty();
        }

        return $html;
    }

    // Overrides: 

    public function delete(&$warnings = array(), $force_delete = false)
    {
        if ($this->getData('linked_object_name') == 'discount') {
            $parent = $this->getParentInstance();
            $parent->dol_object->statut = 0;
            $return = parent::delete($warnings, $force_delete);
            $parent->dol_object->statut = $parent->getInitData('statut');
        } else
            $return = parent::delete($warnings, $force_delete);
        
        
        return $return;
    }
}
