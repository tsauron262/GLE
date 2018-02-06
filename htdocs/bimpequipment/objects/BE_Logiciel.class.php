<?php

class BE_Logiciel extends BimpObject
{

    public function displayProduct($display)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        if ((int) $this->getData('use_product')) {
            $field = new BC_Field($this, 'id_product');
            $field->display_name = $display;
            return $field->renderHtml();
        }

        $name = $this->getData('name');
        $version = $this->getData('version');
        $field = new BC_Field($this, 'id_fournisseur');

        if ($display === 'card') {
            return BC_Card::renderCard(null, $name, '', array(
                        array(
                            'label' => 'Fabricant',
                            'value' => $field->renderHtml()
                        ),
                        array(
                            'label' => 'Version',
                            'value' => $version
                        )
            ));
        }

        $content = '';
        if ($field->isOk()) {
            $content = '<strong>Fabricant: </strong>' . $field->renderHtml();
            if (!is_null($version) && $version) {
                $content .= '<br/><strong>Version: </strong>' . $version;
            }
        }

        $html = '<span';
        if ($content) {
            $html .= ' class="bs-popover"';
            $html .= BimpRender::renderPopoverData($content, 'bottom', 'true');
        }
        $html .= '>';
        $html .= (!is_null($name) ? $name : '');
        $html .= '</span>';
        return $html;
    }
}
