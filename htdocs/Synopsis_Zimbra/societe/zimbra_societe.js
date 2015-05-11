/*
 * GLE by Synopsis & DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.Synopsis-erp.com
 *
 */
 var imgLoadRem = "";
function getZimbraContact(str){

    //loadingContact img
    var loadingContact = document.getElementById('loadingContact');
    imgLoadRem=document.createElement("img");
    imgLoadRem.src=DOL_URL_ROOT+"/Zimbra/contacts/img/wait-22px.gif";
    imgLoadRem.setAttribute('src',DOL_URL_ROOT+"/Zimbra/contacts/img/wait-22px.gif");
    loadingContact.appendChild(imgLoadRem);

    //Ajax magic
    contactFiche_xmlhttpPost(DOL_URL_ROOT+"/Zimbra/contacts/ajaxContact.response.php?str=" + str, 1);
}
function contactFiche_getquerystring(lev)
{
    return(true);
}
var debug = false;
function contactFiche_xmlhttpPost(strURL, lev)//Ajax engine
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
                contactFiche_updatepage(self.xmlHttpReq,lev);
        }
    }
        self.xmlHttpReq.send(contactFiche_getquerystring(lev));
}

function closeHidDiv()
{
    document.getElementById('hiddiv1').style.display='none';
    document.getElementById('hiddiv').style.display='none';
}

