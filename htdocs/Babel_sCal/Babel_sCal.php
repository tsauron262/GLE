<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
 include_once('pre.inc.php');

//print DOL_DOCUMENT_ROOT."/Babel_sCal/Babel_scal.class.php";

require_once(DOL_DOCUMENT_ROOT."/Babel_sCal/Babel_scal.class.php");

$langs->load('BabelCalc');

//sCalBabel
if  (!$user->rights->sCalBabel->sCalBabel->Affiche)
{
    exit(0);
}
//droit
//    var_dump($_REQUEST);
    $_SESSION['BabelScalwasOpen'] = $_REQUEST['wasOpen'];
//var_dump($_REQUEST);

if ($_REQUEST['action'] =='remRes')
{
//    include_once(11);
//    var_dump($_REQUEST);
    $_SESSION['BabelScalRes'] = $_REQUEST['remRes'];
    $_SESSION['BabelScalremMem'] = $_REQUEST['remMem'];
    exit(0);
}
$calc = new Babel_scal($db,$langs);

  $calc->draw_scal_init(true);
  $calc->draw_scal_screen(true);
  $calc->GetFormula(true);
  $calc->draw_scal_button(true);

  $calc->draw_scal_footer(true);


?>