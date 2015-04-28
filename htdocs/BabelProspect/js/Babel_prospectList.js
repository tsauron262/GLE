/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
var CustomDraggable;
var grid1;
var grid;

Rico.loadModule('LiveGrid','Effect','LiveGridMenu','GLE.css');
Rico.onLoad(
    function() {
        var imgctl=new Rico.TableColumn.image();
        var CustId=[2,3,4,5];
        var CustIdCol=4;
        var highlight=Rico.TableColumn.HighlightCell;
        var grid_options = {
            frozenColumns:  2,
            //headingSort: 'hover',
            canFilterDefault: false,
            FilterLocation: 0,     // put filter on a new header row
            saveColumnInfo: {width:true, filter:true, sort:true},
            //highlightMethod : 'outline',
            highlightElem   : 'cursorRow',
            useUnformattedColWidth: false,
            visibleRows    : '80%',
            columnSpecs:  [
               {canHide: 0, visible: false},
               {Hdg:'Reference.',filterUI:'t3',control:new Rico.TableColumn.link("nouvelleProspection.php?action=config&id={0}") ,width:300,canDrag:false},
               {type:'date',Hdg:'DateDebut',width:200, ClassName:'aligncenter'},
               {type:'date',Hdg:'DateFin',width:200, canDrag:false ,ClassName:'aligncenter'},
               {control:new MyCustomColumn(4), Hdg:'Statut',width:60, canDrag:false, ClassName:'alignleft',},
               {Hdg:'Nbr Tiers',width:60, ClassName:'aligncenter'},
               {canHide: 0, visible: false},
               {canHide: 0, visible: false},
        ]};
        var buffer = new Rico.Buffer.Base();
            grid = new Rico.LiveGrid('data_grid2', buffer, grid_options);
            buffer.loadRowsFromArray(nameList);
            buffer.fetch(0);
        }); //ferme le Rico.onLoad

highlightCellBabel = Class.create();

highlightCellBabel.prototype = {
  initialize: function(chkcol,chkval,highlightColor,highlightBackground) {
    this._chkcol=chkcol;
    this._chkval=chkval; //array
    this._highlightColor=highlightColor;
    this._highlightBackground=highlightBackground;
  },

  _clear: function(gridCell,windowRow) {
    gridCell.style.color='';
    gridCell.style.backgroundColor='';
    gridCell.innerHTML='&nbsp;';
  },

  _display: function(v,gridCell,windowRow) {
    var gridval=this.liveGrid.buffer.getWindowValue(windowRow,this._chkcol);
    var match=false;
    var colorTxt=false;
    var colorBg=false;
    var CustColorTxt = new Array();
    var CustColorBg = new Array();
        CustColorTxt[0]="white";
        CustColorTxt[1]="blue";
        CustColorTxt[2]="green";
        CustColorTxt[3]="red";
        CustColorBg[0]="darkblue";
        CustColorBg[1]="darkgrey";
        CustColorBg[2]="darkred";
        CustColorBg[3]="darkgreen";

    for (var i=0;i<this._chkval.length;i++)
    {
        if (gridval==this._chkval[i])
        {
            this._highlightColor=CustColorTxt[i];
            this._highlightBackground=CustColorBg[i];
            match=true;
            break;
        }
    }

    gridCell.style.color=match ? this._highlightColor : '';
    gridCell.style.backgroundColor=match ? this._highlightBackground : '';
    gridCell.innerHTML=this._format(v);
  }
}