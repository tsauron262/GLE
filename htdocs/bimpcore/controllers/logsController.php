<?php

class logsController extends BimpController
{

    public function renderHtml()
    {
        $html = '';

        $html .= '<div class="page_content">';
        $html .= '<h1>Bimp Logs</h1>';

        $html .= '<div class="row">';

        $html .= '<div class="col-lg-3 col-md-4 col-sm-12">';

        $title = BimpRender::renderIcon('fas_filter', 'iconLeft') . 'Filtres';

        $rows = array(
            'Du'          => BimpInput::renderInput('date', 'date_from', ''),
            'Au'          => BimpInput::renderInput('date', 'date_to', ''),
            'Utilisateur' => BimpInput::renderInput('search_user', 'id_user', '', array('include_empty' => 1)),
            'Objet' => BimpInput::renderInput('select', 'bimp_object', '', array(
                'options' => BimpCache::getBimpObjectsArray(true, true, true)
            ))
        );

        $content = '<div id="bimp_logs_search_form" class="bimp_form labels_up">';

        foreach ($rows as $label => $input) {
            $content .= '<div class="bimp_form_row">';
            $content .= '<label>' . $label . '</label>';
            $content .= '<div class="bimp_form_input">' . $input . '</div>';
            $content .= '</div>';
        }

        $content .= '</div>';

        $footer = '<div style="text-align: right;">';
        $footer .= '<button class="btn btn-default" onclick="">';
        $footer .= BimpRender::renderIcon('fas_search', 'iconLeft') . 'Rechercher';
        $footer .= '</button>';
        $footer .= '</div>';

        $html .= BimpRender::renderPanel($title, $content, $footer, array(
                    'type' => 'secondary'
        ));
        $html .= '</div>';

        $html .= '<div class="col-lg-9 col-md-8 col-sm-12">';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
