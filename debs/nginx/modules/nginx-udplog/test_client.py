##
# See the README for usage
##

import argparse
import urllib2
import random
import time

USER_AGENTS = (
    'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6',
    'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
    'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)',
    'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)',
    'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT 5.1; .NET CLR 1.1.4322)',
    'Opera/9.20 (Windows NT 6.0; U; en)',
    'Opera/9.00 (Windows NT 5.1; U; en)',
    'Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.1) Opera 7.02 [en]',
    'Googlebot-Image/1.0 ( http://www.googlebot.com/bot.html)',
    'msnbot-Products/1.0 (+http://search.msn.com/msnbot.htm)',

    # really long user agent
    'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)'*10,
)

def rand_ustring(n):
    "Create n-char string of unicode"
    return u"".join(unichr(random.randint(0x66d, 0x1000)) for i in xrange(n))

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("url", 
                        type=str, 
                        help="URL of the nginx web server to send requests to")
    parser.add_argument("-n",
                        type=int,
                        help="Number of random user agent strings to send (default=10)",
                        default=10)
    parser.add_argument("-w",
                        type=float,
                        help="Seconds to wait between requests (default=0.25)",
                        default=0.25)
    parser.add_argument("-l",
                        type=int,
                        help="Length of garbage user agent strings (default=50)",
                        default=50)
    args = parser.parse_args()

    # Send good user agents
    for user_agent in USER_AGENTS:
        req = urllib2.Request(args.url, headers={
            "User-Agent": user_agent 
        })
        urllib2.urlopen(req)
        time.sleep(args.w)

    # Send random unicode user agents
    for i in xrange(args.n):
        garbage = rand_ustring(args.l)
        req = urllib2.Request(args.url, headers={
            "User-Agent": garbage.encode('utf-8')
        })
        urllib2.urlopen(req)
        time.sleep(args.w)
