<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Fixed Black Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <img src="/kkt_logo.png" alt="Kitty Kat Technologies" class="h-12 w-auto" style="filter: brightness(0) invert(1);">
        <nav class="flex items-center gap-6">
          <router-link to="/" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</router-link>
          <router-link to="/orders" class="text-gray-400 hover:text-white transition-colors text-sm">Orders</router-link>
          <router-link to="/transfers" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Transfers</router-link>
          <router-link to="/parameters" class="text-gray-400 hover:text-white transition-colors text-sm">Parameters</router-link>
          <router-link to="/import" class="text-gray-400 hover:text-white transition-colors text-sm">Import</router-link>
          <router-link to="/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</router-link>
        </nav>
      </div>
    </div>

    <!-- Spacer -->
    <div class="h-20 bg-black"></div>

    <!-- Dark Hero Header -->
    <header class="bg-black relative">
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute right-0 top-0 bottom-0 w-2/3" style="
          background-image: linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0.7) 15%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0) 60%), url('/rev3/truck_header.jpg');
          background-size: auto 100%;
          background-position: center right;
          background-repeat: no-repeat;
          opacity: 0.85;
        "></div>
      </div>

      <div class="relative px-8 py-3 pb-24">
        <!-- ROW 1: Title + KPIs -->
        <div class="flex items-start justify-between mb-2 mt-6">
          <div>
            <h1 class="text-2xl font-bold text-white mb-1">
              <i class="fas fa-truck mr-3 text-gray-400"></i>Transfers
            </h1>
            <p class="text-sm text-gray-400">Fuel transfer management between stations</p>
          </div>
          <div class="flex items-center gap-10 pt-1">
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white">{{ stats.total_transfers || 0 }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Total</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="(stats.pending_transfers || 0) > 0 ? 'text-orange-400' : 'text-white'">
                {{ stats.pending_transfers || 0 }}
              </div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Pending</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="(stats.in_progress_transfers || 0) > 0 ? 'text-blue-400' : 'text-white'">
                {{ stats.in_progress_transfers || 0 }}
              </div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">In</div>
                <div class="text-white text-xs font-semibold">Progress</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-green-400">{{ stats.completed_transfers || 0 }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Completed</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ROW 2: Info chips -->
        <div class="flex items-center gap-4 pb-3 mt-4">
          <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="far fa-clock text-gray-400"></i>
            <span class="font-medium text-gray-300">{{ currentDateTime }}</span>
          </div>
          <div v-if="(stats.pending_transfers || 0) + (stats.in_progress_transfers || 0) > 0"
            class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="fas fa-circle text-orange-400" style="font-size:8px"></i>
            <span class="font-medium text-gray-300">
              {{ (stats.pending_transfers || 0) + (stats.in_progress_transfers || 0) }} active transfers
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content — big white card overlapping header -->
    <div class="relative -mt-16 z-10">
      <div class="max-w-[1920px] mx-auto px-6 pt-0 pb-10">

        <!-- Big White Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

          <!-- Stats bar -->
          <div class="px-5 py-3 border-b border-gray-200 flex flex-wrap items-center gap-y-2 text-sm">
            <span class="flex items-center text-xs font-semibold text-gray-400 uppercase tracking-wider mr-3">
              <i class="fas fa-truck mr-1.5"></i>Transfers
            </span>
            <span class="mr-4 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>
              <span class="text-gray-600">{{ stats.pending_transfers || 0 }} Pending</span>
            </span>
            <span class="mr-4 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
              <span class="text-gray-600">{{ stats.in_progress_transfers || 0 }} In Progress</span>
            </span>
            <span class="mr-4 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
              <span class="text-gray-600">{{ stats.completed_transfers || 0 }} Completed</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>
              <span class="text-gray-600">{{ stats.cancelled_transfers || 0 }} Cancelled</span>
            </span>
            <span class="ml-auto text-gray-400 text-xs">{{ filteredTransfers.length }} records</span>
          </div>

          <!-- Filter bar -->
          <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <!-- Status filter -->
            <select v-model="filterStatus"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>

            <!-- Urgency filter -->
            <select v-model="filterUrgency"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">All Urgency</option>
              <option value="CATASTROPHE">CATASTROPHE</option>
              <option value="CRITICAL">CRITICAL</option>
              <option value="MUST_ORDER">MUST ORDER</option>
              <option value="WARNING">WARNING</option>
              <option value="PLANNED">PLANNED</option>
            </select>

            <!-- Station search -->
            <input v-model="filterStation"
              type="text" placeholder="Search station…"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 w-44">

            <!-- Fuel type search -->
            <input v-model="filterFuel"
              type="text" placeholder="Search fuel…"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 w-36">

            <!-- Clear -->
            <button v-if="filterStatus || filterUrgency || filterStation || filterFuel"
              type="button" @click="clearFilters"
              class="text-sm text-gray-500 hover:text-gray-700 px-2 py-1.5 flex items-center gap-1">
              <i class="fas fa-times text-xs"></i> Clear
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="text-center py-16">
            <i class="fas fa-spinner fa-spin text-gray-400 text-3xl"></i>
            <p class="text-sm text-gray-500 mt-3">Loading transfers...</p>
          </div>

          <!-- Empty state -->
          <div v-else-if="filteredTransfers.length === 0" class="text-center py-16">
            <i class="fas fa-truck text-gray-300 text-4xl mb-3"></i>
            <p class="text-sm font-semibold text-gray-500">No transfers found</p>
            <p v-if="filterStatus || filterUrgency || filterStation || filterFuel"
              class="text-xs text-gray-400 mt-1">Try adjusting your filters</p>
          </div>

          <!-- Table -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('id')">
                    # <i class="fas" :class="sortIcon('id')"></i>
                  </th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('from_station_name')">
                    From → To <i class="fas" :class="sortIcon('from_station_name')"></i>
                  </th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('fuel_type_name')">
                    Fuel Type <i class="fas" :class="sortIcon('fuel_type_name')"></i>
                  </th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('transfer_amount_liters')">
                    QTY (L) <i class="fas" :class="sortIcon('transfer_amount_liters')"></i>
                  </th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    QTY (T)
                  </th>
                  <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Urgency
                  </th>
                  <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('status')">
                    Status <i class="fas" :class="sortIcon('status')"></i>
                  </th>
                  <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('estimated_days')">
                    Est. Days <i class="fas" :class="sortIcon('estimated_days')"></i>
                  </th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:text-gray-700 select-none"
                    @click="toggleSort('created_at')">
                    Created <i class="fas" :class="sortIcon('created_at')"></i>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in sortedTransfers" :key="t.id"
                  class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                  :class="{ 'opacity-60': t.status === 'cancelled' }">

                  <!-- ID -->
                  <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ t.id }}</td>

                  <!-- From → To -->
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="font-medium text-gray-800">{{ shortName(t.from_station_name) }}</span>
                      <i class="fas fa-arrow-right text-gray-400 text-xs shrink-0"></i>
                      <span class="font-medium text-gray-800">{{ shortName(t.to_station_name) }}</span>
                    </div>
                    <div v-if="t.notes" class="text-xs text-gray-400 mt-0.5 max-w-[200px] truncate" :title="t.notes">
                      {{ t.notes }}
                    </div>
                  </td>

                  <!-- Fuel Type -->
                  <td class="px-4 py-3">
                    <span class="text-gray-800">{{ t.fuel_type_name }}</span>
                  </td>

                  <!-- QTY (L) -->
                  <td class="px-4 py-3 text-right font-mono text-gray-700">
                    {{ formatNumber(t.transfer_amount_liters) }}
                  </td>

                  <!-- QTY (T) -->
                  <td class="px-4 py-3 text-right font-mono text-gray-700">
                    {{ formatTons(t.transfer_amount_liters, t.density) }}
                  </td>

                  <!-- Urgency -->
                  <td class="px-4 py-3 text-center">
                    <span v-if="t.urgency"
                      class="px-2 py-0.5 rounded text-xs font-bold"
                      :class="urgencyClass(t.urgency)">
                      {{ urgencyShort(t.urgency) }}
                    </span>
                    <span v-else class="text-gray-300 text-xs">—</span>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                      :class="statusClass(t.status)">
                      {{ statusLabel(t.status) }}
                    </span>
                  </td>

                  <!-- Est. Days -->
                  <td class="px-4 py-3 text-center text-gray-600">
                    <span v-if="t.estimated_days">{{ t.estimated_days }}d</span>
                    <span v-else class="text-gray-300">—</span>
                  </td>

                  <!-- Created -->
                  <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                    {{ t.created_at ? t.created_at.substring(0, 10) : '—' }}
                    <div v-if="t.completed_at" class="text-green-600 mt-0.5">
                      ✓ {{ t.completed_at.substring(0, 10) }}
                    </div>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

        </div><!-- end big white card -->
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { transfersApi } from '../services/api';

