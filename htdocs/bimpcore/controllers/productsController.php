<?php


class productsController extends BimpController
{
    
    public function renderProductEntrepot()
    {
        $html = '';
        $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Product_Entrepot');
        $list = new BC_ListTable($obj);
        
        if(isset($_GET['date_for_stock']))
            $list->addExtraData('date_for_stock', $_GET['date_for_stock']);
        
        $html .= $list->renderHtml();
        
        return $html;
    }
}
