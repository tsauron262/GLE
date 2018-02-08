<?php

class BC_Card extends BimpComponent
{

    public static $type = 'card';
    public $display_object = null;
    public $object_type = '';
    public static $field_params = array(
        'label'   => array('default' => ''),
        'icon'    => array('default' => ''),
        'field'   => array('default' => ''),
        'display' => array('default' => 'default'),
        'prop'    => array('default' => ''),
        'value'   => array('default' => null)
    );

    public function __construct(BimpObject $object, $display_object_name = null, $name = '')
    {
        $this->params_def['type'] = array('default' => '');
        $this->params_def['title'] = array('default' => 'nom');
        $this->params_def['image'] = array();
        $this->params_def['view_btn'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['fields'] = array('type' => 'keys');

        $path = null;

        if (is_null($display_object_name)) {
            $this->display_object = $this->object;
        } else {
            $this->display_object = $object->getChildObject($display_object_name);

            if (is_null($this->display_object)) {
                $this->addError('Objet à afficher invalide');
            }
        }

        if (is_a($this->display_object, 'BimpObject')) {
            $this->object_type = 'bimp_object';
            $object = $this->display_object;

            if (!$name || $name === 'default') {
                if ($object->config->isDefined('card')) {
                    $path = 'card';
                    $name = '';
                } elseif ($object->config->isDefined('cards/default')) {
                    $path = 'cards';
                    $name = 'default';
                }
            } else {
                $path = 'cards';
            }
        } else {
            $this->object_type = 'dol_object';

            if (!$name || $name === 'default') {
                if ($object->config->isDefined('objects/' . $display_object_name . '/card')) {
                    $path = 'objects/' . $display_object_name . '/card';
                    $name = '';
                } elseif ($object->config->isDefined('objects/' . $display_object_name . '/cards/default')) {
                    $path = 'objects/' . $display_object_name . '/cards';
                    $name = 'default';
                }
            } else {
                $path = 'objects/' . $display_object_name . '/cards';
            }
        }

        parent::__construct($object, $name, $path);
    }

    public function renderHtml()
    {
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        switch ($this->object_type) {
            case 'bimp_object':
                $html .= $this->renderBimpObjectCard();
                break;

            case 'dol_object':
                $html .= $this->renderDolObjectCard();
                break;
        }

        return $html;
    }

    public function renderBimpObjectCard()
    {
        $fields = array();

        foreach ($this->params['fields'] as $key) {
            $field_params = parent::fetchParams($this->config_path . '/fields/' . $key, self::$field_params);

            if (is_null($field_params['value'])) {
                if ($field_params['field']) {
                    $field = new BC_Field($this->display_object, $field_params['field']);
                    if ($field_params['display']) {
                        $field->display_name = $field_params['display'];
                    }
                    if (!$field_params['label']) {
                        $field_params['label'] = $field->params['label'];
                    }
                    $field_params['value'] = $field->renderHtml();
                    unset($field);
                } elseif ($field_params['prop']) {
                    if (property_exists($this->display_object, $field_params['prop'])) {
                        $field_params['value'] = $this->display_object->{$field_params['prop']};
                    } else {
                        $field_params['value'] = BimpRender::renderAlerts('Erreur: la propriété "' . $field_params['prop'] . '" n\existe pas');
                    }
                }
            }
            if (!is_null($field_params['value']) && $field_params['value']) {
                $fields[] = array(
                    'label' => $field_params['label'],
                    'icon'  => $field_params['icon'],
                    'value' => $field_params['value']
                );
            }
        }
        if ($this->params['title'] === 'nom') {
            $this->params['title'] = $this->display_object->getInstanceName();
        }

        return self::renderCard($this->display_object, $this->params['title'], $this->params['image'], $fields, $this->params['view_btn']);
    }

    public function renderDolObjectCard()
    {
        switch ($this->params['type']) {
            case 'societe_card':
                return $this->renderSocieteCard();

            case 'contact_card':
                return $this->renderContactCard();

            case 'user_card':
                return $this->renderUserCard();
                
            case 'produt_card':
                return $this->renderProductCard();
        }

        if (is_null($this->params['title']) || !$this->params['title']) {
            $prop = $this->object->getConf($this->config_path . '/title/object_prop', null);
            if (!is_null($prop) && property_exists($this->display_object, $prop)) {
                $this->params['title'] = $this->display_object->{$prop};
            }
        }

        $fields = array();

        foreach ($this->params['fields'] as $name) {
            $field_params = parent::fetchParams($this->config_path . '/fields/' . $name, self::$field_params);

            if (is_null($field_params['value'])) {
                if ($field_params['prop']) {
                    if (property_exists($this->display_object, $field_params['prop'])) {
                        $field_params['value'] = $this->display_object->{$field_params['prop']};
                    } else {
                        $field_params['value'] = BimpRender::renderAlerts('Erreur: la propriété "' . $field_params['prop'] . '" n\existe pas');
                    }
                }
            }
            if (!is_null($field_params['value']) && $field_params['value']) {
                $fields[] = array(
                    'label' => $field_params['label'],
                    'icon'  => $field_params['icon'],
                    'value' => $field_params['value']
                );
            }
        }

        return self::renderCard($this->display_object, $this->params['title'], $this->params['image'], $fields, $this->params['view_btn']);
    }

    public function renderSocieteCard()
    {
        if (!is_a($this->display_object, 'Societe')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas une société');
        }

        $img_url = null; // todo...
        $fields = array();

        if (isset($this->display_object->nom) && isset($this->display_object->nom)) {
            $title = $this->display_object->nom;
            $name = '';
            if (isset($this->display_object->firstname) && $this->display_object->firstname) {
                $name .= BimpTools::ucfirst(strtolower($this->display_object->firstname)) . ' ';
            }
            if (isset($this->display_object->lastname) && $this->display_object->lastname) {
                $name .= strtoupper($this->display_object->lastname);
            }
            if ($name) {
                $fields[] = array(
                    'icon'  => 'user-circle',
                    'value' => $name
                );
            }
        } else {
            $title = strtoupper($this->display_object->lastname) . ' ' . BimpTools::ucfirst(strtolower($this->display_object->firstname));
        }

        if (isset($this->display_object->code_client) && $this->display_object->code_client) {
            $fields[] = array(
                'label' => 'Code client',
                'value' => $this->display_object->code_client
            );
        }

        if (isset($this->display_object->code_fournisseur) && $this->display_object->code_fournisseur) {
            $fields[] = array(
                'label' => 'Code fournisseur',
                'value' => $this->display_object->code_fournisseur
            );
        }

        $address = '';
        if (isset($this->display_object->address) && $this->display_object->address) {
            $address .= $this->display_object->address . '<br/>';
        }

        if (isset($this->display_object->zip) && $this->display_object->zip) {
            $address .= $this->display_object->zip . ' ';
        }

        if (isset($this->display_object->town) && $this->display_object->town) {
            $address .= $this->display_object->town . '<br/>';
        }

        if (isset($this->display_object->pays) && $this->display_object->pays) {
            $address .= $this->display_object->pays;
        } elseif (isset($this->display_object->country) && $this->display_object->country) {
            $address .= $this->display_object->country;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->display_object->phone) && $this->display_object->phone) {
            $fields[] = array(
                'icon'  => 'phone',
                'value' => $this->display_object->phone
            );
        }

        if (isset($this->display_object->fax) && $this->display_object->fax) {
            $fields[] = array(
                'icon'  => 'fax',
                'value' => $this->display_object->fax
            );
        }

        if (isset($this->display_object->email) && $this->display_object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->display_object->email . '" style="text-overflow: ellipsis;">' . $this->display_object->email . '</a>'
            );
        }

