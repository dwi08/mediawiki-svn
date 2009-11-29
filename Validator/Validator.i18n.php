<?php

/**
 * Internationalization file for the Validator extension
 *
 * @file Validator.i18n.php
 * @ingroup Validator
 *
 * @author Jeroen De Dauw
*/

$messages = array();

/** English
 * @author Jeroen De Dauw
 */
$messages['en'] = array(
	'validator_name' => 'Validator',
	'validator_desc' => 'Validator provides an easy way for other extensions to validate parameters of parser functions and tag extensions, set default values and generate error messages.',

	'validator_error_parameters' => 'The following errors have been detected in your syntax',

	'validator_error_unknown_argument' => '$1 is not a valid parameter.',
	'validator_error_invalid_argument' => 'The value $1 is not valid for parameter $2.',
	'validator_error_empty_argument' => 'Parameter $1 can not have an empty value.',
	'validator_error_required_missing' => 'The required parameter $1 is not provided.',

	'validator_error_must_be_number' => 'Parameter $1 can only be a number.',
	'validator_error_ivalid_range' => 'Parameter $1 must be between $2 and $3.',
	'validator_error_accepts_only' => 'Parameter $1 only accepts {{PLURAL:$2|this value|these values}}: $2.',	
);

/** Belarusian (Taraškievica orthography) (Беларуская (тарашкевіца))
 * @author EugeneZelenko
 * @author Jim-by
 */
$messages['be-tarask'] = array(
	'validator_error_parameters' => 'У сынтаксісе выяўлены наступныя памылкі',

	'validator_error_invalid_argument' => 'Значэньне $1 не зьяўляецца слушным для парамэтру $2.',
	'validator_error_empty_argument' => 'Парамэтар $1 ня можа мець пустое значэньне.',
	'validator_error_required_missing' => 'Не пададзены абавязковы парамэтар $1.',

	'validator_error_must_be_number' => 'Парамэтар $1 можа быць толькі лікам.',
	'validator_error_ivalid_range' => 'Парамэтар $1 павінен быць паміж $2 і $3.',
);

/** Breton (Brezhoneg)
 * @author Fohanno
 * @author Fulup
 * @author Y-M D
 */
$messages['br'] = array(
	'validator_error_parameters' => 'Kavet eo bet ar fazioù da-heul en o ereadur',

	'validator_error_invalid_argument' => "N'eo ket reizh an dalvoudenn $1 evit an arventenn $2.",
	'validator_error_empty_argument' => "N'hall ket an arventenn $1 bezañ goullo he zalvoudenn",
	'validator_error_required_missing' => "N'eo ket bet pourchaset an arventenn rekis $1",

	'validator_error_must_be_number' => 'Un niver e rank an arventenn $1 bezañ hepken.',
	'validator_error_ivalid_range' => 'Rankout a ra an arventenn $1 bezañ etre $2 hag $3.',
);

/** Lower Sorbian (Dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'validator_error_parameters' => 'Slědujuce zmólki su se namakali w twójej syntaksy:',

	'validator_error_invalid_argument' => 'Gódnota $1 njejo płaśiwa za parameter $2.',
	'validator_error_empty_argument' => 'Parameter $1 njamóžo proznu gódnotu měś.',
	'validator_error_required_missing' => 'Trěbny parameter $1 njejo pódany.',

	'validator_error_must_be_number' => 'Parameter $1 móžo jano licba byś.',
	'validator_error_ivalid_range' => 'Parameter $1 musy mjazy $2 a $3 byś.',
);

/** Spanish (Español)
 * @author Crazymadlover
 * @author Translationista
 */
$messages['es'] = array(
	'validator_error_empty_argument' => 'El parámetro $1 no puede tener un valor vacío.',
	'validator_error_required_missing' => 'No se ha provisto el parámetro requerido $1.',

	'validator_error_must_be_number' => 'El parámetro $1 sólo puede ser un número.',
	'validator_error_ivalid_range' => 'El parámetro $1 debe ser entre $2 y $3.',
);

/** French (Français)
 * @author Crochet.david
 * @author IAlex
 * @author McDutchie
 * @author PieRRoMaN
 * @author Verdy p
 */
