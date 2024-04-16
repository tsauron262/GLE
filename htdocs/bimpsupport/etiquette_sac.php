<?php
require_once("../main.inc.php");
require_once ("../bimpcore/Bimp_Lib.php");
ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';
class EtiquetteSac1 extends BimpEtiquettePDF {


    public function __construct($db) {
        parent::__construct($db, $orientation = 'L', $format = array(75, 36));

        $this->typeObject = "sac";
        $this->prefName = "Etiquette_Sac_";
    }

    public function writeContent($content = '') {
        $debug = false;
//        $html = "";
        
        $ids = array();
        if(isset($this->object) && is_array($this->object))
            $ids = $this->object;
        
        $objects = array();
        foreach($ids as $id){
            $objTmp = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Sac', $id);
            $objects[] = $objTmp;
        }
        
        $i = 0;
        foreach($objects as $object){
            $i++;
            if($i > 1)
                $this->pdf->newPage();
            $this->pdf->SetFont('times', 'B', 15);
            $this->pdf->setXY(12,1);
            $this->pdf->Cell(59,5,dol_trunc($object->getData('ref'),18),1,0,'C');

            $this->pdf->setXY(11,8);
            $this->pdf->SetFont('times', '', 10);

//            $label = $object->label;
//            $label = dol_trunc($label,90);
//            if(strlen($label) > 53)
//                $this->pdf->SetFont('times', '', 9);
//
//
//
//            $this->pdf->MultiCell(77,5,$label,$debug,'C');

    //         $html .= "<span class='center'>".$label."</span>";
    //
    //        $html .= "<div class='tier fleft'></div>";
    //        $html .= "<div class='cadre tier fleft'>".dol_print_date(dol_now(), "%B %Y")."</div>";
    //        $html .= "<div class='tier fleft'>".price($object->price)." €</div>";



    //        $this->writeContent($html);
            if (file_exists(static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg'))
                $this->pdf->Image( static::$tpl_dir . '/' . static::$type . '/logomininoir.jpg', 16,25,15,10);


            $codeBar = ($object->barcode != "")? $object->barcode : $object->ref;
            $maxLn = 35;
            $longeur = (strlen($codeBar) < $maxLn)? strlen($codeBar) : $maxLn;
            $this->pdf->write1DBarcode($codeBar, 'C128', 22-($longeur), 13, 7+($longeur*2), 5, '', array('text'=> true));

            
            require_once(DOL_DOCUMENT_ROOT . "/synopsisphpqrcode/qrlib.php");
            $dir = DOL_DATA_ROOT . "/bimpcore/sav/tmp/";
            if (!is_dir($dir))
                mkdir($dir);
                $file = $dir . "/sac".$object->id.".png";
            QRcode::png($object->ref
                , $file
                , "L", 4, 2);
            $this->pdf->Image($file, 45, 9, 0, 24);
            
            


//            $this->pdf->setXY(32,29);
//            $this->pdf->Cell(25,5,dol_print_date(dol_now(), "%B %Y"),1,0,'C');
//            $this->pdf->setXY(59,29);
//            $price = $object->price * (1+$object->tva_tx/100);
//            $this->pdf->Cell(24,5,price($price)." €",$debug,0,'R');
        }
    }

}





global $db, $langs;
$errors = array();

$type = BimpTools::getValue('type', '', 'aZ09');

if (!$type) {
    die('Erreur: type d\'étiquette à générer absent');
}

$id_sacs = array();

if (BimpTools::isSubmit('id_sacs')) {
    $id_sacs = explode(',', BimpTools::getValue('id_sacs', '', 'aZ09comma'));
}

if (count($id_sacs) == 0) {
    die('Erreur: ID du produit absent');
}


$qty = (int) BimpTools::getValue('qty', 1, 'int');

$pdf = null;
switch ($type) {
    case 'normal':
        $pdf = new EtiquetteSac1($db);
        break;

//    case 'magasin':
//        require_once './pdf/classes/EtiquetteProd2.php';
//        $pdf = new EtiquetteProd2($db);
//        break;

    default:
        die('Erreur: type d\'étiquette invalide: "' . $type . '"');
}

$pdf->qty_etiquettes = $qty;
$pdf->init($id_sacs);


if ($pdf->render('etiquette_sac_' . $type . '_' . $id_product . '.pdf', true, true)) {
    exit;
}

if (count($pdf->errors)) {
    $pdf->displayErrors();
}








