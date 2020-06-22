function reloadObjectStatsList(list_id, callback) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var $resultContainer = $('#' + list_id + '_ajax_content');

    // Données de la liste:
    var data = getListData($list);

// Options Group By: 
    var $gbContainer = $list.find('.stats_list_group_by_options');
    if ($gbContainer.length) {
        var groupBy = [];
        $gbContainer.find('.inputMultipleValues').find('tr.itemRow').each(function () {
            var opt_value = $(this).find('input.item_value').val();
            var opt_section = parseInt($(this).find('input[name="group_by_' + opt_value + '_option_section"]').val());
            groupBy.push({
                value: opt_value,
                section: opt_section
            });
        });
        if (groupBy.length) {
            data['group_by'] = groupBy;
        }
    }

    // Envoi requête:
    var error_msg = 'Une erreur est sruvenue. La liste n\'a pas pu être rechargée';

    BimpAjax('loadObjectStatsList', data, $resultContainer, {
        $list: $list,
        $resultContainer: $resultContainer,
        display_success: false,
        display_processing: true,
        processing_msg: 'Chargement',
        error_msg: error_msg,
        append_html: true,
        success: function (result, bimpAjax) {
            if (result.html) {
                hidePopovers($list);

                if (result.filters_panel_html) {
                    bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                        $(this).html(result.filters_panel_html);
                    });
                } else {
                    bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                        $(this).hide();
                    });
                }

                onStatsListRefreshed(bimpAjax.$list);

                if (typeof (callback) === 'function') {
                    callback(true);
                }
            } else {
                if (typeof (callback) === 'function') {
                    callback(false);
                }
            }
        },
        error: function () {
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

function onStatsListLoaded($list) {
    if (!$list.length) {
        return;
    }

    if (!parseInt($list.data('loaded_event_processed'))) {
        $list.data('loaded_event_processed', 1);

        $list.find('input[name="param_n"]').change(function () {
            reloadObjectStatsList($list.attr('id'));
        });

        setCommonEvents($('#' + $list.attr('id') + '_container'));
        setInputsEvents($list);

        var $filters = $list.find('.object_filters_panel');
        if ($filters.length) {
            $filters.each(function () {
                onListFiltersPanelLoaded($(this));
            });
        }

        if (!$list.data('object_change_event_init')) {
            var module = $list.data('module');
            var object_name = $list.data('object_name');

            var objects = $list.data('objects_change_reload');
            if (objects) {
                objects = objects.split(',');
            } else {
                objects = [];
            }

            if (!$('body').data($list.attr('id') + '_object_events_init')) {
                $('body').on('objectChange', function (e) {
//                    bimp_msg($list.attr('id') + ' => ' + e.module + ', ' + module + ', ' + e.object_name + ', ' + object_name);
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectStatsList($list.attr('id'));
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectStatsList($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('objectDelete', function (e) {
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectStatsList($list.attr('id'));
                    } else if (objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectStatsList($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('listFiltersChange', function (e) {
                    if (e.$filters.data('list_identifier') === $list.attr('id')) {
                        reloadObjectStatsList($list.attr('id'));
                    }
                });
                $('body').data($list.attr('id') + '_object_events_init', 1);
            }

            $list.data('object_change_event_init', 1);
        }

        $('body').trigger($.Event('listCustomLoaded', {
            $list: $list
        }));
    }
}

function onStatsListRefreshed($list) {
    setCommonEvents($list);
    setInputsEvents($list);

    var $filters = $list.find('.object_filters_panel');
    if ($filters.length) {
        $filters.each(function () {
            onListFiltersPanelLoaded($(this));
        });
    }

    $list.trigger('statsListRefresh');
}

$(document).ready(function () {
    $('body').on('bimp_ready', function () {
        $('.object_stats_list').each(function () {
            onStatsListLoaded($(this));
        });
    });

    $('body').on('contentLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_stats_list').each(function () {
                onStatsListLoaded($(this));
            });
        }
    });
});
