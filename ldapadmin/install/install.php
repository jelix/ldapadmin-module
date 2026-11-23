<?php
/**
 * @author    Laurent Jouanneau
 * @copyright   2023 Laurent Jouanneau
 * @license MIT
 */
use Jelix\IniFile\IniModifierInterface;
use Jelix\Installer\Module\API\DatabaseHelpers;
use Jelix\Installer\Module\API\InstallHelpers;
use Jelix\Installer\EntryPoint;

class ldapadminModuleInstaller extends \Jelix\Installer\Module\Installer
{
    public function install(InstallHelpers $helpers)
    {
        jAcl2DbManager::createRightGroup('ldapadmin', 'ldapadmin~main.acl.grp');
        jAcl2DbManager::createRight('ldapadmin.configuration', 'ldapadmin~main.acl.config', 'ldapadmin');
        jAcl2DbManager::addRight('admins', 'ldapadmin.configuration');
    }
}
