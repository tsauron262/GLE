$(window).load(function () {
    $("div.eventAbss").hover(function () {
        elem = this;
        setTimeout(function () {
            $(elem).addClass("actif");
        }, 500);
    }, function () {
        setTimeout(function () {
            $("div.eventAbss").removeClass("actif");
        }, 500);
    });
    $("#group").change(function () {
        id = $(this).find("option:selected").val();
        $(".userCheck").removeAttr("checked");
        for (var idUser in tabGroup[id]) {
            $(".userCheck#user" + tabGroup[id][idUser]).attr("checked", "checked");
        }
        initNbUser();
    });
    $(".userCheck").change(function () {
        initNbUser();
    });
    initNbUser();
    $('.contentListUser').click(function () {
        setTimeout(function () {
            $('.listUser').fadeIn();
        }, 500);
    });
//    $('.listUser').hover(function(){
//        setTimeout(function(){
//            $('.listUser').fadeIn();
//        }, 500);
//    }, function(){
//        setTimeout(function(){
//            $('.listUser').fadeOut();
//        }, 500);
//    });




    $("body").keyup(function (e) {
        //alert(e.keyCode);
        if (e.keyCode == 37) {
            $(".ui-button-icon-primary.ui-icon.ui-icon-seek-prev").click();
        }
        if (e.keyCode == 39) {
            $(".ui-button-icon-primary.ui-icon.ui-icon-seek-next").click();
        }
    });

//    $(".dateVue").val("2010/01/01");

    $('#calendar').on("touchstart", function (e) {
        ts = e.originalEvent.touches[0].clientX;
    });
    
    $('#calendar').on("touchend", function (e) {
        var te = e.originalEvent.changedTouches[0].clientX;
        if(te-ts>150){
            $('.wc-prev').click();
        }
        if(te-ts<-150){
            $('.wc-next').click();
        }
    });
});

function initNbUser() {
    i = 0;
    $(".userCheck:checked").each(function () {
        i++;
    });
    $(".nbGroup").html(i + " personnes");
}


function initSimult() {
    $(".wc-cal-event").each(function () {
//        alert($(this).css("top") + "|"+$(this).position().top);
        if ($(this).css("top").replace("px", "") < $(this).position().top)
            $(this).parent().find(".wc-cal-event").css("width", (100 / $(this).parent().find(".wc-cal-event").size()) - 1 + "%");
    });

    $("div.wc-title a").click(function () {
        id = $(this).parent().find(".idAction").attr("value");
        titre = $(this).html();
        dispatchePopIFrame(DOL_URL_ROOT + "/comm/action/card.php?id=" + id + "&action=edit&optioncss=print", function () {
            $('#calendar').weekCalendar('refresh');
        }, titre, 100);
        return false;
    });
}