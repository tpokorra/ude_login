<?php

/**
 * University Duisburg-Essen User Login Plugin
 *
 * Allows to specify configuration settings such as IMAP/SMTP servers, active plugins, etc.
 * on a per-user level. The settings are read from a static text file with the login username
 * being stated in the first column.
 *
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 * @license GNU GPLv3+
 *
 * Copyright (C) 2014, Kolab Systems AG <contact@kolabsys.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 */

class ude_login extends rcube_plugin
{
    private $userconfig;

    /**
     * Plugin initialization
     */
    public function init()
    {
        $this->add_hook('authenticate', array($this, 'authenticate'));

        // load plugin configuration
        $this->load_config();

        // load user-specific config from session data
        $this->startup(array());
    }

    /**
     * Callback for application startup
     */
    public function startup($args)
    {
        $rcmail = rcube::get_instance();

        if (is_array($_SESSION['ude_config']) && $rcmail->task != 'logout') {
            $this->userconfig = $_SESSION['ude_config'];

            // apply user-specific settings to config
            foreach ($this->userconfig as $prop => $value) {
                if (strpos($prop, 'plugins') === false) {
                    $rcmail->config->set($prop, $value);
                }
            }

            // get the list of default plugins to be loaded for each user
            $user_plugins = $rcmail->config->get('ude_default_plugins', array());

            // enable plugins
            if (!empty($this->userconfig['enable_plugins'])) {
                foreach ((array)$this->userconfig['enable_plugins'] as $plugin) {
                    if (!in_array($plugin, $user_plugins)) {
                        $user_plugins[] = $plugin;
                    }

                    // add support for Kolab plugins
                    if ($plugin == 'calendar') {
                        $rcmail->config->set('calendar_enabled', true);
                    }
                    else if ($plugin == 'tasklist') {
                        $rcmail->config->set('tasklist_enabled', true);
                    }
                }
            }

            // "disable" plugins
            if (!empty($this->userconfig['disable_plugins'])) {
                foreach ((array)$this->userconfig['disable_plugins'] as $plugin) {
                    if (($j = array_search($plugin, $user_plugins)) !== false) {
                        unset($user_plugins[$j]);
                    }

                    // add support for Kolab plugins
                    if ($plugin == 'calendar') {
                        $rcmail->config->set('calendar_disabled', true);
                    }
                    else if ($plugin == 'tasklist') {
                        $rcmail->config->set('tasklist_disabled', true);
                    }
                    else if ($plugin == 'kolab_files') {
                        $rcmail->config->set('kolab_files_disabled', true);
                    }
                }
            }

            // load (remaining) user-specific plugins
            foreach (array_unique($user_plugins) as $plugin) {
                $rcmail->plugins->load_plugin($plugin);
            }
        }
    }

    /**
     * Callback for 'authenticate' hook
     */
    public function authenticate($args)
    {
        $userconfig = $this->_get_user_data($args['user']);
        if (!empty($userconfig)) {
            // override host parameter to perform user authentication
            if (!empty($userconfig['default_host'])) {
                $args['host'] = $userconfig['default_host'];
            }

            // remember config in session to avoid file access on every request
            $_SESSION['ude_config'] = $userconfig;
        }

        return $args;
    }

    /**
     * Helper method to parse the users database file and return config options
     * for the matching user.
     */
    private function _get_user_data($username)
    {
        $rcmail = rcube::get_instance();

        $fn = $dbfile = $rcmail->config->get('ude_login_db', 'users.txt');
        if ($fn[0] != '/') {
            $fn = realpath($dbfile = (__DIR__ . '/' . $fn));
        }

        if (!$fn || !is_readable($fn)) {
            rcube::raise_error(array(
                'code' => 500,
                'type' => 'php',
                'file' => __FILE__,
                'line' => __LINE__,
                'message' => "ude_login plugin warning: failed to open login db file '$dbfile'"), true);
            return false;
        }

        // append 'username_domain' to login name if configured
        $username_domain = $rcmail->config->get('username_domain');
        if (is_string($username_domain) && strlen($username_domain) && strpos($username, '@') === false) {
            $username_full = $username . '@' . $username_domain;
        }
        else {
            $username_full = $username;
        }

        // get domain for serching in file - first match (username|domain) finish
        $username_domain_array = array();
        preg_match('/@(.+)$/', $username_full, $username_domain_array);
        $username_domain = '@'. $username_domain_array[1];

        // pre-filter the user database file using 'grep'
        if ($rcmail->config->get('ude_use_grep', false)) {
            $fp = popen('grep ' . escapeshellarg("^$username\|^$username_domain") . ' ' . escapeshellarg($fn), 'r');
            $use_grep = true;
        }
        else {  // just open the file for reading
            $fp = fopen($fn, 'r');
            $use_grep = false;
        }

        while (($rec = fgetcsv($fp, 1000, "\t")) !== false) {
            if (!empty($rec[0]) && ($rec[0] == $username || $rec[0] == $username_full || $rec[0] == $username_domain)) {
                $this->userconfig = array();
                foreach ($rec as $i => $arg) {
                    if ($i == 0 || strpos($arg, '=') === false) {
                        continue;
                    }

                    // split parameters
                    list($prop, $value) = explode('=', $arg, 2);
                    if ($prop == 'enable_plugins' || $prop == 'disable_plugins' || strpos($value, ',')) {
                        $value = preg_split('/,\s*/', $value);
                    }
                    $this->userconfig[$prop] = $value;
                }
                break;
            }
        }
        if ($use_grep) {
            pclose($fp);
        }
        else {
            fclose($fp);
        }

        return $this->userconfig;
    }

}
