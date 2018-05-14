<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalPDF.php';

class PropalSavPDF extends PropalPDF
{

    public static $type = 'sav';
    public $sav = null;

    public function init($object)
    {
        if (!is_null($object) && is_a($object, 'Propal') && (int) $object->id) {
            $this->sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            if (!$this->sav->find(array('id_propal' => (int) $object->id))) {
                unset($this->sav);
                $this->sav = null;
                $this->errors[] = 'Aucun SAV associé à cette propale trouvé';
            }
        }

        parent::init($object);
    }

    protected function initHeader()
    {
        parent::initHeader();

        $rows = '';

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

    public function renderBeforeLines()
    {
        if (!is_null($this->sav)) {
            if ((int) $this->sav->getData('id_user_tech')) {
                $tech = $this->sav->getChildObject('user_tech');
                if (!is_null($tech) && $tech->isLoaded()) {
                    $this->writeContent('<p style="font-size: 9px"><strong>Technicien en charge : </strong>' . $tech->dol_object->getFullName($this->langs) . '</p>');
                }
            }
        }
    }

    public function renderAfterBottom()
    {
        $html .= '<table cellpadding="20px"><tr><td>';
        $html .= '<p style="font-size: 7px; color: #002E50">';
        $html .= 'Les informations personnelles requises suivantes (nom, adresse, téléphone et adresse mail) sont nécessaires pour poursuivre la ';
        $html .= 'demande de réparation.<br/>Si le service est requis conformément à une obligation de réparation d’un tiers, ces informations seront ';
        $html .= 'transférées au tiers pour vérification et des objectifs de qualité, notamment la confirmation de la transaction de réparation et la ';
        $html .= 'soumission d’une enquéte client. En signant, vous acceptez ce transfert ainsi que l’utilisation de ces informations par un tiers. <br/><br/>';
        $html .= 'Les pièces de maintenance ou les produits utilisés pour la réparation de votre produit sont neufs ou d\'un état équivalent à neuf ';
        $html .= 'en termes de performance et de fiabilité.';
        $html .= '</p>';
        $html .= '</td></tr></table>';

        $this->writeContent($html);
    }
}
