<?php
/*
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
 */

/**
        \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
        \version    $Revision: 1.23 $
*/
$debug=true;

require("./pre.inc.php");
//require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/BabelPrime/fct_BabelPrime.php');
if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$def = array();


/**
 * Affichage du formulaire de saisie
 */

llxHeader();
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Prime"),$linkback,'setup');

//javascript
print<<<EOF
<script type='text/javascript'>
function ChangeProfils(pObj)
{
    var url=location.href;
        url = window.location.protocol +'//'+ window.location.host +''+ window.location.pathname;
        //alert (url1);
        url += "?Profilid=";
    var selected = "";
    for (var i=0;i<pObj.options.length;i++)
    {
        if (pObj.options[i].selected)
        {
            if (pObj.options[i].value == "0") //reset
            {
                document.location.href = url;
            } else {
                selected = pObj.options[i].value;
            }
        }
    }
    url+=selected;
    document.location.href = url;
}
</script>
EOF;

//print_titre("Gestion des primes");
print '<br>';

//Modele:
$action=$_GET['Action'];
if ("x".$action == "x") $action= $_POST['Action'];


$ProfilId=$_GET['Profilid'];
if ("x".$ProfilId == "x") $ProfilId= $_POST['Profilid'];

if ($debug)
{
    echo "Action: ".$action."<BR>";    echo "ProfilId: ".$ProfilId."<BR>";
}



if ($action == "del")
{
    delete_all($ProfilId,$db);
    $requeteDel = "delete from Babel_Prime_Profil where id =".$ProfilId;
    $db->query($requeteDel);
    print "<script type='text/javascript'>  var url = window.location.protocol +'//'+ window.location.host +''+ window.location.pathname;  //document.location.href = url; </script>";
}


