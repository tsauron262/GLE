
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


    $("form.formFast").submit(function() {
        if((heureNull($("input[name='date1']").val()) || heureNull($("input[name='date3']").val())) 
                && (heureNull($("input[name='date2']").val()) || heureNull($("input[name='date4']").val()))){
        alert("Merci de remplir les horaires");
        return false;
    }
    });

    autoSave(function(){
            jQuery("form.formFast").submit();
        });
});
function heureNull(str){
    if(str == "00:00" || str == "" || str == " ")
        return true;
    return false;
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
        
        if($(this).val() == 4 || $(this).val() == 16 || $(this).val() == 22){
            $("input[name='" + $(this).attr("name").replace("fk_typeinterv", "forfait") + "']").attr("checked", "checked");
            textarea = $("textarea[name='" + $(this).attr("name").replace("fk_typeinterv", "descPrest") + "']");
            textarea.val(textarea.val()+ "Déplacement");
        }
    });
    
    $("#for0").change(function(){
       $("input[name='datePRDV']").val("00/00/0000");
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
//        select = $("select[name='" + $(this).attr("name").replace("fk_typeinterv", "fk_prod") + "']");
        select = $('select[name="' + $(this).attr("name").replace("fk_typeinterv", "fk_prod") + '"]');
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

