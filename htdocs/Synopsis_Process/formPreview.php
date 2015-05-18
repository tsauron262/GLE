<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 30 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : formPreview.php
  * GLE-1.2
  *
  */
//  require_once('Var_Dump.php');

  require_once('pre.inc.php');

  if(!$user->rights->process->configurer){
        accessforbidden();
  }


  $id = $_REQUEST['id'];
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");
  $form = new formulaire($db);
  $res = $form->fetch($id);
  $js="";
  $arrTabsTitle=array();
  if ($res > 0 )
  {
      $arr=array();
      $arrCss=array();
      $arr1=array();
      $arrJs2=array();
      $curTabId = false;


      $hasDoubleSel=false;
      foreach($form->lignes as $key=>$lignes)
      {
           if ($lignes->type->jsScript.'x' != 'x')
               $arr[$lignes->type->code]="<script type='text/javascript' src='".DOL_URL_ROOT."/".$lignes->type->jsScript."'></script>";
           if ($lignes->type->cssScript.'x' != 'x')
               $arrCss[$lignes->type->code]="<link type='text/css' rel='stylesheet' href='".DOL_URL_ROOT."/".$lignes->type->cssScript."'/>";
           if ($lignes->type->jsCode.'x' != 'x')
               $arr1[$lignes->type->code]=$lignes->type->jsCode;

           if ($lignes->type->isBegEndTab == 1) $curTabId = $lignes->id;
           if ($lignes->type->isBegEndTab == 2) $curTabId = false;
           if ($curTabId && $lignes->type->isTabTitle > 0)
           {
                $arrTabsTitle[$curTabId][]=$lignes->label;
           }
           if ($lignes->src->uniqElem->OptGroup."x" != "x"){
                $hasDoubleSel=true;
           }

      }

          if ($hasDoubleSel)
          {
               $js .= "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.jDoubleSelect.js'></script>";

               $js .= <<<EOF
                <script>
                jQuery(document).ready(function(){
                    jQuery('select.double').each(function(){
                        var self=jQuery(this);

                        var widthSelect = parseInt(jQuery("#"+self.attr('id')+"").parent()[0].offsetWidth * 0.9);
                        jQuery(this).jDoubleSelect({
                                        text:'',
                                        finish: function(){
                                            jQuery("#"+self.attr('id')+"_jDS").selectmenu({
                                                style:'dropdown',
                                                maxHeight: 300,
                                                width: widthSelect,
                                            });

                                        },
                                        el1_change: function(){
                                            jQuery("#"+self.attr('id')+"_jDS_2").selectmenu({
                                                style:'dropdown',
                                                maxHeight: 300,
                                                width: widthSelect,
                                            });

                                        },
                                        el2_dest: jQuery("#"+self.attr('id')+"_jDS_Dest_2"),

                        });

                    });
                });

                      </script>
EOF;
          }


      $js .= join('',$arr);
      $js .= join('',$arrCss);
      $js .= "<script>".join('',$arr1)." ; </script>";
  }
//print "<xmp>".$js."</xmp>";
  llxHeader($js,$form->label);
//var_dump($user->rights->societe->voir);
  print "<div class='titre'>".$form->label."</div>";
