#!/usr/bin/env bash

cd ~

if [[ -f "swoole-src/.libs/swoole.so" ]]
then
    cd swoole-src

    cp .libs/CMakeLists.txt ./CMakeLists.txt
    cp .libs/Makefile ./Makefile
    cp .libs/Makefile.fragments ./Makefile.fragments
    cp .libs/Makefile.objects ./Makefile.objects

    echo "swoole.so exists"
else
    echo "swoole.so not existing, build started..."

    rm -rf swoole-src

    git clone https://github.com/swoole/swoole-src.git
    cd swoole-src
    phpize
    ./configure --enable-sockets
    make -j 4

    mkdir .libs
    cp CMakeLists.txt .libs/CMakeLists.txt
    cp Makefile .libs/Makefile
    cp Makefile.fragments .libs/Makefile.fragments
    cp Makefile.objects .libs/Makefile.objects
fi

make install

echo "extension = swoole.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
