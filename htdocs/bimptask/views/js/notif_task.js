
class notif_task extends AbstractNotification {
    
    
    
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
//        this.my = 'my_task';
//        this.unaffected = 'unaffected_task';
    }
    
//    setIdMax(id_max) {
//        this.id_max = id_max;
//    }

    getMy() {
        return this.my;
    }
    
    init() {
        
        this.my = 'my_task';
        this.unaffected = 'unaffected_task';
        var nt = this;

        if($('a#' + this.dropdown_id).length == 0) {
       
            var html = '<a class="nav-link dropdown-toggle" href="#" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-tasks atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';

            html +='<div class="tabs-animated">';

            // Nav tabs 
            html +='<ul id="nav_task" class="nav nav-tabs" role="tablist">';
            html +='<li role="presentation" class="active"><a href="#' + nt.my + '" aria-controls="' + nt.my + '" role="tab" data-toggle="tab">Mes tâches</a></li>';
            html +='<li role="presentation"><a href="#' + nt.unaffected + '" aria-controls="' + nt.unaffected + '" role="tab" data-toggle="tab">Tâches non attribuée</a></li>';
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

            $(this.parent_selector).append(html);
            
            
            // Animation slide sur le coté
            $('a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
                    if($(this).attr('aria-controls') == this.my || $(this).attr('aria-controls') == this.unaffected) {
                        var $old_tab = $($(e.target).attr("href"));
                        var $new_tab = $($(e.relatedTarget).attr("href"));

                        if($new_tab.index() < $old_tab.index()){
                                $old_tab.css('position', 'relative').css("right", "0").show();
                                $old_tab.animate({"right":"-100%"}, 300, function () {
                                        $old_tab.css("right", 0).removeAttr("style");
                                });
                        }
                        else {
                                $old_tab.css('position', 'relative').css("left", "0").show();
                                $old_tab.animate({"left":"-100%"}, 300, function () {
                                        $old_tab.css("left", 0).removeAttr("style");
                                });
                        }
                    }
            });

            $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
                    if($(this).attr('aria-controls') == this.my || $(this).attr('aria-controls') == this.unaffected) {
                        var $new_tab = $($(e.target).attr("href"));
                        var $old_tab = $($(e.relatedTarget).attr("href"));

                        if($new_tab.index() > $old_tab.index()){
                                $new_tab.css('position', 'relative').css("right", "-2500px");
                                $new_tab.animate({"right":"0"}, 200);
                        }
                        else {
                                $new_tab.css('position', 'relative').css("left", "-2500px");
                                $new_tab.animate({"left":"0"}, 200);
                        }
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
    
//    static getButton(button_name, name_display, icon, params) {
//        return '<button name="' + button_name + '" class="btn btn-default" type="button" onclick="setObjectAction($(this), '
//        + '{module: \'bimptask\', object_name: \'BIMP_Task\', ' + params 
//        + ');"><i class="fa ' + icon + ' iconLeft"></i>' + name_display + '</button>';
//    }
//    
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
    
//    static getButtonAttribute(id_object) {
//        return notif_task.getButton('attribute', 'Attribuer', 'fa-user', 
//                'id_object: \'' + id_object + '\'}, \'attribute\', {}, '
//              + '\'attribute\', null, null, null, null, false, \'medium\'');
//   }
//    
    getButtonRefuseAttribute(id_object) {
        return this.getButton('refuse_attribute', 'Refuser l\'attribution', 'fa-window-close', 
                'id_object: \'' + id_object + '\'}, \'attribute\', '
              + '{id_user_owner: 0}, null, null, null, \'Refuser cette attribution ?\', null, false, \'medium\'');
    }
    
//    static getButtonRefuseAttribute(id_object) {
//        return notif_task.getButton('refuse_attribute', 'Refuser l\'attribution', 'fa-window-close', 
//                'id_object: \'' + id_object + '\'}, \'attribute\', '
//              + '{id_user_owner: 0}, null, null, null, \'Refuser cette attribution ?\', null, false, \'medium\'');
//    }

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
        // Tâche pour l'utilisateur courant
        if(element.user_type == this.my) {
            html += this.getbuttonSendMail(element.id);
            html += this.getbuttonClose(element.id);
            html += this.getButtonRefuseAttribute(element.id);
        // Tâche non affectée
        } else if(element.user_type == this.unaffected) {
            html += this.getbuttonSendMail(element.id);
            html += this.getbuttonClose(element.id);
            html += this.getButtonAttribute(element.id);
        }
        
        element.class = 'single_task';
       
        return html;
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
        
        if($('div[key="task_' + id_task + '"]').parent().parent() === this.my)
            this.elementRemoved(1, this.dropdown_id);
        
        $('div[key="task_' + id_task + '"]').remove();
    }
    
    // TODO voir comment move lors de clique (version 1)
    move(id_task, prio, id_new_user) {
        
        var to_move = $('div[key="task_' + id_task + '"]');
        var user_type = this.unaffected;
        var prio_type = 'task_no_prio';
        
        // Ne rien faire lorsque l'on désattribue une tâche déjà non attribuée
        if(parseInt(id_new_user) === 0 && to_move.parent().parent().attr('id') == this.unaffected)
            return;
                
        // Affecté à l'utilisateur courant
        if(parseInt(id_new_user) > 0 && parseInt(id_user) === parseInt(id_new_user)) {
            this.elementAdded(1, this.dropdown_id);
            user_type = this.my;
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

    }
}