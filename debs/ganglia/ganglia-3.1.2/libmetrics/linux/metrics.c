#include <stdio.h>
#include <stdlib.h>
#ifndef __USE_GNU
#define __USE_GNU
#endif
#include <string.h>
#include <time.h>
#include <unistd.h>
#include <ctype.h>
#include <sys/sysinfo.h>
#include <sys/stat.h>
#include <sys/statvfs.h>
#include <sys/time.h>

/* From old ganglia 2.5.x... */
#include "file.h"
#include "interface.h"
#include "libmetrics.h"
/* End old ganglia 2.5.x headers */

#define OSNAME "Linux"
#define OSNAME_LEN strlen(OSNAME)

/* /proc/net/dev hash table stuff */
typedef struct net_dev_stats net_dev_stats;
struct net_dev_stats {
  char *name;
  unsigned long rpi;
  unsigned long rpo;
  unsigned long rbi;
  unsigned long rbo;
  net_dev_stats *next;
};
#define NHASH 101
#define MULTIPLIER 31
static net_dev_stats *netstats[NHASH];

/* Never changes */
#ifndef BUFFSIZE
#define BUFFSIZE 16384
#endif
char proc_cpuinfo[BUFFSIZE];
char proc_sys_kernel_osrelease[BUFFSIZE];

#define SCALING_MAX_FREQ "/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"
char sys_devices_system_cpu[32];
int cpufreq;

typedef struct {
  struct timeval last_read;
  float thresh;
  char *name;
  char buffer[BUFFSIZE];
} timely_file;

timely_file proc_stat    = { {0,0} , 1., "/proc/stat" };
timely_file proc_loadavg = { {0,0} , 5., "/proc/loadavg" };
timely_file proc_meminfo = { {0,0} , 5., "/proc/meminfo" };
timely_file proc_net_dev = { {0,0} , 1., "/proc/net/dev" };

float timediff(const struct timeval *thistime, const struct timeval *lasttime)
{
  float diff;

  diff = ((double) thistime->tv_sec * 1.0e6 +
          (double) thistime->tv_usec -
          (double) lasttime->tv_sec * 1.0e6 -
          (double) lasttime->tv_usec) / 1.0e6;

  return diff;
}

char *update_file(timely_file *tf)
{
  int rval;
  struct timeval now;
  gettimeofday(&now, NULL);
  if(timediff(&now,&tf->last_read) > tf->thresh) {
    rval = slurpfile(tf->name, tf->buffer, BUFFSIZE);
    if(rval == SYNAPSE_FAILURE) {
      err_msg("update_file() got an error from slurpfile() reading %s",
              tf->name);
      return (char *)SYNAPSE_FAILURE;
    }
    else tf->last_read = now;
  }
  return tf->buffer;
}

/*
** A helper function to determine the number of cpustates in /proc/stat (MKN)
*/
#define NUM_CPUSTATES_24X 4
#define NUM_CPUSTATES_26X 7
static unsigned int num_cpustates;

unsigned int
num_cpustates_func ( void )
{
   char *p;
   unsigned int i=0;

   proc_stat.last_read.tv_sec=0;
   proc_stat.last_read.tv_usec=0;
   p = update_file(&proc_stat);
   proc_stat.last_read.tv_sec=0;
   proc_stat.last_read.tv_usec=0;

/*
** Skip initial "cpu" token
*/
   p = skip_token(p);
   p = skip_whitespace(p);
/*
** Loop over file until next "cpu" token is found.
** i=4 : Linux 2.4.x
** i=7 : Linux 2.6.x
*/
   while (strncmp(p,"cpu",3)) {
     p = skip_token(p);
     p = skip_whitespace(p);
     i++;
     }

   return i;
}

/*
** Helper functions to hash /proc/net/dev stats (Kernighan & Pike)
*/
static unsigned int hashval(const char *s)
{
  unsigned int hval;
  unsigned char *p;

  hval = 0;
  for (p = (unsigned char *)s; *p != '\0'; p++)
    hval = MULTIPLIER * hval + *p;
  return hval % NHASH;
}

