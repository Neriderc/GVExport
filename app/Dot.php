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

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\I18n;
//use League\Flysystem\Util;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Registry;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Main class for managing the DOT file
 *
 */
class Dot {
	var array $individuals = array();
	var array $skipList = array();
	var array $families = array();
	var array $indi_search_method = array("ance" => FALSE, "desc" => FALSE, "spou" => FALSE, "sibl" => FALSE, "cous" => FALSE, "any" => FALSE);
	var string $font_size;
    var string $font_size_name;
	var array $colors = array();
	var array $settings = array();
	var array $pagesize = array();
    var array $messages = array(); // messages for toast system
	private const ERROR_CHAR = "E:"; // Messages that start with this will be highlighted
    private $tree, $file_system;

    /**
	 * Constructor of Dot class
	 */
	function __construct($tree, $file_system) {
		global $GVE_CONFIG;
		$this->tree = $tree;
		$this->file_system = $file_system;
    // Load settings from config file

		// Load font
		$this->font_size = $GVE_CONFIG["dot"]["fontsize"];
        $this->font_size_name = $GVE_CONFIG["dot"]["fontsize_name"];
		$this->settings["defaulttypeface"] = $GVE_CONFIG["default_typeface"];
		$this->settings["typeface"] = $this->settings["defaulttypeface"];
        $this->settings["typefaces"] = $GVE_CONFIG["settings"]["typefaces"];
        $this->settings["typeface_fallback"] = $GVE_CONFIG["settings"]["typeface_fallback"];

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
		$this->colors["colorbg"] = $GVE_CONFIG["dot"]["colorbg"];
		$this->colors["colorindibg"] = $GVE_CONFIG["dot"]["colorindibg"];
		$this->colors["colorstartbg"] = $GVE_CONFIG["dot"]["colorstartbg"];
		$this->colors["colorborder"] = $GVE_CONFIG["dot"]["colorborder"];
		$this->colors["font_color"]["name"] = $GVE_CONFIG["dot"]["fontcolor_name"];
        $this->colors["font_color"]["details"] = $GVE_CONFIG["dot"]["fontcolor_details"];
        $this->colors["arrows"]["default"] = $GVE_CONFIG["dot"]["arrow_default"];
        $this->colors["arrows"]["related"] = $GVE_CONFIG["dot"]["arrow_related"];
        $this->colors["arrows"]["not_related"] = $GVE_CONFIG["dot"]["arrow_not_related"];

		// Default settings
        $this->settings["color_arrow_related"] = $GVE_CONFIG["settings"]["color_arrow_related"];
        $this->settings["startcol"] = $GVE_CONFIG["settings"]["startcol"];
        $this->settings["diagram_type"] = "simple";
		$this->settings["diagram_type_combined_with_photo"] = true;
		$this->settings["indi"] = "";
		$this->settings["use_pagesize"] = "";
		$this->settings["page_margin"] = $GVE_CONFIG["default_margin"];
		$this->settings["show_lt_editor"] = FALSE;
		$this->settings["mark_not_related"] = FALSE;
		$this->settings["fast_not_related"] = FALSE;
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
        $this->settings["use_abbr_name"] = $GVE_CONFIG["settings"]["use_abbr_name"];
		$this->settings["use_abbr_names"] = $GVE_CONFIG["settings"]["use_abbr_names"];

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

		$this->settings["adv_people"] = $GVE_CONFIG["settings"]["adv_people"];
		$this->settings["adv_appear"] = $GVE_CONFIG["settings"]["adv_appear"];
		$this->settings["auto_update"] = $GVE_CONFIG["settings"]["auto_update"];
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
	function setSettings(string $setting, $value) {
		$this->settings[$setting] = $value;
	}

	/**
	 * Function to set gender and family colors
	 *
	 * @param string $color_type
	 * @param string $color
	 */
	function setColor(string $color_type, string $color) {
		$this->colors[$color_type] = $color;
	}

	/**
	 * Function to set font size
	 *
	 * @param string $font_size
	 * @param string $type
	 */
	function setFontSize(string $font_size, string $type) {
        if ($type == 'name') {
            $this->font_size_name = $font_size;
        } else {
            $this->font_size = $font_size;
        }
	}

    function setArrowColour(string $type, string $value)
    {
        $this->colors["arrows"][$type] = $value;
    }

    function setColourArrowRelated(string $value)
    {
        $this->settings["color_arrow_related"] = $value;
    }

    /**
	 * Function to set font colour for name
	 *
	 * @param string $font_color
	 */
	function setFontColorName(string $font_color) {
		$this->colors["font_color"]["name"] = $font_color;
	}

    /**
	 * Function to set font colour for details (date of birth, place of marriage, etc.)
	 *
	 * @param string $font_color
	 */
	function setFontColorDetails(string $font_color) {
        $this->colors["font_color"]["details"] = $font_color;
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
	function setIndiSearchMethod(string $method) {
		$this->indi_search_method[$method] = TRUE;
	}

	function getDOTDump(): string
    {
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
	 * @param array $nameArray webtrees name array for the person
	 * @param string $pid XREF of the person, for adding to name if enabled
	 * @return string Returns formatted name
	 */
	function getFormattedName(array $nameArray, string $pid): string {
        if (isset($nameArray['full'])) {
            $name = $this->getAbbreviatedName($nameArray);
        } else {
            $name = $nameArray[0];
        }

        // Tidy webtrees terms for missing names
        $name = str_replace(array("@N.N.", "@P.N."), "...", $name);
		// Show nickname in quotes
		$name = str_replace(array('<q class="wt-nickname">', '</q>'), array('"', '"'), $name);
        if ($this->settings["diagram_type"] != "simple") {
            // Show preferred name as underlined by replacing span with underline tags
            $pos_start = strpos($name, '<span class="starredname">');
            while ($pos_start != false) {
                // Start by replacing the </span>
                $pos_end = strpos(substr($name, $pos_start), "</span>") + $pos_start;
                if ($pos_end) {
                    $name = substr_replace($name, "_/U_", $pos_end, strlen("</span>"));
                }

                // Next do the starting tags
                $pos_start = strpos($name, '<span class="starredname">');
                if ($pos_start !== false) {
                    $name = substr_replace($name, "_U_", $pos_start, strlen('<span class="starredname">'));
                }
                $pos_start = strpos($name, '<span class="starredname">');
            }
        }
        $name = strip_tags($name);

        // We use _ instead of < >, remove tags, then switch them to proper tags. This lets
        // us control the tags included in an environment where we don't normally have control
        $name = str_replace("_U__/U_", "", $name); // remove blank tags
        $name = str_replace("_U_", "<u>", $name);
        $name = str_replace("_/U_", "</u> ", $name);

		// If PID already in name (from another module), remove it, so we don't add twice
		$name = str_replace(" (" . $pid . ")", "", $name);
		if ($this->settings["show_pid"]) {
			// Show INDI id
			$name = $name . " (" . $pid . ")";
		}
		return $name;
	}

    function getAbbreviatedName($nameArray)
    {
        switch ($this->settings["use_abbr_name"]) {
            case 0: /* Full name */
                return $nameArray["full"];
            case 10: /* Given and Surnames */
                return $nameArray["givn"] . " " . $nameArray["surn"];
            case 20: /* Given names */
                return $nameArray["givn"];
            case 30: /* First given name only */
                return explode(" ", $nameArray["givn"])[0];
            case 40: /* Surname(s) */
                return $nameArray["surn"];
            case 50: /* Initials only */
                // Split by space or hyphen to get different names
                $givenParts = preg_split('/[\s-]/', $nameArray["givn"]);
                $initials = substr($givenParts[0],0,1);
                if (isset($givenParts[1])) {
                    $initials .= substr($givenParts[1],0,1);
                }
                $surnameParts = preg_split('/[\s-]/', $nameArray["surn"]);
                $initials .= substr($surnameParts[0],0,1);
                if (isset($surnameParts[1])) {
                    // If there is a hyphen in the surname found before the first space
                    $spacePos = strpos($nameArray["surn"], " ");
                    if (strpos(substr($nameArray["surn"], 0, $spacePos ?: strlen($nameArray["surn"])), "-")) {
                        $initials .= "-";
                    }
                    $initials .= substr($surnameParts[1],0,1);
                }
                return $initials;
            case 60: /* Given name initials and Surname */
                // Split by space or hyphen to get different names
                $givenParts = preg_split('/[\s-]/', $nameArray["givn"]);
                $initials = substr($givenParts[0],0,1) . ".";
                if (isset($givenParts[1])) {
                    $initials .= substr($givenParts[1],0,1) . ".";
                }
                return $initials . " " . $nameArray["surn"];
            case 70: /* Don't show names */
                return " ";
            default:
                return $nameArray["full"];

        }
    }
	/** Checks if provided individual is related by
	 * adoption or foster to the provided family record
	 * @param Individual $i webtrees individual object for the person to check
	 * @param Family $f webtrees family object for the family to check against
	 * @param integer $ind the indent level for printing the debug log
	 * @return string
	 */
	function getRelationshipType($i, $f, int $ind = 0): string
	{
		$fid = $f->xref();
		$facts = $i->facts();
		$famFound = FALSE;
		// Find out if individual has adoption record
		foreach ($facts as $fact) {
			$gedcom = strtoupper($fact->gedcom());
			// If adoption record found, check for family link
			if (substr_count($gedcom, "1 ADOP") > 0) {
				$GEDLines = preg_split("/\n/", $gedcom);
				foreach ($GEDLines as $line) {
					if (substr_count($line, "2 FAMC") > 0) {
						$GEDFamID = explode("@", $line)[1];

						// Check if link is to the family we are looking for
						if ($GEDFamID == $fid) {
							$famFound = TRUE;
							// ---DEBUG---
							if ($this->settings["debug"]) {
									$this->printDebug("(".$i->xref().") -- ADOP record: " . preg_replace("/\n/", " | ", $gedcom) . "\n", $ind);
							}
							// -----------
						}
					}

					if ($famFound && substr_count($line, "3 ADOP") > 0) {
						$adopfamcadoptype = explode(" ", $line)[2];
						break;
					}
				}
			}

			// Find other non-blood relationships between records
			if (substr_count($gedcom, "2 PEDI") > 0 && substr_count($gedcom, "2 PEDI BIRTH") == 0) {
				$GEDLines = preg_split("/\n/", $gedcom);
				foreach ($GEDLines as $line) {
					if (substr_count($line, "1 FAMC") > 0) {
						$GEDFamID = explode("@", $line)[1];

						// Adopter family found
						if ($GEDFamID == $fid) {
							$adopfamcadoptype = "OTHER";
							break;
						}
					}
				}
			}
		}
		// If we found no record of non-blood relationship, return blank
		// Otherwise return the type (BOTH/HUSB/WIFE for adoptions, "OTHER" for anything else)
		if (!isset($adopfamcadoptype)) {
			return "";
		}

		// --- DEBUG ---
		if ($this->settings["debug"]) {
			$this->printDebug("-- Link between individual ".$i->xref()." and family ".$fid." is ".($adopfamcadoptype=="" ? "blood" : $adopfamcadoptype).".\n", $ind);
		}
		// -------------

		return $adopfamcadoptype;
	}

	function createIndiList (&$individuals, &$families, $full) {
        $indis = explode(",", $this->settings["indi"]);
        $indiLists = array();
        for ($i=0;$i<count($indis);$i++) {
            $indiLists[$i] = array();
            if (trim($indis[$i]) !== "") {
                $this->addIndiToList("Start | Code 16", trim($indis[$i]), $this->indi_search_method["ance"], $this->indi_search_method["desc"], $this->indi_search_method["spou"], $this->indi_search_method["sibl"], TRUE, 0, 0, $indiLists[$i], $families, $full);
            }
        }
        // Merge multiple lists from the different starting persons into one list
        // Taking extra care to ensure if one list marks a person as related
        // they should be marked as related in the final tree
        $individuals = $indiLists[0];
        for($i=1;$i<count($indiLists);$i++) {
            $indiList = $indiLists[$i];
            foreach ($indiList as $key => $value) {
                if (isset($individuals[$key])) {
                    if (!$individuals[$key]["rel"] && $value["rel"]) {
                        $individuals[$key]["rel"] = true;
                    }
                } else {
                    $individuals[$key] = $value;
                }
            }
        }

		// -- DEBUG ---
		if ($this->settings["debug"]) {
			$this->printDebug("Finished individuals list: ".print_r($individuals));
		}
		// -------------
	}

	/**
	 * This function updates our family and individual arrays to remove records that mess up
	 * the "combined" mode. This is particularly important when including a "stop" individual,
	 * as this can cause half a family record to be shown. So we attempt to remove these.
	 *
	 * @param array $individuals // List of individual records
	 * @param array $families // List of family records
	 * @return void
	 */
	function removeGhosts(array &$individuals, array &$families) {
		foreach ($individuals as $i) {
			foreach ($i["fams"] as $f) {
                if (isset($f["fid"])) {
                    $xref = $f["fid"];
                    // If not dummy family, the family has no children, and one of the spouse records are missing
                    if (substr($xref, 0, 2) != "F_" && (!isset($families[$xref]["has_children"]) || !$families[$xref]["has_children"]) && (!isset($families[$xref]["husb_id"]) || !isset($families[$xref]["wife_id"]))) {
                        // Remove this family from both the individual record of families and from the family list
                        unset($families[$xref]);
                        unset($individuals[$i["pid"]]["fams"][$xref]);
                    }
                }
			}
		}
	}

	function createDOTDump(): string
    {
		// If no individuals in the clippings cart (or option chosen to override), use standard method
		if (!functionsClippingsCart::isIndividualInCart($this->tree) || !$this->settings["usecart"] ) {
			// Create our tree
			$this->createIndiList($this->individuals, $this->families, false);
			if ($this->settings["diagram_type"] == "combined") {
                if ($this->indi_search_method["spou"] != "") {
                    $this->removeGhosts($this->individuals, $this->families);
                }
			} else {
                // Remove families with only one link
                foreach ($this->families as $f) {
                    $xref = $f["fid"];
                    // If not dummy family, the family has no children, and one of the spouse records are missing
                    if (substr($xref, 0, 2) != "F_" && (!isset($this->families[$xref]["has_children"]) || !$this->families[$xref]["has_children"]) && (!isset($this->families[$xref]["husb_id"]) || !isset($this->families[$xref]["wife_id"]))) {
                        // Remove this family from the family list
                        unset($this->families[$xref]);
                    }
                }
            }

			// If option to display related in another colour is selected,
			// check if any non-related persons in tree
			$relList = array();
			$relFams = array();
			$NonrelativeExists = FALSE;
			if ($this->settings["mark_not_related"] && !$this->settings["fast_not_related"]) {
				foreach ($this->individuals as $indi) {
					if (!$indi['rel']) {
						$NonrelativeExists = TRUE;
						break;
					}
				}
				// If there are non-related persons, generate a full relative tree starting from
				// the initial persons, to ensure no relation links exist outside displayed records
				if ($NonrelativeExists) {
					// Save and change some settings before generating full tree
					$save = $this->indi_search_method;
					$this->indi_search_method = array("ance" => TRUE, "desc" => TRUE, "spou" => FALSE, "sibl" => TRUE, "cous" => TRUE, "any" => FALSE);
					// Generate full tree of relatives
					$this->createIndiList($relList, $relFams, true);
					// Restore settings
					$this->indi_search_method = $save;
					// Update our relative statuses on the main tree
					foreach ($this->individuals as $indi) {
						$pid = $indi['pid'];
						// If needed, overwrite relation status
						if (isset($relList[$pid])) {
							$this->individuals[$pid]['rel'] = $relList[$pid]['rel'];
						}
					}
				}
			}
		} else {
		// If individuals in clipping cart and option chosen to use them, then proceed
			$functionsCC = new functionsClippingsCart($this->tree, $this->isPhotoRequired(), ($this->settings["diagram_type"] == "combined"), $this->settings["dpi"]);
			$this->individuals = $functionsCC->getIndividuals();
			$this->families = $functionsCC->getFamilies();
		}

        $out = $this->printDOTHeader();

		// ### Print the individuals list ###
		if ($this->settings["diagram_type"] != "combined") {
			foreach ($this->individuals as $pid) {
				$out .= $this->printPerson($pid['pid'], $pid['rel']);
			}
		}

		// ### Print the families list ###
		// If no_fams option is not checked then we print the families
		if (!$this->settings["no_fams"]) {
			foreach ($this->families as $fid=>$fam_data) {
                if ($this->settings["diagram_type"] == "combined") {
                    $nodeName = $this->generateFamilyNodeName($fid);
                    // We do not show those families which has no parents and children in case of "combined" view;
                    if ((isset($this->families[$fid]["has_children"]) && $this->families[$fid]["has_children"])
                        || (isset($this->families[$fid]["has_parents"]) && $this->families[$fid]["has_parents"])
                        || ((isset($this->families[$fid]["husb_id"]) && $this->families[$fid]["husb_id"]) && (isset($this->families[$fid]["wife_id"]) && $this->families[$fid]["wife_id"]))
                    ) {
                        $out .= $this->printFamily($fid, $nodeName);
                    }
				} elseif ($this->settings["diagram_type"] != "combined") {
					$out .= $this->printFamily($fid, $fid);
				}
			}
		}

		// ### Print the connections ###
		// If no_fams option is not checked
		if (!$this->settings["no_fams"]) {
			foreach ($this->families as $fid=>$set) {
                // COMBINED type diagram
				if ($this->settings["diagram_type"] == "combined") {
                    $nodeName = $this->generateFamilyNodeName($fid);
                    // In case of dummy family do nothing, because it has no children
					if (substr($fid, 0, 2) != "F_") {
						// Get the family data
						$f = $this->getUpdatedFamily($fid);

						// Draw an arrow from FAM to each CHIL
						foreach ($f->children() as $child) {
							if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
								$fams = isset($this->individuals[$child->xref()]["fams"]) ? $this->individuals[$child->xref()]["fams"] : [];
								foreach ($fams as $fam) {
                                    $famName = $this->generateFamilyNodeName($fam);
                                    $arrowColor = $this->getArrowColor($child, $fid);
                                    $out .= $nodeName . " -> " . $famName . ":" . $this->convertID($child->xref()) . " [color=\"$arrowColor\", arrowsize=0.3] \n";
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
						$out .= $this->convertID($husb_id) . " -> " . $this->convertID($fid) ." [color=\"" . $this->colors["arrows"]["default"] . "\", arrowsize=0.3]\n";
					}
					// Draw an arrow from WIFE to FAM
					if (!empty($wife_id) && (isset($this->individuals[$wife_id]))) {
						$out .= $this->convertID($wife_id) . " -> ". $this->convertID($fid) ." [color=\"" . $this->colors["arrows"]["default"] . "\", arrowsize=0.3]\n";
					}
					// Draw an arrow from FAM to each CHIL
					foreach ($f->children() as $child) {
						if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
                            $arrowColor = $this->getArrowColor($child, $fid);
							$out .= $this->convertID($fid) . " -> " . $this->convertID($child->xref()) . " [color=\"$arrowColor\", arrowsize=0.3]\n";
						}
					}
				}
			}
		} else {
		// If no_fams option is checked then we do not print the families
			foreach ($this->families as $fid=>$set) {
				if ($this->settings["diagram_type"] != "combined") {
					$f = $this->getUpdatedFamily($fid);
					// Draw an arrow from HUSB and WIFE to FAM
					$husb_id = empty($f->husband()) ? null : $f->husband()->xref();
					$wife_id = empty($f->wife()) ? null : $f->wife()->xref();

					// Draw an arrow from FAM to each CHIL
					foreach ($f->children() as $child) {
						if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
							if (!empty($husb_id) && (isset($this->individuals[$husb_id]))) {
								$out .= $this->convertID($husb_id) . " -> " . $this->convertID($child->xref()) ." [color=\"#555555\", arrowsize=0.3]\n";
							}
							if (!empty($wife_id) && (isset($this->individuals[$wife_id]))) {
								$out .= $this->convertID($wife_id) . " -> ". $this->convertID($child->xref()) ." [color=\"#555555\", arrowsize=0.3]\n";
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
	 * @param	string $place_long Place string in long format (Town,County,State/Region,Country)
	 * @return	string	The abbreviated place name
	 */
	function getAbbreviatedPlace(string $place_long): string
    {
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
            } else {
				/* Otherwise, we have chosen one of the ISO code options */
				switch ($this->settings["use_abbr_place"]) {
					case 20: //City and 2-Letter ISO Country Code
						$code = "iso2";
						break;
					case 30: //City and 3-Letter ISO Country Code
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
            }
            return $place;
        }
	}

	/**
 	 * Gets the colour associated with the given gender
 	 *
 	 * If a custom colour was used then this function will pull it from the form
 	 * otherwise it will use the default colours in the config file
 	 *
 	 * @param string $gender (F/M/U)
 	 * @param boolean $related (TRUE/FALSE) Person is blood-related
 	 * @return string $colour (#RRGGBB)
 	 */
	function getGenderColour(string $gender, bool $related = TRUE): string
    {
		// Determine the fill color
		if ($gender == 'F') {
			if ($related || !$this->settings["mark_not_related"]) {
				$fillcolor = $this->colors["colorf"];
			} else  {
				$fillcolor = $this->colors["colorf_nr"];
			}
		} elseif ($gender == 'M'){
			if ($related || !$this->settings["mark_not_related"]) {
				$fillcolor = $this->colors["colorm"];
			} else  {
				$fillcolor = $this->colors["colorm_nr"];
			}
		} elseif ($gender == 'X'){
			if ($related || !$this->settings["mark_not_related"]) {
				$fillcolor = $this->colors["colorx"];
			} else  {
				$fillcolor = $this->colors["colorx_nr"];
			}
		} else {
			if ($related || !$this->settings["mark_not_related"]) {
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
	function getFamilyColour(): string
    {
		// Determine the fill color
        return $this->colors["colorfam"];
	}

	/**
	 * Prints DOT header string.
	 *
	 * @return	string	DOT header text
	 */
	function printDOTHeader(): string
    {
        $out = "digraph WT_Graph {\n";
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
		$out .= "bgcolor=\"" . $this->colors['colorbg'] . "\"\n";
		$out .= "edge [ style=solid, arrowhead=normal arrowtail=none];\n";
		if ($this->settings["diagram_type"] == "simple") {
			$out .= "node [ shape=box, style=filled fontsize=\"" . $this->font_size ."\" fontname=\"" . $this->settings["typeface"] ."\"];\n";
		} else {
			$out .= "node [ shape=plaintext fontsize=\"" . $this->font_size ."\" fontname=\"" . $this->settings["typefaces"][$this->settings["typeface"]] . ", " . $this->settings["typeface_fallback"][$this->settings["typeface"]] .", " . $this->settings["typefaces"][$this->settings["defaulttypeface"]] . ", Sans\"];\n";
		}
		return $out;
	}

	/**
	 * Prints DOT footer string.
	 *
	 * @return	string	DOT header text
	 */
	function printDOTFooter(): string
    {
        return "}\n";
	}

	/**
	 * Gives back a text with HTML special chars
	 *
	 * @param string $text	String to convert
	 * @return	string	Converted string
	 */
	function convertToHTMLSC(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
	}

	/**
	 * Prints the line for a single person.
	 *
	 * @param string $pid Person ID
	 */
	function printPerson(string $pid, $related = TRUE): string
    {
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
	 * @param string $pid Person ID
	 */
	function printPersonLabel(string $pid, $related = TRUE): string
    {
		$out = "";
		$bordercolor = $this->colors["colorborder"];	// Border color of the INDI's box
        $deathplace = "";
		// Get the personal data
		if ($this->settings["diagram_type"] == "combined" && ( substr($pid, 0, 3) == "I_H" || substr($pid, 0, 3) == "I_W" )) {
			// In case of dummy individual
			$fillcolor = $this->getGenderColour('U', false);
			$isdead = false;
            $deathdate = "";
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
                $birthdate = $this->formatDate($i->getBirthDate(), $this->settings["bd_type"] !== "gedcom");
			} else {
				$birthdate = "";
			}

			if ($this->settings["show_bp"]) {
				// Show birthplace
				$birthplace = $this->getAbbreviatedPlace($i->getBirthPlace()->gedcomName());
			} else {
				$birthplace = "";
			}

			// --- Death data ---
			if ($this->settings["show_dy"]) {
                if ($this->settings["show_by"]) {
                    $deathdate = $this->formatDate($i->getDeathDate(), $this->settings["dd_type"] !== "gedcom");
                } else {
                    $deathdate = "";
                }
			} else {
				$deathdate = "";
			}
			if ($this->settings["show_dp"]) {
				// Show death place
				$deathplace = $this->getAbbreviatedPlace($i->getDeathPlace()->gedcomName());
			}
            // --- Name ---
            $names = $i->getAllNames();
            $nameArray = $names[$i->getPrimaryName()];
            $name = $this->getFormattedName($nameArray, $pid);

            if ($i->getPrimaryName() !== $i->getSecondaryName()) {
                $altNameArray = $names[$i->getSecondaryName()];
                $addname = $this->getFormattedName($altNameArray, "");
                if (!empty($addname) && trim($addname) !== "" && $this->settings["use_abbr_name"] < 50) {
                    if ($this->settings["diagram_type"] == "simple")
                        $name .= '\n' . $addname;//@@ Meliza Amity
                    else
                        $name .= '<BR />' . $addname;//@@ Meliza Amity
                }
            }
		}

		// --- Printing the INDI details ---
		if ($this->settings["diagram_type"] == "simple") {
			if ($this->settings["show_url"]) {
				// substr($_SERVER['QUERY_STRING'], 0, strrpos($_SERVER['QUERY_STRING'], '/'))
				$out .= "color=\"" . $bordercolor . "\", fillcolor=\"" . $fillcolor . "\", fontcolor=\"" . $this->colors["font_color"]["name"] . "\", target=\"_blank\", href=\"" . $this->convertToHTMLSC($link) . "\" label="; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
			} else {
				$out .= "color=\"" . $bordercolor . "\", fillcolor=\"" . $fillcolor . "\", fontcolor=\"" . $this->colors["font_color"]["name"] . "\", label=";
			}
			$out .= '"';
			$out .= str_replace('"','\"',$name) . '\n' . $this->settings["birth_text"] . $birthdate . " " . (empty($birthplace)?'':'('.$birthplace.')') . '\l';
			if ($isdead) {
				$out .= $this->settings["death_text"] . $deathdate . " " . (empty($deathplace)?'':'('.$deathplace.')');
			} else {
				$out .= " ";
			}
			$out .= '\l';
			$out .= '"';
		} else {
			// Convert birth & death place to get rid of characters which mess up the HTML output
			$birthplace = $this->convertToHTMLSC($birthplace);
			if ($isdead) {
				$deathplace = $this->convertToHTMLSC($deathplace);
			}
            $href = $this->settings["show_url"] ? "TARGET=\"_blank\" HREF=\"" . $this->convertToHTMLSC($link) . "\"" : "";
			// Draw table
            $indibgcolor = $this->isStartingIndividual($pid) && $this->settings['startcol'] == "true" ? $this->colors["colorstartbg"] : $this->colors["colorindibg"];
			if ($this->settings["diagram_type"] == "combined") {
				$out .= "<TABLE BORDER=\"0\" CELLBORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\" BGCOLOR=\"" . $indibgcolor . "\" $href>";
			} else {
				$out .= "<TABLE COLOR=\"" . $bordercolor . "\" BORDER=\"1\" CELLBORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\" BGCOLOR=\"" . $indibgcolor . "\" $href>";
			}
            $birthData = " $birthdate " . (empty($birthplace) ? "" : "($birthplace)");
            $deathData = " $deathdate " . (empty($deathplace) ? "" : "($deathplace)");

            $detailsExist = trim($name . $birthData . $deathData) != "";

            if (!$detailsExist && !$this->settings["diagram_type_combined_with_photo"]) {
                // No information in out tiles so make coloured boxes
                $size = "WIDTH=\"" . ($this->font_size * 3) . "\" HEIGHT=\"" . ($this->font_size * 3) . "\"";
            } else {
                $size = ""; // Let it sort out size itself
            }
			// Top line (colour only)
			$out .= "<TR><TD COLSPAN=\"2\" CELLPADDING=\"2\" BGCOLOR=\"$fillcolor\" PORT=\"nam\" $size></TD></TR>";



			// Second row (photo, name, birth & death data)
            if ($detailsExist || $this->settings["diagram_type_combined_with_photo"]) {
                $out .= "<TR>";
                // Show photo
                if ($this->settings["diagram_type_combined_with_photo"]) {
                    if (isset($this->individuals[$pid]["pic"]) && !empty($this->individuals[$pid]["pic"])) {
                        $out .= "<TD ROWSPAN=\"2\" CELLPADDING=\"1\" PORT=\"pic\" WIDTH=\"" . ($this->font_size * 3.5) . "\" HEIGHT=\"" . ($this->font_size * 4) . "\" FIXEDSIZE=\"true\" ALIGN=\"CENTER\"><IMG SCALE=\"false\" SRC=\"" . $this->individuals[$pid]["pic"] . "\" /></TD>";
                    } else {
                        // Blank cell zero width to keep the height right
                        $out .= "<TD ROWSPAN=\"2\" CELLPADDING=\"1\" PORT=\"pic\" WIDTH=\"" . ($detailsExist ? "0" : ($this->font_size * 3.5)) . "\" HEIGHT=\"" . ($this->font_size * 4) . "\" FIXEDSIZE=\"true\"></TD>";
                    }
                }
                if ($detailsExist) {
                    $out .= "<TD ALIGN=\"LEFT\" BALIGN=\"LEFT\"  TARGET=\"_BLANK\" CELLPADDING=\"4\" PORT=\"dat\">";
                }
                // Show name
                if (trim($name) != "") {
                    $out .= "<FONT COLOR=\"" . $this->colors["font_color"]["name"] . "\" POINT-SIZE=\"" . ($this->font_size_name) . "\">" . $name . "</FONT>";
                    if (trim($birthData . $deathData) != "") {
                        $out .= "<BR />";
                    }
                }
                if (trim($birthData) != "") {
                    $out .= "<FONT COLOR=\"" . $this->colors["font_color"]["details"] . "\" POINT-SIZE=\"" . ($this->font_size) . "\">" . $this->settings["birth_text"] . $birthData . "</FONT>";
                    if (trim($deathData) != "") {
                        $out .= "<BR />";
                    }
                }
                if ($isdead && trim($deathData) !== "") {
                    $out .= "<FONT COLOR=\"" . $this->colors["font_color"]["details"] . "\" POINT-SIZE=\"" . ($this->font_size) . "\">" . $this->settings["death_text"] . $deathData . "</FONT>";
                } else {
                    $out .= " ";
                }

                if ($detailsExist) {
                    $out .= "</TD>";
                }
                $out .= "</TR>";
            }
			// Close table
			$out .= "</TABLE>";
		}

		return $out;
	}

	/**
	 * Prints the line for drawing a box for a family.
	 *
	 * @param string $fid Family ID
	 * @param string $nodeName Name of DOT file node we are creating
	 */
	function printFamily(string $fid, string $nodeName): string
    {
		$out = "";

		$out .= $nodeName;
		$out .= " [ ";

		// Showing the ID of the family, if set
		if ($this->settings["show_fid"]) {
			$family = " (" . $fid . ")";
		} else {
			$family = "";
		}

		// --- Data collection ---
		// If a "dummy" family is set (begins with "F_"), then there is no marriage & family data, so no need for querying webtrees...
		if (substr($fid, 0, 2) == "F_") {
			$fillcolor = $this->getFamilyColour();
			$marriageplace = "";
			$husb_id = $this->families[$fid]["husb_id"];
			$wife_id = $this->families[$fid]["wife_id"];
			if (!empty($this->families[$fid]["unkn_id"])) {
				$unkn_id = $this->families[$fid]["unkn_id"];
			}
			$link = "#";
		// Querying webtrees for the data of a FAM object
		} else {
			$f = $this->getUpdatedFamily($fid);
			$fillcolor = $this->getFamilyColour();
			$link = $f->url();

			// Show marriage year
			if ($this->settings["show_my"]) {
                if ($this->settings["show_by"]) {
                    $marriagedate = $this->formatDate($f
                        ->getMarriageDate(), $this->settings["md_type"] !== "gedcom");
                } else {
                    $marriagedate = "";
                }
			} else {
				$marriagedate = "";
			}

			// Show marriage place
			if ($this->settings["show_mp"] && !empty($f->getMarriage()) && !empty($f->getMarriagePlace())) {
				$marriageplace = $this->getAbbreviatedPlace($f->getMarriagePlace()->gedcomName());
			} else {
				$marriageplace = "";
			}

			// Get the husband's and wife's id from PGV
            $husb_id = $this->families[$fid]["husb_id"] ?? "";
            $wife_id = $this->families[$fid]["wife_id"] ?? "";
		}

		// --- Printing ---
		// "Combined" type
		if ($this->settings["diagram_type"] == "combined") {
			$out .= "label=<";

			// --- Print table ---
			$out .= "<TABLE COLOR=\"" . $this->colors["colorborder"] . "\" BORDER=\"0\" CELLBORDER=\"1\" CELLPADDING=\"2\" CELLSPACING=\"0\">";

			// --- Print couple ---
			$out .= "<TR>";

			if (!empty($unkn_id)) {
				// Print unknown gender INDI
                $out = $this->addPersonLabel($unkn_id, $out);
            } else {
				// Print husband
				if (!empty($husb_id)) {
                    $out = $this->addPersonLabel($husb_id, $out);
				}

				// Print wife
				if (!empty($wife_id)) {
                    $out = $this->addPersonLabel($wife_id, $out);
				}
			}

			$out .= "</TR>";
            // --- Print marriage ---
			if (substr($fid, 0, 2) !== "F_" && !(empty($marriagedate) && empty($marriageplace) && $family == "") && ($this->settings["show_my"] || $this->settings["show_mp"] || $this->settings["show_fid"])) {
				$out .= "<TR>";
				if ($this->settings["show_url"]) {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" PORT=\"marr\" TARGET=\"_BLANK\" HREF=\"" . $this->convertToHTMLSC($link) . "\" BGCOLOR=\"" . $fillcolor . "\">"; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
				} else {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" PORT=\"marr\" BGCOLOR=\"" . $fillcolor . "\">";
				}

				$out .= "<FONT COLOR=\"". $this->colors["font_color"]["details"] ."\" POINT-SIZE=\"" . ($this->font_size) ."\">" . (empty($marriagedate)?"":$marriagedate) . "<BR />" . (empty($marriageplace)?"":"(".$marriageplace.")") . $family . "</FONT>";
				$out .= "</TD>";
				$out .= "</TR>";
			}

			$out .= "</TABLE>";

			$out .= ">";
		} else {
		// Non-combined type
			if ($this->settings["show_url"]) {
                $href = "target=\"_blank\" href=\"" . $this->convertToHTMLSC($link) . "\", target=\"_blank\", ";
            } else {
                $href = "";
            }
            // If names, birth details, and death details are all disabled - show a smaller marriage circle to match the small tiles for individuals.
            if (!$this->settings["show_by"] && !$this->settings["show_bp"] && !$this->settings["show_dy"] && !$this->settings["show_dp"] && !$this->settings["show_my"] && !$this->settings["show_pid"] && !$this->settings["show_fid"] && $this->settings["use_abbr_names"][$this->settings["use_abbr_name"]] == "Don't show names") {
                $out .= "color=\"" . $this->colors["colorborder"] . "\",fillcolor=\"" . $fillcolor . "\", $href shape=point, height=0.2, style=filled";
                $out .= ", label=" . "< >";
            } else {
                $out .= "color=\"" . $this->colors["colorborder"] . "\",fillcolor=\"" . $fillcolor . "\", $href shape=ellipse, style=filled";
                $out .= ", label=" . "<<TABLE border=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD><FONT COLOR=\"". $this->colors["font_color"]["details"] ."\" POINT-SIZE=\"" . ($this->font_size) ."\">" . (empty($marriagedate)?"":$marriagedate) . "<BR />" . (empty($marriageplace)?"":"(".$marriageplace.")") . $family . "</FONT></TD></TR></TABLE>>";
            }
        }

		$out .= "];\n";

		return $out;
	}

	/**
	 * Adds an individual to the indi list
	 *
	 * @param string $pid XREF of individual to add
	 * @param boolean $ance whether to include ancestors when adding this individual
	 * @param boolean $desc whether to include descendants when adding this individual
	 * @param boolean $spou whether to include spouses when adding this individual
	 * @param boolean $sibl whether to add siblings when adding this individual
	 * @param boolean $rel whether to treat this individual as related
	 * @param integer $ind indent level - used for debug output
	 * @param integer $level the current generation - 0 is starting generation, negative numbers are descendants, positive are ancestors
	 * @param array $individuals array of individuals to be updated (passed by reference)
	 * @param array $families array of families to be updated (passed by reference)
	 * @param boolean $full whether we are scanning full tree of relatives, ignoring settings
	 */
	function addIndiToList($sourcePID, string $pid, bool $ance, bool $desc, bool $spou, bool $sibl, bool $rel, int $ind, int $level, array &$individuals, array &$families, bool $full): bool
    {
		// Seen this XREF before and skipped, so just skip again without further checks
		if (isset($this->skipList[$pid])) {
			return false;
		}

		// Set ancestor/descendant levels in case these options disabled
		$ance_level = $this->indi_search_method["ance"] ? $this->settings["ance_level"] : 0;
		$desc_level = $this->indi_search_method["desc"] ? $this->settings["desc_level"] : 0;
        if ($this->settings["desc_level"] == 0) {
            $desc = false;
        }
		// Get updated INDI data
		$i = $this->getUpdatedPerson($pid);

		// If PID invalid, skip this person
		if ($i == null) {
			$this->messages[] = self::ERROR_CHAR . I18N::translate("Invalid starting individual:") . " " . $pid;
			$this->skipList[$pid] = TRUE;
			return false;
		}

		$individuals[$pid]['pid'] = $pid;
		// Overwrite the 'related' status if it was not set before, or it's 'false' (for those people who are added as both related and non-related)

        if (!isset($individuals[$pid]['rel']) || (!$individuals[$pid]['rel'] && $rel)) {
				$individuals[$pid]['rel'] = $rel;
		} else {
			// We've already added this person
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
			$individuals[$pid]["pic"] = $this->addPhotoToIndi($pid);
		}

		// Add the family nr which he/she belongs to as spouse (needed when "combined" mode is used)
		if ($this->settings["diagram_type"] == "combined") {
			$fams = $i->spouseFamilies();
			if ($fams->count() > 0) {

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - /COMBINED MODE/ adding FAMs where INDI is marked as spouse:\n", $ind);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$individuals[$pid]["fams"][$fid] = $fid;

					if (isset($families[$fid]) && ($families[$fid] == $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid, $families);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}

					if ($fam->husband() && $fam->husband()->xref() == $pid) {
						$families[$fid]["husb_id"] = $pid;
					} else {
						$families[$fid]["wife_id"] = $pid;
					}

					if ($desc) {
						$families[$fid]["has_parents"] = TRUE;
					}
				}
			} else {
				// If there is no spouse family we create a dummy one
				$individuals[$pid]["fams"]["F_$pid"] = "F_$pid";
				$this->addFamToList("F_$pid", $families);

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - /COMBINED MODE/ adding dummy FAM (F_$pid), because this INDI does not belong to any family as spouse\n", $ind);
				}
				// -------------

				$families["F_$pid"]["has_parents"] = TRUE;
				if ($i->sex() == "M") {
					$families["F_$pid"]["husb_id"] = $pid;
					$families["F_$pid"]["wife_id"] = "";
				} elseif ($i->sex() == "F") {
				 	$families["F_$pid"]["wife_id"] = $pid;
				 	$families["F_$pid"]["husb_id"] = "";
				} else {
					// Unknown gender
					$families["F_$pid"]["unkn_id"] = $pid;
					$families["F_$pid"]["wife_id"] = "";
				 	$families["F_$pid"]["husb_id"] = "";
				}
			}
		}



		// Check that INDI is listed in stop pids (should we stop the tree processing or not?)
		$stop_proc = FALSE;
		if (isset($this->settings["stop_proc"]) && $this->settings["stop_proc"]) {
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

		if (!$stop_proc || $full)
		{

			// Add ancestors (parents)
			if ($ance && ($full || $level < $ance_level)) {
				// Get the list of families where the INDI is listed as CHILD
				$famc = $i->childFamilies();

				// --- DEBUG ---
				if ($this->settings["debug"]) {
					$this->printDebug("($pid) - adding ANCESTORS (LEVEL: $level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a child (to find his/her parents):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				if ($famc->count() > 0) {
					// For every family where the INDI is listed as CHILD
					foreach ($famc as $fam) {
						// Get the family ID
						$fid = $fam->xref();
						// Get the family object
						$f = $this->getUpdatedFamily($fid);

                        if (isset($families[$fid]["fid"]) && ($families[$fid]["fid"]== $fid)) {
                            // Family ID already added, do nothing
                            // --- DEBUG ---
                            if ($this->settings["debug"]) {
                                $this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
                            }
                            // -------------
                        } else {
                            $this->addFamToList($fid, $families);

                            // --- DEBUG ---
                            if ($this->settings["debug"]) {
                                $this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
                            }
                            // -------------
                        }

						// Work out if indi has adoptive relationship to this family
						$relationshipType = $this->getRelationshipType($i, $fam, $ind);
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
							$families[$fid]["has_children"] = TRUE;
							$families[$fid]["husb_id"] = $husb_id;

							if ($relationshipType == "BOTH" || $relationshipType == "HUSB") {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /FATHER/ with INDI id ($husb_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 1", $husb_id, TRUE, FALSE, $this->indi_search_method["spou"] && $relationshipType !== "BOTH", $this->indi_search_method["sibl"], FALSE, $ind, $level+1, $individuals, $families, $full);
							} else {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding a PARENT /FATHER/ with INDI id ($husb_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 2", $husb_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], $rel && $relationshipType == "", $ind, $level+1, $individuals, $families, $full);

							}
						}
						if (!empty($wife_id)) {
							$families[$fid]["has_children"] = TRUE;
							$families[$fid]["wife_id"] = $wife_id;

							if ($relationshipType == "BOTH" || $relationshipType == "WIFE") {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /MOTHER/ with INDI id ($wife_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 3", $wife_id, TRUE, FALSE, $this->indi_search_method["spou"] && $relationshipType !== "BOTH", $this->indi_search_method["sibl"], FALSE, $ind, $level+1, $individuals, $families, $full);
							} else {
								// --- DEBUG ---
								if ($this->settings["debug"]) {
									$this->printDebug("($pid) -- adding a PARENT /MOTHER/ with INDI id ($wife_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 4", $wife_id, TRUE, FALSE, $this->indi_search_method["spou"], $this->indi_search_method["sibl"], $rel && $relationshipType == "", $ind, $level+1, $individuals, $families, $full);

							}
						}

						if ($this->settings["diagram_type"] == "combined") {
							// This person's spouse family HAS parents
							foreach ($individuals[$pid]["fams"] as $s_fid=>$s_fam) {
								$families[$s_fid]["has_parents"] = TRUE;
							}
						}

					}
				} else {
					if ($this->settings["diagram_type"] == "combined") {
						// This person's spouse family HAS NO parents
						foreach ($individuals[$pid]["fams"] as $s_fid=>$s_fam) {
							if (!isset($families[$s_fid]["has_parents"])) {
								$families[$s_fid]["has_parents"] = FALSE;
							}
						}
					}
				}
				// Decrease the max ancestors level
			}

			// Add descendants (children)
			if ($desc && ($full || $level > -1*$desc_level)) {
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
					$families[$fid]["has_children"] = FALSE;
					$f = $this->getUpdatedFamily($fid);

                    $h = $f->husband();
                    if($h){
                        if($h->xref() == $pid){
                            $families[$fid]["husb_id"] = $pid;
                        } else {
                            $families[$fid]["wife_id"] = $pid;
                        }
                    }
                    else {
                        $w = $f->wife();
                        if($w){
                            if($w->xref() == $pid){
                                $families[$fid]["wife_id"] = $pid;
                            } else {
                                $families[$fid]["husb_id"] = $pid;
                            }
                        }
                    }

					if (isset($families[$fid]["fid"]) && ($families[$fid]["fid"]== $fid)) {
						// Family ID already added
						// do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid, $families);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					}


					$children = $f->children();
                    if (sizeof($children) !== 0) {
                        $families[$fid]["has_children"] = TRUE;
                    }
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
							$relationshipType = $this->getRelationshipType($child, $f, $ind);
							if ($relationshipType != "") {
								$related = false;
							} else {
								$related = $rel;
							}

							if ($this->indi_search_method["any"]) {
								$this->addIndiToList($pid."|Code 14", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, FALSE, $ind, $level-1, $individuals, $families, $full);
							}
							$this->addIndiToList($pid."|Code 5", $child_id, FALSE, TRUE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level-1, $individuals, $families, $full);

						}
					}
				}
			}

			// Add spouses
			if (($spou && !$desc) || ($spou && $desc && $level >= -1*$desc_level) || ($spou && $this->settings["diagram_type"] == "combined")) {
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

                    if (isset($families[$fid]["fid"]) && ($families[$fid]["fid"]== $fid)) {
                        // Family ID already added, do nothing
                        // --- DEBUG ---
                        if ($this->settings["debug"]) {
                            $this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
                        }
                        // -------------
                    } else {
                        $this->addFamToList($fid, $families);

                        // --- DEBUG ---
                        if ($this->settings["debug"]) {
                            $this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
                        }
                        // -------------
                    }

					//$spouse_id = $f->getSpouseId($pid);
					// Alternative method of getting the $spouse_id - workaround by Till Schulte-Coerne
                    // And the coerced into webtrees by Iain MacDonald
                    $h = $f->husband();
					if ($h) {
                        $w = $f->wife();
                        if($h->xref() == $pid) {
                            if($w) {
                                $spouse_id = $w->xref();
                                $families[$fid]["husb_id"] = $pid;
                                $families[$fid]["wife_id"] = $spouse_id;
                            }
                        }
                        else {
                            if($w && $w->xref() == $pid) {
                                $spouse_id = $h->xref();
                                $families[$fid]["husb_id"] = $spouse_id;
                                $families[$fid]["wife_id"] = $pid;
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
                        $this->addIndiToList($pid."|Code 6", $spouse_id, $this->indi_search_method["any"] && $ance, $this->indi_search_method["any"] && $desc, $this->indi_search_method["any"], $this->indi_search_method["any"], FALSE, $ind, $level, $individuals, $families, $full);
					}

				}
			}

			// Add siblings
			if ($sibl && ($full || $level < $ance_level)) {
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

					if (isset($families[$fid]["fid"]) && ($families[$fid]["fid"]== $fid)) {
						// Family ID already added, do nothing
						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
						}
						// -------------
					} else {
                        $this->addFamToList($fid, $families);

						// --- DEBUG ---
						if ($this->settings["debug"]) {
							$this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
						}
						// -------------
					}

					$children = $f->children();
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id) && ($child_id != $pid)) {
							$families[$fid]["has_children"] = TRUE;
							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- adding a SIBLING with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// Work out if indi has adoptive relationship to this family
							$relationshipType = $this->getRelationshipType($child, $fam, $ind);
                            // Work out if WE have adoptive relationship to this family
                            $sourceRelationshipType = $this->getRelationshipType($i, $fam, $ind);
							if ($relationshipType != "" || $sourceRelationshipType != "") {
								$related = false;
							} else {
								$related = $rel;
							}

							// If searching for cousins, then the descendants of ancestors' siblings should be added
							if ($this->indi_search_method["cous"]) {
								$this->addIndiToList($pid."|Code 8", $child_id, TRUE, TRUE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level, $individuals, $families, $full);
							} else {
								$this->addIndiToList($pid."|Code 9", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, $related, $ind, $level, $individuals, $families, $full);
							}

						}
					}
				}
			}

			// Add step-siblings
			if ($sibl && $level < $ance_level && !$full) {
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
					$this->addFamToList($fid, $families);

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
							$families[$fid]["has_children"] = TRUE;
							// --- DEBUG ---
							if ($this->settings["debug"]) {
								$this->printDebug("($pid) -- adding a STEP-SIBLING with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// If searching for step-cousins, then the descendants of ancestors' siblings should be added
							if ($this->indi_search_method["cous"]) {
								$this->addIndiToList($pid."|Code 10", $child_id, FALSE, TRUE, $this->indi_search_method["spou"], FALSE, $rel, $ind, $level, $individuals, $families, $full);
							} else {
								$this->addIndiToList($pid."|Code 11", $child_id, TRUE, FALSE, $this->indi_search_method["spou"], FALSE, $rel, $ind, $level, $individuals, $families, $full);
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
        return false;
    }

	/**
	 * Adds a family to the family list
	 *
	 */
	function addFamToList($fid, &$families) {
        if($fid instanceof Family)
            $fid = $fid->xref();
        if(!isset($families[$fid]))
			$families[$fid] = array();
		$families[$fid]["fid"] = $fid;
	}

	/**
	 * Adds a path to the photo of a given individual
 	 *
	 * @param string $pid Individual's GEDCOM id (Ixxx)
	 */
	function addPhotoToIndi(string $pid) {
		$i = Registry::individualFactory()->make($pid, $this->tree);
		$m = $i->findHighlightedMediaFile();
		if (empty($m)) {
			return null;
		} else if (!$m->isExternal() && $m->fileExists($this->file_system)) {
			// If we are rendering in the browser, provide the URL, otherwise provide the server side file location
			if (isset($_REQUEST["download"])) {
                require_once(dirname(__FILE__) . "/ImageFile.php");
                $image = new ImageFile($m, $this->tree, $this->settings["dpi"] * 2);
				return $image->getImageLocation();
			} else {
				return str_replace("&","%26",$m->imageUrl($this->settings["dpi"]*2,$this->settings["dpi"]*2,"contain"));
			}
		} else {
			return null;
		}
	}

	function getUpdatedFamily($fid): ?Family
    {
		return Registry::familyFactory()->make($fid, $this->tree);
	}

	function getUpdatedPerson($pid): ?Individual
    {
		return Registry::individualFactory()->make($pid, $this->tree);
	}

	function printDebug($txt, $ind = 0) {
		print(str_repeat("\t", $ind) . $txt);
	}

	// Linked IDs has a colon, it needs to be replaced
	function convertID($id) {
		return preg_replace("/:/", "_", $id);
	}

    public function getArrowColor($i, $fid)
    {
        $relationshipType = "";
        if (substr($fid, 0, 2) != "F_") {
            $f = $this->getUpdatedFamily($fid);
            $relationshipType = $this->getRelationshipType($i, $f);
        }

        if ($relationshipType != "") {
            $arrowColor = $this->settings["color_arrow_related"] == "color_arrow_related" ? $this->colors["arrows"]["not_related"] : $this->colors["arrows"]["default"];
        } else {
            $arrowColor = $this->settings["color_arrow_related"] == "color_arrow_related" ? $this->colors["arrows"]["related"] : $this->colors["arrows"]["default"];
        }
        return $arrowColor;
    }
    public function formatDate($date, $yearOnly = false, $date_format = null) {
        $date_format = $date_format ?? I18N::dateFormat();
        $q1 = $date->qual1;
        $d1 = $date->minimumDate()->format($date_format, $date->qual1);
        $q2 = $date->qual2;
        if ($date->maximumDate() === null) {
            $d2 = '';
        } else {
            $d2 = $date->maximumDate()->format($date_format, $q2);
        }
        $dy = $date->minimumDate()->format("%Y");

        if (!$yearOnly) {
            switch ($q1 . $q2) {
                case '':
                    $tmp = $d1;
                    break;
                case 'ABT':
                    $tmp = "~ " . $d1;
                    break;
                case 'CAL':
                    $tmp = I18N::translate('calculated %s', $d1);
                    break;
                case 'EST':
                    $tmp = "± " . $d1;
                    break;
                case 'INT':
                    $tmp = I18N::translate('interpreted %s', $d1);
                    break;
                case 'BEF':
                    $tmp = "&lt; " . $d1;
                    break;
                case 'AFT':
                    $tmp = "&gt; " . $d1;
                    break;
                case 'FROM':
                    $tmp = I18N::translate('from %s', $d1);
                    break;
                case 'TO':
                    $tmp = I18N::translate('to %s', $d1);
                    break;
                case 'BETAND':
                    $tmp = "&gt; " . $d1 . " &lt; " . $d2;
                    break;
                case 'FROMTO':
                    $tmp = I18N::translate('from %s to %s', $d1, $d2);
                    break;
                default:
                    $tmp = '';
                    break;
            }
        } else {
            $tmp = trim("$q1 $dy");
        }
        return $tmp;
    }

    /** Add the DOT code to include this individual in the diagram.
     *
     * @param string $pid
     * @param string $out
     * @return string
     */
    public function addPersonLabel(string $pid, string $out): string
    {
        if (isset($this->individuals[$pid]['rel']) && !$this->individuals[$pid]['rel']) {
            $related = FALSE;
        } else {
            $related = TRUE;
        }
        $out .= "<TD CELLPADDING=\"0\" PORT=\"" . $pid . "\">";
        $out .= $this->printPersonLabel($pid, $related);
        $out .= "</TD>";
        return $out;
    }

    /**
     *  Check if XREF in list of starting individuals
     * @param string $pid Xref to check
     * @return bool
     */
    private function isStartingIndividual(string $pid): bool
    {
        $indis = explode(",", $this->settings["indi"]);
        for ($i=0;$i<count($indis);$i++) {
            if (trim($indis[$i]) == $pid) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $fid XREF of the family for this node
     * @return string
     */
    private function generateFamilyNodeName(string $fid): string
    {
        return $this->convertID($fid) . (isset($this->families[$fid]["husb_id"]) ? "_" . $this->families[$fid]["husb_id"] : "") . (isset($this->families[$fid]["wife_id"]) ? "_" . $this->families[$fid]["wife_id"] : "") . (isset($this->families[$fid]["unkn_id"]) ? "_" . $this->families[$fid]["unkn_id"] : "");
    }
}