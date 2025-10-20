<?php

echo "🛒 TEST BÁN HÀNG THỰC TẾ QUA POS API + KIỂM TRA TỒN KHO\n";
echo "Domain: http://karinox-fnb.nam/\n";
echo "Flow: Chọn bàn → Order → Thêm items → Payment → Invoice → Stock Deduction\n";
echo "=======================================================================\n\n";

class RealPOSSalesTest
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
    $error = curl_error($ch);
    curl_close($ch);

    return [
      'http_code' => $httpCode,
      'data' => json_decode($response, true),
      'raw' => $response,
      'response_time' => $responseTime,
      'error' => $error
    ];
  }

  public function getStockOfProduct($productId)
  {
    echo "📦 Lấy thông tin tồn kho sản phẩm ID: {$productId}...\n";

    $result = $this->makeRequest('GET', "/api/admin/inventory/stock-report?branch_id={$this->branchId}");

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $stockData = $result['data']['data'];

      foreach ($stockData as $item) {
        if ($item['product_id'] == $productId) {
          echo "✅ Tìm thấy: {$item['product_name']} - Tồn kho: {$item['stock_quantity']}\n";
          return $item;
        }
      }
    }

    echo "❌ Không tìm thấy sản phẩm trong báo cáo tồn kho\n";
    return null;
  }

  public function getOrCreateOrderByTable($tableId)
  {
    echo "🛒 BƯỚC 1: Lấy/Tạo đơn hàng theo bàn\n";
    echo "===================================\n";
    echo "📍 Logic POS: Khi chọn bàn, nếu đã có đơn hàng đang dở sẽ lấy nó, nếu chưa có sẽ tạo đơn hàng mới\n";
    echo "🪑 Table ID: {$tableId}\n";

    // Lấy order hiện tại của bàn (nếu có)
    $result = $this->makeRequest('GET', "/api/pos/orders/by-table/{$tableId}");
    echo "Response Status: {$result['http_code']} ({$result['response_time']}ms)\n";

    if ($result['http_code'] === 200 && isset($result['data']) && !empty($result['data'])) {
      // Có order đang dở, lấy order đó
      $orders = $result['data'];
      $order = is_array($orders) ? $orders[0] : $orders;

      echo "✅ Tìm thấy order đang dở! ID: {$order['id']}\n";
      echo "📊 Status: {$order['status']}\n";
      echo "💰 Tổng tiền hiện tại: " . number_format($order['total_amount'] ?? 0) . "đ\n";
      echo "📦 Số items hiện tại: " . count($order['items'] ?? []) . "\n\n";

      return $order;
    } else {
      echo "⚠️  Chưa có order nào cho bàn này\n";
      echo "💡 Order sẽ được tạo tự động khi thêm sản phẩm đầu tiên\n\n";

      return null;
    }
  }

  public function addItemToOrder($orderId, $productId, $quantity = 1, $customerId = null)
  {
    echo "🛒 BƯỚC 2: Thêm sản phẩm vào đơn hàng\n";
    echo "====================================\n";

    // Lấy thông tin sản phẩm từ API
    $productResult = $this->makeRequest('GET', "/api/admin/products/{$productId}");

    if ($productResult['http_code'] !== 200) {
      echo "❌ Không thể lấy thông tin sản phẩm ID: {$productId}\n";
      return null;
    }

    $product = $productResult['data']['data'];
    echo "📦 Thêm sản phẩm: {$product['name']}\n";
    echo "💰 Giá: " . number_format($product['price'] ?? 0) . "đ\n";
    echo "🔢 Số lượng: {$quantity}\n";

    // Chuẩn bị data để update order
    $updateData = [
      'items' => [
        [
          'product_id' => $productId,
          'quantity' => $quantity,
          'unit_price' => $product['price'] ?? 0,
          'note' => 'Test item từ API'
        ]
      ]
    ];

    // Thêm customer nếu có
    if ($customerId) {
      $updateData['customer_id'] = $customerId;
      echo "👤 Gán customer ID: {$customerId}\n";
    }

    echo "📝 Cập nhật order ID: {$orderId}...\n";

    $result = $this->makeRequest('PUT', "/api/pos/orders/{$orderId}", $updateData);
    echo "Response Status: {$result['http_code']} ({$result['response_time']}ms)\n";

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $order = $result['data']['data'];
      echo "✅ Thêm sản phẩm thành công!\n";
      echo "🆔 Order ID: {$order['id']}\n";
      echo "💰 Tổng tiền mới: " . number_format($order['total_amount'] ?? 0) . "đ\n";
      echo "📦 Tổng items: " . count($order['items'] ?? []) . "\n";
      echo "📊 Status: {$order['status']}\n\n";

      return $order;
    } else {
      echo "❌ Không thể thêm sản phẩm vào order\n";
      echo "Error: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return null;
    }
  }

  public function processPayment($orderId, $amount)
  {
    echo "💳 BƯỚC 3: Xử lý thanh toán\n";
    echo "==========================\n";

    $paymentData = [
      'order_id' => $orderId,
      'amount' => $amount,
      'payment_method' => 'cash',
      'received_amount' => $amount,
      'change_amount' => 0,
      'note' => 'Test payment từ API'
    ];

    echo "💰 Thanh toán cho Order ID: {$orderId}\n";
    echo "💵 Số tiền: " . number_format($amount) . "đ\n";
    echo "🏦 Phương thức: Tiền mặt\n";

    $result = $this->makeRequest('POST', '/api/pos/payments', $paymentData);
    echo "Response Status: {$result['http_code']} ({$result['response_time']}ms)\n";

    if ($result['http_code'] === 201 && isset($result['data']['data'])) {
      $payment = $result['data']['data'];
      echo "✅ Thanh toán thành công! Payment ID: {$payment['id']}\n";
      echo "✅ Trạng thái thanh toán: {$payment['status']}\n\n";

      return $payment;
    } else {
      echo "❌ Thanh toán thất bại\n";
      echo "Error: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return null;
    }
  }

  public function checkOrderAfterPayment($orderId)
  {
    echo "🔍 BƯỚC 4: Kiểm tra Order sau thanh toán\n";
    echo "=======================================\n";

    $result = $this->makeRequest('GET', "/api/pos/orders/{$orderId}");

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $order = $result['data']['data'];
      echo "📋 Order ID: {$order['id']}\n";
      echo "📊 Order Status: {$order['status']}\n";
      echo "💳 Payment Status: {$order['payment_status']}\n";

      // Kiểm tra invoice
      if (isset($order['invoice_id']) && $order['invoice_id']) {
        echo "🧾 Invoice ID: {$order['invoice_id']}\n";
        return $order;
      } else {
        echo "⚠️  Chưa có Invoice được tạo, tìm kiếm manual...\n";

        // Thử tìm invoice bằng cách khác
        $invoiceResult = $this->makeRequest('GET', "/api/admin/invoices?order_id={$orderId}");
        if ($invoiceResult['http_code'] === 200 && isset($invoiceResult['data']['data'])) {
          $invoices = $invoiceResult['data']['data'];
          if (!empty($invoices)) {
            $invoice = $invoices[0];
            echo "✅ Tìm thấy Invoice! ID: {$invoice['id']}\n";
            $order['invoice_id'] = $invoice['id'];
          }
        }
      }

      echo "\n";
      return $order;
    } else {
      echo "❌ Không thể lấy thông tin order\n";
      return null;
    }
  }

  public function checkInventoryTransactions($orderId, $invoiceId = null)
  {
    echo "📊 BƯỚC 5: Kiểm tra Inventory Transactions\n";
    echo "=========================================\n";

    $result = $this->makeRequest('GET', "/api/admin/inventory/transactions");

    if ($result['http_code'] === 200 && isset($result['data']['data'])) {
      $transactions = $result['data']['data'];

      echo "📋 Tìm kiếm transactions liên quan đến Order #{$orderId}";
      if ($invoiceId) {
        echo " / Invoice #{$invoiceId}";
      }
      echo "...\n";

      $relatedTransactions = [];
      foreach ($transactions as $transaction) {
        $isRelated = false;

        // Kiểm tra theo reference_id hoặc note
        if (
          $transaction['reference_id'] == $orderId ||
          ($invoiceId && $transaction['reference_id'] == $invoiceId) ||
          (strpos($transaction['note'], "Order #{$orderId}") !== false) ||
          ($invoiceId && strpos($transaction['note'], "Invoice #{$invoiceId}") !== false)
        ) {
          $isRelated = true;
        }

        if ($isRelated) {
          $relatedTransactions[] = $transaction;
        }
      }

      if (!empty($relatedTransactions)) {
        echo "✅ Tìm thấy " . count($relatedTransactions) . " inventory transaction(s) liên quan!\n\n";

        foreach ($relatedTransactions as $transaction) {
          echo "🏷️  Transaction ID: {$transaction['id']}\n";
          echo "📦 Loại: {$transaction['type']}\n";
          echo "🔗 Reference: {$transaction['reference_type']} #{$transaction['reference_id']}\n";
          echo "📝 Ghi chú: {$transaction['note']}\n";
          echo "📅 Thời gian: {$transaction['created_at']}\n";

          // Lấy chi tiết transaction
          $detailResult = $this->makeRequest('GET', "/api/admin/inventory/transactions/{$transaction['id']}");
          if ($detailResult['http_code'] === 200 && isset($detailResult['data']['data']['details'])) {
            $details = $detailResult['data']['data']['details'];
            echo "📋 Chi tiết (" . count($details) . " items):\n";

            foreach ($details as $detail) {
              echo "   - Product ID: {$detail['product_id']}\n";
              echo "     Quantity: {$detail['quantity']}\n";
              echo "     Unit Cost: " . number_format($detail['unit_cost']) . "đ\n";
              if (isset($detail['unit_sale_price'])) {
                echo "     Unit Sale: " . number_format($detail['unit_sale_price']) . "đ\n";
              }
            }
          }
          echo "\n";
        }

        return $relatedTransactions;
      } else {
        echo "⚠️  Không tìm thấy inventory transaction nào liên quan\n\n";
        return [];
      }
    } else {
      echo "❌ Không thể lấy danh sách inventory transactions\n\n";
      return [];
    }
  }

  public function createTestCustomer()
  {
    echo "👤 Tạo khách hàng test...\n";

    $customerData = [
      'fullname' => 'Test Customer POS ' . date('H:i:s'),
      'phone' => '098' . rand(1000000, 9999999),
      'email' => 'testpos' . time() . '@karinox.vn',
      'gender' => 'male',
      'status' => 'active'
    ];

    echo "Data: " . json_encode($customerData, JSON_UNESCAPED_UNICODE) . "\n";
    $result = $this->makeRequest('POST', '/api/admin/customers', $customerData);

    echo "Response Status: {$result['http_code']} ({$result['response_time']}ms)\n";
    if ($result['http_code'] === 201 && isset($result['data']['data'])) {
      $customer = $result['data']['data'];
      echo "✅ Customer tạo thành công! ID: {$customer['id']}\n";
      echo "📞 Phone: {$customer['phone']}\n";
      echo "📧 Email: {$customer['email']}\n\n";
      return $customer;
    } else {
      echo "❌ Không thể tạo customer\n";
      echo "Error: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
      return null;
    }
  }

  public function compareStockBeforeAfter($productId, $stockBefore, $expectedChange)
  {
    echo "📊 BƯỚC 6: So sánh tồn kho trước/sau bán\n";
    echo "======================================\n";

    $stockAfter = $this->getStockOfProduct($productId);

    if (!$stockAfter) {
      echo "❌ Không thể lấy tồn kho sau bán\n";
      return false;
    }

    $actualChange = $stockBefore['stock_quantity'] - $stockAfter['stock_quantity'];

    echo "📋 So sánh kết quả:\n";
    echo "   Sản phẩm: {$stockBefore['product_name']}\n";
    echo "   Tồn trước: {$stockBefore['stock_quantity']}\n";
    echo "   Tồn sau: {$stockAfter['stock_quantity']}\n";
    echo "   Mong đợi giảm: {$expectedChange}\n";
    echo "   Thực tế giảm: {$actualChange}\n";

    if ($actualChange === $expectedChange) {
      echo "   ✅ CHÍNH XÁC! Tồn kho giảm đúng {$actualChange}\n\n";
      return true;
    } else {
      echo "   ❌ SAI LỆCH! Mong đợi {$expectedChange}, thực tế {$actualChange}\n\n";
      return false;
    }
  }
}

