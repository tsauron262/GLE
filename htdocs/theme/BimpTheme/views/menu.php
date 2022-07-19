<!-- style forcé pour supprimé le filtre bleu (pas très beau) voir pour intégrer dans le css -->
<div class="main-content" style="background-color: white !important;">

   <?php
   
   
   echo '<link rel="stylesheet" type="text/css" title="default" href="' . DOL_URL_ROOT . '/theme/BimpTheme/views/css/custom.css">' . "\n";
   ?>

   <?php 

   function getTotalNoReadMessage()
   {
       global $user;
       $messages = BimpObject::getInstance('bimpcore', "BimpNote");

       return count($messages->getList(["fk_user_dest" => $user->id, "viewed" => 0, "auto" => 0]));
   }

   function displayMessageIcone()
   {
       $nbMessage = getTotalNoReadMessage();
       $html = '';
       $html .= '<div class="dropdown modifDropdown login_block_other">
                                
                                    <a class="nav-link dropdown-toggle" href="#" id="notiDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    
                                        <i class="fa fa-envelope"></i>';

       if ($nbMessage)
           $html .= '<span class="badge bg-danger">' . $nbMessage . '</span>';

       $html .= '</a>
                                    
                                    <div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notiDropdown">
                                    
                                        <h4 class="header">Notifications</h4>
                                        
                                        <div class="notifications-wrap">
                                        </div>
                                        
                                        <div class="footer">
                                        
                                            <a href="javascript:void(0);">See all activity</a>
                                            
                                        </div>
                                        
                                    </div>
                                    
                                </div>
                            ';

       return $html;
   }
   