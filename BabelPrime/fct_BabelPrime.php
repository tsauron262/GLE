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




function traitementPourcent($in)
{
    $out = floatval($in);
    if ($out > 1) $out /= 100;
    $out = preg_replace('/\,/','.',$out);
    return($out);
}
function traitementFloat($in)
{
    $out = $in * 100;
    $out .="%";
    return($out);

}
function delete_all($ProfilId,$db)
{
    $arr_Fourch = array();
            $arr_LiFourch = array();
            //Fourchette
            $requeteDel1 = "SELECT Babel_Prime_FourchPN.id as Fid ," .
                    "              Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil.id AS liFid" .
                    "         FROM Babel_Prime_FourchPN," .
                    "              Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil " .
                    "        WHERE Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil.FourchPN_refid = Babel_Prime_FourchPN.id" .
                    "          AND Profil_refid =  " . $ProfilId;
            //echo "<BR>".$requeteDel1;
            $sqlDel1 = $db->query($requeteDel1);
            $i=0;
            while ($objDel1=$db->fetch_object($sqlDel1))
            {
                $arr_Fourch[$i]=$objDel1->Fid;
                $arr_LiFourch[$i]=$objDel1->liFid;
                $i++;
            }

            //Details Profils
            $requeteDel2 = "SELECT id " .
                    "         FROM Babel_Prime_Profil_details " .
                    "        WHERE Babel_Prime_Profil_details.profil_refid =  ".$ProfilId;
            //echo "<BR>".$requeteDel2;
            $sqlDel2 = $db->query($requeteDel2);
            $arr_DetProfil=array();
            $i=0;
            while($objDel2 = $db->fetch_object($sqlDel2))
            {
                $arr_DetProfil[$i]=$objDel2->id;
                $i++;
            }

            //Seuil
            $arr_Seuil = array();
            $arr_LiSeuil = array();
            foreach($arr_DetProfil as $key=>$val)
            {
                $requeteDel3 = "SELECT Babel_Prime_Li_Profil_details_Seuil.id AS LiId," .
                        "              Seuil_refid AS Sid" .
                        "         FROM Babel_Prime_Li_Profil_details_Seuil," .
                        "              Babel_Prime_Seuil" .
                        "        WHERE Babel_Prime_Li_Profil_details_Seuil.Seuil_refid = Babel_Prime_Seuil.id " .
                        "          AND Profil_details_refid =  ".$val;
                $sqlDel3 = $db->query($requeteDel3);
                $i=0;
                //echo "<BR>".$requeteDel3;
                while($objDel3 = $db->fetch_object($sqlDel3))
                {
                    $arr_Seuil[$i]=$objDel3->Sid;
                    $arr_LiSeuil[$i]=$objDel3->LiId;
                    $i++;
                }
            }
            //On efface:
            foreach ($arr_LiSeuil as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_Li_Profil_details_Seuil WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }
            foreach ($arr_Seuil as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_Seuil WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }
            foreach ($arr_DetProfil as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_Profil_details WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }
            foreach ($arr_LiFourch as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }
            foreach ($arr_Fourch as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_FourchPN WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }
            foreach ($arr_DetProfil as $key=>$val)
            {
                $requeteDelete1 = "DELETE FROM Babel_Prime_Profil_details WHERE id=".$val;
                //echo "<BR>".$requeteDelete1 .";<BR>";
                $db->query($requeteDelete1);
            }

}
function print_Salaire($pyear,$yearText,$psalaireFixe,$psalaireVar,$pObjectifTot,$pedit)
{
    print "   <tr class='liste_titre'>\n";
    print "       <td style='width:50%;' colspan=4>Profil Ann&eacute;e ".$yearText."</td>\n";
    print "   </tr>\n";
    print "   <tr class='pair'>\n";
    print "   <th align='left' name='headerProfil-SalaireFixe' >Salaire Fixe</th>\n";
    print "   <td align='center' name='Profil-SalaireFixe' >";
    if ($pedit == 1)
    {
        print "     <input type='text' style='text-align=\"center\";' name='A".$pyear."edit-Profil-SalaireFixe' id='A".$pyear."edit-Profil-SalaireFixe' value='".$psalaireFixe."'/>";
    } else {
        print $psalaireFixe;
    }

    print "   </td>\n";
    print "   <th align='left' name='headerProfilSalaireVar' >Salaire Variable</th>\n";
    print "   <td align='center' name='Profil-SalaireVar' >";
    if ($pedit == 1)
    {
        print "     <input type='text' style='text-align=\"center\";' name='A".$pyear."edit-Profil-SalaireVar' id='A".$pyear."edit-Profil-SalaireVar' value='".$psalaireVar."'/>";
    } else {
        print $psalaireVar;
    }

    print "   </td>\n";
    print "   </tr>\n";
    print "   <tr class='impair'>\n";
    print "   <th colspan = 1 align='left' name='headerProfilObjectifTot' >Objectif Total</th>\n";
    print "   <td colspan = 1 align='center' name='Profil-ObjectifTot' >";
    if ($pedit == 1)
    {
        print "     <input type='text' style='text-align=\"center\";' name='A".$pyear."edit-Profil-ObjectifTot' id='A".$pyear."edit-Profil-ObjectifTot' value='".$pObjectifTot."'/>";
    } else {
        print  $pObjectifTot;
    }
    print "   </td>\n";
    print "   <td colspan=2 >&nbsp;</td>\n";
    print "   </tr>\n";
    print "   <tr class='pair'>\n";
    print "   <th colspan = 2 align='center' name='headerProfil-Seuils-Prime' >Seuils Prime</th>\n";
    print "   <th colspan = 2 align='center' name='headerProfil-Seuils-Obj' >Seuils Objectif</th>\n";

}
function print_Seuil($year,$init,$edit=0,&$pseuil=array())
{
    $h = $init;
        for ($j=1;$j<5;$j++)
        {
            $pair = "pair";
            if ($h == 0){ $pair = "pair"; $h = 1;}
            else if ($h == 1){ $pair = "impair"; $h = 0;}
            print "<tr class='".$pair."'>\n";
            print "   <th colspan = 1 align='left' name='headerProfil-Seuils-Prime".$j."' >Seuil ".$j."</th>\n";

            print "   <td colspan = 1 align='center' name='Profil-Seuil-Prime".$j."' >";
            if ($edit == 1)
            {
                print "<input type='text' style='text-align=\"center\";' name='A".$year."edit-Profil-Seuil-Prime".$j."' id='A".$year."edit-Profil-Seuil-Prime".$j."' value='".traitementFloat($pseuil[$year][$j]['Prime'])."'/>";
            } else {
                print traitementFloat($pseuil[$year][$j]['Prime']);
            }
            print "</td>\n";
            print "   <th colspan = 1 align='left' name='headerProfil-Seuils-Objectif".$j."' >Seuil ".$j."</th>\n";
            print "   <td colspan = 1 align='center' name='Profil-Seuil-Objectif".$j."' >";
            if ($edit == 1)
            {
                print "<input type='text' style='text-align=\"center\";' name='A".$year."edit-Profil-SeuilObjectif".$j."' id='A".$year."edit-Profil-SeuilObjectif".$j."' value='".traitementFloat($pseuil[$year][$j]['Objectif'])."'/>";
            } else {
                print traitementFloat($pseuil[$year][$j]['Objectif']);
            }
            print "</td>\n";
            print "</tr>\n";
        }
}
    function print_pourcent($db,$init,$edit, &$pourcentMin,&$pourcentMax,&$pourcentAnn)
    {
        $h=$init;
        $requete=" SELECT * " .
                "    FROM Babel_Prime_CatPN " .
                "ORDER BY name";
        $sqlb=$db->query($requete);
        while ($obj=$db->fetch_object($sqlb))
        {
            $pair = "pair";
            if ($h == 0){ $pair = "pair"; $h = 1;}
            else if ($h == 1){ $pair = "impair"; $h = 0;}

            print "<tr class='".$pair."'>\n";
            print "   <th align='left' name='header".$obj->shortName."Cat' >".$obj->name."</th>\n";
            print "   <td align='center' name='Pourcent-".$obj->shortName."-Min' >";
            if ($edit == 1)
            {
                print "<input type='text' style='text-align=\"center\";' name='edit-Pourcent-".$obj->nameShort."-Min' id='edit-Pourcent-".$obj->nameShort."-Min' value='".traitementFloat($pourcentMin[$obj->nameShort])."'/>";
            } else {
                print traitementFloat($pourcentMin[$obj->nameShort]);
            }
            print "</td>\n";
            print "   <td align='center' name='Pourcent-".$obj->shortName."-Max' >";
            if ($edit == 1)
            {
                print "<input type='text' style='text-align=\"center\";' name='edit-Pourcent-".$obj->nameShort."-Max' id='edit-Pourcent-".$obj->nameShort."-Max' value='".traitementFloat($pourcentMax[$obj->nameShort])."'/>";
            } else {
                print traitementFloat($pourcentMax[$obj->nameShort]);
            }
            print "</td>\n";
            print "   <td align='center' name='Pourcent-".$obj->shortName."-Ann' >";
            if ($edit == 1)
            {
                print "<input type='text' style='text-align=\"center\";' name='edit-Pourcent-".$obj->nameShort."-Ann' id='edit-Pourcent-".$obj->nameShort."-Ann' value='".traitementFloat($pourcentAnn[$obj->nameShort])."'/>";
            } else {
                print traitementFloat($pourcentAnn[$obj->nameShort]);
            }
            print "</td>\n";
            print "</tr>\n";
        }
    }
?>