if ($action == "SaveSql")
{
    //    $ProfilId="";
//        echo '<table>';
//        foreach ($_POST as $key=>$val)
//        {
//            echo "<tr><th>".$key."<td>".$val;
//        }
//        echo '</table>';

        $NewProfil = $_POST['NewProfils'];
        $requete = " SELECT id, " .
                "           nameShort " .
                "      FROM Babel_Prime_CatPN";
        $sqlSsql1=$db->query($requete);
        $arr = array();
        while ($obj=$db->fetch_object($sqlSsql1))
        {
            $arr[$obj->nameShort]=$obj->id;
        }

        $pourcent[$arr['Mat']]['Min'] = traitementPourcent($_POST['edit-Pourcent-Mat-Min']);
        $pourcent[$arr['Mat']]['Max'] = traitementPourcent($_POST['edit-Pourcent-Mat-Max']);
        $pourcent[$arr['Mat']]['Ann'] = traitementPourcent($_POST['edit-Pourcent-Mat-Ann']);

        $pourcent[$arr['Soft']]['Min'] = traitementPourcent($_POST['edit-Pourcent-Soft-Min']);
        $pourcent[$arr['Soft']]['Max'] = traitementPourcent($_POST['edit-Pourcent-Soft-Max']);
        $pourcent[$arr['Soft']]['Ann'] = traitementPourcent($_POST['edit-Pourcent-Soft-Ann']);

        $pourcent[$arr['Serv']]['Min'] = traitementPourcent($_POST['edit-Pourcent-Serv-Min']);
        $pourcent[$arr['Serv']]['Max'] = traitementPourcent($_POST['edit-Pourcent-Serv-Max']);
        $pourcent[$arr['Serv']]['Ann'] = traitementPourcent($_POST['edit-Pourcent-Serv-Ann']);

        $pourcent[$arr['ServRec']]['Min'] = traitementPourcent($_POST['edit-Pourcent-ServRec-Min']);
        $pourcent[$arr['ServRec']]['Max'] = traitementPourcent($_POST['edit-Pourcent-ServRec-Max']);
        $pourcent[$arr['ServRec']]['Ann'] = traitementPourcent($_POST['edit-Pourcent-ServRec-Ann']);


        $profil['A0']['SalaireFixe']=$_POST['A0edit-Profil-SalaireFixe'];
        $profil['A0']['SalaireVar']=$_POST['A0edit-Profil-SalaireVar'];

        $profil['A0']['ObjectifTot']=$_POST['A0edit-Profil-ObjectifTot'];

        $profil['A0']['SeuilPrime']['1']= traitementPourcent($_POST['A0edit-Profil-Seuil-Prime1']);
        $profil['A0']['SeuilPrime']['2']= traitementPourcent($_POST['A0edit-Profil-Seuil-Prime2']);
        $profil['A0']['SeuilPrime']['3']= traitementPourcent($_POST['A0edit-Profil-Seuil-Prime3']);
        $profil['A0']['SeuilPrime']['4']= traitementPourcent($_POST['A0edit-Profil-Seuil-Prime4']);

        $profil['A0']['SeuilObjectif']['1']= traitementPourcent($_POST['A0edit-Profil-SeuilObjectif1']);
        $profil['A0']['SeuilObjectif']['2']= traitementPourcent($_POST['A0edit-Profil-SeuilObjectif2']);
        $profil['A0']['SeuilObjectif']['3']= traitementPourcent($_POST['A0edit-Profil-SeuilObjectif3']);
        $profil['A0']['SeuilObjectif']['4']= traitementPourcent($_POST['A0edit-Profil-SeuilObjectif4']);

        $profil['A1']['SalaireFixe']=$_POST['A1edit-Profil-SalaireFixe'];
        $profil['A1']['SalaireVar']=$_POST['A1edit-Profil-SalaireVar'];

        $profil['A1']['ObjectifTot']=$_POST['A1edit-Profil-ObjectifTot'];

        $profil['A1']['SeuilPrime']['1']= traitementPourcent($_POST['A1edit-Profil-Seuil-Prime1']);
        $profil['A1']['SeuilPrime']['2']= traitementPourcent($_POST['A1edit-Profil-Seuil-Prime2']);
        $profil['A1']['SeuilPrime']['3']= traitementPourcent($_POST['A1edit-Profil-Seuil-Prime3']);
        $profil['A1']['SeuilPrime']['4']= traitementPourcent($_POST['A1edit-Profil-Seuil-Prime4']);

        $profil['A1']['SeuilObjectif']['1']= traitementPourcent($_POST['A1edit-Profil-SeuilObjectif1']);
        $profil['A1']['SeuilObjectif']['2']= traitementPourcent($_POST['A1edit-Profil-SeuilObjectif2']);
        $profil['A1']['SeuilObjectif']['3']= traitementPourcent($_POST['A1edit-Profil-SeuilObjectif3']);
        $profil['A1']['SeuilObjectif']['4']= traitementPourcent($_POST['A1edit-Profil-SeuilObjectif4']);


        //$NewProfil = "test";
        if ("x".$NewProfil != "x")
        {//ajoute un profil
            $requete = "INSERT into Babel_Prime_Profil (name) VALUES ('".$NewProfil."')";
            $sql=$db->query($requete);
            $ProfilId = $db->last_insert_id("Babel_Prime_Profil");

        } else {
            //modifie un profil
                $ProfilId = $_POST['Profils'];
                //Delete tout ce qui a atrait au profil dans Babel_Prime_Profil_details, Babel_Prime_FourchPN, Babel_Prime_Seuil, Babel_Prime_Li_Profil_details_Seuil, Babel_Prime_Li_Profil_details_Seuil
                delete_all($ProfilId,$db);
        }

        //on remplit Babel_Prime_Profil_details
        $requete2 = "INSERT INTO Babel_Prime_Profil_details (profil_refid,  Annee1, SalaireFixe, SalaireVar, ObjectifTot)" .
                "         VALUES ('".$ProfilId."','0','".$profil['A0']['SalaireFixe']."','".$profil['A0']['SalaireVar']."','".$profil['A0']['ObjectifTot']."')";
        $sqlP1=$db->query($requete2);
        //echo "$requete2<BR>";
        $ProfilDetA0Id = $db->last_insert_id("Babel_Prime_Profil_details");

        $requete2b = "INSERT INTO Babel_Prime_Profil_details (profil_refid,  Annee1, SalaireFixe, SalaireVar, ObjectifTot)" .
                "            VALUES ('".$ProfilId."','1','".$profil['A1']['SalaireFixe']."','".$profil['A1']['SalaireVar']."','".$profil['A1']['ObjectifTot']."')";
//        echo "$requete2b<BR>";
        $sqlP3=$db->query($requete2b);
        $ProfilDetA1Id = $db->last_insert_id("Babel_Prime_Profil_details");

        $arr_Cat_Fourch=array(); //lie la categorie avec l'id SQL de la fourchette
        //on remplit Babel_Prime_FourchPN
        foreach ($pourcent as $keyVal=>$ValVal)
        {
            $requete4="INSERT INTO Babel_Prime_FourchPN ( Babel_Prime_CatPN_refid, pourcentMin , pourcentMax, pourcentAnn) " .
                    "       VALUES ('".$keyVal."','".$ValVal['Min']."','".$ValVal['Max']."','".$ValVal['Ann']."') ";
            $sqlP4=$db->query($requete4);
            //echo "$requete4<BR>";
            $arr_Cat_Fourch[$keyVal]=$db->last_insert_id("Babel_Prime_FourchPN");
        }
        //on remplit Babel_Prime_Seuil
        //année 1
        $arr_A1_Prime_Seuil = array();
        foreach ($profil['A1']['SeuilPrime'] as $keyVal=>$ValVal)
        {
            $PNSeuil = $profil['A1']['SeuilPrime'][$keyVal];
            $objSeuil = $profil['A1']['SeuilObjectif'][$keyVal];
            //EOS EOS => objSeuil => vaut tjrs 0
            $requete5="INSERT INTO Babel_Prime_Seuil ( level, SeuilPrime , SeuilObj ) " .
                    "       VALUES ('". $keyVal ."','".$PNSeuil."','".$objSeuil."') ";
            $sql5=$db->query($requete5);
//            echo "$requete5<BR>";
            $arr_A1_Prime_Seuil[$keyVal]=$db->last_insert_id("Babel_Prime_Seuil");

        }
        //année n
        $arr_A0_Prime_Seuil = array();
        foreach ($profil['A0']['SeuilPrime'] as $keyVal=>$ValVal)
        {
            $PNSeuil = $profil['A0']['SeuilPrime'][$keyVal];
            $objSeuil = $profil['A0']['SeuilObjectif'][$keyVal];
            $requete6="INSERT INTO Babel_Prime_Seuil ( level, SeuilPrime , SeuilObj ) " .
                    "       VALUES ('". $keyVal ."','".$PNSeuil."','".$objSeuil."') ";
            $sql6=$db->query($requete6);
//            echo "$requete6<BR>";
            $arr_A0_Prime_Seuil[$keyVal]=$db->last_insert_id("Babel_Prime_Seuil");

        }

        //on remplit Babel_Prime_Li_Profil_details_Seuil

        //Annee 1
        foreach ($arr_A1_Prime_Seuil as $keyVal=>$ValVal)
        {
            $requete7a="INSERT INTO Babel_Prime_Li_Profil_details_Seuil ( Seuil_refid, Profil_details_refid ) " .
                    "       VALUES ('".$ValVal."','".$ProfilDetA1Id."') ";
            $sql7a=$db->query($requete7a);
//            echo "$requete7a<BR>";
        }


        //Annee n
        foreach ($arr_A0_Prime_Seuil as $keyVal=>$ValVal)
        {
            $requete7b="INSERT INTO Babel_Prime_Li_Profil_details_Seuil ( Seuil_refid, Profil_details_refid ) " .
                    "        VALUES ('".$ValVal."','".$ProfilDetA0Id."') ";
            $sql7b=$db->query($requete7b);
//            echo "$requete7b<BR>";
        }


        //on remplit Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil
        foreach ($arr_Cat_Fourch as $keyVal=>$ValVal)
        {
            $requete7c="INSERT INTO Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil ( FourchPN_refid, Profil_refid ) " .
                    "       VALUES ('".$ValVal."','".$ProfilId."') ";
            $sql7c=$db->query($requete7c);
//            echo "$requete7c<BR>";
        }


//si newProfils => ajoute Profils, recupere id
//sinon, on prend le profil et on ajoute les donnees dans les tables


}