static net_dev_stats *hash_lookup(char *devname, size_t nlen) 
{
  int hval;
  net_dev_stats *stats;
  char *name=strndup(devname,nlen);

  hval = hashval(name);
  for (stats = netstats[hval]; stats != NULL; stats = stats->next)
  {
    if (strcmp(name, stats->name) == 0) {
      free(name);
      return stats;
    }
  }

  stats = (net_dev_stats *)malloc(sizeof(net_dev_stats));
  if ( stats == NULL )
  {   
    err_msg("unable to allocate memory for /proc/net/dev/stats in hash_lookup(%s,%d)",name,nlen);
    free(name);
    return NULL;
  }
  stats->name = strndup(devname,nlen);
  stats->rpi = 0;
  stats->rpo = 0;
  stats->rbi = 0;
  stats->rbo = 0;
  stats->next = netstats[hval];
  netstats[hval] = stats;

  free(name);
  return stats;
}

/*
** FIXME: this routine should be rewritten to do per-interface statistics
*/
static double bytes_in=0,bytes_out=0,pkts_in=0,pkts_out=0;
void update_ifdata ( char *caller )
{
   char *p;
   int i;
   static struct timeval stamp={0,0};
   unsigned long rbi=0,rbo=0,rpi=0,rpo=0;
   unsigned long l_bytes_in=0,l_bytes_out=0,l_pkts_in=0,l_pkts_out=0;
   double l_bin,l_bout,l_pin,l_pout;

   p = update_file(&proc_net_dev);
   if ((proc_net_dev.last_read.tv_sec != stamp.tv_sec) &&
       (proc_net_dev.last_read.tv_usec != stamp.tv_usec))
      {
	 /*  skip past the two-line header ... */
	 p = index (p, '\n')+1;
	 p = index (p, '\n')+1;

	 while (*p != 0x00 )
	    {
	       /*  skip past the interface tag portion of this line */
	       /*  but save the name of the interface (hash key) */
	       char *src;
	       size_t n = 0;

	       while (p != 0x00 && isblank(*p))
		  p++;
	       src = p;
	       while (p != 0x00 && *p != ':')
		  {
		     n++;
		     p++;
		  }

	       p = index(p, ':');

	       /* Ignore 'lo' and 'bond*' interfaces (but sanely) */
	       if (p && strncmp (src, "lo", 2) &&
		   strncmp (src, "bond", 4))
		  { 
		     p++;
		     /* Check for data from the last read for this */
		     /* interface.  If nothing exists, add to the table. */
		     net_dev_stats *ns = hash_lookup(src, n); 
		     if ( !ns ) return;

		     /* receive */
		     rbi = strtoul( p, &p ,10);
		     if ( rbi >= ns->rbi ) {
			l_bytes_in += rbi - ns->rbi;
		     } else {
			debug_msg("update_ifdata(%s) - Overflow in rbi: %lu -> %lu",caller,ns->rbi,rbi);
			l_bytes_in += ULONG_MAX - ns->rbi + rbi;
		     }
		     ns->rbi = rbi;

		     rpi = strtoul( p, &p ,10);
		     if ( rpi >= ns->rpi ) {
			l_pkts_in += rpi - ns->rpi;
		     } else {
			debug_msg("updata_ifdata(%s) - Overflow in rpi: %lu -> %lu",caller,ns->rpi,rpi);
			l_pkts_in += ULONG_MAX - ns->rpi + rpi;
		     }
		     ns->rpi = rpi;
		     
		     /* skip unneeded metrics */
		     for (i = 0; i < 6; i++) rbo = strtoul(p, &p, 10);

		     /* transmit */
		     rbo = strtoul( p, &p ,10);
		     if ( rbo >= ns->rbo ) {
			l_bytes_out += rbo - ns->rbo;
		     } else {
			debug_msg("update_ifdata(%s) - Overflow in rbo: %lu -> %lu",caller,ns->rbo,rbo);
			l_bytes_out += ULONG_MAX - ns->rbo + rbo;
		     }
		     ns->rbo = rbo;

		     rpo = strtoul( p, &p ,10);
		     if ( rpo >= ns->rpo ) {
			l_pkts_out += rpo - ns->rpo;
		     } else {
			debug_msg("update_ifdata(%s) - Overflow in rpo: %lu -> %lu",caller,ns->rpo,rpo);
			l_pkts_out += ULONG_MAX - ns->rpo + rpo;
		     }
		     ns->rpo = rpo;
		  }
	       p = index (p, '\n') + 1;    // skips a line
	    }

	 /*
	 ** Compute timediff. Check for bogus delta-t
	 */
	 float t = timediff(&proc_net_dev.last_read,&stamp);
	 if ( t <  proc_net_dev.thresh) {
	   err_msg("update_ifdata(%s) - Dubious delta-t: %f",caller,t);
           return;
	 }
	 stamp = proc_net_dev.last_read;

	 /*
	 ** Compute rates in local variables
	 */
	 l_bin = l_bytes_in / t;
	 l_bout = l_bytes_out / t;
	 l_pin = l_pkts_in / t;
	 l_pout = l_pkts_out / t;

#ifdef REMOVE_BOGUS_SPIKES
	 /*
	 ** Check for "invalid" data, caused by HW error. Throw away dubious data points
	 ** FIXME: This should be done per-interface, with threshholds depending on actual link speed
	 */	
	 if ((l_bin > 1.0e13) || (l_bout > 1.0e13) ||
             (l_pin > 1.0e8)  || (l_pout > 1.0e8)) {
		err_msg("update_ifdata(%s): %g %g %g %g / %g",caller,l_bin,l_bout,l_pin,l_pout,t);
		return;
	}
#endif

        /*
        ** Finally return Values
        */
        bytes_in  = l_bin;
        bytes_out = l_bout;
        pkts_in   = l_pin;
        pkts_out  = l_pout;
      }

   return;
}

