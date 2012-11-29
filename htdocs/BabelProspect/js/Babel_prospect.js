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


Rico.loadModule('LiveGrid','DragAndDrop','Effect','LiveGridMenu','GLE.css');
Rico.onLoad(
    function() {
        var grid_options = {
            frozenColumns:  3,
            headingSort: 'hover',
            canFilterDefault: false,
            FilterLocation: -1,     // put filter on a new header row
            saveColumnInfo: {width:true, filter:true, sort:true},
            //highlightMethod : 'outline',
            highlightElem   : 'none',
            useUnformattedColWidth: false,
            onRefreshComplete: grid_refresh_complete,

            visibleRows    : '80%',
            columnSpecs:  [
               {canHide: 0, visible: false, canDrag:true},
               {Hdg:'Client ?',filterUI:'s',width:30, canDrag:true},
               {Hdg:'Nom',filterUI:'t5',width:150, canDrag:true},
               {Hdg:'Ville',filterUI:'t5',width:150, canDrag:true},
               {Hdg:'D&eacute;partement',filterUI:'t5',width:160, canDrag:true},
               {canHide: 0, visible: false},
               {Hdg:'Effectif',type:'text',width:100,filterCol:6,filterUI:'s', canDrag:true,sortCol:5},
               {canHide: 0, visible: false, canDrag:true},
               {Hdg:'Secteur.',type:'text',width:100,filterUI:'s',filterCol:8, canDrag:true,sortCol:7}],
        };
        var buffer = new Rico.Buffer.Base();
            grid = new Rico.LiveGrid('data_grid', buffer, grid_options);
            buffer.loadRowsFromArray(names);
            buffer.fetch(0);
        //var buffer1 = new Rico.Buffer.AjaxXML('/dolibarr-24dev/htdocs/BabelProspect/ajax/listSociete_xmlresponse.php?action=unlisted&campagneId=7');
        var grid_options1 = {
                    frozenColumns: 3,
                    canFilterDefault: false,
                    useUnformattedColWidth: false,
                    headingSort: 'hover',
                    FilterLocation: -1,     // put filter on a new header row
                    saveColumnInfo: {width:true, filter:true, sort:true},
                    highlightElem   : 'none',
                    onRefreshComplete: grid_refresh_complete,
                    visibleRows    : '80%',
                    columnSpecs:  [
                        {canHide: 0, visible: false, canDrag:true},
                        {Hdg:'Client ?',filterUI:'s',width:30, canDrag:true},
                        {Hdg:'Nom',filterUI:'t5',width:150, canDrag:true},
                        {Hdg:'Ville',filterUI:'t5',width:150, canDrag:true},
                        {Hdg:'D&eacute;partement',filterUI:'t5',width:160, canDrag:true},
                        {canHide: 0, visible: false},
                        {Hdg:'Effectif',type:'text',width:100,filterCol:6,filterUI:'s', canDrag:true,sortCol:5},
                        {canHide: 0, visible: false, canDrag:true},
                        {Hdg:'Secteur.',type:'text',width:100,filterUI:'s',filterCol:8, canDrag:true,sortCol:7}],
        };
        var buffer1 = new Rico.Buffer.Base();
            grid1 = new Rico.LiveGrid('data_grid1', buffer1, grid_options1);
           buffer1.loadRowsFromArray(names1);
            buffer1.fetch(0);
                 CustomDropzone = Class.create();
                 CustomDropzone.prototype = Object.extend(new Rico.Dropzone(), CustomDropzoneMethods1);
              dndMgr.registerDropZone( new CustomDropzone(grid1));

                 CustomDropzone1 = Class.create();
                 CustomDropzone1.prototype = Object.extend(new Rico.Dropzone(), CustomDropzoneMethods);
              dndMgr.registerDropZone( new CustomDropzone1(grid));


        }); //ferme le Rico.onLoad


    function grid_refresh_complete(startpos, endpos)
   {
       var col = 2;
//this.liveGrid.buffer.getValue(this.liveGrid.buffer.bufferRow(this.dragRow),2)
       if (grid) {
           var rows = grid.columns[col].numRows();
//        alert( grid.buffer.rows[i].length );
           for (var r=0; r < rows; r++)
           {

//            alert (RicoUtil.getElementsComputedStyle(grid.buffer.getValue(r,col), "backgroundColor", "background-color"));
                if (grid.buffer.rowSelected[r]  == true)
                {//grid.columns[col].getValue(r)
                    grid.buffer.columns[col].getCell(r).style.color           = "#ffffff";
//                    el.style.backgroundColor = "#08246b";
//                el.style.border          = "1px solid blue";
                }
//                for(var )
//               if (grid.columns[col].isSelected()) {
//                   var idx = grid.highlightIdx;
//                   idx.row = r;
//                   grid.menuIdx = idx;
//                   grid.highlight(idx);
//                   break;
//               }
           }
       }
   }

