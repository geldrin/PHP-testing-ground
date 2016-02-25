#!/bin/bash

if [ "$#" -eq 0 ];
then
    echo "ERROR: domain is not provided!"
    exit
fi

domain=$1
openssl_config="openssl.cnf"

if [ ! -f $openssl_config ];
then
    echo "ERROR: OpenSSL config does note exists"
    exit
fi

keyfile=${domain}.key
csrfile=${domain}.csr

# Generate key
openssl genrsa -out ${keyfile} 2048

# Generate CSR
openssl req -new -out ${csrfile} -key ${keyfile} -sha256 -config openssl.cnf

# Verify CSR
openssl req -text -noout -verify -in ${csrfile}
