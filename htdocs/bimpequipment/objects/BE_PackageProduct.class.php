<?php

class BE_PackageProduct extends BimpObject
{

    public function getListExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $package = $this->getParentInstance();

            if (BimpObject::objectLoaded($package)) {
                if ($package->isActionAllowed('saveProductQty') && $package->canSetAction('saveProductQty')) {
                    $buttons[] = array(
                        'label'   => 'Modifier les quantités',
                        'icon'    => 'fas_edit',
                        'onclick' => $package->getJsActionOnclick('saveProductQty', array(
                            'id_package_product' => (int) $this->id,
                            'qty'                => (int) $this->getData('qty')
                                ), array(
                            'form_name'   => 'edit_product',
                            'no_triggers' => true
                        ))
                    );
                }

                if ($package->isActionAllowed('removeProduct') && $package->canSetAction('removeProduct')) {
                    $buttons[] = array(
                        'label'   => 'Retirer',
                        'icon'    => 'fas_trash-alt',
                        'onclick' => $package->getJsActionOnclick('removeProduct', array(
                            'id_package_product' => (int) $this->id
                                ), array(
                            'form_name'   => 'remove_product',
                            'no_triggers' => true
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function displayProduct()
    {
        if ($this->isLoaded()) {
            $product = $this->getChildObject('product');
            if (BimpObject::objectLoaded($product)) {
                $html .= BimpObject::getInstanceNomUrlWithIcons($product->dol_object);
                $html .= '<br/>' . $product->getData('label');
                return $html;
            }
        }

        return '';
    }
}
