test:
	node launch.js

debug:
	node debug launch.js

test-old:
	node_modules/.bin/qunit \
	--deps  ./data \
	--code  ../../resources/mediawiki/mediawiki.js \
	--tests ./suites/resources/mediawiki/mediawiki.js \
		./suites/resources/mediawiki/mediawiki.user.js \
	--cov false
