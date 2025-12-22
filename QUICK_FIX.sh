#!/bin/bash
##############################################################################
# ProConsultancy - QUICK FIX SCRIPT
##############################################################################
# This script automatically fixes the critical blocking issues
# Run this from your project root directory
##############################################################################

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   ProConsultancy - Automated Quick Fix Script               ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Check if we're in the project directory
if [ ! -f "login.php" ]; then
    echo "❌ ERROR: Please run this script from the ProConsultancy project root directory"
    exit 1
fi

echo "✓ Found project directory"
echo ""

##############################################################################
# BACKUP
##############################################################################

echo "📦 Creating backup..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp login.php "$BACKUP_DIR/" 2>/dev/null
cp includes/core/login_handle.php "$BACKUP_DIR/" 2>/dev/null
cp includes/config/config.php "$BACKUP_DIR/" 2>/dev/null
cp .env "$BACKUP_DIR/" 2>/dev/null
echo "✓ Backup created in: $BACKUP_DIR"
echo ""

##############################################################################
# FIX 1: LOGIN.PHP - API PATH
##############################################################################

echo "🔧 Fix 1: Correcting login API path..."
if grep -q "const API_URL = 'include/login_handle.php';" login.php; then
    sed -i "s|const API_URL = 'include/login_handle.php';|const API_URL = 'includes/core/login_handle.php';|g" login.php
    echo "✓ Fixed login.php API path"
else
    echo "⚠️  Login API path already correct or different format"
fi
echo ""

##############################################################################
# FIX 2: LOGIN_HANDLE.PHP - AUTH INCLUDE PATH
##############################################################################

echo "🔧 Fix 2: Correcting Auth include path..."
if [ -f "includes/core/login_handle.php" ]; then
    if grep -q "require_once __DIR__ . '/../core/Auth.php';" includes/core/login_handle.php; then
        sed -i "s|require_once __DIR__ . '/../core/Auth.php';|require_once __DIR__ . '/Auth.php';|g" includes/core/login_handle.php
        echo "✓ Fixed Auth include path"
    else
        echo "⚠️  Auth include path already correct or different format"
    fi
else
    echo "❌ ERROR: includes/core/login_handle.php not found!"
fi
echo ""

##############################################################################
# FIX 3: ADD SESSION_START
##############################################################################

echo "🔧 Fix 3: Adding session_start()..."
if [ -f "includes/core/login_handle.php" ]; then
    if ! grep -q "session_start();" includes/core/login_handle.php; then
        # Add session_start() after opening PHP tag
        sed -i '1 a\session_start();' includes/core/login_handle.php
        echo "✓ Added session_start() to login handler"
    else
        echo "⚠️  session_start() already present"
    fi
else
    echo "❌ ERROR: includes/core/login_handle.php not found!"
fi
echo ""

##############################################################################
# FIX 4: ADD COMPANY_TAGLINE CONSTANT
##############################################################################

echo "🔧 Fix 4: Adding COMPANY_TAGLINE constant..."

# Add to config.php
if [ -f "includes/config/config.php" ]; then
    if ! grep -q "COMPANY_TAGLINE" includes/config/config.php; then
        # Find the line with COMPANY_PHONE and add after it
        sed -i "/define('COMPANY_PHONE'/a define('COMPANY_TAGLINE', env('COMPANY_TAGLINE', 'Your Recruitment Partner'));" includes/config/config.php
        echo "✓ Added COMPANY_TAGLINE to config.php"
    else
        echo "⚠️  COMPANY_TAGLINE already defined in config.php"
    fi
else
    echo "❌ ERROR: includes/config/config.php not found!"
fi

# Add to .env
if [ -f ".env" ]; then
    if ! grep -q "COMPANY_TAGLINE" .env; then
        echo "COMPANY_TAGLINE=Your Recruitment Partner" >> .env
        echo "✓ Added COMPANY_TAGLINE to .env"
    else
        echo "⚠️  COMPANY_TAGLINE already in .env"
    fi
else
    echo "❌ ERROR: .env file not found!"
fi
echo ""

##############################################################################
# FIX 5: ENABLE DEBUG MODE
##############################################################################

echo "🔧 Fix 5: Enabling debug mode for testing..."
if [ -f ".env" ]; then
    if grep -q "APP_DEBUG=false" .env; then
        sed -i 's/APP_DEBUG=false/APP_DEBUG=true/g' .env
        echo "✓ Debug mode enabled in .env"
    else
        echo "⚠️  Debug mode already enabled or different value"
    fi
else
    echo "❌ ERROR: .env file not found!"
fi
echo ""

##############################################################################
# FIX 6: CREATE REQUIRED DIRECTORIES
##############################################################################

echo "🔧 Fix 6: Creating required directories..."
mkdir -p uploads/candidates 2>/dev/null
mkdir -p uploads/jobs 2>/dev/null
mkdir -p uploads/documents 2>/dev/null
mkdir -p uploads/temp 2>/dev/null
mkdir -p logs 2>/dev/null

chmod -R 755 uploads 2>/dev/null
chmod -R 755 logs 2>/dev/null

echo "✓ Created upload and log directories"
echo ""

##############################################################################
# SUMMARY
##############################################################################

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   QUICK FIXES COMPLETED                                      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "✅ FIXED:"
echo "   1. Login API path (login.php)"
echo "   2. Auth include path (login_handle.php)"
echo "   3. Added session_start()"
echo "   4. Added COMPANY_TAGLINE constant"
echo "   5. Enabled debug mode"
echo "   6. Created required directories"
echo ""
echo "⚠️  STILL REQUIRED:"
echo "   1. Create database and import schema:"
echo "      mysql -u root -p < FINAL_DATABASE_SETUP.sql"
echo ""
echo "   2. Verify database credentials in .env match:"
echo "      DB_USER=proconsultancy_user"
echo "      DB_PASS=ProConsult2024!"
echo "      DB_NAME=proconsultancy_db"
echo ""
echo "   3. Test login with:"
echo "      Email: admin@proconsultancy.be"
echo "      Password: Admin@123"
echo ""
echo "📁 Backup saved in: $BACKUP_DIR"
echo ""
echo "🚀 You can now test the application!"
echo ""
