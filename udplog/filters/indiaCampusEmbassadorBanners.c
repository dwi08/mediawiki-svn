#include <stdio.h>
#include <string.h>

char url[]= "title=Special:BannerLoader&banner=AmbassadorsIndiaanon";
char url2[] = "title=Special:BannerLoader&banner=AmbassadorsIndiaLoggedIn";

main() {
	char line[10240];
	char title[10240];
	char *urlstart, *urlend;
	char *t = 0;

	while (!feof(stdin)) {
		char *r;
		r=fgets(line, 10000, stdin);

		int pos=0;
		t = line;

		while(pos++<8) {
			if (!t)
				continue;
			t = strstr(t, " ");
			if (!t)
				continue;
			t++;
		}
		if (!t)
			continue;
		urlstart = t;
		urlend = strstr(urlstart, " ");
		strncpy(title, urlstart, urlend-urlstart);
		title[urlend-urlstart]=0;

		if (		strstr(title, url)  || strstr(title,url2)
				){
			printf("%s", line);
		}
	}

}
