function BimpTimer(id, timer_id, object_module, object_name, id_object, field_name, time_total, time_current) {
    var timer = this;
    this.id = id;
    this.timer_id = timer_id;
    this.object_module = object_module;
    this.object_name = object_name;
    this.id_object = id_object;
    this.field_name = field_name;
    this.initial_time_total = time_total;
    this.initial_time_current = time_current;
    this.time_total = time_total;
    this.time_current = time_current;
    this.before_reset_time_total = 0;
    this.before_reset_time_current = 0;
    this.session_start = 0;
    this.session_start_time_total = time_total;
    this.session_start_time_current = time_current;
    this.is_pause = true;

    this.start = function () {
        timer.is_pause = false;
        $('.' + timer.timer_id).find('.bimp_timer_start_btn').hide();
        $('.' + timer.timer_id).find('.bimp_timer_pause_btn').show();
        this.session_start_time_total = timer.time_total;
        this.session_start_time_current = timer.time_current;
        if (!timer.session_start) {
            timer.startSession();
        }
        timer.increaseTimer();
    };

    this.pause = function () {
        timer.is_pause = true;
        $('.' + timer.timer_id).find('.bimp_timer_pause_btn').hide();
        $('.' + timer.timer_id).find('.bimp_timer_start_btn').show();
        timer.stopSession();
    };

    this.startSession = function () {
        var date = new Date();
        timer.session_start = Math.floor(date.getTime() / 1000);
        saveObjectField('bimpcore', 'BimpTimer', timer.id, 'session_start', timer.session_start, null, null);
    };

    this.stopSession = function () {
        timer.session_start = 0;
        var fields = {
            'time_session': timer.time_current,
            'session_start': 0
        };
        saveObject('bimpcore', 'BimpTimer', timer.id, fields, null, null);
    };

    this.save = function () {
        saveObjectField(timer.object_module, timer.object_name, timer.id_object, timer.field_name, timer.time_total, null, function () {
            timer.initial_time_total = timer.time_total;
            timer.session_start_time_total = timer.time_total;
            timer.initial_time_current = 0;
            timer.time_current = 0;
            timer.session_start_time_current = 0;
            if (!timer.is_pause) {
                var date = new Date();
                timer.session_start = Math.floor(date.getTime() / 1000);
                saveObjectField('bimpcore', 'BimpTimer', timer.id, 'session_start', timer.session_start, null, null);
            } else {
                var fields = {
                    'time_session': 0,
                    'session_start': 0
                };
                saveObject('bimpcore', 'BimpTimer', timer.id, fields, null, null);
            }
            timer.updateTimer();
        });
    };

    this.reloadTotalTime = function () {
        loadObjectFieldValue(timer.object_module, timer.object_name, timer.id_object, timer.field_name, null, function (result) {
            if (result.value) {
                timer.initial_time_total = parseInt(result.value);
                timer.time_total = timer.initial_time_total;
                timer.updateTimer();
                if (timer.is_pause) {
                    timer.pause();
                } else {
                    timer.start();
                }
            }
        });
    };

    this.updateTimer = function () {
        if (!$('.' + timer.timer_id).length) {
            return;
        }

        var currentSecondes = timer.time_current;
        var currentMinutes = 0;
        var currentHours = 0;
        var currentDays = 0;

        if (currentSecondes >= 60) {
            currentMinutes = Math.floor(currentSecondes / 60);
            currentSecondes -= (currentMinutes * 60);

            if (currentMinutes >= 60) {
                currentHours = Math.floor(currentMinutes / 60);
                currentMinutes -= (currentHours * 60);

                if (currentHours >= 24) {
                    currentDays = Math.floor(currentHours / 24);
                    currentHours -= (currentDays * 24);
                }
            }
        }

        $('.' + timer.timer_id + '_current_time').each(function () {
            $(this).find('.bimp_timer_secondes').text(currentSecondes);
            $(this).find('.bimp_timer_minutes').text(currentMinutes);
            $(this).find('.bimp_timer_hours').text(currentHours);
            $(this).find('.bimp_timer_days').text(currentDays);
        });

        var totalSecondes = timer.time_total;
        var totalMinutes = 0;
        var totalHours = 0;
        var totalDays = 0;

        if (totalSecondes >= 60) {
            totalMinutes = Math.floor(totalSecondes / 60);
            totalSecondes -= (totalMinutes * 60);

            if (totalMinutes >= 60) {
                totalHours = Math.floor(totalMinutes / 60);
                totalMinutes -= (totalHours * 60);

                if (totalHours >= 24) {
                    totalDays = Math.floor(totalHours / 24);
                    totalHours -= (totalDays * 24);
                }
            }
        }

        $('.' + timer.timer_id + '_total_time').each(function () {
            $(this).find('.bimp_timer_secondes').text(totalSecondes);
            $(this).find('.bimp_timer_minutes').text(totalMinutes);
            $(this).find('.bimp_timer_hours').text(totalHours);
            $(this).find('.bimp_timer_days').text(totalDays);
        });
    };

    this.increaseTimer = function () {
        if (!$('.' + timer.timer_id).length) {
            return;
        }
        if (timer.is_pause || !timer.session_start) {
            return;
        }

        var date = new Date();
        var time = Math.floor(date.getTime() / 1000);
        var session_time = (time - timer.session_start);
        timer.time_total = timer.session_start_time_total + session_time;
        timer.time_current = timer.session_start_time_current + session_time;
        timer.updateTimer();
        setTimeout(function () {
            timer.increaseTimer();
        }, 1000);
    };

    this.resetCurrent = function () {
        timer.before_reset_time_current = timer.time_current;
        timer.before_reset_time_total = timer.time_total;
        timer.time_current = 0;
        timer.initial_time_current = 0;
        $('.' + timer.timer_id).find('.bimp_timer_cancel_reset_btn').show();
        timer.reloadTotalTime();
    };

    this.resetTotal = function () {
        timer.before_reset_time_current = timer.time_current;
        timer.before_reset_time_total = timer.time_total;
        timer.time_current = 0;
        timer.time_total = 0;
        timer.updateTimer();
        $('.' + timer.timer_id).find('.bimp_timer_cancel_reset_btn').show();
        if (timer.is_pause) {
            timer.pause();
        } else {
            timer.start();
        }
    };

    this.cancelLastReset = function () {
        var old_time_current = timer.time_current;
        var old_time_total = timer.time_total;
        timer.time_current = timer.before_reset_time_current;
        timer.time_total = timer.before_reset_time_total;
        timer.before_reset_time_current = old_time_current;
        timer.before_reset_time_total = old_time_total;
        timer.updateTimer();
        if (timer.is_pause) {
            timer.pause();
        } else {
            timer.start();
        }
    };
}