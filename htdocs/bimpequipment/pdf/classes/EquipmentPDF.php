<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';

class EquipmentPDF extends BimpEtiquettePDF
{

    public $equipments = array();
    public $mode = 3;

    public function __construct($db, $orientation = 'L', $format = array(89, 36))
    {
        if(isset($_GET['mode'])) 
                $this->mode = $_GET['mode'];
        if($this->mode > 1)
            $format = array(57, 32);
        parent::__construct($db, $orientation, $format);

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

        $nb = BimpTools::getValue('qty', 1, 'int');
        for($i=0;$i<$nb;$i++){
            if($i>0)
                $this->pdf->newPage();
                
            
            if (!BimpObject::objectLoaded($this->object) || !is_a($this->object, 'Equipment')) {
                $this->writeContent('Erreur: équipement absent ou invalide');
            } else {
                $html = '<table cellpadding="1">';
                $name = $this->object->getProductLabel();
                $serial = $this->object->getRef();

                if($this->mode < 3){
                $html .= '<tr>';
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
                }

                if ($serial) {
                    $html .= '<tr>';
                    $html .= '<td style="text-align: CENTER;font-size: '.(($this->mode == 4) ? '7':'11').'px;font-weight: bold;color: #000000">'.(($this->mode < 3) ? 'SN: ':'') . $serial .(($this->mode == 4) ? '  |  '.$serial:'') . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</table>';

                if ($serial) {
                    $this->pdf->setY(0);
                    if($this->mode == 1){
                        $this->writeContent($html);
                        $this->pdf->write1DBarcode($serial, 'C128', 19, 22, 50, 10, '', array('text' => false));
                    }elseif($this->mode == 2){
                        $this->writeContent($html);
                        $this->pdf->write1DBarcode($serial, 'C128', 4, 17, 49, 10, '', array('text' => false));
                    }elseif($this->mode == 3){
                        $this->pdf->write1DBarcode($serial, 'C128', 4, 2, 49, 9, '', array('text' => false));
                        $this->pdf->setY(10);
                        $this->writeContent($html);
                        $this->pdf->write1DBarcode($serial, 'C128', 4, 17, 49, 9, '', array('text' => false));
                        $this->pdf->setY(25);
                        $this->writeContent($html);
                    }elseif($this->mode == 4){
                        $this->pdf->write1DBarcode($serial, 'C128', 2, 3, 25, 20, '', array('text' => false));
                        $this->pdf->setY(24);
                        $this->writeContent($html);
                        $this->pdf->write1DBarcode($serial, 'C128', 30, 3, 25, 20, '', array('text' => false));
                    }
                }
            }
        }
    }
}
