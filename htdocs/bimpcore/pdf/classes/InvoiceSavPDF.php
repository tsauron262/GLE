<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/InvoicePDF.php';

class InvoiceSavPDF extends InvoicePDF
{

    public static $type = 'sav';
    public $sav = null;

    public function init($object)
    {
        if (!is_null($object) && is_a($object, 'Facture') && (int) $object->id) {
            $this->sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            if (!$this->sav->find(array('id_facture' => (int) $object->id))) {
                if (!$this->sav->find(array('id_facture_acompte' => (int) $object->id))) {
                    unset($this->sav);
                    $this->sav = null;
//                    $this->errors[] = 'Aucun SAV associé à cette facture trouvé';
                }
            }
        }

        parent::init($object);
    }

    protected function initHeader()
    {
        parent::initHeader();
        
        if (!is_null($this->sav)) {
            $rows .= $this->sav->getData('ref') . '<br/>';
            $equipment = $this->sav->getchildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                $rows .= $equipment->getData('serial');
            }
        }

        $this->header_vars['apple_img'] = DOL_DOCUMENT_ROOT . "/synopsistools/img/agree.jpg";
        $this->header_vars['header_middle'] = $rows;
    }

    public function getAfterTotauxHtml()
    {
        if ($this->object->type === 3) {
            return '';
        }
        $html .= '<table style="width: 95%" cellpadding="3">';

        $html .= '<tr>';
        $html .= '<td>Matériel récupéré le:</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Signature :</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/></td>';
        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }
}
