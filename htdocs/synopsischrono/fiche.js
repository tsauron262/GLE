
function ajax_updater_postFct(socid, valueSelected)
{
    if (valueSelected == "socid")
        majDoubleSoc(socid, false);
    $("#" + valueSelected).trigger("change");
}

function ajax_updater_postFct2(socid, valueSelected)
{
    if (!socid > 0)
        socid = $("#socid").val();
    if (socid > 0)
    {
        jQuery.ajax({
            url: DOL_URL_ROOT+"/synopsischrono/ajax/contactSoc-xml_response.php",
            type: "POST",
            datatype: "xml",
            data: "socid=" + socid,
            success: function (msg) {
                jQuery('#contactSociete').replaceWith("<div id='contactSociete'>" + jQuery(msg).find('contactsList').text() + "</div>");
//                          jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                if (valueSelected == 'old') {
                    idMax = 0;
                    jQuery('#contactSociete').find('option').each(function () {
                        id = parseInt($(this).attr("value"));
                        if (id > idMax)
                            idMax = id;
                    });
                    jQuery('#contactSociete').find('option[value="' + idMax + '"]').attr("selected", "selected");
                }
                else if (valueSelected != null) {
                    jQuery('#contactSociete').find('option[value="' + valueSelected + '"]').attr("selected", "selected");
                }
            }
        });
        majDoubleSoc(socid, false);
    } else {
        jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
    }
    majOldSelect();
}
function majOldSelect() {
    $("select.old").each(function () {
        if ($(this).val() == "0") {
            maxValue = 0;
            $(this).children().each(function () {
                var val = $(this).attr('value');
                val = parseInt(val, 10);
                if (maxValue === undefined || maxValue < val) {
                    maxValue = val;
                }
            });
            if (maxValue > 0) {
                $(this).val(maxValue);
                id = $(this).attr("id");
                id = id.replace("_jDS_2", "");
                $("#" + id).val(maxValue);
            }
        }
    });
}

function majDoubleSoc(socid, ifVide) {
    if (!socid > 0)
        socid = $("#socid").val();
    if (socid > 0) {
        if (jQuery('#socid').find('option[value=' + socid + ']').size() > 0)
            valueStr = jQuery('#socid').find('option[value=' + socid + ']').html();
        else if (jQuery("#search_socid").size() > 0)
            valueStr = jQuery("#search_socid").val();
        if (typeof valueStr !== 'undefined' && valueStr + "x" != "x") {
            valueStr = valueStr.replace(" ", "_").replace(" ", "_").replace(" ", "_").replace(" ", "_").replace("'", "_").replace("/", "_");
            $("select.double").each(function () {
                val = jQuery(this).find(':selected').val();
                if (!(ifVide && val > 1)) {
                    select = $(this);
                    $(this).find('option[value="' + valueStr + '"]').each(function () {
                        $(this).attr("selected", "selected");
                        $(select).val(valueStr);
                        $(select).change();
                    });
                }
            });
        }
        $(".chrid-keyid").each(function () {
            var div = $(this).parent();
            jQuery.ajax({
                url: "ajax/getList.php",
                type: "POST",
                datatype: "html",
                data: "socid=" + socid + "&chrid-keyid=" + div.children(".chrid-keyid").val(),
                success: function (msg) {
//                eval(msg.find("script"));
                    if (msg != "") {
                        div.html(msg);
                        initFormChrono();
                    }
                }
            });
        });
    }
    $(".noteContrat").change(function () {
        ajNoteContrat();
    });
    ajNoteContrat();
}

attenteNote = false;
function ajNoteContrat() {
    if (attenteNote == false) {
        attenteNote = true;

        setTimeout(function () {
            selectCtr = $("div > .noteContrat");
            idCtr = selectCtr.val();
            divDest = selectCtr.parent().parent().parent().parent().parent().parent();

            if (idCtr > 0) {
                var datas = 'url=' + "/contrat/card.php?id=" + idCtr;
                datas = datas + '&type=note';
                jQuery.ajax({
                    url: DOL_URL_ROOT + '/synopsistools/ajax/note_ajax.php',
                    data: datas,
                    datatype: "xml",
                    type: "POST",
                    cache: false,
                    success: function (msg) {
                        if (msg != "0") {
                            if (msg.indexOf("[1]") > -1) {
                                msg = msg.replace("[1]", "");
                            }

                            $(".destNoteContrat").fadeOut();
                            divDest.append("<div class='destNoteContrat'>" + msg + "</div>");

                        }
                    }
                });
            }



            attenteNote = false;
        }, 500);
    }
}




