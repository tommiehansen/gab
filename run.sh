#!/bin/bash
clear

# USER SETTINGS ---------------------------------------------------------

# Server (with php) + dir
SERVER="http://localhost:8889"
DIR="/gab/"

# Threads
THREADS=3 # TODO: FIX ...

# Strategy, must be valid
# Check your JS-files for names and remove .js extension e.g. RSI_BULL_BEAR
STRATEGY="RSI_BULL_BEAR_ADX"

# TOML for strategy, just copy-paste the params and add dynamic variables
# NOTE: Avoid odd formatting with thousands of tabs and spaces...
# NOTE: format is <MIN>:<MAX>,<STEPPING> e.g. 10:20,5 or -20:-5,5
TOML="
[SMA]
long = 700:1000,50
short = 10:90,5

[BULL]
rsi = 5:20,5
high = 70:90,5
low = 30:50,5
mod_high = 5:10,5
mod_low = -30:-15,5

[BEAR]
rsi = 5:20,5
high = 20:40,5
low = 10:30,5
mod_high = 10:20,5
mod_low = -20:-5,5

[ADX]
adx = 1:10,5
high = 60:80,5
low = 20:40,5
"

# settings, rest of the settings -- make sure you actually have the dataset
candle_size="10:15,5"
exchange="poloniex"
currency="USDT"
asset="ETC"
history_size="10"

# dates, use Y-m-d format e.g. 2018-12-24 or Y-m-dT23:00:00:00Z e.g. 2018-12-24T23:30:00:00Z if nerding out
# NOTE: Changing date from/to *WILL* create a new database for that daterange
# NOTE: You need to check your dataset(s) to get these values
from="2017-12-01"
to="2018-02-01"

# END USER SETTINGS ----------------------------------------------------

# urlencode <string>
urlencode()
{
   old_lc_collate=$LC_COLLATE
   LC_COLLATE=C

   local length="${#1}"
   for (( i = 0; i < length; i++ )); do
       local c="${1:i:1}"
       case $c in
           [a-zA-Z0-9.~_-]) printf "$c" ;;
           *) printf '%%%02X' "'$c" ;;
       esac
   done

   LC_COLLATE=$old_lc_collate
}

# urlencode
TOML=$(urlencode "$TOML")
from=$(urlencode "$from")
to=$(urlencode "$to")

# concatinate
SRC=$SERVER
SRC+=$DIR
SRC+="system/post.php"




# this works ...
generate_post_data()
{
    cat <<EOF
    dataset=%7B%22exchange%22%3A%22$exchange%22%2C%22currency%22%3A%22$currency%22%2C%22asset%22%3A%22$asset%22%2C%22from%22%3A%22$from%22%2C%22to%22%3A%22$to%22%7D&strat=$STRATEGY&toml=$TOML&candle_size=$candle_size&history_size=$history_size&cli=true
EOF
}

# colors
red=$'\e[1;31m'
grn=$'\e[1;32m'
yel=$'\e[1;33m'
blu=$'\e[1;34m'
mag=$'\e[1;35m'
cyn=$'\e[1;36m'
end=$'\e[0m'

SECONDS=0
TIMER=0
COUNTER=0
MAX_COUNTER=0
MAX_NO_RESULTS=200 # TODO ... Need a way to kill everything if NO_RESULTS_RUN > X

runforever()
{
    let "COUNTER++"
    #echo $COUNTER;
    date=`date '+%H:%M:%S'`
    printf "${cyan}${date}:${end} ${mag}In progress...${end}"

    timer_start=$(date +%s)

    # curl
    #curl -X POST --data "$(generate_post_data)" $SRC
    get=$(curl -s -X POST --data "$(generate_post_data)" $SRC)
    timer_end=$(date +%s)
    diff=$(($timer_end - $timer_start))

    # TODO
    #if [[ diff < 3 ]]; then
    #    let "MAX_COUNTER++"
    #else
    #    MAX_COUNTER=0 # reset
    #fi
    printf "\r";
    date=`date '+%H:%M:%S'`
    printf "${yel}${date}:${end} "
    ELAPSED="Duration: $(($SECONDS / 3600))h $((($SECONDS / 60) % 60))m"
    EXEC_TIME="$((($diff / 60) % 60))m $(($diff % 60))s"
    STATUS=" [ Exec: $EXEC_TIME / $ELAPSED / Total runs: ${COUNTER} ]"
    mod="${get/Bad!/${red}BAD!${end}}"
    mod="${mod/Success!/${grn}SUCCESS!${end}}"
    echo $mod.$STATUS
    #echo " MAX: $MAX_COUNTER "

    # run again
    runforever
}

# header
line="========================================================\n"
printf "${yel}${line}${end}\n"
echo "${yel}  GAB CLI${end}"
printf "${yel}  $SRC ${end}\n\n"
printf "${yel}${line}${end}\n"

# display threads
printf "${cyn}INFO${end} > Running $STRATEGY using 1 thread(s) \n"
printf "${cyn}INFO${end} > To quit hold CTRL+C \n\n"
printf "\n\n";

# run forever with X threads
#export -f runforever
#xargs -P 3 {} bash -c 'runforever'
#xargs -P 2 'runforever'

# init
runforever
