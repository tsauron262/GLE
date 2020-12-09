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
            $rows .= '<span style="color: #' . BimpCore::getParam('pdf/primary', '000000') . '">' . $this->sav->getData('ref') . '</span><br/>';
            $equipment = $this->sav->getchildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                $rows .= $equipment->getData('serial');
                $imei = $equipment->getData('imei');
                if($imei != '' && $imei != "n/a")
                    $rows .= "<br/>".$imei;
            }
        }

//        $this->header_vars['apple_img'] = DOL_DOCUMENT_ROOT . "/synopsistools/img/agree.jpg";
        $this->header_vars['header_right'] = $rows;
    }

    public function getAfterTotauxHtml($blocSignature = false)
    {
        return '';
    }

    public function renderSignature()
    {
        if ($this->object->type === 3) {
            return;
        }

        $html = '';
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

        $table = new BimpPDF_Table($this->pdf, false);
        $table->cellpadding = 0;
        $table->remove_empty_cols = false;
        $table->addCol('left', '', 95);
        $table->addCol('right', '', 95);

        $table->rows[] = array(
            'left'  => '',
            'right' => $html
        );

        $this->writeContent('<br/><br/>');
        $table->write();
    }

    public function renderSavConditions()
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

    public function renderAfterBottom()
    {
        $this->renderFullBlock('renderSignature');
        $this->renderFullBlock('renderSavConditions');
    }
    public function renderAfterLines()
    {
        $html = parent::renderAfterLines();
        if (!is_null($this->sav)) {
            $equipment = $this->sav->getchildObject('equipment');
            if($equipment->getData('old_serial') != ''){
                $html .= '<p style="font-size: 6px; font-style: italic">';
                if($html != '')
                    $html .= "<br/>";
                $html .= 'Ancien(s) serial :<br/>'.str_replace('<br/>', ' - ',$equipment->getData('old_serial'));
            $html .= '</p>';
            }
        }
        $this->writeContent($html);
    }
}
