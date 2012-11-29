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
function updateStats(arr)
{
    $("#progressbar").progressbar('value',arr['avancement']);
    $('#progressbar').find(".helper-progress-gle").each(function(){
        $(this).text(arr['avancement']+"%");
        });
    $("#statAvgDay").text(arr['avgday']+" Client(s) / jour");
}

function GoNextSoc(mode)
{
    //replace img wait
    $("#scrollDown").replaceWith('<div id="scrollDown" style="width: 100%; overflow-y:auto; max-height: 55px;" > \
                                  <div style="padding: 20pt;"> \
                                  <center><div class="ui-corner-all" style="border: 1px #4B4D7F Solid; background-color: #EEEEFF; font-size: 12pt; padding: 0pt;margin: 2pt; width: 300px; position: relative;"><img align=absmiddle src="'+DOL_URL_ROOT+'/theme/auguria/img/ajax-loaer-big.gif"/><span style="color:#4B4D7F; font-weight: 900;">&nbsp;&nbsp;&nbsp;&nbsp;Chargement en cours</span></div></center> \
                                  </div> \
                                  </div>');
    $('#socInfo').replaceWith('<div id="socInfo"> \
                              <div style="padding: 20pt;"> \
                                <center><div class="ui-corner-all" style="border: 1px #4B4D7F Solid; background-color: #EEEEFF; font-size: 16pt; padding: 10pt;margin: 10pt; width: 400px; position: relative;"><img align=absmiddle src="'+DOL_URL_ROOT+'/theme/auguria/img/ajax-loaer-big.gif"/><span style="color:#4B4D7F; font-weight: 900;">&nbsp;&nbsp;&nbsp;&nbsp;Chargement en cours</span></div></center> \
                              </div>\
                              </div>\
                              ');

    if (mode == "postpone" )
    {
        $('#newActComDialog').dialog("close");
            $('#newContDialog').dialog("close");

            $("#socinfo").replaceWith('<div id="socInfo"></div>');
            $.ajax({
                type: "POST",
                url: "ajax/campNav_xmlresponse.php",
                data: "action=nextAndPostPone&campid="+campId+"&socid="+SOCID+"&userId="+userId,
                success: function(msg){
                    SOCID = $(msg).find('nextSoc').text();
                    SOCNAMELONG = $(msg).find('nextSOCNAMELONG').text();
                    SOCNAME = $(msg).find('nextSOCNAME').text();
                    var arrStat=new Array();
                    arrStat['avgday'] = $(msg).find('AvgDay').text();
                    arrStat['avancement'] = $(msg).find('avancement').text();
                    updateStats(arrStat);
                        initAll();
                }
            });
            $.scrollTo(0,0);


    } else if (mode == "giveTo" )
    {
        $('#newActComDialog').dialog("close");
            $('#newContDialog').dialog("close");

            $("#socinfo").replaceWith('<div id="socInfo"></div>');
            $.ajax({
                type: "POST",
                url: "ajax/campNav_xmlresponse.php",
                data: "action=giveTo&campid="+campId+"&socid="+SOCID+"&userId="+userId,
                success: function(msg){
                    SOCID = $(msg).find('nextSoc').text();
                    SOCNAMELONG = $(msg).find('nextSOCNAMELONG').text();
                    SOCNAME = $(msg).find('nextSOCNAME').text();
                    var arrStat=new Array();
                        arrStat['avgday'] = $(msg).find('AvgDay').text();
                        arrStat['avancement'] = $(msg).find('avancement').text();
                    updateStats(arrStat);
                        initAll();
                }
            });
            $.scrollTo(0,0);


    }else {
        $('#newActComDialog').dialog("close");
            $('#newContDialog').dialog("close");

            $("#socinfo").replaceWith('<div id="socInfo"></div>');
            $.ajax({
                type: "POST",
                url: "ajax/campNav_xmlresponse.php",
                data: "action=nextAndClose&campid="+campId+"&socid="+SOCID+"&userId="+userId,
                success: function(msg){
                    SOCID = $(msg).find('nextSoc').text();
                    SOCNAMELONG = $(msg).find('nextSOCNAMELONG').text();
                    SOCNAME = $(msg).find('nextSOCNAME').text();
                    var arrStat=new Array();
                    arrStat['avgday'] = $(msg).find('AvgDay').text();
                    arrStat['avancement'] = $(msg).find('avancement').text();
                    updateStats(arrStat);

                        initAll();
                }
            });
            $.scrollTo(0,0);
    }

}

