var inputsEvents = [];

function addInputEvent(form_id, input_name, event, callback) {
    for (i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id &&
                inputsEvents[i].input_name === input_name &&
                inputsEvents[i].event === event &&
                ('' + inputsEvents[i].callback) === ('' + callback)) {
            return;
        }
    }
    inputsEvents.push({
        form_id: form_id,
        input_name: input_name,
        event: event,
        callback: callback
    });
}

// Enregistrements ajax des objets: 

function saveObjectFromForm(form_id, $button, successCallback) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $resultContainer = $('#' + form_id + '_result');
    var $form = $('#' + form_id);

    if (!$form.length) {
        var msg = 'Erreur technique: formulaire non trouvé';
        bimp_msg(msg, 'danger', $resultContainer);
        return;
    }

    var module = $form.data('module');
    var object_name = $form.data('object_name');

    var $formular = $form.find('form.' + object_name + '_form');

    if (!$formular.length) {
        bimp_msg('Erreur. Formulaire absent ou invalide', 'danger');
        return;
    }

    var data = new FormData($formular.get(0));

    BimpAjax('saveObject', data, $resultContainer, {
        processData: false,
        contentType: false,
        display_success_in_popup_only: true,
        success: function (result) {
            if ((typeof (result.object_view_url) !== 'undefined') && result.object_view_url) {
                var $link = $button.parent().find('.objectViewLink');
                if ($link.length) {
                    $link.removeClass('hidden').attr('href', result.object_view_url);
                }
            }
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
            reloadForm(form_id);
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        },
        error: function () {
            $button.removeClass('disabled');
        }
    });
}

function loadModalForm($button, data) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    if (typeof (data.id_object) === 'undefined') {
        data.id_object = 0;
    }

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    var title = '';

    if (data.id_object) {
        title = '<i class="fa fa-edit iconLeft"></i>Edition ';
        if (typeof (object_labels[data.object_name].of_the) !== 'undefined') {
            title += object_labels[data.object_name].of_the;
        } else {
            title += 'l\'objet "' + data.object_name + '"';
        }
        title += ' ' + data.id_object;
    } else {
        title = '<i class="fa fa-plus-circle iconLeft"></i>Ajout ';
        if (typeof (object_labels[data.object_name].of_a) !== 'undefined') {
            title += object_labels[data.object_name].of_a;
        } else {
            title += 'd\'un objet "' + data.object_name + '"';
        }
    }

    $modal.find('.modal-title').html(title);
    $modal.find('.loading-text').text('Chargement du formulaire');
    $modal.find('.content-loading').show();
    $modal.modal('show');

    var isCancelled = false;

    $modal.on('hide.bs.modal', function (e) {
        $modal.find('.extra_button').remove();
        $modal.find('.content-loading').hide();
        isCancelled = true;
        $button.removeClass('disabled');
    });

    data.full_panel = 0;

    BimpAjax('loadObjectForm', data, null, {
        display_success: false,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé',
        success: function (result) {
            var $modal = $('#page_modal');
            var $resultContainer = $modal.find('.modal-ajax-content');
            $modal.find('.content-loading').hide();
            if (!isCancelled) {
                if (typeof (result.html) !== 'undefined') {
                    $resultContainer.html(result.html).slideDown(250);

                    var button_html = '<button type="button" class="extra_button btn btn-primary save_object_button"';
                    button_html += ' onclick="saveObjectFromForm(\'' + result.form_id + '\', $(this));">';
                    button_html += '<i class="fa fa-save iconLeft"></i>Enregistrer</button>';
                    $modal.find('.modal-footer').append(button_html);

                    button_html = '<a class="hidden objectViewLink extra_button btn btn-primary" href="">';
                    button_html += '<i class="fa fa-file-o iconLeft"></i>Afficher</a>';
                    $modal.find('.modal-footer').append(button_html);

                    var $form = $resultContainer.find('.object_form');
                    if ($form.length) {
                        $form.each(function () {
                            onFormLoaded($form);
                        });
                    }
                }
                $modal.modal('handleUpdate');
            }
        },
        error: function (result) {
            $modal.find('.content-loading').hide();
            $modal.modal('handleUpdate');
        }
    });
}

