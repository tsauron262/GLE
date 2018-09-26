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
                if($equipment->getData('product_label') != "")
                    $rows .= $equipment->getData('product_label')."<br/>";
                $rows .= $equipment->getData('serial');
            }
            
            $infoCentre = $this->sav->getCentreData();
            global $mysoc;
            $mysoc->email = $infoCentre['mail'];
            $mysoc->phone = $infoCentre['tel'];
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
//        $html .= '<p style="font-size: 7px; color: #002E50">';
        $html .= '<div style="text-indent: 15px; font-size: 7px; color: #002E50">';
        $html .= 'Si le service est requis conformément à une obligation de réparation d’un tiers, ces informations seront ';
        $html .= 'transférées au tiers pour vérification et des objectifs de qualité, notamment la confirmation de la transaction de réparation et la ';
        $html .= 'soumission d’une enquéte client. En signant, vous acceptez ce transfert ainsi que l’utilisation de ces informations par un tiers.';
        $html .= '<br/>';
        $html .= 'Les pièces de maintenance ou les produits utilisés pour la réparation de votre produit sont neufs ou d\'un état équivalent à neuf ';
        $html .= 'en termes de performance et de fiabilité. ';
        $html .= '<br/>';
        $html .= 'Pour du matériel couvert par Apple, la garantie initiale s\'applique. Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d\'oeuvre. Les pannes logicielles ne sont pas couvertes par la garantie du fabricant. Une garantie de 30 jours est appliquée pour les réparations logicielles.';
        $html .= '<br/>';
        $html .= 'Les informations personnelles requises suivantes (nom, adresse, téléphone et adresse mail) sont nécessaires pour poursuivre la ';
        $html .= 'demande de réparation.';
        $html .= '</div>';
//        $html .= '</p>';
        $html .= '</td></tr></table>';

        $this->writeContent($html);
    }
}
