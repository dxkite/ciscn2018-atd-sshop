from multiprocessing import Pool
from AutoRegister import AutoRegister
import sys

def regiser():
    ip = sys.argv[1]
    port = sys.argv[2]
    csrfname = sys.argv[3]
    name = sys.argv[4]
    number = sys.argv[5]
    register=AutoRegister(ip, port, csrfname,name)
    if register.register_user() == True:
        print '[+] %d Register Success' % i
    else:
        print '[!] %d Register Failed' % i  
     
