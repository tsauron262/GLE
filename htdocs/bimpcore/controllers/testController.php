<?php

class testController extends BimpController
{

    public function renderHtml()
    {
        $html = 'TEST';
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

        $gsx = new GSX_v2(897316);

        $res = $gsx->fetchConsignmentOrders('INCREASE', 'ALL');

        $html .= '<pre>';
        $html .= print_r($res, 1);
        $html .= '</pre>';
        
        $html .= 'Erreurs<pre>';
        $html .= print_r($gsx->getErrors(), 1);
        $html .= '</pre>';

        return $html;
    }
}
