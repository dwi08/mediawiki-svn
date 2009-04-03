<?php
/** Serbo-Croatian (Srpskohrvatski / Српскохрватски)
 *
 * @ingroup Language
 * @file
 *
 * @author OC Ripper
 * @author לערי ריינהארט
 */

$messages = array(
# User preference toggles
'tog-rememberpassword' => 'Upamti moju lozinku na ovom kompjuteru za buduće posjete',

# Dates
'january'   => 'januar',
'february'  => 'februar',
'march'     => 'mart',
'april'     => 'april',
'may_long'  => 'maj',
'june'      => 'jun',
'july'      => 'jul',
'august'    => 'august',
'september' => 'septembar',
'october'   => 'oktobar',
'november'  => 'novembar',
'december'  => 'decembar',

# Categories related messages
'pagecategories'         => '{{PLURAL:$1|Kategorija|Kategorije}}',
'category_header'        => 'Stranice u kategoriji "$1"',
'subcategories'          => 'Potkategorije',
'category-subcat-count'  => '{{PLURAL:$2|Ova kategorija ima sljedeću $1 potkategoriju.|Ova kategorija ima {{PLURAL:$1|sljedeće potkategorije|sljedećih $1 potkategorija}}, od $2 ukupno.}}',
'category-article-count' => '{{PLURAL:$2|U ovoj kategoriji se nalazi $1 stranica.|{{PLURAL:$1|Prikazana je $1 stranica|Prikazano je $1 stranice|Prikazano je $1 stranica}} od ukupno $2 u ovoj kategoriji.}}',
'listingcontinuesabbrev' => 'nast.',

'qbfind'     => 'Pronađite',
'navigation' => 'Navigacija',

'errorpagetitle'   => 'Greška',
'returnto'         => 'Povratak na $1.',
'tagline'          => 'Izvor: {{SITENAME}}',
'help'             => 'Pomoć',
'search'           => 'Pretraga',
'searchbutton'     => 'Traži',
'searcharticle'    => 'Idi',
'history'          => 'Historija stranice',
'history_short'    => 'Historija',
'printableversion' => 'Verzija za ispis',
'permalink'        => 'Trajni link',
'edit'             => 'Uredi',
'editthispage'     => 'Uredite ovu stranicu',
'delete'           => 'Obriši',
'protect'          => 'Zaštiti',
'newpage'          => 'Nova stranica',
'talkpage'         => 'Razgovaraj o ovoj stranici',
'talkpagelinktext' => 'Razgovor',
'talk'             => 'Razgovor',
'views'            => 'Pregledi',
'toolbox'          => 'Traka sa alatima',
'otherlanguages'   => 'Na drugim jezicima',
'redirectpagesub'  => 'Preusmjeri stranicu',
'lastmodifiedat'   => 'Ova stranica je posljednji put izmijenjena $1, $2.', # $1 date, $2 time
'jumpto'           => 'Skoči na:',
'jumptonavigation' => 'navigacija',
'jumptosearch'     => 'pretraga',

# All link text and link target definitions of links into project namespace that get used by other message strings, with the exception of user group pages (see grouppage) and the disambiguation template definition (see disambiguations).
'aboutsite'            => 'O projektu {{SITENAME}}',
'copyright'            => 'Sadržaj je dostupan pod $1.',
'disclaimers'          => 'Odricanje odgovornosti',
'helppage'             => 'Help:Sadržaj',
'mainpage'             => 'Glavna strana',
'mainpage-description' => 'Glavna strana',
'privacy'              => 'Politika privatnosti',

'badaccess' => 'Greška pri odobrenju',

'retrievedfrom'       => 'Dobavljeno iz "$1"',
'youhavenewmessages'  => 'Imate $1 ($2).',
'newmessageslink'     => 'novih promjena',
'newmessagesdifflink' => 'posljednja promjena',
'editsection'         => 'uredi',
'viewsourcelink'      => 'pogledaj kod',
'editsectionhint'     => 'Uredi sekciju: $1',
'red-link-title'      => '$1 (stranica ne postoji)',

# Short words for each namespace, by default used in the namespace tab in monobook
'nstab-main'     => 'Stranica',
'nstab-special'  => 'Posebna stranica',
'nstab-category' => 'Kategorija',

# General errors
'missingarticle-rev' => '(izmjena#: $1)',
'badtitletext'       => 'Zatražena stranica je bila nevaljana, prazna ili neispravno povezana s među-jezičkim ili inter-wiki naslovom.
Može sadržavati jedno ili više slova koja se ne mogu koristiti u naslovima.',
'viewsource'         => 'Pogledaj kod',
'viewsourcefor'      => 'za $1',

# Login and logout pages
'yourname'                => 'Korisničko ime:',
'yourpassword'            => 'Lozinka/zaporka:',
'remembermypassword'      => 'Upamti moju lozinku na ovom kompjuteru za buduće posjete',
'login'                   => 'Prijavi se',
'nav-login-createaccount' => 'Prijavi se / Registruj se',
'userlogin'               => 'Prijavi se / stvori korisnički račun',
'logout'                  => 'Odjavi me',
'nologinlink'             => 'Otvorite račun',
'mailmypassword'          => 'Pošalji mi novu lozinku putem E-maila',

# Edit page toolbar
'bold_sample'     => 'Podebljan tekst',
'bold_tip'        => 'Podebljan tekst',
'italic_sample'   => 'Kurzivan tekst',
'italic_tip'      => 'Kurzivan tekst',
'link_sample'     => 'Naslov linka',
'link_tip'        => 'Interni link',
'extlink_sample'  => 'http://www.primjer.com naslov linka',
'extlink_tip'     => 'Eksterni link (zapamti prefiks http:// )',
'headline_sample' => 'Tekst naslova',
'headline_tip'    => 'Podnaslov',
'math_sample'     => 'Unesite formulu ovdje',
'math_tip'        => 'Matematička formula (LaTeX)',
'nowiki_sample'   => 'Dodaj neformatirani tekst ovdje',
'nowiki_tip'      => 'Ignoriraj wiki formatiranje',
'image_tip'       => 'Uklopljena datoteka/fajl',
'media_tip'       => 'Putanja ka multimedijalnoj datoteci/fajlu',
'sig_tip'         => 'Vaš potpis sa trenutnim vremenom',
'hr_tip'          => 'Horizontalna linija (koristite rijetko)',

# Edit pages
'preview'                          => 'Pretpregled',
'anoneditwarning'                  => "'''Upozorenje:''' Niste prijavljeni.
Vaša IP adresa će biti zabilježena u historiji ove stranice.",
'summary-preview'                  => 'Pretpregled sažetka:',
'newarticle'                       => '(Novi)',
'newarticletext'                   => "Preko linka ste došli na stranicu koja još uvijek ne postoji.
* Ako želite stvoriti stranicu, počnite tipkati u okviru dolje (v. [[{{MediaWiki:Helppage}}|stranicu za pomoć]] za više informacija).
* Ukoliko ste došli greškom, pritisnike dugme '''Nazad''' ('''back''') na vašem pregledniku.",
'previewnote'                      => "'''Upamtite da je ovo samo pretpregled.'''
Vaše izmjene još uvijek nisu snimljene!",
'editing'                          => 'Uređujete $1',
'editingsection'                   => 'Uređujete $1 (sekciju)',
'template-semiprotected'           => '(polu-zaštićeno)',
'hiddencategories'                 => 'Ova stranica pripada {{PLURAL:$1|1 skrivenoj kategoriji|$1 skrivenim kategorijama}}:',
'permissionserrorstext-withaction' => 'Nemate dozvolu za $2, zbog {{PLURAL:$1|sljedećeg|sljedećih}} razloga:',
'deleted-notice'                   => 'Ova stranica je obrisana.
Registar brisanja za stranicu je dolje naveden radi referenci.',

# History pages
'viewpagelogs'           => 'Pogledaj protokole ove stranice',
'currentrev-asof'        => 'Trenutna revizija na dan $1',
'nextrevision'           => 'Novija izmjena →',
'currentrevisionlink'    => 'Trenutna verzija',
'cur'                    => 'tren',
'last'                   => 'preth',
'histlegend'             => "Odabir razlika: označite radio dugme verzija za usporedbu i pritisnite enter ili dugme na dnu.<br />
Objašnjenje: '''({{int:cur}})''' = razlika sa trenutnom verzijom,
'''({{int:last}})''' = razlika sa prethodnom verzijom, '''{{int:minoreditletter}}''' = manja izmjena.",
'history-fieldset-title' => 'Pretraga historije',

# Revision deletion
'rev-delundel' => 'pokaži/sakrij',

# Diffs
'history-title'           => 'Historija izmjena stranice "$1"',
'difference'              => '(Razlika između revizija)',
'lineno'                  => 'Linija $1:',
'compareselectedversions' => 'Uporedite označene verzije',
'editundo'                => 'ukloni ovu izmjenu',

# Search results
'searchresults'             => 'Rezultati pretrage',
'searchresults-title'       => 'Rezultati pretrage za "$1"',
'searchsubtitle'            => 'Tražili ste \'\'\'[[:$1]]\'\'\' ([[Special:Prefixindex/$1|sve stranice koje počinju sa "$1"]]{{int:pipe-separator}}[[Special:WhatLinksHere/$1|sve stranice koje vode do "$1"]])',
'searchsubtitleinvalid'     => "Tražili ste '''$1'''",
'noexactmatch'              => "'''Ne postoji stranica sa naslovom \"\$1\".'''
Možete [[:\$1|stvoriti ovu stranicu]].",
'noexactmatch-nocreate'     => "'''Ne postoji stranica sa naslovom \"\$1\".'''",
'search-interwiki-default'  => '$1 rezultati:',
'search-interwiki-more'     => '(više)',
'search-mwsuggest-enabled'  => 'sa sugestijama',
'search-mwsuggest-disabled' => 'bez sugestija',
'powersearch'               => 'Napredna pretraga',

# Preferences page
'preferences' => 'Postavke',

# Groups
'group-sysop' => 'Administratori',

# User rights log
'rightslog' => 'Registar korisničkih prava',

# Associated actions - in the sentence "You do not have permission to X"
'action-edit' => 'uređujete ovu stranicu',

# Recent changes
'nchanges'                       => '$1 {{PLURAL:$1|izmjena|izmjene|izmjena}}',
'recentchanges-legend'           => 'Postavke za Nedavne promjene',
'recentchanges-feed-description' => 'Praćenje nedavnih izmjena na ovom wikiju u ovom feedu.',
'rcnote'                         => "Ispod {{PLURAL:$1|je '''$1''' promjena|su '''$1''' zadnje promjene|su '''$1''' zadnjih promjena}} u {{PLURAL:$2|posljednjem '''$2''' danu|posljednja '''$2''' dana|posljednjih '''$2''' dana}}, od $4, $5.",
'rclistfrom'                     => 'Prikaži nove izmjene počevši od $1',
'rcshowhideminor'                => '$1 male izmjene',
'rcshowhidebots'                 => '$1 botove',
'rcshowhideliu'                  => '$1 prijavljene korisnike',
'rcshowhideanons'                => '$1 anonimne korisnike',
'rcshowhidemine'                 => '$1 moje izmjene',
'rclinks'                        => 'Prikaži najskorijih $1 izmjena u posljednjih $2 dana<br />$3',
'rc-enhanced-expand'             => 'Pokaži detalje (neophodan JavaScript)',
'rc-enhanced-hide'               => 'Sakrij detalje',

# Recent changes linked
'recentchangeslinked'       => 'Srodne izmjene',
'recentchangeslinked-title' => 'Srodne promjene sa "$1"',
'recentchangeslinked-to'    => 'Pokaži promjene stranica koji su povezane sa datom stranicom',

# Upload
'uploadlogpage' => 'Registar postavljanja',
'uploadedimage' => 'postavljeno "[[$1]]"',

# File description page
'sharedupload'              => 'Ova datoteka/fajl je sa $1 i mogu je koristiti ostali projekti.',
'uploadnewversion-linktext' => 'Postavite novu verziju ove datoteke/fajla',

# File deletion
'filedelete-otherreason'      => 'Ostali/dodatni razlog/zi:',
'filedelete-reason-otherlist' => 'Ostali razlog/zi',

# Statistics
'statistics' => 'Statistike',

# Miscellaneous special pages
'nbytes'       => '$1 {{PLURAL:$1|bajt|bajtova}}',
'nmembers'     => '$1 {{PLURAL:$1|član|članova}}',
'prefixindex'  => 'Sve stranice sa prefiksom',
'newpages'     => 'Nove stranice',
'movethispage' => 'Premjesti ovu stranicu',

# Book sources
'booksources'               => 'Književni izvori',
'booksources-search-legend' => 'Traži književne izvore',
'booksources-go'            => 'Idi',

# Special:Log
'log' => 'Registri',

# Special:AllPages
'allpages'       => 'Sve stranice',
'alphaindexline' => '$1 do $2',
'prevpage'       => 'Prethodna stranica ($1)',
'allpagesfrom'   => 'Prikaži stranice koje počinju od:',
'allpagesto'     => 'Pokaži stranice koje završavaju na:',
'allarticles'    => 'Sve stranice',

# Special:LinkSearch
'linksearch' => 'Vanjski/spoljašni linkovi',

# Special:Log/newusers
'newuserlogpage'          => 'Registar novih korisnika',
'newuserlog-create-entry' => 'Novi korisnički račun',

# Special:ListGroupRights
'listgrouprights-members' => '(lista članova)',

# E-mail user
'emailuser' => 'Pošalji E-mail ovom korisniku',

# Watchlist
'watchlist'         => 'Moj spisak praćenja',
'watchlistfor'      => "(za '''$1''')",
'addedwatch'        => 'Dodano na listu praćenih stranica',
'addedwatchtext'    => "Stranica \"[[:\$1]]\" je dodana [[Special:Watchlist|vašoj listi praćenih stranica]].
Buduće promjene ove stranice i njoj pridružene stranice za razgovor će biti navedene ovdje, te će stranica izgledati '''podebljana''' u [[Special:RecentChanges|listi nedavnih]] izmjena kako bi se lakše uočila.",
'removedwatch'      => 'Uklonjeno s liste praćenja',
'removedwatchtext'  => 'Stranica "[[:$1]]" je uklonjena s [[Special:Watchlist|vaše liste praćenja]].',
'watchthispage'     => 'Prati ovu stranicu',
'watchlist-details' => '{{PLURAL:$1|$1 stranica praćena|$1 stranice praćene|$1 stranica praćeno}} ne računajući stranice za razgovor.',
'wlshowlast'        => 'Prikaži posljednjih $1 sati $2 dana $3',
'watchlist-options' => 'Opcije liste praćenja',

# Delete
'deletepage'            => 'Izbrišite stranicu',
'confirmdeletetext'     => 'Upravo ćete obrisati stranicu sa svom njenom historijom.
Molimo da potvrdite da ćete to učiniti, da razumijete posljedice te da to činite u skladu sa [[{{MediaWiki:Policy-url}}|pravilima]].',
'actioncomplete'        => 'Akcija završena',
'deletedtext'           => '"<nowiki>$1</nowiki>" je obrisan/a.
V. $2 za registar nedavnih brisanja.',
'dellogpage'            => 'Registar brisanja',
'deletionlog'           => 'registar brisanja',
'deletecomment'         => 'Razlog za brisanje:',
'deleteotherreason'     => 'Ostali/dodatni razlog/zi:',
'deletereasonotherlist' => 'Ostali razlog/zi',

# Protect
'protectlogpage'              => 'Registar zaštite',
'protectedarticle'            => '"[[$1]]" zaštićeno',
'modifiedarticleprotection'   => 'promijenjen nivo zaštite za "[[$1]]"',
'protectcomment'              => 'Komentar:',
'protectexpiry'               => 'Ističe:',
'protect_expiry_invalid'      => 'Upisani vremenski rok nije valjan.',
'protect_expiry_old'          => 'Upisani vremenski rok je u prošlosti.',
'protect-unchain'             => 'Deblokirajte dozvole premještanja',
'protect-text'                => "Ovdje možete gledati i izmijeniti nivo zaštite za stranicu '''<nowiki>$1</nowiki>'''.",
'protect-locked-access'       => "Nemate ovlasti za mijenjanje stepena zaštite.
Slijede trenutne postavke stranice '''$1''':",
'protect-cascadeon'           => 'Ova stranica je trenutno zaštićena jer je uključena u {{PLURAL:$1|stranicu, koja ima|stranice, koje imaju|stranice, koje imaju}} uključenu prenosnu (kaskadnu) zaštitu.  
Možete promijeniti stepen zaštite ove stranice, ali to neće uticati na prenosnu zaštitu.',
'protect-default'             => 'Dozvoli svim korisnicima',
'protect-fallback'            => 'Potrebno je imati "$1" ovlasti',
'protect-level-autoconfirmed' => 'Blokiraj nove i neregistrovane korisnike',
'protect-level-sysop'         => 'Samo administratori',
'protect-summary-cascade'     => 'prenosna (kaskadna) zaštita',
'protect-expiring'            => 'ističe $1 (UTC)',
'protect-cascade'             => 'Zaštiti sve stranice koje su uključene u ovu (kaskadna zaštita)',
'protect-cantedit'            => 'Ne možete mijenjati nivo zaštite ove stranice, jer nemate prava da je uređujete..',
'protect-otherreason'         => 'Ostali/dodatni razlog/zi:',
'protect-otherreason-op'      => 'Ostali/dodatni razlog/zi:',
'restriction-type'            => 'Dozvola:',
'restriction-level'           => 'Nivo ograničenja:',

# Undelete
'undeleteinvert'   => 'Sve osim odabranog',
'undeletedarticle' => 'vraćen "[[$1]]"',

# Namespace form on various pages
'invert'         => 'Sve osim odabranog',
'blanknamespace' => '(Glavno)',

# Contributions
'contributions'       => 'Doprinosi korisnika',
'contributions-title' => 'Korisnički doprinosi od $1',
'contribsub2'         => 'Za $1 ($2)',
'uctop'               => '(vrh)',
'month'               => 'Od mjeseca (i ranije):',
'year'                => 'Od godine (i ranije):',

'sp-contributions-newbies'  => 'Pokaži doprinose samo novih korisnika',
'sp-contributions-blocklog' => 'registar blokiranja',
'sp-contributions-talk'     => 'Razgovor',
'sp-contributions-search'   => 'Pretraga doprinosa',
'sp-contributions-username' => 'IP adresa ili korisničko ime:',
'sp-contributions-submit'   => 'Traži',

# What links here
'whatlinkshere'            => 'Šta je povezano ovdje',
'whatlinkshere-title'      => 'Stranice koje vode na "$1"',
'whatlinkshere-page'       => 'Stranica:',
'linkshere'                => "Sljedeće stranice vode na '''[[:$1]]''':",
'isredirect'               => 'preusmjeri stranicu',
'whatlinkshere-prev'       => '{{PLURAL:$1|prethodni|prethodna|prethodnih}} $1',
'whatlinkshere-next'       => '{{PLURAL:$1|sljedeći|sljedeća|sljedećih}} $1',
'whatlinkshere-links'      => '← linkovi',
'whatlinkshere-hideredirs' => '$1 preusmjerenja',
'whatlinkshere-hidelinks'  => '$1 linkove',
'whatlinkshere-filters'    => 'Filteri',

# Block/unblock
'blockip'                  => 'Blokiraj korisnika',
'ipbreasonotherlist'       => 'Ostali razlog/zi',
'ipboptions'               => '2 sata:2 hours,1 dan:1 day,3 dana:3 days,1 sedmica:1 week,2 sedmice:2 weeks,1 mjesec:1 month,3 mjeseca:3 months,6 mjeseci:6 months,1 godine:1 year,zauvijek:infinite', # display1:time1,display2:time2,...
'ipbotherreason'           => 'Ostali/dodatni razlog/zi:',
'ipblocklist'              => 'Blokirane IP adrese i korisnička imena',
'contribslink'             => 'doprinosi',
'blocklogpage'             => 'Registar blokiranja',
'blocklogentry'            => 'blokiran [[$1]] s rokom: $2 $3',
'unblocklogentry'          => 'deblokiran $1',
'block-log-flags-nocreate' => 'pravljenje računa onemogućeno',

# Move page
'movepagetext'     => "Korištenjem donjeg formulara možete preimenovati stranicu, preusmjerivši njenu historiju na novi naziv.
Stari naslov će postati stranica za preusmjerenje na novi naslov.
Možete updateirati preusmjerenja koja idu na originalni naslov automatski.
Ako to ne učinite, budite sigurni da ste provjerili [[Special:DoubleRedirects|dupla]] ili [[Special:BrokenRedirects|mrtva prusmjerenja]].
Odgovorni ste za to da poveznice nastave povezivati stranice kojima su namijenjene.

Uzmite u obzir da stranica '''neće''' biti preusmjerena ako već postoji stranica s novim naslovom, osim ako je prazna ili ako je preusmjerenje bez prethodne historije uređivanja.
To znači da stranicu možete ponovno preimenovati u stari naslov ako je u pitanju bila pogreška, te da ne možete presnimiti već postojeću stranicu.

'''UPOZORENJE!'''
Ovo može biti drastična i neočekivana promjena za popularnu stranicu;
budite sigurni da ste shvatili sve posljedice prije nego što nastavite.",
'movepagetalktext' => "Odgovarajuća stranica za razgovor, ako postoji, će automatski biti premještena istovremeno '''osim:'''
*Neprazna stranica za razgovor već postoji pod novim imenom, ili
*Odznačite donju kutiju.

U tim slučajevima, moraćete ručno da premjestite stranicu ukoliko to želite.",
'movearticle'      => 'Premjestite stranicu:',
'newtitle'         => 'novi naslov:',
'move-watch'       => 'Prati ovu stranicu',
'movepagebtn'      => 'premjestite stranicu',
'pagemovedsub'     => 'Premještanje uspjelo',
'movepage-moved'   => '<big>\'\'\'"$1" je premještena na "$2"\'\'\'</big>', # The two titles are passed in plain text as $3 and $4 to allow additional goodies in the message.
'articleexists'    => 'Stranica pod tim imenom već postoji, ili je ime koje ste izabrali neispravno.  
Molimo Vas da izaberete drugo ime.',
'talkexists'       => "'''Sama stranica je uspješno premještena, ali stranica za razgovor nije mogla biti premještena jer takva već postoji na novom naslovu.  Molimo Vas da ih spojite ručno.'''",
'movedto'          => 'premještena na',
'movetalk'         => 'Premjestite pridruženu stranicu za razgovor',
'1movedto2'        => '[[$1]] premješteno na [[$2]]',
'1movedto2_redir'  => 'premještena [[$1]] na [[$2]] putem preusmjerenja',
'movelogpage'      => 'Registar premještanja',
'movereason'       => 'Razlog:',

# Export
'export' => 'Izvezite stranice',

# Namespace 8 related
'allmessages' => 'Sistemske poruke',

# Tooltip help for the actions
'tooltip-pt-login'                => 'Predlažem da se prijavite; međutim, to nije obavezno',
'tooltip-ca-talk'                 => 'Razgovor o sadržaju stranice',
'tooltip-ca-addsection'           => 'Započnite novu sekciju.',
'tooltip-ca-viewsource'           => 'Ova stranica je zaštićena.
Možete vidjeti njen izvor',
'tooltip-ca-history'              => 'Prethodne verzije ove stranice',
'tooltip-ca-protect'              => 'Zaštiti ovu stranicu',
'tooltip-ca-delete'               => 'Izbriši ovu stranicu',
'tooltip-ca-watch'                => 'Dodajte ovu stranicu na Vaš spisak praćenja',
'tooltip-ca-unwatch'              => 'Izbrišite ovu stranicu sa spiska praćenja',
'tooltip-search'                  => 'Pretraži ovaj wiki',
'tooltip-search-go'               => 'Idi na stranicu s upravo ovakvim imenom ako postoji',
'tooltip-search-fulltext'         => 'Pretraga stranica sa ovim tekstom',
'tooltip-n-mainpage'              => 'Posjetite glavnu stranu',
'tooltip-n-portal'                => 'O projektu, što možete učiniti, gdje možete naći stvari',
'tooltip-n-recentchanges'         => 'Spisak nedavnih izmjena na wikiju.',
'tooltip-n-randompage'            => 'Otvorite slučajnu stranicu',
'tooltip-n-help'                  => 'Mjesto gdje možete nešto da naučite',
'tooltip-t-whatlinkshere'         => 'Spisak svih stranica povezanih sa ovim',
'tooltip-t-recentchangeslinked'   => 'Nedavne izmjene ovdje povezanih stranica',
'tooltip-feed-rss'                => 'RSS feed za ovu stranicu',
'tooltip-feed-atom'               => 'Atom feed za ovu stranicu',
'tooltip-t-contributions'         => 'Pogledajte listu doprinosa ovog korisnika',
'tooltip-t-emailuser'             => 'Pošaljite e-mail ovom korisniku',
'tooltip-t-specialpages'          => 'Popis svih posebnih stranica',
'tooltip-t-permalink'             => 'Stalni link ove verzije stranice',
'tooltip-ca-nstab-main'           => 'Pogledajte sadržaj stranice',
'tooltip-ca-nstab-user'           => 'Pogledajte korisničku stranicu',
'tooltip-ca-nstab-special'        => 'Ovo je posebna stranica, te je ne možete uređivati',
'tooltip-ca-nstab-project'        => 'Pogledajte stranicu projekta',
'tooltip-ca-nstab-category'       => 'Pogledajte stranicu kategorije',
'tooltip-minoredit'               => 'Označite ovo kao manju izmjenu',
'tooltip-preview'                 => 'Prethodni pregled stranice, molimo koristiti prije snimanja!',
'tooltip-diff'                    => 'Prikaz izmjena koje ste napravili u tekstu',
'tooltip-compareselectedversions' => 'Pogledajte pazlike između dvije selektovane verzije ove stranice.',
'tooltip-watch'                   => 'Dodajte ovu stranicu na Vaš spisak praćenja',

# Browsing diffs
'previousdiff' => '← Starija izmjena',
'nextdiff'     => 'Novija izmjena →',

# Media information
'file-info-size'       => '($1 × $2 piksela, veličina datoteke/fajla: $3, MIME tip: $4)',
'file-nohires'         => '<small>Veća rezolucija nije dostupna.</small>',
'svg-long-desc'        => '(SVG fajl, nominalno $1 × $2 piksela, veličina fajla: $3)',
'show-big-image'       => 'Puna rezolucija',
'show-big-image-thumb' => '<small>Veličina ovog prikaza: $1 × $2 piksela</small>',

# Special:NewFiles
'showhidebots' => '($1 botove)',

# Metadata
'metadata'          => 'Metapodaci',
'metadata-help'     => 'Ova datoteka/fajl sadržava dodatne podatke koje je vjerojatno dodala digitalna kamera ili skener u procesu snimanja odnosno digitalizacije. Ako je datoteka/fajl mijenjana u odnosu na prvotno stanje, neki detalji možda nisu u skladu s trenutnim stanjem.',
'metadata-expand'   => 'Pokaži dodatne detalje',
'metadata-collapse' => 'Sakrij dodatne detalje',
'metadata-fields'   => "Sljedeći EXIF metapodaci će biti prikazani ispod slike u tablici s metapodacima. Ostali će biti sakriveni (možete ih vidjeti ako kliknete na link ''Pokaži sve detalje'').
* make
* model
* datetimeoriginal
* exposuretime
* fnumber
* focallength", # Do not translate list items

# External editor support
'edit-externally'      => 'Izmijeni ovu datoteku/fajl koristeći eksternu aplikaciju',
'edit-externally-help' => '(Pogledajte [http://www.mediawiki.org/wiki/Manual:External_editors instrukcije za podešavanje] za više informacija)',

# 'all' in various places, this might be different for inflected languages
'watchlistall2' => 'sve',
'monthsall'     => 'sve',

# Watchlist editing tools
'watchlisttools-view' => 'Vidi relevantne promjene',
'watchlisttools-edit' => 'Vidi i uredi listu praćenja',
'watchlisttools-raw'  => 'Uredi grubu listu praćenja',

# Special:SpecialPages
'specialpages' => 'Posebne stranice',

);
