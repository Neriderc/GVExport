<?php

namespace vendor\WebtreesModules\gvexport;

/**
 * The current user
 */
class User
{


    /**
     * Check if current user is logged in
     *
     * @return bool
     */
    public static function isUserLoggedIn(): bool
    {
        if (Auth::user()->id() == Settings::GUEST_USER_ID) {
            return false;
        } else {
            return true;
        }
    }
}