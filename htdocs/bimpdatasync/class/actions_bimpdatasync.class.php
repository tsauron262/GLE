<?php

class ActionsBimpdatasync
{

    public function formattachOptions($parameters, &$object, &$action, $hookmanage)
    {
        if (is_a($object, 'Product')) {
//            if (GETPOST('sendit')) {
                global $user;
                $object->call_trigger('PRODUCT_MODIFY', $user);
//            }
        }
        return 0;
    }
}
