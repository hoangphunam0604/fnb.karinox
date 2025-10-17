@echo off
echo 🌐 TEST API QUA CURL
echo ==================

echo.
echo 📋 Test 1: GET Categories
curl -X GET "http://127.0.0.1:8000/api/admin/categories" -H "Accept: application/json" -H "X-Branch-Id: 1"

echo.
echo.
echo 📦 Test 2: GET Products  
curl -X GET "http://127.0.0.1:8000/api/admin/products" -H "Accept: application/json" -H "X-Branch-Id: 1"

echo.
echo.
echo 👤 Test 3: GET Customers
curl -X GET "http://127.0.0.1:8000/api/admin/customers" -H "Accept: application/json" -H "X-Branch-Id: 1"

echo.
echo.
echo 📊 Test 4: GET Stock Report
curl -X GET "http://127.0.0.1:8000/api/admin/inventory/stock-report" -H "Accept: application/json" -H "X-Branch-Id: 1"

echo.
echo.
echo ➕ Test 5: POST New Category
curl -X POST "http://127.0.0.1:8000/api/admin/categories" ^
  -H "Accept: application/json" ^
  -H "Content-Type: application/json" ^
  -H "X-Branch-Id: 1" ^
  -d "{\"name\": \"Category từ API\", \"description\": \"Tạo bằng curl\"}"

echo.
echo.
echo ❌ Test 6: Validation Error
curl -X POST "http://127.0.0.1:8000/api/admin/categories" ^
  -H "Accept: application/json" ^
  -H "Content-Type: application/json" ^
  -H "X-Branch-Id: 1" ^
  -d "{\"description\": \"Không có tên\"}"

echo.
echo.
echo 🎉 API TESTS COMPLETED!