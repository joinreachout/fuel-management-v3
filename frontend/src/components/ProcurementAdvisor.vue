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

        <!-- Next-action chip -->
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
                  {{ summary.mandatory_orders }} position{{ summary.mandatory_orders > 1 ? 's' : '' }}
                  will reach critical levels soon. Immediate procurement recommended.
                </div>
              </div>
            </div>
          </div>

          <!-- KPI cards — clickable, switch to Recommendations tab -->
          <div class="grid grid-cols-2 gap-3">
            <div
              @click="activeTab = 'recommendations'"
              class="bg-red-50 p-3 rounded-lg cursor-pointer hover:bg-red-100 transition-colors group">
              <div class="text-xs text-red-600 font-semibold mb-1 flex items-center justify-between">
                Mandatory Orders
                <i class="fas fa-arrow-right text-red-300 group-hover:text-red-500 transition-colors text-xs"></i>
              </div>
              <div class="text-2xl font-bold text-red-900">{{ summary.mandatory_orders || 0 }}</div>
              <div class="text-xs text-red-500 mt-1">CATASTROPHE + CRITICAL</div>
            </div>
            <div
              @click="activeTab = 'recommendations'"
              class="bg-orange-50 p-3 rounded-lg cursor-pointer hover:bg-orange-100 transition-colors group">
              <div class="text-xs text-orange-600 font-semibold mb-1 flex items-center justify-between">
                Recommended Orders
                <i class="fas fa-arrow-right text-orange-300 group-hover:text-orange-500 transition-colors text-xs"></i>
              </div>
              <div class="text-2xl font-bold text-orange-900">{{ summary.recommended_orders || 0 }}</div>
              <div class="text-xs text-orange-500 mt-1">MUST ORDER + WARNING</div>
            </div>
            <div class="bg-purple-50 p-3 rounded-lg">
              <div class="text-xs text-purple-600 font-semibold mb-1">Avg Lead Time</div>
              <div class="text-2xl font-bold text-purple-900">{{ summary.avg_lead_time_days || 0 }} days</div>
              <div class="text-xs text-purple-500 mt-1">Based on suppliers</div>
            </div>
            <div class="bg-emerald-50 p-3 rounded-lg">
              <div class="text-xs text-emerald-600 font-semibold mb-1">Total Value</div>
              <div class="text-2xl font-bold text-emerald-900">{{ formatCurrency(summary.total_value_estimate) }}</div>
              <div class="text-xs text-emerald-500 mt-1">Recommended orders</div>
            </div>
          </div>

          <!-- 14-day Timeline -->
          <div v-if="timelineItems.length > 0" class="mt-4 bg-gray-50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                Upcoming — Next 14 Days
              </span>
              <span class="text-xs text-gray-400">{{ timelineItems.length }} items</span>
            </div>

            <div class="relative" style="height: 52px">
              <div class="absolute rounded-full bg-gray-200"
                style="top: 14px; left: 28px; right: 28px; height: 2px;"></div>

              <div
                v-for="(item, idx) in timelineItems" :key="idx"
                class="absolute group cursor-default"
                :style="{ left: `calc(28px + ${item.pct} * (100% - 56px))`, top: '6px' }">
                <div class="w-5 h-5 rounded-full border-2 border-white shadow-sm"
                  :class="item.dotClass"></div>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 pointer-events-none
                            hidden group-hover:block z-20">
                  <div class="bg-gray-900 text-white text-xs rounded-lg px-2.5 py-2 shadow-xl whitespace-nowrap">
                    <div class="font-semibold">{{ item.stationShort }} · {{ item.fuel }}</div>
                    <div class="text-gray-300 mt-0.5">{{ item.days.toFixed(1) }}d to critical</div>
                    <div class="text-gray-400">{{ item.urgency }}</div>
                  </div>
                </div>
              </div>

              <div class="absolute text-xs text-gray-400 font-medium" style="top: 34px; left: 0">TODAY</div>
              <div class="absolute text-xs text-gray-400 font-medium" style="top: 34px; right: 0">14d</div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-3 mt-1 flex-wrap">
              <template v-for="lv in urgencyLevels" :key="lv.key">
                <div v-if="timelineItems.some(t => t.urgency === lv.key)"
                  class="flex items-center gap-1 text-xs">
                  <div class="w-3 h-3 rounded-full" :class="lv.dotClass"></div>
                  <span class="text-gray-500">{{ lv.shortLabel }}</span>
                </div>
              </template>
            </div>
          </div>

          <div v-else-if="!loading" class="mt-4 bg-gray-50 rounded-xl p-4 text-center">
            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            <p class="text-xs text-gray-500 mt-2">No shortages in the next 14 days</p>
          </div>
        </div>
      </div>

      <!-- ── RECOMMENDATIONS ───────────────────────────────────────────── -->
      <div v-if="activeTab === 'recommendations'">
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
              <template v-for="lv in urgencyLevels" :key="lv.key">
                <span v-if="urgencyCounts[lv.key] > 0"
                  class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                  :class="lv.chipClass">
                  {{ urgencyCounts[lv.key] }} {{ lv.shortLabel }}
                </span>
              </template>
            </div>
            <div class="flex rounded-full overflow-hidden h-2 gap-px">
              <template v-for="lv in urgencyLevels" :key="lv.key">
                <div v-if="urgencyCounts[lv.key] > 0"
                  :class="lv.bgClass"
                  :style="{ flex: urgencyCounts[lv.key] }"
                  class="transition-all duration-500">
                </div>
              </template>
            </div>
          </div>

          <!-- 3-column compact card grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <div
              v-for="rec in recommendations"
              :key="rec.id"
              class="border-2 rounded-xl p-3 flex flex-col gap-2 bg-white"
              :class="getBorderClass(rec.urgency)">

              <!-- Row 1: Station + urgency badge -->
              <div class="flex items-start justify-between gap-1">
                <div class="min-w-0">
                  <div class="font-bold text-gray-800 text-sm leading-tight truncate">
                    {{ shortName(rec.station_name) }}
                  </div>
                  <div class="text-xs text-gray-500 truncate">{{ rec.depot_name }} · {{ rec.fuel_type }}</div>
                </div>
                <span class="shrink-0 px-1.5 py-0.5 rounded-md text-xs font-bold"
                  :class="getUrgencyClass(rec.urgency)">
                  {{ urgencyShortLabel(rec.urgency) }}
                </span>
              </div>

              <!-- Row 2: Compact stock bar -->
              <div>
                <div class="flex justify-between text-xs mb-1">
                  <span class="text-gray-500">{{ formatTons(rec.current_stock_tons) }}</span>
                  <span class="font-semibold" :class="getDaysTextClass(rec.urgency)">
                    {{ rec.fill_percentage }}%
                  </span>
                </div>
                <div class="relative h-2.5 rounded-full bg-gray-100 overflow-hidden">
                  <!-- Zone backgrounds -->
                  <div class="absolute inset-y-0 left-0 bg-red-100"
                    :style="{ width: rec.thresholds.critical + '%' }"></div>
                  <div class="absolute inset-y-0 bg-orange-50"
                    :style="{ left: rec.thresholds.critical + '%', width: (rec.thresholds.min - rec.thresholds.critical) + '%' }"></div>
                  <div class="absolute inset-y-0 bg-yellow-50"
                    :style="{ left: rec.thresholds.min + '%', width: (rec.thresholds.target - rec.thresholds.min) + '%' }"></div>
                  <div class="absolute inset-y-0 bg-green-50"
                    :style="{ left: rec.thresholds.target + '%', width: (rec.thresholds.max - rec.thresholds.target) + '%' }"></div>
                  <!-- Current fill -->
                  <div class="absolute inset-y-0 left-0 rounded-full"
                    :class="getFillBarClass(rec.urgency)"
                    :style="{ width: Math.min(rec.fill_percentage, 100) + '%' }"></div>
                  <!-- Threshold markers -->
                  <div class="absolute inset-y-0 w-px bg-red-400 opacity-70"
                    :style="{ left: rec.thresholds.critical + '%' }"></div>
                  <div class="absolute inset-y-0 w-px bg-orange-400 opacity-70"
                    :style="{ left: rec.thresholds.min + '%' }"></div>
                  <div class="absolute inset-y-0 w-px bg-green-500 opacity-70"
                    :style="{ left: rec.thresholds.target + '%' }"></div>
                </div>
                <!-- Threshold labels: only CRIT and TGT to save space -->
                <div class="relative mt-0.5" style="height: 12px">
                  <span class="absolute text-xs text-red-400 -translate-x-1/2"
                    style="font-size: 10px"
                    :style="{ left: rec.thresholds.critical + '%' }">
                    {{ rec.thresholds.critical }}%
                  </span>
                  <span class="absolute text-xs text-green-600 -translate-x-1/2"
                    style="font-size: 10px"
                    :style="{ left: rec.thresholds.target + '%' }">
                    {{ rec.thresholds.target }}%
                  </span>
                </div>
              </div>

              <!-- Row 3: Days-to-critical hero + key info -->
              <div class="flex gap-2 items-stretch">
                <!-- Hero: days until critical level -->
                <div class="shrink-0 flex flex-col items-center justify-center px-3 py-2
                            rounded-xl border-2 text-center min-w-[60px]"
                  :class="getDaysBoxClass(rec.urgency)">
                  <div class="text-2xl font-black leading-none tabular-nums"
                    :class="getDaysTextClass(rec.urgency)">
                    {{ rec.days_until_critical_level === 0 ? '!' : Math.round(rec.days_until_critical_level) }}
                  </div>
                  <div class="leading-tight mt-0.5" style="font-size: 10px; font-weight: 600"
                    :class="getDaysTextClass(rec.urgency)">
                    {{ rec.days_until_critical_level === 0 ? 'CRITICAL' : 'to crit.' }}
                  </div>
                </div>

                <!-- Key metrics grid -->
                <div class="flex-1 grid grid-cols-2 gap-1 text-xs">
                  <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                    <div class="text-gray-400 leading-tight">Order by</div>
                    <div class="font-bold text-orange-600 mt-0.5">{{ rec.last_order_date }}</div>
                  </div>
                  <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                    <div class="text-gray-400 leading-tight">Recommend</div>
                    <div class="font-bold text-emerald-700 mt-0.5">{{ formatTons(rec.recommended_order_tons) }}</div>
                  </div>
                  <div class="bg-gray-50 rounded-lg px-2 py-1.5 col-span-2">
                    <div class="text-gray-400 leading-tight">Critical level date</div>
                    <div class="font-medium text-gray-700 mt-0.5">{{ rec.critical_level_date }}</div>
                  </div>
                </div>
              </div>

              <!-- Row 4: Best supplier -->
              <div v-if="rec.best_supplier"
                class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-2 py-1.5">
                <span class="font-medium text-gray-700 truncate">{{ rec.best_supplier.name }}</span>
                <span class="text-gray-500 shrink-0 ml-1">
                  <i class="fas fa-truck text-blue-400 mr-0.5"></i>{{ rec.best_supplier.avg_delivery_days }}d
                </span>
              </div>

              <!-- Row 5: PO Pending banner (compact) with inline remove -->
              <div v-if="rec.po_pending && rec.active_po"
                class="bg-blue-50 border border-blue-200 rounded-lg px-2 py-1.5 text-xs">
                <!-- PO info row -->
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-clipboard-check text-blue-500 shrink-0"></i>
                  <span class="font-semibold text-blue-800">{{ rec.active_po.order_number }}</span>
                  <span class="text-blue-600 flex-1 truncate">· {{ rec.active_po.quantity_tons }}t · {{ rec.active_po.delivery_date }}</span>
                  <!-- 2-step remove: first click shows confirm, second click deletes -->
                  <template v-if="cancelingId === rec.active_po.id">
                    <button
                      type="button"
                      @click="confirmRemovePO(rec.active_po.id)"
                      :disabled="cancelLoading"
                      class="shrink-0 px-1.5 py-0.5 bg-red-500 text-white rounded font-bold
                             hover:bg-red-600 transition-colors disabled:opacity-50">
                      <i v-if="cancelLoading" class="fas fa-spinner fa-spin"></i>
                      <span v-else>Delete?</span>
                    </button>
                    <button
                      type="button"
                      @click="cancelingId = null"
                      class="shrink-0 px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded hover:bg-gray-300 transition-colors">
                      No
                    </button>
                  </template>
                  <button v-else
                    type="button"
                    @click="cancelingId = rec.active_po.id"
                    class="shrink-0 px-1.5 py-0.5 rounded text-red-400 hover:bg-red-100
                           hover:text-red-600 transition-colors"
                    title="Remove this planned PO and recalculate">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <!-- Confirm hint text -->
                <div v-if="cancelingId === rec.active_po.id"
                  class="mt-1 text-red-600 font-medium" style="font-size: 10px">
                  Remove PO? System will recalculate and re-offer this recommendation.
                </div>
              </div>

              <!-- Row 6: Action button (pushed to bottom via mt-auto) -->
              <button
                v-if="rec.po_pending"
                type="button"
                @click="router.push('/orders')"
                class="mt-auto w-full py-2 text-xs font-semibold rounded-lg
                       bg-blue-100 text-blue-700 hover:bg-blue-200 transition-all border border-blue-200">
                <i class="fas fa-external-link-alt mr-1"></i>View Purchase Orders
              </button>
              <button
                v-else
                type="button"
                @click="createOrder(rec)"
                class="mt-auto w-full py-2 text-sm font-bold rounded-lg
                       bg-gradient-to-r from-blue-600 to-indigo-600 text-white
                       hover:from-blue-700 hover:to-indigo-700 transition-all">
                <i class="fas fa-plus mr-1"></i>Create Purchase Order
              </button>

            </div>
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
import { procurementApi, ordersApi } from '../services/api';

