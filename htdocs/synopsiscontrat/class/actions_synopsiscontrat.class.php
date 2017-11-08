<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsisres/extractObjTypeId.php");

class Actionssynopsiscontrat {

    var $menuOk = false;
    

    function doActions($parameters, &$object, &$action, $hookmanager) {
        
    }


    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
        echo $object->statut;
        if($object->statut == 1)
//        echo '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$object->id.'&action=activerAll">Activer tous les services</a></div>';  
        echo '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/synopsiscontrat/request.php?id='.$object->id.'&action=activerAll">Activer tous les services</a></div>';  
    }

}
