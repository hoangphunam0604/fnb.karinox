# Cài đặt

tạo secret key JWT

```sh
php artisan key:gen
php artisan jwt:secret
php artisan storage:link
php artisan migrate --seed

# Khởi động Laravel Reverb (WebSocket Server)
php artisan reverb:start

```