print '<form name="BabelPrime" action="BabelPrime.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";

//get value from Database
    $Getprofilid = "";
    if ("x".$_GET['Profilid'] == "x")
    {
        $Getprofilid = $_POST['Profilid'];
        if ("x".$_POST["Profilid"] == "x")
        {
            $requete = "SELECT min(id) as defid FROM Babel_Prime_Profil";
            $presql=$db->query($requete);
            $obj=$db->fetch_object($presql);
            $Getprofilid = $obj->defid;
        }
    } else {
        $Getprofilid = $_GET['Profilid'];
    }


    $requete = " SELECT Babel_Prime_Profil.name as Pname," .
            "           Babel_Prime_Profil_details.Annee1 as PdetAnnee," .
            "           Babel_Prime_Profil_details.SalaireFixe as PdetSalaireFixe,      " .
            "           Babel_Prime_Profil_details.SalaireVar as PdetSalaireVar,      " .
            "           Babel_Prime_Profil_details.ObjectifTot as PdetObjectifTot      " .
            "      FROM Babel_Prime_Profil " .
            " LEFT JOIN (Babel_Prime_Profil_details) on Babel_Prime_Profil_details.profil_refid = Babel_Prime_Profil.id  " .
            "     WHERE Babel_Prime_Profil.id = ".$Getprofilid ;
            //echo $requete."<BR>";
    $msql = $db->query($requete);
