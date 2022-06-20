<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_tiers.class.php';
    
    class TRA_factureFournisseur{
        
        protected $db;
        protected $compte_general;
        public $rapport = [];
        private $caisse;
        private $TRA_tiers;
        public $rapportTier;
        private $sensFacture;
        private $zoneAchat = ['france' => 1,'UE' => 2, 'HorsUE' => 3];
        
        public static $rfa = Array('GEN-CRT', 'GEN-RFA', 'GEN-IPH', 'REMISE', 'GEN-RETROCESSION', 'GEN-AVOIR', 'GEN-AVOIR-6097000', 'GEN-PUB', 'GEN-INCENTIVE', 'GEN-PROTECTPRIX', 'GEN-REBATE', 'GEN-AVOIR-PRESTATION', 'GEN-DEMO');
        
        function __construct($bimp_db, $tiers_file) { 
            $this->db = $bimp_db; 
            $this->TRA_tiers = new TRA_tiers($bimp_db, $tiers_file);
        }
        
        public function constructTra(Bimp_FactureFourn $facture) {
            
            $fournisseur            = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));
            $interco                = ($fournisseur->getData('is_subsidiary')) ? true : false;
            $this->compte_general   = ($fournisseur->getData('is_subsidiary')) ? $fournisseur->getData('accounting_account_fournisseur') : '40100000';
            $code_compta            = $this->TRA_tiers->getCodeComptable($fournisseur, 'code_compta_fournisseur');
            $use_autoliquidation    = ($facture->getData('zone_vente') == 2 || $facture->getData('zone_vente') == 4) ? true : false;
            $datec                  = new DateTime($facture->getData('datec'));
            $reglement              = $this->db->getRow('c_paiement', 'id = ' . $facture->getData('fk_mode_reglement'));
            $TTC                    = $facture->getData('total_ttc');
            $this->sensFacture      = ($TTC > 0) ? "C" : "D";
            
            
            if ($facture->getData('date_lim_reglement')) {
                $date_echeance = new DateTime($facture->getData('date_lim_reglement'));
            } else {
                $cond = $facture->getData('fk_cond_reglement');
                $date_echeance = new DateTime($facture->getData('datef'));
                if ($cond == 48) {
                    $date_echeance->add(new DateInterval("P60D"));
                } elseif ($conf == 52) {
                    $date_echeance->add(new DateInterval("P30D"));
                } elseif ($cond == 7) {
                    $date_echeance->add(new DateInterval("P45D"));
                }
                $date_echeance->add(new DateInterval("P1D"));
            }
            
            $structure = array();
            $structure['JOURNAL']                   = sizing(code_journal($facture->getData('ef_type'), 'A', $interco), 3);
            $structure['DATE']                      = sizing($datec->format('dmY'), 8);
            $structure['TYPE_PIECE']                = sizing($this->getTypePiece($facture->getData('type')), 2);
            $structure['COMPTE_GENERAL']            = sizing($this->compte_general, 17);
            $structure['TYPE_DE_COMPTE']            = sizing('X', 1);
            $structure['CODE_COMPTA']               = sizing($code_compta, 16);
            $structure['NEXT']                      = sizing('', 1);
            $structure['REF_INTERNE']               = sizing($facture->getref(), 35);
            $structure['LEBAL']                     = sizing(strtoupper(suppr_accents($fournisseur->getData('nom'))), 35);
            $structure['REGLEMENT']                 = sizing(($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3);
            $structure['ECHEANCE']                  = sizing($date_echeance->format('dmY'), 8);
            $structure['SENS']                      = sizing($this->sensFacture, 1);
            $structure['MONTANT']                   = sizing(abs(round($TTC, 2)), 20, true);
            $structure['TYPE_ECRITURE']             = sizing('N', 1);
            $structure['NUMERO_PIECE']              = sizing($facture->id, 8, true);
            $structure['DEVISE']                    = sizing('EUR', 3);
            $structure['TAUX_DEV']                  = sizing('1', 10);
            $structure['TAUX_DEV']                  = sizing('1', 10);
            $structure['CODE_MONTANT']              = sizing('E--', 3);
            $structure['MONTANT_2']                 = sizing("", 20);
            $structure['MONTANT_3']                 = sizing("", 20);
            $structure['ETABLISSEMENT']             = sizing('001', 3);
            $structure['AXE']                       = sizing('A1',2);
            $structure['NUMRO_ECHEANCE']            = sizing("1", 2);
            $structure['REF_EXTERNE']               = sizing($facture->getData('ref_supplier'), 35);
            $structure['DATE_REF_EXTERNE']          = sizing('01011900', 8);
            $structure['DATE_CREATION']             = sizing($datec->format('dmY'), 8);
            $structure['SOCIETE']                   = sizing("",3);
            $structure['AFFAIRE']                   = sizing("",17);
            $structure['DATE_TAUX_DEV']             = sizing("01011900",8);
            $structure['NOUVEAU_ECRAN']             = sizing("N",3);
            $structure['QUANTITE_1']                = sizing("",20);
            $structure['QUANTITE_2']                = sizing("",20);
            $structure['QUANTITE_QUALIF_1']         = sizing("",3);
            $structure['QUANTITE_QUALIF_2']         = sizing("",3);
            $structure['REF_LIBRE']                 = sizing(suppr_accents($facture->getRef()),35);
            $structure['TVA_ENCAISSEMENT']          = sizing("-",1);
            $structure['REGIME_TVA']                = sizing("CEE",3);
            $structure['TVA']                       = sizing("T",3);
            $structure['TPF']                       = sizing("",3);
            $structure['CONTRE_PARTIE']             = sizing("",17);
            $structure['VIDE']                      = sizing("",606);
            $structure['LETTRAGE_DEV']              = sizing("-",1);
            $structure['LETTRAGE_EURO']             = sizing("X",1);
            $structure['ETAT_LETTRAGE']             = sizing("AL",2);
            $structure['VIDE_2']                    = sizing("",153);
            $structure['VALIDE']                    = sizing("-",1);
            $structure['BEFORE']                    = sizing("",1);
            $structure['DATE_DEBUT']                = sizing("",8);
            $structure['DATE_FIN']                  = sizing("",8);
            
            $ecriture = implode('', $structure) . "\n";
            
            if(count($facture->dol_object->lines)) {
                $total_tva = 0;
                $total_d3e = 0;
                foreach($facture->dol_object->lines as $line) {
                    
                    if($line->total_ht != 0) {
                        $produit = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                        $total_tva += $line->tva;
                        $total_d3e += $produit->getData('deee') * $line->qty;
                        $sens = ($this->sensFacture == 'C') ? ($line->total_ht > 0) ? 'D' : 'C' : ($TTC < 0) ? 'C' : 'D';
                        
                        if($this->sensFacture == 'C') {
                            if($line->total_ht > 0) {
                                $sens = 'D';
                            } else {
                                $sens = 'C';
                            }
                        } else {
                            if($line->total_ht > 0) {
                                $sens = 'C';
                            } else {
                                $sens = 'D';
                            }
                        }
                        
                        if($fournisseur->getData('code_compta_fournisseur') == BimpCore::getConf('code_fournisseur_apple', null, "bimptocegid")) {
                            $compteLigne = BimpCore::getConf('code_fournisseur_apple');
                        }elseif(in_array($produit->getRef(), self::$rfa)) {
                            $compteLigne = Bimpcore::getConf('rfa_fournisseur_fr', null, 'bimptocegid');
                        } else {
                            $compteLigne = $produit->getCodeComptableAchat($facture->getData('zone_vente'));
                        }
                        
                        $structure['REF_LIBRE']                 = sizing(suppr_accents($produit->getRef()),35);
                        $structure['COMPTE_GENERAL']            = sizing($compteLigne, 17);
                        $structure['TYPE_DE_COMPTE']            = sizing('', 1);
                        $structure['CODE_COMPTA']               = sizing("", 16);
                        $structure['SENS']                      = sizing($sens, 1);
                        $structure['MONTANT']                   = sizing(abs(round($line->total_ht - ($produit->getData('deee') * $line->qty), 2)), 20, true);
                        
                        $ecriture .= implode('', $structure) . "\n";

                    }

                }
                
                if($facture->getData('zone_vente') == $this->zoneAchat['france']) {
                    if($total_d3e > 0) {
                        $structure['REF_LIBRE']                 = sizing('DEEE',35);
                        $structure['COMPTE_GENERAL']            = sizing(Bimpcore::getConf('achat_dee_fr', null, 'bimptocegid'), 17);
                        $structure['MONTANT']                   = sizing(abs(round($total_d3e, 2)), 20, true);
                        $ecriture .= implode('', $structure) . "\n";
                    }
                    if($total_tva > 0) {
                        $structure['REF_LIBRE']                 = sizing('TVA',35);
                        $structure['COMPTE_GENERAL']            = sizing(Bimpcore::getConf('achat_tva_fr', null, 'bimptocegid'), 17);
                        $structure['MONTANT']                   = sizing(abs(round($total_tva, 2)), 20, true);
                        $ecriture .= implode('', $structure) . "\n";
                    }
                    
                }
                
            }
            
            return $ecriture;
            
        }
        
        private function getTypePiece($type) {
            switch($type) {
                case 2: return 'AF'; break;
                case 3: return 'OF'; break;
                default: return 'FF'; break;
            }
        }
        
    }