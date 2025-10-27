<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\VoucherCampaignRequest;
use App\Http\Admin\Resources\VoucherCampaignResource;
use App\Models\VoucherCampaign;
use App\Services\VoucherCampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VoucherCampaignController extends Controller
{
  protected VoucherCampaignService $service;

  public function __construct(VoucherCampaignService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of voucher campaigns
   */
  public function index(Request $request): JsonResponse
  {
    $params = $request->all();
    $campaigns = $this->service->getList($params);

    return response()->json([
      'data' => VoucherCampaignResource::collection($campaigns->items()),
      'meta' => [
        'current_page' => $campaigns->currentPage(),
        'last_page' => $campaigns->lastPage(),
        'per_page' => $campaigns->perPage(),
        'total' => $campaigns->total(),
      ]
    ]);
  }

  /**
   * Store a newly created voucher campaign
   */
  public function store(VoucherCampaignRequest $request): JsonResponse
  {
    $data = $request->validated();
    $data['created_by'] = $request->user()->id;

    $campaign = $this->service->createCampaign($data);

    return response()->json([
      'message' => 'Voucher campaign created successfully',
      'data' => new VoucherCampaignResource($campaign)
    ], 201);
  }

  /**
   * Display the specified voucher campaign
   */
  public function show(VoucherCampaign $voucherCampaign): JsonResponse
  {
    $voucherCampaign->load(['creator', 'vouchers']);

    return response()->json([
      'data' => new VoucherCampaignResource($voucherCampaign)
    ]);
  }

  /**
   * Update the specified voucher campaign
   */
  public function update(VoucherCampaignRequest $request, VoucherCampaign $voucherCampaign): JsonResponse
  {
    $data = $request->validated();

    $updatedCampaign = $this->service->update($voucherCampaign->id, $data);

    return response()->json([
      'message' => 'Voucher campaign updated successfully',
      'data' => new VoucherCampaignResource($updatedCampaign)
    ]);
  }

  /**
   * Remove the specified voucher campaign
   */
  public function destroy(VoucherCampaign $voucherCampaign): JsonResponse
  {
    $this->service->delete($voucherCampaign->id);

    return response()->json([
      'message' => 'Voucher campaign deleted successfully'
    ]);
  }

  /**
   * Generate vouchers for a campaign
   */
  public function generateVouchers(Request $request, VoucherCampaign $voucherCampaign): JsonResponse
  {
    $request->validate([
      'quantity' => 'required|integer|min:1|max:1000'
    ]);

    try {
      $vouchers = $this->service->generateVouchers($voucherCampaign, $request->quantity);

      return response()->json([
        'message' => 'Vouchers generated successfully',
        'data' => [
          'generated_count' => count($vouchers),
          'campaign' => new VoucherCampaignResource($voucherCampaign->fresh())
        ]
      ]);
    } catch (\InvalidArgumentException $e) {
      return response()->json([
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Get campaign analytics
   */
  public function analytics(VoucherCampaign $voucherCampaign): JsonResponse
  {
    $analytics = $this->service->getCampaignAnalytics($voucherCampaign);

    return response()->json([
      'data' => $analytics
    ]);
  }

  /**
   * Export voucher codes
   */
  public function exportCodes(Request $request, VoucherCampaign $voucherCampaign): JsonResponse
  {
    $unusedOnly = $request->boolean('unused_only', false);
    $codes = $this->service->exportVoucherCodes($voucherCampaign, $unusedOnly);

    return response()->json([
      'data' => [
        'campaign_name' => $voucherCampaign->name,
        'export_type' => $unusedOnly ? 'unused_only' : 'all',
        'total_codes' => count($codes),
        'codes' => $codes
      ]
    ]);
  }

  /**
   * Bulk activate campaign vouchers
   */
  public function activateVouchers(VoucherCampaign $voucherCampaign): JsonResponse
  {
    $count = $this->service->activateCampaignVouchers($voucherCampaign);

    return response()->json([
      'message' => "Activated {$count} vouchers successfully",
      'data' => [
        'activated_count' => $count,
        'campaign' => new VoucherCampaignResource($voucherCampaign->fresh())
      ]
    ]);
  }

  /**
   * Bulk deactivate campaign vouchers
   */
  public function deactivateVouchers(VoucherCampaign $voucherCampaign): JsonResponse
  {
    $count = $this->service->deactivateCampaignVouchers($voucherCampaign);

    return response()->json([
      'message' => "Deactivated {$count} vouchers successfully",
      'data' => [
        'deactivated_count' => $count,
        'campaign' => new VoucherCampaignResource($voucherCampaign->fresh())
      ]
    ]);
  }

  /**
   * Get campaign vouchers with pagination
   */
  public function vouchers(Request $request, VoucherCampaign $voucherCampaign): JsonResponse
  {
    $perPage = $request->input('per_page', 15);
    $vouchers = $voucherCampaign->vouchers()
      ->with(['branches'])
      ->paginate($perPage);

    return response()->json([
      'data' => $vouchers->items(),
      'meta' => [
        'current_page' => $vouchers->currentPage(),
        'last_page' => $vouchers->lastPage(),
        'per_page' => $vouchers->perPage(),
        'total' => $vouchers->total(),
      ]
    ]);
  }
}
