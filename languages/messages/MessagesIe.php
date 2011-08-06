<?php
/** Interlingue (Interlingue)
 *
 * See MessagesQqq.php for message documentation incl. usage of parameters
 * To improve a translation please visit http://translatewiki.net
 *
 * @ingroup Language
 * @file
 *
 * @author Jmb
 * @author Malafaya
 * @author Reedy
 * @author Remember the dot
 * @author Renan
 * @author Valodnieks
 * @author לערי ריינהארט
 */

$namespaceNames = array(
	NS_MEDIA            => 'Media',
	NS_SPECIAL          => 'Special',
	NS_TALK             => 'Discussion',
	NS_USER             => 'Usator',
	NS_USER_TALK        => 'Usator_Discussion',
	NS_PROJECT_TALK     => '$1_Discussion',
	NS_FILE             => 'File',
	NS_FILE_TALK        => 'File_Discussion',
	NS_MEDIAWIKI        => 'MediaWiki',
	NS_MEDIAWIKI_TALK   => 'MediaWiki_Discussion',
	NS_TEMPLATE         => 'Avise',
	NS_TEMPLATE_TALK    => 'Avise_Discussion',
	NS_HELP             => 'Auxilie',
	NS_HELP_TALK        => 'Auxilie_Discussion',
	NS_CATEGORY         => 'Categorie',
	NS_CATEGORY_TALK    => 'Categorie_Discussion',
);