/*
 * This function is called only once by the gmond.  Use to 
 * initialize data structures, etc or just return SYNAPSE_SUCCESS;
 */
g_val_t
metric_init(void)
{
   g_val_t rval;
   char * dummy;
   struct stat struct_stat;

   num_cpustates = num_cpustates_func();

   /* scaling_max_freq will contain the max CPU speed if available */
   cpufreq = 0;
   if ( stat(SCALING_MAX_FREQ, &struct_stat) == 0 ) {
      cpufreq = 1;
      slurpfile(SCALING_MAX_FREQ, sys_devices_system_cpu, 32);
   }

   rval.int32 = slurpfile("/proc/cpuinfo", proc_cpuinfo, BUFFSIZE);
   if ( rval.int32 == SYNAPSE_FAILURE )
      {
         err_msg("metric_init() got an error from slurpfile() /proc/cpuinfo");
         return rval;
      }  

   rval.int32 = slurpfile( "/proc/sys/kernel/osrelease", 
                       proc_sys_kernel_osrelease, BUFFSIZE);
   if ( rval.int32 == SYNAPSE_FAILURE )
      {
         err_msg("metric_init() got an error from slurpfile()");
         return rval;
      }  
   
   /* Get rid of pesky \n in osrelease */
   proc_sys_kernel_osrelease[rval.int32-1] = '\0';

   dummy = update_file(&proc_net_dev);
   if ( dummy == (char *)SYNAPSE_FAILURE )
      {
         err_msg("metric_init() got an error from update_file()");
         rval.int32 = SYNAPSE_FAILURE;
         return rval;
      } 

   update_ifdata("metric_inint");

   rval.int32 = SYNAPSE_SUCCESS;
   return rval;
}

g_val_t
pkts_in_func ( void )
{
   g_val_t val;

   update_ifdata("PI");
   val.f = pkts_in;
   debug_msg(" ********** pkts_in:  %f", pkts_in);
   return val;
}

g_val_t
pkts_out_func ( void )
{
   g_val_t val;

   update_ifdata("PO");
   val.f = pkts_out;
   debug_msg(" ********** pkts_out:  %f", pkts_out);
   return val;
}

