# Hướng dẫn Frontend - Voucher Campaign Management

> Tài liệu này hướng dẫn tạo frontend quản lý Voucher Campaign sử dụng **Vuetify 3 + Vue 3** kết nối với API backend Laravel.

## 📋 Tổng quan chức năng

### Backend API đã có sẵn:
- ✅ **VoucherCampaign CRUD** - Quản lý chiến dịch voucher
- ✅ **Bulk Voucher Generation** - Tạo hàng loạt voucher từ template
- ✅ **Analytics & Reports** - Thống kê sử dụng và hiệu quả
- ✅ **Export/Import** - Xuất mã voucher, kích hoạt/tắt hàng loạt
- ✅ **Template System** - Cấu hình voucher template linh hoạt

---

## 🎯 Danh sách màn hình cần tạo

### 1. **Campaign List (Danh sách chiến dịch)**
- Table hiển thị campaigns với filters
- Actions: Create, Edit, Delete, View Analytics
- Status indicators và progress bars

### 2. **Campaign Form (Tạo/sửa chiến dịch)**
- Stepper form với 3 steps: Basic Info → Voucher Template → Review
- Rich validation và preview
- Auto-generate settings

### 3. **Campaign Detail (Chi tiết chiến dịch)**
- Overview statistics
- Voucher generation actions
- Voucher list với filters
- Analytics charts

### 4. **Analytics Dashboard (Báo cáo)**
- Usage charts (daily/monthly)
- Performance metrics
- Export functionality

---

## 🛠 API Endpoints Reference

### Base URL: `/admin/voucher-campaigns`

```typescript
// API Interface Types
interface VoucherCampaign {
  id: number
  name: string
  description?: string
  campaign_type: 'event' | 'promotion' | 'loyalty' | 'seasonal' | 'birthday' | 'grand_opening'
  start_date: string
  end_date: string
  target_quantity: number
  generated_quantity: number
  used_quantity: number
  code_prefix: string
  code_format: string
  code_length: number
  is_active: boolean
  auto_generate: boolean
  voucher_template: VoucherTemplate
  // ... computed fields
  usage_rate: number
  generation_rate: number
  days_remaining: number
  can_generate_more: boolean
}

interface VoucherTemplate {
  description?: string
  voucher_type: 'common' | 'private'
  discount_type: 'fixed' | 'percentage'
  discount_value: number
  max_discount?: number
  min_order_value?: number
  usage_limit?: number
  per_customer_limit?: number
  // ... thêm các constraint fields
}
```

### CRUD Operations:
```typescript
// 1. Lấy danh sách campaigns
GET /admin/voucher-campaigns
// Query params: page, per_page, search, campaign_type, is_active

// 2. Tạo campaign mới
POST /admin/voucher-campaigns
// Body: VoucherCampaignRequest

// 3. Chi tiết campaign
GET /admin/voucher-campaigns/{id}

// 4. Cập nhật campaign
PUT /admin/voucher-campaigns/{id}

// 5. Xóa campaign
DELETE /admin/voucher-campaigns/{id}
```

### Extended Operations:
```typescript
// 6. Tạo voucher cho campaign
POST /admin/voucher-campaigns/{id}/generate-vouchers
// Body: { quantity: number }

// 7. Analytics/Statistics  
GET /admin/voucher-campaigns/{id}/analytics

// 8. Export voucher codes
GET /admin/voucher-campaigns/{id}/export-codes?unused_only=true

// 9. Kích hoạt/tắt voucher hàng loạt
PUT /admin/voucher-campaigns/{id}/activate-vouchers
PUT /admin/voucher-campaigns/{id}/deactivate-vouchers

// 10. Danh sách voucher của campaign
GET /admin/voucher-campaigns/{id}/vouchers?page=1&per_page=50
```

---

## 🎨 UI Components cần tạo

### 1. **CampaignListView.vue**

