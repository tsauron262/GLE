function triggerObjectChange(module, object_name, id_object) {
    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    $('body').trigger($.Event('objectChange', {
        module: module,
        object_name: object_name,
        id_object: id_object
    }));
}

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
        data[i] = fields[i];
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
            success: function (result, bimpAjax) {
//                if (typeof (successCallBack) === 'function') {
//                    successCallBack(result);
//                }
                for (var i in result.objects_list) {
                    $('body').trigger($.Event('objectChange', {
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

function setObjectNewStatus($button, object_data, new_status, extra_data, $resultContainer, successCallback, confirm_msg) {
    // object_data.id_object peut être un array d'id
    if (typeof (confirm_msg) === 'string') {
        if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
            return;
        }
    }

    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    if (typeof ($resultContainer) === 'undefined') {
        $resultContainer = null;
    }

    var data = {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        new_status: new_status,
        extra_data: extra_data
    };

    BimpAjax('setObjectNewStatus', data, $resultContainer, {
        $button: $button,
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result, bimpAjax);
            }
            $('body').trigger($.Event('objectChange', {
                module: bimpAjax.module,
                object_name: bimpAjax.object_name,
                id_object: bimpAjax.id_object
            }));
        }
    });
}

function setObjectAction($button, object_data, action, extra_data, form_name, $resultContainer, successCallback, confirm_msg, on_form_submit, no_triggers, modal_format, modal_scroll_bottom, modal_title, use_bimpdatasync) {
    if (typeof (confirm_msg) === 'string') {
        if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
            return;
        }
    }

    if (typeof (no_triggers) === 'undefined') {
        no_triggers = false;
    }

    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    if (typeof ($resultContainer) === 'undefined') {
        $resultContainer = null;
    }

    if (typeof (modal_scroll_bottom) === 'undefined') {
        modal_scroll_bottom = false;
    }

    if (typeof (use_bimpdatasync) === 'undefined') {
        use_bimpdatasync = false;
    }

    if (typeof (modal_title) === 'undefined' || !modal_title) {
        if ($.isOk($button)) {
            if ($button.hasClass('rowButton')) {
                modal_title = $button.data('content');
            } else {
                modal_title = $button.text();
            }
        } else {
            modal_title = 'Action "' + action + '"';
        }
    }

    if (typeof (form_name) === 'string' && form_name) {
        if (typeof (modal_format) !== 'string') {
            modal_format = 'medium';
        }

        object_data.form_name = form_name;

        object_data.param_values = {
            fields: extra_data
        };

        loadModalForm($button, object_data, modal_title, function ($form) {
            if ($.isOk($form)) {
                var modal_idx = parseInt($form.data('modal_idx'));
                if (!modal_idx) {
                    bimp_msg('Erreur technique: index de la modale absent', 'danger', null, true);
                    return;
                }

                for (var field_name in extra_data) {
                    var $input = $form.find('[name="' + field_name + '"]');
                    if ($input.length) {
                        $input.val(extra_data[field_name]);
                    }
                }

                bimpModal.$footer.find('.save_object_button.modal_' + modal_idx).remove();
                bimpModal.$footer.find('.objectViewLink.modal_' + modal_idx).remove();
                bimpModal.addButton('Valider<i class="fa fa-arrow-circle-right iconRight"></i>', '', 'primary', 'set_action_button', modal_idx);
                bimpModal.$footer.find('.set_action_button.modal_' + modal_idx).click(function () {
                    if (validateForm($form)) {
                        $form.find('.inputContainer').each(function () {
                            field_name = $(this).data('field_name');
                            if ($(this).data('multiple')) {
                                field_name = $(this).data('values_field');
                            }
                            if (field_name) {
                                extra_data[field_name] = getInputValue($(this));
                            }
                        });
                        if (typeof (on_form_submit) === 'function') {
                            var returned_extra_data = on_form_submit($form, extra_data);

                            if (!returned_extra_data) {
                                return;
                            }

                            extra_data = returned_extra_data;
                        }
                        
                        
                        /*gestion des upload TODO problé easychrone */
                        var nbFile = 0;
                        var nbFileOk = 0;
                        var button = $(this);
                        $form.find('input[type=file]').each(function(){
                            var id = $(this).attr('id');
                            var name = $(this).attr('name');
                            if($('#'+id).val() != ''){
                                nbFile++;
                                button.addClass('disabled');
                                $('#'+id).simpleUpload(DOL_URL_ROOT + "/bimpcore/ajax/upload.php?id="+name, {
                                        start: function(file){
                                            $form.append('<div id="progressbar_'+id+'"></div>');
                                        },
                                        progress: function(progress){
                                                console.log(progress);
                                                $( "#progressbar_"+id).progressbar({value: progress});
                                        },
                                        success: function(data){
                                                nbFileOk++;
                                                if(nbFileOk == nbFile){
                                                    button.removeClass('disabled');
                                                    setObjectAction(button, object_data, action, extra_data, null, $('#' + $form.attr('id') + '_result'), function (result) {
                                                        if (typeof (result.allow_reset_form) === 'undefined' || !result.allow_reset_form) {
                                                            if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
                                                                bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx')).remove();
                                                            } else {
                                                                bimpModal.removeContent(parseInt($form.data('modal_idx')));
                                                            }
                                                        }
                                                        if (typeof (successCallback) === 'function') {
                                                            successCallback(result);
                                                        }
                                                    }, null, null, no_triggers, '', true, modal_title, use_bimpdatasync);
                                                }
                                        },
                                        error: function(error){
                                                alert('error upload file');
                                        }

                                });
                            }
                        });
                        /*fin gestion des upload*/
                        
                        
                        
                        
                        
                        if(nbFile == 0){//pas d'upload
                            setObjectAction($(this), object_data, action, extra_data, null, $('#' + $form.attr('id') + '_result'), function (result) {
                                if (typeof (result.allow_reset_form) === 'undefined' || !result.allow_reset_form) {
                                    if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
                                        bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx')).remove();
                                    } else {
                                        bimpModal.removeContent(parseInt($form.data('modal_idx')));
                                    }
                                }
                                if (typeof (successCallback) === 'function') {
                                    successCallback(result);
                                }
                            }, null, null, no_triggers, '', true, modal_title, use_bimpdatasync);
                        }
                    }
                });
                checkFormInputsReloads($form);
            }
        }, '', modal_format);
    } else {
        var data = {
            module: object_data.module,
            object_name: object_data.object_name,
            id_object: object_data.id_object,
            object_action: action,
            extra_data: extra_data
        };

        if (use_bimpdatasync) {
            if (typeof (bds_initObjectActionProcess) !== 'function') {
                $('body').append('<script type="text/javascript" src="' + dol_url_root + '/bimpdatasync/views/js/operations.js"></script>');
            }

            if (typeof (bds_initObjectActionProcess) !== 'function') {
                bimp_msg('Erreur: fonction d\'initialisation du processus absente', 'danger');
                return;
            }

            bds_initObjectActionProcess($button, data, modal_title, $resultContainer);
        } else {
            BimpAjax('setObjectAction', data, $resultContainer, {
                $button: $button,
                display_success_in_popup_only: true,
                display_warnings_in_popup_only: true,
                module: object_data.module,
                object_name: object_data.object_name,
                id_object: object_data.id_object,
                display_processing: true,
                processing_padding: 10,
                append_html: true,
                modal_scroll_bottom: modal_scroll_bottom,
                success: function (result, bimpAjax) {
                    if (typeof (successCallback) === 'function') {
                        successCallback(result);
                    }

                    if (!no_triggers) {
                        if (typeof (result.success_callback) !== 'string' ||
                                !/window\.location/.test(result.success_callback)) {
                            if (!$.isOk($resultContainer) || (typeof (result.html) === 'undefined') || !result.html) {
                                $('body').trigger($.Event('objectChange', {
                                    module: bimpAjax.module,
                                    object_name: bimpAjax.object_name,
                                    id_object: bimpAjax.id_object
                                }));
                            }
                        }
                    }
                }
            });
        }
    }
}

