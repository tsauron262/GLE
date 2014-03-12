
function ajax_updater_postFct(socid, valueSelected)
{
    if (valueSelected == "socid")
        majDoubleSoc(socid, false);
    $("#" + valueSelected).trigger("change");
}

function ajax_updater_postFct2(socid, valueSelected)
{
    if (socid > 0)
    {
        majDoubleSoc(socid, false);
        jQuery.ajax({
            url: "ajax/contactSoc-xml_response.php",
            type: "POST",
            datatype: "xml",
            data: "socid=" + socid,
            success: function(msg) {
                jQuery('#contactSociete').replaceWith("<div id='contactSociete'>" + jQuery(msg).find('contactsList').text() + "</div>");
//                          jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                if (valueSelected == 'old') {
                    idMax = 0;
                    jQuery('#contactSociete').find('option').each(function() {
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
    } else {
        jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
    }
}
function majDoubleSoc(socid, ifVide) {
    valueStr = jQuery('#socid').find('option[value=' + socid + ']').html().replace(" ", "_").replace(" ", "_").replace(" ", "_").replace(" ", "_").replace("'", "_").replace("/", "_");
    $("select.double").each(function() {
        val = jQuery(this).find(':selected').val();
        if (!(ifVide && val > 1)) {
            select = $(this);
            $(this).find('option[value=' + valueStr + ']').each(function() {
                $(this).attr("selected", "selected");
                $(select).val(valueStr);
                $(select).change();
            });
        }
    });
    $(".chrid-keyid").each(function() {
            div = $(this).parent();
            jQuery.ajax({
                url: "ajax/getList.php",
                type: "POST",
                datatype: "html",
                data: "socid=" + socid + "&chrid-keyid=" + div.children(".chrid-keyid").val(),
                success: function(msg) {
//                eval(msg.find("script"));
                    if (msg != "") {
                        div.html(msg);
                        initFormChrono();
                    }
                }
            });
    });
}
jQuery(document).ready(function() {
    setTimeout(function() {
        socid = jQuery('#socid').find(':selected').val();
        majDoubleSoc(socid, true);
    }, 200);
    jQuery('#socid').change(function() {
        socid = jQuery(this).find(':selected').val();
        ajax_updater_postFct2(socid);
    });

    $(".addContact").click(function() {
        socid = $("select#socid").val();
        popOjectAffiche(socid, 'newContact', function() {
            ajax_updater_postFct2(socid, 'old');
        }, 'Contact', 1)
    });


    jQuery.validator.addMethod(
            'required',
            function(value, element) {
                return (value + "x" != "x");
            },
            '<br/>Ce champs est requis'
            );
    jQuery('.datepicker').datepicker({showTime: false});
    jQuery('.datetimepicker').each(function() {
        date = $(this).attr("value");
        if (date == "" && $(this).hasClass("now"))
            date = new Date();
        $(this).datetimepicker({showTime: true}).datepicker("setDate", date);
    });

    jQuery('#form').validate();
});




function addLienAj(firstParent, mode) {
    if (!mode)
        mode = "add";
    parentDiv = $(firstParent).parent();
    model = $(parentDiv).find(".model").html();
    select = $(parentDiv).find("select");
    selectId = $(select).val();
    idIncr++;
    selectNom = $(select).find("option:selected").text();
    ajaxManipElementElement(mode, $(parentDiv).find(".sourcetype").val(), $(parentDiv).find(".targettype").val(), $(parentDiv).find(".sourceid").val(), selectId, $(parentDiv).find(".ordre").val(), function(ok) {
        if (ok == "ok")
            addLienHtml(idIncr, selectId, selectNom, model, parentDiv);
    });
}

function addChronoAj(firstParent) {
    parentDiv = $(firstParent).parent();
    model = $(parentDiv).find(".model").html();
    addChrono(firstParent, $("#socid").val(), function(valReturn) {
        targetType = $(parentDiv).find(".targettype").val();
        titre = 'Nouveau ' + targetType;
        ajaxManipElementElement("add", $(parentDiv).find(".sourcetype").val(), targetType, $(parentDiv).find(".sourceid").val(), valReturn, $(parentDiv).find(".ordre").val(), function(ok) {
            if (ok == "ok") {
                idIncr = idIncr + 1;
                addLienHtml(idIncr, valReturn, titre, model, parentDiv);
                dispatchePopObject(valReturn, 'chrono', function() {
                }, titre, 1);
            }
        });
    });
}

function initFormChrono() {
    $(".showFormChrono").each(function() {
        $(this).parent().find(".chronoForm").hide();
        $(this).click(function() {
            $(this).parent().find(".chronoForm").show();
            $(this).parent().find(".hide").removeClass("hide");
            $(this).remove();
        });
    });




    idIncr = 100;
    $(".formAjax .addLien").click(function() {
        addLienAj($(this));
        return false;
    });
    $(".formAjax .changeLien").click(function() {
        $(this).parent().find("div.elem").each(function() {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        addLienAj($(this), 'set');
        return false;
    });
    $(".formAjax .addChrono").click(function() {
        addChronoAj($(this));
        return false;
    });
    $(".formAjax .changeChrono").click(function() {
        $(this).parent().find("div.elem").each(function() {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        addChronoAj($(this));
        return false;
    });
    $("form #chronoTable .addChrono").click(function() {
        parentDiv = $(this).parent();
        model = $(parentDiv).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        titre = "Nouveau " + $(this).parent().parent().find("th").html();
        addChrono(this, socid, function(valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, titre, model, parentDiv);
            dispatchePopObject(valReturn, 'chrono', function() {
            }, titre, 1);
        });
        return false;
    });
    $("form #chronoTable .changeChrono").click(function() {
        $(this).parent().find("div.elem").each(function() {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        parentDiv = $(this).parent();
        model = $(parentDiv).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        titre = "Nouveau " + $(this).parent().parent().find("th").html();
        addChrono(this, socid, function(valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, titre, model, parentDiv);
            dispatchePopObject(valReturn, 'chrono', function() {
            }, titre, 1);
        });
        return false;
    });

    $("form #chronoTable .addLien").click(function() {
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        idIncr++;
        selectNom = $(select).find("option:selected").text();
        addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        return false;
    });
    $("form #chronoTable .changeLien").click(function() {
        $(this).parent().find("div.elem").each(function() {
            if (!$(this).hasClass("model"))
                $(this).remove();
        });
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        idIncr++;
        selectNom = $(select).find("option:selected").text();
        addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        return false;
    });
}