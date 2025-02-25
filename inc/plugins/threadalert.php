<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
$plugins->add_hook('newthread_start', 'threadalert_newthread');
$plugins->add_hook('newthread_do_newthread_end', 'threadalert_do_newthread');
$plugins->add_hook("newreply_start", "threadalert_newreply");
$plugins->add_hook("showthread_start", "threadalert_showthread");
$plugins->add_hook("newreply_do_newreply_end", "threadalert_do_newreply");
$plugins->add_hook('editpost_end', 'threadalert_editpost');
$plugins->add_hook('editpost_do_editpost_end', 'threadalert_do_editpost');
$plugins->add_hook('global_start', 'threadalert_register_myalerts_formatter_back_compat'); // Backwards-compatible alert formatter registration hook-ins.
$plugins->add_hook('xmlhttp', 'threadalert_register_myalerts_formatter_back_compat', -2/* Prioritised one higher (more negative) than the MyAlerts hook into xmlhttp */);
$plugins->add_hook('myalerts_register_client_alert_formatters', 'threadalert_register_myalerts_formatter'); // Backwards-compatible alert formatter registration hook-ins.

 
// Die Informationen, die im Pluginmanager angezeigt werden
function threadalert_info(){
	return array(
		"name"		=> "Themen-Alert",
		"description"	=> "Erweitert das Forum um die Möglichkeit, automatisch Benachrichtigungen (Alerts) an alle Accounts zu senden, wenn in bestimmten Themen neue Beiträge veröffentlicht werden.",
		"website"	=> "https://github.com/little-evil-genius/Themen-Alert",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function threadalert_install(){

    global $db;

	// DATENBANKFELD
    threadalert_database();

	// TEMPLATES ERSTELLEN
	// Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "threadalert",
        "title" => $db->escape_string("Themen-Alert"),
    );
    $db->insert_query("templategroups", $templategroup);
    threadalert_templates();

}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function threadalert_is_installed(){

    global $db;
    
    if ($db->field_exists("threadalert", "threads")) {
        return true;
    }
    return false;

} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function threadalert_uninstall(){

    global $db;

	if ($db->field_exists("threadalert", "threads")) {
		$db->drop_column("threads", "threadalert");
	}

}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function threadalert_activate(){

	global $db, $cache;

    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('threadalert_alert'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    } else {
		flash_message("Das Plugin <a href=\"https://github.com/MyBBStuff/MyAlerts\" target=\"_blank\">MyAlerts</a> von EuanT muss installiert sein!", 'error');
		admin_redirect('index.php?module=config-plugins');
	}
    

	// VARIABLEN EINFÜGEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('editpost', '#'.preg_quote('{$postoptions}').'#', '{$postoptions} {$threadalertoptions}');
	find_replace_templatesets('newreply_modoptions', '#'.preg_quote('{$stickoption}').'#', '{$stickoption} {$threadalertoption}');
	find_replace_templatesets('showthread_quickreply', '#'.preg_quote('{$closeoption}').'#', '{$closeoption} {$threadalertoption}');

}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function threadalert_deactivate(){

    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('threadalert_alert');
	}

    // VARIABLEN ENTFERNEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("editpost", "#".preg_quote('{$threadalertoptions}')."#i", '', 0);
	find_replace_templatesets("newreply_modoptions", "#".preg_quote('{$threadalertoption}')."#i", '', 0);
	find_replace_templatesets("showthread_quickreply", "#".preg_quote('{$threadalertoption}')."#i", '', 0);

}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// NEUES THEMA ERÖFFNEN - ANZEIGE
function threadalert_newthread() {

    global $templates, $mybb, $lang, $post_errors, $threadalertoption;

    // Sprachdatei laden
    $lang->load('threadalert');

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
		if ($mybb->get_input('threadalert') == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    } else {
        // Entwurf
        if ($tid > 0) {
			if (get_thread($tid)['threadalert'] == 1) {
				$threadalert_check = "checked";
			} else {
				$threadalert_check = "";
			}
        } else {
            $threadalert_check = "";
        }
    }

	eval("\$threadalertoption = \"".$templates->get("threadalert_threadoption")."\";");
}

// NEUES THEMA ERÖFFNEN - SPEICHERN
function threadalert_do_newthread() {

    global $mybb, $db, $tid;

    $threadalert = array(
        'threadalert' => (int)$mybb->get_input('threadalert')
    );
    $db->update_query("threads", $threadalert, "tid='".$tid."'");
}

// SCHNELLANTWORTBOX - ANZEIGE
function threadalert_showthread() {

    global $templates, $mybb, $lang, $post_errors, $threadalertoption;

	if ($mybb->usergroup['canmodcp'] != '1') return;

    // Sprachdatei laden
    $lang->load('threadalert');

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
		if ($mybb->get_input('threadalert') == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    } else {
        if (get_thread($tid)['threadalert'] == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    }

    eval("\$threadalertoption = \"".$templates->get("threadalert_quickreply")."\";");
}

// NEUE ANTWORT - ANZEIGE
function threadalert_newreply() {

    global $templates, $mybb, $lang, $post_errors, $threadalertoption;

    // Sprachdatei laden
    $lang->load('threadalert');

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
		if ($mybb->get_input('threadalert') == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    } else {
        if (get_thread($tid)['threadalert'] == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    }

    eval("\$threadalertoption = \"".$templates->get("threadalert_threadoption")."\";");
}

// NEUE ANTWORT - SPEICHERN & ALERT
function threadalert_do_newreply() {

    global $mybb, $db, $thread, $lang, $visible, $threadalert;

    // BENACHRICHTIGUNG
    if($visible == 1 && $mybb->get_input('threadalert') == 1){

		// Sprachdatei laden
		$lang->load('threadalert');

		$lastpost = $db->fetch_field($db->query("SELECT pid FROM ".TABLE_PREFIX."posts WHERE tid = '".$thread['tid']."' ORDER BY pid DESC LIMIT 1"), "pid");

        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {

			$user_query = $db->simple_select("users", "uid", "uid != '".$mybb->user['uid']."'");
			$alluids_array = [];
			while ($user = $db->fetch_array($user_query)) {
				$alluids_array[] = $user['uid'];
			}
    
            // Jedem Account
            foreach ($alluids_array as $uid) {
                if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
					$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('threadalert_alert');
					if ($alertType != NULL && $alertType->getEnabled()) {
						$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$uid, $alertType, (int)$mybb->user['uid']);
						$alert->setExtraDetails([
							'username' => $mybb->user['username'],
							'from' => $mybb->user['uid'],
							'tid' => $thread['tid'],
							'pid' => $lastpost,
							'subject' => $thread['subject'],
						]);
						MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
					}
				}
            }
        }
    }
}

