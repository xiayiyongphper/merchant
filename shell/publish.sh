#!/bin/bash
dir=/home/swoole/merchant
shell_dir=$(cd "$(dirname "$0")"; pwd)
cd $shell_dir
cd ..
current_dir=`pwd`
version=`basename $current_dir`
sudo rm -rf $dir
sudo ln -sf $current_dir $dir
sudo mkdir -p $dir/service/runtime/logs
sudo chmod -R 777 $dir/service/runtime