```vue
<template>
  <v-container>
    <!-- Header với filters và actions -->
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <span>Quản lý chiến dịch Voucher</span>
            <v-spacer />
            <v-btn color="primary" @click="createCampaign">
              <v-icon left>mdi-plus</v-icon>
              Tạo chiến dịch
            </v-btn>
          </v-card-title>
          
          <!-- Filters -->
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="filters.search"
                  label="Tìm kiếm"
                  prepend-inner-icon="mdi-magnify"
                  clearable
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.campaign_type"
                  :items="campaignTypes"
                  label="Loại chiến dịch"
                  clearable
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  label="Trạng thái"
                  clearable
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Data Table -->
    <v-row>
      <v-col cols="12">
        <v-data-table-server
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="campaigns"
          :items-length="totalItems"
          :loading="loading"
          @update:options="loadCampaigns"
        >
          <!-- Custom columns -->
          <template #item.campaign_type="{ item }">
            <v-chip :color="getTypeColor(item.campaign_type)" small>
              {{ item.campaign_type_label }}
            </v-chip>
          </template>

          <template #item.progress="{ item }">
            <div class="d-flex align-center">
              <v-progress-linear
                :model-value="item.generation_rate"
                height="8"
                rounded
                :color="getProgressColor(item.generation_rate)"
              />
              <span class="ml-2 text-caption">
                {{ item.generated_quantity }}/{{ item.target_quantity }}
              </span>
            </div>
          </template>

          <template #item.usage_rate="{ item }">
            <div class="text-center">
              <v-progress-circular
                :model-value="item.usage_rate"
                :size="40"
                :width="4"
                :color="getUsageColor(item.usage_rate)"
              >
                {{ item.usage_rate }}%
              </v-progress-circular>
            </div>
          </template>

          <template #item.actions="{ item }">
            <v-menu>
              <template #activator="{ props }">
                <v-btn icon="mdi-dots-vertical" v-bind="props" />
              </template>
              <v-list>
                <v-list-item @click="viewDetail(item)">
                  <v-list-item-title>Chi tiết</v-list-item-title>
                </v-list-item>
                <v-list-item @click="editCampaign(item)">
                  <v-list-item-title>Sửa</v-list-item-title>
                </v-list-item>
                <v-list-item @click="viewAnalytics(item)">
                  <v-list-item-title>Báo cáo</v-list-item-title>
                </v-list-item>
                <v-divider />
                <v-list-item @click="deleteCampaign(item)" color="error">
                  <v-list-item-title>Xóa</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table-server>
      </v-col>
    </v-row>
  </v-container>
</template>
```

**Dữ liệu cần thiết:**
```typescript
const headers = [
  { title: 'Tên chiến dịch', key: 'name', sortable: true },
  { title: 'Loại', key: 'campaign_type', sortable: false },
  { title: 'Thời gian', key: 'date_range', sortable: false },
  { title: 'Tiến độ tạo', key: 'progress', sortable: false },
  { title: 'Tỷ lệ sử dụng', key: 'usage_rate', sortable: false },
  { title: 'Trạng thái', key: 'is_active', sortable: true },
  { title: 'Hành động', key: 'actions', sortable: false }
]

const campaignTypes = [
  { title: 'Sự kiện', value: 'event' },
  { title: 'Khuyến mại', value: 'promotion' },
  { title: 'Khách hàng thân thiết', value: 'loyalty' },
  { title: 'Theo mùa', value: 'seasonal' },
  { title: 'Sinh nhật', value: 'birthday' },
  { title: 'Khai trương', value: 'grand_opening' }
]
```

---

### 2. **CampaignFormDialog.vue**

