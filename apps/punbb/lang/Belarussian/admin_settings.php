<?php
//перавёў Вова1
$lang_admin_settings = array(
'Settings updated'                => 'Налады абноўленыя.',
// Setup section
'Setup personal'                => 'Персанальныя налады',
'Setup personal legend'            => 'Інсталяцыя PunBB',
'Board description label'        => 'Апісанне форуму',
'Board title label'                => 'Назва форуму',
'Default style label'            => 'Стыль па змаўчанні',
'Setup local'                    => 'Налады лакалізацыі',
'Setup local legend'            => 'Налады лакалізацыі для форуму',
'Default timezone label'        => 'Гадзінны пояс',
'DST label'                        => 'Выкарыстоўваць летні час (зрух на 1 гадзіну наперад адносна часу, прынятага ў дадзеным гадзінным поясе).',
'Default language label'        => 'Асноўная мова',
'Default language help'            => '(Калі моўны пакет выдалены, неабходна абнавіць дадзеную наладу)',
'Time format label'                => 'Фармат часу',
'Date format label'                => 'Фармат даты',
'Current format'                => '[ Бягучы фармат: %s ] %s',
'External format help'            => '<a class="exthelp" href="http://www.php.net/manual/ru/function.date.php">Даведацца падрабязней</a> аб фарматаванні.',
'Setup timeouts'                => 'Таймаўты',
'Setup timeouts legend'            => 'Таймаўты',
'Visit timeout label'            => 'Таймаўт наведвання',
'Visit timeout help'            => 'Колькасць секунд з моманту выхаду карыстальнікам',
'Online timeout label'            => 'Таймаўт актыўнасці',
'Online timeout help'            => 'Колькасць секунд да змянення статусу знаходжання карыстальніка на форуме',
'Redirect time label'            => 'Чаканне перанапрамку',
'Redirect time help'            => 'Калі ўсталяваць 0 секунд, старонка перанапрамку не будзе выводзіцца',
'Setup pagination'                => 'Налады адлюстроўвання колькасці тэм, паведамленняў і праглядаў па змаўчанні',
'Setup pagination legend'        => 'Налады адлюстроўвання',
'Topics per page label'            => 'Тэм на старонцы',
'Posts per page label'            => 'Паведамленняў на старонцы',
'Topic review label'            => 'На старонцы адказу',
'Topic review help'                => 'Новыя першымі. 0 — адключыць.',
'Setup reports'                    => 'Метад атрымання апавяшчэнняў у тэмах',
'Setup reports legend'            => 'Налады апавяшчэння',
'Reporting method'                => 'Метад апавяшчэння',
'Report internal label'            => 'Па ўнутранай сістэме апавяшчэнняў.',
'Report both label'                => 'Па ўнутранай сістэме апавяшчэнняў і выкарыстоўваючы эл. пошту па спісе рассылання.',
'Report email label'            => 'Выкарыстоўваючы эл. пошту па спісе рассылання.',
'Setup URL'                        => 'URL-схема (<abbr title="Search Engine Friendly">SEF</abbr> URLs) для старонак',
'Setup URL legend'                => 'URL-схема',
'URL scheme info'                => '<strong>УВАГА!</strong> Калі абраць схему, адрозную ад <strong>Default</strong>, то неабходна скапіяваць/перайменаваць файл <em>.htaccess.dist</em> у <em>.htaccess</em> у корані форуму. Сервер, на якім усталяваны форум, павінен быць сканфігураваны з падтрымкай mod_rewrite і павінен дазваляць выкарыстоўванне файлаў <em>.htaccess</em>. Калі выкарыстоўваецца вэб-сервер, адрозны ад Apache, калі ласка, звярніцесь да дакументацыі гэтага сервера.',
'URL scheme label'                => 'URL-схема',
'URL scheme help'                =>    'Змяняйце налады ў тым выпадку, калі вы разумееце сэнс прыведзенай інфармацыі.',
'Setup links'                    => 'Дадатковыя спасылкі ў прыведзеным меню',
'Setup links info'                => 'Форма ўводу HTML-кода гіперспасылак. У галоўнае меню можа быць дададзена любая колькасць пунктаў. Фармат запісу дадання новых пунктаў такі: X = &lt;a href="URL"&gt;СПАСЫЛКА&lt;/a&gt; дзе X — пазіцыя, на якую спасылка павінна быць памешчана (напрыклад, 0 уставіць новы пункт у самым пачатку, а 2 уставіць новы пункт пасля пункту «Карыстальнікі»). Кожны новы пункт пішацца з новага радка.',
'Setup links legend'            => 'Элементы меню',
'Enter links label'                => 'Дадатковыя спасылкі',
'Error no board title'            => 'Неабходна ўвесці назву форуму.',
'Error timeout value'            => 'Значэнне параметру «Таймаўт актыўнасці» павінна быць менш за значэнне параметру «Таймаўт наведвання».',
// Features section
'Features general'                => 'Асноўныя налады функцыяналу PunBB',
'Features general legend'        => 'Налады функцыяналу',
'Searching'                        => 'Пошук',
'Search all label'                => 'Дазволіць карыстальнікам пошук па ўсіх форумах адначасова. Адключыце функцыю, калі сервер моцна нагружаны.',
'User ranks'                    => 'Рангі',
'User ranks label'                => 'Уключыць карыстальніцкія рангі, заснаваныя на колькасці паведамленняў.',
'Censor words'                    => 'Цэнзура',
'Censor words label'            => 'Уключыць функцыю забароны непажаданых слоў.',
'Quick jump'                    => 'Хуткі пераход',
'Quick jump label'                => 'Уключыць выпадаючы спіс хуткага пераходу (скачка да форуму).',
'Show version'                    => 'Паказваць версію',
'Show version label'            => 'Паказваць версію PunBB унізе старонцы.',
'Show moderators'                => 'Паказваць мадэратараў',
'Show moderators label'            => 'Паказваць мадэратараў на галоўнай старонцы.',
'Online list'                    => 'Спіс актыўнасці',
'Users online label'            => 'Адлюстроўваць спіс гасцей і зарэгістраваных карыстальнікаў, знаходзячыхся на форуме.',
'Features posting'                => 'Налады функцый у паведамленнях і тэмах',
'Features posting legend'        => 'Функцыі постынга',
'Quick post'                    => 'Хуткі адказ',
'Quick post label'                => 'Дадаць форму хуткага адказу ўнізе тэм.',
'Subscriptions'                    => 'Падпіскі',
'Subscriptions label'            => 'Дазволіць карыстальнікам падпіску на атрыманне апавяшчэнняў аб новых адказах у тэме па электроннай пошце.',
'Guest posting'                    => 'Паведамленні ад гасцей',
'Guest posting label'            => 'Госці павінны пазначыць адрас эл. пошты пры адпраўцы паведамленняў.',
'User has posted'                => 'Удзел карыстальніка',
'User has posted label'            => 'Адлюстроўваць кропку перад індыкатарам стану тэмы, калі карыстальнік адказваў у ёй раней. Адключыце функцыю, калі нагрузка на сервер высокая.',
'Topic views'                    => 'Прагляд тэм',
'Topic views label'                => 'Адлюстроўваць колькасць праглядаў тэм. Адключыце функцыю, калі сервер моцна нагружаны.',
'User post count'                => 'Колькасць паведамленняў',
'User post count label'            => 'Адлюстроўваць колькасць паведамленняў карыстальніка ў паведамленнях, профіле і спісе карыстальнікаў.',
'User info'                        => 'Інфармацыя карыстальніка',
'User info label'                => 'Адлюстроўваць месцазнаходжанне карыстальніка, дату рэгістрацыі, колькасць паведамленняў, адрасы эл. пошты і сайту ў паведамленнях.',
'Features posts'                => 'Апрацоўка зместу тэм і паведамленняў',
'Features posts legend'            => 'Апрацоўка зместу тэм і паведамленняў',
'Post content group'            => 'Опцыі паведамлення',
'Allow BBCode label'            => 'Дазволіць BB-коды ў паведамленнях (рэкамендуецца).',
'Allow img label'                => 'Дазволіць BB-тэг [img] у паведамленнях.',
'Smilies in posts label'        => 'Канвертаваць тэкставыя смайлы ў паведамленнях у графічныя.',
'Make clickable links label'    => 'Пераўтвараць URL-адрасы ў гіперспасылкі ў паведамленнях.',
'Allow capitals group'            => 'Загалоўныя літары',
'All caps message label'        => 'Дазволіць усе загалоўныя літары ў паведамленнях.',
'All caps subject label'        => 'Дазволіць усе загалоўныя літары ў паведамленнях.',
'Indent size label'                => 'Водступ у тэгу [code]',
'Indent size help'                => 'Велічыня водступу радка блока тэксту. Калі ўсталяваць 8, будзе выкарыстаны звычайны водступ',
'Quote depth label'                => 'Максімальная ўкладзенасць [quote]',
'Quote depth help'                => 'Максімальны парадак уключэння тэга [quote] унутр іншых тэгаў [quote]. Змест укладзеных тэгаў цытавання, парадак якіх большы за названае значэнне, будзе адкінуты',
'Features sigs'                    =>    'Подпіс карыстальніка і яго змест',
'Features sigs legend'            => 'Уласцівасці подпісу',
'Allow signatures'                => 'Дазволіць подпісы',
'Allow signatures label'        => 'Дазваляць карыстальнікам прыядноўваць подпісы да паведамленняў.',
'Signature content group'        => 'Змест подпісу',
'BBCode in sigs label'            => 'Дазволіць BB-коды ў подпісах.',
'Img in sigs label'                => 'Дазволіць BB-тэг [img] у подпісах (не рэкамендуецца).',
'All caps sigs label'            => 'Дазволіць подпісы, якія складаюцца з загалоўных літар.',
'Smilies in sigs label'            => 'Канвертаваць тэкставыя смайлы ў графічныя ў подпісах.',
'Max sig length label'            => 'Максімум сімвалаў',
'Max sig lines label'            => 'Максімум радкоў',
'Features Avatars'                => 'Аватары карыстальнікаў (параметры загрузкі і памеру)',
'Features Avatars legend'        => 'Налады аватара карыстальніка',
'Allow avatars'                    => 'Дазволіць аватары',
'Allow avatars label'            => 'Дазволіць карыстальнікам загружаць аватары.',
'Avatar directory label'        => 'Каталог для загрузкі',
'Avatar directory help'            => 'Адносна каранёвага каталога форуму PunBB. PHP павінен мець правы запісу на гэты каталог.',
'Avatar Max width label'        => 'Шырыня аватара',
'Avatar Max width help'            => 'Максімальная шырыня ў пікселях (рэкамендуецца 60).',
'Avatar Max height label'        => 'Вышыня аватара',
'Avatar Max height help'        => 'Максімальная вышыня ў піскселях (рэкамендуецца 60).',
'Avatar Max size label'            => 'Памер аватара',
'Avatar Max size help'            => 'Максімальны памер у байтах (рэкамендуецца 15360).',
'Features update'                => 'Аўтаматычна правяраць абнаўленні',
'Features update info'            => 'PunBB можа перыядычна правяраць наяўнасць важных абнаўленняў. Яны могуць быць прызначаныя для абнаўлення версіі форуму ці ўсталявання пашырэнняў, выпраўляючых памылкі і ўразлівасці. Калі абнаўленні будуць даступныя, адміністратар форуму атрымае апавяшчэнне.',
'Features update disabled info'    => 'Немагчыма аўтаматычна праверыць абнаўленні. Для падтрымкі дадзенай функцыі асяроддзе PHP у якім запушчаны PunBB, павінна падтрымліваць <a href="http://www.php.net/manual/en/ref.curl.php">пашырэнне cURL</a>, <a href="http://www.php.net/manual/en/function.fsockopen.php">функцыю fsockopen() </a> або быць сканфігуравана з падтрымкай <a href="http://www.php.net/manual/en/ref.filesystem.php#ini.allow-url-fopen">allow_url_fopen</a>.',
'Features update legend'        => 'Аўтаматычнае абнаўленне',
'Update check'                    => 'Правяраць абнаўленні',
'Update check label'            => 'Уключыць аўтаматычную праверку абнаўленняў.',
'Check for versions'            => 'Правяраць новыя версіі',
'Auto check for versions'        => 'Уключыць праверку новых версій пашырэнняў.',
'Features mask passwords'            => 'Адлюстроўванне пароляў',
'Features mask passwords legend'    => 'Адлюстроўванне пароляў',
'Features mask passwords info'        => 'Калі ўсталяваны, то PunBB будзе хаваць усе паролі ў формах і запытваць пацверджанне. Калі выключана, пароль будзе адкрыты і карыстальнікі будуць бачыць пароль пры ўводзе. Поле ўводу пароля на форме аўтарызацыі заўжды будзе схавана (незалежна ад гэтай опцыі).',
'Enable mask passwords'                => 'Хаваць паролі',
'Enable mask passwords label'        => 'Уключыць хаванне пароляў у форме.',
'Features gzip'                    => 'Сціскаць выходныя дадзеныя, выкарыстоўваючы gzip',
'Features gzip legend'            => 'Сціскаць дадзеныя',
'Features gzip info'            => 'Калі гэта функцыя ўключана, PunBB будзе перадаваць браўзеру дадзеныя, сціснутыя gzip. Гэта скараціць нагрузку на паласу прапускання дадзеных, але крыху павялічыць нагрузку на працэсар (CPU). Для гэтай функцыі трэба, каб PHP быў сканфігураваны з падтрымкай zlib (--with-zlib). Заўвага: Калі выкарыстоўваецца адзін з Apache-модуляў: mod_gzip ці mod_deflate, усталяваных для сціскання PHP сцэнарыяў, дадзеную функцыю варта адключыць.',
'Enable gzip'                    => 'Сцісканне gzip',
'Enable gzip label'                => 'Уключыць сцісканне выходных дадзеных, выкарыстоўваючы gzip.',
// Announcements section
'Announcements head'            => 'Адлюстроўванне аб\'яў на форуме',
'Announcements legend'            => 'Аб\'ява',
'Enable announcement'            => 'Уключыць аб\'яву',
'Enable announcement label'        => 'Уключыць вывад аб\'явы.',
'Announcement heading label'    => 'Загаловак',
'Announcement message label'    => 'Змест',
'Announcement message help'        => 'У аб\'яве можна выкарыстоўваць HTML. Тэкст аб\'явы апрацоўваецца інакш, чым паведамленні.',
'Announcement message default'    => '<p>Увядзіце сюды змест аб\'явы.</p>',
// Registration section
'Registration new'                => 'Новыя рэгістрацыі',
'New reg info'                    => 'Можна выкарыстоўваць праверку ўсіх новых рэгістрацый. Калі праверка рэгістрацый уключана, карыстальнік атрымлівае на адрас электроннай пошты паведамленне са спасылкай актывацыі. Ён можа скарыстацца ёй для аўтарызацыі. Гэта функцыя таксама патрабуе ад карыстальніка пацвярджаць адрас электроннай пошты, калі ён захоча яго змяніць пасля рэгістрацыі. Гэта эфектыўны шлях абмежавання пустых рэгістрацый, дазваляючы быць упэўненым, што ва ўсіх карыстальнікаў у профіле паказаны рэальны адрас электроннай пошты.',
'Registration new legend'        => 'Налады новых рэгістрацый',
'Allow new reg'                    => 'Новыя рэгістрацыі',
'Allow new reg label'            => 'Дазволіць новыя рэгістрацыі. Адключаць толькі ў асобых выпадках.',
'Verify reg'                    => 'Праверка рэгістрацыі',
'Verify reg label'                => 'Пацвярджаць рэгістрацыі па электроннай пошце.',
'Reg e-mail group'                => 'Адрас эл. пошты рэгістрацыі',
'Allow banned label'            => 'Дазволіць рэгістрацыю з адрасам эл. пошты, які заблакаваны.',
'Allow dupe label'                => 'Дазволіць рэгістрацыю з адрасам эл. пошты, які ўжо належыць іншаму карыстальніку.',
'Report new reg'                => 'Апавяшчэнне па эл. пошце',
'Report new reg label'            => 'Апавяшчаць карыстальнікаў са спісу рассылкі аб рэгістрацыі новых карыстальнікаў.',
'E-mail setting group'            => 'Базавыя налады пошты',
'Display e-mail label'            => 'Паказваць адрас эл. пошты іншым карыстальнікам.',
'Allow form e-mail label'        => 'Хаваць адрас эл. пошты, але дазволіць адпраўляць паштовыя паведамленні праз форум.',
'Disallow form e-mail label'    => 'Хаваць адрас эл. пошты і забараніць адпраўляць паштовыя паведамленні праз форум.',
'Registration rules'            => 'Правілы форуму (выкарыстоўванне і афармленне правілаў форуму)',
'Registration rules info'        => 'Можна абавязаць карыстальнікаў прымаць правілы форуму пры рэгістрацыі (напішыце іх у тэкставым полі ніжэй). Правілы заўжды будуць даступныя для прагляду па спасылцы з галоўнага меню на кожнай старонцы форуму.',
'Registration rules legend'        => 'Правілы форуму',
'Require rules'                    => 'Выкарыстоўваць правілы',
'Require rules label'            => 'Абавязаць карыстальнікаў прымаць правілы форуму перад праходжаннем рэгістрацыі.',
'Compose rules label'            => 'Тэкст правілаў',
'Compose rules help'            => 'Можна выкарыстоўваць HTML у гэтым блоку.',
'Rules default'                    => 'Увядзіце сюды вашы правілы.',
// Email section
'E-mail addresses'                => 'Паштовыя адрасы і спіс рассылкі',
'E-mail addresses legend'        => 'Адрасы эл. пошты',
'Admin e-mail'                    => 'Эл. пошта адміністратара',
'Webmaster e-mail label'        => 'Эл. пошта вэб-майстра',
'Webmaster e-mail help'            => 'Адрас адпраўніка, які будзе выкарыстаны форумам для рассылкі',
'Mailing list label'            => 'Спіс рассылкі',
'Mailing list help'                => 'Падзяляйце адрасы атрымальнікаў сігналаў і/альбо апавяшчэнняў аб новых рэгістрацыях коскай',
'E-mail server'                    => 'Налады паштовага сервера для адпраўкі лістоў ад форума',
'E-mail server legend'            => 'Паштовы сервер',
'E-mail server info'            => 'У большасці выпадкаў PunBB без праблем адпраўляе паштовыя паведамленні, выкарыстоўваючы ўнутраны паштовы сэрвіс, у гэтым выпадку можна прапусціць гэтыя налады. Таксама PunBB можа быць сканфігураваны для выкарыстоўвання знешняга паштовага сервера. Увядзіце адрас знешняга сервера і, калі патрабуецца, пакажыце нумар парта SMTP-сервера, калі  SMTP-сервер які выкарыстоўваецца не можа працаваць праз стандартны 25 порт (напрыклад: mail.example.com:3580).',
'SMTP address label'            => 'Адрас SMTP-сервера',
'SMTP address help'                => 'Для знешніх сервераў. Пакіньце пустым, каб выкарыстоўваць унутранную паштовую службу',
'SMTP username label'            => 'Імя карыстальніка SMTP-сервера',
'SMTP username help'            => 'Не патрабуецца большасці SMTP-сервераў',
'SMTP password label'            => 'Пароль SMTP',
'SMTP password help'            => 'Не патрабуецца большасці SMTP-сервераў',
'SMTP SSL'                        => 'Уключыць SSL для SMTP',
'SMTP SSL label'                => 'Шыфраваць SMTP-злучэнне, выкарыстоўваючы SSL. Выбірайце, толькі калі ўпэўнены, што  версія PHP якая выкарыстоўваецца падтрымлівае SSL і ўжываемы SMTP-сервер патрабуе гэтага.',
'Error invalid admin e-mail'    =>    'Уведзены адрас электроннай пошты адміністратара змяшчае памылку.',
'Error invalid web e-mail'        =>    'Уведзены адрас электроннай пошты вэб-майстра змяшчае памылку.',
// Maintenance section
'Maintenance head'                => 'Усталяванне рэжыму прафілактыкі і афармленне паведамлення аб прымяненні дадзенага рэжыму',
'Maintenance mode info'            => '<strong>Важна!</strong> Форум будзе даступны толькі адміністратарам. Дадзены рэжым варта выкарыстоўваць, калі трэба зачыніць форум для правядзення якіх-небудзь работ і змянення налад.',
'Maintenance mode warn'            => '<strong>Увага!</strong> НЕ ВЫХОДЗЬЦЕ, пакуль форум знаходзіцца ў рэжыме прафілактыкі. Вы не зможаце ўвайсці зноў!',
'Maintenance legend'            => 'Прафілактыка',
'Maintenance mode'                => 'Рэжым прафілактыкі',
'Maintenance mode label'        => 'Усталяваць на форуме рэжым прафілактыкі.',
'Maintenance message label'        => 'Паведамленне аб рэжыме прафілактыкі',
'Maintenance message help'        => 'Паведамленне, якое будзе паказана, калі форум пяройдзе ў рэжым прафілактыкі. Калі не ўвесці свой варыянт, будзе паказана стандартнае паведамленне. Для напісання паведамлення можна выкарыстоўваць HTML-код',
'Maintenance message default'    => 'Форум часова пераведзены ў рэжым прафілактыкі. Калі ласка, паспрабуйце зайсці зноў праз некалькі хвілін.<br /><br />Адміністрацыя',
);