//    $i_tmp=$db->num_rows($msql);
    $init = 0;

    while ($objabc = $db->fetch_object($msql))
    //for ($j_tmp=0;$j_tmp<$i_tmp;$j_tmp++)
    {

      //  $objabc = $db->fetch_object($msql);
        $profileName = $objabc->Pname;
        //echo 'tt' . $objabc->Pname . ' tt ';

        if ($init == 0)
        {
            $init =1;
            $salaireFixe[0] = 17000;
            $salaireVar[0] = 12000;

            $salaireFixe[1] = 12000;
            $salaireVar[1] = 24000;

            $ObjectifTot[0] = 110000;
            $ObjectifTot[1] = 120000;

            $pourcentMin['Mat']=0.1;
            $pourcentMin['Soft']=0.1;
            $pourcentMin['Serv']=0.1;
            $pourcentMin['ServRec']=0.1;

            $pourcentMax['Mat']=0.5;
            $pourcentMax['Soft']=0.5;
            $pourcentMax['Serv']=0.5;
            $pourcentMax['ServRec']=0.5;

            $pourcentAnn['Mat']=0.3;
            $pourcentAnn['Soft']=0.3;
            $pourcentAnn['ServRec']=0.3;
            $pourcentAnn['ServRec']=0.3;

            $seuil['0']['1']['Prime']=0.4;
            $seuil['0']['2']['Prime']=0.6;
            $seuil['0']['3']['Prime']=1;
            $seuil['0']['4']['Prime']=1.2;

            $seuil['1']['1']['Prime']=0.4;
            $seuil['1']['2']['Prime']=0.6;
            $seuil['1']['3']['Prime']=1;
            $seuil['1']['4']['Prime']=1.2;

            $seuil['0']['1']['Objectif']=0.5;
            $seuil['0']['2']['Objectif']=0.75;
            $seuil['0']['3']['Objectif']=1;
            $seuil['0']['4']['Objectif']=1.2;

            $seuil['1']['1']['Objectif']=0.5;
            $seuil['1']['2']['Objectif']=0.75;
            $seuil['1']['3']['Objectif']=1;
            $seuil['1']['4']['Objectif']=1.2;
        }

        if ("x".$profileName != "x" || $ProfilId != '-1') //existant
        {
            $salaireFixe[$objabc->PdetAnnee] = $objabc->PdetSalaireFixe;
            $salaireVar[$objabc->PdetAnnee] = $objabc->PdetSalaireVar;

            $ObjectifTot[$objabc->PdetAnnee] = $objabc->PdetObjectifTot;


            //Fourchette
            $requete1 = "SELECT Babel_Prime_FourchPN.pourcentMin as FourchpourcentMin,      " .
                    "           Babel_Prime_FourchPN.pourcentMax as FourchpourcentMax,      " .
                    "           Babel_Prime_FourchPN.pourcentAnn as FourchpourcentAnn,      " .
                    "           Babel_Prime_CatPN.nameShort as PNCat_shortName      " .
                    "      FROM Babel_Prime_FourchPN," .
                    "           Babel_Prime_CatPN," .
                    "           Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil " .
                    "     WHERE Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil.FourchPN_refid = Babel_Prime_FourchPN.id  " .
                    "       AND Babel_Prime_CatPN.id = Babel_Prime_FourchPN.Babel_Prime_CatPN_refid".
                    "      AND Profil_refid = ".$Getprofilid;
            $sql1 = $db->query($requete1);
            // echo $requete1;
            while ($obj1 = $db->fetch_object($sql1))
            {
                $pourcentAnn[$obj1->PNCat_shortName]=$obj1->FourchpourcentAnn;
                $pourcentMax[$obj1->PNCat_shortName]=$obj1->FourchpourcentMax;
                $pourcentMin[$obj1->PNCat_shortName]=$obj1->FourchpourcentMin;
            }

            //Seuil

                    $requete2 = "SELECT Babel_Prime_Seuil.SeuilPrime, " .
                            "           Babel_Prime_Seuil.SeuilObj,     " .
                            "           Babel_Prime_Seuil.level,     " .
                            "           Babel_Prime_Profil_details.Annee1     " .
                            "      FROM Babel_Prime_Seuil," .
                            "           Babel_Prime_Profil_details," .
                            "           Babel_Prime_Li_Profil_details_Seuil " .
                            "     WHERE Babel_Prime_Li_Profil_details_Seuil.Profil_details_refid = Babel_Prime_Profil_details.id  " .
                            "       AND Babel_Prime_Li_Profil_details_Seuil.Seuil_refid = Babel_Prime_Seuil.id".
                            "       AND Babel_Prime_Profil_details.profil_refid = ".$Getprofilid;
            $sql1a = $db->query($requete2);
            //echo "<BR>".$requete2."<BR>";
            while ($obj1 = $db->fetch_object($sql1a))
            {
                $seuil[$obj1->Annee1][$obj1->level]['Prime']=$obj1->SeuilPrime;
                $seuil[$obj1->Annee1][$obj1->level]['Objectif']=$obj1->SeuilObj;
            }
        }
    }

