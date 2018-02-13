var ajaxRequestsUrl = './index.php';
var bimp_requests = [];
var bimp_nologged_requests = [];
var bimp_is_logged = true;

function BimpAjax(action, data, $resultContainer, params) {
    var request_id = bimp_requests.length;
    bimp_requests[request_id] = new BimpAjaxObject(request_id, action, data, $resultContainer, params);
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

    this.send = function (bimpAjax) {
        if (!bimp_is_logged) {
            bimpAjax.nologged(bimpAjax);
            return;
        }
        $.ajax({
            url: bimpAjax.url,
            type: bimpAjax.type,
            dataType: bimpAjax.dataType,
            data: data,
            contentType: bimpAjax.contentType,
            processData: bimpAjax.processData,
            success: function (result) {
                if (typeof (result.request_id) !== 'undefined') {
                    bimpAjax = bimp_requests[parseInt(result.request_id)];
                } else {
                    bimp_msg('Erreur: ID de requête invalide');
                    return;
                }

                if (typeof (result.nologged) !== 'undefined') {
                    bimpAjax.nologged(bimpAjax);
                    return;
                }

                if (bimpAjax.display_processing && bimpAjax.$resultContainer) {
                    bimpAjax.$resultContainer.html('').stop().slideUp(250);
                }
                if ((typeof (result.errors) !== 'undefined') && result.errors && result.errors.length) {
                    bimpAjax.display_result_errors(result.errors);

                    if (typeof (bimpAjax.error) === 'function') {
                        bimpAjax.error(result, bimpAjax);
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
                        bimpAjax.success(result, bimpAjax);
                    }
                }
                delete bimp_requests[bimpAjax.request_id];
            },
            error: function () {
                if (bimpAjax.display_errors) {
                    bimpAjax.display_result_errors(bimpAjax.error_msg);
                }
                if (typeof (bimpAjax.error) === 'function') {
                    bimpAjax.error(null, bimpAjax);
                }
                delete bimp_requests[bimpAjax.request_id];
            }
        });
    };

    this.nologged = function (bimpAjax) {
        if (bimp_is_logged) {
            bimp_is_logged = false;
            var $container = $('body');
            var $login = $container.find('#bimp_login_popup');
            if (!$login.length) {
                if (typeof (dol_url_root) !== 'undefined') {
                    var html = '<div id="bimp_login_popup">';
                    html += '<iframe id="bimp_login_iframe" frameborder="0" src="' + dol_url_root + '/bimpcore/ajax_login.php"></iframe>';
                    html += '</div>';
                    $container.append(html);
                    $('#bimp_login_iframe').on('load', function () {
                        if ($(this).contents().find("body").find('#login_ok').length) {
                            bimp_on_login_success();
                        }
                    });
                } else {
                    alert('Une erreur est survenue. Impossible de charger le formulaire d\'authentification' + "\n" + 'Veuillez rafraîchir la page pour vous authentifier.');
                }

            }
        }
        bimp_nologged_requests.push(bimpAjax.request_id);
    };

    bimpAjax.send(bimpAjax);
}

function bimp_on_login_success() {
    if (bimp_is_logged) {
        return;
    }

    bimp_is_logged = true;

    if ($('#bimp_login_popup').length) {
        $('#bimp_login_popup').remove();
    }

    for (var i in bimp_nologged_requests) {
        if (typeof (bimp_requests[bimp_nologged_requests[i]]) !== 'undefined') {
            bimp_requests[bimp_nologged_requests[i]].send(bimp_requests[bimp_nologged_requests[i]]);
        }
    }
    bimp_nologged_requests = [];
}