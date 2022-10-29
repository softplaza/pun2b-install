<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'			=>	'ltr',	// ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'			=>	'ua',

// Number formatting
'lang_decimal_point'		=>	'.',
'lang_thousands_sep'		=>	',',

// Notices
'Bad request'				=>	'Помилка. Посилання, на яке ви перейшли неправильне або його термін дії вже кінчився.',
'No view'					=>	'У вас немає дозволу переглядати ці форуми.',
'No permission'				=>	'У вас немає дозволу переглядати цю сторінку.',
'CSRF token mismatch'		=>	'Помилка з сесією. Можливо ви заповнювали форму, якщо це так то продовжуйте. Якщо ні, перейдіть назад.',
'No cookie'					=>	'Перевірте, щоб у вас були увімкнені cookie (куки)',


// Miscellaneous
'Forum index'				=>	'Головна сторінка',
'Submit'					=>	'Опублікувати',	// "name" of submit buttons
'Cancel'					=>	'Скасувати', // "name" of cancel buttons
'Preview'					=>	'Попередній перегляд',	// submit button to preview message
'Delete'					=>	'Видалити',
'Split'						=>	'Розділити',
'Ban message'				=>	'Ви забанені на цьому форумі.',
'Ban message 2'				=>	'Бан розблокується %s.',
'Ban message 3'				=>	'Ви забанені на цьому форумі. Причина:',
'Ban message 4'				=>	'Усі питання до адміністрації в %s.',
'Never'						=>	'Ніколи',
'Today'						=>	'Сьогодні',
'Yesterday'					=>	'Вчора',
'Forum message'				=>	'Повідомлення',
'Maintenance warning'		=>	'<strong>Увага! %s включені.</strong> Не виходьте, бо не зможете увійти потім!',
'Maintenance mode'			=>	'Режим обслуговування',
'Redirecting'				=>	'Перенаправлення…', // With space!
'Forwarding info'			=>	'Ви будете перенаправлені через %s %s.',
'second'					=>	'секунду',	// singular
'seconds'					=>	'секунди',	// plural
'Click redirect'			=>	'Клацніть тут, щоб не чекати (або ваш браузер не підтримує автоматичну переадресацію)',
'Invalid e-mail'			=>	'Неправильна електронна адреса',
'New posts'					=>	'<i class="fa fa-angle-double-down"></i>Нові повідомлення',	// the link that leads to the first new post
'New posts title'			=>	'Знайти теми, в яких присутні нові повідомлення',	// the popup text for new posts links
'Active topics'				=>	'<i class="fa fa-fire"></i>Активні теми',
'Active topics title'		=>	'Знайти теми з нещодавніми повідомленнями.',
'Unanswered topics'			=>	'<i class="fa fa-bed"></i>Теми без відповіді',
'Unanswered topics title'	=>	'Знайти теми без відповідей.',
'Username'					=>	'Логін',
'Registered'				=>	'Зареєстрований',
'Write message'				=>	'Написати повідомлення',
'Forum'						=>	'Форум',
'Posts'						=>	'Повідомлення',
'Pages'						=>	'Сторінки',
'Page'						=>	'Сторінка',
'BBCode'					=>	'BB Коди',	// You probably shouldn't change this
'Smilies'					=>	'Смайлики',
'Images'					=>	'Зображення',
'You may use'				=>	'Ви можете використовувати: %s',
'and'						=>	'і',
'Image link'				=>	'зображення',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'						=>	'писав',	// For [quote]'s (e.g., User wrote:)
'Code'						=>	'Код',		// For [code]'s
'Forum mailer'				=>	'%s відправник',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Write message legend'		=>	'Написати своє повідомлення.',
'Required information'		=>	'Необхідна інформація',
'Reqmark'					=>	'*',
'Required warn'				=>	'Усі поля, які відмічені повинні бути заповнені перед відправленням форми.',
'Crumb separator'			=>	' &rarr;&#160;', // The character or text that separates links in breadcrumbs
'Title separator'			=>	' — ',
'Page separator'			=>	'&#160;', //The character or text that separates page numbers
'Spacer'					=>	'…', // Ellipsis for paginate
'Paging separator'			=>	' ', //The character or text that separates page numbers for page navigation generally
'Previous'					=>	'Попередні',
'Next'						=>	'Наступні',
'Cancel redirect'			=>	'Операція скасована.',
'No confirm redirect'		=>	'Не підтверджено. Операція скасована.',
'Please confirm'			=>	'Будь ласка, підтвердіть:',
'Help page'					=>	'Допомога з: %s',
'Re'						=>	'Re:',
'Page info'					=>	'(Сторінка %1$s з %2$s)',
'Item info single'			=>	'%s: %s',
'Item info plural'			=>	'%s: %s до %s з %s', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator'			=>	' ', // e.g. 1 Page | 10 Topics
'Powered by'				=>	'Технології %s, технічна підтримка %s.',
'Maintenance'				=>	'Режим обслуговування',
'Installed extension'		=>	'%s офіційні доповнення встановлені. Копірайт &copy; 2003&ndash;2014 <a href="http://punbb.informer.com/">PunBB</a>.',
'Installed extensions'		=>	'Встановлено <span id="extensions-used" title="%s">%s official extensions</span>. Копірайт &copy; 2003&ndash;2014 <a href="http://punbb.informer.com/">PunBB</a>.',

