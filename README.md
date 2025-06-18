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

Tạo cho tôi Admin Controller cho file service và model tôi đã gửi
Lưu ý:
Namespage App\Http\Controllers\Api\Admin
inject Service
Tạo và trả về Resource tương ứng `namespace App\Http\Resources\Api\Admin`
tách FormRequest riêng cho store / update `namespace App\Http\Requests\Api\Admin;`
