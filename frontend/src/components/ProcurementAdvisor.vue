<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header with Tabs -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-brain text-indigo-500 mr-2"></i>
        Procurement Advisor
      </h3>
      <p class="text-xs text-gray-500 mt-1">AI-powered procurement recommendations</p>

      <!-- Tabs -->
      <div class="pa-tabs">
        <button
          type="button"
          class="pa-tab"
          :class="{ 'active': activeTab === 'briefing' }"
          @click="activeTab = 'briefing'">
          <i class="fas fa-bullhorn mr-1"></i>Briefing
        </button>
        <button
          type="button"
          class="pa-tab"
          :class="{ 'active': activeTab === 'recommendations' }"
          @click="activeTab = 'recommendations'">
          Recommendations
        </button>
        <button
          type="button"
          class="pa-tab"
          :class="{ 'active': activeTab === 'pricecheck' }"
          @click="activeTab = 'pricecheck'">
          <i class="fas fa-tag mr-1"></i>Price Check
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
      <!-- Briefing Tab -->
      <div v-if="activeTab === 'briefing'" class="space-y-4">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading procurement data...</p>
        </div>

        <div v-else>
          <div v-if="summary.mandatory_orders > 0" class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-500 p-4 rounded-r-lg">
            <div class="flex items-start gap-3">
              <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-1"></i>
              <div>
                <div class="font-bold text-amber-900 mb-1">Urgent Action Required</div>
                <div class="text-sm text-amber-800">
                  {{ summary.mandatory_orders }} station{{ summary.mandatory_orders > 1 ? 's' : '' }} will reach critical levels soon. Immediate procurement recommended.
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="bg-blue-50 p-3 rounded-lg">
              <div class="text-xs text-blue-600 font-semibold mb-1">Mandatory Orders</div>
              <div class="text-2xl font-bold text-blue-900">{{ summary.mandatory_orders || 0 }}</div>
              <div class="text-xs text-blue-600 mt-1">Critical shortage risk</div>
            </div>

            <div class="bg-emerald-50 p-3 rounded-lg">
              <div class="text-xs text-emerald-600 font-semibold mb-1">Recommended Orders</div>
              <div class="text-2xl font-bold text-emerald-900">{{ summary.recommended_orders || 0 }}</div>
              <div class="text-xs text-emerald-600 mt-1">Optimize stock levels</div>
            </div>

            <div class="bg-purple-50 p-3 rounded-lg">
              <div class="text-xs text-purple-600 font-semibold mb-1">Avg Lead Time</div>
              <div class="text-2xl font-bold text-purple-900">{{ summary.avg_lead_time_days || 0 }} days</div>
              <div class="text-xs text-purple-600 mt-1">Based on suppliers</div>
            </div>

            <div class="bg-orange-50 p-3 rounded-lg">
              <div class="text-xs text-orange-600 font-semibold mb-1">Total Value</div>
              <div class="text-2xl font-bold text-orange-900">{{ formatCurrency(summary.total_value_estimate) }}</div>
              <div class="text-xs text-orange-600 mt-1">Recommended orders</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recommendations Tab -->
      <div v-if="activeTab === 'recommendations'" class="space-y-3">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading recommendations...</p>
        </div>

        <div v-else-if="recommendations.length === 0" class="text-center py-8">
          <i class="fas fa-check-circle text-green-500 text-3xl"></i>
          <p class="text-sm text-gray-600 mt-2 font-semibold">All stock levels are healthy!</p>
          <p class="text-xs text-gray-500 mt-1">No urgent procurement needed at this time.</p>
        </div>

        <div v-else>
          <div
            v-for="rec in recommendations"
            :key="rec.id"
            class="border rounded-lg p-4 hover:shadow-md transition-shadow"
            :class="getBorderClass(rec.urgency)">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="font-semibold text-gray-800">{{ rec.station_name }}</div>
                <div class="text-sm text-gray-600">{{ rec.depot_name }} • {{ rec.fuel_type }}</div>
              </div>
              <span
                class="px-2 py-1 rounded text-xs font-bold"
                :class="getUrgencyClass(rec.urgency)">
                {{ rec.urgency }}
              </span>
            </div>

            <div class="grid grid-cols-4 gap-2 text-xs mb-3">
              <div>
                <div class="text-gray-500">Current Stock</div>
                <div class="font-semibold text-gray-900">{{ formatTons(rec.current_stock_tons) }}</div>
                <div class="text-gray-400 text-xs">{{ rec.fill_percentage }}%</div>
              </div>
              <div>
                <div class="text-gray-500">Days Left</div>
                <div class="font-semibold text-gray-900">{{ rec.days_left }} days</div>
                <div class="text-gray-400 text-xs">{{ rec.critical_date }}</div>
              </div>
              <div>
                <div class="text-gray-500">Order By</div>
                <div class="font-semibold text-orange-600">{{ rec.last_order_date }}</div>
              </div>
              <div>
                <div class="text-gray-500">Recommended</div>
                <div class="font-semibold text-emerald-600">{{ formatTons(rec.recommended_order_tons) }}</div>
              </div>
            </div>

            <div v-if="rec.best_supplier" class="bg-white bg-opacity-50 rounded p-2 text-xs mb-3">
              <div class="text-gray-500 mb-1">Best Supplier</div>
              <div class="flex items-center justify-between">
                <span class="font-semibold text-gray-800">{{ rec.best_supplier.name }}</span>
                <span class="text-gray-600">{{ rec.best_supplier.avg_delivery_days }} days delivery</span>
              </div>
            </div>

            <!-- PO Pending banner — shown when a Purchase Order already exists -->
            <div v-if="rec.po_pending && rec.active_po"
              class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-3 text-xs flex items-start gap-2">
              <i class="fas fa-clipboard-check text-blue-500 mt-0.5 flex-shrink-0"></i>
              <div>
                <div class="font-semibold text-blue-800">PO Issued — Awaiting ERP Confirmation</div>
                <div class="text-blue-600 mt-0.5">
                  {{ rec.active_po.order_number }} •
                  {{ rec.active_po.quantity_tons }} t •
                  Delivery: {{ rec.active_po.delivery_date }}
                </div>
              </div>
            </div>

            <!-- Action button: different state when PO already exists -->
            <button
              v-if="rec.po_pending"
              type="button"
              @click="router.push('/orders')"
              class="w-full bg-blue-100 text-blue-700 py-2 px-4 rounded-lg text-sm font-semibold hover:bg-blue-200 transition-all border border-blue-200">
              <i class="fas fa-external-link-alt mr-1"></i>
              View Purchase Orders
            </button>
            <button
              v-else
              type="button"
              @click="router.push('/orders')"
              class="w-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:from-blue-600 hover:to-indigo-600 transition-all">
              Create Order
            </button>
          </div>
        </div>
      </div>

      <!-- Price Check Tab -->
      <div v-if="activeTab === 'pricecheck'" class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="text-sm font-semibold text-gray-700 mb-3">Current Market Prices</div>
          <div class="space-y-2">
            <div v-for="price in marketPrices" :key="price.fuel" class="flex items-center justify-between">
              <span class="text-sm text-gray-700">{{ price.fuel }}</span>
              <div class="flex items-center gap-2">
                <span class="font-bold text-gray-900">{{ price.price }}</span>
                <span
                  class="text-xs px-2 py-0.5 rounded"
                  :class="price.trend === 'up' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'">
                  {{ price.trend === 'up' ? '↑' : '↓' }} {{ price.change }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
          <div class="flex items-start gap-2">
            <i class="fas fa-lightbulb text-blue-600 text-lg mt-0.5"></i>
            <div class="text-sm text-blue-900">
              <span class="font-bold">Price Alert:</span> Diesel prices expected to increase by 3% next week. Consider early procurement.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { procurementApi } from '../services/api';

const router = useRouter();

const activeTab = ref('briefing');
const loading = ref(true);
const shortages = ref([]);
const summary = ref({
  total_shortages: 0,
  mandatory_orders: 0,
  recommended_orders: 0,
  avg_lead_time_days: 0,
  total_value_estimate: 0
});

// Market prices - static for now (could be fetched from API later)
const marketPrices = [
  { fuel: 'Diesel B7', price: '€1.42/L', trend: 'up', change: '2.1%' },
  { fuel: 'Petrol 95', price: '€1.58/L', trend: 'down', change: '0.8%' },
  { fuel: 'Petrol 98', price: '€1.72/L', trend: 'up', change: '1.2%' }
];

// Format currency
const formatCurrency = (value) => {
  if (!value || value === 0) return '$0';
  if (value >= 1000000) {
    return `$${(value / 1000000).toFixed(1)}M`;
  }
  if (value >= 1000) {
    return `$${(value / 1000).toFixed(0)}K`;
  }
  return `$${value.toFixed(0)}`;
};

// Format tons
const formatTons = (value) => {
  if (!value) return '0 t';
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K t`;
  }
  return `${value.toFixed(1)} t`;
};

// Get urgency badge class
const getUrgencyClass = (urgency) => {
  const classes = {
    'CATASTROPHE': 'bg-red-600 text-white',
    'CRITICAL': 'bg-red-500 text-white',
    'MUST_ORDER': 'bg-orange-500 text-white',
    'WARNING': 'bg-yellow-500 text-white',
    'PLANNED': 'bg-blue-500 text-white'
  };
  return classes[urgency] || 'bg-gray-500 text-white';
};

// Get border class for urgency
const getBorderClass = (urgency) => {
  const classes = {
    'CATASTROPHE': 'border-red-500 bg-red-50',
    'CRITICAL': 'border-red-400 bg-red-50',
    'MUST_ORDER': 'border-orange-400 bg-orange-50',
    'WARNING': 'border-yellow-400 bg-yellow-50',
    'PLANNED': 'border-blue-300 bg-blue-50'
  };
  return classes[urgency] || 'border-gray-200';
};

// Computed recommendations from shortages
const recommendations = computed(() => {
  return shortages.value.map(s => ({
    id: s.depot_id + '_' + s.fuel_type_id,
    station_name: s.station_name,
    depot_name: s.depot_name,
    fuel_type: s.fuel_type_name,
    urgency: s.urgency,
    days_left: s.days_left,
    critical_date: s.critical_date,
    last_order_date: s.last_order_date,
    current_stock_tons: s.current_stock_tons,
    fill_percentage: s.fill_percentage,
    recommended_order_tons: s.recommended_order_tons,
    best_supplier: s.best_supplier,
    // PO tracking
    po_pending: s.po_pending || false,
    active_po:  s.active_po  || null
  }));
});

// Load data
const loadData = async () => {
  try {
    loading.value = true;

    // Load both summary and shortages
    const [summaryResp, shortagesResp] = await Promise.all([
      procurementApi.getSummary(),
      procurementApi.getUpcomingShortages(14)
    ]);

    if (summaryResp.data.success) {
      summary.value = summaryResp.data.data;
    }

    if (shortagesResp.data.success) {
      shortages.value = shortagesResp.data.data;
    }

  } catch (error) {
    console.error('Error loading procurement data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Procurement Advisor Tabs */
.pa-tabs {
  display: flex;
  gap: 0;
  padding: 0 24px;
  margin: 0 -24px;
  margin-top: 12px;
  margin-bottom: 0;
}

.pa-tab {
  background: #e0e7ff;
  border: 1px solid transparent;
  padding: 10px 20px;
  font-size: 14px;
  color: #4338ca;
  cursor: pointer;
  margin-bottom: -1px;
  border-radius: 6px 6px 0 0;
  transition: background 0.2s, color 0.2s;
}

.pa-tab:hover:not(.active) {
  background: #c7d2fe;
}

.pa-tab.active {
  background: #fff;
  color: #0f172a;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  border-bottom: 1px solid #fff;
}
</style>
