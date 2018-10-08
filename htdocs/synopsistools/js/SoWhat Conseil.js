$(window).on("load", function () {
    var selectTmp = "";
    selectTmp += "<option></option>";
    selectTmp += "<option>HONORAIRES DE SUIVI</option>";
    selectTmp += "<option>HONORAIRES DE CREATION</option>";
    selectTmp += "<option>IMPRESSION</option>";
    selectTmp += "<option>IMPRESSION ET LIVRAISON</option>";
    selectTmp += "<option>PRISES DE VUES</option>";
    selectTmp += "<option>PRESTATION VIDEO</option>";
    $("#select_type").parent().append("<div><select id='titreLigne'>"+selectTmp+"</select></div>");
    $("#select_type").val(0);
    $("#prod_entry_mode_free").prop('checked', true);
    $("#titreLigne").change(function(){
        $("#dp_desc").val($(this).val());
        $("#price_ht").val("0");
        $("#qty").val("2");
    });
});
    