g_val_t
bytes_out_func ( void )
{
   g_val_t val;

   update_ifdata("BO");
   val.f = bytes_out;
   debug_msg(" ********** bytes_out:  %f", bytes_out);
   return val;
}

g_val_t
bytes_in_func ( void )
{
   g_val_t val;

   update_ifdata("BI");
   val.f = bytes_in;
   debug_msg(" ********** bytes_in:  %f", bytes_in);
   return val;
}

g_val_t
cpu_num_func ( void )
{
   static int cpu_num = 0;
   g_val_t val;

   /* Only need to do this once */
   if (! cpu_num) {
      /* We'll use _SC_NPROCESSORS_ONLN to get operating cpus */
      cpu_num = get_nprocs();
   }
   val.uint16 = cpu_num;

   return val;
}

g_val_t
cpu_speed_func ( void )
{
   char *p;
   static g_val_t val = {0};

   /* we'll use scaling_max_freq before we fallback on proc_cpuinfo */
   if(cpufreq && ! val.uint32)
      {
         p = sys_devices_system_cpu;
         val.uint32 = (uint32_t)(strtol( p, (char **)NULL , 10 ) / 1000 );
      }

/* i386, ia64, x86_64 and hppa all report MHz in the same format */
#if defined (__i386__) || defined(__ia64__) || defined(__hppa__) || defined(__x86_64__)
   if (! val.uint32 )
      {
         p = proc_cpuinfo;  
         p = strstr( p, "cpu MHz" );
         if(p) {
           p = strchr( p, ':' );
           p++;
           p = skip_whitespace(p);
           val.uint32 = (uint32_t)strtol( p, (char **)NULL , 10 );
         } else {
           val.uint32 = 0;
         }
      }
#endif
#if defined (__alpha__)
   if (! val.uint32 ) {
         int num;
         p = proc_cpuinfo;
         p = strstr( p, "cycle frequency [Hz]" );
         if(p) {
           p = strchr( p, ':' );
           p++;
           p = skip_whitespace(p);
           sscanf(p, "%d", &num);
           num = num / 1000000;  /* Convert to Mhz */
           val.uint32 = (uint32_t)num;
         } else {
           val.uint32 = 0;
         }
      }
#endif
#if defined (__powerpc__)
   if (! val.uint32 )
      {
         p = proc_cpuinfo;
         p = strstr( p, "clock" );
         if(p) { 
           p = strchr( p, ':' );
           p++;
           p = skip_whitespace(p);
           val.uint32 = (uint32_t)strtol( p, (char **)NULL , 10 );
        } else {
           val.uint32 = 0;
        }
      }
#endif
   return val;
}

/*
** FIXME: all functions using /proc/meminfo sould use a central routine like networking
*/
g_val_t
mem_total_func ( void )
{
   char *p;
   g_val_t val;

   p = strstr( update_file(&proc_meminfo), "MemTotal:");
   if(p) {
     p = skip_token(p);
     val.f = atof( p );
   } else {
     val.f = 0;
   }

   return val;
}

g_val_t
swap_total_func ( void )
{
   char *p;
   g_val_t val;
 
   p = strstr( update_file(&proc_meminfo), "SwapTotal:" );
   if(p) {
     p = skip_token(p);
     val.f = atof( p );  
   } else {
     val.f = 0;
   }

   return val;
}

g_val_t
boottime_func ( void )
{
   char *p;
   g_val_t val;

   p = update_file(&proc_stat); 

   p = strstr ( p, "btime" );
   if(p) { 
     p = skip_token ( p );
     val.uint32 = atoi ( p );
   } else {
     val.uint32 = 0;
   }

   return val;
}

g_val_t
sys_clock_func ( void )
{
   g_val_t val;

   val.uint32 = time(NULL);
   return val;
}

