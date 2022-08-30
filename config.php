<?php
/**
 * Main config file for GVExport Module
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2005	John Finlay and Others
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package PhpGedView
 * @subpackage Modules, GVExport
 * @version 0.8.0
 * @author Ferenc Kurucz <korbendallas1976@gmail.com>
 */

if (preg_match("/\Wconfig.php/", $_SERVER["SCRIPT_NAME"])>0) {
	print "Got your hand caught in the cookie jar.";
	exit;
}

global $GVE_CONFIG;

// GraphViz binary
$GVE_CONFIG["graphviz_bin"] = "/usr/bin/dot"; // Default on Debian Linux
//$GVE_CONFIG["graphviz_bin"] = "/usr/local/bin/dot"; // Default if you compiled Graphviz from source
//$GVE_CONFIG["graphviz_bin"] = "c:\\Graphviz2.17\\bin\\dot.exe"; // for Windows (install dot.exe in a directory with no blank spaces)
//$GVE_CONFIG["graphviz_bin"] = ""; // Uncomment this line if you don't have GraphViz installed on the server

$GVE_CONFIG["filename"] = "gvexport";

// Test we can actually access GraphViz
$stdout_output = null;
$return_var = null;
if (is_exec_available()) {
	exec($GVE_CONFIG["graphviz_bin"] . " -V" . " 2>&1", $stdout_output, $return_var);
}
if (!is_exec_available() || $return_var !== 0)
{
	$GVE_CONFIG["graphviz_bin"] = "";
}

