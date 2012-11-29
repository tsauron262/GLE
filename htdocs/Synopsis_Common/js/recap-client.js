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
 function recap_client_getDatas()
{
    var strUrl = DOL_URL_ROOT + "/Synopsis_Common/ajax/recap-client_xmlresponse.php?level=1&socid="+SOCID;
    var dur = document.getElementById('duree').innerHTML;
    if ("x"+dur != "x") { strUrl += "&duree="+dur; }
    recap_client_xmlhttpPost(strUrl,1);
}

function recap_client_getquerystring(lev)
{
//    alert(lev);
    return(true);
}


var debug = false;
function recap_client_xmlhttpPost(strURL, lev)//Ajax engine
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
        if (self.xmlHttpReq.readyState == 4) {
                if (lev == 1)
                {
                    recap_client_updatepage(self.xmlHttpReq);
                } else if (lev==2){
                    recap_client_updatepageShowContact(self.xmlHttpReq);
                }

        }
    }
        self.xmlHttpReq.send(recap_client_getquerystring(lev));
}

    var PairImpair= new Array();
        PairImpair["pair"] = "impair";
        PairImpair["impair"] = "pair";

function pairImpair(str)
{
    return (PairImpair[str]);

}
function recap_client_updatepage(xmlDoc)
{
    var html = xmlDoc.responseText;
    //alert(html);

    //1 erase the table
    var table = document.getElementById('recapMainTable');
    var tbodyRem = document.getElementById('recapMain');
        if (tbodyRem) table.removeChild(tbodyRem);


    //2 add the new datas
        tbody = document.createElement('tbody');
        tbody.id = "recapMain";
    var xml=xmlDoc.responseXML.documentElement;
    var MainRecap = xml.getElementsByTagName('recapMain')[0].getElementsByTagName('row');

        var trArray=new Array();
            trArray[0] = "info";
            trArray[1] = "date_valid";
            trArray[2] = "title";
            trArray[3] = "ref";
            trArray[4] = "propalStatut";
            trArray[5] = "remise_percent";
            trArray[6] = "remise_absolue";
            trArray[7] = "remise";
            trArray[8] = "total_ht";
            trArray[9] = "commande";
            trArray[10] = "commandeStatut";
            trArray[11] = "facture";
            trArray[12] = "factureStatut";
            trArray[13] = "paye";
        var trWidth=new Array();
            trWidth[0] = "5px";
            trWidth[1] = "30px";
            trWidth[2] = "auto";
            trWidth[3] = "100px";
            trWidth[4] = "80px";
            trWidth[5] = "50px";
            trWidth[6] = "50px";
            trWidth[7] = "50px";
            trWidth[8] = "80px";
            trWidth[9] = "100px";
            trWidth[10] = "80px";
            trWidth[11] = "100px";
            trWidth[12] = "80px";
            trWidth[13] = "40px";
        var trAlign=new Array();
            trAlign[0] = "center";
            trAlign[1] = "center";
            trAlign[2] = "left";
            trAlign[3] = "left";
            trAlign[4] = "left";
            trAlign[5] = "center";
            trAlign[6] = "center";
            trAlign[7] = "center";
            trAlign[8] = "center";
            trAlign[9] = "left";
            trAlign[10] = "left";
            trAlign[11] = "left";
            trAlign[12] = "left";
            trAlign[13] = "center";


    var className = "impair";
    for (var i=0;i<MainRecap.length;i++)
    {
        var datas = MainRecap[i];
        var trValue = new Array();
            var tr = document.createElement('tr');
                tr.className = pairImpair(className);
                className = pairImpair(className);
            for (var j =0; j<trArray.length;j++)
            {
                trValue[j]=new Array();
                trValue[j]['name']=new Array();
                trValue[j]['value']=new Array();

                if (datas.getElementsByTagName(trArray[j]) &&
                    datas.getElementsByTagName(trArray[j])[0] &&
                    datas.getElementsByTagName(trArray[j])[0].firstChild )
                {
                    trValue[j]['name']=trArray[j];
                    trValue[j]['value']=datas.getElementsByTagName(trArray[j])[0].firstChild.nodeValue;
                } else {
                    trValue[j]['name']=trArray[j];
                    trValue[j]['value']=" ";
                }
            }
            for(var h=0;h<trValue.length;h++)
            {
                    var td = document.createElement('td');
                        td.innerHTML = trValue[h]['value'];
                        td.style.textAlign = trAlign[h];
                        td.style.width = trWidth[h];
                    tr.appendChild(td);

            }
        tbody.appendChild(tr);
    }
    table.appendChild(tbody);


    var tableProd = document.getElementById('recapProdTable');
    var tbodyRemProd = document.getElementById('recapProd');
        if (tbodyRemProd) tableProd.removeChild(tbodyRemProd);

        tbodyProd = document.createElement('tbody');
        tbodyProd.id = "recapProd";
    var xmlProd=xmlDoc.responseXML.documentElement;
    var MainRecapProd = xml.getElementsByTagName('recapProdTable')[0].getElementsByTagName('row');

        var trArrayProd=new Array();
            trArrayProd[0] = "ProdQty";
            trArrayProd[1] = "ProdDesc";
        var trWidthProd=new Array();
            trWidthProd[0] = "100px";
            trWidthProd[1] = "auto";
        var trAlignProd=new Array();
            trAlignProd[0] = "center";
            trAlignProd[1] = "left";

        className = "impair";
        //alert(MainRecapProd.length);
    for (var i=0;i<MainRecapProd.length;i++)
    {
        var datas = MainRecapProd[i];
        var trValue = new Array();
            var tr = document.createElement('tr');
                tr.className = pairImpair(className);
                className = pairImpair(className);
            for (var j =0; j<trArrayProd.length;j++)
            {
                trValue[j]=new Array();
                trValue[j]['name']=new Array();
                trValue[j]['value']=new Array();

                if (datas.getElementsByTagName(trArrayProd[j]) &&
                    datas.getElementsByTagName(trArrayProd[j])[0] &&
                    datas.getElementsByTagName(trArrayProd[j])[0].firstChild )
                {
                    trValue[j]['name']=trArrayProd[j];
                    trValue[j]['value']=datas.getElementsByTagName(trArrayProd[j])[0].firstChild.nodeValue;
                } else {
                    trValue[j]['name']=trArrayProd[j];
                    trValue[j]['value']=" ";
                }
            }
            for(var h=0;h<trValue.length;h++)
            {
                    var td = document.createElement('td');
                        td.innerHTML = trValue[h]['value'];
                        td.style.textAlign = trAlignProd[h];
                        td.style.width = trWidthProd[h];
                    tr.appendChild(td);
            }
        tbodyProd.appendChild(tr);
    }
    tableProd.appendChild(tbodyProd);

    var tableServ = document.getElementById('recapServTable');
    var tbodyRemServ = document.getElementById('recapServ');
        if (tbodyRemServ) tableServ.removeChild(tbodyRemServ);

//    var tableServ = document.getElementById('recapMainTable');

        tbodyServ = document.createElement('tbody');
        tbodyServ.id = "recapServ";
    var xmlServ=xmlDoc.responseXML.documentElement;
    var MainRecapServ = xml.getElementsByTagName('recapServTable')[0].getElementsByTagName('row');

        var trArrayServ=new Array();
            trArrayServ[0] = "ServQty";
            trArrayServ[1] = "ServDesc";
        var trWidthServ=new Array();
            trWidthServ[0] = "100px";
            trWidthServ[1] = "auto";
        var trAlignServ=new Array();
            trAlignServ[0] = "center";
            trAlignServ[1] = "left";

        className = "impair";
        //alert(MainRecapServ.length);
    for (var i=0;i<MainRecapServ.length;i++)
    {
        var datas = MainRecapServ[i];
        var trValue = new Array();
            var tr = document.createElement('tr');
                tr.className = pairImpair(className);
                className = pairImpair(className);
            for (var j =0; j<trArrayServ.length;j++)
            {
                trValue[j]=new Array();
                trValue[j]['name']=new Array();
                trValue[j]['value']=new Array();

                if (datas.getElementsByTagName(trArrayServ[j]) &&
                    datas.getElementsByTagName(trArrayServ[j])[0] &&
                    datas.getElementsByTagName(trArrayServ[j])[0].firstChild )
                {
                    trValue[j]['name']=trArrayServ[j];
                    trValue[j]['value']=datas.getElementsByTagName(trArrayServ[j])[0].firstChild.nodeValue;
                } else {
                    trValue[j]['name']=trArrayServ[j];
                    trValue[j]['value']=" ";
                }
            }
            for(var h=0;h<trValue.length;h++)
            {
                    var td = document.createElement('td');
                        td.innerHTML = trValue[h]['value'];
                        td.style.textAlign = trAlignServ[h];
                        td.style.width = trWidthServ[h];
                    tr.appendChild(td);
            }
        tbodyServ.appendChild(tr);
        tbodyServ.id='recapServ';
    }
    tableServ.appendChild(tbodyServ);
}
var Rem;