$specialPageAliases = array(
	'Activeusers'               => array( 'Usatores_activ' ),
	'Allmessages'               => array( 'Omni_li_missages' ),
	'Allpages'                  => array( 'Omni_li_págines' ),
	'Ancientpages'              => array( 'Págines_antiqui' ),
	'Blankpage'                 => array( 'Págine_in_blanc' ),
	'Block'                     => array( 'Blocar', 'Blocar_IP', 'Blocar_usator' ),
	'Blockme'                   => array( 'Blocar_in_mi_self' ),
	'Booksources'               => array( 'Fontes_de_libres' ),
	'BrokenRedirects'           => array( 'Redirectionmentes_ínperfect' ),
	'ChangePassword'            => array( 'Change_parol-clave' ),
	'ComparePages'              => array( 'Comparar_págines' ),
	'Confirmemail'              => array( 'Confirmar_email' ),
	'Contributions'             => array( 'Contributiones' ),
	'CreateAccount'             => array( 'Crear_conto' ),
	'Deadendpages'              => array( 'Págines_moderat' ),
	'DeletedContributions'      => array( 'Contributiones_deletet' ),
	'Disambiguations'           => array( 'Disambiguitones' ),
	'DoubleRedirects'           => array( 'Redirectionmentes_duplic' ),
	'EditWatchlist'             => array( 'Redacter_liste_de_págines_vigilat' ),
	'Emailuser'                 => array( 'Email_de_usator' ),
	'Export'                    => array( 'Exportar' ),
	'Fewestrevisions'           => array( 'Revisiones_max_poc' ),
	'FileDuplicateSearch'       => array( 'Sercha_de_file_duplicat' ),
	'Filepath'                  => array( 'Viette_de_file' ),
	'Import'                    => array( 'Importar' ),
	'Invalidateemail'           => array( 'Email_ínvalid' ),
	'BlockList'                 => array( 'Liste_de_bloc', 'Liste_de_bloces', 'Liste_de_bloc_de_IP' ),
	'LinkSearch'                => array( 'Sercha_de_catenun' ),
	'Listadmins'                => array( 'Liste_de_administratores' ),
	'Listbots'                  => array( 'Liste_de_machines' ),
	'Listfiles'                 => array( 'Liste_de_files', 'Liste_de_file', 'Liste_de_figura' ),
	'Listgrouprights'           => array( 'Jures_de_gruppe_de_liste', 'Jures_de_gruppe_de_usator' ),
	'Listredirects'             => array( 'Liste_de_redirectionmentes' ),
	'Listusers'                 => array( 'Liste_de_usatores', 'Liste_de_usator' ),
	'Lockdb'                    => array( 'Serrar_DB' ),
	'Log'                       => array( 'Diarium', 'Diariumes' ),
	'Lonelypages'               => array( 'Págines_solitari', 'Págines_orfan' ),
	'Longpages'                 => array( 'Págines_long' ),
	'MergeHistory'              => array( 'Historie_de_fusion' ),
	'MIMEsearch'                => array( 'Serchar_MIME' ),
	'Mostcategories'            => array( 'Plu_categories' ),
	'Mostimages'                => array( 'Files_max_ligat', 'Plu_files', 'Plu_figuras' ),
	'Mostlinked'                => array( 'Págines_max_ligat', 'Max_ligat' ),
	'Mostlinkedcategories'      => array( 'Categories_max_ligat', 'Categories_max_usat' ),
	'Mostlinkedtemplates'       => array( 'Avises_max_ligat', 'Avises_max_usat' ),
	'Mostrevisions'             => array( 'Plu_revisiones' ),
	'Movepage'                  => array( 'Mover_págine' ),
	'Mycontributions'           => array( 'Mi_contributiones' ),
	'Mypage'                    => array( 'Mi_págine' ),
	'Mytalk'                    => array( 'Mi_discussion' ),
	'Myuploads'                 => array( 'Mi_cargamentes' ),
	'Newimages'                 => array( 'Nov_files', 'Nov_figuras' ),
	'Newpages'                  => array( 'Nov_págines' ),
	'PasswordReset'             => array( 'Recomensar_parol-clave' ),
	'PermanentLink'             => array( 'Catenun_permanen' ),
	'Popularpages'              => array( 'Págines_populari' ),
	'Preferences'               => array( 'Preferenties' ),
	'Prefixindex'               => array( 'Index_de_prefixe' ),
	'Protectedpages'            => array( 'Págines_gardat' ),
	'Protectedtitles'           => array( 'Titules_gardat' ),
	'Randompage'                => array( 'Sporadic', 'Págine_sporadic' ),
	'Randomredirect'            => array( 'Redirectionmente_sporadic' ),
	'Recentchanges'             => array( 'Nov_changes' ),
	'Recentchangeslinked'       => array( 'Changes_referet', 'Changes_relatet' ),
	'Revisiondelete'            => array( 'Deleter_revision' ),
	'RevisionMove'              => array( 'Mover_revision' ),
	'Search'                    => array( 'Serchar' ),
	'Shortpages'                => array( 'Págines_curt' ),
	'Specialpages'              => array( 'Págines_special' ),
	'Statistics'                => array( 'Statistica' ),
	'Tags'                      => array( 'Puntales' ),
	'Unblock'                   => array( 'Desblocar' ),
	'Uncategorizedcategories'   => array( 'Categories_íncategorizet' ),
	'Uncategorizedimages'       => array( 'Files_íncategorizet', 'Figuras_íncategorizet' ),
	'Uncategorizedpages'        => array( 'Págines_íncategorizet' ),
	'Uncategorizedtemplates'    => array( 'Avises_íncategorizet' ),
	'Undelete'                  => array( 'Restaurar' ),
	'Unlockdb'                  => array( 'Disserrar_DB' ),
	'Unusedcategories'          => array( 'Categories_sin_use' ),
	'Unusedimages'              => array( 'Files_sin_use', 'Figuras_sin_use' ),
	'Unusedtemplates'           => array( 'Avises_sin_use' ),
	'Unwatchedpages'            => array( 'Págines_desvigilat' ),
	'Upload'                    => array( 'Cargar_file' ),
	'UploadStash'               => array( 'Cargamente_stash_de_file' ),
	'Userlogin'                 => array( 'Intrar' ),
	'Userlogout'                => array( 'Surtida' ),
	'Userrights'                => array( 'Jures_de_usator', 'Crear_administrator', 'Crear_machine' ),
	'Wantedcategories'          => array( 'Categories_carit' ),
	'Wantedfiles'               => array( 'Files_carit' ),
	'Wantedpages'               => array( 'Págines_carit', 'Catenunes_ínperfect' ),
	'Wantedtemplates'           => array( 'Avises_carit' ),
	'Watchlist'                 => array( 'Liste_de_págines_vigilat' ),
	'Whatlinkshere'             => array( 'Quo_catenunes_ci' ),
	'Withoutinterwiki'          => array( 'Sin_interwiki' ),
);

