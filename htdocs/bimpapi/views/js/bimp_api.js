var next_bimpapi_ajax_id = 1;
var bimpapi_logged = [];
var bimpapi_nologged_requests = [];

function BimpApiAjax(api_name, api_idx, method, request_name, api_params, $resultContainer, request_options, ajax_params) {
    if (typeof (ajax_params.confirm_msg) !== 'undefined' && ajax_params.confirm_msg) {
        if (!confirm(ajax_params.confirm_msg)) {
            return;
        }
    }

    var apiAjax = this;
    this.id = next_bimpapi_ajax_id;
    this.api_name = api_name;
    this.api_idx = api_idx;
    next_bimpapi_ajax_id++;

    if (typeof (api_params) === 'undefined') {
        api_params = {};
    }

    if (typeof (request_options) === 'undefined') {
        request_options = {};
    }

    if (typeof (ajax_params) === 'undefined') {
        ajax_params = {};
    }

    if (typeof (ajax_params.url) === 'undefined' || !ajax_params.url) {
        ajax_params.url = dol_url_root + '/bimpapi/index.php';
    }

    this.action = 'bimpApiRequest';

    if (method !== 'apiProcessRequestForm') {
        this.data = {
            api_name: api_name,
            api_idx: api_idx,
            api_method: method,
            api_requestName: request_name,
            api_options: request_options,
            api_params: api_params
        };
    } else {
        this.data = api_params;
    }

    this.$resultContainer = $resultContainer;
    this.params = ajax_params;

    if (typeof (ajax_params.success) === 'function') {
        this.params.request_success = ajax_params.success;
    }

    this.params.success = function (result, bimpAjax) {
        if (typeof (result.api_no_logged) !== 'undefined' && parseInt(result.api_no_logged)) {
            bimpAjax.apiAjax.nologged();
        } else if (typeof (bimpAjax.apiAjax.params.request_success) === 'function') {
            bimpAjax.apiAjax.params.request_success(result, bimpAjax);
        }
    };

    this.params.apiAjax = this;

    if (typeof (bimpapi_logged[api_name]) === 'undefined') {
        bimpapi_logged[api_name] = true;
    }

    this.send = function () {
        if (bimpapi_logged[api_name]) {
            BimpAjax(apiAjax.action, apiAjax.data, apiAjax.$resultContainer, apiAjax.params);
        } else {
            apiAjax.nologged();
        }
    };

    this.nologged = function () {
        bimpapi_logged[api_name] = false;
        bimpapi_nologged_requests[apiAjax.id] = apiAjax;
        BimpApi.openLoginModal(null, apiAjax.api_name);
    };

    this.send();
}

function BimpApi() {

    this.ajaxRequest = function ($button, api_name, api_idx, request_name, fields, $result_container, options, success_callback, confirm_msg) {
        if ($button.hasClass('disabled')) {
            return;
        }

        BimpApiAjax(api_name, api_idx, 'apiProcessRequest', request_name, {'fields': fields}, $result_container, options, {
            $button: $button,
            success: success_callback
        });
    };

    this.openLoginModal = function ($button, api_name) {
        if (!$.isOk($button)) {
            $button = $('');
        }
    };

    this.loadRequestModalForm = function ($button, title, api_name, api_idx, request_name, ajax_data, api_params, request_options, success_callback) {
        if ($.isOk($button) && $button.hasClass('disabled')) {
            return;
        }

        if (typeof (ajax_data) === 'undefined') {
            ajax_data = {};
        }

        if (typeof (api_params) === 'undefined') {
            api_params = {};
        }

        if (typeof (request_options) === 'undefined') {
            request_options = {};
        }

        bimpModal.newContent(title, '', false, '', null, 'medium');

        var modal_idx = bimpModal.idx;
        var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

        var ajax_params = {
            $button: $button,
            modal_idx: modal_idx,
            display_success: false,
            display_processing: true,
            processing_msg: 'Chargement du formulaire',
            append_html: true,
            success: function (result, bimpAjax) {
                if (typeof (result.html) !== 'undefined' && result.html) {
                    var $content = bimpModal.$contents.find('#modal_content_' + bimpAjax.modal_idx);
                    if ($.isOk($content)) {
                        var $form = $content.find('.bimp_api_request_form');
                        if ($form.length) {
                            onFormLoaded($form);
                            $form.data('modal_idx', bimpAjax.modal_idx);
                            if (typeof (result.button) === 'object') {
                                var label = result.button.label;
                                var onclick = result.button.onclick;
                            } else {
                                var label = 'Envoyer<i class="fa fa-arrow-circle-right iconRight"></i>';
                                var onclick = 'BimpApi.processRequestModalForm($(this))';
                            }
                            bimpModal.addButton(label, onclick, 'primary', 'save_object_button', bimpAjax.modal_idx);
                        }
                    }
                }
            }
        };

        if (typeof (success_callback) === 'function') {
            ajax_params.load_request_form_success = success_callback;
        }

        BimpApiAjax(api_name, api_idx, 'apiLoadRequestForm', request_name, api_params, $container, request_options, ajax_params);
    };

    this.processRequestModalForm = function ($button) {
        if ($.isOk($button)) {
            if ($button.hasClass('disabled')) {
                return;
            }

            var modal_idx = parseInt($button.data('modal_idx'));

            if (modal_idx && !isNaN(modal_idx)) {
                var $container = bimpModal.getContent(modal_idx);

                if ($.isOk($container)) {
                    var $form = $container.find('form.bimp_api_request_form');
                    if ($form.length) {
                        var data = new FormData($form.get(0));
                        var api_name = $form.data('api_name');
                        var api_idx = $form.data('api_idx');
                        var request_name = $form.data('api_requestName');

                        BimpApiAjax(api_name, api_idx, 'apiProcessRequestForm', request_name, data, $form.find('.ajaxResultContainer'), {}, {
                            modal_idx: modal_idx,
                            $button: $button,
                            display_processing: true,
                            processing_padding: 20,
                            append_html: true,
                            processData: false,
                            contentType: false,
                            modal_scroll_bottom: true,
                            success: function (result, bimpAjax) {
                                bimpModal.$footer.find('.extra_button.modal_' + bimpAjax.modal_idx).remove();
                                if (typeof (result.new_buttons) === 'Array') {
                                    for (var i in result.new_buttons) {
                                        bimpModal.addButton(result.new_buttons[i].label, result.new_buttons[i].onclick, 'primary', 'save_object_button', bimpAjax.modal_idx);
                                    }
                                }
                            }
                        });
                        return;
                    }
                }
            }
        }

        bimp_msg('Une erreur est survenue. Envoi du formulaire impossible', 'danger');
    };
}

var BimpApi = new BimpApi();