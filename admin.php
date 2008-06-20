<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2008  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Phorum 5 Admin

define("PHORUM_ADMIN", 1);

// set a sane error level for our admin.
// this will make the coding time faster and
// the code run faster.
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('./common.php');
require_once('./include/admin_functions.php');

// determine absolute URI for the admin
$PHORUM["admin_http_path"] = phorum_get_current_url(false);

// determine http_path (at install time; after that it's in the settings)
if(!isset($PHORUM["http_path"])){
    $PHORUM["http_path"] = dirname($_SERVER["PHP_SELF"]);
}

// A variable that can be filled for showing a notification in the
// admin header.php.
$notification = NULL;

// if we are installing or upgrading, we don't need to check for a session
// 2005081000 was the internal version that introduced the installed flag
if(!isset($PHORUM['internal_version']) || (!isset($PHORUM['installed']) && $PHORUM['internal_version']>='2005081000')) {

    // this is an install
    $module="install";

} elseif ( (isset($_REQUEST["module"]) && $_REQUEST["module"]=="upgrade") ||
           $PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
           !isset($PHORUM['internal_patchlevel']) ||
           $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL ) {

    // this is an upgrade
    $module="upgrade";

} else {

    // Try to restore an admin session.
    phorum_api_user_session_restore(PHORUM_ADMIN_SESSION);

    if(!isset($PHORUM["user"]) || !$PHORUM["user"]["admin"]){
        // if not an admin
        unset($PHORUM["user"]);
        $module="login";
    } else {
        // load the default module if none is specified
        if(!empty($_REQUEST["module"]) && is_string($_REQUEST["module"])){
            $module = @basename($_REQUEST["module"]);
        } else {
            $module = "default";
        }

        // Check if there are updated module information files.
        // If yes, the load the information and store the data.
        require_once('./include/api/modules.php');
        $updates = phorum_api_modules_check_updated_info();
        if (!empty($updates)) {
            phorum_api_modules_save();
            $notification =
                "Updated module info for module".(count($updates)==1?"":"s") .
                (count($updates)>10 ? "" : ":" . implode(", ", $updates));
        }

        // Check if there are modules that require a database layer upgrade.
        // If this is the case, then we will run the upgrade code in
        // module upgrading mode.
        $modupgrades = phorum_api_modules_check_updated_dblayer();
        if (!empty($modupgrades)) {
            define('MODULE_DATABASE_UPGRADE', 1);
            $module = "upgrade";
        }
    }
}

$module = phorum_hook( "admin_pre", $module );
ob_start();
if($module!="help") require_once('./include/admin/header.php');
require_once("./include/admin/$module.php");
if($module!="help") require_once('./include/admin/footer.php');
ob_end_flush();

?>
