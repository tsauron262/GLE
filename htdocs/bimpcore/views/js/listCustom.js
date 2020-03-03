function reloadObjectListCustom(list_id, callback) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var $resultContainer = $('#' + list_id + '_ajax_content');
    
    var data = getListData($list);

    // Envoi requête:
    var error_msg = 'Une erreur est sruvenue. La liste n\'a pas pu être rechargée';

    BimpAjax('loadObjectListCustom', data, $resultContainer, {
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

                onListCustomRefeshed(bimpAjax.$list);

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

function onListCustomLoaded($list) {
    if (!$list.length) {
        return;
    }

    if (!parseInt($list.data('loaded_event_processed'))) {
        $list.data('loaded_event_processed', 1);

        $list.find('input[name="param_n"]').change(function () {
            reloadObjectListCustom($list.attr('id'));
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
                        reloadObjectListCustom($list.attr('id'));
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectListCustom($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('objectDelete', function (e) {
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectListCustom($list.attr('id'));
                    } else if (objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectListCustom($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('listFiltersChange', function (e) {
                    if (e.$filters.data('list_identifier') === $list.attr('id')) {
                        reloadObjectListCustom($list.attr('id'));
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

function onListCustomRefeshed($list) {
    setCommonEvents($list);
    setInputsEvents($list);

    var $filters = $list.find('.object_filters_panel');
    if ($filters.length) {
        $filters.each(function () {
            onListFiltersPanelLoaded($(this));
        });
    }

    $list.trigger('listCustomRefresh');
}

$(document).ready(function () {
    $('body').on('bimp_ready', function () {
        $('.object_list_custom').each(function () {
            onListCustomLoaded($(this));
        });
    });

    $('body').on('contentLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_list_custom').each(function () {
                onListCustomLoaded($(this));
            });
        }
    });
});