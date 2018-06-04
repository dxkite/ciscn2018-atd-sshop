# 题目说明

本题由tornado商城模版由原本的`Python` + `sqlite`改编成了`PHP` + `Mysql`版本，所有功能均符合出题要求，并针对相关应用融入防止"薅羊毛"功能（具体参照`writeup.md`）。看上去是一道WEB题，实际上模拟真实电商环境并针对CTF比赛进行了重新设计。这道题其实由多个小关卡组成，其中涉及安卓逆向、图片隐写、密码学、web应用漏洞、以及创新的薅羊毛防范、区块链技术。



本程序最终漏洞就是一个任意文件下载(文件包含)漏洞  

通过构造?file=php://filter/convert.base64-encode/resource={DATA}/flag.php的形式就能获得flag

整套流程进行了改进，加入了创新的区块链技术、以及常见的薅羊毛防范，加入了CTF元素。

获得FLAG基本流程如下：

注册新用户→绕过`ip注册限制`至少邀请100名新用户→积分增加，购买Flag商品→下载APK并逆向解密(解出有提示)→网站banner图片隐写解密(解出有提示)→区块链挖矿程序设计→回到前台通过FlagCion购买Flag→snow隐写(解出有提示)→任意文件下载(文件包含)漏洞获得Flag



注：

ip注册限制:一天同一个ip只能注册3个新账号（防薅羊毛基本功能）

**限制条件一**:当用户未购买Flag商品，首页中下载的为假的APK，当用户购买了Flag商品后，则下载的为真实的APK。

**限制条件二**:当用户挖足500个币(FlagCoin)后，可进行下一步操作。 

当用户同时满足**条件一**和**条件二**时   才能触发最终的任意文件下载(文件包含)漏洞   



因为是第一次参加CTF比赛并设计题目，无法估计难度，组委会可根据提供的解题流程和参赛队伍水平来修改hint。谢谢！





hint1:先加入购物车再进行购买，购买后看看cookis里有什么?

hint2:你可能需要一个隐写师傅？

hint3:你确定你累计邀请了100个人并且挖到了500个Flag Coin？做到了再去下载APK试试？



如果遇到问题   可联系dxkite@qq.com 或  admin@gksec.com

## 调整Flag

程序的Flag在文件 `deploy/xctf/app/data/data/flag.php` 中，使用命令

```bash
sed -i 's/CISCN{xxxx}/CISCN{xxxxflag}'  deploy/xctf/app/data/data/flag.php
```

用来替换Flag文件



​									`									 中南林业科技大学涉外学院

​                                                                                                                                                       ATD-SECURITY-TEAM

​                                                                                                                                                                2018.5.24