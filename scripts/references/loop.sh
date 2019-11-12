#!/bin/bash
while getopts n:p:s: option
do
        case "${option}"
        in
                n) Number=$((OPTARG));;
                p) PATH=${OPTARG};;
                s) PHP=${OPTARG};;
        esac
done

counter=1
while [ $counter -le $Number ]
do
    $PHP $PATH &
    ((counter++))
done
wait
exit 1
