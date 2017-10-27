#!/bin/bash

# $1 操作类型
# $2 分支状态记录文件
# $3 原始分支名
# $4 转换后的分支名 (下级域名)
# $5 日志文件路径
# $6 Nginx 执行文件路径
# $7 Nginx 配置文件目录
# $8 Nginx 模板文件
# $9 项目父路径目录
# ${10} 域名
# ${11} 空闲文件夹数

# 判断当前分支是否存在
# $2 分支状态记录文件
# $3 分支名
if [ $1 == 1 ]; then
    cat $2 | grep -P "^$3 " | wc -l

# 移除分支相关内容
else if [ $1 == 'delBranch' ]; then
    echo "移除分支：$3 ($4)" >> $5 2>&1

    # 设置当前分支为闲置状态
    now=`date +%s`
    sed -i "s/^\($4\) .*/\1 0 $now/" $2

    # 删除 Nginx 配置并重启 Nginx

    # 移除多余文件夹
    saveCount=`expr ${11} + 1`
    delBranch=`awk '/^[-0-9A-Za-z]* 0 .*/{print $3" "$1}' $2 | sort -r -k2 | tail -n +$saveCount | awk -F " " '{print $1}'`
    delDir=`echo "$delBranch" | awk -v "PATH=$9" '{print PATH$1}'`
    echo "$delDir" | xargs rm -rf
    echo "$delBranch" | xargs -I {} sed -i '/^{} /d' $2
    echo "移除多余文件夹：`echo "$delDir" | tr '\n' ' '`" >> $5 2>&1

# 正常分支推送
else if [ $1 == 3 ]; then

fi

BRANCH=`git -C $2 branch | grep -E "^[* ]*$1$" -c`

if [ $BRANCH == '1' ]; then
    echo '存在分支' >> $3
    git -C $2 checkout $1 >> $3 2>&1 && git -C $2 pull >> $3 2>&1
else
    echo '新分支' >> $3
    git -C $2 fetch origin $1 >> $3 2>&1 && git -C $2 checkout $1 >> $3 2>&1
fi
