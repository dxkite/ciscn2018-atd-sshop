# CISCN2018-ATD-SSHOP

- 程序开发：[DXkite](https://github.com/DXkite)
- 漏洞设计：Water、DXkite、iriszero
- 技术依赖：[自制Suda框架](https://github.com/DXkite/suda)
- 被使用地区：CISCN2018 西北赛区
- Break：**0**
- Fix: *unknown*

## Write Up

[查看WriteUp](http://t.cn/R1R1nMp)

## 解题思路

1. 注册新用户
2. 绕过ip注册限制至少邀请100名新用户
3. 积分增加，购买Flag商品
4. 下载APK并逆向解密(解出有提示)
5. 网站banner图片隐写解密(解出有提示)
6. 区块链挖矿程序设计
7. 回到前台通过FlagCion购买Flag
8. snow隐写(解出有提示)
9. 任意文件下载(文件包含)漏洞获得Flag

## 题目限制

1.  **防薅羊毛** ：同一个ip只能注册3个新账号
2.  **防跳过设置**： 
    1.  当用户未购买Flag商品，首页中下载的为假的APK(**由360提供技术支持**)，当用户购买了Flag商品后，则下载的为真实的APK。
    2.  当用户挖足500个币(FlagCoin)后，可进行下一步操作。

## HInt

1. 先加入购物车再进行购买，购买后看看cookie里有什么?
2. 你可能需要一个隐写师傅？
3. 你确定你累计邀请了100个人并且挖到了500个Flag Coin？做到了再去下载APK试试？

---

> `FixIt`环节有个坑，4uuu大佬的Checker会注册4uuu账号，下次注册会出现同名账号注册失败结果会导致下次Checker检测失败，所以参赛队伍最好不要拿官方Checker账号来玩，如果用官方Checker检测了，请清理数据库

