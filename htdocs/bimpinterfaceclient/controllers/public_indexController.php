<?php

class public_indexController extends BimpController {
    public function renderHtml() {
        global $langs;
     $html = "";
     
     $html .= '<div class="row"><div class="col-md-12"><div class="header"><h3 class="title" data-color="bimp">'.$langs->trans('bonjour').'</h3></div>';
     $html .= '<div class="content" ><div class="col-md-12">';
     
     $html .= '</div></div>';
      
      return $html;
    }

}
