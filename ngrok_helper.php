# 🌐 EldoGas POS with ngrok Setup Guide

## 📋 Overview

ngrok creates a secure tunnel to your local development server, allowing you to:
- ✅ Access your POS from anywhere on the internet
- ✅ Test offline functionality by disconnecting internet
- ✅ Share your application with others
- ✅ Test with real mobile devices
- ✅ Use HTTPS for testing payment integrations

## 🚀 Quick Setup

### Step 1: Install ngrok

#### Option A: Download from Website
1. Go to [https://ngrok.com/download](https://ngrok.com/download)
2. Download for Windows
3. Extract to a folder (e.g., `C:\ngrok\`)
4. Add to PATH or use full path

#### Option B: Install via Chocolatey (Windows)
```bash
# Install Chocolatey first if you don't have it
# Then install ngrok
choco install ngrok
```

#### Option C: Install via npm
```bash
npm install -g ngrok
```

### Step 2: Sign Up and Get Auth Token
1. Create free account at [https://ngrok.com/signup](https://ngrok.com/signup)
2. Get your auth token from dashboard
3. Configure auth token:
```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE
```

### Step 3: Start Your Laravel Application
```bash
# Navigate to your project
cd C:\xampp\htdocs\eldogaspos

# Option A: Use Laravel's built-in server (Recommended)
php artisan serve --host=0.0.0.0 --port=8000

# Option B: Use XAMPP (Alternative)
# Make sure XAMPP Apache is running
# Your app will be at http://localhost/eldogaspos/public
```

### Step 4: Start ngrok Tunnel
```bash
# For Laravel serve (port 8000)
ngrok http 8000

# For XAMPP (port 80)
ngrok http 80

# For HTTPS tunnel
ngrok http --scheme=https 8000
```

## 🔧 Configuration for ngrok

### Update Laravel Configuration

<function_calls>
<invoke name="write_file">
<parameter name="content"><?php
// Add this to your routes/web.php for ngrok support

// Ngrok helper route to get current ngrok URL
Route::get('/ngrok-info', function() {
    $headers = request()->headers->all();
    $host = request()->getHost();
    $scheme = request()->getScheme();
    
    return response()->json([
        'ngrok_url' => $scheme . '://' . $host,
        'original_host' => request()->header('X-Original-Host'),
        'forwarded_for' => request()->header('X-Forwarded-For'),
        'ngrok_headers' => array_filter($headers, function($key) {
            return str_contains(strtolower($key), 'ngrok');
        }, ARRAY_FILTER_USE_KEY)
    ]);
})->middleware(['web']);
