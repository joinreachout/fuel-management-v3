<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gray-900 px-5 py-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <i class="fas fa-trophy text-yellow-400"></i>
        <span class="text-white font-semibold text-sm">Best Suppliers</span>
        <span class="text-gray-400 text-xs">by price + lead time</span>
      </div>
      <!-- Station filter -->
      <select
        v-model="selectedStation"
        class="bg-gray-800 border border-gray-600 text-gray-200 text-xs rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500"
      >
        <option :value="null">All Stations</option>
        <option v-for="st in stations" :key="st.id" :value="st.id">{{ st.name }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-10">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="rows.length === 0" class="py-10 text-center text-gray-400 text-sm">
      <i class="fas fa-inbox text-2xl mb-2 block"></i>
      No supplier offers found
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-3 py-2.5 text-left font-semibold text-gray-500 w-36">Station</th>
            <th class="px-3 py-2.5 text-left font-semibold text-gray-500 w-24">Fuel</th>
            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">
              <span class="text-yellow-600">★</span> Best (composite)
            </th>
            <th class="px-3 py-2.5 text-right font-semibold text-gray-500">$/ton</th>
            <th class="px-3 py-2.5 text-right font-semibold text-gray-500">Days</th>
            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">
              <span class="text-green-600">$</span> Cheapest
            </th>
            <th class="px-3 py-2.5 text-right font-semibold text-gray-500">$/ton</th>
            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">
              <span class="text-blue-500">⚡</span> Fastest
            </th>
            <th class="px-3 py-2.5 text-right font-semibold text-gray-500">Days</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="row in displayRows"
            :key="row.station_id + '_' + row.fuel_type_id"
            class="hover:bg-gray-50 transition-colors"
          >
            <!-- Station -->
            <td class="px-3 py-2 text-gray-700 font-medium truncate max-w-[9rem]" :title="row.station_name">
              {{ shortStation(row.station_name) }}
            </td>
            <!-- Fuel -->
            <td class="px-3 py-2">
              <span class="px-1.5 py-0.5 rounded text-xs font-mono font-bold bg-gray-200 text-gray-700">
                {{ row.fuel_type_code }}
              </span>
            </td>
            <!-- Best (composite) -->
            <td class="px-3 py-2">
              <div v-if="row.best_supplier" class="flex items-center gap-1.5">
                <span class="text-yellow-500 text-xs">★</span>
                <span class="text-gray-800 font-medium truncate max-w-[10rem]" :title="row.best_supplier.supplier_name">
                  {{ row.best_supplier.supplier_name }}
                </span>
              </div>
              <span v-else class="text-gray-400 italic">—</span>
            </td>
            <!-- Best price -->
            <td class="px-3 py-2 text-right font-mono">
              <span v-if="row.best_supplier" class="text-gray-800">
                {{ row.best_supplier.price_per_ton ? '$' + row.best_supplier.price_per_ton.toFixed(0) : '—' }}
              </span>
            </td>
            <!-- Best days -->
            <td class="px-3 py-2 text-right font-mono">
              <span v-if="row.best_supplier"
                :class="daysColor(row.best_supplier.delivery_days)">
                {{ row.best_supplier.delivery_days }}d
              </span>
            </td>
            <!-- Cheapest supplier -->
            <td class="px-3 py-2">
              <div v-if="cheapest(row)" class="flex items-center gap-1">
                <span class="text-green-600 text-xs">$</span>
                <span class="text-gray-700 truncate max-w-[10rem]" :title="cheapest(row).supplier_name">
                  {{ cheapest(row).supplier_name }}
                </span>
              </div>
              <span v-else class="text-gray-400">—</span>
            </td>
            <!-- Cheapest price -->
            <td class="px-3 py-2 text-right font-mono text-green-700 font-semibold">
              <span v-if="cheapest(row)">
                ${{ cheapest(row).price_per_ton.toFixed(0) }}
              </span>
            </td>
            <!-- Fastest supplier -->
            <td class="px-3 py-2">
              <div v-if="fastest(row)" class="flex items-center gap-1">
                <span class="text-blue-500 text-xs">⚡</span>
                <span class="text-gray-700 truncate max-w-[10rem]" :title="fastest(row).supplier_name">
                  {{ fastest(row).supplier_name }}
                </span>
              </div>
              <span v-else class="text-gray-400">—</span>
            </td>
            <!-- Fastest days -->
            <td class="px-3 py-2 text-right font-mono text-blue-700 font-semibold">
              <span v-if="fastest(row)">{{ fastest(row).delivery_days }}d</span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Show more / show less -->
      <div v-if="rows.length > pageSize" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <span class="text-xs text-gray-400">
          Showing {{ displayRows.length }} of {{ rows.length }} combinations
        </span>
        <button
          @click="expanded = !expanded"
          class="text-xs text-blue-600 hover:text-blue-800 font-medium"
        >
          {{ expanded ? 'Show less ▲' : `Show all ${rows.length} ▼` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { procurementApi, stationsApi } from '../services/api';

const loading          = ref(false);
const rows             = ref([]);
const stations         = ref([]);
const selectedStation  = ref(null);
const expanded         = ref(false);
const pageSize         = 10;

const displayRows = computed(() =>
  expanded.value ? rows.value : rows.value.slice(0, pageSize)
);

const load = async () => {
  loading.value = true;
  try {
    const res = await procurementApi.getBestSuppliers(selectedStation.value);
    if (res.data.success) rows.value = res.data.data;
  } catch (e) {
    console.error('BestSuppliers load error:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  // Load stations for filter dropdown
  try {
    const res = await stationsApi.getAll();
    if (res.data.success) stations.value = res.data.data;
  } catch {}
  await load();
});

watch(selectedStation, () => {
  expanded.value = false;
  load();
});

// Helpers to extract cheapest/fastest from top-3 supplier list
const cheapest = (row) => {
  if (!row.suppliers?.length) return null;
  return row.suppliers.reduce((best, s) =>
    s.price_per_ton < best.price_per_ton ? s : best
  );
};

const fastest = (row) => {
  if (!row.suppliers?.length) return null;
  return row.suppliers.reduce((best, s) =>
    s.delivery_days < best.delivery_days ? s : best
  );
};

const daysColor = (days) => {
  if (days <= 15) return 'text-green-700 font-semibold';
  if (days <= 25) return 'text-yellow-700';
  return 'text-red-600';
};

// Shorten "Станция Каинда" → "Каинда"
const shortStation = (name) => name?.replace(/^Станция\s+/i, '') ?? name;
</script>
