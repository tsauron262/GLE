// Gestion des événements:
function reloadObjectViewsList(list_views_id) {
    var $listViews = $('#' + list_views_id);

    if (!$listViews.length) {
        return;
    }

    var data = getComponentParams($listViews);

    data['list_views_id'] = list_views_id;
    data['module_name'] = $listViews.data('module');
    data['object_name'] = $listViews.data('object_name');
    data['views_list_name'] = $listViews.data('name');

    BimpAjax('loadObjectViewsList', data, null, {
        $listViews: $listViews,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined') {
                bimpAjax.$listViews.children('.object_list_view_content').html(result.html);
                var $container = bimpAjax.$listViews.findParentByClass('list_views_container');

                if (typeof (result.pagination) !== 'undefined' && result.pagination) {
                    $container.children('.paginationContainer').html(result.pagination).show();
                } else {
                    $container.children('.paginationContainer').hide().html('');
                }

                onListViewsRefresh($listViews);
            }
        }
    });
}

function onListViewsLoaded($listViews) {
    if (!$listViews.length) {
        return;
    }

    if (!parseInt($listViews.data('loaded_event_processed'))) {
        $listViews.data('loaded_event_processed', 1);

        setCommonEvents($('#' + $listViews.attr('id') + '_container'));
    }

    if (!$listViews.data('object_change_event_init')) {
        var module = $listViews.data('module');
        var object_name = $listViews.data('object_name');
        var objects = $listViews.data('objects_change_reload');
        if (objects) {
            objects = objects.split(',');
        }

        $('body').on('objectChange', function (e) {
            if ((e.module === module) && (e.object_name === object_name)) {
                reloadObjectViewsList($listViews.attr('id'));
            } else if (objects && objects.length) {
                for (var i in objects) {
                    if (e.object_name === objects[i]) {
                        reloadObjectViewsList($listViews.attr('id'));
                    }
                }
            }
        });

        $listViews.data('object_change_event_init', 1);
        $('body').trigger($.Event('viewsListLoaded', {
            $listViews: $listViews
        }));
    }
}

function onListViewsRefresh($listViews) {
    setCommonEvents($listViews);
    $('body').trigger($.Event('viewsListRefresh', {
        $listViews: $listViews
    }));
}

$(document).ready(function () {
    $('.object_list_view').each(function () {
        onListViewsLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_list_view').each(function () {
                onListViewsLoaded($(this));
            });
        }
    });
});