<?php

namespace vendor\WebtreesModules\gvexport;

/**
 * A colour object for operations on colours
 */
class Colour
{
    private int $red;
    private int $blue;
    private int $green;

    /**
     * Create colour instance using hex code
     *
     * @param $hex_col string the colour hex code for this colour
     */
    function __construct(string $hex_col)
    {
        list($this->red, $this->green, $this->blue) = $this->hexToRgb($hex_col);
    }

    /**
     * Converts an HTML hex colour to an array of RGB
     *
     * @param string $hex_col
     * @return array<int>
     */
    function hexToRgb(string $hex_col): array
    {
        return sscanf($hex_col, '#%02x%02x%02x');
    }

    /**
     * Take object's colour and merge with new HTML hex colour
     * using a ratio between the two (0 - 1)
     *
     * @param $colour
     * @param $ratio
     * @return string
     */
    function mergeWithColour(string $colour, int $ratio): string
    {
        list($red, $green, $blue) = $this->hexToRgb($colour);
        $intermediate_rgb = [];
        $intermediate_rgb['r'] = $this->red + ($red - $this->red) * $ratio;
        $intermediate_rgb['g'] = $this->green + ($green - $this->green) * $ratio;
        $intermediate_rgb['b'] = $this->blue + ($blue - $this->blue) * $ratio;
        return sprintf("#%02x%02x%02x", $intermediate_rgb['r'], $intermediate_rgb['g'], $intermediate_rgb['b']);
    }
}
