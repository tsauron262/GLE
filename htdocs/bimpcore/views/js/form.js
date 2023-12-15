var inputsEvents = [];
var bimpSignaturePads = [];

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

function saveObjectFromForm(form_id, $button, successCallback, on_save, on_submit) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

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

    if (typeof (on_submit) === 'function') {
        if (!on_submit($form)) {
            return;
        }
    }

    prepareFormSubmit($form);
    if (!validateForm($form)) {
        return;
    }

    var data = new FormData($formular.get(0));
    if (!on_save || typeof (on_save) === 'undefined') {
        on_save = $form.data('on_save');
    }

//    console.log(data);

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
        modal_scroll_bottom: true,
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
                    case 'open':
                        if (result.object_view_url) {
                            window.open(result.object_view_url);
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

            triggerObjectChange(result.module, result.object_name, result.id_object);
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}

function prepareFormSubmit($form) {
    var check = true;

    if ($.isOk($form)) {
        $form.find('.inputContainer').each(function () {
            var data_type = $(this).data('data_type');
            var field_name = $(this).data('field_name');
            var field_prefix = $(this).data('field_prefix');
            if (typeof (field_prefix) === 'undefined') {
                field_prefix = '';
            }

            if (field_name) {
                if ($(this).find('.signaturePadContainer').length) {
                    var $signatureContainer = $(this).find('.signaturePadContainer');
                    var $input = $(this).find('input[name="' + field_name + '"]');
                    var pad_id = $signatureContainer.data('pad_id');

                    if (pad_id && $input.length && bimpSignaturePads[pad_id].toDataURL('image/png').length > 7200) {
                        if (typeof (bimpSignaturePads[pad_id]) !== 'undefined') {
                            $input.val(bimpSignaturePads[pad_id].toDataURL('image/png'));
                        } else {
                            $input.val('');
                        }
                    } else
                        $input.remove();

                } else {
                    switch (data_type) {
                        case 'json':
                            var $input = $(this).find('[name="' + field_prefix + field_name + '"]');
                            if (!$input.length) {
                                var values = getJsonInputSubValues($(this), field_name, true);
                                var val_str = JSON.stringify(values);
                                val_str = val_str.replace(/"/g, '&quot;');
                                $(this).prepend('<input type="hidden" name="' + field_prefix + field_name + '" value="' + val_str + '"/>');
                            }
                            break;
                    }
                }
            }
        });
    }

    return check;
}

function loadModalForm($button, data, title, successCallback, on_save, modal_format, on_save_success_callback) {
    if (typeof (on_save) !== 'string') {
        on_save = '';
    }

    if (typeof (modal_format) !== 'string') {
        modal_format = 'medium';
    }

    if (typeof (on_save_success_callback) === 'undefined') {
        on_save_success_callback = 'null';
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
        bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'saveObjectFromForm(\'' + result.form_id + '\', $(this), ' + on_save_success_callback + ', \'' + on_save + '\');', 'primary', 'save_object_button', modal_idx);
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
    }, modal_format);
}

