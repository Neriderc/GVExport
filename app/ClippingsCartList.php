<?php
namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\MediaFile;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;

/**
 * Class to read the clippings cart in order to build up the arrays for individuals and families
 */
class ClippingsCartList {

	private array $individuals = [];
	private array $families = [];
	private ClippingsCart $cart;
	private Tree $tree;
	private bool $photoIsRequired;
	private bool $combinedMode;
	private int $dpi;

	public const DUMMY_INDIVIDUAL_XREF	= 'I_';
	public const DUMMY_FAMILIY_XREF		= 'F_';						// what happens if someone is using such a XREF ???
	public const HAS_PARENTS			= 'has_parents';
	public const ID_HUSBAND				= 'husb_id';
	public const ID_WIFE				= 'wife_id';
	public const ID_UNKNOWN				= 'unkn_id';

	/**
	 * constructor for this class
	 *
	 * @param ClippingsCart $clippingsCart
	 * @param bool $photoIsRequired
	 * @param int $dpi
	 * @param bool $combinedMode
	 */
	function __construct(ClippingsCart $clippingsCart, $photoIsRequired, $dpi, $combinedMode) {
		$this->cart = $clippingsCart;
		$this->tree = $clippingsCart->tree;
		$this->dpi = $dpi;
		$this->photoIsRequired = $photoIsRequired;
		$this->combinedMode = $combinedMode;
	}


	/**
	 * Runs main logic and returns lists of indis and fams from the clippings cart for populating the diagram
	 */
	public function getLists(): array
	{
		$this->createListsFromClippingsCart();

		return [
			'individuals' => $this->individuals,
			'families'  => $this->families,
		];
	}


	/**
	 * Read INDI and FAM from the clippings cart and fill the arrays "individuals" and "families"
	 *
	 */
	private function createListsFromClippingsCart () {
		$records = ClippingsCart::getRecordsInCart($this->tree);
		$this->addIndividualsFromClippingsCart($records);
		$this->addFamiliesFromClippingsCart($records);

		foreach ($records as $record) {
			if ($record instanceof Individual) {
				if ($this->combinedMode) {
					$this->enhanceIndividualsList($record);
				}
				[$this->individuals[$record->xref()]['pic'], 
				 $this->individuals[$record->xref()]['pic_title'], 
				 $this->individuals[$record->xref()]['pic_link']] = $this->getPhoto($record->xref());
			} elseif ($record instanceof Family && $this->combinedMode) {
				$this->addDummyPartner($record, self::ID_HUSBAND);
				$this->addDummyPartner($record, self::ID_WIFE);
			}
		}
	}

	/**
	 * if husband or wife are missing for a family we create a dummy one
	 * that is needed for the "combined" mode
	 *
	 * @param Family $family
	 * @param string $partnerType self::ID_HUSBAND or self::ID_WIFE
	 */
	private function addDummyPartner (Family $family, string $partnerType) {
		if ($partnerType == self::ID_HUSBAND) {
			$partner = $family->husband();
		} elseif ($partnerType == self::ID_WIFE) {
			$partner = $family->wife();
		} else {
			return;
		}
		if (isset($partner) && !$this->cart->isXrefInCart($partner->xref())) {
			$fid = $family->xref();
			$pid = self::DUMMY_INDIVIDUAL_XREF.($partnerType == self::ID_HUSBAND ? 'H' : 'W').$fid;
			$this->addIndiToList($pid);
			$this->individuals[$pid]['rel'] = false;
			$this->individuals[$pid]['fams'][$fid] = $fid;
			$this->families[$fid][$partnerType] = $pid;
			$this->families[$fid][self::HAS_PARENTS] = true;
		}
	}

	/**
	 * add information to the arrays "individuals" and "families" about the spouse families or add a dummy spouse family
	 *
	 * @param Individual $individual
	 */
	private function enhanceIndividualsList (Individual $individual) {
		$fams = $individual->spouseFamilies();
		if (count($fams) > 0) {
			foreach ($fams as $family) {
				$fid = $family->xref();
				if (isset($this->families[$fid]['fid']) && ($this->families[$fid]['fid'] == $fid)) {
					$this->addInfoForExistingFamily($individual, $family);
				}
			}
		} else {
			$this->addDummyFamily($individual);
		}
	}
	
