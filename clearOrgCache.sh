#!/bin/sh
DIR=`dirname "$(readlink -f "$0")"`
exec "$DIR/resources/git-hooks/post-merge";
