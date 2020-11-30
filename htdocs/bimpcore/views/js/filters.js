function addFieldFilterValue($button, exclude) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    if (typeof (exclude) === 'undefined') {
        exclude = false;
    }

    var $container = $button.findParentByClass('bimp_filter_container');
    if ($.isOk($container)) {
        $container.data('new_value_set', 0);
        $container.data('new_excluded_value_set', 0);
        var filter_name = $container.data('filter_name');
        if (filter_name) {
            if ($container.data('type') === 'value_part') {
                var $input = $container.find('input[name="add_' + filter_name + '_filter"]');
                if ($input.val() === '') {
                    var $option_input = $container.find('select[name="add_' + filter_name + '_filter_part_type"]');
                    if (!$option_input.length || $option_input.val() !== 'full') {
                        bimp_msg('Veuillez saisir une valeur ou sélectionner "Est égale à"', 'warning', null, true);
                        $button.removeClass('disabled');
                        return;
                    }
                }
            }

            if (exclude) {
                $container.data('new_excluded_value_set', 1);
            } else {
                $container.data('new_value_set', 1);
            }

            $('body').trigger($.Event('listFiltersChange', {
                $filters: $container.findParentByClass('object_filters_panel')
            }));
        } else {
            bimp_msg('Une erreur est survenue (Nom du champ absent)', 'danger');
        }
    } else {
        bimp_msg('Une erreur est survenue (Conteneur absent)', 'danger');
    }
}

function addFieldFilterCustomValue($button, value, exclude) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    if (typeof (exclude) === 'undefined') {
        exclude = false;
    }

    var $container = $button.findParentByClass('bimp_filter_container');
    if ($.isOk($container)) {
        var filter_name = $container.data('filter_name');
        if (filter_name) {
            var html = '<div class="bimp_filter_value' + (exclude ? ' excluded' : '') + '" data-value="' + value.replace(/"/g, '&quot;') + '" style="display: none">';
            html += '</div>';

            $container.find('.bimp_filter_values_container').append(html);

            $container.data('new_value_set', 0);
            $('body').trigger($.Event('listFiltersChange', {
                $filters: $container.findParentByClass('object_filters_panel')
            }));
        }
    } else {
        bimp_msg('Une erreur est survenue (Conteneur absent)', 'danger');
    }
}

function addFieldFilterDateRangePeriod($button, exclude) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof (exclude) === 'undefined') {
        exclude = false;
    }

    var $container = $button.findParentByClass('bimp_filter_date_range_period');
    if ($.isOk($container)) {
        var data = {
            qty: 0,
            unit: '',
            offset_qty: 0,
            offset_unit: '',
            mode: ''
        };

        data.qty = parseInt($container.find('input.bimp_filter_date_range_period_qty').val());
        data.unit = $container.find('select.bimp_filter_date_range_period_unit').val();
        data.offset_qty = parseInt($container.find('input.bimp_filter_date_range_offset_qty').val());
        data.offset_unit = $container.find('select.bimp_filter_date_range_offset_unit').val();
        data.mode = $container.find('select.bimp_filter_date_range_period_mode').val();

        if (!data.qty || isNaN(data.qty)) {
            bimp_msg('Veuillez saisir une valeur supérieure à 0 pour la durée de la période', 'warning', null, true);
            return;
        }

        if (isNaN(data.qty)) {
            bimp_msg('Valeur saisie invalide pour la durée du décalage', 'warning', null, true);
            return;
        }

        addFieldFilterCustomValue($button, JSON.stringify({period: data}), exclude);
    } else {
        bimp_msg('Une erreur est survenue (Conteneur absent)', 'danger');
    }
}

