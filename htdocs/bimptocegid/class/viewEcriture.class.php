<?php
    
require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_factureFournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_tiers.class.php';

    class viewEcriture {
        
        static private $bimpObject;
        
        static function setCurrentObject($object) {
            self::$bimpObject = $object;
        }
        
        static function display() {
            if(!is_object(self::$bimpObject))
                return BimpRender::renderAlerts('Un bimpObject doit être transmit','danger', false);
            
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', self::$bimpObject->getData('fk_soc'));
            
            $html = '<h3>' . $client->getName() . '</h3>';
            if($client->getData('code_compta')) {
                $html .= 'Code auxiliare CLI: ' . $client->getData('code_compta');
            }
            if($client->getData('code_compta_fournisseur')) {
                if($client->getData('code_compta'))
                    $html .= '<br />';
                $html .= 'Code auxiliare FOU: ' . $client->getData('code_compta_fournisseur');
            }
            $html .= '<br />';
            if(self::$bimpObject->getData('exported')) {
                $html .= 'Cette pièce est exportée en compta';
            } else {
                $html .= 'Cette pièce n\'est pas exportée en compta';
            }

            $field = null;
            switch(self::$bimpObject->object_name) {
                case 'Bimp_Facture':
                    $function   = 'getEcriturePieceClient';
                    $field      = 'code_compta'; 
                    break;
                case 'Bimp_FactureFourn':
                    $function   = 'getEcriturePieceClient';
                    $field      = 'code_compta_fournisseur'; 
                    break;
                case 'Bimp_SocBankAccount':
                    $function   = 'getEcritureRib';
                    break;
            }

            $html .= '<br /><br />' . '<pre><b class=\'danger\'>Facture</b><br />' . self::$function() . '<br /><br />';
            
            if($field && $client->getData($field)) {
                
                $html .= '<b class=\'danger\'>Tiers</b><br />' . self::getEcritureTiers($client, $field) . '<br /><br />';
                
            } 
            
            $html .= '</pre>';
            
            return $html;
            
        }
        
        static function getEcritureTiers($tiers, $field) {
            $tra = new TRA_tiers(self::$bimpObject->db, '');
            $tra->tier      = $tiers;
            $tra->justView  = true;
            return $tra->constructTra($field);
        }
        
        static function getEcritureRib(){
            $ribANDmandat = BimpCache::getBimpObjectInstance('bimptocegid', "BTC_exportRibAndMandat");
            $societe = self::$bimpObject->getParentInstance();
            $html = $ribANDmandat->printRIBtra(self::$bimpObject, $societe);
            $html .= '<br/><br/>';
            $html .= $ribANDmandat->printMANDATtra(self::$bimpObject, $societe);
            return $html;
        }
        
        static function getEcriturePieceClient() {
            switch(self::$bimpObject->object_name) {
                case 'Bimp_Facture':
                    $tra = new TRA_facture(self::$bimpObject->db, '');
                    break;
                case 'Bimp_FactureFourn':
                    $tra = new TRA_factureFournisseur(self::$bimpObject->db, '');
                    break;
                default: return BimpRender::renderAlerts('Erreur lors du chargement de l\'écriture TRA', 'danger', false); break;
            }

            $tra->justView  = true;
            return $tra->constructTra(self::$bimpObject, false);
        }
        
    }