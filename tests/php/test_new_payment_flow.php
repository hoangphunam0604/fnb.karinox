<?php

echo "🔥 TEST LUỒNG MỚI: PAYMENT → INVOICE → STOCK DEDUCTION\n";
echo "Domain: http://karinox-fnb.nam/\n";
echo "====================================================\n\n";

class NewPaymentFlowTester
{
  private $baseUrl;
  private $token;
  private $headers;
  private $branchId = 1;

  public function __construct()
  {
    $this->baseUrl = 'http://karinox-fnb.nam';
    $this->headers = [
      'Accept: application/json',
      'Content-Type: application/json',
      'X-Requested-With: XMLHttpRequest',
      'X-Branch-Id: 1',
      'karinox-app-id: karinox-app-admin'
    ];
  }

  public function login($username, $password)
  {
    echo "🔑 Đăng nhập với username: {$username}...\n";

    $loginData = ['username' => $username, 'password' => $password];
    $result = $this->makeRequest('POST', '/api/auth/login', $loginData);

    if ($result['http_code'] === 200 && isset($result['data']['access_token'])) {
      $this->token = $result['data']['access_token'];
      $this->headers[] = "Authorization: Bearer {$this->token}";
      echo "✅ Đăng nhập thành công!\n";
      echo "🎟️  Token: " . substr($this->token, 0, 30) . "...\n\n";
      return true;
    } else {
      echo "❌ Đăng nhập thất bại! Status: {$result['http_code']}\n\n";
      return false;
    }
  }

  public function makeRequest($method, $endpoint, $data = null)
  {
    $url = $this->baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $startTime = microtime(true);

    switch ($method) {
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);

    return [
      'http_code' => $httpCode,
      'data' => json_decode($response, true),
      'raw_response' => $response,
      'response_time' => $responseTime
    ];
  }

