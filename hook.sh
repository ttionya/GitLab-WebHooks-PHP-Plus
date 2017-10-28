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

function del_no_dir_branch() {
    # $1 原始分支名
    # $2 对应分支路径
    # $3 分支状态记录文件
    
    if [ ! -d "$2" ]; then
        sed -i "/^$1 /d" $3
    fi
}
export -f del_no_dir_branch

function init() {
    # 判断 $2 文件是否存在，不存在则创建空文件
    if [ ! -e $2 ]; then
        cat /dev/null > $2
    fi

    # 判断每个分支是否存在对应的文件夹，不存在则从 $2 删除对应分支记录
    awk -v "PATH=$9" '{print $1" "PATH$1}' $2 | xargs -I {} bash -c "del_no_dir_branch {} $2"
}

function git_pull() {
    echo "开始对 $9$4 文件夹 $3 分支执行 Pull 操作..." >> $5 2>&1
    
    git -C $9$4 fetch origin $3 --prune >> $5 2>&1 \
    && git -C $9$4 checkout $3 >> $5 2>&1 \
    && git -C $9$4 pull >> $5 2>&1

    if [ $? != 0 ]; then
        echo 0
    else
        echo 1
    fi
}

# 初始化
init $@

# 分支正常推送
# 判断分支是否激活 -> 激活 -> 【Pull】
#                |
#                |
#                |
#                |
#               -> 未激活 -> 判断历史文件夹是否存在 -> 存在 -> 
#
if [ $1 == 'pushBranch' ]; then
    activeCount=`grep -P "^$4 1" $2 | wc -l`
    
    if [ $activeCount -gt 0 ]; then
        echo "分支 $3 处于激活状态" >> $5 2>&1
    
        # 更新分支
        git_pull $@
    else
        echo "分支 $3 处于未激活状态" >> $5 2>&1


    fi

# 移除分支
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
fi

BRANCH=`git -C $2 branch | grep -E "^[* ]*$1$" -c`

if [ $BRANCH == '1' ]; then
    echo '存在分支' >> $3
    git -C $2 checkout $1 >> $3 2>&1 && git -C $2 pull >> $3 2>&1
else
    echo '新分支' >> $3
    git -C $2 fetch origin $1 >> $3 2>&1 && git -C $2 checkout $1 >> $3 2>&1
fi
