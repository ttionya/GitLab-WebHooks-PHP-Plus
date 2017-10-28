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
$git_ssh_url = $json->repository->git_ssh_url;

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

// 执行命令的数组
$cmd_array = array(
    'sh',
    $hookfile,
    '',
    $branchfile,
    $branch,
    $branch_safe,
    $logfile,
    $nginx_bin_path,
    $nginx_conf_path,
    $nginx_template_file,
    $project_dir,
    $domain,
    $free_branch_limit,
    $git_ssh_url
);

// 当前 Push 分支状态
if ($json->after === str_pad('', 40, '0')) { // 分支被删除了
    exec(get_cmd('delBranch'));
}
else { // 正常分支推送
    $cmd = get_cmd('pushBranch');
    $result = exec($cmd);

    // 发邮件
    sendmail($result, $cmd);
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

function get_cmd($active_name) {
    global $cmd_array;

    $cmd_array[2] = $active_name;
    $cmd = implode(' ', $cmd_array);
    
    logs('运行脚本：' . $cmd);
    return $cmd;
}

function sendmail($result, $cmd) {
    if ($result) {
        // 成功
        logs('成功');
    }
    else {
        // 失败
        logs('失败');
    }
}

$user_email = $json->user_email;        // 执行 push 操作的用户邮箱