// Output file formats
$GVE_CONFIG["output"]["svg"]["label"] = "SVG"; #ESL!!! 20090213
$GVE_CONFIG["output"]["svg"]["extension"] = "svg";
$GVE_CONFIG["output"]["svg"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tsvg:cairo -o" . $GVE_CONFIG["filename"] . ".svg " . $GVE_CONFIG["filename"] . ".dot";
$GVE_CONFIG["output"]["svg"]["cont_type"] = "image/svg+xml";
$GVE_CONFIG["output"]["svg"]["rewrite_media_paths"] = true;


$GVE_CONFIG["output"]["dot"]["label"] = "DOT"; #ESL!!! 20090213
$GVE_CONFIG["output"]["dot"]["extension"] = "dot";
$GVE_CONFIG["output"]["dot"]["exec"] = "";
$GVE_CONFIG["output"]["dot"]["cont_type"] = "text/plain; charset=utf-8";

$GVE_CONFIG["output"]["png"]["label"] = "PNG"; #ESL!!! 20090213
$GVE_CONFIG["output"]["png"]["extension"] = "png";
$GVE_CONFIG["output"]["png"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tpng -o" . $GVE_CONFIG["filename"] . ".png " . $GVE_CONFIG["filename"] . ".dot";
$GVE_CONFIG["output"]["png"]["cont_type"] = "image/png";

$GVE_CONFIG["output"]["jpg"]["label"] = "JPG"; #ESL!!! 20090213
$GVE_CONFIG["output"]["jpg"]["extension"] = "jpg";
$GVE_CONFIG["output"]["jpg"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tjpg -o" . $GVE_CONFIG["filename"] . ".jpg " . $GVE_CONFIG["filename"] . ".dot";
$GVE_CONFIG["output"]["jpg"]["cont_type"] = "image/jpeg";


if ( !empty( $GVE_CONFIG["graphviz_bin"]) && $GVE_CONFIG["graphviz_bin"] != "") {

	$GVE_CONFIG["output"]["gif"]["label"] = "GIF"; #ESL!!! 20090213
	$GVE_CONFIG["output"]["gif"]["extension"] = "gif";
	$GVE_CONFIG["output"]["gif"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tgif -o" . $GVE_CONFIG["filename"] . ".gif " . $GVE_CONFIG["filename"] . ".dot";
	$GVE_CONFIG["output"]["gif"]["cont_type"] = "image/gif";

	$GVE_CONFIG["output"]["pdf"]["label"] = "PDF"; #ESL!!! 20090213
	$GVE_CONFIG["output"]["pdf"]["extension"] = "pdf";
	$GVE_CONFIG["output"]["pdf"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tpdf -o" . $GVE_CONFIG["filename"] . ".pdf " . $GVE_CONFIG["filename"] . ".dot";
	$GVE_CONFIG["output"]["pdf"]["cont_type"] = "application/pdf";

	$GVE_CONFIG["output"]["ps"]["label"] = "PS"; #ESL!!! 20090213
	$GVE_CONFIG["output"]["ps"]["extension"] = "ps";
	$GVE_CONFIG["output"]["ps"]["exec"] = $GVE_CONFIG["graphviz_bin"] . " -Tps2 -o" . $GVE_CONFIG["filename"] . ".ps " . $GVE_CONFIG["filename"] . ".dot";
	$GVE_CONFIG["output"]["ps"]["cont_type"] = "application/postscript";
}

// Default colors - please use #RRGGBB format
$GVE_CONFIG["dot"]["colorm"] = "#ADD8E6";	// Default color of male individuals (light blue)
$GVE_CONFIG["dot"]["colorf"] = "#FFB6C1";	// Default color of female individuals (light pink)
$GVE_CONFIG["dot"]["colorx"] = "#FCEAA1";	// Default color of Other gender individuals (light yellow)
$GVE_CONFIG["dot"]["coloru"] = "#CCEECC";	// Default color of unknown gender individuals (light green)
$GVE_CONFIG["dot"]["colorm_nr"] = "#EEF8F8";	// Default color of not blood-related male individuals
$GVE_CONFIG["dot"]["colorf_nr"] = "#FDF2F2";	// Default color of not blood-related female individuals
$GVE_CONFIG["dot"]["colorx_nr"] = "#FCF7E3";	// Default color of not blood-related Other gender individuals
$GVE_CONFIG["dot"]["coloru_nr"] = "#D6EED6";	// Default color of not blood-related unknown gender individuals
$GVE_CONFIG["dot"]["colorfam"] = "#FFFFE0";	// Default color of families (different light yellow)
$GVE_CONFIG["dot"]["colorch"] = "#FF0000"; // Default color of changed (waiting for validation) records
$GVE_CONFIG["dot"]["fontsize"] = "10";	// Default font size

// Page and drawing size settings
$GVE_CONFIG["default_pagesize"] = "A4";
$GVE_CONFIG["default_margin"] = "0.5"; // in inches on every side
//A4
$GVE_CONFIG["pagesize"]["A4"]["x"] = "8.267";
$GVE_CONFIG["pagesize"]["A4"]["y"] = "11.692";
//Letter
$GVE_CONFIG["pagesize"]["Letter"]["x"] = "8.5";
$GVE_CONFIG["pagesize"]["Letter"]["y"] = "11";

$GVE_CONFIG["settings"]["dpi"] = "72"; // default DPI (75: screen, 300: print)
$GVE_CONFIG["settings"]["ranksep"] = "100%";
$GVE_CONFIG["settings"]["nodesep"] = "100%";
$GVE_CONFIG["settings"]["space_base"] = .15;

// Direction of graph
$GVE_CONFIG["default_direction"] = "LR";
$GVE_CONFIG["directions"]["TB"] = "Top-to-Bottom";
$GVE_CONFIG["directions"]["LR"] = "Left-to-Right";

// Font name
$GVE_CONFIG["default_fontname"] = "Arial";

// mclimit settings (number of iterations to help to reduce crossings)
$GVE_CONFIG["default_mclimit"] = "50";
$GVE_CONFIG["mclimits"] = ["1" => "1", "5" => "5" , "20" => "20", "50" => "50", "100" => "100"];

// Customization
$GVE_CONFIG["custom"]["birth_text"] = "*";	// Text shown on chart before the birth date
$GVE_CONFIG["custom"]["death_text"] = "+";	// Text shown on chart before the death date

// Settings
$GVE_CONFIG["settings"]["use_abbr_place"] = "Full place name";
$GVE_CONFIG["settings"]["download"] = TRUE;

// Deafult max levels of ancestors
$GVE_CONFIG["settings"]["ance_level"] = 2;
// Deafult max levels of descendants
$GVE_CONFIG["settings"]["desc_level"] = 2;
// By default, if there are clippings in the clippings cart then use them
$GVE_CONFIG["settings"]["usecart"] = TRUE;

// Hide advanced settings by default
$GVE_CONFIG["settings"]["adv_appear"] = FALSE;
$GVE_CONFIG["settings"]["adv_people"] = FALSE;

// Debug mode (if set to true the DOT file & other debug info will be dumped on screen)
$GVE_CONFIG["debug"] = FALSE;

// Load country data for abbreviating place names
// Data comes from https://www.datahub.io/core/country-codes
// This material is licensed by its maintainers under the Public Domain Dedication and License, however,
// they note that the data is ultimately sourced from ISO who have an unclear licence regarding use,
// particularly around commercial use. Though all data sources providing ISO data have this problem.
$string = file_get_contents(dirname(__FILE__)."/resources/data/country-codes_json.json");
$json = json_decode($string, true);
foreach ($json as $row) {
	$GVE_CONFIG["countries"]["iso2"][strtolower($row["CLDR display name"])] = $row["ISO3166-1-Alpha-2"];
	$GVE_CONFIG["countries"]["iso3"][strtolower($row["CLDR display name"])] = $row["ISO3166-1-Alpha-3"];
}
// Options for abbreviating place names
$GVE_CONFIG["settings"]["use_abbr_places"] = [0 => "Full place name", 10 => "City and Country" ,  20 => "City and 2 Letter ISO Country Code", 30 => "City and 3 Letter ISO Country Code"];

// Check if exec function is available to prevent error if webserver has disabled it
// From: https://stackoverflow.com/questions/3938120/check-if-exec-is-disabled
function is_exec_available() {
	static $available;

	if (!isset($available)) {
		$available = true;
		if (ini_get('safe_mode')) {
			$available = false;
		} else {
			$d = ini_get('disable_functions');
			$s = ini_get('suhosin.executor.func.blacklist');
			if ("$d$s") {
				$array = preg_split('/,\s*/', "$d,$s");
				if (in_array('exec', $array)) {
					$available = false;
				}
			}
		}
	}

	return $available;
}

?>
