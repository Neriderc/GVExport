<?php

namespace vendor\WebtreesModules\gvexport;

/**
 * A colour object for operations on colours
 */
class Colour
{
    private $red;
    private $blue;
    private $green;

    /**
     * Create colour instance using hex code
     *
     * @param $hex_col string the colour hex code for this colour
     */
    function __construct(string $hex_col) {
        $this->hex = $hex_col;
        list($this->red, $this->green, $this->blue) = $this->hexToRgb($hex_col);
    }

    function hexToRgb($hex_col) {
        return sscanf($hex_col, '#%02x%02x%02x');
    }
    function mergeWithColour($colour, $ratio): string
    {
        list($red, $green, $blue) = $this->hexToRgb($colour);
        $intermediate_rgb = [];
        $intermediate_rgb['r'] = $this->red + ($red - $this->red) * $ratio;
        $intermediate_rgb['g'] = $this->green + ($green - $this->green) * $ratio;
        $intermediate_rgb['b'] = $this->blue + ($blue - $this->blue) * $ratio;
        return sprintf("#%02x%02x%02x", $intermediate_rgb['r'], $intermediate_rgb['g'], $intermediate_rgb['b']);
    }
}