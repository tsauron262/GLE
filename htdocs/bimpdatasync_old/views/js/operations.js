function OperationStep(data) {
    var step = this;

    this.name = data.name;
    this.label = data.label;
    this.status = 'waiting';
    this.is_done = 0;
    this.nbReset = 0;

    this.on_error = 'stop';
    this.on_cancel = 'stop';

    this.$row = $('#step_' + data.name);

    if (typeof (data.on_error) !== 'undefined') {
        this.on_error = data.on_error;
    }
    if (typeof (data.on_cancel) !== 'undefined') {
        this.on_cancel = data.on_cancel;
    }

    // Gestion de la liste des éléments:

    this.elements = {
        'list': [],
        'nbTotal': 0,
        'nbDone': 0,
        'nbPerIteration': 1
    };

    this.setElements = function (elements) {
        step.elements.list = elements;
        step.elements.nbTotal = elements.length;
        step.elements.nbDone = 0;
    };

    this.addEelements = function (elements) {
        for (var i in elements) {
            step.elements.list.push(elements[i]);
        }
        step.elements.nbTotal += elements.length;
    };

    this.addElement = function (element) {
        step.elements.list.push(element);
        step.elements.nbTotal++;
    };

    this.unsetElements = function (elements) {
        var currents = step.elements.list;
        step.elements.list = [];

        for (var i in currents) {
            var check = true;
            for (var j in elements) {
                if (currents[i] === elements[j]) {
                    check = false;
                    break;
                }
            }
            if (check) {
                step.elements.list.push(currents[i]);
            }
        }

        step.elements.nbTotal = step.elements.list.length;
    };

    this.unsetElement = function (element) {
        for (var i in step.elements.list) {
            if (step.elements.list[i] === element) {
                step.elements.list.splice(i, 1);
                step.elements.nbTotal--;
                break;
            }
        }
    };

    this.setNbElementsPerIteration = function (nbElementsPerIteration) {
        step.elements.nbPerIteration = nbElementsPerIteration;
    };

    this.getIterationElements = function () {
        var elements = [];
        if (!step.elements.list.length) {
            return elements;
        }

        var n = 0;
        for (var i = step.elements.nbDone; i < step.elements.nbTotal; i++) {
            if (step.elements.nbPerIteration) {
                if (n >= step.elements.nbPerIteration) {
                    break;
                }
            }
            elements.push(step.elements.list[i]);
            n++;
        }

        var nbIterationsDone = 0;
        if (step.elements.nbPerIteration > 0) {
            nbIterationsDone = Math.ceil(step.elements.nbDone / step.elements.nbPerIteration);
        }
        var nbIterations = Math.ceil(step.elements.nbTotal / step.elements.nbPerIteration);

        var msg = 'traitement du paquet ' + (nbIterationsDone + 1) + ' sur ';
        msg += nbIterations + ' en cours...';
        step.$row.find('.stepExtraInfo').text(msg);

        return elements;
    };

    // Gestion du statut: 

    this.start = function () {
        step.is_done = 0;
        step.status = 'processing';
        step.setRowVars('Traitement en cours...');
    };

    this.onAjaxError = function () {
        step.status = 'error';
        step.setRowVars('Une erreur est survenue');
    };

    this.onAjaxSuccess = function () {
        if (step.elements.list.length) {
            step.elements.nbDone += step.elements.nbPerIteration;
            if (step.elements.nbDone > step.elements.nbTotal) {
                step.elements.nbDone = step.elements.nbTotal;
            }
        }

        if (step.elements.nbDone < step.elements.nbTotal) {
            step.setRowVars('');
            return;
        }

        step.status = 'success';
        step.is_done = 1;
        step.setRowVars('');
    };

    this.hold = function (msg) {
        if (!step.is_done && step.status === 'processing') {
            step.status = 'hold';
            if (typeof (msg) !== 'undefined') {
                step.setRowVars(msg);
            } else {
                step.setRowVars('Opération suspendue');
            }
        }
    };

    this.resume = function () {
        if (!step.is_done) {
            step.status = 'processing';
            step.setRowVars('Taitement en cours');
        }
    };

    this.skip = function () {
        if (!step.is_done) {
            step.status = 'skipped';
            step.setRowVars('Opération ignorée');
        }
    };

    this.cancel = function () {
        if (!step.is_done) {
            step.status = 'cancelled';
            step.setRowVars('Opération annulée');
        }
    };

    this.reset = function () {
        step.is_done = 0;
        step.elements.nbDone = 0;
        step.nbReset++;
    };

    // Traitements HTML:

    this.append = function ($body) {
        if (!$body.length) {
            var msg = 'Une erreur est survenue. Ajout de l\'étape ';
            msg += '"' + step.label + '" impossible (conteneur absent)';
            bimp_display_msg(msg, $('#operationStepAjaxResult'), 'danger');
            return false;
        }

        var html = '<tr id="step_' + step.name + '"';
        html += ' class="operationStepRow ' + step.status + '"';
        html += '>';

        html += '<td class="stepLabel">' + step.label + '</td>';

        html += '<td class="stepCount">';
        if (step.elements.nbTotal > 0) {
            html += '<span class="nbStepElementsDone">';
            html += step.elements.nbDone;
            html += '</span>';
            html += '&nbsp;/&nbsp;'
            html += '<span class="nbStepElementsTotal">';
            html += step.elements.nbTotal;
            html += '</span>';
        }
        html += '</td>';

        html += '<td class="stepProgession">';
        html += '<div class="progessionBar">';
        html += '<div class="progressionDone" style="width: 0%"></div>';
        html += '</div>';
        html += '<div class="stepExtraInfo"></div>';
        html += '</td>';

        html += '<td class="stepStatus"><span></span></td>';

        html += '</tr>';

        $body.append(html);

        step.$row = $body.find('#step_' + step.name);

        if (!step.$row.length) {
            var msg = 'Echec de l\'insertion de l\'étape "' + step.label + '"';
            bimp_display_msg(msg, $('#operationStepAjaxResult'), 'danger');
            return false;
        }

        return true;
    };

    this.setRowVars = function (msg) {
        if (!step.$row || !step.$row.length) {
            return;
        }

        step.$row.attr('class', 'operationStepRow ' + step.status);
        if (step.elements.list.length) {
            step.$row.find('.nbStepElementsDone').text(step.elements.nbDone);
            step.$row.find('.nbStepElementsTotal').text(step.elements.nbTotal);
            if (step.elements.nbTotal > 0) {
                var progression = Math.floor((step.elements.nbDone / step.elements.nbTotal) * 100);
                step.$row.find('.progressionDone').css('width', progression + '%');
            }
        } else {
            if (step.is_done) {
                step.$row.find('.progressionDone').css('width', '100%');
            } else {
                step.$row.find('.progressionDone').css('width', '0%');
            }
        }

        if (typeof (msg) !== 'undefined') {
            step.$row.find('.stepExtraInfo').text(msg);
        }
    };

    if (typeof (data.elements) !== 'undefined') {
        this.setElements(data.elements);
        if (typeof (data.nbElementsPerIteration) !== 'undefined') {
            this.setNbElementsPerIteration(data.nbElementsPerIteration);
        } else {
            this.setNbElementsPerIteration(0);
        }
    }

    if (!this.$row.length) {
        this.append($('#operationStepsTable').find('tbody'));
    }
}

