import sys, argparse, os
import logging, types, re
import time, datetime
import MySQLdb, MySQLdb.cursors
import urllib, urllib2, json, htmlentitydefs
import wmf

class MissingRevError(Exception):pass

def encode(v):
	if v == None: return "\N"
	
	if type(v) == types.LongType:     v = int(v)
	elif type(v) == types.UnicodeType: v = v.encode('utf-8')
	
	return str(v).encode("string-escape")

def emit(rev):
	
	print(
		"\t".join(
			encode(rev[c]) for c in [
				'rev_id',
				'rev_timestamp',
				'poster_id',
				'poster_name',
				'recipient_name',
				'message_match'
			]
		)
	)


#  MediaWiki Date format
#
#                      |  year |   month |     day |    hour |  minute |  second |
MW_DATE = re.compile(r"^[0-9]{4}[0-1][0-9][0-3][0-9][0-2][0-9][0-5][0-9][0-5][0-9]$")
def mwDate(string):
	if MW_DATE.match(string) == None:
		raise ValueError("%r is not a valid date.  Expected YYMMDDHHmmSS" % string)
	else:
		return string

def main():
	parser = argparse.ArgumentParser(
		description="""
		Gathers experimental message postings from user_talk messages.
		""",
		epilog="""
		python message_postings.py 
		-h db42 
		--start=20111222000000 
		--end=20111223000000 
		--comment="\(\[\[WP:HG\|HG\]\]\)" 
		--message="Template:uw-vandalism1"
		""",
		conflict_handler="resolve"
	)
	parser.add_argument(
		'-c', '--cnf',
		metavar="<path>",
		type=str, 
		help='the path to MySQL config info (defaults to ~/.my.cnf)',
		default=os.path.expanduser("~/.my.cnf")
	)
	parser.add_argument(
		'-h', '--host',
		type=str, 
		help='the database host to connect to (defaults to localhost)',
		default="localhost"
	)
	parser.add_argument(
		'-d', '--db',
		type=str, 
		help='the language db to run the query in (defaults to enwiki)',
		default="enwiki"
	)
	parser.add_argument(
		'-a', '--api_uri',
		type=str, 
		help='the mediawiki API to connect to in order to retrieve message content (defaults to http://en.wikipedia.org/w/api.php)',
		default="http://en.wikipedia.org/w/api.php"
	)
	parser.add_argument(
		'--start',
		type=mwDate,
		help='the start of the experimental period. (Required)',
		required=True
	)
	parser.add_argument(
		'--end',
		type=mwDate, 
		help='the end of the experimental period.  (defaults to NOW())',
		default=datetime.datetime.utcnow().strftime("%Y%m%d%H%M%S")
	)
	parser.add_argument(
		'--user_name',
		type=str, 
		help='the user_name to further filter postings by (useful for tracking bots)'
	)
	parser.add_argument(
		'--comment',
		type=re.compile,
		help='regular expression to match against message posting comment'
	)
	parser.add_argument(
		'--message',
		type=re.compile,
		help='regular expression to match against message content (required)',
		required=True
	)
	args = parser.parse_args()
	
	LOGGING_STREAM = sys.stderr
	logging.basicConfig(
		level=logging.DEBUG,
		stream=LOGGING_STREAM,
		format='%(asctime)s %(levelname)-8s %(message)s',
		datefmt='%b-%d %H:%M:%S'
	)
	logging.debug("Comment pattern is %r." % args.comment.pattern)
	logging.debug("Message pattern is %r." % args.message.pattern)
	
	logging.info("Connecting to %s:%s using %s." % (args.host, args.db, args.cnf))
	db = Database(
		host=args.host, 
		db=args.db, 
		read_default_file=args.cnf
	)
	
	logging.info("Connecting to API @ %s." % args.api_uri)
	api = WPAPI(args.api_uri)
	
	logging.info("Querying for matching revisions:")
	count = {"matched": 0, "missed": 0}
	for rev in db.getPostings(args.start, args.end, args.user_name, args.comment):
		message = api.getAdded(rev['rev_id'])
		match = args.message.search(message)
		if match != None:
			rev['message_match'] = match.group(0)
			
			emit(rev)
			LOGGING_STREAM.write("|")
			count['matched'] += 1
		else:
			LOGGING_STREAM.write("o")
			count['missed'] += 1
		
	LOGGING_STREAM.write("\n")
	logging.info("Process completed. %(matched)s messages matched, %(missed)s messages missed." % count)



class Database:
	
	def __init__(self, *args, **kwargs):
		self.args   = args
		self.kwargs = kwargs
		self.conn   = MySQLdb.connect(*args, **kwargs)
	
	def getPostings(self, start, end, userName=None, commentRE=None):
		if (userName, commentRE) == (None, None):
			raise TypeError("Must specify at at least one of userName or commentRE.")
		
		cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
		query = """
			SELECT 
				r.rev_id,
				r.rev_timestamp,
				r.rev_comment,
				r.rev_user                      AS poster_id,
				r.rev_user_text                 AS poster_name,
				REPLACE(p.page_title, "_", " ") AS recipient_name
			FROM revision r
			INNER JOIN page p ON r.rev_page = p.page_id
			WHERE rev_timestamp BETWEEN %(start)s AND %(end)s
			AND page_namespace = 3
			"""
		if userName != None:
			query += "AND rev_user_text = %(user_name)s\n"
		if commentRE != None:
			query += "AND rev_comment REGEXP %(comment_pattern)s\n"
		
		cursor.execute(
			query,
			{
				'start': start,
				'end': end,
				'user_name': userName,
				'comment_pattern': commentRE.pattern
			}
		)
		
		for row in cursor:
			yield row
		
	

class WPAPI:
	DIFF_ADD_RE = re.compile(r'<td class="diff-addedline"><div>(.+)</div></td>')
	
	def __init__(self, uri):
		self.uri = uri
	
	def getDiff(self, revId, retries=10):
		attempt = 0
		while attempt < retries:
			try:
				response = urllib2.urlopen(
					self.uri,
					urllib.urlencode({
						'action': 'query',
						'prop': 'revisions',
						'revids': revId,
						'rvprop': 'ids',
						'rvdiffto': 'prev',
						'format': 'json'
					})
				)
				result = json.load(response)
				return result['query']['pages'].values()[0]['revisions'][0]['diff']['*']
			except urllib2.HTTPError as e:
				time.sleep(attempt*2)
				attempt += 1
				
			
	
	def getAdded(self, revId):
		diff = self.getDiff(revId)
		
		return self.unescape(
				"\n".join(
				match.group(1) 
				for match in WPAPI.DIFF_ADD_RE.finditer(diff)
			)
		)
		
	def unescape(self, text):
		def fixup(m):
			text = m.group(0)
			if text[:2] == "&#":
				# character reference
				try:
					if text[:3] == "&#x":
						return unichr(int(text[3:-1], 16))
					else:
						return unichr(int(text[2:-1]))
				except ValueError:
					pass
			else:
				# named entity
				try:
					text = unichr(htmlentitydefs.name2codepoint[text[1:-1]])
				except KeyError:
					pass
			return text # leave as is
		return re.sub("&#?\w+;", fixup, text)
	
	
if __name__ == "__main__": 
	main()