const router = useRouter();

const activeTab    = ref('briefing');
const loading      = ref(true);
const shortages    = ref([]);
const summary      = ref({
  total_shortages:      0,
  mandatory_orders:     0,
  recommended_orders:   0,
  avg_lead_time_days:   0,
  total_value_estimate: 0,
});

// PO inline remove — two-step confirm: first click sets cancelingId, second confirms delete
const cancelingId  = ref(null);
const cancelLoading = ref(false);

const marketPrices = [
  { fuel: 'Diesel B7',  price: '€1.42/L', trend: 'up',   change: '2.1%' },
  { fuel: 'Petrol 95',  price: '€1.58/L', trend: 'down', change: '0.8%' },
  { fuel: 'Petrol 98',  price: '€1.72/L', trend: 'up',   change: '1.2%' },
];

const urgencyLevels = [
  { key: 'CATASTROPHE', shortLabel: 'CATASTR', bgClass: 'bg-red-600',     dotClass: 'bg-red-600',    chipClass: 'bg-red-100 text-red-700'      },
  { key: 'CRITICAL',    shortLabel: 'CRITICAL', bgClass: 'bg-red-400',    dotClass: 'bg-red-400',    chipClass: 'bg-red-50  text-red-600'      },
  { key: 'MUST_ORDER',  shortLabel: 'MUST',     bgClass: 'bg-orange-500', dotClass: 'bg-orange-500', chipClass: 'bg-orange-100 text-orange-700' },
  { key: 'WARNING',     shortLabel: 'WARN',     bgClass: 'bg-yellow-400', dotClass: 'bg-yellow-400', chipClass: 'bg-yellow-100 text-yellow-700' },
  { key: 'PLANNED',     shortLabel: 'PLANNED',  bgClass: 'bg-blue-400',   dotClass: 'bg-blue-400',   chipClass: 'bg-blue-100 text-blue-600'    },
];

