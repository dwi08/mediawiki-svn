<?php

/**
* Internationalisation file for the GroupPermissions Manager extension
* See http://www.mediawiki.org/wiki/Extension:GroupPermissions_Manager for more info
*/

$messages = array();

/** English
 * @author Ryan Schmidt
 */
$messages['en'] = array(
	'grouppermissions' => 'Manage Group Permissions',
	'grouppermissions-desc' => 'Manage group permissions via a special page',
	'grouppermissions-desc2' => 'Extended permissions system',
	'grouppermissions-header' => 'You may use this page to change the underlying permissions of the various usergroups',
	'grouppermissions-search' => 'Group:',
	'grouppermissions-dologin' => 'Login',
	'grouppermissions-dosearch' => 'Go',
	'grouppermissions-searchlabel' => 'Search for Group',
	'grouppermissions-deletelabel' => 'Delete Group',
	'grouppermissions-error' => 'An unknown error has occurred, please hit the back button on your browser and try again',
	'grouppermissions-change' => 'Change group permissions',
	'grouppermissions-add' => 'Add group',
	'grouppermissions-delete' => 'Delete group',
	'grouppermissions-comment' => 'Comment:',
	'grouppermissions-addsuccess' => '$1 has been successfully added',
	'grouppermissions-deletesuccess' => '$1 has been successfully deleted',
	'grouppermissions-changesuccess' => 'Permissions for $1 have successfully been changed',
	'grouppermissions-true' => 'True',
	'grouppermissions-false' => 'False',
	'grouppermissions-never' => 'Never',
	'grouppermissions-nooldrev' => 'Error encountered when attempting to archive the current config file. No archive will be made',
	'grouppermissions-sort-read' => 'Reading',
	'grouppermissions-sort-edit' => 'Editing',
	'grouppermissions-sort-manage' => 'Management',
	'grouppermissions-sort-admin' => 'Administration',
	'grouppermissions-sort-tech' => 'Technical',
	'grouppermissions-sort-misc' => 'Miscellaneous',
	'grouppermissions-log-add' => 'added group "$2"',
	'grouppermissions-log-change' => 'changed permissions for group "$2"',
	'grouppermissions-log-delete' => 'deleted group "$2"',
	'grouppermissions-log-name' => 'GroupPermissions log',
	'grouppermissions-log-entry' => '', #do not translate this message
	'grouppermissions-log-header' => 'This page tracks changes to the underlying permissions of user groups',
	'grouppermissions-needjs' => 'Warning: JavaScript is disabled on your browser. Some features may not work!',
	'grouppermissions-sp-header' => 'You may use this page to manage how permissions are sorted and add new permissions',

	'right-viewsource' => 'View wiki source of protected pages',
	'right-raw' => 'View raw pages',
	'right-render' => 'View rendered pages without navigation',
	'right-info' => 'View page info',
	'right-credits' => 'View page credits',
	'right-history' => 'View page histories',
	'right-search' => 'Search the wiki',
	'right-contributions' => 'View contributions pages',
	'right-recentchanges' => 'View recent changes',
	'right-edittalk' => 'Edit discussion pages',
	'right-edit' => 'Edit pages (which are not discussion pages)',
);

/** Tarifit (Tarifit)
 * @author Jose77
 */
$messages['rif'] = array(
	'grouppermissions-dosearch' => 'Raḥ ɣa',
);

/** Bulgarian (Български)
 * @author DCLXVI
 */
$messages['bg'] = array(
	'grouppermissions-search'  => 'Група:',
	'grouppermissions-dologin' => 'Влизане',
	'grouppermissions-comment' => 'Коментар:',
	'right-edit'               => 'редактиране на страници',
);

/** Esperanto (Esperanto)
 * @author Yekrats
 */
$messages['eo'] = array(
	'grouppermissions-search'        => 'Grupo:',
	'grouppermissions-dologin'       => 'Salutnomo',
	'grouppermissions-dosearch'      => 'Ek',
	'grouppermissions-searchlabel'   => 'Serĉi Grupon',
	'grouppermissions-deletelabel'   => 'Forigi Grupon',
	'grouppermissions-add'           => 'Aldoni grupon',
	'grouppermissions-addsuccess'    => '$1 estis sukcese aldonita',
	'grouppermissions-deletesuccess' => '$1 estis sukcese forigita',
	'grouppermissions-true'          => 'Vera',
	'grouppermissions-false'         => 'Falsa',
	'grouppermissions-never'         => 'Neniam',
	'grouppermissions-log-delete'    => 'forigis grupon "$2"',
	'right-edit'                     => 'Redaktu paĝojn',
);

