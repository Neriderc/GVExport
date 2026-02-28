<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\GedcomRecord;

/**
 * Class to read the indis and fams from the clippings card
 */
class ClippingsCartListBuilder
{
	/** @var array<mixed> */
	private array $individuals = [];
	/** @var array<mixed> */
	private array $families = [];
	private Tree $tree;

	/**
	 * @param ClippingsCart $clippingsCart
	 */
	function __construct(ClippingsCart $clippingsCart)
	{
		$this->tree = $clippingsCart->tree;
	}


	/**
	 * Runs main logic and returns lists of indis and fams from the clippings cart for populating the diagram
	 * 
	 * @return array<mixed>
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
	private function createListsFromClippingsCart(): void
	{
		$records = ClippingsCart::getRecordsInCart($this->tree);
		$this->addIndividualsFromClippingsCart($records);
		$this->addFamiliesFromClippingsCart($records);
	}

	/**
	 * read INDI records from the clippings cart and initially fill the array "individuals"
	 *
	 * @param array<GedcomRecord> $records
	 */
	private function addIndividualsFromClippingsCart(array $records): void
	{
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
	 * @param array<GedcomRecord> $records
	 */
	private function addFamiliesFromClippingsCart(array $records): void
	{
		foreach ($records as $record) {
			if ($record instanceof Family) {
				$this->addFamToList($record);
			}
		}
	}

	/**
	 * add an individual to the individuals list
	 *
	 * @param GedcomRecord $record The clippings cart record
	 */
	private function addIndiToList($record): void
	{
		$pid = $record instanceof Individual ? $record->xref() : $record;

		if (!isset($this->individuals[$pid])) {
			$this->individuals[$pid] = array();
		}

		$this->individuals[$pid]['pid'] = $pid;
		$this->individuals[$pid]['record'] = $record;
	}

	/**
	 * add a family to the families list
	 *
	 * @param GedcomRecord $record The clippings cart record
	 */
	private function addFamToList($record): void
	{
		$fid = $record->xref();
		if (!isset($this->families[$fid])) {
			$this->families[$fid] = array();
		}
		$this->families[$fid]['fid'] = $fid;
		$this->families[$fid]['record'] = $record;
	}
}
