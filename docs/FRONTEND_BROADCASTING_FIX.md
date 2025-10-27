# Frontend Broadcasting Configuration Fix

## Current Issue

Frontend đang gửi: `channel_name=private-order.28`
Backend expecting: `order.{orderId}` (public) hoặc `private-order.{orderId}` (private)

## Solution 1: Use Private Channel (Recommended)

### Backend Updated ✅

```php
// routes/channels.php
Broadcast::channel('private-order.{orderId}', function ($user, $orderId) {
    if (!$user) {
        return false;
    }

    return $user->hasAnyRole([
        UserRole::ADMIN->value,
        UserRole::MANAGER->value,
        UserRole::CASHIER->value,
    ]);
});
```

### Frontend Configuration

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'ed5zsi5ebpdmawcqbwva',
    wsHost: 'karinox-fnb.nam',
    wsPort: 6001,
    forceTLS: false,
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
        },
    },
    authEndpoint: '/broadcasting/auth',
});

// Listen to private channel
const orderId = 28;
const channel = Echo.private(`order.${orderId}`);

channel.listen('invoice.completed', (e) => {
    console.log('Invoice completed:', e);
});
```

## Solution 2: Use Public Channel

### Backend

```php
// routes/channels.php - Keep as is
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // Always return true for public channel
    return true;
});
```

### Frontend

```javascript
// Listen to public channel (no auth required)
const orderId = 28;
const channel = Echo.channel(`order.${orderId}`); // Note: .channel() not .private()

channel.listen('invoice.completed', (e) => {
    console.log('Invoice completed:', e);
});
```

## Event Broadcasting Update

### Update InvoiceCompleted Event

```php
// app/Events/InvoiceCompleted.php
public function broadcastOn(): array
{
    return [
        new PrivateChannel("order.{$this->invoice->order_id}") // Use PrivateChannel
        // OR
        new Channel("order.{$this->invoice->order_id}") // Use public Channel
    ];
}
```

## Current Payload Analysis

Your current payload:

```
socket_id=757256206.744709061&channel_name=private-order.28
```

### Issues:

1. **Content-Type**: Should be `application/json` not `application/x-www-form-urlencoded`
2. **Channel name**: `private-order.28` indicates private channel usage

### Expected JSON Payload:

```json
{
    "socket_id": "757256206.744709061",
    "channel_name": "private-order.28"
}
```

## Testing with cURL

### Test Private Channel

```bash
curl -X POST http://karinox-fnb.nam/broadcasting/auth \
  -H "Authorization: Bearer YOUR_VALID_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "socket_id": "757256206.744709061",
    "channel_name": "private-order.28"
  }'
```

### Expected Success Response

```json
{
    "auth": "signature_string_here"
}
```

## Recommendation

**Use Private Channel** because:

- ✅ Better security (authentication required)
- ✅ Order-specific access control
- ✅ Matches your current frontend setup
- ✅ Can validate user has access to specific order

## Frontend Complete Example

```javascript
// Initialize Echo
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'ed5zsi5ebpdmawcqbwva',
    wsHost: 'karinox-fnb.nam',
    wsPort: 6001,
    forceTLS: false,
    auth: {
        headers: {
            Authorization: `Bearer ${getToken()}`,
            Accept: 'application/json',
        },
    },
    authEndpoint: '/broadcasting/auth',
});

// Payment completion flow
async function processPayment(orderCode) {
    try {
        // 1. Process payment
        const paymentResponse = await cashPayment(orderCode);

        if (paymentResponse.status) {
            const orderId = paymentResponse.order.order_id;

            // 2. Listen for invoice completion on private channel
            const channel = Echo.private(`order.${orderId}`);

            channel.listen('invoice.completed', (e) => {
                console.log('Invoice completed:', e);
                showSuccessMessage(e.message);
                enablePrintActions(e.invoice_id);

                // Cleanup
                Echo.leaveChannel(`private-order.${orderId}`);
            });

            // Optional: Set timeout
            setTimeout(() => {
                Echo.leaveChannel(`private-order.${orderId}`);
            }, 30000);
        }
    } catch (error) {
        console.error('Payment error:', error);
    }
}
```
