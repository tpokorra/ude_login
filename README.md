Universität Duisburg-Essen User Login plugin
=============================================

This plugin allows to specify configuration settings such as IMAP/SMTP
servers, active plugins, etc. on a per-user level. The settings are read
from a static text file matching the login username.

The plugin also offers a way to disable some plugins for certain users.
Since unloading plugins isn't supported by Roundcube itself, this can only
be achieved by letting the ude_login plugin load those plugins which
potentially could be disabled on a per-user basis. Thus, instead of
listing the active plugins in the `plugins` config option, they need
to be listed in the `ude_default_plugins` option.

The user settings file is a tab-separated text file with the following
schema:

```
username1<tab>default_host=imap1.domain.tld<tab>smtp_server=smtp1.domain.tld<tab>enable_plugins=calendar<tab>disable_plugins=acl,managesieve
username2<tab>default_host=imap2.domain.tld<tab>disable_plugins=acl
```

The first column identifies the login username. Following columns contain
any number of <key>=<value> pairs, their order is not relevant. <key> can
correspond to any valid Roundcube config option which is overridden with
the given value. The following special keys are handled separately:

* enable_plugins: a comma-separated list of plugins to load for this user
* disabled_plugins: a comma-separated list of plugins to disable for this user

Note 1: enable_plugins only needs to list plugins which are not part of the
standard set defined in the `plugins` and `ude_default_plugins` options.

Note 2: disabled_plugins can only disable plugins which are loaded through
this plugin, namely those listed in the `ude_default_plugins` option.


Installation
------------

1. Place the contents of the plugin package into a directory named
/<path-to-roundcube>/plugins/ude_login/.

2. Add config options (see below) to the main Roundcube config file.

3. Activate the plugin by adding 'ude_login' to the `plugins` list
in Roundcube's main config file.


Configuration Options
---------------------

* An absolute path to the static text file providing the individual settings
  for the authenticated users. Defaults to 'plugins/ude_login/users.txt'.

  $config['ude_login_db'] = '/path/to/user_settings_db.txt';

* Use the `grep` shell command to read a pre-filtered list of entries from
  the users database. This may increase performance with a very large input file.

  $config['ude_use_grep'] = true;

* The default set of plugins which shall be loaded for each user.
  In order to allow "disabling" plugins for individual users, they cannot
  be added to the regular `plugins` list in Roundcube's config but shall be
  listed here. They'll then be loaded by the ude_login plugin.

  $config['ude_default_plugins'] = array('acl', 'managesieve');


License
-------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses/.
