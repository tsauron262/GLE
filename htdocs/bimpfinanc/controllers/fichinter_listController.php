<?php

class fichinter_listController extends BimpController
{
    var $nomIdClient = "fk_soc";

    public function displayHead()
    {
        global $db, $langs, $user;
        $fk_soc = $_REQUEST[$this->nomIdClient];
        if($fk_soc > 0){
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $soc = new Societe($db);
            $soc->fetch($fk_soc);
            $head = societe_prepare_head($soc);
            dol_fiche_head($head, 'difi', $langs->trans("SAV"));


            $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

            dol_banner_tab($soc, $this->nomIdClient, $linkback, ($user->societe_id?0:1), 'rowid', 'nom', '', '');
        }
    }
}