$messages['fr'] = array(
	'validator_error_parameters' => 'Les erreurs suivantes ont été détectées dans votre syntaxe',

	'validator_error_invalid_argument' => "La valeur $1 n'est pas valide pour le paramètre $2.",
	'validator_error_empty_argument' => 'Le paramètre $1 ne peut pas avoir une valeur vide.',
	'validator_error_required_missing' => "Le paramètre requis $1 n'est pas fourni.",

	'validator_error_must_be_number' => 'Le paramètre $1 peut être uniquement un nombre.',
	'validator_error_ivalid_range' => 'Le paramètre $1 doit être entre $2 et $3.',
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'validator_error_parameters' => 'Detectáronse os seguintes erros na sintaxe empregada',

	'validator_error_invalid_argument' => 'O valor $1 non é válido para o parámetro $2.',
	'validator_error_empty_argument' => 'O parámetro $1 non pode ter un valor baleiro.',
	'validator_error_required_missing' => 'Non se proporcionou o parámetro $1 necesario.',

	'validator_error_must_be_number' => 'O parámetro $1 só pode ser un número.',
	'validator_error_ivalid_range' => 'O parámetro $1 debe estar entre $2 e $3.',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'validator_error_parameters' => 'Die Fähler sin in Dyyre Syntax gfunde wore',

	'validator_error_invalid_argument' => 'Dr Wärt $1 isch nit giltig fir dr Parameter $2.',
	'validator_error_empty_argument' => 'Dr Parameter $1 cha kei lääre Wärt haa.',
	'validator_error_required_missing' => 'Dr Paramter $1, wu aagforderet woren isch, wird nit z Verfiegig gstellt.',

	'validator_error_must_be_number' => 'Dr Parameter $1 cha nume ne Zahl syy.',
	'validator_error_ivalid_range' => 'Dr Parameter $1 muess zwische $2 un $3 syy.',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'validator_error_parameters' => 'Slědowace zmylki buchu w twojej syntaksy wotkryli:',

	'validator_error_invalid_argument' => 'Hódnota $1 njeje płaćiwa za parameter $2.',
	'validator_error_empty_argument' => 'Parameter $1 njemóže prózdnu hódnotu měć.',
	'validator_error_required_missing' => 'Trěbny parameter $1 njeje podaty.',

	'validator_error_must_be_number' => 'Parameter $1 móže jenož ličba być.',
	'validator_error_ivalid_range' => 'Parameter $1 dyrbi mjez $2 a $3 być.',
);

/** Hungarian (Magyar)
 * @author Dani
 * @author Glanthor Reviol
 */
$messages['hu'] = array(
	'validator_error_parameters' => 'A következő hibák találhatóak a szintaxisodban',

	'validator_error_invalid_argument' => 'A(z) $1 érték nem érvényes a(z) $2 paraméterhez.',
	'validator_error_empty_argument' => 'A(z) $1 paraméter értéke nem lehet üres.',
	'validator_error_required_missing' => 'A(z) $1 kötelező paraméter nem lett megadva.',

	'validator_error_must_be_number' => 'A(z) $1 paraméter csak szám lehet.',
	'validator_error_ivalid_range' => 'A(z) $1 paraméter értékének $2 és $3 között kell lennie.',
);

/** Interlingua (Interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'validator_error_parameters' => 'Le sequente errores ha essite detegite in tu syntaxe',

	'validator_error_invalid_argument' => 'Le valor $1 non es valide pro le parametro $2.',
	'validator_error_empty_argument' => 'Le parametro $1 non pote haber un valor vacue.',
	'validator_error_required_missing' => 'Le parametro requisite $1 non ha essite fornite.',

	'validator_error_must_be_number' => 'Le parametro $1 pote solmente esser un numero.',
	'validator_error_ivalid_range' => 'Le parametro $1 debe esser inter $2 e $3.',
);

/** Indonesian (Bahasa Indonesia)
 * @author Bennylin
 * @author Irwangatot
 * @author IvanLanin
 */
$messages['id'] = array(
	'validator_error_parameters' => 'Kesalahan berikut telah dideteksi pada sintaksis Anda',

	'validator_error_invalid_argument' => 'Nilai $1 tidak valid untuk parameter $2.',
	'validator_error_empty_argument' => 'Parameter $1 tidak dapat bernilai kosong.',
	'validator_error_required_missing' => 'Parameter $1 yang diperlukan tidak diberikan.',

	'validator_error_must_be_number' => 'Parameter $1 hanya dapat berupa angka.',
	'validator_error_ivalid_range' => 'Parameter $1 harus antara $2 dan $3.',
);

/** Japanese (日本語)
 * @author Aotake
 * @author Fryed-peach
 */
$messages['ja'] = array(
	'validator_error_parameters' => 'あなたの入力から以下のエラーが検出されました',

	'validator_error_invalid_argument' => '値「$1」は引数「$2」として妥当ではありません。',
	'validator_error_empty_argument' => '引数「$1」は空の値をとることができません。',
	'validator_error_required_missing' => '必須の引数「$1」が入力されていません。',

	'validator_error_must_be_number' => '引数「$1」は数値でなければなりません。',
	'validator_error_ivalid_range' => '引数「$1」は $2 と $3 の間の値でなければなりません。',
);

/** Ripoarisch (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'validator_error_parameters' => 'Heh {{PLURAL:$1|dä|di|keine}} Fähler {{PLURAL:$1|es|sin|keine}} en Dinge Syntax opjevalle:',

	'validator_error_invalid_argument' => 'Däm Parameeter $2 singe Wäät es $1, dat es ävver doför nit jöltesch.',
	'validator_error_empty_argument' => 'Dä Parameeter $1 kann keine Wäät met nix dren hann.',
	'validator_error_required_missing' => 'Dä Parameeter $1 moß aanjejovve sin, un fählt.',

	'validator_error_must_be_number' => 'Dä Parameeter $1 kann blohß en Zahl sin.',
	'validator_error_ivalid_range' => 'Dä Parameeter $1 moß zwesche $2 un $3 sin.',
);


/** Macedonian (Македонски)
 * @author Bjankuloski06
 * @author McDutchie
 */
