#!/bin/bash

echo "DOC -> PDF conversion"
unoconv -f pdf ${1}

echo "DOC -> TXT conversion"
unoconv -f txt ${1}

echo "CHECK OUTPUT"
