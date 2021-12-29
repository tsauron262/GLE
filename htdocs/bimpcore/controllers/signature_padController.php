<?php

class signature_padController extends BimpController
{

    public function renderHtml()
    {
        $html = '';

        $html .= '<style>';
        $html .= 'input[name="id_signature"] {line-height: 28px; vertical-align: middle; font-size: 16px;font-weight: bold;border: 1px solid $gray-dark;padding: 5px;}';
        $html .= ' input[name="id_signature"]:focus {box-shadow: 0 0 4px #0087B4; border-color: #005A78;}';
        $html .= '</style>';

        $html .= '<div id="signature_form" style="padding: 30px; text-align: center">';

        $html .= '<h3>ID Signature:</h3>';
        $html .= '<div style="margin: 30px 0">';
        $html .= BimpInput::renderInput('text', 'id_signature', '', array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 0,
                        'unsigned'  => 1
                    )
        ));

        $html .= '</div>';

        $html .= '<div style="margin: 30px 0">';
        $html .= '<span id="idSignatureSubmit" class="btn btn-success btn-large" onclick="BimpSignaturePad.loadSignatureForm()">';
        $html .= 'Valider' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="ajaxResultContainer" style="display: none; margin-top: 50px"></div>';
        $html .= '</div>';

        return $html;
    }
}
