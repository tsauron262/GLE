$(document).ready(function(){
    
    var qs  = $('.qs');
    var infos = $("#informations");
    var popover = $("#popover");
    var page = $(".bimp_ldlc_page");
    
    popover.text("Fermer les informations légales");
    
    console.log(infos);
    
    qs.click(function(){
        var etat = infos.css('display');
        if(etat == "none") {
            popover.text("Fermer les informations légales");
            infos.fadeIn();
        } else if(etat == "block") {
            popover.text("Ouvrir les informations légales");
            infos.fadeOut();
        }
    });

});
