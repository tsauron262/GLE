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
class ActionsSynopsisapple {
    
    function doActions($parameters, &$object, &$action, $hookmanager) {
    }
    
    function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
        global $langs;
	$hookmanager->resArray['searchintosn']=array('text'=>img_object("Chrono", "chrono@synopsischrono") . $langs->trans("S/N"), 'url'=>DOL_URL_ROOT.'/synopsisapple/list.php?mainmenu=Process&filtre='.GETPOST('q'));
        return 0;
    }
}
