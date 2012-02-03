/* 
 *	Copyright (C) 1998, 2006 Free Software Foundation, Inc.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this software; see the file COPYING.  If not, write to
 * the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301 USA
 */

#include <stdlib.h>
#include <unistd.h>
#include <stdio.h>
#include <math.h>
#include <rrd.h>
#include <getopt.h>
#include <glob.h>


typedef struct data_s {
  char *name;
  int size;
  double *raw;
  char *fromfile;
} data_t;

typedef struct dataset_s{
  char *filename;
  time_t start,end;
  unsigned long step ;
  int count;
  data_t **items;
} dataset_t;

dataset_t*
fetchrrd(char *rrdfilename, char* strStart, char *strEnd)
{
  dataset_t *result = NULL;
  optind=0;
  time_t start,end;
  rrd_value_t *datatmp;
  unsigned long step, ds_cnt;
  char **ds_namv;
  char parameter[7][255];
  char *parampointer[7];
  strcpy(parampointer[0]=parameter[0],"fetch");
  strcpy(parampointer[1]=parameter[1],"--start");
//  strcpy(parampointer[2]=parameter[2],"now-1day");
  strcpy(parampointer[2]=parameter[2],strStart);
  strcpy(parampointer[3]=parameter[3],"--end");
//  strcpy(parampointer[4]=parameter[4],"now");
  strcpy(parampointer[4]=parameter[4],strEnd);
  sprintf(parampointer[5]=parameter[5],"%s",rrdfilename);
  strcpy(parampointer[6]=parameter[6],"MAX");
  if(rrd_fetch(7, (char **)parampointer,&start,&end,&step,&ds_cnt,&ds_namv,&datatmp) != -1){
    result = (dataset_t*) malloc (sizeof(dataset_t));
    int dp_cnt = (end - start) / step;
    result->filename = rrdfilename;
    result->count = ds_cnt;
    result->start = start;
    result->end = end;
    result->step = step;
    result->items = (data_t**)malloc(sizeof(data_t*)*ds_cnt);
    unsigned int c = 0;
    for(c = 0; c < ds_cnt ; c++){
      data_t *dataitem = (data_t*) malloc (sizeof(data_t));
      dataitem->fromfile = rrdfilename;
      dataitem->name = ds_namv[c];
      dataitem->size = dp_cnt;
      dataitem->raw = (double*)malloc(sizeof(double) * dp_cnt);
      double *src,*dst;
      for(src=datatmp + c, dst=dataitem->raw; 
          src < (datatmp + (dp_cnt * ds_cnt));
          src += ds_cnt,dst++){
          *dst = *src;
      };
      result->items[c] = dataitem;
    };
  } else {
    printf("failed to fetch from file %s\n",rrdfilename);
  };

  return result;
};

/*
double*
ts_wavelet (double* data, int n)
{
  size_t *p = malloc (n * sizeof (size_t));

  gsl_wavelet *w;
  gsl_wavelet_workspace *work;

//  w = gsl_wavelet_alloc (gsl_wavelet_daubechies, 4);
  w = gsl_wavelet_alloc (gsl_wavelet_haar, 2);
  work = gsl_wavelet_workspace_alloc (n);

  gsl_wavelet_transform_forward (w, data, 1, n, work);

  gsl_wavelet_free (w);
  gsl_wavelet_workspace_free (work);

  free (p);
  return data;
}
*/


double
ts_min(data_t *data )
{
  double min = INFINITY;
  int i = 0;
  for(i = 0; i < data->size; i++){
    if(data->raw[i] < min) min = data->raw[i];
  };
  return min;
};

double
ts_max(data_t *data )
{
  double max = 0.0;
  int i = 0;
  for(i = 0; i < data->size; i++){
    if(data->raw[i] > max) max = data->raw[i];
  };
  return max;
};

double
ts_mean(data_t *data )
{
  double res = 0.0;
  int count = 0;
  int i = 0;
  for(i = 0; i < data->size; i++){
    if(isnan(data->raw[i])) continue;
    res += data->raw[i];
    count++;
  };
  if(count > 0) return res / (double) count;
  return NAN;
};

