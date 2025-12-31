<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;

/**
 * Class to access the webtrees clippings cart
 */
class ClippingsCart {

	public Tree $tree;

	/**
	 * @param Tree $tree
	 */
	function __construct(Tree $tree) {
		$this->tree = $tree;
	}
    
    /**
	 * Check if clippings cart of a tree is empty
	 *
	 * @param Tree $tree
	 * @return bool
	 */
	public static function isCartEmpty(Tree $tree): bool
	{
		$cart     = Session::get('cart', []);
		$contents = $cart[$tree->name()] ?? [];

		return $contents === [];
	}

	/**
	 * Are any of the cart records an individual?
	 *
	 * @param Tree $tree
	 * @return bool
	 */
	public static function hasIndividuals(Tree $tree): bool
	{
		if (!self::isCartEmpty($tree)) {
			$records = self::getRecordsInCart($tree);
			foreach ($records as $record) {
				if ($record instanceof Individual) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get the XREFs in the clippings cart
	 *
	 * @param Tree $tree
	 *
	 * @return array of XREFs
	 */
	private static function getXrefsInCart(Tree $tree): array
	{
		$cart = Session::get('cart', []);
		$xrefs = array_keys($cart[$tree->name()] ?? []);
		// PHP converts numeric keys to integers, so we convert the XREFs to strings in case 
        // some are just numbers that got implicitly converted 
		return array_map('strval', $xrefs);
	}

	/**
	 * Count the XREFs in the clippings cart
	 *
	 * @param Tree $tree
	 *
	 * @return int Count of xrefs
	 */
	static function countXrefsInCart(Tree $tree): int
	{
		$cart = Session::get('cart', []);
		$xrefs = array_keys($cart[$tree->name()] ?? []);
		return sizeof($xrefs);
	}

	/**
	 * Get the records in the clippings cart
	 *
	 * @param Tree $tree
	 *
	 * @return array
	 */
	public static function getRecordsInCart(Tree $tree): array
	{
		$xrefs = self::getXrefsInCart($tree);
		$records = array_map(static function (string $xref) use ($tree): ?GedcomRecord {
			return Registry::gedcomRecordFactory()->make($xref, $tree);
		}, $xrefs);

		// some records may have been deleted after they were added to the cart, remove them
		$records = array_filter($records);

		// group and sort the records
		uasort($records, static function (GedcomRecord $x, GedcomRecord $y): int {
			return $x->tag() <=> $y->tag() ?: GedcomRecord::nameComparator()($x, $y);
		});

		return $records;
	}

	/**
	 * check if a XREF is an element in the clippings cart
	 *
	 * @param string $xref
	 * @return bool
	 */
	public function isXrefInCart(string $xref): bool
	{
		return in_array($xref, $this->getXrefsInCart($this->tree), true);
	}
}