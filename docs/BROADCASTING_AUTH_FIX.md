# Broadcasting Authentication Fix Guide

## Issue

Frontend getting 404 for `http://karinox-fnb.nam/api/broadcasting/auth`

## Current Status ✅

- Broadcasting routes are now registered: `api/broadcasting/auth`
- Reverb server configuration looks correct
- Channel definitions exist in `routes/channels.php`

## Check Routes

```bash
php artisan route:list | grep broadcasting
```

Should show:

```
GET|POST|HEAD   api/broadcasting/auth ..... Illuminate\Broadcasting
```

## Frontend Configuration

### 1. Laravel Echo Setup

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'ed5zsi5ebpdmawcqbwva', // REVERB_APP_KEY
    wsHost: 'karinox-fnb.nam', // REVERB_HOST
    wsPort: 6001, // REVERB_PORT
    wssPort: 6001,
    forceTLS: false, // REVERB_SCHEME=http
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: `Bearer ${yourToken}`,
            Accept: 'application/json',
        },
    },
    authEndpoint: '/api/broadcasting/auth', // Ensure this matches
});
```

### 2. Alternative Configuration (if using different host)

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
    // Explicitly set auth endpoint
    authEndpoint: 'http://karinox-fnb.nam/api/broadcasting/auth',
});
```

## Testing Commands

### 1. Test Route Accessibility

```bash
# Test with curl
curl -X POST http://karinox-fnb.nam/api/broadcasting/auth \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

### 2. Test Channel Authentication

```bash
# Test specific channel auth
curl -X POST http://karinox-fnb.nam/api/broadcasting/auth \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"channel_name":"order.123","socket_id":"test123.456"}'
```

## Common Issues & Fixes

### Issue 1: CORS

If getting CORS errors, check `config/cors.php`:

```php
'paths' => [
    'api/*',
    'broadcasting/auth', // Add this
],
```

### Issue 2: Wrong Auth Endpoint

Frontend might be calling wrong endpoint:

- ✅ Correct: `/api/broadcasting/auth`
- ❌ Wrong: `/broadcasting/auth`

### Issue 3: Missing Token

Ensure Authorization header is sent:

```javascript
auth: {
    headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
    }
}
```

### Issue 4: Channel Authorization

Check `routes/channels.php` for proper channel auth:

```php
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // Return true/false or user data
    return true; // For public channels
});
```

## Debug Steps

### 1. Test Laravel Side

```php
// In tinker or test
php artisan tinker

// Test broadcasting
use App\Events\InvoiceCompleted;
use App\Models\Invoice;

$invoice = Invoice::first();
event(new InvoiceCompleted($invoice));
```

### 2. Check Reverb Server

```bash
# Start Reverb with debug
php artisan reverb:start --debug

# Should show connections and auth attempts
```

### 3. Frontend Debug

```javascript
// Enable Echo debugging
window.Echo.connector.pusher.connection.bind('state_change', function (states) {
    console.log('Pusher state changed:', states);
});

// Listen for auth errors
window.Echo.connector.pusher.connection.bind('error', function (error) {
    console.error('Echo connection error:', error);
});
```

## Current Configuration Summary

- **REVERB_HOST**: karinox-fnb.nam
- **REVERB_PORT**: 6001
- **REVERB_SCHEME**: http
- **Auth Endpoint**: `/api/broadcasting/auth`
- **Auth Middleware**: `auth:api`

## Next Steps

1. Test the auth endpoint with curl/Postman
2. Check frontend Echo configuration
3. Verify token is valid and sent correctly
4. Check browser network tab for actual request details
5. Start Reverb server with debug to see connection attempts
