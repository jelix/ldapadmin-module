<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2026 Laurent Jouanneau
 * @license MIT
 */

namespace Jelix\LdapAdmin;

class ServerTypeDefaults
{
    public static function getDefaults(string $serverType): array
    {
        return match ($serverType) {
            'openldap' => [
                'port' => 389,
                'tlsMode' => 'starttls',
                'bindAttribute' => 'dn',
                'searchUserBaseDN' => 'ou=people,dc=example,dc=com',
                'searchUserFilter' => '(&(objectClass=inetOrgPerson)(uid=%%LOGIN%%))',
                'searchAttributes' => 'uid:login,givenName:firstname,sn:lastname,displayName:realname,mail:email',
                'featureGroupLoad' => false,
                'searchGroupBaseDN' => 'ou=groups,dc=example,dc=com',
                'searchGroupFilter' => '(&(objectClass=groupOfNames)(member=%%USERDN%%))',
                'searchGroupProperty' => 'cn',
            ],
            'activedirectory' => [
                'port' => 389,
                'tlsMode' => 'starttls',
                'bindAttribute' => 'dn',
                'searchUserBaseDN' => 'DC=corp,DC=example,DC=com',
                'searchUserFilter' => '(&(objectCategory=person)(objectClass=user)(sAMAccountName=%%LOGIN%%)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))',
                'searchAttributes' => 'sAMAccountName:login,givenName:firstname,sn:lastname,displayName:realname,mail:email,userPrincipalName:',
                'featureGroupLoad' => false,
                'searchGroupBaseDN' => 'DC=corp,DC=example,DC=com',
                'searchGroupFilter' => '(&(objectClass=group)(member=%%USERDN%%))',
                'searchGroupProperty' => 'cn',
            ],
            default => [],
        };
    }
}
