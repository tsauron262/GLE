function reloadPage(page_id) {

}

function reloadObjectHeader(object_data) {

}

function setObjectHeaderPosition($header) {
    if (!$.isOk($header) || !$header.hasClass('object_page_header')) {
        return '';
    }

    if ($header.hasClass('locked')) {
        if ($(window).scrollTop() > 30) {
            fixeObjectHeader($header);
        } else {
            unfixeObjectHeader($header);
        }
    } else {
        unfixeObjectHeader($header);
    }
}

function fixeObjectHeader($header) {
    $header.addClass('fixed');
    var height = $header.height() + 30;
    $header.findParentByClass('object_page').find('.object_page_content').css('margin-top', height+'px');
}

function unfixeObjectHeader($header) {
    $header.removeClass('fixed');
    $header.findParentByClass('object_page').find('.object_page_content').css('margin-top', 0);
}

function onObjectPageLoaded($page) {
    if (!$page.length) {
        return;
    }

    if (!parseInt($page.data('loaded_event_processed'))) {
        var $header = $page.find('.object_page_header .object_header');

        $('body').on('objectChange', function (e) {
            if ((e.module === $page.data('module')) && (e.object_name === $page.data('object_name'))) {
                if ($.isOk($header)) {
                    reloadObjectHeader({
                        module: e.module,
                        object_name: e.object_name,
                        id_object: $page.data('id_object')
                    });
                }
            }
        });

        $('body').on('objectDelete', function (e) {
            // todo: recharger la page ou charger la liste si var $page.data('list_page_url'). 
        });
        $page.data('loaded_event_processed', 1);
        onPageRefreshed($page);
    }
}

function onPageRefreshed($page) {
    var $header = $page.find('.object_page_header .object_header');
    if ($.isOk($header)) {
        setObjectHeaderEvents($header);
    }
}

function setObjectHeaderEvents($header) {
    if ($.isOk($header) && $header.hasClass('object_header')) {
        if (!parseInt($header.data('object_header_events_init'))) {
            $header.find('.unlock_object_header_button').click(function () {
                var $headerContainer = $(this).findParentByClass('object_page_header');
                $headerContainer.removeClass('locked');
                $(this).hide();
                $(this).findParentByClass('header_tools').find('.lock_object_header_button').show();
                setObjectHeaderPosition($headerContainer);
            });
            $header.find('.lock_object_header_button').click(function () {
                var $headerContainer = $(this).findParentByClass('object_page_header');
                $headerContainer.addClass('locked');
                $(this).hide();
                $(this).findParentByClass('header_tools').find('.unlock_object_header_button').show();
                setObjectHeaderPosition($headerContainer);
            });
            $header.data('object_header_events_init', 1);
        }
        setObjectHeaderPosition($header.findParentByClass('object_page_header'));
    }
}

$(document).ready(function () {
    $('body').on('bimp_ready', function (e) {
        $('.object_page').each(function () {
            onObjectPageLoaded($(this));
        });
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_page').each(function () {
                onObjectPageLoaded($(this));
            });
        }
    });
});