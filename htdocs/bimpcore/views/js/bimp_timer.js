function BimpTimer(id_timer, object_module, object_name, id_object, field_name, time_total, time_current) {
    var timer = this;
    this.id_timer = id_timer;
    this.$timer = $('#' + id_timer);
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
    this.is_pause = true;

    this.start = function () {
        timer.is_pause = false;
        timer.increaseTimer();
        timer.$timer.find('.bimp_timer_start_btn').hide();
        timer.$timer.find('.bimp_timer_pause_btn').show();
    };

    this.pause = function () {
        timer.is_pause = true;
        timer.$timer.find('.bimp_timer_pause_btn').hide();
        timer.$timer.find('.bimp_timer_start_btn').show();
    };

    this.save = function () {
        saveObjectField(timer.object_module, timer.object_name, timer.id_object, timer.field_name, timer.time_total, null, function() {
            timer.initial_time_total = time_total;
        });
    };

    this.updateTimer = function () {
        if (!timer.$timer.length) {
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

        var $current = timer.$timer.find('.bimp_timer_current_time');
        $current.find('.bimp_timer_secondes').text(currentSecondes);
        $current.find('.bimp_timer_minutes').text(currentMinutes);
        $current.find('.bimp_timer_hours').text(currentHours);
        $current.find('.bimp_timer_days').text(currentDays);
        
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

        var $total = timer.$timer.find('.bimp_timer_total_time');
        $total.find('.bimp_timer_secondes').text(totalSecondes);
        $total.find('.bimp_timer_minutes').text(totalMinutes);
        $total.find('.bimp_timer_hours').text(totalHours);
        $total.find('.bimp_timer_days').text(totalDays);
    };

    this.increaseTimer = function () {
        if (timer.is_pause) {
            return;
        }

        timer.time_total++;
        timer.time_current++;
        timer.updateTimer();
        setTimeout(function () {
            timer.increaseTimer();
        }, 1000);
    };

    this.resetCurrent = function () {
        timer.before_reset_time_current = timer.time_current;
        timer.before_reset_time_total = timer.time_total;
        timer.time_current = timer.initial_time_current;
        timer.time_total = timer.initial_time_total;
        timer.updateTimer();
        timer.$timer.find('.bimp_timer_cancel_reset_btn').show();
    };

    this.resetTotal = function () {
        timer.before_reset_time_current = timer.time_current;
        timer.before_reset_time_total = timer.time_total;
        timer.time_current = 0;
        timer.time_total = 0;
        timer.updateTimer();
        timer.$timer.find('.bimp_timer_cancel_reset_btn').show();
    };
    
    this.cancelLastReset = function() {
        var old_time_current = timer.time_current;
        var old_time_total = timer.time_total;
        timer.time_current = timer.before_reset_time_current;
        timer.time_total = timer.before_reset_time_total;
        timer.before_reset_time_current = old_time_current;
        timer.before_reset_time_total = old_time_total;
        timer.updateTimer();
    };
}