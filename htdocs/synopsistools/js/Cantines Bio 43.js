$(window).on("load", function() {
     var number = 1000 + Math.floor(Math.random() * 99999);
     
     
     $("select[name='morphy']").parent().parent().hide();
     $("select[name='civilite_id']").parent().parent().hide();
     $("select[name='country_id']").parent().parent().hide();
     $("input[name='login']").parent().parent().hide();
     $("input[name='pass1']").parent().parent().hide();
     $("input[name='pass2']").parent().parent().hide();
     $("input[name='birth']").parent().parent().hide();
     $("input[name='photo']").parent().parent().hide();
     $("input[name='public']").parent().parent().hide();
     
     
     $("input[name='login']").attr("value", "loginAll"+number);
     $("select[name='morphy']").attr("value", "phy");
     $("input[name='pass1']").attr("value", "mdp"+number);
     $("input[name='pass2']").attr("value", "mdp"+number);
});