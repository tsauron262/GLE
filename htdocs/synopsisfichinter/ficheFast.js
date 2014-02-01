
function initPresta() {
    jQuery(".supprPrestaButton").click(function() {
        jQuery("#supprPresta").val(jQuery("#supprPresta").val() + $(this).attr("id").replace("suppr_", ""));
        jQuery(this).parent().parent().next("tr").next("tr").fadeOut();
        jQuery(this).parent().parent().next("tr").fadeOut();
        jQuery(this).parent().parent().fadeOut();
    });
}
jQuery(document).ready(function() {
    initPresta();
    jQuery("#ajPresta").click(function() {
        nbPresta++;
        prefId = "presta" + nbPresta + "_";
        actuContradet();
        var htmlStr = jQuery("#refAjaxLigne table tbody").html();
        varARemp = "'+prefId+'";
        htmlStr = htmlStr.split(varARemp).join(prefId);
        htmlStr = htmlStr.split("-supprjs").join("");
        jQuery("#ajPrestaZone").before(htmlStr);
        initHeure($(".heures"));
        initPresta();
    });
    jQuery(".heures").each(function() {
        initHeure(this);
    });
    autoSave();
});
function autoSave() {
    jQuery("*").click(function() {
        initTimeSave();
    });
    jQuery("*").keypress(function() {
        initTimeSave();
    });
    enreg = false;
    function boucleSave() {
        if (new Date().getTime() > timeMax && enreg == false){
            enreg = true;
            jQuery("form.formFast").submit();
        }
        setTimeout(function() {
            boucleSave();
        }, 1000);
    }
    timeMax = null;
    function initTimeSave() {
        if (timeMax == null) {
            timeMax = new Date().getTime() + 30000;
            boucleSave();
        }
        else
            timeMax = new Date().getTime() + 30000;
    }
}

function initHeure(elem) {
    jQuery(elem).addClass("originalHeure");
    jQuery(elem).removeClass("heures");
    jQuery(elem).hide();
    select1 = "";
    for (i = 0; i < 12; i++)
        select1 = select1 + '<option value="' + i + '">' + i + '</option>';
    select2 = "";
    for (i = 0; i < 11; i++)
        select2 = select2 + '<option value="' + i + '">' + i * 5 + '</option>';
    jQuery(elem).before('<select class="heureHeure">' + select1 + '</select>H <select class="minHeure">' + select2 + '</select>M ');





//mise a jour
    secondesAv = jQuery(elem).val();
    heuresAv = 0;
    minAv = 0;
    for (; secondesAv >= 3600; secondesAv = secondesAv - 3600)
        heuresAv++;
    for (; secondesAv >= 300; secondesAv = secondesAv - 300)
        minAv++;
    $(elem).parent().find(".heureHeure option[value=" + heuresAv + "]").attr("selected", "selected");
    $(elem).parent().find(".minHeure option[value=" + minAv + "]").attr("selected", "selected");
//Enreg
    jQuery(elem).parent().find(".heureHeure, .minHeure").change(function() {
        secondes = (3600 * parseInt(jQuery(elem).parent().find(".heureHeure option:selected").val())) + (300 * parseInt(jQuery(elem).parent().find(".minHeure option:selected").val()));
        jQuery(elem).parent().find(".originalHeure").val(secondes);
    });
    cacherDecacherPRDV();
    $(".interTerm").change(function() {
        cacherDecacherPRDV();
    });
    cacherDecacherProd();
    $(".fk_typeinterv").change(function() {
        cacherDecacherProd();
    });
    jQuery(".radioContradet").change(function() {
        actuContradet();
    });
    actuContradet();
}




function actuContradet() {
    jQuery(".radioContradet").removeAttr("disabled");
    jQuery(".radioContradet").each(function() {
        if (jQuery(this).is(":checked"))
            jQuery(".contradet" + jQuery(this).val() + ":not(:checked)").attr("disabled", true);
    });
}

function cacherDecacherPRDV() {
    zoneRDV = $(".zoneDatePREDV");
    if ($(".interTerm").is(":checked"))
        zoneRDV.hide();
    else
        zoneRDV.show();
}

function cacherDecacherProd() {
    $(".fk_typeinterv").each(function() {
        select = $("select[name='" + $(this).attr("name").replace("fk_typeinterv", "fk_prod") + "']");
        if ($(this).val() == "2")
            select.hide();
        else
            select.show();
    });
}


zoneRDV = $(".zoneDatePREDV");
if ($(".interTerm").is(":checked"))
    zoneRDV.hide();
else
    zoneRDV.show();