<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'			=>	'ltr',	// ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'			=>	'lv',

// Number formatting
'lang_decimal_point'		=>	'.',
'lang_thousands_sep'		=>	',',

// Notices
'Bad request'				=>	'Slikts pieprasījums. Links kuram Jūs sekojat ir nederīgs, vai tā termiņš ir iztecējis.',
'No view'					=>	'Jums nav pieejas šim forumam.',
'No permission'				=>	'Jums nav pieejas šai lapai.',
'CSRF token mismatch'		=>	'Nevar apstiprināt drošības kodu. Iespējams, iemesls ir tas, ka ir pagājis pārāk ilgs laika posms starp laiku kad Jūs ienācāt lapā, un laiku kad Jūs nosūtījāt formas datus, vai uzklikšķināt uz saites. Ja tas ir šāds gadījumā un jūs vēlētos turpināt ar savu darbību, lūdzu, noklikšķiniet uz apstiprinājuma pogas. Pretējā gadījumā jums vajadzētu noklikšķiniet pogu Cancel, lai atgrieztos tur, kur jūs bijāt',
'No cookie'					=>	'Jūs veiksmīgi ienācāt, tomēr kookijs nav aktivizēts. Lūdzu iespējojiet kookijus savā pārlūkprogrammā.',


// Miscellaneous
'Forum index'				=>	'Foruma sākums',
'Submit'					=>	'Darīt',	// "name" of submit buttons
'Cancel'					=>	'Atcelt', // "name" of cancel buttons
'Preview'					=>	'Priekšskatīt',	// submit button to preview message
'Delete'					=>	'Dzēst',
'Split'						=>	'Dalīt',
'Ban message'				=>	'Jūs esat banots.',
'Ban message 2'				=>	'Jūsu bans beigsies %s.',
'Ban message 3'				=>	'Persona kas Jūs nobanoja atstāja Jums šādu ziņojumu:',
'Ban message 4'				=>	'Lūdzu griezieties pie foruma administrātora ja Jums ir kādas neskaidrības %s.',
'Never'						=>	'Nekad',
'Today'						=>	'Šodien',
'Yesterday'					=>	'Vakar',
'Forum message'				=>	'Foruma ziņojums',
'Maintenance warning'		=>	'<strong>Brīdinājums! %s ir iespējots.</strong> NEIZEJIET NO FORUMA, jo tad Jūs nevarēsiet atkal ienākt.',
'Maintenance mode'			=>	'Apkopes režīms',
'Redirecting'				=>	' Pāradresē…', // With space!
'Forwarding info'			=>	'Jūs tiksiet automātiski pāradresēts pēc %s %s.',
'second'					=>	'sekunde',	// singular
'seconds'					=>	'sekundes',	// plural
'Click redirect'			=>	'Spied šeit ja nevēlies vairāk gaidīt (vai ja ja pārlūks neveic automātisku pāradresāciju)',
'Invalid e-mail'			=>	'Ievadītā epasta adrese ir nederīga.',
'New posts'					=>	'Jauni posti',	// the link that leads to the first new post
'New posts title'			=>	'Atrast tēmas kurās ir jauni posti, kopš Jūsu pēdejā apmeklējuma.',	// the popup text for new posts links
'Active topics'				=>	'Aktīvas tēmas',
'Active topics title'		=>	'Atras tēmas ar jauniem postiem.',
'Unanswered topics'			=>	'Neatbildētas tēmas',
'Unanswered topics title'	=>	'Atrast neatbildētas tēmas.',
'Username'					=>	'Lietotājvārds',
'Registered'				=>	'Reģistrēts',
'Write message'				=>	'Rakstīt:',
'Forum'						=>	'Forums',
'Posts'						=>	'Posti',
'Pages'						=>	'Lapas',
'Page'						=>	'Lapa',
'BBCode'					=>	'BBCode',	// You probably shouldn't change this
'Smilies'					=>	'Smaidiņi',
'Images'					=>	'Attēli',
'You may use'				=>	'Jūs varat lietot: %s',
'and'						=>	'un',
'Image link'				=>	'attēls',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'						=>	'rakstīja',	// For [quote]'s (e.g., User wrote:)
'Code'						=>	'Kods',		// For [code]'s
'Forum mailer'				=>	'%s par mobīlām ierīcēm pastnieks',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Write message legend'		=>	'Rakstīt postu',
'Required information'		=>	'Pieprasītā informācija',
'Reqmark'					=>	'*',
'Required warn'				=>	'Visi lauki ar izcelto virsrakstu ir obligāti jāaizpilda.',
'Crumb separator'			=>	' &rarr;&#160;', // The character or text that separates links in breadcrumbs
'Title separator'			=>	' — ',
'Page separator'			=>	'&#160;', //The character or text that separates page numbers
'Spacer'					=>	'…', // Ellipsis for paginate
'Paging separator'			=>	' ', //The character or text that separates page numbers for page navigation generally
'Previous'					=>	'Iepriekšejais',
'Next'						=>	'Nākamais',
'Cancel redirect'			=>	'Operation cancelled.',
'No confirm redirect'		=>	'Nav apstiprinājuma. Darbība atcelta.',
'Please confirm'			=>	'Lūdzu apstipriniet:',
'Help page'					=>	'Palīdzība ar: %s',
'Re'						=>	'Re:',
'Page info'					=>	'(Lapa %1$s no %2$s)',
'Item info single'			=>	'%s: %s',
'Item info plural'			=>	'%s: %s to %s of %s', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator'			=>	' ', // e.g. 1 Page | 10 Topics
'Powered by'				=>	'Powered by %s, supported by %s.',
'Maintenance'				=>	'Apkope',
'Installed extension'		=>	'Oficiālais paplašinājums %s ir instalēts. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',
'Installed extensions'		=>	'Šobrīd instalēti <span id="extensions-used" title="%s">%s official extensions</span>. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',

