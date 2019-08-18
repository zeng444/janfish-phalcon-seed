## 种子说明

### 目录结构说明

api 微应用模式例子
cli 脚本应用模式例子
configs 公用配置文件
logs 日志目录
core 核心程序

### 版本环境

>  PHP Version 7.1.2
>  Phalcon 3.4.1

兼容

>  PHP Version 5.5.3
>  Phalcon 2.0.8


### PHP配置

php.ini

```
variables_order = "GPCSE"
```

### 系统环境配置


/etc/profile

```
export SITE_ENV=DEV
```

### Nginx

vhost

```
server {
    listen   80;
    server_name YOUR_LOCAL_DOMAIN;
    root /vagrant/phalcon7/app/public;
    include phalcon7.conf;
}
```
phalcon7.conf

```
index index.php;
try_files $uri $uri/ @rewrite;
location @rewrite {
   rewrite ^(.*)$ /index.php?_url=$1;
}
location ~ index\.php$ {
    include /etc/nginx/fastcgi_params;
    fastcgi_index /index.php;
    fastcgi_pass 127.0.0.1:9007;
    fastcgi_param PATH_INFO       $fastcgi_path_info;
    fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param  SITE_ENV DEV;
}
```


### phalcon tools

- 配置configs目录下config.ini，主要修改数据库配置和Model路径配置
- 项目根目录下创建.phalcon目录
- 根目录下运行phalcon tools生成模型等

```
phalcon model tableName  --extends=BaseModel --doc
phalcon all-models  --extends=BaseModel --doc
```

###  phalcon-ide-stubs

> https://github.com/phalcon/ide-stubs


## PHPUnit 环境

### PHP5.5

```
wget https://phar.phpunit.de/phpunit-4.8.phar
chmod +x phpunit-6.2.phar
mv phpunit-6.2.phar /usr/local/bin/phpunit

```

### PHP7.1
```
wget https://phar.phpunit.de/phpunit-6.2.phar
chmod +x phpunit-6.2.phar
mv phpunit-6.2.phar /usr/local/bin/phpunit

```


## GRUNT编译环境

### NODEJS

- install nodejs
- npm install -g grunt-cli
- npm config set registry https://registry.npm.taobao.org
- cd app/public
- npm install

### 编译命令

- grunt release
- grunt build_watch