/** French (Français)
 * @author Grondin
 */
$messages['fr'] = array(
	'grouppermissions'               => 'Gérer les permissions des groupes',
	'grouppermissions-desc'          => 'Gère les permissions des groupes au travers d’une page spéciale',
	'grouppermissions-desc2'         => 'Système étendu des permissions',
	'grouppermissions-header'        => 'Vous pouvez utiliser cette page pour modifier les permissions soulignées des différents groupes d’utilisateurs',
	'grouppermissions-search'        => 'Groupe :',
	'grouppermissions-dologin'       => 'Connexion',
	'grouppermissions-dosearch'      => 'Lancer',
	'grouppermissions-searchlabel'   => 'Recherche d’un groupe',
	'grouppermissions-deletelabel'   => 'Supprimer le groupe',
	'grouppermissions-error'         => 'Une erreur indéterminée est intervenue, veuillez cliquer sur le bouton de retour à la page précédente de votre navigateur puis essayez à nouveau',
	'grouppermissions-change'        => 'Modifier les permissions du groupe',
	'grouppermissions-add'           => 'Ajouter un groupe',
	'grouppermissions-delete'        => 'Supprimer le groupe',
	'grouppermissions-comment'       => 'Commentaire :',
	'grouppermissions-addsuccess'    => '$1 a été ajouté avec succès',
	'grouppermissions-deletesuccess' => '$1 a été supprimé avec succès',
	'grouppermissions-changesuccess' => 'Les permissions pour $1 ont été modifiées avec succès',
	'grouppermissions-true'          => 'Vrai',
	'grouppermissions-false'         => 'Faux',
	'grouppermissions-never'         => 'Jamais',
	'grouppermissions-nooldrev'      => 'Une erreur est intervenue lors de la tentative d’archivage du fichier de configuration. Aucune archive ne sera créée.',
	'grouppermissions-sort-read'     => 'Lecture',
	'grouppermissions-sort-edit'     => 'Édition',
	'grouppermissions-sort-manage'   => 'Gestion',
	'grouppermissions-sort-admin'    => 'Administration',
	'grouppermissions-sort-tech'     => 'Technique',
	'grouppermissions-sort-misc'     => 'Divers',
	'grouppermissions-log-add'       => 'a ajouté le groupe « $2 »',
	'grouppermissions-log-change'    => 'a modifié les permissions du groupe « $2 »',
	'grouppermissions-log-delete'    => 'a supprimé le groupe « $2 »',
	'grouppermissions-log-name'      => 'Journal des permissions des groupes',
	'grouppermissions-log-header'    => 'Cette page piste les changements des permissions soulignées des groupes utilisateurs.',
	'grouppermissions-needjs'        => 'Aversissement : JavaScript est désactivé sur votre navigateur. Plusieurs fonctionnalités peuvent ne pas fonctionner !',
	'grouppermissions-sp-header'     => 'Vous pouvez utiliser cette page pour gérer comment les permissions sont affichées et pour ajouter de nouvelles permissions',
	'right-viewsource'               => 'Voir le code source wiki des pages protégées',
	'right-raw'                      => 'Voir les pages brutes',
	'right-render'                   => 'Voir le rendu des pages sans navigation',
	'right-info'                     => 'Voir les informations de la page',
	'right-credits'                  => 'Voir les crédits de la page',
	'right-history'                  => 'Voir les historiques de la page',
	'right-search'                   => 'Rechercher le wiki',
	'right-contributions'            => 'Voir les pages des contributions',
	'right-recentchanges'            => 'Voir les modifications récentes',
	'right-edittalk'                 => 'Modifier les pages de discussion',
	'right-edit'                     => 'Modifier les pages (qui n’ont pas de page de discussion)',
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'grouppermissions'               => 'Xestionar os permisos dun grupo',
	'grouppermissions-desc'          => 'Xestionar os permisos dun grupo mediante unha páxina especial',
	'grouppermissions-desc2'         => 'Sistema de permisos estendido',
	'grouppermissions-header'        => 'Pode usar esta páxina para cambiar os permisos subxacentes de varios grupos de usuario',
	'grouppermissions-search'        => 'Grupo:',
	'grouppermissions-dologin'       => 'Rexistro',
	'grouppermissions-dosearch'      => 'Ir',
	'grouppermissions-searchlabel'   => 'Procurar por grupo',
	'grouppermissions-deletelabel'   => 'Borrar un grupo',
	'grouppermissions-error'         => 'Ocorreu un erro descoñecido, por favor, prema no botón "Atrás" do seu navegador e ténteo de novo',
	'grouppermissions-change'        => 'Cambiar os permisos dun grupo',
	'grouppermissions-add'           => 'Engadir un grupo',
	'grouppermissions-delete'        => 'Borrar un grupo',
	'grouppermissions-comment'       => 'Comentario:',
	'grouppermissions-addsuccess'    => '$1 foi engadido con éxito',
	'grouppermissions-deletesuccess' => '$1 foi borrado con éxito',
	'grouppermissions-changesuccess' => 'Os permisos para $1 foron cambiados con éxito',
	'grouppermissions-true'          => 'Verdadeiro',
	'grouppermissions-false'         => 'Falso',
	'grouppermissions-never'         => 'Nunca',
	'grouppermissions-nooldrev'      => 'Atopouse un erro ao intentar arquivar a configuración actual do ficheiro. Non se fará ningún arquivo',
	'grouppermissions-sort-read'     => 'Lendo',
	'grouppermissions-sort-edit'     => 'Editando',
	'grouppermissions-sort-manage'   => 'Xestión',
	'grouppermissions-sort-admin'    => 'Administración',
	'grouppermissions-sort-tech'     => 'Técnico',
	'grouppermissions-sort-misc'     => 'Varios',
	'grouppermissions-log-add'       => 'engadiu o grupo "$2"',
	'grouppermissions-log-change'    => 'cambiou os permisos do grupo "$2"',
	'grouppermissions-log-delete'    => 'borrou o grupo "$2"',
	'grouppermissions-log-name'      => 'Rexistro de permisos de grupo',
	'grouppermissions-log-header'    => 'Nesta páxina pode seguir os cambios dos permisos subxacentes dos grupos de usuario',
	'grouppermissions-needjs'        => 'Aviso: o JavaScript non está permitido no seu navegador. Pode que algunhas características non funcionen ben!',
	'grouppermissions-sp-header'     => 'Pode usar esta páxina para xestionar como están ordenados e engadir novos permisos',
	'right-viewsource'               => 'Ver o código fonte das páxinas protexidas',
	'right-raw'                      => 'Ver as páxinas "brutas"',
	'right-render'                   => 'Ver as páxinas renderizadas sen navegación',
	'right-info'                     => 'Ver a información das páxinas',
	'right-credits'                  => 'Ver os créditos das páxinas',
	'right-history'                  => 'Ver os historiais da páxinas',
	'right-search'                   => 'Procurar no wiki',
	'right-contributions'            => 'Ver as páxinas de contribucións',
	'right-recentchanges'            => 'Ver os cambios recentes',
	'right-edittalk'                 => 'Editar as páxinas de conversa',
	'right-edit'                     => 'Editar páxinas',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'grouppermissions'               => "D'Rechter vu Gruppe geréieren",
	'grouppermissions-desc'          => "D'Rechter vu Gruppen iwwer eng Spezialsäit geréieren",
	'grouppermissions-desc2'         => 'ERweiderte System vun de Rechter',
	'grouppermissions-header'        => "Dir kënnt dës Säit benotzen fir déi ënnerluechte Rechter vun de verschidden Benotzergruppen z'änneren",
	'grouppermissions-search'        => 'Grupp:',
	'grouppermissions-dologin'       => 'Aloggen',
	'grouppermissions-dosearch'      => 'Lass',
	'grouppermissions-searchlabel'   => 'Sich no engem Grupp',
	'grouppermissions-deletelabel'   => 'Grupp läschen',
	'grouppermissions-change'        => "D'Rechter vum Grupp änneren",
	'grouppermissions-add'           => 'Grupp derbäisetzen',
	'grouppermissions-delete'        => 'Grupp läschen',
	'grouppermissions-comment'       => 'Bemierkung:',
	'grouppermissions-addsuccess'    => '$1 gouf derbäigesat',
	'grouppermissions-deletesuccess' => '$1 gouf geläscht',
	'grouppermissions-changesuccess' => "D'Rechter fir $1 goufe geännert",
	'grouppermissions-true'          => 'Wouer',
	'grouppermissions-false'         => 'Falsch',
	'grouppermissions-never'         => 'Nie',
	'grouppermissions-sort-edit'     => 'Ännerung',
	'grouppermissions-sort-manage'   => 'Gestioun',
	'grouppermissions-sort-admin'    => 'Verwaltung',
	'grouppermissions-sort-misc'     => 'Verschiddenes',
	'grouppermissions-log-add'       => 'huet de Grupp "$2" derbäigesat',
	'grouppermissions-log-change'    => 'huet d\'Rechter fir de Grupp "$2" geännert',
	'grouppermissions-log-delete'    => 'huet de Grupp "$2" geläscht',
	'grouppermissions-log-name'      => 'Lëscht vun de Rechter vu Gruppen',
	'right-info'                     => "D'Informarioune vun der Säit weisen",
	'right-history'                  => "D'Versioune vun der Säit weisen",
	'right-search'                   => 'Op der Wiki sichen',
	'right-recentchanges'            => 'Weis rezent Ännerungen',
	'right-edittalk'                 => 'Diskussiounssäiten änneren',
	'right-edit'                     => 'Säiten änneren',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'grouppermissions'               => 'Groepsrechten beheren',
	'grouppermissions-desc'          => 'Groepsrechten beheren via een speciale pagina',
	'grouppermissions-desc2'         => 'Uitgebreid rechtensysteem',
	'grouppermissions-header'        => 'U kunt via deze pagina de groepsrechten van gebruikersgroepen aanpassen',
	'grouppermissions-search'        => 'Groep:',
	'grouppermissions-dologin'       => 'Aanmelden',
	'grouppermissions-dosearch'      => 'OK',
	'grouppermissions-searchlabel'   => 'Naar groep zoeken',
	'grouppermissions-deletelabel'   => 'Groep verwijderen',
	'grouppermissions-error'         => 'Er is een onbekende fout opgetreden. Klik alstublieft op de knop "vorige pagina" in uw browser en probeer het nog een keer',
	'grouppermissions-change'        => 'Groepsrechten wijzigen',
	'grouppermissions-add'           => 'Groep toevoegen',
	'grouppermissions-delete'        => 'Groep verwijderen',
	'grouppermissions-comment'       => 'Opmerking:',
	'grouppermissions-addsuccess'    => '$1 is toegevoegd',
	'grouppermissions-deletesuccess' => '$1 is verwijderd',
	'grouppermissions-changesuccess' => 'De rechten voor $1 zijn aangepast',
	'grouppermissions-true'          => 'Waar',
	'grouppermissions-false'         => 'Onwaar',
	'grouppermissions-never'         => 'Nooit',
	'grouppermissions-nooldrev'      => 'Er is een fout opgetreden bij het maken van een veiligheidskopie van het huidige instellingenbestand. Er wordt geen veiligheidskopie gemaakt',
	'grouppermissions-sort-read'     => 'Lezen',
	'grouppermissions-sort-edit'     => 'Bewerken',
	'grouppermissions-sort-manage'   => 'Beheer',
	'grouppermissions-sort-admin'    => 'Administratie',
	'grouppermissions-sort-tech'     => 'Technisch',
	'grouppermissions-sort-misc'     => 'Overige',
	'grouppermissions-log-add'       => 'heeft groep "$2" toegevoegd',
	'grouppermissions-log-change'    => 'heeft de rechten voor groep "$2" aangepast',
	'grouppermissions-log-delete'    => 'heeft groep "$2" verwijderd',
	'grouppermissions-log-name'      => 'Groepsrechtenlogboek',
	'grouppermissions-log-header'    => 'Op deze pagina worden wijzigingen in de rechten van gebruikersgroepen weergegeven',
	'grouppermissions-needjs'        => 'Waarschuwing: JavaScript is uitgeschakeld in uw browser. Een aantal mogelijkheden werkt wellicht niet!',
	'grouppermissions-sp-header'     => 'U kunt deze pagina gebruiker voor het sorteren van rechten en het toevoegen van nieuwe rechten',
	'right-viewsource'               => "Brontekst van beveiligde pagina's bekijken",
	'right-raw'                      => "Ruwe pagina's bekijken",
	'right-render'                   => "Gerenderde pagina's zonder navigatie bekijken",
	'right-info'                     => 'Paginainformatie bekijken',
	'right-credits'                  => 'Pagina-auteurs bekijken',
	'right-history'                  => 'Paginageschiedenis bekijken',
	'right-search'                   => 'Wiki doorzoeken',
	'right-contributions'            => "Bijdragenpagia's bekijken",
	'right-recentchanges'            => 'Recente wijzigingen bekijken',
	'right-edittalk'                 => "Overlegpagina's bewerken",
	'right-edit'                     => "Pagina's bewerken",
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'grouppermissions'               => 'Behandle grupperettigheter',
	'grouppermissions-desc'          => 'Behandle grupperettigheter via en spesialside',
	'grouppermissions-desc2'         => 'Utvidet rettighetssystem',
	'grouppermissions-header'        => 'Du kan bruke denne siden for å endre rettightene de forskjellige brukergruppene har',
	'grouppermissions-search'        => 'Gruppe:',
	'grouppermissions-dologin'       => 'Logg inn',
	'grouppermissions-dosearch'      => 'Gå',
	'grouppermissions-searchlabel'   => 'Søk etter gruppe',
	'grouppermissions-deletelabel'   => 'Slett gruppe',
	'grouppermissions-error'         => 'En ukjent feil oppsto. Trykk på tilbake-knappen i nettleseren din og prøv igjen',
	'grouppermissions-change'        => 'Endre grupperettigheter',
	'grouppermissions-add'           => 'Legg til gruppe',
	'grouppermissions-delete'        => 'Slett gruppe',
	'grouppermissions-comment'       => 'Kommentar:',
	'grouppermissions-addsuccess'    => '$1 ble lagt til',
	'grouppermissions-deletesuccess' => '$1 har blitt slettet',
	'grouppermissions-changesuccess' => 'Rettighetene for $1 ble endret',
	'grouppermissions-true'          => 'Sant',
	'grouppermissions-false'         => 'Usant',
	'grouppermissions-never'         => 'Aldri',
	'grouppermissions-nooldrev'      => 'Feil oppsto under forsøk på å arkivere konfigurasjonsfilen. Ingen arkivering ble gjort.',
	'grouppermissions-sort-read'     => 'Lesing',
	'grouppermissions-sort-edit'     => 'Redigering',
	'grouppermissions-sort-manage'   => 'Behandling',
	'grouppermissions-sort-admin'    => 'Administrasjon',
	'grouppermissions-sort-tech'     => 'Teknisk',
	'grouppermissions-sort-misc'     => 'Diverse',
	'grouppermissions-log-add'       => 'la til gruppen «$2»',
	'grouppermissions-log-change'    => 'endret rettigheter for gruppen «$2»',
	'grouppermissions-log-delete'    => 'slettet gruppen «$2»',
	'grouppermissions-log-name'      => 'Logg for endringer i grupperettigheter',
	'grouppermissions-log-header'    => 'Denne siden viser endringer i rettighetene brukergrupper innehar.',
	'right-viewsource'               => 'Se kilden til beskyttede sider',
	'right-raw'                      => 'Se sider i råformat',
	'right-render'                   => 'Se sider uten navigasjon',
	'right-info'                     => 'Se sideinfo',
	'right-credits'                  => 'Se sidekrediteringer',
	'right-history'                  => 'Se sidehistorikker',
	'right-search'                   => 'Søke i wikien',
	'right-contributions'            => 'Vise bidragssider',
	'right-recentchanges'            => 'Vise siste endringer',
	'right-edittalk'                 => 'Redigere diskusjonssider',
	'right-edit'                     => 'Redigere sider',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'right-viewsource'    => 'Zobraziť zdrojový text chránených stránok',
	'right-raw'           => 'Zobraziť nespracované stránky',
	'right-render'        => 'Zobraziť vykreslené stránky bez navigácie',
	'right-info'          => 'Zobraziť informácie o stránke',
	'right-credits'       => 'Zobraziť autorov stránky',
	'right-history'       => 'Zobraziť histórie stránok',
	'right-search'        => 'Hľadať na wiki',
	'right-contributions' => 'Zobraziť stránky príspevkov',
	'right-recentchanges' => 'Zobraziť posledné zmeny',
	'right-edittalk'      => 'Upraviť diskusné stránky',
	'right-edit'          => 'Upravovať stránky (ktoré nie sú diskusné stránky)',
);

