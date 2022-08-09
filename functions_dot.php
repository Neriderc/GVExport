<?php
/**
 * DOT file generating functions for GraphViz module
 *
 * Based on script made by Nick J <nickpj At The Host Called gmail.com> - http://nickj.org/
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2007  John Finlay and Others
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
 * @version 0.8.3
 * @author Ferenc Kurucz <korbendallas1976@gmail.com>
 * @license GPL v2 or later
 */

namespace vendor\WebtreesModules\gvexport;

// Load the config file
require_once(dirname(__FILE__)."/config.php");
require_once("functionsClippingsCart.php");

use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\I18n;
//use League\Flysystem\Util;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Registry;


/**
 * Main class for managing the DOT file
 *
 */
class Dot {
	var $individuals = array();
	var $families = array();
	var $indi_search_method = array("ance" => FALSE, "desc" => FALSE, "spou" => FALSE, "sibl" => FALSE, "cous" => FALSE, "any" => FALSE);
	var $font_size;
	var $colors = array();
	var $settings = array();
	var $pagesize = array();

	/**
	 * Constructor of Dot class
	 */
	function __construct($tree, $file_system, $use_urls_for_media) {
		global $GVE_CONFIG;
		// Load settings from config file

		$this->tree = $tree;
		$this->file_system = $file_system;
		$this->use_urls_for_media = $use_urls_for_media;

		// Load font size
		$this->font_size = $GVE_CONFIG["dot"]["fontsize"];
		$this->settings["fontname"] = $GVE_CONFIG["default_fontname"];

		// Load colors
		$this->colors["colorm"] = $GVE_CONFIG["dot"]["colorm"];
		$this->colors["colorf"] = $GVE_CONFIG["dot"]["colorf"];
		$this->colors["colorx"] = $GVE_CONFIG["dot"]["colorx"];
		$this->colors["coloru"] = $GVE_CONFIG["dot"]["coloru"];
		$this->colors["colorm_nr"] = $GVE_CONFIG["dot"]["colorm_nr"];
		$this->colors["colorf_nr"] = $GVE_CONFIG["dot"]["colorf_nr"];
		$this->colors["colorx_nr"] = $GVE_CONFIG["dot"]["colorx_nr"];
		$this->colors["coloru_nr"] = $GVE_CONFIG["dot"]["coloru_nr"];
		$this->colors["colorfam"] = $GVE_CONFIG["dot"]["colorfam"];

		// Default settings
		$this->settings["diagram_type"] = "simple";
		$this->settings["diagram_type_combined_with_photo"] = true;
		$this->settings["indi"] = "";
		$this->settings["multi_indi"] = FALSE;
		$this->settings["use_pagesize"] = "";
		$this->settings["page_margin"] = $GVE_CONFIG["default_margin"];
		$this->settings["show_lt_editor"] = FALSE;
		$this->settings["mark_not_related"] = FALSE;
		$this->settings["graph_dir"] = $GVE_CONFIG["default_direction"];
		$this->settings["mclimit"] = $GVE_CONFIG["default_mclimit"];

		$this->settings["show_by"] = FALSE;
		$this->settings["show_bp"] = FALSE;
		$this->settings["show_dy"] = FALSE;
		$this->settings["show_dp"] = FALSE;
		$this->settings["show_my"] = FALSE;
		$this->settings["show_mp"] = FALSE;
		$this->settings["show_pid"] = FALSE;
		$this->settings["show_fid"] = FALSE;
		$this->settings["show_url"] = FALSE;

		$this->settings["no_fams"] = FALSE;

		$this->settings["use_abbr_place"] = $GVE_CONFIG["settings"]["use_abbr_place"];
		$this->settings["use_abbr_places"] = $GVE_CONFIG["settings"]["use_abbr_places"];
		$this->settings["countries"] = $GVE_CONFIG["countries"];
		$this->settings["download"] = $GVE_CONFIG["settings"]["download"];
		$this->settings["debug"] = $GVE_CONFIG["debug"];

		$this->settings["ance_level"] = $GVE_CONFIG["settings"]["ance_level"];
		$this->settings["desc_level"] = $GVE_CONFIG["settings"]["desc_level"];
		$this->settings["usecart"] = $GVE_CONFIG["settings"]["usecart"];

		$this->settings["birth_text"] = $GVE_CONFIG["custom"]["birth_text"];
		$this->settings["death_text"] = $GVE_CONFIG["custom"]["death_text"];

		$this->settings["dpi"] = $GVE_CONFIG["settings"]["dpi"];
		$this->settings["ranksep"] = $GVE_CONFIG["settings"]["ranksep"];
		$this->settings["nodesep"] = $GVE_CONFIG["settings"]["nodesep"];
		$this->settings["space_base"] = $GVE_CONFIG["settings"]["space_base"];
	}

	function setPageSize($pagesize, $size_x = FALSE, $size_y = FALSE) {
		global $GVE_CONFIG;
		if ($pagesize == "Custom" && isset($size_x) && isset($size_y)) {
			$this->pagesize["x"] = $size_x;
			$this->pagesize["y"] = $size_y;
		} elseif (!empty($pagesize) && isset($GVE_CONFIG["pagesize"][$pagesize]["x"]) && isset($GVE_CONFIG["pagesize"][$pagesize]["y"])) {
			$this->pagesize["x"] = $GVE_CONFIG["pagesize"][$pagesize]["x"];
			$this->pagesize["y"] = $GVE_CONFIG["pagesize"][$pagesize]["y"];
		} else {
			$pagesize = $GVE_CONFIG["default_pagesize"];
			$this->pagesize["x"] = $GVE_CONFIG["pagesize"][$pagesize]["x"];
			$this->pagesize["y"] = $GVE_CONFIG["pagesize"][$pagesize]["y"];
		}
	}

	/**
	 * Function to set settings
	 *
	 * @param string $setting
	 * @param mixed $value
	 */
	function setSettings($setting, $value) {
		$this->settings[$setting] = $value;
	}

	/**
	 * Function to set gender and family colors
	 *
	 * @param string $color_type
	 * @param string $color
	 */
	function setColor($color_type, $color) {
		$this->colors[$color_type] = $color;
	}

	/**
	 * Function to set font size
	 *
	 * @param string $font_size
	 */
	function setFontSize($font_size) {
		$this->font_size = $font_size;
	}

	/**
	 * Sets the method used during the search of individuals
	 *
	 * The method could be:
	 *  "ance" - search for ancestors
	 *  "desc" - search for descendants
	 *  "spou" - search for spouses
	 *  "sibl" - search for siblings
	 *
	 * @param string $method
	 */
	function setIndiSearchMethod($method) {
		$this->indi_search_method[$method] = TRUE;
	}

