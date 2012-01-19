#include <stdio.h>
#include <string.h>

char bp1[] = "bookcmd=";
char bp2[] = "wfAjaxCollection";
char bp3[] = "Special:Book";
char bp4[] = "Spezial:Buch";
char bp5[] = "Especial:Libro";
char bp6[] = "Sp%C3%A9cial:Livre";
char bp7[] = "Especial:Livro";
char bp8[] = "Speciale:Libro";
char bp9[] = "Special:Bok";
char bp10[] = "Speciaal:Boek";


/*
char bp3[] = "Special%3ABook";
char bp4[] = "Spezial%3ABuch";
char bp5[] = "Especial%3ALibro";
char bp6[] = "Sp%C3%A9cial%3ALivre";
char bp7[] = "Especial%3ALivro";
char bp8[] = "Speciale%3ALibro";
char bp9[] = "Special%3ABok";
char bp10[] = "Speciaal%3ABoek";
*/

char bparams[]="?title=Special:BannerLoader";


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
		if (!urlend)
			continue;

		strncpy(title, urlstart, urlend-urlstart);
		title[urlend-urlstart]=0;
		if (		strstr(title, bp1) ||  
				strstr(title, bp2) || 
				strstr(title, bp3) ||
				strstr(title, bp4) ||
				strstr(title, bp5) ||
				strstr(title, bp6) ||
				strstr(title, bp7) ||
				strstr(title, bp8) ||
				strstr(title, bp9) ||
				strstr(title, bp10) 
				){
			printf("%s", line);
		}
	}

}
