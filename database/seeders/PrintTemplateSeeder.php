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
<p style='text-align:center'>
  <img alt="karinox" src="https://cdn1-fnb-userdata.kiotviet.vn/2024/03/karinopr/printertemplate/e0ecccb725914139874ede0b53bc738d" style="height:150px; width:150px" />
</p>
                  <div style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>HOÁ ĐƠN BÁN HÀNG</strong></span></span></div>

                  <div>
                  <table border="0" cellpadding="1" cellspacing="1" style="width:100%">
                    <tbody>
                      <tr>
                        <td><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>NV:</strong>&nbsp;{Nhan_Vien_Ban_Hang}</span></span></td>
                        <td rowspan="3">
                        <p style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>{Ten_Phong_Ban}</strong></span></span></p>

                        <p style="text-align:center"><span style="font-size:12px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Điểm TH:</strong></span>&nbsp;</span><span style="font-family:Tahoma,Geneva,sans-serif"><span style="font-size:12px">{Tong_Diem_Hien_Tai}</span></span></p>
                        </td>
                      </tr>
                      <tr>
                        <td><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Ngày:</strong>&nbsp;{Ngay_Thang_Nam}</span></span></td>
                      </tr>
                      <tr>
                        <td><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Số HD:&nbsp;</strong>{Ma_Don_Hang}</span></span></td>
                      </tr>
                      <tr>
                        <td><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>KHTT:</strong>&nbsp;{Khach_Hang}</span></span></td>
                        <td style="text-align:center">
                        <p><span style="font-size:12px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Điểm ĐT</strong>:&nbsp;{Tong_Diem_Sau_Hoa_Don}</span></span></p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  </div>

                  <div style="height:6px">&nbsp;</div>

                  <table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse; width:100%">
                    <tbody>
                      <tr>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>STT</strong></span></span></td>
                        <td><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Tên sản phẩm</strong></span></span></td>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>SL</strong></span></span></td>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Đ.Giá</strong></span></span></td>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>T.Tiền</strong></span></span></td>
                      </tr>
                      <tr>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif">{STT}</span></span></td>
                        <td>
                        <p><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif">{Ten_Hang_Hoa}</span></span></p>

                        <p><span style="font-size:12px"><span style="font-family:Tahoma,Geneva,sans-serif">{Ghi_Chu_Hang_Hoa}</span></span></p>
                        </td>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif">{So_Luong}</span></span></td>
                        <td style="text-align:center"><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif">{Don_Gia}</span></span></td>
                        <td style="text-align:right">
                        <p><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif">{Thanh_Tien}</span></span></p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  &nbsp;

                  <table style="width:100%">
                    <tbody>
                      <tr>
                        <td style="text-align:right">
                        <p><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Tổng tiền HĐ:</strong></span></span></p>

                        <p><span style="font-size:14px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>Tổng tiền ĐTĐ:</strong></span></span></p>

                        <p><span style="font-family:Tahoma,Geneva,sans-serif"><span style="font-size:14px"><strong>Tổng tiền ĐTCK:</strong></span></span></p>
                        </td>
                        <td style="border-bottom:1px black solid; text-align:right">
                        <p><span style="font-family:Tahoma,Geneva,sans-serif"><strong>{Tong_Tien_Hang}</strong></span></p>

                        <p><span style="font-size:12px"><span style="font-family:Tahoma,Geneva,sans-serif"><strong>{Tong_Thanh_Toan_Sau_Diem}</strong></span></span></p>

                        <p><span style="font-family:Tahoma,Geneva,sans-serif"><span style="font-size:12px"><strong>{Tong_Tien_Hang_Tru_CKHD}</strong></span></span></p>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <p style="text-align: center;"><span style="font-size:16px"><strong>QUÉT MÃ ĐỂ GỬI GÓP Ý&nbsp;CỦA BẠN</strong></span></p>

                  <p style="text-align:center"><span style="font-size:8px"><img alt="qr" src="https://cdn1-fnb-userdata.kiotviet.vn/2024/03/karinopr/printertemplate/9888410d614d4c9fafcd9734bf456f66" style="height:130px; width:130px" /></span></p>

                  <p style="text-align: center;"><span style="font-size:14px">Nếu bạn có điều gì đó muốn chia sẻ, Karinox rất mong được lắng nghe ý kiến của bạn để cải thiện chất lượng dịch vụ.</span></p>
                  <p style="text-align: center;"><span style="font-size:16px"><strong>&nbsp;XIN CẢM ƠN QUÝ KHÁCH</strong></span></p>
HTML,
            'label' => <<<HTML
                        <div style="text-align: center;">
                          <strong>{{ ten }}</strong><br/>
                          SL: {{ soLuong }} - Bàn {{ ban }}
                        </div>
                    HTML,
            'kitchen' => <<<HTML
