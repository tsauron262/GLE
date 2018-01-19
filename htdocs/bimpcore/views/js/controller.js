function loadTabContent(url, tab_name) {
    var $container = $('#controllerTabContentContainer');
    var $tabs = $('.tabs');
    $tabs.find('.tabactive').each(function () {
        $(this).removeClass('tabactive').addClass('tabunactive');
    });

    $tabs.find('#' + tab_name).removeClass('tabunactive').addClass('tabactive');

    if ($container.length) {
        $container.fadeOut(250, function () {
            var $content = $(this).find('#controllerTabContent');
            $content.html('').hide();
            $(this).find('.content-loading').show();
            $(this).show();

            var data = {
                ajaxRequestUrl: url
            };

            bimp_json_ajax('loadControllerTab', data, $content, function (result) {
                if (typeof (result.html) !== 'undefined') {
                    $container.find('.content-loading').hide();
                    $content.html(result.html).fadeIn(250);
                    $('body').trigger($.Event('controllerTabLoaded', {
                        $container: $container,
                        tab_name: tab_name
                    }));
                }
            }, function (result) {
                $(this).find('.content-loading').hide();
            }, false);
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
});