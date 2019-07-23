# Ti RPC

## swoole版本说明
这个东西做出来的时候时间比较早，当时swoole 2.0也才刚发版没多久。所以，这个玩意在swoole 1.x系列和swoole 2.x系列里是没什么问题的。但是swoole 4可能发生的一些变动可能会导致这个玩意报一些错。我建议是：swoole 4情况下，不优先考虑这个；如果一定要考虑，自己修一下吧。因为swoole后面是重协程的，而咱这个玩意主要思路还是多进程方式。至于会不会改进做一个协程版本的：会做。但是没有经历至少几十万用户验证前，我应该不会轻易扔出来的。现有的这个版本虽然在协程时代显得LOW，但是贵在经历过百万级的用户体量考验。

## 简要概述：
封装的比较简单，代码风格略粗暴，没有过多的组件以及厚重的包装，我不太喜欢过于复杂的东西。整体思路是借鉴蓝天的，代码则自己实现（你也可以认为是抄袭），自己做了些许改动。由于第一个版本我实现的比较烂，所以没有放出来，这个版本代码略干净能看一些，又在我们公司生产环境经过长期的验证，每日支撑将近8000万次的调用，所以我就放出来了。

我不会刻意去推广这个，也会长期改进维护，只希望能帮到一些人。


## TODO LIST：
1.加入更好的异常机制代替丑陋的if else

2.加入包头定长拆包协议（已实现）

## VIM配置

set shiftwidth=2

set softtabstop=2

set tabstop=2

set rnu

set fileencodings=utf-8,gb2312,gb18030,gbk,ucs-bom,cp936,latin1

set enc=utf8

set fencs=utf8,gbk,gb2312,gb18030


## 功能简介：
1.同时提供tcp和http两种方式。

2.提供四种不同调用方式：
 
 SW : 单个请求,等待结果
 
 SN : 单个请求,不等待结果
 
 MW : 多个请求,等待结果
  
 MN : 多个请求,不等待结果  

3.客户端可以通过长链接连接RPC服务，避免TCP握挥手带来的性能损耗

4.TCP提供根据数据长度拆包和包头定长两种拆包方式，默认启用包头定长

5.其余特性参考 http://wiki.swoole.com



## 部署安装
1. git clone https://github.com/elarity/ti-rpc.git
2. 到ti-rpc根目录下执行php index.php查看使用方式

已经加入对composer的支持，根目录下有个composer.json，请不要随意修改其中内容如果你明白你在做什么操作。如果你需要从github找到一个php库并使用，比如这个[curl类](https://github.com/php-curl-class/php-curl-class)，那么你需要在ti rpc的根目录下执行如下命令：

```php
composer require php-curl-class/php-curl-class
```
这个时候，ti rpc将会采用composer自动加载器而不是自定义的自动加载，从而可以方便粗暴快捷简单地使用任何一个php composer库




## 使用方式
##### php index.php [command] [option]
- start，以非daemon形式开始服务，也就是debug模式
- start -d，以daemon模式开启服务，一般用于正式部署
- stop，停止服务
- reload，热加载业务代码
- status，查看服务状态

![](http://static.ti-node.com/github_tirpc_1.png)

## 内容添加
1. 添加curl和RNCryptor加解密库

## 联系方式：
wechat：sbyuanmaomao
