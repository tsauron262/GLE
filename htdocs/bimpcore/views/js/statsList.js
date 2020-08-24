// Traitements Ajax: 

function getStatsListData($list, sub_list_id, params) {
    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    if (typeof (params) === 'undefined') {
        params = {};
    }

    params['selected_rows'] = 0;
    params['new_values'] = 0;

    if (sub_list_id) {
        params['sort'] = 0;
        params['pagination'] = 0;
        params['search_filters'] = 0;
    }

    // Données de la liste:
    var data = getListData($list);

    // Options Group By: 
    var $gbContainer = $list.find('.stats_list_group_by_options');
    if ($gbContainer.length) {
        var groupBy = [];
        $gbContainer.find('.inputMultipleValues').find('tr.itemRow').each(function () {
            var opt_value = $(this).find('input.item_value').val();
            var opt_section = $(this).find('input[name="group_by_' + opt_value + '_option_section"]').val();

            groupBy.push({
                value: opt_value,
                section: opt_section
            });
        });
        if (groupBy.length) {
            data['group_by'] = groupBy;
        }
    }

    if (sub_list_id) {
        var $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');

        if ($container.length) {
            var $params = $container.children('.subStatsListParams');

            if ($params.length) {
                // Options de trie et de pagination:
                data['param_sort_field'] = $params.find('input[name=param_sort_field]').val();
                data['param_sort_way'] = $params.find('input[name=param_sort_way]').val();
                data['param_sort_option'] = $params.find('input[name=param_sort_option]').val();
                data['param_n'] = $params.find('input[name=param_n]').val();
                data['param_p'] = $params.find('input[name=param_p]').val();

                // Champs de recherche de la sous-liste: 
                var $search_row = $container.find('#' + sub_list_id + '_searchRow');
                var search_data = getListSearchFilters($search_row);

                data['search_fields'] = search_data['search_fields'];
                data['search_children'] = search_data['search_children'];
            } else {
                bimp_msg('Une erreur est survenue (Paramètres non trouvés). Impossible de recharger la liste', 'danger', null, true);
                return false;
            }
        } else {
            bimp_msg('Une erreur est survenue (Conteneur non trouvé). Impossible de recharger la liste', 'danger', null, true);
            return false;
        }
    }

    return data;
}

