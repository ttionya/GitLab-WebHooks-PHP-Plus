#!/bin/bash

# $1 操作
# $1 分支
# $2 项目地址
# $3 日志地址

# 判断当前分支是否存在
# $2 分支状态记录文件
# $3 分支名
if [ $1 == 1 ]; then
    cat $2 | grep -P "^$3 " | wc -l
fi

BRANCH=`git -C $2 branch | grep -E "^[* ]*$1$" -c`

if [ $BRANCH == '1' ]; then
    echo '存在分支' >> $3
    git -C $2 checkout $1 >> $3 2>&1 && git -C $2 pull >> $3 2>&1
else
    echo '新分支' >> $3
    git -C $2 fetch origin $1 >> $3 2>&1 && git -C $2 checkout $1 >> $3 2>&1
fi
