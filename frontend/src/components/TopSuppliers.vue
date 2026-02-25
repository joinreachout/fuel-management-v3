<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-gray-800">
          <i class="fas fa-trophy text-yellow-500 mr-2"></i>
          Top Suppliers
        </h3>
        <p class="text-xs text-gray-500 mt-1">Ranked by delivery speed &amp; coverage</p>
      </div>
      <span class="bg-gray-100 text-gray-500 text-xs font-mono px-2 py-0.5 rounded-full border border-gray-200">
        {{ suppliers.length }} active
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-yellow-500"></div>
    </div>

    <!-- List -->
    <div v-else class="divide-y divide-gray-100">

      <!-- Top 3 -->
      <div v-for="(supplier, index) in top3" :key="supplier.id">
        <div
          class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition-colors"
          @click="toggleExpanded(supplier.id)"
        >
          <!-- Rank badge -->
          <div
            class="flex-none w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
            :class="rankClass(index)"
          >#{{ index + 1 }}</div>

          <!-- Name + location -->
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 truncate">{{ supplier.name }}</div>
            <div v-if="supplier.location" class="text-xs text-gray-400 truncate">{{ supplier.location }}</div>
          </div>

          <!-- Delivery days -->
          <div
            class="flex-none flex items-center gap-1 text-xs text-blue-600 font-medium"
            title="Delivery days"
          >
            <i class="fas fa-truck text-blue-400" style="font-size:10px"></i>
            <span>{{ deliveryRange(supplier) }}&thinsp;days</span>
          </div>

          <!-- Chevron -->
          <i
            class="fas fa-chevron-down text-gray-400 flex-none transition-transform"
            style="font-size:10px"
            :class="{ 'rotate-180': expandedId === supplier.id }"
          ></i>
        </div>

        <!-- Expanded: prices + stats -->
        <div
          v-if="expandedId === supplier.id"
          class="bg-gray-50 px-4 py-3 border-t border-gray-100 text-xs"
        >
          <!-- Prices per fuel type -->
          <div v-if="supplier.prices?.length" class="mb-2">
            <div class="font-semibold text-gray-500 mb-1.5">Prices ($/ton)</div>
            <div class="grid grid-cols-2 gap-1">
              <div
                v-for="p in supplier.prices"
                :key="p.fuel_type_id"
                class="flex items-center justify-between bg-white rounded px-2 py-1 border border-gray-200"
              >
                <span class="font-mono font-bold text-gray-600">{{ p.fuel_type_code }}</span>
                <span class="font-semibold text-gray-800">${{ Math.round(p.price_per_ton) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="italic text-gray-400 mb-2">No price offers on file</div>

          <!-- Stats -->
          <div class="flex flex-wrap gap-x-3 gap-y-1 text-gray-500 mt-1">
            <span>
              <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
              {{ supplier.stations_served }} stations
            </span>
            <span>
              <i class="fas fa-file-invoice mr-1 text-gray-400"></i>
              {{ supplier.erp_orders_count }} ERP orders
            </span>
            <span v-if="supplier.delivered_rate !== null">
              <i class="fas fa-check-circle mr-1 text-gray-400"></i>
              {{ supplier.delivered_rate }}% delivered
              <span class="text-gray-300 italic ml-1">(no on-time data)</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Show more toggle -->
      <div v-if="rest.length">
        <button
          class="w-full px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center gap-1 font-medium"
          @click="showRest = !showRest"
        >
          <i
            class="fas fa-chevron-down transition-transform"
            style="font-size:10px"
            :class="{ 'rotate-180': showRest }"
          ></i>
          {{ showRest ? 'Show less' : `${rest.length} more suppliers` }}
        </button>

        <div v-if="showRest" class="divide-y divide-gray-100">
          <div
            v-for="supplier in rest"
            :key="supplier.id"
            class="flex items-center gap-3 px-4 py-2 text-xs hover:bg-gray-50"
          >
            <div class="flex-none w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium">
              {{ supplier.priority }}
            </div>
            <div class="flex-1 min-w-0 text-gray-700 truncate font-medium">{{ supplier.name }}</div>
            <div class="flex-none flex items-center gap-1 text-blue-500" title="Delivery days">
              <i class="fas fa-truck text-blue-300" style="font-size:9px"></i>
              <span>{{ deliveryRange(supplier) }}&thinsp;days</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50">
      <router-link to="/parameters" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
        Manage supply offers in Parameters →
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { suppliersApi } from '../services/api.js';

const loading    = ref(true);
const suppliers  = ref([]);
const expandedId = ref(null);
const showRest   = ref(false);

const top3 = computed(() => suppliers.value.slice(0, 3));
const rest = computed(() => suppliers.value.slice(3));

const loadData = async () => {
  loading.value = true;
  try {
    const res = await suppliersApi.getTop();
    suppliers.value = res.data?.data || [];
  } catch (e) {
    console.error('TopSuppliers: failed to load', e);
  } finally {
    loading.value = false;
  }
};

const toggleExpanded = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const deliveryRange = (s) => {
  if (s.min_delivery_days === null) return '—';
  if (s.min_delivery_days === s.max_delivery_days) return s.min_delivery_days;
  return `${s.min_delivery_days}–${s.max_delivery_days}`;
};

const rankClass = (index) => {
  if (index === 0) return 'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white';
  if (index === 1) return 'bg-gradient-to-br from-gray-300 to-gray-500 text-white';
  return 'bg-gradient-to-br from-orange-300 to-orange-500 text-white';
};

onMounted(loadData);
</script>
