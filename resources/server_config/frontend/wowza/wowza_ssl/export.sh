#!/bin/bash

#
# Input = PFX file from certificate authoority
#
# Help
#
# PKCS#12/PFX Format
# The PKCS#12 or PFX format is a binary format for storing the server certificate, any intermediate certificates,
# and the private key in one encryptable file. PFX files usually have extensions such as .pfx and .p12.
# PFX files are typically used on Windows machines to import and export certificates and private keys.
#
# When converting a PFX file to PEM format, OpenSSL will put all the certificates and the private key into a single file.
# You will need to open the file in a text editor and copy each certificate and private key (including the BEGIN/END statments)
# to its own individual text file and save them as certificate.cer, CACert.cer, and privateKey.key respectively.

# PFX certificate
cert_name=$1

# Full certificate containing private key and CA certificates
full_cert="certificate.cer"
signed_cert="signed.cert"
der_cert="videosquare.eu.cert.der"
# Server private key
server_key="videosquare.eu.key"
# Server private key in DER format
server_key_der="videosquare.eu.key.der"

echo "Master certificate: ${cert_name}"

rm -f videosquare.eu.pem
rm -f ${server_key}
rm -f ${server_key_der}
rm -f ${der_cert}

# pass: jfo

echo "## Certificate conversion"

echo "STEP: PXF -> Certificate (containing private key and CA certificates)"
command="openssl pkcs12 -in ${cert_name} -out ${full_cert} -nodes"
echo ${command}
${command}

echo "STEP: Convert signed certificate to DER format"
echo "HINT: Copy full cert to signed.cert, edit it and remove all except your cert between -----BEGIN CERTIFICATE----- / -----END CERTIFICATE----- tags"
command="openssl x509 -in ${signed_cert} -inform PEM -out ${der_cert} -outform DER"
echo ${command}
${command}

echo "## Private key conversion"

echo "STEP: Export the private key without a passphrase or password."
command="openssl pkcs12 -in ${cert_name} -nocerts -nodes -out videosquare.eu.pem"
echo ${command}
${command}

echo "STEP: Generate a public version of the private RSAkey"
command="openssl rsa -in videosquare.eu.pem -out ${server_key}"
echo ${command}
${command}

echo "STEP: Server DER key"
command="openssl pkcs8 -topk8 -nocrypt -in ${server_key} -inform PEM -out ${server_key_der} -outform DER"
echo ${command}
${command}

echo "## Import certificates and keys to Java"

echo "STEP: Import server key and certificate to Java keystore"
command="java ImportKey ${server_key_der} ${cert_der}"
echo ${command}
${command}

echo "STEP: Move keystore keys to Wowza config directory"
command="mv /root/keystore.ImportKey /usr/local/WowzaMediaServer/conf/"
echo ${command}
#${command}

echo "STEP: Import signed certificate with keytool"
command="keytool -import -alias wowza -trustcacerts -file ${signed_cert} -keystore /usr/local/WowzaMediaServer/conf/keystore.ImportKey"
echo ${command}
