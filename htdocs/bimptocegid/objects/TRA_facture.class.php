<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    class TRA_facture {
        
        protected $db;
        protected $compte_general;
        protected $compte_general_client;
        protected $sens_facture;
        public $rapport = [];
        
        function __construct($bimp_db) { $this->db = $bimp_db; }

        public function constructTra(Bimp_Facture $facture) {
            
            // Définition  de si la facture est ignoré en compta
            for ($i = 0; $i < count($facture->dol_object->lines); $i++) {
                if ($facture->dol_object->lines[$i]->desc == "Acompte" 
                        && $facture->dol_object->lines[$i]->multicurrency_total_ht == $facture->getData('total')) {
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
            $date_facture_source = new DateTime($fatcure_source->getData('datef'));
            $date_creation       = new dateTime($facture->getData('datec'));
            $date_echeance       = new DateTime($facture->getData('date_lim_reglement'));
            $id_reglement        = ($facture->getData('fk_mode_reglement') > 0) ? $facture->getData('fk_mode_reglement') : 6;
            $reglement           = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);
            $use_tva             = true;
            $use_d3e             = ($facture->getData('zone_vente') == 1) ? true : false;
            $TTC                 = $facture->getData('multicurrency_total_ttc');
            $controlle_ttc       = round($TTC, 2);
            
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
            $structure['COMPTE_GENERAL']        = sizing($this->compte_general, 17);
            $structure['TYPE_DE_COMPTE']        = sizing("X", 1);
            $structure['CODE_AUXILIAIRE']       = sizing($client->getData('code_compta'), 16);
            $structure['NEXT']                  = sizing("", 1);
            $structure['REF_INTERNE']           = sizing($facture->getData('facnumber'), 35);
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
            $structure['AXE']                   = sizing('A1',2);
            $structure['NUMRO_ECHEANCE']        = sizing("1", 2);
            $structure['REF_EXTERNE']           = sizing($facture->getData('facnumber'), 35);
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
            $structure['REF_LIBRE']             = sizing(suppr_accents($facture->getData('libelle')),35);
            $structure['TVA_ENCAISSEMENT']      = sizing("-",1);
            $structure['REGIME_TVA']            = sizing("CEE",3);
            $structure['TVA']                   = sizing("T",3);
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
                
                foreach($facture->dol_object->lines as $line) {
                    
                    // Définition du seens comptable de la ligne
                    if($this->sens_facture == "D") { //c'est une facture
                        $sens = ($line->multicurrency_total_ht > 0) ? "C" : "D";
                    }
                    if($this->sens_facture == "C") { //c'est un avoir
                        $sens = ($line->multicurrency_total_ht > 0) ? "D" : "C";
                    }
                    
                    if($line->multicurrency_total_ht != 0) {
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                        $current_montant = round($line->multicurrency_total_ht, 2);
                        if($use_d3e) {
                            $current_montant = round($line->multicurrency_total_ht, 2) - ($product->getData('deee') * $line->qty);
                            $total_deee += $product->getData('deee') * $line->qty;
                        }
                        $total_ht += round($current_montant, 2);
                        $this->compte_general = $product->getCodeComptableVente($facture->getData('zone_vente'), ($product->getData('type_compta') == 0) ? -1 : $product->getData('type_compta'));
                        $structure['SENS']                  = sizing(($line->multicurrency_total_ht > 0) ? "C" : "D",1);
                        $structure['COMPTE_GENERAL']        = sizing(sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true) , 17);
                        $structure['TYPE_DE_COMPTE']        = sizing("", 1);
                        $structure['CODE_AUXILIAIRE']       = sizing("", 16);
                        $structure['MONTANT']               = sizing(abs(round($current_montant,2)), 20, true);
                        $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                        $structure['REF_LIBRE']             = sizing($product->getRef(),35);
                        $ecriture .= implode('', $structure) . "\n";
                    }
                }
                if($use_d3e && $total_deee > 0) {
                    $this->compte_general = $product->getCodeComptableVenteDeee($facture->getData('zone_vente'));
                    $structure['COMPTE_GENERAL']        = sizing(sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true) , 17);
                    $structure['SENS']                  = sizing(($line->multicurrency_total_ht > 0) ? "C" : "D",1);
                    $structure['TYPE_DE_COMPTE']        = sizing("", 1);
                    $structure['CODE_AUXILIAIRE']       = sizing("", 16);
                    $structure['MONTANT']               = sizing(abs($total_deee), 20, true);
                    $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                    $structure['REF_LIBRE']             = sizing("DEEE",35);
                    $ecriture .= implode('', $structure) . "\n";
                } else {
                    // erreur de correspondance DEEE
                }
                if($facture->getData('zone_vente') == 1 || $facture->getData('zone_vente') == 2) {
                    $this->compte_general = $product->getCodeComptableVenteTva($facture->getData('zone_vente'));
                    $structure['COMPTE_GENERAL']        = sizing(sizing(interco_code($this->compte_general, $this->compte_general_client), 8, false, true) , 17);
                    $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                    $structure['MONTANT']               = sizing(abs(round($facture->getData('multicurrency_total_tva'), 2)), 20, true);
                    $structure['CONTRE_PARTIE']         = sizing($this->compte_general_client,17);
                    $structure['REF_LIBRE']             = sizing("TVA",35);
                    $ecriture .= implode('', $structure) . "\n";
                } else {
                    // erreur de correspondance TVA
                }
                
                $total_mis_en_ligne = round($total_deee + $total_tva + $total_ht, 2);
                
                if($total_mis_en_ligne == $controlle_ttc) {
                    $this->rapport['fails'][$facture->getRef()] = "Il y à un ecart de " . ($total_mis_en_ligne - $controlle_ttc) . '€';
                }
                
            } else {
                $facture->updateField('exported',102);
                $this->rapport['fails'][$facture->getRef()] = "La facture ne contient pas de ligne";
                return 0;
            }
            return $ecriture;
        }
        
    }