        if (isset($this->display_object->skype) && $this->display_object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->display_object->skype
            );
        }

        if (isset($this->display_object->url) && $this->display_object->url) {
            $fields[] = array(
                'icon'  => 'home',
                'value' => '<a href="http://' . $this->display_object->url . '" title="Site web" target="_blank">' . $this->display_object->url . '</a>'
            );
        }

        return self::renderCard($this->display_object, $title, $img_url, $fields, true);
    }

    public function renderContactCard()
    {
        if (!is_a($this->display_object, 'Contact')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas un contact');
        }
        $img_url = null; // todo...
        $fields = array();

        $title = '';

        if (isset($this->display_object->lastname) && $this->display_object->lastname) {
            $title .= strtoupper($this->display_object->lastname) . ' ';
        }
        if (isset($this->display_object->firstname) && $this->display_object->firstname) {
            $title .= BimpTools::ucfirst(strtolower($this->display_object->firstname));
        }


        $address = '';
        if (isset($this->display_object->address) && $this->display_object->address) {
            $address .= $this->display_object->address . '<br/>';
        }

        if (isset($this->display_object->zip) && $this->display_object->zip) {
            $address .= $this->display_object->zip . ' ';
        }

        if (isset($this->display_object->town) && $this->display_object->town) {
            $address .= $this->display_object->town;
        }

        if (isset($this->display_object->state) && $this->display_object->state) {
            $address .= ' - ' . $this->display_object->state;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->display_object->phone_pro) && $this->display_object->phone_pro) {
            $fields[] = array(
                'label' => 'Pro',
                'icon'  => 'phone',
                'value' => $this->display_object->phone_pro
            );
        }

        if (isset($this->display_object->phone_perso) && $this->display_object->phone_perso) {
            $fields[] = array(
                'label' => 'Perso',
                'icon'  => 'phone',
                'value' => $this->display_object->phone_perso
            );
        }

        if (isset($this->display_object->phone_mobile) && $this->display_object->phone_mobile) {
            $fields[] = array(
                'label' => 'Mobile',
                'icon'  => 'mobile',
                'value' => $this->display_object->phone_mobile
            );
        }

        if (isset($this->display_object->fax) && $this->display_object->fax) {
            $fields[] = array(
                'icon'  => 'fax',
                'value' => $this->display_object->fax
            );
        }

        if (isset($this->display_object->email) && $this->display_object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->display_object->email . '" style="text-overflow: ellipsis;">' . $this->display_object->email . '</a>'
            );
        }

        if (isset($this->display_object->skype) && $this->display_object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->display_object->skype
            );
        }

        return self::renderCard($this->display_object, $title, $img_url, $fields, true);
    }

    public function renderUserCard()
    {
        if (!is_a($this->display_object, 'User')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas un utilisateur');
        }
        
        $img_url = null; // todo...
        $fields = array();

        $title = '';

        if (isset($this->display_object->name) && $this->display_object->name) {
            $title .= $this->display_object->name;
        } else {
            if (isset($this->display_object->lastname) && $this->display_object->lastname) {
                $title .= strtoupper($this->display_object->lastname) . ' ';
            }
            if (isset($this->display_object->firstname) && $this->display_object->firstname) {
                $title .= BimpTools::ucfirst(strtolower($this->display_object->firstname));
            }
        }

        if (isset($this->display_object->job) && $this->display_object->job) {
            $fields[] = array(
                'label' => 'Fonction',
                'value' => $this->display_object->Job
            );
        }

        $address = '';
        if (isset($this->display_object->address) && $this->display_object->address) {
            $address .= $this->display_object->address . '<br/>';
        }

        if (isset($this->display_object->zip) && $this->display_object->zip) {
            $address .= $this->display_object->zip . ' ';
        }

        if (isset($this->display_object->town) && $this->display_object->town) {
            $address .= $this->display_object->town;
        }

        if (isset($this->display_object->state) && $this->display_object->state) {
            $address .= ' - ' . $this->display_object->state;
        }

        if ($address) {
            $fields[] = array(
                'icon'  => 'map-marker',
                'value' => $address
            );
        }

        if (isset($this->display_object->office_phone) && $this->display_object->office_phone) {
            $fields[] = array(
                'label' => 'Bureau',
                'icon'  => 'phone',
                'value' => $this->display_object->phone_pro
            );
        }

        if (isset($this->display_object->office_fax) && $this->display_object->office_fax) {
            $fields[] = array(
                'label' => 'Bureau',
                'icon'  => 'fax',
                'value' => $this->display_object->fax
            );
        }

        if (isset($this->display_object->user_mobile) && $this->display_object->user_mobile) {
            $fields[] = array(
                'label' => 'Mobile',
                'icon'  => 'mobile',
                'value' => $this->display_object->user_mobile
            );
        }

        if (isset($this->display_object->email) && $this->display_object->email) {
            $fields[] = array(
                'icon'  => 'envelope',
                'value' => '<a href="mailto:' . $this->display_object->email . '" style="text-overflow: ellipsis;">' . $this->display_object->email . '</a>'
            );
        }

        if (isset($this->display_object->skype) && $this->display_object->skype) {
            $fields[] = array(
                'icon'  => 'skype',
                'value' => $this->display_object->skype
            );
        }

        return self::renderCard($this->display_object, $this->display_object, $title, $img_url, $fields, true);
    }
    
    public function renderProductCard()
    {
        if (!is_a($this->display_object, 'Product')) {
            return BimpRender::renderAlerts('Erreur de configuration. Cet objet n\'est pas un utilisateur');
        }
        
        $img_url = BimpTools::getProductMainImgUrl($this->display_object);
        
        $fields = array();
    }

    public static function renderCard($object, $title, $img_url = '', $fields = array(), $view_btn = false)
    {
        $html = '';
        $html .= '<div class="media object_card">';
        if (!is_null($img_url) && $img_url) {
            $html .= '<div class="media-left">';
            $html .= '<img src="' . $img_url . '" alt=""/>';
            $html .= '</div>';
        }
        $html .= '<div class="media-body">';
        if (!is_null($title) && $title) {
            $html .= '<h4 class="media-heading">' . $title . '</h4>';
        }
        $html .= '<table class="object_card_table">';
        $html .= '<thead></thead>';
        $html .= '<tbody>';
        foreach ($fields as $field) {
            if (isset($field['value']) && $field['value']) {
                $html .= '<tr>';
                $html .= '<th>';
                if (isset($field['icon']) && $field['icon']) {
                    $html .= '<i class="fa fa-' . $field['icon'] . ' iconLeft"></i>';
                }
                if (isset($field['label']) && $field['label']) {
                    $html .= $field['label'] . ':';
                }
                $html .= '</th>';

                $html .= '<td>' . $field['value'] . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        if ($view_btn && !is_null($object)) {
            $url = BimpObject::getInstanceUrl($object);

            if ($url) {
                $html .= '<div style="text-align: right; margin-top: 15px">';
                $html .= '<div class="btn-group">';

                $html .= '<a type="button" class="btn btn-default bs-popover"';
                $html .= ' href="' . $url . '"';
                $html .= ' data-toggle="popover"';
                $html .= ' data-trigger="hover"';
                $html .= ' data-content="Afficher la page"';
                $html .= ' data-container="body"';
                $html .= ' data-placement="top">';
                $html .= '<i class="fa fa-file-o iconLeft"></i>';
                $html .= 'Afficher</a>';

                $html .= '<button type="button" class="btn btn-default bs-popover" ';
                $html .= ' data-toggle="popover"';
                $html .= ' data-trigger="hover"';
                $html .= ' data-content="vue rapide"';
                $html .= ' data-container="body"';
                $html .= ' data-placement="top"';
                $html .= 'onclick="loadModalObjectPage($(this), \'' . $url . '\', \'page_modal\', \'' . htmlentities(addslashes($title)) . '\')">';
                $html .= '<i class="fa fa-eye"></i></button>';

                $html .= '<a href="' . $url . '" class="btn btn-default bs-popover" target="_blank"';
                $html .= 'data-toggle="popover"';
                $html .= ' data-trigger="hover"';
                $html .= ' data-content="Afficher dans un nouvel onglet"';
                $html .= ' data-container="body"';
                $html .= ' data-placement="top"';
                $html .= '>';
                $html .= '<i class="fa fa-external-link"></i>';
                $html .= '</a>';

                $html .= '</div>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
