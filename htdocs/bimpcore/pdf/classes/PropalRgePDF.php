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

}
