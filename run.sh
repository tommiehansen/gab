#!/bin/bash
clear

# USER SETTINGS ---------------------------------------------------------

# server (with php) + dir
SERVER="http://localhost:8889"
DIR="/gab/"

# threads
THREADS=3 # TODO: FIX ...

# strategy
STRATEGY="RSI_BULL_BEAR"

# TOML for strategy
TOML="
    SMA_long = 100:1000,100
    SMA_short = 10:50,10
    BULL_RSI = 10:20,5
    BULL_RSI_high = 70:80,10
    BULL_RSI_low = 50:60,10
    BEAR_RSI = 10:20,10
    BEAR_RSI_high = 50:60,5
    BEAR_RSI_low = 10:30,5
"

# settings
candle_size="5:15,5"
exchange="bitfinex"
currency="USD"
asset="XRP"
history_size="10"
from="0"
to="0"

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

# urlencode TOML
TOML=$(urlencode "$TOML")

# concatinate
SRC=$SERVER
SRC+=$DIR
SRC+="system/post.php"

# echo
line="========================================================\n"
printf "$line\n"
echo "  GAB CLI"
printf "  $SRC\n\n"
printf "$line\n"


# this works...
generate_post_data()
{
    cat <<EOF
    dataset=%7B%22exchange%22%3A%22$exchange%22%2C%22currency%22%3A%22$currency%22%2C%22asset%22%3A%22$asset%22%2C%22from%22%3A$from%2C%22to%22%3A$to%7D&strat=$STRATEGY&toml=$TOML&candle_size=$candle_size&history_size=$history_size&cli=true
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
MAX_NO_RESULTS=200 # fix this...

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
    #timer_end=$(date +%s)
    #diff=$(($timer_end - $timer_start))
    #if [[ diff < 3 ]]; then
    #    let "MAX_COUNTER++"
    #else
    #    MAX_COUNTER=0 # reset
    #fi
    printf "\r";
    date=`date '+%H:%M:%S'`
    printf "${yel}${date}:${end} "
    ELAPSED="Total duration: $(($SECONDS / 3600))hrs $((($SECONDS / 60) % 60))min $(($SECONDS % 60))sec"
    EXEC_TIME="$((($diff / 60) % 60))min $(($diff % 60))sec"
    echo $get
    printf "Run time: $EXEC_TIME / $ELAPSED / Total runs: ${COUNTER}\n"
    #echo " MAX: $MAX_COUNTER "

    # run again
    runforever
}

# display threads
printf "${cyn}INFO${end} > Running with ${THREADS} threads \n"
printf "${cyn}INFO${end} > To quit hold CTRL+C \n\n"
printf "\n\n";

# run forever with X threads
#export -f runforever
#xargs -P 3 {} bash -c 'runforever'
#xargs -P 2 'runforever'

# init
runforever
