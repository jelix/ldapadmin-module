<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2026 Laurent Jouanneau
 * @licence   MIT
 */
namespace Jelix\LdapAdmin;

use Jelix\MultiAuth\LdapProviderConfig;
use Jelix\MultiAuth\ProvidersConfigurations;
use Jelix\MultiAuth\ProviderConfiguration;

/**
 * Adapter for the multiauth module
 */
class MultiAuthAdapter implements LdapConfigAdapterInterface
{

    protected ProviderConfiguration $providerConfig;

    protected ProvidersConfigurations $multiAuthProviders;

    public function __construct()
    {
        $this->multiAuthProviders = ProvidersConfigurations::loadFromApplication();
        $this->providerConfig = $this->multiAuthProviders->getLdapProviderConfig(true);
    }

    /**
     * @return bool
     */
    public function hasConfiguration(): bool
    {
        return ($this->providerConfig && $this->providerConfig->providerConfigExists && LdapProviderConfig::hasConfiguration($this->providerConfig->configuration));
    }

    public function getConfiguration() : array
    {
        if ($this->providerConfig && $this->providerConfig->providerConfigExists) {
            try {
                return LdapProviderConfig::loadFullConfiguration($this->providerConfig->configuration);
            } catch(\Exception $e) {
                // error during the configuration load: no ldapprofile
                return [];
            }
        }
        return [];
    }

    public function saveConfiguration(array $configuration): bool
    {
        $this->providerConfig->configuration = $configuration;
        $this->providerConfig = $this->multiAuthProviders->setLdapProviderConfig($this->providerConfig);
        $this->multiAuthProviders = ProvidersConfigurations::loadFromApplication();
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
        if ($configuration == null || $configuration['accountsDaoProfile'] == '') {
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

    /**
     * @return bool true if the ldap connector can be changed
     */
    public function canStatusBeChanged(): bool
    {
        return ($this->providerConfig && $this->providerConfig->providerConfigExists);
    }

    /**
     * @return int one of the const STATUS_*
     */
    public function getStatus(): int
    {
        return ($this->providerConfig && $this->providerConfig->providerConfigExists && $this->providerConfig->isEnabled()) ? LdapConfigAdapterInterface::STATUS_ENABLED : LdapConfigAdapterInterface::STATUS_DISABLED;
    }

    /**
     * @param int $status one of the const STATUS_*
     */
    public function setStatus(int $status)
    {
        if (!$this->providerConfig || !$this->providerConfig->providerConfigExists) {
            throw new AdapterException("Status cannot be changed", 1);
        }

        $this->providerConfig->configuration['enabled'] = ($status != LdapConfigAdapterInterface::STATUS_DISABLED);
        $this->providerConfig = $this->multiAuthProviders->setLdapProviderConfig($this->providerConfig);
        $this->multiAuthProviders = ProvidersConfigurations::loadFromApplication();
    }
}
