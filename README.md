# Themen-Alert
Dieses Plugin erweitert das Forum um die Möglichkeit, automatisch Benachrichtigungen (Alerts) an alle Accounts zu senden, wenn in bestimmten Themen neue Beiträge veröffentlicht werden.

# Funktionen
- Themen können als Alert-Themen markiert werden, sodass bei neuen Beiträgen alle Accounts benachrichtigt werden.
- Die Benachrichtigungseinstellung gilt für das gesamte Thema, unabhängig davon, welcher Account einen neuen Beitrag verfasst.
- Beim Erstellen eines neuen Beitrags besteht die Möglichkeit, für diesen Beitrag (k)eine Benachrichtigung zu versenden, ohne die allgemeine Einstellung des Themas zu beeinflussen. Selbst wenn dieses Thema sonst kein Alert-Themen ist.

# Funktionsweise
### Neues Thema erstellen:
Beim Erstellen eines neuen Themas steht unter "Moderations-Optionen" eine zusätzliche Checkbox zur Verfügung:<br>
[ ] Alert verschicken: An alle Accounts ein Alert schicken.<br>
Wird diese Option aktiviert, wird das Thema als Alert-Thema markiert.
### Neuen Beitrag verfassen:
Bei Beiträgen kann individuell festgelegt werden, ob für diesen einzelnen Beitrag (k)eine Benachrichtigung versendet werden soll. Sonst wird auf die Themen-Einstellung zurückgegriffen.

# Vorrausetzung
- <a href="https://github.com/MyBBStuff/MyAlerts\" target="_blank">MyAlerts</a> von EuanT <b>muss</b> installiert sein.

# Hinweis
Im ACP unter Konfiguration > Alert Types sollte bei threadalert_alert der Haken bei "Can be disabled by users?" entfernt werden. Andernfalls könnten Benutzer:innen die Benachrichtigungen selbstständig deaktivieren und würden dann keine Alerts mehr erhalten für neue Beiträge in den Themen.

# Datenbank-Änderungen
hinzugefügte Spalte in der Tabelle <b>threads</b>:
- threadalert

# Neue Sprachdateien
- deutsch_du/threadalert.lang.php

# Themen & Post Alert Templates
- Themen-Alert

# Neue Templates (nicht global!)
- threadalert_editpost
- threadalert_quickreply
- threadalert_threadoption

# Neue Variablen
- editpost: {$threadalertoptions}
- newreply_modoptions: {$threadalertoption}
- showthread_quickreply: {$threadalertoption}

# manuelle Erweiterungen
## Alert bei neu Eröffnung vom Thema
suche nach:
```php
function threadalert_do_newthread() {

    global $mybb, $db, $tid;

    $threadalert = array(
        'threadalert' => (int)$mybb->get_input('threadalert')
    );
    $db->update_query("threads", $threadalert, "tid='".$tid."'");
}
```
ersetze es durch:
```php
function threadalert_do_newthread() {

    global $mybb, $db, $tid, $lang, $visible;

    $threadalert = array(
        'threadalert' => (int)$mybb->get_input('threadalert')
    );
    $db->update_query("threads", $threadalert, "tid='".$tid."'");

    // BENACHRICHTIGUNG
    if($visible == 1 && $mybb->get_input('threadalert') == 1){

		// Sprachdatei laden
		$lang->load('threadalert');

		$thread = get_thread($tid);

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
							'pid' => $thread['firstpost'],
							'subject' => $thread['subject'],
						]);
						MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
					}
				}
            }
        }
    }
}
```

## Alerts nur an den Hauptaccount
suche nach:
```php
$user_query = $db->simple_select("users", "uid", "uid != '".$mybb->user['uid']."'");
```
ersetze es durch:
```php
$user_query = $db->simple_select("users", "uid", "uid != '".$mybb->user['uid']."' AND as_uid = '0'");
```
(Zeile auch in der manuellen Erweiterung "Alert bei neu Eröffnung vom Thema" vorhanden.)
