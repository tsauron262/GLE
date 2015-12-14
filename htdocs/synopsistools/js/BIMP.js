$(window).load(function() {
//    $("input[name='transparency']").parent().parent().hide();
    $("input[name='priority']").parent().parent().hide();
    $("input[name='percentage'], .hideifna").hide();






    if (document.URL.search("comm/propal.php") > 0) {
        $("a.butAction").each(function() {
            if ($(this).html() == "Modifier")
                $(this).hide();
        });
    }



    if ($(".apple_sn").size() && $(".zonePlus").size()) {
        var serial = $(".apple_sn").html();
        var resultZone = $(".zonePlus");
        ok = false;
        if (serial != "") {
//            resultZone.html('<p class="error">Veuillez entrer un numéro de série</p>');
////        } else if (!/^[0-9a-zA-Z]+$/.test(serial)) {
////            resultZone.html('<p class="error">Le format du numéro de série est incorrect</p>');
//        } else {
            resultZone.append("<span class='loadApple butAction'>Charger info GSX</span>");




            $(".loadApple").click(function() {
                $(".loadApple").remove();
                tabT = serial.split("<br>");
//                tabT = serial.split("\n");
                $.getScript(DOL_URL_ROOT + "/synopsisapple/appleGsxScripts.js", function() {
                    $("head").append($(document.createElement("link")).attr({rel: "stylesheet", type: "text/css", href: DOL_URL_ROOT + "/synopsisapple/appleGSX.css"}));
                    for (i = 0; i < tabT.length; i++) {
                        serial = tabT[i].replace(/^\s+/g, '').replace(/\s+$/g, '');
                        if (/^[A-Z0-9]{11,12}$/.test(serial)) {
                            resultZone.append('<div id="requestsResponsesContainer"></div><div id="requestResult"></div>');
                            GSX.loadProduct(serial);
//                        setRequest('newSerial', '&serial=' + serial);
                            ok = true;
                        }
                        if (/^[0-9]{15}$/.test(serial)) {
                            resultZone.append('<div id="requestsResponsesContainer"></div><div id="requestResult"></div>');
                            GSX.loadProduct(serial);
//                        setRequest('newSerial', '&serial=' + serial);
                            ok = true;
                        }
                    }
                    if (!ok)
                        resultZone.html('<p class="error">Pas de numéro de série correct</p>');
                });
            });
        }
        else
            resultZone.html('<p class="error">Pas de numéro de série</p>');
    }

    if ($("textarea#Descr").size()) {
        textarea2 = $("textarea#Descr");
        tabAccess = Array("Rayure", "Écran cassé", "Liquide");
        textarea2.parent().append(' <select name="sometext" multiple="multiple" class="grand" id="sometext2">    <option>' + tabAccess.join('</option><option>') + '</option></select>');
        $("#sometext2").click(function() {
            textarea2.val(textarea2.val() + $(this).val() + ', ');
        });
    }


    if ($("textarea.choixAccess").size()) {
        textarea = $("textarea.choixAccess");
        tabAccess = Array("Housse", "Alim", "Carton", "Clavier", "Souris", "Dvd", "Batterie", "Boite complet");
        textarea.parent().append(' <select name="sometext" multiple="multiple" class="grand" id="sometext">    <option>' + tabAccess.join('</option><option>') + '</option></select>');
        $("#sometext").click(function() {
            textarea.val(textarea.val() + $(this).val() + ', ');
        });
    }

    $("input[name='lastname'], input[name='options_apple_centre'], #serialInput").focusout(function() {
        input = $(this);
        input.val(input.val().toUpperCase());
    });

    $("input[name='firstname']").focusout(function() {
        input = $(this);
        input.val(input.val().substring(0, 1).toUpperCase() + input.val().substring(1, 100));
    });




    $("input#NoMachine, input#Chrono1011").focusout(function() {
        input = $(this);
        input.val(input.val().toUpperCase());

        if ($(this).attr("id") == "NoMachine") {
            inputM = $("#Machine");
            inputG = $("#Garantie");
            inputTG = $("#typeGarantie");
            inputD = $("#DateAchat");
        }
        else {
            inputM = $('input[name="description"]');
            inputG = $("#Chrono1015");
            inputTG = $("#Chrono1064");
            inputD = $("#Chrono1014");
            input.parent().append("<div id='reponse'></div>");
        }
            zoneRep = $("#reponse");
        NoSerie = input.val();
        datas = "serial=" + NoSerie;
        roue = $("#patientez");
        reponse = valeurM = valeurG = valeurTG = valeurD = "";
        roue.show();

        jQuery.ajax({
            url: DOL_URL_ROOT + '/synopsisapple/ajax/requestProcess.php?action=loadSmallInfoProduct',
            data: datas,
            datatype: "xml",
            type: "POST",
            cache: false,
            success: function(msg) {
                if (msg.indexOf("tabResult") !== -1) {
                    eval(msg);
                    if (typeof (tabResult) != "undefined") {
                        valeurM = tabResult[0];
                        valeurTG = tabResult[1];
                        valeurG = tabResult[2];
                        valeurD = tabResult[3];
                        valeurPlus = tabResult[4];
                        input.parent().append(valeurPlus);
                    }
                }
                else
                    reponse = msg;

                if (valeurM != "")
                    inputM.attr("value", valeurM);
                if (valeurTG != "")
                    inputTG.attr("value", valeurTG);
                if (valeurG != "")
                    inputG.attr("value", valeurG);
                if (valeurD != "")
                    inputD.attr("value", valeurD);
                zoneRep.html(reponse);
                roue.hide();
            }
        });

    });


    $("td:contains('Pass Apple')").each(function() {
        $(this).next("td").each(function() {
            if ($(this).find("input").size() > 0) {
                val = $(this).find("input").val();
                $(this).parent().append("<input type='password' name='options_apple_mdp' value='" + val + "'/>");
                $(this).remove();
            }
            else
                $(this).html("*******");
        });
    });

    inputCentre = $('input[name="options_apple_centre"]');
    inputCentre.after(selectCentre);
    $("select[name='centreRapide']").change(function() {
        inputCentre.val(inputCentre.val() + " " + $(this).val());
    });


    elem = "#Chrono1034, #inputautocompleteChrono1034";
    $(elem).change(function() {
        initTrans(elem);
    });
    initTrans(elem);
    $("#inputautocompleteChrono1070, #inputautocompleteChrono1071").focusout(function() {
        $("#mailTrans").attr("checked", "checked");
    });
});


function initTrans(elem) {
    elemA = "select.hideTrans, input.hideTrans";
    if ($(elem).val() == "1")
        $(elemA).parent().parent().show();
    else
        $(elemA).parent().parent().hide();
}