// ── State ──────────────────────────────────────────────────────────────────────
const loading   = ref(true);
const transfers = ref([]);
const stats     = ref({});

// ── Filters ────────────────────────────────────────────────────────────────────
const filterStatus  = ref('');
const filterUrgency = ref('');
const filterStation = ref('');
const filterFuel    = ref('');

function clearFilters() {
  filterStatus.value  = '';
  filterUrgency.value = '';
  filterStation.value = '';
  filterFuel.value    = '';
}

// ── Sort ───────────────────────────────────────────────────────────────────────
const sort = reactive({ key: 'created_at', dir: 'desc' });

function toggleSort(key) {
  if (sort.key === key) {
    sort.dir = sort.dir === 'asc' ? 'desc' : 'asc';
  } else {
    sort.key = key;
    sort.dir = key === 'created_at' ? 'desc' : 'asc';
  }
}

function sortIcon(key) {
  if (sort.key !== key) return 'fa-sort text-gray-300';
  return sort.dir === 'asc' ? 'fa-sort-up text-blue-500' : 'fa-sort-down text-blue-500';
}

// ── Current datetime ───────────────────────────────────────────────────────────
const currentDateTime = ref('');
function updateTime() {
  const now = new Date();
  currentDateTime.value = now.toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

// ── Filtered + sorted transfers ────────────────────────────────────────────────
const filteredTransfers = computed(() => {
  return transfers.value.filter(t => {
    if (filterStatus.value  && t.status  !== filterStatus.value)  return false;
    if (filterUrgency.value && t.urgency !== filterUrgency.value) return false;
    if (filterStation.value) {
      const q = filterStation.value.toLowerCase();
      if (!(t.from_station_name?.toLowerCase().includes(q) || t.to_station_name?.toLowerCase().includes(q))) return false;
    }
    if (filterFuel.value) {
      const q = filterFuel.value.toLowerCase();
      if (!t.fuel_type_name?.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

const sortedTransfers = computed(() => {
  return [...filteredTransfers.value].sort((a, b) => {
    let av = a[sort.key], bv = b[sort.key];
    if (av == null) av = '';
    if (bv == null) bv = '';
    const n = typeof av === 'number' || !isNaN(Number(av)) ? Number(av) - Number(bv) : String(av).localeCompare(String(bv));
    return sort.dir === 'asc' ? n : -n;
  });
});

// ── Formatters ─────────────────────────────────────────────────────────────────
const shortName = (name) => name?.replace(/^Станция\s+/i, '') ?? name;

function formatNumber(v) {
  if (!v) return '0';
  return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatTons(liters, density) {
  if (!liters || !density) return '—';
  return (Number(liters) * Number(density) / 1000).toFixed(2);
}

// ── Style helpers ──────────────────────────────────────────────────────────────
function urgencyClass(u) {
  return {
    CATASTROPHE: 'bg-red-600 text-white',
    CRITICAL:    'bg-red-500 text-white',
    MUST_ORDER:  'bg-orange-500 text-white',
    WARNING:     'bg-yellow-500 text-white',
    PLANNED:     'bg-blue-500 text-white',
  }[u] || 'bg-gray-200 text-gray-600';
}

function urgencyShort(u) {
  return { CATASTROPHE:'CATASTR', CRITICAL:'CRITICAL', MUST_ORDER:'MUST', WARNING:'WARN', PLANNED:'PLANNED' }[u] || u;
}

function statusClass(s) {
  return {
    pending:     'bg-orange-100 text-orange-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
    cancelled:   'bg-gray-100 text-gray-500',
  }[s] || 'bg-gray-100 text-gray-600';
}

function statusLabel(s) {
  return { pending:'Pending', in_progress:'In Transit', completed:'Completed', cancelled:'Cancelled' }[s] || s;
}

// ── Data loading ───────────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true;
  try {
    const res = await transfersApi.getAll();
    if (res.data.success) {
      transfers.value = res.data.data   || [];
      stats.value     = res.data.stats  || {};
    }
  } catch (e) {
    console.error('Transfers load error:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  updateTime();
  setInterval(updateTime, 60000);
  loadData();
});
</script>