function reloadForm(form_id) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var data = {
        'module': $form.data('module'),
        'object_name': $form.data('object_name'),
        'id_object': $form.data('id_object'),
        'id_parent': $form.data('id_parent'),
        'form_name': $form.data('name'),
        'full_panel': 0
    };

    var $params = $('#' + form_id + '_params');
    if ($params.length) {
        $params.find('input.object_component_param').each(function () {
            data[$(this).attr('name')] = $(this).val();
        });
    }

    $form.find('.object_form_content').hide();

    var $modal = $form.findParentByClass('modal-body');
    if ($modal.length) {
        $modal.find('.loading-text').text('Chargement du formulaire');
        $modal.find('.content-loading').show();
    }

    BimpAjax('loadObjectForm', data, null, {
        display_success: false,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être rechargé',
        success: function (result) {
            if (result.form_id && result.html) {
                var $form = $('#' + form_id);
                var $modal = $form.findParentByClass('modal-body');
                if ($modal.length) {
                    $modal.find('.content-loading').hide();
                    $modal.find('.loading-text').text('');
                }

                if ($form.length) {
                    $form.find('.object_form_content').html(result.html).slideDown(function () {
                        setFormEvents($form);
                        setCommonEvents($form);
                    });
                }
            }
        }
    });
}

function loadObjectFormFromForm(title, result_input_name, parent_form_id, module, object_name, form_name, id_parent, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $form = $('#' + parent_form_id);

    if (!$form.length) {
        bimp_msg('Une erreur est survenue. Impossible de charger le formulaire', 'danger');
        return;
    }

    var $resultContainer = $form.find('#' + parent_form_id + '_result');

    if (!$resultContainer) {
        bimp_msg('Une erreur est survenue. Impossible de charger le formulaire', 'danger');
        return;
    }

    $resultContainer.html('').hide();

    var $parentFormSubmit = null;

    var $panel = $form.findParentByClass('panel');
    if ($panel && $panel.length) {
        $parentFormSubmit = $panel.find('.panel-footer').find('.save_object_button');
    } else {
        var $modal = $form.findParentByClass('modal-content');
        if ($modal && $modal.length) {
            $parentFormSubmit = $modal.find('.modal-footer').find('.save_object_button');
        }
    }

    var data = {
        'module': module,
        'object_name': object_name,
        'form_name': form_name,
        'id_object': 0,
        'id_parent': id_parent
    };

    BimpAjax('loadObjectForm', data, null, {
        display_success: false,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé',
        $button: $button,
        $parentForm: $form,
        $resultContainer: $resultContainer,
        title: title,
        $parentFormSubmit: $parentFormSubmit,
        result_input_name: result_input_name,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                if (bimpAjax.$parentFormSubmit) {
                    bimpAjax.$parentFormSubmit.addClass('disabled');
                }
                var $formContent = bimpAjax.$parentForm.find('.object_form_content');
                $formContent.slideUp(250, function () {
                    var html = '<div class="panel panel-default">';

                    html += '<div class="panel-heading">';
                    html += '<div class="panel-title">';
                    html += bimpAjax.title;
                    html += '</div>';
                    html += '</div>';

                    html += '<div class="panel-body">';
                    html += result.html;
                    html += '</div>';

                    html += '<div class="panel-footer" style="text-align: right">';
                    html += '<button class="cancel_button btn btn-default">';
                    html += '<i class="fa fa-times iconLeft"></i>Annuler';
                    html += '<button class="save_object_button btn btn-primary">';
                    html += '<i class="fa fa-save iconLeft"></i>Enregistrer';
                    html += '</div>';

                    html += '</div>';
                    bimpAjax.$resultContainer.html(html).slideDown(function () {
                        var $newForm = bimpAjax.$resultContainer.find('#' + result.form_id);
                        if ($newForm.length) {
                            onFormLoaded($newForm);
                            bimpAjax.$resultContainer.find('.panel-footer').find('.cancel_button').click(function () {
                                bimpAjax.$resultContainer.slideUp(250, function () {
                                    bimpAjax.$resultContainer.html('');
                                    $formContent.slideDown(250, function () {
                                        if (bimpAjax.$parentFormSubmit) {
                                            bimpAjax.$parentFormSubmit.removeClass('disabled');
                                        }
                                    });
                                });
                            });
                            bimpAjax.$resultContainer.find('.panel-footer').find('.save_object_button').click(function () {
                                saveObjectFromForm(result.form_id, $(this), function (saveResult) {
                                    bimpAjax.$resultContainer.slideUp(250, function () {
                                        bimpAjax.$resultContainer.html('');
                                        $formContent.slideDown(250, function () {
                                            if (bimpAjax.$parentFormSubmit) {
                                                bimpAjax.$parentFormSubmit.removeClass('disabled');
                                            }
                                            if (bimpAjax.result_input_name) {
                                                var fields = {};
                                                fields[bimpAjax.result_input_name] = saveResult.id_object;
                                                reloadObjectInput(bimpAjax.$parentForm.attr('id'), bimpAjax.result_input_name, fields);
                                            }
                                        });
                                    });
                                });
                            });
                        }
                    });
                });
            }
            bimpAjax.$button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });

    $button.addClass('disabled');
}