function displayProductStocks($button, id_product, id_entrepot) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $('.productStocksContainer').each(function () {
        $(this).html('').hide();
    });

    var $container = $button.parent().find('#product_' + id_product + '_stocks_popover_container');

    $container.show();

    BimpAjax('loadProductStocks', {
        id_product: id_product,
        id_entrepot: id_entrepot
    }, $container, {
        url: dol_url_root + '/bimpcore/index.php',
        $button: $button,
        display_processing: true,
        display_success: false,
        processing_msg: 'Chargement',
        processing_padding: 10,
        append_html: true,
        success: function (result, bimpAjax) {
            bimpAjax.$resultContainer.find('input[name="stockSearch"]').keyup(function (e) {
                var search = $(this).val();
                var regex = new RegExp(search, 'i');
                bimpAjax.$resultContainer.find('.productStockTable').children('tbody').children('tr').each(function () {
                    var label = $(this).children('td:first-child').text();
                    if (regex.test(label)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        }
    });
}

function loadObjectCard($container, module, object_name, id_object, card_name, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        card_name: card_name
    };

    BimpAjax('loadObjectCard', data, $container, {
        display_success: false,
        append_html: true,
        display_processing: true,
        processing_msg: '',
        processing_padding: 10,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result, bimpAjax);
            }
        }
    });
}

