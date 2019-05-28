<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/controllers/BimpCommController.php';

class factureController extends BimpCommController
{

    public function init()
    {
//        header("Location: " . DOL_URL_ROOT.'/compta/facture/card.php?id='.(int) BimpTools::getValue('id', 0));
//        exit;
        global $langs, $conf;

        $langs->load('bills');
        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');
        $langs->load("errors");
        $langs->load("stocks");

        if (!empty($conf->incoterm->enabled))
            $langs->load('incoterm');
        if (!empty($conf->margin->enabled))
            $langs->load('margins');
    }
}