// ── Computed: recommendations ─────────────────────────────────────────────────
const recommendations = computed(() =>
  shortages.value.map(s => ({
    id:                       s.depot_id + '_' + s.fuel_type_id,
    station_id:               s.station_id,
    station_name:             s.station_name,
    depot_name:               s.depot_name,
    fuel_type:                s.fuel_type_name,
    fuel_type_code:           s.fuel_type_code,
    urgency:                  s.urgency,
    days_left:                s.days_left,
    // Key new field: days until stock falls below critical threshold (e.g. 20%)
    days_until_critical_level: s.days_until_critical_level ?? s.days_left,
    critical_level_date:      s.critical_level_date || s.critical_date,
    critical_date:            s.critical_date,
    last_order_date:          s.last_order_date,
    current_stock_tons:       s.current_stock_tons,
    fill_percentage:          s.fill_percentage,
    recommended_order_tons:   s.recommended_order_tons,
    best_supplier:            s.best_supplier,
    thresholds:               s.thresholds_pct || { critical: 20, min: 40, target: 80, max: 95 },
    po_pending:               s.po_pending  || false,
    active_po:                s.active_po   || null,
  }))
);

// ── Computed: urgency count per level ─────────────────────────────────────────
const urgencyCounts = computed(() => {
  const counts = {};
  for (const lv of urgencyLevels) counts[lv.key] = 0;
  for (const rec of recommendations.value) {
    if (counts[rec.urgency] !== undefined) counts[rec.urgency]++;
  }
  return counts;
});