```vue
<template>
  <v-dialog v-model="dialog" max-width="900px" persistent>
    <v-card>
      <v-card-title>
        <span>{{ isEdit ? 'Sửa' : 'Tạo' }} chiến dịch voucher</span>
      </v-card-title>

      <v-card-text>
        <v-stepper v-model="step" alt-labels>
          <v-stepper-header>
            <v-stepper-item 
              title="Thông tin cơ bản" 
              value="1"
              :complete="step > 1"
            />
            <v-divider />
            <v-stepper-item 
              title="Cấu hình Voucher"
              value="2" 
              :complete="step > 2"
            />
            <v-divider />
            <v-stepper-item 
              title="Xác nhận & Tạo"
              value="3"
            />
          </v-stepper-header>

          <v-stepper-window>
            <!-- Step 1: Basic Info -->
            <v-stepper-window-item value="1">
              <v-form ref="basicForm" v-model="basicFormValid">
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.name"
                      label="Tên chiến dịch"
                      :rules="[rules.required]"
                      required
                    />
                  </v-col>
                  
                  <v-col cols="12">
                    <v-textarea
                      v-model="form.description"
                      label="Mô tả"
                      rows="3"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="form.campaign_type"
                      :items="campaignTypes"
                      label="Loại chiến dịch"
                      :rules="[rules.required]"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.target_quantity"
                      label="Số lượng voucher mục tiêu"
                      type="number"
                      :rules="[rules.required, rules.minValue(1)]"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.start_date"
                      label="Ngày bắt đầu"
                      type="datetime-local"
                      :rules="[rules.required]"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.end_date"
                      label="Ngày kết thúc"
                      type="datetime-local"
                      :rules="[rules.required, rules.afterStartDate]"
                      required
                    />
                  </v-col>

                  <!-- Code Generation Settings -->
                  <v-col cols="12">
                    <v-divider class="my-4" />
                    <h4>Cài đặt mã voucher</h4>
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="form.code_prefix"
                      label="Tiền tố mã voucher"
                      hint="VD: SALE2024, BF2025"
                      :rules="[rules.required, rules.codePrefix]"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-select
                      v-model="form.code_length"
                      :items="[4,6,8,10,12]"
                      label="Độ dài phần ngẫu nhiên"
                      :rules="[rules.required]"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="codePreview"
                      label="Mã mẫu"
                      readonly
                      hint="Xem trước định dạng mã voucher"
                    />
                  </v-col>
                </v-row>
              </v-form>
            </v-stepper-window-item>

            <!-- Step 2: Voucher Template -->
            <v-stepper-window-item value="2">
              <v-form ref="templateForm" v-model="templateFormValid">
                <VoucherTemplateForm v-model="form.voucher_template" />
              </v-form>
            </v-stepper-window-item>

            <!-- Step 3: Review -->
            <v-stepper-window-item value="3">
              <CampaignPreview 
                :campaign="form" 
                @generate-now="handleGenerateNow"
              />
            </v-stepper-window-item>
          </v-stepper-window>
        </v-stepper>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="dialog = false">Hủy</v-btn>
        <v-btn 
          v-if="step > 1" 
          @click="step--"
        >
          Quay lại
        </v-btn>
        <v-btn 
          v-if="step < 3"
          color="primary"
          @click="nextStep"
          :disabled="!canProceed"
        >
          Tiếp theo
        </v-btn>
        <v-btn 
          v-if="step === 3"
          color="success"
          @click="saveCampaign"
          :loading="saving"
        >
          {{ isEdit ? 'Cập nhật' : 'Tạo chiến dịch' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
```

---

### 3. **VoucherTemplateForm.vue**