g_val_t
machine_type_func ( void )
{
   g_val_t val;
 
#ifdef __i386__
   snprintf(val.str, MAX_G_STRING_SIZE, "x86");
#endif
#ifdef __x86_64__
   snprintf(val.str, MAX_G_STRING_SIZE, "x86_64");
#endif
#ifdef __ia64__
   snprintf(val.str, MAX_G_STRING_SIZE, "ia64");
#endif
#ifdef __sparc__
   snprintf(val.str, MAX_G_STRING_SIZE, "sparc");
#endif
#ifdef __alpha__
   snprintf(val.str, MAX_G_STRING_SIZE, "alpha");
#endif
#ifdef __powerpc__
   snprintf(val.str, MAX_G_STRING_SIZE, "powerpc");
#endif
#ifdef __m68k__
   snprintf(val.str, MAX_G_STRING_SIZE, "m68k");
#endif
#ifdef __mips__
   snprintf(val.str, MAX_G_STRING_SIZE, "mips");
#endif
#ifdef __arm__
   snprintf(val.str, MAX_G_STRING_SIZE, "arm");
#endif
#ifdef __hppa__
   snprintf(val.str, MAX_G_STRING_SIZE, "hppa");
#endif
#ifdef __s390__
   snprintf(val.str, MAX_G_STRING_SIZE, "s390");
#endif

   return val;
}

g_val_t
os_name_func ( void )
{
   g_val_t val;

   snprintf(val.str, MAX_G_STRING_SIZE, "Linux");
   return val;
}

g_val_t
os_release_func ( void )
{
   g_val_t val;

   snprintf(val.str, MAX_G_STRING_SIZE, "%s", proc_sys_kernel_osrelease);
   return val;
}

/*
** FIXME: all functions using /proc/stat should be rewritten to use a central function like networking
*/

/*
 * A helper function to return the total number of cpu jiffies
 */
unsigned long
total_jiffies_func ( void )
{
   char *p;
   unsigned long user_jiffies, nice_jiffies, system_jiffies, idle_jiffies,
                 wio_jiffies, irq_jiffies, sirq_jiffies;

   p = update_file(&proc_stat);
   p = skip_token(p);
   p = skip_whitespace(p);
   user_jiffies = strtod( p, &p );
   p = skip_whitespace(p);
   nice_jiffies = strtod( p, &p ); 
   p = skip_whitespace(p);
   system_jiffies = strtod( p , &p ); 
   p = skip_whitespace(p);
   idle_jiffies = strtod( p , &p );

   if(num_cpustates == NUM_CPUSTATES_24X)
     return user_jiffies + nice_jiffies + system_jiffies + idle_jiffies;

   p = skip_whitespace(p);
   wio_jiffies = strtod( p , &p );
   p = skip_whitespace(p);
   irq_jiffies = strtod( p , &p );
   p = skip_whitespace(p);
   sirq_jiffies = strtod( p , &p );
  
   return user_jiffies + nice_jiffies + system_jiffies + idle_jiffies +
          wio_jiffies + irq_jiffies + sirq_jiffies; 
}   


g_val_t
cpu_user_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_user_jiffies,  user_jiffies, 
                 last_total_jiffies, total_jiffies, diff;
   
   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     user_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();
     
     diff = user_jiffies - last_user_jiffies; 
     
     if ( diff )
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
     
     last_user_jiffies  = user_jiffies;
     last_total_jiffies = total_jiffies;
     
   }
   return val;
}

g_val_t
cpu_nice_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_nice_jiffies,  nice_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
 
     p = skip_token(p);
     p = skip_token(p);
     nice_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();

     diff = (nice_jiffies  - last_nice_jiffies);
 
     if ( diff )
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
 
     last_nice_jiffies  = nice_jiffies;
     last_total_jiffies = total_jiffies;

   }
   return val;
}

g_val_t 
cpu_system_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_system_jiffies,  system_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     system_jiffies = strtod( p , (char **)NULL );
     if (num_cpustates > NUM_CPUSTATES_24X) {
       p = skip_token(p);
       p = skip_token(p);
       p = skip_token(p);
       system_jiffies += strtod( p , (char **)NULL ); /* "intr" counted in system */
       p = skip_token(p);
       system_jiffies += strtod( p , (char **)NULL ); /* "sintr" counted in system */
       }
     total_jiffies  = total_jiffies_func();

     diff = system_jiffies  - last_system_jiffies;
 
     if ( diff )
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
 
     last_system_jiffies  = system_jiffies;
     last_total_jiffies = total_jiffies;   

   }
   return val;
}

