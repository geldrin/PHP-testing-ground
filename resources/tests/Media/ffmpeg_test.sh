#!/bin/bash

ffmpeg -y -i ${1} -strict experimental -async 10 -c:a libfaac -ac 1 -b:a 64k -ar 44100 -c:v libx264 -profile:v main -preset:v fast -s 640x360 -aspect 16:9 -r 25 -b:v 600k -threads 0 -f mp4 output.mp4