```vue
<template>
  <v-row>
    <!-- Discount Settings -->
    <v-col cols="12">
      <h4>Cài đặt giảm giá</h4>
    </v-col>

    <v-col cols="12" md="6">
      <v-select
        v-model="template.voucher_type"
        :items="voucherTypes"
        label="Loại voucher"
        :rules="[rules.required]"
        required
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-select
        v-model="template.discount_type"
        :items="discountTypes"
        label="Kiểu giảm giá"
        :rules="[rules.required]"
        required
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-text-field
        v-model="template.discount_value"
        :label="discountValueLabel"
        type="number"
        :rules="[rules.required, rules.minValue(0)]"
        required
      />
    </v-col>

    <v-col v-if="template.discount_type === 'percentage'" cols="12" md="6">
      <v-text-field
        v-model="template.max_discount"
        label="Giảm tối đa (VNĐ)"
        type="number"
        :rules="[rules.required]"
        required
      />
    </v-col>

    <!-- Usage Constraints -->
    <v-col cols="12">
      <v-divider class="my-4" />
      <h4>Điều kiện sử dụng</h4>
    </v-col>

    <v-col cols="12" md="6">
      <v-text-field
        v-model="template.min_order_value"
        label="Giá trị đơn hàng tối thiểu (VNĐ)"
        type="number"
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-text-field
        v-model="template.usage_limit"
        label="Giới hạn số lần sử dụng"
        type="number"
        hint="Để trống = không giới hạn"
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-text-field
        v-model="template.per_customer_limit"
        label="Giới hạn mỗi khách hàng"
        type="number"
        hint="Số lần tối đa 1 KH có thể dùng"
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-text-field
        v-model="template.per_customer_daily_limit"
        label="Giới hạn mỗi KH mỗi ngày"
        type="number"
      />
    </v-col>

    <!-- Time Constraints -->
    <v-col cols="12">
      <v-divider class="my-4" />
      <h4>Ràng buộc thời gian (tùy chọn)</h4>
    </v-col>

    <v-col cols="12" md="6">
      <v-select
        v-model="template.valid_days_of_week"
        :items="daysOfWeek"
        label="Ngày trong tuần được phép"
        multiple
        chips
        clearable
      />
    </v-col>

    <v-col cols="12" md="6">
      <v-select
        v-model="template.valid_months"
        :items="months"
        label="Tháng được phép"
        multiple
        chips
        clearable
      />
    </v-col>

    <!-- Branch Restrictions -->
    <v-col cols="12">
      <v-divider class="my-4" />
      <h4>Giới hạn chi nhánh</h4>
    </v-col>

    <v-col cols="12">
      <v-autocomplete
        v-model="template.branch_ids"
        :items="branches"
        item-title="name"
        item-value="id"
        label="Chi nhánh áp dụng"
        multiple
        chips
        clearable
        hint="Để trống = áp dụng tất cả chi nhánh"
      />
    </v-col>

    <!-- Preview -->
    <v-col cols="12">
      <v-divider class="my-4" />
      <VoucherPreview :template="template" />
    </v-col>
  </v-row>
</template>
```

---

### 4. **CampaignDetailView.vue**

```vue
<template>
  <v-container>
    <!-- Header -->
    <v-row>
      <v-col cols="12">
        <v-breadcrumbs :items="breadcrumbs" />
      </v-col>
    </v-row>

    <!-- Campaign Overview -->
    <v-row>
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>{{ campaign.name }}</v-card-title>
          <v-card-text>
            <p>{{ campaign.description }}</p>
            
            <v-row class="mt-4">
              <v-col cols="6" md="3">
                <v-statistic
                  title="Voucher đã tạo"
                  :value="campaign.generated_quantity"
                  :sub-value="`/${campaign.target_quantity}`"
                />
              </v-col>
              <v-col cols="6" md="3">
                <v-statistic
                  title="Đã sử dụng"
                  :value="campaign.used_quantity"
                  :sub-value="`${campaign.usage_rate}%`"
                />
              </v-col>
              <v-col cols="6" md="3">
                <v-statistic
                  title="Còn lại"
                  :value="campaign.remaining_quantity"
                  sub-value="voucher"
                />
              </v-col>
              <v-col cols="6" md="3">
                <v-statistic
                  title="Ngày còn lại"
                  :value="campaign.days_remaining"
                  sub-value="ngày"
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card>
          <v-card-title>Hành động nhanh</v-card-title>
          <v-card-text>
            <div class="d-flex flex-column ga-2">
              <v-btn
                color="primary"
                @click="showGenerateDialog = true"
                :disabled="!campaign.can_generate_more"
                block
              >
                <v-icon left>mdi-plus</v-icon>
                Tạo thêm voucher
              </v-btn>

              <v-btn
                color="info"
                @click="exportCodes"
                block
              >
                <v-icon left>mdi-download</v-icon>
                Xuất mã voucher
              </v-btn>

              <v-btn
                color="warning"
                @click="toggleVoucherStatus"
                block
              >
                <v-icon left>mdi-pause</v-icon>
                {{ allVouchersActive ? 'Tắt' : 'Kích hoạt' }} tất cả
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts & Analytics -->
    <v-row>
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Thống kê sử dụng theo ngày</v-card-title>
          <v-card-text>
            <UsageChart :data="analytics.daily_usage" />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Top chi nhánh sử dụng</v-card-title>
          <v-card-text>
            <BranchUsageChart :data="analytics.top_branches" />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Voucher List -->
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>Danh sách voucher</v-card-title>
          <v-card-text>
            <VoucherDataTable 
              :campaign-id="campaign.id"
              @refresh="loadCampaignData"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Generate Vouchers Dialog -->
    <GenerateVouchersDialog 
      v-model="showGenerateDialog"
      :campaign="campaign"
      @generated="handleVouchersGenerated"
    />
  </v-container>
</template>
```

