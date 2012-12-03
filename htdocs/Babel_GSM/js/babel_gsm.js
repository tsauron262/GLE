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
var MenuDisplay = "false";
var SecMenuDisplay = "false";
function MenuDisplayCSS()
{
    if (SecMenuDisplay=="true")
    {
        var arrSecMenu = document.getElementsByName("secMenu");
        for(var i=0;i<arrSecMenu.length;i++)
        {
            arrSecMenu[i].style.display="none";
            SecMenuDisplay="false";
        }

    }
    if (MenuDisplay=="false")
    {
        document.getElementById('menuDiv').style.display="block";
        MenuDisplay="true";
    } else {
        document.getElementById('menuDiv').style.display="none" ;
        MenuDisplay="false";
    }
}
function SecMenuDisplayCSS(idNum)
{
    if (MenuDisplay=="true")
    {
        document.getElementById('menuDiv').style.display="none";
        MenuDisplay="false";
    }
    if (SecMenuDisplay=="true")
    {
        var arrSecMenu = document.getElementsByName("secMenu");
        for(var i=0;i<arrSecMenu.length;i++)
        {
            if ( String(arrSecMenu[i].id) != String("secMenu"+idNum))
            {
                arrSecMenu[i].style.display="none";
                SecMenuDisplay="false";
            } else {
                SecMenuDisplay="true";
            }
        }

    }

    if (SecMenuDisplay=="false")
    {

        document.getElementById('secMenu'+idNum).style.display="block";
        var wdth = leftPos(document.getElementById("mnubut_sec"+idNum));
        document.getElementById('secMenu'+idNum).style.left = parseInt(wdth ) +"px";
        SecMenuDisplay="true";
    } else {
        document.getElementById('secMenu'+idNum).style.display="none" ;
        SecMenuDisplay="false";
    }
}

function leftPos(el){
    xPos = el.offsetLeft;
    tmpEl = el.offsetParent;
    while (tmpEl != null) {
        xPos += tmpEl.offsetLeft;
        tmpEl = tmpEl.offsetParent;
    }
    return xPos;
}

function correctSize()
{
    //alert (screen.width);
    var tableArr=document.getElementsByTagName('TABLE');
    var divArr=document.getElementsByTagName('DIV');
    var swidth = screen.width - 7 ;
    for (var i=0;i<tableArr.length;i++)
    {
        if (tableArr[i].name='noCorrect') { continue;}
        tableArr[i].style.width=swidth+"px";
    }
    for (var i=0;i<divArr.length;i++)
    {
        if (divArr[i].name='noCorrect') { continue;}
        divArr[i].style.width=swidth+"px";
    }
    for (var i=0;i<document.getElementsByName('forceresize').length; i++)
    {
        document.getElementsByName('forceresize')[i].style.width=swidth+"px";
    }
}
function DisplayDet(inpt)
{
    location.href=inpt+".php";

}
function MenuDisplayCSS_secondary(inpt,inpt_id,propalid)
{
        location.href=inpt+".php?"+inpt_id+"="+propalid;
}
