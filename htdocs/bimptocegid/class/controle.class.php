<?php

    class controle {
        
        private static $file;
        private static $lines;
        private static $type;
        private static $justeLineNumber;
        private static $startChar_header                    = 0;
        private static $numbChar_header                     = 14;
        private static $mustInChar_header                   = '***S5CLIJRLETE';
        private static $startChar_alignementTiers           = 877;
        private static $numbChar_alignementTiers            = 2;
        private static $mustInChar_alignementTiers          = '--';
        private static $startChar_alignementFacture         = 385;
        private static $numbChar_alignementFacture          = 5;
        private static $mustInChar_alignementFacture        = '-CEET';
        private static $startChar_alignementPaiement        = 446;
        private static $numbChar_alignementPaiement         = 24;
        private static $mustInChar_alignementPaiement       = '010119000101190001011900';
        private static $startChar_alignementRIB             = 153;
        private static $numbChar_alignementRIB              = 6;
        private static $mustInChar_alignementRIB            = '001---';
        private static $startChar_alignementMANDAT          = 256;
        private static $numbChar_alignementMANDAT           = 3;
        private static $mustInChar_alignementMANDAT         = '1FI';

        
        public static function tra($file, $lines = '', $type = '', $justeLineNumber = false) { 
            
            self::$file  = basename($file);
            self::$lines = $lines;
            self::$justeLineNumber = $justeLineNumber;
            
            if(strpos(self::$file, '_(TIERS)_') > 0) self::$type = 'tiers';
            if(strpos(self::$file, '_(RIBS)_') > 0) self::$type = 'ribs';
            if(strpos(self::$file, '_(MANDATS)_') > 0) self::$type = 'mandats';
            if(strpos(self::$file, '_(VENTES)_') > 0) self::$type = 'ventes';
            if(strpos(self::$file, '_(ACHATS)_') > 0) self::$type = 'ventes';
            if(strpos(self::$file, '_(PAIEMENTS)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, '_(DEPLACEMENTPAIEMENTS)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, '_(PAYNI)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, '_(BORDEREAUXCHK)_') > 0) self::$type = 'paiements';
            if(strpos(self::$file, 'IP') === 0) self::$type = 'paiements';
            
            return Array('header' => self::controleHeader(), 'alignement' => self::controleAlignement(), 'balance' => self::controleBalanceFactures());
            
        }
        
        private static function controleHeader() {
            
            if(substr(self::$lines[0], self::$startChar_header, self::$numbChar_header) != self::$mustInChar_header) {
                if(self::$justeLineNumber)
                    return 1;
                else
                    return '<b>Ligne #1</b> : la zone de controle retourne " ' . substr(self::$lines[0], self::$startChar_header, self::$numbChar_header) . ' " au lieu de ' . self::$mustInChar_header;
            }
            
            return '';
            
        }
        
        private static function controleBalanceFactures() {
            $charControleLigneTTC = 30;
            $startTraCharAmout = 130;
            $charRefFacture = 48;
            $charControleSens = 129;
            
            $factures = Array();
            
            if(self::$type == 'ventes') {
                foreach(self::$lines as $index => $line) {
                    if($index > 0) {
                        
                        $ttcControle = 0;
                        $htControle  = 0;
                        $isTTC = (substr($line, $charControleLigneTTC, 1) == 'X') ? true : false;
                        if($isTTC){
                            $sens = substr($line, $charControleSens, 1);
                            $ttcControle = (float) (substr($line, $startTraCharAmout, 20));
                            $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['#'] = $index+1;
                            $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['TTC'] = $ttcControle;
                            
                        } else {
                            $htControle = (float) (substr($line, $startTraCharAmout, 20));
                            
                            if(substr($line, 0, 1) == 'A') {                                
                                if($sens == 'D') {
                                    if(substr($line, $charControleSens, 1) == 'D') {
                                        $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] -= abs($htControle);
                                    } else {
                                        $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] += abs($htControle);
                                    }
                                } else {
                                    if(substr($line, $charControleSens, 1) == 'D') {
                                        $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] += abs($htControle);
                                    } else {
                                        $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] -= abs($htControle);
                                    }
                                }
                            } else {
                                if(substr($line, $charRefFacture, 1) == 'A') {
                                    if(substr($line, $charControleSens, 1) == 'C') {
                                    $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] += abs($htControle);
                                } else {
                                    $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] -= abs($htControle);
                                }
                                }elseif(substr($line, $charControleSens, 1) == 'C') {
                                    $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] += $htControle;
                                } else {
                                    $factures[str_replace(' ', '', substr($line, $charRefFacture, 35))]['HT#' . ($index+1)] -= $htControle;
                                }
                            }
                        }
                    }
                }
                
//                if($fact) {
//                    echo '<pre>'; die(print_r($factures, 1));
//                }
                
                $return = Array();
                
                foreach($factures as $field => $infos) {
                    $controle = 0;
                    foreach($infos as $index => $info) {
                        if($index != '#' && $index != 'TTC') {
                            $controle += $info;
                        }
                    }
                    if(abs(round($infos['TTC'], 2)) - abs(round($controle,2)) != 0) {
                        $return[$field]['TTC'] = $infos['TTC'];
                        $return[$field]['CONTROLE'] = $controle;
                        $return[$field]['LINE'] = $infos['#'];
                    }
                }
           
            }
            
            return $return;
            
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
                case 'ribs':
                    $start  = self::$startChar_alignementRIB;
                    $strlen = self::$numbChar_alignementRIB;
                    $equal  = self::$mustInChar_alignementRIB;
                    break;
                case 'mandats':
                    $start  = self::$startChar_alignementMANDAT;
                    $strlen = self::$numbChar_alignementMANDAT;
                    $equal  = self::$mustInChar_alignementMANDAT;
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
                        
                            if(substr($line, $start, $strlen) != $equal){
                                if(self::$justeLineNumber) {
                                    $errors[] = ($index + 1);
                                } else {
                                    $errors[] = '<b>Ligne #' . ($index + 1) . '</b>';
                                }
                            }

                    }
                    
                }
                
            }
            
            return $errors;
            
        } 
        
    }

