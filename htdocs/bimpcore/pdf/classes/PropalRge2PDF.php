<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalPDF.php';

class PropalRge2PDF extends PropalPDF
{
    public static $label_prime = "Prime CEE EDF";
    public static $label_prime2 = "MaPrimeRénov'";
    protected function initHeader()
    {
        parent::initHeader();

        $header_right = '';
        
        $soc_logo_file = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/src/img/rge.png';   
        if (file_exists($soc_logo_file)) {
            $sizes = dol_getImageSize($soc_logo_file, false);
            if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'] , $sizes['height'] , 250, 90);

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
    
    
    
    public function renderAfterLines()
    {
        $html = parent::renderAfterLines();
        if(isset($this->object->array_options['options_prime']) && $this->object->array_options['options_prime'] > 0){
            $html .= '<table cellpadding="20px"><tr><td>';
    //        $html .= '<p style="font-size: 7px; color: #002E50">';
            $html .= '<div style="text-indent: 15px; font-size: 7px; color: #002E50">';
            $html .= "Les travaux ou prestations objet du présent document donneront lieu à une contribution financière versée par LA COMPAGNIE ANTILLAISE DES PÉTROLES dans le cadre de son rôle actif et incitatif sous forme de prime, directement à l'entrepreneur réalisant les travaux sous réserve de l’engagement de fournir exclusivement à LA COMPAGNIE ANTILLAISE DES PÉTROLES par l’intermédiaire de son Mandataire NEOVEE CEE les documents nécessaires à la valorisation des opérations au titre du dispositif des Certificats d’Économies d’Énergie et sous réserve de la validation de l’éligibilité du dossier par LA COMPAGNIE ANTILLAISE DES PÉTROLES puis par l’autorité administrative compétente, le PNCEE.
Les travaux concernent des opérations standardisées, selon la fiche BAR-TH-104,. Le montant de cette contribution financière, hors champ d’application de la TVA, est susceptible de varier en fonction des travaux effectivement réalisés et du volume des CEE attribués à l’opération et est estimé à ".price($this->object->array_options['options_prime'])." €";
            $html .= '</div>';
    //        $html .= '</p>';
            $html .= '</td></tr></table>';
        }
        $this->writeContent($html);
    }
    
    
    public function getSenderInfosHtml() {
        $html = parent::getSenderInfosHtml();
        
        $html .= '<span style="font-size: 7px"><br/>DÉCENNALE MAAF:143052607MMCE001
<br/>Chauray - 79036 NIORT.
<br/>N° QUALIT ENR/PAC/BOIS/CHAUFFAGE+ : 46512' . '</span>';
        return $html;
    }
    

}
