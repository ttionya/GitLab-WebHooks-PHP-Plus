#!/bin/bash

# $1 分支
# $2 项目地址
# $3 日志地址

git -C $2 checkout $1 >> $3 2>&1 &
git -C $2 pull >> $3 2>&1 &