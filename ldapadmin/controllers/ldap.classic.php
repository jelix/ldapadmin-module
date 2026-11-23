<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2023-2026 Laurent Jouanneau
 * @license MIT
 */

use Jelix\Ldap\Exception;
use Jelix\Ldap\LdapClient;
use Jelix\LdapAdmin\LdapConfigAdapterInterface;
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
     * Show an overview of the ldap configuration with status and action buttons.
     *
     * @return jResponseHtml
     * @throws jException
     */
    public function index()
    {
        /** @var jResponseHtml $rep */
        $rep = $this->getResponse('html');

        $ldapConfAdapter = LdapConfigAdapters::getAdapter();

        $tpl = new jTpl();

        if ($ldapConfAdapter === null) {
            $tpl->assign('hasProvider', false);
        } else {
            $tpl->assign('hasProvider', true);
            $hasConfig = $ldapConfAdapter->hasConfiguration();
            $tpl->assign('hasConfiguration', $hasConfig);
            $tpl->assign('canStatusBeChanged', $ldapConfAdapter->canStatusBeChanged());
            $tpl->assign('status', $ldapConfAdapter->getStatus());

            if ($hasConfig) {
                $config = $ldapConfAdapter->getConfiguration();
                if ($config === array()) {
                    $tpl->assign('hasConfiguration', false);
                } else {
                    $tpl->assign('hostname', $config['hostname'] ?? '');
                    $tpl->assign('port', $config['port'] ?? 389);
                    $tpl->assign('tlsMode', $config['tlsMode'] ?? '');
                    $tpl->assign('adminUserDn', $config['adminUserDn'] ?? '');
                    $tpl->assign('hasAdminPassword', !empty($config['adminPassword']));
                    $tpl->assign('groupSyncEnabled', !empty($config['featureGroupLoad']));

                    list($userSearches, ) = $this->getUserSearchesFromConfig($config);
                    $tpl->assign('userSearchCount', count($userSearches));
                }
            }
        }

        $rep->addAssets('ldapadmin');
        $rep->body->assign('MAIN', $tpl->fetch('index'));
        $rep->body->assign('selectedMenuItem', 'ldap');

        return $rep;
    }

    /**
     * Enable or disable the ldap provider.
     */
    public function setStatus()
    {
        $ldapConfAdapter = LdapConfigAdapters::getAdapter();

        if ($ldapConfAdapter === null || !$ldapConfAdapter->canStatusBeChanged()) {
            jMessage::add(jLocale::get('ldapadmin~main.message.status.cannot.change'), 'error');
            return $this->redirect('ldapadmin~ldap:index');
        }

        $newStatus = (int) $this->param('status', LdapConfigAdapterInterface::STATUS_DISABLED);
        try {
            $ldapConfAdapter->setStatus($newStatus);
            if ($newStatus === LdapConfigAdapterInterface::STATUS_ENABLED) {
                jMessage::add(jLocale::get('ldapadmin~main.message.status.enabled'), 'notice');
            } else {
                jMessage::add(jLocale::get('ldapadmin~main.message.status.disabled'), 'notice');
            }
        } catch (\Jelix\LdapAdmin\AdapterException $e) {
            jMessage::add(jLocale::get('ldapadmin~main.message.status.cannot.change'), 'error');
        }

        return $this->redirect('ldapadmin~ldap:index');
    }

    /**
     * Show the form to edit the configuration of an ldap provider
     *
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
            $isNewForm = !$form;
            if ($isNewForm) {
                $form = jForms::create(self::FORM_SEL, self::FORM_ID);
                $form->setData('ldap_host', isset($config['hostname']) ? $config['hostname'] : '');
                $form->setData('ldap_port', isset($config['port']) ? $config['port'] : 389);
                $form->setData('ldap_tls', isset($config['tlsMode']) ? $config['tlsMode'] : '');
                $form->setData('ldap_admin_dn', isset($config['adminUserDn']) ? $config['adminUserDn'] : '');
                $form->setData('bindAttribute', !empty($config['bindAttribute']) ? $config['bindAttribute'] : 'dn');
                $form->setData('searchGroupEnabled', isset($config['featureGroupLoad']) ? $config['featureGroupLoad'] : false);
                $form->setData('searchGroupFilter', isset($config['searchGroupFilter']) ? $config['searchGroupFilter'] : '');
                $form->setData('searchGroupProperty', isset($config['searchGroupProperty']) ? $config['searchGroupProperty'] : '');
                $form->setData('searchGroupBaseDN', isset($config['searchGroupBaseDN']) ? $config['searchGroupBaseDN'] : '');
                $form->setData(
                    'searchGroupKeepUserInDefaultGroups',
                    !isset($config['searchGroupKeepUserInDefaultGroups']) || $config['searchGroupKeepUserInDefaultGroups']
                );
            }
            // the admin password is never pre-filled, see hasAdminPassword below
            $form->setData('searchHelp', jLocale::get('ldapadmin~main.userSearches.help'));

            $hasAdminPassword = isset($config['adminPassword']) && $config['adminPassword'] !== '';

            $tpl->assign('hasProvider',true);
            $tpl->assign('form', $form);
            $tpl->assign('hasAdminPassword', $hasAdminPassword);

            $nonFormsData = $_SESSION['ldapadmin_form_extra'] ?? null;
            unset($_SESSION['ldapadmin_form_extra']);

            if (!$isNewForm && $nonFormsData) {
                $userSearches = $nonFormsData['userSearches'];
                $extraLdapAttributes = $nonFormsData['extraLdapAttributes'];
                $mappableFields = $ldapConfAdapter->getMappableFields($nonFormsData['searchAttributesMapping']);
            } else {
                list($userSearches, $isConsistent) = $this->getUserSearchesFromConfig($config);
                if (!$isConsistent) {
                    jMessage::add(jLocale::get('ldapadmin~main.message.userSearches.invalidConfig'), 'warning');
                }
                $mapping = LdapClient::parseSearchAttributes($config['searchAttributes'] ?? '');
                $mappableFields = $ldapConfAdapter->getMappableFields($mapping);
                $extraLdapAttributes = $this->getExtraLdapAttributes($mapping);
            }

            $tpl->assign('userSearches', $userSearches);
            $tpl->assign('mappableFields', $mappableFields);
            $tpl->assign('extraLdapAttributes', $extraLdapAttributes);
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

        list($userSearches, $incompleteRows) = $this->getUserSearchesFromRequest();
        $extraLdapAttributes = $this->getFilteredArrayParam('extraLdapAttributes');
        $searchAttributesMap = (array) $this->param('searchAttributesMap', array());

        $valid = $form->check();

        if (!empty($incompleteRows)) {
            $form->setErrorOn('searchHelp', jLocale::get('ldapadmin~main.message.userSearches.incomplete', array(implode(', ', $incompleteRows))));
            $valid = false;
        } elseif (empty($userSearches)) {
            $form->setErrorOn('searchHelp', jLocale::get('ldapadmin~main.message.userSearches.missing'));
            $valid = false;
        }

        if (!$valid) {
            $this->saveNonFormsDataToSession($searchAttributesMap, $extraLdapAttributes);
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
        $config = array_merge($config, $this->collapseUserSearches($userSearches));
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
        return $this->redirect('ldapadmin~ldap:index');
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
        }, LdapClient::TEST_MODE_IGNORE_USER_SEARCH | LdapClient::TEST_MODE_IGNORE_GROUP_SEARCH);
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
        }, LdapClient::TEST_MODE_IGNORE_GROUP_SEARCH);
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
        },  LdapClient::TEST_MODE_IGNORE_GROUP_SEARCH);
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
    private function buildTestResponse($test, $testMode = 0)
    {
        /** @var jResponseJson $rep */
        $rep = $this->getResponse('json');

        $ldapConfAdapter = LdapConfigAdapters::getAdapter();
        if ($ldapConfAdapter === null) {
            $rep->data = array('ok' => false, 'error' => 'No ldap provider is configured.');
            return $rep;
        }

        $config = $this->buildConfigFromRequest($ldapConfAdapter->getConfiguration());
        $ldapClient = new LdapClient($config, null, $testMode);

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

        // incomplete rows are ignored here: the test uses the complete ones
        list($userSearches) = $this->getUserSearchesFromRequest();
        if (!empty($userSearches)) {
            $config = array_merge($config, $this->collapseUserSearches($userSearches));
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
     * Store non-jForms data in the session so config() can restore them
     * after a failed save.
     */
    private function saveNonFormsDataToSession(array $searchAttributesMap, array $extraLdapAttributes)
    {
        $rawBaseDNs = array_values((array) $this->param('searchUserBaseDN', array()));
        $rawFilters = array_values((array) $this->param('searchUserFilter', array()));
        $allUserSearches = array();
        for ($i = 0; $i < max(count($rawBaseDNs), count($rawFilters)); ++$i) {
            $baseDN = $rawBaseDNs[$i] ?? '';
            $filter = $rawFilters[$i] ?? '';
            if ($baseDN === '' && $filter === '') {
                continue;
            }
            $allUserSearches[] = array('baseDN' => $baseDN, 'filter' => $filter);
        }
        if (empty($allUserSearches)) {
            $allUserSearches[] = array('baseDN' => '', 'filter' => '');
        }

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

        $_SESSION['ldapadmin_form_extra'] = array(
            'userSearches' => $allUserSearches,
            'extraLdapAttributes' => $extraLdapAttributes,
            'searchAttributesMapping' => $mapping,
        );
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
     * List of user searches (base DN + filter pairs), in the order LdapClient
     * does them, built from the searchUserBaseDN and searchUserFilter
     * parameters, which may be a string or an array.
     *
     * Like LdapClient, a single value is used for every value of the other
     * parameter. If counts are inconsistent (different and none equals 1),
     * values are paired by index, missing values are empty strings, and
     * the configuration is reported as inconsistent.
     *
     * @return array [ list of ['baseDN'=>string, 'filter'=>string], bool $isConsistent ]
     */
    private function getUserSearchesFromConfig(array $config)
    {
        $baseDNs = $this->toNonEmptyStrings($config['searchUserBaseDN'] ?? '');
        $filters = $this->toNonEmptyStrings($config['searchUserFilter'] ?? '');
        $cb = count($baseDNs);
        $cf = count($filters);

        $isConsistent = true;
        if ($cb == 1 && $cf > 1) {
            $baseDNs = array_fill(0, $cf, $baseDNs[0]);
        } elseif ($cf == 1 && $cb > 1) {
            $filters = array_fill(0, $cb, $filters[0]);
        } elseif ($cb != $cf) {
            $isConsistent = false;
        }

        $searches = array();
        for ($i = 0; $i < max(count($baseDNs), count($filters), 1); ++$i) {
            $searches[] = array(
                'baseDN' => $baseDNs[$i] ?? '',
                'filter' => $filters[$i] ?? '',
            );
        }

        return array($searches, $isConsistent);
    }

    /**
     * User searches typed in the form: searchUserBaseDN[] and
     * searchUserFilter[] values having the same index are on the same row.
     * Empty rows are ignored.
     *
     * @return array [ list of ['baseDN'=>string, 'filter'=>string], list of row numbers (from 1) having only one of the two values ]
     */
    private function getUserSearchesFromRequest()
    {
        $baseDNs = array_values((array) $this->param('searchUserBaseDN', array()));
        $filters = array_values((array) $this->param('searchUserFilter', array()));

        $searches = array();
        $incompleteRows = array();
        for ($i = 0; $i < max(count($baseDNs), count($filters)); ++$i) {
            $baseDN = trim($baseDNs[$i] ?? '');
            $filter = trim($filters[$i] ?? '');
            if ($baseDN === '' && $filter === '') {
                continue;
            }
            if ($baseDN === '' || $filter === '') {
                $incompleteRows[] = $i + 1;
                continue;
            }
            $searches[] = array('baseDN' => $baseDN, 'filter' => $filter);
        }

        return array($searches, $incompleteRows);
    }

    /**
     * Build the searchUserBaseDN and searchUserFilter parameters from the
     * list of user searches. A parameter having the same value on every
     * search is stored as a single string, so the counts rule of LdapClient
     * (same count, or one of them equals 1) is always respected.
     *
     * @param array $searches list of ['baseDN'=>string, 'filter'=>string], not empty
     */
    private function collapseUserSearches(array $searches)
    {
        $collapse = function ($values) {
            return (count(array_unique($values)) == 1) ? $values[0] : $values;
        };

        return array(
            'searchUserBaseDN' => $collapse(array_column($searches, 'baseDN')),
            'searchUserFilter' => $collapse(array_column($searches, 'filter')),
        );
    }

    /**
     * @param string|array $value
     * @return string[] trimmed and non-empty values
     */
    private function toNonEmptyStrings($value)
    {
        $values = array_map('trim', (array) $value);
        return array_values(array_filter($values, function ($v) {
            return $v !== '';
        }));
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
