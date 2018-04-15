#!/bin/bash
clear

file=$1
set -a


# check if settings.sh exists
if [ ! -f $file ]; then
    echo " ERROR!"
    echo " You have no $file file, please create an .sh file in the cli folder and try again."
    echo " Tip: Use settings.sample.sh as a template"
    echo " Note: You must use LF (unix) line endings"
    exit
fi

if (( "$#" != 1 ))
then
    echo " ERROR - No configuration file supplied"
    echo " Usage: . run.sh cli/your-settings.sh"
    exit
fi


# ask user for number of threads
THREADS=1
echo -n "How many threads? [1-99]: "
read THREADS
if [[ $THREADS -lt 1 || $THREADS -gt 99 ]]
    then
        echo "Bad number of threads, idiot."
        return
else
    clear
fi

trap 'echo " exiting gab";kill $(jobs -p);exit' SIGINT

# import user settings
. $file


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
    LIMIT=$(expr $THREADS \* 2)
    for ID in $(seq 1 $LIMIT); do
    echo $ID; done | xargs -I{} --max-procs $THREADS bash -c "
    ./curl.sh '$(generate_post_data)' $SRC $COUNTER {}"

    COUNTER=$(expr $COUNTER + $LIMIT)
    runforever
}

# header
line="========================================================\n"
printf "${yel}${line}${end}\n"
echo "${yel}  GAB CLI${end}"
printf "${yel}  $SRC ${end}\n\n"
printf "${yel}${line}${end}\n"

# display threads
info="${cyn}INFO${end} >"
set="${yel}SETTINGS${end} >"
printf "$info Running $STRATEGY using $THREADS thread(s) \n"
printf "$info To quit hold CTRL+C \n\n"
printf "$set \n"
printf "    $asset / $currency @ $exchange \n"
printf "    Candle: ${candle_size} min  History: $history_size \n"
printf "\n";


arr[0]=" you bloody idiot."
arr[1]=" and let's have a nice day?"
arr[2]=" ... hopefully it will turn out well."
arr[3]=" you piece of s*** garbabe w***!"
arr[4]="! Maybe drink some coffee and go watch the sun?"
arr[5]="! This is a great time to go do other things."
arr[6]=" and while it runs let's spam Tommie Hansen with random questions."
arr[7]=" Lambo generator started."

rand=$(( RANDOM % 8 ))

printf "Let's go${arr[$rand]} \n\n"


# init
runforever
