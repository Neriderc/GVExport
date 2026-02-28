<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Module\FamilyTreeFavoritesModule;
use Fisharebest\Webtrees\Module\UserFavoritesModule;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\LinkedRecordService;

/**
 * Container for shared note style data
 */
class SharedNoteList
{
    /** @var array<string,string> */
    private array $indi_style = [];
    private string $default_col;


    /**
     * Initiate the SharedNotesList object
     *
     * @param string $json The shared notes saved JSON
     * @param Tree $tree  The webtrees tree
     * @param $default_col
     */
    public function __construct(string $json, Tree $tree, string $default_col)
    {
        $data = json_decode($json, true);
        if (!empty($data)) {
            foreach (array_reverse($data) as $element) {
                $note_xref = $element['xref'];
                $bg_col = $element['bg_col'];

                $linkedrecordservice = new LinkedRecordService();

                $note = Registry::noteFactory()->make($note_xref, $tree);
                $indis =  $linkedrecordservice->linkedIndividuals($note)->toArray();
                foreach ($indis as $full_xref) {
                    $xref = $full_xref;
                    $pos = strpos($full_xref, '@');
                    if ($pos !== false) {
                        $xref = substr($xref, 0, $pos);
                    }
                    $this->indi_style[$xref] = $bg_col;
                }
            }
        }
        $this->default_col = $default_col;
    }

    /**
     * Returns whether the individual has a custom shared note colour set
     *
     * @param string $xref
     * @return bool
     */
    public function indiHasSharedNote(string $xref): bool
    {
        return !empty($this->indi_style[$xref]);
    }

    /**
     * Returns the appropriate background colour for this individual based on the shared note settings
     *
     * @param string $xref
     * @return mixed|string
     */
    public function getSharedNoteColour(string $xref)
    {
        if (!empty($this->indi_style[$xref])) {
            return $this->indi_style[$xref];
        } else {
            return $this->default_col;
        }
    }
}