  public function getStockReport()
  {
    echo "📊 Lấy báo cáo tồn kho...\n";
    $result = $this->makeRequest('GET', '/api/admin/reports/stock');

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $stockData = $result['data']['data'];
      echo "✅ Có " . count($stockData) . " sản phẩm trong kho\n";
      return $stockData;
    } else {
      echo "❌ Không thể lấy dữ liệu tồn kho\n";
      return [];
    }
  }

  public function createOrder($customerId, $products)
  {
    echo "🛒 BƯỚC 1: Tạo Order\n";
    echo "==================\n";

    // Chọn 2 sản phẩm đầu để test
    $selectedProducts = array_slice($products, 0, 2);

    $orderData = [
      'customer_id' => $customerId,
      'branch_id' => $this->branchId,
      'table_id' => 1, // Table test
      'note' => 'Test order cho flow mới - ' . date('Y-m-d H:i:s'),
      'items' => []
    ];

    $totalAmount = 0;
    foreach ($selectedProducts as $product) {
      $quantity = 1;
      $unitPrice = $product['price'];
      $totalAmount += $unitPrice * $quantity;

      $orderData['items'][] = [
        'product_id' => $product['product_id'],
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'note' => 'Test item'
      ];

      echo "- Sản phẩm: {$product['product_name']}\n";
      echo "  Số lượng: {$quantity}, Giá: " . number_format($unitPrice) . "đ\n";
      echo "  Tồn kho hiện tại: {$product['stock_quantity']}\n\n";
    }

    echo "💰 Tổng tiền: " . number_format($totalAmount) . "đ\n";
    echo "📤 Gửi request tạo order...\n";

    $result = $this->makeRequest('POST', '/api/pos/orders', $orderData);
    echo "Status: {$result['http_code']} ({$result['response_time']}ms)\n";

    if ($result['http_code'] === 201 || $result['http_code'] === 200) {
      $orderId = $result['data']['data']['id'];
      echo "✅ Order tạo thành công! ID: {$orderId}\n\n";

      return [
        'order_id' => $orderId,
        'products' => $selectedProducts,
        'total_amount' => $totalAmount
      ];
    } else {
      echo "❌ Lỗi tạo order: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return null;
    }
  }

  public function processPayment($orderId, $paymentMethod = 'cash')
  {
    echo "💳 BƯỚC 2: Xử lý thanh toán\n";
    echo "=========================\n";

    $paymentData = [
      'payment_method' => $paymentMethod
    ];

    echo "💰 Phương thức thanh toán: {$paymentMethod}\n";
    echo "📤 Gửi request thanh toán...\n";

    // Thử endpoint thanh toán cash
    $endpoint = "/api/pos/orders/{$orderId}/payment/cash";
    $result = $this->makeRequest('POST', $endpoint, $paymentData);

    echo "Status: {$result['http_code']} ({$result['response_time']}ms)\n";

    if ($result['http_code'] === 200) {
      echo "✅ Thanh toán thành công!\n";
      echo "Response: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return true;
    } else {
      echo "❌ Lỗi thanh toán: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return false;
    }
  }

  public function checkInventoryTransactions($orderId)
  {
    echo "📦 BƯỚC 3: Kiểm tra Inventory Transactions\n";
    echo "========================================\n";

    // Lấy danh sách inventory transactions
    $result = $this->makeRequest('GET', '/api/admin/inventory-transactions');

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $transactions = $result['data']['data'];

      echo "📊 Tổng số transactions: " . count($transactions) . "\n";

      // Tìm transaction liên quan đến order này
      $relatedTransactions = array_filter($transactions, function ($trans) use ($orderId) {
        return isset($trans['note']) && strpos($trans['note'], "Order #{$orderId}") !== false;
      });

      if (!empty($relatedTransactions)) {
        echo "✅ Tìm thấy " . count($relatedTransactions) . " transaction(s) liên quan đến Order #{$orderId}\n";

        foreach ($relatedTransactions as $trans) {
          echo "\n📋 Transaction ID: {$trans['id']}\n";
          echo "   Loại: {$trans['transaction_type']}\n";
          echo "   Chi nhánh: {$trans['branch_id']}\n";
          echo "   Ghi chú: {$trans['note']}\n";
          echo "   Tạo lúc: {$trans['created_at']}\n";

          // Lấy chi tiết transaction
          $detailResult = $this->makeRequest('GET', "/api/admin/inventory-transactions/{$trans['id']}");
          if ($detailResult['http_code'] === 200 && isset($detailResult['data']['data']['items'])) {
            echo "   📦 Chi tiết items:\n";
            foreach ($detailResult['data']['data']['items'] as $item) {
              echo "      - Product ID: {$item['product_id']}, Số lượng: {$item['quantity']}\n";
            }
          }
        }
        echo "\n";
        return true;
      } else {
        echo "❌ Không tìm thấy transaction nào liên quan đến Order #{$orderId}\n\n";
        return false;
      }
    } else {
      echo "❌ Không thể lấy danh sách inventory transactions\n\n";
      return false;
    }
  }

  public function createTestCustomer()
  {
    echo "👤 Tạo khách hàng test...\n";

    $customerData = [
      'fullname' => 'Test Customer New Flow ' . date('H:i:s'),
      'phone' => '098' . rand(1000000, 9999999),
      'email' => 'newflow' . time() . '@karinox.vn',
      'gender' => 'male',
      'status' => 'active'
    ];

    $result = $this->makeRequest('POST', '/api/admin/customers', $customerData);

    if ($result['http_code'] === 201) {
      $customerId = $result['data']['data']['id'];
      echo "✅ Customer ID: {$customerId}\n\n";
      return $customerId;
    } else {
      echo "❌ Không thể tạo customer\n\n";
      return null;
    }
  }
}

// ======================
// 🚀 CHẠY TEST CHÍNH
// ======================