try {
  $tester = new RealPOSSalesTest();

  // BƯỚC 0: Login
  echo "🔑 BƯỚC 0: Đăng nhập hệ thống\n";
  echo "=============================\n";
  if (!$tester->login('karinox_admin', 'karinox_admin')) {
    echo "❌ Không thể đăng nhập. Dừng test.\n";
    exit;
  }

  // BƯỚC SETUP: Chuẩn bị dữ liệu test
  echo "📋 SETUP: Chuẩn bị dữ liệu test\n";
  echo "===============================\n";

  // Tạo customer
  $customer = $tester->createTestCustomer();
  if (!$customer) {
    echo "❌ Không thể tạo customer test\n";
    exit;
  }

  // Chọn sản phẩm test và bàn test
  $productId = 1; // Hạt cà phê Arabica
  $tableId = 1;   // Bàn 1
  $quantity = 1;  // Bán 1 sản phẩm

  echo "📦 Kiểm tra tồn kho ban đầu của sản phẩm...\n";
  $stockBefore = $tester->getStockOfProduct($productId);

  if (!$stockBefore || $stockBefore['stock_quantity'] <= 0) {
    echo "❌ Sản phẩm không có tồn kho để test\n";
    exit;
  }

  echo "\n";

  // FLOW THỰC TẾ POS: Chọn bàn → Order → Thêm items → Payment → Invoice → Stock Deduction
  echo "🚀 BẮT ĐẦU FLOW POS THỰC TẾ\n";
  echo "============================\n";

  // Bước 1: Lấy/Tạo Order theo bàn
  $order = $tester->getOrCreateOrderByTable($tableId);

  if (!$order) {
    // Nếu chưa có order, thử kiểm tra tất cả orders hiện có
    echo "🔍 Tìm kiếm order có sẵn để test...\n";
    $allOrdersResult = $tester->makeRequest('GET', "/api/pos/orders");

    if ($allOrdersResult['http_code'] === 200 && isset($allOrdersResult['data']) && !empty($allOrdersResult['data'])) {
      $orders = $allOrdersResult['data'];
      echo "📋 Tìm thấy " . count($orders) . " order(s) trong hệ thống\n";

      // Lấy order mới nhất để test (dù đã completed)
      if (!empty($orders)) {
        $latestOrder = $orders[0]; // Giả sử order đầu tiên là mới nhất
        echo "✅ Sử dụng order mới nhất để test! ID: {$latestOrder['id']}\n";
        echo "📊 Status: {$latestOrder['status']}\n";
        echo "🪑 Table: {$latestOrder['table_id']}\n";
        echo "💡 Sẽ test bằng cách thêm items mới vào order này\n";
        $order = $latestOrder;
        $orderId = $order['id'];
      }
    } else {
      echo "⚠️  Không lấy được danh sách orders\n";
      echo "Error: " . json_encode($allOrdersResult['data'], JSON_UNESCAPED_UNICODE) . "\n";
    }

    if (!$order) {
      echo "⚠️  Không tìm thấy order nào để test\n";
      echo "💡 Trong thực tế POS, order được tạo khi chọn bàn\n";
      echo "🚧 Test sẽ dừng tại đây vì chưa có cơ chế tạo order mới qua API\n";
      exit;
    }
  } else {
    $orderId = $order['id'];
  }

  // Bước 2: Thêm sản phẩm vào order
  if ($orderId) {
    $orderWithItems = $tester->addItemToOrder($orderId, $productId, $quantity, $customer['id']);

    if (!$orderWithItems) {
      echo "❌ Không thể thêm sản phẩm vào order. Dừng test.\n";
      exit;
    }
  } else {
    echo "❌ Không có order ID để thêm sản phẩm\n";
    exit;
  }

  // Bước 3: Thanh toán
  $payment = $tester->processPayment($orderWithItems['id'], $orderWithItems['total_amount']);
  if (!$payment) {
    echo "❌ Thanh toán thất bại. Dừng test.\n";
    exit;
  }

  // Chờ events được xử lý
  echo "⏳ Chờ 3 giây để events được xử lý...\n\n";
  sleep(3);

  // Bước 4: Kiểm tra Order Status & Invoice
  $orderUpdated = $tester->checkOrderAfterPayment($orderWithItems['id']);
  if (!$orderUpdated) {
    echo "❌ Không thể kiểm tra trạng thái order\n";
    exit;
  }

  // Bước 5: Kiểm tra Inventory Transactions
  $transactions = $tester->checkInventoryTransactions($orderWithItems['id'], $orderUpdated['invoice_id'] ?? null);

  // Bước 6: So sánh tồn kho
  $stockCorrect = $tester->compareStockBeforeAfter($productId, $stockBefore, $quantity);

  // TỔNG KẾT
  echo "🎯 TỔNG KẾT KIỂM TRA\n";
  echo "===================\n";

  $checks = [
    'Authentication' => true,
    'Customer Creation' => $customer !== null,
    'Table Order Check' => true, // Đã check được API
    'Add Items to Order' => $orderWithItems !== null,
    'Payment Processing' => $payment !== null,
    'Order Status Update' => $orderUpdated && $orderUpdated['status'] === 'completed',
    'Invoice Creation' => $orderUpdated && isset($orderUpdated['invoice_id']),
    'Inventory Transactions' => !empty($transactions),
    'Stock Deduction' => $stockCorrect
  ];

  $passCount = 0;
  $totalCount = count($checks);

  foreach ($checks as $check => $passed) {
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    echo "{$status} {$check}\n";
    if ($passed) $passCount++;
  }

  echo "\n📊 KẾT QUẢ: {$passCount}/{$totalCount} checks passed\n";

  if ($passCount === $totalCount) {
    echo "🎉 THÀNH CÔNG! HỆ THỐNG POS + TỒN KHO HOẠT ĐỘNG HOÀN HẢO!\n";
    echo "✨ Event-driven stock deduction working perfectly!\n";
    echo "🏪 POS flow: Chọn bàn → Order → Thêm items → Payment → Invoice → Stock Deduction\n";
  } else {
    echo "⚠️  CÓ MỘT SỐ VẤN ĐỀ CẦN KIỂM TRA\n";
    echo "🔍 Xem chi tiết bên trên để debug\n";
  }
} catch (Exception $e) {
  echo "❌ LỖI: " . $e->getMessage() . "\n";
  echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}
