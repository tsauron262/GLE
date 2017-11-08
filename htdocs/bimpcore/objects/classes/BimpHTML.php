<?php

class BimpHtml {
     public static function renderSection($id, $title, $content, $params = array())
     {
         if (isset($params['foldable']) && $params['foldable']) {
             $foldable = true;
         } else {
             $foldable = false;
         }
         
         $html = '<section id="'.$id.'"';
         if ($foldable) {
             $html .= ' class="foldable_section"';
         }
         $html .= '>';
         
         
         if ($foldable) {
             $html .= '<div class="foldable_section_caption">';
             $html .= $title;
             $html .= '</div>';
             $html .= '<div class="foldable_section_content">';
             $html .= $content;
             $html .= '</div>';
         } else {
             $html .= '<div class="section_title">'.$title.'</div>';
             $html .= '<div class="section_content">';
             $html .= $content;
             $html .= '</div>';
         }
         
         $html .= '</section>';
         
         return $html;
     }
     
     public static function renderToolbar($param)
     {
         
     }
}