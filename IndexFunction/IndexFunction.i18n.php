<?php
/**
 * Internationalisation file for IndexFunction extension.
 *
 * @addtogroup Extensions
*/

$messages = array();

$messages['en'] = array(
	'indexfunc-desc' => 'Parser function to create automatic redirects and disambiguation pages',

	'indexfunc-badtitle' => 'Invalid title: "$1"',
	'indexfunc-editwarning' => 'Warning:
This title is an index title for the following {{PLURAL:$2|page|pages}}:
$1
Be sure the page you are about to create does not already exist under a different title.
If you create this page, remove this title from the <nowiki>{{#index:}}</nowiki> on the above {{PLURAL:$2|page|pages}}.',
	'indexfunc-index-exists' => 'The page "$1" already exists',
	'indexfunc-movewarn' => 'Warning:
"$1" is an index title for the following {{PLURAL:$3|page|pages}}:
$2
Please remove "$1" from the <nowiki>{{#index:}}</nowiki> on the above {{PLURAL:$3|page|pages}}.',

	'index' => 'Index search',
	'index-legend' => 'Search the index',
	'index-search' => 'Search:',
	'index-submit' => 'Submit',
	'index-disambig-start' => "'''$1''' may refer to several pages:",
	'index-exclude-categories' => '', # List of categories to exclude from the auto-disambig pages
	'index-emptylist' => 'There are no pages associated with "$1"',
	'index-expand-detail' => 'Show pages indexed under this title',
	'index-hide-detail' => 'Hide the list of pages',
	'index-no-results' => 'The search returned no results',	
	'index-search-explain' => 'This page uses a prefix search. 
	
Type the first few characters and press the submit button to search for page titles and index entries that start with the search string',
	'index-details-explain' => 'Entries with arrows are index entries.
Click the arrow to show all pages indexed under that title.',
);

/** Message documentation (Message documentation)
 * @author Bennylin
 * @author Fryed-peach
 * @author Purodha
 * @author Raymond
 */
$messages['qqq'] = array(
	'indexfunc-desc' => '{{desc}}',
	'indexfunc-badtitle' => '{{Identical|Invalid title}}',
	'index' => 'This is either the name of the parser function, to be used inside the wiki code, or not used, if I got it right. --[[User:Purodha|Purodha Blissenbach]] 00:13, 15 July 2009 (UTC)
{{Identical|Index}}',
	'index-legend' => 'Used in [[Special:Index]].',
	'index-search' => '{{Identical|Search}}',
	'index-submit' => '{{Identical|Submit}}',
	'index-search-explain' => 'If your language permits, you can replace <code>submit</code> with <code>{<nowiki />{int:{{msg-mw|index-submit}}}}</code> for the button label.',
);

/** Belarusian (Taraškievica orthography) (Беларуская (тарашкевіца))
 * @author EugeneZelenko
 * @author Jim-by
 */
$messages['be-tarask'] = array(
	'indexfunc-desc' => 'Функцыя парсэра для стварэньня аўтаматычных перанакіраваньняў і старонак неадназначнасьцяў',
	'indexfunc-badtitle' => 'Няслушная назва: «$1»',
	'indexfunc-editwarning' => 'Папярэджаньне: Гэтая назва зьяўляецца індэкснай для {{PLURAL:$2|наступнай старонкі|наступных старонак}}: $1
Упэўніцеся, што старонка, якую Вы зьбіраецеся стварыць, яшчэ не існуе зь іншай назвай.
Калі Вы створыце гэту старонку, выдаліце гэту назву з <nowiki>{{#index:}}</nowiki> у {{PLURAL:$2|наступнай старонцы|наступных старонках}}.',
	'indexfunc-index-exists' => 'Старонка «$1» ужо існуе',
	'indexfunc-movewarn' => 'Папярэджаньне: «$1» зьяўляецца індэкснай назвай для {{PLURAL:$3|наступнай старонкі|наступных старонак}}: $2
Калі ласка, выдаліце «$1» з <nowiki>{{#index:}}</nowiki> у {{PLURAL:$3|наступнай старонцы|наступных старонках}}.',
	'index' => 'Індэкс',
	'index-legend' => 'Пошук у індэксе',
	'index-search' => 'Пошук:',
	'index-submit' => 'Адправіць',
	'index-disambig-start' => "'''$1''' можа адносіцца да некалькіх старонак:",
	'index-emptylist' => 'Няма старонак зьвязаных з «$1»',
	'index-expand-detail' => 'Паказаць старонкі праіндэксаваныя пад гэтай назвай',
	'index-hide-detail' => 'Схаваць сьпіс старонак',
	'index-no-results' => 'Пошук не прынёс выніках',
	'index-search-explain' => 'Гэта старонка выкарыстоўвае прэфіксны пошук.

Увядзіце першыя некалькі сымбаляў і націсьніце кнопку для пошуку назваў старонак і індэксных запісаў, якія пачынаюцца з пошукавага радку',
	'index-details-explain' => 'Запісы са стрэлкамі зьяўляюцца індэкснымі, націсьніце на стрэлку для паказу ўсіх старонак праіндэксаваных пад гэтай назвай.',
);

/** Bosnian (Bosanski)
 * @author CERminator
 */
$messages['bs'] = array(
	'indexfunc-desc' => 'Parserska funkcija za pravljenje automatskih preusmjerenja i čvor stranica',
	'indexfunc-badtitle' => 'Nevaljan naslov: "$1"',
	'indexfunc-index-exists' => 'Stranica "$1" već postoji',
	'index-legend' => 'Pretraživanje indeksa',
	'index-search' => 'Traži:',
	'index-submit' => 'Pošalji',
	'index-disambig-start' => "'''$1''' se može odnositi na nekoliko stranica:",
	'index-missing-param' => 'Ova stranica ne može biti korištena bez parametara',
	'index-emptylist' => 'Nema stranica povezanih sa "$1"',
);

/** Lower Sorbian (Dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'indexfunc-desc' => 'Parserowa funkcija za napóranje awtomatiskich dalejpósrědnjenjow a bokow za rozjasnjenje zapśimjeśow',
	'indexfunc-badtitle' => 'Njepłaśiwy titel: "$1"',
	'indexfunc-editwarning' => 'Warnowanje: Toś ten titel jo indeksowy titel za {{PLURAL:$2|slědujucy bok|slědujucej boka|slědujuce boki|slědujuce boki}}: $1
Wobwěsć se, až bok, kótaryž coš napóraś, hyšći njeeksistěrujo pód drugim titelom.
Jolic napórajoš toś ten bok, wótpóraj toś ten titel z <nowiki>{{#index:}}</nowiki> na {{PLURAL:$2|górjejcnem boku|górjejcnyma bokoma|górjejcnych bokach|górjejcnych bokach}}.',
	'indexfunc-index-exists' => 'Bok "$1" južo eksistěrujo',
	'indexfunc-movewarn' => 'Warnowanje: "$2" jo indeksowy titel za {{PLURAL:$3|slědujucy bok|slědujucej boka|slědujuce boki|slědujuce boki}}: $2
Pšosym wótpóraj "$1" z <nowiki>{{#index:}}</nowiki> na {{PLURAL:$3|górjejcnem boku|górjejcnyma bokoma|górjejcnych bokach|górjejcnych bokach}}.',
	'index' => 'Indeksowe pytanje',
	'index-legend' => 'Indeks pśepytaś',
	'index-search' => 'Pytaś:',
	'index-submit' => 'Wótpósłaś',
	'index-disambig-start' => "'''$1''' móžo se na někotare boki póśěgnuś:",
	'index-emptylist' => 'Njejsu boki zwězane z "$1"',
	'index-expand-detail' => 'Boki, kótarež su pód toś tym titelom indicěrowane, pokazaś',
	'index-hide-detail' => 'Lisćinu bokow schowaś',
	'index-no-results' => 'Pytanje njejo wuslědki wrośiło',
	'index-search-explain' => 'Toś ten bok wužywa prefiksowe pytanje.

Zapódaj nejpjerwjej někotare znamuška a klikni tłocašk {{int:index-submit}}, aby titele bokow a indeksowe zapiski pytał, kótarež zachopinaju se z pytańskim wurazom',
	'index-details-explain' => 'Zapiski ze šypkami su indeksowe zapiski, klikni na šypku, aby wše boki pokazali, kótarež su pód tym titelom indicěrowane.',
);

/** Spanish (Español)
 * @author Crazymadlover
 */
$messages['es'] = array(
	'indexfunc-desc' => 'Función analizadora para crear redirecciones y páginas de desambiguación',
	'indexfunc-badtitle' => 'Título inválido: "$1"',
	'indexfunc-index-exists' => 'La página "$1" ya existe',
	'index-legend' => 'Buscar el índice',
	'index-search' => 'Buscar:',
	'index-submit' => 'Enviar',
	'index-disambig-start' => "'''$1''' puede referir a varias páginas:",
	'index-missing-param' => 'Esta página no puede ser usada sin parámetros',
	'index-emptylist' => 'No hay páginas asociadas con "$1"',
);

/** French (Français)
 * @author Crochet.david
 * @author IAlex
 */
$messages['fr'] = array(
	'indexfunc-desc' => "Fonction du parseur pour créer des pages de redirection et d'homonymie automatiquement",
	'indexfunc-badtitle' => 'Titre invalide : « $1»',
	'indexfunc-editwarning' => "Attention : ce titre est un titre d'index pour {{PLURAL:$2|la page suivante|les pages suivantes}} :
$1
Soyez sûr que la page que vous êtes sur le point de créer n'existe pas sous un autre titre.
Si vous créez cette page, retirez-là de <nowiki>{{#index:}}</nowiki> {{PLURAL:$2|de la page|des pages}} ci-dessus.",
	'indexfunc-index-exists' => 'La page « $1 » existe déjà',
	'indexfunc-movewarn' => "Attention : « $1 »  est un titre d'index pour {{PLURAL:$3|la page suivante|les pages suivantes}} :
$2
Enlevez « $1 » de <nowiki>{{#index:}}</nowiki> {{PLURAL:$3|de la page|des pages}} ci-dessus.",
	'index' => 'Index',
	'index-legend' => 'Rechercher dans l’index',
	'index-search' => 'Chercher:',
	'index-submit' => 'Envoyer',
	'index-disambig-start' => "'''$1''' peut se référer à plusieurs pages :",
	'index-emptylist' => 'Il n’y a pas de pages liées à « $1 »',
	'index-expand-detail' => 'Afficher les pages indexées sous ce titre',
	'index-hide-detail' => 'Masque la liste des pages',
	'index-no-results' => "La recherche n'a retourné aucun résultat",
	'index-details-explain' => "Les entrées avec des flèches sont des entrées d'index, cliquez sur la flèche pour voir toutes les pages indexées sous ce titre.",
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'indexfunc-desc' => 'Funcións analíticas para crear redireccións automáticas e páxinas de homónimos',
	'indexfunc-badtitle' => 'Título inválido: "$1"',
	'indexfunc-editwarning' => 'Aviso: este título é un título de índice para {{PLURAL:$2|a seguinte páxina|as seguintes páxinas}}:
$1
Asegúrese de que a páxina que está a piques de crear aínda non foi creada cun título diferente.
Se crea esta páxina, elimine este título de <nowiki>{{#index:}}</nowiki> {{PLURAL:$2|na páxina de enriba|nas páxinas de enriba}}.',
	'indexfunc-index-exists' => 'A páxina "$1" xa existe',
	'indexfunc-movewarn' => 'Aviso: "$1" é un título de índice para {{PLURAL:$3|a seguinte páxina|as seguintes páxinas}}:
$2
Por favor, elimine "$1" de <nowiki>{{#index:}}</nowiki> {{PLURAL:$3|na páxina de enriba|nas páxinas de enriba}}.',
	'index-legend' => 'Procurar no índice',
	'index-search' => 'Procurar:',
	'index-submit' => 'Enviar',
	'index-disambig-start' => "'''$1''' pódese referir a varias páxinas:",
	'index-missing-param' => 'Esta páxina non se pode usar sen parámetros',
	'index-emptylist' => 'Non hai páxinas asociadas con "$1"',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'indexfunc-desc' => 'Parserfunktion go automatischi Wyterleitige un Begriffsklärige aalege',
	'indexfunc-badtitle' => 'Nit giltige Titel „$1“',
	'indexfunc-index-exists' => 'D Syte „$1“ git s scho.',
	'index-legend' => 'S Verzeichnis dursueche',
	'index-search' => 'Suech:',
	'index-submit' => 'Abschicke',
	'index-disambig-start' => "'''$1''' cha zue verschidene Syte ghere:",
	'index-missing-param' => 'Die Syte cha nit brucht wäre, ohni ass Parameter aagee sin',
	'index-emptylist' => 'S git kei Syte, wu zue „$1“ ghere',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'indexfunc-desc' => 'Parserowa funkcija za wutworjenje awtomatiskich daleposrědkowanjow a rozjasnjenjow wjacezmyslnosćow',
	'indexfunc-badtitle' => 'Njepłaćiwy titul: "$1"',
	'indexfunc-editwarning' => 'Kedźbu: Tutón titul je indeksowy titul za {{PLURAL:$2|slědowacu stronu|slědowacej stronje|slědowace strony|slědowace strony}}: $1
Přeswědčče so, zo strona, kotruž chceš wutworić, pod druhim titulom hišće njeeksistuje.
Jeli tutu stronu wutworiš, wotstroń tutón titul z <nowiki>{{#index:}}</nowiki> na {{PLURAL:$2|hornjej stronje|hornimaj stronomaj|hornich stronach|hornich stronach}}.',
	'indexfunc-index-exists' => 'Strona "$1" hižo eksistuje',
	'indexfunc-movewarn' => 'Kedźbu: "$1" je indeksowy titul za {{PLURAL:$3|slědowacu stronu|slědowacej stronje|slědowace strony|slědowace strony}}: $2
Prošu wotstroń "$1" z <nowiki>{{#index:}}</nowiki> na {{PLURAL:$3|hornjej stronje|hornimaj stronomaj|hornich stronach|hornich stronach}}.',
	'index' => 'Indeks',
	'index-legend' => 'Indeks přepytać',
	'index-search' => 'Pytać:',
	'index-submit' => 'Wotpósłać',
	'index-disambig-start' => "'''$1''' móže so na wjacore strony poćahować:",
	'index-emptylist' => 'Njejsu strony, kotrež su z "$1" zwjazane.',
	'index-expand-detail' => 'Strony pokazać, kotrež su pod tutym titulom indikowane',
	'index-hide-detail' => 'Lisćinu stronow schować',
	'index-no-results' => 'Pytanje njeje žane wuslědki přinjesło',
	'index-search-explain' => 'Tuta strona prefiksowe pytanje wužiwa.

Zapodaj najprjedy někotre znamješka a klikń na tłóčatko {{int:index-submit}}, zo by titule stronow a indeksowe zapiski pytał, kotrež so z pytanskim tekstom započinaja',
	'index-details-explain' => 'Zapiski z šipkami su indeksowe zapiski, klikń na šipk, zo by wšě strony pokazał, kotrež su pod tym titulom indikowane.',
);

/** Interlingua (Interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'indexfunc-desc' => 'Function del analysator syntactic pro le creation automatic de redirectiones e paginas de disambiguation',
	'indexfunc-badtitle' => 'Titulo invalide: "$1"',
	'indexfunc-index-exists' => 'Le pagina "$1" ja existe',
	'index-legend' => 'Cercar in le indice',
	'index-search' => 'Cerca:',
	'index-submit' => 'Submitter',
	'index-disambig-start' => "'''$1''' pote referer a plure paginas:",
	'index-missing-param' => 'Iste pagina non pote esser usate sin parametros',
	'index-emptylist' => 'Il non ha paginas associate con "$1"',
);

/** Indonesian (Bahasa Indonesia)
 * @author Bennylin
 */
$messages['id'] = array(
	'indexfunc-desc' => 'Fungsi parser untuk membuat pengalihan otomatis dan halaman disambiguasi',
	'indexfunc-badtitle' => 'Judul tidak sah: "$1"',
	'indexfunc-index-exists' => 'Halaman "$1" sudah ada',
	'index-legend' => 'Cari di indeks',
	'index-search' => 'Cari:',
	'index-submit' => 'Kirim',
	'index-disambig-start' => "'''$1''' dapat mengacu kepada:",
	'index-missing-param' => 'Halaman ini tidak dapat digunakan tanpa parameter',
	'index-emptylist' => 'Tidah ada halaman yang berhubungan dengan "$1"',
);

/** Japanese (日本語)
 * @author Fryed-peach
 * @author Hosiryuhosi
 * @author 青子守歌
 */
$messages['ja'] = array(
	'indexfunc-desc' => '自動的なリダイレクトや曖昧さ回避ページを作成するためのパーサー関数',
	'indexfunc-badtitle' => '不正なタイトル:「$1」',
	'indexfunc-index-exists' => 'ページ「$1」は既に存在します。',
	'index-legend' => '索引の検索',
	'index-search' => '検索:',
	'index-submit' => '送信',
	'index-disambig-start' => "「'''$1'''」はいくつかのページを指す可能性があります:",
	'index-missing-param' => 'このページは引数なしで使用できません',
	'index-emptylist' => '「$1」と関連付けられたページはありません',
);

/** Ripoarisch (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'indexfunc-desc' => 'Paaserfunxjuhn för Ömleijdunge un „Watt ėßß datt?“-Sigge automattesch jemaat ze krijje.',
	'indexfunc-badtitle' => '„$1“ es ene onjöltije Sigge-Tittel.',
	'indexfunc-index-exists' => 'Di Sigg „$1“ jitt et ald.',
	'index-legend' => 'Donn en de automatesche Ömleidunge un „Watt ėßß datt?“-Leßte söhke',
	'index-search' => 'Söhk noh:',
	'index-submit' => 'Lohß Jonn!',
	'index-disambig-start' => "Dä Tittel '''$1''' deiht op ongerscheidlijje Sigge paße:",
	'index-missing-param' => 'Heh di Sigg kann nit der oohne ene Parremeter jebruch wääde.',
	'index-emptylist' => 'Mer han kein Sigge, di met „$1“ verbonge wöhre.',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'indexfunc-desc' => 'Parser-Fonctioun fir Viruleedungen an Homonymie-Säiten automatesch unzeleeën',
	'indexfunc-badtitle' => 'Net valabelen Titel: "$1"',
	'indexfunc-index-exists' => 'D\'Säit "$1" gëtt et schonn',
	'index' => 'Index',
	'index-legend' => 'Am Index sichen',
	'index-search' => 'Sichen:',
	'index-submit' => 'Schécken',
	'index-disambig-start' => "'''$1''' ka sech op méi Säite bezéien:",
	'index-emptylist' => 'Et gëtt keng Säiten déi mat "$1" assoziéiert sinn',
	'index-expand-detail' => 'Déi Säite weisen déi ënner dësem Titel indexéiert sinn',
	'index-hide-detail' => "D'Lëscht vu Säite verstoppen",
	'index-no-results' => "D'Sich hat keng Resultater",
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'indexfunc-desc' => "Parserfunctie om automatisch doorverwijzingen en doorverwijspagina's aan te maken",
	'indexfunc-badtitle' => 'Ongeldige paginanaam: "$1"',
	'indexfunc-editwarning' => "Waarschuwing: deze pagina is een indexpagina voor de volgende {{PLURAL:$2|pagina|pagina's}}:
$1
Zorg ervoor dat de pagina die u wilt aanmaken niet al bestaat onder een andere naam.
Als u deze pagina aanmaakt, verwijder deze dan uit de <nowiki>{{#index:}}</nowiki> in de bovenstaande {{PLURAL:$2|pagina|pagina's}}.",
	'indexfunc-index-exists' => 'De pagina "$1" bestaat al',
	'indexfunc-movewarn' => 'Waarschuwing: "$1" is een indexpagina voor de volgende {{PLURAL:$3|pagina|pagina\'s}}:
$2
Verwijder "$1" uit de <nowiki>{{#index:}}</nowiki> op de bovenstaande {{PLURAL:$3|pagina|pagina\'s}}.',
	'index' => 'Index',
	'index-legend' => 'De index doorzoeken',
	'index-search' => 'Zoeken:',
	'index-submit' => 'OK',
	'index-disambig-start' => "'''$1''' kan verwijzen naar meerdere pagina's:",
	'index-emptylist' => 'Er zijn geen pagina\'s geassocieerd met "$1"',
	'index-expand-detail' => "Onder deze naam geïndexeerde pagina's weergeven",
	'index-hide-detail' => "Lijst met pagina' verbergen",
	'index-no-results' => 'De zoekopdracht heeft geen resultaten opgeleverd',
	'index-search-explain' => 'Deze pagina maakt gebruik van zoeken op voorvoegsel.

Voer de eerste paar letters in en druk op de verzendknop om te zoeken naar paginanamen en trefwoorden die beginnen met de opgegeven zoekreeks',
	'index-details-explain' => "Trefwoorden met pijlen komen uit de index.
Klik op de pijl om alle onder die paginaam geïndexeerde pagina's weer te geven.",
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'indexfunc-desc' => "Foncion del parser per crear de paginas de redireccion e d'omonimia automaticament",
	'indexfunc-badtitle' => 'Títol invalid : « $1»',
	'indexfunc-editwarning' => "Atencion : aqueste títol es un títol d'indèx per {{PLURAL:$2|la pagina seguenta|las paginas seguentas}} :
$1
Siatz segur(a) que la pagina que sètz a mand de crear existís pas jos un autre títol.
Se creatz aquesta pagina, levatz-la de <nowiki>{{#index:}}</nowiki> {{PLURAL:$2|de la pagina|de las paginas}} çaisús.",
	'indexfunc-index-exists' => 'La pagina « $1 » existís ja',
	'indexfunc-movewarn' => "Atencion : « $1 »  es un títol d'indèx per {{PLURAL:$3|la pagina seguenta|las paginas seguentas}} :
$2
Levatz « $1 » de <nowiki>{{#index:}}</nowiki> {{PLURAL:$3|de la pagina|de las paginas}} çaisús.",
	'index' => 'Indèx',
	'index-legend' => 'Recercar dins l’indèx',
	'index-search' => 'Cercar :',
	'index-submit' => 'Mandar',
	'index-disambig-start' => "'''$1''' se pòt referir a mai d'una pagina :",
	'index-emptylist' => 'I a pas de paginas ligadas a « $1 »',
	'index-expand-detail' => 'Afichar las paginas indexadas jos aqueste títol',
	'index-hide-detail' => 'Amaga la lista de las paginas',
	'index-no-results' => 'La recèrca a pas tornat cap de resultat',
	'index-details-explain' => "Las entradas amb de sagetas son d'entradas d'indèx, clicatz sus la sageta per veire totas las paginas indexadas jos aqueste títol.",
);

/** Russian (Русский)
 * @author Ferrer
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'indexfunc-desc' => 'Функция парсера для создания автоматических перенаправлений и страниц неоднозначностей',
	'indexfunc-badtitle' => 'Ошибочный заголовок «$1»',
	'indexfunc-editwarning' => 'Предупреждение. Это название является индексным для {{PLURAL:$2|следующей страницы|следующих страниц}}:
$1
Убедитесь, что страница, которую вы собираетесь создать, не существует под другим названием.
Если вы создаёте эту страницу, удалите это название из <nowiki>{{#index:}}</nowiki> на {{PLURAL:$2|указанной выше странице|указанных выше страницах}}.',
	'indexfunc-index-exists' => 'Страница «[[$1]]» уже существует',
	'indexfunc-movewarn' => 'Предупреждение. «$1» является индексным названием для {{PLURAL:$3|следующей страницы|следующих страниц}}:
$2
Пожалуйста, удалите «$1» из <nowiki>{{#index:}}</nowiki> на {{PLURAL:$2|указанной выше странице|указанных выше страницах}}.',
	'index' => 'Индекс',
	'index-legend' => 'Поиск по индексу',
	'index-search' => 'Поиск:',
	'index-submit' => 'Отправить',
	'index-disambig-start' => "'''$1''' может относиться к нескольким страницам:",
	'index-emptylist' => 'Нет страниц, связанных с «$1»',
	'index-expand-detail' => 'Показать страницы, проиндексированные под этим заголовком',
	'index-hide-detail' => 'Скрыть список страниц',
	'index-no-results' => 'Поиск не дал результатов',
	'index-search-explain' => 'Эта страница осуществляет префиксный поиск.

Введите несколько первых символов и нажмите кнопку отправки запроса, чтобы осуществить поиск по заголовкам страниц и индексным записям, начинающимся с заданной строки',
	'index-details-explain' => 'Элементы со стрелками являются индексными записями, нажмите на стрелку, чтобы показать все страницы, проиндексированные в соответствии с этим названием.',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'indexfunc-desc' => 'Funkcia syntaktického analyzátora na automatickú tvorbu presmerovaní a rozlišovacích stránok',
	'indexfunc-badtitle' => 'Neplatný názov: „$1“',
	'indexfunc-editwarning' => 'Upozornenie: Tento názov je indexový názov {{PLURAL:$2|nasledovnej stránky|nasledovných stránok}}:
$1
Uistite sa, že stránka, ktorú sa chystáte vytvoriť už neexistuje pod iným názvom.
Ak túto stránku vytvoríte, odstráňte jej názov z <nowiki>{{#index:}}</nowiki> v hore {{PLURAL:$2|uvedenej stránke|uvedených stránkach}}.',
	'indexfunc-index-exists' => 'Stránka „$1“ už existuje',
	'indexfunc-movewarn' => 'Upozornenie: „$1“ je indexový názov {{PLURAL:$3|nasledovnej stránky|nasledovných stránok}}:
$2
Prosím, odstráňte „$1“ z <nowiki>{{#index:}}</nowiki> v hore {{PLURAL:$3|uvedenej stránke|uvedených stránkach}}.',
	'index-legend' => 'Hľadať v indexe',
	'index-search' => 'Hľadať:',
	'index-submit' => 'Odoslať',
	'index-disambig-start' => "'''$1''' môže odkazovať na niekoľko stránok:",
	'index-missing-param' => 'Túto stránku nemožno použiť bez zadania parametrov',
	'index-emptylist' => 'S „$1“ nesúvisia žiadne stránky',
);

