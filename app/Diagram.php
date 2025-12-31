<?php

namespace vendor\WebtreesModules\gvexport;

/**
 * Diagram functions outside of DOT
 */
class Diagram
{
    /**
     * Saves record count of diagram to session storage
     *
     * @param string $token Token used by front end to check record has been updated
     * @param int $indis
     * @param int $fams
     * @return void
     */
    public static function updateRecordCount(string $token, int $indis, int $fams, int $images)
    {
        $data = json_encode([
            'time_token' => $token,
            'indis' => $indis,
            'fams' => $fams,
            'images' => $images
        ]);
        $_SESSION[Settings::PREFERENCE_PREFIX . '_' . Settings::RECORD_COUNT_PREFERENCE_NAME] = $data;
    }

    /**
     * Loads record count of diagram from session storage
     *
     * @param $token
     * @return string
     */
    static public function loadRecordCount($token): string
    {
        $defaults = [
            'time_token' => '',
            'indis' => -1,
            'fams' => -1,
            'images' => -1
        ];

        $records = $_SESSION[Settings::PREFERENCE_PREFIX . '_' . Settings::RECORD_COUNT_PREFERENCE_NAME] ?? null;

        $decoded = $records ? json_decode($records, true) : null;

        if ($decoded && isset($decoded['time_token']) && $decoded['time_token'] === $token) {
            return $records;
        }

        return json_encode($defaults);
    }


    static function getGraphvizSettings($settings): array
    {
        // Output file formats
        $Graphviz['output']['svg']['label'] = "SVG"; #ESL!!! 20090213
        $Graphviz['output']['svg']['extension'] = "svg";
        $Graphviz['output']['svg']['exec'] = $settings['graphviz_bin'] . " -Tsvg -o" . $settings['filename'] . ".svg " . $settings['filename'] . ".dot";
        $Graphviz['output']['svg']['cont_type'] = "image/svg+xml";

        $Graphviz['output']['dot']['label'] = "DOT"; #ESL!!! 20090213
        $Graphviz['output']['dot']['extension'] = "dot";
        $Graphviz['output']['dot']['exec'] = "";
        $Graphviz['output']['dot']['cont_type'] = "text/plain; charset=utf-8";

        $Graphviz['output']['png']['label'] = "PNG"; #ESL!!! 20090213
        $Graphviz['output']['png']['extension'] = "png";
        $Graphviz['output']['png']['exec'] = $settings['graphviz_bin'] . " -Tpng -o" . $settings['filename'] . ".png " . $settings['filename'] . ".dot";
        $Graphviz['output']['png']['cont_type'] = "image/png";

        $Graphviz['output']['jpg']['label'] = "JPG"; #ESL!!! 20090213
        $Graphviz['output']['jpg']['extension'] = "jpg";
        $Graphviz['output']['jpg']['exec'] = $settings['graphviz_bin'] . " -Tjpg -o" . $settings['filename'] . ".jpg " . $settings['filename'] . ".dot";
        $Graphviz['output']['jpg']['cont_type'] = "image/jpeg";

        $Graphviz['output']['pdf']['label'] = "PDF"; #ESL!!! 20090213
        $Graphviz['output']['pdf']['extension'] = "pdf";
        $Graphviz['output']['pdf']['exec'] = $settings['graphviz_bin'] . " -Tpdf -o" . $settings['filename'] . ".pdf " . $settings['filename'] . ".dot";
        $Graphviz['output']['pdf']['cont_type'] = "application/pdf";

        if (!empty($settings['graphviz_bin']) && $settings['graphviz_bin'] != "") {

            $Graphviz['output']['gif']['label'] = "GIF"; #ESL!!! 20090213
            $Graphviz['output']['gif']['extension'] = "gif";
            $Graphviz['output']['gif']['exec'] = $settings['graphviz_bin'] . " -Tgif -o" . $settings['filename'] . ".gif " . $settings['filename'] . ".dot";
            $Graphviz['output']['gif']['cont_type'] = "image/gif";

            $Graphviz['output']['ps']['label'] = "PS"; #ESL!!! 20090213
            $Graphviz['output']['ps']['extension'] = "ps";
            $Graphviz['output']['ps']['exec'] = $settings['graphviz_bin'] . " -Tps2 -o" . $settings['filename'] . ".ps " . $settings['filename'] . ".dot";
            $Graphviz['output']['ps']['cont_type'] = "application/postscript";
        }

        return $Graphviz;
    }
}