function loadModalObjectNotes($button, module, object_name, id_object, list_model, filter_by_user) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof (list_model) === 'undefined') {
        list_model = '';
    }

    if (typeof (filter_by_user) === 'undefined') {
        filter_by_user = 1;
    }

    bimpModal.loadAjaxContent($button, 'loadObjectNotes', {
        module: module,
        object_name: object_name,
        id_object: id_object,
        list_model: list_model,
        filter_by_user: filter_by_user
    }, "Messages", 'Chargement', function (result, bimpAjax) {
        setCommonEvents(bimpAjax.$resultContainer);
        setInputsEvents(bimpAjax.$resultContainer);
        bimpAjax.$resultContainer.find('.object_list').each(function () {
            onListLoaded($(this));
        });
    });
}

function loadObjectCustomContent($button, $resultContainer, object_data, method, method_params, success_callback) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }
    }

    if (typeof (method_params) === 'undefined') {
        method_params = {};
    }

    var display_processing = false;
    var processing_msg = '';
    var append_html = false;

    if ($.isOk($resultContainer)) {
        var display_processing = true;
        var processing_msg = 'Chargement';
        var append_html = true;
    }

    if (typeof (object_data.id_object) === 'undefined') {
        object_data.id_object = 0;
    }

    BimpAjax('loadObjectCustomContent', {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        method: method,
        params: method_params
    }, $resultContainer, {
        $button: $button,
        display_success: false,
        display_processing: display_processing,
        processing_msg: processing_msg,
        append_html: append_html,
        success_callback: success_callback,
        success: function (result, bimpAjax) {
            if (typeof (bimpAjax.success_callback) !== 'undefined') {
                bimpAjax.success_callback(result, bimpAjax);
            }
        }

    });
}

function loadModalObjectCustomContent($button, object_data, method, method_params, title, success_callback, modal_format) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }
        $button.hasClass('disabled');
    }

    if (typeof (method_params) === 'undefined') {
        method_params = {};
    }

    if (typeof (modal_format) === 'undefined') {
        modal_format = 'medium';
    }

    bimpModal.loadAjaxContent($button, 'loadObjectCustomContent', {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        method: method,
        params: method_params
    }, title, 'Chargement', success_callback, {}, modal_format);
}

function forceBimpObjectUnlock($button, object_data, $resultContainer) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof ($resultContainer)) {
        $resultContainer = $button.findParentByClass('ajaxResultContainer');
    }

    BimpAjax('forceBimpObjectUnlock', object_data, $resultContainer, {
        $button: $button,
        display_processing: true,
        processing_padding: 15,
        success_msg: 'Dévérouillage effectué avec succès'
    });
}





/*
 * simpleUpload.js v.1.1
 *
 * Copyright 2018, Michael Brook, All rights reserved.
 * http://simpleupload.michaelcbrook.com/
 *
 * simpleUpload.js is an extremely simple yet powerful jQuery file upload plugin.
 * It is free to use under the MIT License (http://opensource.org/licenses/MIT)
 *
 * https://github.com/michaelcbrook/simpleUpload.js
 * @michaelcbrook
 */