$(document).ready(function(){
    //load tabs
    $.ajaxSetup({
      type: "POST",
      async:false
    });
/// rating

        $('#AvisMain :radio.star').rating();
//        $('#AvisMain :radio.star').rating('select', NoteSoc);


    //$("#scrollDown").scrollTo('100%',0);

        initAll();
        //next company




}); //end document ready
function initAll()
{
    $("#progressbar").progressbar({ value: Progress });
    //$("#MainProgress").progressbar({ value: ProgressSoc });


    $("#socInfo").load("ajax/socinfo_html-response.php?campagneId="+campId+"&userid="+userId+"&socid="+SOCID);
    $("#tabs").tabs({cache: true,fx: { opacity: 'toggle' },
        spinner:"Chargement ...",});

//get postPone value , idempourstarrating ,

    $("#PostPoneAvancement").slider({ min: 0 ,
                               orientation: 'horizontal',
                               value: 0,
                               step: 1,
                               max: 10,
                               animate: true,
                               range: "min",
                                });


    $("#zoom_slider").slider({ min: 0 ,
                               orientation: 'horizontal',
                               value: 24,
                               step: 6,
                               max: 48,
                               range: "min",
                               animate: true,
                               change: function(event, ui){
                                    setDuree(ui.value);
                                    var strUrl = DOL_URL_ROOT + "/Synopsis_Common/ajax/recap-client_xmlresponse.php?level=1&socid="+SOCID;
                                    var dur = document.getElementById('duree').innerHTML;
                                    if ("x"+dur != "x") { strUrl += "&duree="+dur; }
                                    $.ajax({
                                        async: true,
                                        type: "POST",
                                        url: strUrl,
                                        data: "",
                                        success: function(msg){
                                            //1 erase the table
                                            var table = document.getElementById('recapMainTable');
                                            var tbodyRem = document.getElementById('recapMain');
                                            if (tbodyRem)
                                                table.removeChild(tbodyRem);

                                            //2 add the new datas
                                            tbody = document.createElement('tbody');
                                            tbody.id = "recapMain";
                                            var xml = msg;

                                        var trArray = new Array();
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
                                            trArray[14] = "ProdQty";
                                            trArray[15] = "ProdDesc";
                                            trArray[16] = "ServQty";
                                            trArray[17] = "ServDesc";
                                        var trWidth = new Array();
                                            trWidth['info'] = "5px";
                                            trWidth['date_valid'] = "30px";
                                            trWidth['remise_percent'] = "50px";
                                            trWidth['remise_absolue'] = "50px";
                                            trWidth['remise'] = "50px";
                                            trWidth['ref'] = "100px";
                                            trWidth['pid'] = "50px";
                                            trWidth['total_ht'] = "80px";
                                            trWidth["title"] = "auto";
                                            trWidth["propalStatut"] = "80px";
                                            trWidth["commande"] = "100px";
                                            trWidth["commandeStatut"] = "80px";
                                            trWidth["facture"] = "100px";
                                            trWidth["factureStatut"] = "80px";
                                            trWidth["paye"] = "40px";
                                            trWidth["ProdQty"] = "100px";
                                            trWidth["ProdDesc"] = "auto";
                                            trWidth["ServQty"] = "100px";
                                            trWidth["ServDesc"] = "auto";
                                        var trAlign = new Array();
                                            trAlign['info'] = "center";
                                            trAlign['date_valid'] = "center";
                                            trAlign["title"] = "left";
                                            trAlign["ref"] = "left";
                                            trAlign["propalStatut"] = "left";
                                            trAlign["remise_percent"] = "center";
                                            trAlign["remise_absolue"] = "center";
                                            trAlign["remise"] = "center";
                                            trAlign["total_ht"] = "center";
                                            //                                                    trAlign["pid"] = "left";
                                            trAlign["commande"] = "left";
                                            trAlign["commandeStatut"] = "left";
                                            trAlign["facture"] = "left";
                                            trAlign["factureStatut"] = "left";
                                            trAlign["paye"] = "center";
                                            trAlign["ProdQty"] = "center";
                                            trAlign["ProdDesc"] = "left";
                                            trAlign["ServQty"] = "center";
                                            trAlign["ServDesc"] = "left";

                                            var className = "impair";
                                            //for (var i=0;i<MainRecap.length;i++)
                                            //{

                                            $(msg).find('recapMain').find('row').each(function(){
                                                var tr = document.createElement('tr');
                                                tr.className = pairImpair(className);
                                                className = pairImpair(className);
                                                var tdCell = new Array();
                                                tdCell['info'] = $(this).find('info').text();
                                                tdCell['date_valid'] = $(this).find('date_valid').text();
                                                tdCell['title'] = $(this).find('title').text();
                                                tdCell['ref'] = $(this).find('ref').text();
                                                tdCell['propalStatut'] = $(this).find('propalStatut').text();
                                                tdCell['remise_percent'] = $(this).find('remise_percent').text();
                                                tdCell['remise_absolue'] = $(this).find('remise_absolue').text();
                                                tdCell['remise'] = $(this).find('remise').text();
                                                //                                                    tdCell['pid'] = $(this).find('pid').text();
                                                tdCell['total_ht'] = $(this).find('total_ht').text();
                                                //                                                    tdCell['title'] = $(this).find('title').text();
                                                tdCell['commande'] = $(this).find('commande').text();
                                                tdCell['commandeStatut'] = $(this).find('commandeStatut').text();
                                                tdCell['facture'] = $(this).find('facture').text();
                                                tdCell['factureStatut'] = $(this).find('factureStatut').text();
                                                tdCell['paye'] = $(this).find('paye').text();
                                                //tdCell['rowid'] = $(this).attr('id');
                                                for (var h in tdCell) {
                                                    var td = document.createElement('td');
                                                    td.innerHTML = tdCell[h];
                                                    td.style.textAlign = trAlign[h];
                                                    td.style.width = trWidth[h];
                                                    tr.appendChild(td);
                                                }



                                                tbody.appendChild(tr);
                                            });
                                            table.appendChild(tbody);

                                            var tableProd = document.getElementById('recapProdTable');
                                            var tbodyRemProd = document.getElementById('recapProd');
                                            if (tbodyRemProd)
                                                tableProd.removeChild(tbodyRemProd);

                                            tbodyProd = document.createElement('tbody');
                                            tbodyProd.id = "recapProd";
                                            className = "impair";
                                            $(msg).find('recapProdTable').find('row').each(function(){
                                                var tr = document.createElement('tr');
                                                tr.className = pairImpair(className);
                                                className = pairImpair(className);
                                                var tdCell = new Array();
                                                tdCell['ProdQty'] = $(this).find('ProdQty').text();
                                                tdCell['ProdDesc'] = $(this).find('ProdDesc').text();
                                                for (var h in tdCell) {
                                                    var td = document.createElement('td');
                                                    td.innerHTML = tdCell[h];
                                                    td.style.textAlign = trAlign[h];
                                                    td.style.width = trWidth[h];
                                                    tr.appendChild(td);
                                                }
                                                tbodyProd.appendChild(tr);
                                            });
                                            tableProd.appendChild(tbodyProd);


                                            var tableServ = document.getElementById('recapServTable');
                                            var tbodyRemServ = document.getElementById('recapServ');
                                            if (tbodyRemServ)
                                                tableServ.removeChild(tbodyRemServ);

                                            tbodyServ = document.createElement('tbody');
                                            tbodyServ.id = "recapServ";
                                            className = "impair";
                                            $(msg).find('recapServTable').find('row').each(function(){
                                                var tr = document.createElement('tr');
                                                tr.className = pairImpair(className);
                                                className = pairImpair(className);
                                                var tdCell = new Array();
                                                tdCell['ServQty'] = $(this).find('ServQty').text();
                                                tdCell['ServDesc'] = $(this).find('ServDesc').text();
                                                for (var h in tdCell) {
                                                    var td = document.createElement('td');
                                                    td.innerHTML = tdCell[h];
                                                    td.style.textAlign = trAlign[h];
                                                    td.style.width = trWidth[h];
                                                    tr.appendChild(td);
                                                }
                                                tbodyServ.appendChild(tr);
                                            });
                                            tableServ.appendChild(tbodyServ);
                                        } // close success
                                             }); //close $.ajax
            } // end onChange event
        }); // end slider
        //note de campagne
        //Get Note on load
        $.ajax({async: true,
            type: "POST",
            url: "ajax/campNote_xmlresponse.php",
            data: "action=get&campid="+campId+"&socid="+SOCID,
            success: function(msg){
                var note = $(msg).find("note").text();
                    $("#note").find('div').replaceWith('<div style="width: 100%; " >'+note+'</div>');

                var longHtml = '<div id="scrollDown" style="width: 100%; overflow-y: auto; max-height: 55px;">';
                    longHtml += '<table width="100%" style="border-collapse: collapse; background-color: rgb(255, 255, 255);">';
                    longHtml += '<tbody>';
                $(msg).find('histo').each(function(){
                        longHtml += '<tr><td width="130px">'+$(this).find('date').text()+'</td><td width="160px">'+$(this).find('userid').text()+'</td><td  width="130px">'+$(this).find('raison').text()+'</td><td>'+$(this).find('noteHisto').text()+'</td>';
                });
                longHtml += '</tbody>';
                longHtml += '</table>';

                 $("#noteAvancement").find("#scrollDown").replaceWith(longHtml);
                    $("#scrollDown").scrollTo('100%',0);
                ProgressSoc = $(msg).find('ProgressSoc').text() * 10;
                NoteSoc = $(msg).find('NoteSoc').text()  * 1;

                $("#MainProgress").progressbar({ value: ProgressSoc });

                $('#AvisMain :radio.star').rating('enable');
                $('#AvisMain :radio.star').rating('select', "n"+NoteSoc);
                $('#AvisMain :radio.star').rating('disable');


                    //HERE Mark
            }
        });


        $('#note').click(function(){
            //take text
            var text = $(this).text();
            //Make text area
            var h = $('#noteCampForHeight').css("height");
            $(this).find('div').replaceWith('<textarea id="textAreaReplace" onBlur="replaceTextArea()" style="width: 100%; height: '+h+'">'+text+'</textarea>');
            $('#textAreaReplace').focus();
        });
        $.validator.addMethod(
            "FRDate",
            function(value, element) {
                // put your own logic here, this is just a (crappy) example
                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W\d\d\:\d\d$/);
            },
            "La date doit �tre au format dd/mm/yyyy hh:mm"
        );


        //Action comm
        $('#newContDialog').dialog({ autoOpen: false ,
                                       hide: 'explode',
                                       modal: false,
                                       position: "center",
                                       minWidth: 400,
                                       width: 400,
                                       show: 'bounce',
                                       title: 'Nouveau contact',
                                       buttons: { "Ok": function() {
                                                //post data
                                                var contactCivil = $('#contactCivil').find(':selected').val();
                                                var contactNom = $('#contactNom').val();


                                                var contactPrenom = $('#contactPrenom').val();

                                                var contactGSM = $('#contactGSM').val();
                                                var contactTEL = $('#contactTEL').val();
                                                var noteContact = $('#noteContact').val();
                                                var contactEmail = $('#contactEmail').val();

                                                var post = '';
                                                    post += "&Civil="+contactCivil;
                                                    post += "&Nom="+contactNom;
                                                    post += "&GSM="+contactGSM;
                                                    post += "&email="+contactEmail;
                                                    post += "&TEL="+contactTEL;
                                                    post += "&Prenom="+contactPrenom;
                                                    post += "&socid="+SOCID;
                                                    post += "&userid="+userId;
                                                    post += "&note="+noteContact;

                                                if ($("#formDialogCont").validate({
                                                                    rules: {
                                                                            contactNom: {
                                                                                required: true,
                                                                                minlength: 3
                                                                            },
                                                                            contactEmail : {
                                                                                email: true,
                                                                                minlength: 5

                                                                            },
                                                                        },
                                                                        messages: {
                                                                            contactNom: {
                                                                                required : "<br>Champs requis",
                                                                                minlength : "<br>Ce champs doit faire au moins 3 caract&egrave;res",
                                                                            },
                                                                            contactEmail: {
                                                                                email : "<br>Cet email est invalide",
                                                                                minlength: "<br>Cet email est invalide"
                                                                            },
                                                                        }
                                                                }).form()) {
                                                                    $.ajax({
                                                                            async: false,
                                                                            url: "ajax/addCont-xmlresponse.php",
                                                                            type: "POST",
                                                                            data: post,
                                                                            success: function(msg){
                                                                                $('#newContDialog').dialog("close");
                                                                                $("#ContactActComm").replaceWith("<div id='ContactActComm'>En chargement ...</div>");
                                                                                //load contact
                                                                                $.getJSON("ajax/listCont_json.php?socid="+SOCID,
                                                                                            function(data){
                                                                                                if (data != null)
                                                                                                {
                                                                                                    var longHtml = '<select id="ContactActComm">'
                                                                                                    $.each(data.rows, function(i,item){
                                                                                                        longHtml += '<option val="'+item.id+'">'+item.cell+'</option>';
                                                                                                    });
                                                                                                    longHtml += '</select>';
                                                                                                    $("#ContactActComm").replaceWith(longHtml);
                                                                                                } else {
                                                                                                    $("#ContactActComm").replaceWith("<div id='ContactActComm'>Pas de contact</div>");
                                                                                                }
                                                                                            });
                                                                            }
                                                                        });
                                                                    }
                                                        }
                                                },
                                       open: function (e,u){
                                           $("#societeNameCont").html(SOCNAMELONG);
                                       }
        }); //dialog


        //Give To
        $('#giveToDialog').dialog({ autoOpen: false ,
                                       hide: 'explode',
                                       modal: false,
                                       position: "center",
                                       minWidth: 400,
                                       width: 400,
                                       show: 'bounce',
                                       title: 'Passer le prospect &agrave;',
                                       buttons: {
                                        "Ok": function(){
                                            //post data
                                                        var noteGiveTo = $('#noteGiveTo').val();
                                                        var userGiveTo = $('#userGiveTo').find(':selected').val();

                                                        var post = '';
                                                        post += "&userGiveTo=" + userGiveTo;
                                                        post += "&socid=" + SOCID;
                                                        post += "&userid=" + userId;
                                                        post += "&note=" + noteGiveTo;
                                                        post += "&campId=" + campId;
                                                        if ($("#formDialogGiveTo").validate({
                                                            rules: {
                                                                userGiveTo: {
                                                                    required: true
                                                                },
                                                                noteGiveTo: {
                                                                    required: true,
                                                                    minlength: 5
                                                                }
                                                            },
                                                            messages: {
                                                                userGiveTo:
                                                                {
                                                                    required: "<br>Champs requis",
                                                                },
                                                                noteGiveTo: {
                                                                    required: "<br>Champs requis",
                                                                    minlength: "<br>Ce champs doit avoir plus de 5 caract&egrave;res"
                                                                }
                                                            }

                                                        }).form()) {

                                                            $.ajax({
                                                                async: false,
                                                                url: "ajax/giveTo-xmlresponse.php",
                                                                type: "POST",
                                                                data: post,
                                                                success: function(msg){
                                                                    $('#giveToDialog').dialog("close");
                                                                    GoNextSoc("giveTo");
                                                                }
                                                            });
                                                        }
                                                    }
                                                },
                                       open: function (e,u){
                                           $("#societeNameCont").html(SOCNAMELONG);
                                       }
        }); //dialog
        $('#newActComDialog').dialog({ autoOpen: false ,
                                       hide: 'explode',
                                       modal: false,
                                       position: "center",
                                       minWidth: 400,
                                       width: 400,
                                       show: 'bounce',
                                       title: 'Nouvelle action commerciale',
                                       buttons: { "Ok": function() {
                                            //post data
                                            var titre = $('#TitreActComm').val();

                                            var datedeb = $('#dateDebActComm').val();

                                            var datefin = $('#dateFinActComm').val();

                                            var TypeActComm = $('#TypeActComm').find(':selected').val();
                                            var ContactActComm = "";
                                            ($('#ContactActComm').find(':selected').val()?ContactActComm=$('#ContactActComm').find(':selected').val():ContactActComm="");
                                            var AffecteAActComm = $('#AffecteAActComm').find(':selected').val();
                                            var noteActComm = $('#noteActComm').val();
                                            var post = '';
                                                if ('x' + ContactActComm != 'x' ) {
                                                     post += "contactid=" + ContactActComm;
                                                }
                                                post += "&datedeb="+datedeb;
                                                post += "&datefin="+datefin+"&codeType="+TypeActComm;
                                                post += "&label="+titre+"&affectedto="+AffecteAActComm;
                                                post += "&socid="+SOCID+"&note="+noteActComm;
                                            if ($("#formDialog").validate({
                                                rules: {
                                                        TitreActComm: "required",
                                                        dateDebActComm: {
                                                            FRDate: true,
                                                            required: true
                                                        },
                                                        dateFinActComm: {
                                                            FRDate: true,
                                                            required: true
                                                        },
                                                        ContactActComm: {
                                                            required: true
                                                        },
                                                        AffecteAActComm: {
                                                            required: true
                                                        },
                                                        TypeActComm: {
                                                            required: true
                                                        },
                                                    },
                                                    messages: {
                                                        TitreActComm: "<br>Champs requis",
                                                        dateDebActComm: "<br>Champs requis",
                                                        dateFinActComm: {
                                                            required: "<br>Champs requis",
                                                            minlength: "<br>au moins 2 chars"
                                                        },
                                                        ContactActComm: {
                                                            required: "<br>Champs requis"
                                                        },
                                                        AffecteAActComm: {
                                                            required: "<br>Champs requis"
                                                        },
                                                        TypeActComm: {
                                                            required: "<br>Champs requis"
                                                        },
                                                    }
                                            }).form()) //fin du IF
                                            {
                                                $.ajax({
                                                    async: false,
                                                    url: "ajax/addActCom-xmlresponse.php",
                                                    type: "POST",
                                                    data: post,
                                                    success: function(msg){
                                                        $(this).dialog("close");
                                                    }
                                                });
                                            }
                                        }
                                                },
                                       open: function (e,u){
                                           $("#societeNameActComm").html(SOCNAMELONG);
                                           //clean contact
                                            $("#ContactActComm").replaceWith("<div id='ContactActComm'>En chargement ...</div>");
                                            //load contact
                                            $.getJSON("ajax/listCont_json.php?socid="+SOCID,
                                                        function(data){
                                                            if (data != null)
                                                            {
                                                                var longHtml = '<select id="ContactActComm">'
                                                                $.each(data.rows, function(i,item){
                                                                    longHtml += '<option val="'+item.id+'">'+item.cell+'</option>';
        //
                                                                });
                                                                longHtml += '</select>';
                                                                $("#ContactActComm").replaceWith(longHtml);
                                                            } else {
                                                                $("#ContactActComm").replaceWith("<div id='ContactActComm'>Pas de contact</div>");
                                                            }
                                                        });
                                            $.scrollTo(0,0);
                                       }
        }); //dialog


        $('#newPostPoneDialog').dialog({ autoOpen: false ,
            hide: 'explode',
            modal: false,
            position: "center",
            minWidth: 400,
            width: 400,
            show: 'bounce',
            title: 'Nouvelle &eacute;tape',
            buttons: { "Ok": function() {
                    //post data
                    var PostPoneAvancement = $("#PostPoneAvancement").slider('value');
                    //

                    var PostPoneRaison = $('#PostPoneRaison').val();
                    var PostPoneAvis = "";
                    $('.star').each(function(){
                        if ($(this).attr('checked'))
                        {
                            PostPoneAvis=$(this).val();
                        }
                    });//get avis
                    var PostPoneDate = $('#PostPoneDate').val();
                    var PostPoneNote = $('#PostPoneNote').val();
                    var post = '';
                        post += "&avancement="+PostPoneAvancement;
                        post += "&raison="+PostPoneRaison;
                        post += "&avis="+PostPoneAvis;
                        post += "&date="+PostPoneDate;
                        post += "&note="+PostPoneNote;
                        post += "&socid="+SOCID+"&userid="+userId;
                        post += "&action=update";
                        post += "&campId="+campId;
                    if ($("#formDialogPostPone").validate({
                                                            rules: {
                                                                PostPoneRaison: {
                                                                    required: true
                                                                },
                                                                PostPoneNote: {
                                                                    required: true,
                                                                    minlength: 5
                                                                },
                                                                PostPoneDate: {
                                                                    required: true,
                                                                    FRDate: true
                                                                }
                                                            },
                                                            messages: {
                                                                PostPoneRaison:
                                                                {
                                                                    required: "<br>Champs requis",
                                                                },
                                                                PostPoneNote: {
                                                                    required: "<br>Champs requis",
                                                                    minlength: "<br>Ce champs doit avoir plus de 5 caract&egrave;res"
                                                                },
                                                                PostPoneDate: {
                                                                    required: "<br>Champs requis",
                                                                    minlength: "<br>Le format de la date est invalide"
                                                                }
                                                            }
                                                        }).form()) {
//alert ('toto');
                        $.ajax({
                            async: false,
                            url: "ajax/postPone-xmlresponse.php",
                            type: "POST",
                            data: post,
                            success: function(){
                                $('#newPostPoneDialog').dialog("close");
                                //call next Soc
                                GoNextSoc("postpone");

                            }
                        });
                    }
                }
            },
        open: function (e,u){
            $("#societeNamePostPone").html(SOCNAMELONG);
            //clean dialog
            $("#PostPoneRaison").val("");
            $("#PostPoneDate").val("");
            $("#PostPoneNote").val("");
            //get  avancement et avis (note star ratting)

            $.ajax({
                    async: false,
                    url: "ajax/postPone-xmlresponse.php",
                    type: "POST",
                    data : "&action=getExtra&socid="+SOCID+"&userid="+userId+"&campId="+campId,
                    success: function(msg){
                        $('#formDialogPostPone :radio.star').rating('select', $(msg).find('avis').text());
                        $('#PostPoneAvancement').slider( 'value' , $(msg).find('avancement').text() );

                    }
            });
                $.scrollTo(0,0);
        }

        }); //dialog

        $('#closeDialog').dialog({ autoOpen: false ,
                                       hide: 'explode',
                                       modal: false,
                                       position: "center",
                                       minWidth: 400,
                                       width: 400,
                                       show: 'bounce',
                                       title: 'Fin de la campagne pour une soci&eacute;t&eacute;',
                                       buttons: { "Ok": function() {
                                                                //post data
                                                                var noteClose = $('#noteClose').val();
                                                                var StcommClose = $('#StcommClose').val();
                                                                var resultClose = $('#resultClose :selected').val();

                                                                var post = "&noteClose="+noteClose;
                                                                    post += "&StcommClose="+StcommClose;
                                                                    post += "&resultClose="+resultClose;
                                                                    post += "&socid="+SOCID+"&userid="+userId;
                                                                    post += "&action=update";
                                                                    post += "&campId="+campId;

                                                                if ($("#formDialogClose").validate({
                                                                    rules: {
                                                                            noteClose: {
                                                                                required : true,
                                                                                minlength: 5
                                                                            },
                                                                            StcommClose: {
                                                                                required : true,
                                                                            },
                                                                            resultClose: {
                                                                                required : true,
                                                                            }
                                                                        },
                                                                        messages: {
                                                                            noteClose: {
                                                                                required : "<BR>Champs requis",
                                                                                minlength : "<BR>Ce champs doit faire au moins 5 caract&egrave;res</div>",
                                                                            },
                                                                            StcommClose: {
                                                                                required : "<BR><div style='position: relative; white-space: nowrap; z-index: 20000'>Champs requis",
                                                                            },
                                                                            resultClose: {
                                                                                required : "<BR>Champs requis",
                                                                            }

                                                                        }

                                                                }).form() ) {
                                                                    $.ajax({
                                                                        async: false,
                                                                        url: "ajax/close-xmlresponse.php",
                                                                        type: "POST",
                                                                        data: post,
                                                                        success: function(){
                                                                            $('#newPostPoneDialog').dialog("close");
                                                                            $('#closeDialog').dialog("close");
                                                                            //call next Soc
                                                                            GoNextSoc("");

                                                                        }
                                                                    });
                                                                }
                                                            }
                                                },
                                       open: function (e,u){
                                           $("#societeNamePostPone").html(SOCNAMELONG);
                                        //clean dialog
                                           $("#PostPoneRaison").val("");
                                           $("#PostPoneDate").val("");
                                           $("#PostPoneNote").val("");
                                           //get  avancement et avis (note star ratting)

                                        $.ajax({
                                                async: false,
                                                url: "ajax/postPone-xmlresponse.php",
                                                type: "POST",
                                                data : "&action=getExtra&socid="+SOCID+"&userid="+userId+"&campId="+campId,
                                                success: function(msg){
                                                    $('#formDialogPostPone :radio.star').rating('select', $(msg).find('avis').text());
                                                    $('#PostPoneAvancement').slider( 'value' , $(msg).find('avancement').text() );

                                                }
                                        });
                                            $.scrollTo(0,0);
                                       }

        }); //dialog


        $('#giveToBut').click(function(){
            //open dialog

            $('#giveToDialog').dialog("open");
            return(false);
        }); // fin newActCom click


        $('#newActCom').click(function(){
            //open dialog

            $('#newActComDialog').dialog("open");
            return(false);
        }); // fin newActCom click
        $('#newCont').click(function(){
            //open dialog

            $('#newContDialog').dialog("open");
            return(false);
        }); // fin newActCom click

        $('#nextSoc').click(function(){
            //open dialog

            $('#closeDialog').dialog("open");
            return(false);
        }); // fin nextSoc click
