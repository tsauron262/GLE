/*
 ** GLE by Synopsis et DRSI
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

//Script to launch the Babel_IM popup
var BabelIMWin = false;
function Babel_showIM(pPath)
{
    if (BabelIMWin && BabelIMWin.closed == false)
    {
        BabelIMWin.focus();
    }  else {
        BabelIMWin = window.open(pPath+'/sparkweb/BabelIM.php',
                    'BabelIM',
                    config='height=700, width=600, toolbar=no, menubar=no, scrollbars=no, resizable=yes, location=no, directories=no, status=no');
    }
}
