# 🧪 Print API Test Collection

## 1. Kết nối chi nhánh

```http
POST http://localhost:8000/api/print/branchs/BR001/connect
Content-Type: application/json

{}
```

## 2. Lấy danh sách mẫu in

```http
GET http://localhost:8000/api/print/templates
```

## 3. Test Print Data APIs

### In tạm tính (Provisional)

```http
GET http://localhost:8000/api/print/data/provisional/1
```

### In hóa đơn đầy đủ

```http
GET http://localhost:8000/api/print/data/invoice-all/1
```

### In chỉ hóa đơn

```http
GET http://localhost:8000/api/print/data/invoice/1
```

### In phiếu bếp

```http
GET http://localhost:8000/api/print/data/kitchen/1
```

### In tem phiếu

```http
GET http://localhost:8000/api/print/data/label/1
```

## 4. Test WebSocket Event (PHP)

```php
<?php
// test-print-websocket.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\PrintRequested;

// Test gửi event in hóa đơn
event(new PrintRequested('invoice', 1, 1));
echo "✅ Đã gửi print request cho invoice ID 1\n";

// Test gửi event in tạm tính
event(new PrintRequested('provisional', 1, 1));
echo "✅ Đã gửi print request cho order ID 1\n";
```

## 5. JavaScript WebSocket Test

```html
<!DOCTYPE html>
<html>
    <head>
        <title>Print WebSocket Test</title>
        <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    </head>
    <body>
        <h1>Print WebSocket Test</h1>
        <div id="log"></div>

        <script>
            const pusher = new Pusher('your-app-key', {
                wsHost: 'localhost',
                wsPort: 8080,
                wssPort: 8080,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });

            const channel = pusher.subscribe('print-branch-1');

            channel.bind('print.requested', function (data) {
                console.log('Print request received:', data);

                document.getElementById('log').innerHTML += `<p>📨 Print Request: ${data.type} ID ${data.id}</p>`;

                // Test gọi API
                fetch(`/api/print/data/${data.type}/${data.id}`)
                    .then((response) => response.json())
                    .then((result) => {
                        console.log('Print data:', result);
                        document.getElementById('log').innerHTML += `<p>✅ Data received: ${JSON.stringify(result.data)}</p>`;
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        document.getElementById('log').innerHTML += `<p>❌ Error: ${error.message}</p>`;
                    });
            });

            // Test buttons
            document.body.innerHTML += `
            <button onclick="testPrint('invoice', 1)">Test Invoice Print</button>
            <button onclick="testPrint('provisional', 1)">Test Provisional Print</button>
        `;

            function testPrint(type, id) {
                fetch('/api/print/test-event', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type, id, branch_id: 1 }),
                });
            }
        </script>
    </body>
</html>
```

## Expected Responses

### Successful Print Data Response

```json
{
    "success": true,
    "message": "Lấy dữ liệu in thành công",
    "data": {
        "type": "invoice",
        "metadata": {
            // Print data here
        }
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Lỗi lấy dữ liệu in: Entity not found",
    "error_code": 500
}
```

## Quick Debug Commands

```bash
# Kiểm tra migrations
php artisan migrate:status

# Tạo test data
php artisan db:seed

# Kiểm tra WebSocket server
php artisan reverb:start

# Test connectivity
curl -X GET "http://localhost:8000/api/print/templates"
```
