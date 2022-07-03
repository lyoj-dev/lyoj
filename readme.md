<p align="center"><img src="https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/logo.png"></p>

<p align="center">一个多平台的在线评测系统，使用 PHP 和 C++ 构建。</p>

------

[简体中文](./readme.md) [English](./readme-en.md)

## 功能简介

1. 使用 C++ 作为后端语言，加快评测机的运行速度。
2. 使用流行的 MySQL/MariaDB 作为数据库开发语言，简单易上手。
3. 使用 PHP 作为前端开发语言，网站功能更加强大。
4. 由 Editor.md 项目稍作改造而成的 Markdown 可视化编辑器，对原项目中出现的部分语法解析错误进行修正，并调整 Latex 解析格式，题目迁移更加简便。
5. 支持多平台多架构进行项目搭建，甚至一台破旧手机也能够完成本项目的部署。
6. 支持 LemonLime 软件中 Special Judge 源文件的格式，同样支持 Testlib.h 格式。
7. PHP 模块化编写，让网站更易容易扩展。
7. 支持文件输入输出和标准输入输出两种输入输出方式。
8. 更新中......

## 项目预览

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/1.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/2.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/3.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/4.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/5.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/6.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/7.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/8.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/9.jpg)

![](https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/10.jpg)

## 部署指南

目前 LYOJ 理论支持的系统有: `Windows10+ x64`，`Ubuntu20.04+ amd64`，`Ubuntu20.04+ arm64`，`NOI Linux 2 amd64`，`Debian 10 amd64`，`Debian 10 arm64`，`CentOS 8 amd64`，`CentOS 8 arm64`，......

实际开发者测试成功的系统有: `Windows10 x64`，`Ubuntu20.04 amd64`，`Ubuntu20.04 arm64`，`NOI Linux 2 amd64`，`CentOS 8 amd64`。其中由于 Linux 各发行版之间的差异，`CentOS 8 amd64`只有手动部署的情况下能够部署成功。

以上未经测试过的系统并不代表无法部署，可能只是开发者家里没有相应的设备而未完成测试，请根据自己设备情况进行测试。

### 系统依赖

必要软件包: PHP8.0+，MySQL/MariaDB，nginx/apache/IIS，g++，MySQL Connector，libjson。其中网站端无论是使用 nginx/apache/IIS 都能运行。

对于 Windows 用户，所有必须软件都会发布在 Release 界面里。

对于 Ubuntu/NOI Linux 2/Debian 用户，您只需要输入 `sudo apt install mariadb-server mariadb-client nginx g++ libmysqlclient-dev libjsoncpp-dev -y` 就能安装除 PHP8.0+ 以外的必要软件包。至于 PHP8.0+，在每个版本发布时的 Release 界面都有着已经配置好了的 PHP8.0+，下载对应系统版本的压缩包，解压后开箱即用。

对于 CentOS 用户，很抱歉由于软件源比较古老，除 nginx 以外的所有软件包都无法符合能够正常运行的最低版本要求，需要您自行更换非官方软件源或自行编译所有必要软件包。

可选软件包: Python2，Python3，openjdk8，openjdk11，openjdk13，openjdk16，openjdk17，PHP7.4，fpc。

### 一键脚本

我们提供了针对于 Ubuntu 用户的一键安装脚本，对于 Ubuntu 用户，您只需要输入 

```bash
sudo bash -c "$( curl -L https://raw.fastgit.org/LittleYang0531/lyoj/dev/script/install.sh )"; # 国内用户输入这行
sudo bash -c "$( curl -L https://raw.githubusercontent.com/LittleYang0531/lyoj/dev/script/install.sh )"; # 国外用户输入这行
```

并按照脚本内的提示完成安装即可。

除 Ubuntu 以外的用户均只能通过手动安装来进行安装。

备注:

1. 我们正在准备制作适用于 CentOS 和 ArchLinux 的一键安装脚本，但由于机子有限，开发进程可能会延后。
2. Windows 的安装程序应该会比 CentOS 和 ArchLinux 先发布。
3. 由于 CentOS 软件源过于古老，一键安装脚本不一定能制作成功。

### 一键配置

对于 Windows 用户，我们并没有提供一键配置选项，请依据下面的手动配置选项进行配置