g_val_t 
cpu_idle_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_idle_jiffies,  idle_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     idle_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();
     
     diff = idle_jiffies - last_idle_jiffies;
     
     if ( diff ) 
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
     
     last_idle_jiffies  = idle_jiffies;
     last_total_jiffies = total_jiffies;
     
   }
   
   return val;
}

g_val_t 
cpu_aidle_func ( void )
{
   char *p;
   g_val_t val;
   double idle_jiffies, total_jiffies;
   
   p = update_file(&proc_stat);

   p = skip_token(p);
   p = skip_token(p);
   p = skip_token(p);
   p = skip_token(p);
   idle_jiffies  = strtod( p , (char **)NULL );
   total_jiffies = total_jiffies_func();
   
   val.f = (idle_jiffies/total_jiffies)*100;
   return val;
}

g_val_t 
cpu_wio_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_wio_jiffies,  wio_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   if (num_cpustates == NUM_CPUSTATES_24X) {
     val.f = 0.;
     return val;
     }

   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     wio_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();
     
     diff = wio_jiffies - last_wio_jiffies;
     
     if ( diff ) 
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
     
     last_wio_jiffies  = wio_jiffies;
     last_total_jiffies = total_jiffies;
     
   }
   
   return val;
}

g_val_t 
cpu_intr_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_intr_jiffies,  intr_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   if (num_cpustates == NUM_CPUSTATES_24X) {
     val.f = 0.;
     return val;
     }

   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     intr_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();
     
     diff = intr_jiffies - last_intr_jiffies;
     
     if ( diff ) 
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
     
     last_intr_jiffies  = intr_jiffies;
     last_total_jiffies = total_jiffies;
     
   }
   
   return val;
}

g_val_t 
cpu_sintr_func ( void )
{
   char *p;
   static g_val_t val;
   static struct timeval stamp={0,0};
   static double last_sintr_jiffies,  sintr_jiffies,
                 last_total_jiffies, total_jiffies, diff;
 
   if (num_cpustates == NUM_CPUSTATES_24X) {
     val.f = 0.;
     return val;
     }

   p = update_file(&proc_stat);
   if((proc_stat.last_read.tv_sec != stamp.tv_sec) &&
      (proc_stat.last_read.tv_usec != stamp.tv_usec)) {
     stamp = proc_stat.last_read;
     
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     p = skip_token(p);
     sintr_jiffies  = strtod( p , (char **)NULL );
     total_jiffies = total_jiffies_func();
     
     diff = sintr_jiffies - last_sintr_jiffies;
     
     if ( diff ) 
       val.f = (diff/(total_jiffies - last_total_jiffies))*100;
     else
       val.f = 0.0;
     
     last_sintr_jiffies  = sintr_jiffies;
     last_total_jiffies = total_jiffies;
     
   }
   
   return val;
}

g_val_t
load_one_func ( void )
{
   g_val_t val;

   val.f = strtod( update_file(&proc_loadavg), (char **)NULL);

   return val;
}

g_val_t
load_five_func ( void )
{
   char *p;
   g_val_t val;

   p = update_file(&proc_loadavg);
   p = skip_token(p);
   val.f = strtod( p, (char **)NULL);
 
   return val;
}

g_val_t
load_fifteen_func ( void )
{
   char *p;
   g_val_t val;

   p = update_file(&proc_loadavg);
 
   p = skip_token(p);
   p = skip_token(p);
   val.f = strtod( p, (char **)NULL);

   return val;
}

