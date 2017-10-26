# GitLab Web Hook For PHP

## 开始使用

- [Git](https://git-scm.com/)：需要支持 `-C` 命令
- [PHP](http://php.net/downloads.php)：需要有 `exec` 函数执行权限，使用前需要将 `exec` 函数从 `disable_functions` 列表中移除。

### 1. 克隆项目文件

```shell
git clone git@gitlab.com:project/project.git project
```

### 2. 克隆 `gitlab-webhooks-php`

可以将本脚本 ` git clone` 到任何地方，但需要确保可以使用 http 访问。

```shell
git clone git@github.com:ttionya/gitlab-webhooks-php.git
cd gitlab-webhooks-php
```

### 3. 配置 `gitlab.php` 文件

使用编辑器编辑 `gitlab.php` 文件

可编辑选项有：
- $hookfile： 需要执行的 shell 脚本的地址，默认为 `hook.sh`
- $logfile： 日志文件，默认为 `hook.log`
- $project_dir： 项目路径，建议为绝对路径
- $token： Token，用于鉴定来源是否合法。不仅需要在 GitLab 控制台的 `Secret Token` 中添加，还需要在 `URL` 中添加，例如 `http://example.com/?token=xxxxxxx`
- $ref： 根据分支判断是否需要执行 `git pull` 操作。支持 `*` 指定任何分支，字符串 `refs/heads/master` 指定具体分支，正则表达式 `/^refs\/heads\/(master|dev)$/i` 匹配多个分支

### 4. 配置 GitLab 项目的 Webhooks

**GitLab 控制台 => 选择项目 => Setting => Integrations 添加 Webhooks**

举个栗子：
```text
URL: http://example.com/?token=9998877

Secret Token: 9998877
```

### 5. 权限配置

大多数 Web Server 都会使用一个低权限的用户运行以确保安全，所以需要先确定 Web Server 使用的用户。

##### Apache：

```shell
$ ps -ef | grep httpd
root       2643      1  0 Jul09 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7188   2643  0 03:03 ?        00:00:04 /usr/local/apache/bin/httpd -k start
www        7189   2643  0 03:03 ?        00:00:04 /usr/local/apache/bin/httpd -k start
www        7190   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7191   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7192   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
```

##### Nginx：

```shell
$ ps -ef | grep php
root      2689     1  0 17:05 ?        00:00:00 php-fpm: master process (/usr/local/php/etc/php-fpm.conf)
www       2690  2689  0 17:05 ?        00:00:00 php-fpm: pool www
www       2691  2689  0 17:05 ?        00:00:00 php-fpm: pool www
www       2692  2689  0 17:05 ?        00:00:00 php-fpm: pool www
www       2693  2689  0 17:05 ?        00:00:00 php-fpm: pool www
```

**此时使用的是低权限用户 `www`**

则调用 SHELL 的用户就是 `www`，所以需要为 `www` 用户添加 SSH 密钥。使用下面命令创建密钥，保存在 `~/.ssh/id_rsa` 中：

```shell
ssh-keygen -t rsa -b 2048
```

把生成的 `id_rsa.pub` 粘贴到 GitLab 控制台中。

##### 设置项目目录和 Webhooks 目录的权限

```shell
chown -R www:www project
chown -R www:www gitlab-webhooks-php
```

### 6. 测试服务器是否通信成功

在 GitLab 控制台添加后点击 `Test` 按钮，查看日志文件是否被创建并包含内容。

## 致谢

- [https://github.com/bravist/gitlab-webhook-php/](https://github.com/bravist/gitlab-webhook-php/)
- [https://gitlab.com/kpobococ/gitlab-webhook](https://gitlab.com/kpobococ/gitlab-webhook)

## 许可证

MIT