function appendModalForm(html, form_id, buttons, title) {
    if (typeof (html) !== 'string' || !html) {
        return;
    }

    if (typeof (title) === 'undefined') {
        title = '';
    }

    if (buttons === 'default') {
        buttons = [{
                label: '<i class="fas fa5-save iconLeft"></i>Enregistrer',
                onclick: 'saveObjectFromForm(\'' + form_id + '\')',
                type: 'primary',
                classes: 'save_object_button'
            }];
    } else if (typeof (buttons) === 'undefined') {
        buttons = [];
    }

    bimpModal.newContent(title, html);
    var modal_idx = bimpModal.idx;
    var $form = bimpModal.$contents.find('#modal_content_' + modal_idx).find('#' + form_id);
    if ($.isOk($form)) {
        $form.data('modal_idx', modal_idx);
        bimpModal.removeComponentContent($form.attr('id'));
        for (var i in buttons) {
            if (buttons[i].type === 'undefined') {
                buttons[i].type = 'primary';
            }
            if (buttons[i].classes === 'undefined') {
                buttons[i].classes = 'save_object_button';
            }
            bimpModal.addButton(buttons[i].label, buttons[i].onclick, buttons[i].type, buttons[i].classes, modal_idx);
        }

        onFormLoaded($form);
    }
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
//        bimpModal.clearAllContents();
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

function loadObjectFormFromForm(title, result_input_name, parent_form_id, module, object_name, form_name, id_parent, reload_input, $button, values, id_obj) {
    var $form = $('#' + parent_form_id);

    if (typeof (id_obj) === 'undefined') {
        id_obj = 0;
    }

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

    if (id_obj == -1) {//selection automatique pour edition
        var cible = $form.find('[name="' + result_input_name + '"]');
        if (cible.each(function () {
            id_obj = cible.val();
        }))
            ;
        if (id_obj < 1) {
            bimp_msg('Rien a modifier, aucun objet séléctionné', 'danger');
            return;
        }
    }

    var data = {
        'module': module,
        'object_name': object_name,
        'form_name': form_name,
        'id_object': id_obj,
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
                                                        if (typeof (fields[bimpAjax.result_input_name]) === 'undefined' || !fields[bimpAjax.result_input_name]) {
                                                            fields[bimpAjax.result_input_name] = [];
                                                        }
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
    var check = true;

    if ($form.find('.is_reloading').length) {
        bimp_msg('Un champ est en cours de rechargement. Veuillez attendre que celui-ci soit terminé avant de valider le formulaire', 'warning');
        return false;
    }

    $form.find('.inputContainer').each(function () {
        var $template = $(this).findParentByClass('subObjectFormTemplate');
        if (!$.isOk($template)) {
            var field_name = $(this).data('field_name');
            if (field_name) {
                var $input = $(this).find('[name="' + field_name + '"]');

                // Patch: (problème avec l'éditeur html => le textarea n'est pas alimenté depuis l'éditeur) 
                if ($(this).find('.cke').length) {
                    var html_value = $('#cke_' + field_name).find('iframe').contents().find('body').html();
                    if (html_value === '<br>') {
                        html_value = '';
                    }
                    $(this).find('[name="' + field_name + '"]').val(html_value);
                }

                if ($(this).find('.signaturePadContainer').length) {
                    var $signatureContainer = $(this).find('.signaturePadContainer');
                    var pad_id = $signatureContainer.data('pad_id');

                    if (pad_id && $input.length && parseInt($(this).data('required'))) {
                        if (typeof (bimpSignaturePads[pad_id]) !== 'undefined') {
                            var _data = bimpSignaturePads[pad_id]._data;
                            var size = 0;
                            _data.forEach(element => size += element.length);

                            if (!size) {
                                bimp_msg('Signature absente', 'danger', null, true);
                                check = false;
                            } else if (size < 25) {
                                bimp_msg('Signature trop petite', 'danger', null, true);
                                check = false;
                            }
                        }
                    }

                    var $checksMentions = $signatureContainer.find('.signature_mention_check');

                    if ($checksMentions.length) {
                        $checksMentions.each(function () {
                            if (!$(this).prop('checked')) {
                                var check_mention = $(this).parent().find('label').text();
                                bimp_msg('Veuillez cocher la case "' + check_mention + '"', 'danger', null, true);
                                check = false;
                            }
                        });
                    }
                } else if (parseInt($(this).data('required'))) {
                    if (parseInt($(this).data('multiple'))) {
                        if ($(this).find('.check_list_container').length) {
                            var list_check = false;
                            $(this).find('.check_list_container').find('.check_list_item').each(function () {
                                if ($(this).find('[name="' + field_name + '[]"]').prop('checked')) {
                                    list_check = true;
                                }
                            });
                            if (!list_check) {
                                $(this).addClass('value_required');
                                data_missing = true;
                            } else {
                                $(this).removeClass('value_required');
                            }
                        }
                    } else {
                        if ($input.length) {
                            var data_type = $(this).data('data_type');
                            if (data_type && ((data_type === 'id_object') || (data_type === 'id'))) {
                                if (!parseInt($input.val()) || parseInt($input.val()) <= 0) {
//                                    bimp_msg($input.tagName() + $input.attr('name') + ': ' + $input.val());
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

    if (data_missing) {
        check = false;
        bimp_msg('Certains champs obligatoires ne sont pas renseignés', 'danger', null, true);
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

        value = getInputValue($container);
        if (typeof (value) === 'undefined') {
            value = '';
        }
    } else {
        $container = $form.find('#' + input_name + '_subObjectsContainer');
        if ($container.length) {
            is_object = 1;
        } else {
            return;
        }
    }

    if ($.isOk($container)) {
        var $parent = $container.parent();
        var reload_idx = parseInt($parent.data('reload_idx'));
        if (isNaN(reload_idx)) {
            reload_idx = 0;
        }
        reload_idx++;
        $parent.data('reload_idx', reload_idx);
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

    $container.addClass('is_reloading');
    checkFormInputsReloads($form);

    BimpAjax('loadObjectInput', data, $container, {
        $form: $form,
        $container: $container,
        input_name: input_name,
        reload_idx: reload_idx,
        error_msg: 'Echec du chargement du champ',
        display_success: false,
        display_processing: true,
        processing_padding: 0,
        processing_msg: '',
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined') {
                var $form = $('#' + result.form_id);
                var $parent = bimpAjax.$container.parent();
                var parent_reload_idx = parseInt($parent.data('reload_idx'));
                if (isNaN(parent_reload_idx)) {
                    parent_reload_idx = 0;
                }

                if (parent_reload_idx > bimpAjax.reload_idx) {
                    // Un nouveau reload a eut lieu entre temps... 
                    return;
                }

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

                checkFormInputsReloads($form);

                $('body').trigger($.Event('inputReloaded', {
                    $form: $form,
                    input_name: bimpAjax.input_name,
                    $input: $input
                }));
            }
        }
    });
}

function checkFormInputsReloads($form) {
    if (!$.isOk($form)) {
        console.error('onInputReloadingEnd() : $form invalide');
        return;
    }

    var $button = getFormSubmitButton($form);

    if ($.isOk($button)) {
        if ($form.find('.is_reloading').length) {
            if (!$button.hasClass('disabled')) {
                $button.addClass('disabled');
            }
        } else {
            $button.removeClass('disabled');
        }
    }
}

function reloadParentInput($button, input_name, depends_on_fields) {
    var $inputContainer = $button.findParentByClass('inputContainer');
    if (!$.isOk($inputContainer)) {
        bimp_msg('Erreur technique (conteneur absent)', 'danger', null, true);
        return;
    }

    var field_name = $inputContainer.data('field_name');
    if (field_name !== input_name) {
        bimp_msg('Erreur technique (Nom de champ invalide)', null, true);
        return;
    }

    var $form = $inputContainer.findParentByClass('object_form');
    if (!$.isOk($form)) {
        bimp_msg('Erreur technique (formulaire non trouvé)', null, true);
        return;
    }

    var fields = {};
    if (typeof (depends_on_fields) !== 'undefined') {
        for (var i in depends_on_fields) {
            fields[depends_on_fields[i]] = getFieldValue($form, depends_on_fields[i]);
        }
    }

    reloadObjectInput($form.attr('id'), input_name, fields);
}

function searchObjectList($input) {
    if (!$.isOk($input)) {
        bimp_msg('Une erreur est survenue. Impossible d\'effectuer la recherche', 'danger', null, true);
        console.error('$input invalide');
        return;
    }

    var $container = $input.findParentByClass('inputContainer');
    if (!$.isOk($container)) {
        $container = $input.findParentByClass('searchInputContainer');
        if (!$.isOk($container)) {
            bimp_msg('Une erreur est survenue. Impossible d\'effectuer la recherche', 'danger', null, true);
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
    if ($.isOk($inputContainer)) {
        return getInputValue($inputContainer);
    }

    return '';
}

function getFormSubmitButton($form) {
    if (!$.isOk($form)) {
        console.error('getFormSubmitButton() : $form invalide');
        return null;
    }

    var $form_container = $form.findParentByClass('object_form_container');

    if (!$.isOk($form_container)) {
        console.error('getFormSubmitButton() : $form_container non trouvé');
        return null;
    }

    if ($form_container.parent().hasClass('panel-body')) {
        var $panelFooter = $form_container.parent().parent().children('panel-footer');

        if (!$.isOk($panelFooter)) {
            console.error('getFormSubmitButton() : $panelFooter non trouvé');
            return null;
        }

        return $panelFooter.find('.save_object_button');
    } else {
        var $modal_content = $form_container.findParentByClass('modal_content');

        if (!$.isOk($modal_content)) {
            console.error('getFormSubmitButton() : $modal_content absent');
            return null;
        }

        var modal_idx = parseInt($modal_content.data('idx'));

        if (!modal_idx) {
            console.error('getFormSubmitButton() : modal_idx absent');
            return null;
        }

        var $modal = $modal_content.findParentByClass('modal-content');
        if (!$.isOk($modal)) {
            console.error('getFormSubmitButton() : $modal non trouvé');
            return null;
        }

        var $modalFooter = $modal.find('.modal-footer');
        if (!$.isOk($modalFooter)) {
            console.error('getFormSubmitButton() : $modalFooter non trouvé');
            return null;
        }

        var $buttons = $modalFooter.find('.set_action_button.modal_' + modal_idx);
        if ($buttons.length) {
            return $buttons;
        }

        $buttons = $modalFooter.find('.save_object_button.modal_' + modal_idx);
        if ($buttons.length) {
            return $buttons;
        }
    }

    return null;
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
    var data_type = $inputContainer.data('data_type');
    var multiple = $inputContainer.data('multiple');
    var check_list = $inputContainer.data('check_list');

    if (typeof (check_list) === 'undefined') {
        check_list = $inputContainer.find('.check_list_container').length;
    } else {
        check_list = parseInt(check_list);
        if (isNaN(check_list)) {
            check_list = 0;
        }
    }

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
//                bimp_msg('Erreur: liste de valeurs absente pour le champ "' + field_name + '"', 'danger', null, true);
                return [];
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
        if (field_name) {
            if ($inputContainer.find('.signaturePadContainer').length) {
                var $signatureContainer = $inputContainer.find('.signaturePadContainer');
                var $input = $inputContainer.find('input[name="' + field_name + '"]');
                var pad_id = $signatureContainer.data('pad_id');

                if (pad_id && $input.length) {
                    if (typeof (bimpSignaturePads[pad_id]) !== 'undefined') {
                        value = bimpSignaturePads[pad_id].toDataURL('image/png');
                        $input.val(value);
                    } else {
                        value = '';
                        $input.val('');
                        bimp_msg('Erreur: bloc signature non trouvé pour le champ "' + field_name + '"', 'danger');
                    }
                }
                return value;
            }

            if ($inputContainer.find('.bimp_drop_files_container').length) {
                value = [];
                $inputContainer.find('.file_item').each(function () {
                    var $input = $(this).find('input.file_name');
                    if ($input.length) {
                        value.push($input.val());
                    }
                });

                return value;
            }
        }


        if ($inputContainer.find('.cke').length) {
            var html_value = $('#cke_' + field_name).find('iframe').contents().find('body').html();
            $inputContainer.find('[name="' + field_name + '"]').val(html_value);
        }

        if (typeof (data_type) === 'undefined') {
            data_type = 'string';
        }

        switch (data_type) {
            case 'json':
                value = getJsonInputSubValues($inputContainer, field_name, false);
                break;

            default:
                value = $inputContainer.find('[name="' + field_name + '"]').val();
                break;
        }
    }

    return value;
}

function getJsonInputSubValues($container, parent_name, remove_inputs) {
    if (typeof (remove_inputs) === 'undefined') {
        remove_inputs = false;
    }
    var value = {};
    if ($.isOk($container)) {
        $container.find('tr.bimp_json_input_value.' + parent_name + '_value').each(function () {
            var value_name = $(this).data('value_name');
            var input_name = $(this).data('input_name');
            if (value_name) {
                if (input_name) {
                    var $input = $(this).find('[name="' + input_name + '"]');
                    if ($input.length) {
                        value[value_name] = $input.val();
                        if (remove_inputs) {
                            $input.remove();
                        }
                    }
                } else {
                    value[value_name] = getJsonInputSubValues($(this).next('tr'), parent_name + '_' + value_name, remove_inputs);
                }
            }
        });
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
        bimp_msg('Une erreur technique est survenue ("inputContainer" absent). opération impossible', 'danger', null, true);
        return;
    }

    var $container = $inputContainer.find('.inputMultipleValuesContainer');
    if (!$container.length) {
        bimp_msg('Une erreur technique est survenue ("inputMultipleValuesContainer" absent). opération impossible', 'danger', null, true);
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
            bimp_msg(msg, 'danger', null, true);
            return;
        }
    }

    var value = '';
    var label = '';
    if ($value_input.length) {
        value = $value_input.val();

        if (typeof ($value_input.data('value_label')) !== 'undefined') {
            label = $value_input.data('value_label');
            $value_input.data('value_label', '');
        }

        if ($value_input.parent().hasClass('search_object_input_container')) {
            var $search_result = $value_input.parent().find('.search_object_result');

            if ($search_result.length) {
                if (!label) {
                    label = $search_result.text();
                }
                $search_result.html('').hide();
            }
        }
    } else if ($label_input.length) {
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
                bimp_msg('Cet élément a déjà été ajouté', 'warning', null, true);
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

        var item_options_html = '';
        var item_options = '';
        if ($value_input.get(0).tagName.toLowerCase() === 'select') {
            item_options = $value_input.find('[value="' + value + '"]').data('item_options');
        }

        if (item_options) {
            var $optionsContainer = $container.find('.multiple_values_items_options');
            if ($.isOk($optionsContainer)) {
                item_options = item_options.split(',');
                for (var i in item_options) {
                    $optionsContainer.children('.item_option').each(function () {
                        if ($(this).data('name') === item_options[i]) {
                            var option_input_name = values_field_name + '_' + value + '_option_' + item_options[i];
                            item_options_html += '<div class="item_option" data-name="' + item_options[i] + '">';
                            item_options_html += $(this).html().replace(/item_option_input_name/g, option_input_name);
                            item_options_html += '</div>';
                        }
                    });
                }
            }
        }


        var html = '<tr class="itemRow">';
        html += '<td style="display: none"><input class="item_value" type="hidden" value="' + value + '" name="' + values_field_name + '[]"/></td>';
        if (sortable) {
            html += '<td class="positionHandle"><span></span></td>';
        }
        html += '<td class="item_label">' + label + item_options_html + '</td>';
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
                var $valuesList = $container.find('table').find('tbody.multipleValuesList');
                $valuesList.append(html);
                if (sortable) {
                    setSortableMultipleValuesHandlesEvents($container);
                }
                if (item_options_html) {
                    setCommonEvents($valuesList);
                    setInputsEvents($valuesList);
                }
                checkMultipleValues();
                $('body').trigger($.Event('inputMultipleValuesChange', {
                    input_name: values_field_name,
                    $container: $container
                }));
            });
        } else {
            var $valuesList = $container.find('table').find('tbody.multipleValuesList');
            $valuesList.append(html);
            if (sortable) {
                setSortableMultipleValuesHandlesEvents($container);
            }
            if (item_options_html) {
                setCommonEvents($valuesList);
                setInputsEvents($valuesList);
            }
            checkMultipleValues();
            $('body').trigger($.Event('inputMultipleValuesChange', {
                input_name: values_field_name,
                $container: $container
            }));
        }
    } else {
        bimp_msg('Veuillez sélectionner une valeur', 'warning', null, true);
    }
}

function addMultipeInputAllValues($button, value_input_name, label_input_name, ajax_save) {
    if (typeof (ajax_save) === 'undefined') {
        ajax_save = false;
    }

    if ($button.hasClass('disabled')) {
        return;
    }

    var $inputContainer = $button.findParentByClass('inputContainer');
    if (!$inputContainer.length) {
        bimp_msg('Une erreur technique est survenue ("inputContainer" absent). opération impossible', 'danger', null, true);
        return;
    }

    var $container = $inputContainer.find('.inputMultipleValuesContainer');
    if (!$container.length) {
        bimp_msg('Une erreur technique est survenue ("inputMultipleValuesContainer" absent). opération impossible', 'danger', null, true);
        return;
    }

    var $input = $inputContainer.find('[name=' + value_input_name + ']');
    if (!$input.length) {
        bimp_msg('Une erreur technique est survenue ("input" absent). opération impossible', 'danger', null, true);
        return;
    }

    if ($input.tagName() !== 'select') {
        bimp_msg('Ajout de toutes les valeurs impossible (Type d\'input invalide)', 'danger', null, true);
        return;
    }

    var $options = $input.children('option');
    if (!$options.length) {
        bimp_msg('Aucune valeur à ajouter trouvée', 'warning', null, true);
        return;
    }

    var $addBtn = $button.parent().find('.addValueBtn');
    $options.each(function () {
        var $option = $(this);
        $input.val($option.attr('value'));
        addMultipleInputCurrentValue($addBtn, value_input_name, label_input_name, ajax_save);
    });
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
        var $inputContainer = $container.findParentByClass('inputContainer');
        if ($inputContainer.length) {
            var input_name = $inputContainer.data('field_name') + '_add_value';
            if (input_name) {
                if ($inputContainer.find('.itemRow').length) {
                    $inputContainer.find('.noItemRow').hide();
                    $inputContainer.find('.noItemRow').find('.no_item_input').remove();
                } else {
                    $inputContainer.find('.noItemRow').show();
                    var field_name = $inputContainer.data('field_name');
                    if (field_name) {
                        var no_item_input = '<input class="no_item_input" type="hidden" value="" name="' + field_name + '"/>';
                        $inputContainer.find('.noItemRow').children('td').first().append(no_item_input);
                    }
                }

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
                        $input.val('');
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
        bimp_msg('Erreur technique (container absent)', 'danger', null, true);
        return;
    }

    if ($container.attr('id') !== object_name + '_subObjectsContainer') {
        bimp_msg('Erreur technique (container invalide)', 'danger', null, true);
        return;
    }

    var $template = $container.find('.subObjectFormTemplate');
    if (!$.isOk($template)) {
        bimp_msg('Erreur technique (template absent)', 'danger', null, true);
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
        bimp_msg('Une erreur est survenue, opération abandonnée (Conteneur absent ou invalide)', 'danger', null, true);
    }
}

function addFormGroupMultipleItem($button, inputName) {
    var $container = $button.findParentByClass('formInputGroup');
    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. opération impossible', 'danger', null, true);
        return;
    }

    if ($container.data('field_name') !== inputName) {
        bimp_msg('Une erreur est survenue. opération impossible ici', 'danger', null, true);
        return;
    }

    var max_items = parseInt($container.data('max_items'));
    if (!isNaN(max_items) && max_items > 0) {
        if ($container.children('.formGroupMultipleItems').children('.formGroupMultipleItem').length >= max_items) {
            bimp_msg('Vous ne pouvez ajouter que ' + max_items + ' élément(s)', 'warning', null, true);
            return;
        }
    }

    var $template = $container.children('div.dataInputTemplate');
    var $index = $container.find('[name="' + inputName + '_nextIdx"]');
    if ($template.length) {
        if ($index.length) {
            var html = $template.html();
            var index = parseInt($index.val());
            var regex = new RegExp('idx', 'g');
            html = html.replace(regex, index);
            index++;
            var $list = $container.find('div.formGroupMultipleItems');
            var $form = $container.findParentByTag('form');
            if ($list.length) {
                $list.append(html);
                var $item = $list.find('.formGroupMultipleItem').last();
                setInputsEvents($item);
                setCommonEvents($item);
                if ($form.length) {
                    setContainerDisplayIfEvents($form, $item);
                }
                $index.val(index);
                return;
            }
        }
    }
}

function removeFormGroupMultipleItem($button) {
    if ($.isOk($button)) {
        var $container = $button.findParentByClass('formGroupMultipleItem');
        if ($.isOk($container)) {
            $container.remove();
            return;
        }
    }

    bimp_msg('Une erreur est survenue, opération abandonnée (Conteneur absent ou invalide)', 'danger', null, true);
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
                if ($container.find('.search_object_input_container').length) {
                    $container.find('.search_object_input_container').find('.search_object_input').find('input').val('');
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

        if (!$selected.length) {
            if (!$container.find('input.check_list_no_selected').length) {
                var $inputContainer = $container.findParentByClass('inputContainer');
                if ($.isOk($inputContainer)) {
                    var field_name = $inputContainer.data('field_name');
                    if (field_name) {
                        $container.append('<input type="hidden" value="" name="' + field_name + '" class="check_list_no_selected"/>');
                    }
                }
            }
        } else {
            $container.find('input.check_list_no_selected').remove();
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

function calcTotalCompteurCaisse($container) {
    if (!$.isOk($container)) {
        return;
    }

    var total = 0;
    var $inputs = $container.find('input.compteur_caisse_input');
    $inputs.each(function () {
        var val = parseInt($(this).val());
        if (!isNaN(val)) {
            total += val * parseFloat($(this).data('value'));
        }
    });
    total = Math.round10(total, -2);
    $container.find('.compteur_caisse_total').text(total);
    $container.find('.compteur_caisse_total_input').val(total).change();
}

function selectChecklistItem($container, value, value_type) {
    if (!$.isOk($container)) {
        bimp_msg('Erreur: liste de choix non trouvée', 'danger');
        return;
    }

    if (typeof (value_type) === 'undefined') {
        value_type = 'label';
    }
    var check = false;
    $container.find('.check_list_item').each(function () {
        if (!check) {
            var $item = $(this);
            var $cb = null;
            switch (value_type) {
                case 'label':
                    var label = value;
                    if ($item.find('label').text().toLowerCase().indexOf(label.toLowerCase()) !== -1 || ("S" + $item.find('label').text()).toLowerCase().indexOf(label.toLowerCase()) !== -1) {
                        $cb = $item.find('input.check_list_item_input');
                    }
                    break;
                case 'value':
                    $cb = $item.find('input.check_list_item_input');
                    if (!$cb.length || $cb.val() != value) {
                        $cb = null;
                    }
                    break;
            }

            if ($.isOk($cb)) {
                if ($cb.prop('checked')) {
                    bimp_msg('L\'option "' + label + '" est déjà sélectionnée', 'warning', null, true);
                } else {
                    $cb.prop('checked', true);
                    $container.children('.check_list_search_input').children('input').change();
                    check = true;
                }
            }
        }
    });
    if (!check) {
        bimp_msg('Option "' + value + '" non trouvée', 'warning', null, true);
    }
}

function onChecklistSearchInputChange($input) {
    if ($.isOk($input)) {
        var val = $input.val();
        var $container = $input.findParentByClass('check_list_search_input');
        var choices = [];
        if (typeof (val) === 'string' && val !== '') {
            if ($.isOk($container)) {
                var regex1 = new RegExp('^(.*)(' + val + ')(.*)$', 'i');
                var regex2 = '';
                if (/^S.+$/i.test(val)) {
                    regex2 = new RegExp('^(.*)(' + val.replace(/^S(.+)$/i, '$1') + ')(.*)$', 'i');
                }
                $container.findParentByClass('check_list_container').find('.check_list_item').each(function () {
                    if (!$(this).children('input[type=checkbox]').prop('checked')) {
                        var choice = {
                            'label': '',
                            'value': ''
                        };
                        var text = $(this).children('label').text();
                        if (text) {
                            if (regex1.test(text)) {
                                choice.label = text.replace(regex1, '$1<strong>$2</strong>$3');
                            } else if (regex2 && regex2.test(text)) {
                                choice.label = text.replace(regex2, '$1<strong>$2</strong>$3');
                            }

                            var $item_input = $(this).find('input.check_list_item_input');
                            if ($item_input.length) {
                                choice.value = $item_input.val();
                            }
                        }

                        if (choice.label || choice.value) {
                            choices.push(choice);
                        }
                    }
                });
                if (choices.length) {
                    displayInputChoices($input, choices, function ($btn) {
                        if ($.isOk($btn)) {
                            var value = $btn.data('item_value');
                            var value_type = 'value';
                            if (typeof (value) === 'undefined') {
                                value = $btn.html();
                                value = value.replace('<strong>', '');
                                value = value.replace('</strong>', '');
                                value_type = 'label';
                            }

                            var $checkList = $btn.findParentByClass('check_list_container');
                            $input.addClass('noEnterCheck');
                            selectChecklistItem($checkList, value, value_type);
                        }
                    });
                }
            }
        }

        if (!choices.length) {
            $container.find('.input_choices').remove();
        }
    }
}

function displayInputChoices($input, choices, onItemSelected) {
    if (!$.isOk($input)) {
        return;
    }

    var input_name = $input.attr('name');
    var container_id = input_name + '_input_choices';

    $input.parent().find('#' + container_id).remove();

    if (typeof (choices) !== 'undefined' && choices.length) {
        var html = '<div class="input_choices hideOnClickOut" id="' + container_id + '">';
        for (var i in choices) {
            var label = '';
            var data = [];
            var card = '';
            var value = 'undefined';
            if (typeof (choices[i]) === 'string') {
                label = choices[i];
            } else if (typeof (choices[i]) === 'object') {
                label = choices[i].label;

                if (typeof (choices[i].data) !== 'undefined') {
                    data = choices[i].data;
                }
                if (typeof (choices[i].card) !== 'undefined') {
                    card = choices[i].card;
                }
                if (typeof (choices[i].value) !== 'undefined') {
                    value = choices[i].value;
                }
            }

            if (label) {
                html += '<span class="btn btn-light-default input_choice';
                if (card) {
                    html += ' bs-popover';
                }
                if (typeof (choices[i].disabled) !== 'undefined' && choices[i].disabled) {
                    html += ' disabled';
                }
                html += '"';
                if (card) {
                    data['toggle'] = 'popover';
                    data['trigger'] = 'hover';
                    data['container'] = 'body';
                    data['placement'] = 'bottom';
                    data['html'] = 'true';
                    data['content'] = card;
                }

                for (var name in data) {
                    html += ' data-' + name + '="' + data[name] + '"';
                }

                if (value !== 'undefined') {
                    html += ' data-item_value="' + value + '"';
                }

                html += '>' + label + '</span>';
            }
        }

        html += '</div>';

        $input.after(html);
        setInputChoicesEvents($input, onItemSelected);
    }
}

function loadSearchObjectResults($input, idx) {
    if ($.isOk($input)) {
        setTimeout(function () {
            if (parseInt($input.data('idx')) === idx) {
                var val = $input.val();
                var display_results = true;
                if ($.isOk($input.findParentByClass('searchInputContainer'))) {
                    display_results = false;
                } else if ($.isOk($input.findParentByClass('bimp_filter_input_container'))) {
                    display_results = false;
                } else if ($.isOk($input.findParentByClass('singleLineForm'))) {
                    display_results = false;
                } else if (!parseInt($input.data('display_results'))) {
                    display_results = false;
                }
                if (val) {
                    var $parent = $input.findParentByClass('search_object_input');
                    if ($.isOk($parent)) {
                        $parent.children('.spinner').animate({'opacity': 1}, {'duration': 50});
                        var ajax_data = $input.data('ajax_data');
                        ajax_data.search_value = val;
                        $input.data('last_results_idx', idx);
                        BimpAjax('getSearchObjectResults', ajax_data, null, {
                            results_idx: idx,
                            $input: $input,
                            $parent: $parent,
                            display_success: false,
                            display_results: display_results,
                            success: function (result, bimpAjax) {
                                if (parseInt(bimpAjax.results_idx) !== parseInt(bimpAjax.$input.data('last_results_idx'))) {
                                    return;
                                }
                                $parent.children('.spinner').animate({'opacity': 0}, {'duration': 50});
                                bimpAjax.$parent.find('.input_choices').remove();
                                if (typeof (result.results) === 'object') {
                                    var choices = [];
                                    for (var i in result.results) {
                                        choices.push({
                                            label: result.results[i].label,
                                            data: {
                                                value: result.results[i].id
                                            },
                                            card: result.results[i].card,
                                            disabled: result.results[i].disabled
                                        });
                                    }

                                    if (choices.length) {
                                        if (choices.length === 1 && parseInt(bimpAjax.$input.data('auto_select'))) {
                                            var $container = bimpAjax.$input.findParentByClass('search_object_input_container');

                                            if ($.isOk($container) && $container.parent().hasClass('addValueInputContainer')) {
                                                var input_name = $container.data('input_name');
                                                if (input_name) {
                                                    var $input = $container.find('[name="' + input_name + '"]');
                                                    if ($input.length) {
                                                        $input.data('value_label', choices[0].label);
                                                        $container.find('[name="' + input_name + '_search"]').val('');
                                                        $input.val(choices[0].data.value).change();
                                                        $container.parent().find('.addValueBtn').click();
                                                        bimpAjax.$input.data('auto_select', 0);
                                                        return;
                                                    }
                                                }
                                            }


                                        }

                                        displayInputChoices(bimpAjax.$input, choices, function ($btn) {
                                            $('body').find('.popover.fade').remove();
                                            if ($.isOk($btn)) {
                                                var id_object = parseInt($btn.data('value'));
                                                if (!id_object) {
                                                    bimp_msg('Erreur: ID de l\'objet absent', 'danger');
                                                } else {
                                                    var $container = $btn.findParentByClass('search_object_input_container');
                                                    if (!$.isOk($container)) {
                                                        bimp_msg('Erreur technique: conteneur absent', 'danger');
                                                    } else {
                                                        var html = '';
                                                        var card = '';
                                                        var label = '';
                                                        if ($btn.hasClass('bs-popover')) {
                                                            card = $btn.data('content');
                                                        }

                                                        if (card) {
                                                            html = card;
                                                        } else {
                                                            label = $btn.html();
                                                        }

                                                        if (!html) {
                                                            html = '<span class="success"><i class="fas fa5-check iconLeft"></i></span>';
                                                            if (label) {
                                                                html += label;
                                                            } else {
                                                                html = 'Objet #' + id_object;
                                                            }
                                                        }

                                                        if (bimpAjax.display_results) {
                                                            var $result = $container.children('.search_object_result');
                                                            if ($result.length) {
                                                                if ($container.children('.no_item_selected').css('display') !== 'none') {
                                                                    $container.children('.no_item_selected').fadeOut(250, function () {
                                                                        $result.html(html);
                                                                        if (!parseInt($result.data('never_show'))) {
                                                                            $result.fadeIn(250);
                                                                        }
                                                                    });
                                                                } else {
                                                                    $result.fadeOut(250, function () {
                                                                        $result.html(html);
                                                                        if (!parseInt($result.data('never_show'))) {
                                                                            $result.fadeIn(250);
                                                                        }
                                                                    });
                                                                }
                                                            }
                                                        }

                                                        var input_name = $container.data('input_name');
                                                        if (input_name) {
                                                            $container.find('[name="' + input_name + '"]').val(id_object).change();
                                                            if (bimpAjax.display_results) {
                                                                $container.find('[name="' + input_name + '_search"]').val('');
                                                            } else {
                                                                $container.find('[name="' + input_name + '_search"]').val($btn.text());
                                                            }

                                                            if ($container.parent().hasClass('addValueInputContainer')) {
                                                                $container.parent().find('.addValueBtn').click();
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }
                            },
                            error: function () {
                                $parent.children('.spinner').animate({'opacity': 0}, {'duration': 50});
                            }
                        });
                    }
                } else {
                    var $container = $input.findParentByClass('search_object_input_container');
                    if ($.isOk($container)) {
                        var input_name = $container.data('input_name');
                        if (input_name) {
                            $container.find('[name="' + input_name + '"]').val(0).change();
                            if (display_results && $container.children('.no_item_selected').css('display') === 'none') {
                                $container.children('.search_object_result').fadeOut(250, function () {
                                    $(this).html('');
                                    if (!parseInt($container.children('.no_item_selected').data('never_show'))) {
                                        $container.children('.no_item_selected').fadeIn(250);
                                    }
                                });
                            } else {
                                $container.children('.search_object_result').html('').hide(); // Par précaution: 
                            }
                        }
                    }
                }
            }
        }, 350);
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
// Règles pour display_if sur plusieurs inputs: 
//      - Tous les show_values doivent être vrai pour afficher. 
//      - Si un seul hide_values vrai : champ masqué. 

        var inputs_names = $container.data('inputs_names');
        if (inputs_names) {
            inputs_names += '';
            inputs_names = inputs_names.split(',');
            var $form = $container.findParentByClass('object_form');
            if ($.isOk($form)) {
                show = true;
                var hide = false;
                for (var i in inputs_names) {
                    $input = $form.find('[name="' + inputs_names[i] + '"]');
                    if ($input.length) {
                        input_val = $input.val();
                        show_values = $container.data('show_values_' + inputs_names[i]);
                        hide_values = $container.data('hide_values_' + inputs_names[i]);
                        if (typeof (show_values) !== 'undefined') {
                            var input_check = false;
                            show_values += '';
                            show_values = show_values.split(',');
                            for (var j in show_values) {
                                if (input_val == show_values[j]) {
                                    input_check = true;
                                    break;
                                }
                            }
                            if (!input_check) {
                                show = false;
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

// Actions Filters Input:

function setFiltersInputObjectData($filtersInputContainer, module, object_name) {
    if ($.isOk($filtersInputContainer)) {
        var cur_module = $filtersInputContainer.data('obj_module');
        var cur_object_name = $filtersInputContainer.data('obj_name');
        if (cur_module && cur_object_name && cur_module === module && cur_object_name === object_name) {
            return;
        }

        $filtersInputContainer.data('obj_module', module);
        $filtersInputContainer.data('obj_name', object_name);
        if (module && object_name) {
            $filtersInputContainer.find('.no_object_notif').stop().slideUp(250);
            $filtersInputContainer.find('.obj_filters_input_values').find('.panel-body').html('<div class="info">Aucun filtre ajouté</div>');
            BimpAjax('getFiltersInputAddFiltersInput', {
                module: module,
                object_name: object_name
            }, $filtersInputContainer.find('.filters_input_add_filter_form'), {
                display_success: false,
                display_processing: true,
                processing_msg: '',
                processing_padding: 10,
                append_html: true,
                success: function (result, bimpAjax) {
                    setFiltersInputAddFilterFormEvents(bimpAjax.$resultContainer);
                }
            });
        } else {
            $filtersInputContainer.find('.filters_input_add_filter_form').hide().html('');
            $filtersInputContainer.find('.no_object_notif').stop().slideDown(250);
        }
    }
}

function reloadFiltersInputValue($filtersInputContainer, filters, success_callback, error_callback) {
    var module = $filtersInputContainer.data('obj_module');
    var object_name = $filtersInputContainer.data('obj_name');
    BimpAjax('getFiltersInputValuesHtml', {
        module: module,
        object_name: object_name,
        filters: filters
    }, $filtersInputContainer.find('.obj_filters_input_values').children('.panel').children('.panel-body'), {
        display_success: false,
        display_processing: true,
        processing_msg: '',
        processing_padding: 10,
        append_html: true,
        success: function (result, bimpAjax) {
            if (typeof (success_callback) === 'function') {
                success_callback(result, bimpAjax);
            }
        },
        error: function (result, bimpAjax) {
            if (typeof (error_callback) === 'function') {
                error_callback(result, bimpAjax);
            }
        }
    });
}

function reloadFiltersInputAddFilterInput($button) {
    if ($.isOk($button)) {
        var $container = $button.findParentByClass('objectFilterItemsSelectContainer');
        if ($.isOk($container)) {
            var $select = $container.children('select.field_select');
            if ($select.length) {
                $select.change();
                return;
            }
        }
    }

    bimp_msg('Une erreur est survenue. Actualisation impossible', 'danger', null, true);
}

function addFiltersInputFilter($button, filter_name, filter, exclude) {
    if (typeof (exclude) === 'undefined') {
        exclude = false;
    }

    var $filtersInputContainer = $button.findParentByClass('obj_filters_input_container');
    if ($.isOk($filtersInputContainer)) {
        var filters = getFiltersInputFilters($filtersInputContainer);
        if (typeof (filters[filter_name]) === 'undefined') {
            filters[filter_name] = {
                values: [],
                excluded_values: []
            };
        }

        if (exclude) {
            filters[filter_name].excluded_values.push(filter);
        } else {
            filters[filter_name].values.push(filter);
        }

        reloadFiltersInputValue($filtersInputContainer, filters, function (result, bimpAjax) {
            resetFiltersInputInputs($button);
            if (typeof (result.values_json) !== 'undefined') {
                var $inputContainer = $button.findParentByClass('obj_filters_input_container');
                if ($.isOk($inputContainer)) {
                    var field_name = $inputContainer.data('field_name');
                    if (field_name) {
                        var $input = $inputContainer.find('input[name="' + field_name + '"]');
                        if ($input.length) {
                            $input.val(result.values_json);
                        }
                    }
                }
            }

            $filtersInputContainer.find('.filter_submit_btn').removeClass('disabled');
        }, function (result, bimpAjax) {
            $filtersInputContainer.find('.filter_submit_btn').removeClass('disabled');
        });
    } else {
        bimp_msg('Une erreur est survenue (conteneur absent) - Ajout du filtre impossible');
    }
}

function removeFiltersInputFilter($button) {
    if ($.isOk($button)) {
        var $value = $button.findParentByClass('filter_value');
        if ($.isOk($value)) {
            var $container = $value.findParentByClass('filter_active_values');
            $value.remove();
            if ($.isOk($container)) {
                var $valuesContainer = $container.findParentByClass('obj_filters_input_values');
                var has_inc = false;
                var has_exc = false;
                var $inc = $container.children('.included_values');
                if ($inc.length) {
                    if ($inc.find('.filter_value').length) {
                        has_inc = true;
                    }
                }

                if (!has_inc) {
                    var $exc = $container.children('.excluded_values');
                    if ($exc.length) {
                        if ($exc.find('.filter_value').length) {
                            has_exc = true;
                        }
                    }
                }

                if (!has_inc && !has_exc) {
                    $container.remove();
                }

                if ($.isOk($valuesContainer)) {
                    var filters = '';
                    var $panel = $valuesContainer.children('.panel').children('.panel-body');
                    if ($panel.length) {
                        if (!$panel.find('.filter_active_values').length) {
                            $panel.html('<div class="info">Aucun filtre ajouté</div>');
                            filters = {};
                        }
                    }

                    var $filtersInputContainer = $valuesContainer.findParentByClass('obj_filters_input_container');
                    if ($.isOk($filtersInputContainer)) {
                        var field_name = $filtersInputContainer.data('field_name');
                        if (field_name) {
                            var $input = $filtersInputContainer.find('input[name="' + field_name + '"]');
                            if ($input.length) {
                                if (filters === '') {
                                    filters = getFiltersInputFilters($filtersInputContainer);
                                }

                                $input.val(JSON.stringify(filters));
                            }
                        }
                    }
                }
            }
        }
    }
}

function getFiltersInputFilters($filtersInputContainer) {
    var filters = {};
    if ($.isOk($filtersInputContainer)) {
        $filtersInputContainer.find('.obj_filters_input_values').find('.filter_active_values').each(function () {
            var filter_name = $(this).data('filter_name');
            if (filter_name) {
                var filter = {
                    values: [],
                    excluded_values: []
                };
                var $included_values = $(this).children('.included_values');
                if ($included_values.length) {
                    $included_values.children('.filter_value').each(function () {
                        var filter_value = $(this).data('filter');
                        if (typeof (filter_value) !== 'undefined') {
                            filter.values.push(filter_value);
                        }
                    });
                }

                var $excluded_values = $(this).children('.excluded_values');
                if ($excluded_values.length) {
                    $excluded_values.children('.filter_value').each(function () {
                        var filter_value = $(this).data('filter');
                        if (typeof (filter_value) !== 'undefined') {
                            filter.excluded_values.push(filter_value);
                        }
                    });
                }

                if (filter.values.length || filter.excluded_values.length) {
                    filters[filter_name] = filter;
                }
            }
        });
    }

    return filters;
}

function resetFiltersInputInputs($button) {
    if ($.isOk($button)) {
        var $container = $button.findParentByClass('bimp_filter_input_container');
        if ($.isOk($container)) {
            $container.find('.bimp_filter_input').each(function () {
                var default_value = $(this).data('default_value');
                if (typeof (default_value) === 'undefined') {
                    default_value = '';
                }

                $(this).val(default_value).change();
                if ($(this).hasClass('datepicker_value')) {
                    var input_id = $(this).attr('id');
                    if (input_id) {
                        $('#' + input_id + '_bs_dt_picker').data('DateTimePicker').clear();
                    }
                }
            });
        }
    }
}

// Gestion des événements:

function onFormLoaded($form) {
    if (!$form.length) {
        return;
    }

    checkFormInputsReloads($form);

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
        setCommonEvents($form);
        setFormEvents($form);
        $('body').trigger($.Event('formLoaded', {
            $form: $form
        }));
        var $modal = $form.findParentByClass('modal');
        if ($.isOk) {
            $modal.focus(function (e) {
                $form.find('.auto_focus').first().focus();
                e.stopPropagation();
            });
        }

        var focus_done = false;
        $form.find('.inputContainer').each(function () {
            if (!focus_done) {
                var field_name = $(this).data('field_name');
                if (field_name) {
                    var $input = $(this).find('[name="' + field_name + '"]');
                    if ($input.length) {
                        var $row = $input.findParentByClass('formRow');
                        if ($.isOk($row) && $row.css('display') !== 'none') {
                            var tag = $input.tagName();
                            if (tag === 'textarea' || (tag === 'input' && $input.attr('type') === 'text')) {
                                $input.focus();
                                focus_done = true;
                            } else if (tag !== 'input' || $input.attr('type') !== 'hidden') {
                                focus_done = true;
                            }
                        }
                    }
                }
            }
        });
    }
}

function setFormEvents($form) {
    if (!$form.length) {
        return;
    }

    setContainerDisplayIfEvents($form, $form);
    resetInputDisplay($form);
    setInputsEvents($form);
    var form_id = $form.attr('id');
    $form.find('.inputContainer').each(function () {
        setInputContainerEvents($(this));
    });
    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id) {
            var $input = $form.find('[name=' + inputsEvents[i].input_name + ']');
            if ($input.length && !parseInt($input.data('form_input_event_' + i + '_init'))) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
                $input.data('form_input_event_' + i + '_init', 1);
            }
        }
    }

    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id && inputsEvents[i].event === 'change') {
            var $input = $form.find('[name=' + inputsEvents[i].input_name + ']');
            if ($input.length && !parseInt($input.data('form_init_input_change'))) {
                $input.data('form_init_input_change', 1);
                $input.change();
            }
        }
    }

    $form.find('form').first().submit(function (e) {
        e.preventDefault();
        e.stopPropagation();

        var hold = $form.data('hold_auto_submit');

        if (typeof (hold) === 'undefined') {
            hold = 0;
        }

        if (!parsetInt(hold)) {
            submitForm($form.attr('id'));
        } else {
            $form.data('hold_auto_submit', 0)
        }
    });

    $form.keydown(function (e) {
        if (e.key === 'Enter') {
            e.stopPropagation();
        }
    });
}

function setContainerDisplayIfEvents($form, $container) {
// Gestion automatique des affichages d'options supplémentaires lorsque certains input prennent certaines valeurs.
// placer les champs optionnels dans un container avec class="display_if". 
// Et Attributs: 
// data-input_name=nom_input_a_checker (string)
// data-show_values=valeur(s)_pour_afficher_container (string ou objet)
// data-hide_values=valeur(s)_pour_masquer_container (string ou objet)

    if (!$.isOk($container) || !$.isOk($form)) {
        return;
    }

    $container.find('.display_if').each(function () {
        var $displayIf = $(this);
        if (!parseInt($displayIf.data('display_if_events_init'))) {
            var input_name = $displayIf.data('input_name');
            if (input_name) {
                var $input = $form.find('[name=' + input_name + ']');
                if ($input.length) {
                    $input.change(function () {
                        toggleInputDisplay($displayIf, $(this));
                    });
                }
            } else {
                var inputs_names = $displayIf.data('inputs_names');
                if (inputs_names) {
                    inputs_names += '';
                    inputs_names = inputs_names.split(',');
                    for (var i in inputs_names) {
                        var $input = $form.find('[name="' + inputs_names[i] + '"]');
                        if ($input.length) {
                            $input.change(function () {
                                toggleInputDisplay($displayIf, $(this));
                            });
                        }
                    }
                }
            }
            $displayIf.data('display_if_events_init', 1);
        }
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

    setSelectDisplayHelpEvents($input);
    $inputContainer.data('input_container_events_init', 1);
}

function setInputsEvents($container) {
    var in_modal = $.isOk($container.findParentByClass('modal'));
    $container.find('input').add($container.find('textarea')).focus(function () {
        if (typeof (text_input_focused) !== 'undefined') {
            text_input_focused = true;
        }
    }).blur(function () {
        if (typeof (text_input_focused) !== 'undefined') {
            text_input_focused = false;
        }
    });
    $container.find('select').each(function () {
        if (!$(this).data('select_2_converted')) {
            if (!$.isOk($(this).findParentByClass('subObjectFormTemplate')) &&
                    !$.isOk($(this).findParentByClass('dataInputTemplate')) &&
                    !$(this).hasClass('no_select2')) {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }

                var select_val = $(this).val();
                var $option = $(this).children('option[value="' + select_val + '"]');
                if ($option.length) {
                    $option.prop('selected', true);
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

                        var text = data.text;

                        text = text.replace('[bold]', '<b>');
                        text = text.replace('[/bold]', '</b>');

                        html += text + '</span>';
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

                        var text = selection.text;
                        text = text.replace('[bold]', '<b>');
                        text = text.replace('[/bold]', '</b>')

                        html += text + '</span>';
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
    $container.find('.input_possible_values').each(function () {
        if (!parseInt($(this).data('input_possible_values_event_init'))) {
            var field_name = $(this).data('field_name');
            var $input = $(this).parent().find('textarea[name="' + field_name + '"],input[name="' + field_name + '"]');
            var replace_cur_value = parseInt($(this).data('replace_cur_value'));
            
            if (isNaN(replace_cur_value)) {
                replace_cur_value = 0;
            }

            $(this).find('.input_possible_value').click(function () {
                var text = '';
                if (!replace_cur_value) {
                    text = $input.val();
                    if (text) {
                        text += ', ';
                    }
                }
                text += $(this).text();
                $input.val(text).change();
            });
            $(this).data('input_possible_values_event_init', 1);
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
    $container.find('.addValueInputContainer').each(function () {
        if (!parseInt($(this).data('add_value_input_events_init'))) {
            $(this).data('add_value_input_events_init', 1);
            var $input = $(this).children('input[type="text"]');
            var $btn = $(this).children('.addValueBtn');

            if ($input.length && $btn.length) {
                $input.keydown(function (e) {
                    if (e.key === 'Enter') {
                        var $form = $(this).findParentByClass('object_form');
                        if ($.isOk($form)) {
                            $form.data('hold_auto_submit', 1);
                        }
                    }
                });

                $input.keyup(function (e) {
                    if (e.key === 'Alt' || e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        $btn.click();
                    }
                })
            }
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
    $container.find('input.total_min').each(function () {
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

            $checkListContainer.find('.check_list_group_caption').each(function () {
                $(this).click(function (e) {
                    var $parent = $(this).findParentByClass('check_list_group');
                    if ($.isOk($parent)) {
                        var $checkBox = $parent.children('input[type="checkbox"]');
                        if ($checkBox.length) {
                            if ($checkBox.prop('checked')) {
                                $checkBox.prop('checked', false).change();
                            } else {
                                $checkBox.prop('checked', true).change();
                            }
                        }
                    }
                });
                $(this).find('.check_list_group_title').click(function (e) {
                    e.stopPropagation();
                    var $parent = $(this).findParentByClass('check_list_group');
                    if ($.isOk($parent)) {
                        if ($parent.hasClass('open')) {
                            $parent.children('.check_list_group_items').slideUp(250, function () {
                                $parent.removeClass('open').addClass('closed');
                            });
                        } else {
                            $parent.children('.check_list_group_items').slideDown(250, function () {
                                $parent.removeClass('closed').addClass('open');
                            });
                        }
                    }
                });
            });
            var $input = $checkListContainer.find('.check_list_search_input').find('input');
            if ($input.length) {
                $input.change(function () {
                    onChecklistSearchInputChange($(this));
                });
                $input.click(function (e) {
                    e.stopPropagation();
                    $input.change();
                });
                $input.keyup(function (e) {
                    if ($(this).hasClass('noEnterCheck')) {
                        $(this).removeClass('noEnterCheck');
                    } else {
                        if (e.key === 'Enter' || e.key === 'Tab') {
                            selectChecklistItem($(this).findParentByClass('check_list_container'), $(this).val());
                            $(this).val('').change();
                        } else if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
                            onChecklistSearchInputChange($(this));
                        }
                    }
                });
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
    $container.find('.compteur_caisse').each(function () {
        if (!parseInt($(this).data('compteur_event_init'))) {
            var $compteurContainer = $(this);
            var $inputs = $(this).find('input.compteur_caisse_input');
            $inputs.each(function () {
                $(this).focus(function () {
                    $(this).select();
                }).keydown(function (e) {
                    if (e.key === 'Tab' || e.key === 'Enter') {
                        var idx = parseInt($(this).data('idx')) + 1;
                        var $nextInput = $compteurContainer.find('input.compteur_caisse_input.idx_' + idx);
                        if (!$nextInput.length) {
                            $nextInput = $compteurContainer.find('input.compteur_caisse_input.idx_0');
                        }
                        $nextInput.focus();
                    }
                }).change(function () {
                    calcTotalCompteurCaisse($compteurContainer);
                });
            });
            $(this).data('compteur_event_init', 1);
        }
    });
    $container.find('.search_object_input_container').each(function () {
        if (!parseInt($(this).data('search_object_input_events_init'))) {
            var $search_container = $(this);
            if ($search_container.parent().hasClass('addValueInputContainer')) {
                $search_container.parent().find('.addValueBtn').hide();
                $search_container.find('.no_item_selected').hide().data('never_show', 1);
                $search_container.find('.search_object_result').hide().data('never_show', 1);
            }
            var $input = $(this).children('.search_object_input').children('input');
            if ($input.length) {
                $input.keyup(function (e) {
                    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') {
                        e.preventDefault();
                        var idx = parseInt($input.data('idx'));
                        if (isNaN(idx)) {
                            idx = 0;
                        }
                        idx++;
                        $input.data('idx', idx);
                        loadSearchObjectResults($input, idx);
                    }
                }).keydown(function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            }

            var $value_input = $(this).children('input.search_object_input_value');
            if ($value_input.length) {
                $value_input.change(function () {
                    var val = $(this).val();
                    if (val === '' || parseInt(val) === 0) {
                        var $search_input = $search_container.children('.search_object_input').children('input.search_object_search_input');
                        if ($search_input.length) {
                            $search_input.val('');
                        }
                    }
                });
            }

            $(this).data('search_object_input_events_init', 1);
        }
    });
    $container.find('.obj_filters_input_container').each(function () {
        if (!parseInt($(this).data('obj_filters_input_events_init'))) {
            var $filters_container = $(this);
            var obj_input_name = $filters_container.data('obj_input_name');
            if (obj_input_name) {
                var $input = $container.find('[name="' + obj_input_name + '"]');
                if ($input.length) {
                    $input.change(function () {
                        var module = '';
                        var object_name = '';
                        var val = $input.val();
                        if (val) {
                            var obj_data = val.split('-');
                            if (obj_data[0] && obj_data[1]) {
                                module = obj_data[0];
                                object_name = obj_data[1];
                            }
                        }

                        setFiltersInputObjectData($filters_container, module, object_name);
                    });
                }
            }

            setFiltersInputAddFilterFormEvents($filters_container);
            $(this).data('obj_filters_input_events_init', 1);
        }
    });
    $container.find('.allow_hashtags').each(function () {
        BIH.setEvents($(this));
    });
    $container.find('.signaturePadContainer').each(function () {
        var pad_id = $(this).data('pad_id');

        if (pad_id) {
            var $signaturePad = $(this).find('#' + pad_id);

            if ($signaturePad.length) {
                if ($signaturePad.findParentByClass('inputContainer').width()) {
                    $signaturePad.attr('width', $signaturePad.findParentByClass('inputContainer').width());
                } else {
                    $signaturePad.attr('width', '750px');
                }
                $signaturePad.attr('height', '350px');

                var signaturePad = new SignaturePad($signaturePad[0], {
                    backgroundColor: 'rgba(255, 255, 255, 0)',
                    penColor: 'rgb(0, 0, 0)'
                });

                bimpSignaturePads[pad_id] = signaturePad;

                $(this).find('.clearSignaturePadBtn').click(function () {
                    signaturePad.clear();
                });
            }
        }
    });
    $container.find('.bimp_drop_files_container').each(function () {
        BFU.initEvents($(this));
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

function setSelectDisplayHelpEvents($input) {
//    if ($input.tagName() === 'select') {
//        if (!$input.data('select_help_event_init')) {
//            var $inputContainer = $input.findParentByClass('inputContainer');
//            if ($.isOk($inputContainer) && $inputContainer.find('.selectOptionHelp').length) {
//                $input.change(function () {
//                    var $inputContainer = $input.findParentByClass('inputContainer');
//                    if ($.isOk($inputContainer)) {
//                        var field_name = $inputContainer.data('field_name');
//                        $inputContainer.find('div.selectOptionHelp').stop().hide().removeAttr('style');
//                        var option_value = $input.val();
//                        $inputContainer.find('div.' + field_name + '_help').each(function () {
//                            if ($(this).data('option_value') === option_value) {
//                                $(this).slideDown(250);
//                            }
//                        });
//                    }
//                });
//                $input.change();
//            }
//            $input.data('select_help_event_init', 1);
//        }
//    }
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

function setInputChoicesEvents($input, onItemSelected) {
    if ($.isOk($input)) {
        if (!parseInt($input.data('input_choices_events_init'))) {
//            $input.unbind('keydown');
            $input.keydown(function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }).keyup(function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                }

                var $choices = $input.parent().find('.input_choices');
                if ($.isOk($choices)) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        e.stopPropagation();
                        var $btn = $choices.find('.btn');
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
                            var $new = $choices.find('.btn').eq(n - 1);
                            if ($new.length) {
                                $btn.removeClass('selected');
                                $new.addClass('selected');
                            }
                        }
                    } else if (e.key === 'Enter') {
                        var $btn = $choices.find('.btn.selected');
                        if ($btn.length === 1) {
                            $btn.click();
                            $choices.remove();
                        }
                    }
                }
            });
            $input.data('input_choices_events_init', 1);
        }

        var $choices = $input.parent().find('.input_choices');
        if ($choices.length) {
            if (!parseInt($choices.data('input_choices_events_init'))) {
                $choices.find('.btn').each(function () {
                    $(this).click(function (e) {
                        e.stopPropagation();
                        if (typeof (onItemSelected) === 'function') {
                            onItemSelected($(this));
                        } else {
                            $input.val($(this).html());
                        }
                        $choices.remove();
                    });
                });
                setCommonEvents($choices.parent());
                $choices.data('input_choices_events_init', 1);
            }
        }
    }
}

function setFiltersInputAddFilterFormEvents($container) {
    $container.find('select.filter_type_select').each(function () {
        if (!parseInt($(this).data('filers_input_events_init'))) {
            $(this).data('filers_input_events_init', 1);
            $(this).change(function () {
                var $parent = $(this).findParentByClass('objectFiltersTypeSelect_content');
                if ($.isOk($parent)) {
                    var type = $(this).val();
                    $parent.children('.objectFilterItemsSelectContainer').each(function () {
                        if ($(this).data('type') === type) {
                            $(this).stop().slideDown(250);
                        } else {
                            $(this).stop().slideUp(250);
                        }
                        $(this).children('select').val('').change();
//                        $(this).children('.filter_item_options').html('').hide(); // A remplacer par contenu filtre? 
                    });
                }
            });
        }
    });
    $container.find('.objectFilterItemsSelectContainer').each(function () {
        if (!parseInt($(this).data('filers_input_events_init'))) {
            $(this).data('filers_input_events_init', 1);
            var type = $(this).data('type');
            var $select = $(this).children('select');
            var $itemOptions = $(this).children('.filter_item_options');
            if ($select.length) {
                $select.change(function () {
                    var item = $(this).val();
                    if (item) {
                        var $selectContainer = $(this).findParentByClass('objectFiltersSelect_container');
                        if ($.isOk($selectContainer)) {
                            var fields_prefixe = $selectContainer.data('fields_prefixe');
                            if (typeof (fields_prefixe) === 'undefined') {
                                fields_prefixe = '';
                            }

                            switch (type) {
                                case 'fields':
                                    // Sélection d'un champ objet:
                                    var $filtersInputContainer = $(this).findParentByClass('obj_filters_input_container');
                                    if ($.isOk($filtersInputContainer)) {
                                        var module = $filtersInputContainer.data('obj_module');
                                        var object_name = $filtersInputContainer.data('obj_name');
                                        BimpAjax('getFiltersInputAddFilterForm', {
                                            module: module,
                                            object_name: object_name,
                                            filter: fields_prefixe + item
                                        }, $itemOptions, {
                                            display_success: false,
                                            display_processing: true,
                                            processing_msg: '',
                                            processing_padding: 10,
                                            append_html: true,
                                            success: function (result, bimpAjax) {
                                                setBimpFiltersEvents(bimpAjax.$resultContainer);
                                            }
                                        });
                                    }
                                    break;
                                    // Sélection d'un objet lié; 
                                case 'linked_objects':
                                    var module = $selectContainer.data('module');
                                    var object_name = $selectContainer.data('object_name');
                                    var object_label = $(this).find('option[value="' + item + '"]').text();
                                    BimpAjax('getFiltersInputAddFiltersInput', {
                                        module: module,
                                        object_name: object_name,
                                        child_name: item,
                                        object_label: object_label,
                                        fields_prefixe: fields_prefixe
                                    }, $itemOptions, {
                                        display_success: false,
                                        display_processing: true,
                                        processing_msg: '',
                                        processing_padding: 10,
                                        append_html: true,
                                        success: function (result, bimpAjax) {
                                            setFiltersInputAddFilterFormEvents(bimpAjax.$resultContainer);
                                        }
                                    });
                                    break;
                            }
                        }
                    } else {
                        // Pour annuler un éventuel chargement ajax en cours: 
                        var ajax_refresh_idx = parseInt($itemOptions.data('ajax_refresh_idx'));
                        if (typeof (ajax_refresh_idx) !== 'undefined') {
                            ajax_refresh_idx++;
                            $itemOptions.data('ajax_refresh_idx', ajax_refresh_idx);
                        }
                        $itemOptions.html('').hide();
                    }
                });
            }
        }
    });
}

// Outils: 

function BimpInputHashtags() {
    var bih = this;
    this.$curInput = $();
    this.curInputCursorPos = 0;

    this.$modal = $();
    this.$objInput = $();
    this.$searchInput = $();
    this.$curValueLabel = $();
    this.$validateMsg = $();
    this.modalOpen = false;
    this.objects = {};
    this.aliases = {};
    this.refreshIdx = 0;
    this.curSearchIdx = 0;
    this.lastSearch = '';
    this.curObjKw = '';
    this.curValue = '';

    // Actions: 

    this.openModal = function ($input) {
        if (bih.modalOpen) {
            return;
        }

        bih.reset();
        bih.modalOpen = true;
        bih.$curInput = $input;
        bih.insertModal();
        bih.$modal.modal('show');
    };

    this.insertModal = function () {
        if (bih.$modal.length) {
            return;
        }

        var html = '';
        html += '<div class="modal ajax-modal" tabindex="-1" role="dialog" id="bih_autocomplete_modal">';
        html += '<div class="modal-dialog modal-md" role="document">';
        html += '<div class="modal-content">';

        html += '<div class="modal-header">';
        html += '<h4 class="modal-titles_container"><i class="fas fa5-link iconLeft"></i>Ajout d\'un lien objet</h4>';
        html += '</div>';

        html += '<div id="bih_autocomplete_modal_body" class="modal-body">';
        html += '</div>';

        html += '</div>';
        html += '</div>';
        html += '</div>';

        $('body').append(html);

        bih.$modal = $('#bih_autocomplete_modal');

        bih.$modal.on('shown.bs.modal', function (e) {
            if (bih.$modal.find('#bih_autocomplete_modal_content').length) {
                bih.$modal.find('#bihObjectLabel').html('').hide();
                bih.$objInput.val('').removeClass('no_results').show().focus();
                bih.$searchInput.val('').removeClass('no_results');
                bih.$curValueLabel.stop().html('').hide();
                bih.$validateMsg.hide();
            } else {
                BimpAjax('getHastagsAutocompleteModalContent', {}, $('#bih_autocomplete_modal_body'), {
                    display_success: false,
                    display_processing: true,
                    processing_msg: 'Chargement du formulaire',
                    processing_padding: 30,
                    append_html: true,
                    success: function (result, bimpAjax) {
                        if (result.html) {
                            if (typeof (result.objects) !== 'undefined') {
                                bih.objects = result.objects;
                            }
                            if (typeof (result.aliases) !== 'undefined') {
                                bih.aliases = result.aliases;
                            }

                            bih.$objInput = bih.$modal.find('input[name="bih_search_object_input"]');
                            bih.$searchInput = bih.$modal.find('input[name="bih_search_input"]');
                            bih.$curValueLabel = bih.$modal.find('#bihCurValueLabel');
                            bih.$validateMsg = bih.$modal.find('#bihValidateMsg');

                            bih.$objInput.focus();
                            bih.$objInput.keyup(function (e) {
                                if (e.key === 'Tab' || e.key === 'Enter') {
                                    if (e.key !== 'Enter' || !bih.$modal.find('#bih_search_object_input_input_choices').find('.selected').length) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        if ($('#bih_search_object_input_input_choices').find('.input_choice').length) {
                                            $('#bih_search_object_input_input_choices').find('.input_choice').first().click();
                                        }
                                    }
                                } else if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
                                    var val = $(this).val();
                                    if (val.length > 0) {
                                        bih.searchObj();
                                    }
                                }
                            }).keydown(function (e) {
                                if (e.key === 'Tab') {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                            });

                            bih.$modal.find('#bihObjectLabel').click(function (e) {
                                bih.cancelObjKeyword();
                            });

                            bih.$searchInput.keyup(function (e) {
                                if (e.key === 'Enter' && bih.curValue) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    bih.validate();
                                } else {
                                    var val = $(this).val();
                                    if (val.length > 1) {
                                        bih.curSearchIdx++;
                                        var searchIdx = bih.curSearchIdx;
                                        setTimeout(function () {
                                            if (bih.curSearchIdx === searchIdx) {
                                                bih.search();
                                            }
                                        }, 500);
                                    }
                                }
                            })
                        }
                    }
                });
            }
        });

        bih.$modal.on('hidden.bs.modal', function (e) {
            if (bih.modalOpen) {
                bih.modalOpen = false;
                bih.cancel();
            }

            if (bih.$curInput.length) {
                var inputTag = bih.$curInput.tagName();

                if (inputTag === 'input' || inputTag === 'textarea') {
                    bih.$curInput.focus();
                    if (!bih.curInputCursorPos) {
                        bih.curInputCursorPos = bih.$curInput.val().length;
                    }

                    if (bih.curInputCursorPos) {
                        setInputCursorPos(bih.$curInput, bih.curInputCursorPos);
                    }
                } else {
                    // Ne fonctionne pas... 
//                    var input_name = bih.$curInput.data('bih_input_name');
//                    if (input_name && typeof (CKEDITOR.instances[input_name]) !== 'undefined') {
//                        CKEDITOR.instances[input_name].focus();
//                        setCKEditorCursorPos(input_name, bih.curInputCursorPos);
//                    }
                }
            }

            bih.$curInput = $();
            bih.curInputCursorPos = 0;
            bih.reset();
        });

        $('body').keyup(function (e) {
            if (e.key === 'Escape') {
                if (bih.modalOpen) {
                    bih.cancel();
                }
            } else if (e.key === 'Enter') {
                if (bih.modalOpen && bih.curValue) {
                    e.preventDefault();
                    e.stopPropagation();
                    bih.validate();
                }
            }
        });
    };
    this.searchObj = function () {
        bih.$objInput.removeClass('no_results');
        var val = bih.$objInput.val();
        var choices = [];
        var results = {};

        if (/^[^ ]+ +$/.test(val)) {
            val = val.replace(/^([^ ]+) +$/, '$1');
        }

        if (val) {
            if (val === '#') {
                $('#bih_search_object_input_input_choices').remove();
                bih.setObjKeyword('Hashtag');
                return;
            }

            var regex = new RegExp('^(.*)(' + val + ')(.*)$', 'i');
            for (var obj in bih.objects) {
                if (regex.test(obj)) {
                    var label = obj.replace(regex, '$1<b>$2</b>$3');

                    results[obj] = bih.objects[obj] + label;
                }
            }

            for (var alias in bih.aliases) {
                if (regex.test(alias)) {
                    var alias_label = alias.replace(regex, '$1<b>$2</b>$3');

                    if (typeof (results[bih.aliases[alias]]) !== 'undefined') {
                        var obj_label = results[bih.aliases[alias]];

                        if (/^(.+) \(Alias: (.+)\)(.*)$/.test(obj_label)) {
                            alias_label = obj_label.replace(/^(.+) \(Alias: (.+)\)(.*)$/, '$1 (Alias: $2 - ' + alias_label + ')$3');
                        } else {
                            alias_label = obj_label + ' (Alias: ' + alias_label + ')';
                        }
                    } else {
                        alias_label = bih.objects[bih.aliases[alias]] + bih.aliases[alias] + ' (Alias: ' + alias_label + ')';
                    }

                    results[bih.aliases[alias]] = alias_label;
                }
            }
        }

        for (var obj_kw in results) {
            choices.push({
                'label': results[obj_kw],
                'value': obj_kw
            });
        }

        if (choices.length) {
            displayInputChoices(bih.$objInput, choices, function ($btn) {
                var value = $btn.data('item_value');

                if (value) {
                    bih.setObjKeyword(value);
                }
            });
            return;
        } else if (val) {
            bih.$objInput.addClass('no_results');
        }

        $('#bih_search_object_input_input_choices').remove();
    };
    this.setObjKeyword = function (obj_kw) {
        if (obj_kw === bih.curObjKw) {
            return;
        }

        bih.lastSearch = '';
        bih.curObjKw = obj_kw;
        bih.$objInput.val('').hide();

        bih.$modal.find('#bihObjectLabel').html(bih.objects[obj_kw] + obj_kw).css('display', 'inline-block');
        bih.$searchInput.focus();
    };
    this.cancelObjKeyword = function () {
        bih.curObjKw = '';
        bih.curValue = '';
        bih.lastSearch = '';
        bih.$modal.find('#bihObjectLabel').html('').hide();
        bih.$validateMsg.stop().slideUp(250);
        bih.$curValueLabel.stop().slideUp(250, function () {
            bih.$curValueLabel.html('');
        });
        bih.$objInput.val('').show().focus();
    };
    this.search = function () {
        if (bih.modalOpen) {
            var val = bih.$searchInput.val();

            if (val.replace(/^(.+) *$/, '$1') === bih.lastSearch) {
                return;
            }

            bih.curValue = '';
            bih.$curValueLabel.stop().slideUp(250, function () {
                bih.$curValueLabel.html('');
            });
            bih.$validateMsg.stop().slideUp(250);

            bih.refreshIdx++;

            if (val.length > 1) {
                bih.$modal.find('.spinner').css('opacity', 1);
                bih.lastSearch = val;

                bih.$searchInput.removeClass('no_results');

                BimpAjax('findHashtagResults', {
                    'search': val,
                    'obj_kw': bih.curObjKw
                }, null, {
                    refreshIdx: bih.refreshIdx,
                    display_success: false,
                    display_errors: true,
                    display_warnings: false,
                    success: function (result, bimpAjax) {
                        if (bih.refreshIdx !== bimpAjax.refreshIdx) {
                            return;
                        }
                        bih.$modal.find('.spinner').css('opacity', 0);

                        if (result.obj_kw !== bih.curObjKw) {
                            bih.setObjKeyword(result.obj_kw);
                        }

                        if (result.choices.length) {
                            bih.closeNewHashtagForm();
                            displayInputChoices(bih.$searchInput, result.choices, function ($btn) {
                                var value = $btn.data('item_value');

                                if (value) {
                                    bih.curValue = value;
                                    var label_html = '';

                                    if ($btn.hasClass('bs-popover')) {
                                        label_html = $btn.data('content');
                                    }

                                    if (!label_html) {
                                        label_html = '<span class="success"><i class="fas fa5-check"></i></span>&nbsp;&nbsp;';
                                        label_html += $btn.text();
                                    }

                                    bih.$curValueLabel.html(label_html).stop().slideDown(250);
                                    bih.$validateMsg.stop().slideDown(250);
                                }

                                $('#bih_search_input_input_choices').remove();
                            });
                        } else {
                            bih.$searchInput.addClass('no_results');
                            $('#bih_search_input_input_choices').remove();

                            if (bih.curObjKw === 'Hashtag') {
                                bih.openNewHashtagForm();
                            }
                        }
                    },
                    error: function (result, bimpAjax) {
                        if (bih.refreshIdx === bimpAjax.refreshIdx) {
                            bih.$modal.find('.spinner').css('opacity', 0);
                        }
                    }
                });
            }
        }
    };
    this.validate = function () {
        if (bih.curValue) {
            if (bih.$curInput.length) {
                var inputTag = bih.$curInput.tagName();

                var val = '';
                if (inputTag === 'input' || inputTag === 'textarea') {
                    val = bih.$curInput.val();
                } else {
                    val = bih.$curInput.html();
                }

//                val = val.replace("\n", "<br/>");
                var reg = new RegExp(/^(.* )?#(.*)$/, 'm');
                if (reg.test(val)) {

                    valN = val.replace(reg, '$1' + '{{' + bih.curValue + '}} ' + '$2');



//                    var newValBegin = val.replace(reg, '$1');
//                    console.log(newValBegin);
//                    newValBegin += '{{' + bih.curValue + '}} ';
//
//                    var newValEnd = val.replace(reg, '$2');
//
//                    if (newValEnd) {
//                        newValEnd = ' ' + newValEnd;
//                    }
//                    valN = val.replace('$0', newValBegin + newValEnd);
//                    
//                    console.log(newValEnd);
//                    newValBegin = newValBegin.replace("<br/>", "\n");
//                    newValEnd = newValEnd.replace("<br/>", "\n");

                    if (inputTag === 'input' || inputTag === 'textarea') {
                        bih.$curInput.val(valN);
                    } else {
                        bih.$curInput.html(valN);
                    }

//                    bih.curInputCursorPos = newValBegin.length;

                    bih.modalOpen = false;
                    bih.$modal.modal('hide');

                    bih.reset();
                }
            }
        }
    };
    this.cancel = function () {
        if (bih.$curInput.length) {
            var inputTag = bih.$curInput.tagName();

            var val = '';
            if (inputTag === 'input' || inputTag === 'textarea') {
                val = bih.$curInput.val();
            } else {
                val = bih.$curInput.html();
            }

            var val_before = val.replace(/^(.* )?#.*$/, '$1');
            if (val_before) {
                bih.curInputCursorPos = val_before.length;
            } else {
                bih.curInputCursorPos = 0;
            }

            val = val.replace(/(.* )?#/g, '$1');

            if (inputTag === 'input' || inputTag === 'textarea') {
                bih.$curInput.val(val);
            } else {
                bih.$curInput.html(val);
            }
        }

        if (bih.modalOpen) {
            bih.modalOpen = false;
            bih.$modal.modal('hide');
        }
    };
    this.reset = function () {
        bih.lastSearch = '';
        bih.curObjKw = '';
        bih.curValue = '';

        if (bih.$objInput.length) {
            bih.$objInput.val('').show().removeClass('no_results');
        }

        if (bih.$searchInput.length) {
            bih.$searchInput.val('').removeClass('no_results');
        }

        if (bih.$modal.length) {
            bih.$modal.find('#bihObjectLabel').html('').hide();
        }

        if (bih.$curValueLabel) {
            bih.$curValueLabel.stop().hide().html('');
        }

        if (bih.$validateMsg) {
            bih.$validateMsg.stop().hide();
        }

        bih.closeNewHashtagForm();
    };

    // Form New Hastag: 

    this.openNewHashtagForm = function () {
        if (bih.modalOpen && bih.$modal.length) {
            bih.resetNewHashtagForm();
            bih.$modal.find('#bihAddHastagFormContainer').stop().slideDown(250);
        }
    };
    this.closeNewHashtagForm = function () {
        if (bih.$modal.length) {
            bih.$modal.find('#bihAddHastagFormContainer').stop().slideUp(250);
            bih.resetNewHashtagForm();
        }
    };
    this.resetNewHashtagForm = function () {
        if (bih.$modal.length) {
            bih.$modal.find('input[name="bih_new_ht_code"]').val('');
            bih.$modal.find('input[name="bih_new_ht_label"]').val('');
            bih.$modal.find('input[name="bih_new_ht_description"]').val('');
        }
    };
    this.addHashtag = function () {
        if (bih.modalOpen && bih.$modal.length) {
            var fields = {};

            fields.code = bih.$modal.find('input[name="bih_new_ht_code"]').val();

            if (!fields.code) {
                bimp_msg('Veuillez saisir un mot-clé', 'danger', null, true);
            } else {
                fields.label = bih.$modal.find('input[name="bih_new_ht_label"]').val();
                fields.description = bih.$modal.find('input[name="bih_new_ht_description"]').val();

                saveObject('bimpcore', 'BimpHashtag', 0, fields, null, function (result) {
                    if (result.id_object) {
                        bih.closeNewHashtagForm();

                        if (bih.curObjKw === 'Hashtag') {
                            bih.curValue = 'Hastag:' + result.id_object;
                            bih.validate();
                        }
                    }
                }, false);
            }
        }
    };

    // Evénements: 

    this.setEvents = function ($input) {
        if (!parseInt($input.data('bih_events_init'))) {
            if ($input.hasClass('html_editor_hashtags')) {
                // Editeur HTML: 
                var field_name = $input.attr('name');

                if (field_name) {
                    var $container = $input.parent().find('#cke_' + field_name);

                    if (!$container.length) {
                        setTimeout(function () {
                            bih.setEvents($input);
                        }, 500);
                        return;
                    } else {
                        var input_name = $container.attr('id').replace(/^cke_(.+)$/, '$1');

                        // On réintialise les events sur chaque click du bouton "Source": 
                        var $btn = $container.find('.cke_button__source');
                        if ($btn.length) {
                            if (!parseInt($btn.data('bih_events_init'))) {
                                $btn.data('bih_input_name', input_name);
                                $btn.click(function (e) {
                                    var $container2 = $(this).findParentByClass('cke_editor_' + $btn.data('bih_input_name'));

                                    if ($.isOk($container2)) {
                                        var $input2 = $container2.parent().children('.html_editor_hashtags');

                                        if ($input2.length) {
                                            bih.setEvents($input2);
                                        }
                                    }
                                });

                                $btn.data('bih_events_init', 1);
                            }

                            if ($btn.hasClass('cke_button_off')) {
                                var $iframe_body = $container.find('iframe').contents().find('body');
                                if ($iframe_body.length) {
                                    if (!parseInt($iframe_body.data('bih_events_init'))) {
                                        $iframe_body.data('bih_input_name', input_name);

                                        $iframe_body.keyup(function (e) {
                                            var text = $(this).html();

                                            if (/^(.* )?#([ \s\r\t\n].*)?/.test(text)) {
                                                bih.openModal($(this));
                                            }
                                        });

                                        $iframe_body.data('bih_events_init', 1);
                                    }
                                } else {
                                    setTimeout(function () {
                                        bih.setEvents($input);
                                    }, 250);
                                    return;
                                }
                            } else {
                                var $src_input = $container.find('textarea.cke_source');

                                if ($src_input.length) {
                                    if (!parseInt($src_input.data('bih_events_init'))) {
                                        $src_input.data('bih_input_name', input_name);

                                        $src_input.keyup(function (e) {
                                            var text = $(this).val();

                                            if (/^(.* )?#( .*)?$/.test(text)) {
                                                bih.openModal($(this));
                                            }
                                        });

                                        $src_input.data('bih_events_init', 1);
                                    }
                                } else {
                                    setTimeout(function () {
                                        bih.setEvents($input);
                                    }, 250);
                                    return;
                                }
                            }
                        }
                    }
                }
            } else {
                // Input ou textarea classique: 
                $input.keyup(function (e) {
                    var text = $(this).val();
//                    text = text.replace("\n", ' ');
                    // Si un hashtag vient d'être frappé:
                    const regex2 = new RegExp(/^(.* )?#( .*)?$/, 'm');
                    if (regex2.test(text)) {
                        bih.openModal($(this));
                    }
                });
                $input.data('bih_events_init', 1);
            }
        }
    };
}

function BimpInputScanner() {
    var bis = this;
    this.$curInput = $();
    this.init = false;
    this.modalOpen = false;
    this.$modal = $();
    this.scanner = null;

    this.openModal = function ($input) {
        if (bis.modalOpen) {
            return;
        }

        bis.modalOpen = true;
        bis.$curInput = $input;
        bis.insertModal();
        bis.$modal.modal('show');
    };

    this.insertModal = function () {
        if (bis.$modal.length) {
            return;
        }

        if (typeof (Html5QrcodeScanner) === 'undefined') {
            $('body').append('<script type="text/javascript" src="' + dol_url_root + '/bimpcore/views/js/html5-qrcode.min.js"></script>');
        }

        var html = '';
        html += '<div class="modal ajax-modal" tabindex="-1" role="dialog" id="bis_scanner_modal">';
        html += '<div class="modal-dialog modal-sm" role="document">';
        html += '<div class="modal-content">';

        html += '<div class="modal-header">';
        html += '<h4 class="modal-titles_container"><i class="fas fa5-camera iconLeft"></i>Scan Code-barres ou QrCode</h4>';
        html += '</div>';

        html += '<div id="bis_scanner_modal_body" class="modal-body" style="text-align: center">';
        html += '<div style="width: 250px; margin: auto" id="bis_scanner"></div>';

        html += '<div style="margin-top: 30px">';
        html += '<span class="btn btn-danger btn-large" onclick="BIS.$modal.modal(\'hide\');">';
        html += '<i class="fas fa5-times iconLeft"></i>Annuler';
        html += '</span>';
        html += '</div>';

        html += '</div>';

        html += '</div>';
        html += '</div>';
        html += '</div>';

        $('body').append(html);

        bis.$modal = $('#bis_scanner_modal');

        bis.$modal.on('shown.bs.modal', function (e) {
            if (!bis.init) {
                bis.scanner = new Html5QrcodeScanner("bis_scanner", {fps: 10, qrbox: 250});
                bis.init = true;
            }
            bis.scanner.render(bis.onScanSuccess);
        });

        bis.$modal.on('hidden.bs.modal', function (e) {
            bis.modalOpen = false;
            bis.scanner.clear();
            bis.$curInput = $();
        });
    };

    this.onScanSuccess = function (decodedText, decodedResult) {
        if (bis.$curInput.length) {
            var inputTag = bis.$curInput.tagName();

            var val = '';
            if (inputTag === 'input' || inputTag === 'textarea') {
                val = bis.$curInput.val();
            } else {
                val = bis.$curInput.html();
            }

            if (val) {
                val += ' ';
            }

            val += decodedText;

            if (inputTag === 'input' || inputTag === 'textarea') {
                bis.$curInput.val(val);
                if (bis.$curInput.hasClass('search_object_search_input')) {
                    bis.$curInput.data('auto_select', 1);
                    bis.$curInput.trigger($.Event('keyup', {
                        key: ''
                    }));
                }
            } else {
                bis.$curInput.html(val);
            }
        }

        bis.$modal.modal('hide');
    };
}

function BimpFileUploader() {
    var ptr = this;
    this.initEvents = function ($container) {
        if ($.isOk($container)) {
            $container.find('input.add_file_input[type=file]').each(function () {
                if (!parseInt($(this).data('add_file_event_init'))) {
                    $(this).change(function (e) {
                        var $inputContainer = $(this).findParentByClass('inputContainer');
                        var $container = $(this).findParentByClass('bimp_drop_files_container');
                        var $area = null;
                        var field_name = '';

                        if ($.isOk($inputContainer)) {
                            field_name = $inputContainer.data('field_name');
                        }
                        if ($.isOk($container)) {
                            $area = $container.find('.bimp_drop_files_area');
                        }

                        ptr.updloadFiles(this.files, $area, field_name);
                        this.val('');
                    });
                    $(this).data('add_file_event_init', 1);
                }
            });
            $container.find('.bimp_drop_files_area').each(function () {
                if (!parseInt($(this).data('drop_area_events_init'))) {
                    $(window).on('dragover', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }).on('drop', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    $(this).on('dragover', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        $(this).addClass('hightlight');
                    });
                    $(this).on('dragleave', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        $(this).removeClass('hightlight');
                    });

                    $(this).on('drop', function (e) {
                        var files = e.originalEvent.dataTransfer.files;
                        e.preventDefault();
                        e.stopPropagation();

                        $(this).removeClass('hightlight');

                        if (!files.length) {
                            bimp_msg('Aucun fichier valide déposé', 'danger', null, true);
                        } else {
                            var field_name = '';

                            var $inputContainer = $(this).findParentByClass('inputContainer');
                            if ($.isOk($inputContainer)) {
                                field_name = $inputContainer.data('field_name');
                            }

                            ptr.updloadFiles(files, $(this), field_name);
                        }
                    });
                    $(this).data('drop_area_events_init', 1);
                }
            });
        }
    };

    this.checkFiles = function ($area, new_files) {
        var $container = $area.findParentByClass('bimp_drop_files_container');

        if (!$.isOk($container)) {
            bimp_msg('Une erreur est survenue - ajout de fichiers impossible', 'danger');
            console.error('BimpFileUploader::checkFiles() - $container absent');
            return false;
        }

        // Check du nombre max de fichiers: 
        var max_items = $container.data('max_items');

        if (typeof (max_items) !== 'undefined') {
            max_items = parseInt(max_items);
            var nb_files = new_files.length + $area.find('.file_item').length;

            if (max_items && nb_files > max_items) {
                var msg = 'Vous ne pouvez déposer ';
                if (max_items === 1) {
                    msg += 'qu\'un seul fichier';
                } else {
                    msg += 'que ' + max_items + ' fichiers';
                }
                bimp_msg(msg, 'warning', null, true);
                return false;
            }
        }

        // Check du type de fichier
        var type_check = true;

        var allowed_types = $container.data('allowed_types');
        if (allowed_types) {
            allowed_types = allowed_types.split(',');

            for (var i in new_files) {
                var file_ok = false;
                if (typeof (new_files[i].type) !== 'undefined') {
                    var file_data = new_files[i].type.split('/');
                    var file_type = file_data[0];

                    for (var j in allowed_types) {
                        if (file_type == allowed_types[j]) {
                            file_ok = true;
                            break;
                        }
                    }

                    if (!file_ok) {
                        bimp_msg('Type de fichier non autorisé pour le fichier "' + new_files[i].name + '"', 'danger', null, true);
                        type_check = false;
                    }
                }
            }
        }

        // Check de l'extension: 
        var ext_check = true;
        var allowed_ext = $container.data('allowed_ext');
        if (allowed_ext) {
            allowed_ext = allowed_ext.split(',');

            for (var i in new_files) {
                var file_ok = false;
                if (typeof (new_files[i].type) !== 'undefined') {
                    var file_data = new_files[i].type.split('/');
                    var ext = file_data[1];

                    for (var j in allowed_ext) {
                        if (ext == allowed_ext[j]) {
                            file_ok = true;
                            break;
                        }
                    }

                    if (!file_ok) {
                        bimp_msg('Extension non autorisée pour le fichier "' + new_files[i].name + '"', 'danger', null, true);
                        ext_check = false;
                    }
                }
            }
        }

        if (!type_check || !ext_check) {
            return false;
        }

        return true;
    }

    this.updloadFiles = function (files, $area, field_name) {
        if (!ptr.checkFiles($area, files)) {
            return;
        }

        var files_dir = '';

        if ($.isOk($area)) {
            var $container = $area.findParentByClass('bimp_drop_files_container');
            if ($.isOk($container)) {
                files_dir = $container.data('files_dir');
            }

            $area.find('.content-loading').show();
        }

        var formData = new FormData();
        for (var i in files) {
            formData.append('file_' + i, files[i]);
        }

        formData.append('field_name', field_name);
        formData.append('files_dir', files_dir);

        BimpAjax('uploadFiles', formData, null, {
            $area: $area,
            processData: false,
            contentType: false,
            display_success: false,
            success: function (result, bimpAjax) {
                if ($.isOk(bimpAjax.$area)) {
                    bimpAjax.$area.find('.content-loading').hide();

                    if (result.files.length) {
                        for (var i in result.files) {
                            if (result.files[i].item) {
                                bimpAjax.$area.find('.drop_files').append(result.files[i].item);
                            }
                        }
                    }
                }
            },
            error: function (result, bimpAjax) {
                if ($.isOk(bimpAjax.$area)) {
                    bimpAjax.$area.find('.content-loading').hide();
                }
            }
        });
    };

    this.removeItem = function ($button) {
        var $item = $button.findParentByClass('file_item');

        if ($.isOk($item)) {
            $item.remove();
        }
    };
}

var BIH = new BimpInputHashtags();
var BIS = new BimpInputScanner();
var BFU = new BimpFileUploader();

$(document).ready(function () {
    $('.object_form').each(function () {
        onFormLoaded($(this));
    });
    $.datepicker.setDefaults($.datepicker.regional[ "fr" ]);
    $('body').on('contentLoaded', function (e) {
        if (e.$container.length) {
            setInputsEvents(e.$container);
            e.$container.find('.object_form').each(function () {
                onFormLoaded($(this));
            });
        }
    });
});
