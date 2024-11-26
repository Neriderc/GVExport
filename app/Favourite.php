<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Module\FamilyTreeFavoritesModule;
use Fisharebest\Webtrees\Module\UserFavoritesModule;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;


/**
 * GVExport representation of a webtrees Favourite
 */
class Favourite
{
    public const TYPE_USER_FAVOURITE = 'USER_FAVOURITE';
    public const TYPE_TREE_FAVOURITE = "TREE_FAVOURITE";
    private string $type;

    /**
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Add a favourite to webtrees
     *
     * @param $tree
     * @param $url
     * @param $title
     * @return bool
     */
    public function addFavourite($tree, $url, $title): bool
    {
        switch ($this->type) {
            case self::TYPE_USER_FAVOURITE:
                return $this->addUserFavourite($tree, $url, $title);
            case self::TYPE_TREE_FAVOURITE:
                return $this->addTreeFavourite($tree, $url, $title);
            default:
                return false;
        }
    }

    /**
     * Add a User favourite to webtrees
     *
     * @param Tree $tree
     * @param string $url
     * @param string $title
     * @return bool
     */
    private function addUserFavourite(Tree $tree, string $url, string $title): bool
    {
        $note = "";
        $user = Auth::user();
        $favorite = function ($tree, $user, $url, $title, $note) {
            return Registry::container()->get(UserFavoritesModule::class)->addUrlFavorite($tree, $user, $url, $title, $note);
        };
        $favorite->call(Registry::container()->get(UserFavoritesModule::class), $tree, $user, $url, $title, $note);
        return true;
    }

    /**
     * Add a Tree favourite to webtrees
     *
     * @param Tree $tree
     * @param string $url
     * @param string $title
     * @return bool
     */
    private function addTreeFavourite(Tree $tree, string $url, string $title): bool
    {
        $note = "";
        $favorite = function ($tree, $url, $title, $note) {
            return Registry::container()->get(FamilyTreeFavoritesModule::class)->addUrlFavorite($tree, $url, $title, $note);
        };
        $favorite->call(Registry::container()->get(FamilyTreeFavoritesModule::class), $tree, $url, $title, $note);
        return true;
    }
}