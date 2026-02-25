<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

    <!-- ── Header ──────────────────────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <div class="flex items-start justify-between mb-1">
        <div>
          <h3 class="text-lg font-bold text-gray-800">
            <i class="fas fa-brain text-indigo-500 mr-2"></i>
            Procurement Advisor
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">AI-powered procurement recommendations</p>
        </div>

        <!-- Next-action chip — visible once data loads -->
        <div v-if="!loading && nextActionChip"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold mt-1 shrink-0"
          :class="nextActionChip.classes">
          <i :class="nextActionChip.icon"></i>
          {{ nextActionChip.label }}
        </div>
      </div>

      <!-- Tabs -->
      <div class="pa-tabs">
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'briefing' }"
          @click="activeTab = 'briefing'">
          <i class="fas fa-bullhorn mr-1"></i>Briefing
        </button>
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'recommendations' }"
          @click="activeTab = 'recommendations'">
          Recommendations
          <span v-if="!loading && recommendations.length"
            class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">
            {{ recommendations.length }}
          </span>
        </button>
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'pricecheck' }"
          @click="activeTab = 'pricecheck'">
          <i class="fas fa-tag mr-1"></i>Price Check
        </button>
      </div>
    </div>

    <!-- ── Tab content ─────────────────────────────────────────────────── -->
    <div class="p-6">

      <!-- ── BRIEFING ──────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'briefing'" class="space-y-4">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading procurement data...</p>
        </div>

        <div v-else>
          <!-- Urgent alert -->
          <div v-if="summary.mandatory_orders > 0"
            class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-500 p-4 rounded-r-lg mb-4">
            <div class="flex items-start gap-3">
              <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-1"></i>
              <div>
                <div class="font-bold text-amber-900 mb-1">Urgent Action Required</div>
                <div class="text-sm text-amber-800">
                  {{ summary.mandatory_orders }} station{{ summary.mandatory_orders > 1 ? 's' : '' }}
                  will reach critical levels soon. Immediate procurement recommended.
                </div>
              </div>
            </div>
          </div>

          <!-- KPI cards -->
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

          <!-- 14-day Timeline -->
          <div v-if="timelineItems.length > 0" class="mt-4 bg-gray-50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                Upcoming Shortages — Next 14 Days
              </span>
              <span class="text-xs text-gray-400">{{ timelineItems.length }} items</span>
            </div>

            <div class="relative" style="height: 52px">
              <!-- Timeline line -->
              <div class="absolute rounded-full bg-gray-200"
                style="top: 14px; left: 28px; right: 28px; height: 2px;"></div>

              <!-- Dot for each shortage -->
              <div
                v-for="(item, idx) in timelineItems"
                :key="idx"
                class="absolute group cursor-default"
                :style="{ left: `calc(28px + ${item.pct} * (100% - 56px))`, top: '6px' }">
                <div class="w-5 h-5 rounded-full border-2 border-white shadow-sm flex items-center justify-center"
                  :class="item.dotClass">
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 pointer-events-none
                            hidden group-hover:block z-20">
                  <div class="bg-gray-900 text-white text-xs rounded-lg px-2.5 py-2 shadow-xl whitespace-nowrap">
                    <div class="font-semibold">{{ item.stationShort }} · {{ item.fuel }}</div>
                    <div class="text-gray-300 mt-0.5">{{ item.days.toFixed(1) }}d left</div>
                    <div class="text-gray-400">{{ item.urgency }}</div>
                  </div>
                </div>
              </div>

              <!-- Labels: TODAY and 14d anchored to ends -->
              <div class="absolute text-xs text-gray-400 font-medium" style="top: 34px; left: 0">TODAY</div>
              <div class="absolute text-xs text-gray-400 font-medium" style="top: 34px; right: 0">14d</div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-3 mt-1 flex-wrap">
              <div v-for="lv in urgencyLevels" :key="lv.key"
                v-if="timelineItems.some(t => t.urgency === lv.key)"
                class="flex items-center gap-1 text-xs">
                <div class="w-3 h-3 rounded-full" :class="lv.dotClass"></div>
                <span class="text-gray-500">{{ lv.shortLabel }}</span>
              </div>
            </div>
          </div>

          <div v-else-if="!loading" class="mt-4 bg-gray-50 rounded-xl p-4 text-center">
            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            <p class="text-xs text-gray-500 mt-2">No shortages in the next 14 days</p>
          </div>
        </div>
      </div>

      <!-- ── RECOMMENDATIONS ───────────────────────────────────────────── -->
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

          <!-- Urgency Distribution Bar -->
          <div class="mb-4 bg-gray-50 rounded-xl p-3">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wide mr-1">Urgency</span>
              <span v-for="lv in urgencyLevels" :key="lv.key"
                v-if="urgencyCounts[lv.key] > 0"
                class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                :class="lv.chipClass">
                {{ urgencyCounts[lv.key] }} {{ lv.shortLabel }}
              </span>
            </div>
            <div class="flex rounded-full overflow-hidden h-2.5 gap-px">
              <div v-for="lv in urgencyLevels" :key="lv.key"
                v-if="urgencyCounts[lv.key] > 0"
                :class="lv.bgClass"
                :style="{ flex: urgencyCounts[lv.key] }"
                class="transition-all duration-500 first:rounded-l-full last:rounded-r-full">
              </div>
            </div>
          </div>

          <!-- Cards -->
          <div
            v-for="rec in recommendations"
            :key="rec.id"
            class="border rounded-xl p-4 hover:shadow-md transition-shadow"
            :class="getBorderClass(rec.urgency)">

            <!-- Row 1: Name + Urgency badge -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <div class="font-semibold text-gray-800 text-sm leading-tight">{{ rec.station_name }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ rec.depot_name }} · {{ rec.fuel_type }}</div>
              </div>
              <span class="px-2 py-1 rounded-lg text-xs font-bold shrink-0 ml-2"
                :class="getUrgencyClass(rec.urgency)">
                {{ urgencyLabel(rec.urgency) }}
              </span>
            </div>

            <!-- Stock Level Bar -->
            <div class="mb-3">
              <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-gray-500 font-medium">Stock Level</span>
                <span class="font-bold" :class="getDaysTextClass(rec.urgency)">
                  {{ formatTons(rec.current_stock_tons) }}
                  <span class="text-gray-400 font-normal ml-1">({{ rec.fill_percentage }}%)</span>
                </span>
              </div>
              <div class="relative h-4 rounded-full bg-gray-100 overflow-hidden">
                <!-- Background zone: 0 → critical (red tint) -->
                <div class="absolute inset-y-0 left-0 bg-red-100"
                  :style="{ width: rec.thresholds.critical + '%' }"></div>
                <!-- Background zone: critical → min (orange tint) -->
                <div class="absolute inset-y-0 bg-orange-50"
                  :style="{ left: rec.thresholds.critical + '%', width: (rec.thresholds.min - rec.thresholds.critical) + '%' }"></div>
                <!-- Background zone: min → target (yellow tint) -->
                <div class="absolute inset-y-0 bg-yellow-50"
                  :style="{ left: rec.thresholds.min + '%', width: (rec.thresholds.target - rec.thresholds.min) + '%' }"></div>
                <!-- Background zone: target → max (green tint) -->
                <div class="absolute inset-y-0 bg-green-50"
                  :style="{ left: rec.thresholds.target + '%', width: (rec.thresholds.max - rec.thresholds.target) + '%' }"></div>

                <!-- Current fill (solid bar) -->
                <div class="absolute inset-y-0 left-0 rounded-full"
                  :class="getFillBarClass(rec.urgency)"
                  :style="{ width: Math.min(rec.fill_percentage, 100) + '%' }">
                </div>

                <!-- Zone boundary lines -->
                <div class="absolute inset-y-0 w-px bg-red-400 opacity-80"
                  :style="{ left: rec.thresholds.critical + '%' }"></div>
                <div class="absolute inset-y-0 w-px bg-orange-400 opacity-80"
                  :style="{ left: rec.thresholds.min + '%' }"></div>
                <div class="absolute inset-y-0 w-px bg-green-600 opacity-80"
                  :style="{ left: rec.thresholds.target + '%' }"></div>
              </div>
              <!-- Zone labels below bar -->
              <div class="relative mt-1" style="height: 14px">
                <span class="absolute text-xs text-red-400 -translate-x-1/2"
                  :style="{ left: rec.thresholds.critical + '%' }">
                  {{ rec.thresholds.critical }}%
                </span>
                <span class="absolute text-xs text-orange-400 -translate-x-1/2"
                  :style="{ left: rec.thresholds.min + '%' }">
                  {{ rec.thresholds.min }}%
                </span>
                <span class="absolute text-xs text-green-600 -translate-x-1/2"
                  :style="{ left: rec.thresholds.target + '%' }">
                  {{ rec.thresholds.target }}%
                </span>
              </div>
            </div>

            <!-- Row 3: Days Left hero + Order info -->
            <div class="flex items-center gap-3 mb-3 mt-4">
              <!-- Days Left: hero box -->
              <div class="shrink-0 text-center px-4 py-2 rounded-xl border-2"
                :class="getDaysBoxClass(rec.urgency)">
                <div class="text-3xl font-black leading-none tabular-nums"
                  :class="getDaysTextClass(rec.urgency)">
                  {{ Math.round(rec.days_left) }}
                </div>
                <div class="text-xs font-semibold mt-0.5" :class="getDaysTextClass(rec.urgency)">
                  days
                </div>
              </div>
              <!-- Info grid -->
              <div class="flex-1 grid grid-cols-2 gap-2 text-xs">
                <div>
                  <div class="text-gray-400 mb-0.5">Order by</div>
                  <div class="font-bold text-orange-600">{{ rec.last_order_date }}</div>
                  <div class="text-gray-400 mt-1">Critical date</div>
                  <div class="text-gray-600 font-medium">{{ rec.critical_date }}</div>
                </div>
                <div>
                  <div class="text-gray-400 mb-0.5">Recommended qty</div>
                  <div class="font-bold text-emerald-700 text-sm">{{ formatTons(rec.recommended_order_tons) }}</div>
                </div>
              </div>
            </div>

            <!-- Best Supplier -->
            <div v-if="rec.best_supplier"
              class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 text-xs mb-3 border border-gray-100">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-industry text-gray-400"></i>
                <span class="font-semibold text-gray-700">{{ rec.best_supplier.name }}</span>
              </div>
              <span class="text-gray-500 flex items-center gap-1">
                <i class="fas fa-truck text-blue-400"></i>
                {{ rec.best_supplier.avg_delivery_days }}d delivery
              </span>
            </div>

            <!-- PO Pending banner -->
            <div v-if="rec.po_pending && rec.active_po"
              class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-3 text-xs flex items-start gap-2">
              <i class="fas fa-clipboard-check text-blue-500 mt-0.5 shrink-0"></i>
              <div>
                <div class="font-semibold text-blue-800">PO Issued — Awaiting ERP Confirmation</div>
                <div class="text-blue-600 mt-0.5">
                  {{ rec.active_po.order_number }} ·
                  {{ rec.active_po.quantity_tons }} t ·
                  Delivery: {{ rec.active_po.delivery_date }}
                </div>
              </div>
            </div>

            <!-- Action button -->
            <button
              v-if="rec.po_pending"
              type="button"
              @click="router.push('/orders')"
              class="w-full bg-blue-100 text-blue-700 py-2 px-4 rounded-lg text-sm font-semibold
                     hover:bg-blue-200 transition-all border border-blue-200">
              <i class="fas fa-external-link-alt mr-1"></i>
              View Purchase Orders
            </button>
            <button
              v-else
              type="button"
              @click="createOrder(rec)"
              class="w-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-2 px-4
                     rounded-lg text-sm font-semibold hover:from-blue-600 hover:to-indigo-600 transition-all">
              <i class="fas fa-plus mr-1"></i>
              Create Order
            </button>

          </div>
        </div>
      </div>

      <!-- ── PRICE CHECK ────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'pricecheck'" class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="text-sm font-semibold text-gray-700 mb-3">Current Market Prices</div>
          <div class="space-y-2">
            <div v-for="price in marketPrices" :key="price.fuel"
              class="flex items-center justify-between">
              <span class="text-sm text-gray-700">{{ price.fuel }}</span>
              <div class="flex items-center gap-2">
                <span class="font-bold text-gray-900">{{ price.price }}</span>
                <span class="text-xs px-2 py-0.5 rounded"
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
              <span class="font-bold">Price Alert:</span>
              Diesel prices expected to increase by 3% next week. Consider early procurement.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { procurementApi } from '../services/api';

