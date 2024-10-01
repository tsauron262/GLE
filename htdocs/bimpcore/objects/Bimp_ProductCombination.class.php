<?php

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
}
