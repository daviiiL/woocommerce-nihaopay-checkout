@echo off
setlocal enabledelayedexpansion

:: WooCommerce NihaoPay Checkout - Release Build Script
:: This script builds the plugin and creates a release ZIP file

echo Building WooCommerce NihaoPay Checkout...

:: Check system requirements
echo Checking system requirements...

:: Check for PowerShell (required for ZIP creation)
powershell -Command "Get-Host" >nul 2>&1
if errorlevel 1 (
    echo ERROR: PowerShell not found. PowerShell is required for this script.
    echo Please ensure PowerShell is installed and available in PATH.
    pause
    exit /b 1
)

:: Check for Node.js
node --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js not found. Please install Node.js 20 or higher.
    echo Visit https://nodejs.org/ to download Node.js 20+
    pause
    exit /b 1
)

:: Check Node.js version (requires 20+)
for /f "tokens=1 delims=." %%a in ('node --version') do (
    set "NODE_MAJOR=%%a"
    set "NODE_MAJOR=!NODE_MAJOR:v=!"
)
if !NODE_MAJOR! LSS 20 (
    echo ERROR: Node.js version !NODE_MAJOR! found, but version 20+ is required.
    echo Current version: 
    node --version
    echo Visit https://nodejs.org/ to download Node.js 20+
    pause
    exit /b 1
)

:: Check for npm
npm --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: npm not found. Please install npm (usually comes with Node.js).
    pause
    exit /b 1
)

echo Success: All system requirements satisfied
echo   - PowerShell: Available
for /f %%a in ('node --version') do echo   - Node.js: %%a
for /f %%a in ('npm --version') do echo   - npm: %%a
echo.

:: Get plugin version from main plugin file
for /f "tokens=2 delims=:" %%a in ('findstr /C:"Version:" woocommerce-nihaopay-checkout.php') do (
    set "VERSION=%%a"
    set "VERSION=!VERSION: =!"
)

echo Building version !VERSION!...

:: Check for existing build and ask for confirmation
if exist "woocommerce-nihaopay-checkout-!VERSION!.zip" (
    echo.
    echo WARNING: A release file with the same version already exists:
    echo   - File: woocommerce-nihaopay-checkout-!VERSION!.zip
    echo   - Version: !VERSION!
    echo.
    set /p "OVERWRITE=Do you want to overwrite the existing file? (y/N): "
    if /i not "!OVERWRITE!"=="y" (
        echo Build cancelled by user.
        pause
        exit /b 1
    )
    echo Removing existing release file...
    del "woocommerce-nihaopay-checkout-!VERSION!.zip"
)

:: Check for package updates
echo Checking for package updates...
call npm run packages-update

:: Install dependencies
echo Installing dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

:: Build the plugin
echo Building plugin assets...
call npm run build --silent
if errorlevel 1 (
    echo ERROR: Failed to build plugin
    pause
    exit /b 1
)

:: Create temp directory for packaging
if exist "temp_release" rmdir /s /q "temp_release"
mkdir "temp_release"

:: Copy files to temp directory (excluding unwanted files)
echo Creating release package...
xcopy /E /I /Q . "temp_release\" ^
    /EXCLUDE:build_excludes.txt

:: Create exclusion list file
echo node_modules\ > build_excludes.txt
echo resources\ >> build_excludes.txt
echo package.json >> build_excludes.txt
echo package-lock.json >> build_excludes.txt
echo webpack.config.js >> build_excludes.txt
echo bin\ >> build_excludes.txt
echo .git >> build_excludes.txt
echo .gitignore >> build_excludes.txt
echo build_release.bat >> build_excludes.txt
echo build_release.sh >> build_excludes.txt
echo README.md >> build_excludes.txt
echo build_excludes.txt >> build_excludes.txt
echo temp_release\ >> build_excludes.txt

:: Copy files excluding the unwanted ones
robocopy . "temp_release" /E /XF package.json package-lock.json webpack.config.js build_release.bat build_release.sh README.md build_excludes.txt /XD node_modules resources bin .git temp_release /NFL /NDL /NJH /NJS

:: Create ZIP using PowerShell
echo Creating ZIP file...
powershell -Command "Compress-Archive -Path 'temp_release\*' -DestinationPath 'woocommerce-nihaopay-checkout-!VERSION!.zip' -Force"

:: Clean up
del build_excludes.txt
rmdir /s /q "temp_release"

echo.
echo SUCCESS: Release package created: woocommerce-nihaopay-checkout-!VERSION!.zip
echo.

:: Show package contents using PowerShell
echo Package contents:
powershell -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::OpenRead('woocommerce-nihaopay-checkout-!VERSION!.zip').Entries | Select-Object Name, Length | Format-Table -AutoSize"

echo Build complete!
pause