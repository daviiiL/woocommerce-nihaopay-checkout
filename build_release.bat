@echo off
setlocal enabledelayedexpansion

echo Building WooCommerce NihaoPay Checkout...

echo Checking system requirements...

powershell -Command "Get-Host" >nul 2>&1
if errorlevel 1 (
    echo ERROR: PowerShell not found.
    pause
    exit /b 1
)

node --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js not found.
    pause
    exit /b 1
)

for /f "tokens=1 delims=." %%a in ('node --version') do (
    set "NODE_MAJOR=%%a"
    set "NODE_MAJOR=!NODE_MAJOR:v=!"
)
if !NODE_MAJOR! LSS 20 (
    echo ERROR: Node.js 20+ required.
    pause
    exit /b 1
)

npm --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: npm not found.
    pause
    exit /b 1
)

echo Success: All requirements satisfied

for /f "tokens=2 delims=:" %%a in ('findstr /C:"Version:" woocommerce-nihaopay-checkout.php') do (
    set "VERSION=%%a"
    set "VERSION=!VERSION: =!"
)

echo Building version !VERSION!...

if exist "woocommerce-nihaopay-checkout-!VERSION!.zip" (
    echo WARNING: Release file already exists.
    set /p "OVERWRITE=Overwrite? (y/N): "
    if /i not "!OVERWRITE!"=="y" (
        echo Build cancelled.
        pause
        exit /b 1
    )
    del "woocommerce-nihaopay-checkout-!VERSION!.zip"
)

echo Checking for package updates...
call npm run packages-update

echo Installing dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

echo Building plugin assets...
call npm run build --silent
if errorlevel 1 (
    echo ERROR: Failed to build plugin
    pause
    exit /b 1
)

if exist "temp_release" rmdir /s /q "temp_release"
mkdir "temp_release\woocommerce-nihaopay-checkout"

echo Creating release package...

xcopy woocommerce-nihaopay-checkout.php "temp_release\woocommerce-nihaopay-checkout\" /Q

for %%d in (includes assets languages) do (
    if exist "%%d" xcopy "%%d" "temp_release\woocommerce-nihaopay-checkout\%%d\" /E /I /Q
)

if exist "README.txt" (
    xcopy "README.txt" "temp_release\woocommerce-nihaopay-checkout\" /Q
) else if exist "readme.txt" (
    xcopy "readme.txt" "temp_release\woocommerce-nihaopay-checkout\" /Q
)

powershell -Command "Compress-Archive -Path 'temp_release\*' -DestinationPath 'woocommerce-nihaopay-checkout-!VERSION!.zip' -Force"

rmdir /s /q "temp_release"

echo SUCCESS: Release package created: woocommerce-nihaopay-checkout-!VERSION!.zip

echo Package contents:
powershell -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::OpenRead('woocommerce-nihaopay-checkout-!VERSION!.zip').Entries | Select-Object Name, Length | Format-Table -AutoSize"

echo Build complete!
pause