// THEMA BEARBEITEN (FIRST POST) - ANZEIGE
function threadalert_editpost() {

    global $templates, $mybb, $lang, $thread, $pid, $post_errors, $threadalertoptions;

	// post isnt the first post in thread
    if ($thread['firstpost'] != $pid && $mybb->usergroup['canmodcp'] != '1') return;

    // Sprachdatei laden
    $lang->load('threadalert');

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
		if ($mybb->get_input('threadalert') == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    } else {
		if ($thread['threadalert'] == 1) {
			$threadalert_check = "checked";
		} else {
			$threadalert_check = "";
		}
    }

    eval("\$threadalertoptions = \"".$templates->get("threadalert_editpost")."\";");
}

// THEMA BEARBEITEN (FIRST POST) - SPEICHERN
function threadalert_do_editpost() {

    global $mybb, $db, $tid;

    $threadalert = array(
        'threadalert' => (int)$mybb->get_input('threadalert')
    );
    $db->update_query("threads", $threadalert, "tid='".$tid."'");
}

### ALERTS ###
// Backwards-compatible alert formatter registration.
function threadalert_register_myalerts_formatter_back_compat(){

	global $lang;
	$lang->load('threadalert');

	if (function_exists('myalerts_info')) {
		$myalerts_info = myalerts_info();
		if (version_compare($myalerts_info['version'], '2.0.4') <= 0) {
			threadalert_register_myalerts_formatter();
		}
	}
}

// Alert formatter registration.
function threadalert_register_myalerts_formatter(){

	global $mybb, $lang;
	$lang->load('threadalert');

	if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
	    class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
	    !class_exists('ThreadAlertAlertFormatter')
	) {
		class ThreadAlertAlertFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
		{
			/**
			* Format an alert into it's output string to be used in both the main alerts listing page and the popup.
			*
			* @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
			*
			* @return string The formatted alert string.
			*/
			public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
			{
				$alertContent = $alert->getExtraDetails();
				return $this->lang->sprintf(
					$this->lang->threadalert_alert,
					$outputAlert['from_user'],
					$alertContent['subject']
				);
		
			}

			/**
			* Init function called before running formatAlert(). Used to load language files and initialize other required
			* resources.
			*
			* @return void
			*/
			public function init()
			{
				if (!$this->lang->threadalert_alert) {
					$this->lang->load('threadalert');
				}
			}
		
			/**
			* Build a link to an alert's content so that the system can redirect to it.
			*
			* @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
			*
			* @return string The built alert, preferably an absolute link.
			*/
			public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
			{
				$alertContent = $alert->getExtraDetails();
				$postLink = $this->mybb->settings['bburl'] . '/' . get_post_link((int)$alertContent['pid'], (int)$alertContent['tid']).'#pid'.(int)$alertContent['pid'];
				return $postLink;
			}
		}

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager) {
		        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		if ($formatterManager) {
			$formatterManager->registerFormatter(new ThreadAlertAlertFormatter($mybb, $lang, 'threadalert_alert'));
		}
	}
}

// DATENBANKSPALTEN
function threadalert_database() {

    global $db;

    // DATENBANKSPALTE THREADS
    if (!$db->field_exists("threadalert", "threads")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `threadalert` INT(1) NOT NULL DEFAULT '0';");
    }
}

// TEMPLATES
function threadalert_templates() {

    global $db;

	$templates[] = array(
        'title'		=> 'threadalert_editpost',
        'template'	=> $db->escape_string('<tr>
		<td class="trow2" valign="top"><strong>{$lang->threadalert_editpost}</strong><br><span class="smalltext">{$lang->threadalert_editpost_desc}</span></td>
		<td class="trow2"><span class="smalltext">
		<label><input type="checkbox" class="checkbox" name="threadalert" value="1"{$threadalert_check} /> {$lang->threadalert_option}</label>
		</span></td>
		</tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

	$templates[] = array(
        'title'		=> 'threadalert_threadoption',
        'template'	=> $db->escape_string('<br /><label><input type="checkbox" class="checkbox" name="threadalert" value="1"{$threadalert_check} /> {$lang->threadalert_option}</label>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

	$templates[] = array(
        'title'		=> 'threadalert_quickreply',
        'template'	=> $db->escape_string('<br /><label><input type="checkbox" class="checkbox" name="threadalert" value="1"{$threadalert_check} /> {$lang->threadalert_quickreply}</label>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

	foreach ($templates as $template) {
		$check = $db->num_rows($db->simple_select("templates", "title", "title = '".$template['title']."'"));
		if ($check == 0) {
			$db->insert_query("templates", $template);
		}	
	}
}
