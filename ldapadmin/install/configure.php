<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2023-2026 Laurent Jouanneau
 * @license MIT
 */
use \Jelix\Installer\Module\API\ConfigurationHelpers;
use Jelix\Routing\UrlMapping\EntryPointUrlModifier;
use Jelix\Routing\UrlMapping\MapEntry\MapInclude;

class ldapadminModuleConfigurator extends \Jelix\Installer\Module\Configurator
{

    public function getDefaultParameters()
    {
        return array(
        );
    }

    public function declareUrls(EntryPointUrlModifier $registerOnEntryPoint)
    {
        $registerOnEntryPoint->havingName(
            'admin',
            array(
                new MapInclude('urls.xml')
            )
        );
    }

    public function configure(ConfigurationHelpers $helpers)
    {
        $helpers->declareGlobalWebAssets(
            'ldapadmin',
            array(
                'js' => array('ldapadmin:ldapadmin.js'),
                'css' => array('ldapadmin:ldapadmin.css'),
                'require' => array('jquery_ui'),
            ),
            'common',
            false
        );
    }
}
