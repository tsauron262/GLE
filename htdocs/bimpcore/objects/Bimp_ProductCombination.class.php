<?php

require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductCombination2ValuePair.class.php';

class Bimp_ProductCombination extends BimpObject
{

    public $no_dol_right_check = true;

    // Getters params: 

    public function getProductListHeaderButtons()
    {
        $buttons = array();

        $id_prod = (int) $this->getData('fk_product_parent');

        if ($id_prod) {
            $url = DOL_URL_ROOT . '/variants/combinations.php?id=' . $id_prod;
            $buttons[] = array(
                'label'   => 'Gérer les déclinaisons' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight'),
                'icon'    => 'fas_cog',
                'onclick' => 'window.open(\'' . $url . '\')'
            );

//            if ($this->can('create')) {
//                $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
//
//                if (BimpObject::objectLoaded($prod)) {
//                    if ($this->isActionAllowed('createCombination') && $this->canSetAction('createCombination')) {
//                        $buttons[] = array(
//                            'label'   => 'Ajouter une combinaison',
//                            'icon'    => 'fas_plus-circle',
//                            'onclick' => $prod->getJsActionOnclick('createCombination', array(), array(
//                                'form_name' => 'combination'
//                            ))
//                        );
//                    }
//                }
//            }
        }

        return $buttons;
    }

    // Getters données : 
    public function getCombinations()
    {
        $comb2val = new ProductCombination2ValuePair($this->db->db);
        return  $comb2val->fetchByFkCombination($this->id);
    }

    // Affichages : 

    public function displayCombination()
    {
        $html = '';

        if ($this->isLoaded()) {
            $combinations = $this->getCombinations();
            $iMax = count($combinations);

            for ($i = 0; $i < $iMax; $i++) {
                $html .= dol_htmlentities($combinations[$i]);
                if ($i !== ($iMax - 1)) {
                    $html .= '<br/>';
                }
            }
        }

        return $html;
    }
}