//        $("#nextSoc").click(function(){
//            //Close dialog
//            GoNextSoc();
//        });

        $('#postponeSoc').click(function(){
            //open dialog

            $('#newPostPoneDialog').dialog("open");
            return(false);
        });

        $('#newActComTop').click(function(){
            //open dialog

            $('#newActComDialog').dialog("open");
            return(false);
        });


        //timepicker in dialog
        $('#dateDebActComm').datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });

        $('#dateFinActComm').datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });



        $('#PostPoneDate').datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });

}
function replaceTextArea()
{
    var text = $("#textAreaReplace").val();
    //$("#textAreaReplace").replaceWith('<div style="width: 100%; height: 100%;" id="note">'+text+'</div>');
    $.ajax({async: true,
            type: "POST",
            url: "ajax/campNote_xmlresponse.php",
            data: "action=set&note="+text+"&campid="+campId+"&socid="+SOCID,
            success: function(msg){
                var note = $(msg).find("note").text();
                $("#note").find('textarea').replaceWith('<div style="width: 100%; " >'+note+'</div>');
            }
    });
}

  function setDuree(val)
  {
    for (var i=0;i<document.getElementsByName('duree').length;i++)
    {
        document.getElementsByName('duree')[i].innerHTML = Math.round(val);
    }
  }



