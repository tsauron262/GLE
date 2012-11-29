<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 3 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * dolibarr-24dev
  */
?>
<?php
require_once('./pre.inc.php');
//if (!isset ($_SESSION)) session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<?php
//<html>
//<head>
//<title>GLE - Note de frais</title>
$js ='<script src="'.DOL_URL_ROOT.'/Synopsis_Common/js/rico2/src/prototype.js" type="text/javascript"></script>
<script src="'.DOL_URL_ROOT.'/Synopsis_Common/js/rico2/src/rico.js" type="text/javascript"></script>';
//print $js;
llxHeader($js);
$langs->load("synopsisGene@Synopsis_Tools");
require "applib.php";
//var_dump($user->rights->TechPeople->ndf);
if ($user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->Valide){
    OpenDB();
    $sqltext="select Babel_ndf.id as CustomerID,
                     concat(firstname, ' ',name)  as Utilisateur,
                     " . $GLOBALS['oDB']->SqlYear("periode") . " as yr,
                     periode  as mr,
                     total,
                     Babel_ndf.statut as st,
                     Babel_ndf.statut as st1,
                     ".MAIN_DB_PREFIX."user.rowid as uid
                FROM Babel_ndf,
                     ".MAIN_DB_PREFIX."user
               WHERE fk_user_author=".MAIN_DB_PREFIX."user.rowid
             group by id
             order by periode DESC";
    //print $sqltext;
    $_SESSION['GLE_NdfGrid']=$sqltext;

} else if ($user->rights->TechPeople->ndf->AfficheMien) {
    OpenDB();

    $sqltext="select Babel_ndf.id as CustomerID,
                     concat(firstname, ' ',name)  as Utilisateur,
                     " . $GLOBALS['oDB']->SqlYear("periode") . " as yr,
                     periode  as mr,
                     total,
                     Babel_ndf.statut as st,
                     Babel_ndf.statut as st1,
                     ".MAIN_DB_PREFIX."user.rowid as uid
                FROM Babel_ndf,
                     ".MAIN_DB_PREFIX."user
               WHERE fk_user_author=".MAIN_DB_PREFIX."user.rowid AND fk_user_author = ".$user->id."
             group by id
             order by periode DESC";
    //print $sqltext;
    $_SESSION['GLE_NdfGrid']=$sqltext;
} else {
    print "L'acc&egrave; à ce menu vous est impossible.'";
    exit(0);
}
CloseApp();

require "chklang2.php";
require "settings.php";
?>

<script type='text/javascript'>
Rico.loadModule('LiveGridAjax','LiveGridMenu','GLE.css');
<?php
setStyle();
?>

var GLE_NdfGrid;

function setFilter() {
  for (var i=0; i<yrboxes.length; i++) {
    if (yrboxes[i].checked==true) {
      var yr=yrboxes[i].value;
      GLE_NdfGrid.columns[2].setSystemFilter('EQ',yr);
      return;
    }
  }
}

MyCustomColumn = Class.create();

MyCustomColumn.prototype = {

  initialize: function(col) {
    this._img=[];
    this._colnum=col;
    this._div=[];
  },

  _create: function(gridCell,windowRow) {

    this._img[windowRow]=RicoUtil.createFormField(gridCell,'img',null,this.liveGrid.tableId+'_img_'+this.index+'_'+windowRow);
    this._div[windowRow]=RicoUtil.createFormField(gridCell,'div',null,this.liveGrid.tableId+'_div_'+this.index+'_'+windowRow);
    this._clear(gridCell,windowRow);
  },

  _clear: function(gridCell,windowRow) {
    var img=this._img[windowRow];
    var div=this._div[windowRow];
    img.style.display='none';
    img.src='';
    div.innerHTML='';
    div.style.display='none';

  },

  _display: function(v,gridCell,windowRow) {
    var trad = new Array();
    trad[0]="Brouillon";
    trad[1]="Brouillon";
    trad[2]="En cours";
    trad[3]="Valid&eacute;";
    trad[4]="Refus&eacute;";
    var picto = new Array();
    picto[0]=0;
    picto[1]=0;
    picto[2]=3;
    picto[3]=4;
    picto[4]=5;

    var img=this._img[windowRow];

    var div=this._div[windowRow];
    var url;
    if (picto[v] == 5)
    {
        url = "<?php print DOL_URL_ROOT;?>/theme/auguria/img/stcomm-1.png";
    } else {
        url = "<?php print DOL_URL_ROOT;?>/theme/auguria/img/statut"+picto[v]+".png";
    }

    this._img[windowRow].src=url;
    this._img[windowRow].align="absmiddle";
    this._img[windowRow].title = trad[v];
    //this._div[windowRow].style.display="inline";

    div.innerHTML = " "+trad[v] ;

//    this.liveGrid.buffer.setWindowValue(windowRow,this._colnum,trad[v] + this._img[windowRow].innerHTML);
    img.style.display='inline';
    div.style.display='inline';
  }
}

MyCustomColumn1 = Class.create();

