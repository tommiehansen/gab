#!/bin/bash

red=$'\e[1;31m'
grn=$'\e[1;32m'
yel=$'\e[1;33m'
blu=$'\e[1;34m'
mag=$'\e[1;35m'
cyn=$'\e[1;36m'
end=$'\e[0m'

SECONDS=0
TIMER=0
date=`date '+%H:%M:%S'`
#printf "${cyan}${date}:${end} ${mag}In progress, run ${3}...${end}\n"
timer_start=$(date +%s)
#echo "curl -s -X POST --data \"$1\" $2"
get=$(curl -s -X POST --data "$1" $2);
#echo $get
timer_end=$(date +%s)
diff=$(($timer_end - $timer_start))
printf "\r"
date=`date '+%H:%M:%S'`
printf "${yel}${date}:${end} "
EXEC_TIME="$((($diff / 60) % 60))m $(($diff % 60))s"
STATUS=" [ Exec: $EXEC_TIME / Run #${COUNTER} ]"
mod="${get/Bad!/${red}BAD!${end}}"
mod="${mod/Notice:/${blu}Notice:${end}}"
mod="${mod/Success!/${grn}SUCCESS!${end}}"
echo $mod.$STATUS