<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipLevelRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'rank' => ['required', 'integer', 'min:1'],
      'name' => ['required', 'string', 'max:191'],
      'min_spent' => ['required', 'numeric', 'min:0'],
      'max_spent' => ['nullable', 'numeric', 'gte:min_spent'],

      'reward_multiplier' => ['nullable', 'numeric', 'min:0'],

      'upgrade_reward_content' => ['nullable', 'string', 'max:500'],
      'birthday_gift' => ['nullable', 'string', 'max:500'],
      'party_booking_offer' => ['nullable', 'string', 'max:500'],
      'shopping_entertainment_offers' => ['nullable', 'string', 'max:500'],
      'new_product_offers' => ['nullable', 'string', 'max:500'],
      'end_of_year_gifts' => ['nullable', 'string', 'max:500'],
    ];
  }

  public function messages(): array
  {
    return [
      'rank.required' => 'Vui lòng nhập :attribute.',
      'rank.integer' => ':attribute phải là số nguyên.',
      'rank.min' => ':attribute phải lớn hơn hoặc bằng :min.',

      'name.required' => 'Vui lòng nhập :attribute.',
      'name.max' => ':attribute không được vượt quá :max ký tự.',

      'min_spent.required' => 'Vui lòng nhập :attribute.',
      'min_spent.numeric' => ':attribute phải là số.',
      'min_spent.min' => ':attribute không được âm.',

      'max_spent.numeric' => ':attribute phải là số.',
      'max_spent.gte' => ':attribute phải lớn hơn hoặc bằng giá trị tối thiểu.',

      'reward_multiplier.numeric' => ':attribute phải là số.',
      'reward_multiplier.min' => ':attribute không được âm.',

      'upgrade_reward_content.max' => ':attribute không được vượt quá :max ký tự.',
      'birthday_gift.max' => ':attribute không được vượt quá :max ký tự.',
      'party_booking_offer.max' => ':attribute không được vượt quá :max ký tự.',
      'shopping_entertainment_offers.max' => ':attribute không được vượt quá :max ký tự.',
      'new_product_offers.max' => ':attribute không được vượt quá :max ký tự.',
      'end_of_year_gifts.max' => ':attribute không được vượt quá :max ký tự.',
    ];
  }

  public function attributes(): array
  {
    return [
      'rank' => 'thứ tự xếp hạng',
      'name' => 'tên hạng thành viên',
      'min_spent' => 'chi tiêu tối thiểu',
      'max_spent' => 'chi tiêu tối đa',
      'reward_multiplier' => 'hệ số nhân điểm thưởng',
      'upgrade_reward_content' => 'nội dung quà khi nâng hạng',
      'birthday_gift' => 'quà sinh nhật',
      'party_booking_offer' => 'ưu đãi đặt tiệc',
      'shopping_entertainment_offers' => 'ưu đãi mua sắm/giải trí',
      'new_product_offers' => 'ưu đãi sản phẩm mới',
      'end_of_year_gifts' => 'quà tặng cuối năm',
    ];
  }
}