function editBimpFilterValue($value) {
    var $container = $value.findParentByClass('bimp_filter_container');

    if ($.isOk($container)) {
        var type = $container.data('type');
        var value = $value.data('value');
        var filter_name = $container.data('filter_name');
        var check = false;
        var $input = null;

        if (filter_name) {
            switch (type) {
                case 'value_part':
                    $input = $container.find('input[name="add_' + filter_name + '_filter"]');
                    var $partTypeInput = $container.find('select[name="add_' + filter_name + '_filter_part_type"]');
                    var part = value;
                    var part_type = 'middle';

                    if (typeof (value.value) !== 'undefined') {
                        part = value.value;
                    }
                    if (typeof (value.part_type) !== 'undefined') {
                        part_type = value.part_type;
                    }

                    if ($input.length) {
                        $input.val(part);

                        if ($partTypeInput.length) {
                            $partTypeInput.val(part_type).change();
                        }
                        check = true;
                    }
                    break;

                case 'value':
                    $input = $container.find('input[name="add_' + filter_name + '_filter"]');
                    if ($input.length) {
                        $input.val(value);
                        check = true;

                        var $search_input = $container.find('.search_object_input').find('input');
                        if ($search_input.length) {
                            $search_input.val($value.text());
                        }
                    }
                    break;

                case 'range':
                    if (typeof (value.min) !== 'undefined') {
                        $input = $container.find('input[name="add_' + filter_name + '_filter_min"]');
                        if ($input.length) {
                            $input.val(value.min);
                            check = true;
                        }
                    }
                    if (typeof (value.max) !== 'undefined') {
                        $input = $container.find('input[name="add_' + filter_name + '_filter_max"]');
                        if ($input.length) {
                            $input.val(value.max);
                            check = true;
                        }
                    }
                    break;

                case 'date_range':
                    if (typeof (value.period) !== 'undefined') {
                        var input_name = 'add_' + filter_name + '_filter';
                        check = true;

                        var sub_check = false;
                        if (typeof (value.period.qty) !== 'undefined') {
                            $input = $container.find('input[name="' + input_name + '_period_qty"]');
                            if ($input.length) {
                                $input.val(parseInt(value.period.qty));
                                sub_check = true;
                            }
                        }
                        if (!sub_check) {
                            check = false;
                        }

                        sub_check = false;
                        if (typeof (value.period.unit) !== 'undefined') {
                            $input = $container.find('select[name="' + input_name + '_period_unit"]');
                            if ($input.length) {
                                $input.val(value.period.unit).change();
                                sub_check = true;
                            }
                        }
                        if (!sub_check) {
                            check = false;
                        }

                        if (typeof (value.period.offset_qty) !== 'undefined') {
                            $input = $container.find('input[name="' + input_name + '_period_offset_qty"]');
                            if ($input.length) {
                                $input.val(parseInt(value.period.offset_qty));
                            }
                        }

                        if (typeof (value.period.offset_unit) !== 'undefined') {
                            $input = $container.find('select[name="' + input_name + '_period_offset_unit"]');
                            if ($input.length) {
                                $input.val(value.period.offset_unit).change();
                            }
                        }

                        sub_check = false;
                        if (typeof (value.period.mode) !== 'undefined') {
                            $input = $container.find('select[name="' + input_name + '_period_mode"]');
                            if ($input.length) {
                                $input.val(value.period.mode).change();
                                sub_check = true;
                            }
                        }
                        if (!sub_check) {
                            check = false;
                        }
                    } else {
                        if (typeof (value.min) !== 'undefined') {
                            $input = $container.find('input[name="add_' + filter_name + '_filter_from_picker"]');
                            if ($input.length) {
                                $input.data('DateTimePicker').date(moment(value.min));
                                check = true;
                            }
                        }
                        if (typeof (value.max) !== 'undefined') {
                            $input = $container.find('input[name="add_' + filter_name + '_filter_to_picker"]');
                            if ($input.length) {
                                $input.data('DateTimePicker').date(moment(value.max));
                                check = true;
                            }
                        }
                    }
                    break;

                case 'check_list':
                    return;
            }
        }

        if (check) {
            $container.addClass('open').removeClass('closed').find('.bimp_filter_content').stop().slideDown(250);
            $value.remove();
        }
    }
}

function removeBimpFilterValue(e, $button) {
    if (e) {
        e.stopPropagation();
    }
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $value = $button.findParentByClass('bimp_filter_value');
    if ($.isOk($value)) {
        var $container = $value.findParentByClass('bimp_filter_container');

        if ($.isOk($container)) {
            if ($container.data('type') === 'check_list') {
                var $input = $container.find('input.check_list_item_input[value="' + $value.data('value') + '"]');
                if ($input.length) {
                    $input.prop('checked', false).change();
                    return;
                }
            }
            var $filters = $value.findParentByClass('object_filters_panel');

            if ($.isOk($filters)) {
                $value.remove();
                $('body').trigger($.Event('listFiltersChange', {
                    $filters: $filters
                }));
            } else {
                bimp_msg('Une erreur est survenue (Conteneur absent)', 'danger');
            }
        } else {
            bimp_msg('Une erreur est survenue (Conteneur absent)', 'danger');
        }
    } else {
        bimp_msg('Une erreur est survenue (Filtre à retiré absent)', 'danger');
    }
}

