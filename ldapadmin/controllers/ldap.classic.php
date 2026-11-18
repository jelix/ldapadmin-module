<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2023-2026 Laurent Jouanneau
 * @license MIT
 */

use Jelix\Ldap\Exception;
use Jelix\Ldap\LdapClient;
use Jelix\LdapAdmin\LdapConfigAdapters;

class ldapCtrl extends jController
{
    // Configure access via jacl2 rights management
    public $pluginParams = array(
        '*' => array('jacl2.right' => 'ldapadmin.configuration'),
    );

    const FORM_SEL = 'ldapadmin~ldap';

    const FORM_ID = 'ldapadmin';

    /**
     * List ldap providers.
     *
     * To be implemented later: for now, only one, pre-configured, ldap
     * provider can be edited, through config()/save().
     *
     * @return jResponseHtml
     * @throws jException
     */
    /*public function index()
    {
    }*/

    /**
     * Show the form to edit the configuration of an ldap provider
     * @return jResponseHtml
     * @throws jException
     */
    public function config()
    {
        /** @var jResponseHtml $rep */
        $rep = $this->getResponse('html');

        $ldapConfAdapter = LdapConfigAdapters::getAdapter();

        $tpl = new jTpl();

        if ($ldapConfAdapter === null) {
            $tpl->assign('hasProvider', false);
        } else {
            $config = $ldapConfAdapter->getConfiguration();

            $form = jForms::get(self::FORM_SEL, self::FORM_ID);
            if (!$form) {
                $form = jForms::create(self::FORM_SEL, self::FORM_ID);
            }
            $form->setData('ldap_host', isset($config['hostname']) ? $config['hostname'] : '');
            $form->setData('ldap_port', isset($config['port']) ? $config['port'] : 389);
            $form->setData('ldap_tls', isset($config['tlsMode']) ? $config['tlsMode'] : '');
            $form->setData('ldap_admin_dn', isset($config['adminUserDn']) ? $config['adminUserDn'] : '');
            // the admin password is never pre-filled, see hasAdminPassword below
            $form->setData('searchUserBaseDN', isset($config['searchUserBaseDN']) ? $config['searchUserBaseDN'] : '');
            $form->setData('bindAttribute', !empty($config['bindAttribute']) ? $config['bindAttribute'] : 'dn');
            $form->setData('searchGroupEnabled', isset($config['featureGroupLoad']) ? $config['featureGroupLoad'] : false);
            $form->setData('searchGroupFilter', isset($config['searchGroupFilter']) ? $config['searchGroupFilter'] : '');
            $form->setData('searchGroupProperty', isset($config['searchGroupProperty']) ? $config['searchGroupProperty'] : '');
            $form->setData('searchGroupBaseDN', isset($config['searchGroupBaseDN']) ? $config['searchGroupBaseDN'] : '');
            $form->setData(
                'searchGroupKeepUserInDefaultGroups',
                !isset($config['searchGroupKeepUserInDefaultGroups']) || $config['searchGroupKeepUserInDefaultGroups']
            );

            $hasAdminPassword = isset($config['adminPassword']) && $config['adminPassword'] !== '';

            $tpl->assign('hasProvider',true);
            $tpl->assign('form', $form);
            $tpl->assign('hasAdminPassword', $hasAdminPassword);
            $tpl->assign('searchUserFilters', isset($config['searchUserFilter']) ? $config['searchUserFilter'] : array(''));

            $mapping = LdapClient::parseSearchAttributes($config['searchAttributes'] ?? '');
            $tpl->assign('mappableFields', $ldapConfAdapter->getMappableFields($mapping));
            $tpl->assign('extraLdapAttributes', $this->getExtraLdapAttributes($mapping));
        }

        $rep->addAssets('ldapadmin');
        $rep->body->assign('MAIN', $tpl->fetch('config'));
        $rep->body->assign('selectedMenuItem', 'ldap');

        return $rep;
    }

