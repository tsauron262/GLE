<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_tiers.class.php';
    
    class TRA_facture {
        
        protected $db;
        protected $compte_general;
        protected $compte_general_client;
        protected $sens_facture;
        public $rapport = [];
        public $rapportTier = [];
        protected $TRA_tiers;
        protected $debug;
        
        function __construct($bimp_db, $tiers_file, $debug = false) { 
            $this->db = $bimp_db; 
            $this->TRA_tiers = new TRA_tiers($bimp_db, $tiers_file);
            $this->debug = $debug;
        }

        public function constructTra(Bimp_Facture $facture, $createTiers = true) {
                        
            for ($i = 0; $i < count($facture->dol_object->lines); $i++) {
                if ($facture->dol_object->lines[$i]->desc == "Acompte" 
                        && $facture->dol_object->lines[$i]->multicurrency_total_ht == $facture->getData('total_ht')) {
                    $this->rapport['IGNORE'][$facture->getRef()] = "Facture d'accompte";
                    $facture->updateField('ignore_compta', 1);
                    $facture->updateField('exported',204);
                    return 0;
                }
            }
            
            $ecriture = "";

            $client              = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $facture->getData('fk_soc'));
            $fatcure_source      = $facture->getChildObject('facture_source');
            $is_client_interco   = false;
            $date_facture        = new DateTime($facture->getData('datef'));
            $date_facture_source = new DateTime($fatcure_source->getData('datef')); // Soit la date de la facture source soit la date du jour.
            $date_creation       = new dateTime($facture->getData('datec'));
            $date_echeance       = new DateTime($facture->getData('date_lim_reglement'));
            $id_reglement        = ($facture->getData('fk_mode_reglement') > 0) ? $facture->getData('fk_mode_reglement') : 6;
            $reglement           = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);
            $use_tva             = true;
            $use_d3e             = ($facture->getData('zone_vente') == 1) ? true : false;
            $TTC                 = $facture->getData('multicurrency_total_ttc');
            $controlle_ttc       = round($TTC, 2);            
            $code_compta         = $this->TRA_tiers->getCodeComptable($client, 'code_compta', $createTiers);
            
            if ($client->getData('is_subsidiary')) {
                $this->compte_general = $client->getData('accounting_account');
                $is_client_interco = true;
            }else {
                $this->compte_general  = '41100000';
            }
            
            $this->compte_general_client  = $this->compte_general;
            $this->sens_facture = ($TTC > 0) ? "D" : "C";
            
            //Structure du fichier TRA (Ligne client)
            $structure = Array();
            $structure['JOURNAL']               = sizing(code_journal($facture->getData('ef_type'), "V", $is_client_interco), 3);
            $structure['DATE']                  = sizing($date_facture->format('dmY'), 8);
            $structure['TYPE_PIECE']            = sizing("FC", 2);
            $structure['COMPTE_GENERAL']        = sizing(sizing($this->compte_general, 8, false, true) , 17);
            $structure['TYPE_DE_COMPTE']        = sizing("X", 1);
            $structure['CODE_AUXILIAIRE']       = sizing($code_compta, 16);
            $structure['NEXT']                  = sizing("", 1);
            $structure['REF_INTERNE']           = sizing($facture->getData('ref'), 35);
            $structure['LABEL']                 = sizing(strtoupper(suppr_accents($client->getData('nom'))), 35);
            $structure['REGLEMENT']             = sizing(($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3);
            $structure['ECHEANCE']              = sizing($date_echeance->format('dmY'), 8);
            $structure['SENS']                  = sizing($this->sens_facture,1);
            $structure['MONTANT']               = sizing(abs(round($TTC, 2)), 20, true);
            $structure['TYPE_ECRITURE']         = sizing("N", 1);
            $structure['NUMERO_PIECE']          = sizing($facture->id, 8, true);
            $structure['DEVISE']                = sizing('EUR', 3);
            $structure['TAUX_DEV']              = sizing('1', 10);
            $structure['CODE_MONTANT']          = sizing('E--', 3);
            $structure['MONTANT_2']             = sizing("", 20);
            $structure['MONTANT_3']             = sizing("", 20);
            $structure['ETABLISSEMENT']         = sizing('001', 3);
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                $structure['AXE']                   = sizing('A1',2);
                $structure['NUMRO_ECHEANCE']        = sizing("1", 2);
            }
            else{
                $structure['AXE']                   = sizing('',2);
                $structure['NUMRO_ECHEANCE']        = sizing("", 2);
            }
            $structure['REF_EXTERNE']           = sizing($facture->getData('ref'), 35);
            $structure['DATE_REF_EXTERNE']      = sizing($date_facture_source->format('dmY'), 8);
            $structure['DATE_CREATION']         = sizing($date_creation->format('dmY'), 8);
            $structure['SOCIETE']               = sizing("",3);
            $structure['AFFAIRE']               = sizing("",17);
            $structure['DATE_TAUX_DEV']         = sizing("01011900",8);
            $structure['NOUVEAU_ECRAN']         = sizing("N",3);
            $structure['QUANTITE_1']            = sizing("",20);
            $structure['QUANTITE_2']            = sizing("",20);
            $structure['QUANTITE_QUALIF_1']     = sizing("",3);
            $structure['QUANTITE_QUALIF_2']     = sizing("",3);
            
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid'))
                $structure['REF_LIBRE']             = sizing(suppr_accents($facture->getData('libelle')),35);
            else
                $structure['REF_LIBRE']             = sizing('',35);
            
            $structure['TVA_ENCAISSEMENT']      = sizing("-",1);
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                $structure['REGIME_TVA']            = sizing("CEE",3);
                $structure['TVA']                   = sizing("T",3);
            }
            else{
                $structure['REGIME_TVA']            = sizing("",3);
                $structure['TVA']                   = sizing("",3);
            }
            $structure['TPF']                   = sizing("",3);
            $structure['CONTRE_PARTIE']         = sizing("",17);
            $structure['VIDE']                  = sizing("",606);
            $structure['LETTRAGE_DEV']          = sizing("-",1);
            $structure['LETTRAGE_EURO']         = sizing("X",1);
            $structure['ETAT_LETTRAGE']         = sizing("AL",2);
            $structure['VIDE_2']                = sizing("",153);
            $structure['VALIDE']                = sizing("-",1);
            $structure['BEFORE']                = sizing("",1);
            $structure['DATE_DEBUT']            = sizing("",8);
            $structure['DATE_FIN']              = sizing("",8);
            
            $ecriture .= implode('', $structure) . "\n";
            
            $total_des_lignes = 0;
            $total_tva = round($facture->getData('multicurrency_total_tva'),2);
            $total_ht = 0;
            $total_deee = 0;
            
            if(count($facture->dol_object->lines)) {
                $count_lines = count($facture->dol_object->lines);
                $compte_le_plus_grand = '';
                $montant_le_plus_grand = 0;
                
                foreach($facture->dol_object->lines as $line) {
                    
                    // Définition du seens comptable de la ligne
                    if($this->sens_facture == "D") { //c'est une facture
                        $sens = ($line->multicurrency_total_ht > 0) ? "C" : "D";
                    }
                    if($this->sens_facture == "C") { //c'est un avoir
                        $sens = ($line->multicurrency_total_ht > 0) ? "D" : "C";
                    }
                    if(!$line->fk_product && method_exists($facture, 'getProdWithFactureType'))
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $facture->getProdWithFactureType($line));    
                    else
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                    
                    if($line->multicurrency_total_ht != 0) {
                        $debug['ZONE_VENTE_' . $line->id] = $facture->getData('zone_vente');
                        $current_montant = round($line->multicurrency_total_ht, 2);
                        if($use_d3e) {
                            $current_montant = round($line->multicurrency_total_ht, 2) - ($product->getData('deee') * $line->qty);
                            $total_deee += $product->getData('deee') * $line->qty;
                        }
                        $total_ht += round($current_montant, 2);
                        
                        if($product->isLoaded()) {
                            $this->compte_general = $product->getCodeComptableVente($facture->getData('zone_vente'));
                            $debug['LOADED_PRODUCT_' . $line->id] = $product->getRef();
                        }
                        else {
//                            $this->compte_general = '70600000';
                            $this->compte_general = $product->getCodeComptableVente($facture->getData('zone_vente'), $line->product_type, ($line->tva_tx == 0)? 1 : 0);//.$line->product_type;
                            $debug['LOADED_PRODUCT_' . $line->id] = 'NULL';
                        }
                        
                            
                        
                        $debug['CHOIX_COMPTE_' . $line->id] = $line->id . ' => ' . $this->compte_general;
                        $debug['ID_PRODUCT_' . $line->id] = $line->id . ' => ' . $line->fk_product;
                        
                        $structure['SENS']                  = sizing($this->getSens($line->multicurrency_total_ht),1, true);
                        $structure['COMPTE_GENERAL']        = sizing(sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true) , 17);
                        $structure['TYPE_DE_COMPTE']        = sizing("", 1);
                        $structure['CODE_AUXILIAIRE']       = sizing("", 16);
                        $structure['MONTANT']               = sizing(abs(round($current_montant,2)), 20, true);
                        if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                            $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                            $structure['REF_LIBRE']             = sizing(($product->isLoaded()) ? $product->getRef() : 'Ligne ' . $line->id,35);
                        }
                        $ecriture .= implode('', $structure) . "\n";
                        
                        if(abs($current_montant) > abs($montant_le_plus_grand)) {
                            $montant_le_plus_grand = abs($current_montant);
                            $compte_le_plus_grand = sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true);
                        }
                        
                    }
                }
                
                if($use_d3e && $total_deee != 0) {
                    $this->compte_general = $product->getCodeComptableVenteDeee($facture->getData('zone_vente'));
                    $structure['COMPTE_GENERAL']        = sizing(sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true) , 17);
                    $structure['SENS']                  = sizing($this->getSens($total_deee),1);
                    $structure['TYPE_DE_COMPTE']        = sizing("", 1);
                    $structure['CODE_AUXILIAIRE']       = sizing("", 16);
                    $structure['MONTANT']               = sizing(abs($total_deee), 20, true);
                    if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                        $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                        $structure['REF_LIBRE']             = sizing("DEEE",35);
                    }
                    $ecriture .= implode('', $structure);
                    if(abs($total_tva) > 0) $ecriture .= "\n";
                    
                }
                
                if($facture->getData('zone_vente') == 1 || $facture->getData('zone_vente') == 2) {
                    if($product->isLoaded())
                        $this->compte_general = $product->getCodeComptableVenteTva($facture->getData('zone_vente'));
                    else
                        $this->compte_general = '44571000';
                    $structure['COMPTE_GENERAL']        = sizing($this->compte_general , 17);
                    $structure['SENS']                  = sizing($this->getSens($total_tva),1);
                    $structure['MONTANT']               = sizing(abs(round($facture->getData('multicurrency_total_tva'), 2)), 20, true);
                    if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                        $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                        $structure['REF_LIBRE']             = sizing("TVA",35);
                    }
                    $ecriture .= implode('', $structure) . "\n";
                }
                
                                
                $total_mis_en_ligne =  (round($total_deee,2) + round($total_tva, 2) + round($total_ht, 2));
                $controlle_ttc = (round($TTC, 2));
                $reste = round($controlle_ttc - $total_mis_en_ligne,2);
                
                if($reste != 0) {
                    $structure['COMPTE_GENERAL']        = sizing($compte_le_plus_grand, 17);
                    $structure['SENS']                  = sizing($this->getSens($reste),1);
                    $structure['MONTANT']               = sizing(abs($reste), 20, true);
                    if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                        $structure['REF_LIBRE']             = sizing($facture->getRef(),35);
                    }
                    $ecriture .= implode('', $structure) . "\n";
                }
                
            } else {
                $facture->updateField('exported',102);
                return 0;
            }
            
            $this->rapportTier = $this->TRA_tiers->rapport;
            
            if(!$this->debug)
                return $ecriture;
            
            $return = $ecriture;
            $return.= '<br />' . print_r($debug, 1);
            
            return $return;
            
        }
        
        private function getSens($montant) {            
            
            return ($montant > 0) ? 'C' : 'D';
   
        }
        
    }