test:
	# --use-legacy for issue https://github.com/joyent/node/issues/1771
	# Try again once issue is solved
	node --use-legacy launch.js

dumb:
	node --use-legacy dumb-launch.js

test-old:
	node_modules/.bin/qunit \
	--deps  ./data \
	--code  ../../resources/mediawiki/mediawiki.js \
	--tests ./suites/resources/mediawiki/mediawiki.js \
		./suites/resources/mediawiki/mediawiki.user.js \
	--cov false
