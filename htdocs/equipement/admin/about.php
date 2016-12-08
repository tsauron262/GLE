<?php
/* Copyright (C) 2014-2016	Charlie BENKE	 <charlie@patas-monkey.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		htdocs/equipement/admin/about.php
 * 	\ingroup	factory
 * 	\brief		about page
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory


// Libraries
dol_include_once("/equipement/core/lib/equipement.lib.php");


// Translations
$langs->load("equipement@equipement");

// Access control
if (!$user->admin)
	accessforbidden();

/*
 * View
 */
$page_name = $langs->trans("EquipementSetup") ." - ". $langs->trans("About");
llxHeader('', $page_name);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name , $linkback, 'title_setup');

// Configuration header
$head = equipement_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Equipement"), 0, "equipement@equipement");

// About page goes here
print '<br>';
print $langs->trans("PatasMonkeyPresent").'<br><br>';

$urlmonkey='http://www.patas-monkey.com';
print '<a href="'.$urlmonkey.'" target="_blank"><img border="0" width="180" src="'.dol_buildpath('/equipement/img/patas-monkey_logo.png',1).'"></a>';


print '<br><br>';
print $langs->trans("MoreModulesLink").'<br>';
$url='http://www.dolistore.com/search.php?search_query=benke';
print '<a href="'.$url.'" target="_blank"><img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a><br><br><br>';
print '<br><br>';

print_titre($langs->trans("Changelog"));
print '<br>';

$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
$changelog = @file_get_contents(str_replace("www","dlbdemo",$urlmonkey).'/htdocs/custom/equipement/changelog.xml',false,$context);
if($changelog === FALSE)	// not connected
{
	$tblversionslast=array();
}
else
{
	$sxelast = simplexml_load_string(nl2br ($changelog));
	if ($sxelast === false) 
	{
		$tblversionslast=array();
	}
	else
		$tblversionslast=$sxelast->Version;
}

	libxml_use_internal_errors(true);
	$sxe = simplexml_load_string(nl2br (file_get_contents('../changelog.xml')));
	if ($sxe === false) 
	{
		echo "Erreur lors du chargement du XML\n";
		foreach(libxml_get_errors() as $error) 
		{
			print $error->message;
		}
		exit;
	}
	else
		$tblversions=$sxe->Version;

	print '<table class="noborder" >';
	print '<tr class="liste_titre">';
	print '<th align=center width=100px>'.$langs->trans("NumberVersion").'</th>';
	print '<th align=center width=100px>'.$langs->trans("MonthVersion").'</th>';
	print '<th align=left >'.$langs->trans("ChangesVersion").'</th></tr>' ;
	$var = true;

	//
	if (count($tblversionslast) > count($tblversions))
	{
		// il y a du nouveau
		for ($i = count($tblversionslast)-1; $i >=0; $i--)
		{
			$var = ! $var;
			$color="";
			$xpath = '//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]';
			$tmp = $sxelast->xpath($xpath);
			if (empty($tmp))
				$color=" bgcolor=#FF6600 ";
			print "<tr $bc[$var]>";
			print '<td align=center '.$color.' valign=top>'.$tblversionslast[$i]->attributes()->Number.'</td>';
			print '<td align=center '.$color.' valign=top>'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>' ;
			$lineversion=$tblversionslast[$i]->change;
			print '<td align=left '.$color.' valign=top>';
			//var_dump($lineversion);
			foreach($lineversion as $changeline)
			{
				print $changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';
			}
			print '</td></tr>';
		}

	}
	elseif (count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0 )
	{	// version exp�rimentale
		for ($i = count($tblversions)-1; $i >=0; $i--)
		{
			$var = ! $var;
			$color="";
			$xpath = '//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]';
			if (empty($sxelast->xpath($xpath)))
				$color=" bgcolor=lightgreen "; 
			print "<tr $bc[$var]>";
			print '<td align=center '.$color.' valign=top>'.$tblversions[$i]->attributes()->Number.'</td>';
			print '<td align=center '.$color.' valign=top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>' ;
			$lineversion=$tblversions[$i]->change;
			print '<td align=left '.$color.' valign=top>';
			//var_dump($lineversion);
			foreach($lineversion as $changeline)
			{
				print $changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';
			}
			print '</td></tr>';
		}
	}
	else
	{	//on est � jour des versions ou pas de connection internet 
		for ($i = count($tblversions)-1; $i >=0; $i--)
		{
			$var = ! $var;
			print "<tr $bc[$var]>";
			print '<td align=center valign=top>'.$tblversions[$i]->attributes()->Number.'</td>';
			print '<td align=center valign=top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>' ;
			$lineversion=$tblversions[$i]->change;
			print '<td align=left valign=top>';
			//var_dump($lineversion);
			foreach($lineversion as $changeline)
			{
				print $changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';
			}
			print '</td></tr>';
		}
	}
	print '</table><br>';


llxFooter();
$db->close();
?>