<?php

class Bimp_Stock extends BimpObject
{

    public function displayProduct()
    {
        $product = $this->getChildObject('product');
        if (BimpObject::objectLoaded($product)) {
            $html = $product->getLink();
            $html .= BimpRender::renderObjectIcons($product, 1, 'default');
            $html .= '<br/>';
            $html .= $product->getData('label');
            return $html;
        }

        return '';
    }
}
