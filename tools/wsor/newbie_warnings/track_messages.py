import sys, MySQLdb, MySQLdb.cursors, argparse, os, logging, types, time
import wmf, re

#python track_messages.py -s db1047 -r "Notification: (proposed deletion|listing at \\[\\[WP:AFD\\|articles for deletion\\]\\]) of \\[\\[.+\\]\\]\\. \\(\\[\\[WP:TW\\|TW\\]\\]\\)"

def encode(v):
	if v == None: return "\N"
	
	if type(v) == types.LongType:     v = int(v)
	elif type(v) == types.UnicodeType: v = v.encode('utf-8')
	
	return str(v).encode("string-escape")

def decode(v):
	if v == "\\N": return None
	else: return v

def intOrNone(v):
	if v == None:
		return None
	else:
		return int(v)

def emit(event, p, time):
	print(
		"\t".join(encode(v) for v in [
			event,
			p['user_id'],
			p['user_name'],
			time
		])
	)
	sys.stdout.flush()
			

def main():
	parser = argparse.ArgumentParser(
		description=''
	)
	parser.add_argument(
		'-c', '--cnf',
		metavar="<path>",
		type=str, 
		help='the path to MySQL config info (defaults to ~/.my.cnf)',
		default=os.path.expanduser("~/.my.cnf")
	)
	parser.add_argument(
		'-s', '--host',
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
		'-i', '--input',
		type=lambda fn:open(fn, 'w'),
		help='Load a previous file',
		default=sys.stdin
	)
	parser.add_argument(
		'-o', '--out',
		type=lambda fn:open(fn, 'a+'), 
		help='Where should output be appended',
		default=sys.stdout
	)
	parser.add_argument(
		'-u', '--username',
		type=str, 
		help='The username to track posting for (useful for bots)'
	)
	parser.add_argument(
		'-r', '--comment',
		type=lambda v: re.compile(v).pattern, 
		help='Comment matching regular expression'
	)
	parser.add_argument(
		'-w', '--wait',
		type=float, 
		help='The amount of time to wait between updates (in seconds)',
		default=5
	)
	args = parser.parse_args()
	
	LOGGING_STREAM = sys.stderr
	logging.basicConfig(
		level=logging.INFO,
		stream=LOGGING_STREAM,
		format='%(asctime)s %(levelname)-8s %(message)s',
		datefmt='%b-%d %H:%M:%S'
	)
	
	logging.info("Connecting to %s:%s using %s." % (args.host, args.db, args.cnf))
	conn = MySQLdb.connect(
		host=args.host, 
		db=args.db, 
		read_default_file=args.cnf
	)
	
	mt = MessageTracker(conn, username=args.username, comment=args.comment)

	currUsers = set()
	oldPosts = {}
	if not args.input.isatty():
		logging.info("Loading old data.")
		for line in args.input:
			parts = line.strip().split("\t")
			#sys.stderr.write(str(parts) + "\n")
			if len(parts) == 4:
				userName = parts[2]
				oldPosts[userName] = {'user_name': userName, 'user_id': intOrNone(decode(parts[1])), 'posting': parts[3], 'messages': 1}
				lastTime = parts[3]
				LOGGING_STREAM.write(".")
			else:
				LOGGING_STREAM.write("|")
	
	if args.username != None:
		logging.info("Tracking posts made by %s." % args.username)
	if args.comment != None:
		logging.info("Tracking posts with comments that match %r." % args.comment)
	
	try:
		
		time.sleep(args.wait)
		while True:
			logging.info("Tracking %s posts.  Looking for new ones since %s." % (len(oldPosts), mt.lastTime))
			newUsers = mt.getUsersWithNewMessages()
			currUsers = set()
			for p in mt.getWaitingPosts(oldPosts.viewkeys() | newUsers):
				if p['user_name'] not in oldPosts:
					#Found a new posting
					LOGGING_STREAM.write(">")
					p['posting'] = mt.lastTime
					oldPosts[p['user_name']] = p
					emit("received", p, mt.lastTime)
				elif p['messages'] < oldPosts[p['user_name']]['messages']:
					#Looks like someone checked the message
					LOGGING_STREAM.write("<")
					emit("read", oldPosts[p['user_name']], mt.lastTime)
					del oldPosts[p['user_name']]
				else:
					#Same shit, different minute
					pass
				
				currUsers.add(p['user_name'])
			
			for missing in oldPosts.viewkeys() - currUsers:
				LOGGING_STREAM.write("<")
				emit("read", oldPosts[missing], mt.lastTime)
				del oldPosts[missing]
			
			LOGGING_STREAM.write("\n")
			time.sleep(args.wait)
		
	except KeyboardInterrupt:
		logging.info("Keyboard interrupt detected.  Shutting down.")
	except Exception as e:
		logging.error(str(e))
		raise e
		
	logging.info("An update was last made at %s" % mt.lastTime)
	
	
	
def safe(val):
	return '"' + val.replace('"', '\\"') + '"'

class MessageTracker:
	
	def __init__(self, conn, username=None, comment=None):
		self.conn = conn
		self.lastTime = self.getTime()
		self.username = username
		self.comment  = comment
		
	
	def getTime(self):
		cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
		cursor.execute(
			"""
			SELECT rc_timestamp AS time 
			FROM recentchanges 
			ORDER BY rc_timestamp DESC 
			LIMIT 1
			"""
		)
		self.conn.commit()
		for row in cursor:
			return row['time']
		
	
	def getUsersWithNewMessages(self):
		cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
		currTime = self.getTime()
		query = """
			SELECT DISTINCT p.page_title AS title
			FROM recentchanges rc
			INNER JOIN page p
				ON rc.rc_cur_id = p.page_id
			WHERE p.page_namespace = 3
			AND rc.rc_timestamp BETWEEN %(after_time)s AND %(before_time)s
		"""
		
		if self.username != None:
			query += "AND rc.rc_user_text = %(user_text)s\n"
		if self.comment != None:
			query += "AND rc.rc_comment RLIKE %(comment)s\n"
		
		#print(query)
		#print(self.lastTime)
		#print(currTime)
		#print(self.username)
		cursor.execute(
			query,
			{
				"after_time":  self.lastTime,
				"before_time": currTime,
				"user_text":   self.username,
				"comment":     self.comment
			}
		)
		logging.debug("Getting posts that occurred between %s and %s" % (self.lastTime, currTime))
		self.lastTime = currTime
		return set(p['title'].replace("_", " ") for p in cursor)
	
	def getWaitingPosts(self, users):
		cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
		userString = ",".join(safe(u) for u in users)
		if len(userString) != 0:
			cursor.execute("""
				SELECT
					u.user_id,
					u.user_name,
					count(*) as messages
				FROM user_newtalk nt
				LEFT JOIN user u
					ON u.user_id = nt.user_id
				WHERE u.user_name IN (""" + userString + """)
				GROUP BY u.user_id, u.user_name
				UNION
				SELECT 
					NULL as user_id,
					nt.user_ip as user_name,
					count(*) as messages
				FROM user_newtalk nt
				WHERE nt.user_ip IN (""" + userString + """)
				GROUP BY nt.user_ip, NULL
				"""
			)
			for post in cursor:
				yield post
	
if __name__ == "__main__": main()
