function addFieldFilterValue($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $container = $button.findParentByClass('bimp_filter_container');
    if ($.isOk($container)) {
        $container.data('new_value_set', 0);
        var field_name = $container.data('field_name');
        if (field_name) {
            if ($container.data('type') === 'value_part') {
                var $input = $container.find('input[name="add_' + field_name + '_filter"]');
                if ($input.val() === '') {
                    bimp_msg('Veuillez saisir une valeur', 'danger');
                    return;
                }
            }

            $container.data('new_value_set', 1);
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

function editBimpFilterValue($value) {
    var $container = $value.findParentByClass('bimp_filter_container');

    if ($.isOk($container)) {
        var type = $container.data('type');
        var value = $value.data('value');
        var field_name = $container.data('field_name');
        var check = false;
        var $input = null;
        switch (type) {
            case 'value':
            case 'value_part':
                $input = $container.find('input[name="add_' + field_name + '_filter"]');
                if ($input.length) {
                    $input.val(value);
                    check = true;
                }
                break;

            case 'range':
                if (typeof (value.min) !== 'undefined') {
                    $input = $container.find('input[name="add_' + field_name + '_filter_min"]');
                    if ($input.length) {
                        $input.val(value.min);
                        check = true;
                    }
                }
                if (typeof (value.max) !== 'undefined') {
                    $input = $container.find('input[name="add_' + field_name + '_filter_max"]');
                    if ($input.length) {
                        $input.val(value.max);
                        check = true;
                    }
                }
                break;

            case 'date_range':
                if (typeof (value.min) !== 'undefined') {
//                    .find('input.bs_datetimepicker').data('DateTimePicker').date(moment(initial_value)
                    $input = $container.find('input[name="add_' + field_name + '_filter_from_picker"]');
                    if ($input.length) {
                        $input.data('DateTimePicker').date(moment(value.min));
                        check = true;
                    }
                }
                if (typeof (value.max) !== 'undefined') {
                    $input = $container.find('input[name="add_' + field_name + '_filter_to_picker"]');
                    if ($input.length) {
                        $input.data('DateTimePicker').date(moment(value.max));
                        check = true;
                    }
                }
                break;

            case 'check_list':
                return;
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
        bimp_msg('Une erreur est survenue (Filtre à retiré absent)', 'danger');
    }
}

function getAllListFieldsFilters($filters, with_open_value) {
    if (typeof (with_open_value) === 'undefined') {
        with_open_value = true;
    }

    var filters = {
        fields: {},
        children: {}
    };

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            var $container = $(this);
            var field_name = $container.data('field_name');
            var child_name = $container.data('child_name');

            var filter = {
                values: []
            };

            if (with_open_value) {
                var open = 1;
                if ($container.hasClass('closed')) {
                    open = 0;
                }
                filter.open = open;
            }

            if ($container.data('type') === 'check_list') {
                $container.find('[name="add_' + field_name + '_filter[]"]').each(function () {
                    if ($(this).prop('checked')) {
                        filter.values.push($(this).val());
                    }
                });
            } else {
                $container.find('.bimp_filter_value').each(function () {
                    filter.values.push($(this).data('value'));
                });
            }

            if (parseInt($container.data('new_value_set'))) {
                switch ($container.data('type')) {
                    case 'value':
                    case 'value_part':
                        filter.values.push($container.find('[name="add_' + field_name + '_filter"]').val());
                        break;

                    case 'date_range':
                        var values = {};
                        values.min = $container.find('[name="add_' + field_name + '_filter_from"]').val();
                        values.max = $container.find('[name="add_' + field_name + '_filter_to"]').val();
                        filter.values.push(values);
                        break;

                    case 'range':
                        var values = {};
                        values.min = $container.find('[name="add_' + field_name + '_filter_min"]').val();
                        values.max = $container.find('[name="add_' + field_name + '_filter_max"]').val();
                        filter.values.push(values);
                        break;
                }
            }

            if (child_name) {
                if (typeof (filters['children'][child_name]) === 'undefined') {
                    filters['children'][child_name] = {};
                }
                filters['children'][child_name][field_name] = filter;
            } else {
                filters['fields'][field_name] = filter;
            }
        });
    }

    return filters;
}

function removeAllListFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_value').remove();
        $filters.find('.check_list_item').each(function () {
            $(this).find('input[type="checkbox"]').prop('checked', false);
        });

        $('body').trigger($.Event('listFiltersChange', {
            $filters: $filters
        }));
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
    }
}