	function getDOTDump() {
		$out = "";

		// --- DEBUG ---
		if ($this->settings["debug"]) {
			print("<pre>");
		}
		// -------------

		$out .= $this->createDOTDump();

		// --- DEBUG ---
		if ($this->settings["debug"]) {
			print("</pre>");
		}
		// -------------

		return $out;
	}

	/**
	 * get preference in this tree to show thumbnails
	 * @param object $tree
	 *
	 * @return bool
	 */
	private function isTreePreferenceShowingThumbnails(object $tree): bool
	{
		return ($tree->getPreference('SHOW_HIGHLIGHT_IMAGES') == '1');
	}

	/**
	 * check if a photo is required
	 *
	 * @return bool
	 */
	private function isPhotoRequired(): bool
	{
		return ($this->isTreePreferenceShowingThumbnails($this->tree) &&
			($this->settings["diagram_type_combined_with_photo"]));
	}

	/** Add formatting to name before adding to DOT
	 * @param string $name full name of the person
	 * @param string $pid XREF of the person, for adding to name if enabled
	 * @return string Returns formatted name
	 */
	function formatName($name, $pid): string {
		// Show nickname in quotes
		$name = str_replace(array('<q class="wt-nickname">', '</q>'), array('"', '"'), $name);

		// Show preferred name as underlined by replacing span with underline tags
		$pos_start = strpos($name,'<span class="starredname">');
		if ($pos_start !== false) {
			$pos_end = strpos(substr($name, $pos_start), "</span>") + $pos_start;
			if ($pos_end) {
				$name = substr_replace($name, "_/U_", $pos_end, strlen("</span>"));
			}
			$name = str_replace('<span class="starredname">', '_U_', $name);
		}
		$name = strip_tags($name);
		// We use _ instead of < >, remove tags, then switch them to proper tags. This lets
		// us control the tags included in an environment where we don't normally have control
		$name = str_replace("_U_", "<u>", $name);
		$name = str_replace("_/U_", "</u> ", $name);

		// If PID already in name (from another module), remove it so we don't add twice
		$name = str_replace(" (" . $pid . ")", "", $name);
		if ($this->settings["show_pid"]) {
			// Show INDI id
			$name = $name . " (" . $pid . ")";
		}
		return $name;
	}

	/** Checks if provided individual is related by
	 * adoption to the provided family record
	 * @param individual $i webtrees individual object for the person to check
	 * @param family $f webtrees family object for the family to check against
	 * @return string
	 */
	function checkIndiAdopted($i, $f) {
		$fid = $f->xref();
		$facts = $i->facts();
		$adopfam_found = FALSE;
		// Find out that actual family has adopters or not
		foreach ($facts as $fact) {
			if (substr_count($fact->gedcom(), "1 ADOP") > 0) {
				$adop = preg_split("/\n/", $fact->gedcom());
				foreach ($adop as $adopline) {
					if (substr_count($adopline, "2 FAMC") > 0) {
						$adopfamcline = preg_split("/@/", $adopline);
						$adopfamid = $adopfamcline[1];

						// Adopter family found
						if ($adopfamid == $fid) {
							$adopfam_found = TRUE;
							// ---DEBUG---
							if ($this->settings["debug"]) {
								$this->printDebug("($i->xref()) -- ADOP record: " . preg_replace("/\n/", " | ", $fact->gedcom()) . "\n", $ind);
							}
							// -----------
						}
					}

					if ($adopfam_found && substr_count($adopline, "3 ADOP") > 0) {
						$adopfamcadopline = preg_split("/ /", $adopline);
						$adopfamcadoptype = $adopfamcadopline[2];
					}
				}
			}
		}
		if (!isset($adopfamcadoptype)) {
			$adopfamcadoptype = "";
		}
		return $adopfamcadoptype;
	}
	function createIndiList () {
		if ($this->settings["multi_indi"] == FALSE) {
			$this->addIndiToList("Start | Code 15", $this->settings["indi"], $this->indi_search_method["ance"], $this->indi_search_method["desc"], $this->indi_search_method["spou"], $this->indi_search_method["sibl"], TRUE, 0, 0);
		} else {
			// if multiple indis are defined
			$indis = explode(",", $this->settings["indi"]);
			for ($i=0;$i<count($indis);$i++) {
				$this->addIndiToList("Start | Code 16", trim($indis[$i]), $this->indi_search_method["ance"], $this->indi_search_method["desc"], $this->indi_search_method["spou"], $this->indi_search_method["sibl"], TRUE, 0, 0);
			}
		}
		// -- DEBUG ---
		if ($this->settings["debug"]) {
			$this->printDebug("Finished individuals list: ".print_r($this->individuals), $ind);
		}
		// -------------
	}

