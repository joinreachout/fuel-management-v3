<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-trophy text-yellow-500 mr-2"></i>
        Top 3 Suppliers
      </h3>
      <p class="text-xs text-gray-500 mt-1">Ranked by composite score: delivery speed (50%) + pricing (50%)</p>
    </div>

    <!-- Content -->
    <div class="px-6 py-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Supplier Card -->
        <div
          v-for="(supplier, index) in suppliers"
          :key="supplier.id"
          class="relative overflow-hidden rounded-xl border-2 transition-all hover:shadow-lg cursor-pointer"
          :class="{
            'border-yellow-400 bg-gradient-to-br from-yellow-50 to-amber-50': index === 0,
            'border-gray-300 bg-gradient-to-br from-gray-50 to-slate-50': index === 1,
            'border-orange-300 bg-gradient-to-br from-orange-50 to-amber-50': index === 2
          }"
          @click="toggleDetails(supplier.id)">

          <!-- Rank Badge -->
          <div class="absolute top-3 right-3 w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg shadow-lg"
               :class="{
                 'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white': index === 0,
                 'bg-gradient-to-br from-gray-300 to-gray-500 text-white': index === 1,
                 'bg-gradient-to-br from-orange-400 to-orange-600 text-white': index === 2
               }">
            #{{ index + 1 }}
          </div>

          <!-- Main Info -->
          <div class="p-5">
            <div class="flex items-start gap-4 mb-4">
              <!-- Logo/Icon -->
              <div class="w-16 h-16 rounded-lg flex items-center justify-center text-2xl shadow-md"
                   :class="{
                     'bg-yellow-100 text-yellow-600': index === 0,
                     'bg-gray-100 text-gray-600': index === 1,
                     'bg-orange-100 text-orange-600': index === 2
                   }">
                <i class="fas fa-truck-loading"></i>
              </div>

              <!-- Supplier Details -->
              <div class="flex-1">
                <h4 class="text-lg font-bold text-gray-900 mb-1">{{ supplier.name }}</h4>
                <div class="text-xs text-gray-500 mb-2">{{ supplier.location }}</div>

                <!-- Composite Score -->
                <div class="flex items-center gap-2">
                  <div class="text-2xl font-bold"
                       :class="{
                         'text-yellow-600': index === 0,
                         'text-gray-600': index === 1,
                         'text-orange-600': index === 2
                       }">
                    {{ supplier.compositeScore }}
                  </div>
                  <div class="text-xs text-gray-500">/ 100</div>
                  <div class="ml-2 flex items-center gap-1">
                    <i v-for="star in 5" :key="star"
                       class="fas fa-star text-xs"
                       :class="star <= Math.round(supplier.compositeScore / 20) ? 'text-yellow-400' : 'text-gray-300'"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Score Breakdown -->
            <div class="grid grid-cols-2 gap-3 mb-4">
              <!-- Delivery Speed -->
              <div class="p-3 rounded-lg bg-white border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fas fa-shipping-fast text-blue-500"></i>
                  <span class="text-xs font-semibold text-gray-700">Delivery Speed</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="text-lg font-bold text-blue-600">{{ supplier.deliveryScore }}</div>
                  <div class="text-xs text-gray-500">50% weight</div>
                </div>
                <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-1000"
                       :style="{ width: `${supplier.deliveryScore}%` }">
                  </div>
                </div>
              </div>

              <!-- Pricing -->
              <div class="p-3 rounded-lg bg-white border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fas fa-dollar-sign text-green-500"></i>
                  <span class="text-xs font-semibold text-gray-700">Pricing</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="text-lg font-bold text-green-600">{{ supplier.pricingScore }}</div>
                  <div class="text-xs text-gray-500">50% weight</div>
                </div>
                <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-green-500 to-green-600 rounded-full transition-all duration-1000"
                       :style="{ width: `${supplier.pricingScore}%` }">
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="p-2 rounded bg-white border border-gray-200">
                <div class="text-xs text-gray-500">Avg Lead Time</div>
                <div class="text-sm font-bold text-gray-800">{{ supplier.avgLeadTime }}d</div>
              </div>
              <div class="p-2 rounded bg-white border border-gray-200">
                <div class="text-xs text-gray-500">On-Time Rate</div>
                <div class="text-sm font-bold text-gray-800">{{ supplier.onTimeRate }}%</div>
              </div>
              <div class="p-2 rounded bg-white border border-gray-200">
                <div class="text-xs text-gray-500">Orders YTD</div>
                <div class="text-sm font-bold text-gray-800">{{ supplier.ordersYTD }}</div>
              </div>
            </div>

            <!-- Expandable Details -->
            <div v-if="expandedId === supplier.id" class="mt-4 pt-4 border-t border-gray-200">
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Reliability Score</span>
                  <span class="font-semibold text-gray-900">{{ supplier.reliabilityScore }}/10</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Quality Rating</span>
                  <span class="font-semibold text-gray-900">{{ supplier.qualityRating }}/10</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Payment Terms</span>
                  <span class="font-semibold text-gray-900">{{ supplier.paymentTerms }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Min Order Quantity</span>
                  <span class="font-semibold text-gray-900">{{ supplier.minOrderQty }} L</span>
                </div>
                <div class="mt-3">
                  <a href="#" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    Full profile in Parameters →
                  </a>
                </div>
              </div>
            </div>

            <!-- Expand Button -->
            <button
              class="w-full mt-3 py-2 text-xs font-medium text-gray-600 hover:text-gray-900 transition-colors flex items-center justify-center gap-2"
              @click.stop="toggleDetails(supplier.id)">
              <span>{{ expandedId === supplier.id ? 'Show Less' : 'Show More' }}</span>
              <i class="fas fa-chevron-down transition-transform"
                 :class="{ 'rotate-180': expandedId === supplier.id }"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div class="mt-6 text-center">
        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
          View all suppliers in Parameters →
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const loading = ref(false);
const expandedId = ref(null);

// Mock supplier data
const suppliers = ref([
  {
    id: 1,
    name: 'Premium Fuels Ltd.',
    location: 'Regional Hub - North',
    compositeScore: 94,
    deliveryScore: 96,
    pricingScore: 92,
    avgLeadTime: 2,
    onTimeRate: 98,
    ordersYTD: 156,
    reliabilityScore: 9.4,
    qualityRating: 9.6,
    paymentTerms: 'Net 30',
    minOrderQty: '10,000'
  },
  {
    id: 2,
    name: 'Global Energy Supply Co.',
    location: 'International Hub',
    compositeScore: 89,
    deliveryScore: 90,
    pricingScore: 88,
    avgLeadTime: 3,
    onTimeRate: 95,
    ordersYTD: 142,
    reliabilityScore: 8.9,
    qualityRating: 9.2,
    paymentTerms: 'Net 45',
    minOrderQty: '15,000'
  },
  {
    id: 3,
    name: 'FastTrack Petroleum',
    location: 'Regional Hub - South',
    compositeScore: 86,
    deliveryScore: 88,
    pricingScore: 84,
    avgLeadTime: 3,
    onTimeRate: 92,
    ordersYTD: 128,
    reliabilityScore: 8.5,
    qualityRating: 8.8,
    paymentTerms: 'Net 30',
    minOrderQty: '12,000'
  }
]);

const toggleDetails = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};
</script>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
