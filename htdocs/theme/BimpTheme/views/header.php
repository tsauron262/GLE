
        <?php
        
        error_reporting(E_ERROR);
        ini_set("display_errors", 1);
        
        top_htmlheadPerso('');

        function top_htmlheadPerso($head, $title = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $disablejmobile = 0, $disablenofollow = 0) {
            global $db, $conf, $langs, $user, $hookmanager;

            top_httphead();

            if (empty($conf->css))
                $conf->css = '/theme/eldy/style.css.php'; // If not defined, eldy by default

            print '<!doctype html>' . "\n";

            if (!empty($conf->global->MAIN_USE_CACHE_MANIFEST))
                print '<html lang="' . substr($langs->defaultlang, 0, 2) . '" manifest="' . DOL_URL_ROOT . '/cache.manifest">' . "\n";
            else
                print '<html lang="' . substr($langs->defaultlang, 0, 2) . '">' . "\n";
            //print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
            if (empty($disablehead)) {
                $ext = 'layout=' . $conf->browser->layout . '&version=' . urlencode(DOL_VERSION);

                print "<head>\n";
                if (GETPOST('dol_basehref', 'alpha'))
                    print '<base href="' . dol_escape_htmltag(GETPOST('dol_basehref', 'alpha')) . '">' . "\n";
                // Displays meta
                print '<meta charset="UTF-8">' . "\n";
                print '<meta name="robots" content="noindex' . ($disablenofollow ? '' : ',nofollow') . '">' . "\n"; // Do not index
                print '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";  // Scale for mobile device
                print '<meta name="author" content="Dolibarr Development Team">' . "\n";
                // Favicon
                $favicon = dol_buildpath('/theme/' . $conf->theme . '/img/favicon.ico', 1);
                if (!empty($conf->global->MAIN_FAVICON_URL))
                    $favicon = $conf->global->MAIN_FAVICON_URL;
                if (empty($conf->dol_use_jmobile))
                    print '<link rel="shortcut icon" type="image/x-icon" href="' . $favicon . '"/>' . "\n"; // Not required into an Android webview




                    
//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
                //if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
                //if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="author" title="Dolibarr Development Team" href="https://www.dolibarr.org">'."\n";
                // Displays title
                $appli = constant('DOL_APPLICATION_TITLE');
                if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                    $appli = $conf->global->MAIN_APPLICATION_TITLE;

                print '<title>';
                $titletoshow = '';
                if ($title && !empty($conf->global->MAIN_HTML_TITLE) && preg_match('/noapp/', $conf->global->MAIN_HTML_TITLE))
                    $titletoshow = dol_htmlentities($title);
                else if ($title)
                    $titletoshow = dol_htmlentities($appli . ' - ' . $title);
                else
                    $titletoshow = dol_htmlentities($appli);

                if (!is_object($hookmanager))
                    $hookmanager = new HookManager($db);
                $hookmanager->initHooks("main");
                $parameters = array('title' => $titletoshow);
                $result = $hookmanager->executeHooks('setHtmlTitle', $parameters);  // Note that $action and $object may have been modified by some hooks
                if ($result > 0)
                    $titletoshow = $hookmanager->resPrint;    // Replace Title to show
                else
                    $titletoshow .= $hookmanager->resPrint;      // Concat to Title to show

                print $titletoshow;
                print '</title>';

                print "\n";

                if (GETPOST('version', 'int'))
                    $ext = 'version=' . GETPOST('version', 'int'); // usefull to force no cache on css/js
                if (GETPOST('testmenuhider', 'int') || !empty($conf->global->MAIN_TESTMENUHIDER))
                    $ext .= '&testmenuhider=' . (GETPOST('testmenuhider', 'int') ? GETPOST('testmenuhider', 'int') : $conf->global->MAIN_TESTMENUHIDER);

                $themeparam = '?lang=' . $langs->defaultlang . '&amp;theme=' . $conf->theme . (GETPOST('optioncss', 'aZ09') ? '&amp;optioncss=' . GETPOST('optioncss', 'aZ09', 1) : '') . '&amp;userid=' . $user->id . '&amp;entity=' . $conf->entity;
                $themeparam .= ($ext ? '&amp;' . $ext : '');
                if (!empty($_SESSION['dol_resetcache']))
                    $themeparam .= '&amp;dol_resetcache=' . $_SESSION['dol_resetcache'];
                if (GETPOST('dol_hide_topmenu', 'int')) {
                    $themeparam .= '&amp;dol_hide_topmenu=' . GETPOST('dol_hide_topmenu', 'int');
                }
                if (GETPOST('dol_hide_leftmenu', 'int')) {
                    $themeparam .= '&amp;dol_hide_leftmenu=' . GETPOST('dol_hide_leftmenu', 'int');
                }
                if (GETPOST('dol_optimize_smallscreen', 'int')) {
                    $themeparam .= '&amp;dol_optimize_smallscreen=' . GETPOST('dol_optimize_smallscreen', 'int');
                }
                if (GETPOST('dol_no_mouse_hover', 'int')) {
                    $themeparam .= '&amp;dol_no_mouse_hover=' . GETPOST('dol_no_mouse_hover', 'int');
                }
                if (GETPOST('dol_use_jmobile', 'int')) {
                    $themeparam .= '&amp;dol_use_jmobile=' . GETPOST('dol_use_jmobile', 'int');
                    $conf->dol_use_jmobile = GETPOST('dol_use_jmobile', 'int');
                }

                if (!defined('DISABLE_JQUERY') && !$disablejs && $conf->use_javascript_ajax) {
                    print '<!-- Includes CSS for JQuery (Ajax library) -->' . "\n";
                    $jquerytheme = 'base';
                    if (!empty($conf->global->MAIN_USE_JQUERY_THEME))
                        $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
                    if (constant('JS_JQUERY_UI'))
                        print '<link rel="stylesheet" type="text/css" href="' . JS_JQUERY_UI . 'css/' . $jquerytheme . '/jquery-ui.min.css' . ($ext ? '?' . $ext : '') . '">' . "\n";  // JQuery
                    else
                        print '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/css/' . $jquerytheme . '/jquery-ui.css' . ($ext ? '?' . $ext : '') . '">' . "\n";    // JQuery
                    if (!defined('DISABLE_JQUERY_JNOTIFY'))
                        print '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css' . ($ext ? '?' . $ext : '') . '">' . "\n";          // JNotify
                    if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))) {     // jQuery plugin "mutiselect", "multiple-select", "select2"...
                        $tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ? constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
                        print '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/plugins/' . $tmpplugin . '/dist/css/' . $tmpplugin . '.css' . ($ext ? '?' . $ext : '') . '">' . "\n";
                    }
                }

                if (!defined('DISABLE_FONT_AWSOME')) {
                    print '<!-- Includes CSS for font awesome -->' . "\n";
                    print '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/theme/common/fontawesome/css/font-awesome.min.css' . ($ext ? '?' . $ext : '') . '">' . "\n";
                }

                print '<!-- Includes CSS for Dolibarr theme -->' . "\n";
                // Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
                $themepath = dol_buildpath($conf->css, 1);
                $themesubdir = '';
                if (!empty($conf->modules_parts['theme'])) { // This slow down
                    foreach ($conf->modules_parts['theme'] as $reldir) {
                        if (file_exists(dol_buildpath($reldir . $conf->css, 0))) {
                            $themepath = dol_buildpath($reldir . $conf->css, 1);
                            $themesubdir = $reldir;
                            break;
                        }
                    }
                }

                //print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
                print '<link rel="stylesheet" type="text/css" href="' . $themepath . $themeparam . '">' . "\n";
                if (!empty($conf->global->MAIN_FIX_FLASH_ON_CHROME))
                    print '<!-- Includes CSS that does not exists as a workaround of flash bug of chrome -->' . "\n" . '<link rel="stylesheet" type="text/css" href="filethatdoesnotexiststosolvechromeflashbug">' . "\n";

                // CSS forced by modules (relative url starting with /)
                if (!empty($conf->modules_parts['css'])) {
                    $arraycss = (array) $conf->modules_parts['css'];
                    foreach ($arraycss as $modcss => $filescss) {
                        $filescss = (array) $filescss; // To be sure filecss is an array
                        foreach ($filescss as $cssfile) {
                            if (empty($cssfile))
                                dol_syslog("Warning: module " . $modcss . " declared a css path file into its descriptor that is empty.", LOG_WARNING);
                            // cssfile is a relative path
                            print '<!-- Includes CSS added by module ' . $modcss . ' -->' . "\n" . '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($cssfile, 1);
                            // We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters, so browser cache is not used.
                            if (!preg_match('/\.css$/i', $cssfile))
                                print $themeparam;
                            print '">' . "\n";
                        }
                    }
                }
                // CSS forced by page in top_htmlhead call (relative url starting with /)
                if (is_array($arrayofcss)) {
                    foreach ($arrayofcss as $cssfile) {
                        print '<!-- Includes CSS added by page -->' . "\n" . '<link rel="stylesheet" type="text/css" title="default" href="' . dol_buildpath($cssfile, 1);
                        // We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters and browser cache is not used.
                        if (!preg_match('/\.css$/i', $cssfile))
                            print $themeparam;
                        print '">' . "\n";
                    }
                }

                // Output standard javascript links
                if (!defined('DISABLE_JQUERY') && !$disablejs && !empty($conf->use_javascript_ajax)) {
                    // JQuery. Must be before other includes
                    print '<!-- Includes JS for JQuery -->' . "\n";
                    if (defined('JS_JQUERY') && constant('JS_JQUERY'))
                        print '<script type="text/javascript" src="' . JS_JQUERY . 'jquery.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    else
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    if (!empty($conf->global->MAIN_FEATURES_LEVEL) && !defined('JS_JQUERY_MIGRATE_DISABLED')) {
                        if (defined('JS_JQUERY_MIGRATE') && constant('JS_JQUERY_MIGRATE'))
                            print '<script type="text/javascript" src="' . JS_JQUERY_MIGRATE . 'jquery-migrate.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        else
                            print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-migrate.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    }
                    if (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI'))
                        print '<script type="text/javascript" src="' . JS_JQUERY_UI . 'jquery-ui.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    else
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-ui.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    if (!defined('DISABLE_JQUERY_TABLEDND'))
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/tablednd/jquery.tablednd.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    // jQuery jnotify
                    if (empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && !defined('DISABLE_JQUERY_JNOTIFY')) {
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    }
                    // Flot
                    if (empty($conf->global->MAIN_DISABLE_JQUERY_FLOT) && !defined('DISABLE_JQUERY_FLOT')) {
                        if (constant('JS_JQUERY_FLOT')) {
                            print '<script type="text/javascript" src="' . JS_JQUERY_FLOT . 'jquery.flot.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                            print '<script type="text/javascript" src="' . JS_JQUERY_FLOT . 'jquery.flot.pie.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                            print '<script type="text/javascript" src="' . JS_JQUERY_FLOT . 'jquery.flot.stack.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        } else {
                            print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/flot/jquery.flot.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                            print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/flot/jquery.flot.pie.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                            print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/flot/jquery.flot.stack.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        }
                    }
                    // jQuery jeditable
                    if (!empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && !defined('DISABLE_JQUERY_JEDITABLE')) {
                        print '<!-- JS to manage editInPlace feature -->' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jeditable/jquery.jeditable.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jeditable/jquery.jeditable.ui-datepicker.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jeditable/jquery.jeditable.ui-autocomplete.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        print '<script type="text/javascript">' . "\n";
                        print 'var urlSaveInPlace = \'' . DOL_URL_ROOT . '/core/ajax/saveinplace.php\';' . "\n";
                        print 'var urlLoadInPlace = \'' . DOL_URL_ROOT . '/core/ajax/loadinplace.php\';' . "\n";
                        print 'var tooltipInPlace = \'' . $langs->transnoentities('ClickToEdit') . '\';' . "\n"; // Added in title attribute of span
                        print 'var placeholderInPlace = \'&nbsp;\';' . "\n"; // If we put another string than $langs->trans("ClickToEdit") here, nothing is shown. If we put empty string, there is error, Why ?
                        print 'var cancelInPlace = \'' . $langs->trans('Cancel') . '\';' . "\n";
                        print 'var submitInPlace = \'' . $langs->trans('Ok') . '\';' . "\n";
                        print 'var indicatorInPlace = \'<img src="' . DOL_URL_ROOT . "/theme/" . $conf->theme . "/img/working.gif" . '">\';' . "\n";
                        print 'var withInPlace = 300;';  // width in pixel for default string edit
                        print '</script>' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/core/js/editinplace.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jeditable/jquery.jeditable.ckeditor.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    }
                    // jQuery Timepicker
                    if (!empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER')) {
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/core/js/timepicker.js.php?lang=' . $langs->defaultlang . ($ext ? '&amp;' . $ext : '') . '"></script>' . "\n";
                    }
                    if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))) {     // jQuery plugin "mutiselect", "multiple-select", "select2", ...
                        $tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ? constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
                        print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/' . $tmpplugin . '/dist/js/' . $tmpplugin . '.full.min.js' . ($ext ? '?' . $ext : '') . '"></script>' . "\n"; // We include full because we need the support of containerCssClass
                    }
                }
                
               
            global $jsCssBimp;
         echo $jsCssBimp;

                if (!$disablejs && !empty($conf->use_javascript_ajax)) {
                    // CKEditor
                    if (!empty($conf->fckeditor->enabled) && (empty($conf->global->FCKEDITOR_EDITORNAME) || $conf->global->FCKEDITOR_EDITORNAME == 'ckeditor') && !defined('DISABLE_CKEDITOR')) {
                        print '<!-- Includes JS for CKEditor -->' . "\n";
                        $pathckeditor = DOL_URL_ROOT . '/includes/ckeditor/ckeditor/';
                        $jsckeditor = 'ckeditor.js';
                        if (constant('JS_CKEDITOR')) { // To use external ckeditor 4 js lib
                            $pathckeditor = constant('JS_CKEDITOR');
                        }
                        print '<script type="text/javascript">';
                        print 'var CKEDITOR_BASEPATH = \'' . $pathckeditor . '\';' . "\n";
                        print 'var ckeditorConfig = \'' . dol_buildpath($themesubdir . '/theme/' . $conf->theme . '/ckeditor/config.js' . ($ext ? '?' . $ext : ''), 1) . '\';' . "\n";  // $themesubdir='' in standard usage
                        print 'var ckeditorFilebrowserBrowseUrl = \'' . DOL_URL_ROOT . '/core/filemanagerdol/browser/default/browser.php?Connector=' . DOL_URL_ROOT . '/core/filemanagerdol/connectors/php/connector.php\';' . "\n";
                        print 'var ckeditorFilebrowserImageBrowseUrl = \'' . DOL_URL_ROOT . '/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector=' . DOL_URL_ROOT . '/core/filemanagerdol/connectors/php/connector.php\';' . "\n";
                        print '</script>' . "\n";
                        print '<script type="text/javascript" src="' . $pathckeditor . $jsckeditor . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                    }

                    // Browser notifications
                    if (!defined('DISABLE_BROWSER_NOTIF')) {
                        $enablebrowsernotif = false;
                        if (!empty($conf->agenda->enabled) && !empty($conf->global->AGENDA_REMINDER_BROWSER))
                            $enablebrowsernotif = true;
                        if ($conf->browser->layout == 'phone')
                            $enablebrowsernotif = false;
                        if ($enablebrowsernotif) {
                            print '<!-- Includes JS of Dolibarr (brwoser layout = ' . $conf->browser->layout . ')-->' . "\n";
                            print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/core/js/lib_notification.js.php' . ($ext ? '?' . $ext : '') . '"></script>' . "\n";
                        }
                    }

                    // Global js function
                    print '<!-- Includes JS of Dolibarr -->' . "\n";
                    print '<script type="text/javascript" src="' . DOL_URL_ROOT . '/core/js/lib_head.js.php?lang=' . $langs->defaultlang . ($ext ? '&' . $ext : '') . '"></script>' . "\n";

                    // JS forced by modules (relative url starting with /)
                    if (!empty($conf->modules_parts['js'])) {  // $conf->modules_parts['js'] is array('module'=>array('file1','file2'))
                        $arrayjs = (array) $conf->modules_parts['js'];
                        foreach ($arrayjs as $modjs => $filesjs) {
                            $filesjs = (array) $filesjs; // To be sure filejs is an array
                            foreach ($filesjs as $jsfile) {
                                // jsfile is a relative path
                                print '<!-- Include JS added by module ' . $modjs . '-->' . "\n" . '<script type="text/javascript" src="' . dol_buildpath($jsfile, 1) . '"></script>' . "\n";
                            }
                        }
                    }
                    // JS forced by page in top_htmlhead (relative url starting with /)
                    if (is_array($arrayofjs)) {
                        print '<!-- Includes JS added by page -->' . "\n";
                        foreach ($arrayofjs as $jsfile) {
                            if (preg_match('/^http/i', $jsfile)) {
                                print '<script type="text/javascript" src="' . $jsfile . '"></script>' . "\n";
                            } else {
                                if (!preg_match('/^\//', $jsfile))
                                    $jsfile = '/' . $jsfile; // For backward compatibility
                                print '<script type="text/javascript" src="' . dol_buildpath($jsfile, 1) . '"></script>' . "\n";
                            }
                        }
                    }
                }
                
  
    ?>  
    
    <!-- CSS du nouveau Bimptheme -->
    <link rel="stylesheet" href="<?php echo DOL_URL_ROOT . '/theme/BimpTheme/views/plugins/fontawesome-free/css/all.min.css' ?>">
    <link rel="stylesheet" href="<?php echo DOL_URL_ROOT . '/theme/BimpTheme/views/plugins/icon-kit/dist/css/iconkit.min.css' ?>">
    <link rel="stylesheet" href="<?php echo DOL_URL_ROOT . '/theme/BimpTheme/views/plugins/ionicons/dist/css/ionicons.min.css' ?>">
    <link rel="stylesheet" href="<?php echo DOL_URL_ROOT . '/theme/BimpTheme/views/plugins/perfect-scrollbar/css/perfect-scrollbar.css' ?>">
    <link rel="stylesheet" href="<?php echo DOL_URL_ROOT . '/theme/BimpTheme/views/dist/css/theme.min.css' ?>">
    
        <?php

                if (!empty($head))
                    print $head . "\n";
                if (!empty($conf->global->MAIN_HTML_HEADER))
                    print $conf->global->MAIN_HTML_HEADER . "\n";

                print "";
            }

            $conf->headerdone = 1; // To tell header was output
        }
        
//                
//           global $jsCssBimp;
//        echo $jsCssBimp;
        
    ?>
    
    
    <!-- Scripts du nouveau BimpTheme -->
    <script src="<?php echo DOL_URL_ROOT ?>/theme/BimpTheme/views/plugins/perfect-scrollbar/dist/perfect-scrollbar.min.js"></script>
    <script src="<?php echo DOL_URL_ROOT ?>/theme/BimpTheme/views/plugins/screenfull/dist/screenfull.js"></script>
    <script src="<?php echo DOL_URL_ROOT ?>/theme/BimpTheme/views/js/datatables.js"></script>
    <script src="<?php echo DOL_URL_ROOT ?>/theme/BimpTheme/views/dist/js/theme.js"></script>


    </head>
