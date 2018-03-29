//Script version 4.2
$(window).on("load", function () {
//    $("input[name='transparency']").parent().parent().hide();
    $("input[name='priority']").parent().parent().hide();
    $("input[name='percentage'], .hideifna").hide();






    if (document.URL.search("comm/propal.php") > 0) {
        $("a.butAction").each(function () {
            if ($(this).html() == "Modifier")
                $(this).hide();
        });
    }


    if ($(".signatureCh").length > 0) {
        $.getScript(DOL_URL_ROOT + "/synopsistools/js/jquery.signature.min.js", function () {
            $(".signatureCh").each(function () {
                var elem = $(this);
                elem.click(function () {
                    var parent = elem.parent();
                    parent.append('<button type="button" class="clear2Button">Vider</button><div class="captureSignature"></div>');
                    parent.find(".clear2Button").click(function () {
                        parent.find('.captureSignature').signature('clear');
                    });
                    parent.find('.captureSignature').signature({syncField: $(this)});
                    parent.find('.captureSignature').signature('draw', elem.html());
                    //elem.hide();
                });
            });
        });
    }







   /* $("td:contains('Pass Apple')").each(function () {   ///plus de mdp apple
        $(this).next("td").each(function () {
            if ($(this).find("input").length > 0) {
                val = $(this).find("input").val();
                $(this).parent().append("<input type='password' name='options_apple_mdp' value='" + val + "'/>");
                $(this).remove();
            } else
                $(this).html("*******");
        });
    });*/

    inputCentre = $('input[name="options_apple_centre"]');
    inputCentre.after(selectCentre);
    $("select[name='centreRapide']").change(function () {
        inputCentre.val(inputCentre.val() + " " + $(this).val());
    });


    elem = "#Chrono1034, #inputautocompleteChrono1034";
    $(elem).change(function () {
        initTrans(elem);
    });
    initTrans(elem);
    $("#inputautocompleteChrono1070, #inputautocompleteChrono1071").focusout(function () {
        $("#mailTrans").attr("checked", "checked");
    });
    
    
    
    //change nom menu product bimp
    //$('#mainmenutd_products').find('.mainmenuaspan').text('PRESTATIONS');
});


function initTrans(elem) {
    elemA = "select.hideTrans, input.hideTrans";
    if ($(elem).val() == "1")
        $(elemA).parent().parent().show();
    else
        $(elemA).parent().parent().hide();
}