function showContact(pId,pObj)
{
    Rem = pObj;
    var propalId = pId;
    var strUrl = DOL_URL_ROOT + "/Synopsis_Common/ajax/recap-client_xmlresponse.php?level=2&socid="+SOCID;
//    var dur = document.getElementById('duree').innerHTML;
    if ("x"+pId != "x") { strUrl += "&propalid="+pId; }
//    alert(strUrl);
    recap_client_xmlhttpPost(strUrl,2);

}
function recap_client_updatepageShowContact(xmlDoc)
{


    var html = xmlDoc.responseText;
    var xml=xmlDoc.responseXML.documentElement;
    var contArr = new Array();
    var contArrIdx = new Array();
    //get Element from xml
    var htmlObj_table = document.createElement('table');
        htmlObj_table.style.height="100%"
        htmlObj_table.style.width="400px";
        htmlObj_table.style.minWidth="400px";
    var htmlObj_tbody = document.createElement('tbody');

    for(var i=0;i<xml.getElementsByTagName('row').length; i++)
    {
        var htmlObj_thtxt = document.createTextNode(xml.getElementsByTagName('row')[i].getElementsByTagName('libelle')[0].firstChild.nodeValue);
        var htmlObj_th = document.createElement('th');
            htmlObj_th.appendChild(htmlObj_thtxt);
            htmlObj_th.setAttribute('colspan',4);
            htmlObj_th.setAttribute('nowrap','nowrap');
            htmlObj_th.width='200px';
        var htmlObj_trth = document.createElement('tr');
            htmlObj_trth.appendChild(htmlObj_th);
            htmlObj_tbody.appendChild(htmlObj_trth);
        var htmlObj_tr = document.createElement('tr');
            htmlObj_tr.style.width="400px";

        for(var j=0; j<xml.getElementsByTagName('row')[i].childNodes.length;j++)
        {
            //get Id of the row
            if (xml.getElementsByTagName('row')[i].childNodes[j].nodeType != 3 ) //&& xml.getElementsByTagName('row')[i].childNodes[j] && xml.getElementsByTagName('row')[i].childNodes[j].firstChild
            {
                var nodeName = xml.getElementsByTagName('row')[i].childNodes[j].nodeName;
                var nodeValue = "";
                if (xml.getElementsByTagName('row')[i].childNodes[j].firstChild)
                {
                    nodeValue = xml.getElementsByTagName('row')[i].childNodes[j].firstChild.nodeValue;
                }
//alert(nodeName + " "+nodeValue);
                var htmlObj_td1 = document.createElement('td');
                    htmlObj_td1.setAttribute('nowrap','nowrap');
                    htmlObj_td1.innerHTML = nodeValue;
                    htmlObj_td1.style.width="200px";
                    htmlObj_tr.appendChild(htmlObj_td1);
//                alert(nodeName+' '+nodeValue);
            }
        }
        htmlObj_tbody.appendChild(htmlObj_tr);
    }
    htmlObj_table.appendChild(htmlObj_tbody);

//    alert(sHtml);
    //draw a tooltip div
//    var ttdiv = document.createElement('div');
//        ttdiv.id = "ttdiv";
//        ttdiv.name = "ttdiv";
//        ttdiv.style.backgroundColor='#444466';
//        ttdiv.style.width="auto";
//        ttdiv.style.height="auto";
//        ttdiv.style.display='block';
//        ttdiv.style.position = "absolute";
//        ttdiv.style.zIndex=100;
//        ttdiv.style.color='#FFFFFF';
////        ttdiv.innerHTML="Test";
//        ttdiv.appendChild(htmlObj_table);
//     var topX = recap_client_findPosX(Rem);
//     var topY = recap_client_findPosY(Rem);
//        ttdiv.style.top = topY + 20 +"px";
//        ttdiv.style.left = topX +"px";
//    document.getElementsByTagName('body')[0].appendChild(ttdiv);
Tip(htmlObj_table.innerHTML);
//Rem.setAttribute("onmouseout",'UnTip()');

//    alert(ttdiv);

}
function recap_client_findPosX(obj)
  {
    var curleft = 0;
    if(obj.offsetParent)
        while(1)
        {
          curleft += obj.offsetLeft;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.x)
        curleft += obj.x;
    return curleft;
  }

  function recap_client_findPosY(obj)
  {
    var curtop = 0;
    if(obj.offsetParent)
        while(1)
        {
          curtop += obj.offsetTop;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.y)
        curtop += obj.y;
    return curtop;
  }