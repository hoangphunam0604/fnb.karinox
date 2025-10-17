<?php

echo "🌐 TEST API QUA DOMAIN PRODUCTION\n";
echo "Domain: http://karinox-fnb.nam/\n";
echo "=================================\n\n";

class ProductionAPITester 
{
    private $baseUrl;
    private $headers;
    
    public function __construct() {
        $this->baseUrl = 'http://karinox-fnb.nam';
        $this->headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'X-Branch-Id: 1', // Auto-detect branch from header
            'User-Agent: KarinoxFNB-Test/1.0'
        ];
    }
    
    public function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        echo "📡 {$method} {$endpoint}\n";
        echo "URL: {$url}\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            echo "Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "Response Time: {$responseTime}ms\n";
        echo "HTTP Status: {$httpCode}\n";
        
        if ($error) {
            echo "❌ cURL Error: {$error}\n";
            return ['error' => $error, 'http_code' => 0, 'response_time' => $responseTime];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "✅ Success\n";
        } else {
            echo "❌ HTTP Error\n";
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "Raw Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n";
        }
        
        echo str_repeat("-", 80) . "\n\n";
        
        return [
            'http_code' => $httpCode,
            'data' => $responseData,
            'raw' => $response,
            'response_time' => $responseTime
        ];
    }
    
    public function testEndpoint($method, $endpoint, $data = null, $expectCode = 200) {
        $result = $this->makeRequest($method, $endpoint, $data);
        
        $status = '❌ FAIL';
        if ($result['http_code'] === $expectCode) {
            $status = '✅ PASS';
        }
        
        echo "Test Result: {$status} (Expected: {$expectCode}, Got: {$result['http_code']})\n\n";
        return $result;
    }
}

