// Gestion des événements:
function reloadObjectViewsList(views_list_id) {
    var $viewsList = $('#' + views_list_id);

    if (!$viewsList.length) {
        return;
    }

    var data = {
        module_name: $viewsList.data('module_name'),
        object_name: $viewsList.data('object_name'),
        views_list_name: $viewsList.data('views_list_name')
    };

    bimp_json_ajax('loadObjectViewsList', data, null, function (result) {
        if (typeof (result.html) !== 'undefined') {
            $viewsList.html(result.html);
            var $container = $viewsList.parent('.viewsListContainer');

            if (typeof (result.pagination) !== 'undefined' && result.pagination) {
                $container.children('.paginationContainer').html(result.pagination).show();
            } else {
                $container.children('.paginationContainer').hide().html('');
            }

            onViewsListRefresh($viewsList);
        }
    });
}

function onViewsListLoaded($viewsList) {
    if (!$viewsList.length) {
        return;
    }

    if (!parseInt($viewsList.data('loaded_event_processed'))) {
        $viewsList.data('loaded_event_processed', 1);

        setCommonEvents($('#' + $viewsList.attr('id') + '_container'));
    }

    if (!$viewsList.data('object_change_event_init')) {
        var module = $viewsList.data('module_name');
        var object_name = $viewsList.data('object_name');
        var objects = $viewsList.data('objects_change_reload');
        if (objects) {
            objects = objects.split(',');
        }

        $('body').on('objectChange', function (e) {
            if ((e.module === module) && (e.object_name === object_name)) {
                reloadObjectViewsList($viewsList.attr('id'));
            } else if (objects && objects.length) {
                for (var i in objects) {
                    if (e.object_name === objects[i]) {
                        reloadObjectViewsList($viewsList.attr('id'));
                    }
                }
            }
        });

        $viewsList.data('object_change_event_init', 1);
        $('body').trigger($.Event('viewsListLoaded', {
            $viewsList: $viewsList
        }));
    }
}

function onViewsListRefresh($viewsList) {
    setCommonEvents($viewsList);
    $('body').trigger($.Event('viewsListRefresh', {
        $viewsList: $viewsList
    }));
}

$(document).ready(function () {
    $('.objectViewslist').each(function () {
        onViewsListLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.objectViewslist').each(function () {
                onViewsListLoaded($(this));
            });
        }
    });
});