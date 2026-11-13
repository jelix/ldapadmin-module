<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2026 Laurent Jouanneau
 * @licence   MIT
 */
namespace Jelix\LdapAdmin;

class LdapConfigAdapters
{
    public static function getAdapter() : ?LdapConfigAdapterInterface
    {
        if (\jApp::isModuleEnabled('multiauth')) {
            return new MultiAuthAdapter();
        }
        return null;
    }
}