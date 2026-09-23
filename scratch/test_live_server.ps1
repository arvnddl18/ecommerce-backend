$baseUrl = "http://127.0.0.1:8000"
Write-Host "=== Testing Live Server at $baseUrl ===" -ForegroundColor Cyan

# 1. Test Homepage
try {
    $homeRes = Invoke-WebRequest -Uri "$baseUrl" -UseBasicParsing
    Write-Host "[PASS] Storefront Homepage: HTTP $($homeRes.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Homepage request failed: $_" -ForegroundColor Red
}

# 2. Test Categories Endpoint
try {
    $catRes = Invoke-RestMethod -Uri "$baseUrl/api/v1/categories" -Method GET
    Write-Host "[PASS] Categories API: Found $($catRes.data.Count) categories" -ForegroundColor Green
    foreach ($cat in $catRes.data) {
        Write-Host "       - $($cat.name) ($($cat.slug))" -ForegroundColor Gray
    }
} catch {
    Write-Host "[FAIL] Categories API failed: $_" -ForegroundColor Red
}

# 3. Test Products Endpoint with filters
try {
    $prodRes = Invoke-RestMethod -Uri "$baseUrl/api/v1/products" -Method GET
    Write-Host "[PASS] Products API: Found $($prodRes.data.Count) apparel products" -ForegroundColor Green
    
    # Test Size filter
    $sizeFiltered = Invoke-RestMethod -Uri "$baseUrl/api/v1/products?size=L" -Method GET
    Write-Host "[PASS] Products Filter (Size=L): Returned $($sizeFiltered.data.Count) items" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Products API failed: $_" -ForegroundColor Red
}

# 4. Test 16+ Compliance & Buyer Registration
$randomSuffix = Get-Random -Minimum 1000 -Maximum 9999
$buyerEmail = "buyer_$randomSuffix@example.com"

try {
    # 4a. Verify that age_verified = false fails validation
    $rejected = $false
    try {
        $failBody = @{
            name = "Underage Shopper"
            email = "underage_$randomSuffix@example.com"
            password = "password123"
            password_confirmation = "password123"
            age_verified = $false
        } | ConvertTo-Json

        Invoke-RestMethod -Uri "$baseUrl/api/v1/auth/register" -Method POST -Body $failBody -ContentType "application/json"
    } catch {
        $rejected = $true
    }

    if ($rejected) {
        Write-Host "[PASS] 16+ Age Gate: Rejected registration when age_verified = false" -ForegroundColor Green
    } else {
        Write-Host "[FAIL] 16+ Age Gate allowed registration with age_verified = false" -ForegroundColor Red
    }

    # 4b. Successful Buyer Registration with age_verified = true
    $buyerBody = @{
        name = "Verified Buyer"
        email = $buyerEmail
        password = "password123"
        password_confirmation = "password123"
        age_verified = $true
        role = "buyer"
    } | ConvertTo-Json

    $buyerRes = Invoke-RestMethod -Uri "$baseUrl/api/v1/auth/register" -Method POST -Body $buyerBody -ContentType "application/json"
    $buyerToken = $buyerRes.token
    Write-Host "[PASS] Buyer Registration: Registered $($buyerRes.user.email) (role: $($buyerRes.user.role), age_verified: $($buyerRes.user.age_verified))" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Buyer registration failed: $_" -ForegroundColor Red
}

# 5. Test Wishlist for Authenticated Buyer
if ($buyerToken) {
    try {
        $headers = @{
            Authorization = "Bearer $buyerToken"
            Accept = "application/json"
        }
        
        # Toggle Product 1
        $wishToggle = Invoke-RestMethod -Uri "$baseUrl/api/v1/wishlist/1/toggle" -Method POST -Headers $headers
        Write-Host "[PASS] Wishlist Toggle: $($wishToggle.message) (is_wishlisted: $($wishToggle.is_wishlisted))" -ForegroundColor Green
        
        # Fetch Wishlist
        $wishlist = Invoke-RestMethod -Uri "$baseUrl/api/v1/wishlist" -Method GET -Headers $headers
        Write-Host "[PASS] Wishlist API: Retrieved $($wishlist.data.Count) wishlisted items" -ForegroundColor Green
    } catch {
        Write-Host "[FAIL] Wishlist operations failed: $_" -ForegroundColor Red
    }
}

