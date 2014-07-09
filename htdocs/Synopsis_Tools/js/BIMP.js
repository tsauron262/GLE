$(window).load(function() {
     $("input[name='transparency']").parent().parent().hide();
     $("input[name='priority']").parent().parent().hide();
     $("input[name='percentage'], .hideifna").remove();
    
    
    
    
    
    
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
            tabT = serial.split(" ");
            $.getScript(DOL_URL_ROOT + "/apple/appleGsxScripts.js", function() {
                $("head").append($(document.createElement("link")).attr({rel: "stylesheet", type: "text/css", href: DOL_URL_ROOT + "/apple/appleGSX.css"}));
                for (i = 0; i < tabT.length; i++) {
                    serial = tabT[i];
                    if(/^[A-Z0-9]{11,12}$/.test(serial)){
                        resultZone.append('<div id="requestsResponsesContainer"></div><div id="requestResult"></div>');
                        GSX.loadProduct(serial);
//                        setRequest('newSerial', '&serial=' + serial);
                        ok = true;
                    }
                }
                if(!ok)
                    resultZone.html('<p class="error">Pas de numéro de série correct</p>');
            });
        }
        else
                    resultZone.html('<p class="error">Pas de numéro de série</p>');
    }
    
    if($("textarea.choixAccess").size()){
        textarea = $(".choixAccess");
        tabAccess = Array("Housse", "Alim", "Carton", "Clavier", "Souris", "Dvd", "Baterie", "Boite complet");
        textarea.parent().append(' <select name="sometext" multiple="multiple" class="grand" id="sometext">    <option>'+tabAccess.join('</option><option>')+'</option></select>'); 
        $("#sometext").click(function(){
            textarea.append($(this).val()+', ');
        });
    }
    
    $("input#NoMachine").focusout(/*function(){
        
    },*/ function(){
        input = $("#NoMachine");
        inputM = $("#Machine");
        inputG = $("#Garantie");
        inputD = $("#DateAchat");
        NoSerie = input.attr('value');
        datas = "serial="+NoSerie;
        roue = $("#patientez");
        reponse = valeurM = valeurG = valeurD =  "";
        roue.show();
        jQuery.ajax({
                    url: DOL_URL_ROOT + '/apple/ajax/requestProcess.php?action=loadSmallInfoProduct',
                    data: datas,
                    datatype: "xml",
                    type: "POST",
                    cache: false,
                    success: function(msg) {
                       if(msg.indexOf("tabResult")!==-1){
                            eval(msg);
                            if (typeof(tabResult) != "undefined"){
                            valeurM = tabResult[0];
                            valeurG = tabResult[1];
                            valeurD = tabResult[2];
                           }
                       }
                       else
                           reponse = msg;
                       
                        inputM.attr("value", valeurM);
                        inputG.attr("value", valeurG);
                        if (valeurD !== "") {
                            valeurD = valeurD.split("/");
                            valeurD = valeurD[0] + "-" + valeurD[1] + "-" + valeurD[2];
                            inputD.attr("value", valeurD);
                        }
                        $("#reponse").html(reponse);
                        roue.hide();
                    }
                });
        
    });
});

