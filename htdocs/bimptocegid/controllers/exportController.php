<?php

class exportController extends BimpController {
    
    public static $can_exported = [
        "paiement", "societe", "facture", 'facture_fourn'
    ];
    
    public function renderHtml() {
        global $user;
//        if (!$user->admin && $user->id != 460) {
//            accessforbidden();
//        }
        
        
        
        if(isset($_REQUEST['element'])) {
            if(in_array($_REQUEST['element'], self::$can_exported)) {
                $export = BimpObject::getInstance('bimptocegid', 'BTC_export');
                $export->export($_REQUEST['element'], ($_REQUEST['origin']) ? $_REQUEST['origin'] : "web");
            } else {
                return BimpRender::renderAlerts("L'élément " . $_REQUEST['element'] . " ne peut pas être exporté", 'danger', false);
            }
            
        } else {
            $return = BimpRender::renderAlerts("Il n'y à pas d'élément transmit pour l'exportation", 'danger', false);
            $txt.= "<i>Pour plus d'informations sur les exports merci d'accèder à la page <a href='".DOL_URL_ROOT."/bimptocegid/'>d'accueil du module</a></i>";
            
            $return.= BimpRender::renderAlerts($txt, "warning", false);
            
            return $return;
        }
        
    }

}
