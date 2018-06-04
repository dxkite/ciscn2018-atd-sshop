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