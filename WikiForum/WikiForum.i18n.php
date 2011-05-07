<?php
/**
 * Internationalisation file for WikiForum extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Michael Chlebek
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['en'] = array(
	'wikiforum-desc' => '[[Special:WikiForum|Forum]] extension for MediaWiki',
	'wikiforum' => 'Discussion board',

	// Configuration variables -- do not translate!
	'wikiforum-day-definition-new' => '3', // a thread is considered new for this many days
	'wikiforum-max-threads-per-page' => '20', // number of threads which shall be shown per page on a forum
	'wikiforum-max-replies-per-page' => '10', // number of replies which shall be shown per page on a thread

	'wikiforum-anonymous' => 'Anonymous',
	'wikiforum-announcement-only-description' => 'Announcement forum (only moderators can add threads)',
	'wikiforum-by' => '$1<br />by $2', // $1 is a timestamp (time and date), $2 is a username
	'wikiforum-description' => 'Description:',
	'wikiforum-forum-is-empty' => 'This forum is currently empty.
Please contact a forum administrator to have some categories and forums added.',
	'wikiforum-forum-name' => 'Forum $1', // $1 is the name of a forum
	'wikiforum-name' => 'Name:',
	'wikiforum-button-preview' => 'Preview', // button text in the reply form
	'wikiforum-preview' => 'Preview',
	'wikiforum-preview-with-title' => 'Preview: $1',
	'wikiforum-save' => 'Save', // button text
	'wikiforum-error-search' => 'Search error',
	'wikiforum-error-search-missing-query' => 'You must supply a term to search for!',
	'wikiforum-search-hits' => 'Found {{PLURAL:$1|one hit|$1 hits}}',
	'wikiforum-search-thread' => 'Thread: $1',
	'wikiforum-thread-deleted' => 'thread deleted',
	'wikiforum-topic-name' => 'Forum - $1',
	'wikiforum-updates' => 'Newly updated forums',

	'wikiforum-write-thread' => 'New topic',
	'wikiforum-replies' => 'Replies',
	'wikiforum-views' => 'Views',
	'wikiforum-thread' => 'Thread',
	'wikiforum-threads' => 'Threads',
	'wikiforum-latest-reply' => 'Latest reply',
	'wikiforum-latest-thread' => 'Latest thread',
	'wikiforum-forum' => 'Forum: $1 > $2',
	'wikiforum-overview' => 'Overview',
	'wikiforum-pages' => 'Pages:', // followed by pagination links, like [01] [02] etc.
	'wikiforum-thread-closed' => 'Thread closed',
	'wikiforum-new-thread' => 'New thread',
	'wikiforum-edit-thread' => 'Edit thread',
	'wikiforum-delete-thread' => 'Delete thread',
	'wikiforum-close-thread' => 'Close thread',
	'wikiforum-reopen-thread' => 'Reopen thread',
	'wikiforum-write-reply' => 'Write a reply',
	'wikiforum-edit-reply' => 'Edit reply',
	'wikiforum-delete-reply' => 'Delete reply',
	'wikiforum-save-thread' => 'Save thread',
	'wikiforum-save-reply' => 'Save reply',
	'wikiforum-thread-title' => 'Title of your thread',
	'wikiforum-no-threads' => 'No threads are available at the moment.',

	'wikiforum-edit' => 'edit',
	'wikiforum-close' => 'close',
	'wikiforum-delete' => 'delete',
	'wikiforum-reopen' => 'reopen',

	'wikiforum-posted' => 'Posted at $1 by $2',
	'wikiforum-edited' => 'Edited at $1 by $2',
	'wikiforum-closed-text' => 'Thread was closed at $1 by $2',

	'wikiforum-cat-not-found' => 'Category not found',
	'wikiforum-cat-not-found-text' => 'Category does not exist - go back to $1',
	'wikiforum-forum-not-found' => 'Forum not found',
	'wikiforum-forum-not-found-text' => 'Forum does not exist - go back to $1',
	'wikiforum-thread-not-found' => 'Thread not found',
	'wikiforum-thread-not-found-text' => 'Thread does not exist or was already deleted - go back to $1.',

	'wikiforum-error-thread-reopen' => 'Error while reopening thread',
	'wikiforum-error-thread-close' => 'Error while closing thread',
	'wikiforum-error-general' => 'Object not found or no rights to perform this action.',
	'wikiforum-error-no-rights' => 'You don\'t have the rights to perform this action.',
	'wikiforum-error-not-found' => 'Object not found.',
	'wikiforum-error-no-text-or-title' => 'Title or text not correctly filled out.',
	'wikiforum-error-no-reply' => 'Reply not correctly filled out.',
	'wikiforum-error-double-post' => 'Double-click protection: thread already added.', // @todo FIXME: better wording
	'wikiforum-error-thread-closed' => 'Thread is currently closed. It\'s not possible to add a new reply here.',

	'wikiforum-error-delete' => 'Error while deleting',
	'wikiforum-error-sticky' => 'Error while changing sticky attribute',
	'wikiforum-error-move-thread' => 'Error while moving thread',
	'wikiforum-error-add' => 'Error while adding',
	'wikiforum-error-edit' => 'Error while editing',

	'wikiforum-add-category' => 'Add category',
	'wikiforum-edit-category' => 'Edit category',
	'wikiforum-delete-category' => 'Delete category',
	'wikiforum-add-forum' => 'Add forum',
	'wikiforum-edit-forum' => 'Edit forum',
	'wikiforum-delete-forum' => 'Delete forum',
	'wikiforum-sort-up' => 'sort up',
	'wikiforum-sort-down' => 'sort down',
	'wikiforum-remove-sticky' => 'Remove sticky',
	'wikiforum-make-sticky' => 'Make sticky',
	'wikiforum-move-thread' => 'Move thread',
	'wikiforum-paste-thread' => 'Paste thread',
	'wikiforum-quote' => 'Quote',

	// For Special:ListGroupRights
	'right-wikiforum-admin' => 'Add, edit and delete categories and forums on [[Special:WikiForum|the discusion board]]',
	'right-wikiforum-moderator' => 'Edit and delete threads and replies on [[Special:WikiForum|the discusion board]]',

	// Forum admin group, as per discussion with Jedimca0 on 30 December 2010
	'group-forumadmin' => 'Forum administrators',
	'group-forumadmin-member' => 'Forum administrator',
	'grouppage-forumadmin' => 'Project:Forum administrators',
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'wikiforum-desc' => '[[Special:WikiForum|Foorumilisäosa]] MediaWikille',
	'wikiforum' => 'Keskustelupalsta',
	'wikiforum-anonymous' => 'Anonyymi',
	'wikiforum-announcement-only-description' => 'Ilmoitusfoorumi (vain moderaattorit voivat lisätä aiheita)',
	'wikiforum-by' => '$1;<br />kirjoittanut $2',
	'wikiforum-description' => 'Kuvaus:',
	'wikiforum-forum-is-empty' => 'Tämä foorumi on tällä hetkellä tyhjä.
Otathan yhteyttä foorumin ylläpitäjään saadaksesi joitakin luokkia ja foorumeja lisätyksi.',
	'wikiforum-forum-name' => 'Foorumi $1',
	'wikiforum-name' => 'Nimi:',
	'wikiforum-button-preview' => 'Esikatsele',
	'wikiforum-preview' => 'Esikatselu',
	'wikiforum-preview-with-title' => 'Esikatselu: $1',
	'wikiforum-save' => 'Tallenna',
	'wikiforum-error-search' => 'Hakuvirhe',
	'wikiforum-error-search-missing-query' => 'Sinun tulee antaa hakusana, jolla haetaan!',
	'wikiforum-search-hits' => 'Löydettiin {{PLURAL:$1|yksi tulos|$1 tulosta}}',
	'wikiforum-search-thread' => 'Aihe: $1',
	'wikiforum-thread-deleted' => 'aihe on poistettu',
	'wikiforum-topic-name' => 'Foorumi - $1',
	'wikiforum-updates' => 'Äskettäin päivitetyt foorumit',
	'wikiforum-write-thread' => 'Uusi aihe',
	'wikiforum-replies' => 'Vastauksia',
	'wikiforum-views' => 'Katselukertoja',
	'wikiforum-thread' => 'Aihe',
	'wikiforum-threads' => 'Aiheita', // @todo CHECKME
	'wikiforum-latest-reply' => 'Viimeisin vastaus',
	'wikiforum-latest-thread' => 'Viimeisin aihe',
	'wikiforum-forum' => 'Foorumi: $1 > $2',
	'wikiforum-overview' => 'Yleiskatsaus',
	'wikiforum-pages' => 'Sivuja:',
	'wikiforum-thread-closed' => 'Aihe suljettu',
	'wikiforum-new-thread' => 'Uusi aihe',
	'wikiforum-edit-thread' => 'Muokkaa aihetta',
	'wikiforum-delete-thread' => 'Poista aihe',
	'wikiforum-close-thread' => 'Sulje aihe',
	'wikiforum-reopen-thread' => 'Avaa aihe',
	'wikiforum-write-reply' => 'Kirjoita vastaus',
	'wikiforum-edit-reply' => 'Muokkaa vastausta',
	'wikiforum-delete-reply' => 'Poista vastaus',
	'wikiforum-save-thread' => 'Tallenna aihe',
	'wikiforum-save-reply' => 'Tallenna vastaus',
	'wikiforum-thread-title' => 'Aiheesi otsikko',
	'wikiforum-no-threads' => 'Tällä hetkellä aiheita ei ole saatavilla.',
	'wikiforum-edit' => 'muokkaa',
	'wikiforum-close' => 'sulje',
	'wikiforum-delete' => 'poista',
	'wikiforum-reopen' => 'avaa',
	'wikiforum-posted' => 'Kirjoittanut $2 $1',
	'wikiforum-edited' => 'Muokannut $2 $1',
	'wikiforum-closed-text' => 'Aiheen sulki $2 $1',
	'wikiforum-cat-not-found' => 'Luokkaa ei löydy',
	'wikiforum-cat-not-found-text' => 'Luokkaa ei ole olemassa - mene takaisin sivulle $1',
	'wikiforum-forum-not-found' => 'Foorumia ei löydy',
	'wikiforum-forum-not-found-text' => 'Foorumia ei ole olemassa - mene takaisin sivulle $1',
	'wikiforum-thread-not-found' => 'Aihetta ei löydy',
	'wikiforum-thread-not-found-text' => 'Aihetta ei ole olemassa tai se on jo poistettu - mene takaisin sivulle $1',
	'wikiforum-error-thread-reopen' => 'Virhe aihetta avatesssa',
	'wikiforum-error-thread-close' => 'Virhe aihetta sulkiessa',
	//'wikiforum-error-general' => 'Object not found or no rights to perform this action.',
	'wikiforum-error-no-rights' => 'Sinulla ei ole oikeuksia suorittaa tätä toimintoa.',
	/*'wikiforum-error-not-found' => 'Object not found.',
	'wikiforum-error-no-text-or-title' => 'Title or text not correctly filled out.',
	'wikiforum-error-no-reply' => 'Reply not correctly filled out.',*/
	'wikiforum-error-double-post' => 'Kaksoisnapsautuksen esto: aihe on jo lisätty.',
	'wikiforum-error-thread-closed' => 'Aihe on tällä hetkellä lukittu. Ei ole mahdollista jättää tänne uutta vastausta.',
	'wikiforum-error-delete' => 'Virhe poistaessa',
	'wikiforum-error-sticky' => 'Virhe tiedote-ominaisuutta muutettaessa', // this could use a better wording...
	'wikiforum-error-move-thread' => 'Virhe aihetta siirrettäessä',
	'wikiforum-error-add' => 'Virhe lisätessä',
	'wikiforum-error-edit' => 'Virhe muokatessa',
	'wikiforum-add-category' => 'Lisää luokka',
	'wikiforum-edit-category' => 'Muokkaa luokkaa',
	'wikiforum-delete-category' => 'Poista luokka',
	'wikiforum-add-forum' => 'Lisää foorumi',
	'wikiforum-edit-forum' => 'Muokkaa foorumia',
	'wikiforum-delete-forum' => 'Poista foorumi',
	/*'wikiforum-sort-up' => 'sort up',
	'wikiforum-sort-down' => 'sort down',*/
	'wikiforum-remove-sticky' => 'Poista tiedote-status',
	'wikiforum-make-sticky' => 'Tee tiedote',
	'wikiforum-move-thread' => 'Siirrä aihe',
	'wikiforum-paste-thread' => 'Liitä aihe',
	'wikiforum-quote' => 'Siteeraa',
	'right-wikiforum-admin' => 'Lisätä, muokata ja poistaa luokkia ja foorumeja [[Special:WikiForum|keskustelupalstalla]]',
	'right-wikiforum-moderator' => 'Muokata ja poistaa aiheita ja vastauksia [[Special:WikiForum|keskustelupalstalla]]',
	'group-forumadmin' => 'Foorumin ylläpitäjät',
	'group-forumadmin-member' => 'Foorumin ylläpitäjä',
	'grouppage-forumadmin' => 'Project:Foorumin ylläpitäjät',
);

