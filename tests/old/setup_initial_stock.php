<?php

echo "📦 TẠO TỒN KHO BAN ĐẦU\n";
echo "Domain: http://karinox-fnb.nam/\n";
echo "=====================\n\n";

class StockInitializer 
{
    private $baseUrl;
    private $token;
    private $headers;
    private $branchId = 1;
    
    public function __construct() {
        $this->baseUrl = 'http://karinox-fnb.nam';
        $this->headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'X-Branch-Id: 1',
            'karinox-app-id: karinox-app-admin'
        ];
    }
    
    public function login($username, $password) {
        echo "🔑 Đăng nhập với username: {$username}...\n";
        
        $loginData = ['username' => $username, 'password' => $password];
        $result = $this->makeRequest('POST', '/api/auth/login', $loginData);
        
        if ($result['http_code'] === 200 && isset($result['data']['access_token'])) {
            $this->token = $result['data']['access_token'];
            $this->headers[] = "Authorization: Bearer {$this->token}";
            echo "✅ Đăng nhập thành công!\n\n";
            return true;
        } else {
            echo "❌ Đăng nhập thất bại!\n\n";
            return false;
        }
    }
    
    public function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'data' => json_decode($response, true),
            'response_time' => $responseTime
        ];
    }
    
    public function getProducts() {
        echo "📦 Lấy danh sách sản phẩm...\n";
        $result = $this->makeRequest('GET', '/api/admin/products');
        
        if ($result['http_code'] === 200 && isset($result['data']['data'])) {
            $products = $result['data']['data'];
            echo "✅ Found " . count($products) . " products\n\n";
            return $products;
        } else {
            echo "❌ Cannot get products\n\n";
            return [];
        }
    }
    
    public function createInitialStock($products) {
        echo "📊 Tạo tồn kho ban đầu...\n";
        echo "========================\n";
        
        foreach ($products as $product) {
            echo "📦 Nhập kho: {$product['code']} - {$product['name']}\n";
            
            $transactionData = [
                'type' => 'in',
                'reference_type' => 'initial_stock',
                'reference_id' => 'INIT_' . time() . '_' . $product['id'],
                'note' => 'Nhập kho ban đầu cho test',
                'items' => [
                    [
                        'product_id' => $product['id'],
                        'quantity' => 50, // Nhập 50 cái mỗi sản phẩm
                        'unit_cost' => $product['cost_price'] ?? ($product['regular_price'] * 0.6)
                    ]
                ]
            ];
            
            $result = $this->makeRequest('POST', '/api/admin/inventory', $transactionData);
            echo "   Status: {$result['http_code']} ({$result['response_time']}ms)\n";
            
            if ($result['http_code'] === 201) {
                echo "   ✅ Nhập thành công 50 sản phẩm\n";
            } else {
                echo "   ❌ Lỗi: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n";
            }
            echo "\n";
        }
    }
    
    public function checkStock() {
        echo "📊 Kiểm tra tồn kho sau nhập...\n";
        echo "==============================\n";
        
        $result = $this->makeRequest('GET', "/api/admin/inventory/stock-report?branch_id={$this->branchId}");
        
        if ($result['http_code'] === 200 && isset($result['data']['data'])) {
            $stockData = $result['data']['data'];
            echo "✅ Báo cáo tồn kho thành công!\n";
            echo "📦 Tổng số sản phẩm có tồn kho: " . count($stockData) . "\n\n";
            
            echo "📋 Chi tiết tồn kho:\n";
            foreach ($stockData as $item) {
                echo "- {$item['product_code']}: {$item['product_name']} | Tồn: {$item['stock_quantity']} | Giá: " . number_format($item['price']) . "đ\n";
            }
            
            return $stockData;
        } else {
            echo "❌ Không thể lấy báo cáo tồn kho\n";
            return [];
        }
    }
}

try {
    $initializer = new StockInitializer();
    
    // Login
    if (!$initializer->login('karinox_admin', 'karinox_admin')) {
        echo "❌ Không thể đăng nhập. Dừng.\n";
        exit;
    }
    
    // Get products
    $products = $initializer->getProducts();
    
    if (empty($products)) {
        echo "❌ Không có sản phẩm để nhập kho\n";
        exit;
    }
    
    // Create initial stock
    $initializer->createInitialStock($products);
    
    // Check final stock
    $stockData = $initializer->checkStock();
    
    if (!empty($stockData)) {
        echo "\n🎉 TẠO TỒN KHO THÀNH CÔNG!\n";
        echo "========================\n";
        echo "✅ " . count($stockData) . " sản phẩm có tồn kho\n";
        echo "✅ Sẵn sàng cho test bán hàng\n";
        echo "✅ Tổng giá trị tồn kho: " . number_format(array_sum(array_map(function($item) {
            return $item['stock_quantity'] * $item['price'];
        }, $stockData))) . "đ\n\n";
        
        echo "🚀 Bây giờ có thể chạy: php test_sales_inventory_api.php\n";
    } else {
        echo "\n❌ Không thể tạo tồn kho\n";
    }
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
}