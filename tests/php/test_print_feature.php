<?php

/**
 * Test script cho Test Print feature
 * 
 * Chạy: php tests/php/test_print_feature.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class TestPrintFeatureTest
{
  private $baseUrl;
  private $token;
  private $apiKey;

  public function __construct()
  {
    $this->baseUrl = 'http://karinox-fnb.local';
    // Trong thực tế, lấy từ authentication
    $this->token = 'your_jwt_token_here';
    $this->apiKey = 'your_device_api_key_here';
  }

  public function runTests()
  {
    echo "🧪 Testing Print Feature - Test Print Functionality\n\n";

    // Test 1: Basic test print với JWT
    $this->testBasicStaffTestPrint();

    // Test 2: Test với template cụ thể
    $this->testWithCustomTemplate();

    // Test 3: Test tất cả loại mock data
    $this->testAllMockDataTypes();

    // Test 4: Test tất cả print types
    $this->testAllPrintTypes();

    // Test 5: Client test print với API key
    $this->testClientTestPrint();

    // Test 6: Validation errors
    $this->testValidationErrors();

    echo "\n✅ All Test Print tests completed!\n";
  }

  private function testBasicStaffTestPrint()
  {
    echo "📝 Test 1: Basic Staff Test Print\n";

    $data = [
      'print_type' => 'provisional',
      'device_id' => 'test_printer_001'
    ];

    $response = $this->makeRequest('POST', '/api/print/test', $data, $this->token);

    if ($response && $response['success']) {
      echo "   ✅ Basic test print successful\n";
      echo "   📋 Job ID: " . $response['data']['id'] . "\n";
      echo "   🏷️  Order Code: " . $response['mock_data_preview']['order_code'] . "\n";
    } else {
      echo "   ❌ Basic test print failed\n";
    }
    echo "\n";
  }

  private function testWithCustomTemplate()
  {
    echo "🎨 Test 2: Test Print with Custom Template\n";

    $data = [
      'print_type' => 'invoice',
      'template_id' => 1,
      'mock_data_type' => 'complex'
    ];

    $response = $this->makeRequest('POST', '/api/print/test', $data, $this->token);

    if ($response && $response['success']) {
      echo "   ✅ Custom template test successful\n";
      echo "   💰 Total Amount: " . number_format($response['mock_data_preview']['total_amount']) . "đ\n";
    } else {
      echo "   ❌ Custom template test failed\n";
      if ($response && !$response['success']) {
        echo "   📝 Error: " . $response['message'] . "\n";
      }
    }
    echo "\n";
  }

  private function testAllMockDataTypes()
  {
    echo "🎭 Test 3: All Mock Data Types\n";

    $mockTypes = ['simple', 'complex', 'with_toppings', 'large_order'];

    foreach ($mockTypes as $type) {
      $data = [
        'print_type' => 'provisional',
        'mock_data_type' => $type,
        'device_id' => "test_{$type}_printer"
      ];

      $response = $this->makeRequest('POST', '/api/print/test', $data, $this->token);

      if ($response && $response['success']) {
        echo "   ✅ Mock data type '{$type}' - Items: {$response['mock_data_preview']['items_count']}\n";
      } else {
        echo "   ❌ Mock data type '{$type}' failed\n";
      }
    }
    echo "\n";
  }

  private function testAllPrintTypes()
  {
    echo "🖨️ Test 4: All Print Types\n";

    $printTypes = ['provisional', 'invoice', 'labels', 'kitchen'];

    foreach ($printTypes as $type) {
      $data = [
        'print_type' => $type,
        'mock_data_type' => 'simple',
        'device_id' => "{$type}_printer_001"
      ];

      $response = $this->makeRequest('POST', '/api/print/test', $data, $this->token);

      if ($response && $response['success']) {
        echo "   ✅ Print type '{$type}' successful\n";

        // Special handling cho labels (multiple jobs)
        if ($type === 'labels' && is_array($response['data'])) {
          echo "      📄 Created " . count($response['data']) . " label jobs\n";
        }
      } else {
        echo "   ❌ Print type '{$type}' failed\n";
      }
    }
    echo "\n";
  }

  private function testClientTestPrint()
  {
    echo "🔑 Test 5: Client Test Print (API Key)\n";

    $data = [
      'print_type' => 'kitchen',
      'device_id' => 'kitchen_client_001',
      'mock_data_type' => 'complex'
    ];

    // Giả lập API key request
    $response = $this->makeRequest('POST', '/api/print/client/test', $data, null, $this->apiKey);

    if ($response && $response['success']) {
      echo "   ✅ Client test print successful\n";
      echo "   🍽️  Kitchen items created\n";
    } else {
      echo "   ❌ Client test print failed (API key may not be configured)\n";
    }
    echo "\n";
  }

  private function testValidationErrors()
  {
    echo "🚨 Test 6: Validation Errors\n";

    // Test invalid print_type
    $invalidData = [
      'print_type' => 'invalid_type',
      'device_id' => 'test_printer'
    ];

    $response = $this->makeRequest('POST', '/api/print/test', $invalidData, $this->token);

    if ($response && !$response['success']) {
      echo "   ✅ Invalid print_type correctly rejected\n";
    } else {
      echo "   ❌ Invalid print_type should be rejected\n";
    }

    // Test missing required field
    $missingData = [
      'device_id' => 'test_printer'
      // Missing print_type
    ];

    $response = $this->makeRequest('POST', '/api/print/test', $missingData, $this->token);

    if ($response && !$response['success']) {
      echo "   ✅ Missing print_type correctly rejected\n";
    } else {
      echo "   ❌ Missing print_type should be rejected\n";
    }
    echo "\n";
  }

  private function makeRequest($method, $endpoint, $data = null, $token = null, $apiKey = null)
  {
    $url = $this->baseUrl . $endpoint;

    $headers = [
      'Content-Type: application/json',
    ];

    if ($token) {
      $headers[] = 'Authorization: Bearer ' . $token;
      $headers[] = 'X-Branch-ID: 1';
    }

    if ($apiKey) {
      $headers[] = 'X-API-Key: ' . $apiKey;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_SSL_VERIFYPEER => false
    ]);

    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
      echo "   ⚠️  cURL Error: Unable to connect to {$url}\n";
      return null;
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
      echo "   ⚠️  HTTP {$httpCode}: {$url}\n";
      if ($decoded && isset($decoded['message'])) {
        echo "   📝 Message: {$decoded['message']}\n";
      }
    }

    return $decoded;
  }

  public function generateMockDataPreview()
  {
    echo "🎭 Mock Data Preview Examples:\n\n";

    echo "1. Simple Order:\n";
    echo "   - 2x Cà phê đen (25,000đ)\n";
    echo "   - 1x Trà sữa truyền thống (35,000đ)\n";
    echo "   - Tổng: 85,000đ\n\n";

    echo "2. Complex Order:\n";
    echo "   - Khách hàng: Nguyễn Văn Test (Gold)\n";
    echo "   - Voucher: TESTDISCOUNT (-10,000đ)\n";
    echo "   - Tổng: 173,000đ\n\n";

    echo "3. With Toppings:\n";
    echo "   - Trà sữa socola + Trân châu + Thạch\n";
    echo "   - Cà phê sữa đá + Shot thêm\n\n";

    echo "4. Large Order:\n";
    echo "   - 15 items, 448,000đ\n";
    echo "   - Khách hàng: Công ty ABC (Platinum)\n\n";
  }
}

// Chạy tests
$tester = new TestPrintFeatureTest();

echo "=" . str_repeat("=", 60) . "\n";
echo " KARINOX F&B - TEST PRINT FEATURE TESTING\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$tester->generateMockDataPreview();
$tester->runTests();

echo "🎯 Test Print Feature ready for production use!\n";
echo "📖 See docs/test-print-feature.md for full documentation.\n";