	/**
	 * if there is no spouse family we create a dummy one
	 * that is needed for the "combined" mode
	 *
	 * @param Individual $individual
	 */
	private function addDummyFamily (Individual $individual) {
		$pid = $individual->xref();
		$this->addFamToList(self::DUMMY_FAMILIY_XREF.$pid);
		$this->individuals[$pid]['fams'][self::DUMMY_FAMILIY_XREF.$pid] = self::DUMMY_FAMILIY_XREF.$pid;
		$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::HAS_PARENTS] = true;
		if ($individual->sex() == "M") {
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_HUSBAND] = $pid;
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_WIFE] = "";
		} elseif ($individual->sex() == "F") {
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_WIFE] = $pid;
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_HUSBAND] = "";
		} elseif ($individual->sex() == "X") {
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_UNKNOWN] = $pid;
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_WIFE] = "";
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_HUSBAND] = "";
		} else {
			// unknown gender
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_UNKNOWN] = $pid;
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_WIFE] = "";
			$this->families[self::DUMMY_FAMILIY_XREF.$pid][self::ID_HUSBAND] = "";
		}
	}

	/**
	 * add information for an existing family
	 *
	 * @param Individual $individual
	 * @param Family $family
	 */
	private function addInfoForExistingFamily (Individual $individual, Family $family) {
		$pid = $individual->xref();
		$fid = $family->xref();
		$this->individuals[$pid]['fams'][$fid] = $fid;
		if ($family->husband() && $family->husband()->xref() == $pid) {
			$this->families[$fid][self::ID_HUSBAND] = $pid;
		} else {
			$this->families[$fid][self::ID_WIFE] = $pid;
		}
		$this->families[$fid][self::HAS_PARENTS] = true;
	}

	/**
	 * read INDI records from the clippings cart and initially fill the array "individuals"
	 *
	 * @param array $records
	 */
	private function addIndividualsFromClippingsCart (array $records) {
		foreach ($records as $record) {
			if ($record instanceof Individual) {
				$pid = $record->xref();
				$this->addIndiToList($pid);
                $this->individuals[$pid]['rel'] = true;
			}
		}
	}

	/**
	 * read FAM records from the clippings cart and initially fill the array "families"
	 *
	 * @param array $records
	 */
	private function addFamiliesFromClippingsCart (array $records)
	{
		foreach ($records as $record) {
			if ($record instanceof Family) {
				$fid = $record->xref();
				$this->addFamToList($fid);
			}
		}
	}

	/**
	 * add an individual to the individuals list
	 *
	 * @param string $pid XREF of this individual
	 */
	private function addIndiToList(string $pid) {
		if(!isset($this->individuals[$pid])) {
			$this->individuals[$pid] = array();
		}
		$this->individuals[$pid]['pid'] = $pid;
	}

	/**
	 * add a family to the families list
	 *
	 * @param string $fid XREF of this family
	 */
	private function addFamToList(string $fid) {
		if(!isset($this->families[$fid])) {
			$this->families[$fid] = array();
		}
		$this->families[$fid]['fid'] = $fid;
	}


	/**
	 * Adds a path to the highlighted photo of a given individual
	 * if it is in the clippings cart
	 * and if the class parameter defines that photos are required.
	 * External image references are not supported.
	 * 
	 * 9/12/2025 This function had changes made by @stefaz to resolve issues with media files 
	 *
	 * @param string $pid XREF of individual
	 * @return array [URL/path, title, link] or [null, "", null]
	 */
	private function getPhoto(string $pid): array
	{
		if ($this->photoIsRequired) {
			$individual = Registry::individualFactory()->make($pid, $this->tree);
			if ($individual === null) {
				return [null, "", null];
			}
			$mediaFile = $this->preferedPhotoInCart($individual);
			if (isset($mediaFile) && !$mediaFile->isExternal()) {
				$media_title = strip_tags($individual->fullName());
				// If we are rendering in the browser, provide the URL, otherwise provide the server side file location
				if (isset($_REQUEST["render"])) {
					$pic = Site::getPreference('INDEX_DIRECTORY') . $this->tree->getPreference('MEDIA_DIRECTORY') . $mediaFile->filename();
				} else {
					$pic = str_replace("&", "%26", $mediaFile->imageUrl($this->dpi, $this->dpi, "contain"));
				}
				// Get the media object to retrieve the download URL for the link
				$fact = $individual->facts(['OBJE'])->first(static function (Fact $fact): bool {
					$media = $fact->target();
					return $media instanceof Media && $media->firstImageFile() instanceof MediaFile;
				});
				$pic_link = null;
				if ($fact instanceof Fact && $fact->target() instanceof Media) {
					$pic_link = $fact->target()->firstImageFile()->downloadUrl('inline');
				}
				return [$pic, $media_title, $pic_link];
			}
		}
		return [null, "", null];
	}

	/**
	 * find a highlighted media file for an individual;
	 * the media object has to be in the clippings cart
	 *
	 * @param Individual $individual
	 * @return MediaFile|null
	 */
	private function preferedPhotoInCart(Individual $individual): ?MediaFile
	{
		$fact = $individual->facts(['OBJE'])
			->first(static function (Fact $fact): bool {
				$media = $fact->target();

				return $media instanceof Media && $media->firstImageFile() instanceof MediaFile;
			});

		if ($fact instanceof Fact && $fact->target() instanceof Media && $this->cart->isXrefInCart($fact->target()->xref())) {
			return $fact->target()->firstImageFile();
		}

		return null;
	}
}