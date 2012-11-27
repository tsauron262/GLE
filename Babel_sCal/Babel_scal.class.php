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
class Babel_scal {

    public $db;
    public $langs;
    function Babel_scal($db,$langs) {
        $this->db = $db;
        $this->langs = $langs;
    }


    function draw_scal_footer($print=false)
    {
        $html = "</table>";
        $html .= "</form>";
    }

    function draw_scal_init($print=false)
    {
        $html =' <form name="sCal"><div style="position: relative; float: left;  left: 25px; top: 3px; ">';
        $html .= "<div class='closeButtonScal' onClick=\"scal_showCacl('".DOL_URL_ROOT."/Babel_sCal/');\">".img_picto($this->langs->trans('Fermer'),"agt_stop.png")."</div></div>\n";
        $html .=' <table BGCOLOR="#68686E" border=1 cellspacing=0 cellpadding=0 style="width:300px;min-width:300px;max-width:300px;">'."\n";
        $html .= str_repeat('<col style="width:50px">',6)."\n";
        if ($print)
        {
             print $html;
        }
         return($html);

    }

  function draw_scal_screen($print=false)
  {
     $html ='<tr>'."\n";
     $html .= '<td colspan=6 align="right"><b><span class="scalTitle" style="padding-right: 15pt; " >Babel - sCal</span></b></td>'."\n";
     $html .= '</tr>'."\n";
     $html .= '<tr align="center">'."\n";
     $html .= '<td><input type="button" class="scalButton" value="JS" onClick="xPlusEq(document.sCal.FSET1.value)"></td>'."\n";
     $html .= '<td colspan=4>'."\n";
     if ($print)
     {
         print $html;
     }
     return($html);
  }

  function GetFormula($print=false)
  {
    $html = "<SELECT name='FSET1' class='scalSelect'><option SELECTED value=''>Conversion-></option>\n\t";
      $requete = " SELECT Babel_Scal_formula.Formula as fform,
                          Babel_Scal_formula.Description as fdesc,
                          Babel_Scal_formula.DisplayOrder as forder,
                          Babel_Scal_categorie.nom as cnom,
                          Babel_Scal_categorie.id as cid
                     FROM Babel_Scal_formula
                LEFT JOIN Babel_Scal_categorie
                       ON Babel_Scal_formula.Scal_categorie_refid = Babel_Scal_categorie.id
                ORDER BY Babel_Scal_categorie.DisplayOrder, Babel_Scal_formula.Scal_categorie_refid, Babel_Scal_formula.DisplayOrder
    ";
      $resql = $this->db->Query($requete);
//print $requete;
      if ($resql)
      {
            $remCat =-1;
            while ($res=$this->db->fetch_object($resql))
            {
                if ($remCat == -1)
                {
                        $html .= "<optgroup label='".utf8_encode($this->langs->trans($res->cnom))."'>\n\t";
                        $remCat = $res->cid;

                }
                if ($remCat == $res->cid)
                {
                    $html .= "<option value='".$res->fform."'>".utf8_encode($this->langs->trans($res->fdesc))."</option>\n\t";
                } else {
                    $html .= "</optgroup><optgroup label='".utf8_encode($this->langs->trans($res->cnom))."'>\n\t";
                    $html .= "<option value='".$res->fform."'>".utf8_encode($this->langs->trans($res->fdesc))."</option>\n\t";
                    $remCat = $res->cid;
                }

           }
      }
      $html .= "</optgroup></select>\n";
      $html .= "  </td>";
    if ($print)
    {
        print $html;
    }
    return($html);
  }

    function draw_scal_button($print)
    {
         $html = '<td><input type="button" class="scalButton" Value="Do" onClick="Xwork(document.sCal.FSET1.value)"></td></tr>'."\n";
         $html .= '<tr>'."\n";
         $content =  $_SESSION['BabelScalRes'];
         $html .= '<td colspan=6><TextArea name="IOx" id="IOx" rows=4  style="background-color: #C2E149; width: 100%" >'.$content.'</TextArea></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr align="center">'."\n";
         $html .= '<td><input type="button" class="scalButton" Value="x&rsaquo;m" onClick="XtoM()"></td>'."\n";
         $html .= '<td><input type="button" class="scalButton" Value="m&rsaquo;x" onClick="MtoX()"></td>'."\n";
         $memContent =  $_SESSION['BabelScalremMem'];

         $html .= '<td colspan=2><input type="text" name="IOm" id="IOm" style="background-color: #C2E149; border: 2px Solid #A2C129; width: 96px; height: 16px;" value="'.$memContent.'" size=8></td>'."\n";

         $html .= '<td><input type="button" class="scalButton" Value=" m+" onClick="Mplus()"></td>'."\n";
         $html .= '<td><input type="button" class="scalButton" Value="mc" onClick="Mclear()"></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr>'."\n";
         $html .= '<td colspan=6 style="line-height: 1pt;">&nbsp;</td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr align="center">'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  7  " onClick="xPlusEq(7)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  8  " onClick="xPlusEq(8)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  9  " onClick="xPlusEq(9)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value=" (    " onClick="xPlusEq(\'(\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="    ) " onClick="xPlusEq(\')\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value=" C  " onClick="Clear()"></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr align="center">'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  4  " onClick="xPlusEq(4)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  5  " onClick="xPlusEq(5)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  6  " onClick="xPlusEq(6)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  *  " onClick="xPlusEq(\'*\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  /   " onClick="xPlusEq(\'/\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value=" &lt;  " onClick="BkSpace()"></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr align="center">'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  1  " onClick="xPlusEq(1)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  2  " onClick="xPlusEq(2)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  3  " onClick="xPlusEq(3)"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  +  " onClick="xPlusEq(\'+\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  -   " onClick="xPlusEq(\'-\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  ^  " onClick="xPlusEq(\'^\')"></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr align="center">'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  0  " onClick="xPlusEq(\'0\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="  &bull;  " onClick="xPlusEq(\'.\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value=" +/- " onClick="xMultEq(\'-1\')"></td>'."\n";
         $html .= ' <td><input type="button" class="scalButton" Value="1/x "  onClick="recip()">'."\n";
         $html .= ' <td colspan=2><input type="button" class="scalButton" Value="    =    " onClick="xEval()" style=\'font-size:12pt;\'></td>'."\n";
         $html .= '</tr>'."\n";
         $html .= <<< EOF
         <style>
         input.scalButton{
            -moz-border-radius: 0!important;
EOF;
         $html .= 'background-image: url("'.DOL_URL_ROOT.'/Babel_sCal/css/button_bg.png")!important;';
         $html .= <<< EOF
            background-repeat: repeat-x!important;
            background-position: center bottom;
            background-color: transparent;
            border: 0 none !important;
            color: black!important;;
            cursor: pointer!important;;
            display: inline-block!important;;
            font-family: 'Lucida Grande';
            font-size: 11px;
            font-style: normal;
            font-variant: normal;
            font-weight: normal!important;
            height: auto!important;
            margin: 2px 2px 2px 0 !important;
            min-width: 0!important;
            padding: 2px 1px!important;
            text-align: center;
            text-transform: none;
         }
         <style>
EOF;
         if ($print)
         {
            print $html;
         }
         return($html);
    }


}



?>