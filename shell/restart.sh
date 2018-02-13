#!/bin/bash
cur_dir=$(cd "$(dirname "$0")"; pwd)
ps -eaf |grep "RPC merchant Server" | grep -v "grep"| awk '{print $2}'|xargs kill -9
ps -eaf |grep "server_merchant" | grep -v "grep"| awk '{print $2}'|xargs kill -9
ps -eaf |grep "server_http_merchant" | grep -v "grep"| awk '{print $2}'|xargs kill -9
sleep 1
cd $cur_dir
cd ..
php server_merchant.php start
nohup php server_http_merchant.php &
