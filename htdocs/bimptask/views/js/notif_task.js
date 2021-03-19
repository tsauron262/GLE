
class notif_task extends AbstractNotification {
    
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
    }
    
    init() {
        
        this.my = 'my_task';
        this.unaffected = 'unaffected_task';
        var nt = this;
        
        if(theme != 'BimpTheme')
            var notif_white = 'notif_white';
        else
            var notif_white = '';

        if($('a#' + this.dropdown_id).length == 0) {
       
            var html = '<a class="nav-link dropdown-toggle ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-tasks atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';

            html +='<div class="tabs-animated">';

            // Nav tabs 
            html +='<ul id="nav_task" class="nav nav-tabs" role="tablist">';
            html +='<li role="presentation" class="active"><a href="#' + nt.my + '" aria-controls="' + nt.my + '" role="tab" data-toggle="tab">Mes tâches</a></li>';
            html +='<li role="presentation"><a href="#' + nt.unaffected + '" aria-controls="' + nt.unaffected + '" role="tab" data-toggle="tab">Tâches non attribuées</a></li>';
            html +='<li role="presentation" class="reload"><a>' + this.getBoutonReload(this.dropdown_id)+'</a></li>';
//            html +='<li role="presentation"><a href="#" aria-controls="' + nt.unaffected + '" role="tab" data-toggle="tab">' + this.getBoutonReload(this.dropdown_id)+'</a></li>';
            html +='</ul>';

            // Tab panels 
            html +='<div class="tab-content task_panel">';
            html +='<div role="tabpanel" class="list_notification tab-pane fade in active" id="' + nt.my + '"><div class="task_with_prio"></div><div class="task_no_prio"></div></div>';
            html +='<div role="tabpanel" class="list_notification tab-pane fade" id="' + nt.unaffected + '"><div class="task_with_prio"></div><div class="task_no_prio"></div></div>';
            html +='</div>';

            html +='</div>';
            html += '</div>';

            $(this.parent_selector).prepend(html);
            
            
//            // Animation slide sur le coté
            $('ul#nav_task > li > a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
		var $old_tab = $($(e.target).attr("href"));
		var $new_tab = $($(e.relatedTarget).attr("href"));

		if($new_tab.index() < $old_tab.index()){
			$old_tab.css('position', 'relative').css("right", "0").show();
			$old_tab.animate({"right":"-100%"}, 300, function () {
				$old_tab.css("right", 0).removeAttr("style");
			});
		} else {
			$old_tab.css('position', 'relative').css("left", "0").show();
			$old_tab.animate({"left":"-100%"}, 300, function () {
				$old_tab.css("left", 0).removeAttr("style");
			});
		}
            });

            $('ul#nav_task > li > a[data-toggle="tab"]').on('show.bs.tab', function (e) {
		var $new_tab = $($(e.target).attr("href"));
		var $old_tab = $($(e.relatedTarget).attr("href"));

		if($new_tab.index() > $old_tab.index()){
			$new_tab.css('position', 'relative').css("right", "-2500px");
			$new_tab.animate({"right":"0"}, 500);
		}
		else {
			$new_tab.css('position', 'relative').css("left", "-2500px");
			$new_tab.animate({"left":"0"}, 500);
		}
            });

            super.init(this);
        }
    }
    
    isNew(element) {
        
        // Cette tâche concerne directement l'utilisateur connecté
        if(element.user_type == this.my)
            return 1;
        
        // Cette tâche est non attribué
        return 0;
    }
    
    getButton(button_name, name_display, icon, params) {
        return '<button name="' + button_name + '" class="btn btn-default" type="button" onclick="setObjectAction($(this), '
        + '{module: \'bimptask\', object_name: \'BIMP_Task\', ' + params 
        + ');"><i class="fa ' + icon + ' iconLeft"></i>' + name_display + '</button>';
    }
    
    getbuttonSendMail(id_object) {
        return this.getButton('rep_mail', 'Rep Mail', 'fa-send', 
                'id_object: \'' + id_object + '\'}, \'sendMail\', {}, \'newMail\','
              + 'null, null, null, null, false, \'medium\'');
    }
    
    getbuttonClose(id_object) {
        return this.getButton('close', 'Terminer', 'fa-close', 
                'id_object: \'' + id_object + '\'}, \'close\', {}, null, null, '
              + 'null, \'Terminer la tâche ?\', null, false, \'medium\'');
    }
    
    getButtonAttribute(id_object) {
        return this.getButton('attribute', 'Attribuer', 'fa-user', 
                'id_object: \'' + id_object + '\'}, \'attribute\', {}, '
              + '\'attribute\', null, null, null, null, false, \'medium\'');
   }
       
    getButtonRefuseAttribute(id_object) {
        return this.getButton('refuse_attribute', 'Refuser l\'attribution', 'fa-window-close', 
                'id_object: \'' + id_object + '\'}, \'attribute\', '
              + '{id_user_owner: 0}, null, null, null, \'Refuser cette attribution ?\', null, false, \'medium\'');
    }

