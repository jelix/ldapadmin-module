This is a module for Jelix, providing an interface to manage the LDAP configuration for some other modules, like
[multiauth](https://github.com/jelix/multiauth-module/). 

Installation
============

This module is for Jelix 1.8.28 and higher. It is compatible with the multiauth module.

Install files with Jelix 1.8+
-----------------------------

You should use Composer to install the module. Run this commands in a shell:

```
composer require "jelix/ldapadmin-module"
```

Launch the configurator for your application to enable the module

```bash
# for developers of the application
php yourapp/dev.php module:configure ldapadmin
# or for users adding multiauth on a application they just installed:
php yourapp/configure.php ldapadmin

php yourapp/install/installer.php

```

If you are using the master_admin module, you should see a new menu item to access to the configuration form for LDAP.