// ── Computed: "next action" header chip ──────────────────────────────────────
const nextActionChip = computed(() => {
  if (!shortages.value.length) return null;
  const today = new Date(); today.setHours(0, 0, 0, 0);
  let earliest = null;
  for (const s of shortages.value) {
    if (!s.last_order_date) continue;
    if (!earliest || new Date(s.last_order_date) < new Date(earliest.last_order_date)) earliest = s;
  }
  if (!earliest) return null;
  const diff = Math.round((new Date(earliest.last_order_date) - today) / 86400000);
  if (diff <= 0) return { label: 'Order needed: TODAY',              icon: 'fas fa-fire',                 classes: 'bg-red-100 text-red-700 border border-red-300'       };
  if (diff <= 2) return { label: `Order by: ${earliest.last_order_date}`, icon: 'fas fa-exclamation-triangle', classes: 'bg-orange-100 text-orange-700 border border-orange-300' };
  if (diff <= 7) return { label: `Next: ${earliest.last_order_date}`,     icon: 'fas fa-clock',                classes: 'bg-yellow-100 text-yellow-700 border border-yellow-200' };
  return               { label: `Next: ${earliest.last_order_date}`,      icon: 'fas fa-calendar-alt',         classes: 'bg-gray-100 text-gray-600 border border-gray-200'       };
});

