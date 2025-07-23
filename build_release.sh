#!/bin/bash

# WooCommerce NihaoPay Checkout - Release Build Script
# This script builds the plugin and creates a release ZIP file

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check Node.js version
check_node_version() {
    if ! command_exists node; then
        return 1
    fi
    
    local version=$(node -v | sed 's/v//')
    local major_version=$(echo $version | cut -d. -f1)
    
    if [ "$major_version" -ge 20 ]; then
        return 0
    else
        return 1
    fi
}

# Check system requirements
echo -e "${YELLOW}Checking system requirements...${NC}"

# Check for zip
if ! command_exists zip; then
    echo -e "${RED}ERROR: 'zip' command not found. Please install zip utility.${NC}"
    echo -e "${YELLOW}On Ubuntu/Debian: sudo apt-get install zip${NC}"
    echo -e "${YELLOW}On CentOS/RHEL: sudo yum install zip${NC}"
    echo -e "${YELLOW}On macOS: zip is usually pre-installed${NC}"
    exit 1
fi

# Check for Node.js 20+
if ! check_node_version; then
    if ! command_exists node; then
        echo -e "${RED}ERROR: Node.js not found. Please install Node.js 20 or higher.${NC}"
    else
        local current_version=$(node -v)
        echo -e "${RED}ERROR: Node.js version ${current_version} found, but version 20+ is required.${NC}"
    fi
    echo -e "${YELLOW}Visit https://nodejs.org/ to download Node.js 20+${NC}"
    exit 1
fi

# Check for npm
if ! command_exists npm; then
    echo -e "${RED}ERROR: npm not found. Please install npm (usually comes with Node.js).${NC}"
    exit 1
fi

echo -e "${GREEN}✓ All system requirements satisfied${NC}"
echo -e "${GREEN}  - zip: $(zip --version | head -n1)${NC}"
echo -e "${GREEN}  - Node.js: $(node -v)${NC}"
echo -e "${GREEN}  - npm: $(npm -v)${NC}"

# Get plugin version from main plugin file
VERSION=$(grep "Version:" woocommerce-nihaopay-checkout.php | sed 's/.*Version: *//' | sed 's/ *$//')

echo -e "${YELLOW}Building WooCommerce NihaoPay Checkout v${VERSION}...${NC}"

# Clean up any existing build
if [ -f "woocommerce-nihaopay-checkout-${VERSION}.zip" ]; then
    echo -e "${YELLOW}Removing existing release file...${NC}"
    rm "woocommerce-nihaopay-checkout-${VERSION}.zip"
fi

# Check for package updates
echo -e "${YELLOW}Checking for package updates...${NC}"
npm run packages-update

# Install dependencies
echo -e "${YELLOW}Installing dependencies...${NC}"
npm install

# Build the plugin
echo -e "${YELLOW}Building plugin assets...${NC}"
npm run build --silent

# Create release ZIP
echo -e "${YELLOW}Creating release package...${NC}"
zip -r "woocommerce-nihaopay-checkout-${VERSION}.zip" . \
    -x "node_modules/*" \
       "resources/*" \
       "package*.json" \
       "webpack.config.js" \
       "bin/*" \
       ".git*" \
       "build_release.*" \
       "*.md" \
       ".DS_Store" \
       "Thumbs.db"

echo -e "${GREEN}✓ Release package created: woocommerce-nihaopay-checkout-${VERSION}.zip${NC}"

# Show package contents
echo -e "${YELLOW}Package contents:${NC}"
unzip -l "woocommerce-nihaopay-checkout-${VERSION}.zip"

echo -e "${GREEN}✓ Build complete!${NC}"