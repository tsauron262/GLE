<?php
/**
 * PHP Integration of Open Flash Chart
 * Copyright (C) 2008 John Glazebrook <open-flash-chart@teethgrinder.co.uk>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/Charts/OFC_Charts_Base.php');

class OFC_Charts_Bar_Value
{
    function OFC_Charts_Bar_Value( $top, $bottom=null )
    {
        $this->top = $top;

        if( isset( $bottom ) )
        {
            $this->bottom = $bottom;
        }
    }

    function set_colour( $colour )
    {
        $this->colour = $colour;
    }

    function set_tooltip( $tip )
    {
        $this->tip = $tip;
    }
}

class OFC_Charts_Bar extends OFC_Charts_Base
{
    function OFC_Charts_Bar()
    {
        parent::OFC_Charts_Base();

        $this->type      = 'bar';
    }

    function set_key( $text, $size )
    {
        $this->text = $text;
        $this->{'font-size'} = $size;
    }

    function set_values( $v )
    {
        $this->values = $v;
    }

    function append_value( $v )
    {
        $this->values[] = $v;
    }

    function set_colour( $colour )
    {
        $this->colour = $colour;
    }
    /**
     *@param $on_show as line_on_show object
     */
    function set_on_show($on_show)
    {
        $this->{'on-show'} = $on_show;
    }

    function set_on_click( $text )
    {
        $tmp = 'on-click';
        $this->$tmp = $text;
    }

    function set_alpha( $alpha )
    {
        $this->alpha = $alpha;
    }

    function set_tooltip( $tip )
    {
            $this->tip = $tip;
    }

}


class OFC_Charts_Bar_On_Show extends OFC_Charts_Bar
{
    /**
     *@param $type as string. Can be any one of:
     * - 'pop-up'
     * - 'drop'
     * - 'fade-in'
     * - 'grow-up'
     * - 'grow-down'
     * - 'pop'
     *
     * @param $cascade as float. Cascade in seconds
     * @param $delay as float. Delay before animation starts in seconds.
     */
    function __construct($type, $cascade, $delay)
    {
        $this->type = $type;
        $this->cascade = (float)$cascade;
        $this->delay = (float)$delay;
    }
}