// ── Computed: 14-day timeline ─────────────────────────────────────────────────
const timelineItems = computed(() => {
  const dotByUrgency = { CATASTROPHE:'bg-red-600', CRITICAL:'bg-red-400', MUST_ORDER:'bg-orange-500', WARNING:'bg-yellow-400', PLANNED:'bg-blue-400' };
  return shortages.value
    .filter(s => s.days_until_critical_level >= 0 && s.days_until_critical_level <= 14)
    .sort((a, b) => a.days_until_critical_level - b.days_until_critical_level)
    .map(s => ({
      days:         s.days_until_critical_level,
      pct:          Math.min(s.days_until_critical_level / 14, 1),
      stationShort: s.station_name.replace(/^Станция\s*/i, ''),
      fuel:         s.fuel_type_code || s.fuel_type_name,
      urgency:      s.urgency,
      dotClass:     dotByUrgency[s.urgency] || 'bg-gray-400',
    }));
});

// ── Formatters ────────────────────────────────────────────────────────────────
const formatCurrency = (v) => {
  if (!v) return '$0';
  if (v >= 1_000_000) return `$${(v / 1_000_000).toFixed(1)}M`;
  if (v >= 1_000)     return `$${(v / 1_000).toFixed(0)}K`;
  return `$${v.toFixed(0)}`;
};
const formatTons = (v) => {
  if (!v) return '0 t';
  if (v >= 1_000) return `${(v / 1_000).toFixed(1)}K t`;
  return `${v.toFixed(1)} t`;
};
const shortName = (name) => name?.replace(/^Станция\s+/i, '') ?? name;