function addObjectMultipleValuesItem(module, object_name, id_object, field, item_value, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        item_value: item_value
    };

    BimpAjax('addObjectMultipleValuesItem', data, $resultContainer, {
        success: function (result) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        }
    });
}

function deleteObjectMultipleValuesItem(module, object_name, id_object, field, item_value, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        item_value: item_value
    };


    BimpAjax('deleteObjectMultipleValuesItem', data, $resultContainer, {
        success: function (result) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }

            $('body').trigger($.Event('objectChange', {
                module: module,
                object_name: object_name,
                id_object: id_object
            }));
        }
    });
}

function saveAssociations(operation, associations, $resultContainer, successCallBack) {
    var data = {
        operation: operation,
        associations: associations
    };

    BimpAjax('saveAssociations', data, $resultContainer, {
        success: function (result) {
            if (typeof (successCallBack) === 'function') {
                successCallBack(result);
            }
            for (var i in result.done) {
                $('body').trigger($.Event('objectChange', {
                    module: associations[result.done[i]].module,
                    object_name: associations[result.done[i]].object_name,
                    id_object: associations[result.done[i]].id_object
                }));
            }
        }
    });
}

// Gestion des formulaires objets: 

function reloadObjectInput(form_id, input_name, fields) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var $container = $form.find('.' + input_name + '_inputContainer');

    if (!$container.length) {
        return;
    }

    var custom = 0;
    if ($container.hasClass('customField')) {
        custom = 1;
    }

    var data = {
        form_id: form_id,
        module: $form.data('module'),
        object_name: $form.data('object_name'),
        form_name: $form.data('name'),
        id_object: $form.data('id_object'),
        id_parent: $form.data('id_parent'),
        field_name: input_name,
        fields: fields,
        custom_field: custom
    };

    if (custom) {
        data['form_row'] = $container.data('form_row');
    }

    BimpAjax('loadObjectInput', data, $container, {
        error_msg: 'Echec du chargement du champ',
        display_success: false,
        success: function (result) {
            if (typeof (result.html) !== 'undefined') {
                var $form = $('#' + result.form_id);
                var $container = $form.find('.' + result.field_name + '_inputContainer').parent();
                $container.html(result.html).slideDown(250, function () {
                    var $input = $form.find('[name=' + result.field_name + ']');
                    if ($input.length) {
                        setInputEvents($form, $input);
                    }
                });
            }
        }
    });
}