/** French (Français)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fr'] = array(
	'wikiforum' => 'Forum de discussion',
	'wikiforum-anonymous' => 'Anonyme',
	'group-forumadmin' => 'Administrateurs du forum',
	'group-forumadmin-member' => 'Administrateur du forum',
	'grouppage-forumadmin' => 'Project:Administrateurs du forum',	
);

/** Dutch (Nederlands)
 * @author Jedimca0
 */
$messages['nl'] = array(
	'wikiforum-desc' => '[[Special:WikiForum|Forum]] extensie voor MediaWiki',
	'wikiforum' => 'Discussie bord',
	'wikiforum-anonymous' => 'Anonieme',
	'wikiforum-announcement-only-description' => 'Aankondigingen forum (Alleen forum moderatoren kunnen nieuwe berichten plaatsen)',
	'wikiforum-by' => '$1<br />door $2',
	'wikiforum-description' => 'Omschrijving:',
	'wikiforum-forum-is-empty' => 'Dit forum is op het moment leeg. 
Neem a.u.b. contact op met een forum administrator on categorieën en forums to te voegen.',
	'wikiforum-forum-name' => 'Forum $1',
	'wikiforum-name' => 'Naam:',
	'wikiforum-button-preview' => 'Preview',
	'wikiforum-preview' => 'Preview',
	'wikiforum-preview-with-title' => 'Preview: $1',
	'wikiforum-save' => 'opslaan',
	'wikiforum-error-search' => 'Zoek error',
	'wikiforum-error-search-missing-query' => 'U moet een term invoeren om naar te zoeken!',
	'wikiforum-search-hits' => '{{PLURAL:$1|forum|$1 forums}} gevonden',
	'wikiforum-search-thread' => 'Thread: $1',
	'wikiforum-thread-deleted' => 'thread verwijderd',
	'wikiforum-topic-name' => 'Forum - $1',
	'wikiforum-updates' => 'ge-update forums',
	'wikiforum-write-thread' => 'Nieuw onderwerp',
	'wikiforum-replies' => 'Reacties',
	'wikiforum-views' => 'Bekeken',
	'wikiforum-thread' => 'Thread',
	'wikiforum-threads' => 'Threads',
	'wikiforum-latest-reply' => 'Laatste reactie',
	'wikiforum-latest-thread' => 'Laatste thread',
	'wikiforum-forum' => 'Forum: $1 > $2',
	'wikiforum-overview' => 'Overzicht',
	'wikiforum-pages' => "Pagina's:",
	'wikiforum-thread-closed' => 'Thread gesloten',
	'wikiforum-new-thread' => 'Nieuw thread',
	'wikiforum-edit-thread' => 'bewerk thread',
	'wikiforum-delete-thread' => 'verwijder thread',
	'wikiforum-close-thread' => 'sluit thread',
	'wikiforum-reopen-thread' => 'heropen thread',
	'wikiforum-write-reply' => 'Schrijf een reactie',
	'wikiforum-edit-reply' => 'Bewerk reactie',
	'wikiforum-delete-reply' => 'verwijder reactie',
	'wikiforum-save-thread' => 'thread opslaan',
	'wikiforum-save-reply' => 'reactie opslaan',
	'wikiforum-thread-title' => 'Titel van uw thread',
	'wikiforum-no-threads' => 'Er zijn op het moment geen threads beschikbaar.',
	'wikiforum-edit' => 'bewerk',
	'wikiforum-close' => 'sluit',
	'wikiforum-delete' => 'verwijder',
	'wikiforum-reopen' => 'heropen',
	'wikiforum-posted' => 'Geplaatst op $1 door $2',
	'wikiforum-edited' => 'Bewerkt op $1 door $2',
	'wikiforum-closed-text' => 'Thread was gesloten op $1 door $2',
	'wikiforum-cat-not-found' => 'Categorie niet gevonden',
	'wikiforum-cat-not-found-text' => 'Categorie bestaat niet - terug naar $1',
	'wikiforum-forum-not-found' => 'Forum niet gevonden',
	'wikiforum-forum-not-found-text' => 'Forum bestaat niet - terug naar $1',
	'wikiforum-thread-not-found' => 'Thread niet gevonden',
	'wikiforum-thread-not-found-text' => 'Thread bestaat niet of was verwijderd - terug naar $1.',
	'wikiforum-error-thread-reopen' => 'Fout bij het heropenen van thread',
	'wikiforum-error-thread-close' => 'Fout tijdens sluiten van thread',
	'wikiforum-error-general' => 'Object niet gevonden, of u heeft geen rechten om deze actie uit te voeren.',
	'wikiforum-error-no-rights' => 'U heeft het recht niet om deze actie uit te voeren.',
	'wikiforum-error-not-found' => 'Object niet gevonden.',
	'wikiforum-error-no-text-or-title' => 'Titel of text was niet correct ingevuld.',
	'wikiforum-error-no-reply' => 'Reactie niet correct ingevuld.',
	'wikiforum-error-double-post' => 'Dubbel click preventie, thread is al toegevoegd.',
	'wikiforum-error-thread-closed' => 'Thread is op het moment gesloten, het is niet mogelijk om een reactie toe te voegen.',
	'wikiforum-error-delete' => 'Fout tijdens verwijderen',
	'wikiforum-error-sticky' => 'Fout tijdens veranderen van sticky aanmerking',
	'wikiforum-error-move-thread' => 'Fout tijdens her verplaatsen van het thread',
	'wikiforum-error-add' => 'Fout tijdens toevoegen',
	'wikiforum-error-edit' => 'Fout tijdens bewerken',
	'wikiforum-add-category' => 'Categorie toevoegen',
	'wikiforum-edit-category' => 'Categorie bewerken',
	'wikiforum-delete-category' => 'Categorie verwijderen',
	'wikiforum-add-forum' => 'Forum toevoegen',
	'wikiforum-edit-forum' => 'Forum bewerken',
	'wikiforum-delete-forum' => 'Forum verwijderen',
	'wikiforum-sort-up' => 'Naar boven sorteren',
	'wikiforum-sort-down' => 'Naar beneden sorteren',
	'wikiforum-remove-sticky' => 'Sticky verwijderen',
	'wikiforum-make-sticky' => 'Sticky maken',
	'wikiforum-move-thread' => 'Thread verplaatsen',
	'wikiforum-paste-thread' => 'Thread plakken',
	'wikiforum-quote' => 'Quote',
	'right-wikiforum-admin' => 'Forums en categorieën toevoegen, bewerken en verwijderen op [[Special:WikiForum|het forum]]',
	'right-wikiforum-moderator' => 'Threads en reacties bewerken en verwijderen op [[Special:WikiForum|het forum]]',
	'group-forumadmin' => 'Forum administratoren',
	'group-forumadmin-member' => 'Forum administrator',
	'grouppage-forumadmin' => 'Project:Forum administratoren',
);