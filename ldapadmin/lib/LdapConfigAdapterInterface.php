<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2026 Laurent Jouanneau
 * @licence   MIT
 */
namespace Jelix\LdapAdmin;

interface LdapConfigAdapterInterface
{
    /**
     * Get the ldap configuration as an array.
     *
     * It should contain parameters for the LdapClient class.
     *
     * @return array|null the configuration or null if not available
     */
    public function getConfiguration() : ?array;

    /**
     * save the given ldap configuration
     *
     * @param array $configuration
     * @return bool true if it has been saved with success
     */
    public function saveConfiguration(array $configuration): bool;

    /**
     *
     * @param array $mapping searchAttributes config: ldap attribute => field name
     * @return array list of ['ref'=>string, 'label'=>string, 'ldapAttr'=>string]
     */
    public function getMappableFields($searchAttributesMapping): array;
}