var CustomDropzone;
var CustomDropzone1;
//drag


//
//
//var CustomDropzoneMethods = {
//
//      initialize: function( grid ) {
//        this.liveGrid     = grid;
//        this.htmlElement  = grid.outerDiv;
//        this.absoluteRect = null;
//      },
////    activate: function() {
//        this.htmlElement.style.backgroundColor= "rgb(200,200,200)";
//      new Rico.Effect.FadeTo( this.htmlElement, .4, 250, 4 );
//   },
//
//   deactivate: function() {
//      this.htmlElement.style.backgroundColor= "rgb(100,200,255)";
//      new Rico.Effect.FadeTo( this.htmlElement, 1, 250, 4 );
//   },
//
//   showHover: function() {
//      if ( this.showingHover )
//         return;
//      //this.header.style.color = "#000000";
//      this.htmlElement.style.backgroundColor= "rgb(200,200,200)";
//      new Effect.FadeTo( this.htmlElement, .7, 250, 4 );
//      this.showingHover = true;
//   },
//   hideHover: function() {
//      if ( !this.showingHover )
//         return;
//      //this.header.style.color = "#5b5b5b";
//      this.htmlElement.style.backgroundColor= "rgb(200,200,200)";
//      new Rico.Effect.FadeTo( this.htmlElement, .4, 250, 4 );
//      this.showingHover = false;
//   },
//   accept: function(draggableObjects) {
//
//      n = draggableObjects.length;
//      for ( var i = 0 ; i < n ; i++ )
//         this._insertSorted(draggableObjects[i]);
//   },

//
//    accept: function(draggableObjects) {
//    for ( var i = 0 ; i < draggableObjects.length ; i++ ) {
//
//      // copy data from drag grid buffer to drop grid buffer
//
//      var srcGrid = draggableObjects[i].liveGrid;
//      if (srcGrid==this.liveGrid) continue;
//      var srcRow  = srcGrid.buffer.bufferRow(draggableObjects[i].dragRow);
//      var newRows = this.liveGrid.buffer.appendRows(1);
//      for (var c=0; c < srcGrid.columns.length; c++)
//    {
//        newRows[0][c]=srcGrid.buffer.getValue(srcRow,c);
//        alert (newRows[0][c]);
//    }
//
//
////      logger.value+="CustomDropzone.accept: " + draggableObjects[i].htmlElement.innerHTML + " from [" + srcGrid.tableId +"] to [" + this.liveGrid.tableId +"]\n";
//
//      // refresh drop grid
//
//      this.liveGrid.buffer.fetch(0);
//      this.liveGrid.scrollToRow(this.liveGrid.buffer.size-1); // scroll to the end
//
//      // remove item from drag grid
//
//      srcGrid.buffer.deleteRows(srcRow);
//      srcGrid.buffer.fetch(Math.min(srcGrid.lastRowPos || 0, srcGrid.topOfLastPage()-1));
//    //updateSQL table
//    updateSql('unlisted',srcGrid.buffer.getValue(srcRow,0));
//    }
//  }
//}

