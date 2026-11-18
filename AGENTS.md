# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Jelix package (`jelix/ldapadmin-module`, Jelix 1.8.28+, PHP >= 8.2) providing one Jelix module, `ldapadmin`: an admin UI
(under `master_admin`) to configure, through a web form, an LDAP client implemented by `\Jelix\Ldap\LdapClient`
(package `jelix/ldap-client`).

That LDAP client is configured differently depending on which module uses it, so `ldapadmin` never reads or writes
the configuration itself: it goes through an adapter. Each module using `LdapClient` should have an adapter. Currently
only the `multiauth` module has one (its ldap settings are stored in an INI file).

## Running tests

Tests need a full Jelix application (`tests/testapp/`) plus an OpenLDAP server, run with Docker Compose from `tests/`.
The repository root is mounted at `/jelixapp/` in the containers.

```bash
cd tests
./run-docker build              # first time only
./run-docker                    # start containers (app at http://localhost:8846 or http://ldapadmin.local:8846)
./appctl ldapreset              # (re)populate the test ldap directory
./appctl install-module-local   # configure + install the ldapadmin module into the test app
```

Other `./appctl <cmd>` commands: `reset`, `clean_tmp`, `install` (Jelix installer), `rights`, `composer-install`,
`composer-update`, `shell` / `shellroot` (php container), `ldapusers` (list ldap test users), `ldapreset` (restore the
default ldap users), `ldapshell` (ldap container).

Test accounts: `admin`/`admin` (db account), `john`/`passjohn` (ldap, in groups `group1` and `group2`),
`jane`/`passjane` (ldap, in group `group1`).

Run the PHPUnit suite with `./appctl unittests`. It runs `vendor/bin/phpunit` in the php container, from
`/jelixapp/tests/unittests/` (which has its own `composer.json`/`vendor/` and `phpunit.xml`). Extra arguments go
to phpunit, and paths are relative to `tests/unittests/`:

```bash
./appctl unittests ldapadmin/fooTest.php
./appctl unittests --filter testSomething
```

Tests go in `tests/unittests/ldapadmin/` (it holds only `.dummy` for now). `bootstrap.php` boots
`tests/testapp/application.init.php` with the `jelixtests` environment. It does not autoload the module on its own.

`tests/` is left out of the distributed package (`.gitattributes` has `tests export-ignore`).

## Architecture

- **Menu & access**: `ldapadmin/classes/ldapadmin.listener.php` (`onmasteradminGetMenuContent`) adds the menu item in
  `master_admin`. The item and every action of `ldapCtrl` are gated by the jAcl2 right `ldapadmin.configuration`.
  `install/install.php` creates that right (and gives it to the `admins` group) and declares the `ldapadmin` web assets
  (`www/ldapadmin.js`/`.css`, which require `jquery_ui`). `install/configure.php` is an empty configurator.
- **Adapters** (`ldapadmin/lib/`, namespace `Jelix\LdapAdmin`, autoloaded through `module.xml`):
  `LdapConfigAdapters::getAdapter()` returns the adapter to use (currently `MultiAuthAdapter` if `multiauth` is
  enabled, otherwise `null`, which the controller must handle). Adapters implement `LdapConfigAdapterInterface`:
  `getConfiguration()`, `saveConfiguration()`, `getMappableFields()`. To support another module, add an adapter and
  select it in `getAdapter()`.
- **MultiAuthAdapter**: it reads the first multiauth provider whose `providerName` is `ldap`, through
  `LdapProviderConfig::loadFullConfiguration()`. It saves into the `[ldap:<ldapprofile>]` section of
  `var/config/profiles.ini.php` with `IniModifier`. Only keys in `LdapClient::defaultParameters` (plus
  `searchGroupKeepUserInDefaultGroups`) are written. `getMappableFields()` takes the mappable fields from the controls
  of the multiauth account-creation jForms (`accountsFormSelector`), optionally narrowed by `daoPropertiesForMapping`.
- **Controller** `ldapadmin/controllers/ldap.classic.php`: `config()` shows the jForms form
  (`forms/ldap.form.xml`, scalar settings) in `templates/config.tpl`. `save()` saves through the adapter.
  Array-valued settings and the searchAttributes-mapping UI are plain HTML inputs read by hand
  (`$this->param(...)`), not jForms controls: jForms has no working repeat construct. Repeatable lists
  (`.repeat-list` + `<template class="repeat-list-template">`, handled in `www/ldapadmin.js`) are used for
  `extraLdapAttributes[]` and for the "user searches" table. Each row of that table is a `searchUserBaseDN[]` +
  `searchUserFilter[]` pair. `getUserSearchesFromConfig()` expands the config into rows, as `LdapClient` does, and
  `collapseUserSearches()` saves a column holding a single repeated value as a string. That way the LdapClient rule
  (same count, or one of them equals 1) always holds.
  `testConnection()`/`testMapping()`/`testIdentification()`/`testGroups()` are JSON (AJAX) actions. They check the
  values currently typed in the form, which aren't saved yet: `buildConfigFromRequest()` merges them into the adapter
  configuration and builds a temporary `LdapClient`.
- `index()` (to list the ldap providers) is commented out on purpose. Only one preconfigured provider can be edited for now.

## Versioning

When releasing, keep three places in sync: `VERSION`, the `<version>` in `ldapadmin/module.xml`, and `CHANGELOG.md`.