<div class="default_template_kitchen">{Lien}
<h2 class="title">CHẾ BIẾN</h2>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Bàn:</strong> {Phong_Ban_Gom_Nhom}</td>
		</tr>
		<tr>
			<td colspan="2"><strong>Phục vụ:</strong> {Nguoi_Thong_Bao}</td>
		</tr>
		<tr>
			<td class="note">{Ma_Don_Hang} - {Ngay_Thong_Bao} {Gio_Thong_Bao}</td>
		</tr>
	</tbody>
</table>

<table border="0" cellpadding="5" cellspacing="0" class="styles-table" style="width:100%">
	<tbody>
		<tr>
			<th class="text-left">Món</th>
			<th class="text-center">ĐVT</th>
			<th class="text-center">SL</th>
		</tr>
		<tr>
			<td class="text-left">{Ten_Hang_Hoa} ({Ma_Hang})
			<div class="note">{Ghi_Chu_Hang_Hoa}</div>

			<div class="sub">
			<p>{Mon_Them}</p>
			</div>
			</td>
			<td class="text-center">{Don_Vi_Tinh}</td>
			<td class="text-center">{So_Luong}</td>
		</tr>
	</tbody>
</table>
HTML,
            'provisional' =>  <<<HTML
<table style="width:100%">
<tbody>
<tr>
<td><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Tên cửa hàng</span></span></td>
</tr>
<tr>
<td><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Chi nhánh: {Chi_Nhanh_Ban_Hang}</span></span></td>
</tr>
<tr>
<td><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Điện thoại: 1900 6522</span></span></td>
</tr>
</tbody>
</table>

<div style="border-bottom:1px dashed black">&nbsp;</div>

<div><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Ngày bán: {Ngay_Thang_Nam}</span></span></div>

<div style="text-align:center"><br />
<span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif"><strong>PHIẾU TẠM TÍNH</strong></span></span><br />
<span style="font-size:10px"><span style="font-family:arial,helvetica,sans-serif"><strong>{Ma_Don_Hang}</strong></span></span></div>

<div><span style="font-family:arial,helvetica,sans-serif; font-size:11px">Phòng bàn: {Ten_Phong_Ban}</span></div>

<div><span style="font-family:arial,helvetica,sans-serif; font-size:11px">Giờ vào: {Ngay_Thang_Nam}</span></div>

<div><span style="font-family:arial,helvetica,sans-serif; font-size:11px">Khách hàng: {Khach_Hang}</span></div>

<div><span style="font-family:arial,helvetica,sans-serif; font-size:11px">Người bán: {Nhan_Vien_Ban_Hang}</span></div>

<div style="border-bottom:1px dashed black">
<p><span style="font-family:arial,helvetica,sans-serif; font-size:11px"><strong>Người bán:</strong> {Nhan_Vien_Ban_Hang}</span></p>

<p>&nbsp;</p>
</div>

<table style="width:100%">
<tbody>
<tr>
<td style="width:35%"><strong><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Đơn giá</span></span></strong></td>
<td style="text-align:center; width:30%"><strong><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">SL</span></span></strong></td>
<td style="text-align:right"><strong><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Thành tiền</span></span></strong></td>
</tr>
<tr>
<td colspan="3">
<p><span style="font-family:arial,helvetica,sans-serif; font-size:12px">{Ten_Hang_Hoa}</span></p>
<p><em style="font-style:italic">{Ghi_Chu_Hang_Hoa}</em></p>
</td>
</tr>
<tr>
<td>
<p><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Don_Gia}</span></span></p>
</td>
<td style="text-align:center">
<p><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{So_Luong}</span></span></p>
</td>
<td style="text-align:right">
<p><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Thanh_Tien}</span></span></p>
</td>
</tr>
<tr>
<td colspan="3" style="border-bottom:1px dashed black; width:100%">&nbsp;</td>
</tr>
</tbody>
</table>

<p><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Ghi_Chu}</span></span></p>

<table style="width:100%">
<tbody>
<tr>
<td style="text-align:right"><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Tổng tiền hàng:</span></span></td>
<td style="text-align:right"><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Tong_Tien_Hang}</span></span></td>
</tr>
<tr>
<td style="text-align:right"><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Chiết khấu:</span></span></td>
<td style="text-align:right"><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Chiet_Khau_Hoa_Don}</span></span></td>
</tr>
<tr>
<td style="text-align:right"><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">Tổng cộng:</span></span></td>
<td style="text-align:right"><strong><span style="font-size:11px"><span style="font-family:arial,helvetica,sans-serif">{Tong_Cong}</span></span></strong></td>
</tr>
</tbody>
</table>
HTML
          },
        ]);
      }
    }
  }
}
