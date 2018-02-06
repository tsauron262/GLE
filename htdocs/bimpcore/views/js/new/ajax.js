var requests = [];

function BimpAjax(action, data, $resultContainer, params) {
    var request_id = requests.length;
    requests[request_id] = new BimpAjaxObject(request_id, action, data, $resultContainer, params);
}

function BimpAjaxObject(request_id, action, data, $resultContainer, params) {
    var bimpAjax = this;

    if ($resultContainer && (typeof ($resultContainer) !== 'object') ||
            ($resultContainer && !$resultContainer.length)) {
        $resultContainer = null;
    }

    this.$resultContainer = $resultContainer;

    this.request_id = request_id;
    this.url = ajaxRequestsUrl;
    this.type = 'POST';
    this.dataType = 'json';

    this.display_success = true;
    this.display_errors = true;
    this.display_processing = false;

    this.display_success_in_popup_only = false;
    this.display_errors_in_popup_only = false;

    this.append_html = false;

    this.processing_msg = 'Traitement en cours';
    this.success_msg = 'Opération effectuée avec succès';
    this.error_msg = 'Une erreur est survenue, l\'opération n\'a pas aboutie';

    this.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
    this.processData = true;

    this.success = function () {
        bimp_msg('pas de function');
    };
    this.error = function () {
    };

    if (typeof (params) === 'object') {
        for (i in params) {
            bimpAjax[i] = params[i];
        }
    }

    if (!/\?/.test(bimpAjax.url)) {
        bimpAjax.url += '?';
    } else {
        bimpAjax.url += '&';
    }
    bimpAjax.url += 'ajax=1&action=' + action + '&request_id=' + request_id;

    if (this.display_processing && this.$resultContainer) {
        bimp_msg(this.processing_msg, 'info', this.$resultContainer);
    }

    this.display_result_errors = function (errors) {
        if (!bimpAjax.display_errors) {
            return;
        }

        var msg = '';

        if (typeof (errors) !== 'undefined') {
            if (typeof (errors) === 'string') {
                msg = errors;
            } else if (typeof (errors) === 'object') {
                msg = errors.length;
                if (errors.length > 1) {
                    msg += ' erreurs détectées';
                } else {
                    msg += ' erreur détectée';
                }
                msg += '<br/><br/>';
                var n = 1;
                for (var i in errors) {
                    msg += n + '- ' + errors[i] + '<br/>';
                    n++;
                }
            }
        }

        if (!msg) {
            msg = bimpAjax.error_msg;
        }

        if (bimpAjax.display_errors_in_popup_only) {
            bimp_msg(msg, 'danger');
        } else {
            bimp_msg(msg, 'danger', bimpAjax.$resultContainer);
        }
    };

    this.display_result_success = function (result) {
        if (!bimpAjax.display_success) {
            return;
        }

        var msg = '';
        if (typeof (result.success) === 'string') {
            msg = result.success;
        } else {
            msg = bimpAjax.success_msg;
        }

        if (bimpAjax.display_success_in_popup_only) {
            bimp_msg(result.success, 'success');
        } else {
            bimp_msg(result.success, 'success', bimpAjax.$resultContainer);
        }
    };

    $.ajax({
        url: bimpAjax.url,
        type: bimpAjax.type,
        dataType: bimpAjax.dataType,
        data: data,
        contentType: bimpAjax.contentType,
        processData: bimpAjax.processData,
        success: function (result) {
            if (typeof (result.request_id) !== 'undefined') {
                bimpAjax = requests[parseInt(result.request_id)];
            } else {
                bimp_msg('Erreur: ID de requête invalide');
                return;
            }
            if (bimpAjax.display_processing && bimpAjax.$resultContainer) {
                bimpAjax.$resultContainer.html('').stop().slideUp(250);
            }
            if ((typeof (result.errors) !== 'undefined') && result.errors && result.errors.length) {
                bimpAjax.display_result_errors(result.errors);

                if (typeof (bimpAjax.error) === 'function') {
                    bimpAjax.error(result);
                }
            } else {
                bimpAjax.display_result_success(result);

                if (bimpAjax.append_html) {
                    if (bimpAjax.$resultContainer && typeof (result.html) === 'string') {
                        bimpAjax.$resultContainer.stop().slideUp(250, function () {
                            bimpAjax.$resultContainer.html(result.html).slideDown(250);
                        });
                    }
                }
                if (typeof (bimpAjax.success) === 'function') {
                    bimpAjax.success(result);
                }
            }
            delete requests[bimpAjax.request_id];
        },
        error: function () {
            if (bimpAjax.display_errors) {
                bimpAjax.display_result_errors(bimpAjax.error_msg);
            }
            if (typeof (bimpAjax.error) === 'function') {
                bimpAjax.error(null);
            }
            delete requests[bimpAjax.request_id];
        }
    });
}