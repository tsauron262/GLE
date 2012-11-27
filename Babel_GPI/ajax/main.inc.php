<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
    \file       htdocs/main.inc.php
    \ingroup    core
    \brief      Fichier de formatage generique des ecrans Dolibarr
    \version    $Id: main.inc.php,v 1.364.2.1 2008/09/10 11:13:39 eldy Exp $
*/

// Pour le tuning optionnel. Activer si la variable d'environnement DOL_TUNING est positionnee.
// A appeler avant tout. Fait l'equivalent de la fonction dol_microtime_float pas encore chargee.
$micro_start_time=0;
if (! empty($_SERVER['DOL_TUNING']))
{
    list($usec, $sec) = explode(" ", microtime());
    $micro_start_time=((float)$usec + (float)$sec);
}


// Forcage du parametrage PHP magic_quotes_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
function stripslashes_deep($value)
{
   return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
//if (! preg_match('/PHP/6/i', $_SERVER['SERVER_SOFTWARE']))
if (function_exists('get_magic_quotes_gpc'))    // magic_quotes_* plus pris en compte dans PHP6
{
    if (get_magic_quotes_gpc())
    {
    $_GET     = array_map('stripslashes_deep', $_GET);
    $_POST    = array_map('stripslashes_deep', $_POST);
    $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
    @set_magic_quotes_runtime(0);
}

// Filtre les GET et POST pour supprimer les SQL INJECTION
function test_sql_inject($val)
{
  $sql_inj = 0;
    if (is_array($val))
    {

        foreach($val as $key=>$val1)
        {
            if (is_array($val1))
            {
                foreach($val1 as $key2=>$val2)
                {
                  $sql_inj += preg_match('/delete[[:space:]]+from/i', $val2);
                  $sql_inj += preg_match('/create[[:space:]]+table/i', $val2);
                  $sql_inj += preg_match('/update.+set.+=/i', $val2);
                  $sql_inj += preg_match('/insert[[:space:]]+into/i', $val2);
                  $sql_inj += preg_match('/select.+from/i', $val2);
                }
            } else {
                  $sql_inj += preg_match('/delete[[:space:]]+from/i', $val1);
                  $sql_inj += preg_match('/create[[:space:]]+table/i', $val1);
                  $sql_inj += preg_match('/update.+set.+=/i', $val1);
                  $sql_inj += preg_match('/insert[[:space:]]+into/i', $val1);
                  $sql_inj += preg_match('/select.+from/i', $val1);
            }
        }
    } else {
      $sql_inj += preg_match('/delete[[:space:]]+from/i', $val);
      $sql_inj += preg_match('/create[[:space:]]+table/i', $val);
      $sql_inj += preg_match('/update.+set.+=/i', $val);
      $sql_inj += preg_match('/insert[[:space:]]+into/i', $val);
      $sql_inj += preg_match('/select.+from/i', $val);
    }

  return $sql_inj;
}
foreach ($_GET as $key => $val)
{
  if (test_sql_inject($val) > 0)
    unset($_GET[$key]);
}
foreach ($_POST as $key => $val)
{
  if (test_sql_inject($val) > 0)
    unset($_POST[$key]);
}
// Fin filtre des GET et POST


// This is to make Dolibarr working with Plesk
// Babel dont like plesk
//set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Set and init common variables
require_once("master.inc.php");

// Check if HTTPS
if ($conf->main_force_https)
{
    if (preg_match('/^http:/i',$_SERVER["SCRIPT_URI"]) && ! preg_match('/^https:/i',$_SERVER["SCRIPT_URI"]))
    {
        if ($_SERVER["HTTPS"] != 'on')
        {
            dol_syslog("dolibarr_main_force_https is on but https disabled on serveur",LOG_ERR);
        }
        else
        {
            dol_syslog("dolibarr_main_force_https is on, we make a redirect",LOG_DEBUG);
            $newurl=preg_replace('/^http:','https:/i',$_SERVER["SCRIPT_URI"]);

            header("Location: ".$newurl);
            exit;
        }
    }
}

// Chargement des includes complementaire de presentation
if (! defined('NOREQUIREMENU')) require_once(DOL_DOCUMENT_ROOT ."/menu.class.php");            // Need 11ko memory (11ko in 2.2)
if (! defined('NOREQUIREHTML')) require_once(DOL_DOCUMENT_ROOT ."/core/class/html.form.class.php");    // Need 690ko memory (800ko in 2.2)
if (! defined('NOREQUIREAJAX') && $conf->use_javascript_ajax) require_once(DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php');    // Need 20ko memory
//stopwithmem();

// Init session
$sessionname="DOLSESSID_".$dolibarr_main_db_name;
session_name($sessionname);
session_start();
dol_syslog("Session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"]);




// Si le login n'a pu etre recupere, on est identifie avec un compte qui n'existe pas.
// Tentative de hacking ?
//if (! $user->login) accessforbidden();



dol_syslog("Access to ".$_SERVER["PHP_SELF"],LOG_INFO);


$langs->load("main");
$langs->load("dict");


/**
 *  \brief      Show HTML header
 *  \param      head        Optionnal head lines
 *  \param      title       Web page title
 *    \param        disablejs    Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *    \param        disablehead    Do not output head section
 *    \param        arrayofjs    Array of js files to add in header
 *    \param        arrayofcss    Array of css files to add in header
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
    global $user, $conf, $langs, $db;

    if (empty($conf->css))  $conf->css ='/theme/eldy/eldy.css.php';

    //header("Content-type: text/html; charset=UTF-8");
    header("Content-type: text/html; charset=".$conf->character_set_client);

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
    print "\n";
    print "<html>\n";
    if ($disablehead == 0)
    {
        print "<head>\n";

        print $langs->lang_header();
        print $head;

        // Affiche meta
        print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
        print '<meta name="author" content="Dolibarr Development Team">'."\n";

        // Affiche title
        $appli='Dolibarr';
        if (! empty($conf->global->MAIN_TITLE)) $appli=$conf->global->MAIN_TITLE;

        if ($title) print '<title>'.$appli.' - '.$title.'</title>';
        else print "<title>".$appli."</title>";
        print "\n";

        // Output style sheets
        print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
        // CSS forced by modules
        if (is_array($conf->css_modules))
        {
            foreach($conf->css_modules as $cssfile)
            {    // cssfile is an absolute path
                print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$cssfile.'">'."\n";
            }
        }
        // CSS forced by page
        if (is_array($arrayofcss))
        {
            foreach($arrayofcss as $cssfile)
            {
                print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$cssfile.'">'."\n";
            }
        }

        // Definition en alternate style sheet des feuilles de styles les plus maintenues
        // Les navigateurs qui supportent sont rares. Plus aucun connu.
        /*
        print '<link rel="alternate stylesheet" type="text/css" title="Eldy" href="'.DOL_URL_ROOT.'/theme/eldy/eldy.css.php">'."\n";
        print '<link rel="alternate stylesheet" type="text/css" title="Freelug" href="'.DOL_URL_ROOT.'/theme/freelug/freelug.css.php">'."\n";
        print '<link rel="alternate stylesheet" type="text/css" title="Yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";
        */

        print '<link rel="top" title="'.$langs->trans("Home").'" href="'.DOL_URL_ROOT.'/">'."\n";
        print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
        print '<link rel="author" title="Dolibarr Development Team" href="http://www.dolibarr.org">'."\n";
        print '<link rel="author" title="Synopsis et DRSI Development Team" href="http://www.synopsis-erp.com">'."\n";

        // Output javascript links
        if (! $disablejs && $conf->use_javascript_ajax)
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/core/lib/lib_head.js"></script>'."\n";
        }
        if (! $disablejs && $conf->use_javascript_ajax)
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

            // This one is required for all Ajax features
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype.js"></script>'."\n";
            // This one is required fox boxes
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";

            // Those ones are required only with option "confirm by ajax popup"
            if ($conf->global->MAIN_CONFIRM_AJAX)
            {
                // PWC css
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/alert.css">'."\n";
                // Scriptaculous used by PWC
                print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/effects.js"></script>'."\n";
                print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/controls.js"></script>'."\n";
                // PWC js
                print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/pwc/window.js"></script>'."\n";
            }
        }
        if ($conf->global->MAIN_MODULE_BABELsCALC && $user->rights->sCalBabel->sCalBabel->Affiche)
        {

            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/Babel_sCal/js/scal.js"></script>'."\n";
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_sCal/css/scal.css">'."\n";
            if ($_SESSION['BabelScalwasOpen']=='1')
            {
                print '<script type="text/javascript" >var path="'.DOL_URL_ROOT.'/Babel_sCal/";scal_showCacl(path)</script>';
            }

        }
        if ($conf->global->MAIN_MODULE_BABELIM && $user->rights->IMBabel->IMBabel->Affiche)
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/Babel_IM/js/Babel_IM.js"></script>'."\n";
        }

        if (is_array($arrayofjs))
        {
            foreach($arrayofjs as $jsfile)
            {
                print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/'.$jsfile.'"></script>'."\n";
            }
        }
        if (! $disablejs && $conf->use_javascript_ajax)
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/core/lib/lib_head.js"></script>'."\n";
        }

        print "</head>\n";
    }
}


function llxFooter($foot='',$limitIEbug=1)
{
    global $conf, $dolibarr_auto_user, $micro_start_time;

    print "\n".'</div> <!-- end div class="fiche" -->'."\n";

//    print "\n".'</div> <!-- end div class="vmenuplusfiche" -->'."\n";
    print "\n".'</td></tr></table> <!-- end right area -->'."\n";

    if (! empty($_SERVER['DOL_TUNING']))
    {
        $micro_end_time=dol_microtime_float(true);
        print '<script language="javascript" type="text/javascript">window.status="Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
        if (function_exists("memory_get_usage"))
        {
            print ' - Memory usage: '.memory_get_usage();
        }
        if (function_exists("zend_loader_file_encoded"))
        {
            print ' - Zend encoded file: '.(zend_loader_file_encoded()?'yes':'no');
        }
        print '"</script>';
        print "\n";
    }

    if ($conf->use_javascript_ajax)
    {
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/core/lib/lib_foot.js"></script>';
    }

    // Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
    if ($limitIEbug && ! $conf->browser->firefox) print "\n".'<div class="tabsAction">&nbsp;</div>'."\n";

    print "</body>\n";
    print "</html>\n";
}

?>
