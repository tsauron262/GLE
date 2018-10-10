var reload_fixe_tabs_hold = false;
var reload_fixe_tabs_delay = 3000;

function reloadFixeTabs(iterate) {
    if (reload_fixe_tabs_hold) {
        setTimeout(function () {
            reloadFixeTabs(iterate);
        }, 3000);
    } else {
        BimpAjax('loadFixeTabs', {}, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            display_warnings_in_popup_only: true,
            success: function (result, bimpAjax) {
                if (result.html) {
                    $('#bimp_fixe_tabs').html(result.html);
                }
                setFixeTabsEvents();
            }
        });

        if (iterate) {
            setTimeout(function () {
                reloadFixeTabs(true);
            }, reload_fixe_tabs_delay);
        }
    }
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
}

$(document).ready(function () {
    setFixeTabsEvents();

    $('#bimp_fixe_tabs').mouseleave(function () {
        reload_fixe_tabs_hold = false;
        $(this).find('.fixe_tab_caption').removeClass('active');
        $('#bimp_fixe_tabs_contents').stop().slideUp(250, function () {
            $(this).find('.fixe_tab_content').hide();
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
    }, reload_fixe_tabs_delay);
});