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
