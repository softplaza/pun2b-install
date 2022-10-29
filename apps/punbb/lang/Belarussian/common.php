<?php

// Language definitions for frequently used strings
//Пераклаў Вова1
$lang_common = array(
// Text orientation and encoding
'lang_direction'            => 'ltr',    // ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'            => 'be',
// Number formatting
'lang_decimal_point'        => ',',
'lang_thousands_sep'        => ' ',
// Notices
'Bad request'                => 'Няверны запыт. Спасылка, па якой вы прыйшлі, змяшчае памылку або састарэла.',
'No view'                    => 'У вас няма права прагляду дадзеных раздзелаў.',
'No permission'                => 'У вас няма права доступу да дадзенай старонцы.',
'CSRF token mismatch'        => 'Немагчыма пацвердзіць маркер доступу. Магчыма, прайшоў некаторы час пасля першага ўваходу на старонку і наступнай адпраўкай формы альбо клікам па спасылцы. Калі гэта так і вы жадаеце выканаць сваё дзеянне да канца, націсніце кнопку «Пацвердзіць». Для вяртання на зыходную старонку трэба націснуць клавішу «Адмяніць».',
'No cookie'                    => 'Вы паспяхова ўвайшлі пад сваім уліковым запісам, але cookie не ўсталяваны. Калі ласка, праверце свае налады, і, калі магчыма, дазвольце выкарыстоўванне cookie для дадзенага сайта.',
// Miscellaneous
'Forum index'                => 'Загаловак раздзела',
'Submit'                    => 'Адправіць',    // "name" of submit buttons
'Cancel'                    => 'Адмяніць', // "name" of cancel buttons
'Preview'                    => 'Перадпрагляд',    // submit button to preview message
'Delete'                    => 'Выдаліць',
'Split'                        => 'Раздзяліць',
'Ban message'                => 'Вы заблакаваны ў дадзеным раздзеле.',
'Ban message 2'                => 'Блакіроўка мінае %s.',
'Ban message 3'                => 'Адміністратар ці мадэратар заблакаваў вас і пакінуў наступнае паведамленне:',
'Ban message 4'                => 'За далейшымі растлумачэннямі звярніцеся да аміністратара раздзела: %s.',
'Never'                        => 'Ніколі',
'Today'                        => 'Сёння',
'Yesterday'                    => 'Учора',
'Forum message'                => 'Паведамленне',
'Maintenance warning'        => '<strong>УВАГА! Уключаны %s.</strong> НЕ ВЫХОДЗЬЦЕ З-ПАД УЛІКОВАГА ЗАПІСУ — вы не зможаце ўвайсці назад.',
'Maintenance mode'            => 'рэжым прафілактыкі',
'Redirecting'                => ' Перанапрамак',
'Forwarding info'            => 'Вы будзеце аўтаматычна перанакіраваны на новую старонку праз %s %s.',
'second'                    => 'сякунду',    // singular
'seconds'                    => 'сякунд',    // plural
'Click redirect'            => 'Націсніце тут, калі не жадаеце чакаць (альбо калі ваш браўзер не перанакіроўвае аўтаматычна)',
'Invalid e-mail'            => 'Уведзены адрас электроннай пошты няверны.',
'New posts'                    => 'Новыя паведамленні',    // the link that leads to the first new post
'New posts title'            => 'Знайсці тэмы з паведамленнямі пасля вашага апошняга наведвання.',    // the popup text for new posts links
'Active topics'                => 'Актыўныя тэмы',
'Active topics title'        => 'Знайсці тэмы з нядаўнымі паведамленнямі.',
'Unanswered topics'            => 'Тэмы без адказаў',
'Unanswered topics title'    => 'Знайсці тэмы, на якія не было адказаў.',
'Username'                    => 'Імя карыстальніка',
'Registered'                => 'Зарэгістраваны',
'Write message'                => 'Напішыце паведамленне',
'Forum'                        => 'Форум',    // TODO: never used
'Posts'                        => 'Паведамленні', // TODO: never used
'Pages'                        => 'Старонкі',
'Page'                        => 'Старонка',
'BBCode'                    => 'BBCode',    // You probably shouldn't change this
'Smilies'                    => 'Смайлікаў',
'Images'                    => 'Выявы',
'You may use'                => 'Вы можаце выкарыстоўваць: %s',
'and'							=> 'і',
'Image link'                => 'выява',    // This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'                        => 'піша',    // For [ quote]'s (e.g., User wrote:)
'Code'                        => 'Код',        // For [ code]'s
'Forum mailer'                => 'Паштовы робат форуму «%s»',    // As in "MyForums Mailer" in the signature of outgoing emails
'Write message legend'        => 'Складзіце паведамленне',
'Required information'        => 'Абавязкова да запаўнення',
'Reqmark'                    => '*',
'Required warn'                => 'Усе палі, выдзеленыя паўтлустым напісаннем, павінны быць запоўнены.',
'Crumb separator'            => ' &rarr;&nbsp;', // The character or text that separates links in breadcrumbs
'Title separator'            => ' &mdash; ',
'Page separator'            => '&#160;', //The character or text that separates page numbers
'Spacer'                    => '…', // Ellipsis for paginate
'Paging separator'            => ' ', //The character or text that separates page numbers for page navigation generally
'Previous'                    => 'Назад',
'Next'                        => 'Далей',
'Cancel redirect'            => 'Аперацыя адменена.',
'No confirm redirect'        => 'Пацверджанне не атрымана. Аперацыя адменена.',
'Please confirm'            => 'Калі ласка, пацвердзіце:',
'Help page'                    => 'Даведка па: %s',
'Re'                        => 'Re:',
'Page info'                    => '(Старонка %1$s з %2$s)',
'Item info single'            => '%s %s',
'Item info plural'            => '%s з %s па %s з %s', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator'            => ' ', // e.g. 1 Page | 10 Topics
'Powered by'                => 'Форум працуе на %s, пры падтрымцы %s',
'Maintenance'                => 'Прафілактыка',
'Installed extension'         => 'Усталявана <span id="extensions-used" title="%s">%s афіцыйных пашырэнняў</span>. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',
//'Installed extensions'        => 'Усталявана <span id="extensions-used" title="%s">%s афіцыйных пашырэнняў</span>. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',
// CSRF confirmation form
'Confirm'                    => 'Пацвердзіць',    // Button
'Confirm action'            => 'Пацверджанне',
'Confirm action head'        => 'Калі ласка, пацвердзіце ці адмяніце сваё дзеянне',
// Title
'Title'                        => 'Загаловак',
'Member'                    => 'Удзельнік',    // Default title
'Moderator'                    => 'Мадэратар',
'Administrator'                => 'Адміністратар',
'Banned'                    => 'Заблакаваны',
'Guest'                        => 'Госць',
// Stuff for include/parser.php
'BBCode error 1'            => 'Знойдзен тэг [/%1$s] без адпаведнага [%1$s]',
'BBCode error 2'            => 'Тэг [%s] пусты',
'BBCode error 3'            => 'Выкарыстоўванне тэга [%1$s] унутры [%2$s] недапушчальна',
'BBCode error 4'            => 'Укладанне тэга[%s] у самаго сябе недапушчальна',
'BBCode error 5'            => 'Знойдзен тэг [%1$s] без адпаведнага [/%1$s]',
'BBCode error 6'            => 'Тэг [%s] мае пусты раздзел атрыбутаў',
'BBCode nested list'        => 'Тэгі [list] не патрэбны',
'BBCode code problem'        => 'Праблема з тэгамі [code]',
// Stuff for the navigator (top of every page)
'Index'                        => 'Форум',
'User list'                    => 'Карыстальнікі',
'Rules'                        => 'Правілы',
'Search'                    => 'Пошук',
'Register'                    => 'Рэгістрацыя',
'register'                    => 'зарэгістравацца',
'Login'                        => 'Уваход',
'login'                        => 'увайсці',
'Not logged in'                => 'Вы не ўвайшлі.',
'Profile'                    => 'Профіль',
'Logout'                    => 'Выхад',
'Logged in as'                => 'Вы ўвайшлі як %s.',
'Admin'                        => 'Адміністраванне',
'Last visit'                => 'Апошняе наведванне: %s',
'Mark all as read'            => 'Адзначыць усе тэмы прачытанымі',
'Login nag'                    => 'Калі ласка, увайдзіце альбо зарэгіструйцесь.',
'New reports'                => 'Скаргі',
// Alerts
'New alerts'                => 'Апавяшчэнні',
'Maintenance alert'            => '<strong>Уключаны рэжым прафілактыкі.</strong> <em>НЕ ВЫХОДЗЬЦЕ</em> з-пад свайго ўліковага запісу — вы не зможаце ўвайсці назад.',
'Updates'                    => 'Абнаўленні PunBB',
'Updates failed'            => 'Апошняя праверка абнаўленняў punbb.informer.com скончылась няўдала. Гэта можа азначаць, што сэрвіс абнаўленняў перагружаны або недасягальны. Калі дадзенае папярэджанне не знікне на працягу аднаго-двух дзён, можна адключыць аўтаматычную праверку і правяраць наяўнасць абнаўленняў у ручную.',
'Updates version n hf'        => 'Новая версія PunBB — %s даступна для загрузкі на <a href="http://punbb.informer.com/">punbb.informer.com</a>. Акрамя таго, адзін ці некалькі пакетаў выпраўленняў даступныя для ўсталявання на ўкладцы «Пашырэнні» інтэрфейсу адміністратара.',
'Updates version'            => 'Новая версія PunBB — %s даступна для загрузкі на <a href="http://punbb.informer.com/">punbb.informer.com</a>.',
'Updates hf'                => 'Адзін ці некалькі пакетаў выпраўленняў даступныя для ўсталявання на ўкладцы «Пашырэнні» інтэрфейсу адміністратара.',
'Database mismatch'            => 'Неадпавяданне версіі базы дадзеных',
'Database mismatch alert'    => 'База дадзеных PunBB будзе выкарыстаная ў звязку з новай версіяй кода PunBB. Іх неадпавяданне можа прывесці да непрацаздольнасці форуму. Рэкамендуецца абнавіць форум да самай новай версіі PunBB.',
// Stuff for Jump Menu
'Go'                        => 'Перайсці',        // submit button in forum jump
'Jump to'                    => 'Перайсці ў раздзел:',
// For extern.php RSS feed
'RSS description'            => 'Нядаўнія тэмы раздзела «%s».',
'RSS description topic'        => 'Нядаўнія паведамленні ў раздзеле «%s».',
'RSS reply'                    => 'Re: ',    // The topic subject will be appended to this string (to signify a reply)
// Accessibility
'Skip to content'            => 'Перайсці да зместу раздзела',
// Debug information
'Querytime'                    => 'Згенеравана за %1$s сякунды (%2$s%% PHP — %3$s%% БД) %4$s запытаў да базы дадзеных',
'Debug table'                => 'Адладкавая інфармацыя',
'Debug summary'                => 'Прадукцыйнасць базы дадзеных',
'Query times'                => 'Час (с)',
'Query'                        => 'Запыт',
'Total query time'            => 'Агульны час запыту',
// Error message
'Forum error header'        => 'Прабачце! Адбылася памылка.',
'Forum error description'   => 'Гэта часовая памылка. Проста абнавіце старонку. Калі праблема не вырашаецца, паспрабуйце паўтарыць праз 5-10 хвілін.',
'Forum error location'      => 'Памылка адбылася ў радку %1$s у %2$s',
'Forum error db reported'   => 'База дадзеных:',
'Forum error db query'      => 'Запыт з памылкай:',
'Menu admin'      => 'Admin Menu',
'Menu profile'      => 'Profile Menu',
);