double 
ts_stdev(data_t *data )
{
    if(data->size == 0)
        return 0.0;
    int count = 0;
    double sq_diff_sum = 0;
    double mean = ts_mean(data);
    for(int i = 0; i < data->size; ++i) {
       if(isnan(data->raw[i])) continue;
       double diff = data->raw[i] - mean;
       sq_diff_sum += diff * diff;
       count++;
    }
    double variance = sq_diff_sum / (double) count;
    return sqrt(variance);
}

data_t*
ts_normalise(data_t *data )
{
  double max = ts_max(data);
  int i = 0;
  for(i = 0; i < data->size; i++){
    data->raw[i] = data->raw[i] / max;
  };
  return data;
};

double
ts_countNaN(data_t *data )
{
  int count = 0;
  int i = 0;
  for(i = 0; i < data->size; i++){
    if(isnan(data->raw[i])) count++;
  };
  return count;
};

data_t*
ts_znormalise(data_t *data )
{
  double mean = ts_mean(data);
  double stdev = ts_stdev(data);
  int i = 0;

  if(isinf(mean) || isnan(mean) || isinf(stdev) || isnan(stdev)){
    // Special cases
  } else if( stdev == 0.0){
    for(i = 0; i < data->size; i++){
      if(isnan(data->raw[i]) || isinf(data->raw[i])){
        //data->raw[i] = (data->raw[i] - mean) / stdev;
      } else {
        data->raw[i] = 1.0;
      }
    }
  } else {
    for(i = 0; i < data->size; i++){
      data->raw[i] = (data->raw[i] - mean) / stdev;
    }
  };
  return data;
};

data_t*
ts_paa(data_t *data, int paasize )
{
  // PAA size may not divide exactly into our data set so the easiest thing
  // to do is just skip the remainder (assuming the most recent data, on the
  // right of the data set is the most interesting
  data_t *res = (data_t *) malloc (sizeof(data_t));

  // If there is less data in the paa than the requested amount
  // just return the raw data.
  if(data->size <= paasize){
    res->raw = (double *) malloc (sizeof(double) * data->size);
    res->size = data->size;
    int i;
    for(i=0; i<data->size; i++){
      res->raw[i] = data->raw[i];
    };
    return res;
  };

  res->raw = (double *) malloc (sizeof(double) * paasize);
  res->size = paasize;
  int c = data->size / paasize;
  int skip = data->size % paasize;

  int i,j;
  for(i=0; i<paasize; i++){
    double val = 0;
    for(j=0; j<c; j++){
      val += data->raw[skip + i*c +j];
    }
    res->raw[i] = val / (double) c;
  };

  return res;
};

double saxbreaks8val[7] = { -1.5, -0.67, -0.32 ,0.0 ,0.32 ,0.67 ,1.5 };
char saxbreaks8code[9] = { 'a', 'b', 'c' , 'd' , 'e' , 'f' , 'g' , 'h', 'i' };
char*
paa_sax(data_t *paa)
{
  char * res = (char*) malloc (sizeof(char) * paa->size + 1);
  int i;
  for( i=0; i < paa->size; i++)
  {
    double x = paa->raw[i];
    res[i] = 
    x < saxbreaks8val[0] ? saxbreaks8code[0] :
    x >= saxbreaks8val[0] && x < saxbreaks8val[1] ? saxbreaks8code[1] :
    x >= saxbreaks8val[1] && x < saxbreaks8val[2] ? saxbreaks8code[2] :
    x >= saxbreaks8val[2] && x < saxbreaks8val[3] ? saxbreaks8code[3] :
    x >= saxbreaks8val[3] && x < saxbreaks8val[4] ? saxbreaks8code[4] :
    x >= saxbreaks8val[4] && x < saxbreaks8val[5] ? saxbreaks8code[5] :
    x >= saxbreaks8val[5] && x < saxbreaks8val[6] ? saxbreaks8code[6] :
    x >= saxbreaks8val[6] && x < saxbreaks8val[7] ? saxbreaks8code[7] :
    x >= saxbreaks8val[7] ? saxbreaks8code[8] : 'X';
  };
  res[paa->size] = 0;

  return res;
};