//var CustomDropzoneMethods = {
//
//      initialize: function( grid ) {
//        this.liveGrid     = grid;
//        this.htmlElement  = grid.outerDiv;
//        this.absoluteRect = null;
//      },
//    accept: function(draggableObjects) {
//    for ( var i = 0 ; i < draggableObjects.length ; i++ ) {
//
//      var srcGrid = draggableObjects[i].liveGrid;
//      if (srcGrid==this.liveGrid) continue;
//      var srcRow  = srcGrid.buffer.bufferRow(draggableObjects[i].dragRow);
//      var newRows = this.liveGrid.buffer.appendRows(1);
//      for (var c=0; c < srcGrid.columns.length; c++)
//        newRows[0][c]=srcGrid.buffer.getValue(srcRow,c)
//      this.liveGrid.buffer.fetch(0);
//      this.liveGrid.scrollToRow(this.liveGrid.buffer.size-1); // scroll to the end
//      // remove item from drag grid
//      srcGrid.buffer.deleteRows(srcRow);
//      srcGrid.buffer.fetch(Math.min(srcGrid.lastRowPos || 0, srcGrid.topOfLastPage()-1));
//    //updateSQL table
//    updateSql('unlisted',srcGrid.buffer.getValue(srcRow,0));
//    }
//  }
//}



var CustomDropzoneMethods1 = {

      initialize: function( grid ) {
        this.liveGrid     = grid;
        this.htmlElement  = grid.outerDiv;
        this.absoluteRect = null;
      },

    accept: function(draggableObjects) {
    for ( var i = 0 ; i < draggableObjects.length ; i++ ) {

      // copy data from drag grid buffer to drop grid buffer

      var srcGrid = draggableObjects[i].liveGrid;
      if (srcGrid==this.liveGrid) continue;
      var srcRow  = srcGrid.buffer.bufferRow(draggableObjects[i].dragRow);
      var id = srcGrid.buffer.getValue(srcRow ,0);
      var newRows = this.liveGrid.buffer.appendRows(1);
      for (var c=0; c < srcGrid.columns.length; c++)
     {
        //prob only visible row :( :( :( :(
        newRows[0][c]=srcGrid.buffer.getValue(srcRow,c)
      }
        //alert (newRows[0][0]);
      // refresh drop grid
      this.liveGrid.buffer.fetch(0);
      this.liveGrid.scrollToRow(this.liveGrid.buffer.size-1); // scroll to the end
      // remove item from drag grid
      srcGrid.buffer.deleteRows(srcRow);
      srcGrid.buffer.fetch(Math.min(srcGrid.lastRowPos || 0, srcGrid.topOfLastPage()-1));
      //updateSQL table
      updateSql('unlisted',id);
    }
  }
}

var CustomDropzoneMethods = {

      initialize: function( grid ) {
        this.liveGrid     = grid;
        this.htmlElement  = grid.outerDiv;
        this.absoluteRect = null;
      },
    accept: function(draggableObjects) {
    for ( var i = 0 ; i < draggableObjects.length ; i++ ) {
      var srcGrid = draggableObjects[i].liveGrid;
      var srcRow  = srcGrid.buffer.bufferRow(draggableObjects[i].dragRow);
      id = srcGrid.buffer.getValue(srcRow,0);
      //alert(id);
     if (id != null)
     {
//        alert(id);
        if (srcGrid==this.liveGrid) continue;
//        var srcRow  = srcGrid.buffer.bufferRow(draggableObjects[i].dragRow);
        var newRows = this.liveGrid.buffer.appendRows(1);
        for (var c=0; c < srcGrid.columns.length; c++)
            newRows[0][c]=srcGrid.buffer.getValue(srcRow,c)
        this.liveGrid.buffer.fetch(0);
        this.liveGrid.scrollToRow(this.liveGrid.buffer.size-1); // scroll to the end
        // remove item from drag grid
        srcGrid.buffer.deleteRows(srcRow);
        srcGrid.buffer.fetch(0);
        //updateSQL table
    //    alert(srcGrid.id);
    //    alert(srcRow);
        updateSql('listed',id);

    }
    }
  }
}



