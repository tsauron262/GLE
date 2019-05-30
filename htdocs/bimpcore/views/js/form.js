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

    if (!on_save || typeof (on_save) === 'undefined') {
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
            if (!result.warnings || !result.warnings.length) {
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

                    case 'redirect':
                        if (result.object_view_url) {
                            window.location = result.object_view_url;
                        }
                        break;

                    case 'none':
                        break;
                }
            } else {
                bimpAjax.display_warnings_in_popup_only = true;
                bimpAjax.display_result_warnings(result.warnings);
                bimpAjax.display_warnings_in_popup_only = false;
                bimpAjax.$button.remove();
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

function loadModalForm($button, data, title, successCallback, on_save) {
    if (typeof (on_save) !== 'string') {
        on_save = '';
    }
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
        bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'saveObjectFromForm(\'' + result.form_id + '\', $(this), null, \'' + on_save + '\');', 'primary', 'save_object_button', modal_idx);
        bimpModal.addlink('<i class="far fa5-file iconLeft"></i>Afficher', '', 'primary', 'hidden objectViewLink', modal_idx);

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

    if (parseInt($form.data('no_auto_submit'))) {
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
        bimp_msg('Une erreur est survenue. Impossible de charger le formulaire (1)', 'danger');
        return;
    }

    var $resultContainer = $form.find('#' + parent_form_id + '_result');

    if (!$resultContainer) {
        bimp_msg('Une erreur est survenue. Impossible de charger le formulaire (2)', 'danger');
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
            if (bimpModal.$footer.find('.save_object_button.modal_' + modal_idx).length) {
                $parentFormSubmit = bimpModal.$footer.find('.save_object_button.modal_' + modal_idx);
            } else if (bimpModal.$footer.find('.set_action_button.modal_' + modal_idx).length) {
                $parentFormSubmit = bimpModal.$footer.find('.set_action_button.modal_' + modal_idx);
            }
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
                    html += '<i class="fas fa5-save iconLeft"></i>Enregistrer';
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
                                                var $resultInput = bimpAjax.$parentForm.find('[name="' + bimpAjax.result_input_name + '"]');
                                                if (bimpAjax.reload_input) {
                                                    if ($resultInput.length) {
                                                        $resultInput.val(saveResult.id_object);
                                                    }
                                                    var fields = getInputsValues(bimpAjax.$parentForm);
                                                    var $inputContainer = bimpAjax.$parentForm.find('.' + bimpAjax.result_input_name + '_inputContainer');
                                                    if ($inputContainer.data('multiple') || $inputContainer.find('.check_list_container').length) {
                                                        fields[bimpAjax.result_input_name].push(saveResult.id_object);
                                                    } else {
                                                        fields[bimpAjax.result_input_name] = saveResult.id_object;
                                                    }
                                                    reloadObjectInput(bimpAjax.$parentForm.attr('id'), bimpAjax.result_input_name, fields, 1);
                                                } else {
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
                    if (html_value === '<br>') {
                        html_value = '';
                    }
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

function reloadObjectInput(form_id, input_name, fields, keep_new_value) {
    if (typeof (keep_new_value) === 'undefined') {
        keep_new_value = 1;
    }
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
            bimp_msg('Erreur: champ "' + input_name + '" non trouvé', 'warning');
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
        field_prefix: $container.data('field_prefix'),
        is_object: is_object
    };

    if (keep_new_value) {
        data.value = value;
    }

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
                });
                setCommonEvents($parent);
                setInputContainerEvents($parent.find('.inputContainer'));
                setInputsEvents($parent);
                var $input = bimpAjax.$form.find('[name=' + bimpAjax.input_name + ']');
                if ($input.length) {
                    setInputEvents($form, $input);
                    $input.change();
                }
                $('body').trigger($.Event('inputReloaded', {
                    $form: $form,
                    input_name: bimpAjax.input_name,
                    $input: $input
                }));
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

// Traitements des inputs: 

function getInputsValues($container) {
    var values = {};
    $container.find('.inputContainer').each(function () {
        var field = $(this).data('field_name');
        if ($(this).data('multiple')) {
            field = $(this).data('values_field');
        }
        values[field] = getInputValue($(this));
    });
    return values;
}

function getInputValue($inputContainer) {
    if (!$inputContainer.length) {
        return '';
    }

    var field_name = $inputContainer.data('field_name');
    var multiple = $inputContainer.data('multiple');
    var check_list = $inputContainer.find('.check_list_container').length;
    var value = '';

    if (multiple || check_list) {
        value = [];
        if (check_list) {
            var $inputs = $inputContainer.find('[name="' + field_name + '[]"]');
            $inputs.each(function () {
                if ($(this).attr('type') === 'checkbox') {
                    if ($(this).prop('checked')) {
                        value.push($(this).val());
                    }
                } else {
                    value.push($(this).val());
                }
            });
            if (!value.length) {
                value = 0;
            }
        } else {
            field_name = $inputContainer.data('values_field');
            var $valuesContainer = $inputContainer.parent().find('.inputMultipleValuesContainer');
            if (!$valuesContainer.length) {
                bimp_msg('Erreur: liste de valeurs absente pour le champ "' + field_name + '"', 'danger');
                return;
            } else {
                $valuesContainer.find('[name="' + field_name + '[]"]').each(function () {
                    var val = $(this).val();
                    if (val !== '') {
                        value.push(val);
                    }
                });
            }
        }
    } else {
        if ($inputContainer.find('.cke').length) {
            var html_value = $('#cke_' + field_name).find('iframe').contents().find('body').html();
            $inputContainer.find('[name="' + field_name + '"]').val(html_value);
        }
        value = $inputContainer.find('[name="' + field_name + '"]').val();
    }

    return value;
}

function addMultipleInputCurrentValue($button, value_input_name, label_input_name, ajax_save) {
    if (typeof (ajax_save) === 'undefined') {
        ajax_save = false;
    }

    if ($button.hasClass('disabled')) {
        return;
    }

    var $inputContainer = $button.findParentByClass('inputContainer');
    if (!$inputContainer.length) {
        bimp_msg('Une erreur technique est survenue ("inputContainer" absent). opération impossible', 'danger');
        return;
    }

    var $container = $inputContainer.find('.inputMultipleValuesContainer');
    if (!$container.length) {
        bimp_msg('Une erreur technique est survenue ("inputMultipleValuesContainer" absent). opération impossible', 'danger');
        return;
    }

    var $value_input = $inputContainer.find('[name=' + value_input_name + ']');
    var $label_input = $inputContainer.find('[name=' + label_input_name + ']');

    var max_values = $container.data('max_values');
    if (max_values !== 'none') {
        max_values = parseInt(max_values);

        var $items = $container.find('tbody.multipleValuesList').find('tr.itemRow');
        if ($items.length >= max_values) {
            var msg = 'Vous ne pouvez sélectionner qu\'au maximum ' + max_values + ' élément';
            if (max_values > 1) {
                msg += 's';
            }
            bimp_msg(msg, 'danger');
            return;
        }
    }

    var value = '';
    var label = '';

    if ($value_input.length) {
        value = $value_input.val();
    }
    if ($label_input.length) {
        if ($label_input.tagName() === 'select') {
            label = $label_input.find('[value="' + value + '"]').html();
        } else {
            label = $label_input.val();
        }
    }

    if (value || value === 0) {
        var sortable = parseInt($container.data('sortable'));
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
                label = $value_input.find('[value="' + value + '"]').html();
            } else {
                label = value;
            }
        }

        var html = '<tr class="itemRow">';
        html += '<td style="display: none"><input class="item_value" type="hidden" value="' + value + '" name="' + values_field_name + '[]"/></td>';
        if (sortable) {
            html += '<td class="positionHandle"><span></span></td>';
        }
        html += '<td class="item_label">' + label + '</td>';
        html += '<td class="removeButton"><button type="button" class="btn btn-light-danger iconBtn"';
        html += ' onclick="';
        if (ajax_save) {
            html += 'var $button = $(this); deleteObjectMultipleValuesItem(\'' + $container.data('module') + '\', ';
            html += '\'' + $container.data('object_name') + '\', ';
            html += $container.data('id_object') + ', \'' + values_field_name + '\', \'' + value + '\', null, ';
            html += 'function(){removeMultipleInputValue($button, \'' + value_input_name + '\');});';
        } else {
            html += 'removeMultipleInputValue($(this), \'' + value_input_name + '\');';
        }
        html += '"><i class="fas fa5-trash-alt"></i></button></td>';
        html += '</tr>';

        $value_input.val('');
        $label_input.val('');
        if ($inputContainer.find('.search_input_selected_label').length) {
            $inputContainer.find('.search_input_selected_label').hide().find('span').text('');
        }

        if ($value_input.hasClass('select2-hidden-accessible')) {
            $inputContainer.find('.select2-selection__rendered').html('');
        }

        if (ajax_save) {
            addObjectMultipleValuesItem($container.data('module'), $container.data('object_name'), $container.data('id_object'), values_field_name, value, null, function () {
                $container.find('table').find('tbody.multipleValuesList').append(html);
                if (sortable) {
                    setSortableMultipleValuesHandlesEvents($container);
                }
                checkMultipleValues();
                $('body').trigger($.Event('inputMultipleValuesChange', {
                    input_name: values_field_name,
                    $container: $container
                }));
            });
        } else {
            $container.find('table').find('tbody.multipleValuesList').append(html);
            if (sortable) {
                setSortableMultipleValuesHandlesEvents($container);
            }
            checkMultipleValues();
            $('body').trigger($.Event('inputMultipleValuesChange', {
                input_name: values_field_name,
                $container: $container
            }));
        }
    } else {
        bimp_msg('Veuillez sélectionner une valeur', 'warning');
    }
}

