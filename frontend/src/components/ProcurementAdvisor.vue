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
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-500 p-4 rounded-r-lg">
          <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-1"></i>
            <div>
              <div class="font-bold text-amber-900 mb-1">Urgent Action Required</div>
              <div class="text-sm text-amber-800">
                3 stations will reach critical levels within 48 hours. Immediate procurement recommended.
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="bg-blue-50 p-3 rounded-lg">
            <div class="text-xs text-blue-600 font-semibold mb-1">Mandatory Orders</div>
            <div class="text-2xl font-bold text-blue-900">2</div>
            <div class="text-xs text-blue-600 mt-1">Critical shortage risk</div>
          </div>

          <div class="bg-emerald-50 p-3 rounded-lg">
            <div class="text-xs text-emerald-600 font-semibold mb-1">Recommended Orders</div>
            <div class="text-2xl font-bold text-emerald-900">5</div>
            <div class="text-xs text-emerald-600 mt-1">Optimize stock levels</div>
          </div>

          <div class="bg-purple-50 p-3 rounded-lg">
            <div class="text-xs text-purple-600 font-semibold mb-1">Avg Lead Time</div>
            <div class="text-2xl font-bold text-purple-900">3.2 days</div>
            <div class="text-xs text-purple-600 mt-1">Based on suppliers</div>
          </div>

          <div class="bg-orange-50 p-3 rounded-lg">
            <div class="text-xs text-orange-600 font-semibold mb-1">Total Value</div>
            <div class="text-2xl font-bold text-orange-900">€284K</div>
            <div class="text-xs text-orange-600 mt-1">Recommended orders</div>
          </div>
        </div>
      </div>

      <!-- Recommendations Tab -->
      <div v-if="activeTab === 'recommendations'" class="space-y-3">
        <div
          v-for="rec in recommendations"
          :key="rec.id"
          class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
          :class="rec.priority === 'critical' ? 'border-red-300 bg-red-50' : 'border-gray-200'">
          <div class="flex items-start justify-between mb-2">
            <div>
              <div class="font-semibold text-gray-800">{{ rec.location }}</div>
              <div class="text-sm text-gray-600">{{ rec.fuelType }}</div>
            </div>
            <span
              class="px-2 py-1 rounded text-xs font-bold"
              :class="rec.priority === 'critical' ? 'bg-red-500 text-white' : 'bg-blue-500 text-white'">
              {{ rec.priority.toUpperCase() }}
            </span>
          </div>

          <div class="grid grid-cols-3 gap-2 text-xs mb-3">
            <div>
              <div class="text-gray-500">Current Stock</div>
              <div class="font-semibold text-gray-900">{{ rec.currentStock }}</div>
            </div>
            <div>
              <div class="text-gray-500">Days Until Empty</div>
              <div class="font-semibold text-gray-900">{{ rec.daysUntilEmpty }}</div>
            </div>
            <div>
              <div class="text-gray-500">Recommended Order</div>
              <div class="font-semibold text-emerald-600">{{ rec.recommendedOrder }}</div>
            </div>
          </div>

          <button
            type="button"
            class="w-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:from-blue-600 hover:to-indigo-600 transition-all">
            Create Order
          </button>
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
import { ref } from 'vue';

const activeTab = ref('briefing');

const recommendations = [
  {
    id: 1,
    location: 'Station Рыбачье',
    fuelType: 'Diesel B7',
    currentStock: '12,450 L',
    daysUntilEmpty: '2.1 days',
    recommendedOrder: '35,000 L',
    priority: 'critical'
  },
  {
    id: 2,
    location: 'Depot МЧС Ош',
    fuelType: 'Petrol 95',
    currentStock: '28,900 L',
    daysUntilEmpty: '3.5 days',
    recommendedOrder: '50,000 L',
    priority: 'critical'
  },
  {
    id: 3,
    location: 'Station Нарын',
    fuelType: 'Diesel B7',
    currentStock: '45,600 L',
    daysUntilEmpty: '6.2 days',
    recommendedOrder: '40,000 L',
    priority: 'recommended'
  }
];

const marketPrices = [
  { fuel: 'Diesel B7', price: '€1.42/L', trend: 'up', change: '2.1%' },
  { fuel: 'Petrol 95', price: '€1.58/L', trend: 'down', change: '0.8%' },
  { fuel: 'Petrol 98', price: '€1.72/L', trend: 'up', change: '1.2%' }
];
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
