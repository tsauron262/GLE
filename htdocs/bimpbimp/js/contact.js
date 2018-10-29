$(window).on("load", function () {
    $("form[name='formsoc']").submit(function(){
        if($("#phone_pro").val() == "" && $("#phone_mobile").val() == "")
            return confirm("Pas de numéro de Téléphone, êtes-vous sur ?");
    });
});


