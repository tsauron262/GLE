<?php

class TRA extends BimpObject {
    
    public function displayTraFile($file) {
        $html = '';
        
        $html .= '<pre><br />';
        
        $lines = $this->getLinesOfFile($file);
        
        foreach($lines as $index => $line) {
            $html .= $index+1 . '.' . "\t" . $line;
        }
        
        $html .= '<br /></pre>';
        
        return $html;
    }
    
    private function getLinesOfFile($file): array {
        return file($file);
    }
    
    public function getTiersFromfile($file, $type) {
        
        $html = '';
        
        $instance       = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
        $traiteTiers    = Array();
        
        switch($type) {
            case 'tiers': $startCodeAux = 6; break;
        }
        
        $lines = $this->getLinesOfFile($file);
        if(count($lines) > 1) {
            foreach($lines as $index => $line) {
                if($index > 0) {
                    $aux = str_replace(' ', '', substr($line, $startCodeAux, 17));

                    if(!in_array($aux, $traiteTiers)) {
                        $traiteTiers[] = $aux;
                        if($instance->find(['code_compta' => $aux], 1) || $instance->find(['code_compta_fournisseur' => $aux], 1)) {
                            $card = new BC_Card($instance);
                            $html .= BimpRender::renderPanel('<b>' . $instance->getName() . '</b>' . ' ' . $aux, $card->renderHtml(), '', Array('open' => 0));
                        } else {
                            $html .= BimpRender::renderAlerts('Impossible de charger le tiers avec le code auxiliaire ' . $aux, 'danger', false);
                        }
                    }
                }
            }
        } else {
            $html = BimpRender::renderAlerts('Le fichier ' . basename($file) . ' ne comporte pas d\'écritures', 'warning', false);
        }
        
        
        return $html;
        
    }
    
}