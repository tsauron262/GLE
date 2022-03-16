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

            $structure = array();
            $structure['JOURNAL']                   = sizing(code_journal($facture->getData('ef_type'), 'A', $interco), 3);
            $structure['DATE']                      = sizing($datec->format('dmY'), 8);
            $structure['TYPE_PIECE']                = sizing($this->getTypePiece($facture->getData('type')), 2);
            $structure['COMPTE_GENERAL']            = sizing($this->compte_general, 17);
            $structure['TYPE_DE_COMPTE']            = sizing('X', 1);
            $structure['CODE_COMPTA']               = sizing($code_compta, 16);
            $structure['NEXT']                      = sizing('', 1);
            $structure['REF_INTERNE']               = sizing($facture->getref(), 35);
            
            $ecriture = implode('', $structure) . "\n";
            
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