var window_active = true;
var reload_fixe_tabs_hold = false;
var randomId = getRandomInt(9999999999999);
var reload_fixe_tabs_processing = false;
 
function reloadFixeTabs(iterate, reload_fixe_tabs_delay) {
    if (!window_active || reload_fixe_tabs_processing) {
        return;
    }
 
    if (!reload_fixe_tabs_delay) {
        reload_fixe_tabs_delay = 1000;
    }
//    if (reload_fixe_tabs_hold) {
//        setTimeout(function () {
//            reloadFixeTabs(iterate);
//        }, 3000);
//    } else {
 
    reload_fixe_tabs_processing = true;
    BimpAjax('loadFixeTabs', {randomId: randomId}, null, {
        display_success: false,
        display_errors: false,
        display_warnings: false,
        success: function (result, bimpAjax) {
            reload_fixe_tabs_processing = false;
            if (result.html) {
                $('#bimp_fixe_tabs').html(result.html);
            }
            setFixeTabsEvents();
 
            if (iterate) {
                setTimeout(function () {
                    reloadFixeTabs(true);
                }, reload_fixe_tabs_delay);
            }
 
            $('body').trigger($.Event('fixeTabsReloaded', {}));
        },
        error: function () {
            reload_fixe_tabs_processing = false;
            if (iterate) {
                setTimeout(function () {
                    reloadFixeTabs(true, reload_fixe_tabs_delay * 1.4);
                }, reload_fixe_tabs_delay);
            }
        }
 
    });
 
//    }
}
 
function setFixeTabsEvents() {
    $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').each(function () {
        if (!parseInt($(this).data('fixe_tab_caption_event_init'))) {
            $(this).mouseenter(function () {
                reload_fixe_tabs_hold = true;
                $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').removeClass('active');
                $(this).addClass('active');
                $('#bimp_fixe_tabs_contents').find('.fixe_tab_content').hide();
                var id = $(this).data('id_tab');
                var $content = $('#fixe_tab_content_' + id);
                if ($content.length) {
                    $content.show();
                }
                $('#bimp_fixe_tabs_contents').stop().slideDown(250);
            });
 
            $(this).data('fixe_tab_caption_event_init', 1);
        }
    });
 
    $('bimp_fixe_tabs_contents').each(function () {
        if (!parseInt($(this).data('fixe_tab_content_event_init'))) {
            setCommonEvents($(this));
        }
    });
}
 
$(document).ready(function () {
    if (!parseInt($(this).data('fixe_tabs_events_init'))) {
        setFixeTabsEvents();
 
        $('#bimp_fixe_tabs').mouseleave(function () {
            reload_fixe_tabs_hold = false;
            $(this).find('.fixe_tab_caption').removeClass('active');
            $('#bimp_fixe_tabs_contents').stop().slideUp(250, function () {
                $(this).find('.fixe_tab_content').hide();
                $(this).removeAttr('style');
            });
        });
 
        $('body').on('objectChange', function (e) {
            if (e.module === 'bimpsupport') {
                if (e.object_name === 'BS_Ticket' ||
                        e.object_name === 'BS_Inter') {
                    reloadFixeTabs(false);
                }
            }
        });
 
        $('body').on('objectDelete', function (e) {
            if (e.module === 'bimpsupport') {
                if (e.object_name === 'BS_Ticket' ||
                        e.object_name === 'BS_Inter') {
                    reloadFixeTabs(false);
                }
            }
        });
        setTimeout(function () {
            reloadFixeTabs(true);
        }, 2000);
 
        if (!parseInt($(window).data('focus_event_init'))) {
            $(window).focus(function () {
                window_active = true;
                reloadFixeTabs(true);
            });
            $(window).blur(function () {
                window_active = false;
            });
 
            $(window).data('focus_event_init', 1);
        }
 
        $(this).data('fixe_tabs_events_init', 1);
    }
});