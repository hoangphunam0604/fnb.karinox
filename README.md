# Cài đặt

tạo secret key JWT

```sh
php artisan key:gen
php artisan jwt:secret
php artisan storage:link
php artisan migrate --seed

#Tạo lại database
php artisan migrate:refresh --seed

# Khởi động Laravel Reverb (WebSocket Server)
php artisan reverb:start


mv public_html _public_html

ln -s fnb.karinox/public public_html
```

## 📚 Tài liệu

Tất cả tài liệu kỹ thuật và hướng dẫn được tổ chức trong thư mục [`docs/`](docs/).

Xem [docs/README.md](docs/README.md) để biết danh sách đầy đủ các tài liệu có sẵn.
