<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class pdf_bimpfact_attest_lithium extends CommonDocGenerator {

    public $errors = array();
    
    function __construct($db) {
        parent::__construct($db);

    }

    function write_file($facture, $outputlangs = '', $modele = 'attest_lithium') {
        
        global $mysoc, $conf;

        $dir = $facture->getFilesDir();
        $file = $facture->getFilesDir() . 'attest_lithium.pdf';

        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->errors[] = "Impossible de créer le répertoire" . $dir;
                return 0;
            }
        }
            
        // PDF Initialization
        $pdf = pdf_getInstance($this->format);

        // Get commande
        $comm_list = getElementElement('commande', 'facture', null, $facture->id);
        foreach ($comm_list as $data) {
            $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $data['s']);
            if ((int) $comm->getData('fk_soc') == (int) $facture->getData('fk_soc')) {
                $commande = $comm;
                break;
            }
        }

        if(!BimpObject::objectLoaded($commande)) {
            $this->errors[] = "Aucune commande pour ce client n'est associé à " . $facture->getLabel('this');
            return !count($this->errors);
        }

        // Get commande fournisseur
        $cfs = $this->getCommandesFournisseur($commande, $facture);
            
        // PDF template
        $pdf->Open();
        $pdf->AddPage();
        $pdf->AddFont('Helvetica');

        $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/bimpcommercial/core/modules/facture/doc/' . $modele .'.pdf');
        $tplidx1 = $pdf->importPage(1, "/MediaBox");
        $pdf->useTemplate($tplidx1, 0, 0, 0, 0, true);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->setColor('text', 255, 36, 36);

        // Data settings
        
        $i = 0 ;
        $max_row = 5;
        $max_col = 3;
        $init_x = 119;
        $init_y = 48.8;
        $dy = -3;
        $dx = 26;
        $label_commande_fait = 0;
        $i_label_commande = $max_row * 1;
        foreach($cfs as $cf) {
            if($i == $i_label_commande and !$label_commande_fait) {
                $pdf->SetXY($init_x + $dx , $init_y);
                $pdf->MultiCell(300, 4, $commande->getData('libelle'), 0, 'L');
                $label_commande_fait = 1;
                $i++;
            }
 
            if($i == 0 or $i % $max_row != 0) {
                $x = $init_x + ((int) ($i / $max_row)) * $dx;
                $y = $init_y + (($i % $max_row) * $dy);
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(300, 4, $cf->getData('ref'), 0, 'L');
            }
            $i++;
        }
        
        if (!$label_commande_fait) {
            $pdf->SetXY($init_x + $dx , $init_y);
            $pdf->MultiCell(300, 4, $commande->getData('libelle'), 0, 'L');
        }

        $pdf->SetFont('Helvetica', 'B', 7);

        // Checkbox battery position in equipment
        $this->addCheck($pdf, 28, 182.4, 1.5, 0.25);

        // Phone number
        $pdf->SetXY(32, 216.2);
        $pdf->MultiCell(300, 6, '+44 (0) 207 858 0111', 0, 'L');

        // Name
        $nom = 'Franck PINERI';

        $pdf->SetXY(39, 229.3);
        $pdf->MultiCell(300, 6, $nom, 0, 'L');

        // Address
        if(isset($mysoc->address) and $mysoc->address != '')
            $address = $mysoc->address . ' - CS 21055';
        else
            $address = '2 rue des Erables' . ' - CS 21055';
        $pdf->SetXY(39, 232.95);
        $pdf->MultiCell(300, 6, $address, 0, 'L');

        // Zip
        if(isset($mysoc->zip) and 0 < (int) $mysoc->zip)
            $zip = $mysoc->zip;
        else
            $zip = 69760;

        // Town
        if(isset($mysoc->town) and $mysoc->town != '')
            $town = $mysoc->town;
        else
            $town = '2 rue des Erables';
        $pdf->SetXY(39, 236.60);
        $pdf->MultiCell(300, 6, $zip . ' ' . $town, 0, 'L');

        // Signer
        $pdf->SetXY(39, 243);
        $pdf->MultiCell(300, 6, $nom, 0, 'L');

        // Date 
        $pdf->SetXY(118, 243);
        $dt = new DateTime();
        $pdf->MultiCell(300, 6, $dt->format('d/m/Y'), 0, 'L');

        $logo = $conf->mycompany->dir_output . '/signed_contrat.png';
        $pdf->Image($logo, 38, 248, 40);

        // PDF Close
        $pdf->Close();
        $pdf->Output($file, 'F');
        
        return !count($this->errors);
    }

    public function getCommandesFournisseur($commande, $facture) {
        // Obtention ligne de la facture
        $fact_lines = $facture->getChildrenObjects('lines');
//        print_r($fact_lines);
//        die(count($fact_lines). 'AAAAAAAAAAAAAAAAAAAAAA');
//        $fact_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
//        $fact_lines_list = $fact_line_instance->getList(array(
//            'id_obj' => (int) $facture->id
//                ), null, null, 'id', 'asc', 'array', array('id'));
//        $fact_lines = array();
//        
//        foreach ($fact_lines_list as $item)
//            $fact_lines[] = (int) $item['id'];
        
//        // Obtention ligne de la commande
//        $cmd_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
//        $cmd_lines_list = $cmd_line_instance->getList(array(
//            'id_obj' => (int) $commande->id
//                ), null, null, 'id', 'asc', 'array', array('id'));
//        $cmd_lines = array();
//
//        foreach ($cmd_lines_list as $item)
//            $cmd_lines[] = (int) $item['id'];
//
//        // Obtention ligne de la commande fournisseur associée à cette ligne de commande
//        $fourn_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
//        $fourn_lines_list = $fourn_line_instance->getList(array(
//            'linked_object_name' => 'commande_line',
//            'linked_id_object'   => array(
//                'in' => $cmd_lines
//            )
//        ), null, null, 'id', 'asc', 'array', array('id'));
        
        $cfs = array();
        foreach($fact_lines as $fact_line) {
            
                if ($fact_line->getData('linked_object_name') === 'commande_fourn_line' and (int) $fact_line->getData('linked_id_object')) {
                    $comm_fourn_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $fact_line->getData('linked_id_object'));
                    $cf = $comm_fourn_line->getParentInstance();
                    
                 }
                if(BimpObject::objectLoaded($cf))
                    $cfs[$cf->id] = $cf;
                else
                    $this->errors[] = "Une ligne de facture n'est liée à aucune commande fournisseur " . $fact_line->id . ' ' . $fact_line->getData('linked_object_name') . ' ' . $fact_line->getData('linked_id_object');        
                

        }
        

//        $cfs = array();
//        if (!is_null($fourn_lines_list)) {
//            foreach ($fourn_lines_list as $item) {
//                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $item['id']);
//                if (BimpObject::objectLoaded($line)) {
//                    $commande_fourn = $line->getParentInstance();
//                    if (BimpObject::objectLoaded($commande_fourn)) {
//                        $cfs[$commande_fourn->id] = $commande_fourn;
//                    }
//                }
//            }
//        } else {
//            $this->errors[] = "Aucune ligne de commande fournisseur n'est associé à cette commande client";
//            return !count($this->errors);
//        }
//        
//        if (count($cfs) == 0) {
//            $this->errors[] = "Aucune ligne de commande fournisseur n'est associé à cette commande client";
//            return !count($this->errors);
//        }
        
        return $cfs;
    }
    
    function addCheck($pdf, $x, $y, $t = 4, $width = 0.7) {
        $style = array(
            'width' => $width
        );
        
        $pdf->Line($x     , $y, $x + $t, $y + $t, $style);
        $pdf->Line($x + $t, $y, $x     , $y + $t, $style);
    }

}