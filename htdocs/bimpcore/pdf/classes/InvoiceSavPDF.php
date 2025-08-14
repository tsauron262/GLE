<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/InvoicePDF.php';

class InvoiceSavPDF extends InvoicePDF
{

    public static $type = 'sav';
    public static $use_cgv = false;
    public $sav = null;
    public $signature_bloc = false;

    public function __construct($db)
    {
        parent::__construct($db);

        $primary = BimpCore::getParam('pdf/primary_sav', '');

        if ($primary) {
            $this->primary = $primary;
        }
    }

    public function init($object)
    {
        if (!is_null($object) && is_a($object, 'Facture') && (int) $object->id) {
            $this->sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            if (!$this->sav->find(array('id_facture' => (int) $object->id))) {
                if (!$this->sav->find(array('id_facture_acompte' => (int) $object->id))) {
                    unset($this->sav);
                    $this->sav = null;
                }
            }

            // Chargement CGV :

            $cgv_file = '';
            switch (BimpCore::getExtendsEntity()) {
				case 'bimp':
				case 'champagne':
                    if (BimpObject::objectLoaded($this->sav)) {
                        $code_centre = $this->sav->getData('code_centre');
                    }

                    if (!$code_centre) {
                        $code_centre = 'L'; // Par précaution sinon aucune CGV ne peut être intégrée.
                    }
					$centres = BimpCache::getCentresData();
					if (isset($centres[$code_centre]) and is_array($centres[$code_centre])) {
						$centre = $centres[$code_centre];
						$bimpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $this->sav->getData('id_facture'));
						if (BimpObject::objectLoaded($bimpFacture)) {
							$date = ($bimpFacture->getData('date_valid') ? '\'' . $bimpFacture->getData('date_valid') . '\'' : '\'' . $bimpFacture->getData('datec') . '\'');
						}
						else $date = 'CURRENT_DATE';
						$filter = array(
							'types_pieces' => array('in_braces' => 'facture'),
							'date_start'   => array('custom' => 'a.date_start <= ' . $date),
							'secteurs' => array('in_braces' => 'S'),
							'id_centre'    => $centre['id'],
						);
						// echo '<pre>' . print_r($filter, true) . '</pre>';
						// exit();
						$obj_cgv = BimpCache::findBimpObjectInstance('bimpcore', 'BimpCGV', $filter, true, false, false, 'date_start', 'DESC');

						if (BimpObject::objectLoaded($obj_cgv)) {
							$cgv_file = $obj_cgv->getFilesDir();
							$cgv_file .= 'CGV_file.pdf';
						}
					}
					if (!$cgv_file) {
						$cgv_file = DOL_DOCUMENT_ROOT . '/bimpsupport/pdf/cgv_boutiques/cgv_' . $code_centre . '.pdf';
					}
					// exit($cgv_file);
                    break;

                case 'actimac':
                    $cgv_file = DOL_DOCUMENT_ROOT . '/bimpsupport/pdf/cgv_actimac.pdf';
                    break;
            }

            if ($cgv_file && file_exists($cgv_file)) {
                $this->pdf->extra_concat_files[] = $cgv_file;
            }
        }

        parent::init($object);
    }

    protected function initHeader()
    {
        parent::initHeader();
        $rows = '';
        if (!is_null($this->sav)) {
            $rows .= '<span style="color: #' . $this->primary . '">' . $this->sav->getData('ref') . '</span><br/>';
            $equipment = $this->sav->getchildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                $rows .= $equipment->getData('serial');
                $imei = $equipment->getData('imei');
                if ($imei != '' && $imei != "n/a")
                    $rows .= "<br/>" . $imei;
//                $prod = $equipment->getchildObject('product');
                $pordDesc = $equipment->displayProduct('default', true, true);
                if ($pordDesc != '' && $pordDesc != "n/a")
                    $rows .= "<br/>" . '<span style="font-size: 8px;">' . $pordDesc . '</span>';
            }
        }

//        $this->header_vars['apple_img'] = DOL_DOCUMENT_ROOT . "/synopsistools/img/agree.jpg";
        $this->header_vars['header_right'] = $rows;
    }

    public function renderTop()
    {
        parent::renderTop();
        $this->writeContent('<div style="font-size: 9px">Pour augmenter la durée de vie de vos produits Apple, rendez vous sur :<br/><a href="https://support.apple.com/fr-fr">https://support.apple.com/fr-fr</a> ou scannez ce QR code</div><br/><br/>');
        $qr_dir = DOL_DATA_ROOT . "/bimpcore/tmp";
        $this->getQrCode('https://support.apple.com/fr-fr', $qr_dir);

        if (file_exists($qr_dir . '/apple.png')) {
            $this->pdf->Image($qr_dir . "/apple.png", 120, $this->pdf->getY() - 19, 0, 15);
        } else {
            $this->errors[] = 'Echec de la création du QrCode';
        }
    }

    public function getAfterTotauxHtml()
    {
        return '';
    }

    function getQrCode($data, $dir, $file = "apple.png")
    {
        require_once(DOL_DOCUMENT_ROOT . "/synopsisphpqrcode/qrlib.php");
        if (!is_dir($dir))
            mkdir($dir);

        QRcode::png($data
                , $dir . "/" . $file
                , "L", 4, 2);
    }

    public function renderSignature()
    {
//        if ($this->object->type === 3) {
//            return;
//        }
//
//        $html = '';
//        $html .= '<table style="width: 95%" cellpadding="3">';
//
//        $html .= '<tr>';
//        $html .= '<td>Matériel récupéré le:</td>';
//        $html .= '</tr>';
//
//        $html .= '<tr>';
//        $html .= '<td>Signature :</td>';
//        $html .= '</tr>';
//
//        $html .= '<tr>';
//        $html .= '<td style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/></td>';
//        $html .= '</tr>';
//
//        $html .= '</table>';
//
//        $table = new BimpPDF_Table($this->pdf, false);
//        $table->cellpadding = 0;
//        $table->remove_empty_cols = false;
//        $table->addCol('left', '', 95);
//        $table->addCol('right', '', 95);
//
//        $table->rows[] = array(
//            'left'  => '',
//            'right' => $html
//        );
//
//        $this->writeContent('<br/><br/>');
//        $table->write();
    }

    public function renderSavConditions()
    {
        $html = '<table cellpadding="20px"><tr><td>';
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
            if ($equipment->getData('old_serial') != '') {
                $html .= '<p style="font-size: 6px; font-style: italic">';
                if ($html != '')
                    $html .= "<br/>";
                $html .= 'Ancien(s) serial :<br/>' . str_replace('<br/>', ' - ', $equipment->getData('old_serial'));
                $html .= '</p>';
            }
        }
        $this->writeContent($html);
    }
}
