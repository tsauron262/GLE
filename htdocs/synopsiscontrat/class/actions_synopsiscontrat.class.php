<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsisres/extractObjTypeId.php");

class Actionssynopsiscontrat {

    var $menuOk = false;
    

    function doActions($parameters, &$object, &$action, $hookmanager) {
        
    }


    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
        if(get_class($object) == "Contrat" && $object->statut == 1){
            $afficher = false;
            foreach($object->lines as $ligne)
                if($ligne->statut != 1)
                    $afficher = true;
            if($afficher)    
            echo '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/synopsiscontrat/request.php?id='.$object->id.'&action=activerAll">Activer tous les services</a></div>';  
        }
    }

}