// CSRF confirmation form
'Confirm'					=>	'Підтвердити',	// Button
'Confirm action'			=>	'Підтвердити дію',
'Confirm action head'		=>	'Будь ласка, підтвердіть або скасуйте вашу останню дію',

// Title
'Title'						=>	'Назва',
'Member'					=>	'Користувач',	// Default title
'Moderator'					=>	'Модератор',
'Administrator'				=>	'Адміністратор',
'Banned'					=>	'Забанений',
'Guest'						=>	'Гість',

// Stuff for include/parser.php
'BBCode error 1'			=>	'[/%1$s] був знайдений без врахування [%1$s]',
'BBCode error 2'			=>	'[%s] тег пустий',
'BBCode error 3'			=>	'[%1$s] був відкритий в [%2$s], що не дозволено',
'BBCode error 4'			=>	'[%s] був відкритий, що не є дозволеним',
'BBCode error 5'			=>	'[%1$s] знайдений без [/%1$s]',
'BBCode error 6'			=>	'[%s] тег мав пустий зміст налаштувань',
'BBCode nested list'		=>	'[list] теги не можуть бути вкладеними',
'BBCode code problem'		=>	'Проблеми з тегами в [code] ',

// Stuff for the navigator (top of every page)
'Index'						=>	'Головна',
'User list'					=>	'Користувачі',
'Rules'						=>	'Правила',
'Search'					=>	'Пошук',
'Register'					=>	'Зареєструватись',
'register'					=>	'зареєструватись',
'Login'						=>	'Увійти',
'login'						=>	'увійти',
'Not logged in'				=>	'Ви не увійшли.',
'Profile'					=>	'Профіль',
'Logout'					=>	'Вийти',
'Logged in as'				=>	'Ви увійшли як %s.',
'Admin'						=>	'Адміністрація',
'Last visit'				=>	'Останній візит %s',
'Mark all as read'			=>	'Помітити все як прочитане',
'Login nag'					=>	'Будь ласка увійдіть або зареєструйтесь.',
'New reports'				=>	'Нові скарги',

// Alerts
'New alerts'				=>	'Нові сповіщення',
'Maintenance alert'			=>	'<strong>Режим обслуговування увімкнений.</strong> <em>НЕ</em> виходьте, бо не увійдете більше.',
'Updates'					=>	'Оновлення PunBB:',
'Updates failed'			=>	'Перевірка останній оновлень не вдалася, оскільки сервер можливо перезавантажений або тимчасово непрацює. Спробуйте пізніше.',
'Updates version n hf'		=>	'Доступна нова версія PunBB, випуску %s, яка на <a href="http://punbb.informer.com/">punbb.informer.com</a>. Нові хотфікси доступні на панелі <a href="%s">Управління хотфіксами</a> в панелі обслуговування.',
'Updates version'			=>	'Доступна нова версія PunBB, випуску %s, яка на <a href="http://punbb.informer.com/">punbb.informer.com</a>. ',
'Updates hf'				=>	'Нові хотфікси доступні на панелі <a href="%s">Управління хотфіксами</a> в панелі обслуговування.',
'Database mismatch'			=>	'Невідповідність версії БД',
'Database mismatch alert'	=>	'Невідповідність версії БД. Оновіть ваш форум до останньої версії.',

// Stuff for Jump Menu
'Go'						=>	'Перейти',		// submit button in forum jump
'Jump to'					=>	'Швидкий перехід до:',

// For extern.php RSS feed
'RSS description'			=>	'Найновіші теми в %s.',
'RSS description topic'		=>	'Найновіші повідомлення в %s.',
'RSS reply'					=>	'Re: ',	// The topic subject will be appended to this string (to signify a reply)

// Accessibility
'Skip to content'			=>	'Перейти до вмісту',

// Debug information
'Querytime'					=>	'Сторінка згенерована за %1$s секунд (%2$s%% PHP - %3$s%% DB) з %4$s запитами',
'Debug table'				=>	'Дебаг інформація',
'Debug summary'				=>	'Інформація про роботу БД',
'Query times'				=>	'раз (ів)',
'Query'						=>	'Запит',
'Total query time'			=>	'Загальний час на запити',

// Error message
'Forum error header'		=> 'Вибачте! Сторінка не грузиться.',
'Forum error description'	=> 'Це тимчасова проблема. Оновість сторінку через 5 чи 10 хвилин.',
'Forum error location'		=> 'Помилка на %1$s строці в %2$s',
'Forum error db reported'	=> 'Інформація від бази даних:',
'Forum error db query'		=> 'Запит невдався:',

);
