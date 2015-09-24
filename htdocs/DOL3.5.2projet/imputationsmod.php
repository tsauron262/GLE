<?php
     /*
/**
     *
     * Name : imputations.php
     * GLE-1.2
     */
    require_once('pre.inc.php') ;
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php") ;

    $userId = $user->id ;
    if ( $_REQUEST[ 'userid' ] > 0 )
	$userId = $_REQUEST[ 'userid' ] ;

    $curUser = new User( $db ) ;
    $curUser->fetch($userId) ;

    $format = 'weekly' ;
    if ( $_REQUEST[ 'format' ] . 'x' != "x" )
	$format = $_REQUEST[ 'format' ] ;
    $date = strtotime( date( 'Y-m-d' ) ) ;
    if ( $_REQUEST[ 'date' ] . 'x' != "x" )
	$date = $_REQUEST[ 'date' ] ;

    $monthDur = 30 ;

//Si format => weekly => debute un lundi, idem bi weekly
//Si format => monthly => debute le 1 du mois => doit determiner le nb de jour du mois
    if ( ($format == "weekly" || $format == "biweekly") && date( 'w', $date ) != 1 )
    {
	while ( date( 'w', $date ) != 1 )
	{
	    $date -= 3600 * 24 ;
	}
    }
    else if ( $format == 'monthly' && date( 'j', $date ) != 1 )
    {
	$date = strtotime( date( 'Y', $date ) . "-" . date( 'm', $date ) . "-01" ) ;
    }
    if ( $format == 'monthly' )
	$monthDur = date( 't', $date ) ;

    $arrTitleNav = array( 'nextweekly' => "Semaine suivante", 'nextbiweekly' => "Semaine suivante", 'nextmonthly' => "Mois suivant",
	'prevweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevbiweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevmonthly' => "Mois pr&eacute;c&eacute;dent", ) ;

    $fromProj = false ;
    $projet = false ;
    if ( $_REQUEST[ 'fromProjet' ] == 1 && $_REQUEST[ 'id' ] > 0 )
    {
	require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php") ;
	require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php") ;
	$projet = new Project( $db ) ;
	$projet->fetch( $_REQUEST[ 'id' ] ) ;
	$fromProj = true ;
    }
    print_r ($_REQUEST[ 'model' ]) ;
    if ( $_REQUEST[ 'action' ] == 'builddoc' )    // In get or post
    {
	require_once(DOL_DOCUMENT_ROOT . "/core/modules/imputation/modules_imputations.php") ;
	$outputlangs = '' ;
	if ( $_REQUEST[ 'lang_id' ] )
	{
	    $outputlangs = new Translate( "", $conf ) ;
	    $outputlangs->setDefaultLang( $_REQUEST[ 'lang_id' ] ) ;
	}

	$result = imputations_pdf_create( $db, $_REQUEST[ 'id' ], $_REQUEST[ 'model' ], $outputlangs ) ;
	if ( $result <= 0 )
	{
	    dol_print_error( $db, $result ) ;
	    exit ;
	}
	else
	{
	    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php") ;
	    $interface = new Interfaces( $db ) ;
	    $result = $interface->run_triggers( 'ECM_GENIMPUTATIONS', false, $user, $langs, $conf ) ;
	    if ( $result < 0 )
	    {
		$error++ ;
		$this->errors = $interface->errors ;
	    }
	    // Fin appel triggers
	    Header( 'Location: ' . $_SERVER[ "PHP_SELF" ] . '?id=' . $_REQUEST[ 'id' ] . '#builddoc' ) ;
	    exit ;
	}
    }

    if ( $_REQUEST[ 'action' ] == 'save' )
    {
	$arrModTask = array( ) ;
	if ( $userId > 0 )
	{
	    foreach ( $_REQUEST[ 'activity_hidden' ] as $key => $val )
	    {
		$arrModTask[ $key ] = $key ;
		foreach ( $val as $key1 => $val1 )
		{
		    if ( $_REQUEST[ 'activity' ][ $key ][ $key1 ] != $val1 )
		    {
			//Insert or updateMode
			$requete = "SELECT *
                                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective
                                 WHERE task_date_effective = '" . date( 'Y-m-d H:i', $key1 ) . "'
                                   AND fk_user = " . $userId . "
                                   AND fk_task = " . $key ;
			$sql = $db->query( $requete ) ;
			if ( $db->num_rows( $sql ) > 0 )
			{
			    $res = $db->fetch_object( $sql ) ;
			    $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective
                                       SET task_duration_effective = " . intval( $_REQUEST[ 'activity' ][ $key ][ $key1 ] * 3600 ) . "
                                     WHERE rowid = " . $res->rowid ;
			    $sql1 = $db->query( $requete ) ;
			}
			else
			{
			    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective (task_duration_effective, task_date_effective, fk_task, fk_user)
                                         VALUES (" . intval( $_REQUEST[ 'activity' ][ $key ][ $key1 ] * 3600 ) . ",'" . date( 'Y-m-d H:i', $key1 ) . "'," . $key . "," . $userId . ")" ;
			    $sql1 = $db->query( $requete ) ;
			}
		    }
		}
	    }
	}
	foreach ( $arrModTask as $taskId )
	{
	    $requete = "SELECT sum(task_duration_effective) as durEff FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective WHERE fk_task = " . $taskId ;
	    $sql = $db->query( $requete ) ;
	    $res = $db->fetch_object( $sql ) ;
	    $tot = $res->durEff ;
	    $requete = "UPDATE ".MAIN_DB_PREFIX."projet_task SET duration_effective = " . $tot . " WHERE rowid = " . $taskId ;
	    $sql = $db->query( $requete ) ;
	}
	header( 'location: imputations.php?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . "&format=" . $format . "&date=" . $date ) ;
    }


$js = <<<EOF
<link href="css/imputations.css" type="text/css" rel="stylesheet">
<script>
jQuery(document).ready(function(){
    jQuery('.div_scrollable_medium tr').each(function(){
        var self = jQuery(this);
        jQuery(this).mouseover(function(){
            self.addClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).addClass('ui-state-hover');
            });
        });
        jQuery(this).mouseout(function(){
            self.removeClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).removeClass('ui-state-hover');
            });
        });
    });
    jQuery('SELECT#userid').change(function(){
        jQuery('SELECT#userid').parents('form').submit();
    });

});
</script>
EOF;

    llxHeader( $js, "Imputations" ) ;

    if ( $fromProj )
    {
	$head = synopsis_project_prepare_head( $projet ) ;
	dol_fiche_head( $head, 'Imputations', $langs->trans( "Project" ) ) ;
	//saveHistoUser( $projet->id, "projet", $projet->ref ) ;
    }
    print "<br/>" ;
    print "<div class='titre'>Imputations projet</div>" ;
    print "<br/>" ;
    print "<br/>" ;


    print '    <div id="struct_main" class="activities">' ;
    print '<p><table width=100%><tr><td style="width:120px;"><b>Format d\'affichage :</b>' ;
    print '          <td><table>' ;
    if ( $format != 'monthly' )
	print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . '&format=monthly&amp;date=' . $date . '">Mensuel</a>' ;
     if ($format != 'biweekly')
      print '                     <tr><td><a href="?'.($fromProj?'fromProjet=1&id='.$_REQUEST['id'].'&':'').'userid='.$userId.'&format=biweekly&amp;date='.$date.'">Bihebdomadaire</a>';
    if ( $format != 'weekly' )
	print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . '&format=weekly&amp;date=' . $date . '">Hebdomadaire</a>' ;
    print '              </table>' ;


    if ( $user->rights->synopsisprojet->voirImputations )
    {
	require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php") ;
	$html = new Form( $db ) ;
	print "<td><form action='?" . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . "format=" . $format . "&date=" . $date . "' method=GET>" ;
	print "<table><tr><td>" ;
	$html->select_users( $userId, 'userid', 1, '', 0, $display = true ) ;
	print "<td><button class='butAction'>OK</button>" ;
	print "</table>" ;
	print "</form>" ;
    }

    print '</table></p>' ;
    print '<form method="post" action="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . '&action=save&format=' . $format . '&date=' . $date . '">' ;
    print '<input type="hidden" name="userid" value="' . $userId . '"></input>' ;
    print '    <div style="width:100%;">' ;
    print '    <table class="calendar" width=100%>' ;
    if ( $user->id == $userId )
	print '     <caption class="ui-state-default ui-widget-header">Mes imputations</caption>' ;
    else
	print '     <caption class="ui-state-default ui-widget-header">Les imputations de ' . $curUser->getNomUrl( 1 ) . '</caption>' ;
    print '       <thead>' ;
    print '         <tr>' ;
    print '           <th class="ui-state-hover ui-widget-header navigation" colspan="2">' ;
    print '                 &nbsp;' ;

    $prevDate = intval( $date - 3600 * 8 * 5 ) ;
    if ( $format == "monthly" )
	$prevDate = strtotime( date( 'Y-m-d', strtotime( date( 'Y', $date ) . "-" . intval( (date( 'm', $date ) - 1 > 9 ? date( 'm', $date ) - 1 : "0" . date( 'm', $date ) - 1 ) ) . "-01" ) ) ) ;
    $nextDate = intval( $date + 3600 * 8 * 5 ) ;
    if ( $format == "monthly" )
	$nextDate = strtotime( date( 'Y-m-d', strtotime( date( 'Y', $date ) . "-" . intval( (date( 'm', $date ) + 1 > 9 ? date( 'm', $date ) + 1 : "0" . date( 'm', $date ) + 1 ) ) . "-01" ) ) ) ;
    print '                 <a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $prevDate . '">' ;
    print '                     <span class="ui-icon ui-icon-arrowthickstop-1-w" title="' . $arrTitleNav[ 'prev' . $format ] . '" style="float:left"></span>' ;
    print '                 </a>' ;
    print '                 <a class="today" href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '">' ;
    print '                     <span class="ui-icon ui-icon-arrowthickstop-1-s" title="Aujourd\'hui" style="float:left"></span>' ;
    print '                 </a>' ;
    print '                 <a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST[ 'id' ] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $nextDate . '">' ;
    print '                     <span class="ui-icon ui-icon-arrowthickstop-1-e" title="' . $arrTitleNav[ 'next' . $format ] . '" style="float:left"></span>' ;
    print '                 </a>' ;
    $arrMonthFR = array( '1' => 'Jan', "2" => "Fev", "3" => "Mar", "4" => "Avr", "5" => "Mai", "6" => "Jun", "7" => "Jui", "8" => "Aou", "9" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec" ) ;
    if ( $format == 'weekly' )
    {
	print '                 Activit&eacute;s de la semaine' . date( 'W', $date ) . ' </th>' ;
    }
    else if ( $format == 'biweekly' )
    {
	print '                 Activit&eacute;s des semaines ' . date( 'W', $date ) . ' - ' . intval( date( 'W', $date ) + 1 ) . ' </th>' ;
    }
    else if ( $format == 'monthly' )
    {
	print '                 Activit&eacute;s du mois de ' . $arrMonthFR[ date( 'n', $date ) ] . ' </th>' ;
    }
    print '             <th class="ui-state-hover ui-widget-header" colspan="1"></th>' ;
    print '             <th class="ui-state-hover ui-widget-header" colspan="2">Total</th>' ;

    $arrNbJour = array( 'monthly' => $monthDur, 'weekly' => 7, "biweekly" => 14 ) ;
    $totalDay = array( ) ;

    $tmpDate = $date ;
    for ( $i = 0 ; $i < $arrNbJour[ $format ] ; $i++ )
    {
	print '<th class="ui-state-hover ui-widget-header day_' . date( 'w', $tmpDate ) . '">' . date( 'd', $tmpDate ) . '</th>' ;
	$tmpDate += 3600 * 24 ;
	$totalDay[ $tmpDate ] = 0 ;
    }
    print "</tr>" ;
    print '<tr>' ;
    print '  <th class="ui-widget-header">' ;
    print '  </th>' ;
    print '  <th class="ui-widget-header">&nbsp;&nbsp;</th>' ;
    print '             <th class="ui-widget-header" title="Restant">Res&nbsp;</th>' ;
    print '             <th class="ui-widget-header">h</th>' ;
    print '             <th class="ui-widget-header">jh</th>' ;
    $tmpDate = $date ;
    $arrJourFR = array( 0 => "Dim", 1 => "Lun", 2 => "Mar", 3 => "Mer", 4 => "Jeu", 5 => "Ven", 6 => "Sam" ) ;
    for ( $i = 0 ; $i < $arrNbJour[ $format ] ; $i++ )
    {
	print '<th class="ui-widget-header day_' . date( 'w', $tmpDate ) . '">' . $arrJourFR[ date( 'w', $tmpDate ) ] . '</th>' ;
	$tmpDate += 3600 * 24 ;
    }
    print "</tr>" ;
    print "</thead>" ;
    print '<tbody class="div_scrollable_medium">' ;
//trouve tous les projet de l'utilisateur ou il a un role
    //print '     <td nowrap class="display_value">testestest</td>' ;
    $requete = "SELECT DISTINCT t.rowid as tid,
                  p.rowid as pid,
                  p.ref as pref,
                  t.title
             FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors AS a,
                  ".MAIN_DB_PREFIX."Synopsis_projet_view AS p,
                  ".MAIN_DB_PREFIX."projet_task AS t
            WHERE p.rowid = t.fk_projet
              AND t.rowid = a.fk_projet_task
              AND a.type = 'user'
              AND t.statut = 'open'
		AND a.fk_user = $userId 
	    ORDER BY p.dateo";
    /*
     *         AND p.fk_statut <> 0
     *         AND p.fk_statut <> 5
     *         AND p.fk_statut <> 50
     *         AND p.fk_statut <> 999
     */
    $sql = $db->query( $requete ) ;
    $remProjId = false ;
    $bool = true ;
    $arrPairImpair[ false ] = "ui-widget-content ui-priority-primary" ;
    $arrPairImpair[ true ] = "ui-widget-content ui-priority-primary ui-state-default" ;
    require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php') ;
    $proj = new Project( $db ) ;
    $arrTaskId = array( ) ;
    $grandTotalLigne = 0 ;
    while ( $res = $db->fetch_object( $sql ) )
    {
	$bool = !$bool ;
	$arrTaskId[ $res->tid ] = $res->tid ;
	print '<tr class="' . $arrPairImpair[ $bool ] . '">' ;
	if ( !$remProjId )
	{
	    print '  <td class="nowrap" colspan="1">' ;
	    $proj->fetch( $res->pid ) ;
	    print "" . $proj->getNomUrl( 1 ) ;
	    $remProjId = $res->pid ;
	    print '  <td class="nowrap" colspan="1">' ;
	}
	else if ( $remProjId != $res->pid )
	{
	    print '  <td class="nowrap" colspan="1">' ;
	    $proj->fetch( $res->pid ) ;
	    print "" . $proj->getNomUrl( 1 ) ;
	    $remProjId = $res->pid ;
	    print '  <td class="nowrap" colspan="1">' ;
	}
	else
	{
	    print '  <td class="nowrap" colspan="1">' ;
	    print '  <td class="nowrap" colspan="1">' ;
	}
	print $res->title ;
	print '     </td>' ;

	$requete1 = "SELECT sum(task_duration) as sumTps
                  FROM ".MAIN_DB_PREFIX."projet_task_time
                 WHERE fk_user = " . $userId . "
                   AND fk_task = " . $res->tid ;
	$sql1 = $db->query( $requete1 ) ;
	$res1 = $db->fetch_object( $sql1 ) ;

	$requete2 = "SELECT sum(task_duration_effective) as sumTps
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective
                 WHERE fk_user = " . $userId . "
                   AND fk_task = " . $res->tid ;
	$sql2 = $db->query( $requete2 ) ;
	$res2 = $db->fetch_object( $sql2 ) ;
	$restant = round( intval( $res1->sumTps - $res2->sumTps ) / 36 ) / 100 ;
	$totalLigne = round( intval( $res2->sumTps ) / 36 ) / 100 ;
	$grandTotalLigne += intval( $res2->sumTps ) / 3600 ;
	$hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY ;
	$totalLignePerDay = round( intval( $res2->sumTps ) / (36 * $hourPerDay) ) / 100 ;

	//Restant
	if ( $restant < 0 )
	    print '     <td nowrap class="display_value error">' . $restant . '</td>' ;
	else
	    print '     <td nowrap class="display_value">' . $restant . '</td>' ;
	//Total h
	print '     <td nowrap class="display_value">' . $totalLigne . '</td>' ;
	//Total jh
	print '     <td nowrap class="display_value">' . $totalLignePerDay . '</td>' ;


	$tmpDate = $date ;
	for ( $i = 0 ; $i < $arrNbJour[ $format ] ; $i++ )
	{
	    $nbHeure = 0 ;
	    $requete = "SELECT (task_duration_effective / 3600) as task_duration_effective
                     FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective as e
                    WHERE fk_task = " . $res->tid . "
                      AND fk_user =" . $userId . "
                      AND task_date_effective = '" . date( 'Y-m-d', $tmpDate ) . " 00:00:00'" ;
	    $sql1 = $db->query( $requete ) ;
	    $res1 = $db->fetch_object( $sql1 ) ;
	    $nbHeure = ($res1->task_duration_effective > 0 ? (round( $res1->task_duration_effective * 100 ) / 100) : 0) ;
	    $totalDay[ $tmpDate ] += $res1->task_duration_effective ;
	    print '     <td class="day_' . date( 'w', $tmpDate ) . '" style="text-align:center;overflow:auto;">' ;
	    print '             <input type="hidden" name="activity_hidden[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" maxlength="1" />' ;
	    print '             <input type="text" name="activity[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" maxlength="1" />' ;
	    print '     </td>' ;
	    $tmpDate += 3600 * 24 ;
	}

	print '    </tr>' ;
    }
    print '    </tbody>' ;

    print "<tfoot>" ;
    print '         <tr>' ;
    print '             <th class="ui-state-default ui-widget-header" colspan=3 align=right>Total&nbsp;' ;

    $hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY ;
    $grandTotalLignePerDay = round( $grandTotalLigne * 100 / $hourPerDay ) / 100 ;
    $grandTotalLigne = round( $grandTotalLigne * 100 ) / 100 ;

//Total h
    print '             <th class="ui-state-default ui-widget-header">' . $grandTotalLigne . '</th>' ;
//Total h/j
    print '             <th class="ui-state-default ui-widget-header">' . $grandTotalLignePerDay . '</th>' ;

    $tmpDate = $date ;
    for ( $i = 0 ; $i < $arrNbJour[ $format ] ; $i++ )
    {
	if ( !$totalDay[ $tmpDate ] > 0 )
	    $totalDay[ $tmpDate ] = 0 ;
	print '<th class="ui-state-default ui-widget-header day_' . date( 'w', $tmpDate ) . '">' . $totalDay[ $tmpDate ] . '</th>' ;
	$tmpDate += 3600 * 24 ;
    }
    print "</tr>" ;

//Total Mois
    $requete = "SELECT sum(task_duration_effective) / 3600 as durEff
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective
             WHERE fk_user = " . $userId . "
               AND month(task_date_effective) = " . date( 'm', $date ) . "
               AND year(task_date_effective) = " . date( 'Y', $date ) . "
               AND fk_task in (" . join( ',', $arrTaskId ) . ")" ;

    $sql = $db->query( $requete ) ;
    $res = $db->fetch_object( $sql ) ;

    $colspan = $arrNbJour[ $format ] - 5 ; // -5 -5 + 5
    print "<tr><td style='padding:10px;' colspan=" . $colspan . "</td>" ;
    print "    <th style='padding:10px;' align='right' class='ui-widget-header ui-state-default' colspan='5'>Total mensuel&nbsp;</td>" ;
    print "    <td align=center style='padding:10px;' class='ui-widget-content' colspan='5'>" . round( $res->durEff * 100 ) / 100 . " h</td>" ;
    print "</tr>" ;

    print "</tfoot>" ;


    print '  </table>' ;

    print "<div class='tabsAction'>" ;
    print "<button class='butAction'>Sauvegarder</button>" ;
    print "</div>" ;
    print "</form>" ;

    print '<table width="500"><tr><td width="50%" valign="top">' ;
    print '<a name="builddoc"></a>' ; // ancre

    /*
     * Documents generes
     *
     */
    $comref = sanitize_string( "Imputations-" . date( 'Y' ) . '-' . $user->login ) ;
    $file = $conf->imputations->dir_output . '/' . $comref . '/' . $comref . '.xlsx' ;
    $relativepath = $comref . '/' . $comref . '.xlsx' ;
    $filedir = $conf->imputations->dir_output . '/' . $comref ;
    $urlsource = $_SERVER[ "PHP_SELF" ] . (isset($_REQUEST[ 'id' ]) ? 'id=' . $_REQUEST[ 'id' ]  : '');
    $genallowed = $user->rights->synopsisprojet->creer ;
    $delallowed = $user->rights->synopsisprojet->supprimer ;

    $modelpdf = "" ;

    $formfile = new FormFile( $db ) ;

    $somethingshown = $formfile->show_documents( 'imputations', "", $filedir, $urlsource, $genallowed, $delallowed, $modelpdf ) ;

//    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$display=true)


    print "</table>" ;
?>