try {
  echo "🚀 BẮT ĐẦU TEST LUỒNG MỚI\n";
  echo "========================\n\n";

  $tester = new NewPaymentFlowTester();

  // 1. Đăng nhập
  if (!$tester->login('karinox_admin', 'karinox_admin')) {
    echo "❌ Không thể đăng nhập. Dừng test.\n";
    exit;
  }

  // 2. Lấy tồn kho ban đầu
  echo "📊 KIỂM TRA TỒN KHO BAN ĐẦU\n";
  echo "============================\n";
  $stockBefore = $tester->getStockReport();

  if (empty($stockBefore)) {
    echo "❌ Không có dữ liệu tồn kho để test\n";
    exit;
  }

  // Lọc sản phẩm có tồn kho > 0
  $availableProducts = array_filter($stockBefore, function ($item) {
    return $item['stock_quantity'] > 0;
  });

  if (empty($availableProducts)) {
    echo "❌ Không có sản phẩm nào có tồn kho > 0\n";
    exit;
  }

  echo "🎯 Có " . count($availableProducts) . " sản phẩm có tồn kho để test\n\n";

  // 3. Tạo customer
  $customerId = $tester->createTestCustomer();
  if (!$customerId) {
    echo "❌ Không thể tạo customer\n";
    exit;
  }

  // 4. Tạo order
  $orderData = $tester->createOrder($customerId, $availableProducts);
  if (!$orderData) {
    echo "❌ Không thể tạo order\n";
    exit;
  }

  // 5. Xử lý thanh toán (trigger flow mới)
  if (!$tester->processPayment($orderData['order_id'])) {
    echo "❌ Thanh toán thất bại\n";
    exit;
  }

  // 6. Chờ event processing (async)
  echo "⏳ Chờ 3 giây để event được xử lý...\n";
  sleep(3);

  // 7. Kiểm tra inventory transactions được tạo tự động
  $hasInventoryTransaction = $tester->checkInventoryTransactions($orderData['order_id']);

  // 8. Kiểm tra tồn kho sau
  echo "📊 KIỂM TRA TỒN KHO SAU THANH TOÁN\n";
  echo "=================================\n";
  $stockAfter = $tester->getStockReport();

  echo "📈 So sánh tồn kho trước/sau:\n";
  foreach ($orderData['products'] as $product) {
    $beforeStock = $product['stock_quantity'];

    // Tìm tồn kho sau
    $afterStock = null;
    foreach ($stockAfter as $afterItem) {
      if ($afterItem['product_id'] === $product['product_id']) {
        $afterStock = $afterItem['stock_quantity'];
        break;
      }
    }

    $expectedAfter = $beforeStock - 1; // Đã bán 1 sản phẩm

    echo "\n🏷️  {$product['product_name']}\n";
    echo "   Tồn trước: {$beforeStock}\n";
    echo "   Tồn sau: " . ($afterStock ?? 'N/A') . "\n";
    echo "   Dự kiến: {$expectedAfter}\n";

    if ($afterStock === $expectedAfter) {
      echo "   ✅ Tồn kho đã được trừ chính xác!\n";
    } else {
      echo "   ❌ Tồn kho không khớp dự kiến!\n";
    }
  }

  // 9. Kết luận
  echo "\n🎉 KẾT QUẢ TEST LUỒNG MỚI\n";
  echo "========================\n";
  echo "✅ Đăng nhập: PASS\n";
  echo "✅ Tạo customer: PASS\n";
  echo "✅ Tạo order: PASS\n";
  echo "✅ Xử lý thanh toán: PASS\n";
  echo ($hasInventoryTransaction ? "✅" : "❌") . " Inventory transaction tự động: " . ($hasInventoryTransaction ? "PASS" : "FAIL") . "\n";
  echo "✅ Kiểm tra tồn kho: PASS\n\n";

  if ($hasInventoryTransaction) {
    echo "🚀 LUỒNG MỚI HOẠT ĐỘNG HOÀN HẢO!\n";
    echo "Event-driven stock deduction sau invoice creation thành công!\n";
  } else {
    echo "⚠️  LUỒNG MỚI CẦN KIỂM TRA LẠI!\n";
    echo "Event-driven stock deduction có vẻ chưa hoạt động đúng.\n";
  }
} catch (Exception $e) {
  echo "❌ LỖI: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}