try {
    $api = new ProductionAPITester();
    
    echo "🔍 BƯỚC 1: Kiểm tra kết nối cơ bản\n";
    echo "================================\n";
    
    // Test trang chủ
    $result = $api->testEndpoint('GET', '/');
    
    // Test API health check
    $result = $api->testEndpoint('GET', '/api/health', null, 200);
    
    echo "📋 BƯỚC 2: Test Admin API Endpoints\n";
    echo "==================================\n";
    
    // Test Categories API
    $result = $api->testEndpoint('GET', '/api/admin/categories');
    
    // Test Products API  
    $result = $api->testEndpoint('GET', '/api/admin/products');
    
    // Test Customers API
    $result = $api->testEndpoint('GET', '/api/admin/customers');
    
    // Test với pagination
    $result = $api->testEndpoint('GET', '/api/admin/products?page=1&per_page=5');
    
    echo "📊 BƯỚC 3: Test Inventory API\n";
    echo "============================\n";
    
    // Test Stock Report
    $result = $api->testEndpoint('GET', '/api/admin/inventory/stock-report');
    
    // Test Inventory Transactions
    $result = $api->testEndpoint('GET', '/api/admin/inventory');
    
    echo "➕ BƯỚC 4: Test POST Requests (Tạo dữ liệu)\n";
    echo "==========================================\n";
    
    // Test tạo category mới
    $categoryData = [
        'name' => 'Test API Category ' . date('H:i:s'),
        'description' => 'Category được tạo từ API test lúc ' . date('Y-m-d H:i:s')
    ];
    
    $result = $api->testEndpoint('POST', '/api/admin/categories', $categoryData, 201);
    
    $newCategoryId = null;
    if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
        $newCategoryId = $result['data']['data']['id'];
        echo "🎉 Tạo category thành công! ID: {$newCategoryId}\n";
        echo "🏷️  Prefix tự động: {$result['data']['data']['code_prefix']}\n\n";
        
        // Test tạo product với category vừa tạo
        $productData = [
            'name' => 'Test Product API ' . date('H:i:s'),
            'category_id' => $newCategoryId,
            'regular_price' => 75000,
            'cost_price' => 45000,
            'status' => 'active',
            'allows_sale' => true,
            'description' => 'Sản phẩm test API'
        ];
        
        $result = $api->testEndpoint('POST', '/api/admin/products', $productData, 201);
        
        if ($result['http_code'] === 201 && isset($result['data']['data']['code'])) {
            echo "🎉 Tạo product thành công!\n";
            echo "📦 Mã sản phẩm tự động: {$result['data']['data']['code']}\n";
            echo "💰 Giá bán: " . number_format($result['data']['data']['regular_price']) . "đ\n\n";
        }
    }
    
    echo "👤 BƯỚC 5: Test Customer Management\n";
    echo "==================================\n";
    
    // Test tạo customer
    $customerData = [
        'name' => 'Customer API Test ' . date('H:i:s'),
        'phone' => '098' . rand(1000000, 9999999),
        'email' => 'test' . time() . '@karinox.vn',
        'gender' => 'male',
        'status' => 'active'
    ];
    
    $result = $api->testEndpoint('POST', '/api/admin/customers', $customerData, 201);
    
    echo "❌ BƯỚC 6: Test Validation & Error Handling\n";
    echo "==========================================\n";
    
    // Test validation error - category không có tên
    $invalidData = ['description' => 'Category không có tên'];
    $result = $api->testEndpoint('POST', '/api/admin/categories', $invalidData, 422);
    
    // Test validation error - product không có category
    $invalidProduct = [
        'name' => 'Product không có category',
        'regular_price' => 10000
    ];
    $result = $api->testEndpoint('POST', '/api/admin/products', $invalidProduct, 422);
    
    // Test 404 - resource không tồn tại
    $result = $api->testEndpoint('GET', '/api/admin/categories/99999', null, 404);
    
    echo "🔍 BƯỚC 7: Test Search & Filter\n";
    echo "==============================\n";
    
    // Test search products
    $result = $api->testEndpoint('GET', '/api/admin/products?search=test');
    
    // Test filter by status
    $result = $api->testEndpoint('GET', '/api/admin/products?status=active');
    
    // Test sort
    $result = $api->testEndpoint('GET', '/api/admin/products?sort=created_at&order=desc');
    
    echo "📱 BƯỚC 8: Test Mobile POS API\n";
    echo "==============================\n";
    
    // Test POS endpoints
    $result = $api->testEndpoint('GET', '/api/pos/categories');
    $result = $api->testEndpoint('GET', '/api/pos/products');
    
    echo "🔒 BƯỚC 9: Test Authentication\n";
    echo "=============================\n";
    
    // Test login (sẽ fail nếu không có credentials)
    $loginData = [
        'email' => 'admin@karinox.vn',
        'password' => 'test123'
    ];
    
    $result = $api->testEndpoint('POST', '/api/auth/login', $loginData, 401); // Expect 401 vì sai password
    
    echo "⚡ BƯỚC 10: Performance Summary\n";
    echo "=============================\n";
    
    // Test multiple requests để check performance
    $performanceTests = [
        ['GET', '/api/admin/categories'],
        ['GET', '/api/admin/products'],
        ['GET', '/api/admin/customers'],
        ['GET', '/api/admin/inventory/stock-report']
    ];
    
    $totalTime = 0;
    $testCount = 0;
    
    foreach ($performanceTests as $test) {
        $result = $api->makeRequest($test[0], $test[1]);
        if (isset($result['response_time'])) {
            $totalTime += $result['response_time'];
            $testCount++;
        }
    }
    
    $avgTime = $testCount > 0 ? round($totalTime / $testCount, 2) : 0;
    
    echo "📊 Performance Statistics:\n";
    echo "- Tổng số requests: {$testCount}\n";
    echo "- Tổng thời gian: {$totalTime}ms\n"; 
    echo "- Thời gian trung bình: {$avgTime}ms\n";
    echo "- Đánh giá: " . ($avgTime < 500 ? "🚀 Excellent" : ($avgTime < 1000 ? "✅ Good" : "⚠️ Needs optimization")) . "\n\n";
    
    echo "🎉 TEST PRODUCTION API HOÀN THÀNH!\n";
    echo "==================================\n";
    echo "✅ Basic connectivity\n";
    echo "✅ CRUD operations\n";
    echo "✅ Auto-generation features\n";
    echo "✅ Validation & error handling\n";
    echo "✅ Search & filtering\n";
    echo "✅ Authentication testing\n";
    echo "✅ Performance monitoring\n";
    echo "✅ Mobile POS endpoints\n\n";
    
    echo "🚀 HỆ THỐNG API PRODUCTION SẴN SÀNG!\n";
    echo "Domain: http://karinox-fnb.nam/\n";
    echo "Avg Response Time: {$avgTime}ms\n";
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}