$messages = array(
'underline-always' => 'Sempre',
'underline-never'  => 'Nequande',

# Dates
'sunday'        => 'soledí',
'monday'        => 'lunedí',
'tuesday'       => 'mardí',
'wednesday'     => 'mercurdí',
'thursday'      => 'jovedí',
'friday'        => 'venerdí',
'saturday'      => 'saturdí',
'sun'           => 'sol',
'mon'           => 'lun',
'tue'           => 'mar',
'wed'           => 'mer',
'thu'           => 'jov',
'fri'           => 'ven',
'sat'           => 'sat',
'january'       => 'januar',
'february'      => 'februar',
'march'         => 'marte',
'april'         => 'april',
'may_long'      => 'may',
'june'          => 'junio',
'july'          => 'julí',
'august'        => 'august',
'september'     => 'septembre',
'october'       => 'octobre',
'november'      => 'novembre',
'december'      => 'decembre',
'january-gen'   => 'januar',
'february-gen'  => 'februar',
'march-gen'     => 'marte',
'april-gen'     => 'april',
'may-gen'       => 'may',
'june-gen'      => 'junio',
'july-gen'      => 'juli',
'august-gen'    => 'august',
'september-gen' => 'septembre',
'october-gen'   => 'octobre',
'november-gen'  => 'novembre',
'december-gen'  => 'decembre',
'jan'           => 'jan',
'feb'           => 'feb',
'mar'           => 'mar',
'apr'           => 'apr',
'may'           => 'may',
'jun'           => 'jun',
'jul'           => 'jul',
'aug'           => 'aug',
'sep'           => 'sep',
'oct'           => 'oct',
'nov'           => 'nov',
'dec'           => 'dec',

# Categories related messages
'pagecategories'         => '{{PLURAL:$1|Categorie|Categories}}',
'category_header'        => 'Articules in categorie "$1"',
'category-media-header'  => 'Multimedia in categorie "$1"',
'listingcontinuesabbrev' => 'cont.',

'mainpagetext' => "'''Software del wiki installat con successe.'''",

'about'         => 'Concernent',
'article'       => 'Articul',
'newwindow'     => '(inaugurar in nov planca de fenestre)',
'cancel'        => 'Anullar',
'moredotdotdot' => 'Plu...',
'mypage'        => 'Mi págine',
'mytalk'        => 'Mi discussion',
'anontalk'      => 'Discussion por ti ci IP',
'and'           => '&#32;e',

# Cologne Blue skin
'qbfind'         => 'Constatar',
'qbedit'         => 'Redacter',
'qbpageoptions'  => 'Págine de optiones',
'qbpageinfo'     => 'Págine de information',
'qbmyoptions'    => 'Mi optiones',
'qbspecialpages' => 'Págines special',

'errorpagetitle'    => 'Errore',
'returnto'          => 'Retornar a $1.',
'tagline'           => 'De {{SITENAME}}',
'help'              => 'Auxilie',
'search'            => 'Serchar',
'searchbutton'      => 'Serchar',
'go'                => 'Ear',
'searcharticle'     => 'Ear',
'history'           => 'Historie',
'history_short'     => 'Historie',
'printableversion'  => 'Version por impression',
'permalink'         => 'Catenun permanent',
'edit'              => 'Redacter',
'editthispage'      => 'Redacter',
'delete'            => 'Deleter',
'deletethispage'    => 'Deleter ti págine',
'undelete_short'    => 'Restaurar {{PLURAL:$1|1 modification|$1 modificationes}}',
'protect'           => 'Gardar',
'protectthispage'   => 'Gardar ti págine',
'unprotect'         => 'Desgardar',
'unprotectthispage' => 'Desgardar ti págine',
'newpage'           => 'Nov págine',
'talkpage'          => 'Parlar ti págine',
'talkpagelinktext'  => 'Discurse',
'specialpage'       => 'Págine special',
'personaltools'     => 'Utensiles personal',
'postcomment'       => 'Impostar un comenta',
'articlepage'       => 'Vider li articul',
'talk'              => 'Discurse',
'views'             => 'Vistas',
'toolbox'           => 'Buxe de utensiles',
'userpage'          => 'Vider págine del usator',
'imagepage'         => 'Vider págine del image',
'viewtalkpage'      => 'Vider li discussion',
'otherlanguages'    => 'Altri lingues',
'redirectedfrom'    => '(Redirectet de $1)',
'redirectpagesub'   => 'Págine de redirecterion',
'viewcount'         => 'Ti págine ha esset consultat {{PLURAL:$1|un vez|$1 vezes}}.',
'protectedpage'     => 'Un protectet págine',
'jumpto'            => 'Saltar a:',
'jumptonavigation'  => 'navigation',
'jumptosearch'      => 'serchar',

# All link text and link target definitions of links into project namespace that get used by other message strings, with the exception of user group pages (see grouppage) and the disambiguation template definition (see disambiguations).
'aboutsite'            => 'Concernent {{SITENAME}}',
'aboutpage'            => 'Project:Concernent',
'copyright'            => 'Contenete disponibil sub $1.',
'copyrightpage'        => '{{ns:project}}:Jure de copie',
'disclaimers'          => 'Advertimentes',
'edithelp'             => 'Auxilie',
'edithelppage'         => 'Help:Qualmen modificar un págine',
'helppage'             => 'Help:Auxilie',
'mainpage'             => 'Págine principal',
'mainpage-description' => 'Principal págine',
'portal'               => 'Págine del comunité',
'portal-url'           => 'Project:Págine del comunité',

'youhavenewmessages' => 'Vu have $1 ($2).',
'newmessageslink'    => 'nov missages',
'editsection'        => 'modificar',
'editold'            => 'redacter',
'editsectionhint'    => 'Modification de section: $1',
'toc'                => 'Tabelle de contenetes',
'showtoc'            => 'monstrar',
'hidetoc'            => 'celar',
'viewdeleted'        => 'Vider $1?',

# Short words for each namespace, by default used in the namespace tab in monobook
'nstab-main'      => 'Págine',
'nstab-user'      => 'Usator',
'nstab-project'   => 'Págine de projecte',
'nstab-mediawiki' => 'Missage',
'nstab-template'  => 'Modelle',
'nstab-help'      => 'Auxilie',
'nstab-category'  => 'Categorie',

# General errors
'error'      => 'Erra',
'viewsource' => 'Vider fonte',

# Login and logout pages
'logouttext'                 => "'''Vu ha terminat vor session.'''

Vu posse continuar usar {{SITENAME}} anonimimen, o vu posse aperter un session denov quam li sam usator o quam un diferent usator.",
'yourname'                   => 'Vor nómine usatori:',
'yourpassword'               => 'Vor passa-parol:',
'yourpasswordagain'          => 'Tippa denov vor passa-parol',
'remembermypassword'         => 'Memorar mi passa-parol (per cookie) (for a maximum of $1 {{PLURAL:$1|day|days}})',
'login'                      => 'Aperter session',
'nav-login-createaccount'    => 'Crear un conto o intrar',
'loginprompt'                => 'Cookies deve esser permisset por intrar in {{SITENAME}}.',
'userlogin'                  => 'Crear un conto o intrar',
'logout'                     => 'Surtir',
'userlogout'                 => 'Surtir',
'notloggedin'                => 'Vu ne ha intrat',
'createaccount'              => 'Crear un nov conto',
'gotaccountlink'             => 'Intrar',
'badretype'                  => 'Li passa-paroles queles vu tippat ne es identic.',
'loginerror'                 => 'Erra in initiation del session',
'nocookieslogin'             => '{{SITENAME}} utilisa cookies por far intrar usatores. Vu nu ne permisse cookies. Ples permisser les e provar denov.',
'loginsuccesstitle'          => 'Apertion de session successosi',
'loginsuccess'               => 'Vu ha apertet vor session in {{SITENAME}} quam "$1".',
'wrongpassword'              => 'Li passa-parol quel vu scrit es íncorect. Prova denov.',
'mailmypassword'             => 'Invia me un nov passa-parol per electronic post',
'acct_creation_throttle_hit' => 'Vu ja ha creat $1 contos. Vu ne posse crear pli mult quam to.',
'loginlanguagelabel'         => 'Lingue: $1',

# Password reset dialog
'oldpassword' => 'Anteyan passa-parol:',
'newpassword' => 'Nov passa-parol:',
'retypenew'   => 'Confirmar nov passa-parol',

# Edit pages
'summary'          => 'Resumate:',
'minoredit'        => 'Modification minori',
'watchthis'        => 'Sequer ti articul',
'savearticle'      => 'Conservar págine',
'preview'          => 'Previder',
'showpreview'      => 'Previder págine',
'loginreqtitle'    => 'Apertion de session obligatori',
'accmailtitle'     => 'Li passa-parol es inviat.',
'accmailtext'      => "Li passa-parol por '$1' ha esset inviat a $2.",
'newarticle'       => '(Nov)',
'editing'          => 'Modification de $1',
'editingsection'   => 'modification de $1 (section)',
'editingcomment'   => 'modification de $1 (comenta)',
'copyrightwarning' => "Omni contributiones a {{SITENAME}} es considerat quam publicat sub li termines del $2 (ples vider $1 por plu mult detallies). Si vu ne vole que vor ovres mey esser modificat e distribuet secun arbitrie, ples ne inviar les. Adplu, ples contribuer solmen vor propri ovres o ovres ex un fonte quel es líber de jures. '''NE UTILISA OVRES SUB JURE EDITORIAL SIN DEFINITIV AUTORISATION!'''",

# Diffs
'lineno' => 'Linea $1:',

# Search results
'viewprevnext'   => 'Vider ($1 {{int:pipe-separator}} $2) ($3)',
'searchhelp-url' => 'Help:Auxilie',

# Quickbar
'qbsettings' => 'Personalisation del barre de utensiles',

# Preferences page
'preferences'    => 'Preferenties',
'mypreferences'  => 'Mi preferenties',
'prefsnologin'   => 'Vu ne ha intrat',
'changepassword' => 'Modificar passa-parol',
'saveprefs'      => 'Conservar preferenties',
'youremail'      => 'Vor ret-adresse:',

# Groups
'group-user'  => 'Usatores',
'group-sysop' => 'Administratores',

'group-user-member' => 'Usator',

'grouppage-user'  => '{{ns:project}}:Usatores',
'grouppage-sysop' => '{{ns:project}}:Administratores',

# Recent changes
'nchanges'          => '$1 {{PLURAL:$1|change|changes}}',
'recentchanges'     => 'Recent modificationes',
'recentchangestext' => 'Seque sur ti-ci págine li ultim modificationes al wiki.',
'rclistfrom'        => 'Monstrar li nov modificationes desde $1.',
'rcshowhideminor'   => '$1 modificationes minori',
'rcshowhidemine'    => '$1 mi redactiones',
'rclinks'           => 'Monstrar li $1 ultim modificationes fat durante li $2 ultim dies<br />$3.',
'diff'              => 'dif',
'hist'              => 'hist',
'hide'              => 'Celar',
'show'              => 'Monstrar',
'minoreditletter'   => 'm',
'newpageletter'     => 'N',

# Recent changes linked
'recentchangeslinked'         => 'Relatet modificationes',
'recentchangeslinked-feed'    => 'Relatet modificationes',
'recentchangeslinked-toolbox' => 'Relatet modificationes',

# Upload
'upload'    => 'Cargar file',
'uploadbtn' => 'Cargar file',
'filedesc'  => 'Descrition',
'savefile'  => 'Conservar file',

# Special:ListFiles
'listfiles' => 'Liste de images',

# File description page
'filehist-user'    => 'Usator',
'filehist-comment' => 'Comenta',

# Random page
'randompage' => 'Págine in hasarde',

# Statistics
'statistics' => 'Statisticas',

# Miscellaneous special pages
'ncategories'             => '$1 {{PLURAL:$1|categorie|categories}}',
'lonelypages'             => 'Orfani págines',
'uncategorizedpages'      => 'Págines sin categories',
'uncategorizedcategories' => 'Categories sin categories',
'unusedimages'            => 'Orfani images',
'wantedpages'             => 'Li max demandat págines',
'shortpages'              => 'Curt págines',
'longpages'               => 'Long págines',
'deadendpages'            => 'Págines sin exeada',
'listusers'               => 'Liste de usatores',
'newpages'                => 'Nov págines',
'ancientpages'            => 'Li max old págines',
'move'                    => 'Mover',

# Book sources
'booksources' => 'Librari fontes',

# Special:Log
'specialloguserlabel'  => 'Usator:',
'speciallogtitlelabel' => 'Titul:',

# Special:AllPages
'allpages'       => 'Omni págines',
'alphaindexline' => '$1 a $2',
'allarticles'    => 'Omni págines',
'allpagessubmit' => 'Vade',

# Special:Categories
'categories' => 'Categories',

# Watchlist
'watchlist'      => 'Liste de sequet págines',
'addedwatch'     => 'Adjuntet al liste',
'addedwatchtext' => "Li págine ''[[$1]]'' ha esset adjuntet a vor [[Special:Watchlist|liste de sequet págines]]. Li proxim modificationes de ti ci págine e del associat págine de discussion va esser listat ci, e li págine va aperir '''aspessat''' in li [[Special:RecentChanges|liste de recent modificationes]] por esser trovat plu facilmen. Por supresser ti ci págine ex vor liste, ples claccar sur « Ne plu sequer » in li cadre de navigation.",
'watch'          => 'Sequer',
'watchthispage'  => 'Sequer ti págine',

# Delete
'deletepage'            => 'Deleter págine',
'actioncomplete'        => 'Supression efectuat',
'deletecomment'         => 'Motive:',
'deleteotherreason'     => 'Altri/suplementari motive:',
'deletereasonotherlist' => 'Altri motive',

# Protect
'prot_1movedto2'   => '[[$1]] moet a [[$2]]',
'protectcomment'   => 'Motive:',
'restriction-type' => 'Permission:',

# Namespace form on various pages
'blanknamespace' => '(Principal)',

# Contributions
'mycontris' => 'Mi contributiones',

'sp-contributions-talk' => 'Discussion',

# What links here
'whatlinkshere'      => 'Ligat págines',
'whatlinkshere-page' => 'Págine:',

# Block/unblock
'ipblocklist'  => 'Blocat adresses e usatores',
'contribslink' => 'contribs',

# Move page
'movearticle' => 'Moer págine:',
'movenologin' => 'Vu ne ha intrat',
'movepagebtn' => 'Moer págine',
'movedto'     => 'moet a',
'1movedto2'   => '[[$1]] moet a [[$2]]',
'movereason'  => 'Motive:',

# Export
'export' => 'Exportar págines',

# Namespace 8 related
'allmessages' => 'Liste del missages del sistema',

# Tooltip help for the actions
'tooltip-pt-mytalk'      => 'Vor discussion',
'tooltip-pt-preferences' => 'Mi preferenties',
'tooltip-ca-delete'      => 'Deleter ti págine',
'tooltip-ca-move'        => 'Moer ti págine',
'tooltip-n-mainpage'     => 'Visita li Principal págine',
'tooltip-t-specialpages' => 'Liste de omni special págines',

# Special:NewFiles
'newimages' => 'Galerie de nov images',

# 'all' in various places, this might be different for inflected languages
'watchlistall2' => 'omni',
'namespacesall' => 'omni',
'monthsall'     => 'omni',

# Special:Version
'version' => 'Version',

# Special:SpecialPages
'specialpages' => 'Special págines',

);
