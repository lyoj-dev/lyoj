#!/usr/bin/env bash

BLACK='\e[0;30m';
RED='\e[0;31m';
GREEN='\e[0;32m';
YELLOW='\e[0;33m';
BLUE='\e[0;34m';
PURPLE='\e[0;35m';
CYAN='\e[0;36m';
WHITE='\e[0;37m';

REPO_URL_ORIG="https://github.com/lyoj-dev/lyoj.git";
EXT_URL_ORIG="https://github.com/lyoj-dev/lyoj-ext.git";
LANG_URL_ORIG="https://github.com/lyoj-dev/lyoj-lang.git";
PHP8_URL_ORIG="https://github.com/php/php-src/archive/refs/tags/php-8.1.8.zip";
ONIG_URL_ORIG="https://github.com/kkos/oniguruma/releases/download/v6.9.7.1/onig-6.9.7.1.tar.gz";
REPO_URL=$REPO_URL_ORIG;
EXT_URL=$EXT_URL_ORIG;
LANG_URL=$LANG_URL_ORIG;
PHP8_URL=$PHP8_URL_ORIG;
ONIG_URL=$ONIG_URL_ORIG;
PROXY_URL="https://ghproxy.com/";
PHP_EXT=( "imap" "mbstring" "mysqli" "openssl" "posix" "sockets" "tidy" );
PHP_PARAM=(
    "--with-kerberos --with-imap-ssl"
    ""
    ""
    ""
    ""
    ""
    ""
);
PHP_PRE=(
    ""
    ""
    ""
    "mv config0.m4 config.m4"
    ""
    ""
    ""
);
SHELL_PATH=$( readlink -f $0 );
SHELL_PATH=${SHELL_PATH%/*}"/";

function check_root() {
    if [[ $(id -u) != 0 ]]; then 
        echo -e "$RED""无 root 权限，请使用 sudo bash install.sh 来运行脚本或切换到 root 用户来运行脚本";
        exit 0;
    fi
}

DISTRO_NAME=""
ARCH_TYPE=""

function check_sys() {
    if [[ -f /etc/redhat-release ]]; then
        DISTRO_NAME="CentOS";
    elif grep -q -E -i "debian" /etc/issue; then
        DISTRO_NAME="Debian";
    elif grep -q -E -i "ubuntu" /etc/issue; then
        DISTRO_NAME="Ubuntu";
    # elif grep -q -E -i "centos|red hat|redhat" /etc/issue; then
    #     DISTRO_NAME="CentOS";
    # elif grep -q -E -i "Arch|Manjaro" /etc/issue; then
    #     DISTRO_NAME="ArchLinux";
    elif grep -q -E -i "debian" /proc/version; then
        DISTRO_NAME="Debian";
    elif grep -q -E -i "ubuntu" /proc/version; then
        DISTRO_NAME="Ubuntu";
    # elif grep -q -E -i "centos|red hat|redhat" /proc/version; then
    #     DISTRO_NAME="CentOS";
    else
        echo -e "LYOJ 暂不支持该Linux发行版";
        exit 0;
    fi
    ARCH_TYPE=$(uname -m);
}

function install_php8() {
    echo -e "$CYAN""即将安装 PHP8...";

    if [[ $( command -v php8 ) != "" ]]; then 
        echo -e "$GREEN""检测到已经安装过 PHP8, 跳过安装!";
        return;
    fi

    # oniguruma 源代码安装
    echo -e "$CYAN""安装 Oniguruma""$WHITE";
    echo -e "$PURPLE""sudo ""$YELLOW""wget ""$RED""$ONIG_URL ""$GREEN""-O ""$RED""onig.tar.gz""$WHITE";
    wget $ONIG_URL -O onig.tar.gz ;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""oniguruma 源代码下载失败, 请检查您的网络连接";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""tar ""$GREEN""-zxvf ""$RED""onig.tar.gz ""$CYAN""> /dev/null""$WHITE";
    tar -zxvf onig.tar.gz > /dev/null;
    echo -e "$YELLOW""cd ""$RED""onig""$ONIG_VERSION""/""$WHITE";
    cd onig-6.9.7/;
    echo -e "$PURPLE""sudo ""$YELLOW""autoreconf ""$GREEN""-vfi""$WHITE";
    sudo autoreconf -vfi ;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""oniguruma 配置失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""./configure""$WHITE";
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""oniguruma 配置失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    sudo ./configure;
    echo -e "$PURPLE""sudo ""$YELLOW""make ""$GREEN""-j32 ""$CYAN""&& ""$PURPLE""sudo ""$YELLOW""make ""$BLUE""install""$WHITE";
    sudo make -j32 && sudo make install ;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""oniguruma 编译安装失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$YELLOW""cd ""$RED""../""$WHITE";
    cd ../;

    # PHP8 源代码安装
    echo -e "$CYAN""安装 PHP8 基础应用程序""$WHITE";
    echo -e "$PURPLE""sudo ""$YELLOW""wget ""$RED""$PHP8_URL ""$GREEN""-O php.zip""$WHITE";
    sudo wget $PHP8_URL -O php.zip;
    echo -e "$PURPLE""sudo ""$YELLOW""unzip ""$RED""php.zip""$WHITE";
    sudo unzip php.zip;
    echo -e "$PURPLE""sudo ""$YELLOW""mv ""$RED""php-src-* php-src""$WHITE";
    sudo mv php-src-* php-src;
    echo -e "$YELLOW""cd ""$RED""./php-src/""$WHITE";
    cd ./php-src/;
    echo -e "$PURPLE""sudo ""$YELLOW""./buildconf ""$GREEN""--force""$WHITE";
    sudo ./buildconf --force;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""PHP 配置失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""./buildconf""$WHITE";
    sudo ./buildconf;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""PHP 配置失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""./configure ""$GREEN""--prefix=/etc/judge/php/8 --sysconfdir=/etc/judge/php/8 --with-config-file-path=/etc/judge/php/8 --enable-fpm --enable-cgi --enable-mysqlnd""$WHITE";
    sudo ./configure --prefix=/etc/judge/php/8 --sysconfdir=/etc/judge/php/8 --with-config-file-path=/etc/judge/php/8 --enable-fpm --enable-cgi --enable-mysqlnd;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""PHP 配置失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""make ""$GREEN""-j32 ""$CYAN""&& ""$PURPLE""sudo ""$YELLOW""make ""$BLUE""install""$WHITE";
    sudo make -j32 && sudo make install;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""PHP 编译安装失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./php.ini-production ""$RED""/etc/judge/php/8/php.ini""$WHITE";
    sudo cp ./php.ini-production /etc/judge/php/8/php.ini;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""PHP 配置文件复制失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$YELLOW""cd ""$RED""../""$WHITE";
    cd ../; i=0;

    # PHP8 扩展程序安装
    for ext in "${PHP_EXT[@]}"; do
        echo -e "$CYAN""安装 PHP8 扩展程序 ""$ext""$WHITE";
        echo -e "$YELLOW""cd ""$RED""./php-src/ext/$ext""$WHITE";
        cd ./php-src/ext/$ext;
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""PHP8 扩展程序 $ext 未找到""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi;

        if [[ ${PHP_PRE[$i]} != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW"${PHP_PRE[$i]};
            sudo ${PHP_PRE[$i]};
            if [[ $( echo $? ) != 0 ]]; then    
                echo -e "$RED""PHP8 扩展程序 $ext 预处理失败""$WHITE";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
            fi
        fi

        echo -e "$PURPLE""sudo ""$YELLOW""/etc/judge/php/8/bin/phpize ""$GREEN""--force""$WHITE";
        sudo /etc/judge/php/8/bin/phpize --force
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""PHP8 扩展程序 $ext 配置失败""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi;

        echo -e "$PURPLE""sudo ""$YELLOW""./configure ""$GREEN""--with-php-config=/etc/judge/php/8/bin/php-config "${PHP_PARAM[$i]}"$WHITE";
        sudo ./configure --with-php-config=/etc/judge/php/8/bin/php-config ${PHP_PARAM[$i]};
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""PHP8 扩展程序 $ext 配置失败""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi;
        echo -e "$PURPLE""sudo ""$YELLOW""make ""$GREEN""-j32 ""$CYAN""&& ""$PURPLE""sudo ""$YELLOW""make ""$BLUE""install""$WHITE";
        sudo make -j32 && sudo make install;
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""PHP8 扩展程序 $ext 安装失败""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi;
        echo -e "$YELLOW""cd ""$RED""../../../""$WHITE";
        cd ../../../;
        i=$i+1;
    done
    
    echo -e "$PURPLE""sudo ""$YELLOW""ln ""$RED""/etc/judge/php/8/bin/php ""$RED""/usr/bin/php8 ""$GREEN""-s""$WHITE";
    sudo ln /etc/judge/php/8/bin/php /usr/bin/php8 -s;
    echo -e "$PURPLE""sudo ""$YELLOW""ln ""$RED""/etc/judge/php/8/bin/php-cgi ""$RED""/usr/bin/php-cgi8 ""$GREEN""-s""$WHITE";
    sudo ln /etc/judge/php/8/bin/php-cgi /usr/bin/php-cgi8;
    echo -e "$PURPLE""sudo ""$YELLOW""ln ""$RED""/etc/judge/php/8/sbin/php-fpm ""$RED""/usr/bin/php-fpm8 ""$GREEN""-s""$WHITE";
    sudo ln /etc/judge/php/8/sbin/php-fpm /usr/bin/php-fpm8 -s;
    echo -e "$PURPLE""sudo ""$YELLOW""mv ""$RED""/etc/judge/php/8/php-fpm.conf.default ""$RED""/etc/judge/php/8/php-fpm.conf""$WHITE";
    sudo mv /etc/judge/php/8/php-fpm.conf.default /etc/judge/php/8/php-fpm.conf;
    echo -e "$PURPLE""sudo ""$YELLOW""mv ""$RED""/etc/judge/php/8/php-fpm.d/www.conf.default ""$RED""/etc/judge/php/8/php-fpm.d/www.conf""$WHITE";
    sudo mv /etc/judge/php/8/php-fpm.d/www.conf.default /etc/judge/php/8/php-fpm.d/www.conf;
    echo -e "$PURPLE""sudo ""$YELLOW""groupadd ""$RED""nobody""$WHITE";
    sudo groupadd nobody;

    echo -e "$GREEN""PHP8 安装成功";
}

function start_database() {
    if [[ $( ps -ef | pgrep mysqld ) != "" || $( ps -ef | pgrep mariadbd ) != "" ]]; then 
        echo -e "$GREEN""检测到已经启动了 MySQL/MariaDB 服务器, 跳过启动步骤";
    else 
        if [[ $( command -v mysqld ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mysql ""$BLUE""start""$WHITE";
            sudo service mysql start;
        elif [[ $( command -v mariadbd ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mariadb ""$BLUE""start""$WHITE";
            sudo service mariadb start;
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""启动 MySQL/MariaDB 服务器失败""$WHITE";
            echo -e "$RED""请检查您的配置文件并修正, 如果是由于命令错误而导致的, 请自行启动 MySQL/MariaDB 服务器""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    fi
}

function install_lyoj() {
    echo -e "$CYAN""即将安装 LYOJ";

    echo -e "$PURPLE""sudo ""$YELLOW""git ""$BLUE""clone ""$RED""$REPO_URL""$WHITE";
    sudo git clone $REPO_URL;
    echo -e "$YELLOW""cd ""$RED""./lyoj/web""$WHITE";
    cd ./lyoj/web;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""./extensions ""$GREEN""-r""$WHITE";
    sudo rm ./extensions -r;
    echo -e "$PURPLE""sudo ""$YELLOW""git ""$BLUE""clone ""$RED""$EXT_URL ""$RED""extensions""$WHITE";
    sudo git clone $EXT_URL extensions;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""./lang ""$GREEN""-r""$WHITE";
    sudo rm ./lang -r;
    echo -e "$PURPLE""sudo ""$YELLOW""git ""$BLUE""clone ""$RED""$LANG_URL ""$RED""lang""$WHITE";
    sudo git clone $LANG_URL lang;
    echo -e "$YELLOW""cd ""$RED""../""$WHITE";
    cd ../;
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/etc/judge/""$WHITE";
    sudo mkdir /etc/judge/;
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/var/log/judge/""$WHITE";
    sudo mkdir /var/log/judge/;
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/etc/judge/contest/""$WHITE";
    sudo mkdir /etc/judge/contest/;
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./crontab ""$RED""/etc/judge/ ""$GREEN""-r""$WHITE";
    sudo cp ./crontab /etc/judge/ -r;
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/etc/judge/problem/""$WHITE";
    sudo mkdir /etc/judge/problem/;
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./spjtemp ""$RED""/etc/judge/ ""$GREEN""-r""$WHITE";
    sudo cp ./spjtemp /etc/judge/ -r
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/etc/judge/tmp/""$WHITE";
    sudo mkdir /etc/judge/tmp/;
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./web ""$RED""/etc/judge/ ""$GREEN""-r""$WHITE";
    sudo cp ./web /etc/judge/ -r;
    
    echo -e "$PURPLE""sudo ""$YELLOW""chmod ""$CYAN""0777 ""$RED""/etc/judge ""$GREEN""-R""$WHITE";
    sudo chmod 0777 /etc/judge -R;
    echo -e "$PURPLE""sudo ""$YELLOW""mkdir ""$RED""/var/log/judge/""$WHITE";
    sudo mkdir /var/log/judge/;
    
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./config.json ""$RED""/etc/judge/config.json""$WHITE";
    sudo cp ./config.json /etc/judge/config.json;
    echo -e "$PURPLE""sudo ""$YELLOW""cp ""$RED""./judge.cpp ""$RED""/etc/judge/judge.cpp""$WHITE";
    sudo cp ./judge.cpp /etc/judge/judge.cpp;

    start_database;

    DB_NAME="";
    DB_PASSWD="";
    while true; do 
        echo -e"$RED""* 本系统所安装的数据库用户名及密码均为root";
        echo -en "$WHITE""请输入数据库用户名: ";
        read -r DB_NAME;
        echo -en "$WHITE""请输入数据库密码: ";
        read -rs DB_PASSWD;
        echo -e "";
        echo -e "$WHITE""正在登录数据库...";
        DB_INFO="";
        if [[ $( command -v mysql ) != "" ]]; then 
            DB_INFO=$( mysql -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        elif [[ $( command -v mariadb ) != "" ]]; then 
            DB_INFO=$( mariadb -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $DB_INFO != "" ]]; then 
            echo -e "$RED""数据库登录失败, ""$DB_INFO";
        else 
            break;
        fi
    done

    DB_TYPE="";
    if [[ $( command -v mysql ) != "" ]]; then 
        DB_TYPE="mysql";
    elif [[ $( command -v mariadb ) != "" ]]; then 
        DB_TYPE="mariadb";
    else 
        echo -e "$RED""数据库组件不完整, 请重新安装!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""$DB_TYPE ""$GREEN""-u$DB_NAME -p*** -h127.0.0.1 -e\"CREATE DATABASE judge;\"""$WHITE";
    sudo $DB_TYPE -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e"CREATE DATABASE judge;";
    echo -e "$PURPLE""sudo ""$YELLOW""$DB_TYPE ""$GREEN""-u$DB_NAME -p*** -h127.0.0.1 -e\"use judge;source init.sql;\"""$WHITE";
    sudo $DB_TYPE -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e"use judge;source init.sql;";

    for name in $( find /etc/judge/spjtemp/* -name "*.cpp" | awk '{ print $1 }' ); do
        EXEC_NAME=${name%.*}; 
        echo -e "$PURPLE""sudo ""$YELLOW""g++ ""$RED""$name ""$GREEN""-o ""$GREEN""$EXEC_NAME ""$GREEN""-std=c++14 -O2""$WHITE";
        sudo g++ $name -o $EXEC_NAME --std=c++14 -O2;
        if [[ $( echo $? ) != 0 ]]; then  
            echo -e "$RED""SPJ 模板 $EXEC_NAME 编译失败"; 
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    done

    echo -e "$PURPLE""sudo ""$YELLOW""g++ ""$RED""/etc/judge/judge.cpp ""$GREEN""-o ""$GREEN""/usr/bin/judge ""$GREEN""-lmysqlclient -ljsoncpp -lpthread -std=c++14 -O2""$WHITE";
    sudo g++ /etc/judge/judge.cpp -o /usr/bin/judge -lmysqlclient -ljsoncpp -lpthread -std=c++14 -O2
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""LYOJ 后台程序编译失败";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi 
}

function install_database() {
    echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""mariadb-client mariadb-server ""$GREEN""-y""$WHITE";
    sudo apt install mariadb-server mariadb-client -y;
    if [[ $( echo $? ) != 0 ]]; then 
        echo -e "$RED""MariaDB 安装失败, 尝试安装 MySQL!""$WHITE";
        echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""mysql-client mysql-server ""$GREEN""-y""$WHITE";
        sudo apt install mysql-client mysql-server -y;
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""MySQL 安装失败, 建议自行安装 MySQL/MariaDB, 然后重新运行此脚本";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    fi

    DB_TYPE="";
    if [[ $( command -v mysql ) != "" ]]; then 
        DB_TYPE="mysql";
    elif [[ $( command -v mariadb ) != "" ]]; then 
        DB_TYPE="mariadb";
    else 
        echo -e "$RED""数据库组件不完整, 请重新安装!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    start_database;
    echo -e "$PURPLE""sudo ""$YELLOW""$DB_TYPE ""$GREEN""-e\"set password for root@localhost=password('root');\"";
    sudo $DB_TYPE -e"set password for root@localhost=password('root');";
}

function install() {
    clear;

    echo -e "$WHITE""系统名称: ""$DISTRO_NAME";
    echo -e "$WHITE""系统架构: ""$ARCH_TYPE";

    echo -e "";
    echo -en "$WHITE""请选择是否需要使用 Github 代理? [Y/N, Default: N]";
    read -r use;
    if [[ $use == "Y" ]]; then 
        PHP8_URL="$PROXY_URL""$PHP8_URL_ORIG";
        REPO_URL="$PROXY_URL""$REPO_URL_ORIG";
        EXT_URL="$PROXY_URL""$EXT_URL_ORIG";
        ONIG_URL="$PROXY_URL""$ONIG_URL_ORIG";
    else
        PHP8_URL="$PHP8_URL_ORIG";
        REPO_URL="$REPO_URL_ORIG";
        EXT_URL="$EXT_URL_ORIG";
        ONIG_URL="$ONIG_URL_ORIG";
    fi
    
    if [[ $DISTRO_NAME != "Ubuntu" && $DISTRO_NAME != "Debian" ]]; then 
        echo -e "$RED""此安装脚本暂时不支持此系统, 请自行手动安装!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi

    echo -e "";
    echo -e "$CYAN""正在更新软件源...";
    case "$DISTRO_NAME" in  
    "Ubuntu"|"Debian") 
        echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""update""$WHITE";
        sudo apt update
    ;;
    "CentOS")
        echo -e "$PURPLE""sudo ""$YELLOW""yum ""$BLUE""update""$WHITE";
        sudo yum update
    ;;
    # "ArchLinux")
    #     sudo
    # ;;
    esac;

    echo -e "$CYAN""正在安装依赖包...";
    case "$DISTRO_NAME" in  
    "Ubuntu"|"Debian") 
        if [[ $( command -v g++ ) != "" ]]; then 
            echo -e "$GREEN""检测到已经安装过 g++, 跳过安装""$WHITE";
        else 
            echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""g++ ""$GREEN""-y""$WHITE";
            sudo apt install g++ -y;
            if [[ $( echo $? ) != 0 ]]; then
                echo -e "$RED""g++ 安装失败""$WHITE";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
                main_face;
            fi
        fi

        if [[ $( command -v git ) != "" ]]; then 
            echo -e "$GREEN""检测到已经安装过 git, 跳过安装""$WHITE";
        else 
            echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""git ""$GREEN""-y""$WHITE";
            sudo apt install git -y;
            if [[ $( echo $? ) != 0 ]]; then
                echo -e "$RED""git 安装失败""$WHITE";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
                main_face;
            fi
        fi

        if [[ $( command -v make ) != "" ]]; then 
            echo -e "$GREEN""检测到已经安装过 make, 跳过安装""$WHITE";
        else 
            echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""make ""$GREEN""-y""$WHITE";
            sudo apt install make -y;
            if [[ $( echo $? ) != 0 ]]; then
                echo -e "$RED""make 安装失败""$WHITE";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
                main_face;
            fi
        fi

        if [[ $( command -v mysqld ) != "" || $( command -v mariadbd ) ]]; then
            echo -e "$GREEN""检测到已经安装过 MySQL/MariaDB, 跳过安装""$WHITE";
        else 
            install_database;
        fi

        echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""libmysqlclient-dev libjsoncpp-dev ""$GREEN""-y""$WHITE";
        sudo apt install libmysqlclient-dev libjsoncpp-dev -y;
        if [[ $( echo $? ) != 0 ]]; then
            echo -e "$RED""libmysqlclient, libjsoncpp 安装失败""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi

        echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""pkg-config build-essential autoconf bison re2c libxml2-dev libsqlite3-dev libzip-dev libcurl4-gnutls-dev libtidy-dev libxslt1-dev libenchant-dev libc-client2007e-dev libkrb5-dev libldap2-dev unixodbc-dev libmysqlclient-dev freetds-dev libpspell-dev libreadline-dev librttr-dev libsnmp-dev libsocket++-dev libsodium-dev spl libsqlite3-dev zlib1g-dev libargon2-dev libedit-dev ""$GREEN""-y""$WHITE";
        sudo apt install pkg-config build-essential autoconf bison re2c libxml2-dev libsqlite3-dev libzip-dev libcurl4-gnutls-dev libtidy-dev libxslt1-dev libenchant-dev libc-client2007e-dev libkrb5-dev libldap2-dev unixodbc-dev libmysqlclient-dev freetds-dev libpspell-dev libreadline-dev librttr-dev libsnmp-dev libsocket++-dev libsodium-dev spl libsqlite3-dev zlib1g-dev libargon2-dev libedit-dev -y;
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""PHP 软件包依赖安装失败""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    ;;
    "CentOS")
        echo -e "$PURPLE""sudo ""$YELLOW""yum ""$BLUE""install ""$RED""g++ ""$GREEN""-y""$WHITE";
        sudo yum install g++ -y
    ;;
    # "ArchLinux")
    #     sudo
    # ;;
    esac;

    install_php8;
    install_lyoj;

    echo -e "$GREEN""安装完成!";
    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
    return;
}

function update() {
    clear; 

    echo -e "$RED""暂不支持此功能";
    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
    main_face;
}

function uninstall() {
    clear;

    echo -en "$WHITE""请确认是否需要卸载 LYOJ & PHP8 & nginx? [Y/N, Default: N]";
    read -r confirm;
    echo -e "";
    if [[ $confirm != "Y" ]]; then  
        echo -e "$GREEN""已取消卸载进程!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi

    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/etc/judge ""$GREEN""-r""$WHITE";
    sudo rm /etc/judge -r;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/var/log/judge ""$GREEN""-r""$WHITE";
    sudo rm /var/log/judge -r;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/usr/bin/judge""$WHITE";
    sudo rm /usr/bin/judge;
    echo -e "$GREEN""LYOJ 卸载成功!""$WHITE";

    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/usr/bin/php8""$WHITE";
    sudo rm /usr/bin/php8;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/usr/bin/php-fpm8""$WHITE";
    sudo rm /usr/bin/php-fpm8;
    echo -e "$PURPLE""sudo ""$YELLOW""rm ""$RED""/usr/bin/php-cgi8""$WHITE";
    sudo rm /usr/bin/php-cgi8;
    echo -e "$GREEN""PHP8 卸载成功!""$WHITE";

    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
    main_face;
}

function backup() {
    clear;

    echo -e "$YELLOW""提示: 即将将 LYOJ 所有数据备份到 ""$SHELL_PATH""judge.zip""$WHITE";
    echo -e "";

    if [[ $( command -v zip ) == "" || $( command -v unzip ) == "" ]]; then  
        echo -e "$CYAN""检测到未安装 zip/unzip, 即将为您安装""$WHITE";

        case "$DISTRO_NAME" in  
        "Debian"|"Ubuntu") 
            echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""zip unzip ""$GREEN""-y""$WHITE";
            sudo apt install zip unzip -y;
            if [[ $( echo $? ) != 0 ]]; then  
                echo -e "$RED""zip/unzip 安装失败, 请自行下载文件安装, 然后重启脚本";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
                main_face;
            else  
                echo -e "$GREEN""zip/unzip 安装成功";
            fi
        esac
    fi

    if [[ $( ps -ef | pgrep mysqld ) != "" || $( ps -ef | pgrep mariadbd ) != "" ]]; then 
        echo -e "$GREEN""检测到已经启动了 MySQL/MariaDB 服务器, 跳过启动步骤";
    else 
        if [[ $( command -v mysqld ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mysql ""$BLUE""start""$WHITE";
            sudo service mysql start;
        elif [[ $( command -v mariadbd ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mariadb ""$BLUE""start""$WHITE";
            sudo service mariadb start;
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""启动 MySQL/MariaDB 服务器失败""$WHITE";
            echo -e "$RED""请检查您的配置文件并修正, 如果是由于命令错误而导致的, 请自行启动 MySQL/MariaDB 服务器""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    fi

    DB_NAME="";
    DB_PASSWD="";
    while true; do 
        echo -en "$WHITE""请输入数据库用户名: ";
        read -r DB_NAME;
        echo -en "$WHITE""请输入数据库密码: ";
        read -rs DB_PASSWD;
        echo -e "";
        echo -e "$WHITE""正在登录数据库...";
        DB_INFO="";
        if [[ $( command -v mysql ) != "" ]]; then 
            DB_INFO=$( mysql -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        elif [[ $( command -v mariadb ) != "" ]]; then 
            DB_INFO=$( mariadb -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $DB_INFO != "" ]]; then 
            echo -e "$RED""数据库登录失败, ""$DB_INFO";
        else 
            break;
        fi
    done

    echo -e "$YELLOW""cd ""$RED""/etc/judge""$WHITE";
    cd /etc/judge;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./contest/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./contest/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./crontab/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./crontab/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./problem/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./problem/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./spjtemp/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./spjtemp/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/data/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/data/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/files/* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/files/* -r;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/logo.png""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/logo.png;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/favicon.ico""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/favicon.ico;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/rsa_private_key.pem""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/rsa_private_key.pem;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./web/rsa_public_key.pem""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./web/rsa_public_key.pem;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./config.json""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./config.json;
    DB_TYPE="";
    if [[ $( command -v mysqldump ) != "" ]]; then 
        DB_TYPE="mysqldump";
    elif [[ $( command -v mariadb-dump ) != "" ]]; then 
        DB_TYPE="mariadb-dump";
    else 
        echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi
    echo -e "$PURPLE""sudo ""$YELLOW""$DB_TYPE"" ""$GREEN""-u$DB_NAME -p$DB_PASSWD -h127.0.0.1 ""$RED""judge ""$CYAN""> judge.sql""$WHITE";
    sudo "$DB_TYPE" -u"$DB_NAME" -p"$DB_PASSWD" -h127.0.0.1 judge > judge.sql;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./judge.sql""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./judge.sql;
    echo -e "$YELLOW""cd ""$RED""/var/log/judge""$WHITE";
    cd /var/log/judge;
    echo -e "$PURPLE""sudo ""$YELLOW""zip ""$RED""$SHELL_PATH""judge.zip ""$RED""./* ""$GREEN""-r""$WHITE";
    sudo zip "$SHELL_PATH""judge.zip" ./* -r;
    echo -e "$GREEN""LYOJ 备份成功";

    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
    main_face;
}

function restore() {
    clear;

    echo -e "$YELLOW""提示: 即将还原 LYOJ 数据, 需要清空所有数据库信息""$WHITE";
    echo -e "";

    echo -en "$WHITE""请确认是否需要还原 LYOJ 数据? [Y/N, Default: N]";
    read -r confirm;
    echo -e "";

    if [[ $confirm != "Y" ]]; then  
        echo -e "$GREEN""已取消还原进程!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi

    if [[ $( ps -ef | pgrep mysqld ) != "" || $( ps -ef | pgrep mariadbd ) != "" ]]; then 
        echo -e "$GREEN""检测到已经启动了 MySQL/MariaDB 服务器, 跳过启动步骤";
    else 
        if [[ $( command -v mysqld ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mysql ""$BLUE""start""$WHITE";
            sudo service mysql start;
        elif [[ $( command -v mariadbd ) != "" ]]; then 
            echo -e "$PURPLE""sudo ""$YELLOW""service ""$RED""mariadb ""$BLUE""start""$WHITE";
            sudo service mariadb start;
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $( echo $? ) != 0 ]]; then 
            echo -e "$RED""启动 MySQL/MariaDB 服务器失败""$WHITE";
            echo -e "$RED""请检查您的配置文件并修正, 如果是由于命令错误而导致的, 请自行启动 MySQL/MariaDB 服务器""$WHITE";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
    fi

    if [[ $( command -v zip ) == "" || $( command -v unzip ) == "" ]]; then  
        echo -e "$CYAN""检测到未安装 zip/unzip, 即将为您安装""$WHITE";
        case "$DISTRO_NAME" in  
        "Debian"|"Ubuntu") 
            echo -e "$PURPLE""sudo ""$YELLOW""apt ""$BLUE""install ""$RED""zip unzip ""$GREEN""-y""$WHITE";
            sudo apt install zip unzip -y;
            if [[ $( echo $? ) != 0 ]]; then  
                echo -e "$RED""zip/unzip 安装失败, 请自行下载文件安装, 然后重启脚本";
                echo -e "$WHITE""Press \"Enter\" to continue...";
                read -rp "";
                main_face;
            else  
                echo -e "$GREEN""zip/unzip 安装成功";
            fi
        esac
    fi
    
    DB_NAME="";
    DB_PASSWD="";
    while true; do 
        echo -en "$WHITE""请输入数据库用户名: ";
        read -r DB_NAME;
        echo -en "$WHITE""请输入数据库密码: ";
        read -rs DB_PASSWD;
        echo -e "";
        echo -e "$WHITE""正在登录数据库...";
        DB_INFO="";
        if [[ $( command -v mysql ) != "" ]]; then 
            DB_INFO=$( mysql -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        elif [[ $( command -v mariadb ) != "" ]]; then 
            DB_INFO=$( mariadb -u$DB_NAME -p$DB_PASSWD -h127.0.0.1 -e";" );
        else 
            echo -e "$RED""数据库组件不完整, 请重新安装!""$GREEN";
            echo -e "$WHITE""Press \"Enter\" to continue...";
            read -rp "";
            main_face;
        fi
        if [[ $DB_INFO != "" ]]; then 
            echo -e "$RED""数据库登录失败, ""$DB_INFO";
        else 
            break;
        fi
    done

    echo -e "$YELLOW""cd ""$RED""/etc/judge""$WHITE";
    cd /etc/judge;
    echo -e "$PURPLE""sudo ""$YELLOW""unzip ""$GREEN""-o ""$RED""$SHELL_PATH""judge.zip""$WHITE";
    sudo unzip -o "$SHELL_PATH""judge.zip";
    echo -e "$PURPLE""sudo ""$YELLOW""mv ""$RED""/etc/judge/info.log ""$RED""/var/log/judge/info.log""$WHITE";
    sudo mv /etc/judge/info.log /var/log/judge/info.log;
    echo -e "$PURPLE""sudo ""$YELLOW""mv ""$RED""/etc/judge/error.log ""$RED""/var/log/judge/error.log""$WHITE";
    sudo mv /etc/judge/error.log /var/log/judge/error.log;
    
    echo -e "$PURPLE""sudo ""$YELLOW""mysql ""$GREEN""-u$DB_NAME -p*** -h127.0.0.1 -e\"DROP DATABASE judge;\"""$WHITE";
    sudo mysql -u"$DB_NAME" -p"$DB_PASSWD" -h127.0.0.1 -e"DROP DATABASE judge;";
    echo -e "$PURPLE""sudo ""$YELLOW""mysql ""$GREEN""-u$DB_NAME -p*** -h127.0.0.1 -e\"CREATE DATABASE judge;\"""$WHITE";
    sudo mysql -u"$DB_NAME" -p"$DB_PASSWD" -h127.0.0.1 -e"CREATE DATABASE judge;";
    echo -e "$PURPLE""sudo ""$YELLOW""mysql ""$GREEN""-u$DB_NAME -p*** -h127.0.0.1 -e\"use judge;source judge.sql;\"""$WHITE";
    sudo mysql -u"$DB_NAME" -p"$DB_PASSWD" -h127.0.0.1 -e"use judge;source judge.sql;";

    echo -e "$GREEN""还原成功!""$WHITE";
    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
    main_face;
}

function start_judge() {
    if [[ $( ps -ef | pgrep judge ) != "" ]]; then
        echo -e "$RED""LYOJ 后台程序正在运行中!";
        echo -e "$RED""请不要重复启动，否则会出现逻辑错误!";
        echo -e "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        return;
    fi
    sudo judge > /dev/null &
    echo -e "$GREEN""LYOJ 后台程序运行成功";
    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
}

function stop_judge() {
    if [[ $( ps -ef | pgrep judge ) == "" ]]; then
        echo -e "$RED""LYOJ 后台程序未启动!";
        echo -e "$WHITE""Press \"Enter\" to continue..."
        read -rp "";
        return;
    fi
    sudo pkill judge;
    echo -e "$GREEN""LYOJ 后台程序已停止";
    echo -e "$WHITE""Press \"Enter\" to continue...";
    read -rp "";
}

function restart_judge() {
    stop_judge;
    start_judge;
}

function view_log() { 
    if [[ ! -f "/var/log/judge/info.log" ]]; then 
        touch /var/log/judge/info.log;
    fi
    sudo tail -f /var/log/judge/info.log;
}

function main_face() {
    clear;

    cd $SHELL_PATH;

    echo -e "$WHITE""LYOJ 一键安装管理脚本";
    echo -e "$WHITE""--- --- --- --- --- --- --- --- --- ---";
    echo -e "$WHITE""1. 安装 LYOJ";
    echo -e "$WHITE""2. 更新 LYOJ";
    echo -e "$WHITE""3. 卸载 LYOJ";
    echo -e "$WHITE""4. 备份服务器数据";
    echo -e "$WHITE""5. 还原服务器数据";
    echo -e "$WHITE""--- --- --- --- --- --- --- --- ---- ---";
    echo -e "$WHITE""6. 启动 LYOJ 后台程序";
    echo -e "$WHITE""7. 停止 LYOJ 后台程序";
    echo -e "$WHITE""8. 重启 LYOJ 后台程序";
    echo -e "$WHITE""9. 查看 LYOJ 后台程序日志";
    echo -e "$WHITE""--- --- --- --- --- --- --- --- --- ---";
    echo -e "$WHITE""10. 退出脚本";
    
    echo "";
    echo -en "$WHITE""LYOJ 安装状态: ";
    if [[ $( command -v judge ) != "" ]]; then
        echo -e "$GREEN""已安装";
    else
        echo -e "$RED""未安装";
    fi
    echo -en "$WHITE""LYOJ 运行状态: ";
    if [[ $( ps -ef | pgrep judge ) != "" ]]; then 
        echo -e "$GREEN""正在运行";
    else 
        echo -e "$RED""未运行";
    fi

    echo -en "$WHITE""请输入数字[1-10]: ";
    read -r choice;

    if [[ ! $choice =~ ^[0-9]+$ || $choice -lt 1 || $choice -gt 10 ]]; then
        echo -en "$RED""未知的操作";
        if [[ $choice =~ ^[0-9]+$ ]]; then
            echo " \"$choice\"";
        else 
            echo "";
        fi
        echo -en "$WHITE""Press \"Enter\" to continue...";
        read -rp "";
        main_face;
    fi

    case "$choice" in 
    1) install ;;
    2) update ;;
    3) uninstall ;;
    4) backup ;;
    5) restore ;;
    6) start_judge ;;
    7) stop_judge ;;
    8) restart_judge ;;
    9) view_log ;;
    10) exit 0 ;;
    esac 
    main_face
}

check_sys;
check_root;
check_db;
main_face;