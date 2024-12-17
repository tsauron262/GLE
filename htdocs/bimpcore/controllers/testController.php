<?php

class testController extends BimpController
{

    public function renderHtml()
    {
        $html = '';

        return $html;
        if (!BimpCore::isUserDev()) {
            return BimpRender::renderAlerts('Page réservée aux développeurs');
        }

//        require_once DOL_DOCUMENT_ROOT . '/bimpcore/components/BCV2_Lib.php';
//
//        global $bimp_logs_locked;
//        $bimp_logs_locked = 1;
//
//        $params = array(
//            'title'                => 'Hello',
//            'icon'                 => 'fas_check',
//            'header_icons'         => array(
//                array(
//                    'label'   => 'TEST',
//                    'icon'    => 'fas_check',
//                    'onclick' => 'alert(\'OK !!\')'
//                )
//            ),
//            'footer_extra_content' => '',
//            'elements'             => array(
//                array(
//                    'label' => 'text',
//                    'input' => array(
//                        'name'           => 'text',
//                        'hashtags'       => 1,
//                        'scanner'        => 1,
//                        'max_chars'      => 30,
//                        'regex'          => 'test',
//                        'no_autocorrect' => 1
//                    ),
//                    'help'  => 'TEST TEST TEST'
//                ),
//                array(
//                    'label' => 'password',
//                    'input' => array(
//                        'type' => 'password',
//                    )
//                ),
//                array(
//                    'label' => 'password',
//                    'input' => array(
//                        'type' => 'password',
//                    )
//                ),
//                array(
//                    'label' => 'number',
//                    'input' => array(
//                        'addon_left'  => 'A partir de',
//                        'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
//                        'type'        => 'number',
//                        'min'         => 0,
//                        'max'         => 100,
//                        'min_label'   => 1,
//                        'max_label'   => 1
//                    )
//                ),
//                array(
//                    'label' => 'qty',
//                    'input' => array(
//                        'type' => 'qty'
//                    )
//                ),
//                array(
//                    'label' => 'textarea',
//                    'input' => array(
//                        'name'      => 'textarea',
//                        'type'      => 'textarea',
//                        'max_chars' => 800,
//                        'hashtags'  => 1,
//                        'scanner'   => 1
//                    ),
//                    'help'  => 'TEST TEST TEST'
//                ),
//                array(
//                    'label' => 'html',
//                    'input' => array(
//                        'type' => 'html'
//                    )
//                ),
//                array(
//                    'label' => 'datetime',
//                    'input' => array(
//                        'name'        => 'datetime',
//                        'type'        => 'datetime',
//                        'display_now' => 1,
//                        'range'       => 1
//                    )
//                ),
//                array(
//                    'label' => 'date',
//                    'input' => array(
//                        'name'        => 'date',
//                        'type'        => 'datetime',
//                        'subtype'     => 'date',
//                        'display_now' => 1,
//                        'range'       => 1
//                    )
//                ),
//                array(
//                    'label' => 'time',
//                    'input' => array(
//                        'name'         => 'time',
//                        'type'         => 'datetime',
//                        'subtype'      => 'time',
//                        'with_seconds' => 0,
//                        'display_now'  => 1,
//                        'range'        => 1
//                    )
//                ),
//                array(
//                    'label' => 'timer',
//                    'input' => array(
//                        'name'  => 'timer',
//                        'type'  => 'timer',
//                        'value' => '165432'
//                    )
//                ),
//                array(
//                    'label' => 'select',
//                    'input' => array(
//                        'type'    => 'select',
//                        'options' => array(
//                            0 => 'TEST 1',
//                            1 => array('label' => 'TEST 2', 'icon' => 'fas_check', 'classes' => array('success'))
//                        ),
//                        'value'   => 1
//                    )
//                ),
//                array(
//                    'label' => 'toggle',
//                    'input' => array(
//                        'type'       => 'toggle',
//                        'value'      => 1,
//                        'toggle_off' => 'NO',
//                        'toggle_on'  => 'YES'
//                    )
//                ),
//                array(
//                    'label' => 'check_list',
//                    'input' => array(
//                        'name'  => 'checklist',
//                        'type'  => 'check_list',
//                        'items' => array(
//                            0 => 'TEST 1',
//                            1 => 'TEST 2',
//                            2 => 'TEST 3'
//                        ),
//                        'value' => '1,2'
//                    )
//                ),
//                array(
//                    'label' => 'select_user',
//                    'input' => array(
//                        'type' => 'select_user'
//                    )
//                ),
//                array(
//                    'label' => 'ziptown',
//                    'input' => array(
//                        'type' => 'ziptown'
//                    )
//                ),
//                array(
//                    'label' => 'search_object',
//                    'input' => array(
//                        'type' => 'search_object'
//                    )
//                ),
//                array(
//                    'label' => 'object_filters',
//                    'input' => array(
//                        'type' => 'object_filters'
//                    )
//                ),
//                array(
//                    'label' => 'signature_pad',
//                    'input' => array(
//                        'type' => 'signature_pad'
//                    )
//                ),
//                array(
//                    'label' => 'drop_files',
//                    'input' => array(
//                        'type' => 'drop_files'
//                    )
//                ),
//                array(
//                    'label' => 'file_upload',
//                    'input' => array(
//                        'type' => 'file_upload'
//                    )
//                ),
//                array(
//                    'label' => 'hidden',
//                    'input' => array(
//                        'type' => 'hidden'
//                    )
//                ),
//                array(
//                    'label' => 'custom',
//                    'input' => array(
//                        'type' => 'custom'
//                    )
//                )
//            )
//        );
//
//        $mem = memory_get_usage();
//        $content = BC_V2\BC_Form::render($params);
//
//        $new_mem = memory_get_usage();
//        $diff = $new_mem - $mem;
//        $html .= '<div>';
//        $html .= 'BC_Form::render - Mém : ' . ($diff > 0 ? '+' : '') . round($diff / 1000, 1);
//        $html .= '</div>';
//        $html .= $content;
//        $html .= 'PARAMS : <pre>';
//        $html .= print_r($params, 1);
//        $html .= '</pre>';
//        return $html;
    }
}
