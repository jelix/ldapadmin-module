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
     * The ldap connector is disabled
     */
    const STATUS_DISABLED = 0;

    /**
     * The ldap connector is enabled
     */
    const STATUS_ENABLED = 1;

    /**
     * @return bool indicate if we can change the status of the ldap connector
     */
    public function canStatusBeChanged(): bool;

    /**
     * Returns the status of the ldap connector.
     *
     * @return int STATUS_DISABLED or STATUS_ENABLED
     */
    public function getStatus(): int;

    /**
     * @param int $status STATUS_DISABLED or STATUS_ENABLED
     * @throw AdapterException if the status cannot be changed
     */
    public function setStatus(int $status);

    /**
     * @return bool if there is a saved configuration or not
     */
    public function hasConfiguration(): bool;

    /**
     * Get the ldap configuration as an array.
     *
     * It should contain parameters for the LdapClient class.
     *
     * If there is not a saved configuration, it should return default parameters or an empty array
     *
     * @return array the configuration or [] if not available
     */
    public function getConfiguration() : array;

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