function simpleUpload(e,l,n){var t=!1,a=null,o=0,r=0,i=[],s=[],p="auto",u=null,f=null,d="file",c={},m={},h=function(e){},v=function(e){},y=function(e){},U=function(e){},g=function(e){},b=function(){},w=function(e){},x=function(){},j=function(e,l){},k=[],E=[],S={files:k},z=0,F=null,T=function(e,l){M(e,l),0==--z&&D(),simpleUpload.activeUploads--,simpleUpload.uploadNext()},C=function(e){return h.call(S,e)},I=function(e,l){return!(R(e)>0)&&(!1===v.call(k[e],l)?(O(e,4),!1):!(R(e)>0)&&void O(e,1))},L=function(e,l){1==R(e)&&y.call(k[e],l)},q=function(e,l){1==R(e)&&(O(e,2),U.call(k[e],l),T(e,"success"))},W=function(e,l){1==R(e)&&(O(e,3),g.call(k[e],l),T(e,"error"))},_=function(e){b.call(k[e]),T(e,"cancel")},M=function(e,l){w.call(k[e],l)},D=function(){x.call(S),null!=F&&F.remove()},N=function(e,l,n){j.call(k[e],l,n)};function A(n){if(1==R(n)){if(null!=a){if(null==a[n]||null==a[n])return void W(n,{name:"InternalError",message:"There was an error uploading the file"});if(window.FormData){var t=$.ajaxSettings.xhr();if(t.upload){var o=a[n],r=new FormData;!function e(l,n,t){null!=t&&""!==t||(t=null);for(var a in n)void 0===n[a]||null===n[a]?l.append(null==t?a+"":t+"["+a+"]",""):"object"==typeof n[a]?e(l,n[a],null==t?a+"":t+"["+a+"]"):"boolean"==typeof n[a]?l.append(null==t?a+"":t+"["+a+"]",n[a]?"true":"false"):"number"==typeof n[a]?l.append(null==t?a+"":t+"["+a+"]",n[a]+""):"string"==typeof n[a]&&l.append(null==t?a+"":t+"["+a+"]",n[a])}(r,c),r.append(d,o);var i={url:e,data:r,type:"post",cache:!1,xhrFields:m,beforeSend:function(e,l){N(n,e,l),E[n].xhr=e},xhr:function(){return t.upload.addEventListener("progress",function(e){e.lengthComputable&&L(n,e.loaded/e.total*100)},!1),t},error:function(e){E[n].xhr=null,W(n,{name:"RequestError",message:"Upload failed",xhr:e})},success:function(e){E[n].xhr=null,L(n,100),q(n,e)},contentType:!1,processData:!1};return"auto"!=p&&(i.dataType=p),void $.ajax(i)}}}"object"==typeof l&&null!==l?function(l){if(0==l){var n=simpleUpload.queueIframe({origin:function(e){var l=document.createElement("a");l.href=e;var n=l.host,t=l.protocol;""==n&&(n=window.location.host);""!=t&&":"!=t||(t=window.location.protocol);return t.replace(/\:$/,"")+"://"+n}(e),expect:p,complete:function(e){1==R(l)&&(E[l].iframe=null,simpleUpload.dequeueIframe(n),L(l,100),q(l,e))},error:function(e){1==R(l)&&(E[l].iframe=null,simpleUpload.dequeueIframe(n),W(l,{name:"RequestError",message:e}))}});E[l].iframe=n;var t=function e(l,n){null!=n&&""!==n||(n=null);var t="";for(var a in l)void 0===l[a]||null===l[a]?t+=$("<div>").append($('<input type="hidden">').attr("name",null==n?a+"":n+"["+a+"]").val("")).html():"object"==typeof l[a]?t+=e(l[a],null==n?a+"":n+"["+a+"]"):"boolean"==typeof l[a]?t+=$("<div>").append($('<input type="hidden">').attr("name",null==n?a+"":n+"["+a+"]").val(l[a]?"true":"false")).html():"number"==typeof l[a]?t+=$("<div>").append($('<input type="hidden">').attr("name",null==n?a+"":n+"["+a+"]").val(l[a]+"")).html():"string"==typeof l[a]&&(t+=$("<div>").append($('<input type="hidden">').attr("name",null==n?a+"":n+"["+a+"]").val(l[a])).html());return t}(c);F.attr("action",e+(-1==e.lastIndexOf("?")?"?":"&")+"_iframeUpload="+n+"&_="+(new Date).getTime()).attr("target","simpleUpload_iframe_"+n).prepend(t).submit()}else W(l,{name:"UnsupportedError",message:"Multiple file uploads not supported"})}(n):W(n,{name:"UnsupportedError",message:"Your browser does not support this upload method"})}}function R(e){return E[e].state}function O(e,l){var n="";if(0==l)n="init";else if(1==l)n="uploading";else if(2==l)n="success";else if(3==l)n="error";else{if(4!=l)return!1;n="cancel"}E[e].state=l,k[e].upload.state=n}function B(e){var l=e.lastIndexOf(".");return-1!=l?e.substr(l+1):""}function J(e){return!isNaN(e)&&parseInt(e)+""==e}!function(){if("object"==typeof n&&null!==n){if("boolean"==typeof n.forceIframe&&(t=n.forceIframe),"function"==typeof n.init&&(h=n.init),"function"==typeof n.start&&(v=n.start),"function"==typeof n.progress&&(y=n.progress),"function"==typeof n.success&&(U=n.success),"function"==typeof n.error&&(g=n.error),"function"==typeof n.cancel&&(b=n.cancel),"function"==typeof n.complete&&(w=n.complete),"function"==typeof n.finish&&(x=n.finish),"function"==typeof n.beforeSend&&(j=n.beforeSend),"string"==typeof n.hashWorker&&""!=n.hashWorker&&(u=n.hashWorker),"function"==typeof n.hashComplete&&(f=n.hashComplete),"object"==typeof n.data&&null!==n.data)for(var e in n.data)c[e]=n.data[e];if("number"==typeof n.limit&&J(n.limit)&&n.limit>0&&(o=n.limit),"number"==typeof n.maxFileSize&&J(n.maxFileSize)&&n.maxFileSize>0&&(r=n.maxFileSize),"object"==typeof n.allowedExts&&null!==n.allowedExts)for(var e in n.allowedExts)i.push(n.allowedExts[e]);if("object"==typeof n.allowedTypes&&null!==n.allowedTypes)for(var e in n.allowedTypes)s.push(n.allowedTypes[e]);if("string"==typeof n.expect&&""!=n.expect){var S=n.expect.toLowerCase(),T=["auto","json","xml","html","script","text"];for(var e in T)if(T[e]==S){p=S;break}}if("object"==typeof n.xhrFields&&null!==n.xhrFields)for(var e in n.xhrFields)m[e]=n.xhrFields[e]}if("object"==typeof l&&null!==l&&l instanceof jQuery){if(!(l.length>0))return!1;l=l.get(0)}if(!t&&window.File&&window.FileReader&&window.FileList&&window.Blob&&("object"==typeof n&&null!==n&&"object"==typeof n.files&&null!==n.files?a=n.files:"object"==typeof l&&null!==l&&"object"==typeof l.files&&null!==l.files&&(a=l.files)),("object"!=typeof l||null===l)&&null==a)return!1;"object"==typeof n&&null!==n&&"string"==typeof n.name&&""!=n.name?d=n.name.replace(/\[\s*\]/g,"[0]"):"object"==typeof l&&null!==l&&"string"==typeof l.name&&""!=l.name&&(d=l.name.replace(/\[\s*\]/g,"[0]"));var M=0;if(null!=a?a.length>0&&(M=a.length>1&&window.FormData&&$.ajaxSettings.xhr().upload?o>0&&a.length>o?o:a.length:1):""!=l.value&&(M=1),M>0){if("object"==typeof l&&null!==l){var N=$(l);F=$("<form>").hide().attr("enctype","multipart/form-data").attr("method","post").appendTo("body"),N.after(N.clone(!0).val("")).removeAttr("onchange").off().removeAttr("id").attr("name",d).appendTo(F)}for(var Q=0;Q<M;Q++)!function(e){E[e]={state:0,hashWorker:null,xhr:null,iframe:null},k[e]={upload:{index:e,state:"init",file:null!=a?a[e]:{name:l.value.split(/(\\|\/)/g).pop()},cancel:function(){if(0==R(e))O(e,4);else{if(1!=R(e))return!1;O(e,4),null!=E[e].hashWorker&&(E[e].hashWorker.terminate(),E[e].hashWorker=null),null!=E[e].xhr&&(E[e].xhr.abort(),E[e].xhr=null),null!=E[e].iframe&&($("iframe[name=simpleUpload_iframe_"+E[e].iframe+"]").attr("src","javascript:false;"),simpleUpload.dequeueIframe(E[e].iframe),E[e].iframe=null),_(e)}return!0}}}}(Q);var H=C(M);if(!1!==H){var X=M;if("number"==typeof H&&J(H)&&H>=0&&H<M)for(var Y=X=H;Y<M;Y++)O(Y,4);for(var G=[],K=0;K<X;K++)!1!==I(K,k[K].upload.file)&&(G[G.length]=K);G.length>0?(z=G.length,simpleUpload.queueUpload(G,function(e){!function(e){if(1==R(e)){var n=null;if(null!=a){if(null==a[e]||null==a[e])return void W(e,{name:"InternalError",message:"There was an error uploading the file"});n=a[e]}else if(""==l.value)return void W(e,{name:"InternalError",message:"There was an error uploading the file"});i.length>0&&!function(e,n){if(null!=n&&null!=n){var t=n.name;if(null!=t&&null!=t&&""!=t){var a=B(t).toLowerCase();if(""!=a){var o=!1;for(var r in e)if(e[r].toLowerCase()==a){o=!0;break}return!!o}return!1}}if("object"!=typeof l||null===l)return!0;var i=l.value;if(""!=i){var a=B(i).toLowerCase();if(""!=a){var o=!1;for(var r in e)if(e[r].toLowerCase()==a){o=!0;break}if(o)return!0}}return!1}(i,n)?W(e,{name:"InvalidFileExtensionError",message:"That file format is not allowed"}):s.length>0&&!function(e,l){if(null!=l&&null!=l){var n=l.type;if(null!=n&&null!=n&&""!=n){n=n.toLowerCase();var t=!1;for(var a in e)if(e[a].toLowerCase()==n){t=!0;break}return!!t}}return!0}(s,n)?W(e,{name:"InvalidFileTypeError",message:"That file format is not allowed"}):r>0&&!function(e,l){if(null!=l&&null!=l){var n=l.size;if(null!=n&&null!=n&&""!=n&&J(n))return n<=e}return!0}(r,n)?W(e,{name:"MaxFileSizeError",message:"That file is too big"}):null!=u&&null!=f?function(e){if(null!=a&&null!=a[e]&&null!=a[e]&&window.Worker){var l=a[e];if(null!=l.size&&null!=l.size&&""!=l.size&&J(l.size)&&(l.slice||l.webkitSlice||l.mozSlice))try{var n,t,o,r,i,s,p=new Worker(u);return p.addEventListener("error",function(l){p.terminate(),E[e].hashWorker=null,A(e)},!1),p.addEventListener("message",function(l){if(l.data.result){var n=l.data.result;p.terminate(),E[e].hashWorker=null,function(e,l){if(1==R(e)){var n=!1;f.call(k[e],l,{success:function(l){return 1==R(e)&&!n&&(n=!0,L(e,100),q(e,l),!0)},proceed:function(){return 1==R(e)&&!n&&(n=!0,A(e),!0)},error:function(l){return 1==R(e)&&!n&&(n=!0,W(e,{name:"HashError",message:l}),!0)}})}}(e,n)}},!1),s=function(e){p.postMessage({message:e.target.result,block:t})},i=function(e){t.end!==l.size&&(t.start+=n,t.end+=n,t.end>l.size&&(t.end=l.size),(o=new FileReader).onload=s,l.slice?r=l.slice(t.start,t.end):l.webkitSlice?r=l.webkitSlice(t.start,t.end):l.mozSlice&&(r=l.mozSlice(t.start,t.end)),o.readAsArrayBuffer(r))},n=1048576,(t={file_size:l.size,start:0}).end=n>l.size?l.size:n,p.addEventListener("message",i,!1),(o=new FileReader).onload=s,l.slice?r=l.slice(t.start,t.end):l.webkitSlice?r=l.webkitSlice(t.start,t.end):l.mozSlice&&(r=l.mozSlice(t.start,t.end)),o.readAsArrayBuffer(r),void(E[e].hashWorker=p)}catch(e){}}A(e)}(e):A(e)}}(e)}),simpleUpload.uploadNext()):D()}else{for(var Y in k)O(Y,4);D()}}}()}simpleUpload.maxUploads=10,simpleUpload.activeUploads=0,simpleUpload.uploads=[],simpleUpload.iframes={},simpleUpload.iframeCount=0,simpleUpload.queueUpload=function(e,l){simpleUpload.uploads[simpleUpload.uploads.length]={uploads:e,callback:l}},simpleUpload.uploadNext=function(){if(simpleUpload.uploads.length>0&&simpleUpload.activeUploads<simpleUpload.maxUploads){var e=simpleUpload.uploads[0],l=e.callback,n=e.uploads.splice(0,1)[0];0==e.uploads.length&&simpleUpload.uploads.splice(0,1),simpleUpload.activeUploads++,l(n),simpleUpload.uploadNext()}},simpleUpload.queueIframe=function(e){for(var l=0;0==l||l in simpleUpload.iframes;)l=Math.floor(999999999*Math.random()+1);return simpleUpload.iframes[l]=e,simpleUpload.iframeCount++,$("body").append('<iframe name="simpleUpload_iframe_'+l+'" style="display: none;"></iframe>'),l},simpleUpload.dequeueIframe=function(e){e in simpleUpload.iframes&&($("iframe[name=simpleUpload_iframe_"+e+"]").remove(),delete simpleUpload.iframes[e],simpleUpload.iframeCount--)},simpleUpload.convertDataType=function(e,l,n){var t="auto";if("auto"==e){if("string"==typeof l&&""!=l){var a=l.toLowerCase(),o=["json","xml","html","script","text"];for(var r in o)if(o[r]==a){t=a;break}}}else t=e;if("auto"==t)return void 0===n?"":"object"==typeof n?n:String(n);if("json"==t){if(null==n)return null;if("object"==typeof n)return n;if("string"==typeof n)try{return $.parseJSON(n)}catch(e){return!1}return!1}if("xml"==t){if(null==n)return null;if("string"==typeof n)try{return $.parseXML(n)}catch(e){return!1}return!1}if("script"==t){if(void 0===n)return"";if("string"==typeof n)try{return $.globalEval(n),n}catch(e){return!1}return!1}return void 0===n?"":String(n)},simpleUpload.iframeCallback=function(e){if("object"==typeof e&&null!==e){var l=e.id;if(l in simpleUpload.iframes){var n=simpleUpload.convertDataType(simpleUpload.iframes[l].expect,e.type,e.data);!1!==n?simpleUpload.iframes[l].complete(n):simpleUpload.iframes[l].error("Upload failed")}}},simpleUpload.postMessageCallback=function(e){try{var l=e[e.message?"message":"data"];if("string"==typeof l&&""!=l&&"object"==typeof(l=$.parseJSON(l))&&null!==l&&"string"==typeof l.namespace&&"simpleUpload"==l.namespace){var n=l.id;if(n in simpleUpload.iframes&&e.origin===simpleUpload.iframes[n].origin){var t=simpleUpload.convertDataType(simpleUpload.iframes[n].expect,l.type,l.data);!1!==t?simpleUpload.iframes[n].complete(t):simpleUpload.iframes[n].error("Upload failed")}}}catch(e){}},window.addEventListener?window.addEventListener("message",simpleUpload.postMessageCallback,!1):window.attachEvent("onmessage",simpleUpload.postMessageCallback),function(e){"function"==typeof define&&define.amd?define(["jquery"],e):"object"==typeof exports?module.exports=e(require("jquery")):e(jQuery)}(function(e){e.fn.simpleUpload=function(l,n){return 0==e(this).length&&"object"==typeof n&&null!==n&&"object"==typeof n.files&&null!==n.files?(new simpleUpload(l,null,n),this):this.each(function(){new simpleUpload(l,this,n)})},e.fn.simpleUpload.maxSimultaneousUploads=function(e){return void 0===e?simpleUpload.maxUploads:"number"==typeof e&&e>0?(simpleUpload.maxUploads=e,this):void 0}});