function updateSql(isListed,id)
{
    //alert(isListed);
//    alert(id);
    if ("x"+id != "x" && id && id != null)
    {
        Babel_prospect_xmlhttpPost("ajax/listSociete_xmlresponse.php?action=add&socid="+id+"&listed="+isListed,1);
    }

}
function Babel_prospect_getquerystring(lev)
{
    var qstr = "&campagneId=" + document.getElementById('campagne_id').value;
    if (lev == 5)
    {
        var idList = "";
        for (var i=0;i<grid.buffer.rows.length;i++)
        {
            idList+=grid.buffer.getValue(i,0)+'__';
        }
        qstr += "&idList="+idList;
    }
    return(qstr);
}
var debug = false;
function Babel_prospect_xmlhttpPost(strURL, lev)//Ajax engine
{
    if (debug == 1) {
        alert('xmlhttpPost');
    }
    //  alert('ajax'+lev);
    var xmlHttpReq = false;
    var self = this;
    // Mozilla/Safari
    if (window.XMLHttpRequest) {
        self.xmlHttpReq = new XMLHttpRequest();
    }
    // IE
    else
        if (window.ActiveXObject) {
            self.xmlHttpReq = new ActiveXObject('Microsoft.XMLHTTP');
        }
    self.xmlHttpReq.open('POST', strURL, true);
    self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    self.xmlHttpReq.onreadystatechange = function(){
        if (self.xmlHttpReq.readyState == 4  ) {
            if (lev != 4 && lev != 5)
            {
               Babel_prospect_updatepageShowContact(self.xmlHttpReq);
            } else {
                Babel_prospect_resetCamp(self.xmlHttpReq);
            }
        }
    }
    self.xmlHttpReq.send(Babel_prospect_getquerystring(lev));
}
function Babel_prospect_resetCamp(xmlDoc)
{
        window.location.reload();
}
function Babel_prospect_updatepageShowContact(xmlDoc)
{
//    alert (xmlDoc.responseText);
    return true;
}

//checkboxKeyJM = Class.create();
//checkboxKeyJM.prototype = {
//      initialize: function(showKey) {
//          this._checkboxes=[];
//          this._spans=[];
//          this._KeyHash=$H();
//          this._showKey=showKey;
//      },
//      _create: function(gridCell,windowRow) {
//          this._checkboxes[windowRow]=RicoUtil.createFormField(gridCell,'input','checkbox',this.liveGrid.tableId+'_chkbox_'+this.index+'_'+windowRow);
//          this._spans[windowRow]=RicoUtil.createFormField(gridCell,'span',null,this.liveGrid.tableId+'_desc_'+this.index+'_'+windowRow);
//          this._clear(gridCell,windowRow);
//          Event.observe(this._checkboxes[windowRow], "click", this._onclick.bindAsEventListener(this), false);
//      },
//      _onclick: function(e) {
//          var elem=Event.element(e);
//          var windowRow=parseInt(elem.id.split(/_/).pop());
//          var v=this.getValue(windowRow);
//          if (elem.checked)
//            this._addChecked(v,e);
//          else
//            this._remChecked(v,e);
//      },
//      _clear: function(gridCell,windowRow) {
//          var box=this._checkboxes[windowRow];
//          box.checked=false;
//          box.style.display='none';
//          this._spans[windowRow].innerHTML='';
//      },
//      _display: function(v,gridCell,windowRow) {
//          var box=this._checkboxes[windowRow];
//          box.style.display='';
//          box.checked=this._KeyHash.get(v);
//          if (this._showKey) this._spans[windowRow].innerHTML=v;
//      },
//      _SelectedKeys: function() {
//          return this._KeyHash.keys();
//      },
//      _addChecked: function(k,e){
//          this._KeyHash.set(k,1);
//          var elem=Event.element(e);
//          var rownum=parseInt(elem.id.split(/_/).pop());
//          var currentRow = this.liveGrid.buffer.getRows(rownum,1);
//        //alert (this.liveGrid.dragRegistered);
//        Rico.writeDebugMsg("tptp #"+this.liveGrid.dragRegistered[rownum][0] + " "+this.liveGrid.dragRegistered[rownum][1]);
//         var dragObj = this.liveGrid.dragRegistered[rownum][0];
//             dragObj.dragRow = rownum;
//         var dragObjId = this.liveGrid.dragRegistered[rownum][1];
//        dragObj.updateSelection(dragObj.draggables[0],true);
//      },
//      _remChecked: function(k,e){
//          //rem from el drag
//          var elem=Event.element(e);
//          var rownum=parseInt(elem.id.split(/_/).pop());
//          this._KeyHash.unset(k);
//        Rico.writeDebugMsg("tptp #"+this.liveGrid.dragRegistered[rownum][0] + " "+this.liveGrid.dragRegistered[rownum][1]);
//         var dragObj = this.liveGrid.dragRegistered[rownum][0];
//             dragObj.dragRow = rownum;
//         var dragObjId = this.liveGrid.dragRegistered[rownum][1];
//      }
//}

