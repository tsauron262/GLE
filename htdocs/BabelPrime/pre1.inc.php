<?php
/*
 * GLE by Synopsis et DRSI
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
require_once ("../main.inc.php");
$user->getrights();

function llxHeader1($head = "")
{
  global $user, $conf, $langs;

  top_menu1($head);

  $menu = new Menu();

  if ($conf->societe->enabled && $user->rights->societe->lire)
    {
      $langs->load("companies");
      $menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("Companies"));

      if ($user->rights->societe->creer)
        {
    $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", $langs->trans("MenuNewCompany"));
        }

      if(is_dir("societe/groupe"))
        {
    $menu->add_submenu(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"));
        }
      $menu->add_submenu(DOL_URL_ROOT."/contact/index.php",$langs->trans("Contacts"));
    }

    if ($conf->categorie->enabled)
    {
        $langs->load("categories");
        $menu->add(DOL_URL_ROOT."/categories/index.php?type=0", $langs->trans("Categories"));
    }

  if ($conf->commercial->enabled && isset($user->rights->commercial->lire) && $user->rights->commercial->lire)
    {
      $langs->load("commercial");
      $menu->add(DOL_URL_ROOT."/comm/index.php",$langs->trans("Commercial"));

      $menu->add_submenu(DOL_URL_ROOT."/comm/clients.php",$langs->trans("Customers"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php",$langs->trans("Prospects"));

      if ($user->rights->propale->lire)
        {
    $langs->load("propal");
    $menu->add_submenu(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
        }
    }

  if ($conf->compta->enabled || $conf->comptaexpert->enabled)
    {
      $langs->load("compta");
      $menu->add(DOL_URL_ROOT."/compta/index.php", $langs->trans("MenuFinancial"));

      if ($user->rights->facture->lire) {
    $langs->load("bills");
    $menu->add_submenu(DOL_URL_ROOT."/compta/facture.php", $langs->trans("Bills"));
      }
    }

  if ($conf->fichinter->enabled  && $user->rights->ficheinter->lire)
    {
      $langs->trans("interventions");
      $menu->add(DOL_URL_ROOT."/fichinter/index.php", $langs->trans("Interventions"));
    }

  if (($conf->produit->enabled || $conf->service->enabled) && $user->rights->produit->lire)
    {
      $langs->load("products");
      $chaine="";
      if ($conf->produit->enabled) { $chaine.= $langs->trans("Products"); }
      if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
      if ($conf->service->enabled) { $chaine.= $langs->trans("Services"); }
      $menu->add(DOL_URL_ROOT."/product/index.php", "$chaine");

/*
        if ($conf->boutique->enabled)
        {
            if ($conf->boutique->livre->enabled)
            {
                $menu->add_submenu(DOL_URL_ROOT."/boutique/livre/index.php", "Livres");
            }

            if ($conf->boutique->album->enabled)
            {
                $menu->add_submenu(DOL_URL_ROOT."/product/album/index.php", "Albums");
            }
        }
*/
    }


  if ($conf->commande->enabled && $user->rights->commande->lire)
    {
      $langs->load("orders");
      $menu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
    }

  if ($conf->document->enabled)
    {
      $menu->add(DOL_URL_ROOT."/docs/index.php", $langs->trans("Documents"));
    }

  if ($conf->expedition->enabled && $user->rights->expedition->lire)
    {
      $langs->load("sendings");
      $menu->add(DOL_URL_ROOT."/expedition/index.php", $langs->trans("Sendings"));
    }

  if ($conf->mailing->enabled && $user->rights->mailing->lire)
    {
      $langs->load("mails");
      $menu->add(DOL_URL_ROOT."/comm/mailing/index.php",$langs->trans("EMailings"));
    }

  if ($conf->telephonie->enabled)
    {
      $menu->add(DOL_URL_ROOT."/telephonie/index.php", "T&eacute;&eacute;phonie");
    }

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/index.php", $langs->trans("Donations"));
    }

  if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
    {
      $langs->load("suppliers");
      $menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    }

  if ($conf->voyage->enabled && $user->societe_id == 0)
    {
      $menu->add(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");
      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");
      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/reduc.php","Reduc");
    }

  if ($conf->domaine->enabled)
    {
      $menu->add(DOL_URL_ROOT."/domain/index.php", "Domaines");
    }

  if ($conf->postnuke->enabled)
    {
      $menu->add(DOL_URL_ROOT."/postnuke/articles/index.php", "Editorial");
    }

  if ($conf->bookmark->enabled && $user->rights->bookmark->lire)
    {
      $menu->add(DOL_URL_ROOT."/bookmarks/liste.php", $langs->trans("Bookmarks"));
    }

  if ($conf->export->enabled)
    {
      $langs->load("exports");
      $menu->add(DOL_URL_ROOT."/exports/index.php", $langs->trans("Exports"));
    }

  if ($user->rights->user->user->lire || $user->admin || $user->local_admin)
    {
      $langs->load("users");
      $menu->add(DOL_URL_ROOT."/user/home.php", $langs->trans("MenuUsersAndGroups"));
    }

  if ($user->admin || $user->local_admin)
    {
      $menu->add(DOL_URL_ROOT."/admin/index.php", $langs->trans("Setup"));
    }

  left_menu($menu->liste);
}