function reloadObjectStatsList(list_id, callback, id_config) {
    if (typeof (id_config) === 'undefined') {
        id_config = 0;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        console.error('reloadObjectStatsList: Liste absente pour "' + list_id + '"');
        return;
    }

    $list.find('input[name="param_p"]').val(1);

    var $resultContainer = $('#' + list_id + '_ajax_content');

    resetStatsListSearchInputs(list_id, '', false);

    var data = getStatsListData($list);

    if (id_config) {
        data['param_id_config'] = id_config;
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
            var list_ok = false;
            if (result.html) {
                list_ok = true;
                hidePopovers(bimpAjax.$list);
            }

            // Panneau filtres: 
            if (result.filters_panel_html) {
                bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                    $(this).html(result.filters_panel_html);
                });
            } else {
                bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                    $(this).hide();
                });
            }

            // Filtres actifs: 
            if (result.active_filters_html) {
                bimpAjax.$list.find('.list_active_filters').each(function () {
                    $(this).html(result.active_filters_html).show();
                });
            } else {
                bimpAjax.$list.find('.list_active_filters').each(function () {
                    $(this).hide().html('');
                });
            }

            onStatsListRefreshed(bimpAjax.$list);

            if (typeof (callback) === 'function') {
                callback(list_ok);
            }
        },
        error: function () {
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

function reloadObjectStatsListRows(list_id, callback) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        console.error('reloadObjectStatsListRows: Liste absente pour "' + list_id + '"');
        return;
    }

    $list.find('.statsListTableContainer[data-sub_list_id="' + list_id + '"]').find('.statsListTableContainer').each(function () {
        $(this).findParentByClass('subStatsListContainer').html('').parent('tr').hide();
    });

    $list.find('.headerTools').find('.loadingIcon').css('opacity', 1);

    var $resultContainer = $('#' + list_id + '_result');
    var data = getStatsListData($list);

    // Envoi requête:
    var error_msg = 'Une erreur est survenue. La liste n\'a pas pu être rechargée';

    // Permet d'éviter un écrasement du HTML si un nouveau refresh est demandé avant le retour ajax.
    // Uile notamment lorsque l'utilisateur sélectionne plusieurs filtres d'affilé
    var refresh_idx = parseInt($list.data('refresh_idx'));
    if (isNaN(refresh_idx)) {
        refresh_idx = 0;
    }
    refresh_idx++;
    $list.data('refresh_idx', refresh_idx);

    BimpAjax('loadObjectStatsListRows', data, $resultContainer, {
        $list: $list,
        refresh_idx,
        $resultContainer: $resultContainer,
        display_success: false,
        display_processing: false,
        error_msg: error_msg,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            var cur_idx = parseInt(bimpAjax.$list.data('refresh_idx'));

            if (!isNaN(cur_idx) && cur_idx > bimpAjax.refresh_idx) {
                return;
            }

            hidePopovers(bimpAjax.$list);

            var rows_ok = false;
            // Rows: 
            if (result.rows_html) {
                rows_ok = true;
                bimpAjax.$list.find('tbody.listRows').html(result.rows_html);
            }

            // Panneau filtres:  
            if (result.filters_panel_html) {
                bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                    $(this).html(result.filters_panel_html);
                });
            } else {
                bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
                    $(this).hide();
                });
            }

            // Pagination: 
            if (result.pagination_html) {
                bimpAjax.$list.find('.listPagination').each(function () {
                    $(this).data('event_init', 0);
                    $(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
                });
                setStatslistPaginationEvents(bimpAjax.$list);
            } else {
                bimpAjax.$list.find('.listPagination').each(function () {
                    $(this).html('').parent('td').parent('tr.paginationContainer').hide();
                });
            }

            // Filtres actifs: 
            if (result.active_filters_html) {
                bimpAjax.$list.find('.list_active_filters').each(function () {
                    $(this).html(result.active_filters_html).show();
                });
            } else {
                bimpAjax.$list.find('.list_active_filters').each(function () {
                    $(this).hide().html('');
                });
            }

            onStatsListRowsRefreshed(bimpAjax.$list);

            if (typeof (callback) === 'function') {
                callback(rows_ok);
            }
        },
        error: function () {
            $list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

function loadObjectSubStatsList($button, parent_list_id, sub_list_title, filters, joins, group_by_idx) {
    var error_msg = 'Une erreur est survenue. La liste n\'a pas pu être chargée';

    var $parent_container = $('.statsListTableContainer[data-sub_list_id="' + parent_list_id + '"]');
    if (!$parent_container.length) {
        console.error('loadObjectSubStatsList: $parent_container absent pour "' + parent_list_id + '"');
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var $list = $parent_container.findParentByClass('object_stats_list');

    if (!$.isOk($list)) {
        console.error('loadObjectSubStatsList: $list absente pour "' + parent_list_id + '"');
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var $row = $button.findParentByClass('statListItemRow');

    if (!$.isOk($row)) {
        console.error('loadObjectSubStatsList: $row absente pour "' + parent_list_id + '"');
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var $nextRow = $row.next('tr.statList_subListRow');

    if (!$nextRow.length) {
        console.error('loadObjectSubStatsList: $nextRow absente pour "' + parent_list_id + '"');
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var data = getStatsListData($list);

    data['stats_list_id'] = $list.attr('id');
    data['search_children'] = {};
    data['search_fields'] = {};
    data['param_p'] = 1;
    data['sub_list_filters'] = filters;
    data['sub_list_joins'] = joins;
    data['group_by_index'] = group_by_idx;
    data['sub_list_title'] = sub_list_title;

    // Envoi requête:
    var error_msg = 'Une erreur est survenue. La liste n\'a pas pu être chargée';

    $button.hide();
    $nextRow.show();

    BimpAjax('loadObjectSubStatsList', data, $nextRow.children('td.subStatsListContainer'), {
        $list: $list,
        append_html: true,
        display_success: false,
        display_processing: true,
        processing_padding: 20,
        error_msg: error_msg,
        display_errors_in_popup_only: false,
        display_warnings_in_popup_only: false,
        success: function (result, bimpAjax) {
            hidePopovers(bimpAjax.$list);

            if (typeof (result.list_id) !== 'undefined' && result.list_id) {
                onStatsListRefreshed(bimpAjax.$list, result.list_id);
            }
        },
        error: function () {
        }
    });
}

function reloadObjectSubStatsListRows(list_id, sub_list_id, callback) {
    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    if (!sub_list_id) {
        console.error('reloadObjectSubStatsListRows: ID sous-liste absent');
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        console.error('reloadObjectSubStatsListRows: Liste absente pour "' + list_id + '"');
        return;
    }

    var $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');

    if (!$container.length) {
        console.error('reloadObjectSubStatsListRows: $container absent pour "' + sub_list_id + '"');
        return;
    }

    // Suppr du html des sous-listes: 
    $container.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]').find('.statsListTableContainer').each(function () {
        $(this).findParentByClass('subStatsListContainer').html('').parent('tr').hide();
    });

    $container.find('.headerTools').find('.loadingIcon').css('opacity', 1);

    var data = getStatsListData($list, sub_list_id);

    if (!data) {
        return;
    }

    data['stats_list_id'] = list_id;
    data['rows_only'] = 1;
    data['sub_list_filters'] = $container.data('sub_list_filters');
    data['sub_list_joins'] = $container.data('sub_list_joins');
    data['group_by_index'] = $container.data('group_by_index');

    // Envoi requête:
    var error_msg = 'Une erreur est survenue. La liste n\'a pas pu être rechargée';

    // Permet d'éviter un écrasement du HTML si un nouveau refresh est demandé avant le retour ajax.
    // Uile notamment lorsque l'utilisateur sélectionne plusieurs filtres d'affilé
    var refresh_idx = parseInt($list.data('refresh_idx'));
    if (isNaN(refresh_idx)) {
        refresh_idx = 0;
    }
    refresh_idx++;
    $container.data('refresh_idx', refresh_idx);

    BimpAjax('loadObjectSubStatsList', data, null, {
        $list: $list,
        $container: $container,
        sub_list_id: sub_list_id,
        refresh_idx,
        display_success: false,
        display_processing: false,
        error_msg: error_msg,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            var cur_idx = parseInt(bimpAjax.$container.data('refresh_idx'));

            if (!isNaN(cur_idx) && cur_idx > bimpAjax.refresh_idx) {
                return;
            }

            hidePopovers(bimpAjax.$container);

            var rows_ok = false;
            // Rows: 
            if (result.html) {
                rows_ok = true;
                bimpAjax.$container.find('tbody.listRows').html(result.html);
            }

            // Pagination: 
            if (result.pagination_html) {
                bimpAjax.$container.find('.listPagination').each(function () {
                    $(this).data('event_init', 0);
                    $(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
                });
                setStatslistPaginationEvents(bimpAjax.$list, bimpAjax.sub_list_id);
            } else {
                bimpAjax.$container.find('.listPagination').each(function () {
                    $(this).html('').parent('td').parent('tr.paginationContainer').hide();
                });
            }

            onStatsListRowsRefreshed(bimpAjax.$list, bimpAjax.sub_list_id);

            if (typeof (callback) === 'function') {
                callback(rows_ok);
            }
        },
        error: function () {
            $list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

// Actions: 

function resetStatsListSearchInputs(list_id, sub_list_id, reload_list) {
    if (typeof (reload_list) === 'undefined') {
        reload_list = true;
    }

    var $row = null;

    if (sub_list_id) {
        $row = $('#' + sub_list_id + '_searchRow');
    } else {
        $row = $('#' + list_id + '_searchRow');
    }

    if ($row.length) {
        $row.find('.searchInputContainer').each(function () {
            var field_name = $(this).data('field_name');
            var search_type = $(this).data('search_type');
            if (field_name) {
                if (search_type === 'values_range') {
                    $(this).find('[name=' + field_name + '_min]').val('');
                    $(this).find('[name=' + field_name + '_max]').val('');
                } else {
                    $(this).find('[name=' + field_name + ']').val('');
                }
            }
        });
        $row.find('.ui-autocomplete-input').val('');
        $row.find('.search_list_input').val('');
        $row.find('.select2-selection__rendered').html('');
        $row.find('.bs_datetimepicker').each(function () {
            $(this).data('DateTimePicker').clear();
            $(this).parent().find('.datepicker_value').val('');
        });
        $row.find('.search_input_selected_label').html('').hide();
        $row.find('.search_object_input').find('input').val('');
    }

    if (reload_list) {
        resetStatsListPage(list_id, sub_list_id);

        if (sub_list_id) {
            reloadObjectSubStatsListRows(list_id, sub_list_id);
        } else {
            reloadObjectStatsListRows(list_id);
        }
    }
}

function sortStatsList(list_id, col_name, sub_list_id) {
    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        console.error('sortStatsList: Liste absente pour "' + list_id + '"');
        return;
    }

    var $container = null;
    var $params = null;

    if (sub_list_id) {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
        $params = $container.children('.subStatsListParams');
        if (!$container) {
            console.error('sortStatsList: $container absent pour "' + sub_list_id + '"');
            return;
        }

        if (!$params) {
            console.error('sortStatsList: $params absent pour "' + sub_list_id + '"');
            return;
        }
    } else {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + list_id + '"]');
        $params = $list.find('#' + $list.data('identifier') + '_params');
        if (!$container) {
            console.error('sortStatsList: $container absent pour "' + list_id + '"');
            return;
        }

        if (!$params) {
            console.error('sortStatsList: $params absent pour "' + list_id + '"');
            return;
        }
    }



    var $row = null;

    if (sub_list_id) {
        $row = $list.find('.headerRow[data-sub_list_id="' + sub_list_id + '"]');
    } else {
        $row = $list.find('.headerRow[data-sub_list_id="' + list_id + '"]');
    }

    if ($row.length) {
        var $span = $row.find('#' + col_name + '_sortTitle');
        if ($span.length) {

            var prev_sort_field = $params.find('input[name=param_sort_field]').val();
            var prev_sort_way = $params.find('input[name=param_sort_way]').val();
            var prev_sort_option = $params.find('input[name=param_sort_option]').val();
            $params.find('input[name=param_sort_field]').val(col_name);
            if ($span.hasClass('sorted-asc')) {
                $params.find('input[name=param_sort_way]').val('desc');
            } else {
                $params.find('input[name=param_sort_way]').val('asc');
            }
            var sort_option = $span.data('sort_option');
            if (!sort_option) {
                sort_option = '';
            }
            $params.find('input[name=param_sort_option]').val(sort_option);

            var callback = function (success) {
                if (success) {
                    $row.find('.sortTitle').each(function () {
                        if ($(this).parent('th').data('col_name') !== col_name) {
                            $(this).removeClass('active').removeClass('sorted-desc').addClass('sorted-asc');
                        }
                    });
                    $span.addClass('active');
                    if ($span.hasClass('sorted-desc')) {
                        $span.removeClass('sorted-desc').addClass('sorted-asc');
                    } else {
                        $span.removeClass('sorted-asc').addClass('sorted-desc');
                    }
                } else {
                    $params.find('input[name=param_sort_field]').val(prev_sort_field);
                    $params.find('input[name=param_sort_way]').val(prev_sort_way);
                    $params.find('input[name=param_sort_option]').val(prev_sort_option);
                }
            };

            $params.find('input[name="param_p"]').val(1);
            if (sub_list_id) {
                reloadObjectSubStatsListRows(list_id, sub_list_id, callback);
            } else {
                reloadObjectStatsListRows(list_id, callback);
            }
        }
    }
}

function loadStatsListPage($list, page, sub_list_id) {
    if (!$list.length) {
        console.error('loadStatsListPage: Liste absente');
        return;
    }

    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    var $container = null;
    var $params = null;

    if (sub_list_id) {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
        $params = $container.children('.subStatsListParams');
    } else {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + $list.data('identifier') + '"]');
        $params = $list.find('#' + $list.data('identifier') + '_params');
    }

    if (!$container || !$params) {
        bimp_msg('Erreur technique (Paramètres absents)', 'danger', null, true);
        return;
    }

    $params.find('input[name=param_p]').val(page);

    if (sub_list_id) {
        reloadObjectSubStatsListRows($list.attr('id'), sub_list_id);
    } else {
        reloadObjectStatsListRows($list.attr('id'));
    }
}

function loadStatsListConfig($button, id_config) {
    var $list = $button.findParentByClass('object_stats_list');

    if (!$.isOk($list)) {
        bimp_msg('Erreur: liste non trouvée', 'danger', null, true);
        return;
    }

    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    reloadObjectStatsList($list.attr('id'), null, id_config);
}

function resetStatsListPage(list_id, sub_list_id) {
    var $params = null;
    if (sub_list_id) {
        var $container = $('#' + list_id).find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
        if ($container.length) {
            $params = $container.children('.subStatsListParams');
        }
    } else {
        $params = $('#' + list_id + '_params');
    }

    if ($.isOk($params)) {
        $params.find('input[name="param_p"]').val(1);
    }
}
// Evénements: 

function onStatsListLoaded($list) {
    // Premier chargement de la liste (structure complète avec filtres et group by):
    if (!$list.length) {
        console.log('onStatsListLoaded: $list absent');
        return;
    }

    if (!parseInt($list.data('loaded_event_processed'))) {
        $list.data('loaded_event_processed', 1);

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
                        reloadObjectStatsListRows($list.attr('id'));
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectStatsListRows($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('objectDelete', function (e) {
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectStatsListRows($list.attr('id'));
                    } else if (objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectStatsListRows($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('listFiltersChange', function (e) {
                    if (e.$filters.data('list_identifier') === $list.attr('id')) {
                        reloadObjectStatsListRows($list.attr('id'));
                    }
                });
                $('body').data($list.attr('id') + '_object_events_init', 1);
            }

            $list.data('object_change_event_init', 1);
        }

        $('body').trigger($.Event('statsListLoaded', {
            $list: $list
        }));
    }

    reloadObjectStatsList($list.attr('id'));
}

function onStatsListRefreshed($list, sub_list_id) {
    // Chargement complet de la liste (ou sous-liste) en-tête compris. 

    if (!$list.length) {
        console.log('onStatsListRefreshed: $list absent');
        return;
    }

    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    var $container = null;
    var $params = null;

    if (sub_list_id) {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
        $params = $container.children('.subStatsListParams');
    } else {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + $list.data('identifier') + '"]');
        $params = $list.find('#' + $list.data('identifier') + '_params');
    }

    if (!$container.length) {
        console.log('onStatsListRefreshed: $container absent');
        return;
    }

    if (!$params.length) {
        console.log('onStatsListRefreshed: $params absent');
        return;
    }

    $params.find('input[name="param_n"]').change(function () {
        if (sub_list_id) {
            reloadObjectSubStatsListRows($list.attr('id'), sub_list_id);
        } else {
            reloadObjectStatsListRows($list.attr('id'));
        }
    });

    setCommonEvents($container);
    setInputsEvents($container);

    var $tools = $container.find('.headerTools');

    if ($tools.length) {
        $tools.find('.openSearchRowButton').click(function () {
            var $searchRow = $container.find('.listSearchRow');
            if ($searchRow.length) {
                if ($(this).hasClass('action-open')) {
                    $searchRow.stop().fadeIn(150);
                    $(this).removeClass('action-open').addClass('action-close');
                } else {
                    $searchRow.stop().fadeOut(150);
                    $(this).removeClass('action-close').addClass('action-open');
                }
            }
        });
        $tools.find('input[name="select_n"]').change(function () {
            var n = parseInt($(this).val());
            $params.find('input[name="param_n"]').val(n).change();
        });
        $tools.find('.refreshListButton').click(function () {
            if (sub_list_id) {
                reloadObjectSubStatsListRows($list.attr('id'), sub_list_id);
            } else {
                reloadObjectStatsListRows($list.attr('id'));
            }
        });

        $params.find('input[name="param_n"]').change(function () {
            var val = parseInt($(this).val());
            var select_val = parseInt($tools.find('input[name="select_n"]').val());
            if (val !== select_val) {
                $tools.find('input[name="select_n"]').val(val).change();
            }
        });
    }

    $container.find('tr.listSearchRow').each(function () {
        $(this).find('.searchInputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                $(this).find('[name=' + field_name + ']').val('');
            }
        });
        $(this).find('.ui-autocomplete-input').val('');
        setStatsListSearchInputsEvents($container, $list.data('identifier'), sub_list_id);
    });

    $list.trigger('statsListRefresh');

    onStatsListRowsRefreshed($list, sub_list_id);
}

function onStatsListRowsRefreshed($list, sub_list_id) {
    // Chargement des lignes de la liste (ou sous-liste) seulemement (+ pagination / filtres / filtres actifs). 

    if (!$list.length) {
        console.log('onStatsListRowsRefreshed: $list absent');
        return;
    }

    var list_id = $list.attr('id');

    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    var $container = null;
    var $params = null;

    if (sub_list_id) {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
        $params = $container.children('.subStatsListParams');
    } else {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + $list.data('identifier') + '"]');
        $params = $list.find('#' + $list.data('identifier') + '_params');
    }

    if (!$container.length) {
        console.log('onStatsListRowsRefreshed: $container absent');
        return;
    }

    if (!$params.length) {
        console.log('onStatsListRowsRefreshed: $params absent');
        return;
    }

    var $tbody = $container.find('tbody.listRows');

    setStatslistPaginationEvents($list, sub_list_id);

    setCommonEvents($tbody);
    setInputsEvents($tbody);

    if (!sub_list_id) {
        setCommonEvents($list.find('.list_active_filters'));
        setInputsEvents($list.find('.list_active_filters'));
    }

    $tbody.find('a').each(function () {
//        $(this).attr('target', '_blank');
        var link_title = $(this).attr('title');
        if (link_title) {
            $(this).popover('destroy');
            $(this).removeClass('classfortooltip');
            $(this).removeAttr('title');
            $(this).popover({
                trigger: 'hover',
                content: link_title,
                placement: 'bottom',
                html: true,
                container: '#' + list_id,
                viewport: {
                    selector: 'window',
                    padding: 0
                }
            });
        }
    });

    if (!sub_list_id) {
        var $filters = $list.find('.object_filters_panel');
        if ($filters.length) {
            $filters.each(function () {
                onListFiltersPanelLoaded($(this));
            });
        }
    }

    $list.trigger('statsListRowsRefresh');
}

function setStatsListSearchInputsEvents($container, list_id, sub_list_id) {
    if ($container.length) {
        $container.find('.searchInputContainer').each(function () {
            if (!parseInt($(this).data('event_init'))) {
                $(this).data('event_init', 1);
                var field_name = $(this).data('field_name');
                var search_type = $(this).data('search_type');
                if (field_name) {
                    switch (search_type) {
                        case 'value_part':
                            var $input = $(this).find('[name=' + field_name + ']');
                            if ($input.length) {
                                var search_on_key_up = $(this).data('search_on_key_up');
                                if (typeof (search_on_key_up) === 'undefined') {
                                    search_on_key_up = 0;
                                }
                                if (parseInt(search_on_key_up)) {
                                    $input.keyup(function () {
                                        if (sub_list_id) {
                                            resetStatsListPage(list_id, sub_list_id);
                                            reloadObjectSubStatsListRows(list_id, sub_list_id);
                                        } else {
                                            resetStatsListPage(list_id, sub_list_id);
                                            reloadObjectStatsListRows(list_id);
                                        }
                                    });
                                } else {
                                    $input.change(function () {
                                        if (sub_list_id) {
                                            resetStatsListPage(list_id, sub_list_id);
                                            reloadObjectSubStatsListRows(list_id, sub_list_id);
                                        } else {
                                            resetStatsListPage(list_id, sub_list_id);
                                            reloadObjectStatsListRows(list_id);
                                        }
                                    });
                                }
                            }
                            break;

                        case 'values_range':
                            var $inputs = $(this).find('[name=' + field_name + '_min]').add('[name=' + field_name + '_max]');
                            if ($inputs.length) {
                                $inputs.change(function () {
                                    if (sub_list_id) {
                                        resetStatsListPage(list_id, sub_list_id);
                                        reloadObjectSubStatsListRows(list_id, sub_list_id);
                                    } else {
                                        resetStatsListPage(list_id, sub_list_id);
                                        reloadObjectStatsListRows(list_id);
                                    }
                                });
                            }
                            break;

                        case 'time_range':
                        case 'date_range':
                        case 'datetime_range':
                            setDateRangeEvents($(this), field_name);
                            var $from = $(this).find('[name=' + field_name + '_from]');
                            var $to = $(this).find('[name=' + field_name + '_to]');
                            $from.add($to).change(function () {
                                if (sub_list_id) {
                                    resetStatsListPage(list_id, sub_list_id);
                                    reloadObjectSubStatsListRows(list_id, sub_list_id);
                                } else {
                                    resetStatsListPage(list_id, sub_list_id);
                                    reloadObjectStatsListRows(list_id);
                                }
                            });
                            break;

                        case 'field_value':
                        default:
                            var $input = $(this).find('[name=' + field_name + ']');
                            if ($input.length) {
                                $input.change(function () {
                                    if (sub_list_id) {
                                        resetStatsListPage(list_id, sub_list_id);
                                        reloadObjectSubStatsListRows(list_id, sub_list_id);
                                    } else {
                                        resetStatsListPage(list_id, sub_list_id);
                                        reloadObjectStatsListRows(list_id);
                                    }
                                });
                            }
                            break;
                    }
                }
            }
        });

        $container.find('.statsListSearchResetButton').click(function () {
            resetStatsListSearchInputs(list_id, sub_list_id, true);
        });
    }
}

function setStatslistPaginationEvents($list, sub_list_id) {
    if (!$list.length) {
        console.log('setStatslistPaginationEvents: $list absent');
        return;
    }

    if (typeof (sub_list_id) === 'undefined') {
        sub_list_id = '';
    }

    var $container = null;

    if (sub_list_id) {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + sub_list_id + '"]');
    } else {
        $container = $list.find('.statsListTableContainer[data-sub_list_id="' + $list.data('identifier') + '"]');
    }

    if (!$container.length) {
        console.log('setStatslistPaginationEvents: $container absent');
        return;
    }

    $container.find('div.listPagination').each(function () {
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
                    loadStatsListPage($list, p - 1, sub_list_id);
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
                    loadStatsListPage($list, p + 1, sub_list_id);
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
                    loadStatsListPage($list, parseInt($(this).data('p')), sub_list_id);
                });
            }
        });
    });
}

// Traitements formulaires: 

function onGenerateStatsListCsvFormSubmit($form, extra_data) {
    if ($.isOk($form)) {
        var cols_options = {};
        var $container = $form.find('.cols_options_inputContainer');
        if ($.isOk($container)) {
            $container.find('select.col_option').each(function () {
                var col_name = $(this).attr('name').replace(/^col_(.+)_option$/, '$1');
                cols_options[col_name] = $(this).val();
            });
            extra_data['cols_options'] = cols_options;
        }
    }

    var $list = null;
    if (typeof (extra_data['list_id']) !== 'undefined' && extra_data['list_id']) {
        var $list = $('#' + extra_data['list_id']);
        if ($.isOk($list)) {
            extra_data['list_data'] = getStatsListData($list);
        }
    }

    return extra_data;
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
