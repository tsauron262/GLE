<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';

class EquipmentPDF extends BimpEtiquettePDF
{

    public $equipments = array();

    public function __construct($db)
    {
        parent::__construct($db);

        $this->prefName = "Etiquette_Equipment_";
    }

    protected function renderContent()
    {
        if (count($this->equipments) > 1) {
            $fl = true;
            foreach ($this->equipments as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $this->object = $equipment;

                    if (!$fl) {
                        $this->pdf->newPage();
                    } else {
                        $fl = false;
                    }

                    $this->writeEquipmentContent();
                }
            }
        } else {
            $this->writeEquipmentContent();
        }
    }

    protected function writeEquipmentContent()
    {
        $html = '';

        if (!BimpObject::objectLoaded($this->object) || !is_a($this->object, 'Equipment')) {
            $this->writeContent('Erreur: équipement absent ou invalide');
        } else {
            $html .= '<table cellpadding="1">';
            $html .= '<tr>';
            $name = $this->object->getProductLabel();
            $serial = $this->object->getRef();

            if (strlen($name) > 50) {
                $name = substr($name, 0, 50) . '...';
            }

            $font_size = 18;
            $max_chars = 14;

            while (BimpTools::getStringNbLines($name, $max_chars) > 1) {
                $max_chars++;
                $font_size--;

                if ($font_size < 10) {
                    break;
                }
            }

            $html .= '<td style="text-align: center; font-size: ' . $font_size . 'px; font-weight: bold">' . $name . '</td>';
            $html .= '</tr>';

            $product = $this->object->getChildObject('product');
            if (BimpObject::objectLoaded($product)) {
                $html .= '<tr>';
                $html .= '<td style="text-align: left;font-size: 11px;font-weight: bold;color: #000000">Ref: ' . $product->ref . '</td>';
                $html .= '</tr>';
            }

            if ($serial) {
                $html .= '<tr>';
                $html .= '<td style="text-align: left;font-size: 11px;font-weight: bold;color: #000000">N/S: ' . $serial . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';

            $this->writeContent($html);

            if ($serial) {
                 $this->pdf->setY();
                $this->pdf->write1DBarcode($serial, 'C128', 19, 22, 50, 10, '', array('text' => false));
            }
        }
    }
}