function top_menu1($head, $title="", $target="")
{
    global $user, $conf, $langs, $db, $dolibarr_main_authentication;

    if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';
    if (! $conf->left_menu) $conf->left_menu='eldy_backoffice.php';

    top_htmlhead1($head, $title);

    print '<body id="mainbody"><div id="dhtmltooltip"></div>';

    /*
     * Si la constante MAIN_NEED_UPDATE est definie (par le script de migration sql en general), c'est que
     * les donnees ont besoin d'un remaniement. Il faut passer le update.php
     */
    if (isset($conf->global->MAIN_NEED_UPDATE) && $conf->global->MAIN_NEED_UPDATE)
    {
        $langs->load("admin");
        print '<div class="fiche">'."\n";
        print '<table class="noborder" width="100%">';
        print '<tr><td>';
        print $langs->trans("UpdateRequired",DOL_URL_ROOT.'/install/index.php');
        print '</td></tr>';
        print "</table>";
        llxFooter();
        exit;
    }


    /*
     * Barre de menu superieure
     */
    print "\n".'<!-- Start top horizontal menu -->'."\n";
    print '<div class="tmenu">'."\n";

    // Charge le gestionnaire des entrees de menu du haut
    if (! file_exists(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".$conf->top_menu))
    {
        $conf->top_menu='eldy_backoffice.php';
    }
    require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".$conf->top_menu);
    $menutop = new MenuTop($db);
    $menutop->atarget=$target;

    // Affiche le menu
    $menutop->showmenu();

    // Lien sur fiche du login
    print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
    print $menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
    print '>'.$user->login.'</a>';

    // Lien logout
    if (! isset($_SERVER["REMOTE_USER"]) || ! $_SERVER["REMOTE_USER"])
    {
        $title=$langs->trans("Logout");
        $title.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dol_print_date($user->datelastlogin,"dayhour");
        if ($dolibarr_main_authentication) $title.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$dolibarr_main_authentication;

        $text='';
        $text.='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
        $text.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
        $text.='>';
        $text.='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
        $text.=' alt="" title=""';
        $text.='>';
        $text.='</a>';

        $html=new Form($db);
        print $html->textwithtooltip('',$title,2,1,$text);

//        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
//        print ' alt="'.$title.'" title="'.$title.'"';
//        print '>';
    }

    print "\n</div>\n<!-- End top horizontal menu -->\n";

}
function top_htmlhead1($head, $title='', $disablejs=0, $disablehead=0)
{
    global $user, $conf, $langs, $db, $micro_start_time;

    // Pour le tuning optionnel. Activer si la variable d'environnement DOL_TUNING
    // est positionne A appeler avant tout.
    if (isset($_SERVER['DOL_TUNING'])) $micro_start_time=dol_microtime_float(true);

    if (! $conf->css)  $conf->css ='/theme/eldy/eldy.css.php';

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
        if ($title)
        {
            print '<title>Dolibarr - '.$title.'</title>';
        }
        else
        {
            if (defined("MAIN_TITLE"))
            {
                print "<title>".MAIN_TITLE."</title>";
            }
            else
            {
                print '<title>Dolibarr</title>';
            }
        }
        print "\n";

        // Affiche style sheets et link
        print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
        print '<link rel="stylesheet" type="text/css" media="print" href="'.DOL_URL_ROOT.'/theme/print.css">'."\n";

        // Style sheets pour la class Window
        if (! empty($conf->use_javascript_ajax))
        {
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/default.css">'."\n";
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/alphacube.css">'."\n";
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/alert.css">'."\n";
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

        if (! $disablejs && ($conf->use_javascript || $conf->use_ajax))
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/core/lib/lib_head.js"></script>'."\n";
        }
        if (! $disablejs && $conf->use_ajax)
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype.js"></script>'."\n";
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/effects.js"></script>'."\n";
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/controls.js"></script>'."\n";
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/window/window.js"></script>'."\n";
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/window/tooltip.js"></script>'."\n";

        }
        //EOS TODO: Si voip OK
        if (! $disablejs && ($conf->use_javascript || $conf->use_ajax))
        {
            print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/core/lib/lib_phoneTo.js"></script>'."\n";
        }
        if (! $disablejs && $conf->use_ajax)
        // Define tradMonths javascript array
        $tradTemp=Array($langs->trans("January"),
                        $langs->trans("February"),
                        $langs->trans("March"),
                        $langs->trans("April"),
                        $langs->trans("May"),
                        $langs->trans("June"),
                        $langs->trans("July"),
                        $langs->trans("August"),
                        $langs->trans("September"),
                        $langs->trans("October"),
                        $langs->trans("November"),
                        $langs->trans("December")
                        );
        print '<script language="javascript" type="text/javascript">';
        print 'var tradMonths = '.php2js($tradTemp).';';
        print '</script>'."\n";
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/ProspectBabel/ProspectionBabel.js"></script>'."\n";

        print "</head>\n";
    }
}

?>