const router = useRouter();

const activeTab = ref('briefing');
const loading   = ref(true);
const shortages = ref([]);
const summary   = ref({
  total_shortages:     0,
  mandatory_orders:    0,
  recommended_orders:  0,
  avg_lead_time_days:  0,
  total_value_estimate: 0,
});

// ── Static price check data (market feed TBD) ────────────────────────────────
const marketPrices = [
  { fuel: 'Diesel B7',  price: '€1.42/L', trend: 'up',   change: '2.1%' },
  { fuel: 'Petrol 95',  price: '€1.58/L', trend: 'down',  change: '0.8%' },
  { fuel: 'Petrol 98',  price: '€1.72/L', trend: 'up',   change: '1.2%' },
];

// ── Urgency level definitions ─────────────────────────────────────────────────
const urgencyLevels = [
  { key: 'CATASTROPHE', shortLabel: 'CATASTR', bgClass: 'bg-red-600',    dotClass: 'bg-red-600',    chipClass: 'bg-red-100 text-red-700' },
  { key: 'CRITICAL',    shortLabel: 'CRITICAL', bgClass: 'bg-red-400',   dotClass: 'bg-red-400',    chipClass: 'bg-red-50  text-red-600' },
  { key: 'MUST_ORDER',  shortLabel: 'MUST',     bgClass: 'bg-orange-500',dotClass: 'bg-orange-500', chipClass: 'bg-orange-100 text-orange-700' },
  { key: 'WARNING',     shortLabel: 'WARN',     bgClass: 'bg-yellow-400',dotClass: 'bg-yellow-400', chipClass: 'bg-yellow-100 text-yellow-700' },
  { key: 'PLANNED',     shortLabel: 'PLANNED',  bgClass: 'bg-blue-400',  dotClass: 'bg-blue-400',   chipClass: 'bg-blue-100 text-blue-600'  },
];

