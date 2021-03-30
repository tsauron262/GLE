if (!ajaxRequestsUrl) {
    var ajaxRequestsUrl = dol_url_root + '/bimpcore/index.php';
}

var bimp_requests = [];
var bimp_nologged_requests = [];
var bimp_is_logged = true;
var bimp_is_unloaded = false;

function BimpAjax(action, data, $resultContainer, params) {
    if (typeof (params.$button) === 'object') {
        if ($.isOk(params.$button)) {
            if (params.$button.hasClass('disabled')) {
                return;
            }
        }
    }
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
    this.$button = null;

    // Paramètres par défaut: 
    this.request_id = request_id;
    this.action = action;
    this.url = ajaxRequestsUrl;
    this.type = 'POST';
    this.dataType = 'json';

    this.display_success = true;
    this.display_errors = true;
    this.display_warnings = true;
    this.display_processing = false;

    this.display_success_in_popup_only = true;
    this.display_errors_in_popup_only = false;
    this.display_warnings_in_popup_only = true;

    this.append_html = false;
    this.use_refresh_idx = true; // Si $resultContainer non null
    this.remove_current_content = true;
    this.append_html_transition = true;

    this.processing_msg = 'Traitement en cours';
    this.success_msg = 'Opération effectuée avec succès';
    this.error_msg = 'Une erreur est survenue, l\'opération n\'a pas aboutie';

    this.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
    this.processData = true;

    this.processing_padding = 60;

    this.modal_scroll_bottom = false;
    this.display_debug_content = true;

    this.success = function () {
    };

    this.error = function () {
    };

    // Surchage des paramètres par défaut: 
    if (typeof (params) === 'object') {
        for (i in params) {
            bimpAjax[i] = params[i];
        }
    }

    if ($.isOk(bimpAjax.$button)) {
        bimpAjax.$button.addClass('disabled');
    }

    if (!/\?/.test(bimpAjax.url)) {
        bimpAjax.url += '?';
    } else {
        bimpAjax.url += '&';
    }
    bimpAjax.url += 'ajax=1&action=' + action + '&request_id=' + request_id;

    if (typeof (context) !== 'undefined' && context) {
        bimpAjax.url += "&context=" + context;
    }

    // Affichage du message de chargement ou suppression du contenu actuel si nécessaire
    if (this.display_processing) {
        if ($.isOk(this.$resultContainer)) {
            var process_html = '<div class="content-loading" style="padding: ' + this.processing_padding + 'px;">';
            process_html += '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
            if (this.processing_msg) {
                process_html += '<p class="loading-text">' + this.processing_msg + '</p>';
            }
            process_html += '</div>';
            this.$resultContainer.html(process_html).find('.content-loading').show();
            this.$resultContainer.show();
        } else {
            bimp_msg(this.processing_msg, 'info', null, true);
        }
    } else if (bimpAjax.remove_current_content &&
            ((bimpAjax.display_success && !bimpAjax.display_success_in_popup_only) ||
                    (bimpAjax.display_errors && !bimpAjax.display_errors_in_popup_only))) {
        if (this.$resultContainer) {
            this.$resultContainer.html('').slideUp(250);
        }
    }

    if (this.modal_scroll_bottom) {
        bimpModal.scrollBottom();
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

        if (msg) {
            if (bimpAjax.display_errors_in_popup_only) {
                bimp_msg(msg, 'danger');
            } else {
                bimp_msg(msg, 'danger', bimpAjax.$resultContainer);
                if (bimpAjax.modal_scroll_bottom) {
                    setTimeout(function () {
                        bimpModal.scrollBottom();
                    }, 260);
                }
            }
        }
    };

    this.display_result_warnings = function (warnings) {
        if (!bimpAjax.display_warnings) {
            return;
        }

        var msg = '';

        if (typeof (warnings) !== 'undefined') {
            if (typeof (warnings) === 'string') {
                msg = warnings;
            } else if (typeof (warnings) === 'object') {
                for (var i in warnings) {
                    msg += warnings[i] + '<br/>';
                }
            }
        }

        if (msg) {
            if (bimpAjax.display_warnings_in_popup_only) {
                bimp_msg(msg, 'warning');
            } else {
                bimp_msg(msg, 'warning', bimpAjax.$resultContainer);
                if (bimpAjax.modal_scroll_bottom) {
                    setTimeout(function () {
                        bimpModal.scrollBottom();
                    }, 260);
                }
            }
        }
    };

    this.display_result_infos = function (infos) {
        var msg = '';

        if (typeof (infos) !== 'undefined') {
            if (typeof (infos) === 'string') {
                msg = infos;
            } else if (typeof (infos) === 'object') {
                for (var i in infos) {
                    msg += infos[i] + '<br/>';
                }
            }
        }

        if (msg) {
            bimp_msg(msg, 'info');
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

        if (msg) {
            if (bimpAjax.display_success_in_popup_only) {
                bimp_msg(msg, 'success');
            } else {
                bimp_msg(msg, 'success', bimpAjax.$resultContainer);
                if (bimpAjax.modal_scroll_bottom) {
                    setTimeout(function () {
                        bimpModal.scrollBottom();
                    }, 260);
                }
            }
        }
    };

    this.send = function (bimpAjax) {
        if (!bimp_is_logged) {
            bimpAjax.nologged(bimpAjax);
            return;
        }

        // Assignation du resfresh idx 
        // (permet d'éviter un écrasement du HTML de $resultContainer si plusieurs req ajax identiques croisées).  
        if (bimpAjax.use_refresh_idx && $.isOk(bimpAjax.$resultContainer)) {
            var refresh_idx = bimpAjax.$resultContainer.data('ajax_refresh_idx');
            if (typeof (refresh_idx) === 'undefined') {
                refresh_idx = 0;
            } else {
                refresh_idx = parseInt(refresh_idx);
                if (isNaN(refresh_idx)) {
                    refresh_idx = 0;
                }
            }

            refresh_idx++;
            bimpAjax.ajax_resfresh_idx = refresh_idx;
            bimpAjax.$resultContainer.data('ajax_refresh_idx', refresh_idx);
        }

        // Envoi requête 
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
                    bimp_msg('Erreur: ID de requête invalide', 'danger', null, true);
                    return;
                }

                if (typeof (result.nologged) !== 'undefined') {
                    bimpAjax.nologged(bimpAjax);
                    return;
                }

                // Vérif du refresh idx:  
                if (bimpAjax.use_refresh_idx && bimpAjax.ajax_refresh_idx && $.isOk(bimpAjax.$resultContainer)) {
                    var refresh_idx = bimpAjax.$resultContainer.data('ajax_refresh_idx');
                    if (typeof (refresh_idx) === 'undefined') {
                        refresh_idx = 0;
                    } else {
                        refresh_idx = parseInt(refresh_idx);
                        if (isNaN(refresh_idx)) {
                            refresh_idx = 0;
                        }
                    }

                    if (refresh_idx > bimpAjax.ajax_refresh_idx) {
                        // Une nouvelle req ajax affectant $resultContainer a été lancée entre temps. 
                        return;
                    }
                }

                if (bimpAjax.display_debug_content && typeof (result.debug_content) === 'string' && result.debug_content) {
                    if (typeof (BimpDebugModal) === 'object' && BimpDebugModal) {
                        BimpDebugModal.newContent('Req ajax #' + bimpAjax.request_id + ' - ' + bimpAjax.action, result.debug_content, false, '', null, 'large', false);
                    }
                }

                if (bimpAjax.display_processing && bimpAjax.$resultContainer) {
                    bimpAjax.$resultContainer.html('').slideUp(250);
                }
                if ((typeof (result.errors) !== 'undefined') && result.errors && result.errors.length) {
                    bimpAjax.display_result_errors(result.errors);

                    if (typeof (bimpAjax.error) === 'function') {
                        bimpAjax.error(result, bimpAjax);
                    }
                } else {
                    bimpAjax.display_result_success(result);
                    var no_callbacks = false;

                    if (bimpAjax.append_html) {
                        if ($.isOk(bimpAjax.$resultContainer) && typeof (result.html) === 'string') {

                            var trigger_function = function (bimpAjax) {
                                $('body').trigger($.Event('contentLoaded', {
                                    $container: bimpAjax.$resultContainer
                                }));
                            };

                            var append_callback = function (result, bimpAjax) {
                                if (typeof (bimpAjax.success) === 'function') {
                                    bimpAjax.success(result, bimpAjax);
                                }

                                if (result.success_callback && typeof (result.success_callback) === 'string') {
                                    eval(result.success_callback);
                                }

                                if (bimpAjax.modal_scroll_bottom) {
                                    bimpModal.scrollBottom();
                                }

                                trigger_function(bimpAjax);
                                bimpAjax.$resultContainer.css('height', 'auto');
                            };

                            if (bimpAjax.remove_current_content) {
                                if (bimpAjax.append_html_transition) {
                                    no_callbacks = true;
                                    bimpAjax.$resultContainer.stop().slideUp(250, function () {
                                        bimpAjax.$resultContainer.html(result.html).slideDown(250, function () {
                                            append_callback(result, bimpAjax);
                                        });
                                    });
                                } else {
                                    bimpAjax.$resultContainer.css('display', 'block').html(result.html);
                                    trigger_function(bimpAjax);
                                }
                            } else {
                                if (bimpAjax.append_html_transition) {
                                    no_callbacks = true;
                                    bimpAjax.$resultContainer.stop().fadeOut(250, function () {
                                        bimpAjax.$resultContainer.html(result.html).fadeIn(250, function () {
                                            append_callback(result, bimpAjax);
                                        });
                                    });
                                } else {
                                    bimpAjax.$resultContainer.css('display', 'block').html(result.html);
                                    trigger_function(bimpAjax);
                                }
                            }
                        }
                    }

                    if (typeof (result.modal_html) === 'string' && result.modal_html) {
                        var modal_title = '';
                        if (typeof (result.modal_title) === 'string') {
                            modal_title = result.modal_title;
                        }

                        setTimeout(function () {
                            bimpModal.newContent(modal_title, result.modal_html, false, '', null);
                        }, 750);
                    }

                    if (!no_callbacks) {
                        if (typeof (bimpAjax.success) === 'function') {
                            bimpAjax.success(result, bimpAjax);
                        }

                        if (result.success_callback && typeof (result.success_callback) === 'string') {
                            eval(result.success_callback);
                        }
                    }
                }

                if ((typeof (result.warnings) !== 'undefined') && result.warnings && result.warnings.length) {
                    bimpAjax.display_result_warnings(result.warnings);
                }
                if ((typeof (result.infos) !== 'undefined') && result.infos && result.infos.length) {
                    bimpAjax.display_result_infos(result.infos);
                }

                if (bimpAjax.modal_scroll_bottom) {
                    bimpModal.scrollBottom();
                }

                if ($.isOk(bimpAjax.$button)) {
                    bimpAjax.$button.removeClass('disabled');
                }
                delete bimp_requests[bimpAjax.request_id];
            },
            error: function (response, a, b) {
                if (!bimp_is_unloaded) {
                    if (bimpAjax.display_errors) {
                        if (typeof (response.responseText) === 'string' && response.responseText) {
                            bimp_notify_error(response.responseText);
                        } else {
                            bimpAjax.display_result_errors(bimpAjax.error_msg);
                        }
                    }
                    if (typeof (bimpAjax.error) === 'function') {
                        bimpAjax.error(null, bimpAjax);
                    }
                    if ($.isOk(bimpAjax.$button)) {
                        bimpAjax.$button.removeClass('disabled');
                    }
                    delete bimp_requests[bimpAjax.request_id];
                }
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
                    if (context != "public") {
                        html += '<iframe id="bimp_login_iframe" frameborder="0" src="' + dol_url_root + '/bimpcore/ajax_login.php"></iframe>';

                    } else{
                        bimp_reloadPage();//html += '<span class="red">todo formulaire ou iframe (moins classe)       pour relog userClient dans /bimpcore/views/js/ajax.js line 284</span>';
                        return;
                    }
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

function setSessionConf(name, value) {
    BimpAjax("setSessionConf", {name, value}, $("existepas"), []);
}

window.addEventListener('beforeunload', function (e) {
    bimp_is_unloaded = true;
});
