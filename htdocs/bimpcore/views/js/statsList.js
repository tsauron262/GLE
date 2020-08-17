// Traitements Ajax: 

function getStatsListData($list) {
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

    return data;
}

function reloadObjectStatsList(list_id, callback, id_config) {
    if (typeof (id_config) === 'undefined') {
        id_config = 0;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    $list.find('input[name="param_p"]').val(1);

    var $resultContainer = $('#' + list_id + '_ajax_content');

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
                hidePopovers($list);
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
        return;
    }

    $list.find('.headerTools').find('.loadingIcon').css('opacity', 1);

    var $resultContainer = $('#' + list_id + '_result');
    var data = getStatsListData($list);

    // Envoi requête:
    var error_msg = 'Une erreur est sruvenue. La liste n\'a pas pu être rechargée';

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
                setPaginationEvents(bimpAjax.$list);
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

            onStatsListRefreshed(bimpAjax.$list);

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

function loadObjectSubStatsList($button, parent_list_id, filters, joins, group_by_idx) {
    var error_msg = 'Une erreur est survenue. La liste n\'a pas pu être chargée';

    var $list = $('#' + parent_list_id);

    if (!$list.length) {
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var $row = $button.findParentByClass('statListItemRow');

    if (!$.isOk($row)) {
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var $nextRow = $row.next('tr.statList_subListRow');

    if (!$nextRow.length) {
        bimp_msg(error_msg, 'danger', null, true);
        return;
    }

    var data = getStatsListData($list);

    data['sub_list_filters'] = filters;
    data['sub_list_joins'] = joins;
    data['group_by_index'] = group_by_idx;

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
        },
        error: function () {
        }
    });
}

// Actions: 

function sortStatsList(list_id, col_name) {
    var $row = $('#' + list_id).find('.headerRow');
    if ($row.length) {
        var $span = $row.find('#' + col_name + '_sortTitle');
        if ($span.length) {
            var $list = $('#' + list_id);
            var prev_sort_field = $list.find('input[name=param_sort_field]').val();
            var prev_sort_way = $list.find('input[name=param_sort_way]').val();
            var prev_sort_option = $list.find('input[name=param_sort_option]').val();
            $list.find('input[name=param_sort_field]').val(col_name);
            if ($span.hasClass('sorted-asc')) {
                $list.find('input[name=param_sort_way]').val('desc');
            } else {
                $list.find('input[name=param_sort_way]').val('asc');
            }
            var sort_option = $span.data('sort_option');
            if (!sort_option) {
                sort_option = '';
            }
            $list.find('input[name=param_sort_option]').val(sort_option);
            reloadObjectStatsListRows(list_id, function (success) {
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
                    $list.find('input[name=param_sort_field]').val(prev_sort_field);
                    $list.find('input[name=param_sort_way]').val(prev_sort_way);
                    $list.find('input[name=param_sort_option]').val(prev_sort_option);
                }
            }, true);
        }
    }
}

function loadStatsListPage($list, page) {
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
        return;
    }

    $list.find('input[name=param_p]').val(page);

    reloadObjectStatsListRows($list.attr('id'));
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

// Evénements: 

function onStatsListLoaded($list) {
    // Premier chargement de la liste: 
    if (!$list.length) {
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

    onStatsListRefreshed($list);
    reloadObjectStatsList($list.attr('id'));
}

function onStatsListRefreshed($list) {
    // Chargement complet de la liste (en-tête compris) 
    if (!$list.length) {
        return;
    }

    $list.find('input[name="param_n"]').change(function () {
        reloadObjectStatsListRows($list.attr('id'));
    });

    setCommonEvents($list);
    setInputsEvents($list);

    var $tools = $list.find('.headerTools');

    if ($tools.length) {
        $tools.find('.openSearchRowButton').click(function () {
            var $searchRow = $list.find('.listSearchRow');
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
            $list.find('input[name="param_n"]').val(n).change();
        });
        $tools.find('.refreshListButton').click(function () {
            reloadObjectStatsListRows($list.attr('id'));
        });

        $list.find('input[name="param_n"]').change(function () {
            var val = parseInt($(this).val());
            var select_val = parseInt($tools.find('input[name="select_n"]').val());
            if (val !== select_val) {
                $tools.find('input[name="select_n"]').val(val).change();
            }
        });
    }

    $list.find('tr.listSearchRow').each(function () {
        $(this).find('.searchInputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                $(this).find('[name=' + field_name + ']').val('');
            }
        });
        $(this).find('.ui-autocomplete-input').val('');
        setStatsListSearchInputsEvents($list);
    });

    $list.trigger('statsListRefresh');

    onStatsListRowsRefreshed($list);
}

function onStatsListRowsRefreshed($list) {
    // Chargement des lignes de la liste seulemement (+ pagination / filtres / filtres actifs). 

    if (!$list.length) {
        return;
    }

    var $tbody = $list.find('tbody.listRows');
    var list_id = $list.attr('id');

    resetListSearchInputs(list_id, false);
    setStatslistPaginationEvents($list);

    setCommonEvents($tbody);
    setInputsEvents($tbody);

    setCommonEvents($list.find('.list_active_filters'));
    setInputsEvents($list.find('.list_active_filters'));

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

    var $filters = $list.find('.object_filters_panel');
    if ($filters.length) {
        $filters.each(function () {
            onListFiltersPanelLoaded($(this));
        });
    }

    $list.trigger('statsListRowsRefresh');
}

function setStatsListSearchInputsEvents($list) {
    if ($list.length) {
        $list.find('.searchInputContainer').each(function () {
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
                                        reloadObjectStatsListRows($list.attr('id'));
                                    });
                                } else {
                                    $input.change(function () {
                                        reloadObjectStatsListRows($list.attr('id'));
                                    });
                                }
                            }
                            break;

                        case 'values_range':
                            var $inputs = $(this).find('[name=' + field_name + '_min]').add('[name=' + field_name + '_max]');
                            if ($inputs.length) {
                                $inputs.change(function () {
                                    reloadObjectStatsListRows($list.attr('id'));
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
                                reloadObjectStatsListRows($list.attr('id'));
                            });
                            break;

                        case 'field_value':
                        default:
                            var $input = $(this).find('[name=' + field_name + ']');
                            if ($input.length) {
                                $input.change(function () {
                                    reloadObjectStatsListRows($list.attr('id'));
                                });
                            }
                            break;
                    }
                }
            }
        });
    }
}

function setStatslistPaginationEvents($list) {
    if (!$list.length) {
        return;
    }

    var $container = $('#' + $list.attr('id') + '_container');
    if (!$container.length) {
        return;
    }

    $container.find('div.listPagination').each(function () {
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
                    if (p <= 1) {
                        return;
                    }
                    loadStatsListPage($list, p - 1);
                });
            }
        }
        var $next = $((this)).find('.nextButton');
        if ($next.length) {
            if (!$next.hasClass('disabled')) {
                $next.click(function () {
                    loadStatsListPage($list, p + 1);
                });
            }
        }
        $(this).find('.pageBtn').each(function () {
            if (!$(this).hasClass('active')) {
                $(this).click(function () {
                    loadStatsListPage($list, parseInt($(this).data('p')));
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