if ($action == "Edit" || ($ProfilId == "-1" ))
{

    print "   <tr class='liste_titre'>\n";
    print "       <td style='width:50%;' colspan=4>Selection Profil</td>\n";
    print "   </tr>\n";
    print "   <tr class='impair'>\n";


    print "   <td name='Profils' colspan=2 >\n";
    print "       <SELECT onChange=\"ChangeProfils(this);\" Name='Profils'  id='Profils'>\n";
    $requete = "SELECT * from Babel_Prime_Profil";
    $sql=$db->query($requete);
    print "           <option value='0' >Select-></option>\n";
    while ($obj=$db->fetch_object($sql))
    {
        if ($obj->id == $ProfilId)//EOS EOS
        {
            print "           <option SELECTED value='".$obj->id."' >".$obj->name."</option>\n";
        } else {
            print "           <option value='".$obj->id."' >".$obj->name."</option>\n";
        }
    }
    print "           <option value='-1' >New...</option>\n";


    print "       </SELECT>\n <SPAN class='button'  onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";'  style='padding-top: 3px; padding-bottom: 2px; padding-left: 10px; padding-right: 10px;' onClick=\"ChangeProfils(document.getElementById('Profils'));\" >OK >></SPAN>"."<SPAN> Profils:".$ProfilId."  </SPAN>";
    print "   </td>\n";
    print "   <td colspan=1> Nouveau Profil</td><td>\n";
    print "<input type='text' style='text-align=\"center\";' name='NewProfils' id='NewProfils' value=''/>\n";
    print "</tr>\n";
    print "<tr><td>&nbsp</tr>\n";
    print "<tr>\n";
    print "   <th align='center' name='headertable' >&nbsp;</th>\n";
    print "   <th align='center' name='headertable' >Min</th>\n";
    print "   <th align='center' name='headertable' >Max</th>\n";
    print "   <th align='center' name='headertable' >Communiqu&eacute;</th>\n";
    print "</tr>\n";
    print_pourcent($db,0,1,$pourcentMin,$pourcentMax,$pourcentAnn);

    print "<tr><td>&nbsp</tr>\n";
    print_Salaire(1,"1",$salaireFixe[1],$salaireVar[1],$ObjectifTot[1],1);
    print "</tr>\n";
    print_Seuil(1,1,1,&$seuil);
    print "<tr><td>&nbsp</tr>\n";
    print_Salaire(0,"n",$salaireFixe[0],$salaireVar[0],$ObjectifTot[0],1);
    print_Seuil(0,1,1,&$seuil);
    print "</tr>\n";
    print "<tr><td>&nbsp</tr>\n";

    print "<tr class='liste_titre'>\n";
    print "   <td colspan=4 align=right>\n";
    print "   <input type='hidden' name='Action' id='Action' value='SaveSql'/>\n";
    print "   <input type='hidden' name='Profilid' value='".$ProfilId."'/>\n";
    print "<input  onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";' style='padding: 3px; padding-left: 10px; padding-right: 10px;' type=\"submit\" onClick=\"document.getElementById('Action').value='SaveSql';\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print "<input  onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";' style='padding: 3px; padding-left: 10px; padding-right: 10px;' type=\"submit\" onClick=\"document.getElementById('Action').value='';\" name=\"reset\" class=\"button\" value=\"".$langs->trans("Reset")."\">";
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print "</tr>\n";
    print "</TABLE>";


} else { //pas edit


    print "   <tr class='liste_titre'>\n";
    print "       <td style='width:50%;' colspan=4>Selection Profil</td>\n";
    print "   </tr>\n";
    print "   <tr class='impair'>\n";


    print "   <td name='Profils' colspan=4 >\n";
    print "       <SELECT  onChange=\"ChangeProfils(this);\"  Name='Profils'  id='Profils'>\n";
    $requete = "SELECT * from Babel_Prime_Profil";
    $sqla=$db->query($requete);
    print "           <option value='0' >Select-></option>\n";
    while ($obj=$db->fetch_object($sqla))
    {
        if ($obj->id == $_GET['Profilid'])
        {
            print "           <option SELECTED value='".$obj->id."' >".$obj->name."</option>\n";
        } else {
            print "           <option value='".$obj->id."' >".$obj->name."</option>\n";
        }
    }

    print "           <option value='-1' >New...</option>\n";
    print "       </SELECT>\n<SPAN class='button' onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";' style='padding-top: 3px; padding-bottom: 2px; padding-left: 10px; padding-right: 10px;' onClick=\"ChangeProfils(document.getElementById('Profils'));\" >OK >></SPAN>";
    print "   </td>\n";
    print " </tr>\n";
    print " <tr/>\n";
    print " <tr>\n";
    print "   <th align='center' name='headertable' >&nbsp;</th>\n";
    print "   <th align='center' name='headertable' >Min</th>\n";
    print "   <th align='center' name='headertable' >Max</th>\n";
    print "   <th align='center' name='headertable' >Communiqu&eacute;</th>\n";
    print " </tr>\n";
if ($debug)
{
    echo "Action: ".$action."<BR>";    echo "ProfilId: ".$ProfilId."<BR>";
}
    print_pourcent($db,0,0,$pourcentMin,$pourcentMax,$pourcentAnn);

    print " <tr><td>&nbsp</tr>\n";
    print_Salaire(1,"1",$salaireFixe[1],$salaireVar[1],$ObjectifTot[1],0);
    print_Seuil(1,1,0,&$seuil);
    print " <tr><td>&nbsp</tr>\n";
    print_Salaire(0,"n",$salaireFixe[0],$salaireVar[0],$ObjectifTot[0],0);
    print_Seuil(0,1,0,&$seuil);
    print " <tr><td>&nbsp</tr>\n";

    print "<tr class='liste_titre'>\n";
    print "   <td colspan=4 align=right>\n";
    print "   <input type='hidden' name='Action' id='Action' value='SaveSql'/>\n";
    print "   <input type='hidden' name='Profilid' value='".$ProfilId."'/>\n";
    print "<input onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";'  style='padding: 3px; padding-left: 10px; padding-right: 10px;' type=\"submit\" onClick=\"document.getElementById('Action').value='Edit';\" name=\"Edit\" class=\"button\" value=\"".$langs->trans("Edit")."\">";
    print "<input  onMouseOver='this.style.textDecoration=\"underline\"; this.style.cursor=\"Pointer\";' onMouseOut='this.style.textDecoration=\"none\"; this.style.cursor=\"none\";'  style='padding: 3px; padding-left: 10px; padding-right: 10px;' type=\"submit\" onClick=\"document.getElementById('Action').value='del';\" name=\"delete\" class=\"button\" value=\"".$langs->trans("Delete")."\">";
    print "</tr>\n";
    print "</TABLE>";


}

print "</form>\n";

clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
