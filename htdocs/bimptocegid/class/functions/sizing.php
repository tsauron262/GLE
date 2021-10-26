<?php

function sizing($texte, $nombre, $espaceAvant = false, $zero = false, $zeroAvant = false) {
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