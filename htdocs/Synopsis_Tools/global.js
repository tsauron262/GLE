$(window).load(function(){
    heightDif = $(".fiche").innerHeight() - $(".tabBar").height(); //hauteur du rest (ne change pas
    $(window).resize(function(){
        traiteScroll(heightDif);
    });
    traiteScroll(heightDif);
    
    
    $("#mainmenua_SynopsisTools.tmenudisabled").parent().hide();
    
//    $(".s-ico").show();
});

function dialogConfirm(url, titre, yes, no, id){
    if (confirm(titre)) {
        window.location = url;
    } 
}

function traiteScroll(heightDif){
    coef = 1;
    scrollY  = $(window).scrollTop() * coef - 100;
    if($(".fiche").scrollTop()> scrollY)
        scrollY  = $(".fiche").scrollTop();
    if($(".tabBar").scrollTop()> scrollY)
        scrollY  = $(".tabBar").scrollTop();
    height =parseInt(window.innerHeight);
    var i = 0;
    $(".fiche").parent().parent().children("td").each(function(){
        i = i+1;
    });
    //    if(height > 560 && i < 3)
    hauteurMenu = parseInt($("div.vmenu").innerHeight()) + parseInt($("#tmenu_tooltip").innerHeight())+30;
    if(height > hauteurMenu && i < 3){//On active le scroll 2
        $(".fiche").addClass("reglabe");
        heightNew = ($(".fiche").innerHeight() - heightDif) - 20;
        if(heightNew > 300){//On active le scroll 3 (le scroll 2 ne doit plus étre utile
            $(".tabBar").height(heightNew);
            $(".tabBar").addClass("reglabe2");
            $(".tabBar").scrollTop(scrollY);
        }
        else{//On désactive le scroll 3 (le scroll 2 doit prendre le relai)
            $(".tabBar").removeClass("reglabe2");
            $(".tabBar").height('auto');
            $(".fiche").scrollTop(scrollY);
        }
    //        alert(heightAv+" "+heightAp);
    }
    else{//On desactive les scroll2 et 3
        $(".fiche").removeClass("reglabe");
        $(".tabBar").removeClass("reglabe2");
        $(".tabBar").height('auto');
        $(window).scrollTop(scrollY);
    }
//    document.body.scrollTop = scrollY;
}