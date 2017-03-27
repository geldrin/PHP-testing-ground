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
    echo "ERROR: OpenSSL config does not exists"
    exit
fi

keyfile=${domain}.key
csrfile=${domain}.csr
certfile=${domain}.crt

# Generate key
openssl genrsa -out ${keyfile} 2048

# Generate CSR
openssl req -new -out ${csrfile} -key ${keyfile} -sha256 -config openssl.cnf -batch

# Verify CSR
openssl req -text -noout -verify -in ${csrfile}

# Generate certificate
#openssl req -new -x509 -key ${keyfile} -sha256 -out ${certfile} -config openssl.cnf -days 3650 -batch
openssl req -x509 -days 3650 -in ${csrfile} -key ${keyfile} -sha256 -config openssl.cnf -out ${certfile} -extensions 'v3_req' -batch

# Print cert
openssl x509 -in ${certfile} -text -noout

# Generate PEM with Key
cat ${certfile} ${keyfile} > ${domain}.pem