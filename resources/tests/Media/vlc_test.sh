#!/bin/bash

convert -size 424x320 xc:none black.png

cvlc -I dummy --stop-time=4306 --mosaic-width=424 --mosaic-height=320 --mosaic-keep-aspect-ratio --mosaic-keep-picture --mosaic-xoffset=0 --mosaic-yoffset=0 --mosaic-position=2 --mosaic-offsets="0,0,13,13" --mosaic-order="1,2" --vlm-conf pip.cfg

rm -f black.png
