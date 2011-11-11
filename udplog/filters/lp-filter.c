
#include <stdio.h>
#include <string.h>

char url1[] ="wikimediafoundation.org/";
char url2[] ="donate.wikimedia.org/";


main() {
    	char line[65534];
	char title[65534];
	char *t = 0;
        char *u = 0;
        
        //to cut down on the processing time: Guess first. 
        //longest filter is 25 characters. 
        //I'm allowing for one heck of a subdomain, here. 
        int search_length = 75;

	while (!feof(stdin)) {
		char *r;
		r=fgets(line, 65534, stdin);

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

		strncpy(title, t, search_length);
		title[search_length]=0;
                    
		if (strstr(title, url1) || strstr(title, url2) ){
                    u = strstr(title, " ");
                    
                    if (!u){ //no spaces, just do it. 
                        printf("%s", line);
                    } else {
                        //make sure it was before the first space.
                        t = strstr(title, url1);
                        if (!t)
                            t = strstr(title, url2);
                        if ( (t) && t < u ) {
                            printf("%s", line);
                        }
                    }
                }

	}

}
