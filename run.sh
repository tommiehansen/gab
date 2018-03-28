#!/bin/bash
clear

file=$@

# check if settings.sh exists
if [ ! -f cli/$file ]; then
    echo " ERROR!"
    echo " You have no $file file located in the folder cli, please create it and try again."
    echo " Tip: Use settings.sample.sh as a template"
    echo " Note: You must use LF (unix) line endings"
    return
fi

if (( "$#" != 1 ))
then
    echo " ERROR - No configuration file supplied"
    echo " Usage: . run.sh your-settings.sh"
return
fi

# import user settings
. cli/$file


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