function var_dumpjs(obj) {
   if(typeof obj == 'object') {
      return 'Type: '+typeof(obj)+((obj.constructor)?'Constructor: '+obj.constructor : '')+' Value: ' + obj;
   } else {
      return 'Type: '+typeof(obj)+' Value: '+obj;
   }
}//end function var_dump

function print_r(obj) {
  win_print_r = window.open('about:blank', 'win_print_r');
  win_print_r.document.write('<html><body>');
  r_print_r(obj, win_print_r);
  win_print_r.document.write('</body></html>');
 }

 function r_print_r(theObj, win_print_r) {
  if(theObj.constructor == Array ||
   theObj.constructor == Object){
   if (win_print_r == null)
    win_print_r = window.open('about:blank', 'win_print_r');
   }
   for(var p in theObj){
    if(theObj[p].constructor == Array||
     theObj[p].constructor == Object){
     win_print_r.document.write("<li>["+p+"] =>"+typeof(theObj)+"</li>");
     win_print_r.document.write("<ul>")
     r_print_r(theObj[p], win_print_r);
     win_print_r.document.write("</ul>")
    } else {
     win_print_r.document.write("<li>["+p+"] =>"+theObj[p]+"</li>");
    }
   }
  win_print_r.document.write("</ul>")
 }



function resetCamp()
{
    var url = "ajax/resetCamp_xmlresponse.php";
    Babel_prospect_xmlhttpPost(url,4);
}
function AddAllCamp()
{
    //1 chope les Id dans le buffer
    //send to backend
    //
    var url = "ajax/AddAllCamp_xmlresponse.php";
    Babel_prospect_xmlhttpPost(url,5);
}
//*Menu
// *
// * Event control
//
//menuEvent
//A string that specifies when the grid's menu should be invoked
//'click' -- invoke menu on single-click
//'dblclick' -- invoke menu on double-click (default)
//'contextmenu' -- invoke menu on right-click
//'none' -- no pop-up menu
// *
// * Menu :  Rico. loadModule('LiveGridMenu');
//  ...
//  var ex1=new Rico.LiveGrid ('ex1', buffer, grid_options);
//  ex1.menu=new Rico.GridMenu();
//  ex1.menu.options.dataMenuHandler=myCustomMenuItems;
//  ...
//function myCustomMenuItems(grid,r,c,onBlankRow) {
//  if (buffer.getWindowValue(r,c)=='Special Value')
//    grid.menu.addMenuItem("Special menu item", specialAction);
//}
//function specialAction() {
//  ...
//}
// */