// CSRF confirmation form
'Confirm'					=>	'Apstiprināt',	// Button
'Confirm action'			=>	'Apstiprināt darbību',
'Confirm action head'		=>	'Lūdzu apstipriniet vai atceliet darbību',

// Title
'Title'						=>	'Virsraksts',
'Member'					=>	'Biedrs',	// Default title
'Moderator'					=>	'Moderators',
'Administrator'				=>	'Administrators',
'Banned'					=>	'Banots',
'Guest'						=>	'Viesis',

// Stuff for include/parser.php
'BBCode error 1'			=>	'[/%1$s] tika  atrasti bez sakritības [%1$s]',
'BBCode error 2'			=>	'[%s] tags ir tukšs',
'BBCode error 3'			=>	'[%1$s] tika atvērts laikā [%2$s], tas nav atļauts',
'BBCode error 4'			=>	'[%s] atvēra sevi, tas nav atļauts',
'BBCode error 5'			=>	'[%1$s] atrada bez sakritības [/%1$s]',
'BBCode error 6'			=>	'[%s] tagam bija tukša atribūtu sadaļu',
'BBCode nested list'		=>	'[list] tagus nevar apvienot',
'BBCode code problem'		=>	'Problēma ar Jūsu [code] tagiem',

// Stuff for the navigator (top of every page)
'Index'						=>	'Sākums',
'User list'					=>	'Lietotāju saraksts',
'Rules'						=>	'Noteikumi',
'Search'					=>	'Meklēt',
'Register'					=>	'Reģistrēties',
'register'					=>	'reģistrēties',
'Login'						=>	'Ienākt',
'login'						=>	'ienākt',
'Not logged in'				=>	'Jūs neesat ienācis.',
'Profile'					=>	'Profils',
'Logout'					=>	'Iziet',
'Logged in as'				=>	'Ienācis kā %s.',
'Admin'						=>	'Administrācija',
'Last visit'				=>	'Pēdejais apmeklējums %s',
'Mark all as read'			=>	'Atzīmēt visas tēmas kā lasītas',
'Login nag'					=>	'Lūdzu ienāciet, vai reģistrējaties.',
'New reports'				=>	'Jauni ziņojumi',

// Alerts
'New alerts'				=>	'Jauni atgādinājumi',
'Maintenance alert'			=>	'<strong>Apkopes režīms aktivizēts.</strong> <em>NEIZEJIET NO FORUMA</em>, ja iziesiet, Jūs nevarēsiet atkal inākt.',
'Updates'					=>	'PunBB updates:',
'Updates failed'			=>	'The latest attempt at checking for updates against the punbb.informer.com updates service failed. This probably just means that the service is temporarily overloaded or out of order. However, if this alert does not disappear within a day or two, you should disable the automatic check for updates and check for updates manually in the future.',
'Updates version n hf'		=>	'A newer version of PunBB, version %s, is available for download at <a href="http://punbb.informer.com/">punbb.informer.com</a>. Furthermore, one or more hotfixes are available for install on the <a href="%s">Manage hotfixes</a> tab of the admin interface.',
'Updates version'			=>	'A newer version of PunBB, version %s, is available for download at <a href="http://punbb.informer.com/">punbb.informer.com</a>.',
'Updates hf'				=>	'One or more hotfixes are available for install on the <a href="%s">Manage hotfixes</a> tab of the admin interface.',
'Database mismatch'			=>	'Database version mismatch',
'Database mismatch alert'	=>	'Your PunBB database is meant to be used in conjunction with a newer version of the PunBB code. This mismatch can lead to your forum not working properly. It is suggested that you upgrade your forum to the newest version of PunBB.',

// Stuff for Jump Menu
'Go'						=>	'Iet',		// submit button in forum jump
'Jump to'					=>	'Pāriet uz forumu:',

// For extern.php RSS feed
'RSS description'			=>	'Jaunākās tēmas iekš %s.',
'RSS description topic'		=>	'Jaunākie posti iekš %s.',
'RSS reply'					=>	'Re: ',	// The topic subject will be appended to this string (to signify a reply)

// Accessibility
'Skip to content'			=>	'Skip to forum content',

// Debug information
'Querytime'					=>	'Generated in %1$s seconds (%2$s%% PHP - %3$s%% DB) with %4$s queries',
'Debug table'				=>	'Debug information',
'Debug summary'				=>	'Database query performance information',
'Query times'				=>	'Time (s)',
'Query'						=>	'Query',
'Total query time'			=>	'Total query time',

// Error message
'Forum error header'		=> 'Atvainojiet! Lapu nevar ielādēt.',
'Forum error description'	=> 'This is probably a temporary error. Just refresh the page and retry. If problem continues, please check back in 5-10 minutes.',
'Forum error location'		=> 'The error occurred on line %1$s in %2$s',
'Forum error db reported'	=> 'Database reported:',
'Forum error db query'		=> 'Failed query:',

);
