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
        } else {
            
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
            
            // Get client
            $id_soc = $commande->getData('fk_soc');
            if((int) $id_soc == 0) {
                $this->errors[] = "Aucune client pour la commande n'est associé à  " . $facture->getLabel('this');
                return !count($this->errors);
            }
            
            // Get commerciaux
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
            if(!BimpObject::objectLoaded($soc)) {
                $this->errors[] = "Clien d'id " . $id_soc . " inconnu";
                return !count($this->errors);
            } else  {
                $id_commercial = (int) key($soc->getCommerciauxArray());
                if(0 < $id_commercial)
                    $commercial = BimpCache::getBimpObjectInstance ('bimpcore', 'Bimp_User', $id_commercial);
                if(!BimpObject::objectLoaded($commercial)) {
                    $this->errors[] = "Il n'y a aucun commercial pour le client " . $soc->getName();
                    return !count($this->errors);
                }
            }
            
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
            $ref = $commande->getRef() . ' - ' . $commande->getData('libelle');
            $pdf->SetXY(119, 48.8);
            $pdf->MultiCell(300, 6, $ref, 0, 'L');
            
            $pdf->SetFont('Helvetica', 'B', 7);
            
            // Checkbox battery position in equipment
            $this->addCheck($pdf, 28, 182.4, 1.5, 0.25);
            
            // Phone number
            $pdf->SetXY(32, 216.2);
            $pdf->MultiCell(300, 6, '+44 (0) 207 858 0111', 0, 'L');
            
            // Name
            $nom = $commercial->getName();
                
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
        }
        
        return !count($this->errors);
    }


    
    function addCheck($pdf, $x, $y, $t = 4, $width = 0.7) {
        $style = array(
            'width' => $width
        );
        
        $pdf->Line($x     , $y, $x + $t, $y + $t, $style);
        $pdf->Line($x + $t, $y, $x     , $y + $t, $style);
    }

}