function saveListFilters($button, filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        var filters = getAllListFieldsFilters($filters, false);

        setObjectAction($button, {
            module: $filters.data('module'),
            object_name: $filters.data('object_name')
        }, 'saveListFilters', {
            list_type: $filters.data('list_type'),
            list_name: $filters.data('list_name'),
            panel_name: $filters.data('name')
        }, 'save_list_filters', null, null, null, function ($form, extra_data) {
            extra_data['filters'] = filters;
            return extra_data;
        });
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
    }
}

function hideAllFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            $(this).addClass('closed').removeClass('open').find('.bimp_filter_content').stop().slideUp(250);
        });
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
    }
}

function showAllFilters(filters_id) {
    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        $filters.find('.bimp_filter_container').each(function () {
            $(this).addClass('open').removeClass('closed').find('.bimp_filter_content').stop().slideDown(250);
        });
    } else {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
    }
}

function loadSavedFilters($button, filters_id) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        var $container = $filters.findParentByClass('listFiltersPanelContainer');
        if ($.isOk($container)) {
            var $input = $filters.find('[name="id_filters_to_load"]');
            if ($input.length) {
                var id_list_filters = parseInt($input.val());
                if (!id_list_filters || isNaN(id_list_filters)) {
                    bimp_msg('Veuillez sélectionner un enregistrement de filtres à charger');
                    return;
                }

                BimpAjax('loadSavedListFilters', {
                    module: $filters.data('module'),
                    object_name: $filters.data('object_name'),
                    list_type: $filters.data('list_type'),
                    list_name: $filters.data('list_name'),
                    list_identifier: $filters.data('list_identifier'),
                    panel_name: $filters.data('name'),
                    id_list_filters: id_list_filters
                }, $container, {
                    $button: $button,
                    $filters: $filters,
                    display_success: false,
                    display_errors_in_popup_only: true,
                    display_warnings_in_popup_only: true,
                    append_html: true,
                    remove_current_content: false,
                    success: function (result, bimpAjax) {
                        var $filters = bimpAjax.$resultContainer.find('.object_filters_panel');
                        onListFiltersPanelLoaded($filters);
                        $('body').trigger($.Event('listFiltersChange', {
                            $filters: bimpAjax.$filters
                        }));
                    }
                });

                return;
            }
        }
    }
    bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
}

function deleteSavedFilters($button, filters_id) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (!confirm('Voulez-vous vraiment supprimer cet enregistrement ?')) {
        return;
    }

    var $filters = $('#' + filters_id);

    if ($.isOk($filters)) {
        var $input = $filters.find('[name="id_filters_to_load"]');
        if ($input.length) {
            var id_list_filters = parseInt($input.val());
            if (!id_list_filters || isNaN(id_list_filters)) {
                bimp_msg('Veuillez sélectionner un enregistrement de filtres à charger');
                return;
            }

            var data = {
                'module': 'bimpcore',
                'object_name': 'ListFilters',
                'objects': [id_list_filters]
            };

            BimpAjax('deleteObjects', data, null, {
                $button: $button,
                success: function (result) {
                    for (var i in result.objects_list) {
                        $('body').trigger($.Event('objectDelete', {
                            module: result.module,
                            object_name: result.object_name,
                            id_object: result.objects_list[i]
                        }));
                    }
                    $('body').trigger($.Event('listFiltersChange', {
                        $filters: $filters
                    }));
                }
            });

            return;
        }
    }

    bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
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
            $filters.find('.bimp_filter_container').each(function () {
                if ($(this).data('type') === 'check_list') {
                    var field_name = $(this).data('field_name');
                    if (field_name) {
                        $(this).find('[name="add_' + field_name + '_filter[]"]').change(function () {
                            $('body').trigger($.Event('listFiltersChange', {
                                $filters: $filters
                            }));
                        });
                    }
                }
            });

            $filters.find('.select2-container').css('width', '100%');
            $filters.find('.inputHelp').hide();

            $filters.data('filters_panels_panel_events_init', 1);
        }
    }
}