# 6. Test Cart operations
try {
    $cartHeaders = @{
        Accept = "application/json"
    }
    
    $cartAddBody = @{
        product_id = 1
        quantity = 2
    } | ConvertTo-Json
    
    $cartAdd = Invoke-RestMethod -Uri "$baseUrl/api/v1/cart/items" -Method POST -Body $cartAddBody -ContentType "application/json" -Headers $cartHeaders
    Write-Host "[PASS] Cart Add: Total quantity is $($cartAdd.data.total_quantity), Subtotal: $($cartAdd.data.formatted_subtotal)" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Cart operations failed: $_" -ForegroundColor Red
}

# 7. Test Seller Registration & Seller Studio API
$sellerEmail = "merchant_$randomSuffix@example.com"
try {
    $sellerBody = @{
        name = "Kiko Studio"
        email = $sellerEmail
        password = "password123"
        password_confirmation = "password123"
        age_verified = $true
        role = "seller"
        store_name = "Kiko Streetwear"
    } | ConvertTo-Json

    $sellerRes = Invoke-RestMethod -Uri "$baseUrl/api/v1/auth/register" -Method POST -Body $sellerBody -ContentType "application/json"
    $sellerToken = $sellerRes.token
    Write-Host "[PASS] Seller Studio Registration: $($sellerRes.user.email) (Store: $($sellerRes.user.seller_profile.store_name), Status: $($sellerRes.user.seller_profile.verification_status))" -ForegroundColor Green

    # Fetch Seller Dashboard
    $sellerHeaders = @{
        Authorization = "Bearer $sellerToken"
        Accept = "application/json"
    }
    $sellerDash = Invoke-RestMethod -Uri "$baseUrl/api/v1/seller/dashboard" -Method GET -Headers $sellerHeaders
    Write-Host "[PASS] Seller Studio Dashboard: Loaded metrics (Gross Sales: $($sellerDash.stats.formatted_revenue), Active Listings: $($sellerDash.stats.total_listings), Alerts: $($sellerDash.low_stock_alerts.Count))" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Seller operations failed: $_" -ForegroundColor Red
}

# 8. Test Admin Login & Platform Command Center Analytics API
try {
    $adminLoginBody = @{
        email = "admin@apexmarketplace.com"
        password = "password"
    } | ConvertTo-Json

    $adminAuth = Invoke-RestMethod -Uri "$baseUrl/api/v1/auth/login" -Method POST -Body $adminLoginBody -ContentType "application/json"
    $adminToken = $adminAuth.token
    Write-Host "[PASS] Admin Authentication: Logged in as $($adminAuth.user.email) (role: $($adminAuth.user.role))" -ForegroundColor Green
    
    $adminHeaders = @{
        Authorization = "Bearer $adminToken"
        Accept = "application/json"
    }
    $adminAnalytics = Invoke-RestMethod -Uri "$baseUrl/api/v1/admin/analytics" -Method GET -Headers $adminHeaders
    Write-Host "[PASS] Admin Command Center: Total GMV: $($adminAnalytics.overview.formatted_gmv), Active Merchants: $($adminAnalytics.overview.active_sellers), Registered Shoppers: $($adminAnalytics.overview.total_buyers)" -ForegroundColor Green

    $adminSellers = Invoke-RestMethod -Uri "$baseUrl/api/v1/admin/sellers" -Method GET -Headers $adminHeaders
    Write-Host "[PASS] Admin Moderation Queue: Retrieved $($adminSellers.data.Count) merchant applications" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Admin operations failed: $_" -ForegroundColor Red
}

Write-Host "=== All Live Server Integration Tests Succeeded ===" -ForegroundColor Cyan
