#include <stdio.h>
#include <string.h>

char editparam[]="action=submit";
char editattempt[]="action=edit";

main() {
	char line[10240];
	char code[10240];
	char *codestart, *codeend;
	char *t = 0;

	while (!feof(stdin)) {
		char *r;
		r=fgets(line, 10000, stdin);

		int pos=0;
		t = line;
		/* eliminate mobile :-) */
		if (r[0] == 'm')
			continue;

		while(pos++<5) {
			if (!t)
				continue;
			t = strstr(t, " ");
			if (!t)
				continue;
			t++;
		}
		if (!t)
			continue;
		codestart = t;
		codeend = strstr(codestart, " ");
		if (!codeend)
			continue;

		strncpy(code, codestart, codeend-codestart);
		code[codeend-codestart]=0;

		if (strstr(code, "/5")) {
			printf("%s", line);
			fflush(stdout);
		}
	}
}
