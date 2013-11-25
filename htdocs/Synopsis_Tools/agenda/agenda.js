$(window).load(function() {
    $("div.eventAbss").hover(function(){
        elem = this;
        setTimeout(function(){
            $(elem).css("z-index", "1000");
        }, 500);
    },function(){
        setTimeout(function(){
            $("div.eventAbss").css("z-index", "");
        }, 500);
    });
    $("#group").change(function(){
        id = $(this).find("option:selected").val();
        $(".userCheck").removeAttr("checked");
        for(var idUser in tabGroup[id]){
            $(".userCheck#user"+tabGroup[id][idUser]).attr("checked", "checked");
        }
        initNbUser();
    });
    $(".userCheck").change(function(){
        initNbUser();
    });
    initNbUser();
    $('.listUser, .nbGroup').hover(function(){
        setTimeout(function(){
            $('.listUser').fadeIn();
        }, 500);
    }, function(){
        setTimeout(function(){
            $('.listUser').fadeOut();
        }, 500);
    });
    
//    $(".dateVue").val("2010/01/01");
});

function initNbUser(){
    i = 0;
    $(".userCheck:checked").each(function(){
        i++;
    });
    $(".nbGroup").html(i+" personnes");
}