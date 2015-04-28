<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 13 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : admin.php
  *   GLE-1.1
  */
//Set pass for societe

require_once('../main.inc.php');
$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$langs->load("synopsisGene@Synopsis_Tools");

$header = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
$header .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
$header .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';
$header .= ' <script src="'.$jqueryuipath.'/ui.progressbar.js" type="text/javascript"></script>';
$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';

$header .= <<<EOJS


    <script type='text/javascript'>
    jQuery(document).ready(function() {

    jQuery("#changeSocGPI").change(function(){
        var str = "";
        $("select option:selected").each(function () {
            login = $(this).val() ;
        });

        jQuery.ajax({
               type: "POST",
               url: "ajax/getPass.php",
               data: "login="+login,
               success: function(msg){
                if (jQuery(msg).find('pass').text()+"x" != "x")
                {
                    //login
                    jQuery("#newPass").val(jQuery(msg).find('pass').text());

                } else {
                    //try again
                }
               }
       });
   });
});

</script>
EOJS;


llxHeader($header,"",1);



$h=0;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/admin.php';
  $head[$h][1] = $langs->trans('Admin GPI');
  $head[$h][2] = 'Admin GPI';
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/index.php" target=\"_blank';
  $head[$h][1] = $langs->trans('Index GPI  (nouvelle fen&ecirc;tre)');
  $head[$h][2] = 'Index Externe';
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/pilotage.php';
  $head[$h][1] = $langs->trans('Pilotage GPI');
  $head[$h][2] = 'pilotage GPI';
  $h++;

dol_fiche_head($head, 'Admin GPI', $langs->trans("CustomerPilotage"));



$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."societe.nom, ".MAIN_DB_PREFIX."societe.rowid  FROM ".MAIN_DB_PREFIX."contrat, ".MAIN_DB_PREFIX."societe WHERE ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."contrat.fk_soc order by ".MAIN_DB_PREFIX."societe.nom";
$sql = $db->query($requete);
print "<form action='admin.php'>";
print "<input type='hidden' name='action' value='changeMdpGPI'></input>";
print "<table width=600 cellpadding=10><tr><td class='ui-widget-header ui-state-default'>Soci&eacute;t&eacute;</td>
                  <td class='ui-widget-content'><select id='changeSocGPI' name='soc'>";
while ($res = $db->fetch_object($sql))
{
    if ($_REQUEST['soc'] == $res->rowid)
    {
        print "<option SELECTED value='".$res->rowid."'>".$res->nom."</option>";
    } else {
        print "<option value='".$res->rowid."'>".$res->nom."</option>";
    }
}
print "</select></td></tr>";
print "<tr><td class='ui-widget-header ui-state-default'>G&eacute;n&eacute;rer un mot de passe pour cette soci&eacute;t&eacute;</td>
           <td class='ui-widget-content'><input type='checkbox' name='autoPass'></input></td></tr>";
print "<tr><td class='ui-widget-header ui-state-default'>Changer le mot de passe de cette soci&eacute;t&eacute;</td>
           <td class='ui-widget-content'><input type='text' id='newPass' name='newPass'></input></td></tr>";

print "<tr><td colspan=2><button class='ui-state-default ui-widget-header ui-corner-all' style='padding: 5px 10px;'>Soumettre</input>";

