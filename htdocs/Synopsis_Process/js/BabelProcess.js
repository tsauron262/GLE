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
var new_id = 1;
function addStep(obj)
{
    var myid = obj.id.replace(new RegExp("[a-zA-Z_]*","g"),"");
//    alert (myid);
    new_id = parseInt(new_id) + 1;
    var skel = document.getElementById("etape_"+myid);
    var clone = skel.cloneNode(true);
        clone.id = clone.id.replace(new RegExp("_[0-9]*$", "g"),"_"+new_id );
    var cloneArr = clone.getElementsByTagName('input');


    for (var i=0;i<cloneArr.length;i++)
    {
        cloneArr[i].name = cloneArr[i].name.replace(new RegExp("_[0-9]*$", "g"),"_"+new_id );
    }
    var cloneArrDiv = clone.getElementsByTagName('div');
    for (var i=0;i<cloneArrDiv.length;i++)
    {
        if (cloneArrDiv[i].id)
        {

            cloneArrDiv[i].id = cloneArrDiv[i].id.replace(new RegExp("_[0-9]*$", "g"),"_"+new_id );

        }
    }
    var cloneArrSpan = clone.getElementsByTagName('span');
    for (var i=0;i<cloneArrSpan.length;i++)
    {
        cloneArrSpan[i].id = cloneArrSpan[i].id.replace(new RegExp("_[0-9]*$", "g"),"_"+new_id );
    }


    skel.parentNode.appendChild(clone);


    //var stepNum = document.getElementById('StepNum_'+myid).innerHTML;
    //document.getElementById('StepNum_'+new_id).innerHTML = stepNum.replace(new RegExp("\ [0-9]*$", "g")," "+new_id);

}
function delStep(obj)
{
    //confirm
    //efface la div
    var id =  obj.id.replace(new RegExp("[a-zA-Z_]*","g"),"");
    var delDiv = document.getElementById("etape_"+id);
    delDiv.parentNode.removeChild(delDiv);
    //renote les Steps et les id
    var tmp_new_id = 1;
    for (var i=1;i<=new_id;i++)
    {
        if (document.getElementById("etape_"+id))
        {
            var divMod = document.getElementById("etape_"+id);
                divMod.id = "etape_"+tmp_new_id;
            var divContDiv = divMod.getElementsByTagName('div');
            for (var j=0; j<divContDiv.length;j++)
            {
                divContDiv[j].id = divContDiv[j].id.replace(new RegExp("\_[0-9]*$", "g")," "+tmp_new_id)  ;
            }
            var divContSpan = divMod.getElementsByTagName('div');
            for (var j=0; j<divContSpan.length;j++)
            {
                divContSpan[j].id = divContSpan[j].id.replace(new RegExp("\_[0-9]*$", "g")," "+tmp_new_id)  ;
            }
            document.getElementById('StepNum_'+tmp_new_id).innerHTML = "Step "+tmp_new_id;

            tmp_new_id ++;

        }
    }
    new_id = tmp_new_id;

}
function chkBoxString(obj)
{
    if (obj.checked)
    {
        obj.parentNode.parentNode.getElementsByTagName('span')[0].innerHTML = "oui";
    } else {
        obj.parentNode.parentNode.getElementsByTagName('span')[0].innerHTML = "non";
    }

}
