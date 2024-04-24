<?php

function sizing($texte, $nombre, $espaceAvant = false, $zero = false, $zeroAvant = false) {
    if(BimpCore::getConf('use_csv', 0, 'bimptocegid')){
        if(stripos($texte, '"') !== false){//attention deja des "
            if(stripos($texte, '"') === 0 && stripos($texte, '";') === (strlen($texte)-2)){
                $texte = substr($texte,1,-2);
            }
            else
                die('oups longeur '.strlen($texte). ' "; trouvé au '.stripos($texte, '";'));
        }

        if(is_int($texte))
            return $texte.';';
        else
            return '"'.$texte.'";';
    }
    
    
        $longeurText = strlen($texte);
        $avantTexte = "";
        $espacesRequis = $nombre - $longeurText;
        if ($espacesRequis > 0) {
            if ($zero) {
                if (!is_null($texte))
                    for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                        $texte .= "0";
                    } else
                    for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                        $texte .= " ";
                    }
            } elseif ($espaceAvant) {
                $avantTexte = "";
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $avantTexte .= " ";
                }
            } elseif ($zeroAvant) {
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $avantTexte .= "0";
                }
            } else {
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $texte .= " ";
                }
            }
        } elseif ($espacesRequis < 0) {
            $texte = substr($texte, 0, $nombre);
        }
        $texte = $avantTexte . $texte;
        return $texte;
    }

?>