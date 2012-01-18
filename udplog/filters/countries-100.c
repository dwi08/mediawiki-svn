#include <stdio.h>
#include <string.h>
#include <GeoIP.h>
#include <GeoIPCity.h>

char country1[]="BD";
char country2[]="LK"; 
char country3[]="BH"; 
char country4[]="IQ";
char country5[]="JO"; 
char country6[]="KE";
char country7[]="KW";
char country8[]="NG";
char country9[]="QA";
char country10[]="ZA";
char country11[]="SN";
char country12[]="TN";
char country13[]="UG";

main() {
	GeoIP * gi;
	GeoIPRecord *gir;
	char line[10240];
	char ipaddr[10240]; //in case the packet is malformed and the IP address is actually not here, cheaper than running a check every loop
	char *ipaddrstart, *ipaddrend;
	char *t = 0;

	gi = GeoIP_open("/var/log/squid/filters/GeoIPLibs/GeoIPCity.current.dat", GEOIP_INDEX_CACHE);

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
		gir = GeoIP_record_by_name(gi, ipaddr);
		localresult = gir->country_code;
		if (	gir && 
				(strstr(localresult, country1)
				|| strstr(localresult, country2)
				|| strstr(localresult, country3)
				|| strstr(localresult, country4)
				|| strstr(localresult, country5)
				|| strstr(localresult, country6)
				|| strstr(localresult, country7)
				|| strstr(localresult, country8)
				|| strstr(localresult, country9)
				|| strstr(localresult, country10)
				|| strstr(localresult, country11)
				|| strstr(localresult, country12)
				|| strstr(localresult, country13)				
				 )
				 ){
			printf("%s %s", localresult, line);
			GeoIPRecord_delete(gir);
		}
	}

}
