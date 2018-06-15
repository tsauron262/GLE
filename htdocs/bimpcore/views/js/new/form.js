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

function saveObjectFromForm(form_id, $button, successCallback, on_save) {
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

    if (!validateForm($form)) {
        return;
    }

    var data = new FormData($formular.get(0));

    if (typeof (on_save) === 'undefined') {
        on_save = $form.data('on_save');
    }

    BimpAjax('saveObject', data, $resultContainer, {
        $form: $form,
        form_id: form_id,
        $button: $button,
        on_save: on_save,
        processData: false,
        contentType: false,
        display_success_in_popup_only: true,
        display_processing: true,
        processing_padding: 10,
        success: function (result, bimpAjax) {
            if (!result.warnings.length) {
                bimpAjax.$resultContainer.slideUp(250, function () {
                    $(this).html('');
                });
                switch (bimpAjax.on_save) {
                    case 'reload':
                        if ((typeof (result.object_view_url) !== 'undefined') && result.object_view_url) {
                            var $link = bimpAjax.$button.parent().find('.objectViewLink');
                            if ($link.length) {
                                $link.removeClass('hidden').attr('href', result.object_view_url);
                            }
                        }
                        reloadForm(bimpAjax.form_id);
                        break;

                    case 'close':
                        closeForm(bimpAjax.form_id);
                        break;

                    case 'none':
                        break;
                }
            } else {
                bimpAjax.display_warnings_in_popup_only = true;
                bimpAjax.display_result_warnings(result.warnings);
                bimpAjax.display_warnings_in_popup_only = false;
            }
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}

function loadModalForm($button, data, title, successCallback) {
    if (typeof (title) === 'undefined' || !title) {
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
    }

    if (typeof (data.id_object) === 'undefined') {
        data.id_object = 0;
    }
    data.full_panel = 0;

    bimpModal.loadAjaxContent($button, 'loadObjectForm', data, title, 'Chargement du formulaire', function (result, bimpAjax) {
        var $form = bimpAjax.$resultContainer.find('.object_form');
        var modal_idx = parseInt(bimpAjax.$resultContainer.data('idx'));
        $form.data('modal_idx', modal_idx);
        bimpModal.removeComponentContent($form.attr('id'));
        bimpModal.addButton('<i class="fa fa-save iconLeft"></i>Enregistrer', 'saveObjectFromForm(\'' + result.form_id + '\', $(this));', 'primary', 'save_object_button', modal_idx);
        bimpModal.addlink('<i class="fa fa-file-o iconLeft"></i>Afficher', '', 'primary', 'hidden objectViewLink', modal_idx);

        if ($form.length) {
            $form.each(function () {
                onFormLoaded($form);
            });
        }

        if (typeof (successCallback) === 'function') {
            successCallback($form);
        }
    }, {
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé'
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

    var $panel = $('#' + form_id + '_panel');
    var $modal = $();
    if (!$.isOk($panel)) {
        if ($form.data('modal_idx')) {
            $modal = $form.findParentByClass('modal');
            if ($modal.length) {
                $modal.find('.loading-text').text('Chargement du formulaire');
                $modal.find('.content-loading').show();
            }
        }
    }

    BimpAjax('loadObjectForm', data, null, {
        $form: $form,
        $panel: $panel,
        $modal: $modal,
        display_success: false,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être rechargé',
        success: function (result, bimpAjax) {
            if (result.form_id && result.html) {
                if ($.isOk(bimpAjax.$form)) {
                    if ($.isOk(bimpAjax.$panel)) {
                        bimpAjax.$panel.find('.panel-footer').find('.save_object_button').removeClass('disabled');
                    }

                    if ($.isOk(bimpAjax.$modal)) {
                        bimpAjax.$modal.find('.content-loading').hide();
                        bimpAjax.$modal.find('.loading-text').text('');
                        var modal_idx = parseInt(bimpAjax.$form.data('modal_idx'));
                        if (modal_idx) {
                            bimpAjax.$modal.find('.modal-footer').find('.save_object_button.modal_' + modal_idx).removeClass('disabled');
                        }
                    }

                    bimpAjax.$form.find('.object_form_content').html(result.html).slideDown(function () {
                        setFormEvents(bimpAjax.$form);
                        setCommonEvents(bimpAjax.$form);
                    });
                }
            }
        }
    });
}

function closeForm(form_id) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var $modal = $form.findParentByClass('modal');
    if ($.isOk($modal)) {
        var modal_idx = parseInt($form.data('modal_idx'));
        if (modal_idx) {
            bimpModal.removeContent(modal_idx);
        }
        bimpModal.hide();
    } else {
        var $container = $('#' + form_id + '_container');
        if ($container.length) {
            $container.slideUp(250, function () {
                $(this).remove();
            });
        }
    }
}

function submitForm(form_id) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var $modal = $form.findParentByClass('modal');
    if ($.isOk($modal)) {
        var modal_idx = parseInt($form.data('modal_idx'));
        var $btn = $modal.find('.modal-footer').find('.save_object_button.modal_' + modal_idx);
        if ($btn.length) {
            $btn.click();
        } else {
            $btn = $modal.find('.modal-footer').find('.set_action_button.modal_' + modal_idx);
            if ($btn.length) {
                $btn.click();
            }
        }
    } else {
        var $container = $('#' + form_id + '_container');
        if ($container.length) {
            var $btn = $container.find('.save_object_button');
            if ($btn.length) {
                $btn.click();
            } else {
                $btn = $container.find('.set_action_button');
                if ($btn.length) {
                    $btn.click();
                }
            }
        }
    }
}

function loadObjectFormFromForm(title, result_input_name, parent_form_id, module, object_name, form_name, id_parent, reload_input, $button, values) {
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
        var modal_idx = $form.data('modal_idx');
        if (modal_idx) {
            $parentFormSubmit = bimpModal.$footer.find('.save_object_button.modal_' + modal_idx);
        }
    }

    var data = {
        'module': module,
        'object_name': object_name,
        'form_name': form_name,
        'id_object': 0,
        'id_parent': id_parent
    };

    if (typeof (values) !== 'undefined' && values) {
        data['param_values'] = values;
    }

    BimpAjax('loadObjectForm', data, null, {
        display_success: false,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé',
        $button: $button,
        $parentForm: $form,
        $resultContainer: $resultContainer,
        title: title,
        $parentFormSubmit: $parentFormSubmit,
        result_input_name: result_input_name,
        reload_input: reload_input,
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
                                                if (bimpAjax.reload_input) {
                                                    var fields = {};
                                                    bimpAjax.$parentForm.find('.inputContainer').each(function () {
                                                        var field_name = $(this).data('field_name');
                                                        if (field_name && (field_name !== bimpAjax.result_input_name)) {
                                                            var $input = $(this).find('[name="' + field_name + '"]');
                                                            if ($input.length) {
                                                                fields[field_name] = $input.val();
                                                            }
                                                        }
                                                    });
                                                    fields[bimpAjax.result_input_name] = saveResult.id_object;
                                                    reloadObjectInput(bimpAjax.$parentForm.attr('id'), bimpAjax.result_input_name, fields);
                                                } else {
                                                    var $resultInput = bimpAjax.$parentForm.find('[name="' + bimpAjax.result_input_name + '"]');
                                                    if ($resultInput.length) {
                                                        $resultInput.val(saveResult.id_object).change();
                                                    }
                                                }
                                            }
                                        });
                                    });
                                }, 'none');
                            });
                        }
                    });
                });
            }
        }
    });
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

