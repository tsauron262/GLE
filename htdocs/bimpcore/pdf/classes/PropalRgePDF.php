<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalPDF.php';

class PropalRgePDF extends PropalPDF
{
    public static $label_prime = "Prime CEE EDF";
    protected function initHeader()
    {
        parent::initHeader();

        $header_right = '';
        
        $soc_logo_file = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/img/rge.png';   
        if (file_exists($soc_logo_file)) {
            $sizes = dol_getImageSize($soc_logo_file, false);
            if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'] / 3, $sizes['height'] / 3, 200, 50);

                $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
            }
        }
        $this->header_vars['header_right'] = $header_right;
    }
    
    public function getTargetInfosHtml() {
        $html = parent::getTargetInfosHtml();
        
        $contacts = $this->propal->getIdContact('external', 'CHANTIER');
        if (isset($contacts[0]) && $contacts[0]) {
            BimpTools::loadDolClass('contact');
            $contact = new Contact($this->db);
            if ($contact->fetch((int) $contacts[0]) > 0) {
                $this->contactChantier = $contact;
            }
        }
        if (isset($this->contactChantier) && is_object($this->contactChantier)) {
            $html .= '<br/><div class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; ">';
            $html .= '<span style="color: #' . $this->primary . '">' . ('Adresse du chantier :') . '</span></div>';
            $html .= '';
            $html .= str_replace("\n", "<br/>", pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contactChantier, !is_null($this->contactChantier) ? 1 : 0, 'target'));
        }
        
       
        
        return $html;
    }

}
