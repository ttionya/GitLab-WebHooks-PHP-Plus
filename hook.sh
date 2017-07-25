#!/bin/bash

# $1 分支
# $2 项目地址
# $3 日志地址

BRANCH=`git -C $2 branch | grep -E "^[* ]*$1$" -c`

if [ $BRANCH == '1' ]; then
    echo '存在分支' >> $3
    git -C $2 checkout $1 >> $3 2>&1 && git -C $2 pull >> $3 2>&1
else
    echo '新分支' >> $3
    git -C $2 fetch origin $1 >> $3 2>&1 && git -C $2 checkout $1 >> $3 2>&1
fi
