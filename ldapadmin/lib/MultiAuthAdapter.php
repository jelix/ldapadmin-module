<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2026 Laurent Jouanneau
 * @licence   MIT
 */
namespace Jelix\LdapAdmin;

use Jelix\IniFile\IniModifier;
use Jelix\Ldap\LdapClient;
use Jelix\MultiAuth\LdapProviderConfig;
use Jelix\MultiAuth\ProvidersConfigurations;

/**
 * Adapter for the multiauth module
 */
class MultiAuthAdapter implements LdapConfigAdapterInterface
{
    public function getConfiguration() : ?array
    {
        $multiauthConfig = ProvidersConfigurations::loadFromApplication();
        foreach($multiauthConfig->getProvidersConfig() as $providerConf) {
            if ($providerConf->providerName != 'ldap') {
                continue;
            }

            return LdapProviderConfig::loadFullConfiguration($providerConf->configuration);
        }
        return null;
    }

    public function saveConfiguration(array $configuration): bool
    {
        $originalConfiguration = $this->getConfiguration();
        $section = 'ldap:'.$originalConfiguration['ldapprofile'];
        $profilesIni = new IniModifier(\jApp::varConfigPath('profiles.ini.php'));
        $extraParameters = array('searchGroupKeepUserInDefaultGroups');

        foreach($configuration as $param => $value) {
            if (isset(LdapClient::defaultParameters[$param]) || in_array($param, $extraParameters)) {
                $profilesIni->setValue($param, $value, $section);
            }
        }
        $profilesIni->save();
        return true;
    }

    /**
     * The DAO fields the searchAttributes mapping can be configured for,
     * built from the controls of the configured user-creation form,
     * optionally restricted by the "daoPropertiesForMapping" ldap provider
     * configuration option (comma-separated list of field names).
     *
     * @param array $searchAttributesMapping current searchAttributes config: ldap attribute => field name
     * @return array list of ['ref'=>string, 'label'=>string, 'ldapAttr'=>string]
     */
    public function getMappableFields($searchAttributesMapping): array
    {
        $configuration = $this->getConfiguration();
        if ($configuration['accountsDaoProfile'] == '') {
            return array();
        }

        $reverseMapping = array();
        foreach ($searchAttributesMapping as $ldapAttr => $field) {
            if ($field !== '') {
                $reverseMapping[$field] = $ldapAttr;
            }
        }

        $allowedFields = null;
        if (!empty($configuration['daoPropertiesForMapping'])) {
            $allowedFields = array_map('trim', explode(',', $configuration['daoPropertiesForMapping']));
        }

        $tmpId = '___ldapadmin_mapping_tmp___';
        $form = \jForms::create($configuration['accountsFormSelector'], $tmpId);
        if (!$form) {
            $form = \jForms::get($configuration['accountsFormSelector'], $tmpId);
        }
        $fields = array();
        if ($form) {
            foreach ($form->getControls() as $ref => $control) {
                if (!$control->type || in_array($control->type, array('submit', 'reset', 'hidden', 'secret', 'secretconfirm', 'captcha'))) {
                    continue;
                }
                if ($allowedFields !== null && !in_array($ref, $allowedFields)) {
                    continue;
                }
                $fields[] = array(
                    'ref' => $ref,
                    'label' => $control->label,
                    'ldapAttr' => isset($reverseMapping[$ref]) ? $reverseMapping[$ref] : '',
                );
            }
            \jForms::destroy($configuration['accountsFormSelector'], $tmpId);
        }
        return $fields;
    }
}