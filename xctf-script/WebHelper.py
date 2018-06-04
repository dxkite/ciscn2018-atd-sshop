import re
import sys
import requests as req
from pyquery import PyQuery as PQ
import string
import random


class WebHelper:
    def __init__(self, ip, port):
        self.ip = ip
        self.port = port
        self.url = 'http://%s:%s/' % (ip, port)

    def _generate_randstr(self, len = 10):
        return ''.join(random.sample(string.ascii_letters, len))

    def _get_uuid(self, html):
        dom = PQ(html)
        return dom('form canvas').attr('rel')

    def _get_answer(self, html):
        uuid = self._get_uuid(html)
        # print uuid
        answer = {}
        with open('./ans/ans%s.txt' % uuid, 'r') as f:
            for line in f.readlines():
                if line != '\n':
                    ans = line.strip().split('=')
                    answer[ans[0].strip()] = ans[1].strip()
        x = random.randint(int(float(answer['ans_pos_x_1'])), int(float(answer['ans_width_x_1']) + float(answer['ans_pos_x_1'])))
        y = random.randint(int(float(answer['ans_pos_y_1'])), int(float(answer['ans_height_y_1']) + float(answer['ans_pos_y_1'])))
        return x,y

    def _get_user_integral(self,session):
        res = session.get(url + 'user')
        dom = PQ(res.text)
        res = dom('div.user-info').text()
        integral = re.search('(\d+\.\d+)', res).group()
        return integral

    def _get_token(self, html):
        dom = PQ(html)
        form = dom("form")
        token = str(PQ(form)("input[name=\"%s\"]" % self.csrfname).attr("value")).strip()
        return token
