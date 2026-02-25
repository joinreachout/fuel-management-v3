<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gray-900 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="fas fa-trophy text-yellow-400 text-sm"></i>
        <span class="text-white font-semibold text-sm">Suppliers</span>
      </div>
      <span class="bg-gray-700 text-gray-300 text-xs font-mono px-2 py-0.5 rounded-full">
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
      <div
        v-for="(supplier, index) in top3"
        :key="supplier.id"
      >
        <!-- Row -->
        <div
          class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition-colors"
          @click="toggleExpanded(supplier.id)"
        >
          <!-- Rank badge -->
          <div
            class="flex-none w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
            :class="rankClass(index)"
          >{{ index + 1 }}</div>

          <!-- Name + location -->
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 truncate">{{ supplier.name }}</div>
            <div v-if="supplier.location" class="text-xs text-gray-400 truncate">{{ supplier.location }}</div>
          </div>

          <!-- Chips -->
          <div class="flex items-center gap-1.5 flex-none">
            <!-- Delivery range -->
            <span class="text-xs font-mono px-1.5 py-0.5 rounded bg-blue-50 text-blue-700">
              {{ deliveryRange(supplier) }}d
            </span>
            <!-- Stations -->
            <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600" title="Stations served">
              {{ supplier.stations_served }}st
            </span>
            <!-- Delivered rate -->
            <span
              v-if="supplier.delivered_rate !== null"
              class="text-xs px-1.5 py-0.5 rounded font-semibold"
              :class="rateClass(supplier.delivered_rate)"
              title="Delivered rate (not on-time — no timestamp data)"
            >{{ supplier.delivered_rate }}%</span>
          </div>

          <!-- Chevron -->
          <i
            class="fas fa-chevron-down text-gray-400 text-xs flex-none transition-transform"
            :class="{ 'rotate-180': expandedId === supplier.id }"
          ></i>
        </div>

        <!-- Expanded: prices per fuel type -->
        <div
          v-if="expandedId === supplier.id"
          class="bg-gray-50 px-4 py-3 border-t border-gray-100"
        >
          <!-- Prices grid -->
          <div v-if="supplier.prices?.length" class="mb-2">
            <div class="text-xs font-semibold text-gray-500 mb-1.5">Prices ($/ton)</div>
            <div class="grid grid-cols-2 gap-1.5">
              <div
                v-for="p in supplier.prices"
                :key="p.fuel_type_id"
                class="flex items-center justify-between bg-white rounded px-2 py-1 border border-gray-200"
              >
                <span class="font-mono text-xs font-bold text-gray-600">{{ p.fuel_type_code }}</span>
                <span class="text-xs font-semibold text-gray-800">${{ p.price_per_ton.toFixed(0) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-gray-400 italic mb-2">No price offers on file</div>

          <!-- Extra stats -->
          <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
            <span>ERP orders: <strong class="text-gray-700">{{ supplier.erp_orders_count }}</strong></span>
            <span>Volume: <strong class="text-gray-700">{{ supplier.total_volume_kl }} kL</strong></span>
            <span>Spend: <strong class="text-gray-700">${{ fmtMoney(supplier.total_spend) }}</strong></span>
            <span v-if="supplier.delivered_rate !== null" class="text-gray-400 italic">
              (delivered rate, not on-time — no timestamp data)
            </span>
          </div>
        </div>
      </div>

      <!-- More suppliers (collapsed) -->
      <div v-if="rest.length">
        <button
          class="w-full px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center gap-1 font-medium"
          @click="showRest = !showRest"
        >
          <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': showRest }"></i>
          {{ showRest ? 'Show less' : `${rest.length} more suppliers` }}
        </button>

        <div v-if="showRest" class="divide-y divide-gray-100">
          <div
            v-for="supplier in rest"
            :key="supplier.id"
            class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50"
          >
            <div class="flex-none w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500 font-medium">
              {{ supplier.priority }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-medium text-gray-700 truncate">{{ supplier.name }}</div>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
              <span class="font-mono">{{ deliveryRange(supplier) }}d</span>
              <span>{{ supplier.stations_served }}st</span>
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

// "15–30d" if range differs, "15d" if same
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

const rateClass = (rate) => {
  if (rate >= 90) return 'bg-green-100 text-green-700';
  if (rate >= 70) return 'bg-yellow-100 text-yellow-700';
  return 'bg-red-100 text-red-700';
};

const fmtMoney = (v) => {
  if (!v) return '0';
  if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
  if (v >= 1_000)     return (v / 1_000).toFixed(0) + 'k';
  return v.toFixed(0);
};

onMounted(loadData);
</script>
