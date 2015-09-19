/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var responsive = true;

var is_open = false;
var moving = false;
var clickmenu = false;
var opac = 0.7;
var tailleMenu1 = 150;
var tailleMenu2 = 200;
var maxmenu = tailleMenu1 + tailleMenu2;


$(window).load(function () {
    var larg = $(window).width();
    if (larg < 940 || 1) {
        $('.login_table td').wrap("<tr></tr>");
    }

    if (responsive) {

        

        $('#id-container').append("<div id='but-menu'></div>");

        $('#id-right').parent().append("<div id='id_cach' ></div>");
        
        $('#tmenu_tooltip').append("<div id='responsive_titre'><a href='"+DOL_URL_ROOT+"/index.php?mainmenu=home'>GLE</a></div>");

        $('#but-menu').click(function (e) {
            if (is_open == false) {
                push_menu(true);
            } else {
                push_menu(false);
            }
        });

        $('.vmenu').click(function (e) {
            clickmenu = true;
            e.stopPropagation();
        });

        $('#id-right ,#id_cach').click(function (e) {
            if (is_open == true && !clickmenu) {
                push_menu(false);
//            close_menu();
            }
            clickmenu = false;
        });

        $(window).on("touchstart", function (e) {
            ts = e.originalEvent.touches[0].clientX;
        });

        $(window).on("touchmove", function (e) {
            var te = e.originalEvent.changedTouches[0].clientX;
            if (ts < 100) {//ouverture
                move_menu(te);
//            open_menu(ti);
            }
//        }
//        if (ts - te > 200) {//fermeture
//            push_menu(ti-maxmenu);
////            $('#id-left').clearQueue().animate({"left": '-300'},null,null,function(){
////                
////            is_open = false;
//                $('#id_cach').fadeOut();
//////            close_menu();
////            });
//        }
        });

        $('#id-left').on("touchmove", function (e) {
            var te = e.originalEvent.changedTouches[0].clientX;
            move_menu(te);

        });

        $(window).on("touchend", function (e) {
            var tu = e.originalEvent.changedTouches[0].clientX;
            if (is_open) {
                tu += maxmenu;
            }
            var ti = tu - ts;
            if (ti > maxmenu / 2) {
                if (moving) {
                    push_menu(true);
                }
            } else if (ti < -maxmenu / 2 || moving) {
                push_menu(false);
            }

            moving = false;
        });
//    $('body').addEventListener(swl,open_menu,false);
    }
});

memoireTe = 0;
function move_menu(te) {
    diff = memoireTe - te;
    if(diff > 10 || diff < -10){//Pour ne bouger que tous les 10px
    memoireTe = te;
//    console.log(te);
    if (is_open) {
        te += maxmenu;
    }
    moving = true;
//            if (te - ts > 1) {
    var ti = (te - ts);
    if (ti > maxmenu) {
        ti = maxmenu;
    }
    $('#id-left').css("left", ti - maxmenu + tailleMenu1);
    $('.tmenudiv').css("left", ti - maxmenu);
    $('#id-left').css("z-index", "60");
    $('.tmenudiv').css("z-index", "60");
    $('#id_cach').show();
    $('#id_cach').css("opacity", ti / maxmenu * opac);
}
//    is_open = true;
}

//function close_menu() {
//    $('#id-left').clearQueue().animate({"left": '-190'});
//    $('#id-left').css("height", "10px");
//    $('#id-left').css("width", "auto");
//    $('.vmenu').css("height", "10px");
//    $('.vmenu').css("width", "174px");
//    $('#id-right').fadeIn();
//    is_open = false;
//}
//
//function open_menu(ti) {
////        $('#id-left').clearQueue().animate({"left": '0'});
//    $('#id-left').css("left", ti / 190);
//    $('#id-left').css("height", "100%");
//    $('.vmenu').css("height", "100%");
//    $('#id-left').css("width", "100%");
//    $('.vmenu').css("width", "50%");
//    $('#id-right').fadeOut();
//    is_open = true;
//}

function push_menu(posX) {//posX -> sert à savoir si le menu doit etre pousser à guche ou à droite
    if (posX == true) {//pousser à droite
        $('#id-left').css("z-index", "60");
        $('.tmenudiv').css("z-index", "60");
        $('#id_cach').show();
        $('#id_cach').clearQueue().animate({"opacity": opac});
        $('#id-left').clearQueue().animate({"left": tailleMenu1});
        $('.tmenudiv').clearQueue().animate({"left": '0'});
        $('body').css('overflow', 'hidden');
        is_open = true;
    } else {
        $('#id-left').clearQueue().animate({"left": -maxmenu});
        $('.tmenudiv').clearQueue().animate({"left": -maxmenu});
        $('#id_cach').clearQueue().animate({"opacity": 0},null,null,function(){$('#id_cach').hide();});
        
        $('body').css('overflow', 'scroll');
        is_open = false;
    }
}