	function createDOTDump() {
		// If no individuals in the clippings cart (or option chosen to overide), use standard method
		if (!functionsClippingsCart::isIndividualInCart($this->tree) || !$this->settings["usecart"] ) {
			$this->createIndiList();
		} else {
		// If individuals in clipping cart and option chosen to use them, then proceed
			$functionsCC = new functionsClippingsCart($this->tree, $this->isPhotoRequired(), ($this->settings["diagram_type"] == "combined"));
			$this->individuals = $functionsCC->getIndividuals();
			$this->families = $functionsCC->getFamilies();
		}

		$out = "";
		$out .= $this->printDOTHeader();

		// ### Print the individuals list ###
		if ($this->settings["diagram_type"] == "combined") {
			// Do nothing, print only families
		} else {
			foreach ($this->individuals as $pid) {
				$out .= $this->printPerson($pid['pid'], $pid['rel']);
			}
		}

		// ### Print the families list ###
		// If no_fams option is not checked then we print the families
		if ($this->settings["no_fams"] == FALSE) {
			foreach ($this->families as $fid=>$fam_data) {
				if ($this->settings["diagram_type"] == "combined") {
					// We do not show those families which has no parents and children in case of "combined" view;
					if ((isset($this->families[$fid]["has_children"]) && $this->families[$fid]["has_children"] == TRUE)
							|| (isset($this->families[$fid]["has_parents"]) && $this->families[$fid]["has_parents"] == TRUE)
							) {
						$out .= $this->printFamily($fid);
					}
				} elseif ($this->settings["diagram_type"] != "combined") {
					$out .= $this->printFamily($fid);
				}
			}
		}

		// ### Print the connections ###
		// If no_fams option is not checked
		if ($this->settings["no_fams"] == FALSE) {
			foreach ($this->families as $fid=>$set) {
				// COMBINED type diagram
				if ($this->settings["diagram_type"] == "combined") {
					if (substr($fid, 0, 2) == "F_") {
						// In case of dummy family do nothing, because it has no children
						//$this->families[$fid]["has_children"] = FALSE;
					} else {
						// Get the family data
						$f = $this->getUpdatedFamily($fid);

						// Draw an arrow from FAM to each CHIL
						foreach ($f->children() as $child) {
							if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
								//$this->families[$fid]["has_children"] = TRUE;
								$fams = isset($this->individuals[$child->xref()]["fams"]) ? $this->individuals[$child->xref()]["fams"] : [];
								foreach ($fams as $fam_nr=>$fam) {
									$out .= $this->convertID($fid) . " -> " . $this->convertID($fam) . ":" . $this->convertID($child->xref()) . "\n";
								}
							}
						}
					}
				} else {
					// Get the family data
					$f = $this->getUpdatedFamily($fid);

					// Get the husband & wife ID
                    $h = $f->husband();
                    $w = $f->wife();
                    if($h)
                        $husb_id = $h->xref();
                    else
                        $husb_id = null;
                    if($w)
                        $wife_id = $w->xref();
                    else
                        $wife_id = null;

					// Draw an arrow from HUSB to FAM
					if (!empty($husb_id) && (isset($this->individuals[$husb_id]))) {
						$out .= $this->convertID($husb_id) . " -> " . $this->convertID($fid) ."\n";
					}
					// Draw an arrow from WIFE to FAM
					if (!empty($wife_id) && (isset($this->individuals[$wife_id]))) {
						$out .= $this->convertID($wife_id) . " -> ". $this->convertID($fid) ."\n";
					}
					// Draw an arrow from FAM to each CHIL
					foreach ($f->children() as $child) {
						if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
							$out .= $this->convertID($fid) . " -> " . $this->convertID($child->xref()) . "\n";
						}
					}
				}
			}
		} else {
		// If no_fams option is checked then we do not print the families
			foreach ($this->families as $fid=>$set) {
				if ($this->settings["diagram_type"] == "combined") {
					/*
					*/
				} else {
					$f = $this->getUpdatedFamily($fid);
					// Draw an arrow from HUSB and WIFE to FAM
					$husb_id = empty($f->husband()) ? null : $f->husband()->xref();
					$wife_id = empty($f->wife()) ? null : $f->wife()->xref();

					// Draw an arrow from FAM to each CHIL
					foreach ($f->children() as $child) {
						if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
							if (!empty($husb_id) && (isset($this->individuals[$husb_id]))) {
								$out .= $this->convertID($husb_id) . " -> " . $this->convertID($child->xref()) ."\n";
							}
							if (!empty($wife_id) && (isset($this->individuals[$wife_id]))) {
								$out .= $this->convertID($wife_id) . " -> ". $this->convertID($child->xref()) ."\n";
							}
						}
					}
				}
			}
		}

		$out .= $this->printDOTFooter();

		return $out;
	}

	/**
	 * Returns an abbreviated version of the PLAC string.
	 *
	 * @param	string	Place string in long format (Town,County,State/Region,Country)
	 * @return	string	The abbreviated place name
	 */
	function getFormattedPlace(string $place_long) {
		// If chose no abbreviating, then return string untouched
		if ($this->settings["use_abbr_place"] == 0 /* Full place name */) {
			return $place_long;
		} else {
			// Cut the place name up into pieces using the commas
			$place_chunks = explode(",", $place_long);
			$place = "";
			$chunk_count = count($place_chunks);
			// Add city to out return string as we always keep this
			if (!empty($place_chunks[0])) {
				$place .= trim($place_chunks[0]);
			}
			// Chose to keep just the first and last sections
			if ($this->settings["use_abbr_place"] == 10 /* City and Country */) {
				if (!empty($place_chunks[$chunk_count - 1]) && ($chunk_count > 1)) {
					if (!empty($place)) {
						$place .= ", ";
					}
					$place .= trim($place_chunks[$chunk_count - 1]);
				}
				return $place;
			} else {
				/* Otherwise, we have chosen one of the ISO code options */
				switch ($this->settings["use_abbr_place"]) {
					case 20: //City and 2 Letter ISO Country Code
						$code = "iso2";
						break;
					case 30: //City and 3 Letter ISO Country Code
						$code = "iso3";
						break;
					default:
						return $place_long;
				}
				/* It's possible the place name string was blank, meaning our return variable is
					   still blank. We don't want to add a comma if that's the case. */
				if (!empty($place) && !empty($place_chunks[$chunk_count - 1]) && ($chunk_count > 1)) {
					$place .= ", ";
				}
				/* Look up our country in the array of country names.
				   It must be an exact match, or it won't be abbreviated to the country code. */
				if (isset($this->settings["countries"][$code][strtolower(trim($place_chunks[$chunk_count - 1]))])) {
					$place .= $this->settings["countries"][$code][strtolower(trim($place_chunks[$chunk_count - 1]))];
				} else {
					// We didn't find out country in the abbreviation list, so just add the full country name
					if (!empty($place_chunks[$chunk_count - 1]) && ($chunk_count > 1)) {
						$place .= trim($place_chunks[$chunk_count - 1]);
					}
				}
				return $place;
			}
		}
	}

	/**
 	 * Gets the colour associated with the given gender
 	 *
 	 * If a custom colour was used then this function will pull it from the form
 	 * otherwise it will use the default colours in the config file
 	 *
 	 * @param char $gender (F/M/U)
 	 * @param boolean $related (TRUE/FALSE) Person is blood-related
 	 * @return string $colour (#RRGGBB)
 	 */
	function getGenderColour($gender, $related = TRUE) {
		global $GVE_CONFIG;
		// Determine the fill color
		if ($gender == 'F') {
			if ($related) {
				$fillcolor = $this->colors["colorf"];
			} else  {
				$fillcolor = $this->colors["colorf_nr"];
			}
		} elseif ($gender == 'M'){
			if ($related) {
				$fillcolor = $this->colors["colorm"];
			} else  {
				$fillcolor = $this->colors["colorm_nr"];
			}
		} elseif ($gender == 'X'){
			if ($related) {
				$fillcolor = $this->colors["colorx"];
			} else  {
				$fillcolor = $this->colors["colorx_nr"];
			}
		} else {
			if ($related) {
				$fillcolor = $this->colors["coloru"];
			} else  {
				$fillcolor = $this->colors["coloru_nr"];
			}
		}
		return $fillcolor;
	}

	/**
 	 * Gets the colour associated with the families
 	 *
 	 * If a custom colour was used then this function will pull it from the form
 	 * otherwise it will use the default colours in the config file
 	 *
 	 * @return string colour (#RRGGBB)
 	 */
	function getFamilyColour() {
		global $GVE_CONFIG;
		// Determine the fill color
		$fillcolor = $this->colors["colorfam"];
		return $fillcolor;
	}

	/**
	 * Prints DOT header string.
	 *
	 * @return	string	DOT header text
	 */
	function printDOTHeader() {
		$out = "";
		$out .= "digraph WT_Graph {\n";
		// Using pagebreak
		if (!empty($this->settings["use_pagesize"])) {
			$out .= "ratio=\"auto\"\n";
			//$out .= "/* PAGESIZE: " . $this->settings["use_pagesize"] . " */";
			// Size of the page
			$out .= "page=\"" . $this->pagesize["x"] . "," . $this->pagesize["y"] . "\"\n";
			// Size of the drawing (pagesize - 1 inch)
			$out .= "size=\"" . ($this->pagesize["x"] - $this->settings["page_margin"]) . "," . ($this->pagesize["y"] - $this->settings["page_margin"]) . "\"\n";
			//$out .= "size=\"50, 50\"\n";
		}

		$out .= "ranksep=\"" . str_replace("%","",$this->settings["ranksep"])*$this->settings["space_base"]/100 . " equally\"\n";
		$out .= "nodesep=\"" . str_replace("%","",$this->settings["nodesep"])*$this->settings["space_base"]/100	 . "\"\n";
		$out .= "dpi=\"" . $this->settings["dpi"] . "\"\n";
		$out .= "mclimit=\"" . $this->settings["mclimit"] . "\"\n";
		$out .= "rankdir=\"" . $this->settings["graph_dir"] . "\"\n";
		$out .= "pagedir=\"LT\"\n";
		$out .= "edge [ style=solid, arrowhead=normal arrowtail=none];\n";
		// I need Arial font because of UTF-8 characters - feel free to change it
		if ($this->settings["diagram_type"] == "simple") {
			$out .= "node [ shape=box, style=filled fontsize=\"" . $this->font_size ."\" fontname=\"" . $this->settings["fontname"] ."\"];\n";
		} else {
			$out .= "node [ shape=plaintext fontsize=\"" . $this->font_size ."\" fontname=\"" . $this->settings["fontname"] ."\"];\n";
		}
		return $out;
	}

	/**
	 * Prints DOT footer string.
	 *
	 * @return	string	DOT header text
	 */
	function printDOTFooter() {
		$out = "";
		$out .= "}\n";
		return $out;
	}

	/**
	 * Gives back a text with HTML special chars
	 *
	 * @param	string	$text	String to convert
	 * @return	string	Converted string
	 */
	function convertToHTMLSC($text) {
		$out = htmlspecialchars($text, ENT_QUOTES, "UTF-8");
		return $out;
	}

	/**
	 * Prints the line for a single person.
	 *
	 * @param integer $pid Person ID
	 */
	function printPerson($pid, $related = TRUE) {
		global $GVE_CONFIG, $pgv_changes, $GEDCOM, $pgv_lang;

		$out = "";
		$out .= $this->convertID($pid); // Convert the ID, so linked GEDCOMs are displayed properly
		$out .= " [ ";

		if ($this->settings["diagram_type"] == "simple") {
			// Simple output
			$out .= $this->printPersonLabel($pid, $related);
		} else {
			// HTML style output
			$out .= "label=<";
			$out .= $this->printPersonLabel($pid, $related);
			$out .= ">";
		}

		$out .= "];\n";

		return $out;
	}

	/**
	 * Prints the data for a single person.
	 *
	 * @param integer $pid Person ID
	 */
	function printPersonLabel($pid, $related = TRUE) {
		global $GVE_CONFIG, $pgv_changes, $lang_short_cut, $LANGUAGE, $GEDCOM, $pgv_lang;

		$out = "";
		$editor = "";
		$bordercolor = "#606060";	// Border color of the INDI's box

		// Get the personal data
		if ($this->settings["diagram_type"] == "combined" && ( substr($pid, 0, 3) == "I_H" || substr($pid, 0, 3) == "I_W" )) {
			// In case of dummy individual
			$fillcolor = $this->getGenderColour('U', false);
			$isdead = false;
			$birthdate = "";
			$birthplace = "";
			$link = "";
			$name = " ";
		} else {
			$i = $this->getUpdatedPerson($pid);
			$fillcolor = $this->getGenderColour($i->sex(), $related);        // Background color is set to specified
			$isdead = $i->isDead();
			$link = $i->url();

			// --- Birth data ---
			if ($this->settings["show_by"]) {
				$birthdate_var = $i->getBirthDate(FALSE);
				$q1 = $birthdate_var->qual1;
				$d1 = $birthdate_var->minimumDate()->format(I18N::dateFormat());
				$dy = $birthdate_var->minimumDate()->format("%Y");
				$q2 = $birthdate_var->qual2;
				if ($birthdate_var->minimumDate() == $birthdate_var->maximumDate())
					$d2 = '';
				else
					$d2 = $birthdate_var->maximumDate()->format(I18N::dateFormat());
				$q3 = '';
				if ($this->settings["bd_type"] == "gedcom") {
					// Show full GEDCOM date
					if (is_object($birthdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$birthdate = trim("{$q1} {$d1} {$q2} {$d2} {$q3}");
					} else {
						$birthdate = $birthdate_var;
					}
				} else {
					// Show birth year only
					if (is_object($birthdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$birthdate = trim("{$q1} {$dy}");
					} else {
						$birthdate = substr($birthdate_var, -4, 4);
					}
				}
			} else {
				$birthdate = "";
			}

			if ($this->settings["show_bp"]) {
				// Show birth place
				$birthplace = $this->getFormattedPlace($i->getBirthPlace()->gedcomName());
			} else {
				$birthplace = "";
			}

			// --- Death data ---
			if ($this->settings["show_dy"]) {
				$deathdate_var = $i->getDeathDate(FALSE);
				$q1 = $deathdate_var->qual1;
				$d1 = $deathdate_var->minimumDate()->format(I18N::dateFormat());
				$dy = $deathdate_var->minimumDate()->format("%Y");
				$q2 = $deathdate_var->qual2;
				if ($deathdate_var->minimumDate() == $deathdate_var->maximumDate())
					$d2 = '';
				else
					$d2 = $deathdate_var->maximumDate()->format(I18N::dateFormat());
				$q3 = '';
				if ($this->settings["dd_type"] == "gedcom") {
					// Show full GEDCOM date
					if (is_object($deathdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$deathdate = trim("{$q1} {$d1} {$q2} {$d2} {$q3}");
					} else {
						$deathdate = $deathdate_var;
					}
				} else {
					// Show death year only
					if (is_object($deathdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$deathdate = trim("{$q1} {$dy}");
					} else {
						$deathdate = substr($deathdate_var, -4, 4);
					}
				}
			} else {
				$deathdate = "";
			}

			if ($this->settings["show_dp"]) {
				// Show death place
				$deathplace = $this->getFormattedPlace($i->getDeathPlace()->gedcomName());
			} else {
				$deathplace = "";
			}

			// --- Name ---
			$name = $i->fullName();//@@ Meliza Amity
			$addname = $i->alternateName();//@@ Meliza Amity
			if (!empty($addname)) {
				if ($this->settings["diagram_type"] == "simple")
					$name .= '\n' . $addname;//@@ Meliza Amity
				else
					$name .= '<BR />' . $addname;//@@ Meliza Amity
			}
			// Handle webtrees tags for formatting name
			$name = $this->formatName($name, $pid);
		}

		// --- Printing the INDI details ---
		if ($this->settings["diagram_type"] == "simple") {
			if ($this->settings["show_url"]) {
				// substr($_SERVER['QUERY_STRING'], 0, strrpos($_SERVER['QUERY_STRING'], '/'))
				$out .= "color=\"" . $bordercolor . "\", fillcolor=\"" . $fillcolor . "\", target=\"_blank\", href=\"" . $this->convertToHTMLSC($link) . "\" label="; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
			} else {
				$out .= "color=\"" . $bordercolor . "\", fillcolor=\"" . $fillcolor . "\", label=";
			}
			$out .= '"';
			$out .= str_replace('"','\"',$name) . '\n' . $this->settings["birth_text"] . $birthdate . " " . (empty($birthplace)?'':'('.$birthplace.')') . '\l';
			if ($isdead) {
				$out .= $this->settings["death_text"] . $deathdate . " " . (empty($deathplace)?'':'('.$deathplace.')');
			} else {
				$out .= " ";
			}
			$out .= '\l';
			if (!empty($editor)) {
				$out .= '\n' . strip_tags($editor);
			}
			$out .= '"';
		} else {
			// Convert birth & death place to get rid of characters which mess up the HTML output
			$birthplace = $this->convertToHTMLSC($birthplace);
			if ($isdead) {
				$deathplace = $this->convertToHTMLSC($deathplace);
			}

			// Draw table
			if ($this->settings["diagram_type"] == "combined") {
				$out .= "<TABLE BORDER=\"0\" CELLBORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\" BGCOLOR=\"#F0F0F0\">";
			} else {
				$out .= "<TABLE BORDER=\"1\" CELLBORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\" BGCOLOR=\"#F0F0F0\">";
			}

			// First row (photo & name)
			$out .= "<TR>";
			// Show photo
			if (($this->settings["diagram_type_combined_with_photo"]) && isset($this->individuals[$pid]["pic"]) && !empty($this->individuals[$pid]["pic"])) {
				$out .= "<TD ROWSPAN=\"2\" CELLPADDING=\"1\" PORT=\"pic\" WIDTH=\"" . ($this->font_size * 5) . "\" HEIGHT=\"" . ($this->font_size * 6) . "\" FIXEDSIZE=\"true\"><IMG SCALE=\"true\" SRC=\"" . $this->individuals[$pid]["pic"] . "\" /></TD>";
			}
			// Show name
			if ($this->settings["show_url"]) {
				$out .= "<TD CELLPADDING=\"2\" BGCOLOR=\"$fillcolor\" TARGET=\"_blank\" HREF=\"" . $this->convertToHTMLSC($link) . "\" PORT=\"nam\"><FONT POINT-SIZE=\"" . ($this->font_size + 2) ."\">" . $name . "</FONT></TD>";
			} else {
				$out .= "<TD CELLPADDING=\"2\" BGCOLOR=\"$fillcolor\" PORT=\"nam\"><FONT POINT-SIZE=\"" . ($this->font_size + 2) ."\">" . $name . "</FONT></TD>";
			}
			$out .= "</TR>";

			// Second row (birth & death data)
			$out .= "<TR>";
			$out .= "<TD ALIGN=\"LEFT\" BALIGN=\"LEFT\" PORT=\"dat\">" . $this->settings["birth_text"] . " $birthdate " . (empty($birthplace)?"":"($birthplace)");
			$out .= "<BR />";
			if ($isdead) {
				$out .= $this->settings["death_text"] . " $deathdate " . (empty($deathplace)?"":"($deathplace)");
			} else {
				$out .= " ";
			}
			if (!empty($editor)) {
				$out .= "<BR/>" . $editor;
			}

			$out .= "</TD>";
			$out .= "</TR>";

			// Close table
			$out .= "</TABLE>";
		}

		return $out;
	}

	/**
	 * Prints the line for drawing a box for a family.
	 *
	 * @param integer $fid Family ID
	 */
	function printFamily($fid) {
		global $GVE_CONFIG, $pgv_changes, $lang_short_cut, $LANGUAGE, $GEDCOM, $pgv_lang;

		$out = "";

		$out .= $this->convertID($fid);
		$out .= " [ ";

		// Showing the ID of the family, if set
		if ($this->settings["show_fid"]) {
			$family = " (" . $fid . ")";
		} else {
			$family = "";
		}

		// --- Data collection ---
		// If a "dummy" family is set (begins with "F_"), then there is no marriage & family data, so no need for querying PGV...
		if (substr($fid, 0, 2) == "F_") {
			$fillcolor = $this->getFamilyColour();
			$marriageyear = "";
			$marriageplace = "";
			$husb_id = $this->families[$fid]["husb_id"];
			$wife_id = $this->families[$fid]["wife_id"];
			if (!empty($this->families[$fid]["unkn_id"])) {
				$unkn_id = $this->families[$fid]["unkn_id"];
			}
			$link = "#";
		// Querying PGV for the data of a FAM object
		} else {
			$f = $this->getUpdatedFamily($fid);
			$fillcolor = $this->getFamilyColour();
			$link = $f->url();

			// Show marriage year
			if ($this->settings["show_my"]) {
				$marrdate_var = $f->getMarriageDate(FALSE);
				$q1=$marrdate_var->qual1;
				$d1=$marrdate_var->minimumDate()->format(I18N::dateFormat());
				$dy=$marrdate_var->minimumDate()->format("%Y");
				$q2=$marrdate_var->qual2;
				if ($marrdate_var->minimumDate() == $marrdate_var->maximumDate())
					$d2='';
				else
					$d2=$marrdate_var->maximumDate()->format(I18N::dateFormat());
				$q3='';
				if ($this->settings["md_type"] == "gedcom") {
				// Show full GEDCOM date
					if (is_object($marrdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$marriagedate = trim("{$q1} {$d1} {$q2} {$d2} {$q3}");
					} else {
						$marriagedate = $marrdate_var;
					}
				} else {
					// Show birth year only
					if (is_object($marrdate_var)) {
						// Workaround for PGV 4.1.5 SVN, it gives back an object not a string
						$marriagedate = trim("{$q1} {$dy}");
					} else {
						$marriagedate = substr($marrdate_var, -4, 4);
					}
				}
			} else {
				$marriagedate = "";
			}

			// Show marriage place
			if ($this->settings["show_mp"] && !empty($f->getMarriage()) && !empty($f->getMarriagePlace())) {
				$marriageplace = $this->getFormattedPlace($f->getMarriagePlace()->gedcomName());
			} else {
				$marriageplace = "";
			}

			// Get the husband's and wife's id from PGV
			//$husb_id = $f->getHusbId();
			//$wife_id = $f->wifeId();
			if (isset($this->families[$fid]["husb_id"])) {
				$husb_id = $this->families[$fid]["husb_id"];
			} else {
				$husb_id = "";
			}
			if (isset($this->families[$fid]["wife_id"])) {
				$wife_id = $this->families[$fid]["wife_id"];
			} else {
				$wife_id = "";
			}
		}


		// --- Printing ---
		// "Combined" type
		if ($this->settings["diagram_type"] == "combined") {
			$out .= "label=<";

			// --- Print table ---
			$out .= "<TABLE BORDER=\"0\" CELLBORDER=\"1\" CELLPADDING=\"2\" CELLSPACING=\"0\">";

			// --- Print couple ---
			$out .= "<TR>";

			if (!empty($unkn_id)) {
				// Print unknown gender INDI
				if (isset($this->individuals[$unkn_id]['rel']) && ($this->individuals[$unkn_id]['rel'] == FALSE)) {
					$related = FALSE;
				} else {
					$related = TRUE;
				}
				$out .= "<TD CELLPADDING=\"0\" PORT=\"" . $unkn_id . "\">";
				$out .= $this->printPersonLabel($unkn_id, $related);
				$out .= "</TD>";
			} else {
				// Print husband
				//$husb_id = $f->getHusbId();
				if (!empty($husb_id)) {
					if (isset($this->individuals[$husb_id]['rel']) && ($this->individuals[$husb_id]['rel'] == FALSE)) {
						$related = FALSE;
					} else {
						$related = TRUE;
					}
					$out .= "<TD CELLPADDING=\"0\" PORT=\"" . $husb_id . "\">";
					$out .= $this->printPersonLabel($husb_id, $related);
					$out .= "</TD>";
				}

				// Print wife
				//$wife_id = $f->wifeId();
				if (!empty($wife_id)) {
					if (isset($this->individuals[$wife_id]['rel']) && ($this->individuals[$wife_id]['rel'] == FALSE)) {
						$related = FALSE;
					} else {
						$related = TRUE;
					}
					$out .= "<TD CELLPADDING=\"0\" PORT=\"" . $wife_id . "\">";
					$out .= $this->printPersonLabel($wife_id, $related);
					$out .= "</TD>";
				}
			}

			$out .= "</TR>";

			// --- Print marriage ---
			if (substr($fid, 0, 2) == "F_") {
				// If it is a dummy FAM, then do nothing
			} else {
				$out .= "<TR>";
				if ($this->settings["show_url"]) {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" PORT=\"marr\" TARGET=\"_BLANK\" HREF=\"" . $this->convertToHTMLSC($link) . "\" BGCOLOR=\"" . $fillcolor . "\">"; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
				} else {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" PORT=\"marr\" BGCOLOR=\"" . $fillcolor . "\">";
				}

				$out .= (empty($marriagedate)?".":$marriagedate) . "<BR />" . (empty($marriageplace)?"":"(".$marriageplace.")") . $family;
				$out .= "</TD>";
				$out .= "</TR>";
			}

			$out .= "</TABLE>";

			$out .= ">";
		} else {
		// Non-combined type
			if ($this->settings["show_url"]) {
				$out .= "color=\"#606060\",fillcolor=\"" . $fillcolor . "\", href=\"" . $this->convertToHTMLSC($link) . "\", target=\"_blank\", shape=ellipse, style=filled"; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
			} else {
				$out .= "color=\"#606060\",fillcolor=\"" . $fillcolor . "\", shape=ellipse, style=filled";
			}
			$out .= ", label=" . '"' . (empty($marriagedate)?'':$marriagedate) . '\n' . (empty($marriageplace)?'':'('.$marriageplace.')') . $family . '"';
		}

		$out .= "];\n";

		return $out;
	}

	/**
	 * Adds an individual to the indi list
	 *
	 * @param string $pid
	 * @param boolean $ance
	 * @param boolean $desc
	 * @param boolean $spou
	 * @param boolean $sibl
	 * @param boolean $rel
	 */
	function addIndiToList($sourcePID, $pid, $ance = FALSE, $desc = FALSE, $spou = FALSE, $sibl = FALSE, $rel = TRUE, $ind = 0, $level = 0) {
		global $GVE_CONFIG, $pgv_changes, $GEDCOM;
		$ance_level = $this->indi_search_method["ance"] ? $this->settings["ance_level"] : 0;
		$desc_level = $this->indi_search_method["desc"] ? $this->settings["desc_level"] : 0;

		$this->individuals[$pid]['pid'] = $pid;

		// Overwrite the 'related' status if it was not set before or it's 'false' (for those people who are added as both related and non-related)
		if (!isset($this->individuals[$pid]['rel']) || ($this->individuals[$pid]['rel'] == FALSE && $rel)) {
			$this->individuals[$pid]['rel'] = $rel;
		} else {
			return false;
		}

		// --- DEBUG ---
		if ($this->settings["debug"]) {
			$individual = $this->getUpdatedPerson($pid);
			$this->printDebug("Name: ".strip_tags($individual->fullName()), $ind);
			$this->printDebug("Source PID: ".$sourcePID, $ind);
			$this->printDebug("--- #$pid# ---\n", $ind);
			$this->printDebug("{\n", $ind);
			$ind++;
			$this->printDebug("($pid) - INDI added to list\n", $ind);
			$this->printDebug("($pid) - ANCE: $ance, DESC: $desc, SPOU: $spou, SIBL: $sibl, REL: $rel, IND: $ind, LEV: $level\n", $ind);
		}
		// -------------
		// Add photo
		if ($this->settings["diagram_type_combined_with_photo"] && $this->isPhotoRequired()) {
			$this->individuals[$pid]["pic"] = $this->addPhotoToIndi($pid);
		}

		// Get updated INDI data
		$i = $this->getUpdatedPerson($pid);

		// Add the family nr which he/she belongs to as spouse (needed when "combined" mode is used)
		if ($this->settings["diagram_type"] == "combined") {
			$fams = $i->spouseFamilies();
			if (count($fams) > 0) {

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - /COMBINED MODE/ adding FAMs where INDI is marked as spouse:\n", $ind);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$this->individuals[$pid]["fams"][$fid] = $fid;

					//$this->families[$fid]["husb_id"] = $fam->getHusbId();
					//$this->families[$fid]["wife_id"] = $fam->wifeId();

					if (isset($this->families[$fid]["fid"]) && ($this->families[$fid]["fid"] == $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}

					if ($fam->husband() && $fam->husband()->xref() == $pid) {
						$this->families[$fid]["husb_id"] = $pid;
					} else {
						$this->families[$fid]["wife_id"] = $pid;
					}

					if ($desc) {
						$this->families[$fid]["has_parents"] = TRUE;
					}
					//var_dump($this->families[$fid]);

					//$this->addFamToList($fid);
				}
			} else {
				// If there is no spouse family we create a dummy one
				$this->individuals[$pid]["fams"]["F_$pid"] = "F_$pid";
				$this->addFamToList("F_$pid");

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - /COMBINED MODE/ adding dummy FAM (F_$pid), because this INDI does not belong to any family as spouse\n", $ind);
				}
				// -------------

				$this->families["F_$pid"]["has_parents"] = TRUE;
				if ($i->sex() == "M") {
					$this->families["F_$pid"]["husb_id"] = $pid;
					$this->families["F_$pid"]["wife_id"] = "";
				} elseif ($i->sex() == "F") {
				 	$this->families["F_$pid"]["wife_id"] = $pid;
				 	$this->families["F_$pid"]["husb_id"] = "";
				} else {
					// Unknown gender
					$this->families["F_$pid"]["unkn_id"] = $pid;
					$this->families["F_$pid"]["wife_id"] = "";
				 	$this->families["F_$pid"]["husb_id"] = "";
				}
			}
		}



		// Check that INDI is listed in stop pids (should we stop the tree processing or not?)
		$stop_proc = FALSE;
		if (isset($this->settings["stop_proc"]) && $this->settings["stop_proc"] == TRUE) {
			$stop_pids = explode(",", $this->settings["stop_pids"]);
			for ($j=0;$j<count($stop_pids);$j++) {
				if ($pid == trim($stop_pids[$j])){
					// --- DEBUG ---
					if ($this->settings["debug"]) {
						$this->printDebug("($pid) -- STOP processing, because INDI is listed in the \"Stop tree processing on INDIs\"\n", $ind);
					}
					// -------------
					$stop_proc = TRUE;
				}
			}
		}

		if (!$stop_proc)
		{

			// Add ancestors (parents)
			if ($ance && $level < $ance_level) {
				// Get the list of families where the INDI is listed as CHILD
				$famc = $i->childFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding ANCESTORS (LEVEL: $level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a child (to find his/her parents):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				if (count($famc) > 0) {
					// For every family where the INDI is listed as CHILD
					foreach ($famc as $fam) {
						// Get the family ID
						$fid = $fam->xref();
						// Get the family object
						$f = $this->getUpdatedFamily($fid);

						if (isset($this->families[$fid]["fid"]) && ($this->families[$fid]["fid"]== $fid)) {
							// Family ID already added
							// do nothing
							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
								//var_dump($fams);
							}
							// -------------
						} else {
							$this->addFamToList($fid);

							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
								//var_dump($fams);
							}
							// -------------
						}

						// Work out if indi has adoptive relationship to this family
						$adopfamcadoptype = $this->checkIndiAdopted($i, $fam);

						// Add father & mother
						$h = $f->husband();
						$w = $f->wife();
						if($h)
							$husb_id = $h->xref();
						else
							$husb_id = null;
						if($w)
							$wife_id = $w->xref();
						else
							$wife_id = null;

						if (!empty($husb_id)) {
							$this->families[$fid]["has_children"] = TRUE;
							$this->families[$fid]["husb_id"] = $husb_id;

							if ($adopfamcadoptype == "BOTH" || $adopfamcadoptype == "HUSB") {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /FATHER/ with INDI id ($husb_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 1", $husb_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], FALSE, $ind, $level+1);
							} else {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding a PARENT /FATHER/ with INDI id ($husb_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 2", $husb_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], $rel, $ind, $level+1);
							}
						}
						if (!empty($wife_id)) {
							$this->families[$fid]["has_children"] = TRUE;
							$this->families[$fid]["wife_id"] = $wife_id;

							if ($adopfamcadoptype == "BOTH" || $adopfamcadoptype == "WIFE") {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /MOTHER/ with INDI id ($wife_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 3", $wife_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], FALSE, $ind, $level+1);
							} else {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding a PARENT /MOTHER/ with INDI id ($wife_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 4", $wife_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], $rel, $ind, $level+1);
							}
						}

						if ($this->settings["diagram_type"] == "combined") {
							// This person's spouse family HAS parents
							foreach ($this->individuals[$pid]["fams"] as $s_fid=>$s_fam) {
								$this->families[$s_fid]["has_parents"] = TRUE;
							}
						}

					}
				} else {
					if ($this->settings["diagram_type"] == "combined") {
						// This person's spouse family HAS NO parents
						foreach ($this->individuals[$pid]["fams"] as $s_fid=>$s_fam) {
							if (!isset($this->families[$s_fid]["has_parents"])) {
								$this->families[$s_fid]["has_parents"] = FALSE;
							}
						}
					}
				}
				// Decrease the max ancestors level
			}

			// Add descendants (children)
			if ($desc && $level > -1*$desc_level) {
				$fams = $i->spouseFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding DESCENDANTS (LEVEL: $level, DESC_LEVEL: $desc_level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a spouse (to find his/her children):\n", $ind);

					//var_dump($fams);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$this->families[$fid]["has_children"] = FALSE;
					$f = $this->getUpdatedFamily($fid);

                    $h = $f->husband();
                    if($h){
                        if($h->xref() == $pid){
                            $this->families[$fid]["husb_id"] = $pid;
                        } else {
                            $this->families[$fid]["wife_id"] = $pid;
                        }
                    }
                    else {
                        $w = $f->wife();
                        if($w){
                            if($w->xref() == $pid){
                                $this->families[$fid]["wife_id"] = $pid;
                            } else {
                                $this->families[$fid]["husb_id"] = $pid;
                            }
                        }
                    }

					if (isset($this->families[$fid]["fid"]) && ($this->families[$fid]["fid"]== $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}
					$this->families[$fid]["has_children"] = TRUE;

					$children = $f->children();
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id)) {

							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- adding a CHILD with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// Work out if indi has adoptive relationship to this family
							$adopfamcadoptype = $this->checkIndiAdopted($child, $f);
							if ($adopfamcadoptype != "") {
								$related = false;
							} else {
								$related = $rel;
							}

							if ($this->indi_search_method["any"]) {
								$this->addIndiToList($pid."|Code 14", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, FALSE, $ind, $level-1);
							}
							$this->addIndiToList($pid."|Code 5", $child_id, FALSE, TRUE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level-1);

						}
					}
				}
			}

			// Add spouses
			if (($spou && !$desc) || ($spou && $desc && $level > -1*$desc_level) || ($spou && $this->settings["diagram_type"] == "combined")) {
				$fams = $i->spouseFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding SPOUSES\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a spouse (to find his/her spouse(s)):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$f = $this->getUpdatedFamily($fid);

					if (isset($this->families[$fid]["fid"]) && ($this->families[$fid]["fid"]== $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}

					//$spouse_id = $f->getSpouseId($pid);
					// Alternative method of getting the $spouse_id - workaround by Till Schulte-Coerne
                    // And the coerced into webtrees by Iain MacDonald
                    $h = $f->husband();
					if ($h) {
                        if($h->xref() == $pid) {
                            $w = $f->wife();
                            if($w) {
                                $spouse_id = $w->xref();
                                $this->families[$fid]["husb_id"] = $pid;
                                $this->families[$fid]["wife_id"] = $spouse_id;
                            }
                        }
                        else {
                            $w = $f->wife();
                            if($w && $w->xref() == $pid) {
                                $spouse_id = $h->xref();
                                $this->families[$fid]["husb_id"] = $spouse_id;
                                $this->families[$fid]["wife_id"] = $pid;
                            }
                        }
                    }

					if (!empty($spouse_id)) {
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- adding SPOUSE with INDI id ($spouse_id) from FAM ($fid):\n", $ind);
							//var_dump($fams);
						}
						// -------------

						if ($this->settings["mark_not_related"] == TRUE) {
							$this->addIndiToList($pid."|Code 6", $spouse_id, $this->indi_search_method["any"] && $ance, $this->indi_search_method["any"] && $desc, $this->indi_search_method["any"], $this->indi_search_method["any"], FALSE, $ind, $level);
						} else {
							$this->addIndiToList($pid."|Code 7", $spouse_id, $this->indi_search_method["any"], $this->indi_search_method["any"], $this->indi_search_method["any"], $this->indi_search_method["any"], TRUE, $ind, $level);
						}
					}

				}
			}

			// Add siblings
			if ($sibl && $level < $ance_level) {
				$famc = $i->childFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding SIBLINGS (LEVEL: $level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a child (to find his/her siblings):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				foreach ($famc as $fam) {
					$fid = $fam->xref();
					$f = $this->getUpdatedFamily($fid);

					if (isset($this->families[$fid]["fid"]) && ($this->families[$fid]["fid"]== $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}

					$children = $f->children();
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id) && ($child_id != $pid)) {
							$this->families[$fid]["has_children"] = TRUE;
							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- adding a SIBLING with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// Work out if indi has adoptive relationship to this family
							$adopfamcadoptype = $this->checkIndiAdopted($i, $fam);
							if ($adopfamcadoptype != "") {
								$related = false;
							} else {
								$related = $rel;
							}

							// If searching for cousins, then the descendants of ancestors' siblings should be added
							if ($this->indi_search_method["cous"]) {
								$this->addIndiToList($pid."|Code 8", $child_id, TRUE, TRUE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level);
							} else {
								$this->addIndiToList($pid."|Code 9", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level);
							}

						}
					}
				}
			}

			// Add step-siblings
			if ($sibl && $level < $ance_level) {
				$fams = $i->childStepFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding STEP-SIBLINGS (LEVEL: $level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI's parents are listed as spouses (to find his/her step-siblings):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$f = $this->getUpdatedFamily($fid);
					$this->addFamToList($fid);

					// --- DEBUG ---
					if ($this->settings["debug"]) {
						$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
						//var_dump($fams);
					}
					// -------------


					$children = $f->children();
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id) && ($child_id != $pid)) {
							$this->families[$fid]["has_children"] = TRUE;
							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- adding a STEP-SIBLING with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// If searching for step-cousins, then the descendants of ancestors' siblings should be added
							if ($this->indi_search_method["cous"]) {
								$this->addIndiToList($pid."|Code 10", $child_id, FALSE, TRUE, $this->indi_search_method["spou"], FALSE, $rel, $ind, $level);
							} else {
								$this->addIndiToList($pid."|Code 11", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, $rel, $ind, $level);
							}
						}
					}
				}
			}
		}


		// --- DEBUG ---
		if ($this->settings["debug"]) {
			$ind--;
			$this->printDebug("}\n", $ind);
		}
		// -------------

	}

	/**
	 * Adds a family to the family list
	 *
	 */
	function addFamToList($fid) {
        if($fid instanceof Family)
            $fid = $fid->xref();
        if(!isset($this->families[$fid]))
            $this->families[$fid] = array();
		$this->families[$fid]["fid"] = $fid;
		//$this->families[$fid]["has_children"] = FALSE;
		//$this->families[$fid]["has_parents"] = FALSE;
	}

	/**
	 * Adds a path to the photo of a given individual
 	 *
	 * @param string $pid Individual's GEDCOM id (Ixxx)
	 */
	function addPhotoToIndi($pid) {
		$i = Registry::individualFactory()->make($pid, $this->tree);
		$m = $i->findHighlightedMediaFile();
		if (empty($m)) {
			return null;
		}
		else if (false && $this->use_urls_for_media) {
			return $m->downloadUrl('inline');
		}
		else if (!$m->isExternal() && $m->fileExists($this->file_system)) {
			// If we are rendering in the browser, provide the URL, otherwise provide the server side file location
			if (isset($_REQUEST["render"])) {
				return Site::getPreference('INDEX_DIRECTORY').$this->tree->getPreference('MEDIA_DIRECTORY').$m->filename();
			} else {
				return str_replace("&","%26",$m->imageUrl(200,200,"contain"));
			}
		} else {
			return null;
		}
	}

	function getUpdatedFamily($fid) {
		return Registry::familyFactory()->make($fid, $this->tree);
	}

	function getUpdatedPerson($pid) {
		return Registry::individualFactory()->make($pid, $this->tree);
	}

	function printDebug($txt, $ind = 0) {
		print(str_repeat("\t", $ind) . $txt);
	}

	// Linked IDs has a colon, it needs to be replaced
	function convertID($id) {
		return preg_replace("/\:/", "_", $id);
	}
}
?>