function removeBimpFilterValueFromActiveFilters($button, filters_id, filter_name, value, excluded) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $filters = $('#' + filters_id);

    if (!$.isOk($filters)) {
        bimp_msg('Erreur: panneau filtre non trouvé', 'danger', null, true);
        return;
    }

    $button.addClass('disabled');

    var done = false;
    $filters.find('.bimp_filter_container').each(function () {
        if (!done) {
            var $container = $(this);
            if ($container.data('filter_name') === filter_name) {
                if ($container.data('type') === 'check_list') {
                    var $input = $container.find('input.check_list_item_input[value="' + value + '"]');
                    if ($input.length) {
                        $input.prop('checked', false).change();
                        done = true;
                    }
                } else {
                    $container.find('.bimp_filter_value').each(function () {
                        if (!done) {
                            var $value = $(this);
                            var val = $value.data('value');

                            if (typeof (val) === 'object') {
                                val = JSON.stringify(val);
                            }
                            if (val == value) {
                                if ((excluded && $value.hasClass('excluded')) || (!excluded && !$value.hasClass('excluded'))) {
                                    $value.remove();
                                    done = true;
                                }
                            }
                        }
                    });
                }
            }
        }
    });

    if (done) {
        var $value = $button.findParentByClass('filter_value');
        $value.remove();
        $('body').trigger($.Event('listFiltersChange', {
            $filters: $filters
        }));
    } else {
        bimp_msg('Filtre actif non trouvé', 'danger', null, true);
    }

    $filters.find();
}

function getAllListFieldsFilters($filters, with_open_value) {
    if (typeof (with_open_value) === 'undefined') {
        with_open_value = true;
    }

    var filters = {};
    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            var $container = $(this);
            var filter_name = $container.data('filter_name');

            var filter = {
                values: [],
                excluded_values: []
            };

            if (with_open_value) {
                var open = 1;
                if ($container.hasClass('closed')) {
                    open = 0;
                }
                filter.open = open;
            }

            if ($container.data('type') === 'check_list') {
                $container.find('[name="add_' + filter_name + '_filter[]"]').each(function () {
                    if ($(this).prop('checked')) {
                        filter.values.push($(this).val());
                    }
                });
            } else {
                $container.find('.bimp_filter_value').each(function () {
                    if ($(this).hasClass('excluded')) {
                        filter.excluded_values.push($(this).data('value'));
                    } else {
                        filter.values.push($(this).data('value'));
                    }
                });
            }

            var new_values_set = parseInt($container.data('new_value_set'));
            var new_excluded_values_set = parseInt($container.data('new_excluded_value_set'));

            if (new_values_set || new_excluded_values_set) {
                var new_value = '';
                switch ($container.data('type')) {
                    case 'user':
                    case 'value':
                        new_value = $container.find('[name="add_' + filter_name + '_filter"]').val();
                        break;

                    case 'value_part':
                        new_value = {};
                        new_value.value = $container.find('[name="add_' + filter_name + '_filter"]').val();
                        var $partTypeInput = $container.find('[name="add_' + filter_name + '_filter_part_type"]');
                        if ($partTypeInput.length) {
                            new_value.part_type = $partTypeInput.val();
                        } else {
                            new_value.part_type = 'middle';
                        }
                        break;

                    case 'date_range':
                        new_value = {};
                        new_value.min = $container.find('[name="add_' + filter_name + '_filter_from"]').val();
                        new_value.max = $container.find('[name="add_' + filter_name + '_filter_to"]').val();
                        break;

                    case 'range':
                        new_value = {};
                        new_value.min = $container.find('[name="add_' + filter_name + '_filter_min"]').val();
                        new_value.max = $container.find('[name="add_' + filter_name + '_filter_max"]').val();
                        break;
                }
                if (new_values_set) {
                    filter.values.push(new_value);
                } else if (new_excluded_values_set) {
                    filter.excluded_values.push(new_value);
                }
            }

            if (with_open_value || filter.values.length || filter.excluded_values.length) {
                filters[filter_name] = filter;
            }
        });
    }

    return filters;
}

function removeAllListFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_value').remove();
        $filters.find('.check_list_item_input').prop('checked', false);

        $('body').trigger($.Event('listFiltersChange', {
            $filters: $filters
        }));
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
    }
}

function saveListFilters($button, filters_id, id_list_filters) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        var filters = getAllListFieldsFilters($filters, false);

        if (typeof (id_list_filters) === 'undefined') {
            id_list_filters = 0;
        }

        if (!id_list_filters) {
            loadModalForm($button, {
                module: 'bimpuserconfig',
                object_name: 'ListFilters',
                id_object: id_list_filters,
                id_parent: 0,
                form_name: 'default',
                param_values: {
                    fields: {
                        filters_id: $filters.attr('id'),
                        obj_module: $filters.data('module'),
                        obj_name: $filters.data('object_name'),
                        filters: filters
                    }
                }
            }, 'Enregistrer les filtres actuels');
        } else {
            if ($.isEmptyObject(filters)) {
                bimp_msg('Aucun filtre sélectionné', 'warning', null, true);
                return;
            }
            saveObjectField('bimpuserconfig', 'ListFilters', id_list_filters, 'filters', filters);
        }
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
    }
}

function hideAllFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            $(this).addClass('closed').removeClass('open').find('.bimp_filter_content').stop().slideUp(250);
            if ($(this).data('type') === 'check_list') {
                showFiltersValues($(this));
            }
        });
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
    }
}

function showAllFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            $(this).addClass('open').removeClass('closed').find('.bimp_filter_content').stop().slideDown(250);
            if ($(this).data('type') === 'check_list') {
                hideFiltersValues($(this));
            }
        });
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
    }
}

function loadFiltersConfig(filters_id, id_filters_config) {
    var $filters = $('#' + filters_id);

    if (!$.isOk($filters)) {
        bimp_msg('Une erreur est survenue. Impossible de charger la configuration de filtres', 'danger', null, true);
        console.log('loadFiltersConfig(): $filters absent (#' + filters_id + ')');
        return;
    }

    var $container = $filters.find('.object_filters_panel_content');

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. Impossible de charger la configuration de filtres', 'danger', null, true);
        console.log('loadFiltersConfig(): $container absent (#' + filters_id + ')');
        return;
    }

    var data = {
        module: $filters.data('module'),
        object_name: $filters.data('object_name'),
        list_type: $filters.data('list_type'),
        list_name: $filters.data('list_name'),
        list_identifier: $filters.data('list_identifier'),
        panel_name: $filters.data('name'),
        id_list_filters: 0,
        id_filters_panel_config: id_filters_config
    };

    var $input = $filters.find('select[name="id_filters_to_load"]');

    if ($input.length) {
        data['id_list_filters'] = parseInt($input.val());
    }

    data['filters_panel_values'] = getAllListFieldsFilters($filters);

    BimpAjax('loadFiltersPanelConfig', data, $container, {
        $filters: $filters,
        display_success: false,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true,
        append_html: true,
        remove_current_content: false,
        success: function (result, bimpAjax) {
            bimpAjax.$filters.data('filters_panels_panel_events_init', 0);
            onListFiltersPanelLoaded(bimpAjax.$filters);
        }
    });
}

function loadSavedFilters(filters_id, id_list_filters, full_panel_html) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        if (typeof (id_list_filters) === 'undefined' || !id_list_filters) {
            var $input = $filters.find('select[name="id_filters_to_load"]');
            if ($input.length) {
                id_list_filters = parseInt($input.val());
            }
        }
        if (typeof (full_panel_html) === 'undefined') {
            full_panel_html = 1;
        }

        if (!id_list_filters || isNaN(id_list_filters)) {
            removeAllListFilters(filters_id);
            return;
        }

        if (full_panel_html) {
            var $container = $filters.findParentByClass('listFiltersPanelContainer');
        } else {
            var $container = $filters.find('.load_saved_filters_container');
        }

        var id_filters_config = 0;

        var $input = $filters.find('select[name="id_filters_config_to_load"]');
        if ($input.length) {
            id_filters_config = parseInt($input.val());
        }

        if ($.isOk($container)) {
            BimpAjax('loadSavedListFilters', {
                module: $filters.data('module'),
                object_name: $filters.data('object_name'),
                list_type: $filters.data('list_type'),
                list_name: $filters.data('list_name'),
                list_identifier: $filters.data('list_identifier'),
                panel_name: $filters.data('name'),
                id_list_filters: id_list_filters,
                id_filters_config: id_filters_config,
                full_panel_html: full_panel_html
            }, $container, {
                full_panel_html: full_panel_html,
                display_success: false,
                display_errors_in_popup_only: true,
                display_warnings_in_popup_only: true,
                append_html: true,
                remove_current_content: false,
                success: function (result, bimpAjax) {
                    if (bimpAjax.full_panel_html) {
                        var $filters = bimpAjax.$resultContainer.find('.object_filters_panel');
                        onListFiltersPanelLoaded($filters);
                        $('body').trigger($.Event('listFiltersChange', {
                            $filters: $filters
                        }));
                    } else {
                        onListFiltersSavedFiltersLoaded(bimpAjax.$resultContainer);
                    }

                }
            });

            return;
        }
    }
    bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
}