var xml_Res = "";
function contactFiche_updatepage(xmlDoc)
{
    //var html = xmlDoc.responseText;\n
    var tbody = document.getElementById('zimbraTbody');
    //tbody.parentNode.removeChild(tbody);\n
    contactFiche_clearInnerHTML(tbody);
    var xml=xmlDoc.responseXML.documentElement;
    xml_Res = xml;
    //alert(xml);\n
    var table = document.createElement('table');
        table.className = 'border';
        table.style.width = '100%';
    var tbod = document.createElement('tbody');

    var thtr = document.createElement('tr');

    var th1txt = document.createTextNode('Repertoire');
    var th2txt = document.createTextNode('Nom');
    var th3txt = document.createTextNode('Email');

    var th1 = document.createElement('th');
        th1.appendChild(th1txt);
    var th2 = document.createElement('th');
        th2.appendChild(th2txt);
    var th3 = document.createElement('th');
        th3.appendChild(th3txt);

        thtr.appendChild(th1);
        thtr.appendChild(th2);
        thtr.appendChild(th3);

        tbod.appendChild(thtr);
    var className = 'impairCont';
    for (var i=0;i<xml.getElementsByTagName('contact').length;i++)
    {
        if( className =='pairCont') {
            className ='impairCont';
        } else {
            className ='pairCont';
        }
        var tr = document.createElement('tr');
            tr.className = className;
            tr.id = xml.getElementsByTagName('contact')[i].getAttribute('id');
            tr.onClick=function(){ selectCont(this);};
            tr.setAttribute('onClick','selectCont(this)');

        var td1 = document.createElement('td');
    //alert(xml.getElementsByTagName('contact').length);\n
        var td1txt = document.createTextNode(xml.getElementsByTagName('contact')[i].getElementsByTagName('parentFolderName')[0].firstChild.nodeValue);
            td1.appendChild(td1txt)
//            if (i<10) { alert (xml.getElementsByTagName('contact')[i].getElementsByTagName('parentFolderName')[0].firstChild.nodeValue);}
        var td2 = document.createElement('td');
        var td2txt = document.createTextNode(xml.getElementsByTagName('contact')[i].getElementsByTagName('name')[0].firstChild.nodeValue);
            td2.appendChild(td2txt)
        var td3 = document.createElement('td');
        var td3txt = document.createTextNode(xml.getElementsByTagName('contact')[i].getElementsByTagName('email')[0].firstChild.nodeValue);
            td3.appendChild(td3txt)
            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
        tbod.appendChild(tr)
    }
    //alert(xml);\n
    table.appendChild(tbod);
    tbody.appendChild(table);
    imgLoadRem.parentNode.removeChild(imgLoadRem);
    //alert(html);\n
    }
    function contactFiche_clearInnerHTML(obj)// tool to delete the content of an HTML object.
    {
        while(obj.firstChild) obj.removeChild(obj.firstChild);
    }
    var SelectPaysSave = "";
    var launched = false;
    function selectCont(obj)
    {
        //reset if needed;
        if (launched == true)
        {
            resetZimContactForm();
        }

        launched = true;

        //close the divs\n

        document.getElementById('hiddiv1').style.display='none';
        document.getElementById('hiddiv').style.display='none';

        //save the pays SELECT
        SelectPaysSave = document.getElementsByName('pays_id')[0].cloneNode(true);

        //fill the form\n
        if (xml_Res)
        {
//            alert (xml_Res + obj.id);
//            var test = xml_Res.getElementsByTagName("contact");
            var test1 = xml_Res.getElementsByTagName("contactDet");
//            var nodeContact = FindXmlById(test,obj.id);
            var nodeContact1 = FindXmlById(test1,obj.id);
//    alert(nodeContact1.childNodes.length);
    var sHtml = "";
    var ArrRes = new Array();
            for (var i=0;i<nodeContact1.childNodes.length;i++)
            {
                if (nodeContact1.childNodes[i].tagName)
                {
                    ArrRes[nodeContact1.childNodes[i].nodeName]=nodeContact1.childNodes[i].firstChild.nodeValue;
                    sHtml += nodeContact1.childNodes[i].tagName +" "+ nodeContact1.childNodes[i].nodeName + " "+ nodeContact1.childNodes[i].firstChild.nodeValue +"<br>\n";
                    //alert (i + " " + nodeContact1.childNodes[i].tagName);
                }

            }
            //   DEBUG
            var debug = document.getElementById('debugDiv');
            debug.innerHTML=sHtml;
            //alert (sHtml);
            //fill Form

            //reset all input , select, et textarea
            document.getElementById("formAdd").reset();

            //company
            if (ArrRes['company'])
            {
                if (ArrRes['company'] && ArrRes['company']+"x" != "x") { document.getElementsByName('nom')[0].value = ArrRes['company']; }
            }

            //email select
            var countEmail = 0;
            var ArrAsk = new Array();
            if (ArrRes['email'] && ArrRes['email']+"x" !="x" && ArrRes['email'].match(/@/))
            {
                ArrAsk[countEmail]=ArrRes['email'];
                countEmail++;
            }
            for (var hh=2;hh<4;hh++)
            {
                if (ArrRes['email'+hh] && ArrRes['email'+hh]+"x" !="x" && ArrRes['email'+hh].match(/@/))
                {
                    ArrAsk[countEmail]=ArrRes['email'+hh];
                    countEmail++;
                }
            }

            if (countEmail > 1)
            {
                InputToSelect("email",ArrAsk);
            } else {
                //email
                if (ArrRes['email'] && ArrRes['email']+"x" != "x" && ArrRes['email'].match(/@/)) { document.getElementsByName('email')[0].value = ArrRes['email']; }
                else if (ArrRes['email2'] && ArrRes['email2']+"x" != "x" && ArrRes['email2'].match(/@/) ) { document.getElementsByName('email')[0].value = ArrRes['email2']; }
                else if (ArrRes['email3'] && ArrRes['email3']+"x" != "x" && ArrRes['email3'].match(/@/) ) { document.getElementsByName('email')[0].value = ArrRes['email3']; }

            }

            //notes
            if (ArrRes['notes'] && ArrRes['notes']+"x" != "x") {
                if (document.getElementById('note'))
                {
                    document.getElementById('note').value = ArrRes['notes'];
                }  else {
                    var binpt = document.createElement('input');
                        binpt.setAttribute('type',"hidden");
                        binpt.id='note';
                        binpt.name='note';
                        binpt.setAttribute('name',"note");
                        binpt.value = ArrRes['notes'];
                    document.getElementById('formAdd').appendChild(binpt);

                }
            }


            //Country    Street    City  PostalCode URL
            var TmpArr = new Array();
                TmpArr[0] = "Country";
                TmpArr[1] = "Street";
                TmpArr[2] = "City";
                TmpArr[3] = "PostalCode";
                TmpArr[4] = "URL";
            var TmpArrDol = new Array();
                TmpArrDol[0] = "pays_id";
                TmpArrDol[1] = "addresse";
                TmpArrDol[2] = "ville";
                TmpArrDol[3] = "cp";
                TmpArrDol[4] = "url";

            var TmpArrIdx= new Array();
                TmpArrIdx[0]="work";
                TmpArrIdx[1]="home";
                TmpArrIdx[2]="other";
            var TmpRes = new Array();
            var countRes = new Array();
            for (var i=0;i<TmpArr.length;i++)
            {
                if (!countRes[TmpArr[i]])
                {
                    countRes[TmpArr[i]]=0;
                    TmpRes[TmpArr[i]] = new Array();
                }
                for (var j=0;j<TmpArrIdx.length;j++)
                {
                    if (ArrRes[TmpArrIdx[j]+TmpArr[i]] && ArrRes[TmpArrIdx[j]+TmpArr[i]]+"x" != "x")
                    {
                        TmpRes[TmpArr[i]][countRes[TmpArr[i]]]=ArrRes[TmpArrIdx[j]+TmpArr[i]];
                        countRes[TmpArr[i]]++;
                    }
                }
            }
            for (var i = 0; i < TmpArr.length; i++)
            {
                if (TmpArr[i] == "Country")
                {
                    //On parse
                    var SelectTmp = document.getElementsByName('pays_id')[0];
                    for (var hh=0;hh<SelectTmp.options.length;hh++)
                    {
                        var RegEx = new RegExp(TmpRes[TmpArr[i]][0],"i");
                        if (SelectTmp.options[hh].text.match(RegEx))
                        {
                            SelectTmp.options[hh].selected=true;
                            break;
                        }
                    }
                } else {
                    if (countRes[TmpArr[i]] > 1) //n elem
                    {
    //                    alert (TmpArrDol[i] + " " + TmpRes[TmpArr[i]]);
                        InputToSelect(TmpArrDol[i],TmpRes[TmpArr[i]])
                    } else if (countRes[TmpArr[i]] == 1){ //1 elem logiquement tri� par importance
                        document.getElementsByName(TmpArrDol[i])[0].value = TmpRes[TmpArr[i]][0];
                    }

                }

            }
            //Tels =>
          var arrTel = new Array();
              arrTel['grp0']=new Array();
              arrTel['grp1']=new Array();
              arrTel['grp2']=new Array();
          var arrTelCat = new Array();
              arrTelCat['grp0']="Courants";
              arrTelCat['grp1']="Soci�t�";
              arrTelCat['grp2']="Autres";

          var iter = 0;
          if ( ArrRes['otherPhone'] && ArrRes['otherPhone']+"x" != "x")
          {
              arrTel['grp0'][iter] = new Array();
              arrTel['grp0'][iter]["value"]=iter;
              arrTel['grp0'][iter]["text"]="Autres : " + ArrRes['otherPhone'];
              iter ++;
          }
          if ( ArrRes['homePhone'] && ArrRes['homePhone']+"x" != "x")
          {
              arrTel['grp0'][iter] = new Array();
              arrTel['grp0'][iter]["value"]=iter;
              arrTel['grp0'][iter]["text"]="T�l perso : " + ArrRes['homePhone'];
              iter ++;
          }
          if ( ArrRes['homePhone2'] && ArrRes['homePhone2']+"x" != "x")
          {
              arrTel['grp0'][iter] = new Array();
              arrTel['grp0'][iter]["value"]=iter;
              arrTel['grp0'][iter]["text"]="T�l perso : " + ArrRes['homePhone2'];
              iter ++;
          }
          if ( ArrRes['workPhone'] && ArrRes['workPhone']+"x" != "x")
          {
              arrTel['grp0'][iter] = new Array();
              arrTel['grp0'][iter]["value"]=iter;
              arrTel['grp0'][iter]["text"]="T�l pro : " + ArrRes['workPhone'];
              iter ++;
          }
          if ( ArrRes['workPhone2'] && ArrRes['workPhone2']+"x" != "x")
          {
              arrTel['grp0'][iter] = new Array();
              arrTel['grp0'][iter]["value"]=iter;
              arrTel['grp0'][iter]["text"]="T�l pro : " + ArrRes['workPhone2'];
              iter ++;
          }
          var iter1 = 0;
          if ( ArrRes['companyPhone'] && ArrRes['companyPhone']+"x" != "x")
          {
              arrTel['grp1'][iter1] = new Array();
              arrTel['grp1'][iter1]["value"]=iter;
              arrTel['grp1'][iter1]["text"]="T�l soci�t� : " + ArrRes['companyPhone'];
              iter ++;
              iter1++;
          }
          iter2 = 0;
          if ( ArrRes['mobilePhone'] && ArrRes['mobilePhone']+"x" != "x")
          {
              arrTel['grp2'][iter2] = new Array();
              arrTel['grp2'][iter2]["value"]=iter;
              arrTel['grp2'][iter2]["text"]="GSM : " + ArrRes['mobilePhone'];
              iter ++;
              iter2++;
          }
          if ( ArrRes['callbackPhone'] && ArrRes['callbackPhone']+"x" != "x")
          {
              arrTel['grp2'][iter2] = new Array();
              arrTel['grp2'][iter2]["value"]=iter;
              arrTel['grp2'][iter2]["text"]="T�l rappel : " + ArrRes['callbackPhone'];
              iter ++;
              iter2++;
          }
          if ( ArrRes['assistantPhone'] && ArrRes['assistantPhone']+"x" != "x")
          {
              arrTel['grp2'][iter2] = new Array();
              arrTel['grp2'][iter2]["value"]=iter;
              arrTel['grp2'][iter2]["text"]="T�l assitant(e) : " + ArrRes['assistantPhone'];
              iter ++;
              iter2++;
          }
          if ( ArrRes['pager'] && ArrRes['pager']+"x" != "x")
          {
              arrTel['grp2'][iter2] = new Array();
              arrTel['grp2'][iter2]["value"]=iter;
              arrTel['grp2'][iter2]["text"]="Pager : " + ArrRes['pager'];
              iter ++;
              iter2++;
          }
          if ( ArrRes['carPhone'] && ArrRes['carPhone']+"x" != "x")
          {
              arrTel['grp2'][iter2] = new Array();
              arrTel['grp2'][iter2]["value"]=iter;
              arrTel['grp2'][iter2]["text"]="T�l voiture : " + ArrRes['carPhone'];
              iter ++;
              iter2++;
          }
//display arrTel
        if (iter > 0)
        {
            InputToSelectCplx('tel',arrTel,arrTelCat);
//            InputToSelectCplx('phone_mobile',arrTel,arrTelCat);
//            InputToSelectCplx('phone_perso',arrTel,arrTelCat);
        }


            //fax select
            var countFax = 0;
            var ArrAskFax = new Array();
            var tmpFaxArr = new Array();
                tmpFaxArr[0]='workFax';
                tmpFaxArr[1]='homeFax';
                tmpFaxArr[2]='otherFax';
            for (var hh=0;hh<3;hh++)
            {
                if (ArrRes[tmpFaxArr[hh]] && ArrRes[tmpFaxArr[hh]]+"x" !="x")
                {
                    ArrAskFax[countFax]=ArrRes[tmpFaxArr[hh]];
                    countFax++;
                }
            }

            if (countFax > 1)
            {
                InputToSelect("fax",ArrAskFax);
            } else {
                //Fax
                if (ArrRes['workFax'] && ArrRes['workFax']+"x" != "x" ) { document.getElementsByName('fax')[0].value = ArrRes['workFax']; }
                else if (ArrRes['homeFax'] && ArrRes['homeFax']+"x" != "x" ) { document.getElementsByName('fax')[0].value = ArrRes['homeFax']; }
                else if (ArrRes['otherFax'] && ArrRes['otherFax']+"x" != "x" ) { document.getElementsByName('fax')[0].value = ArrRes['otherFax']; }

            }
            //url



          //  if (ArrRes['otherPhone'] && ArrRes['otherPhone']+"x" != "x") { document.getElementsByName('phone_perso')[0].value = ArrRes['otherPhone']; }

//on a besoin de
/*
 * nom
 * prenom
 * email
 * societe
 * titre / civility
 * poste
 * address
 * cp
 * ville
 * pays
 * tel pro
 * tel perso
 * tel portable
 * fax
 * email
 * jabberid
 * visibilit�
 * note
 */




//        <firstName>testPrenom</firstName>
//        <jobTitle>test</jobTitle>

//        <mobilePhone>99 99 99 99 99</mobilePhone>
//        <callbackPhone>55 55 55 55 55</callbackPhone>
//        <assistantPhone>33 33 33 33 33</assistantPhone>
//        <pager>01 01 01 01 01</pager>
//        <carPhone>02 02 02 02 02</carPhone>

//        <middleName>test2emPrenom</middleName>
//        <birthday>2009-03-18</birthday>
//        <notes>test Remarque</notes>
//        <lastName>testNom</lastName>
//        <fullName>testPrenom test2emPrenom testNom</fullName>

//        <company>livingston</company>
//        <companyPhone>44 44 44 44 44</companyPhone>

//        <otherCountry>otherTestPays</otherCountry>
//        <otherState>otherTestRegion</otherState>
//        <otherStreet>otherTestStreet</otherStreet>
//        <otherURL>www.otheTestUrl.com</otherURL>
//        <otherCity>otherTestTown</otherCity>
//        <otherPhone>12 12 12 12 12</otherPhone>
//        <otherFax>13 13 13 13 13</otherFax>
//        <otherPostalCode>otherTestCP</otherPostalCode>

//        <homeCity>testHomeTown</homeCity>
//        <homeState>testHomeRegion</homeState>
//        <homePostalCode>testHomeCP</homePostalCode>
//        <homeURL>www.testHomeURL.com</homeURL>
//        <homeStreet>testHomeStreet</homeStreet>
//        <homePhone2>77 77 77 77 77</homePhone2>
//        <homePhone>66 66 66 66 66</homePhone>
//        <homeCountry>testHomePayes</homeCountry>
//        <homeFax>88 88 88 88 88</homeFax>

//        <workCity>workTown</workCity>
//        <workStreet>workStreet</workStreet>
//        <workFax>22 22 22 22 22</workFax>
//        <workState>workRegion</workState>
//        <workPhone>00 00 00 00 00</workPhone>
//        <workPhone2>11 11 11 11 11</workPhone2>
//        <workCountry>workPays</workCountry>
//        <workURL>www.testworkurl.com</workURL>
//        <workPostalCode>workPostal</workPostalCode>

//        <imAddress1>local://jabberIMZimbratest@test.com</imAddress1>
//        <imAddress2>other://jabberIMAutretest@test.com</imAddress2>
//        <imAddress3>msn://jabberIMMsntest@test.com</imAddress3>

//        <email>mail1@test.com</email>
//        <email2>mail2@test.com</email2>
//        <email3>mail3@test.com</email3>

//        <image></image>


        }
    }

    function InputToSelect(objId,selDataArr)
    {
        var obj = document.getElementById(objId);
        if (obj)
        {
            var Objname = obj.name;
            var id = objId;
            var select = document.createElement('SELECT');
                select.id = id+"_selectbox";
                select.name = Objname+"_selectbox";
            var inputH = document.createElement('input');
                inputH.setAttribute("type","hidden");
                inputH.id = id;
                inputH.name = Objname;
                inputH.setAttribute("name",Objname);
                inputH.value = selDataArr[0];

            for (var i=0;i<selDataArr.length;i++)
            {
                var j=i;
                var option = document.createElement('option');
                    option.value=j;
                    option.text=selDataArr[i];
                    select.appendChild(option);
            }
            select.onchange ="change_inpt_value(this);";
            select.setAttribute('onChange',"change_inpt_value(this);");

            //Vire le input et place le select
            var objParent = obj.parentNode;
            obj.parentNode.removeChild(obj);
            objParent.appendChild(select);
            objParent.appendChild(inputH);
        }
    }
