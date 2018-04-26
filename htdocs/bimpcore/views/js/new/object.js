function saveObject(module, object_name, id_object, fields, $resultContainer, successCallback, display_success) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
    };

    if (typeof (display_success) === 'undefined') {
        display_success = true;
    }

    for (var i in fields) {
        data[i] = fields[i]
    }

    BimpAjax('saveObject', data, $resultContainer, {
        display_success: display_success,
        success: function (result, bimpAjax) {
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

function saveObjectField(module, object_name, id_object, field, value, $resultContainer, successCallback, display_success) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        value: value
    };

    if (typeof (display_success) === 'undefined') {
        display_success = true;
    }

    BimpAjax('saveObjectField', data, $resultContainer, {
        display_success: display_success,
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

function saveObjectAssociations(id_object, object_name, association, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $resultContainer = $('#' + object_name + '_' + association + '_associatonsAjaxResult');
    if (!$resultContainer.length) {
        $resultContainer = null;
    }
    var list = [];

    $('#' + object_name + '_' + association + '_associations_list').find('input[type=checkbox]').each(function () {
        if ($(this).prop('checked')) {
            list.push(parseInt($(this).val()));
        }
    });

    var data = {
        'id_object': id_object,
        'object_name': object_name,
        'association': association,
        'list': list
    };

    BimpAjax('saveObjectAssociations', data, $resultContainer, {
        $button: $button
    });
}

function deleteObject($button, module, object_name, id_object, $resultContainer, successCallBack) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var msg = 'Voulez-vous vraiment supprimer ';

    if (typeof (object_labels[object_name]) !== 'undefined') {
        msg += object_labels[object_name]['the'] + ' n°' + id_object;
    } else {
        msg += 'cet objet?';
    }

    if (confirm(msg)) {
        var data = {
            'module': module,
            'object_name': object_name,
            'objects': [id_object]
        };

        BimpAjax('deleteObjects', data, $resultContainer, {
            $button: $button,
            success: function (result) {
                if (typeof (successCallBack) === 'function') {
                    successCallBack(result);
                }
                for (var i in result.objects_list) {
                    $('body').trigger($.Event('objectDelete', {
                        module: result.module,
                        object_name: result.object_name,
                        id_object: result.objects_list[i]
                    }));
                }
            }
        });
    }
}

function loadObjectFieldValue(module, object_name, id_object, field, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field
    };

    BimpAjax('loadObjectFieldValue', data, $resultContainer, {
        display_success: false,
        success: function (result) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}

function setObjectNewStatus(module, object_name, id_object, new_status, $resultContainer, successCallback, extra_data) {
    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        new_status: new_status,
        extra_data: extra_data
    };

    BimpAjax('setObjectNewStatus', data, $resultContainer, {
        module: module,
        object_name: object_name,
        id_object: id_object,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
            $('body').trigger($.Event('objectChange', {
                module: bimpAjax.module,
                object_name: bimpAjax.object_name,
                id_object: bimpAjax.id_object
            }));
        }
    });
}

function setObjectAction($button, object_data, action, extra_data, form_name, $resultContainer, successCallback, confirm_msg) {
    if (typeof (confirm_msg) === 'string') {
        if (!confirm(confirm_msg.replace('&quotes;', '"'))) {
            return;
        }
    }

    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    if (typeof ($resultContainer) === 'undefined') {
        $resultContainer = null;
    }

    if (typeof (form_name) === 'string') {
        object_data.form_name = form_name;
        var title = '';
        if ($.isOk($button)) {
            title = $button.text();
        }
        loadModalForm($button, object_data, title, function () {
            var $modal = $('#page_modal');
            if ($modal.length) {
                var $form = $modal.find('.modal-ajax-content').find('.object_form');
                if ($form.length) {
                    for (var field_name in extra_data) {
                        var $input = $form.find('[name="' + field_name + '"]');
                        if ($input.length) {
                            $input.val(extra_data[field_name]);
                        }
                    }
                    $modal.find('.modal-footer').find('.save_object_button').remove();
                    $modal.find('.modal-footer').find('.objectViewLink').remove();

                    var button_html = '<button type="button" class="extra_button btn btn-primary set_action_button">';
                    button_html += 'Envoyer<i class="fa fa-arrow-circle-right iconRight"></i></button>';
                    $modal.find('.modal-footer').append(button_html);
                    $modal.find('.modal-footer').find('.set_action_button').click(function () {
                        $form.find('.inputContainer').each(function () {
                            var field_name = $(this).data('field_name');
                            if ($(this).find('.cke').length) {
                                var html_value = $('#cke_' + field_name).find('iframe').contents().find('body').html();
                                $(this).find('[name="' + field_name + '"]').val(html_value);
                            }
                            extra_data[field_name] = $(this).find('[name="' + field_name + '"]').val();
                        });
                        setObjectAction($(this), object_data, action, extra_data, null, $('#' + $form.attr('id') + '_result'), function () {
                            $modal.modal('hide');
                            if (typeof (successCallback) === 'function') {
                                successCallback();
                            }
                        });
                    });
                }
            }
        });
    } else {
        var data = {
            module: object_data.module,
            object_name: object_data.object_name,
            id_object: object_data.id_object,
            object_action: action,
            extra_data: extra_data
        };

        BimpAjax('setObjectAction', data, $resultContainer, {
            $button: $button,
            display_success_in_popup_only: true,
            module: object_data.module,
            object_name: object_data.object_name,
            id_object: object_data.id_object,
            display_processing: true,
            processing_padding: 20,
            success: function (result, bimpAjax) {
                if (typeof (successCallback) === 'function') {
                    successCallback(result);
                }
                $('body').trigger($.Event('objectChange', {
                    module: bimpAjax.module,
                    object_name: bimpAjax.object_name,
                    id_object: bimpAjax.id_object
                }));
            }
        });
    }
}