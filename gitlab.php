<?php
/**
 * GitLab Web Hook PHP 版
 * 
 * 用于为每个远程分支生成独立的测试环境
 *
 * 注意：1、需要使用 exec 函数，请确定该函数可用
 *      2、Web Server 为 Nginx
 * 
 */

include 'settings.php';

/*
 * Check Token
 */
if (function_exists(getallheaders)) { // 存在该函数
    $headers = getallheaders();
    $remote_token = $headers['X-Gitlab-Token'];
}
else {
    $remote_token = $_GET['token'];
}

if (empty($remote_token)) {
    logs('请求中不包含 Token');
    die();
}
elseif ($remote_token !== $token) {
    logs('请求中的 Token 不匹配');
    die();
}

/*
 * Check GitLab JSON data
 */
$input = file_get_contents("php://input");
$json  = json_decode($input);
$remote_ref = $json->ref;

if (!is_object($json) || empty($remote_ref)) {
    logs('无效数据');
    die();
}

// 校验 ref
$is_matched = false;
if ($ref === '*' || $remote_ref === $ref || substr($ref, 0 ,1) === '/' && preg_match($ref, $remote_ref)) {
    $is_matched = true;
}

// 获得分支名或者不匹配退出
if ($is_matched) {
    preg_match('/(?:.*\/)*(.*)/i', $remote_ref, $branch);
    $branch = $branch[1];
}
else {
    logs('忽略 ref：' . $remote_ref);
    die();
}

// 获得下级域名
// 判断是否属于特殊分支，特殊分支有指定的下级域名
if (array_key_exists($branch, $special_branches)) {
    $branch_safe = preg_replace('/\W/', '-', $special_branches[$branch]);
}
else {
    $branch_safe = preg_replace('/\W/', '-', $branch);
}

// 当前 Push 分支状态
if ($json->after === str_pad('', 40, '0')) { // 分支被删除了
    exec('sh ' . $hookfile . ' 2 ' . $branchfile . ' ' . $branch_safe . ' ' . $project_dir . ' ' . $free_branch_limit . ' ' . $logfile);
}


/*
 * Functions
 */
function logs($msg, $time = null) {
    global $logfile;

    $date = date('Y-m-d H:i:s', $time === null ? time() : $time);
    $text  = $date . ' (' . $_SERVER['REMOTE_ADDR'] . '): ';

    file_put_contents($logfile, $text . $msg . "\n", FILE_APPEND);
}

$after_hash = $json->after;             // 提交后分支哈希，用于判断分支是否被删除
$user_email = $json->user_email;        // 执行 push 操作的用户邮箱
// 判断当前分支是否存在
$branchIsExist = exec('sh ' . $hookfile . ' 1 ' . $branchfile . ' ' . $branch);
$cmd = 'sh ' . $hookfile . ' ' . $branch . ' ' . $project_dir . ' ' . $logfile;

logs('运行脚本：' . $cmd);
exec($cmd);
