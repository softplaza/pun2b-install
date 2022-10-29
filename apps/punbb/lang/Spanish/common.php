<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'			=>	'ltr',	// ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'			=>	'es',

// Number formatting
'lang_decimal_point'	=>	',',
'lang_thousands_sep'	=>	'.',

// Notices
'Bad request'				=>	'Solicitud errónea. El enlace seguido es incorrecto o ha caducado.',
'No view'					=>	'Careces de permisos para ver estos foros.',
'No permission'				=>	'Careces de permisos para acceder a esta página',
'CSRF token mismatch'		=>	'No se ha podido confirmar el certificado de seguridad. Una posible causa de esto es que ha pasado mucho tiempo desde que iniciaste sesión en la página e intentaste enviar un formulario o seguir un enlace. Si este es el caso y quieres continuar con la acción, por favor, pulsa el botón Confirmar. Si no, debes pulsar el botón Cancelar para volver atrás.',
'No cookie'					=>	'Parece que has iniciado sesión con éxito, pero no se ha guardado una cookie. Por favor, verifica la configuración de tu navegador y, en caso necesario, activa las cookies para este sitio.',


// Miscellaneous
'Forum index'				=>	'Índice del foro',
'Submit'					=>	'Enviar',	// "name" of submit buttons
'Cancel'					=>	'Cancelar', // "name" of cancel buttons
'Preview'					=>	'Previsualizar',	// submit button to preview message
'Delete'					=>	'Borrar',
'Split'						=>	'Dividir',
'Ban message'				=>	'Estás expulsado de este foro',
'Ban message 2'				=>	'La expulsión expira al final de %s.',
'Ban message 3'				=>	'El administrador o moderador que te ha expulsado te ha dejado el siguiente mensaje:',
'Ban message 4'				=>	'Por favor, dirige cualquier pregunta al administrador del foro en %s.',
'Never'						=>	'Nunca',
'Today'						=>	'Hoy',
'Yesterday'					=>	'Ayer',
'Forum message'				=>	'Mensaje del foro',
'Maintenance warning'		=>	'<strong>¡ALERTA! %s activado.</strong> NO CIERRES LA SESIÓN porque no podrás iniciarla de nuevo.',
'Maintenance mode'			=>	'Modo de mantenimiento',
'Redirecting'				=>	'Redirigiendo',
'Forwarding info'			=>	'Deberías ser redirigido a una nueva página en %s %s.',
'second'					=>	'segundo',	// singular
'seconds'					=>	'segundos',	// plural
'Click redirect'			=>	'Haz clic aquí si no quieres esperar más (o si tu explorador no te reenvía automáticamente)',
'Invalid e-mail'			=>	'El e-mail introducido es inválido',
'New posts'					=>	'Mensajes nuevos',	// the link that leads to the first new post
'New posts title'			=>	'Buscar temas con nuevos mensajes desde tu última visita.',	// the popup text for new posts links
'Active topics'				=>	'Temas activos',
'Active topics title'		=>	'Buscar temas con mensajes recientes.',
'Unanswered topics'			=>	'Temas sin respuesta.',
'Unanswered topics title'	=>	'Temas que no han sido respondidos.',
'Username'					=>	'Nombre de usuario (nick)',
'Registered'				=>	'Registrado',
'Write message'				=>	'Escribir mensaje:',
'Forum'						=>	'Foro',
'Posts'						=>	'Mensajes',
'Pages'						=>	'Páginas',
'Page'						=>	'Página',
'BBCode'					=>	'BBCode',	// You probably shouldn't change this
'Smilies'					=>	'Emoticonos',
'Images'					=>	'Imágenes',
'You may use'				=>	'Puedes usar: %s',
'and'						=>	'y',
'Image link'				=>	'imagen',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'						=>	'escribió',	// For [quote]'s (e.g., User wrote:)
'Code'						=>	'Código',		// For [code]'s
'Forum mailer'				=>	'%s administrador de correo',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Write message legend'		=>	'Escribe tu mensaje',
'Required information'		=>	'Información obligatoria',
'Reqmark'					=>	'*',
'Required'					=>	'(Obligatorio)',
'Required warn'				=>	'Los campos marcados con %s deben ser completados.',
'Crumb separator'			=>	' »&#160;', // The character or text that separates links in breadcrumbs
'Title separator'			=>	' - ',
'Page separator'			=>	'&#160;', //The character or text that separates page numbers
'Spacer'					=>	'…', // Ellipsis for paginate
'Paging separator'			=>	' ', //The character or text that separates page numbers for page navigation generally
'Previous'					=>	'Anterior',
'Next'						=>	'Siguiente',
'Cancel redirect'			=>	'Operación cancelada. Redirigiendo …',
'No confirm redirect'		=>	'No se envió confirmación. Operación cancelada. Redirigiendo …',
'Please confirm'			=>	'Por favor, confirma:',
'Help page'					=>	'Ayuda con: %s',
'Re'						=>	'Re:',
'Page info'					=>	'(Página %1$s de %2$s)',
'Item info single'			=>	'%s [ %s ]',
'Item info plural'			=>	'%s [ %s al %s de %s ]', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator'			=>	' ', // e.g. 1 Page | 10 Topics
'Powered by'				=>	'Funciona gracias a <strong>%s</strong>, con apoyo de <strong>%s</strong>',
'Maintenance'				=>	'Mantenimiento',
'Installed extension'		=>	'La extensión oficial %s está instalada. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',
'Installed extensions'		=>	'Las extensiones oficiales <span id="extensions-used" title="%s">%s se encuentran instaladas actualmente</span>. Copyright &copy; 2003&ndash;2012 <a href="http://punbb.informer.com/">PunBB</a>.',