// ── Computed: transform shortages for the Recommendations tab ────────────────
const recommendations = computed(() =>
  shortages.value.map(s => ({
    id:                    s.depot_id + '_' + s.fuel_type_id,
    station_id:            s.station_id,
    station_name:          s.station_name,
    depot_name:            s.depot_name,
    fuel_type:             s.fuel_type_name,
    fuel_type_code:        s.fuel_type_code,
    urgency:               s.urgency,
    days_left:             s.days_left,
    critical_date:         s.critical_date,
    last_order_date:       s.last_order_date,
    current_stock_tons:    s.current_stock_tons,
    fill_percentage:       s.fill_percentage,
    recommended_order_tons: s.recommended_order_tons,
    best_supplier:         s.best_supplier,
    // Threshold percentages (from API; fallback to global defaults)
    thresholds:            s.thresholds_pct || { critical: 20, min: 40, target: 80, max: 95 },
    po_pending:            s.po_pending  || false,
    active_po:             s.active_po   || null,
  }))
);

// ── Computed: urgency counts for the distribution bar ────────────────────────
const urgencyCounts = computed(() => {
  const counts = {};
  for (const lv of urgencyLevels) counts[lv.key] = 0;
  for (const rec of recommendations.value) {
    if (counts[rec.urgency] !== undefined) counts[rec.urgency]++;
  }
  return counts;
});

