#include <stdio.h>
#include <string.h>
#include <GeoIP.h>

char countryCode[]="IN"; //india

main() {
	GeoIP * gi;
	char line[10240];
	char ipaddr[10240]; //in case the packet is malformed and the IP address is actually not here, cheaper than running a check every loop
	char *ipaddrstart, *ipaddrend;
	char *t = 0;

	gi = GeoIP_new(GEOIP_MMAP_CACHE|GEOIP_CHECK_CACHE);

	//while input	
	while (!feof(stdin)) {
		const char* localresult;
		char *r;
		r=fgets(line, 10000, stdin);

		int pos=0;
		t = line;

		//squid log 5th position, delimited by 4th space
		while(pos++<4) {
			if (!t)
				continue;
			t = strstr(t, " ");
			if (!t)
				continue;
			t++;
		}
		if (!t)
			continue;
		//buid IP address string
		ipaddrstart = t;
		ipaddrend = strstr(ipaddrstart, " ");
		strncpy(ipaddr, ipaddrstart, ipaddrend-ipaddrstart);
		ipaddr[ipaddrend-ipaddrstart]=0;
		
		//get entry for GeoIP, printif not null && matches
		localresult = GeoIP_country_code_by_addr(gi, ipaddr);
		if (	localresult && strstr(localresult, countryCode)
				){
			printf("%s", line);
		}
	}

}
