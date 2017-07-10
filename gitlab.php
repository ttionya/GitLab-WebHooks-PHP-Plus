<?php
/**
 * GitLab Web Hook PHP 版
 *
 * 注意：需要使用 exec 函数，请确定该函数可用
 */


/*
 * Hook 文件
 *
 * 建议绝对路径
 */
$hookfile = 'hook.sh';

/*
 * 日志文件
 *
 * 建议绝对路径
 */
$logfile = 'hook.log';

/*
 * 项目路径
 *
 * 建议绝对路径
 */
$project_dir = '/data/www/default/webhooks/';

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

/*
 * Check ref
 */
$is_matched = false;
if ($ref === '*' || $remote_ref === $ref || substr($ref, 0 ,1) === '/' && preg_match($ref, $remote_ref)) {
    $is_matched = true;
}

if ($is_matched) {
    preg_match('/(?:.*\/)*(.*)/i', $remote_ref, $branch);
    $branch = $branch[1];
}
else {
    logs('忽略 ref：' . $remote_ref);
    die();
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


$cmd = 'sh ' . $hookfile . ' ' . $branch . ' ' . $project_dir . ' ' . $logfile;

logs('运行脚本：' . $cmd);
exec($cmd);
