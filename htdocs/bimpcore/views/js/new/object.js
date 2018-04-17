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

    $button.addClass('disabled');

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
        success: function (result) {
            $button.removeClass('disabled');
        },
        error: function () {
            $button.removeClass('disabled');
        }
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
        $button.addClass('disabled');
        var data = {
            'module': module,
            'object_name': object_name,
            'objects': [id_object]
        };

        BimpAjax('deleteObjects', data, $resultContainer, {
            success: function (result) {
                $button.removeClass('disabled');
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
            },
            error: function () {
                $button.removeClass('disabled');
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