function searchObjectList($input) {
    if (!$.isOk($input)) {
        bimp_msg('Une erreur est survenue. Impossible d\'effectuer la recherche', 'danger');
        console.error('$input invalide');
        return;
    }

    var $container = $input.findParentByClass('inputContainer');

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. Impossible d\'effectuer la recherche', 'danger');
        console.error('$container invalide');
        return;
    }

    var value = $input.val();

    if (!value) {
        $container.find('[name=' + $container.data('field_name') + ']').val('0').change();
        $container.find('.search_input_selected_label').slideUp(250, function () {
            $(this).find('span').text('');
        });
        return;
    }

    var data = {
        'table': $input.data('table'),
        'fields_search': $input.data('fields_search'),
        'field_return_value': $input.data('field_return_value'),
        'field_return_label': $input.data('field_return_label'),
        'join': $input.data('join'),
        'join_on': $input.data('join_on'),
        'join_return_label': $input.data('join_return_label'),
        'label_syntaxe': $input.data('label_syntaxe'),
        'filters': $input.data('filters'),
        'value': value
    };

    var $spinner = $container.find('.loading');
    var $result = $container.find('.search_input_results');

    $spinner.addClass('active');
    $result.html('').hide();

    BimpAjax('searchObjectlist', data, null, {
        $input: $input,
        $container: $container,
        $result: $result,
        $spinner: $spinner,
        display_success: false,
        success: function (result, bimpAjax) {
            $result.html('').hide();
            bimpAjax.$spinner.removeClass('active');
            if (typeof (result.list) !== 'undefined') {
                if (result.list.length) {
                    for (var i in result.list) {
                        var html = '<button type="button" class="btn btn-light-default"';
                        html += ' data-value="' + result.list[i].value + '">';
                        html += result.list[i].label;
                        if (result.list[i].join_label) {
                            html += ' (' + result.list[i].join_label + ')';
                        }
                        html += '</button>';
                        bimpAjax.$result.append(html);
                    }
                    var field_name = bimpAjax.$container.data('field_name');
                    var multiple = parseInt(bimpAjax.$container.data('multiple'));
                    if (multiple) {
                        field_name += '_add_value';
                    }
                    var $field_input = bimpAjax.$container.find('[name=' + field_name + ']');
                    bimpAjax.$result.find('button').click(function () {
                        $field_input.val($(this).data('value')).change();
                        bimpAjax.$result.html('').hide();
                        bimpAjax.$input.val('');
                        var label = $(this).text();
                        $container.find('.search_input_selected_label').find('span').text(label);
                        $container.find('.search_input_selected_label').slideDown(250);
                    });
                    bimpAjax.$result.show();
                    bimpAjax.$result.off('mouseleave');
                    bimpAjax.$result.mouseenter(function () {
                        $(this).off('mouseenter');
                        $(this).mouseleave(function () {
                            $(this).slideUp(250);
                        });
                    });
                }
            }
        }, error: function (result, bimpAjax) {
            bimpAjax.$result.html('').hide();
            bimpAjax.$spinner.removeClass('active');
        }
    });
}

function getFieldValue($form, field_name) {
    if (!$form.length) {
        return '';
    }

    var $inputContainer = $form.find('.' + field_name + '_inputContainer');
    var $input = $inputContainer.find('[name=' + field_name + ']');
    if ($input.length) {
        return $input.val();
    }
    return '';
}

function getInputsValues($container) {
    var values = {};
    $container.find('.inputContainer').each(function () {
        var field = $(this).data('field_name');
        var multiple = $(this).data('multiple');
        if (multiple) {
            var $inputs = $(this).find('[name="' + field + '[]"]');
            values[field] = [];
            $inputs.each(function () {
                if ($(this).attr('type') === 'checkbox') {
                    if ($(this).prop('checked')) {
                        values[field].push($(this).val());
                    }
                } else {
                    values[field].push($(this).val());
                }
            });
            if (!values[field].length) {
                values[field] = 0;
            }
        } else {
            var $input = $(this).find('[name="' + field + '"]');
            if ($input.length) {
                values[field] = $input.val();
            }
        }
    });
    return values;
}