function ProcessOperation(data, options) {
    var operation = this;
    this.$notification = $('#operationStepAjaxResult');

    this.id_process = data.id_process;
    this.id_operation = data.id_operation;
    this.options = options;
    this.retry_delay = 0;

    this.steps = [];
    this.status = 'init';

    this.curStep = {
        'step': 0,
        'index': 0,
        'ajax_processing': 0
    };

    this.report = {
        'use': 0,
        'reference': 0,
        'is_active': 0,
        'is_loading': 0,
        'display_loaded': 0,
        '$container': null,
        '$content': null
    };

    this.buttons = {
        '$cancel': $('#cancelOperationButton'),
        '$hold': $('#holdOperationButton'),
        '$resume': $('#resumeOperationButton'),
        '$retry': $('#retryOperationStepButton'),
        '$back': $('#backButton'),
        '$reportOn': $('#enableReportButton'),
        '$reportOff': $('#disableReportButton')
    };

    if (data.use_report && data.report_ref) {
        this.report.use = 1;
        this.report.reference = data.report_ref;
        this.report.$container = $('#reportContentContainer');
        this.report.$content = $('#reportContentContainer');
    }

    this.addSteps = function (steps) {
        if (steps) {
            for (var i in steps) {
                var step = new OperationStep(steps[i]);
                operation.steps.push(step);
            }
        }
    };

    this.start = function () {
        if (!operation.steps.length) {
            var html = '<div class="alert alert-warning">';
            html += 'Aucune opération à traiter';
            html += '</div>';
            operation.$notification.html(html);
            operation.status = 'success';
            operation.hideButtons();
            operation.buttons.$back.show();
        } else {
            operation.status = 'processing';
            operation.retry_delay = 0;
            operation.curStep.step = operation.steps[0];
            operation.curStep.index = 0;
            operation.curStep.step.start();
            operation.hideButtons();
            operation.buttons.$cancel.show();
            operation.buttons.$hold.show();
            operation.processStep();
        }
    };

    this.processStep = function () {
        if (operation.curStep.ajax_processing) {
            return;
        }

        if (operation.status === 'hold') {
            var html = '<div class="alert alert-warning">Opération suspendue</div>';
            operation.$notification.html(html);
            operation.curStep.step.hold();
        }

        if (operation.status === 'cancelled') {
            var html = '<div class="alert alert-danger">Opération annulée</div>';
            operation.$notification.html(html);
            operation.curStep.step.cancel();
        }

        if ((operation.status !== 'processing') || !operation.curStep.step) {
            return;
        }

        if (operation.retry_delay > 0) {
            var html = '<div class="alert alert-info">';
            html += 'Nouvelle tentative dans ' + operation.retry_delay + ' secondes';
            html += '</div>';
            operation.$notification.html(html);
            if (operation.retry_delay === 1) {
                operation.resume();
            } else {
                setTimeout(function () {
                    operation.processStep();
                }, 1000);
                operation.retry_delay--;
            }
            return;
        }

        var nextStep = false;

        if (operation.curStep.step.status === 'hold') {
            operation.hold();
            return;
        }

        if ((operation.curStep.step.is_done) ||
                (operation.curStep.step.status === 'skipped')) {
            nextStep = true;
        }

        if (operation.curStep.step.status === 'cancelled') {
            switch (operation.curStep.step.on_cancel) {
                case 'stop':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Opération annulée</div>';
                    operation.$notification.append(html);
                    operation.hideButtons();
                    operation.buttons.$back.show();
                    return;

                case 'continue':
                    nextStep = true;
                    break;

                case 'retry':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Opération annulée</div>';
                    operation.$notification.append(html);
                    operation.hideButtons();
                    operation.buttons.$retry.show();
                    operation.buttons.$back.show();
                    return;
            }
        }

        if (operation.curStep.step.status === 'error') {
            switch (operation.curStep.step.on_error) {
                case 'stop':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Une erreur est survenue. Opération abandonnée</div>';
                    operation.$notification.append(html);
                    operation.hideButtons();
                    operation.buttons.$back.show();
                    return;

                case 'continue':
                    nextStep = true;
                    break;

                case 'retry':
                    operation.resume();
                    return;

                case 'hold':
                    operation.status = 'hold';
                    var html = '<div class="alert alert-danger">Une erreur est survenue. Opération suspendue</div>';
                    operation.$notification.append(html);
                    operation.hideButtons();
                    operation.buttons.$retry.show();
                    operation.buttons.$back.show();
                    return;
            }
        }

        if (nextStep) {
            operation.curStep.index++;
            if (typeof (operation.steps[operation.curStep.index]) !== 'undefined') {
                operation.curStep.step = operation.steps[operation.curStep.index];
                operation.curStep.step.start();
            } else {
                operation.curStep.step = 0;
            }
        }

        if ((typeof (operation.curStep.step) === 'undefined') || 
                !operation.curStep.step) {
            operation.setSuccess('Opération terminée');
            return;
        }

        var data = {
            'ajax': 1,
            'action': 'executeOperationStep',
            'id_process': operation.id_process,
            'id_operation': operation.id_operation,
            'step_name': operation.curStep.step.name,
            'report_ref': 0,
            'return_report': 0,
            'options': operation.options,
            'step_options': {},
            'elements': 0,
            'iteration': 1
        };

        if (operation.report.use) {
            data.report_ref = operation.report.reference;
            data.return_report = operation.report.is_active;
        }

        if (typeof (operation.curStep.step.options) !== 'undefined') {
            data.step_options = operation.curStep.step.options;
        }

        if (operation.curStep.step.elements.nbTotal) {
            data.elements = operation.curStep.step.getIterationElements();
            var iterationsDone = Math.ceil(operation.curStep.step.elements.nbDone / operation.curStep.step.elements.nbPerIteration);
            data['iteration'] = iterationsDone + 1;
        }

        operation.curStep.ajax_processing = 1;

        $.ajax({
            type: 'POST',
            url: './ajax.php',
            dataType: 'json',
            data: data,
            success: function (result) {
                operation.curStep.ajax_processing = 0;
                if (result.report_html) {
                    operation.setReport(result.report_html);
                }
                if (typeof (result.step_result.debug_content) !== 'undefined') {
                    $('#debugContent').append(result.step_result.debug_content);
                    setFoldableEvents();
                }
                if (result.errors.length) {
                    var msg = '<ul>';
                    for (var e in result.errors) {
                        msg += '<li>' + result.errors[e] + '</li>';
                    }
                    msg += '</ul>';
                    operation.setError(msg);
                    operation.curStep.step.onAjaxError();
                } else {
                    if (typeof (result.step_result.retry) !== 'undefined') {
                        operation.curStep.step.hold(result.step_result.retry.msg);
                        operation.retry_delay = parseInt(result.step_result.retry.delay);
                    } else {
                        operation.curStep.step.onAjaxSuccess();
                    }
                }
                if (typeof (result.step_result.new_steps) !== 'undefined') {
                    operation.addSteps(result.step_result.new_steps);
                }
                operation.processStep();
            },
            error: function () {
                operation.curStep.ajax_processing = 0;
                operation.curStep.step.onAjaxError();
                operation.processStep();
            }
        });
    };

    this.hold = function () {
        operation.status = 'hold';
        operation.hideButtons();
        operation.buttons.$resume.show();
        operation.buttons.$cancel.show();

        var msg = '';
        if (operation.curStep.ajax_processing) {
            msg = 'Opération en cours de suspension';
        } else {
            operation.curStep.step.hold();
            msg = 'Opération suspendue';
        }
        var html = '<div class="alert alert-warning">' + msg + '</div>';
        operation.$notification.html(html);
    };

    this.resume = function () {
        operation.retry_delay = 0;
        operation.status = 'processing';
        operation.hideButtons();
        operation.buttons.$cancel.show();
        operation.buttons.$hold.show();

        operation.$notification.html('');

        operation.curStep.step.resume();
        if (!operation.curStep.ajax_processing) {
            operation.processStep();
        }
    };

    this.cancel = function () {
        operation.status = 'cancelled';
        operation.hideButtons();
        operation.buttons.$back.show();
        var msg = '';
        if (operation.curStep.ajax_processing) {
            msg = 'Opération en cours d\'annulation';
        } else {
            operation.curStep.step.cancel();
            msg = 'Opération annulée';
        }
        var html = '<div class="alert alert-warning">' + msg + '</div>';
        operation.$notification.html(html);

    };

    this.retryOperationStep = function () {
        if (operation.curStep.step) {
//            operation.curStep.step.reset();
            operation.curStep.step.resume();
            operation.hideButtons();
            operation.buttons.$cancel.show();
            operation.buttons.$hold.show();
            operation.status = 'processing';
            operation.$notification.html('');
            operation.processStep();
        }
    };

    this.enableReport = function () {
        operation.buttons.$reportOn.hide();

        if ((!operation.report.use) ||
                typeof (operation.report.reference) === 'undefined') {
            var msg = 'Aucun rapport n\'est disponible pour ce type d\'opération';
            bimp_display_msg(msg, operation.$notification, 'danger');
            return;
        }
        operation.buttons.$reportOff.show();

        if (operation.report.is_loading) {
            return;
        }

        operation.report.is_active = true;
        operation.loadReport();
    };

    this.disableReport = function () {
        operation.buttons.$reportOn.show();
        operation.buttons.$reportOff.hide();
        operation.report.is_active = false;
        operation.report.display_loaded = false;
        operation.report.$content.slideUp(250, function () {
            operation.report.$content.html('');
        });
    };

    this.loadReport = function () {
        if (typeof (operation.report.reference) !== 'undefined') {
            if (operation.report.use &&
                    operation.report.is_active &&
                    !operation.report.display_loaded) {

                operation.report.display_loaded = true;

                var html = '<p class="alert alert-info">';
                html += 'Chargement du rapport en cours</p>';
                $('#reportLoadingResult').html(html).show();

                $.ajax({
                    type: 'POST',
                    url: './ajax.php',
                    dataType: 'html',
                    data: {
                        'ajax': 1,
                        'action': 'loadReport',
                        'report_ref': operation.report.reference
                    },
                    success: function (report_html) {
                        $('#reportLoadingResult').html('').hide();
                        if (operation.report.display_loaded) {
                            operation.report.display_loaded = false;
                            if (report_html) {
                                operation.setReport(report_html);
                                operation.report.$container.slideDown();
                            } else {
                                var msg = '<p class="alert alert-danger">Echec du chargement du rapport</p>';
                                $('#reportLoadingResult').html(msg).show();
                            }
                        }
                    },
                    error: function () {
                        if (operation.report.display_loaded) {
                            operation.report.display_loaded = false;
                            var msg = '<p class="alert alert-danger">Echec du chargement du rapport</p>';
                            $('#reportLoadingResult').html(msg).show();
                        }
                    }
                });
                return;
            }
        }
        bimp_display_msg('Aucun rapport disponible', operation.$notification, 'danger');
    };

    this.displayReport = function () {
        operation.report.is_active = false;
    };

    this.setReport = function (report_html) {
        operation.report.display_loaded = false;
        if (!operation.report.is_active ||
                !operation.report.use ||
                !report_html) {
            return;
        }
        operation.report.$content.html(report_html);
        setReportEvents();
    };

    this.setSuccess = function (msg) {
        operation.status = 'success';
        var html = '<div class="alert alert-success">';
        html += msg;
        html += '</div>';
        operation.$notification.append(html);
        operation.hideButtons();
        operation.buttons.$back.show();
    };

    this.setError = function (msg) {
        var html = '<div class="alert alert-danger">';
        html += msg;
        html += '</div>';
        operation.$notification.html(html);
        operation.hideButtons();
        operation.buttons.$back.show();
        operation.buttons.$retry.show();
    };

    this.hideButtons = function () {
        operation.buttons.$cancel.hide();
        operation.buttons.$hold.hide();
        operation.buttons.$resume.hide();
        operation.buttons.$retry.hide();
        operation.buttons.$back.hide();
    };

    if (typeof (data.steps) !== 'undefined') {
        this.addSteps(data.steps);
    }

    if (typeof (data.debug_content) !== 'undefined') {
        setFoldableEvents();
    }

    this.start();
}