    /**
     * Check and save the configuration of the ldap provider.
     */
    public function save()
    {
        $ldapConfAdapter = LdapConfigAdapters::getAdapter();
        if ($ldapConfAdapter === null) {
            jMessage::add(jLocale::get('ldapadmin~main.message.no.ldap.provider'), 'error');
            return $this->redirect('ldapadmin~ldap:config');
        }

        $form = jForms::fill(self::FORM_SEL, self::FORM_ID);
        if (!$form) {
            return $this->redirect('ldapadmin:config');
        }

        $searchUserFilter = $this->getFilteredArrayParam('searchUserFilter');
        $extraLdapAttributes = $this->getFilteredArrayParam('extraLdapAttributes');
        $searchAttributesMap = (array) $this->param('searchAttributesMap', array());

        $valid = $form->check();

        if (empty($searchUserFilter)) {
            $form->setErrorOn('searchUserBaseDN', jLocale::get('ldapadmin~main.message.searchUserFilter.missing'));
            $valid = false;
        }

        if (!$valid) {
            jMessage::add(jLocale::get('ldapadmin~main.message.form.invalid'), 'error');
            return $this->redirect('ldapadmin~ldap:config');
        }


        $config = $ldapConfAdapter->getConfiguration();

        $adminPassword = $form->getData('ldap_admin_password');
        if (trim($adminPassword) != '') {
            $config['adminPassword'] = $adminPassword;
        }

        $searchAttributesParts = array();
        foreach ($searchAttributesMap as $field => $ldapAttr) {
            $ldapAttr = trim($ldapAttr);
            if ($ldapAttr === '') {
                continue;
            }
            $searchAttributesParts[] = $ldapAttr.':'.$field;
        }
        foreach ($extraLdapAttributes as $ldapAttr) {
            $searchAttributesParts[] = $ldapAttr.':';
        }

        $config['hostname'] = $form->getData('ldap_host');
        $config['port'] = (int) $form->getData('ldap_port');
        $config['tlsMode'] = $form->getData('ldap_tls');
        $config['adminUserDn'] = $form->getData('ldap_admin_dn');
        $config['searchUserBaseDN'] = $form->getData('searchUserBaseDN');
        $config['searchUserFilter'] = $searchUserFilter;
        $config['bindAttribute'] = trim($form->getData('bindAttribute'));
        $config['searchAttributes'] = implode(',', $searchAttributesParts);
        $config['featureGroupLoad'] = $form->getData('searchGroupEnabled');
        if ($config['featureGroupLoad']) {
            $config['searchGroupFilter'] = $form->getData('searchGroupFilter');
            $config['searchGroupProperty'] = $form->getData('searchGroupProperty');
            $config['searchGroupBaseDN'] = $form->getData('searchGroupBaseDN');
            $config['searchGroupKeepUserInDefaultGroups'] = $form->getData('searchGroupKeepUserInDefaultGroups');
        }

        $ldapConfAdapter->saveConfiguration($config);

        jForms::destroy(self::FORM_SEL, self::FORM_ID);

        jMessage::add(jLocale::get('ldapadmin~main.message.saved'), 'notice');
        return $this->redirect('ldapadmin~ldap:config');
    }



    /**
     * Test the connection to the ldap server with the (possibly unsaved)
     * admin connection settings currently typed in the form.
     */
    public function testConnection()
    {
        $rep = $this->buildTestResponse(function ($ldapClient, $config) {
            $result = $ldapClient->testConnection();
            if ($result['result'] === true) {
                return array('ok' => true);
            }
            if (in_array($result['errorCode'], [ 10, 70, 71, 72])) {
                $message = \jLocale::get('ldapadmin~main.test.connection.error.'.$result['errorCode']);
            }
            else {
                $message = \jLocale::get('ldapadmin~main.test.connection.error.unknown').': '.$result['errorMessage'];
            }
            return array(
                'ok' => false,
                'error' => $message
            );
        });
        return $rep;
    }

