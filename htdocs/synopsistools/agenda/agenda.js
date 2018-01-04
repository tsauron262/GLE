$(window).on("load", function () {
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
        if (te - ts > 150) {
            $('.wc-prev').click();
        }
        if (te - ts < -150) {
            $('.wc-next').click();
        }
    });
});

function initNbUser() {
    i = 0;
    $("#chosenSelectId option:selected").each(function () {
        i++;
    });
    $(".nbGroup").html(i + " personnes");
}


function initSimult() {
    $(".wc-cal-event").each(function () {
//        alert($(this).css("top") + "|"+$(this).position().top);
        if (chevauche) {
            if ($(this).css("top").replace("px", "") < $(this).position().top)
                $(this).parent().find(".wc-cal-event").css("width", (100 / $(this).parent().find(".wc-cal-event").length) - 1 + "%");
        } else {
            $(this).css("position", "absolute");
        }
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

/**
 * Chosen: Multiple Dropdown
 */

$(document).ready(function () {
    
    $(".dropdown").chosen();
//    window.WDS_Chosen_Multiple_Dropdown = {};
//    (function (window, $, that) {
//
//        // Constructor.
//        that.init = function () {
//            that.cache();
//
//            if (that.meetsRequirements) {
//                that.bindEvents();
//            }
//        };
//
//        // Cache all the things.
//        that.cache = function () {
//            that.param = {
//                window: $(window),
//                theDropdown: $('.dropdown')
//            };
//        };
//
//        // Combine all events.
//        that.bindEvents = function () {
//            that.param.window.on('load', that.applyChosen);
//        };
//
//        // Do we meet the requirements?
//        that.meetsRequirements = function () {
//            return that.param.theDropdown.length;
//        };
//
//        // Apply the Chosen.js library to a dropdown.
//        // https://harvesthq.github.io/chosen/options.html
//        that.applyChosen = function () {
//            that.param.theDropdown.chosen({
//                inherit_select_classes: true,
//                width: '300px'
//            });
//        };
//
//        // Engage!
//        $(that.init);
//
//    })(window, jQuery, window.WDS_Chosen_Multiple_Dropdown);

    $('#clearAll').click(function () {
        $("#chosenSelectId").children().each(function () {
            $(this).prop('selected', false);
            $(this).trigger("chosen:updated");
        });
        initNbUser();
    });

    $("#group").change(function () {
        id = $(this).find("option:selected").val();
        tabGroup[id].forEach(function (element) {
            $("#user" + element).attr('selected', '');
            $("#user" + element).trigger("chosen:updated");
        });
        initNbUser();
    });
});