// CSRF confirmation form
'Confirm'					=>	'Confirmar',	// Button
'Confirm action'			=>	'Confirmar acción',
'Confirm action head'		=>	'Por favor, confirma o cancela tu última acción',

// Title
'Title'						=>	'Título',
'Member'					=>	'Miembro',	// Default title
'Moderator'					=>	'Moderador',
'Administrator'				=>	'Administrador',
'Banned'					=>	'Expulsado',
'Guest'						=>	'Invitado',

// Stuff for include/parser.php
'BBCode error 1'			=>	'[/%1$s] se encontró sin [%1$s]',
'BBCode error 2'			=>	'la etiqueta [%s] esta vacía.',
'BBCode error 3'			=>	'[%1$s] se abrió sin [%2$s], y no esta permitido.',
'BBCode error 4'			=>	'[%s] se abrió dentro de si mismo, y no esta permitido',
'BBCode error 5'			=>	'[%1$s] se encontró sin [/%1$s]',
'BBCode error 6'			=>	'La etiqueta [%s] tiene un atributo vacío.',
'BBCode nested list'		=>	'Las etiquetas [list] no pueden ser anidadas.',
'BBCode code problem'		=>	'Hay un problema con las etiquetas [code]',

// Stuff for the navigator (top of every page)
'Index'						=>	'Inicio',
'User list'					=>	'Lista de usuarios',
'Rules'						=>  'Reglas',
'Search'					=>  'Búsqueda',
'Register'					=>  'Registrarse',
'register'					=>	'registrarte',
'Login'						=>  'Entrar',
'login'						=>	'entrar',
'Not logged in'				=>  'No has iniciado sesión.',
'Profile'					=>	'Perfil',
'Logout'					=>	'Cerrar sesión',
'Logged in as'				=>	'Sesión iniciada como %s.',
'Admin'						=>	'Administración',
'Last visit'				=>	'última visita %s',
'Mark all as read'			=>	'Marcar todos los temas como leídos',
'Login nag'					=>	'Por favor, inicia sesión o registrate.',
'New reports'				=>	'Nuevos informes',

// Alerts
'New alerts'				=>	'Nuevas alertas',
'Maintenance alert'			=>	'<strong>¡ALERTA! Modo de mantenimiento activado.</strong> Este foro está en modo de mantenimiento. <em>NO CIERRES LA SESIÓN</em>, si lo haces no podrás iniciarla de nuevo.',
'Updates'					=>	'Actualizaciones PunBB',
'Updates failed'			=>	'El último intento de comprobar actualizaciones en el servicio de punbb.informer.com falló. Probablemente significa que el servicio está saturado temporalmente o fuera de servicio. Si esta alerta no desparece en un día o dos, debes desactivar las actualizaciones automáticas y comprobar las actualizaciones manualmente.',
'Updates version n hf'		=>	'Una nueva versión de %s, está disponible para descargar en <a href="http://punbb.informer.com/">punbb.informer.com</a>. Además, uno o más parches para las extensiones están disponibles para instalar en la pestaña "Extensiones" del panel de control de la administración.',
'Updates version'			=>	'Una nueva versión de %s, está disponible para descargar en <a href="http://punbb.informer.com/">punbb.informer.com</a>.',
'Updates hf'				=>	'Uno o más parches para las extensiones están disponibles para instalar en la pestaña "Extensiones" del panel de control de la administración.',
'Database mismatch'			=>	'No coincide la versión de la base de datos',
'Database mismatch alert'	=>	'Tu base de datos está configurada para usarse con una versión más reciente de PunBB. Este desajuste puede producir que el foro no funcione correctamente. Es recomendable que actualices tu foro a la última versión de PunBB.',

// Stuff for Jump Menu
'Go'						=>	'Ir',		// submit button in forum jump
'Jump to'					=>	'Saltar al foro:',

// For extern.php RSS feed
'RSS description'			=>	'Los temas más recientes en %s.',
'RSS description topic'		=>	'Los mensajes más recientes en %s.',
'RSS reply'					=>	'Re: ',	// The topic subject will be appended to this string (to signify a reply)

// Accessibility
'Skip to content'					=>	'Saltar al contenido del foro',

// Debug information
'Querytime'					=>	'Generado en %1$s segundos (%2$s%% PHP - %3$s%% DB) con %4$s consultas',
'Debug table'				=>	'Información de debug',
'Debug summary'				=>	'Información del rendimiento de las consultas a la base de datos',
'Query times'				=>	'Vez (Veces)',
'Query'						=>	'Consulta',
'Total query time'			=>	'Tiempo total de consulta',

// Error message
'Forum error header'		=> 'Lo sentimos! La página no pudo ser cargada.',
'Forum error description'	=> 'Este es probablemente un error temporal. Solo recarga la página e inténtalo otra vez. Si el problema persiste, por favor inténtalo de nuevo en 5 o 10 minutos.',
'Forum error location'		=> 'El error ocurrió en la línea %1$s en %2$s',
'Forum error db reported'	=> 'Base de datos reportada:',
'Forum error db query'		=> 'Consulta fallida:',

);

