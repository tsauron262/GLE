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
            frozenColumns:  2,
            headingSort: 'hover',
            canFilterDefault: false,
            FilterLocation: -1,     // put filter on a new header row
            saveColumnInfo: {width:true, filter:true, sort:true},
            //highlightMethod : 'outline',
            highlightElem   : 'cursorRow',
            useUnformattedColWidth: false,
            visibleRows    : '80%',
            columnSpecs:  [
               {canHide: 0, visible: false},
               {Hdg:'Client ?',filterUI:'s',width:30,filterCol:1,sortCol:1},
               {Hdg:'Nom',filterUI:'t^5',width:150, canDrag:false},
               {Hdg:'Ville',filterUI:'t^5',width:150, canDrag:false},
               {Hdg:'D&eacute;partement',filterUI:'t^5',width:160},
               {canHide: 0, visible: false},
               {Hdg:'Effectif',type:'text',width:100,filterCol:6,filterUI:'s', canDrag:true,sortCol:5},
               {canHide: 0, visible: false, canDrag:true},
               {Hdg:'Secteur.',type:'text',width:150,filterUI:'s',filterCol:8, canDrag:true,sortCol:7}],        };
        var buffer = new Rico.Buffer.Base();
            grid = new Rico.LiveGrid('data_grid', buffer, grid_options);
            buffer.loadRowsFromArray(names);
            buffer.fetch(0);
        }); //ferme le Rico.onLoad

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