    /**
     * Test the user search/mapping with the (possibly unsaved) mapping
     * settings currently typed in the form.
     */
    public function testMapping()
    {
        $login = $this->param('login', '');
        $rep = $this->buildTestResponse(function ($ldapClient, $config) use ($login) {

            $userAttributes = $ldapClient->getUser($login);
            if ($userAttributes === null) {
                return array(
                    'ok' => false,
                    'error' => \jLocale::get('ldapadmin~main.test.user.error.not.found'),
                );
            }

            $userLdapAttributes = $userAttributes->ldapAttributes;
            $extraAttributes = array();
            foreach ($config['searchAttributes'] as $ldapAttr => $fieldName) {
                if ($fieldName === '') {
                    $extraAttributes[$ldapAttr] = isset($userLdapAttributes[$ldapAttr]) ? $userLdapAttributes[$ldapAttr] : '';
                }
            }

            return array(
                'ok' => true,
                'fields' => $userAttributes->userAttributes,
                'extraAttributes' => $extraAttributes,
            );
        });
        return $rep;
    }

    /**
     * Test the identification of a user with the (possibly unsaved)
     * identification settings currently typed in the form.
     */
    public function testIdentification()
    {
        $login = $this->param('login', '');
        $password = $this->param('password', '');
        $rep = $this->buildTestResponse(function (LdapClient $ldapClient, $config) use ($login, $password) {

            $result = $ldapClient->verifyAuthentication($login, $password, false);
            if ($result === false) {
                $error = $ldapClient->getLatestErrorCode();
                if (in_array($error, [ 1, 2, 3, 4 ])) {
                    $message = \jLocale::get('ldapadmin~main.test.auth.error.'.$error);
                }
                else {
                    $message = \jLocale::get('ldapadmin~main.test.auth.error.unknown');
                }
                return array('ok' => false, 'error' => $message);
            }
            return array('ok' => true, 'dn' => $result->dn);
        });
        return $rep;
    }

    /**
     * Test the group retrieval of a user with the (possibly unsaved)
     * group synchronization settings currently typed in the form.
     */
    public function testGroups()
    {
        $login = $this->param('login', '');
        $password = $this->param('password', '');
        $rep = $this->buildTestResponse(function (LdapClient $ldapClient, $config) use ($login, $password) {

            $result = $ldapClient->verifyAuthentication($login, $password, true);
            if ($result === false) {
                $error = $ldapClient->getLatestErrorCode();
                if (in_array($error, [ 1, 2, 3, 4 ])) {
                    $message = \jLocale::get('ldapadmin~main.test.auth.error.'.$error);
                }
                else {
                    $message = \jLocale::get('ldapadmin~main.test.auth.error.unknown');
                }
                return array('ok' => false, 'error' => $message);
            }
            return array('ok' => true, 'groups' => ($result->groups ?: array()));
        });
        return $rep;
    }

    /**
     * @param callable $test function(ldapProvider $provider): array
     * @return jResponseJson
     */
    private function buildTestResponse($test)
    {
        /** @var jResponseJson $rep */
        $rep = $this->getResponse('json');

        $ldapConfAdapter = LdapConfigAdapters::getAdapter();
        if ($ldapConfAdapter === null) {
            $rep->data = array('ok' => false, 'error' => 'No ldap provider is configured.');
            return $rep;
        }

        $config = $this->buildConfigFromRequest($ldapConfAdapter->getConfiguration());
        $ldapClient = new LdapClient($config);

        try {
            $rep->data = $test($ldapClient, $config);
        } catch (Exception $e) {

            if (in_array($e->getCode(), [1, 2, 3, 4, 5, 10, 47, 48, 49, 70, 71, 72])) {
                $message = \jLocale::get('ldapadmin~main.test.connection.error.'.$e->getCode());
            }
            else {
                $message = \jLocale::get('ldapadmin~main.test.connection.error.unknown').': '.$e->getMessage();
            }

            $rep->data = array('ok' => false, 'error' => $message);
        }

        return $rep;
    }

