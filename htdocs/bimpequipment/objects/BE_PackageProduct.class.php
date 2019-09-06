<?php

class BE_PackageProduct extends BimpObject
{

    public function displayProduct()
    {
        if ($this->isLoaded()) {
            $product = $this->getChildObject('product');
            if (BimpObject::objectLoaded($product)) {
                $html = $product->getNomUrl(1, 1, 1, 'default');
                $html .= '<br/>' . $product->getData('label');
                return $html;
            }
        }

        return '';
    }
}
