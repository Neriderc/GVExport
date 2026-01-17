<?php
namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Tree;

/**
 * Class to read the indis and fams from the clippings card
 */
class ClippingsCartListBuilder {

	private array $individuals = [];
	private array $families = [];
	private Tree $tree;
	private bool $combinedMode;

	/**
	 * @param ClippingsCart $clippingsCart
	 */
	function __construct(ClippingsCart $clippingsCart, bool $combinedMode) {
		$this->tree = $clippingsCart->tree;
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
				$this->addIndiToList($record);
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
				$this->addFamToList($record);
				if ($this->combinedMode) {
					$this->addDummyPartner($record, Dot::ID_HUSBAND);
					$this->addDummyPartner($record, Dot::ID_WIFE);
				}
			}
		}
		
	}

	/**
	 * if husband or wife are missing for a family we create a dummy one
	 * that is needed for the "combined" mode
	 *
	 * @param Family $family
	 * @param string $partnerType Dot::ID_HUSBAND or Dot::ID_WIFE
	 */
	private function addDummyPartner(Family $family, string $partnerType)
	{
		if ($partnerType == Dot::ID_HUSBAND) {
			$partner = $family->husband();
		} elseif ($partnerType == Dot::ID_WIFE) {
			$partner = $family->wife();
		} else {
			return;
		}
		if (isset($partner) && !ClippingsCart::isXrefInCart($this->tree, $partner->xref())) {
			$fid = $family->xref();
			$pid = Dot::DUMMY_INDIVIDUAL_XREF . ($partnerType == Dot::ID_HUSBAND ? 'H' : 'W') . $fid;
			$this->addIndiToList($pid);
			$this->individuals[$pid]['rel'] = false;
			$this->individuals[$pid]['fams'][$fid] = $fid;
			$this->families[$fid][$partnerType] = $pid;
			$this->families[$fid][Dot::HAS_PARENTS] = true;
		}
	}

	/**
	 * add an individual to the individuals list
	 *
	 * @param string $pid XREF of this individual
	 */
	private function addIndiToList($record) {
		$pid = $record instanceof Individual ? $record->xref() : $record;
		
		if(!isset($this->individuals[$pid])) {
			$this->individuals[$pid] = array();
		}
		
		$this->individuals[$pid]['pid'] = $pid;
		$this->individuals[$pid]['record'] = $record;
	}

	/**
	 * add a family to the families list
	 *
	 * @param string $fid XREF of this family
	 */
	private function addFamToList($record) {
		$fid = $record->xref();
		if(!isset($this->families[$fid])) {
			$this->families[$fid] = array();
		}
		$this->families[$fid]['fid'] = $fid;
		$this->families[$fid]['record'] = $record;
	}
}