jQuery(document).ready(function () {



    $("a.butAction").click(function () {
        lien = this;
        setTimeout(function () {
            $(lien).removeAttr('href');
            $(lien).removeAttr('onclick');
        }, 100);
    });



    $(".desactiveButtons").click(function () {
        setTimeout(function () {
            $(".butAction").removeAttr('href');
            $(".butAction").removeAttr('onclick');
        }, 100);
    });



    setTimeout(function () {
        socid = jQuery('#socid').find(':selected').val();
        if (!socid > 0)
            socid = $("#socid").val();
        majDoubleSoc(socid, true);
        jQuery('#socid').change(function () {
            setTimeout(function () {
                socid = jQuery(this).find(':selected').val();
                ajax_updater_postFct2(socid);
            }, 300);
        });

        $("#inputautocompletesocid").focusout(function () {
            socid = jQuery('#socid').find(':selected').val();
            if (!socid > 0)
                socid = $("#socid").val();
            ajax_updater_postFct2(socid);
        });
    }, 300);

    $(".addContact").click(function () {
        socid = $("select#socid").val();
        dispatchePopObject(socid, 'newContact', function () {
            ajax_updater_postFct2(socid, 'old');
        }, 'Contact', 1)
    });

    $(".addSoc").click(function () {
        socid = $("select#socid").val();
        dispatchePopObject(socid, 'newSoc', function () {
//            $("#form").attr('action', $("#form").attr('action')+"&action=modify&socid=max");
            $("#form").append('<input type="hidden" name="action2" value="Modify"/><input type="hidden" name="socid" value="max"/>');
            $(".required").removeClass("required");
            $("#form").submit();
        }, 'Société', 1)
    });


    jQuery.validator.addMethod(
            'required',
            function (value, element) {
                return (value + "x" != "x");
            },
            '<br/>Ce champs est requis'
            );
    jQuery('.datepicker').datepicker({showTime: false});
    jQuery('.datetimepicker').each(function () {
        date = $(this).attr("value");
        if ((date == "0000-00-00 00:00:00" || date == "") && $(this).hasClass("now")) {
            date = new Date();
            $(this).datetimepicker({showTime: true}).datepicker("setDate", date);
        }
        else{
            date = new Date($(this).val());
             $(this).datetimepicker({showTime: true}).datepicker("setDate", date);
        }
    });

    jQuery('#form').validate({
        submitHandler: function (form) {
            $("#Envoyer").attr("disabled", "disabled");
            form.submit();
        }
    });

    initFormChrono();

    setTimeout(function () {
        majOldSelect();
    }, 500);



    $("#search_idprod").keydown(function (e) {
        if (e.which == 13) {
            $(this).parent().find(".butAction").click();
            return false;
        }
    });
});




function addLienAj(firstParent, mode) {
    if (!mode)
        mode = "add";
    parentDiv = $(firstParent).parent();
    model = $(parentDiv).find(".model").html();
    select = $(parentDiv).find("select");
    selectId = $(select).val();
    if (selectId > 0) {
        idIncr++;
        selectNom = $(select).find("option:selected").text();
        ajaxManipElementElement(mode, $(parentDiv).find(".sourcetype").val(), $(parentDiv).find(".targettype").val(), $(parentDiv).find(".sourceid").val(), selectId, $(parentDiv).find(".ordre").val(), function (ok) {
            if (ok == "ok")
                addLienHtml(idIncr, selectId, selectNom, model, parentDiv);
        });
    }
}

function addChronoAj(firstParent) {
    parentDiv = $(firstParent).parent();
    model = $(parentDiv).find(".model").html();
    addChrono(firstParent, $("#socid").val(), function (valReturn) {
        targetType = $(parentDiv).find(".targettype").val();
        titre = 'Nouveau ' + targetType;
        ajaxManipElementElement("add", $(parentDiv).find(".sourcetype").val(), targetType, $(parentDiv).find(".sourceid").val(), valReturn, $(parentDiv).find(".ordre").val(), function (ok) {
            if (ok == "ok") {
                idIncr = idIncr + 1;
                addLienHtml(idIncr, valReturn, titre, model, parentDiv);
                dispatchePopObject(valReturn, 'chrono', function () {
                }, titre, 1);
            }
        });
    });
}

function initFormChrono() {
    $(".addLien, .changeLien").unbind('click');
    $(".showFormChrono").each(function () {
        $(this).parent().find(".chronoForm").hide();
        $(this).click(function () {
            $(this).parent().find(".chronoForm").show();
            $(this).parent().find(".hide").removeClass("hide");
            $(this).remove();
        });
    });




    idIncr = 100;
    $(".formAjax .addLien").click(function (event) {
        event.stopPropagation();
        addLienAj($(this));
        return false;
    });
    $(".formAjax .changeLien").click(function (event) {
        event.stopPropagation();
        $(this).parent().find("div.elem").each(function () {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        addLienAj($(this), 'set');
        return false;
    });
    $(".formAjax .addChrono").click(function (event) {
        event.stopPropagation();
        addChronoAj($(this));
        return false;
    });
    $(".formAjax .changeChrono").click(function (event) {
        event.stopPropagation();
        $(this).parent().find("div.elem").each(function () {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        addChronoAj($(this));
        return false;
    });
    $("form #chronoTable .addChrono").click(function (event) {
        alert("ee");
        event.cancelBubble = true;
        event.returnValue = false;
        event.stopPropagation();
        parentDiv = $(this).parent();
        model = $(parentDiv).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        titre = "Nouveau " + $(this).parent().parent().find("th").html();
        addChrono(this, socid, function (valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, titre, model, parentDiv);
            dispatchePopObject(valReturn, 'chrono', function () {
            }, titre, 1);
        });
        return true;
    });
    $("form #chronoTable .changeChrono").click(function (event) {
        event.stopPropagation();
        $(this).parent().find("div.elem").each(function () {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        parentDiv = $(this).parent();
        model = $(parentDiv).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        titre = "Nouveau " + $(this).parent().parent().find("th").html();
        addChrono(this, socid, function (valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, titre, model, parentDiv);
            dispatchePopObject(valReturn, 'chrono', function () {
            }, titre, 1);
        });
        return false;
    });

    $("form #chronoTable .addLien").click(function (event) {
        event.stopPropagation();
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        if (selectId > 0) {
            idIncr++;
            selectNom = $(select).find("option:selected").text();
            addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        }
        return false;
    });
    $("form #chronoTable .changeLien").click(function (event) {
        event.stopPropagation();
        $(this).parent().find("div.elem").each(function () {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        if (selectId > 0) {
            idIncr++;
            selectNom = $(select).find("option:selected").text();
            addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        }
        return false;
    });
}