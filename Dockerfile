FROM centos:centos7
MAINTAINER DXkite dxkite(at)gmail

RUN yum install -y net-tools 

# get xampp
RUN curl -o xampp-linux-installer.run "https://downloadsapachefriends.global.ssl.fastly.net/xampp-files/7.2.5/xampp-linux-x64-7.2.5-0-installer.run?from_af=true"

# install xampp
RUN chmod +x xampp-linux-installer.run
RUN bash -c './xampp-linux-installer.run'
RUN ln -sf /opt/lampp/lampp /usr/bin/lampp



# copy files

RUN mkdir /sshop
COPY ./xctf /sshop
RUN chmod a+rw /sshop/public
RUN chmod a+rw /sshop/app/data
RUN sed -i 's/\/opt\/lampp\/htdocs/\/sshop\/public/g' /opt/lampp/etc/httpd.conf
RUN rm -rf /sshop/public/dev.php
RUN rm -rf /sshop/app/data/session/*
RUN rm -rf /sshop/app/data/cache/*
RUN rm -rf /sshop/app/data/logs/*
RUN rm -rf /sshop/app/data/views/*
RUN rm -rf /sshop/app/data/install/*
RUN mkdir -p /sshop/app/data/runtime
RUN echo  '<?php return ["passwd"=>""];' > /sshop/app/data/runtime/database.config.php

# setup config
RUN echo '/opt/lampp/lampp startapache' >> /start.sh
RUN echo '/opt/lampp/lampp startmysql' >> /start.sh
EXPOSE 80
