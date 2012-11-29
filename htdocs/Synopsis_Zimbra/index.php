<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('pre.inc.php');
#llxHeader1();

$PREAUTH_KEY = ZIMBRA_PREAUTH;
$ZIMBRA_HOST = ZIMBRA_HOST;
$ZIMBRA_DOMAIN = ZIMBRA_DOMAIN;
$ZIMBRA_PROTO = ZIMBRA_PROTO;

if (empty($ZIMBRA_HOST))
{
    llxHeader();
    print '<div class="error ui-state-error">Module Zimbra  was not configured properly.</div>';
    llxFooter('$Date: 2008/07/27 23:06:41 $ - $Revision: 1.5 $');
    exit(0);
}
if (empty($PREAUTH_KEY))
{
    llxHeader();
    print '<div class="error ui-state-error">Module Zimbra has no preauth key.</div>';
    llxFooter('$Date: 2008/07/27 23:06:41 $ - $Revision: 1.5 $');
    exit(0);

}

$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
#$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";
llxHeader("", "Zimbra");
echo left_menu(array());
dol_fiche_head('', 'Zimbra', $langs->trans("Zimbra"));
/**
* Redirect to Zimbra preauth URL
*/
//header("Location: $preauthURL");
echo '<IFRAME style="width: 1200px; height: 1000px;"  SRC="iframe.php">';
echo '</IFRAME>';

llxFooter();

?>
