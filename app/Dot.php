<?php
/**
 * DOT file generating functions for Graphviz module
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

use Fisharebest\Webtrees\Date;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\I18n;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Main class for managing the DOT file
 *
 */
class Dot {
	var array $individuals = array();
	var array $skipList = array();
	var array $families = array();
	var array $indi_search_method = array("ance" => FALSE, "desc" => FALSE, "spou" => FALSE, "sibl" => FALSE, "rels" => FALSE, "any" => FALSE);
	var array $settings = array();
    var array $messages = array(); // messages for toast system
	private const ERROR_CHAR = "E:"; // Messages that start with this will be highlighted
    private Tree $tree;
    public string $debug_string = "";

    /**
	 * Constructor of Dot class
	 */
	function __construct($tree, $module) {
		$this->tree = $tree;
    // Load settings from config file
        $this->settings=(new Settings())->loadUserSettings($module,$tree);
        $this->settings["no_fams"] = FALSE;
	}

    /**
     * Function to set settings
     *
     * @param array $vars
     */
    public function setSettings(array $vars) {
        foreach ($vars as $preference => $value) {
            $this->settings[$preference] = $value;
        }
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
			($this->settings["show_photos"]));
	}


	/** Checks if provided individual is related by
	 * adoption or foster to the provided family record
	 * @param object $i webtrees individual object for the person to check
	 * @param object $f webtrees family object for the family to check against
	 * @param integer $ind the indent level for printing the debug log
	 * @return string
	 */
	private function getRelationshipType(object $i, object $f, int $ind = 0): string
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
							if ($this->settings["enable_debug_mode"]) {
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
		if ($this->settings["enable_debug_mode"]) {
			$this->printDebug("-- Link between individual ".$i->xref()." and family ".$fid." is ".($adopfamcadoptype=="" ? "blood" : $adopfamcadoptype).".\n", $ind);
		}
		// -------------

		return $adopfamcadoptype;
	}

    /** Populate $individuals and $families arrays with lists of the individuals and families
     *  that will be included in the diagram.
     * @param array $individuals    Array to pupulate individuals to
     * @param array $families       Array to populate families to
     * @param bool $full            Whether max levels setting should be ignored to generate a full tree of relatives
     * @return void                 Directly updates arrays so no return value
     */
    private function createIndiList (array &$individuals, array &$families, bool $full) {
        $this->indi_search_method = array("ance" => $this->settings["include_ancestors"], "desc" => $this->settings["include_descendants"], "spou" => $this->settings["include_spouses"], "sibl" => $this->settings["include_siblings"], "rels" => $this->settings["include_all_relatives"], "any" => $this->settings["include_all"]);
        $indis = explode(",", $this->settings['xref_list']);
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
	private function removeGhosts(array &$individuals, array &$families) {
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
		if (!functionsClippingsCart::isIndividualInCart($this->tree) || !$this->settings["use_cart"] ) {
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
			if ($this->settings["mark_not_related"] && !$this->settings["faster_relation_check"]) {
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
					$this->indi_search_method = array("ance" => TRUE, "desc" => TRUE, "spou" => FALSE, "sibl" => TRUE, "rels" => TRUE, "any" => FALSE);
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
			$functionsCC = new functionsClippingsCart($this->tree, $this->isPhotoRequired(), ($this->settings["diagram_type"] == "combined"), $this->settings['dpi']);
			$this->individuals = $functionsCC->getIndividuals();
			$this->families = $functionsCC->getFamilies();
		}

        // Create shared notes data
        $sharednotes = new SharedNoteList($this->settings['sharednote_col_data'], $this->tree, $this->settings['sharednote_col_default']);

        $out = $this->printDOTHeader();

		// ### Print the individuals list ###
		if ($this->settings["diagram_type"] != "combined") {
			foreach ($this->individuals as $person_attributes) {
                $person = new Person($person_attributes, $this);
				$out .= $person->printPerson($sharednotes);
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
                        $out .= $this->printFamily($fid, $nodeName, $sharednotes);
                    }
				} elseif ($this->settings["diagram_type"] != "combined") {
					$out .= $this->printFamily($fid, $fid, $sharednotes);
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
                                    $family_name = $this->generateFamilyNodeName($fam);
                                    $arrow_colour = $this->getArrowColour($child, $fid);
                                    $out .= $nodeName . " -> " . $family_name . ":" . $this->convertID($child->xref()) . " [color=\"$arrow_colour\", arrowsize=0.3] \n";
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
						$out .= $this->convertID($husb_id) . " -> " . $this->convertID($fid) ." [color=\"" . $this->settings["arrows_default"] . "\", arrowsize=0.3]\n";
					}
					// Draw an arrow from WIFE to FAM
					if (!empty($wife_id) && (isset($this->individuals[$wife_id]))) {
						$out .= $this->convertID($wife_id) . " -> ". $this->convertID($fid) ." [color=\"" . $this->settings["arrows_default"] . "\", arrowsize=0.3]\n";
					}
					// Draw an arrow from FAM to each CHIL
					foreach ($f->children() as $child) {
						if (!empty($child) && (isset($this->individuals[$child->xref()]))) {
                            $arrow_colour = $this->getArrowColour($child, $fid);
							$out .= $this->convertID($fid) . " -> " . $this->convertID($child->xref()) . " [color=\"$arrow_colour\", arrowsize=0.3]\n";
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
		// Determine the fill colour
		if ($gender == 'F') {
			if ($related || !$this->settings["mark_not_related"]) {
				$fill_colour = $this->settings["female_col"];
			} else  {
				$fill_colour = $this->settings["female_unrelated_col"];
			}
		} elseif ($gender == 'M'){
			if ($related || !$this->settings["mark_not_related"]) {
				$fill_colour = $this->settings["male_col"];
			} else  {
				$fill_colour = $this->settings["male_unrelated_col"];
			}
		} elseif ($gender == 'X'){
			if ($related || !$this->settings["mark_not_related"]) {
				$fill_colour = $this->settings["other_gender_col"];
			} else  {
				$fill_colour = $this->settings["oth_gender_unrel_col"];
			}
		} else {
			if ($related || !$this->settings["mark_not_related"]) {
				$fill_colour = $this->settings["unknown_gender_col"];
			} else  {
				$fill_colour = $this->settings["unkn_gender_unrel_col"];
			}
		}
		return $fill_colour;
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
		// Determine the fill colour
        return $this->settings["family_col"];
	}

	/**
	 * Prints DOT header string.
	 *
	 * @return	string	DOT header text
	 */
	function printDOTHeader(): string
    {
        $out = "digraph WT_Graph {\n";
		$out .= "ranksep=\"" . str_replace("%","",$this->settings["ranksep"])*$this->settings["space_base"]/100 . " equally\"\n";
		$out .= "nodesep=\"" . str_replace("%","",$this->settings["nodesep"])*$this->settings["space_base"]/100	 . "\"\n";
		$out .= "dpi=\"" . $this->settings['dpi'] . "\"\n";
		$out .= "mclimit=\"" . $this->settings["mclimit"] . "\"\n";
		$out .= "rankdir=\"" . $this->settings["graph_dir"] . "\"\n";
		$out .= "pagedir=\"LT\"\n";
		$out .= "bgcolor=\"" . $this->settings['background_col'] . "\"\n";
		$out .= "edge [ style=solid, arrowhead=normal, arrowtail=none];\n";
        $out .= "node [ shape=plaintext font_size=\"" . $this->settings['font_size'] ."\" fontname=\"" . $this->settings["typefaces"][$this->settings["typeface"]] . "\"];\n";
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
     * Prints the line for drawing a box for a family.
     *
     * @param string $fid Family ID
     * @param string $nodeName Name of DOT file node we are creating
     * @param SharedNoteList $sharednotes
     * @return string
     */
	function printFamily(string $fid, string $nodeName, SharedNoteList $sharednotes): string
    {

        $out = $nodeName;
		$out .= " [ ";

		// Showing the ID of the family, if set
		if ($this->settings["show_xref_families"] == "show_xref_families") {
			$family = " (" . $fid . ")";
		} else {
			$family = "";
		}

		// --- Data collection ---
		// If a "dummy" family is set (begins with "F_"), then there is no marriage & family data, so no need for querying webtrees...
		if (substr($fid, 0, 2) == "F_") {
			$fill_colour = $this->getFamilyColour();
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

            // Set marriage prefix only if marriage exists
            $married = !(empty($f->getMarriage()) && Date::compare($f->getMarriageDate(), new Date('')) == 0);
            if ($married) {
                $prefix = $this->settings["marriage_prefix"] . ' ';
            } else {
                $prefix = '';
            }
			$fill_colour = $this->getFamilyColour();
			$link = $f->url();

			// Show marriage year
			if ($this->settings["show_marriage_date"]) {
                $marriagedate = $this->formatDate($f->getMarriageDate(), $this->settings["marr_date_year_only"]);
			} else {
				$marriagedate = "";
			}

			// Show marriage place
			if ($this->settings["show_marriage_place"] && !empty($f->getMarriage()) && !empty($f->getMarriagePlace())) {
				$marriageplace = $this->getAbbreviatedPlace($f->getMarriagePlace()->gedcomName(), $this->settings);
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
			$out .= "<TABLE COLOR=\"" . $this->settings["border_col"] . "\" BORDER=\"0\" CELLBORDER=\"1\" CELLPADDING=\"2\" CELLSPACING=\"0\">";

			// --- Print couple ---
			$out .= "<TR>";

			if (!empty($unkn_id)) {
                // Print unknown gender INDI
                $person = new Person([], $this);
                $out = $person->addPersonLabel($unkn_id, $out, $sharednotes);
            } else {
			  // Print husband
			  if (!empty($husb_id)) {
                  $person = new Person([], $this);
                  $out = $person->addPersonLabel($husb_id, $out, $sharednotes);
			  }

			  // Print wife
			  if (!empty($wife_id)) {
			     if ($this->settings["combined_layout_type"] == 'OU' && !empty($husb_id)) {
			       $out .= "</TR>";
   			       $out .= "<TR>";
                             }
                   	     $person = new Person([], $this);
                   	     $out = $person->addPersonLabel($wife_id, $out, $sharednotes);
			  }
			}

			$out .= "</TR>";
            // --- Print marriage ---
			if (substr($fid, 0, 2) !== "F_" && !(empty($marriagedate) && empty($marriageplace) && empty($family) && empty($prefix)) && ($this->settings["show_marriage_date"] || $this->settings["show_marriage_place"] || $this->settings["show_xref_families"])) {
				$out .= "<TR>";
				if ($this->settings["add_links"]) {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" CELLBORDER=\"1\" PORT=\"marr\" TARGET=\"_BLANK\" HREF=\"" . $this->convertToHTMLSC($link) . "\" BGCOLOR=\"" . $fill_colour . "\">"; #ESL!!! 20090213 without convertToHTMLSC the dot file has invalid data
				} else {
					$out .= "<TD COLSPAN=\"2\" CELLPADDING=\"0\" CELLBORDER=\"1\" PORT=\"marr\" BGCOLOR=\"" . $fill_colour . "\">";
				}

				$out .= "<FONT COLOR=\"". $this->settings["font_colour_details"] ."\" POINT-SIZE=\"" . ($this->settings["font_size"]) ."\">" . $prefix . (empty($marriagedate)?"":$marriagedate) . "<BR />" . (empty($marriageplace)?"":"(".$marriageplace.")") . $family . "</FONT>";
				$out .= "</TD>";
				$out .= "</TR>";
			}

			$out .= "</TABLE>";

			$out .= "> tooltip=\" \"";
		} else {
		// Non-combined type
			if ($this->settings["add_links"]) {
                $href = "target=\"_blank\" href=\"" . $this->convertToHTMLSC($link) . "\", target=\"_blank\", ";
            } else {
                $href = "";
            }
            // If names, birth details, and death details are all disabled - show a smaller marriage circle to match the small tiles for individuals.
            if (!$this->settings["show_marriage_date"] && !$this->settings["show_marriage_place"] && !$this->settings["show_xref_families"]) {
                $out .= "color=\"" . $this->settings["border_col"] . "\",fillcolor=\"" . $fill_colour . "\", $href shape=point, height=0.2, style=filled";
                $out .= ", label=" . "< >";
            } else {
                $out .= "color=\"" . $this->settings["border_col"] . "\",fillcolor=\"" . $fill_colour . "\", $href shape=oval, style=\"filled\", margin=0.01";
                $out .= ", label=" . "<<TABLE border=\"0\" CELLPADDING=\"5\" CELLSPACING=\"0\"><TR><TD><FONT COLOR=\"". $this->settings["font_colour_details"] ."\" POINT-SIZE=\"" . ($this->settings["font_size"]) ."\">" . $prefix . (empty($marriagedate)?"":$marriagedate) . "<BR />" . (empty($marriageplace)?"":"(".$marriageplace.")") . $family . "</FONT></TD></TR></TABLE>>";
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
		$ance_level = $this->indi_search_method["ance"] ? $this->settings["ancestor_levels"] : 0;
		$desc_level = $this->indi_search_method["desc"] ? $this->settings["descendant_levels"] : 0;
        if ($this->settings["descendant_levels"] == 0) {
            $desc = false;
        }
		// Get updated INDI data
		$i = $this->getUpdatedPerson($pid);

		// If PID invalid, skip this person
		if ($i == null) {
			$this->messages[] = self::ERROR_CHAR . I18N::translate('Invalid starting individual:') . " " . $pid;
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
		if ($this->settings["enable_debug_mode"]) {
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
		if ($this->settings["show_photos"] && $this->isPhotoRequired()) {
			$individuals[$pid]["pic"] = $this->addPhotoToIndi($pid);
		}

		// Add the family nr which he/she belongs to as spouse (needed when "combined" mode is used)
		if ($this->settings["diagram_type"] == "combined") {
			$fams = $i->spouseFamilies();
			if ($fams->count() > 0) {

				// --- DEBUG ---
				if ($this->settings["enable_debug_mode"]) {
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
						if ($this->settings["enable_debug_mode"]) {
							$this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
							//var_dump($fams);
						}
						// -------------
					} else {
						$this->addFamToList($fid, $families);

						// --- DEBUG ---
						if ($this->settings["enable_debug_mode"]) {
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
				if ($this->settings["enable_debug_mode"]) {
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
			$stop_pids = explode(",", $this->settings["stop_xref_list"]);
			for ($j=0;$j<count($stop_pids);$j++) {
				if ($pid == trim($stop_pids[$j])){
					// --- DEBUG ---
					if ($this->settings["enable_debug_mode"]) {
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
				if ($this->settings["enable_debug_mode"]) {
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

                        $this->addIndiFamilies($fid, $pid, $ind, $families);

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
								if ($this->settings["enable_debug_mode"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /FATHER/ with INDI id ($husb_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 1", $husb_id, TRUE, FALSE, $this->indi_search_method["spou"] && $relationshipType !== "BOTH", $this->indi_search_method["sibl"], FALSE, $ind, $level+1, $individuals, $families, $full);
							} else {
								// --- DEBUG ---
								if ($this->settings["enable_debug_mode"]) {
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
								if ($this->settings["enable_debug_mode"]) {
									$this->printDebug("($pid) -- adding an _ADOPTING_ PARENT /MOTHER/ with INDI id ($wife_id) from FAM ($fid):\n", $ind);
									//var_dump($fams);
								}
								// -------------
								$this->addIndiToList($pid."|Code 3", $wife_id, TRUE, FALSE, $this->indi_search_method["spou"] && $relationshipType !== "BOTH", $this->indi_search_method["sibl"], FALSE, $ind, $level+1, $individuals, $families, $full);
							} else {
								// --- DEBUG ---
								if ($this->settings["enable_debug_mode"]) {
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
				if ($this->settings["enable_debug_mode"]) {
					$this->printDebug("($pid) - adding DESCENDANTS (LEVEL: $level, descendant_levels: $desc_level)\n", $ind);
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

                    $this->addIndiFamilies($fid, $pid, $ind, $families);

					$children = $f->children();
                    if (sizeof($children) !== 0) {
                        $families[$fid]["has_children"] = TRUE;
                    }
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id)) {

							// --- DEBUG ---
							if ($this->settings["enable_debug_mode"]) {
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
				if ($this->settings["enable_debug_mode"]) {
					$this->printDebug("($pid) - adding SPOUSES\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a spouse (to find his/her spouse(s)):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				foreach ($fams as $fam) {
					$fid = $fam->xref();
					$f = $this->getUpdatedFamily($fid);

                    $this->addIndiFamilies($fid, $pid, $ind, $families);

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
						if ($this->settings["enable_debug_mode"]) {
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
				if ($this->settings["enable_debug_mode"]) {
					$this->printDebug("($pid) - adding SIBLINGS (LEVEL: $level)\n", $ind);
					$this->printDebug("($pid) -- adding FAMs, where this INDI is listed as a child (to find his/her siblings):\n", $ind);
					//var_dump($fams);
				}
				// -------------

				foreach ($famc as $fam) {
					$fid = $fam->xref();
					$f = $this->getUpdatedFamily($fid);

                    $this->addIndiFamilies($fid, $pid, $ind, $families);

					$children = $f->children();
					foreach ($children as $child) {
						$child_id = $child->xref();
						if (!empty($child_id) && ($child_id != $pid)) {
							$families[$fid]["has_children"] = TRUE;
							// --- DEBUG ---
							if ($this->settings["enable_debug_mode"]) {
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
							if ($this->indi_search_method["rels"]) {
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
				if ($this->settings["enable_debug_mode"]) {
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
					if ($this->settings["enable_debug_mode"]) {
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
							if ($this->settings["enable_debug_mode"]) {
								$this->printDebug("($pid) -- adding a STEP-SIBLING with INDI id ($child_id) from FAM ($fid):\n", $ind);
								//var_dump($fams);
							}
							// -------------

							// If searching for step-cousins, then the descendants of ancestors' siblings should be added
							if ($this->indi_search_method["rels"]) {
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
		if ($this->settings["enable_debug_mode"]) {
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
        $resolution = floatval($this->settings["photo_resolution"]) / 100;
		if (empty($m)) {
			return null;
		} else if (!$m->isExternal() && $m->fileExists()) {
			// If we are rendering in the browser, provide the URL, otherwise provide the server side file location
			if (isset($_REQUEST["download"])) {
                $image = new ImageFile($m, $this->tree, $this->settings['dpi']*$resolution);
				return $image->getImageLocation($this->settings["photo_quality"], $this->settings["convert_photos_jpeg"]);
			} else {
                switch ($this->settings['photo_shape']) {
                    case 0:
                    case 10:
                    case 40:
                        $fit = 'contain';
                        break;
                    default:
                        $fit = 'crop';
                }

				return str_replace("&","%26",$m->imageUrl($this->settings['dpi']*$resolution,$this->settings['dpi']*$resolution, $fit));
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
		$this->debug_string .= (str_repeat("\t", $ind) . $txt);
	}


    public function getArrowColour($i, $fid)
    {
        $relationshipType = "";
        if (substr($fid, 0, 2) != "F_") {
            $f = $this->getUpdatedFamily($fid);
            $relationshipType = $this->getRelationshipType($i, $f);
        }

        if ($relationshipType != "") {
            $arrow_colour = $this->settings["colour_arrow_related"] == "colour_arrow_related" ? $this->settings["arrows_not_related"] : $this->settings["arrows_default"];
        } else {
            $arrow_colour = $this->settings["colour_arrow_related"] == "colour_arrow_related" ? $this->settings["arrows_related"] : $this->settings["arrows_default"];
        }
        return $arrow_colour;
    }

    /**
     * @param string $fid XREF of the family for this node
     * @return string
     */
    private function generateFamilyNodeName(string $fid): string
    {
        return $this->convertID($fid) . (isset($this->families[$fid]["husb_id"]) ? "_" . $this->families[$fid]["husb_id"] : "") . (isset($this->families[$fid]["wife_id"]) ? "_" . $this->families[$fid]["wife_id"] : "") . (isset($this->families[$fid]["unkn_id"]) ? "_" . $this->families[$fid]["unkn_id"] : "");
    }

    private function addIndiFamilies($fid, $pid, $ind, &$families)
    {
        if (isset($families[$fid]["fid"]) && ($families[$fid]["fid"]== $fid)) {
            // Family ID already added, do nothing
            // --- DEBUG ---
            if ($this->settings["enable_debug_mode"]) {
                $this->printDebug("($pid) -- FAM ($fid) already added\n", $ind);
            }
            // -------------
        } else {
            $this->addFamToList($fid, $families);

            // --- DEBUG ---
            if ($this->settings["enable_debug_mode"]) {
                $this->printDebug("($pid) -- FAM ($fid) added\n", $ind);
            }
            // -------------
        }
    }

    /**
     * Returns an abbreviated version of the PLAC string.
     *
     * @param	string $place_long Place string in long format (Town,County,State/Region,Country)
     * @return	string	The abbreviated place name
     */
    public static function getAbbreviatedPlace(string $place_long, array $settings): string
    {
        // If chose no abbreviating, then return string untouched
        if ($settings["use_abbr_place"] == 0 /* Full place name */) {
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
            if ($settings["use_abbr_place"] == 10 /* City and Country */) {
                if (!empty($place_chunks[$chunk_count - 1]) && ($chunk_count > 1)) {
                    if (!empty($place)) {
                        $place .= ", ";
                    }
                    $place .= trim($place_chunks[$chunk_count - 1]);
                }
            } else {
                /* Otherwise, we have chosen one of the ISO code options */
                switch ($settings["use_abbr_place"]) {
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
                if (isset($settings["countries"][$code][strtolower(trim($place_chunks[$chunk_count - 1]))])) {
                    $place .= $settings["countries"][$code][strtolower(trim($place_chunks[$chunk_count - 1]))];
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
     * Gives back a text with HTML special chars
     *
     * @param string $text	String to convert
     * @return	string	Converted string
     */
    public static function convertToHTMLSC(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
    }

    /** Linked IDs have a colon, it needs to be replaced
     * @param $id
     * @return array|string|string[]|null
     */
    public static function convertID($id) {
        return preg_replace("/:/", "_", $id);
    }

    /** Format a date for display in the diagram
     *
     * @param object $date             The date
     * @param bool $yearOnly    Whether to only show year and not day/month
     * @return string
     */
    public static function formatDate(object $date, bool $yearOnly = false): string
    {
        $date_format = I18N::dateFormat();
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
}