#!/usr/bin/env bash

cd ~

if [[ -f "swoole-src/.libs/swoole.so" ]]
then
    cd swoole-src
    cp .libs/CMakeLists.txt ./CMakeLists.txt
    echo "swoole.so exists"
else
    echo "swoole.so not existing, build started..."

    rm -rf swoole-src

    git clone https://github.com/swoole/swoole-src.git
    cd swoole-src
    cp CMakeLists.txt .libs/CMakeLists.txt
    phpize
    ./configure --enable-sockets
    make -j 4
fi

make install

echo "extension = swoole.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
