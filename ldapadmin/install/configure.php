<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2023 Laurent Jouanneau
 * @license MIT
 */
use \Jelix\Installer\Module\API\ConfigurationHelpers;
use Jelix\Routing\UrlMapping\EntryPointUrlModifier;

class ldapadminModuleConfigurator extends \Jelix\Installer\Module\Configurator
{

    public function getDefaultParameters()
    {
        return array(
        );
    }

    /*public function declareUrls(EntryPointUrlModifier $registerOnEntryPoint)
    {

    }*/

    public function configure(ConfigurationHelpers $helpers)
    {
        /*
         $helpers->declareGlobalWebAssets(
            'ldapadmin',
            array(
                'css' => array('design/ldapadmin.css'),
                'js' => array(
                    'js/ldapadmin.js'
                ),
                'require' => array(
                    'jquery_ui'
                )
            ),
            'common',
            false
        );
         */

    }
}
