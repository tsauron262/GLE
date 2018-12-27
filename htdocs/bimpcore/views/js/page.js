function reloadPage(page_id) {

}

function reloadObjectHeader(object_data) {

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
    }
}

function onPageRefreshed($page) {

}

function setObjectHeaderEvents($header) {

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