g_val_t
proc_run_func( void )
{
   char *p;
   g_val_t val;

   p = update_file(&proc_loadavg);
   p = skip_token(p);
   p = skip_token(p);
   p = skip_token(p);
   val.uint32 = strtol( p, (char **)NULL, 10 );

   val.uint32--;
   /* This shouldn't happen.. but it might */
   if (val.uint32 <0)
      val.uint32 = 0;

   return val;
}

g_val_t
proc_total_func ( void )
{
   char *p;
   g_val_t val;

   p = update_file(&proc_loadavg);
   p = skip_token(p);
   p = skip_token(p);
   p = skip_token(p); 
   p = skip_whitespace(p);
   while ( isdigit(*p) )
      p++;
   p++;  /* skip the slash-/ */ 
   val.uint32 = strtol( p, (char **)NULL, 10 ); 

   return val;
}

g_val_t
mem_free_func ( void )
{
   char *p;
   g_val_t val;

   p = strstr( update_file(&proc_meminfo), "MemFree:" );
   if(p) {
     p = skip_token(p);
     val.f = atof( p );
   } else {
     val.f = 0;
   }

   return val;
}

g_val_t
mem_shared_func ( void )
{
   char *p;
   g_val_t val;

   /*
   ** Broken since linux-2.5.52 when Memshared was removed !!
   */
   p = strstr( update_file(&proc_meminfo), "MemShared:" );
   if (p) {
      p = skip_token(p);
      val.f = atof( p );
   } else {
      val.f = 0;
   }

   return val;
}

g_val_t
mem_buffers_func ( void )
{
   char *p;
   g_val_t val;

   p = strstr( update_file(&proc_meminfo), "Buffers:" );
   if(p) {
     p = skip_token(p);
     val.f = atof( p ); 
   } else {
     val.f = 0;
   }

   return val;
}

g_val_t
mem_cached_func ( void )
{
   char *p;
   g_val_t val;

   p = strstr( update_file(&proc_meminfo), "Cached:");
   if(p) {
     p = skip_token(p);
     val.f = atof( p );
   } else {
     val.f = 0;
   }

   return val;
}

g_val_t
swap_free_func ( void )
{
   char *p;
   g_val_t val;

   p = strstr( update_file(&proc_meminfo), "SwapFree:" );
   if(p) {
     p = skip_token(p);
     val.f = atof( p ); 
   } else {
     val.f = 0;
   }

   return val;
}

/* --------------------------------------------------------------------------- */
g_val_t
mtu_func ( void )
{
   /* We want to find the minimum MTU (Max packet size) over all UP interfaces. */
   g_val_t val;

   val.uint32 = get_min_mtu();

   /* A val of 0 means there are no UP interfaces. Shouldn't happen. */
   return val;
}

/* Linux Specific, but we are in the Linux machine file. */
#define MOUNTS "/proc/mounts"

struct nlist {
   struct nlist *next;
   char *name;
};

#define DFHASHSIZE 101
static struct nlist *DFhashvector[DFHASHSIZE];

/* --------------------------------------------------------------------------- */
unsigned int DFhash(const char *s)
{
   unsigned int hashval;
   for (hashval=0; *s != '\0'; s++)
      hashval = *s + 31 * hashval;
   return hashval % DFHASHSIZE;
}

/* --------------------------------------------------------------------------- */
/* From K&R C book, pp. 144-145 */
struct nlist * seen_before(const char *name)
{
   struct nlist *found=0, *np;
   unsigned int hashval;

   /* lookup */
   hashval=DFhash(name);
   for (np=DFhashvector[hashval]; np; np=np->next) {
      if (!strcmp(name,np->name)) {
         found=np;
         break;
      }
   }
   if (!found) {    /* not found */
      np = (struct nlist *) malloc(sizeof(*np));
      if (!np || !(np->name = (char *) strdup(name)))
         return NULL;
      np->next = DFhashvector[hashval];
      DFhashvector[hashval] = np;
      return NULL;
   }
   else /* found name */
      return found;
}