function showContact(pId,pObj)
{
    Rem = pObj;
    var propalId = pId;
    var strUrl = DOL_URL_ROOT + "/Synopsis_Common/ajax/recap-client_xmlresponse.php?level=2&socid="+SOCID;
//    var dur = document.getElementById('duree').innerHTML;
    if ("x"+pId != "x") { strUrl += "&propalid="+pId; }
//    alert(strUrl);
    $.ajax({async: true,
            type: "POST",
            url: strUrl,
            data: "",
            success: function(msg)
            {
                var htmlObj_table = document.createElement('table');
                    htmlObj_table.style.height="100%"
                    htmlObj_table.style.width="400px";
                    htmlObj_table.style.minWidth="400px";
                var htmlObj_tbody = document.createElement('tbody');
                $(msg).find("row").each(function(){
                        var htmlObj_thtxt = document.createTextNode($(this).find('libelle').text());
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
                        var Arr = new Array();
                            Arr['soc'] =$(this).find("societe").text();
                            Arr['nomCont'] =$(this).find("nomCont").text();
                            Arr['libelle'] =$(this).find("libelle").text();
                            Arr['status'] =$(this).find("status").text();
                            for (var h in Arr)
                            {
                                var htmlObj_td1 = document.createElement('td');
                                    htmlObj_td1.setAttribute('nowrap','nowrap');
                                    htmlObj_td1.innerHTML = Arr[h];
                                    htmlObj_td1.style.width="200px";
                                    htmlObj_tr.appendChild(htmlObj_td1);
                            }
                        htmlObj_tbody.appendChild(htmlObj_tr);
                    });
                    htmlObj_table.appendChild(htmlObj_tbody);
                    Tip(htmlObj_table.innerHTML);
            }
    });

}

   var PairImpair= new Array();
        PairImpair["pair"] = "impair";
        PairImpair["impair"] = "pair";

function pairImpair(str)
{
    return (PairImpair[str]);

}

function jqSelImage(obj)
{
    var idFull = $(obj).attr('id');
    var regex = new RegExp('[a-zA-Z]*',"g");
        id = idFull.replace(regex,'');
    $('#StcommClose').val(id);
    $('.imgSelOK').each(function(){
        $(this).removeClass("imgSelOK");
    })
    $(obj).addClass("imgSelOK");
}

//
//function recap_client_findPosX(obj)
//  {
//    var curleft = 0;
//    if(obj.offsetParent)
//        while(1)
//        {
//          curleft += obj.offsetLeft;
//          if(!obj.offsetParent)
//            break;
//          obj = obj.offsetParent;
//        }
//    else if(obj.x)
//        curleft += obj.x;
//    return curleft;
//  }
//
//  function recap_client_findPosY(obj)
//  {
//    var curtop = 0;
//    if(obj.offsetParent)
//        while(1)
//        {
//          curtop += obj.offsetTop;
//          if(!obj.offsetParent)
//            break;
//          obj = obj.offsetParent;
//        }
//    else if(obj.y)
//        curtop += obj.y;
//    return curtop;
//  }
