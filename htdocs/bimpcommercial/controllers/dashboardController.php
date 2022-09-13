<?php

class dashboardController extends BimpController
{

    public function renderHtml()
    {
        $html = '';

        $html .= '<h2>' . BimpRender::renderIcon('fas_tachometer-alt', 'iconLeft') . 'Dashboard commercial</h2>';

        // ToolsBar:
        $html .= '<div class="buttonsContainer align-right" style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #000000">';
        
        $html .= '</div>';
        
        // Stats: 
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-8">';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