---

## 📊 Charts & Analytics Components

### **UsageChart.vue** (Daily Usage Line Chart)
```vue
<template>
  <div>
    <canvas ref="chartRef" />
  </div>
</template>

<script setup>
import { Chart, registerables } from 'chart.js'

// Register Chart.js components
Chart.register(...registerables)

// Line chart hiển thị usage theo ngày
// Data format: [{ date: '2025-01-01', count: 150 }, ...]
</script>
```

### **BranchUsageChart.vue** (Branch Usage Bar Chart)
```vue
<template>
  <div>
    <canvas ref="chartRef" />
  </div>
</template>

<script setup>
// Bar chart hiển thị usage theo chi nhánh
// Data format: [{ name: 'Chi nhánh A', usage_count: 450 }, ...]
</script>
```

---

## 🔧 API Service Layer

### **voucherCampaignApi.js**
```typescript
import { apiClient } from '@/services/api'

export const voucherCampaignApi = {
  // CRUD Operations
  async getList(params = {}) {
    const response = await apiClient.get('/admin/voucher-campaigns', { params })
    return response.data
  },

  async getById(id: number) {
    const response = await apiClient.get(`/admin/voucher-campaigns/${id}`)
    return response.data
  },

  async create(data: any) {
    const response = await apiClient.post('/admin/voucher-campaigns', data)
    return response.data
  },

  async update(id: number, data: any) {
    const response = await apiClient.put(`/admin/voucher-campaigns/${id}`, data)
    return response.data
  },

  async delete(id: number) {
    const response = await apiClient.delete(`/admin/voucher-campaigns/${id}`)
    return response.data
  },

  // Extended Operations
  async generateVouchers(id: number, quantity: number) {
    const response = await apiClient.post(`/admin/voucher-campaigns/${id}/generate-vouchers`, {
      quantity
    })
    return response.data
  },

  async getAnalytics(id: number) {
    const response = await apiClient.get(`/admin/voucher-campaigns/${id}/analytics`)
    return response.data
  },

  async exportCodes(id: number, unusedOnly = true) {
    const response = await apiClient.get(`/admin/voucher-campaigns/${id}/export-codes`, {
      params: { unused_only: unusedOnly }
    })
    return response.data
  },

  async activateVouchers(id: number) {
    const response = await apiClient.put(`/admin/voucher-campaigns/${id}/activate-vouchers`)
    return response.data
  },

  async deactivateVouchers(id: number) {
    const response = await apiClient.put(`/admin/voucher-campaigns/${id}/deactivate-vouchers`)
    return response.data
  }
}
```

---

## 🎯 Composables

