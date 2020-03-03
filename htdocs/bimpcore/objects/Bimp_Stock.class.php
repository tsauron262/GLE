<?php

class Bimp_Stock extends BimpObject
{

    public function displayProduct()
    {
        $product = $this->getChildObject('product');
        if (BimpObject::objectLoaded($product)) {
            $html = $product->dol_object->getNomUrl(1);
            $html .= BimpRender::renderObjectIcons($product, 1, 'default');
            $html .= '<br/>';
            $html .= $product->getData('label');
            return $html;
        }

        return '';
    }
}
