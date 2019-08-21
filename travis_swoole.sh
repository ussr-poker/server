#!/usr/bin/env bash

if [[ -d "swoole-src" ]]
then
    cd swoole-src
    make install
else
    git clone https://github.com/swoole/swoole-src.git
    cd swoole-src
    phpize
    ./configure --enable-sockets
    make -j 4 && make install
    cd ../
fi

echo "extension = swoole.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
