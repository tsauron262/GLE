
class notif_task extends AbstractNotification {
    
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
    }
    
    init() {
        
        if($('a#' + this.dropdown_id).length == 0) {
       
            var html = '<a class="nav-link dropdown-toggle" href="#" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-tasks atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';

            html +='<div class="tabs-animated">';

            // Nav tabs 
            html +='<ul id="nav_task" class="nav nav-tabs" role="tablist">';
            html +='<li role="presentation" class="active"><a href="#my_task" aria-controls="my_task" role="tab" data-toggle="tab">Mes tâches</a></li>';
            html +='<li role="presentation"><a href="#unaffected_task" aria-controls="unaffected_task" role="tab" data-toggle="tab">Tâches non attribuée</a></li>';
            html +='</ul>';

            // Tab panes 
            html +='<div class="tab-content task_panel">';
            html +='<div role="tabpanel" class="list_notification tab-pane fade in active" id="my_task"><div class="task_with_prio"></div><div class="task_no_prio"></div></div>';
            html +='<div role="tabpanel" class="list_notification tab-pane fade" id="unaffected_task"><div class="task_with_prio"></div><div class="task_no_prio"></div></div>';
            html +='</div>';

            html +='</div>';
            html += '</div>';

            $(this.parent_selector).append(html);
            
            
            // Animation slide sur le coté
            $('a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
                    if($(this).attr('aria-controls') == 'my_task' || $(this).attr('aria-controls') == 'unaffected_task') {
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
                    if($(this).attr('aria-controls') == 'my_task' || $(this).attr('aria-controls') == 'unaffected_task') {
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

//            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
//                    // your code on active tab shown
//            });
            
            super.init(this);
        }
    }
    
    getButton(name_display, icon, params) {
        return '<button class="btn btn-default" type="button" onclick="setObjectAction($(this), '
        + '{module: \'bimptask\', object_name: \'BIMP_Task\', ' + params 
        + ');"><i class="fa ' + icon + ' iconLeft"></i>' + name_display + '</button>';
    }
    
    getbuttonSendMail(id_object) {
        return this.getButton('Rep Mail', 'fa-send', 
                'id_object: \'' + id_object + '\'}, \'sendMail\', {}, \'newMail\','
              + 'null, null, null, null, false, \'medium\'');
    }
    
    getbuttonClose(id_object) {
        return this.getButton('Terminer', 'fa-close', 
                'id_object: \'' + id_object + '\'}, \'close\', {}, null, null, '
              + 'null, \'Terminer la tâche ?\', null, false, \'medium\'');
    }
    
    getButtonAttribute(id_object) {
        return this.getButton('Attribuer', 'fa-user', 
                'id_object: \'' + id_object + '\'}, \'attribute\', {}, '
              + '\'attribute\', null, null, null, null, false, \'medium\'');
   }
    
    getButtonRefuseAttribute(id_object) {
        return this.getButton('Refuser l\'attribution', 'fa-window-close', 
                'id_object: \'' + id_object + '\'}, \'attribute\', '
              + '{id_user_owner: 0}, null, null, null, \'Refuser cette attribution ?\', null, false, \'medium\'');
    }

    getKey(element) {
        return 'task_' + element.id;
    }
    
    formatElement(element, key) {
                    
        var html ='';
        var css_class = '';
//        if(parseInt(element.prio) === 20)
//            css_class += 'clignote';
        
        if(parseInt(element.prio) === 20) {
            html += '<i class="danger fa fa-exclamation iconLeft"></i>';
            element.append = 'div.tab-content > #' + element.user_type + ' > div.task_with_prio';

        } else {
            element.append = 'div.tab-content > #' + element.user_type + ' > div.task_no_prio';
        }
        console.log(element.append);
        html += element.subj + ' de "' + element.src + '" ' + element.txt;
        
        // Tâche pour l'utilisateur courant
        if(element.user_type == 'my_task') {
            html += this.getbuttonSendMail(element.id);
            html += this.getbuttonClose(element.id);
            html += this.getButtonRefuseAttribute(element.id);
        // Tâche non affectée
        } else if(element.user_type == 'unaffected_task') {
            html += this.getbuttonSendMail(element.id);
            html += this.getbuttonClose(element.id);
            html += this.getButtonAttribute(element.id);
        }
        
//        html += '</div>';
        
        element.class = 'single_task';
       
        return html;
    }
    
//    isNew(element) {
//
//        if(id_user === parseInt(element.author.id) || parseInt(element.is_viewed) === 1)
//            return 0;
//        
//        return 1;
//    }
    
//    getKey(element) {
//        return element.obj_type + '_' + element.obj_module + '_' + element.obj_name + '_' + element.id_obj;
//    }

    
    displayNotification(element) {
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var titre = "task";
            var n = new Notification(titre, {
                body: element.content,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                if(parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + bn.dropdown_id).trigger('click');
            }
        }
        
    }
    
    displayMultipleNotification(elements) {
        var nb_valid = elements.length;
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var n = new Notification("Vous avez reçu " + nb_valid + " messages.", {
                body: '',
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                $('#' + bn.dropdown_id).trigger('click');
            }
        }
    }


}