    /**
     * Build a full ldap provider configuration array by overriding the
     * currently saved configuration with the (possibly unsaved) values
     * posted by the edition form, so "test" actions can validate values
     * before they are saved.
     */
    private function buildConfigFromRequest(array $config)
    {
        $host = $this->param('ldap_host', '');
        if ($host !== '') {
            $config['hostname'] = $host;
        }
        $port = $this->param('ldap_port', '');
        if ($port !== '') {
            $config['port'] = (int) $port;
        }
        $config['tlsMode'] = $this->param('ldap_tls', isset($config['tlsMode']) ? $config['tlsMode'] : '');
        $config['adminUserDn'] = $this->param('ldap_admin_dn', isset($config['adminUserDn']) ? $config['adminUserDn'] : '');

        $adminPassword = $this->param('ldap_admin_password', '');
        if (trim($adminPassword) !== '') {
            $config['adminPassword'] = $adminPassword;
        }

        $searchUserBaseDN = $this->param('searchUserBaseDN', '');
        if ($searchUserBaseDN !== '') {
            $config['searchUserBaseDN'] = $searchUserBaseDN;
        }

        $searchUserFilter = $this->getFilteredArrayParam('searchUserFilter');
        if (!empty($searchUserFilter)) {
            $config['searchUserFilter'] = $searchUserFilter;
        }

        $bindAttribute = trim($this->param('bindAttribute', ''));
        if ($bindAttribute !== '') {
            $config['bindAttribute'] = $bindAttribute;
        }

        $searchAttributesMap = (array) $this->param('searchAttributesMap', null);
        if ($searchAttributesMap) {
            $extraLdapAttributes = $this->getFilteredArrayParam('extraLdapAttributes');
            $mapping = array();
            foreach ($searchAttributesMap as $field => $ldapAttr) {
                $ldapAttr = trim($ldapAttr);
                if ($ldapAttr !== '') {
                    $mapping[$ldapAttr] = $field;
                }
            }
            foreach ($extraLdapAttributes as $ldapAttr) {
                $mapping[$ldapAttr] = '';
            }
            $config['searchAttributes'] = $mapping;
        }

        $searchGroupEnabled = $this->param('searchGroupEnabled', null);
        if ($searchGroupEnabled !== null) {
            $config['featureGroupLoad'] = $searchGroupEnabled;
            $config['searchGroupFilter'] = $searchGroupEnabled ? $this->param('searchGroupFilter', '') : '';
            $config['searchGroupProperty'] = $this->param('searchGroupProperty', '');
            $config['searchGroupBaseDN'] = $this->param('searchGroupBaseDN', '');
            $searchGroupKeepUserInDefaultGroups = $this->param('searchGroupKeepUserInDefaultGroups', null);
            if ($searchGroupKeepUserInDefaultGroups !== null) {
                $config['searchGroupKeepUserInDefaultGroups'] = (bool) $searchGroupKeepUserInDefaultGroups;
            }
        }

        return $config;
    }

    /**
     * Ldap attributes fetched from the ldap directory but not mapped to any
     * DAO field.
     */
    private function getExtraLdapAttributes($mapping)
    {
        $extra = array();
        foreach ($mapping as $ldapAttr => $field) {
            if ($field === '') {
                $extra[] = $ldapAttr;
            }
        }
        return $extra;
    }

    /**
     * Read an array-valued request parameter (coming from several inputs
     * sharing the same `name[]`), trimmed and with empty values removed.
     */
    private function getFilteredArrayParam($name)
    {
        $values = (array) $this->param($name, array());
        $values = array_map('trim', $values);
        return array_values(array_filter($values, function ($v) {
            return $v !== '';
        }));
    }
}
