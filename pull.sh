#!/bin/sh
DIR=`dirname "$(readlink -f "$0")"`
cd $DIR;

if [ ! -x ".git/hooks/pre-commit" ] || [ ! -x ".git/hooks/post-merge"  ]; then
  rm -f ".git/hooks/pre-commit";
  rm -f ".git/hooks/post-merge";
  ln -s -f "$DIR/resources/git-hooks/pre-commit" ".git/hooks/pre-commit" || echo "Could not create pre-commit symlink";
  ln -s -f "$DIR/resources/git-hooks/post-merge" ".git/hooks/post-merge" || echo "Could not create post-merge symlink";
fi

git pull origin
git submodule update
