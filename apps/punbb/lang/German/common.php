<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction' => 'ltr', // ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier' => 'de',

// Number formatting
'lang_decimal_point' => ',',
'lang_thousands_sep' => '.',

// Notices
'Bad request' => 'Fehlerhafter Aufruf. Der aufgerufene Link ist fehlerhaft oder nicht mehr aktuell.',
'No view' => 'Sie haben keine Berechtigungen, um dieses Forum anzusehen.',
'No permission' => 'Sie haben keine Berechtigungen, um diese Seite aufzurufen.',
'CSRF token mismatch' => 'Das Sicherheitstoken konnte nicht erfolgreich verifiziert werden. Möglicherweise ist zwischen dem ersten Aufruf dieser Seite und dem letzten Abschicken eines Formulars oder Aufrufen eines Links zuviel Zeit verstrichen. Sollte dies der Fall sein und möchten Sie Ihre Aktion fortsetzen, klicken Sie auf den "Bestätigen"-Button. Um dorthin zurückzukehren, woher Sie ursprünglich kommen, klicken Sie auf den "Abbrechen"-Button.',
'No cookie' => 'Sie scheinen sich erfolgreich angemeldet zu haben, allerdings konnte kein Cookie erstellt werden. Bitte überprüfen Sie Ihre Einstellungen und lassen Sie, sofern möglich, Cookies für diese Webseite zu.',

// Miscellaneous
'Forum index' => 'Forumindex',
'Submit' => 'Abschicken', // "name" of submit buttons
'Cancel' => 'Abbrechen', // "name" of cancel buttons
'Preview' => 'Vorschau', // submit button to preview message
'Delete' => 'Löschen',
'Split' => 'Teilen',
'Ban message' => 'Sie sind von diesem Forum verbannt.',
'Ban message 2' => 'Der Bann läuft am Ende von %s ab.',
'Ban message 3' => 'Der Administrator oder Moderator, der Sie gebannt hat, hinterließ folgende Nachricht:',
'Ban message 4' => 'Bitte richten Sie alle Anfragen an den Forenadministrator unter %s.',
'Never' => 'Nie',
'Today' => 'Heute',
'Yesterday' => 'Gestern',
'Forum message' => 'Forumnachricht',
'Maintenance warning' => '<strong>Warnung! %s aktiviert.</strong> Melden Sie sich NICHT ab, da Sie sich sonst nicht mehr anmelden können.',
'Maintenance mode' => 'Wartungsmodus',
'Redirecting' => 'Leite weiter',
'Forwarding info' => 'Sie sollten in %s %s automatisch auf eine neue Seite weitergeleitet werden.',
'second' => 'Sekunde', // singular
'seconds' => 'Sekunden', // plural
'Click redirect' => 'Klicken Sie hier, wenn Sie nicht länger warten möchten (oder falls Sie Ihr Browser nicht automatisch weiterleitet)',
'Invalid e-mail' => 'Die angegebene E-Mail-Adresse ist ungültig.',
'New posts' => 'Neue Beiträge', // the link that leads to the first new post
'New posts title' => 'Themen mit neuen Beiträgen finden, die seit Ihrem letzten Besuch verfasst wurden.', // the popup text for new posts links
'Active topics' => 'Aktive Themen',
'Active topics title' => 'Themen mit den neusten Beiträgen findne.',
'Unanswered topics' => 'Unbeantwortete Themen',
'Unanswered topics title' => 'Themen suchen, auf die bislang nicht geantwortet wurde.',
'Username' => 'Benutzername',
'Registered' => 'Registriert',
'Write message' => 'Nachricht verfassen:',
'Forum' => 'Forum',
'Posts' => 'Beiträge',
'Pages' => 'Seiten',
'Page' => 'Seite',
'BBCode' => 'BBCode', // You probably shouldn't change this
'Smilies' => 'Smilies',
'Images' => 'Bilder',
'You may use' => 'Verwendet werden kann/können: %s',
'and' => 'und',
'Image link' => 'Bild', // This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote' => 'schrieb', // For [quote]'s (e.g., User wrote:)
'Code' => 'Code', // For [code]'s
'Forum mailer' => '%s Mailer', // As in "MyForums Mailer" in the signature of outgoing e-mails
'Write message legend' => 'Ihren Beitrag verfassen',
'Required information' => 'Benötigte Informationen',
'Reqmark' => '*',
'Required warn' => 'Alle Felder namens %s müssen ausgefüllt werden, bevor das Forumlar abgeschickt werden kann.',
'Crumb separator' => ' »&#160;', // The character or text that separates links in breadcrumbs
'Title separator' => ' - ',
'Page separator' => '&#160;', //The character or text that separates page numbers
'Spacer' => '…', // Ellipsis for paginate
'Paging separator' => ' ', //The character or text that separates page numbers for page navigation generally
'Previous' => 'Vorherige',
'Next' => 'Nächste',
'Cancel redirect' => 'Vorgang abgebrochen. Leiter weiter…',
'No confirm redirect' => 'Keine Bestätigung abgegeben. Vorgang abgebrochen. Leiter weiter…',
'Please confirm' => 'Bitte bestätigen:',
'Help page' => 'Hilfe zu: %s',
'Re' => 'Re:',
'Page info' => '(Seite %1$s von %2$s)',
'Item info single' => '%s [ %s ]',
'Item info plural' => '%s [ %s bis %s von %s ]', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator' => ' ', // e.g. 1 Page | 10 Topics
'Powered by' => 'Powered by <strong>%s</strong>, unterstützt von <strong>%s</strong>.',
'Maintenance' => 'Wartung',
'Installed extension' => 'Die offizielle %s Erweiterung ist installiert. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',
'Installed extensions' => 'Zur Zeit installierte, <span id="extensions-used" title="%s">offizielle %s Erweiterungen</span>. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',