// ── Style helpers ─────────────────────────────────────────────────────────────
const urgencyShortLabel = (u) => ({ CATASTROPHE:'CATASTR', CRITICAL:'CRITICAL', MUST_ORDER:'MUST ORDER', WARNING:'WARNING', PLANNED:'PLANNED' }[u] || u);
const getUrgencyClass   = (u) => ({ CATASTROPHE:'bg-red-600 text-white', CRITICAL:'bg-red-500 text-white', MUST_ORDER:'bg-orange-500 text-white', WARNING:'bg-yellow-500 text-white', PLANNED:'bg-blue-500 text-white' }[u] || 'bg-gray-500 text-white');
const getBorderClass    = (u) => ({ CATASTROPHE:'border-red-500',   CRITICAL:'border-red-400',   MUST_ORDER:'border-orange-400', WARNING:'border-yellow-400', PLANNED:'border-blue-300' }[u] || 'border-gray-200');
const getFillBarClass   = (u) => ({ CATASTROPHE:'bg-red-500',   CRITICAL:'bg-red-400',   MUST_ORDER:'bg-orange-500', WARNING:'bg-yellow-400', PLANNED:'bg-blue-400' }[u] || 'bg-gray-400');
const getDaysBoxClass   = (u) => ({ CATASTROPHE:'bg-red-100 border-red-300', CRITICAL:'bg-red-50 border-red-200', MUST_ORDER:'bg-orange-50 border-orange-300', WARNING:'bg-yellow-50 border-yellow-300', PLANNED:'bg-blue-50 border-blue-200' }[u] || 'bg-gray-50 border-gray-200');
const getDaysTextClass  = (u) => ({ CATASTROPHE:'text-red-700', CRITICAL:'text-red-600', MUST_ORDER:'text-orange-600', WARNING:'text-yellow-700', PLANNED:'text-blue-600' }[u] || 'text-gray-700');

// ── Create Order: navigate to Orders with pre-fill ────────────────────────────
function createOrder(rec) {
  router.push({
    path:  '/orders',
    query: {
      action:        'create_po',
      station_id:    rec.station_id,
      fuel_type_id:  rec.fuel_type_id,
      quantity_tons: rec.recommended_order_tons,
      supplier_id:   rec.best_supplier?.id || '',
      delivery_date: rec.last_order_date   || '',
    },
  });
}

// ── Remove PO inline: delete planned PO and reload recommendations ────────────
async function confirmRemovePO(poId) {
  cancelLoading.value = true;
  try {
    await ordersApi.delete(poId);
    cancelingId.value = null;
    await loadData(); // Reload: system recalculates, recommendation reappears
  } catch (e) {
    console.error('Remove PO error:', e);
    cancelingId.value = null;
  } finally {
    cancelLoading.value = false;
  }
}

// ── Data loading ──────────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true;
  try {
    const [summaryRes, shortagesRes] = await Promise.all([
      procurementApi.getSummary(),
      procurementApi.getUpcomingShortages(14),
    ]);
    if (summaryRes.data.success)   summary.value   = summaryRes.data.data;
    if (shortagesRes.data.success) shortages.value = shortagesRes.data.data;
  } catch (e) {
    console.error('ProcurementAdvisor load error:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);
</script>

<style scoped>
.pa-tabs { display:flex; gap:0; padding:0 24px; margin:0 -24px; margin-top:12px; margin-bottom:0; }
.pa-tab  { background:#e0e7ff; border:1px solid transparent; padding:10px 20px; font-size:14px;
           color:#4338ca; cursor:pointer; margin-bottom:-1px; border-radius:6px 6px 0 0;
           transition:background .2s,color .2s; display:flex; align-items:center; }
.pa-tab:hover:not(.active) { background:#c7d2fe; }
.pa-tab.active { background:#fff; color:#0f172a; font-weight:600; border:1px solid #e5e7eb; border-bottom:1px solid #fff; }
</style>