$xmpMode = 0;

  if ($res > 0 )
  {
      print "<div>";
      $isFirstTabsElement = false;
      foreach($form->lignes as $key=>$lignes)
      {
//        print 'toto';
          $rights = $lignes->rights;
          if( $lignes->rights."x" != "x")
          {
              $rights = $lignes->rights;
                $str = 'if(!(' . $rights . ')) { $rights = false; } else { $rights = true; }';
                eval($str);
          } else {
              $rights=true;
          }
          if (!$rights) continue;
           if ($xmpMode == 1) print "<xmp>";
           if ($lignes->type->isBegEndTab == 1)
           {
                //Remove dernier char de htmlTag
                if ($lignes->type->htmlTag."x" != "x")
                {
                    print preg_replace('/>$/',' ',$lignes->type->htmlTag);
                    if (count($lignes->prop) > 0)
                    {
                        foreach($lignes->prop as $key=>$val)
                        {
                            if ($val->valeur ."x"!= "x")
                            print $val->element_name."='".$val->valeur."' ";
                        }
                    }


                   print " class='".$lignes->type->cssClass." ".$lignes->cssClass->valeur." ' " ;
                   if (count($lignes->style) > 0)
                   {
                       print " style='";
                       foreach($lignes->style as $key=>$val)
                       {
                           if ($val->valeur ."x"!= "x")
                            print $val->element_name.":".$val->valeur." ;";
                       }
                       print "' ";
                   }
                   print " >";
                }
                print "<ul>";
                foreach($arrTabsTitle[$lignes->id] as $key1=>$val1){
                    print "  <li><a href='#tabs-".SynSanitize($val1)."'>".$val1."</a></li>";
                }
                print "</ul>";
                $isFirstTabsElement=true;
                continue;
           } else if ($lignes->type->isTabTitle == 1)
           {
               if (!$isFirstTabsElement){ print "</div>"; }
               print '<div id="tabs-'.SynSanitize($lignes->label).'">';
               $isFirstTabsElement=false;
               continue;
           } else if ($lignes->type->isBegEndTab == 2)
           {
               if (!$isFirstTabsElement){ print "</div>"; }
               print "</div>";
               $isFirstTabsElement=false;
               continue;
           }


           if ($lignes->label."x" !="x" && $lignes->type->titleInLegend <> 1&& $lignes->type->titleInsideTag <> 1)
               print $lignes->label."<br>";
           if ($lignes->description."x" !="x" && $lignes->type->descriptionInsideTag <> 1 && $lignes->type->hasDescription==1)
               print "<em>".$lignes->description."</em><br>";
           //Remove dernier char de htmlTag
            if ($lignes->src->type == 'f'){
                $ret = $lignes->src->uniqElem->call_function($lignes->id);
                if ($ret) print $ret;
            } else {
                $iter=1;
                $htmlName="";
                if ($lignes->type->repeatTag > 0){ $iter = $lignes->type->repeatTag; print "<div class='starrating'>"; }
                for($itmp=0;$itmp<$iter;$itmp++)
                {
                    $jtmp = $itmp + 1;
                    if ($lignes->type->htmlTag."x" != "x"){
                        print ($lignes->src->uniqElem->OptGroup."x" != "x"?"<table width=100%><tr><td width=50%>":"");

                        print preg_replace('/>$/',' ',$lignes->type->htmlTag);
                        if (count($lignes->prop) > 0)
                        {
                            foreach($lignes->prop as $key=>$val)
                            {
                                if($val->element_name == 'name' && $val->valeur."x" != "x")
                                    $htmlName=$val->valeur;
                                if($val->element_name == 'id' && $val->valeur."x" != "x")
                                    $htmlId=$val->valeur;
                                if ($val->valeur ."x"!= "x")
                                    print $val->element_name."='".$val->valeur."' ";
                            }
                        }
                        if($htmlName.'x'=='x' && $lignes->type->isInput == 1)
                        {
                            $htmlName = SynSanitize(($lignes->label."x"=="x"?"inpt".$lignes->id:$lignes->label));
                            print " name='".$htmlName."' ";
                        }
                        if($htmlId.'x'=='x' && $lignes->src->uniqElem->OptGroup."x" != "x")
                        {
                            $htmlId = SynSanitize(($lignes->label."x"=="x"?"JDS".$lignes->id:$lignes->label));
                            print " id='".$htmlId."' ";
                        }

//Si value inside value
                        if ($lignes->type->valueInValueField == 1)
                        {
//                            if ($lignes->dflt."x" != "x") print " value='".$lignes->dflt."' ";
                            if ($lignes->dflt."x" != "x")
                            {
                                if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                {
                                    $dflt=getGlobalVar($arr[1]);
                                    print " value='".$dflt."' ";
                                } else {
                                    print " value='".$lignes->dflt."' ";
                                }
                            }

                        }
//Si value checked
                        if ($lignes->type->valueIsChecked == 1 && $iter == 1)
                        {
                            //TODO si valeur du formulaire
//                            if ($lignes->dflt > 0) print " CHECKED ";
                            if ($lignes->dflt."x" != "x")
                            {
                                if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                {
                                    $dflt=getGlobalVar($arr[1]);
                                    if ($dflt > 0) print " CHECKED ";
                                } else if ($lignes->dflt > 0)
                                    print " CHECKED ";
                            }

                        }
//Si value checked et est un type etoile
                        if ($lignes->type->valueIsChecked == 1 && $iter > 1)
                        {
                            //TODO si valeur du formulaire
//                            if ($lignes->dflt == $jtmp ) print " CHECKED ";
                                    if ($lignes->dflt."x" != "x"){
                                        if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                        {
                                            $dflt=getGlobalVar($arr[1]);
                                            if ($dflt  == $jtmp) print " CHECKED ";
                                        } else if ($lignes->dflt  == $jtmp)
                                            print " CHECKED ";
                                    }

                        }

                        print " class='".$lignes->type->cssClass." ".$lignes->cssClass->valeur." ".($lignes->src->uniqElem->OptGroup."x" != "x"?" double noSelDeco ":"")."' " ;
                        if (count($lignes->style) > 0)
                        {
                            print " style='";
                            foreach($lignes->style as $key=>$val)
                            {
                                if ($val->valeur ."x"!= "x")
                                    print $val->element_name.":".$val->valeur." ;";
                            }
                            print "' ";
                        }
                        if ($iter > 1)
                        {
                            print " value='".$jtmp."' ";
                        }
                        print " >";
                        if($lignes->type->code == "autocomplete"){
                            print "<input name='".$htmlName."-autocomplete' id='".$htmlId."-autocomplete' type='hidden'>";
                        }
                        if ($lignes->type->descriptionInsideTag == 1 && $lignes->type->hasDescription==1)
                        {
                            print $lignes->description;
                        }
                        if ($lignes->type->titleInsideTag == 1 && $lignes->type->hasTitle==1)
                        {
                            print $lignes->label;
                        }
                        if ($lignes->label."x" !="x" && $lignes->type->titleInLegend == 1)
                        {
                            print "<legend>".$lignes->label."</legend>";
                        }

                        if ($lignes->type->sourceIsOption ==1 && $lignes->type->hasSource > 0 && $lignes->src)
                        {
                            //Get Source
                            switch ($lignes->src->type){
                                 case "r":
                                 {
                                    if ($lignes->src->uniqElem->OptGroup."x" != "x")
                                    {
                                        $lignes->src->requete->getValues();
                                        //var_dump($lignes->src->requete);
                                          foreach($lignes->src->requete->valuesGroupArr as $keyOptGrp => $valOptGrp)
                                          {
                                            print "<OPTGROUP label='".$valOptGrp['label']."'>";
                                            foreach($valOptGrp['data'] as $key1OptGrp=>$val1OptGrp)
                                            {
                                                $valCompare = $lignes->dflt;
                                                if ($lignes->dflt."x" != "x"){
                                                    if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                                    {
                                                        $dflt=getGlobalVar($arr[1]);
                                                        $valCompare = $dflt;
                                                    }
                                                }
                                                if ($valCompare == $key)
                                                {
                                                    print "<OPTION SELECTED value='".$key1OptGrp."'>".$val1OptGrp."</OPTION>";
                                                } else {
                                                    print "<OPTION value='".$key1OptGrp."'>".$val1OptGrp."</OPTION>";
                                                }
                                            }
                                            print "</OPTGROUP>";
                                          }

                                    } else {

                                     foreach($lignes->src->uniqElem->getValues() as $key=>$val)
                                     {
//Si key == dfltValue && value is Selected
                                        //TODO si valeur autre que default
                                        $valCompare = $lignes->dflt;
                                        if ($lignes->dflt."x" != "x"){
                                            if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                            {
                                                $valCompare = getGlobalVar($arr[1]);
                                            }
                                        }

                                        if ($valCompare == $key)
                                        {
                                             print "<option SELECTED value='".$key."'>".$val."</option>";
                                        } else {
                                             print "<option value='".$key."'>".$val."</option>";
                                        }
                                     }
                                   }
                                 }
                                 break;
                                 case "g":
                                 {
//Si key == dfltValue && value is Selected
                                     foreach($lignes->src->uniqElem->getValues() as $key=>$val)
                                     {
                                        //TODO si valeur autre que default
                                        $valCompare = $lignes->dflt;
                                        if ($lignes->dflt."x" != "x")
                                        {
                                            if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                            {
                                                $dflt=getGlobalVar($arr[1]);
                                                $valCompare = $dflt;
                                            }
                                        }

                                        if ( $valCompare == $key)
                                        {
                                            print "<option SELECTED value='".$key."'>".$val."</option>";
                                        } else {
                                            print "<option value='".$key."'>".$val."</option>";
                                        }
                                     }
                                 }
                                 break;
                                 case "l":
                                 {
//Si key == dfltValue && value is Selected
                                     foreach($lignes->src->uniqElem->getValues() as $key=>$val)
                                     {
                                        //TODO si valeur autre que default
                                        $valCompare = $lignes->dflt;
                                        if ($lignes->dflt."x" != "x")
                                        {
                                            if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                                            {
                                                $dflt=getGlobalVar($arr[1]);
                                                $valCompare = $dflt;
                                            }
                                        }

                                        if ( $valCompare == $key)
                                        {
                                            print "<option SELECTED value='".$key."'>".$val."</option>";
                                        } else {
                                            print "<option value='".$key."'>".$val."</option>";
                                        }
                                     }
                                 }
                                 break;
                             }
                             //Print
                         }
                    }
//Si value is in Tag
                    if ($lignes->type->valueInTag == 1)
                    {
                        //TODO si valeur du formulaire
//                        if ($lignes->dflt."x" != "x") print $lignes->dflt;
                        if (preg_match('/\[GLOBVAR\]([0-9]*)/',$lignes->dflt,$arr))
                        {
                            $dflt=getGlobalVar($arr[1]);
                            print $dflt;
                        } else {
                            print $lignes->dflt;
                        }

                    }
                    if ($lignes->type->endNedded == 1)
                    {
                        print $lignes->type->htmlEndTag;
                    }
                    print ($lignes->src->uniqElem->OptGroup."x" != "x"?"<td><div id='".$htmlId."_jDS_Dest_2'></div></table>":"");
                    if ($lignes->type->code == 'autocomplete')
                    {
                         if ($htmlId.'x'=='x' && $htmlName.'x' == 'x')
                             $htmlId = SynSanitize(($lignes->label."x"=="x"?"JDS".$lignes->id:$lignes->label));
                         else if ($htmlId.'x'=='x')
                             $htmlId = $htmlName;
                         $tmpHtml = "<script>";
                         $tmpHtml .= "jQuery(document).ready(function(){";
                         $tmpHtml .= 'jQuery("input#'.$htmlId.'").autocomplete("'. DOL_URL_ROOT .'/Synopsis_Process/ajax/autocomplete-json.php?type='.$lignes->id.'",';
                         $tmpHtml .= <<<EOF
                                       {minChar: 1,
                                        delay: 400,
                                        width: 260,
                                        dataType: "json",
                                        selectFirst: false,
                                        formatItem: function(data, i, max, value, term) {
                                                    return value;
                                                },
                                         parse: function(data) {
                                                var mytab = new Array();
                                                for (var i = 0; i < data.length; i++) {
                                                    var myres = data[i].label;
                                                    var myvalue = data[i].label;
                                                    mytab[mytab.length] = { data: data[i], value: myvalue, result: myres };
                                                }
                                                return mytab;
                                         },
                                        modifAutocompleteSynopsisReturnSelId: function(selected)
                                        {
                                            var selId = selected.data['id'];
EOF;
                         $tmpHtml .=       "jQuery('#".$htmlId."-autocomplete').val(selId) ;";
                         $tmpHtml .= <<<EOF
                                        }
                                    });
EOF;
                         $tmpHtml .= '});';
                         $tmpHtml .= "</script>";
                         $arrJs2[$lignes->type->code]=$tmpHtml;
                    }


                }
                if ($lignes->type->repeatTag > 0){  print "</div>";  }
            }
            print join(' ',$arrJs2);
            $arrJs2 = array();
           if ($xmpMode == 1) print "</xmp>";
      }
      print "</div>";
  }

function getGlobalVar($globId)
{
    global $db;
    $globalvar = new globalvar($db);
    $globalvar->fetch($globId);
    return($globalvar->glabalVarEval);
}
?>