function validateForm($form) {

    var data_missing = false;
    $form.find('.inputContainer').each(function () {
        var $template = $(this).findParentByClass('subObjectFormTemplate');
        if (!$.isOk($template)) {
            var field_name = $(this).data('field_name');

            if (field_name) {
                // Patch: (problème avec l'éditeur html => le textarea n'est pas alimenté depuis l'éditeur) 
                if ($(this).find('.cke').length) {
                    var html_value = $('#cke_' + field_name).find('iframe').contents().find('body').html();
                    $(this).find('[name="' + field_name + '"]').val(html_value);
                }

                if (parseInt($(this).data('required'))) {
                    if (parseInt($(this).data('multiple'))) {
                        if ($(this).find('.check_list_container').length) {
                            var check = false;
                            $(this).find('.check_list_container').find('.check_list_item').each(function () {
                                if ($(this).find('[name="' + field_name + '[]"]').prop('checked')) {
                                    check = true;
                                }
                            });
                            if (!check) {
//                                bimp_msg($(this).data('field_name'));
                                $(this).addClass('value_required');
                                data_missing = true;
                            } else {
                                $(this).removeClass('value_required');
                            }
                        }
                    } else {
                        var $input = $(this).find('[name="' + field_name + '"]');
                        if ($input.length) {
                            var data_type = $(this).data('data_type');
                            if (data_type && (data_type === 'id_object')) {
                                if (!parseInt($input.val()) || parseInt($input.val()) <= 0) {
//                                    bimp_msg($input.attr('name'));
                                    data_missing = true;
                                    $(this).addClass('value_required');
                                } else {
                                    $(this).removeClass('value_required');
                                }
                            } else {
                                if ($input.val() === '') {
//                                    bimp_msg($input.attr('name'));
                                    data_missing = true;
                                    $(this).addClass('value_required');
                                } else {
                                    $(this).removeClass('value_required');
                                }
                            }
                        }
                    }
                }
            }
        }
    });
    $form.find('.inputMultipleValuesContainer').each(function () {
        var $template = $(this).findParentByClass('subObjectFormTemplate');
        if (!$.isOk($template)) {
            if (parseInt($(this).data('required'))) {
                if (!$(this).find('.inputMultipleValues').find('tr.itemRow').length) {
                    data_missing = true;
//                    bimp_msg($(this).data('field_name'));
                    $(this).addClass('value_required');
                } else {
                    $(this).removeClass('value_required');
                }
            }
        }
    });
    var check = true;
    if (data_missing) {
        check = false;
        bimp_msg('Certains champs obligatoires ne sont pas renseignés', 'danger');
    }

    return check;
}