// ── Computed: "Next action" header chip ──────────────────────────────────────
const nextActionChip = computed(() => {
  if (!shortages.value.length) return null;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  let earliest = null;
  for (const s of shortages.value) {
    if (!s.last_order_date) continue;
    if (!earliest || new Date(s.last_order_date) < new Date(earliest.last_order_date)) {
      earliest = s;
    }
  }
  if (!earliest) return null;

  const diff = Math.round((new Date(earliest.last_order_date) - today) / 86400000);

  if (diff <= 0)  return { label: 'Order needed: TODAY',             icon: 'fas fa-fire',                 classes: 'bg-red-100 text-red-700 border border-red-300' };
  if (diff <= 2)  return { label: `Order by: ${earliest.last_order_date}`, icon: 'fas fa-exclamation-triangle', classes: 'bg-orange-100 text-orange-700 border border-orange-300' };
  if (diff <= 7)  return { label: `Next: ${earliest.last_order_date}`,     icon: 'fas fa-clock',                classes: 'bg-yellow-100 text-yellow-700 border border-yellow-200' };
  return              { label: `Next: ${earliest.last_order_date}`,         icon: 'fas fa-calendar-alt',         classes: 'bg-gray-100 text-gray-600 border border-gray-200' };
});

// ── Computed: 14-day timeline items ─────────────────────────────────────────
const timelineItems = computed(() => {
  const byUrgencyDot = {
    CATASTROPHE: 'bg-red-600',
    CRITICAL:    'bg-red-400',
    MUST_ORDER:  'bg-orange-500',
    WARNING:     'bg-yellow-400',
    PLANNED:     'bg-blue-400',
  };
  return shortages.value
    .filter(s => s.days_left >= 0 && s.days_left <= 14)
    .sort((a, b) => a.days_left - b.days_left)
    .map(s => ({
      days:         s.days_left,
      pct:          Math.min(s.days_left / 14, 1),  // 0–1 for CSS calc
      stationShort: s.station_name.replace(/^Станция\s*/i, ''),
      fuel:         s.fuel_type_code || s.fuel_type_name,
      urgency:      s.urgency,
      dotClass:     byUrgencyDot[s.urgency] || 'bg-gray-400',
    }));
});

