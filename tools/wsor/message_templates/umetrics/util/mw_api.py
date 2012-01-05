import urllib2, urllib, json
import time
from cookielib import CookieJar

class MWAPIError(Exception):
	def __init__(self, code, message):
		self.code = code
		self.info = info
	
	def __repr__(self):
		return "%s(%s)" % (
			self.__class__.__name__,
			", ".join(
				repr(self.code),
				repr(self.info)
			)
		)
	
	def __str__(self):
		return "%s: %s" % (self.code, self.info)

class MWAPI:
	
	def __init__(self, uri):
		self.uri = uri
		self.cookies = CookieJar()
	
	def request(self, retry=0, **kwargs):
		kwargs['format'] = "json"
		
		request = urllib2.Request(
				self.uri,
				urllib.urlencode(kwargs)
		)
		self.cookies.add_cookie_header(request)
		
		try:
			response = urllib2.urlopen(request)
			
			self.cookies.extract_cookies(response, request)
			
			js = json.load(response)
			
			if 'error' in js:
				raise MWAPIError(js['error']['code'], js['error']['info'])
			else:
				return js
			
		except urllib2.HTTPError:
			#wait and try again
			time.sleep(2**retry)
			self.request(retry=retry+1, **kwargs)
				