function resetZimContactForm()
{
    var SelectDiv = document.getElementsByTagName('select');
    var cnt = SelectDiv.length;
    var arrRemSel = new Array();
        for (var i=0; i < cnt; i++)
        {
            if (SelectDiv[i] && SelectDiv[i].id && SelectDiv[i].id +"x" != "x" )
            {
                if (SelectDiv[i].id.match(/_selectbox$/)  )
                {
                    var remId = SelectDiv[i].id;
                    if (remId+"x" == "x")
                    {
                        continue;
                        remId =  SelectDiv[i].name;
                    }
                    var parentNode1 = SelectDiv[i].parentNode;
                    if (parentNode1)
                    {
                        arrRemSel[arrRemSel.length]= SelectDiv[i];
                    }
                    var idInpt = remId.replace(/_selectbox$/,'');
                    var clone = document.getElementById(idInpt).cloneNode(true);
                        clone.setAttribute('type', 'text');
                        clone.value='';
                        clone.setAttribute('value','');


                    if (document.getElementById(idInpt))
                    {
                        var remParentNode2 = document.getElementById(idInpt).parentNode;
                            remParentNode2.removeChild(document.getElementById(idInpt));
                            remParentNode2.appendChild(clone);
                    }
                }
            }
        }
    for (var i=0;i<arrRemSel.length;i++)
    {
        arrRemSel[i].parentNode.removeChild(arrRemSel[i]);
    }

    for(var i=0;i<document.getElementsByTagName('input').length;i++)
    {
    //    alert( document.getElementsByTagName('input')[i].getAttribute('type'));
        if ( document.getElementsByTagName('input')[i].getAttribute('type') == 'text')
            document.getElementsByTagName('input')[i].value="";
    }
        document.getElementById("formAdd").reset();
}

    function InputToSelectCplx(objId,selDataArr,arrTelCat)
    {
        var obj = document.getElementById(objId);
        if (obj && (obj.getAttribute('type') != 'button' || obj.getAttribute('type') != 'submit'))
        {
            var Objname = obj.name;
            var id = objId;
            var select = document.createElement('SELECT');
                select.id = id+"_selectbox";
                select.name = Objname+"_selectbox";
            var inputH = document.createElement('input');
                inputH.setAttribute("type","hidden");
                inputH.id = id;
                inputH.name = Objname;
                inputH.setAttribute("name",Objname);

                inputH.value = selDataArr["grp0"][1]['text'];
//alert (selDataArr.length);
            for (var i=0;i<3;i++)
            {
                var optgroup = document.createElement('optgroup');
                    optgroup.label = arrTelCat["grp"+i];
//                    alert (selDataArr["grp"+i].length);
                for (var ii=0;ii<selDataArr["grp"+i].length;ii++)
                {
                    var option = document.createElement('option');
//                    alert ("grp"+i);
                        option.value=selDataArr["grp"+i][ii]['value'];
                        option.text=selDataArr["grp"+i][ii]['text'];
                        optgroup.appendChild(option);
                }
                select.appendChild(optgroup);
            }
            select.onchange ="change_inpt_value(this);";
            select.setAttribute('onChange',"change_inpt_value(this);");


            //Vire le input et place le select
            var objParent = obj.parentNode;
            obj.parentNode.removeChild(obj);
            objParent.appendChild(select);
            objParent.appendChild(inputH);
        }
    }

    function change_inpt_value(pObj) {
        var id=pObj.id;
        var inptId = pObj.id.replace(/_selectbox$/,"");
        var inpt=document.getElementById(inptId);
        var selectedVal=pObj.options[pObj.selectedIndex].text;
        inpt.value = selectedVal;
    }

    function FindXmlById(objByTagName,pId)
    {
        for (var i=0;i<objByTagName.length;i++)
        {
//        alert(objByTagName[i].getAttribute('id') +" "+pId);
            if (objByTagName[i].getAttribute('id') == pId)
            {
                var ret = objByTagName[i];
                return(ret);
            }
        }
        return(false);
    }

function setZimbraFolder(pName,pId)
{
    document.getElementById("FormZimbra").style.display="block";
    document.getElementById("repZimbraId").value=pId;
    document.getElementById("repZimbra").innerHTML = pName;
//    alert( document.getElementById("repZimbraId").value);
}