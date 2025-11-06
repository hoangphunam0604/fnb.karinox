<?php

namespace App\Helpers;

class PrintTemplateRender
{
  public static function renderTemplate(string $templateStr, array $data): string
  {
    // Tạo DOM từ template HTML
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true); // Bỏ qua lỗi HTML không chuẩn
    $dom->loadHTML($templateStr, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Tìm hàng chứa {STT}
    $xpath = new \DOMXPath($dom);
    $rows = $xpath->query('//tr');
    $templateRow = null;

    foreach ($rows as $row) {
      if (strpos($row->textContent, '{STT}') !== false) {
        $templateRow = $row;
        break;
      }
    }

    // Nhân bản các dòng sản phẩm
    if ($templateRow && isset($data['items']) && is_array($data['items'])) {
      $rowHtml = $dom->saveHTML($templateRow);
      $parent = $templateRow->parentNode;
      $parent->removeChild($templateRow);

      foreach ($data['items'] as $index => $item) {
        $row = $rowHtml;
        $row = str_replace('{STT}', $index + 1, $row);
        $row = str_replace('{Ten_San_Pham}', $item['Ten_San_Pham'] ?? '', $row);
        $row = str_replace('{Topping}', isset($item['Topping']) && $item['Topping'] ? $item['Topping'] . '<br>' : '', $row);
        $row = str_replace('{Ghi_Chu}', $item['Ghi_Chu'] ?? '', $row);
        $row = str_replace('{So_Luong}', $item['So_Luong'] ?? '', $row);
        $row = str_replace('{Don_Gia}', $item['Don_Gia'] ?? '', $row);
        $row = str_replace('{Don_Vi}', $item['Don_Vi'] ?? '', $row);
        $row = str_replace('{Thanh_Tien}', $item['Thanh_Tien'] ?? '', $row);

        // Chèn dòng mới
        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($row);
        $parent->appendChild($fragment);
      }
    }

    // Lấy HTML hoàn chỉnh
    $html = $dom->saveHTML();

    // Thay thế các placeholder còn lại (VD: {Ngay}, {Ma_Don_Hang})
    foreach ($data as $key => $val) {
      if (is_string($val) || is_numeric($val)) {
        $html = str_replace('{' . $key . '}', (string)$val, $html);
      }
    }

    return $html;
  }
}
