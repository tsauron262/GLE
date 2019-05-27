function loadTabContent(url, tab_name) {
    var $container = $('#controllerTabContentContainer');
    var $tabs = $('.tabs');
    if (!$.isOk($tabs)) {
        return;
    }
    
    var $tab = $tabs.find('#'+tab_name);
    
    if (!$.isOk($tab)) {
        return;
    }

    $tabs.find('.tabactive').each(function () {
        $(this).removeClass('tabactive').addClass('tabunactive');
    });

    $tab.removeClass('tabunactive').addClass('tabactive');

    if ($container.length) {
        $container.fadeOut(250, function () {
            var $content = $(this).find('#controllerTabContent');
            $content.html('').hide();
            $(this).find('.content-loading').show();
            $(this).show();

            BimpAjax('loadControllerTab', {}, $content, {
                $container: $container,
                $content: $content,
                display_success: false,
                url: url,
                success: function (result, bimpAjax) {
                    if (typeof (result.html) !== 'undefined') {
                        bimpAjax.$container.find('.content-loading').hide();
                        bimpAjax.$content.html(result.html).fadeIn(250);
                        $('body').trigger($.Event('controllerTabLoaded', {
                            $container: $container,
                            tab_name: tab_name
                        }));
                    }
                },
                error: function (result, bimpAjax) {
                    bimpAjax.$container.find('.content-loading').hide();
                }
            });
        });
    }
}

function onUrlHashChange(newUrl) {
    if (/#(.+)$/.test(newUrl)) {
        var tab_name = newUrl.replace(/^(.*)#(.*)$/, '$2');
        newUrl = newUrl.replace(/^(.*)#(.*)$/, '$1');
        if (/tab=/.test(newUrl)) {
            newUrl = newUrl.replace(/^(.*tab=)[^&]*(.*)$/, '$1' + tab_name + '$2');
        } else {
            if (/\?/.test(newUrl)) {
                newUrl += '&';
            } else {
                newUrl += '?';
            }
            newUrl += 'tab=' + tab_name;
        }
        loadTabContent(newUrl, tab_name);
    }
}

var is_unloaded = false;

$(document).ready(function () {
    $('div.tabs').find('a.tab').click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        var href = $(this).attr('href');
        if (/#(.+)$/.test(href)) {
            var tab_name = href.replace(/^(.*)#(.*)$/, '$2');
            if (window.location.hash === '#' + tab_name) {
                onUrlHashChange(window.location.toString());
            } else {
                window.location.hash = tab_name;
            }
        } else {
            window.location = href;
        }
    });

    var url = window.location.toString();

    if (/#(.+)$/.test(url)) {
        var tab = getUrlParam('tab');
        if (!tab || (('#' + tab) !== window.location.hash)) {
            onUrlHashChange(url);
        }
    }

    window.onhashchange = function (e) {
        onUrlHashChange(e.newURL);
    };

    $('#openModalBtn').popover().click(function () {
        $(this).popover('hide');
    });

    $('.bs-popover').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).popover();
            $(this).data('event_init', 1);
        }
    });

    $('body').on('fixeTabsReloaded', function (e) {
        if ($('.fixe_tab_caption').length) {
            $('#openModalBtn').css('bottom', '45px');
        } else {
            $('#openModalBtn').css('bottom', '0');
        }
    });

    if ($('.fixe_tab_caption').length) {
        $('#openModalBtn').css('bottom', '45px');
    } else {
        $('#openModalBtn').css('bottom', '0');
    }

    $('body').on('objectChange', function (e) {
        reloadObjectHeader({
            module: e.module,
            object_name: e.object_name,
            id_object: e.id_object
        });
    });

    $('body').on('objectDelete', function (e) {
        reloadObjectHeader({
            module: e.module,
            object_name: e.object_name,
            id_object: e.id_object
        });
    });
});