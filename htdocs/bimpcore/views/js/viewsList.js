function reloadObjectViewsList(list_views_id) {
    var $list = $('#' + list_views_id);

    if (!$list.length) {
        console.error('reloadObjectViewsList(): identifiant de la liste invalide (' + list_views_id + ').');
        return;
    }

    var data = getListData($list, {
        'list_data': 0,
        'search_filters': 0,
        'selected_rows': 0,
        'new_values': 0
    });

    data['module'] = $list.data('module');
    data['object_name'] = $list.data('object_name');
    data['list_views_id'] = list_views_id;
    data['views_list_name'] = $list.data('name');

    // Permet d'éviter un écrasement du HTML si un nouveau refresh est demandé avant le retour ajax.
    // Uile notamment lorsque l'utilisateur sélectionne plusieurs filtres d'affilé
    var refresh_idx = parseInt($list.data('refresh_idx'));
    if (isNaN(refresh_idx)) {
        refresh_idx = 0;
    }
    refresh_idx++;
    $list.data('refresh_idx', refresh_idx);

    BimpAjax('loadObjectViewsList', data, null, {
        $list: $list,
        display_success: false,
        success: function (result, bimpAjax) {
            var cur_idx = parseInt(bimpAjax.$list.data('refresh_idx'));

            if (!isNaN(cur_idx) && cur_idx > bimpAjax.refresh_idx) {
                return;
            }

            if (typeof (result.html) !== 'undefined') {
                bimpAjax.$list.children('.object_list_view_content').html(result.html);
                onListViewsRefresh(bimpAjax.$list);
            }
        }
    });
}


// Actions: 

function loadViewsListPage($list, page) {
    if (!$list.length) {
        console.error('loadViewsListPage: Liste absente');
        bimp_msg('Une erreur est survenue. Impossible de charger cette page', 'danger', null, true);
        return;
    }

    $list.find('input[name=param_p]').val(page);
    reloadObjectViewsList($list.attr('id'));
}

// Gestion des événements:

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

        setViewsListPaginationEvents($listViews);

        $listViews.data('object_change_event_init', 1);
        $('body').trigger($.Event('viewsListLoaded', {
            $listViews: $listViews
        }));
    }
}

function onListViewsRefresh($listViews) {
    setCommonEvents($listViews);
    setViewsListPaginationEvents($listViews);
    $('body').trigger($.Event('viewsListRefresh', {
        $listViews: $listViews
    }));
}

function setViewsListPaginationEvents($list) {
    if (!$list.length) {
        console.log('setViewsListPaginationEvents: $list absent');
        return;
    }

    $list.find('.listPagination').each(function () {
        var list_id = $(this).data('views_list_id');
        if (typeof (list_id) !== 'undefined' && list_id === $list.attr('id')) {
            if (!parseInt($(this).data('views_list_pagination_events_init'))) {
                $(this).data('views_list_pagination_events_init', 1);

                var $pagination = $(this);
                if (!parseInt($(this).data('event_init'))) {
                    $(this).data('event_init', 1);
                }

                var p = $(this).find('.pageBtn.active').data('p');
                if (p) {
                    p = parseInt(p);
                } else {
                    p = 1;
                }
                var $prev = $((this)).find('.prevButton');
                if ($prev.length) {
                    if (!$prev.hasClass('disabled')) {
                        $prev.click(function () {
                            if ($(this).hasClass('processing') || $(this).hasClass('disabled')) {
                                return;
                            }

                            if (p <= 1) {
                                return;
                            }

                            $(this).addClass('selected');
                            setPaginationLoading($pagination);
                            loadViewsListPage($list, p - 1);
                        });
                    }
                }
                var $next = $((this)).find('.nextButton');
                if ($next.length) {
                    if (!$next.hasClass('disabled')) {
                        $next.click(function () {
                            if ($(this).hasClass('processing') || $(this).hasClass('disabled')) {
                                return;
                            }

                            $(this).addClass('selected');
                            setPaginationLoading($pagination);
                            loadViewsListPage($list, p + 1);
                        });
                    }
                }
                $(this).find('.pageBtn').each(function () {
                    if (!$(this).hasClass('active')) {
                        $(this).click(function () {
                            if ($(this).hasClass('processing') || $(this).hasClass('active')) {
                                return;
                            }

                            $(this).addClass('selected');
                            setPaginationLoading($pagination);
                            loadViewsListPage($list, parseInt($(this).data('p')));
                        });
                    }
                });
            }
        }
    });
}

$(document).ready(function () {
    $('.object_list_view').each(function () {
        onListViewsLoaded($(this));
    });

    $('body').on('contentLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_list_view').each(function () {
                onListViewsLoaded($(this));
            });
        }
    });
});