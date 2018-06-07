<?php

//require_once("../config/settings.inc.php");
include('./phpqrcode/qrlib.php');
include('./codebar/php-barcode.php');
require('./pdf/fpdf.php');



require("init.php");

class PDF_EAN13 extends FPDF {

    function EAN13($x, $y, $barcode, $h = 16, $w = .35) {
        $this->Barcode($x, $y, $barcode, $h, $w, 13);
    }

    function UPC_A($x, $y, $barcode, $h = 16, $w = .35) {
        $this->Barcode($x, $y, $barcode, $h, $w, 12);
    }

    function GetCheckDigit($barcode) {
        //Compute the check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i+=2)
            $sum+=3 * $barcode[$i];
        for ($i = 0; $i <= 10; $i+=2)
            $sum+=$barcode[$i];
        $r = $sum % 10;
        if ($r > 0)
            $r = 10 - $r;
        return $r;
    }

    function TestCheckDigit($barcode) {
        //Test validity of check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i+=2)
            $sum+=3 * $barcode[$i];
        for ($i = 0; $i <= 10; $i+=2)
            $sum+=$barcode[$i];
        return ($sum + $barcode[12]) % 10 == 0;
    }

    function Barcode($x, $y, $barcode, $h, $w, $len) {
        //Padding
        $barcode = str_pad($barcode, $len - 1, '0', STR_PAD_LEFT);
        if ($len == 12)
            $barcode = '0' . $barcode;
        //Add or control the check digit
        if (strlen($barcode) == 12)
            $barcode.=$this->GetCheckDigit($barcode);
        elseif (!$this->TestCheckDigit($barcode))
            $this->Error('Incorrect check digit');
        //Convert digits to bars
        $codes = array(
            'A' => array(
                '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011',
                '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011'),
            'B' => array(
                '0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101',
                '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111'),
            'C' => array(
                '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100',
                '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100')
        );
        $parities = array(
            '0' => array('A', 'A', 'A', 'A', 'A', 'A'),
            '1' => array('A', 'A', 'B', 'A', 'B', 'B'),
            '2' => array('A', 'A', 'B', 'B', 'A', 'B'),
            '3' => array('A', 'A', 'B', 'B', 'B', 'A'),
            '4' => array('A', 'B', 'A', 'A', 'B', 'B'),
            '5' => array('A', 'B', 'B', 'A', 'A', 'B'),
            '6' => array('A', 'B', 'B', 'B', 'A', 'A'),
            '7' => array('A', 'B', 'A', 'B', 'A', 'B'),
            '8' => array('A', 'B', 'A', 'B', 'B', 'A'),
            '9' => array('A', 'B', 'B', 'A', 'B', 'A')
        );
        $code = '101';
        $p = $parities[$barcode[0]];
        for ($i = 1; $i <= 6; $i++)
            $code.=$codes[$p[$i - 1]][$barcode[$i]];
        $code.='01010';
        for ($i = 7; $i <= 12; $i++)
            $code.=$codes['C'][$barcode[$i]];
        $code.='101';
        //Draw bars
        for ($i = 0; $i < strlen($code); $i++) {
            if ($code[$i] == '1')
                $this->Rect($x + $i * $w, $y, $w, $h, 'F');
        }
        //Print text uder barcode
        $this->SetFont('Arial', '', 12);
        $this->Text($x, $y + $h + 11 / $this->k, substr($barcode, -$len));
    }

}

$cm = $_REQUEST['cm'];
if (isset($_REQUEST['co']))
    $cm = $_REQUEST['co'];


if ($cm != "") {


    $sql = "SELECT *  FROM `".PRESTA_PREF."orders` o
	LEFT JOIN ".PRESTA_PREF."order_detail od ON od.`id_order` = o.`id_order` WHERE  ";

    if ($modeTest) {
        $sql .= "od.id_order_detail IN (SELECT id_order_details FROM ".PRESTA_PREF."tickets);";
    } else {
        $sql.= "o.`reference` = '" . $cm . "'";
    }
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {


        $pdf = new PDF_EAN13('L');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $marge = 7;
        $x = $y = $marge;
        $nbP = 0;

        while ($dataSql = $result->fetch_assoc()) {
            if ($dataSql['valid'] != 1)
                die("Vous achat n'est pas encore considéré comme payé");
            for ($i = 0; $i < $dataSql['product_quantity']; $i++) {
                $nbPColl = 2;
                $nbL = 3;
                if ($nbP == $nbL) {
                    $y = $marge;
                    $x = 150;
                }
                if ($nbP == $nbL * $nbPColl) {
                    $y = $marge;
                    $x = $marge;
                    $pdf->addPage();
                    $nbP = 0;
                }



                $codeBill = $dataSql['reference'] . "-" . $dataSql['product_id'] . "-" . ($i + 1);

                $sql2 = "SELECT *  FROM `".PRESTA_PREF."tickets` WHERE  `code` = '" . $codeBill . "'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows < 1) {
                    $codeBar = rand(100000000000, 999999999999);



                    $sql3 = "INSERT INTO `".PRESTA_PREF."tickets` (`code`, `codeBar`, `id_order_details`) VALUES ('" . $codeBill . "', " . $codeBar . ", " . $dataSql['id_order_detail'] . ");";

                    $result3 = $conn->query($sql3);


                    $result2 = $conn->query($sql2);
                }
                $dataSql2 = $result2->fetch_assoc();


                if ($dataSql['product_id'] == 13)
                    $titre = "VENDREDI";
                elseif ($dataSql['product_id'] == 14)
                    $titre = "SAMEDI";
                elseif ($dataSql['product_id'] == 16 || $dataSql['product_id'] == 15)
                    $titre = "PASS";
                else
                    $titre = "";


                $pdf->SetY($y);
                $pdf->SetX($x);

                $extra = ($modeTest ? '&mode=testazerty' : '');
                QRcode::png('http://sucsenscene.fr/billets/test.php?num=' . $codeBill . $extra, "qr/qr" . $codeBill . ".png");
                if ($titre != "")
                    $pdf->Image($titre . ".jpg", $x + 0, $y, 140);


                $pdf->Image("./qr/qr" . $codeBill . ".png", $x + 105, $y + 1, 34);

                // code 128
                $pdf->EAN13($x + 105, $y + 40, $dataSql2['codeBar']);


                $pdf->SetY($y + 31.5);
                $pdf->SetX($x + 104);
                $pdf->SetFontSize(10);
                $pdf->MultiCell(80, 10, $codeBill);



                $y = $y + 67;
                $nbP++;
            }
        }

        $pdf->Output();
    }
    else {
        echo "Commande inconnue";
    }
} else {
    echo "Numéro de commande innexistant";
}
?>
