<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6">

    <!-- Header -->
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="fas fa-hourglass-half text-red-500 text-sm"></i>
        <span class="text-sm font-bold text-gray-800">Stockout Forecast</span>
        <span class="text-xs text-gray-400 ml-1">— days until fuel runs out by location</span>
      </div>
      <div class="flex items-center gap-3">
        <!-- threshold toggle -->
        <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
          <input type="checkbox" v-model="showAll" class="rounded accent-blue-500">
          Show all
        </label>
        <span v-if="!loading && top10.length"
          class="text-xs text-gray-400">{{ top10.length }} shown</span>
        <button @click="load" class="text-gray-400 hover:text-gray-600 transition-colors p-1" title="Refresh">
          <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <i class="fas fa-spinner fa-spin text-gray-300 text-2xl"></i>
    </div>

    <!-- Content -->
    <div v-else-if="top10.length || byFuelType.length" class="flex gap-0 divide-x divide-gray-100">

      <!-- LEFT: By fuel type summary -->
      <div class="w-72 flex-shrink-0 p-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">By Fuel Type</div>
        <div class="space-y-2">
          <div v-for="ft in byFuelType" :key="ft.fuel_type_id"
            class="flex items-center gap-3 p-2.5 rounded-xl border transition-colors"
            :class="urgencyBg(ft.nearest_days)">
            <div class="flex-1 min-w-0">
              <div class="text-xs font-bold text-gray-800 truncate">{{ ft.fuel_type_name }}</div>
              <div class="text-[11px] text-gray-500 mt-0.5 truncate" :title="ft.nearest_station">
                {{ shortStation(ft.nearest_station) }}
              </div>
            </div>
            <div class="text-right shrink-0">
              <div class="font-black text-lg leading-none" :class="urgencyText(ft.nearest_days)">
                {{ Math.round(ft.nearest_days) }}
              </div>
              <div class="text-[10px] text-gray-400">days</div>
            </div>
            <div class="text-right shrink-0 w-8">
              <div v-if="ft.count_in_45_days > 0"
                class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold"
                :class="ft.count_in_45_days >= 3 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'"
                :title="`${ft.count_in_45_days} stockouts in 45 days`">
                {{ ft.count_in_45_days }}
              </div>
              <div v-else class="w-6 h-6"></div>
            </div>
          </div>

          <!-- Legend for count bubbles -->
          <div class="pt-2 text-[10px] text-gray-400 flex items-center gap-1">
            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-100 text-red-600 font-bold text-[9px]">N</span>
            = stockouts within 45 days
          </div>
        </div>
      </div>

      <!-- RIGHT: Top 10 table -->
      <div class="flex-1 p-4 min-w-0">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
          {{ showAll ? 'All locations' : 'Nearest stockouts' }}
        </div>
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="text-left pb-2 font-semibold text-gray-400 w-8">#</th>
              <th class="text-left pb-2 font-semibold text-gray-400">Station</th>
              <th class="text-left pb-2 font-semibold text-gray-400">Fuel</th>
              <th class="text-center pb-2 font-semibold text-gray-400">Days left</th>
              <th class="text-left pb-2 font-semibold text-gray-400">Empty date</th>
              <th class="text-right pb-2 font-semibold text-gray-400">L/day</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="(item, i) in displayedRows" :key="`${item.station_id}_${item.fuel_type_id}`"
              class="hover:bg-gray-50 transition-colors">
              <td class="py-1.5 text-gray-300 font-mono">{{ i + 1 }}</td>
              <td class="py-1.5 font-medium text-gray-800 pr-2">{{ shortStation(item.station_name) }}</td>
              <td class="py-1.5 text-gray-600 pr-2">{{ item.fuel_type_name }}</td>
              <td class="py-1.5 text-center">
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full font-bold text-[11px]"
                  :class="urgencyBadge(item.days_until_empty)">
                  {{ Math.round(item.days_until_empty) }}d
                </span>
              </td>
              <td class="py-1.5 text-gray-500 whitespace-nowrap">
                {{ item.empty_date || '—' }}
              </td>
              <td class="py-1.5 text-right font-mono text-gray-500">
                {{ Number(item.daily_liters).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Show more / less -->
        <div v-if="top10.length > 10 && !showAll" class="mt-3 text-center">
          <button @click="showAll = true"
            class="text-xs text-blue-500 hover:text-blue-700 transition-colors">
            Show all {{ top10.length }} locations <i class="fas fa-chevron-down ml-0.5"></i>
          </button>
        </div>
      </div>

    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <i class="fas fa-check-circle text-green-400 text-3xl mb-3"></i>
      <p class="text-sm font-semibold text-gray-600">No stockouts predicted</p>
      <p class="text-xs text-gray-400 mt-1">All locations have sufficient fuel stock based on current consumption</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { dashboardApi } from '../services/api'

const loading    = ref(true)
const top10      = ref([])
const byFuelType = ref([])
const showAll    = ref(false)

const displayedRows = computed(() =>
  showAll.value ? top10.value : top10.value.slice(0, 10)
)

async function load() {
  loading.value = true
  try {
    const res = await dashboardApi.getStockoutForecast()
    if (res.data.success) {
      top10.value      = res.data.top_10      || []
      byFuelType.value = res.data.by_fuel_type || []
    }
  } catch (e) {
    console.error('StockoutForecast load error:', e)
  } finally {
    loading.value = false
  }
}

// ── Style helpers — color by days remaining ───────────────────────────────────
function urgencyBg(days) {
  if (days <= 7)  return 'border-red-200 bg-red-50'
  if (days <= 21) return 'border-orange-200 bg-orange-50'
  if (days <= 45) return 'border-yellow-200 bg-yellow-50'
  return 'border-gray-100 bg-gray-50'
}

function urgencyText(days) {
  if (days <= 7)  return 'text-red-600'
  if (days <= 21) return 'text-orange-500'
  if (days <= 45) return 'text-yellow-600'
  return 'text-gray-700'
}

function urgencyBadge(days) {
  if (days <= 7)  return 'bg-red-100 text-red-700'
  if (days <= 21) return 'bg-orange-100 text-orange-700'
  if (days <= 45) return 'bg-yellow-100 text-yellow-700'
  return 'bg-gray-100 text-gray-600'
}

function shortStation(name) {
  return name?.replace(/^Станция\s+/i, '') ?? name
}

onMounted(load)
</script>