function addMultipleInputCurrentValue($button, value_input_name, label_input_name, ajax_save) {
    if (typeof (ajax_save) === 'undefined') {
        ajax_save = false;
    }

    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = $button.findParentByClass('inputMultipleValuesContainer');
    if (!$container.length) {
        bimp_msg('Une erreur est survenue. opération impossible', 'danger');
        return;
    }
    var $inputContainer = $container.parent().find('.inputContainer');
    if (!$inputContainer.length) {
        bimp_msg('Une erreur est survenue. opération impossible', 'danger');
        return;
    }
    var $value_input = $inputContainer.find('[name=' + value_input_name + ']');
    var $label_input = $inputContainer.find('[name=' + label_input_name + ']');
    var value = $value_input.val();
    var label = $label_input.val();
    if (typeof (value) !== 'undefined' && value !== '') {
        if (!label) {
            if ($value_input.get(0).tagName.toLowerCase() === 'select') {
                label = $value_input.find('[value="' + value + '"]').text();
            } else {
                label = value;
            }
        }
        var values_field_name = $inputContainer.data('values_field');
        var html = '<tr>';
        html += '<td style="display: none"><input type="hidden" value="' + value + '" name="' + values_field_name + '[]"/></td>';
        html += '<td>' + label + '</td>';
        html += '<td><button type="button" class="btn btn-light-danger iconBtn"';
        html += ' onclick="$(this).parent(\'td\').parent(\'tr\').remove();';
        if (ajax_save) {
            html += 'var $button = $(this); deleteObjectMultipleValuesItem(\'' + $container.data('module') + '\', ';
            html += '\'' + $container.data('object_name') + '\', ';
            html += $container.data('id_object') + ', \'' + values_field_name + '\', \'' + value + '\', null, ';
            html += 'function(){$button.parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove();})});';
        } else {
            html += '$(this).parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove()});';
        }
        html += '"><i class="fa fa-trash"></i></button></td>';
        html += '</tr>';

        $value_input.val('');
        $label_input.val('');

        if (ajax_save) {
            addObjectMultipleValuesItem($container.data('module'), $container.data('object_name'), $container.data('id_object'), values_field_name, value, null, function () {
                $container.find('div.inputMultipleValuesContainer').find('table').find('tbody').append(html);
            });
        } else {
            $container.find('table').find('tbody').append(html);
        }
    }
}

function checkTextualInput($input) {
    if ($input.length) {
        var data_type = $input.data('data_type');
        if (data_type) {
            var value = $input.val();
            if (value === '') {
                return true;
            }
            var msg = '';
            var initial_value = value;
            switch (data_type) {
                case 'number':
                    var min = $input.data('min');
                    var max = $input.data('max');
                    var decimals = parseInt($input.data('decimals'));
                    var unsigned = parseInt($input.data('unsigned'));

                    value = value.replace(/[^0-9\.,\-]/g, '');
                    value = value.replace(',', '.');
                    if (value === '-') {
                        if (unsigned || (parseFloat(min) >= 0)) {
                            value = '';
                            msg = 'Nombres négatifs interdits';
                        }
                        break;
                    }
                    var parsed_value = 0;
                    if (decimals > 0) {
                        var reg_str = '^(\-?[0-9]+\.[0-9]{0,' + decimals + '}).*$';
                        var reg = new RegExp(reg_str);
                        value = value.replace(reg, '$1');
                        if (/\.$/.test(value)) {
                            break;
                        }
                        parsed_value = parseFloat(value);
                        min = parseFloat(min);
                        max = parseFloat(max);
                    } else {
                        value = value.replace(/^(\-?[0-9]*)\.?.*$/, '$1');
                        parsed_value = parseInt(value);
                        min = parseInt(min);
                        max = parseInt(max);
                    }
                    if (parsed_value < min) {
                        value = min;
                        msg = 'Min: ' + min;
                    } else if (parsed_value > max) {
                        value = max;
                        msg = 'Max: ' + max;
                    }
                    break;

                case 'string':
                    var size = $input.data('size');
                    var forbidden_chars = $input.data('forbidden_chars');
                    var regexp = $input.data('regexp');
                    var invalide_msg = $input.data('invalid_msg');
                    var uppercase = $input.data('uppercase');
                    var lowercase = $input.data('lowercase');
                    break;
            }
            if (('' + value) !== ('' + initial_value)) {
                $input.val(value).change();
            }
            if (msg) {
                displayInputMsg($input, msg, 'info');
            } else {
                $input.popover('destroy');
            }
        }
    }
}

function displayInputMsg($input, msg, className) {
    if (typeof (className) === 'undefined') {
        className = 'info';
    }
    var html = '<p class="alert alert-' + className + '">' + msg + '</p>';
    bimp_display_element_popover($input, html, 'right');
    $input.unbind('blur').blur(function () {
        $input.popover('destroy');
    });
}

