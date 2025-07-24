#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

check_node_version() {
    if ! command_exists node; then
        return 1
    fi
    
    local version=$(node -v | sed 's/v//')
    local major_version=$(echo $version | cut -d. -f1)
    
    [ "$major_version" -ge 20 ]
}

echo -e "${YELLOW}Checking system requirements...${NC}"

for cmd in php wp zip; do
    if ! command_exists $cmd; then
        echo -e "${RED}ERROR: '$cmd' command not found.${NC}"
        exit 1
    fi
done

if ! check_node_version; then
    echo -e "${RED}ERROR: Node.js 20+ required.${NC}"
    exit 1
fi

if ! command_exists npm; then
    echo -e "${RED}ERROR: npm not found.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ All system requirements satisfied${NC}"

VERSION=$(grep "Version:" woocommerce-nihaopay-checkout.php | sed 's/.*Version: *//' | sed 's/ *$//')

echo -e "${YELLOW}Building WooCommerce NihaoPay Checkout v${VERSION}...${NC}"

if [ -f "woocommerce-nihaopay-checkout-${VERSION}.zip" ]; then
    echo -e "${YELLOW}WARNING: Release file already exists.${NC}"
    read -p "Overwrite? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}Build cancelled.${NC}"
        exit 1
    fi
    rm "woocommerce-nihaopay-checkout-${VERSION}.zip"
fi

echo -e "${YELLOW}Checking for package updates...${NC}"
npm run packages-update

echo -e "${YELLOW}Installing dependencies...${NC}"
npm install

echo -e "${YELLOW}Building plugin assets...${NC}"
npm run build --silent

echo -e "${YELLOW}Creating release package...${NC}"

TEMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TEMP_DIR/woocommerce-nihaopay-checkout"
mkdir -p "$PLUGIN_DIR"

cp woocommerce-nihaopay-checkout.php "$PLUGIN_DIR/"

for dir in includes assets languages; do
    [ -d "$dir" ] && cp -r "$dir" "$PLUGIN_DIR/"
done

[ -f "README.txt" ] && cp README.txt "$PLUGIN_DIR/" || [ -f "readme.txt" ] && cp readme.txt "$PLUGIN_DIR/"

if [ ! -f "$PLUGIN_DIR/woocommerce-nihaopay-checkout.php" ]; then
    echo -e "${RED}ERROR: Main plugin file not found${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

cd "$TEMP_DIR"
zip -r "woocommerce-nihaopay-checkout-${VERSION}.zip" woocommerce-nihaopay-checkout/
mv "woocommerce-nihaopay-checkout-${VERSION}.zip" "$OLDPWD/"
cd "$OLDPWD"

rm -rf "$TEMP_DIR"

echo -e "${GREEN}✓ Release package created: woocommerce-nihaopay-checkout-${VERSION}.zip${NC}"

echo -e "${YELLOW}Package contents:${NC}"
unzip -l "woocommerce-nihaopay-checkout-${VERSION}.zip"

echo -e "${GREEN}✓ Build complete!${NC}"
