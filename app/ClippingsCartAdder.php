<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Location;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Submitter;
use Fisharebest\Webtrees\Media;

/**
 * This class is a service to add items to the clipings cart. Largely it copies from
 * the clippings cart module as the module does not make the functions accessible
 */
class ClippingsCartAdder {

	public Tree $tree;

	/**
	 * @param Tree $tree
	 */
	function __construct(Tree $tree) {
		$this->tree = $tree;
	}

	public function addFamilyToCart(Family $family): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $family->tree()->name();
		$xref = $family->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			foreach ($family->spouses() as $spouse) {
				$this->addIndividualToCart($spouse);
			}

			$this->addLocationLinksToCart($family);
			$this->addMediaLinksToCart($family);
			$this->addNoteLinksToCart($family);
			$this->addSourceLinksToCart($family);
			$this->addSubmitterLinksToCart($family);
		}
	}

	public function addIndividualToCart(Individual $individual): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $individual->tree()->name();
		$xref = $individual->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addLocationLinksToCart($individual);
			$this->addMediaLinksToCart($individual);
			$this->addNoteLinksToCart($individual);
			$this->addSourceLinksToCart($individual);
		}
	}

	protected function addLocationToCart(Location $location): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $location->tree()->name();
		$xref = $location->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addLocationLinksToCart($location);
			$this->addMediaLinksToCart($location);
			$this->addNoteLinksToCart($location);
			$this->addSourceLinksToCart($location);
		}
	}

	protected function addLocationLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d _LOC @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$location = Registry::locationFactory()->make($xref, $record->tree());

			if ($location instanceof Location && $location->canShow()) {
				$this->addLocationToCart($location);
			}
		}
	}

	protected function addMediaToCart(Media $media): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $media->tree()->name();
		$xref = $media->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addNoteLinksToCart($media);
		}
	}

	protected function addMediaLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d OBJE @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$media = Registry::mediaFactory()->make($xref, $record->tree());
			
			if ($media instanceof Media && $media->canShow()) {
				$this->addMediaToCart($media);
			}
		}
	}

	protected function addNoteToCart(Note $note): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $note->tree()->name();
		$xref = $note->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);
		}
	}

	protected function addNoteLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d NOTE @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$note = Registry::noteFactory()->make($xref, $record->tree());

			if ($note instanceof Note && $note->canShow()) {
				$this->addNoteToCart($note);
			}
		}
	}

	protected function addSourceToCart(Source $source): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $source->tree()->name();
		$xref = $source->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addNoteLinksToCart($source);
			$this->addRepositoryLinksToCart($source);
		}
	}

	protected function addSourceLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d SOUR @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$source = Registry::sourceFactory()->make($xref, $record->tree());

			if ($source instanceof Source && $source->canShow()) {
				$this->addSourceToCart($source);
			}
		}
	}


	protected function addRepositoryToCart(Repository $repository): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];

		$tree = $repository->tree()->name();
		$xref = $repository->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addNoteLinksToCart($repository);
		}
	}

	protected function addRepositoryLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d REPO @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$repository = Registry::repositoryFactory()->make($xref, $record->tree());

			if ($repository instanceof Repository && $repository->canShow()) {
				$this->addRepositoryToCart($repository);
			}
		}
	}

	protected function addSubmitterToCart(Submitter $submitter): void
	{
		$cart = Session::get('cart');
		$cart = is_array($cart) ? $cart : [];
		$tree = $submitter->tree()->name();
		$xref = $submitter->xref();

		if (($cart[$tree][$xref] ?? false) === false) {
			$cart[$tree][$xref] = true;

			Session::put('cart', $cart);

			$this->addNoteLinksToCart($submitter);
		}
	}

	protected function addSubmitterLinksToCart(GedcomRecord $record): void
	{
		// Slightly changed regex from what came from webtrees code to not require a new line
		preg_match_all('/\d SUBM @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

		foreach ($matches[1] as $xref) {
			$submitter = Registry::submitterFactory()->make($xref, $record->tree());

			if ($submitter instanceof Submitter && $submitter->canShow()) {
				$this->addSubmitterToCart($submitter);
			}
		}
	}
}