function reloadObjectInput(form_id, input_name, fields) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var custom = 0;
    var is_object = 0;
    var value = '';

    var $container = $form.find('.' + input_name + '_inputContainer');

    if ($container.length) {
        if ($container.hasClass('customField')) {
            custom = 1;
        }

        value = $container.find('[name="' + input_name + '"]').val();
        if (typeof (value) === 'undefined') {
            value = '';
        }
    } else {
        $container = $form.find('#' + input_name + '_subObjectsContainer');
        if ($container.length) {
            is_object = 1;
        } else {
            bimp_msg('Erreur: champ "' + input_name + '" non trouvé');
        }
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
        custom_field: custom,
        value: value,
        field_prefix: $container.data('field_prefix'),
        is_object: is_object
    };

    if (custom) {
        data['form_row'] = $container.data('form_row');
    }

    BimpAjax('loadObjectInput', data, $container, {
        $form: $form,
        $container: $container,
        input_name: input_name,
        error_msg: 'Echec du chargement du champ',
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined') {
                var $form = $('#' + result.form_id);
                var $parent = bimpAjax.$container.parent();
                $parent.html(result.html).slideDown(250, function () {
                    $parent.removeAttr('style');
                    setCommonEvents($parent);
                    setInputsEvents($parent);
                    var $input = bimpAjax.$form.find('[name=' + bimpAjax.input_name + ']');
                    if ($input.length) {
                        setInputEvents($form, $input);
                        $input.change();
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
        $container = $input.findParentByClass('searchInputContainer');
        if (!$.isOk($container)) {
            bimp_msg('Une erreur est survenue. Impossible d\'effectuer la recherche', 'danger');
            console.error('$container invalide');
            return;
        }
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
                    var $label_input = bimpAjax.$container.find('[name="' + field_name + '_label"]');
                    bimpAjax.$result.find('button').click(function () {
                        $field_input.val($(this).data('value')).change();
                        bimpAjax.$result.html('').hide();
                        var label = $(this).text();
                        if (!bimpAjax.$input.parent().hasClass('searchInputContainer')) {
                            bimpAjax.$input.val('');
                            $container.find('.search_input_selected_label').find('span').text(label);
                            $container.find('.search_input_selected_label').slideDown(250);
                            if ($label_input.length) {
                                $label_input.val(label);
                            }
                        } else {
                            bimpAjax.$input.val(label);
                        }
                    });
                    bimpAjax.$result.show();
//                    bimpAjax.$result.off('mouseleave');
//                    bimpAjax.$result.mouseenter(function () {
//                        $(this).off('mouseenter');
//                        $(this).mouseleave(function () {
//                            $(this).slideUp(250);
//                        });
//                    });
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

    var value = '';
    var label = '';

    if ($value_input.length) {
        value = $value_input.val();
    }
    if ($label_input.length) {
        if ($label_input.tagName() === 'select') {
            label = $label_input.find('[value="' + value + '"]').text();
        } else {
            label = $label_input.val();
        }
    }

    if (value || value === 0) {
        var values_field_name = $inputContainer.data('values_field');
        var check = true;
        $container.find('.multipleValuesList').find('input[name="' + values_field_name + '[]"]').each(function () {
            if ($(this).val() == value) {
                bimp_msg('Cet élément a déjà été ajouté', 'warning');
                check = false;
            }
        });

        if (!check) {
            return;
        }

        if (!label) {
            if ($value_input.get(0).tagName.toLowerCase() === 'select') {
                label = $value_input.find('[value="' + value + '"]').text();
            } else {
                label = value;
            }
        }

        var html = '<tr class="itemRow">';
        html += '<td style="display: none"><input class="item_value" type="hidden" value="' + value + '" name="' + values_field_name + '[]"/></td>';
        html += '<td>' + label + '</td>';
        html += '<td style="width: 62px"><button type="button" class="btn btn-light-danger iconBtn"';
        html += ' onclick="';
        if (ajax_save) {
            html += 'var $button = $(this); deleteObjectMultipleValuesItem(\'' + $container.data('module') + '\', ';
            html += '\'' + $container.data('object_name') + '\', ';
            html += $container.data('id_object') + ', \'' + values_field_name + '\', \'' + value + '\', null, ';
            html += 'function(){removeMultipleInputValue($button, \'' + value_input_name + '\');});';
        } else {
            html += 'removeMultipleInputValue($(this), \'' + value_input_name + '\');';
        }
        html += '"><i class="fa fa-trash"></i></button></td>';
        html += '</tr>';

        $value_input.val('');
        $label_input.val('');

        if (ajax_save) {
            addObjectMultipleValuesItem($container.data('module'), $container.data('object_name'), $container.data('id_object'), values_field_name, value, null, function () {
                $container.find('table').find('tbody.multipleValuesList').append(html);
                checkMultipleValues();
                $('body').trigger($.Event('inputMultipleValuesChange', {
                    input_name: values_field_name,
                    $container: $container
                }));
            });
        } else {
            $container.find('table').find('tbody.multipleValuesList').append(html);
            checkMultipleValues();
            $('body').trigger($.Event('inputMultipleValuesChange', {
                input_name: values_field_name,
                $container: $container
            }));
        }
    } else {
        bimp_msg('Une erreur est survenue. opération impossible', 'danger');
    }
}

function removeMultipleInputValue($button, value_input_name) {
    var $multipleValues = $button.findParentByClass('inputMultipleValuesContainer');

    $button.parent('td').parent('tr').fadeOut(250, function () {
        $(this).remove();
        checkMultipleValues();
        $('body').trigger($.Event('inputMultipleValuesChange', {
            input_name: $multipleValues.data('field_name'),
            $container: $multipleValues
        }));
    });
}

function checkMultipleValues() {
    $('.inputMultipleValuesContainer').each(function () {
        var $container = $(this);
        if ($(this).find('.itemRow').length) {
            $(this).find('.noItemRow').hide();
        } else {
            $(this).find('.noItemRow').show();
        }
        var $inputContainer = $container.parent().find('.inputContainer');
        if ($inputContainer.length) {
            var input_name = $inputContainer.data('field_name');
            if (input_name) {
                var $input = $inputContainer.find('[name="' + input_name + '"]');
                if ($input.length) {
                    if ($input.tagName() === 'select') {
                        $input.find('option').show();
                        $container.find('.itemRow').each(function () {
                            $input.find('option[value="' + $(this).find('.item_value').val() + '"]').hide();
                        });
                        var show_input = false;
                        $input.find('option').each(function () {
                            if ($(this).css('display') !== 'none') {
                                show_input = true;
                            }
                        });
                        if (show_input) {
                            $input.show();
                            $container.find('.addValueBtn').parent('div').show();
                        } else {
                            $input.hide();
                            $container.find('.addValueBtn').parent('div').hide();
                        }
                    }
                }
            }
        }
    });
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
                    if (typeof (min) === 'undefined') {
                        min = 'none';
                    }
                    var max = $input.data('max');
                    if (typeof (max) === 'undefined') {
                        max = 'none';
                    }
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
                        if (min !== 'none') {
                            min = parseFloat(min);
                        }
                        if (max !== 'none') {
                            max = parseFloat(max);
                        }
                    } else {
                        value = value.replace(/^(\-?[0-9]*)\.?.*$/, '$1');
                        parsed_value = parseInt(value);
                        if (min !== 'none') {
                            min = parseInt(min);
                        }
                        if (max !== 'none') {
                            max = parseInt(max);
                        }
                    }
                    if (min !== 'none' && parsed_value < min) {
                        value = min;
                        msg = 'Min: ' + min;
                    } else if (max !== 'none' && parsed_value > max) {
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
    bimp_display_element_popover($input, html, 'bottom');
    $input.unbind('blur').blur(function () {
        $input.popover('destroy');
    });
}

function addSubObjectForm($button, object_name) {
    var $container = $button.findParentByClass('subObjects');
    if (!$.isOk($container)) {
        bimp_msg('Erreur technique (container absent)', 'danger');
        return;
    }

    if ($container.attr('id') !== object_name + '_subObjectsContainer') {
        bimp_msg('Erreur technique (container invalide)', 'danger');
        return;
    }

    var $template = $container.find('.subObjectFormTemplate');
    if (!$.isOk($template)) {
        bimp_msg('Erreur technique (template absent)', 'danger');
        return;
    }

    var idx = parseInt($container.find('[name="' + object_name + '_count"]').val());
    idx++;
    var html = $template.html();
    html = html.replace(/sub_object_idx/g, idx);
    $container.find('.subObjectsMultipleForms').append(html);
    $container.find('[name="' + object_name + '_count"]').val(idx);
    var $subForm = $container.find('.subObjectsMultipleForms').find('.subObjectForm').last();
    if ($subForm.length) {
        setFormEvents($subForm);
        setCommonEvents($subForm);
    }
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
        $container.stop().slideDown(250, function () {
            $(this).css('height', 'auto');
        });
    } else {
        var input_name = $container.find('.inputContainer').data('field_name');
        if (input_name) {
            var $input = $container.find('[name="' + input_name + '"]');
            if ($input.length) {
                var initial_value = $container.find('.inputContainer').data('initial_value');
                $input.val(initial_value);
            }
        }
        $container.stop().slideUp(250, function () {
            $(this).css('height', 'auto');
        });
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

        $('body').trigger($.Event('formLoaded', {
            $form: $form
        }));
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

    $form.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        var $input = $(this).find('[name="' + field_name + '"]');
        if ($input.length) {
            if ($input.tagName() !== 'textarea') {
                $input.keyup(function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof (e.no_submit) === 'undefined' || !e.no_submit) {
                            submitForm($form.attr('id'));
                        }
                    }
                });
            }
        }
    });

    $form.find('form').first().submit(function (e) {
        e.preventDefault();
        e.stopPropagation();
        submitForm($form.attr('id'));
    });
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
    $container.find('.texarea_values').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            var field_name = $(this).data('field_name');
            var $textarea = $(this).parent().find('textarea[name="' + field_name + '"]');
            $(this).find('.textarea_value').click(function () {
                var text = $textarea.val();
                if (text) {
                    text += ', ';
                }
                text += $(this).text();
                $textarea.val(text).change();
            });
            $(this).data('event_init', 1);
        }
    });
    $container.find('.qtyInputContainer').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).find('.qtyDown').click(function () {
                var $qtyInputcontainer = $(this).findParentByClass('qtyInputContainer');
                if ($.isOk($qtyInputcontainer)) {
                    $qtyInputcontainer.find('input.qtyInput').focus();
                    inputQtyDown($qtyInputcontainer);
                }
            });
            $(this).find('.qtyUp').click(function () {
                var $qtyInputcontainer = $(this).findParentByClass('qtyInputContainer');
                if ($.isOk($qtyInputcontainer)) {
                    $qtyInputcontainer.find('input.qtyInput').focus();
                    inputQtyUp($qtyInputcontainer);
                }
            });
            $(this).find('input.qtyInput').keyup(function (e) {
                var $qtyInputcontainer = $(this).findParentByClass('qtyInputContainer');
                if ($.isOk($qtyInputcontainer)) {
                    if (e.key === 'Up') {
                        inputQtyUp($qtyInputcontainer);
                    } else if (e.key === 'Down') {
                        inputQtyDown($qtyInputcontainer);
                    } else if (e.key === 'End' || e.key === 'Right') {
                        inputQtyMax($qtyInputcontainer);
                    } else if (e.key === 'Home' || e.key === 'Left') {
                        inputQtyMin($qtyInputcontainer);
                    }
                }
            }).focus(function () {
                $(this).select();
            });

            $(this).data('event_init', 1);
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

    if ($input.hasClass('toggle_value')) {
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