MyCustomColumn1.prototype = {

  initialize: function(href,target,prefix) {
    this._href=href;
    this._target=target;
    this._anchors=[];
    this._prefixGLE=prefix;
  },

  _create: function(gridCell,windowRow) {
    this._anchors[windowRow]=RicoUtil.createFormField(gridCell,'a',null,this.liveGrid.tableId+'_a_'+this.index+'_'+windowRow);
    if (this._target) this._anchors[windowRow].target=this._target;
    this._clear(gridCell,windowRow);
  },

  _clear: function(gridCell,windowRow) {
    this._anchors[windowRow].href='';
    this._anchors[windowRow].innerHTML='';
  },

  _display: function(v,gridCell,windowRow) {
    if (this._prefixGLE)
    {
        //var col0=this.liveGrid.buffer.getWindowValue(windowRow,5);
        this._anchors[windowRow].innerHTML= this._prefixGLE+" "+ v;
    } else {
        this._anchors[windowRow].innerHTML=  v.formatDate("mmm yyyy");
    }

    var getWindowValue=this.liveGrid.buffer.getWindowValue.bind(this.liveGrid.buffer);
    this._anchors[windowRow].href=this._href.replace(/\{\d+\}/g,
      function ($1) {
        var colIdx=parseInt($1.substr(1));
        return getWindowValue(windowRow,colIdx);
      }
    );
  }

}

Rico.onLoad( function() {
  yrboxes=document.getElementsByName('year');
  var opts = {
    <?php GridSettingsScript(); ?>,
    prefetchBuffer: false,
    headingSort   : 'hover',
    columnSpecs   : [ {canHide: 0, visible: false, canDrag:false},
                      {width:250,control: new MyCustomColumn1("<?php print DOL_URL_ROOT; ?>/user/fiche.php?id={7}",'',"<img height='12pt' border='0' src='/dolibarr-24dev/htdocs/theme/auguria/img/object_user.png'></img>")},
                      {canHide: 0, visible: false, canDrag:false},
                      {width:150,ClassName:"aligncenter",type: "date",dateFmt:"mmm yyyy",control: new MyCustomColumn1("<?php print DOL_URL_ROOT; ?>/Babel_TechPeople/deplacements/ficheNdf.php?id={0}")},
                      {width:150, type:'number', suffix:'&euro;', decPlaces:2,thouSep:" ", ClassName:"aligncenter"},
                      {canHide: 0, visible: false},
                      {width: 150, control: new MyCustomColumn(6),},
                      {canHide: 0, visible: false, canDrag:false},
                      ]
  };
  var buffer=new Rico.Buffer.AjaxSQL('ricoXMLquery.php', {TimeOut:<?php print array_shift(session_get_cookie_params())/60 ?>});
  GLE_NdfGrid=new Rico.LiveGrid ('GLE_NdfGrid', buffer, opts);
  GLE_NdfGrid.menu=new Rico.GridMenu(<?php GridSettingsMenu(); ?>);
  setFilter();
});
</script>

<style type="text/css">
.ricoLG_top div.ricoLG_col {
  white-space:nowrap;
}
</style>

</head>

<body>

<?php
print "<table id='explanation' border='0' cellpadding='0' cellspacing='5' style='clear:both'><tr valign='top'><td>";
?>
</td>
<p>Note de frais pour :
<?php
$requete ="";
if ($user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->Valide){

    $requete = "SELECT DISTINCT year(periode) as yd from Babel_ndf ";
} else {
    $requete = "SELECT DISTINCT year(periode) as yd from Babel_ndf WHERE fk_user_author = ".$user->id;
}
if ($resql = $db->query($requete))
{
    while ($res=$db->fetch_object($resql))
    {
        if ($res->yd == date('Y') && $res->yd > 0)
        {
            print "<input type='radio' name='year' onclick='setFilter()' checked value='".$res->yd."' >&nbsp;".$res->yd."&nbsp;"."&nbsp;"."&nbsp;";
        } else if ( $res->yd > 0){
            print "<input type='radio' name='year' onclick='setFilter()' value='".$res->yd."' >&nbsp;".$res->yd."&nbsp;"."&nbsp;"."&nbsp;";
        }

    }
}
?>
<button onclick='GLE_NdfGrid.buffer.refresh()' style='margin-left:1em;font-size:8pt;'>Refresh</button>
</p>

<p class="ricoBookmark"><span id="GLE_NdfGrid_bookmark">&nbsp;</span></p>
<table id="GLE_NdfGrid" class="ricoLiveGrid" cellspacing="0" cellpadding="0">
<colgroup>
<col style='width:40px;' >
<col style='width:60px;' >
<col style='width:40px;' >
<col style='width:40px;' >
</colgroup>
  <tr>
      <th>Id#</th>
      <th>Utilisateur</th>
      <th>Year</th>
      <th>P&eacute;riode</th>
      <th>Total</th>
      <th>StatutImg</th>
      <th>Statut</th>
      <th>Uid</th>
  </tr>
</table>
<!--
<textarea id='GLE_NdfGrid_debugmsgs' rows='5' cols='80'>
-->
</body>
</html>