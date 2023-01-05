<?php

class DolLine extends BimpObject
{

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 0;
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        return 0;
    }
}