function loadAllSavedFiltersByObject(object_name, full_panel_html) {
    if (object_name) {
        $('.' + object_name + '_filters_panel').each(function () {
            loadSavedFilters($(this).attr('id'), 0, full_panel_html);
        });
    }
}

function hideFiltersValues($container) {
    $container.find('.bimp_filter_values_container').stop().slideUp(250);
}

function showFiltersValues($container) {
    $container.find('.bimp_filter_values_container').stop().slideDown(250, function () {
        $(this).removeAttr('style');
    });
}

// Gestion des événements: 

function onListFiltersPanelLoaded($filters) {
    if ($.isOk($filters)) {
        if (!parseInt($filters.data('filters_panels_panel_events_init'))) {
            var $container = $filters.findParentByClass('listFiltersPanelContainer');
            if (!$.isOk($container)) {
                $container = $filters;
            }
            setCommonEvents($container);
            setInputsEvents($container);

            $filters.find('select[name="id_filters_config_to_load"]').change(function () {
                loadFiltersConfig($filters.attr('id'), parseInt($(this).val()));
            });

            $filters.find('select[name="id_filters_to_load"]').change(function () {
                loadSavedFilters($filters.attr('id'), parseInt($(this).val()));
            });

            $filters.find('.bimp_filter_container').each(function () {
                if ($(this).data('type') === 'check_list') {
                    var filter_name = $(this).data('filter_name');
                    if (filter_name) {
                        $(this).find('[name="add_' + filter_name + '_filter[]"]').change(function () {
                            $('body').trigger($.Event('listFiltersChange', {
                                $filters: $filters
                            }));
                        });
                    }
                }
            });

            $filters.find('.select2-container').css('width', '100%');
            $filters.find('.inputHelp').hide();

            if (!parseInt($filters.data('config_change_event_init'))) {
                $('body').on('objectChange', function (e) {
                    if (e.module === 'bimpuserconfig' && e.object_name === 'FiltersConfig') {
                        var id_config = 0;
                        var $input = $filters.find('select[name="id_filters_config_to_load"]');
                        if ($input.length) {
                            id_config = parseInt($input.val());
                        }
                        loadFiltersConfig($filters.attr('id'), id_config);
                    }
                });

                $('body').on('objectDelete', function (e) {
                    if ((e.module === 'bimpuserconfig') && (e.object_name === 'FiltersConfig')) {
                        var id_config = 0;
                        var $input = $filters.find('select[name="id_filters_config_to_load"]');
                        if ($input.length) {
                            id_config = parseInt($input.val());
                            if (typeof (e.id_config !== 'undefined') && id_config === e.id_object) {
                                id_config = 0;
                            }
                        }
                        loadFiltersConfig($filters.attr('id'), id_config);
                    }
                });

                $filters.data('config_change_event_init', 1);
            }
            $filters.data('filters_panels_panel_events_init', 1);
        }
    }
}

function onListFiltersSavedFiltersLoaded($container) {
    if ($.isOk($container)) {
        var $filters = $container.findParentByClass('object_filters_panel');

        if ($.isOk($filters)) {
            setCommonEvents($container);
            setInputsEvents($container);

            $container.find('select[name="id_filters_to_load"]').change(function () {
                loadSavedFilters($filters.attr('id'), parseInt($(this).val()));
            });
            $container.find('.select2-container').css('width', '100%');

            $container.data('filters_saved_filters_events_init', 1);
        }
    }
}