### **useCampaigns.js**
```typescript
import { ref, computed } from 'vue'
import { voucherCampaignApi } from '@/services/voucherCampaignApi'

export function useCampaigns() {
  const campaigns = ref([])
  const loading = ref(false)
  const totalItems = ref(0)

  const activeCampaigns = computed(() => 
    campaigns.value.filter(c => c.is_campaign_active)
  )

  async function loadCampaigns(options = {}) {
    loading.value = true
    try {
      const response = await voucherCampaignApi.getList(options)
      campaigns.value = response.data
      totalItems.value = response.meta.total
    } catch (error) {
      console.error('Failed to load campaigns:', error)
    } finally {
      loading.value = false
    }
  }

  async function createCampaign(data) {
    const response = await voucherCampaignApi.create(data)
    campaigns.value.unshift(response.data)
    return response.data
  }

  return {
    campaigns,
    loading,
    totalItems,
    activeCampaigns,
    loadCampaigns,
    createCampaign
  }
}
```

---

## 🔒 Permissions & Guards

```typescript
// Router guards cho các trang admin
const adminRoutes = [
  {
    path: '/admin/voucher-campaigns',
    component: () => import('@/views/admin/voucher-campaigns/CampaignListView.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['admin', 'manager'],
      permissions: ['view voucher-campaigns']
    }
  },
  {
    path: '/admin/voucher-campaigns/:id',
    component: () => import('@/views/admin/voucher-campaigns/CampaignDetailView.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['admin', 'manager'],
      permissions: ['view voucher-campaigns']
    }
  }
]
```

---

## 📱 Responsive Design Notes

- **Mobile First**: Prioritize mobile experience
- **Stepper Forms**: Use horizontal steppers on desktop, vertical on mobile
- **Data Tables**: Enable horizontal scroll on mobile
- **Charts**: Ensure charts are responsive and readable on small screens
- **Action Buttons**: Use FABs or bottom sheets on mobile

---

## 🎨 Vuetify Theme Configuration

```typescript
// vuetify.config.js
export default {
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          secondary: '#424242',
          accent: '#82B1FF',
          success: '#4CAF50',
          warning: '#FF9800',
          error: '#F44336',
          'campaign-event': '#9C27B0',
          'campaign-promotion': '#FF5722',
          'campaign-loyalty': '#607D8B'
        }
      }
    }
  }
}
```

---

## ✅ Checklist Implementation

### Phase 1: Core CRUD
- [ ] CampaignListView với data table
- [ ] CampaignFormDialog với stepper
- [ ] VoucherTemplateForm component
- [ ] API service layer
- [ ] Basic routing

### Phase 2: Advanced Features  
- [ ] CampaignDetailView với statistics
- [ ] Voucher generation functionality
- [ ] Analytics charts
- [ ] Export functionality
- [ ] Bulk operations

### Phase 3: UX Enhancements
- [ ] Loading states & skeletons
- [ ] Error handling & notifications
- [ ] Form validation & preview
- [ ] Responsive design
- [ ] Accessibility features

### Phase 4: Performance & Polish
- [ ] Code splitting & lazy loading
- [ ] Caching & optimization  
- [ ] Unit tests
- [ ] E2E tests
- [ ] Documentation

---

## 🚀 Sample Usage Examples

### Tạo campaign mới:
1. Click "Tạo chiến dịch" → Mở stepper dialog
2. Step 1: Nhập basic info + code settings
3. Step 2: Configure voucher template 
4. Step 3: Preview & confirm → Tạo campaign
5. Optionally: Auto-generate initial vouchers

### Quản lý campaign:
1. View list với filters & search
2. Click vào campaign → Chi tiết
3. Generate thêm vouchers theo nhu cầu
4. Export codes để gửi marketing
5. Monitor analytics & performance

---

> **Lưu ý**: Tài liệu này cung cấp blueprint đầy đủ cho việc tạo frontend. Hãy implement từng component một cách tuần tự và test kỹ với API backend.