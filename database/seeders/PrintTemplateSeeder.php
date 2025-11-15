<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrintTemplate;
use Illuminate\Support\Facades\DB;

class PrintTemplateSeeder extends Seeder
{
  public function run(): void
  {
    // Xoá các template cũ (tuỳ bạn muốn reset hay không)
    PrintTemplate::truncate();
    $branchs = DB::table('branches')->get();
    foreach ($branchs as $branch) {

      // Danh sách các loại template
      $types = ['invoice', 'provisional', 'label', 'kitchen'];

      foreach ($types as $type) {
        // Tạo mẫu mặc định cho từng loại
        PrintTemplate::factory()->create([
          'branch_id'  =>  $branch->id,
          'type' => $type,
          'name' => "Mẫu in mặc định cho {$type}",
          'description' => "Đây là mẫu in chính dùng cho loại {$type}",
          'is_default' => true,
          'is_active' => true,
          'content' => match ($type) {
            'invoice' => <<<HTML
<div style="font:14px/1.4 monospace; ">
<p style="text-align: center;"><img style="height: 150px; width: 150px;" src="https://karinox.vn/wp-content/themes/karinox/assets/img/common/logo-karinox-coffee.svg" /></p>
<p style="text-align: center;"><strong>HOÁ ĐƠN BÁN HÀNG</strong></p>
<table style="width: 100%;" border="0" cellspacing="1" cellpadding="1">
  <tbody>
    <tr>
      <td>
        <strong>Số HD:</strong>&nbsp;{Ma_Don_Hang}<br>
        <strong>NV:</strong>&nbsp;{Nhan_Vien_Ban_Hang}<br>
        <strong>Ngày:&nbsp;</strong>&nbsp;{Ngay_Thang_Nam}<br>
        <strong>Bàn:&nbsp;</strong>&nbsp;{Ten_Phong_Ban}
      </td>
      <td>
        <strong>KHTT:</strong>&nbsp;{Ten_Khach_Hang} ({Ma_Khach_Hang})<br>
        <strong>Điểm Tích Luỹ:</strong>&nbsp;{Diem_Tich_Luy}<br>
        <strong>Điểm Thưởng:</strong>&nbsp;{Diem_Thuong}<br>
      </td>
    </tr>
  </tbody>
</table>
<table style="border-collapse: collapse; width: 100%;" border="1" cellspacing="0" cellpadding="3">
  <tbody>
    <tr>
      <td style="text-align: center;">
        <strong>STT</strong>
      </td>
      <td>
        <strong>Tên sản phẩm</strong>
      </td>
      <td style="text-align: center;">
        <strong>SL</strong>
      </td>
      <td style="text-align: right;">
        <strong>Đ.Giá</strong>
      </td>
      <td style="text-align: right;">
        <strong>T.Tiền</strong>
      </td>
    </tr>
    <tr>
      <td style="text-align: center;">
        {STT}
      </td>
      <td>
        <strong>{Ten_San_Pham}</strong><br>
        {Topping}{Ghi_Chu}
      </td>
      <td style="text-align: center;">
        {So_Luong}
      </td>
      <td style="text-align: right;">
        {Don_Gia}
      </td>
      <td style="text-align: right;">
        {Thanh_Tien}
      </td>
    </tr>
  </tbody>
</table>
<table style="width: 100%;">
  <tbody>
    <tr>
      <th style="text-align: right;">Tổng tiền HĐ:</th>
      <td style="text-align: right;"><strong>{Tong_Tien_Hang}</strong></td>
    </tr>
    <tr>
      <th style="text-align: right;">Giảm Giá:</th>
      <td style="text-align: right;"><strong>({Ma_Giam_Gia}) {Tien_Giam_Gia}</strong></td>
    </tr>
    <tr>
      <th style="text-align: right;">Điểm đổi:</th>
      <td style="text-align: right;"><strong>({Diem_Doi_Thuong}) {Tien_Diem_Doi_Thuong}</strong></td>
    </tr>
    <tr>
      <th style="text-align: right;">Tổng tiền ĐTCK:</th>
      <td style="text-align: right;"><strong>{Tong_Thanh_Toan}</strong></td>
    </tr>
  </tbody>
</table>
<p style="text-align: center;">
  <strong>QUÉT MÃ ĐỂ GỬI GÓP Ý CỦA BẠN</strong>
</p>
<p style="text-align: center;">
  <img style="height: 130px; width: 130px;" src="https://cdn1-fnb-userdata.kiotviet.vn/2024/03/karinopr/printertemplate/9888410d614d4c9fafcd9734bf456f66" alt="qr" />
</p>
</span>
<p style="text-align: center;">
  Nếu bạn có điều gì đó muốn chia sẻ, Karinox rất mong được lắng nghe ý kiến của bạn để cải thiện chất lượng dịch vụ.
</p>
<p style="text-align: center;">
  <strong>XIN CẢM ƠN QUÝ KHÁCH</strong>
</p>
</div>
HTML,
            'label' => <<<HTML
<div style="font:14px/1.4 monospace; ">
<table style="border-collapse: collapse; width: 100%;" border="0" cellspacing="0" cellpadding="3">
  <tbody>
    <tr>
      <td>
        <strong>{Ten_San_Pham}</strong><br>
        {Topping}</br>
        {Ghi_Chu}
      </td>
      <td style="text-align: right; width: 10%; vertical-align: top;">
       {So_Luong} {Don_Vi}
      </td>
    </tr>
  </tbody>
</table>
</div>
HTML,
            'kitchen' => <<<HTML
<div style="font:14px/1.4 monospace; ">
<p style="text-align: center;"><strong>PHIẾU IN BẾP</strong></p><br>
<p>
  <strong>Bàn:&nbsp;</strong>{Ten_Phong_Ban}<br>
  <strong>Phục vụ:&nbsp;</strong>{Ten_Nhan_Vien}<br>
  <strong>Mã đặt hàng:&nbsp;</strong>{Ma_Dat_Hang}<br>
  <strong>Ngày giờ:&nbsp;</strong>{Ngay_Thang_Nam}
</p>
<table style="border-collapse: collapse; width: 100%;" border="1" cellspacing="0" cellpadding="3">
  <tbody>
    <tr>
      <td style="text-align: center;">
        <strong>STT</strong>
      </td>
      <td>
        <strong>Tên món</strong>
      </td>
      <td style="text-align: right;">
        <strong>Đơn vị</strong>
      </td>
      <td style="text-align: center;">
        <strong>SL</strong>
      </td>
    </tr>
    <tr>
      <td style="text-align: center;">
        {STT}
      </td>
      <td>
        <strong>{Ten_San_Pham}</strong><br>
        {Topping}</br>
        {Ghi_Chu}
      </td>
      <td style="text-align: right;">
        {Don_Vi}
      </td>
      <td style="text-align: center;">
        {So_Luong}
      </td>
    </tr>
  </tbody>
</table>
</div>
HTML,
            'provisional' =>  <<<HTML
<div style="font:14px/1.4 monospace; ">
<p ><strong>Tên chi nhánh</strong></p>
<p style="text-align: center;"><strong>PHIẾU TẠM TÍNH</strong></p>
<table style="width: 100%; text-align:left;" border="0" cellspacing="1" cellpadding="1">
  <tbody>
    <tr>
      <th style="width:25%;">Mã đặt hàng:</th>
      <td>{Ma_Dat_Hang}</td>
    </tr>
    <tr>
      <th>NV:</th>
      <td>{Nhan_Vien_Ban_Hang}</td>
    </tr>
    <tr>
      <th>Ngày:</th>
      <td>{Ngay_Thang_Nam}</td>
    </tr>
    <tr>
      <th>Bàn:</th>
      <td>{Ten_Phong_Ban}</td>
    </tr>
  </tbody>
</table>
<br>
<table style="border-collapse: collapse; width: 100%;" border="1" cellspacing="0" cellpadding="3">
  <tbody>
    <tr>
      <td style="text-align: center;">
        <strong>STT</strong>
      </td>
      <td>
        <strong>Tên sản phẩm</strong>
      </td>
      <td style="text-align: center;">
        <strong>SL</strong>
      </td>
      <td style="text-align: right;">
        <strong>Đ.Giá</strong>
      </td>
      <td style="text-align: right;">
        <strong>T.Tiền</strong>
      </td>
    </tr>
    <tr>
      <td style="text-align: center;">
        {STT}
      </td>
      <td>
        <strong>{Ten_San_Pham}</strong><br>
        {Topping}{Ghi_Chu}
      </td>
      <td style="text-align: center;">
        {So_Luong}
      </td>
      <td style="text-align: right;">
        {Don_Gia}
      </td>
      <td style="text-align: right;">
        {Thanh_Tien}
      </td>
    </tr>
  </tbody>
</table>
<br>
<table style="width: 100%;">
  <tbody>
    <tr>
      <th style="text-align: right;">Tổng tiền hàng:</th>
      <td style="text-align: right;"><strong>{Tong_Tien_Hang}</strong></td>
    </tr>
  </tbody>
</table>
</div>
HTML
          },
        ]);
      }
    }
  }
}
