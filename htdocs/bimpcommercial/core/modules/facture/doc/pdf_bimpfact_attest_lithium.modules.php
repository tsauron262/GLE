<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class pdf_bimpfact_attest_lithium extends CommonDocGenerator {

    public $errors = array();
    public $warnings = array();
    
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

        $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/bimpcommercial/core/modules/facture/doc/' . $modele . '.pdf');
        $tplidx1 = $pdf->importPage(1, "/MediaBox");
        $pdf->useTemplate($tplidx1, 0, 0, 0, 0, true);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->setColor('text', 255, 36, 36);

        // Data settings
        
        $i = 0 ;
        $max_row = 5;
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
        // Obtention des lignes de la facture
        $fact_lines = $facture->getChildrenObjects('lines');

        // Commandes fournisseur avec équipement
        $cfs_eq = array();
        // Boucle sur toutes les lignes de facture AVEC équipement
        foreach($fact_lines as $fact_line) {
            if ((int) $fact_line->id_product) {
                $product = $fact_line->getProduct();

                if(!$product->isTypeService()) {
                    if (BimpObject::objectLoaded($product)) {
                        if ($product->isSerialisable()) {
                            $fact_lines_equipment = $fact_line->getEquipmentLines();
                            if(count($fact_lines_equipment)) {
                                $display_eq = '';
                                foreach($fact_lines_equipment as $line_equipment) {
                                    $origine_trouvee = 0;
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $line_equipment->getData('id_equipment'));
                                    $display_eq .= $equipment->getNomUrl();
                                    if(BimpObject::objectLoaded($equipment)) {
                                        $places_cf = $equipment->getChildrenObjects('places', array('origin' => 'order_supplier'));
                                        if(!empty($places_cf)) {
                                            foreach($places_cf as $place_cf){
                                                if(0 < (int) $place_cf->getData('id_origin')) {
                                                    $cfs_eq[$place_cf->getData('id_origin')] = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $place_cf->getData('id_origin'));
                                                    $origine_trouvee = 1;
                                                } else {
                                                    $this->errors[] = 'L\'emplacement de la CF de ' . $equipment->getNomUrl() . ' est introuvable.';
                                                }
                                            }
                                        } else {
                                            $this->errors[] = 'L\'équipement ' . $equipment->getNomUrl() . ' n\'a pas de commande fournisseur associé';
                                        }
                                    } else {
                                       $this->errors[] = 'Équipement inconnu ' . $line_equipment->getData('id_equipment');
                                    }
                                    if(!$origine_trouvee)
                                        $this->errors[] = 'ID de la commande fournisseur associé à ' . $equipment->getNomUrl() . ' inconnu.';
                                }
                            } else {
                                $this->errors[] = 'La ligne de facture ' . $product->getNomUrl() . ' ne contient aucun équipement';
                            }
                        }
                    } else {
                        $this->errors[] = 'ID du produit inconnu.';
                    }
                }
            }
        }
        
        // Obtention de toutes les lignes des commandes fournisseur associé à la commande de cette facture
        $line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
        $lines_list = $line_instance->getList(array(
            'id_obj' => (int) $commande->id
                ), null, null, 'id', 'asc', 'array', array('id'));
        $lines = array();

        foreach ($lines_list as $item) {
            $lines[] = (int) $item['id'];
        }

        $fourn_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
        $all_lines_list = $fourn_line_instance->getList(array(
            'linked_object_name' => 'commande_line',
            'linked_id_object'   => array(
                'in' => $lines
            )
                ), null, null, 'id', 'asc', 'array', array('id'));
        $all_fourn_lines = array();
        foreach($all_lines_list as $line) 
            $all_fourn_lines[] = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $line['id']);
        
        // Commandes fournisseur avec équipement
        $cfs_prod = array();
        // Boucle sur toutes les lignes de facture SANS équipement
        foreach($fact_lines as $fact_line) {
            $origine_trouvee = 0;
            if ((int) $fact_line->id_product) {
                $product = $fact_line->getProduct();
                    if(!$product->isTypeService()) {

                    if (BimpObject::objectLoaded($product)) {
                        if (!$product->isSerialisable()) {

                            // Recherche si une ligne de la commande fournisseur contient le produit de la ligne de facture
                            foreach($cfs_eq as $cf) {
                                $cf_lines = $cf->getChildrenObjects('lines');
                                foreach($cf_lines as $cf_line) {
                                    if((int) $cf_line->getProduct()->id == (int) $product->id)
                                        $origine_trouvee = 1;
                                }
                            }

                            // On n'a pas trouvé de commandes fournisseur avec ce produit dans les commandes fournisseur avec équipement
                            if(!$origine_trouvee){
                                foreach($all_fourn_lines as $cf_line) {
                                    if(0 < (int) $cf_line->getProduct()->id and (int) $cf_line->getProduct()->id == (int) $product->id) {
                                        $cf = $cf_line->getParentInstance();
                                        $cfs_prod[$cf->getData('id')] = $cf;
                                        $origine_trouvee = 1;
                                    }
                                }
                            }
                            if(!$origine_trouvee)
                                $this->errors[] = 'Origine du produit ' . $product->getNomUrl() . ' inconnue';
                        }
                    } else {
                        $this->errors[] = 'ID du produit inconnu.';
                    }
                }
            }
        }
        
        $cfs = array();
        foreach ($cfs_eq as $id_cf => $cf) {
            if(!isset($cfs[$id_cf]))
                $cfs[$id_cf] = $cf;
        }

        foreach ($cfs_prod as $id_cf => $cf) {
            if(!isset($cfs[$id_cf]))
                $cfs[$id_cf] = $cf;
        }

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