function removeMultipleInputValue($button, value_input_name) {
    var $inputContainer = $button.findParentByClass('inputContainer');
    var $multipleValues = $inputContainer.find('.inputMultipleValuesContainer');

    $button.parent('td').parent('tr').fadeOut(250, function () {
        $(this).remove();
        checkMultipleValues();
        $('body').trigger($.Event('inputMultipleValuesChange', {
            input_name: $inputContainer.data('field_name'),
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
        var $inputContainer = $container.findParentByClass('inputContainer');
        if ($inputContainer.length) {
            var input_name = $inputContainer.data('field_name') + '_add_value';
            if (input_name) {
                var $input = $inputContainer.find('[name="' + input_name + '"]');
                if ($input.length) {
                    if ($input.tagName() === 'select') {
                        $inputContainer.find('.addValueInputContainer').find('button.addValueBtn').show();
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
                            if ($input.hasClass('select2-hidden-accessible')) {
                                $inputContainer.find('.select2-container').show();
                            } else {
                                $input.show();
                            }
                            $inputContainer.find('.addValueInputContainer').show();
                        } else {
                            if ($input.hasClass('select2-hidden-accessible')) {
                                $inputContainer.find('.select2-container').hide();
                            } else {
                                $input.hide();
                            }
                            $inputContainer.find('.addValueInputContainer').hide();
                        }
                        $inputContainer.find('.select2-selection__rendered').html('');
                    }
                } else {
                    $inputContainer.find('.addValueInputContainer').find('button.addValueBtn').hide();
                }
            }
        }
    });
}

function checkTextualInput($input, skip_min) {
    if (typeof (skip_min) === 'undefined') {
        skip_min = false;
    }
    if ($input.length) {
        var do_not_change_value = false;
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

                    var is_neg = /^\-.*$/.test(value);
                    value = value.replace(/[^0-9\.,]/g, '');
                    value = value.replace(',', '.');
                    if (is_neg) {
                        if (unsigned || (parseFloat(min) >= 0)) {
                            if (min == 0) {
                                msg = 'Min: 0';
                                if (value !== '') {
                                    value = '-' + value;
                                }
                            } else {
                                msg = 'Nombres négatifs interdits';
                                break;
                            }
                        } else {
                            value = '-' + value;
                        }
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
                        if (skip_min) {
                            do_not_change_value = true;
                        }
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

            if (('' + value) !== ('' + initial_value) && !do_not_change_value) {
                $input.val(value).change();
            }
            if (msg) {
                displayInputMsg($input, msg, 'info');
            } else {
                bimp_destroy_element_popover($input);
            }
        }
    }
}

function checkTotalMaxQtyInput($input) {
    if ($.isOk($input) && $input.hasClass('total_max')) {
        var total_max_value = $input.data('total_max_value');
        var inputs_class = $input.data('total_max_inputs_class');

        if (typeof (total_max_value) !== 'undefined' && typeof (inputs_class) !== 'undefined') {
            total_max_value = parseFloat(total_max_value);
            if (!isNaN(total_max_value) && inputs_class !== '') {
                var $inputsContainer = $input.findParentByClass(inputs_class + '_container');
                if ($.isOk($inputsContainer)) {
                    var total_set = 0;
                    var $inputs = $inputsContainer.find('input.' + inputs_class);
                    $inputs.each(function () {
                        var val = parseFloat($(this).val());
                        if (!isNaN(val)) {
                            total_set += val;
                        }
                    });

                    if (total_set > total_max_value) {
                        var diff = total_set - total_max_value;
                        var cur_val = parseFloat($input.val());
                        if (isNaN(cur_val)) {
                            cur_val = 0;
                        }
                        cur_val -= diff;
                        $input.val(cur_val).change();
                    } else {
                        var remain = total_max_value - total_set;
                        $inputs.each(function () {
                            var val = parseFloat($(this).val());
                            if (isNaN(val)) {
                                val = 0;
                            }
                            var max = remain + val;
                            $(this).data('max', max);
                            var $label = $(this).parent().find('.max_label');
                            if ($label.length) {
                                $label.text('Max: ' + max);
                            }
                        });
                    }
                }
            }
        }
    }
}

function checkTotalMinQtyInput($input) {
    if ($.isOk($input) && $input.hasClass('total_min')) {
        var total_min_value = $input.data('total_min_value');
        var inputs_class = $input.data('total_min_inputs_class');

        if (typeof (total_min_value) !== 'undefined' && typeof (inputs_class) !== 'undefined') {
            total_min_value = parseFloat(total_min_value);
            if (!isNaN(total_min_value) && inputs_class !== '') {
                var $inputsContainer = $input.findParentByClass(inputs_class + '_container');
                if ($.isOk($inputsContainer)) {
                    var total_set = 0;
                    var $inputs = $inputsContainer.find('input.' + inputs_class);
                    $inputs.each(function () {
                        var val = parseFloat($(this).val());
                        if (!isNaN(val)) {
                            total_set += val;
                        }
                    });

                    if (total_set < total_min_value) {
                        var diff = total_min_value - total_set;
                        var cur_val = parseFloat($input.val());
                        if (isNaN(cur_val)) {
                            cur_val = 0;
                        }
                        cur_val += diff;
                        $input.val(cur_val).change();
                    } else {
                        var remain = total_set - total_min_value;
                        $inputs.each(function () {
                            var val = parseFloat($(this).val());
                            if (isNaN(val)) {
                                val = 0;
                            }
                            var min = val - remain;
                            $(this).data('min', min);
                            var $label = $(this).parent().find('.min_label');
                            if ($label.length) {
                                $label.text('Min: ' + min);
                            }
                        });
                    }
                }
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
//    $input.unbind('blur').blur(function () {
//        if ($input.hasClass('bs-popover')) {
//            $input.removeClass('bs-popover');
//            $input.popover('destroy');
//            bimp_msg('here');
//        }
//    });
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

    $('body').trigger($.Event('subObjectFormAdded', {
        $subForm: $subForm,
        id_form: $container.findParentByClass('object_form').data('identifier'),
        idx: idx
    }));
}

function removeSubObjectForm($button) {
    var $container = $button.findParentByClass('formInputGroup');

    if ($.isOk($container)) {
        var $form = $container.findParentByClass('object_form');

        var id_form = $form.data('identifier');
        var object_name = $container.data('object_name');
        var idx = $container.data('idx');

        $container.remove();

        $('body').trigger($.Event('subObjectFormRemoved', {
            id_form: id_form,
            object_name: object_name,
            idx: idx
        }));
    } else {
        bimp_msg('Une erreur est survenue, opération abandonnée (Conteneur absent ou invalide)', 'danger');
    }
}

function searchZipTown($input) {
    if (!$.isOk($input)) {
        return;
    }

    var field_type = $input.data('field_type');
    if (!field_type) {
        return;
    }

    var data = {};
    data[field_type] = $input.val();

    $input.parent().find('.loading').show();

    BimpAjax('searchZipTown', data, null, {
        $input: $input,
        field_type: field_type,
        display_success: false,
        display_errors: false,
        display_warnings: false,
        success: function (result, bimpAjax) {
            $input.parent().find('.loading').hide();
            if (result.html) {
                $input.parent().parent().find('.searchZipTownResults').html(result.html).show();
            } else {
                $input.parent().parent().find('.searchZipTownResults').html('').hide();
            }
        },
        error: function () {
            $input.parent().find('.loading').hide();
        }
    });
}

function selectZipTown($button) {
    var town = $button.data('town');
    var zip = $button.data('zip');
    var state = $button.data('state');
    var country = $button.data('country');

    var $container = $button.findParentByClass('searchZipTownResults');
    $container.html('').hide();
    var $input = $container.parent().find('input.search_ziptown');

    var field_type = $input.data('field_type');

    if (zip && field_type === 'zip') {
        $input.val(zip);
    }
    if (town && field_type === 'town') {
        $input.val(town);
    }

    var $form = $input.findParentByTag('form');
    if ($.isOk($form)) {
        var town_field = $input.data('town_field');
        var zip_field = $input.data('zip_field');
        var state_field = $input.data('state_field');
        var country_field = $input.data('country_field');

        if (town_field && town) {
            var $townInput = $form.find('[name="' + town_field + '"]');
            if ($townInput.length) {
                $townInput.val(town).change();
            }
        }

        if (zip_field && zip) {
            var $zipInput = $form.find('[name="' + zip_field + '"]');
            if ($zipInput.length) {
                $zipInput.val(zip).change();
            }
        }

        if (state_field && state) {
            var $stateInput = $form.find('[name="' + state_field + '"]');
            if ($stateInput.length) {
                $stateInput.val(state).change();
            }
        }

        if (country_field && country) {
            var $countryInput = $form.find('[name="' + country_field + '"]');
            if ($countryInput.length) {
                $countryInput.val(country).change();
            }
        }
    }
}

function resetInputValue($container) {
    var input_name = $container.data('field_name');

    if (!input_name) {
        return;
    }
    var initial_value = $container.data('initial_value');

    if (typeof (initial_value) !== 'undefined') {
        if (typeof (initial_value) === 'string' && initial_value) {
            initial_value = bimp_htmlDecode(initial_value);
        }

        var $input = $container.find('[name="' + input_name + '"]');
        if ($input.length) {
            $input.val(initial_value);

            if (initial_value === 0 || initial_value === '') {
                $container.find('.ui-autocomplete-input').val('');
                if ($container.find('.search_list_input').length) {
                    $container.find('.search_list_input').val('');
                    $container.find('.search_input_selected_label').hide().find('span').html('');
                }
            }
        }
    }
}

function checkCheckList($container) {
    var max = $container.data('max');

    if (typeof (max) !== 'undefined' && max !== 'none') {
        max = parseInt(max);

        var $selected = $container.find('.check_list_item_input:checked');

        if ($selected.length > max) {
            $container.find('span.check_list_nb_items_to_unselect').text($selected.length - max);
            $container.find('.check_list_max_alert').stop().slideDown(250);
        } else {
            $container.find('.check_list_max_alert').stop().slideUp(250);
        }
    }

}

function onCheckListMaxInputChange($container, $input) {
    if ($.isOk($container) && $.isOk($input)) {
        var max = parseInt($input.val());
        if (!isNaN(max)) {
            if (parseInt($container.data('max_input_abs'))) {
                max = Math.abs(max);
            }
            max = parseInt(max);
            $container.data('max', max);
            $container.find('.check_list_max_label').text(max);
            checkCheckList($container);
        }
    }
}

// Gestion de l'affichage conditionnel des champs: 

function toggleInputDisplay($container, $input) {
    var input_val = $input.val();
    var show = false;

    var show_values = $container.data('show_values');
    var hide_values = $container.data('hide_values');

    if (typeof (show_values) !== 'undefined') {
        show_values += '';
        show_values = show_values.split(',');
        for (var i in show_values) {
            if (input_val == show_values[i]) {
                show = true;
                break;
            }
        }
    } else if (typeof (hide_values) !== 'undefined') {
        show = true;
        hide_values += '';
        hide_values = hide_values.split(',');
        for (var i in hide_values) {
            if (input_val == hide_values[i]) {
                show = false;
                break;
            }
        }
    } else {
        var inputs_names = $container.data('inputs_names');
        if (inputs_names) {
            inputs_names += '';
            inputs_names = inputs_names.split(',');
            var $form = $container.findParentByClass('object_form');
            if ($.isOk($form)) {
                show = false;
                var hide = false;
                for (var i in inputs_names) {
                    $input = $form.find('[name="' + inputs_names[i] + '"]');
                    if ($input.length) {
                        input_val = $input.val();
                        show_values = $container.data('show_values_' + inputs_names[i]);
                        hide_values = $container.data('hide_values_' + inputs_names[i]);

                        if (typeof (show_values) !== 'undefined') {
                            show_values += '';
                            show_values = show_values.split(',');
                            for (var j in show_values) {
                                if (input_val == show_values[j]) {
                                    show = true;
                                    break;
                                }
                            }
                        }
                        if (typeof (hide_values) !== 'undefined') {
                            hide_values += '';
                            hide_values = hide_values.split(',');
                            for (var j in hide_values) {
                                if (input_val == hide_values[j]) {
                                    hide = true;
                                    break;
                                }
                            }
                        }
                    }
                }
                if (hide) {
                    show = false;
                }
            }
        }
    }

    if (show) {
        $container.stop().slideDown(250, function () {
            $(this).css('height', 'auto');
        });
    } else {
        resetInputValue($container);
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
        } else {
            var inputs_names = $display_if.data('inputs_names');
            if (inputs_names) {
                inputs_names += '';
                inputs_names = inputs_names.split(',');
                for (var i in inputs_names) {
                    var $input = $form.find('[name="' + inputs_names[i] + '"]');
                    if ($input.length) {
                        if ($input.attr('type') === 'radio') {
                            $input = $form.find('input[name=' + inputs_names[i] + ']:checked');
                        }
                        toggleInputDisplay($display_if, $input);
                    }
                }
            }
        }
    });
}

// Gestion des événements: 

function onFormLoaded($form) {
    if (!$form.length) {
        return;
    }

    if (!parseInt($form.data('loaded_event_processed'))) {
        $form.data('loaded_event_processed', 1);

        $form.find('.subObjectFormTemplate').each(function () {
            $(this).find('.inputContainer').each(function () {
                var field_name = $(this).data('field_name');
                if (field_name) {
                    var className = field_name.replace(/^(.*_)sub_object_idx_(.*)$/, '$1$2') + '_input';
                    if (className) {
                        $(this).find('[name="' + field_name + '"]').addClass(className);
                    }
                }
            });
        });

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
        } else {
            var inputs_names = $container.data('inputs_names');
            if (inputs_names) {
                inputs_names += '';
                inputs_names = inputs_names.split(',');
                for (var i in inputs_names) {
                    var $input = $form.find('[name="' + inputs_names[i] + '"]');
                    if ($input.length) {
                        $input.change(function () {
                            toggleInputDisplay($container, $(this));
                        });
                    }
                }
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
        setInputContainerEvents($(this));
    });

    $form.find('form').first().submit(function (e) {
        e.preventDefault();
        e.stopPropagation();
        submitForm($form.attr('id'));
    });
}

function setInputContainerEvents($inputContainer) {
    if (!$.isOk($inputContainer)) {
        return;
    }

    if (!$inputContainer.hasClass('inputContainer')) {
        return;
    }

    if (parseInt($inputContainer.data('input_container_events_init'))) {
        return;
    }


    var $form = $inputContainer.findParentByClass('object_form');
    var field_name = $inputContainer.data('field_name');
    var data_type = $inputContainer.data('data_type');
    var $input = $inputContainer.find('[name="' + field_name + '"]');
    if ($input.length) {
        if ($.isOk($form)) {
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

        if (data_type && data_type === 'id_object') {
            $input.change(function () {
                var $input = $(this);
                var $container = $input.findParentByClass('inputContainer');
                if ($.isOk($container)) {
                    var display_card_mode = $container.data('display_card_mode');
                    if (display_card_mode && display_card_mode !== 'none') {
                        var $cardContainer = null;
                        var callback = null;
                        if (display_card_mode === 'hint') {
                            var $hint = $container.find('.input_hint');
                            if ($isOk($hint)) {
                                $hint.popover('destroy').hide();
                            } else {
                                $input.after('<span class="input_hint" style="display: none"></span>');
                            }
                            var $div = $container.find('div.hiddenCardContainer');
                            if (!$.isOk($div)) {
                                $container.append('<div class="hiddenCardContainer" style="display: none"></div>');
                                $div = $container.find('div.hiddenCardContainer');
                            }
                            $div.html('<div class="input_card_container"></div>');
                            $cardContainer = $div.find('.input_card_container');
                            callback = function (result, bimpAjax) {
                                if (result.html()) {
                                    if ($.isOk(bimpAjax.resultContainer)) {
                                        var $hint = bimpAjax.resultContainer.find('.input_hint');
                                        if ($.isOk($hint)) {
                                            $hint.show().popover({
                                                container: 'body',
                                                content: result.html,
                                                html: true,
                                                placement: 'bottom',
                                                trigger: 'hover'
                                            });
                                        }
                                    }
                                }
                            };
                        } else if (display_card_mode === 'visible') {
                            $cardContainer = $container.find('.input_card_container');
                            if (!$.isOk($cardContainer)) {
                                $container.append('<div class="input_card_container"></div>');
                                $cardContainer = $container.find('.input_card_container');
                            }
                        }
                        if ($.isOk($cardContainer)) {
                            var id_object = parseInt($input.val());
                            var module = $container.data('object_module');
                            var object_name = $container.data('object_name');
                            var card_name = $container.data('card');
                            if (module && object_name && card_name && id_object && !isNaN(id_object)) {
                                loadObjectCard($cardContainer, module, object_name, id_object, card_name);
                            } else {
                                $cardContainer.stop().slideUp(250, function () {
                                    $(this).html('');
                                });
                            }
                        }
                    }
                }
            });
        }
    }

    setSelectDisplayHelpEvents($(this), $input);

    $inputContainer.data('input_container_events_init', 1);
}

function setInputsEvents($container) {
    var in_modal = $.isOk($container.findParentByClass('modal'));
    $container.find('select').each(function () {
        if (!$(this).data('select_2_converted')) {
            if (!$.isOk($(this).findParentByClass('subObjectFormTemplate')) &&
                    !$(this).hasClass('no_select2')) {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }

                var dropdownCssClass = 'ui-dialog';
                if (in_modal) {
                    dropdownCssClass += ' modal-ui-dialog';
                }
                var options = {
                    dir: 'ltr',
                    width: 'resolve',
                    minimumResultsForSearch: 15,
                    minimumInputLength: 0,
                    language: select2arrayoflanguage,
                    containerCssClass: ':all:',
                    dropdownCssClass: dropdownCssClass,
                    templateResult: function (data, container) {
                        if (data.element) {
                            $(container).addClass($(data.element).attr("class"));
                        }

                        if (data.loading) {
                            return data.text;
                        }

                        var $option = $(data.element);

                        if ($option.css('display') === 'none') {
                            $(container).remove();
                            return;
                        }

                        if ($option.data('html')) {
                            return htmlEntityDecodeJs($(data.element).attr("data-html"));
                        }

                        var html = '<span style="';
                        if ($option.data('color')) {
                            html += 'color: #' + $option.data('color') + '; font-weight: bold;';
                        }
                        html += '">';
                        if ($option.data('icon_class')) {
                            html += '<i class="' + $option.data('icon_class') + ' iconLeft"></i>';
                        }

                        html += data.text + '</span>';
                        return html;
                    },
                    templateSelection: function (selection) {
                        var $option = $(selection.element);

                        var html = '<span style="';
                        if ($option.data('color')) {
                            html += 'color: #' + $option.data('color') + '; font-weight: bold;';
                        }
                        html += '">';
                        if ($option.data('icon_class')) {
                            html += '<i class="' + $option.data('icon_class') + ' iconLeft"></i>';
                        }

                        html += selection.text + '</span>';
                        return html;
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    }
                };
                if (in_modal) {
                    options.dropdownParent = $('#page_modal');
                }
                $(this).select2(options);

                // Hack pour correction bug d'affichage:
                $(this).on('select2:close', function (e) {
                    var $options = $(this).find('option');
//
                    if ($options.length === 1) {
                        var val = $(this).val();
                        var $option = $(this).find('option[value="' + val + '"]');
                        if ($option.length) {
                            var html = '<span style="';
                            if ($option.data('color')) {
                                html += 'color: #' + $option.data('color') + '; font-weight: bold;';
                            }
                            html += '">';
                            if ($option.data('icon_class')) {
                                html += '<i class="' + $option.data('icon_class') + ' iconLeft"></i>';
                            }
                            html += $option.text() + '</span>';
                            $(this).parent().find('span.select2-selection__rendered').html(html);
                        }
                    }
                });
            }

            $(this).data('select_2_converted', 1);
        }
    });
    $container.find('.switch').each(function () {
        if (!parseInt($(this).data('switch_event_init'))) {
            setSwitchInputEvents($(this));
            $(this).data('switch_event_init', 1);
        }
    });
    $container.find('.toggle_value').each(function () {
        if (!parseInt($(this).data('toggle_event_init'))) {
            setToggleInputEvent($(this));
            $(this).data('toggle_event_init', 1);
        }
    });
    $container.find('.searchListOptions').each(function () {
        if (!parseInt($(this).data('search_list_event_init'))) {
            setSearchListOptionsEvents($(this));
            $(this).data('search_list_event_init', 1);
        }
    });
    $container.find('input[type="text"]').each(function () {
        if (!$(this).data('check_event_init')) {
            $(this).keyup(function () {
                checkTextualInput($(this), true);
            }).focusout(function () {
                checkTextualInput($(this), false);
            });
            $(this).data('check_event_init', 1);
        }
    });
    $container.find('.texarea_values').each(function () {
        if (!parseInt($(this).data('textarea_values_event_init'))) {
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
            $(this).data('textarea_values_event_init', 1);
        }
    });
    $container.find('.input_values').each(function () {
        if (!parseInt($(this).data('input_values_event_init'))) {
            var $inputContainer = $(this).findParentByClass('inputContainer');
            var field_name = $(this).data('field_name');
            var allow_custom = parseInt($(this).data('allow_custom'));
            var $input_values = $(this);
            if ($.isOk($inputContainer)) {
                var $target_input = $inputContainer.find('[name="' + field_name + '"]');
                if ($.isOk($target_input)) {
                    $(this).change(function () {
                        var val = $(this).val();
                        $target_input.val(val).change();
                    });
                    $target_input.change(function () {
                        var val = $(this).val() + '';
                        var check = false;
                        $input_values.find('option').each(function () {
                            if (val === $(this).attr('value')) {
                                check = true;
                                $input_values.val(val);
                            }
                        });
                        if (!check) {
                            if (!allow_custom) {
                                $target_input.val($input_values.val()).change();
                            }
                        }
                    });
                }
            }
            $(this).data('input_values_event_init', 1);
        }
    });
    $container.find('.qtyInputContainer').each(function () {
        if (!parseInt($(this).data('qty_event_init'))) {
            $(this).find('.qtyDown').click(function (e) {
                e.stopPropagation();
                var $qtyInputcontainer = $(this).findParentByClass('qtyInputContainer');
                if ($.isOk($qtyInputcontainer)) {
                    $qtyInputcontainer.find('input.qtyInput').focus();
                    inputQtyDown($qtyInputcontainer);
                }
            });
            $(this).find('.qtyUp').click(function (e) {
                e.stopPropagation();
                var $qtyInputcontainer = $(this).findParentByClass('qtyInputContainer');
                if ($.isOk($qtyInputcontainer)) {
                    $qtyInputcontainer.find('input.qtyInput').focus();
                    inputQtyUp($qtyInputcontainer);
                }
            });
            $(this).find('input.qtyInput').keyup(function (e) {
                e.stopPropagation();
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

            $(this).data('qty_event_init', 1);
        }
    });
    $container.find('.search_ziptown').each(function () {
        if (!$(this).data('ziptown_event_init')) {
            $(this).keyup(function (e) {
                if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') {
                    searchZipTown($(this));
                }
            });
            $(this).data('ziptown_event_init', 1);
        }
    });
    $container.find('.inputMultipleValuesContainer').each(function () {
        if (parseInt($(this).data('sortable'))) {
            setSortableMultipleValuesHandlesEvents($(this));
        }
    });
    $container.find('.search_list_input').each(function () {
        if (!parseInt($(this).data('search_list_input_events_init'))) {
            $(this).keyup(function (e) {
                if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') {
                    searchObjectList($(this));
                }
            });
            $(this).data('search_list_input_events_init', 1);
        }
    });
    $container.find('.search_list_input').add('.search_ziptown').each(function () {
        if (!parseInt($(this).data('input_choices_events_init'))) {
            $(this).keyup(function (e) {
                var $div = $(this).findParentByClass('inputContainer').find('.input_choices');
                if ($div.length && $div.css('display') !== 'none') {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        var $btn = $div.find('.btn');
                        if ($btn.length) {
                            var n = 0;
                            var i = 1;
                            $btn.each(function () {
                                if (!n) {
                                    if ($(this).hasClass('selected')) {
                                        n = i;
                                    }
                                }
                                i++;
                            });
                            if (e.key === 'ArrowDown') {
                                if (!n) {
                                    n = 1;
                                } else {
                                    n++;
                                }
                                if (n > $btn.length) {
                                    n = 1;
                                }
                            } else {
                                if (n) {
                                    n--;
                                }
                                if (!n) {
                                    n = $btn.length;
                                }
                            }
                            var $new = $div.find('.btn').eq(n - 1);
                            if ($new.length) {
                                $btn.removeClass('selected');
                                $new.addClass('selected');
                            }
                        }
                    } else if (e.key === 'Enter') {
                        e.stopPropagation();
                        var $btn = $div.find('.btn.selected');
                        if ($btn.length === 1) {
                            $btn.click();
                        }
                    }
                }
            });
            $(this).keydown(function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            $(this).data('input_choices_events_init', 1);
        }
    });
    $container.find('input.total_max').each(function () {
        $(this).change(function () {
            checkTotalMaxQtyInput($(this));
        });
    });
    $container.find('input.total_min').each(function() {
        $(this).change(function () {
            checkTotalMinQtyInput($(this));
        });
    });
    $container.find('.check_list_container').each(function () {
        if (!parseInt($(this).data('check_list_events_init'))) {
            var $checkListContainer = $(this);
            $(this).find('.check_list_item_input').each(function () {
                $(this).change(function () {
                    var $checkListContainer = $(this).findParentByClass('check_list_container');
                    if ($.isOk($checkListContainer)) {
                        checkCheckList($checkListContainer);
                    }
                });
            });

            var max_input_name = $(this).data('max_input_name');
            if (max_input_name) {
                var $form = $(this).findParentByClass('object_form');
                if (!$.isOk($form)) {
                    $form = $(this).findParentByTag('form');
                }
                if ($.isOk($form)) {
                    var $input = $form.find('[name="' + max_input_name + '"]');
                    if ($input.length) {
                        $input.change(function () {
                            onCheckListMaxInputChange($checkListContainer, $(this));
                        });
                        onCheckListMaxInputChange($checkListContainer, $input);
                    }
                }
            }

            checkCheckList($checkListContainer);
            $(this).data('check_list_events_init', 1);
        }
    });
    $container.find('.tab_key_as_enter').each(function () {
        if (!parseInt($(this).data('tab_key_as_enter_event_init'))) {
            $(this).keydown(function (e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    var val = $(this).val();
                    val += "\n";
                    $(this).val(val).change();
                    checkInputAutoExpand(this);
                }
            });
            $(this).change(function () {
                var val = $(this).val();
                val = val.replace("\t", "\n");
                $(this).val(val);
            });
            $(this).data('tab_key_as_enter_event_init', 1);
        }
    });
}

function setInputEvents($form, $input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('events_init'))) {
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
        } else {
            var inputs_names = $(this).data('inputs_names');
            if (typeof (inputs_names) !== 'undefined') {
                inputs_names = inputs_names.split(',');
                for (var i in inputs_names) {
                    if (input_name === inputs_names[i]) {
                        $input.change(function () {
                            toggleInputDisplay($container, $(this));
                        });
                    }
                }
            }
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
    $input.data('events_init', 1);
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
                        $parent.append('<p class="inputHelp">' + help + '</p>');
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

function setSelectDisplayHelpEvents($container, $input) {
    if ($input.tagName() === 'select') {
        if (!$input.data('select_help_event_init')) {
            var field_name = $input.attr('name');
            $input.change(function () {
                $container.find('div.selectOptionHelp').stop().hide().removeAttr('style');
                var $div = $container.find('div.' + field_name + '_' + $input.val() + '_help');
                if ($.isOk($div)) {
                    $div.slideDown(250);
                }
            });
            $input.data('select_help_event_init', 1);
            $input.change();
        }
    }
}

function setSortableMultipleValuesHandlesEvents($container) {
    var $tbody = $container.find('tbody.multipleValuesList');
    var $handles = $tbody.find('td.positionHandle');
    var $rows = $handles.parent('tr');

    if ($tbody.hasClass('ui-sortable')) {
        $tbody.sortable('destroy');
    }

    if ($handles.length) {
        $tbody.sortable({
            appendTo: $tbody,
            axis: 'y',
            cursor: 'move',
            handle: 'td.positionHandle',
            items: $rows,
            opacity: 1,
            start: function (e, ui) {
            },
            update: function (e, ui) {
            }
        });
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
