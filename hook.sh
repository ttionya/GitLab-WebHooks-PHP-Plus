#!/bin/bash

# $1 分支
# $2 项目地址
# $3 日志地址

BRANCH=`git branch | grep -E '^[* ]*$1$' -c`

if [ $BRANCH == 1 ]; then
    # 存在分支
    git -C $2 checkout $1 >> $3 2>&1 && git -C $2 pull origin $1:$1 >> $3 2>&1
else
    # 新分支
    git -C $2 pull origin $1:$1 >> $3 2>&1 && git -C $2 checkout $1 >> $3 2>&1
fi
