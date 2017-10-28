# GitLab Web Hook For PHP And Nginx

为每个提交到远程的分支生成独立的测试环境。

通过自动化脚本，将新提交的分支自动 `git clone` 到指定目录，并根据模板生成并添加 Nginx 配置文件，最后重启 Nginx。

为避免服务器频繁的 `git clone` 影响服务器**磁盘I/O**和**网络开销**，脚本会保留若干个已经被删除的分支的文件夹，有新分支提交时，将不用重新克隆整个项目，而只要切换分支并且拉取更新即可。

## 开始使用

- [Git](https://git-scm.com/)：需要支持 `-C` 命令
- [PHP](http://php.net/downloads.php)：需要有 `exec` 函数执行权限，使用前需要将 `exec` 函数从 `disable_functions` 列表中移除。
- [Nginx](https://nginx.org/en/download.html)
- 会适当地降低服务器安全性，见**权限配置**一节

### 1. 克隆 `GitLab-WebHooks-PHP-Plus`

可以将本脚本 `git clone` 到任何地方，但需要确保可以使用 http 访问。

```shell
git clone git@github.com:ttionya/GitLab-WebHooks-PHP-Plus.git
cd GitLab-WebHooks-PHP-Plus
```

### 2. 配置 `settings.php` 文件

使用编辑器编辑 `settings.php` 文件

#### 可编辑选项有：
- **$project_dir**： 存放项目的父目录路径，绝对路径，不存在将自动生成
- **$domain**： 自动部署时使用的上级域名，子域名由脚本自动生成并部署到 Nginx 配置目录。**你需要将该域名的子域名 DNS 指向部署该脚本的服务器，详见 `settings.php`**
- **$token**： Token，用于鉴定来源是否合法。不仅需要在 GitLab 控制台的 `Secret Token` 中添加，还需要在 `URL` 中添加，例如 `http://example.com/?token=xxxxxxx`
- **$repo_name**： 用于鉴定来源仓库是否合法，格式 `username/reponame`
- **$ref**： 支持 `*` 指定任何分支，字符串 `refs/heads/master` 指定具体分支，正则表达式 `/^refs\/heads\/(master|dev)$/i` 匹配多个分支
- **$special_branches**： 可以为指定分支设置特殊的子域名
- **$nginx_bin_path**： Nginx 执行文件路径
- **$nginx_conf_path**： 保存脚本自动生成的 Nginx 配置文件的独立的目录
- **$nginx_template_file**： Nginx 模板文件

#### 不可编辑选项有：
- **$hookfile**： 需要执行的 shell 脚本的地址
- **$logfile**： 日志文件，默认为 `hook.log`
- **$branchfile**： 记录分支激活 / 空闲的文件
- **$free_branch_limit**： 最大空闲分支数量，超过该数量会删除多余的文件夹

### 3. 配置 GitLab 项目的 Webhooks

**GitLab 控制台 => 选择项目 => Setting => Integrations 添加 Webhooks**

举个栗子：
```text
URL: http://example.com/?token=9998877

Secret Token: 9998877
```

### 4. 权限配置

大多数 Web Server 都会使用一个低权限的用户运行以确保安全，所以需要先确定 Web Server 使用的用户。

#### Apache：

```shell
$ ps -ef | grep httpd
root       2643      1  0 Jul09 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7188   2643  0 03:03 ?        00:00:04 /usr/local/apache/bin/httpd -k start
www        7189   2643  0 03:03 ?        00:00:04 /usr/local/apache/bin/httpd -k start
www        7190   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7191   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
www        7192   2643  0 03:03 ?        00:00:02 /usr/local/apache/bin/httpd -k start
```

#### Nginx：

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

#### 设置项目目录、Webhooks 目录和 Nginx 配置目录的权限

**`www` 用户必须有项目目录、Webhooks 目录和 Nginx 配置目录的写入权限，否则脚本无法正常运行。**

```shell
chown -R www:www project
chown -R www:www GitLab-WebHooks-PHP-Plus
chown -R www:www /usr/local/nginx/conf/gitlab-hook
```

#### 设置 `/etc/sudoers`

Nginx 启动用户一般为 `root`，所以重启 Nginx 需要 `root` 权限，但是脚本中使用 `sudo` 命令需要输入密码。

```shell
echo 'www  ALL=(root)  NOPASSWD: /usr/local/nginx/sbin/nginx' >> /etc/sudoers
```

设置完上面后，运行脚本会发现有 `sorry, you must have a tty to run sudo` 报错，此时需要将以下内容注释。

```shell
sed -i 's/^Defaults\(.*\)requiretty/#Defaults\1requiretty/' /etc/sudoers
```

### 5. 测试服务器是否通信成功

在 GitLab 控制台添加后点击 `Test` 按钮，查看日志文件是否被创建并包含内容。

## 说明

- 分支名与子域名不是完全的对应关系，脚本会将 `\W` 替换为 `-`，请避免使用此类分支，如 `feature.xxx` 和 `feature@xxx` 都将替换为 `feature-xxx`


## 许可证

MIT
