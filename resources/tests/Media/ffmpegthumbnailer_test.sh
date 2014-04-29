#!/bin/bash

/usr/bin/ffmpegthumbnailer-2.0.8 -i ${1} -o output.png -s0 -q8 -t 5