// ── Formatters ───────────────────────────────────────────────────────────────
const formatCurrency = (value) => {
  if (!value) return '$0';
  if (value >= 1_000_000) return `$${(value / 1_000_000).toFixed(1)}M`;
  if (value >= 1_000)     return `$${(value / 1_000).toFixed(0)}K`;
  return `$${value.toFixed(0)}`;
};

const formatTons = (value) => {
  if (!value) return '0 t';
  if (value >= 1_000) return `${(value / 1_000).toFixed(1)}K t`;
  return `${value.toFixed(1)} t`;
};

// ── Style helpers ────────────────────────────────────────────────────────────
const urgencyLabel = (urgency) => ({
  CATASTROPHE: '🔴 CATASTROPHE',
  CRITICAL:    '🔴 CRITICAL',
  MUST_ORDER:  '🟠 MUST ORDER',
  WARNING:     '🟡 WARNING',
  PLANNED:     '🔵 PLANNED',
}[urgency] || urgency);

const getUrgencyClass = (urgency) => ({
  CATASTROPHE: 'bg-red-600 text-white',
  CRITICAL:    'bg-red-500 text-white',
  MUST_ORDER:  'bg-orange-500 text-white',
  WARNING:     'bg-yellow-500 text-white',
  PLANNED:     'bg-blue-500 text-white',
}[urgency] || 'bg-gray-500 text-white');

const getBorderClass = (urgency) => ({
  CATASTROPHE: 'border-red-500 bg-red-50',
  CRITICAL:    'border-red-400 bg-red-50',
  MUST_ORDER:  'border-orange-400 bg-orange-50',
  WARNING:     'border-yellow-400 bg-yellow-50',
  PLANNED:     'border-blue-300 bg-blue-50',
}[urgency] || 'border-gray-200');

const getFillBarClass = (urgency) => ({
  CATASTROPHE: 'bg-red-500',
  CRITICAL:    'bg-red-400',
  MUST_ORDER:  'bg-orange-500',
  WARNING:     'bg-yellow-400',
  PLANNED:     'bg-blue-400',
}[urgency] || 'bg-gray-400');

const getDaysBoxClass = (urgency) => ({
  CATASTROPHE: 'bg-red-100 border-red-300',
  CRITICAL:    'bg-red-50  border-red-200',
  MUST_ORDER:  'bg-orange-50 border-orange-300',
  WARNING:     'bg-yellow-50 border-yellow-300',
  PLANNED:     'bg-blue-50  border-blue-200',
}[urgency] || 'bg-gray-50 border-gray-200');

const getDaysTextClass = (urgency) => ({
  CATASTROPHE: 'text-red-700',
  CRITICAL:    'text-red-600',
  MUST_ORDER:  'text-orange-600',
  WARNING:     'text-yellow-700',
  PLANNED:     'text-blue-600',
}[urgency] || 'text-gray-700');

// ── Navigate to Orders with pre-fill ─────────────────────────────────────────
function createOrder(rec) {
  router.push({
    path:  '/orders',
    query: {
      action:       'create_po',
      station_id:   rec.station_id,
      fuel_type_id: rec.fuel_type_id,
      quantity_tons: rec.recommended_order_tons,
      supplier_id:  rec.best_supplier?.id || '',
      delivery_date: rec.last_order_date || '',
    },
  });
}

// ── Data loading ─────────────────────────────────────────────────────────────
const loadData = async () => {
  try {
    loading.value = true;
    const [summaryResp, shortagesResp] = await Promise.all([
      procurementApi.getSummary(),
      procurementApi.getUpcomingShortages(14),
    ]);
    if (summaryResp.data.success)   summary.value   = summaryResp.data.data;
    if (shortagesResp.data.success) shortages.value = shortagesResp.data.data;
  } catch (error) {
    console.error('Error loading procurement data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
/* ── Tabs ──────────────────────────────────────────────────────────────────── */
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
  display: flex;
  align-items: center;
}

.pa-tab:hover:not(.active) { background: #c7d2fe; }

.pa-tab.active {
  background: #fff;
  color: #0f172a;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  border-bottom: 1px solid #fff;
}
</style>