对于 Ubuntu 用户，拉取完完整的项目包后，进入项目主目录，输入以下命令后，按照提示内容输入信息。等到出现 Success 后，配置完成。 

```bash
root@root:~/lyoj$ sudo g++ judgemgr.cpp -o /usr/bin/judgemgr -lmysqlclient -ljsoncpp -O2
root@root:~/lyoj$ judgemgr build
```

### 手动配置

配置好系统依赖环境后，拉取源码包，并进入源码主目录。

1. 建立项目的主文件夹，对于 Linux，项目主文件夹为 `/etc/judge/`，对于 Windows，项目主文件夹为 `C://judge/`

2. 在 MySQL/MariaDB 中建立一个新数据库，并输入 `source init.sql` 初始化数据库。
3. 退出数据库，数据库的名字以及 MySQL/MariaDB 的相关信息填入到配置文件 `config.json` 中的 `mysql` 数组下。
4. 复制源码中的 web 文件夹和 crontab 文件夹到项目的主文件夹中。
5. 配置网站端的配置文件，具体配置方法见各网站端官网上的教程。
6. 在项目主文件夹中建立 contest，problem，spjtemp，tmp 这四个文件夹。
7. 在 `announcement.md` 和 `email.html` 中分别写入网站公告和邮件格式，并将这两个文件复制项目主文件夹中。
8. 将邮箱地址，邮箱访问码，邮件服务器地址等信息填入`config.json` 中的 `controllers > register > configs` 数组下，复制 `config.json` 到项目主目录下。 
9. 编译源码 spjtemp 目录下所有的 C++ 源文件，并将它们放到项目主文件夹下的 spjtemp 文件夹下，编译命令为 `g++ A.cpp -o A(.exe)`，其中 A 为源文件名，并且括号中的内容只有在 Windows 下方可添加。
10. 编译 `judge.cpp`。
    1. 对于 Windows 用户，若使用 Release 中的软件包进行配置，编译指令为 `g++ judge.cpp -g -o judge.exe -lpsapi -lmysql -ljson -pthread `，否则请遵循各软件包提供的编译参数进行编译。
    2. 对于 Ubuntu 用户，编译指令为 `sudo g++ judge.cpp -g -o /usr/bin/judge -lmysqlclient -ljsoncpp -lpthread`
    3. 对于 CentOS 用户，请根据网上所提供的各软件包的编译参数进行编译。
11. 输入 `sudo judge` 或`./judge.exe` 来运行后台评测机程序。
12. 进入网页端，按照提示进行配置即可。

###  更新

对于一键配置的用户，只需要输入 `judgemgr update` 便可完成所有更新进程。

对于手动配置的用户，您需要保存所有数据，重新拉取源码包进行配置。

## 第三方组件

感谢以下的第三方开源组件对本项目的帮助 (排名不分先后): 

- [PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)

- [Semantic-Org/Semantic-UI](https://github.com/Semantic-Org/Semantic-UI)
- [pandao/editor.md](https://github.com/pandao/editor.md)
- [highlightjs/highlight.js](https://github.com/highlightjs/highlight.js)
- [jquery/jquery](https://github.com/jquery/jquery)
- [KaTeX/KaTeX](https://github.com/KaTeX/KaTeX)
- [layui/layui](https://github.com/layui/layui)
- [microsoft/monaco-editor](https://github.com/microsoft/monaco-editor)

以及曾经使用过的 [luogu-dev/markdown-palettes](https://github.com/luogu-dev/markdown-palettes) ~~（虽然最后由于功能太弱放弃使用了）~~

## 开源许可

本项目基于 AGPL v3 开源。

在您部署 LYOJ 时，最少需要保留底部的 `Powered by lyoj` 字样，其中的 `lyoj` 字样需指向 `https://github.com/LittleYang0531/lyoj`。

若您对源码做出修改，同样需要以 AGPL v3 开源，您可以以 `Powered by lyoj, Modified by xxx` 格式在页脚注明。

鉴于 Mirai 处的 [不和谐事件](https://github.com/mamoe/mirai/issues/850) 对此项目做如下声明：

- 项目开源不代表开发者有义务为您提供服务。
- 在提问前请先阅读《提问的智慧》。
- 若有必要，开发者有权对您停止任何技术支持。

如果您对以上条目感到不适，建议您停止使用本项目。