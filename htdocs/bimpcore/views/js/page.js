var object_header_scroll_trigger = 30;

function reloadPage(page_id) {

}

// Gestion en-tête des pages objets: 

function reloadObjectHeader(object_data) {
    if (!object_data.module || !object_data.object_name || !object_data.id_object) {
        return;
    }

    var $container = $('#' + object_data.object_name + '_' + object_data.id_object + '_header');
    
    if ($.isOk($container)) {
        BimpAjax('reloadObjectHeader', object_data, $container, {
            display_success: false,
            display_errors: false,
            display_warnings: false,
            append_html: true,
            remove_current_content: false
        });
    }
}

function setObjectHeaderPosition($header) {
    if (!$.isOk($header) || !$header.hasClass('object_page_header')) {
        return '';
    }

    if ($header.hasClass('locked')) {
        if ($(window).scrollTop() > object_header_scroll_trigger) {
            fixeObjectHeader($header, true);
        } else {
            unfixeObjectHeader($header);
        }
    } else {
        if (ctrl_down && $(window).scrollTop() > $header.height()) {
            fixeObjectHeader($header, false);
        } else {
            unfixeObjectHeader($header);
        }
    }
}

function fixeObjectHeader($header, fixe_margin) {
    if (typeof (fixe_margin) === 'undefined') {
        fixe_margin = true;
    }

    var height = 0;
    if (!fixe_margin) {
        height = $header.height();
    }

    $header.addClass('fixed');

    if (fixe_margin) {
        height = $header.height() + 30;
    }

    $header.findParentByClass('object_page').find('.object_page_content').css('margin-top', height + 'px');
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
                saveObjectField('bimpcore', 'Bimp_User', id_user, 'object_header_locked', 0, null, null, false);
                setObjectHeaderPosition($headerContainer);
            });
            $header.find('.lock_object_header_button').click(function () {
                var $headerContainer = $(this).findParentByClass('object_page_header');
                $headerContainer.addClass('locked');
                $(this).hide();
                $(this).findParentByClass('header_tools').find('.unlock_object_header_button').show();
                saveObjectField('bimpcore', 'Bimp_User', id_user, 'object_header_locked', 1, null, null, false);
                setObjectHeaderPosition($headerContainer);
            });
            $header.data('object_header_events_init', 1);
        }
        setObjectHeaderPosition($header.findParentByClass('object_page_header'));
    }
}

// Gestion NavTabs: 

function navTabNext(nav_tabs_id) {
    var $nav = $('#navtabs_' + nav_tabs_id);

    if ($.isOk($nav)) {
        var activeNext = false;
        var done = false;
        $nav.children('li').each(function () {
            if (!done) {
                if (activeNext) {
                    $(this).find('a').tab('show');
                    done = true;
                } else {
                    if ($(this).hasClass('active')) {
                        activeNext = true;
                    }
                }
            }
        });
        if (!done) {
            $nav.children('li').first().find('a').tab('show');
        }
    }
}

function navTabPrev(nav_tab_id) {
    var $nav = $('#navtabs_' + nav_tab_id);

    if ($.isOk($nav)) {
        var $prev = null;
        var done = false;
        $nav.children('li').each(function () {
            if (!done) {
                if ($(this).hasClass('active')) {
                    if ($.isOk($prev)) {
                        $prev.find('a').tab('show');
                    } else {
                        $nav.children('li').last().find('a').tab('show');
                    }
                    done = true;
                } else {
                    $prev = $(this);
                }
            }
        });
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