/**
 * Trasliteration regular expression rules table for Punjabi
 * @date 2011-11-19
 * Based on http://fedoraproject.org/wiki/I18N/Indic/PunjabiKeyboardLayouts
 */

 // Normal rules
var rules = [
 
  ['1', '','੧'],
  ['2', '','੨'],
  ['3', '','੩'],
  ['4', '','੪'],
  ['5', '','੫'],
  ['6', '','੬'],
  ['7', '','੭'],
  ['8', '','੮'],
  ['9', '','੯'],
  ['0', '','੦'],
  ['\\_', '','_'],
  ['\\-', '','-'],
  ['\\+', '','+'],
  ['\\=', '','='],
  ['Q', '','ਔ'],
  ['q', '','ੌ'],
  ['W', '','ਐ'],
  ['w', '','ੈ'],
  ['E', '','ਆ'],
  ['e', '','ਾ'],
  ['R', '','ਈ'],
  ['r', '','ੀ'],
  ['T', '','ਊ'],
  ['t', '','ੂ'],
  ['Y', '','ਭ'],
  ['y', '','ਬ'],
  ['U', '','ਙ'],
  ['u', '','ਹ'],
  ['I', '','ਘ'],
  ['i', '','ਗ'],
  ['O', '','ਧ'],
  ['o', '','ਦ'],
  ['P', '','ਝ'],
  ['p', '','ਜ'],
  ['\\{', '','ਢ'],
  ['\\[', '','ਡ'],
  ['\\}', '','ਞ'],
  ['\\]', '','਼'],
  ['A', '','ਓ'],
  ['a', '','ੋ'],
  ['S', '','ਏ'],
  ['s', '','ੇ'],
  ['D', '','ਅ'],
  ['d', '','੍'],
  ['F', '','ਇ'],
  ['f', '','ਿ'],
  ['G', '','ਉ'],
  ['g', '','ੁ'],
  ['H', '','ਫ'],
  ['h', '','ਪ'],
  ['J', '','ੜ'],
  ['j', '','ਰ'],
  ['K', '','ਖ'],
  ['k', '','ਕ'],
  ['L', '','ਥ'],
  ['l', '','ਤ'],
  ['\\:', '','ਛ'],
  ['\\;', '','ਚ'],
  ['\\"', '','ਠ'],
  ["'", '','ਟ'],
  ['Z', '','ੱ'],
  ['z', '','ੰ'],
  ['X', '','ਫ਼'],
  ['x', '','ਜ਼'],
  ['C', '','ਣ'],
  ['c', '','ਮ'],
  ['V', '','ਂ'],
  ['v', '','ਨ'],
  ['B', '','ਞ'],
  ['b', '','ਵ'],
  ['N', '','ਲ਼'],
  ['n', '','ਲ'],
  ['M', '','ਸ਼'],
  ['m', '','ਸ'],
  ['\\<', '','ੳ'],
  [',', '',','],
  ['\\>', '','ੲ'],
  ['\\.', '','.'],
  ['\\?', '','?'],
  ['/', '','ਯ']
];

jQuery.narayam.addScheme( 'pa-inscript', {
	'namemsg': 'narayam-pa-inscript',
	'extended_keyboard': true,
	'lookbackLength': 0,
	'keyBufferLength': 0,
	'rules': rules
} );
