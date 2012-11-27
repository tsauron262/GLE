/*
 * GLE by Synopsis et DRSI
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
dojo.require("BabelCalWidget.widget.Calendar");
dojo.require("BabelCalWidget.widget.Timezones");
dojo.require("dojo.date.stamp");
dojo.require("dojo.date.locale");
dojo.require("dojo.cookie");

dojo.addOnLoad(init);
var oCalendar;

function init(){
    oCalendar = dijit.byId("dojoCalendar");
    oCalendar.setTimeZones(BabelCalWidget.widget.timezones);
    oCalendar.selectedtimezone = "Central European"; //dojo.cookie("DCTZ");
    oCalendar.onSetTimeZone = widgetTimeZoneChanged;
    oCalendar.changeEventTimes = true;
    oCalendar.onEventChanged = widgetEventChanged;
    oCalendar.setAbleToCreateNew(true);
    oCalendar.onNewEntry = widgetNewEntry;
    oCalendar.onValueChanged = widgetValueChanged;
    widgetValueChanged(new Date());
}

function widgetValueChanged(dateObj){
     populate()
    var arrDir = document.getElementsByTagName("div");
    var arrMultiDayDiv = new Array();
    var j=0;
    for(var i=0;i<arrDir.length;i++)
    {
        if (arrDir[i].name == "multidayEvent")
        {
            arrMultiDayDiv[j]=arrDir[i];
            j++;
        }
    }

    //List des div ensemble
    var ArrUl = document.getElementsByTagName('ul');
    for (var hh=0;hh<ArrUl.length;hh++)
    {
//        alert(ArrUl[hh].getAttribute('date'));
        if (ArrUl[hh].date)
        {
            var dateArrtmp = split(ArrUl[hh].date,"-");
            alert(parseInt(dateArrtmp[0])+"-"+parseInt(dateArrtmp[1])+"-"+parseInt(dateArrtmp[2]));
        }
    }

    for (h in arrMultiDayDiv)
    {
        if (arrMultiDayDiv[h].parent)
        {
       //     alert(h + ' ' +arrMultiDayDiv[h].maxEvent);

        }

    }

}

function widgetEventChanged(eventId,eventObject){
    var sReturn = "id " + eventId + '=';
    for(var i in eventObject){
        if(typeof(eventObject[i]) != "object"){
            sReturn += i + " = " + eventObject[i] + '';
        }else{
            oChildObject = eventObject[i];
            var sChildReturn = "";
            var iNum = 0;
            for(var j in oChildObject){
                if(iNum > 0){
                    sChildReturn += ", ";
                }
                sChildReturn += j + ": " + oChildObject[j];
                iNum++;
            }
            sReturn += i + " = " + sChildReturn + '';
        }
    }
    oCalendar.refreshScreen();
}

function widgetNewEntry(eventObject){
    var sReturn = "";
    for(var i in eventObject){
        if(typeof(eventObject[i]) != "object"){
            sReturn += i + " = " + eventObject[i] + "";
        }else{
            oChildObject = eventObject[i];
            var sChildReturn = "";
            var iNum = 0;
            for(var j in oChildObject){
                if(iNum > 0){
                    sChildReturn += ", ";
                }
                sChildReturn += j + ": " + oChildObject[j];
                iNum++;
            }
            sReturn += i + " = " + sChildReturn + "";
        }
    }
//    alert(sReturn);
    //Call script to add to back-end db
    oCalendar.refreshScreen();
}

function widgetTimeZoneChanged(){
    //Setting cookie
    if(oCalendar.selectedtimezone == ""){
        dojo.cookie("DCTZ", null, {expires: -1});
    }else{
        dojo.cookie("DCTZ",oCalendar.selectedtimezone, { expires: 3650 });
    }
}

function setLocale(sLocale){
    oCalendar.lang = sLocale;
    oCalendar._preInitUI(new Date(oCalendar.value));
}

function checkboxAnimeMain(obj)
{
    if (obj.checked)
    {
        var arr = new Array();
            arr = obj.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('input');
        for (var i=0;i<arr.length;i++)//showPropal
        {
            if (arr[i].name.match(new RegExp("^"+obj.name+"","g")))
            {
                arr[i].checked=true;
            }
        }
    } else {
        var arr = new Array();
            arr = obj.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('input');
        for (var i=0;i<arr.length;i++)//showPropal
        {
            if (arr[i].name.match(new RegExp("^"+obj.name+"","g")))
            {
                arr[i].checked=false;
            }
        }
    }
    checkboxAnimeMainInit();
}
var TimgId = "";
var e = "";
    function hideTab(pSection)
    {
        var TbodyId = document.getElementById(pSection);
        e = new Effect.toggle(TbodyId, 'slide', { duration: 1.5, delay : 0,queue:{scope:'myscope', position:'end'} } );
            TimgId = document.getElementById(pSection+"Img");

        setTimeout("waitHideTab()",750);
    }
    function waitHideTab()
    {

        if (TimgId.name == "bas")
        {
            TimgId.name ="next";
            TimgId.src = document.getElementById("leftImg").value;
        } else {
            TimgId.name ="bas";
            TimgId.src = document.getElementById("basImg").value;
        }
    }


    function checkboxAnimeMainAll(obj)
    {
        if (obj.checked)
        {
            var Table = document.getElementById('filterTable');
            var allChkBox = Table.getElementsByTagName('input');
            for (var i=0;i<allChkBox.length;i++)
            {
                if (allChkBox[i].type == "checkbox")
                {
                    allChkBox[i].checked = true;
                }
            }
        } else {
            var Table = document.getElementById('filterTable');
            var allChkBox = Table.getElementsByTagName('input');
            for (var i=0;i<allChkBox.length;i++)
            {
                if (allChkBox[i].type == "checkbox")
                {
                    allChkBox[i].checked = false;
                }
            }

        }
    }
    function checkboxAnimeMainInit()
    {
            var Table = document.getElementById('filterTable');
            var allChkBox = Table.getElementsByTagName('input');
            var allTrue = true;
            for (var i=0;i<allChkBox.length;i++)
            {
                if (allChkBox[i].type == "checkbox" && allChkBox[i].id != "showAll")
                {
                    if (!allChkBox[i].checked)
                        if (allTrue)
                            allTrue = false;
                }
            }
            if (allTrue)
            {
                var chk1 = document.getElementById('showAll');
                chk1.checked = true;
//                checkboxAnimeMainAll(chk1);
            } else {
                document.getElementById('showAll').checked = false;
//                checkboxAnimeMainAll(chk1);
            }
    }
    function postInit()
    {
        checkboxAnimeMainInit();
    }

    function checkboxFirst()
    {
        checkboxAnimeMainInit();
    }

function setZimbraFolder(pName,pId)
{
    document.getElementById("FormZimbra").style.display="block";
    document.getElementById("repZimbraId").value=pId;
    document.getElementById("repZimbra").innerHTML = pName;
//    alert( document.getElementById("repZimbraId").value);
}