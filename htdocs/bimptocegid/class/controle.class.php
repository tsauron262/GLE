<?php

    class controle {
        
        private static $file;
        private static $lines;
        private static $type;
        private static $startChar_header                = 0;
        private static $numbChar_header                 = 14;
        private static $mustInChar_header               = '***S5CLIJRLETE';
        private static $startChar_alignementTiers       = 877;
        private static $numbChar_alignementTiers        = 2;
        private static $mustInChar_alignementTiers      = '--';
        private static $startChar_alignementFacture     = 385;
        private static $numbChar_alignementFacture      = 5;
        private static $mustInChar_alignementFacture    = '-CEET';
        private static $startChar_alignementPaiement    = 446;
        private static $numbChar_alignementPaiement     = 24;
        private static $mustInChar_alignementPaiement   = '010119000101190001011900';
//        private static $startChar_alignementPaiement    = 446;
//        private static $numbChar_alignementPaiement     = 24;
//        private static $mustInChar_alignementPaiement   = '010119000101190001011900';
        
        
        public static function tra($file, $lines, $type) { 
            self::$file  = basename($file);
            self::$lines = $lines;
            
            if(strpos(self::$file, '_(TIERS)_') > 0) self::$type = 'tiers';
            if(strpos(self::$file, '_(VENTES)_') > 0) self::$type = 'ventes';
            if(strpos(self::$file, '_(PAIEMENTS)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, '_(DEPLACEMENTPAIEMENTS)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, '_(PAYNI)_') > 0) self::$type = 'paiements';
            
            return Array('header' => self::controleHeader(), 'alignement' => self::controleAlignement());
            
        }
        
        private static function controleHeader() {
            
            if(substr(self::$lines[0], self::$startChar_header, self::$numbChar_header) != self::$mustInChar_header)
                return 'Erreur sur le header du fichier ' . self::$file . '<br /> la zone de controle retourne ' . substr(self::$lines[0], self::$startChar_header, self::$numbChar_header) . ' au lieu de ' . self::$mustInChar_header;
            
            return '';
            
        }
        
        private static function controleAlignement() {
            
            $errors = Array();

            switch(self::$type) {
                case 'tiers':
                    $start  = self::$startChar_alignementTiers;
                    $strlen = self::$numbChar_alignementTiers;
                    $equal  = self::$mustInChar_alignementTiers;
                    break;
                case 'ventes':
                    $start  = self::$startChar_alignementFacture;
                    $strlen = self::$numbChar_alignementFacture;
                    $equal  = self::$mustInChar_alignementFacture;
                    break;
                case 'paiements':
                    $start  = self::$startChar_alignementPaiement;
                    $strlen = self::$numbChar_alignementPaiement;
                    $equal  = self::$mustInChar_alignementPaiement;
                    break;
                default:
                    $start  = 0;
                    $strlen = 0;
                    $equal  = '';
                    break;
            }
            
            if($strlen > 0) {
                
                foreach(self::$lines as $index => $line) {
                    
                    if($index > 0) {
                        
                        if(substr($line, $start, $strlen) != $equal)
                            $errors[] = 'Ligne #' . ($index + 1) . ' - Erreur d\'alignement la zone de controle retourne ' . substr($line, $start, $strlen) . ' au lieu de ' . $equal;
                        
                    }
                    
                }
                
            }
            
            return $errors;
            
        } 
        
    }

