#!/bin/bash

git -C ../ checkout $1 >> $2 2>&1 &
git -C ../ pull >> $2 2>&1 &