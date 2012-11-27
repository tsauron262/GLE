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

var grid;


Rico.loadModule('LiveGridAjax','Effect','LiveGridMenu','GLE.css');
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
               {canHide: 0, visible: false, canDrag:false},
               {canHide: 0, visible: false, type: "date",dateFmt:"mmyyyy"},
               { width:30,control: new Rico.TableColumn.link(DOL_URL_ROOT+"/Babel_TechPeople/deplacements/fiche.php?id={0}")},
               {canHide: 0, visible: false, canDrag:false},
               {canHide: 0, visible: false, canDrag:false},
               {filterUI:'t5',width:150 , control: new Rico.TableColumn.link(DOL_URL_ROOT+"/user/fiche.php?id={4}","",5)},
               {width:160,suffix:" &euro;" , type:"number", decPlaces:"2", decPoint:",", thouSep:" " },
               { width:150 },
            ]
        };
//        alert (AJAXURL);
        var buffer= new Rico.Buffer.AjaxXML(AJAXURL);
            grid = new Rico.LiveGrid('data_grid', buffer, grid_options);

        }); //ferme le Rico.onLoad



function var_dumpjs(obj) {
   if(typeof obj == 'object') {
      return 'Type: '+typeof(obj)+((obj.constructor)?'Constructor: '+obj.constructor : '')+' Value: ' + obj;
   } else {
      return 'Type: '+typeof(obj)+' Value: '+obj;
   }
}//end function var_dump


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
