<?php
/*
 * 项目父目录
 *
 * 存放项目的父目录路径，建议绝对路径
 * 
 * 最后一个斜杠 '/' 必须存在
 */
$project_dir = '/data/www/default/project/';

/*
 * 域名
 * 
 * 需要将该域名的下级域名 DNS A 记录指向部署该脚本的服务器
 * 
 * 例如：填写 'test.example.com'，需要将 '*.test.example.com' 的 DNS A 记录指向部署该脚本的服务器
 */
$domain = 'test.example.com';

/*
 * Token
 *
 * 填写 Secret Token
 */
$token = '9998877';

/*
 * ref
 *
 * 支持
 *   '*'                                                任何分支
 *   'refs/heads/master'                                master 分支
 *   '/^refs\/heads\/(master|dev)$/i'                   master 和 dev 分支
 */
$ref = '*';

/*
 * 特殊分支
 * 
 * 特殊分支可以指定使用特殊的下级域名
 * 
 * 不与特殊分支匹配的分支使用与分支名相同的名称作为下级域名 (用 - 替代 .)
 * 
 * "分支名" => "下级域名"
 */
$special_branches = array(
    "master" => "www",
    "dev" => "test"
);

/*
 * Nginx 执行文件目录
 * 
 * 绝对路径 
 */
$nginx_bin_path = '/usr/local/nginx/sbin/nginx';

/*
 * Nginx 配置文件目录
 * 
 * 绝对路径
 */
$nginx_conf_path = '/usr/local/nginx/conf/gitlab-hook/';

/*
 * Nginx 模板文件
 * 
 * 建议绝对路径
 */
$nginx_template_file = './nginx.conf.template';

/****************************** 不要修改以下内容 ******************************/
$hookfile               = 'hook.sh';            // 脚本文件
$logfile                = 'hook.log';           // 日志文件
$branchfile             = 'branch.data';        // 分支状态记录文件
$free_branch_limit      = 5;                    // 空闲文件夹数，会删除超过该数量的分支文件夹