/** Swedish (Svenska)
 * @author Boivie
 * @author M.M.S.
 */
$messages['sv'] = array(
	'grouppermissions'               => 'Hantera behörigheter för användargrupper',
	'grouppermissions-desc'          => 'Hantera behörigheter för användargrupper via en specialsida',
	'grouppermissions-desc2'         => 'Utökat system för behörigheter',
	'grouppermissions-header'        => 'Du kan använda denna sida för att ändra de underliggande behörigheterna av de olika användargrupperna',
	'grouppermissions-search'        => 'Användargrupp:',
	'grouppermissions-dologin'       => 'Logga in',
	'grouppermissions-dosearch'      => 'Gå till',
	'grouppermissions-searchlabel'   => 'Sök efter användargrupper',
	'grouppermissions-deletelabel'   => 'Radera användargrupp',
	'grouppermissions-error'         => 'Ett okänt fel har uppstått, var god tryck på tillbaka-knappen i din webbläsare och pröva igen',
	'grouppermissions-change'        => 'Ändra behörigheter för användargrupper',
	'grouppermissions-add'           => 'Lägg till användargrupp',
	'grouppermissions-delete'        => 'Radera användargrupp',
	'grouppermissions-comment'       => 'Kommentar:',
	'grouppermissions-addsuccess'    => '$1 har blivit tillagd',
	'grouppermissions-deletesuccess' => '$1 har blivit raderad',
	'grouppermissions-changesuccess' => 'Behörigheterna för $1 har blivit ändrade',
	'grouppermissions-true'          => 'Sant',
	'grouppermissions-false'         => 'Falskt',
	'grouppermissions-never'         => 'Aldrig',
	'grouppermissions-nooldrev'      => 'Fel uppstod under försök att arkivera den nuvarande konfigurationsfilen. Inget arkiv kommer att skapas',
	'grouppermissions-sort-read'     => 'Läser',
	'grouppermissions-sort-edit'     => 'Redigerar',
	'grouppermissions-sort-manage'   => 'Hantering',
	'grouppermissions-sort-admin'    => 'Administration',
	'grouppermissions-sort-tech'     => 'Tekniskt',
	'grouppermissions-sort-misc'     => 'Diverse',
	'grouppermissions-log-add'       => 'la till grupp "$2"',
	'grouppermissions-log-change'    => 'ändrade rättigheter för grupp "$2"',
	'grouppermissions-log-delete'    => 'raderade grupp "$2"',
	'grouppermissions-log-name'      => 'Grupprättighetslogg',
	'grouppermissions-log-header'    => 'Denna sida visar ändringar i användargruppernas underliggande rättigheter',
	'grouppermissions-needjs'        => 'Warning: JavaScript är avstängt i din webbläsare. Det kan få vissa funktioner att inte fungera.',
	'grouppermissions-sp-header'     => 'Du kan använda denna sida för att hantera hur rättigheter sorteras och lägga till nya rättigheter',
	'right-viewsource'               => 'Se skyddade sidors wiki-kod',
	'right-raw'                      => 'Se sidor i råformat',
	'right-render'                   => 'Se renderade sidor utan navigation',
	'right-info'                     => 'Se sidinfo',
	'right-credits'                  => 'Se sidokrediteringar',
	'right-history'                  => 'Se sidohistorik',
	'right-search'                   => 'Söka wikin',
	'right-contributions'            => 'Se bidragssidor',
	'right-recentchanges'            => 'Se senaste ändringar',
	'right-edittalk'                 => 'Redigera diskussionssidor',
	'right-edit'                     => 'Redigera sidor',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'grouppermissions-search'   => 'సమూహం:',
	'grouppermissions-dologin'  => 'ప్రవేశించు',
	'grouppermissions-dosearch' => 'వెళ్ళు',
	'grouppermissions-comment'  => 'వ్యాఖ్య:',
	'right-edit'                => 'పేజీలను మార్చడం',
);