/* --------------------------------------------------------------------------- */
void DFcleanup()
{
   struct nlist *np, *next;
   int i;
   for (i=0; i<DFHASHSIZE; i++) {
      /* Non-standard for loop. Note the last clause happens at the end of the loop. */
      for (np = DFhashvector[i]; np; np=next) {
         next=np->next;
         free(np->name);
         free(np);
      }
      DFhashvector[i] = 0;
   }
}

/* --------------------------------------------------------------------------- */
int remote_mount(const char *device, const char *type)
{
   /* From ME_REMOTE macro in mountlist.h:
   A file system is `remote' if its Fs_name contains a `:'
   or if (it is of type smbfs and its Fs_name starts with `//'). */
   return ((strchr(device,':') != 0)
      || (!strcmp(type, "smbfs") && device[0]=='/' && device[1]=='/')
      || (!strncmp(type, "nfs", 3)) || (!strcmp(type, "autofs"))
      || (!strcmp(type,"gfs")) || (!strcmp(type,"none")) );
}

/* --------------------------------------------------------------------------- */
float device_space(char *mount, char *device, double *total_size, double *total_free)
{
   struct statvfs svfs;
   uint32_t blocksize;
   uint32_t free;
   uint32_t size;
   /* The percent used: used/total * 100 */
   float pct=0.0;

   /* Avoid multiply-mounted disks - not done in df. */
   if (seen_before(device)) return pct;

   if (statvfs(mount, &svfs)) {
      /* Ignore funky devices... */
      return pct;
   }

   free = svfs.f_bavail;
   size  = svfs.f_blocks;
   blocksize = svfs.f_bsize;
   /* Keep running sum of total used, free local disk space. */
   *total_size += size * (double) blocksize;
   *total_free += free * (double) blocksize;
   /* The percentage of space used on this partition. */
   pct = size ? ((size - free) / (float) size) * 100 : 0.0;
   return pct;
}

/* --------------------------------------------------------------------------- */
float find_disk_space(double *total_size, double *total_free)
{
   FILE *mounts;
   char procline[256];
   char mount[128], device[128], type[32], mode[128];
   /* We report in GB = 1e9 bytes. */
   double reported_units = 1e9;
   /* Track the most full disk partition, report with a percentage. */
   float thispct, max=0.0;
   int rc;

   /* Read all currently mounted filesystems. */
   mounts=fopen(MOUNTS,"r");
   if (!mounts) {
      debug_msg("Df Error: could not open mounts file %s. Are we on the right OS?\n", MOUNTS);
      return max;
   }
   while ( fgets(procline, sizeof(procline), mounts) ) {
      rc=sscanf(procline, "%s %s %s %s ", device, mount, type, mode);
      if (!rc) continue;
      if (!strncmp(mode, "ro", 2)) continue;
      if (remote_mount(device, type)) continue;
      if (strncmp(device, "/dev/", 5) != 0 &&
          strncmp(device, "/dev2/", 6) != 0) continue;
      thispct = device_space(mount, device, total_size, total_free);
      debug_msg("Counting device %s (%.2f %%)", device, thispct);
      if (!max || max<thispct)
         max = thispct;
   }
   fclose(mounts);

   *total_size = *total_size / reported_units;
   *total_free = *total_free / reported_units;
   debug_msg("For all disks: %.3f GB total, %.3f GB free for users.", *total_size, *total_free);

   DFcleanup();
   return max;
}

/* --------------------------------------------------------------------------- */
g_val_t
disk_free_func( void )
{
   double total_free=0.0;
   double total_size=0.0;
   g_val_t val;

   find_disk_space(&total_size, &total_free);

   val.d = total_free;
   return val;
}


/* --------------------------------------------------------------------------- */
g_val_t
disk_total_func( void )
{
   double total_free=0.0;
   double total_size=0.0;
   g_val_t val;

   find_disk_space(&total_size, &total_free);

   val.d = total_size;
   return val;
}

/* --------------------------------------------------------------------------- */
g_val_t
part_max_used_func( void )
{
   double total_free=0.0;
   double total_size=0.0;
   float most_full;
   g_val_t val;

   most_full = find_disk_space(&total_size, &total_free);

   val.f = most_full;
   return val;
}