// CSRF confirmation form
'Confirm' => 'Bestätigen', // Button
'Confirm action' => 'Aktion bestätigen',
'Confirm action head' => 'Bitte bestätigen Sie Ihre letzte Aktion oder brechen Sie sie ab',

// Title
'Title' => 'Titel',
'Member' => 'Mitglied', // Default title
'Moderator' => 'Moderator',
'Administrator' => 'Administrator',
'Banned' => 'Gebannt',
'Guest' => 'Gast',

// Stuff for include/parser.php
'BBCode error 1' => '[/%1$s] wurde ohne ein passendes [%1$s] gefunden',
'BBCode error 2' => '[%s]-Tag ist leer',
'BBCode error 3' => '[%1$s] wurde innerhalb von [%2$s] geöffnet. Dies ist nicht zulässig.',
'BBCode error 4' => '[%s] wurde innerhalb des eigenen Bereichs geöffnet. Dies ist nicht zulässig.',
'BBCode error 5' => '[%1$s] wurde ohne ein passendes [/%1$s] gefunden',
'BBCode error 6' => '[%s]-Tag besitzt einen leeren Attributabschnitt',
'BBCode nested list' => '[list]-Tags können nicht verschachtelt werden',
'BBCode code problem' => 'Es gibt ein Problem mit Ihren [code]-Tags',

// Stuff for the navigator (top of every page)
'Index' => 'Übersicht',
'User list' => 'Benutzerliste',
'Rules' =>  'Regeln',
'Search' =>  'Suche',
'Register' =>  'Registrieren',
'register' => 'registrieren',
'Login' =>  'Anmelden',
'login' => 'anmelden',
'Not logged in' =>  'Sie sind nicht angemeldet.',
'Profile' => 'Profil',
'Logout' => 'Abmelden',
'Logged in as' => 'Angemeldet als %s.',
'Admin' => 'Administration',
'Last visit' => 'Letzter Besuch %s',
'Mark all as read' => 'Alle Themen als gelesen markieren',
'Login nag' => 'Bitte melden Sie sich an oder registrieren Sie sich.',
'New reports' => 'Neue Meldungen',

// Alerts
'New alerts' => 'Neuer Hinweis',
'Maintenance alert' => '<strong>Warnung! Der Wartungsmodus wurde aktiviert.</strong> Dieses Board befindet sich momentan im Wartungsmodus. Melden Sie sich <em>NICHT</em> ab, wenn Sie die Möglichkeit haben wollen, sich wieder anmelden zu können.',
'Updates' => 'PunBB-Updates:',
'Updates failed' => 'Der letzte Versuch, Aktualisierungen über den Updateservice von punbb.informer.com zu finden, ist fehlgeschlagen. Möglicherweise ist dieser Service momentan überlastet oder temporär ausgefallen. Sollte dieser Hinweise jedoch nicht innerhalb der nächsten 2 Tage verschwunden sein, sollten sie die automatische Updateüberprüfung deaktivieren und zukünftig manuell überprüfen ob neue Updates zur Verfügung stehen.',
'Updates version n hf' => 'Eine neue PunBB-Version, Version %s, ist zum Herunterladen unter <a href="http://punbb.informer.com/">punbb.informer.com</a> verfügbar. Es stehen außerdem ein oder mehrere Hotfix(es) unter dem Reiter <a href="%s">"Erweiterungen verwalten"</a> zur Installation bereit.',
'Updates version' => 'Eine neue PunBB Version, Version %s, ist zum Herunterladen unter <a href="http://punbb.informer.com/">punbb.informer.com</a> verfügbar.',
'Updates hf' => 'Ein oder mehrere Hotfix(es) sind unter dem Reiter <a href="%s">"Erweiterungen verwalten"</a> zur Installation verfügbar.',
'Database mismatch' => 'Datenbankversion stimmt nicht überein',
'Database mismatch alert' => 'Ihre PunBB-Datenbank sollte in Verbindung mit einer neuen PunBB-Version genutzt werden, ansonsten ist es möglich, dass die Forensoftware nicht fehlerfrei funktioniert. Es ist ratsam, das Forum auf die neuste PunBB-Version zu bringen.',

// Stuff for Jump Menu
'Go' => 'Go', // submit button in forum jump
'Jump to' => 'Zu Forum wechseln:',

// For extern.php RSS feed
'RSS description' => 'Die neuesten Themen in %s.',
'RSS description topic' => 'Die neusten Beiträge in %s.',
'RSS reply' => 'Re: ', // The topic subject will be appended to this string (to signify a reply)

// Accessibility
'Skip to content' => 'Zum Foreninhalt wechseln',

// Debug information
'Querytime' => 'Erzeugt in %1$s Sekunden, %2$s Anfragen ausgeführt',
'Debug table' => 'Debug-Informationen',
'Debug summary' => 'Datenbank Anfrage-Performance Informationen',
'Query times' => 'Zeit (s)',
'Query' => 'Anfrage',
'Total query time' => 'Gesamte Anfragezeit'

);
