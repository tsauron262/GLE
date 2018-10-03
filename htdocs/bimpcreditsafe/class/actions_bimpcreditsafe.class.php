<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of action_synopsisapple
 *
 * @author tijean
 */
class ActionsBimpcreditsafe {
    
    
    function formObjectOptions($parameters, &$object, &$action, $hookmanager){
        global $langs;
        if($object->element == "societe")
		echo '<script>'. file_get_contents(DOL_DOCUMENT_ROOT."/bimpcreditsafe/js/script.js").'</script>';
    }
}