//<button class="btn  btn-danger" type="button"
//onclick="loadModalView('bimptask', 'BIMP_Task',
//29686, 'notes', $(this), 'Infos')"><i class="fa fa-fas fa-comments iconLeft">
//</i>1 Info(s) 1 Non lue(s)</button>
    getButtonNotViewed(id_object, not_viewed) {
        
        if(not_viewed == 0)
            return '';
        
        var s = ''
        if(not_viewed > 1)
            s = 's';
        
        return '<button not_viewed=' + not_viewed + ' class="btn  btn-danger" type="button" onclick="loadModalView(' 
            + '\'bimptask\', \'BIMP_Task\', ' + id_object + ', \'notes\', $(this), '
            + '\'Infos\')"><i class="fa fa-fas fa-comments iconLeft"></i>' + not_viewed + ' Info' + s + ' ' + not_viewed + ' Non lue ' + s + '</button>';
    }

    getKey(element) {
        return 'task_' + element.id;
    }
    
    formatElement(element, key) {
                    
        var html ='';
        
        if(parseInt(element.prio) === 20) {
            html += '<i class="danger fa fa-exclamation iconLeft"></i>';
            element.append = 'div.tab-content > #' + element.user_type + ' > div.task_with_prio';

        } else {
            element.append = 'div.tab-content > #' + element.user_type + ' > div.task_no_prio';
        }

        html += element.subj + ' de "' + element.src + '" ' + element.txt;
        
        // Boutons
        html += this.getButtonNotViewed(element.id, element.not_viewed);
        
        // Tâche pour l'utilisateur courant
        if(element.user_type == this.my) {
            if(element.can_rep_mail)
               html += this.getbuttonSendMail(element.id);
            if(element.can_close)
                html += this.getbuttonClose(element.id);
            if(element.can_attribute)
                html += this.getButtonRefuseAttribute(element.id);
            
        // Tâche non affectée
        } else if(element.user_type == this.unaffected) {
            if(element.can_rep_mail)
                html += this.getbuttonSendMail(element.id);
            if(element.can_close)
                html += this.getbuttonClose(element.id);
            if(element.can_attribute)
                html += this.getButtonAttribute(element.id);
        }
        
        this.updateNav(element.user_type, 1, element.not_viewed);
        
        element.class = 'single_task';
       
        return html;
    }
    
    updateNav(user_type, nb_task, nb_msg) {
        
        var $nav = $('ul#nav_task > li > a[href="#' + user_type + '"]');
        
        // Init des valeur des attriuts
        if(typeof $nav.attr('nb_task') === typeof undefined)
            $nav.attr('nb_task', 0);
        
        if(typeof $nav.attr('nb_msg') === typeof undefined)
            $nav.attr('nb_msg', 0);
        
        // Calculs des nouvelles valeur
        var new_nb_task = parseInt($nav.attr('nb_task')) + nb_task;
        var new_nb_msg  = parseInt($nav.attr('nb_msg'))  + nb_msg;
        
        if(new_nb_task > 1)
            var s_task = 's';
        else
            var s_task = '';
        
        if(user_type === this.my)
            var to_print = 'tâche' + s_task + ' en attente' + s_task;
        else
            var to_print = 'tâche' + s_task + ' non attribuée' + s_task;
        
        $nav.attr('nb_task', new_nb_task);
        $nav.attr('nb_msg', new_nb_msg);
        
        if(new_nb_msg > 0) {
            if(new_nb_msg > 1)
                var s_msg = 's';
            else
                var s_msg = '';
            $nav.text(new_nb_task + ' ' + to_print + ' ' + new_nb_msg + ' message' + s_msg + ' non lu' + s_msg);
        } else
            $nav.text(new_nb_task + ' ' + to_print);

    }
    
    emptyNav(user_type) {
        
        var $nav = $('ul#nav_task > li > a[href="#' + user_type + '"]');
        
        $nav.attr('nb_task', 0);
        $nav.attr('nb_msg', 0);
        
        if(user_type === this.my)
            var to_print = 'tâche 0 en attente 0';
        else
            var to_print = 'tâche 0 non attribuée 0';
        
        $nav.text(to_print);

    }

    
    displayNotification(element) {
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var n = new Notification(titre, {
                body: element.subj,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                if(parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + bn.dropdown_id).trigger('click');
            }
        }
        
    }
    
    displayMultipleNotification(elements) {
        var nb_task = 0;
        var nb_urg = 0;
        var bn = this;
        
        for(var i in elements) {
            if(elements[i].user_type === this.my) {
                nb_task++;
                if(elements[i].prio === 20)
                    nb_urg++;
            }
        }

        if (window.Notification && Notification.permission === "granted") {
            
            var msg_urg = '';
            
            if(nb_urg != 0) {
                if(nb_urg == 1)
                    msg_urg+= ' (dont ' + nb_urg + ' urgente)';
                else
                    msg_urg+= ' (dont ' + nb_urg + ' urgentes)';
            }
            
            var n = new Notification("Vous avez " + nb_task + msg_urg + " taches en cours.", {
                body: '',
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                $('#' + bn.dropdown_id).trigger('click');
            }
        }
    }
    
    isMultiple(elements) {
        
        var nb_my_task = 0;
        
        for(var i in elements) {
            if(elements[i].user_type === this.my)
                nb_my_task++;
        }
        
        return nb_my_task > 2;
    }
    
    /**
     * Attention dans le cas de cette utilisation pas de mise à jour du span rouge (volontairement)
     */
    remove(id_task) {

        var not_viewed = parseInt($('div[key="task_' + id_task + '"] > button[not_viewed]').attr('not_viewed'));
        
        // Mes tâches
        if($('div[key="task_' + id_task + '"]').parent().parent() === this.my) {
            this.elementRemoved(1, this.dropdown_id);
            this.updateNav(this.unaffected, -1, -not_viewed);
            
        // Tâches non attribuée
        } else {
            this.updateNav(this.unaffected, -1, -not_viewed);
        }
        
        $('div[key="task_' + id_task + '"]').remove();
        
        
    }
    
    move(id_task, prio, id_new_user) {
        
        var to_move = $('div[key="task_' + id_task + '"]');
        var user_type = this.unaffected;
        var prio_type = 'task_no_prio';
        
        var not_viewed = parseInt($('div[key="task_' + id_task + '"] > button[not_viewed]').attr('not_viewed'));
        
        // Ne rien faire lorsque l'on désattribue une tâche déjà non attribuée
        if(parseInt(id_new_user) === 0 && to_move.parent().parent().attr('id') == this.unaffected) {
            return;
        }
                
        // Affecté à l'utilisateur courant
        if(parseInt(id_new_user) > 0 && parseInt(id_user) === parseInt(id_new_user)) {
            this.elementAdded(1, this.dropdown_id);
            user_type = this.my;
            this.updateNav(this.my, 1, not_viewed);
            this.updateNav(this.unaffected, -1, -not_viewed);
        }
        
        // Affecté à l'utilisateur courant
        else {
            this.updateNav(this.my, -1, -not_viewed);
            this.updateNav(this.unaffected, 1, not_viewed);            
        }
        
        // Prioritaire
        if(parseInt(prio) > 0)
            prio_type = 'task_with_prio';
        
        this.swapButton(to_move, id_task);
        

        $('div.tab-content > #' + user_type + ' > div.' + prio_type).prepend(to_move);
        
    }

    swapButton(element, id_task) {
        
        var btn_refuse_attribute = element.find('button[name="refuse_attribute"]');
        if(btn_refuse_attribute.length == 1) {
            var new_button = this.getButtonAttribute(id_task);
            btn_refuse_attribute.replaceWith(new_button);
            return;
        }
        
        var btn_attribute = element.find('button[name="attribute"]');
        if(btn_attribute.length == 1) {
            var new_button = this.getButtonRefuseAttribute(id_task);
            btn_attribute.replaceWith(new_button);
            return;
        }

    }
    
    emptyNotifs() {
        var nb_rm_my = $('.task_with_prio > div').length;
        var nb_rm_unaffected = $('.task_no_prio > div').length;

        $('.task_with_prio').empty();
        $('.task_no_prio').empty();
        
        this.elementRemoved(nb_rm_my, this.dropdown_id);
        this.elementRemoved(nb_rm_unaffected, this.dropdown_id);
        
        this.emptyNav(this.my);
        this.emptyNav(this.unaffected);

    }
}