
#include <stdio.h>
#include <string.h>

char * urls[] = {
    "wikimediafoundation.org/",
    "donate.wikimedia.org/",
};

main() {
    	char line[65534];
	char *t = 0;
        
        int i = 0;  
        
        //calculate these up-front.
        int filtercount = sizeof(urls)/sizeof(*urls);
        int url_length[filtercount];
        for (i=0; i<filtercount; ++i){
            url_length[i] = strlen(urls[i]);
        }

	while (!feof(stdin)) {
		char *r;
		r=fgets(line, 65534, stdin);

		int pos=0;
		t = line;

		while(pos++<7) {
			if (!t)
				continue;
			t = strstr(t, " ");
			if (!t)
				continue;
			t++;
		}
		if (!t)
			continue;
                
                t = strstr(t, "://");
                if (!t)
                        continue;
                t += 3;
                
                int found = 0;
                for (i = 0; i < filtercount; ++i) {
                   if (strncmp(t, urls[i], url_length[i]) == 0) {
                       found = 1;
                       break;
                   }
                }
                if (found) {
                    printf("%s", line);
                }

	}

}