var Operation = null;

function initProcessOperation($button, id_process, id_operation) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $resultContainer = $('#operation_' + id_operation + '_resultContainer');

    if (!id_process) {
        bimp_display_msg('Opération impossible - ID du processus absent', $resultContainer, 'danger');
        return;
    }

    if (!id_process) {
        bimp_display_msg('Opération impossible - ID de l\'opération absent', $resultContainer, 'danger');
        return;
    }

    $button.addClass('disabled');

    var options = {};
    var inputs = $('#process_' + id_process + '_operation_' + id_operation + '_options_form').serializeArray();
    for (i in inputs) {
        options[inputs[i].name] = inputs[i].value;
    }

    var data = {
        'id_process': id_process,
        'id_operation': id_operation,
        'options': options
    };

    bimp_display_msg('Initialisation du processus en cours...', $resultContainer, 'info');

    bimp_json_ajax('initProcessOperation', data, $resultContainer, function (result) {
        if (result.html) {
            $('#contentContainer').slideUp(250, function () {
                $(this).html(result.html).slideDown(250, function () {
                    Operation = new ProcessOperation(result.data, result.options);
                });
            });
        } else {
            var html = '';
            if ((typeof (result.data.result_html) !== 'undefined') &&
                    result.data.result_html) {
                html += result.data.result_html;

            }
            if ((typeof (result.data.debug_content) !== 'undefined') &&
                    result.data.debug_content) {
                html += result.data.debug_content;
            }
            $resultContainer.stop().html(html).slideDown(function () {
                setFoldableEvents();
                $resultContainer.removeAttr('style');
            });
        }

        $button.removeClass('disabled');
    }, function (result) {
        $button.removeClass('disabled');
        if ((typeof (result.data.debug_content) !== 'undefined') &&
                result.data.debug_content) {
            $resultContainer.append(result.data.debug_content).stop().slideDown(250, function () {
                setFoldableEvents();
                $resultContainer.removeAttr('style');
            });
        }
    });
}

function endOperation() {
    $('#cancelOperationButton').hide();
    $('#holdOperationButton').hide();
    $('#retryOperationStepButton').hide();
    $('#resumeOperationButton').hide();
    $('#backButton').show();
}