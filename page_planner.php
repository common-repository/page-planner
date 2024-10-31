<?php
/*
Plugin Name: Page Planner
Plugin URI: http://yourdomain.com/
Description: Page Planner
Version: 1.1
Author: Don Kukral
Author URI: http://yourdomain.com
License: GPL
*/
define( 'PAGE_PLANNER_VERSION' , '1.0.5' );
define( 'PAGE_PLANNER_ROOT' , dirname(__FILE__) );
define( 'PAGE_PLANNER_URL' , plugins_url(plugin_basename(dirname(__FILE__)).'/') );
define( 'PAGE_PLANNER_PAGE', 'index.php?page=page-planner/planner');

include_once(PAGE_PLANNER_ROOT . '/php/planner.php');

add_action('admin_menu', 'page_planner_menu');

function page_planner_menu() {
    $planner = new Planner();
	add_dashboard_page('Page Planner', 'Page Planner', 'read', 'page-planner/planner', array($planner, 'view'));
}

?>