if ($_REQUEST['action'] == 'changeMdpGPI')
{
    if ($_REQUEST['autoPass'] == "on")
    {
        $pool[0]=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $pool[1]=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $pool[2]=array('1','2','3','4','5','6','7','8','9');
        $pool[3]=array('@','!','?',';','.');

        array_rand($pool[0]);
        array_rand($pool[1]);
        array_rand($pool[2]);
        array_rand($pool[3]);


        $arrTmp = array(0,1,2,3,0,1,2);
        array_rand($arrTmp);
        $newPass="";
        foreach($arrTmp as $key=>$val)
        {
            if ($val == 0)
            {
                $rand = rand(0,25);
                $newPass .= $pool[0][$rand];
            } else if ($val == 1)
            {
                $rand = rand(0,25);
                $newPass .= $pool[1][$rand];

            } else if ($val == 2)
            {
                $rand = rand(0,9);
                $newPass .= $pool[2][$rand];
            } else if ($val == 3)
            {
                $rand = rand(0,4);
                $newPass .= $pool[3][$rand];
            }
        }
        $requetePre = "SELECT * FROM Babel_financement_access WHERE fk_soc = ".$_REQUEST['soc'];
        $sqlPre = $db->query($requetePre);
        $requete = "";
        if ($db->num_rows($sqlPre) > 0)
        {
            $requete = "UPDATE Babel_financement_access SET password='".$newPass."' WHERE fk_soc = ".$_REQUEST['soc'];
        } else {
            $requete = "INSERT INTO Babel_financement_access (password,fk_soc) VALUES ('".$newPass."',".$_REQUEST['soc'].")";
        }
        //print $db->num_rows($sqlPre);
        $res = $db->query($requete);
        if ($res > 0)
        {
            print "<tr><td></td></tr>";
            print "<tr><td></td></tr>";
            print "<tr><td class='ui-state-default ui-widget-header'>Nouveau mot de passe</td>
                       <td class='ui-widget-content'>".$newPass."</td></tr>";
        }
    } else {
        if (strlen($_REQUEST['newPass']) > 0)
        {
            $requetePre = "SELECT * FROM Babel_financement_access WHERE fk_soc = ".$_REQUEST['soc'];
            //print $requetePre;
            $sqlPre = $db->query($requetePre);
            $requete = "";
            $newPass = utf8_encode($_REQUEST['newPass']);
            if (strlen($newPass) > 5)
            {
                if (preg_match("/^[a-z A-Z 0-9 ! @ _ \- = \+ \/ . ; , %  \$ \* ]*$/",$newPass))
                {
                    if ($db->num_rows($sqlPre) > 0)
                    {
                        $requete = "UPDATE Babel_financement_access SET password='".$_REQUEST['newPass']."' WHERE fk_soc = ".$_REQUEST['soc'];
                    } else {
                        $requete = "INSERT INTO Babel_financement_access (password,fk_soc) VALUES ('".$newPass."',".$_REQUEST['soc'].")";
                    }
                    $res = $db->query($requete);
                    if ($res > 0)
                    {
                        print "<tr><td></td></tr>";
                        print "<tr><td></td></tr>";
                        print "<tr><td class='ui-widget-header ui-state-default'>Nouveau mot de passe</td>
                                   <td class='ui-widget-content'>".utf8_decode($newPass)."</td></tr>";
                    }
                } else {
                    print "<tr><td></td></tr>";
                    print "<tr><td></td></tr>";
                    print "<tr><td coslpan='2' class='ui-widget-content ui-state-error'>Le nouveau mot de passe n'a pas &eacute;t&eacute; mis en place : Les caract&egrave;res permis sont : <br><ul><li>les caract&egrave;res de A &agrave; Z (majuscules et minuscules),<li>les chiffres <li>les caract&egrave;res ! @ _ - = + / . ; , ".htmlentities('%')." $ * </ul></td></tr>";
                }
            } else {
                    print "<tr><td></td></tr>";
                    print "<tr><td></td></tr>";
                    print "<tr><td coslpan='2' class='ui-widget-content ui-state-error'>Le nouveau mot de passe n'a pas &eacute;t&eacute; mis en place car il doit faire au moins 6 caract&egrave;res</td></tr>";
            }
        } else {
            print "<tr><td></td></tr>";
            print "<tr><td coslpan='2' class='ui-widget-content ui-state-error'>Le nouveau mot de passe n'a pas &eacute;t&eacute; mis en place</td></tr>";
        }
    }

}

print "</table>";

print "</form>";

function llxHeader($head = "", $urlp = "",$disableScriptaculous=0)
{
    global $user, $conf, $langs;
    $langs->load("synopsisGene@Synopsis_Tools");

    top_menu($head,"Admin GPI","",1);
    left_menu($menu->liste);

}
?>