// Gestion de l'affichage conditionnel des champs: 

function toggleInputDisplay($container, $input) {
    var input_val = $input.val();
    var show_values = $container.data('show_values');
    var hide_values = $container.data('hide_values');
    var show = false;
    if (typeof (show_values) !== 'undefined') {
        show_values += '';
        show_values = show_values.split(',');
        for (i in show_values) {
            if (input_val == show_values[i]) {
                show = true;
                break;
            }
        }
    } else if (typeof (hide_values) !== 'undefined') {
        show = true;
        hide_values += '';
        hide_values = hide_values.split(',');
        for (i in hide_values) {
            if (input_val == hide_values[i]) {
                show = false;
                break;
            }
        }
    }

    if (show) {
        $container.stop().slideDown(250);
    } else {
        var input_name = $container.find('.inputContainer').data('field_name');
        if (input_name) {
            var $input = $container.find('[name="' + input_name + '"]');
            if ($input.length) {
                var initial_value = $container.find('.inputContainer').data('initial_value');
                $input.val(initial_value);
            }
        }
        $container.stop().slideUp(250);
    }
}

function resetInputDisplay($form) {
    $form.find('.display_if').each(function () {
        var $display_if = $(this);
        var input_name = $display_if.data('input_name');
        if (input_name) {
            var $input = $form.find('[name=' + input_name + ']');
            if ($input.length) {
                if ($input.attr('type') === 'radio') {
                    $input = $form.find('input[name=' + input_name + ']:checked');
                }
                toggleInputDisplay($display_if, $input);
            }
        }
    });
}

// Gestion des évennements: 

function onFormLoaded($form) {
    if (!$form.length) {
        return;
    }

    if (!parseInt($form.data('loaded_event_processed'))) {
        $form.data('loaded_event_processed', 1);

        setFormEvents($form);
        setCommonEvents($form);
    }
}

function setFormEvents($form) {
    if (!$form.length) {
        return;
    }

    // Gestion automatique des affichages d'options supplémentaires lorsque certains input prennent certaines valeurs.
    // placer les champs optionnels dans un container avec class="display_if". 
    // Et Attributs: 
    // data-input_name=nom_input_a_checker (string)
    // data-show_values=valeur(s)_pour_afficher_container (string ou objet)
    // data-hide_values=valeur(s)_pour_masquer_container (string ou objet)

    $form.find('.display_if').each(function () {
        var $container = $(this);
        var input_name = $container.data('input_name');
        if (input_name) {
            var $input = $form.find('[name=' + input_name + ']');
            if ($input.length) {
                $input.change(function () {
                    toggleInputDisplay($container, $(this));
                });
            }
        }
    });

    resetInputDisplay($form);
    setInputsEvents($form);

    var form_id = $form.attr('id');
    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id) {
            var $input = $form.find('[name=' + inputsEvents[i].input_name + ']');
            if ($input.length) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
            }
        }
    }
}

function setInputsEvents($container) {
    $container.find('.switch').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSwitchInputEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.toggle_value').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setToggleInputEvent($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.searchListOptions').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSearchListOptionsEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('input[type="text"]').each(function () {
        if (!$(this).data('check_event_init')) {
            $(this).keyup(function () {
                checkTextualInput($(this));
            });
            $(this).data('check_event_init', 1);
        }
    });
}

function setInputEvents($form, $input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

    if ($input.hasClass('switch')) {
        setSwitchInputEvents($input);
    }

    if ($input.hasClass('.toggle_value')) {
        setToggleInputEvent($input);
    }

    var input_name = $input.attr('name');
    $form.find('.display_if').each(function () {
        var $container = $(this);
        if ($(this).data('input_name') === input_name) {
            $input.change(function () {
                toggleInputDisplay($container, $(this));
            });
        }
    });

    var form_id = $form.attr('id');
    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id) {
            if (inputsEvents[i].input_name === input_name) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
            }
        }
    }

    resetInputDisplay($form);
    $input.data('event_init', 1);
}

