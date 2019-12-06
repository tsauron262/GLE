function BimpFixeTabs() {
    this.id = getRandomInt(9999999999999);
    this.active = true;
    this.hold = false;
    this.processing = false;
    this.delay = 0;
    this.$loading = $();
    this.$refreshBtn = $();

    var ft = this;

    this.reload = function () {
        if (!ft.active || ft.processing) {
            return;
        }

        if (ft.hold) {
            ft.delay = 0;
            ft.iterate();
        } else {
            ft.processing = true;
            ft.$loading.show();
            ft.$refreshBtn.hide();
            BimpAjax('loadFixeTabs', {randomId: ft.id}, null, {
                display_success: false,
                display_errors: false,
                display_warnings: false,
                success: function (result, bimpAjax) {
                    ft.processing = false;
                    ft.$loading.hide();
                    ft.$refreshBtn.show();

                    if (result.html) {
                        ft.delay = 0;
                        $('#bimp_fixe_tabs').html(result.html);
                        ft.setEvents();
                        $('body').trigger($.Event('fixeTabsReloaded', {}));
                    }

                    ft.iterate();
                },
                error: function () {
                    ft.processing = false;
                    ft.$loading.hide();
                    ft.$refreshBtn.show();
                    ft.iterate();
                }

            });
        }
    };

    this.iterate = function () {
        if (ft.delay < 10000) {
            ft.delay += 2000;
        }

        if (ft.delay > 0) {
            setTimeout(function () {
                ft.reload();
            }, ft.delay);
        } else {
            ft.reload();
        }
    };

    this.onWindowLoaded = function () {
        if (!parseInt($('body').data('fixe_tabs_events_init'))) {
            $('#bimp_fixe_tabs').mouseleave(function () {
                ft.hold = false;
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
                        ft.delay = -2000;
                        ft.iterate();
                    }
                }
            });

            $('body').on('objectDelete', function (e) {
                if (e.module === 'bimpsupport') {
                    if (e.object_name === 'BS_Ticket' ||
                            e.object_name === 'BS_Inter') {
                        ft.delay = -2000;
                        ft.iterate();
                    }
                }
            });

            ft.setEvents();
            ft.iterate();

            if (!parseInt($(window).data('focus_event_init'))) {
                $(window).focus(function () {
                    ft.active = true;
                    ft.iterate();
                });

                $(window).blur(function () {
                    ft.active = false;
                });

                $(window).data('focus_event_init', 1);
            }

            $('body').data('fixe_tabs_events_init', 1);
        }
    };

    this.setEvents = function () {
        ft.$loading = $('#bimp_fixe_tabs_captions').find('.fixe_tabs_loading');
        ft.$refreshBtn = $('#bimp_fixe_tabs_captions').find('.fixe_tabs_refresh_btn');

        $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').each(function () {
            if (!parseInt($(this).data('fixe_tab_caption_event_init'))) {
                $(this).mouseenter(function () {
                    ft.hold = true;
                    $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').removeClass('active');
                    $(this).addClass('active');
                    $('#bimp_fixe_tabs_contents').find('.fixe_tab_content').hide();
                    var id_tab = $(this).data('id_tab');
                    var $content = $('#fixe_tab_content_' + id_tab);
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
                setInputsEvents($(this));
                $(this).data('fixe_tab_content_event_init', 1);
            }
        });
    };
}

var bimpFixeTabs = new BimpFixeTabs();

$(document).ready(function () {
    bimpFixeTabs.onWindowLoaded();
});