char*
process(data_t *data, int paasize )
{
  ts_znormalise(data);
  data_t *paa = ts_paa(data,paasize);
  char *res = paa_sax(paa);
  free(paa->raw);
  free(paa);
  return res;
};

int    help_flag = 0;
int    opt_rraglob_count = 0;
char** opt_rraglobs = NULL;
int    opt_paasize = 10;

void
display_help()
{
  exit(0);
};

int 
main (int argc, char **argv)
{
  int processed = 0;
  int matched = 0;

  int c;
  while(1){
    int option_index = 0;
    static struct option long_options[] = {
      {"version"      ,no_argument       ,&help_flag ,  1},
      {"help"         ,no_argument       ,&help_flag ,  1},
      {"rraglob"      ,required_argument ,0             ,'g'},
      {"paasize"      ,required_argument ,0             ,'s'},
      {0, 0, 0, 0}
    }; 
    c = getopt_long (argc,argv,"vhs:g:",long_options, &option_index);

    if (c == -1) break;
    
    switch(c){
      case   0:
        break;

      case 'V':
      case 'H':
        help_flag = 1;
        break;

      case 'g':
        opt_rraglob_count++;
        opt_rraglobs = (char**) realloc(opt_rraglobs,sizeof(char*) * opt_rraglob_count);
        opt_rraglobs[opt_rraglob_count - 1] = optarg; 
        break;

      case 's':
        opt_paasize = atoi(optarg); 
        break;

      default: 
        abort();
    };
  };
  
  if((argc < optind + 4)||
     (argc > optind + 4)){
    display_help();
  };

  char *srchFile = argv[optind];
  char *srchDS = argv[optind + 1];
  //char *srchAgg = "MAX";
  char *srchStart = argv[optind + 2];
  char *srchEnd = argv[optind + 3];
  dataset_t *srchSet = fetchrrd(srchFile,srchStart,srchEnd);

  if(srchSet == NULL){
    printf("Could not open rrd %s\n",srchFile);
    abort();
  };

  data_t *srchData = NULL;
  for(c=0;c < srchSet->count;c++){
    if(!strcmp(srchDS,srchSet->items[c]->name)){
      srchData = srchSet->items[c];    
    };
  };

  if(srchData == NULL){
    printf("No DS named %s found in %s\n",srchDS,srchFile);
    abort();
  };

  char *srchSax = process(srchData, opt_paasize);


  if (opt_rraglob_count == 0){
    opt_rraglob_count++;
    opt_rraglobs = (char**) realloc (opt_rraglobs, sizeof(char*) * opt_rraglob_count);
    opt_rraglobs[opt_rraglob_count - 1] = "/var/www/cacti/rra/[0-9]*/*.rrd"; 
  };

  printf("Search for time series matching %s using paa size %i...\n",srchSax, opt_paasize);

  int checkglob;
  for(checkglob = 0; checkglob < opt_rraglob_count; checkglob++){
    printf("Search %s\n", opt_rraglobs[checkglob]);
    glob_t filelist;
    filelist.gl_offs = 0;
    if(glob(opt_rraglobs[checkglob],0,NULL,&filelist)){
      break;
    };

    char **paths = NULL;
    for(paths = filelist.gl_pathv;*paths != NULL; paths++){
      char *path = *paths;
      int f;
      dataset_t *set = fetchrrd(path,srchStart,srchEnd);
      for(f = 0; f < set->count; f++){
        data_t *item = set->items[f];
        if(!strcmp(process(item,opt_paasize),srchSax)){
          printf("Match:%s:%s\n",item->fromfile,item->name);
          matched++;
        };
        processed++;
        free(set->items[f]->raw);
        free(set->items[f]);
      };
    };

  };
  printf("Processed %i RRAs\n",processed);
  printf("Matched %i RRAs\n",matched);

  return 0;
};

 
