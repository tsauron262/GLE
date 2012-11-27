<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 5 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : SAV.functions.php
  * GLE-1.1
  */


function sav_prepare_head($objsoc)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Fiche");
    $head[$h][2] = 'index';
    $h++;

    if ($objsoc->statut == 0 || $objsoc->statut == 100)
    {
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/SAV/stock.php?id='.$objsoc->id;
        $head[$h][1] = $langs->trans("Stock");
        $head[$h][2] = 'stock';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/index.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Retour");
    $head[$h][2] = 'retour';
    $head[$h][3] = 'right';
    $h++;

    return($head);
}

?>
