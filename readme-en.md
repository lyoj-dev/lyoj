<p align="center"><img src="https://cdn.jsdelivr.net/gh/LittleYang0531/image/lyoj/logo.png"></p>

<p align="center">A multi-platform Online Judge System. Built with PHP and C++.</p>

------

[简体中文](./readme.md) [English](./readme-en.md)

## Features

1. Use C++ as backend language. It can boost the speed that Judge System running.
2. Use popular MySQL/MariaDB as database language. It is easier to learn.
3. Use PHP as frontend language. It makes the feature of website stronger.
4. The Markdown Visual Editor, which was modified from the repository named Editor.md. Correct some of the syntax parsing errors occurred in the original repository. Adjust Latex parsing format to make it easier to migrate the problem from other online judge platform. 
5. Support to build it multi-platformly and multi-architecturely, even an old mobile phone also can build it successfully.
6. Support the source format of Special Judge in application LemonLime. We provide support for Tastlib.h.
7. The modular writing of PHP makes it easier to extend it.
8. Support File Input/Output or Standard Input/Output.
9. Waiting to update......

## Preview

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

## Build

The system encironment that LYOJ can support theorily: `Windows10+ x64`，`Ubuntu20.04+ amd64`，`Ubuntu20.04+ arm64`，`NOI Linux 2 amd64`，`Debian 10 amd64`，`Debian 10 arm64`，`CentOS 8 amd64`，`CentOS 8 arm64`，......

The system environment that developers tested successfully: `Windows10 x64`，`Ubuntu20.04 amd64`，`Ubuntu20.04 arm64`，`NOI Linux 2 amd64`，`CentOS 8 amd64`。Because of the differences between the distributions of Linux, `CentOS 8 amd64` only can be built with Manual Configuration.

The system environment that not be tested above isn't means cannot be built. One of the possible reason is we don't have ability to tested them on these system. Please test it accroding to your own device's conditions.

### Dependences

Necessaries: PHP8.0+，MySQL/MariaDB，nginx/apache/IIS，g++，MySQL Connector，libjson。Website applications such as nginx/apache/IIS all are OK.

For Windows, all of the necessaries will be released in page Release.

For Ubuntu/NOI Linux 2/Debian, just input `sudo apt install mariadb-server mariadb-client nginx g++ libmysqlclient-dev libjsoncpp-dev -y` can install all of the necessaries except PHP8.0+. When it comes to PHP8.0+, it will be released in Release page when the new version released. You only need to install the compressed packages that correspond to your system architecture. 

For CentOS. Because the package source is too old, all of the necessaries except nginx not match the lowest version requirements that can run normally. You need to change unofficial package source or compile them by yourself.  

Others: Python2，Python3，openjdk8，openjdk11，openjdk13，openjdk16，openjdk17，PHP7.4，fpc。

### Automatically

For Windows, we didn't provide one-click configuration option. Please build it accroding to the manual configuration option below.

For Ubuntu, after getting the whole repositories, enter the main directory of the repository. Then input the command below, and input infomations accroding to the prompt infomation. After the success appeared, your Online Judge system was built successfully!

```bash
root@root:~/lyoj$ sudo g++ judgemgr.cpp -o /usr/bin/judgemgr -lmysqlclient -ljsoncpp -O2
root@root:~/lyoj$ judgemgr build
```

### Manual

After configurating the dependences, get the repository and enter the main directory.

1. Create the main directory of the data. For Linux,the main directory is `/etc/judge/`, For Windows，the main directory is `C://judge/`

2. Create a new database in MySQL/MariaDB. Then input `source init.sql` to initialize the database。
3. Exit database. Enter the database name and some infomations about MySQL/MariaDB in the array `mysql` of `config.json`.
4. Copy 'web' and 'crontab' directory in source directory to the main directory of data.
5. Configure the configuration of the website application.
6. Create 'contest', 'problem', 'spjtemp', 'tmp' directory in main directory of data.
7. Write website announcement and email format in `announcement.md` and `email.html`, and copy them to the main directory of data.
8. Write infomation such as email address, email password, email server in the array `controller > register > configs` in`config.json`. Then copy  `config.json` to the main directory of data.
9. Compile all the source file in 'spjtemp' directory, and put them to the 'spjtemp' directory in the main directory of data. The compile command is `g++ A.cpp -o A(.exe)`(A is refer to the file's name, and only Windows need to add the content in bracket).
10. Compile `judge.cpp`。
    1. For Windows, if you use applications in Release to build, the compile command is `g++ judge.cpp -g -o judge.exe -lpsapi -lmysql -ljson -pthread `. Else please compile it accroding to the compile parameters that each applications provide.
    2. For Ubuntu, the compile command is `sudo g++ judge.cpp -g -o /usr/bin/judge -lmysqlclient -ljsoncpp -lpthread`
    3. For CentOS, please compile it accroding to the compile parameters that each applications provide.
11. Input `sudo judge` or `./judge.exe` to run the judge.
12. Enter to the website, and configure it accroding to the prompt.

###  Update

For configuration automatically, only input  `judgemgr update` can finish all the update tasks.

For configuration manually, you need to save all the data, and reconfigure it.

## 3rd-parties

Thanks to the 3rd-party open source components below for their help with this project (in no particular order): 

- [PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)

- [Semantic-Org/Semantic-UI](https://github.com/Semantic-Org/Semantic-UI)
- [pandao/editor.md](https://github.com/pandao/editor.md)
- [highlightjs/highlight.js](https://github.com/highlightjs/highlight.js)
- [jquery/jquery](https://github.com/jquery/jquery)
- [KaTeX/KaTeX](https://github.com/KaTeX/KaTeX)
- [layui/layui](https://github.com/layui/layui)
- [microsoft/monaco-editor](https://github.com/microsoft/monaco-editor)

And [luogu-dev/markdown-palettes](https://github.com/luogu-dev/markdown-palettes) that used in the past.~~（Although in the end it was abandoned due to its too weakly features）~~

## License

This project is based on AGPL v3 to open source.

When you build LYOJ, you need to retain at least the word 'Powered by lyoj' at the bottom of the website, where the word 'lyoj' points to `https://github.com/LittleYang0531/lyoj`.

If you make changes to the source code, you also need to open source it in AGPL v3, which you can indicate in the footer in the format 'Powered by lyoj, Modified by xxx'.

In view of the [Discordant events](https://github.com/mamoe/mirai/issues/850) at Mirai, the following statement is made for this project:

- The fact that a project is open source does not obligate the developer to provide you with the services.
- Please read "The Wisdom of Asking Questions" before asking questions.
- If necessary, the developer reserves the right to discontinue any technical support for you.

If you are uncomfortable with the above items, we recommend that you discontinue using this item.