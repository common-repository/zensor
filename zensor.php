<?php

/*
 * Plugin Name: Zensor
 * Plugin URI: http://www.scompt.com/projects/zensor
 * Description: Zensor enforces a two-step publishing workflow in WordPress.
 * Author: Edward Dale
 * Version: 0.7
 * Author URI: http://www.scompt.com
 */

/**
 * Main plugin file for Zensor which initializes and includes all other files
 *
 * LICENSE
 * This file is part of Zensor.
 *
 * Zensor is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    Zensor
 * @author     Edward Dale <scompt@scompt.com>
 * @copyright  Copyright 2007 Edward Dale
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @version    $Id: zensor.php 17 2007-06-20 13:54:55Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Functionality for the admin screen in general
 */
require_once(dirname(__FILE__).'/admin.php');

/**
 * Functionality for the options screen
 */
require_once(dirname(__FILE__).'/options.php');

/**
 * Functionality for the management screen where posts/pages are listed
 */
require_once(dirname(__FILE__).'/manage.php');

/**
 * Functionality for when posts/pages are being edited
 */
require_once(dirname(__FILE__).'/edit.php');

/**
 * Functionality for public pages that the user interacts with
 */
require_once(dirname(__FILE__).'/public.php');

/**
 * The name of the Zensor table in the database.  Prefixed with the Wordpress
 * database prefix in the init step.
 * @global string $GLOBALS['zensor_table']
 */
$GLOBALS['zensor_table'] = 'zensor';

/**
 * The version of the Zensor database schema.
 * @global string $GLOBALS['zensor_db_version']
 */
$GLOBALS['zensor_db_version'] = "2";

/**
 * Initializes everything Zensor needs
 *
 * Builds the zensor_table global variable and loads translations.
 */
function zensor_init()
{
	global $zensor_table, $wpdb;
	$zensor_table = $wpdb->prefix . $zensor_table;

	load_plugin_textdomain('zensor', PLUGINDIR.'/zensor/gettext');
}

/*
 * Hook into the init action to initialize things at the right time.
 */
if( function_exists('add_action') ) 
{
	add_action('init', zensor_init, 1);
}

?>