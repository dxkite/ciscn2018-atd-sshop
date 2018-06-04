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
        # ip = '175.%d.%d.%d' % (random.randint(0,255),random.randint(0,255),random.randint(0,255))
        self.header= {
            'User-Agent':'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
            'X-Forwarded-For':ip,
            'X-Client-Ip':ip,
        }
    
    def register_user(self):
        self._generate_header();
        session = req.session();
        # print self.url+'register'

        # res=session.get(self.url+'register');
 
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