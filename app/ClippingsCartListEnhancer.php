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
class ClippingsCartListEnhancer {

	private array $individuals = [];
	private array $families = [];
	private ClippingsCart $cart;
	private Tree $tree;
	private bool $photoIsRequired;
	private bool $combinedMode;
	private int $dpi;

	/**
	 * constructor for this class
	 *
	 * @param ClippingsCart $clippingsCart
	 * @param bool $photoIsRequired
	 * @param int $dpi
	 * @param bool $combinedMode
	 */
	function __construct(ClippingsCart $clippingsCart, array $lists, $photoIsRequired, $dpi, $combinedMode) {
		$this->cart = $clippingsCart;
		$this->tree = $clippingsCart->tree;
		$this->dpi = $dpi;
		$this->photoIsRequired = $photoIsRequired;
		$this->combinedMode = $combinedMode;
		$this->individuals = $lists['individuals'];
		$this->families = $lists['families'];
	}

	/**
	 * Fill the arrays "individuals" and "families" with their relevant data
	 *
	 */
	public function enhance () {
		foreach ($this->individuals as $indi) {
			$record = $indi['record'];
			if ($this->combinedMode) {
				$this->enhanceIndividualsList($record);
			}
			[$this->individuals[$record->xref()]['pic'], 
				$this->individuals[$record->xref()]['pic_title'], 
				$this->individuals[$record->xref()]['pic_link']] = $this->getPhoto($record->xref());
		}


		return [
			'individuals' => $this->individuals,
			'families'  => $this->families,
		];
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
		$this->addFamToList(Dot::DUMMY_FAMILIY_XREF.$pid);
		$this->individuals[$pid]['fams'][Dot::DUMMY_FAMILIY_XREF.$pid] = Dot::DUMMY_FAMILIY_XREF.$pid;
		$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::HAS_PARENTS] = true;
		if ($individual->sex() == "M") {
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_HUSBAND] = $pid;
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_WIFE] = "";
		} elseif ($individual->sex() == "F") {
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_WIFE] = $pid;
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_HUSBAND] = "";
		} elseif ($individual->sex() == "X") {
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_UNKNOWN] = $pid;
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_WIFE] = "";
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_HUSBAND] = "";
		} else {
			// unknown gender
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_UNKNOWN] = $pid;
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_WIFE] = "";
			$this->families[Dot::DUMMY_FAMILIY_XREF.$pid][Dot::ID_HUSBAND] = "";
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
			$this->families[$fid][Dot::ID_HUSBAND] = $pid;
		} else {
			$this->families[$fid][Dot::ID_WIFE] = $pid;
		}
		$this->families[$fid][Dot::HAS_PARENTS] = true;
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

		if ($fact instanceof Fact && $fact->target() instanceof Media && ClippingsCart::isXrefInCart($this->tree, $fact->target()->xref())) {
			return $fact->target()->firstImageFile();
		}

		return null;
	}
}