$messages['mk'] = array(
	'validator_error_parameters' => 'Откриени се следниве грешки во вашата синтакса',

	'validator_error_invalid_argument' => 'Вредноста $1 е неважечка за параметарот $2.',
	'validator_error_empty_argument' => 'Параметарот $1 не може да има празна вредност.',
	'validator_error_required_missing' => 'Бараниот параметар $1 не е наведен.',

	'validator_error_must_be_number' => 'Параметарот $1 може да биде само број.',
	'validator_error_ivalid_range' => 'Параметарот $1 мора да изнесува помеѓу $2 и $3.',
);

/** Dutch (Nederlands)
 * @author Siebrand
 * @author Jeroen De Dauw
 */
$messages['nl'] = array( 
	'validator_desc' => 'Validator geeft andere externsies de mogelijkheid om parameters van parser functions en tag extensions te valideren, in te stellen op hun standaard waarden en fout boodschappen te genereren.',

	'validator_error_parameters' => 'In uw syntaxis zijn de volgende fouten gedetecteerd',

	'validator_error_invalid_argument' => 'De waarde $1 is niet geldig voor de parameter $2.',
	'validator_error_empty_argument' => 'De parameter $1 mag niet leeg zijn.',
	'validator_error_required_missing' => 'De verplichte parameter $1 is niet opgegeven.',

	'validator_error_must_be_number' => 'De parameter $1 mag enkel een getal zijn.',
	'validator_error_ivalid_range' => 'De parameter $1 moet tussen $2 en $3 liggen.',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 * @author McDutchie
 */
$messages['pms'] = array(
	'validator_error_parameters' => "J'eror sì sota a son ëstàit trovà an toa sintassi",

	'validator_error_invalid_argument' => "Ël valor $1 a l'é pa bon për ël paràmetr $2.",
	'validator_error_empty_argument' => 'Ël paràmetr $1 a peul pa avèj un valor veuid.',
	'validator_error_required_missing' => "Ël paràmetr obligatòri $1 a l'é pa dàit.",

	'validator_error_must_be_number' => 'Ël paràmetr $1 a peul mach esse un nùmer.',
	'validator_error_ivalid_range' => 'Ël paràmetr $1 a deuv esse an tra $2 e $3.',
);

/** Portuguese (Português)
 * @author Hamilton Abreu
 */
$messages['pt'] = array(
	'validator_error_parameters' => 'Foram detectados os seguintes erros sintácticos',

	'validator_error_invalid_argument' => 'O valor $1 não é válido para o parâmetro $2.',
	'validator_error_empty_argument' => 'O parâmetro $1 não pode estar vazio.',
	'validator_error_required_missing' => 'O parâmetro obrigatório $1 não foi fornecido.',

	'validator_error_must_be_number' => 'O parâmetro $1 só pode ser numérico.',
	'validator_error_ivalid_range' => 'O parâmetro $1 tem de ser entre $2 e $3.',
);

/** Russian (Русский)
 * @author Lockal
 * @author McDutchie
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'validator_error_parameters' => 'Обнаружены следующие ошибки в синтаксисе',

	'validator_error_invalid_argument' => 'Значение $1 не является допустимым параметром $2',
	'validator_error_empty_argument' => 'Параметр $1 не может принимать пустое значение.',
	'validator_error_required_missing' => 'Не указан обязательный параметр $1.',

	'validator_error_must_be_number' => 'Значением параметра $1 могут быть только числа.',
	'validator_error_ivalid_range' => 'Параметр $1 должен быть от $2 до $3.',
);

/** Swedish (Svenska)
 * @author Fluff
 * @author Per
 */
$messages['sv'] = array(
	'validator_error_parameters' => 'Följande fel har upptäckts i din syntax',

	'validator_error_invalid_argument' => 'Värdet $1 är inte giltigt som parameter $2.',
	'validator_error_empty_argument' => 'Parametern $1 kan inte lämnas tom.',
	'validator_error_required_missing' => 'Den nödvändiga parametern $1 har inte angivits.',

	'validator_error_must_be_number' => 'Parameter $1 måste bestå av ett tal.',
	'validator_error_ivalid_range' => 'Parameter $1 måste vara i mellan $2 och $3.',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 * @author Vinhtantran
 */
$messages['vi'] = array(
	'validator_error_parameters' => 'Cú pháp có các lỗi sau',

	'validator_error_invalid_argument' => 'Giá trị “$1” không hợp tham số “$2”.',
	'validator_error_empty_argument' => 'Tham số “$1” không được để trống.',
	'validator_error_required_missing' => 'Không định rõ tham số bắt buộc “$1”.',

	'validator_error_must_be_number' => 'Tham số “$1” phải là con số.',
	'validator_error_ivalid_range' => 'Tham số “$1” phải nằm giữa $2 và $3.',
);