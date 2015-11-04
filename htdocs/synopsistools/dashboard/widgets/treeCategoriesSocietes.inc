<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
ini_set('browscap',DOL_DOCUMENT_ROOT."/synopsistools/dashboard/browscap.ini");
//$browser = get_browser();

// The get widget handler.
function widget_treeCategoriesSocietes() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user, $browser,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("products");
    $langs->load("categories");
    if ($user->rights->categorie->lire){
        require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
        $title=$langs->trans("ProductsCategoriesArea");
        $c = new Categorie($db);
        $html = new Form($db);
        $type='contact';

        $cate_arbo = $c->get_full_arbo($type);
        $table = "<p>";
        if ($conf->use_javascript_ajax)//  && $browser->browser != 'IE')
        {
            $table .= "<script>var DOL_URL_ROOT='".DOL_URL_ROOT."';</script>";
            $table .= '<script type="text/javascript" src="'.DOL_URL_ROOT.'/synopsistools/jquery/jquery.treeview.js" ></script>';
            $table .= '<link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/synopsistools/css/jquery.treeview.css" />';
            $table .= '<table class="noborder" width="100%">';
            $table .= '<tr class="liste_titre"><td colspan=2>'.$langs->trans("CategoriesTree").'</td>';
            $table .= '</tr>';

            $table .= '<tr><td colspan="2">';
            if (sizeof($cate_arbo))
            {
//                require_once(DOL_DOCUMENT_ROOT.'/includes/treemenu/TreeMenu.php');
                // Ajoute id_mere sur tableau cate_arbo
                $i=0;
                $table .= "<ul id='treeviewCat' class='treeview'>";
                foreach ($cate_arbo as $key => $val)
                {
                    $i++;
                    if ($val['id_mere']."x"=='x')
                    {
                        $table .= "<li><a href=\"".DOL_URL_ROOT."/categories/viewcat.php?id=".$val['id']."&type=0\">".$val['label']."</a>";
                        $table .= "<ul>";
                        if (count($val['id_children']) > 0)
                        {
                            foreach($val['id_children'] as $key1=>$val1)
                            {
                                //$table .= print_r($cate_arbo,true);
                                $tmpId = false;
                                foreach($cate_arbo as $key2=>$val2)
                                {
                                    if ($val2['id'] == $val1)
                                    {
                                        $tmpId = $key2;
                                        break;
                                    }
                                }
                                if ($tmpId  && count($cate_arbo[$tmpId]['id_children']) > 0)
                                {
                                    $table .= recurse_tree($val1,$cate_arbo);
                                }
                            }
                        }
                        $table .= "</ul></li>";
                    }
                }
            } else {
                $table .= $langs->trans("NoneCategory");
            }

            $table .= '</td></tr>';

            $table .= "</table>";
            $table .= '<br>';
        } else {
            /*
            * Categories principales en HTML pure
            */
            $table .= '<table class="noborder" width="100%">';
            $table .= '<tr class="liste_titre"><td>'.$langs->trans("AllCats").'</td><td>'.$langs->trans("Description").'</td></tr>';

            if (sizeof($cate_arbo))
            {
                if (is_array($cate_arbo))
                {
                    $var=true;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                    foreach($cate_arbo as $key => $value)
                    {
                        $var = ! $var;
                        $table .= "\t<tr class=\"".$arrCss[$var]."\">\n";
                        $table .= '<td><a href="viewcat.php?id='.$cate_arbo[$key]['id'].'&amp;type='.$type.'">'.$cate_arbo[$key]['fulllabel'].'</a></td>';
                        $table .= '<td>'.$c->get_desc($cate_arbo[$key]['id']).'</td>';
                        $table .= "\t</tr>\n";
                    }
                }

            }

            $table .= "</table>";
        }



        $table .="</p>";
        return array(
            'title' => 'Cat&eacute;gories - Arbre soci&eacute;t&eacute;s',
            'content' => $table,
            'initScript' => DOL_URL_ROOT."/synopsistools/dashboard/widgets/scripts/treeview.js",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }else {
      return array(
        'title' => 'Cat&eacute;gories - Arbre soci&eacute;t&eacute;s',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_treeCategoriesSocietes_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}
function recurse_tree($idChild,$cate_arbo){
    $html = "";
    foreach($cate_arbo as $key=>$val)
    {
        if ($val['id'] == $idChild)
        {
            $html .= "<li><a href=\"".DOL_URL_ROOT."/categories/viewcat.php?id=".$val['id']."&type=0\">".$val['label']."</a>";
            $html .= "<ul>";
            //$html.= " (".count($val['id_children']).")";
            if (count($val['id_children']) > 0)
            {
                foreach($val['id_children'] as $key1=>$val1)
                {
                    //$table .= print_r($cate_arbo,true);
                    $tmpId = false;
                    foreach($cate_arbo as $key2=>$val2)
                    {
                        if ($val2['id'] == $val1)
                        {
                            $tmpId = $key2;
                            break;
                        }
                    }
                    if ($tmpId  && count($cate_arbo[$tmpId]['id_children']) > 0)
                    {
//                        $html .= "<li>";
                        $html .= recurse_tree($val1,$cate_arbo);
  //                      $html .= "</li>";
                    } else {
                        //$html .= print_r($val,true);
                        $html .= "<li><a href=\"".DOL_URL_ROOT."/categories/viewcat.php?id=".$cate_arbo[$tmpId]['id']."&type=0\">".$cate_arbo[$tmpId]['label']."</a></li>";
                    }
                }
            }
            $html .= "</ul></li>";
        }
    }
    return ($html);
}