function setSwitchInputEvents($input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

    if (parseInt($input.val()) === 1) {
        $input.css({
            'color': '#3b6ea0',
            'border-bottom-color': '#3b6ea0'
        });
    } else {
        $input.css({
            'color': '#B4B4B4',
            'border-bottom-color': '#B4B4B4'
        });
    }
    $input.change(function () {
        if (parseInt($(this).val()) === 1) {
            $(this).css({
                'color': '#3b6ea0',
                'border-bottom-color': '#3b6ea0'
            });
        } else {
            $(this).css({
                'color': '#B4B4B4',
                'border-bottom-color': '#B4B4B4'
            });
        }
    });

    $input.data('event_init', 1);
}

function setToggleInputEvent($input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

    var $toggle = $input.parent().find('.toggle');
    $toggle.change(function () {
        if ($(this).prop('checked')) {
            $input.val(1).change();
        } else {
            $input.val(0).change();
        }
    });
    $input.change(function () {
        var val = parseInt($input.val());
        if (val) {
            if (!$toggle.prop('checked')) {
                $toggle.prop('checked', true);
            }
        } else {
            if ($toggle.prop('checked')) {
                $toggle.prop('checked', false);
            }
        }
    });

    $input.data('event_init', 1);
}

function setDateRangeEvents($container, input_name) {
    var $from = $container.find('[name=' + input_name + '_from_picker]');
    var $to = $container.find('[name=' + input_name + '_to_picker]');

    if (!$from.length || !$to.length) {
        return;
    }

    if (!parseInt($from.data('event_init'))) {
        $from.data('event_init', 1);
        $from.datetimepicker({
            useCurrent: false //Important! See issue #1075
        });
        $from.on("dp.change", function (e) {
            if (e.date) {
                $to.data("DateTimePicker").minDate(e.date);
            }
        });
    }

    if (!parseInt($to.data('event_init'))) {
        $to.data('event_init', 1);
        $to.on("dp.change", function (e) {
            if (e.date) {
                $from.data("DateTimePicker").maxDate(e.date);
            }
        });
    }
}

function setSearchListOptionsEvents($container) {
    if (!$container.length) {
        return;
    }

    if (parseInt($container.data('event_init'))) {
        return;
    }

    var $parent = $container.parent();
    var $switch = $container.find('.switchInputContainer');
    if ($switch.length) {
        var input_name = $switch.data('input_name');
        var $input = $parent.find('.search_list_input');
        var current_value = $input.val();
        if (input_name) {
            $switch.find('[name=' + input_name + ']').change(function () {
                var option = $(this).val();
                var fields_search = $container.find('#searchList_' + option + '_fields_search').val();
                var help = $container.find('#searchList_' + option + '_help').val();
                if (typeof (help) === 'undefined') {
                    help = '';
                }
                var join = $container.find('#searchList_' + option + '_join').val();
                if (typeof (join) === 'undefined') {
                    join = '';
                }
                var join_on = $container.find('#searchList_' + option + '_join_on').val();
                if (typeof (join_on) === 'undefined') {
                    join_on = '';
                }
                var join_return_label = $container.find('#searchList_' + option + '_join_return_label').val();
                if (typeof (join_return_label) === 'undefined') {
                    join_return_label = '';
                }
                var filters = $container.find('#searchList_' + option + '_filters').val();
                if (typeof (filters) === 'undefined') {
                    filters = '';
                }

                $input.val('');
                $input.data('fields_search', fields_search);
                $input.data('join', join);
                $input.data('join_on', join_on);
                $input.data('join_return_label', join_return_label);
                $input.data('filters', filters);
                if (help) {
                    if (!$parent.find('.inputHelp').length) {
                        $input.after('<p class="inputHelp">' + help + '</p>');
                    } else {
                        $parent.find('.inputHelp').text(help);
                    }
                } else {
                    $parent.find('.inputHelp').remove();
                }
            }).change();
            $input.val(current_value);
        }

        $container.data('event_init', 1);
    }
}

$(document).ready(function () {
    $('.object_form').each(function () {
        onFormLoaded($(this));
    });

    $.datepicker.setDefaults($.datepicker.regional[ "fr" ]);

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_form').each(function () {
                onFormLoaded($(this));
            });
        }
    });
});