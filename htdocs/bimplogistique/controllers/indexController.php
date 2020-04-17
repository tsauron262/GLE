<?php


class indexController extends BimpController
{
    protected function ajaxProcessAddProductInput() {
        
        $number = BimpTools::getPostFieldValue('number');
        $input_name = BimpTools::getPostFieldValue('input_name');
        
        $label = '<strong style="margin-right: 5px;" >Produit n°' . $number . '</strong>';
        $input = BimpInput::renderInput('search_product', 'prod_' . $input_name . '_' . $number);
        
        $delete_btn = '<button type="button" class="addValueBtn btn btn-danger" '
                . 'onclick="deleteUnitProduct($(this))" style="margin-left: 5px;">'
                . '<i class="fas fa5-trash-alt"></button>';
        
        $div_url = '<div url_prod></div>';
        
        
        $html  = '<div name="cnt_prod' . $number . '" style="margin: 12px;" is_product>';
        $html .= $label . $input . $delete_btn . $div_url;
        $html .= '</div>';
        
        die(json_encode(array(
            'data'       => $html,
            'success'    => 'Produit ajouté',
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
    
    protected function ajaxProcessGetProductUrl() {
        
        $id_prod = (int) BimpTools::getPostFieldValue('id_prod');
        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
        
        die(json_encode(array(
            'url'        => $prod->getNomUrl(),
            'success'    => 'Produit ajouté',
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
        
    }

}
