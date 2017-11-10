<?php

/**
 *  Recursive function to output a tree. <ul id="iddivjstree"><li>...</li></ul>
 *  It is also used for the tree of categories.
 *  Note: To have this function working, check you have loaded the js and css for treeview.
 *  $arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js',
 *                   '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
 *	$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
 *  TODO Replace with jstree plugin instead of treeview plugin.
 *
 *  @param	array	$tab    		Array of all elements
 *  @param  array   $pere   		Array with parent ids ('rowid'=>,'mainmenu'=>,'leftmenu'=>,'fk_mainmenu=>,'fk_leftmenu=>)
 *  @param  int	    $rang   		Level of element
 *  @param	string	$iddivjstree	Id to use for parent ul element
 *  @param  int     $donoresetalreadyloaded     Do not reset global array $donoresetalreadyloaded used to avoid to go down on an aleady processed record
 *  @param  int     $showfk         1=show fk_links to parent into label  (used by menu editor only)
 *  @return	void
 */
function tree_recur_checkbox($tab, $pere, $rang, $iddivjstree='iddivjstree', $donoresetalreadyloaded=0, $showfk=0)
{
    global $tree_recur_alreadyadded;

    if ($rang == 0 && empty($donoresetalreadyloaded)) $tree_recur_alreadyadded=array();

    if ($rang == 0)
	{
		// Test also done with jstree and dynatree (not able to have <a> inside label)
		print '<script type="text/javascript" language="javascript">
		$(document).ready(function(){
			$("#'.$iddivjstree.'").treeview({
				collapsed: true,
				animated: "fast",
				persist: "cookie",
				control: "#'.$iddivjstree.'control",
				toggle: function() {
					/* window.console && console.log("%o was toggled", this); */
				}
			});
		})
		</script>';

		print '<ul id="'.$iddivjstree.'">';
	}

	if ($rang > 50)
	{
	    return;	// Protect against infinite loop. Max 50 depth
	}

	//ballayage du tableau
	$sizeoftab=count($tab);
	$ulprinted=0;
	for ($x=0; $x < $sizeoftab; $x++)
	{
		//var_dump($tab[$x]);exit;
		// If an element has $pere for parent
		if ($tab[$x]['fk_menu'] != -1 && $tab[$x]['fk_menu'] == $pere['rowid'])
		{
		    //print 'rang='.$rang.'-x='.$x." rowid=".$tab[$x]['rowid']." tab[x]['fk_leftmenu'] = ".$tab[$x]['fk_leftmenu']." leftmenu pere = ".$pere['leftmenu']."<br>\n";
			if (empty($ulprinted) && ! empty($pere['rowid']))
			{
    		    if (! empty($tree_recur_alreadyadded[$tab[$x]['rowid']]))
    		    {
    		          dol_syslog('Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.', LOG_WARNING);
    		          continue;
    		    }

                print '<ul'.(empty($pere['rowid'])?' id="treeData"':'').'>'; $ulprinted++;
			}
			print "\n".'<li '.($tab[$x]['statut']?' class="liuseractive"':'class="liuserdisabled"').'>';
			if ($showfk)
			{
			    print '<table class="nobordernopadding centpercent"><tr><td>';
			    print '<strong> &nbsp; ';
			    print $tab[$x]['title'];
			    print '&nbsp; (fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].')';
			    print '</td><td align="right">';
			    print $tab[$x]['buttons'];
			    print '</td></tr></table>';
			}
			else
			{
//				print str_replace('</a> <a href=', '</a> <input type="checkbox"> <a href=', $tab[$x]['entry']);
				print str_replace('style="background: #aaa"><a href="', 'style="background: #aaa"> <input type="checkbox" id='.$tab[$x]['rowid'].'> <a href="', $tab[$x]['entry']);
			}
			//print ' -> A '.$tab[$x]['rowid'].' mainmenu='.$tab[$x]['mainmenu'].' leftmenu='.$tab[$x]['leftmenu'].' fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].'<br>'."\n";
		    $tree_recur_alreadyadded[$tab[$x]['rowid']]=($rang + 1);
			// And now we search all its sons of lower level
			tree_recur_checkbox($tab, $tab[$x], $rang+1, 'iddivjstree', 0, $showfk);
			print '</li>';
		}
		elseif (! empty($tab[$x]['rowid']) && $tab[$x]['fk_menu'] == -1 && $tab[$x]['fk_mainmenu'] == $pere['mainmenu'] && $tab[$x]['fk_leftmenu'] == $pere['leftmenu'])
		{
		    //print 'rang='.$rang.'-x='.$x." rowid=".$tab[$x]['rowid']." tab[x]['fk_leftmenu'] = ".$tab[$x]['fk_leftmenu']." leftmenu pere = ".$pere['leftmenu']."<br>\n";
		    if (empty($ulprinted) && ! empty($pere['rowid']))
		    {
		        if (! empty($tree_recur_alreadyadded[$tab[$x]['rowid']]))
		        {
		            dol_syslog('Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.', LOG_WARNING);
		            //print 'Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.';
                    continue;
		        }

		        print '<ul'.(empty($pere['rowid'])?' id="treeData"':'').'>'; $ulprinted++;
		    }
			print "\n".'<li '.($tab[$x]['statut']?' class="liuseractive"':'class="liuserdisabled"').'>';
			if ($showfk)
			{
				print "wow";
			    print '<table class="nobordernopadding centpercent"><tr><td>';
			    print '<strong> &nbsp; <a href="edit.php?menu_handler='.$menu_handler_to_search.'&action=edit&menuId='.$menu['rowid'].'">';
			    print 'ICI'.$tab[$x]['title'];
			    print 'WOW</a></strong>';
			    print '&nbsp; (mainmenu='.$tab[$x]['mainmenu'].' leftmenu='.$tab[$x]['leftmenu'].' - fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].')';
			    print '</td><td align="right">';
			    print $tab[$x]['buttons'];
			    print '</td></tr></table>';
			}
			else
			{
			    print 'ok2'.$tab[$x]['entry'];
			}
			//print ' -> B '.$tab[$x]['rowid'].' mainmenu='.$tab[$x]['mainmenu'].' leftmenu='.$tab[$x]['leftmenu'].' fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].'<br>'."\n";
			$tree_recur_alreadyadded[$tab[$x]['rowid']]=($rang + 1);
			// And now we search all its sons of lower level
			//print 'Call tree_recur for x='.$x.' rowid='.$tab[$x]['rowid']." fk_mainmenu pere = ".$tab[$x]['fk_mainmenu']." fk_leftmenu pere = ".$tab[$x]['fk_leftmenu']."<br>\n";
		    tree_recur_checkbox($tab, $tab[$x], $rang+1, 'iddivjstree', 0, $showfk);
			print '</li>';
		}
	}
	if (! empty($ulprinted) && ! empty($pere['rowid'])) { print '</ul>'."\n"; }

    if ($rang == 0) print '</ul>';
}