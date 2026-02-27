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
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'immediate' }"
          @click="activeTab = 'immediate'">
          🚨 Immediate Action
          <span v-if="!loading && crisisItems.length"
            class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white">
            {{ crisisItems.length }}
          </span>
        </button>
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'proactive' }"
          @click="activeTab = 'proactive'">
          📋 Proactive Planning
          <span v-if="!loading && proactiveItems.length"
            class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-blue-500 text-white">
            {{ proactiveItems.length }}
          </span>
        </button>
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'cases' }"
          @click="activeTab = 'cases'; loadCases()">
          <i class="fas fa-clipboard-list mr-1"></i>Cases
          <span v-if="activeCasesCount > 0"
            class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-purple-600 text-white">
            {{ activeCasesCount }}
          </span>
        </button>
        <button type="button" class="pa-tab" :class="{ active: activeTab === 'orderplan' }"
          @click="activeTab = 'orderplan'">
          <i class="fas fa-boxes mr-1"></i>Order Plan
          <span v-if="!loading && orderPlanStats.totalItems > 0"
            class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-emerald-600 text-white">
            {{ orderPlanStats.totalItems }}
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

          <!-- Urgency KPI grid — Mandatory / Act Soon / Planned -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Mandatory: CATASTROPHE + CRITICAL -->
            <button
              type="button"
              @click="activeTab = 'immediate'"
              class="text-left bg-red-50 p-3 rounded-lg cursor-pointer hover:bg-red-100 transition-colors group border border-red-100">
              <div class="text-xs text-red-700 font-semibold mb-1 flex items-center justify-between">
                Mandatory
                <i class="fas fa-arrow-right text-red-300 group-hover:text-red-600 transition-colors text-xs"></i>
              </div>
              <div class="text-2xl font-bold text-red-900">
                {{ mandatoryCount }}
              </div>
              <div class="text-xs text-red-500 mt-1">CATASTROPHE + CRITICAL</div>
            </button>

            <!-- Act Soon: MUST_ORDER + WARNING -->
            <button
              type="button"
              @click="activeTab = 'proactive'"
              class="text-left bg-orange-50 p-3 rounded-lg cursor-pointer hover:bg-orange-100 transition-colors group border border-orange-100">
              <div class="text-xs text-orange-700 font-semibold mb-1 flex items-center justify-between">
                Act Soon
                <i class="fas fa-arrow-right text-orange-300 group-hover:text-orange-600 transition-colors text-xs"></i>
              </div>
              <div class="text-2xl font-bold text-orange-900">
                {{ actSoonCount }}
              </div>
              <div class="text-xs text-orange-500 mt-1">MUST ORDER + WARNING</div>
            </button>

            <!-- Planned: PLANNED -->
            <button
              type="button"
              @click="activeTab = 'proactive'"
              class="text-left bg-blue-50 p-3 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors group border border-blue-100">
              <div class="text-xs text-blue-700 font-semibold mb-1 flex items-center justify-between">
                Planned
                <i class="fas fa-arrow-right text-blue-300 group-hover:text-blue-600 transition-colors text-xs"></i>
              </div>
              <div class="text-2xl font-bold text-blue-900">
                {{ plannedCount }}
              </div>
              <div class="text-xs text-blue-500 mt-1">Planned opportunities</div>
            </button>
          </div>

          <!-- 14-day Timeline -->
          <div v-if="timelineItems.length > 0" class="mt-4 bg-gray-50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                Stock hits critical level — next 14 days
              </span>
              <span class="text-xs text-gray-400">{{ timelineItems.length }} events</span>
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

              <div class="absolute text-gray-400 font-medium" style="top: 34px; left: 0; font-size: 10px">TODAY</div>
              <div class="absolute text-gray-400 font-medium" style="top: 34px; right: 0; font-size: 10px">+14 days</div>
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

      <!-- ── IMMEDIATE ACTION tab ─────────────────────────────────────── -->
      <div v-if="activeTab === 'immediate'">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading...</p>
        </div>
        <div v-else-if="crisisItems.length === 0" class="text-center py-8">
          <i class="fas fa-check-circle text-green-500 text-3xl"></i>
          <p class="text-sm text-gray-600 mt-2 font-semibold">No crisis situations!</p>
          <p class="text-xs text-gray-500 mt-1">All deliveries can arrive before critical dates.</p>
        </div>
        <div v-else>
          <!-- Urgency bar -->
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
                <div v-if="urgencyCounts[lv.key] > 0" :class="lv.bgClass"
                  :style="{ flex: urgencyCounts[lv.key] }" class="transition-all duration-500"></div>
              </template>
            </div>
          </div>
          <!-- Crisis cards grid -->
          <div class="flex items-center gap-2 mb-3 px-1">
            <span class="text-red-600 font-bold text-sm">🚨 Requires Immediate Action</span>
            <span class="text-xs text-red-400">— no regular delivery can arrive in time</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
              <div
                v-for="rec in crisisItems"
                :key="rec.id"
                class="border-2 rounded-xl p-3 flex flex-col gap-2 bg-white"
                :class="getBorderClass(rec.urgency)">
                <!-- Row 1-5: same card internals — reuse slot below via include pattern -->
                <div class="flex items-start justify-between gap-1">
                  <div class="min-w-0">
                    <div class="font-bold text-gray-800 text-sm leading-tight truncate">{{ shortName(rec.station_name) }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ rec.depot_name }} · {{ rec.fuel_type }}</div>
                  </div>
                  <span class="shrink-0 px-1.5 py-0.5 rounded-md text-xs font-bold" :class="getUrgencyClass(rec.urgency)">{{ urgencyShortLabel(rec.urgency) }}</span>
                </div>
                <div>
                  <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-500">{{ formatTons(rec.current_stock_tons) }}</span>
                    <span class="font-semibold" :class="getDaysTextClass(rec.urgency)">{{ rec.fill_percentage }}%</span>
                  </div>
                  <div class="relative h-2.5 rounded-full bg-gray-100 overflow-hidden">
                    <div class="absolute inset-y-0 left-0 bg-red-100" :style="{ width: rec.thresholds.critical + '%' }"></div>
                    <div class="absolute inset-y-0 bg-orange-50" :style="{ left: rec.thresholds.critical + '%', width: (rec.thresholds.min - rec.thresholds.critical) + '%' }"></div>
                    <div class="absolute inset-y-0 bg-yellow-50" :style="{ left: rec.thresholds.min + '%', width: (rec.thresholds.target - rec.thresholds.min) + '%' }"></div>
                    <div class="absolute inset-y-0 bg-green-50" :style="{ left: rec.thresholds.target + '%', width: (rec.thresholds.max - rec.thresholds.target) + '%' }"></div>
                    <div class="absolute inset-y-0 left-0 rounded-full" :class="getFillBarClass(rec.urgency)" :style="{ width: Math.min(rec.fill_percentage, 100) + '%' }"></div>
                    <div class="absolute inset-y-0 w-px bg-red-400 opacity-70" :style="{ left: rec.thresholds.critical + '%' }"></div>
                    <div class="absolute inset-y-0 w-px bg-orange-400 opacity-70" :style="{ left: rec.thresholds.min + '%' }"></div>
                    <div class="absolute inset-y-0 w-px bg-green-500 opacity-70" :style="{ left: rec.thresholds.target + '%' }"></div>
                  </div>
                </div>
                <div class="flex gap-2 items-stretch">
                  <div class="shrink-0 flex flex-col items-center justify-center px-3 py-2 rounded-xl border-2 text-center min-w-[60px]" :class="getDaysBoxClass(rec.urgency)">
                    <div class="text-2xl font-black leading-none tabular-nums" :class="getDaysTextClass(rec.urgency)">
                      {{ rec.days_until_critical_level == null ? '∞' : rec.days_until_critical_level === 0 ? '!' : Math.round(rec.days_until_critical_level) }}
                    </div>
                    <div class="leading-tight mt-0.5" style="font-size: 10px; font-weight: 600" :class="getDaysTextClass(rec.urgency)">
                      {{ rec.days_until_critical_level == null ? 'no data' : rec.days_until_critical_level === 0 ? 'CRITICAL' : 'to crit.' }}
                    </div>
                  </div>
                  <div class="flex-1 grid grid-cols-2 gap-1 text-xs">
                    <div class="bg-gray-50 rounded-lg px-2 py-1.5 col-span-2">
                      <div class="text-gray-400 leading-tight">Critical level date</div>
                      <div class="font-medium text-gray-700 mt-0.5">{{ rec.critical_level_date }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg px-2 py-1.5 col-span-2">
                      <div class="text-gray-400 leading-tight">Stock needed</div>
                      <div class="font-bold text-emerald-700 mt-0.5">{{ formatTons(rec.recommended_order_tons) }}</div>
                    </div>
                  </div>
                </div>
                <div v-if="rec.best_supplier" class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-2 py-1.5">
                  <span class="font-medium text-gray-700 truncate">{{ rec.best_supplier.name }}</span>
                  <span class="text-gray-500 shrink-0 ml-1"><i class="fas fa-truck text-blue-400 mr-0.5"></i>{{ rec.best_supplier.avg_delivery_days }}d</span>
                </div>
                <div v-if="rec.po_pending && rec.active_po" class="bg-amber-50 border border-amber-300 rounded-lg px-2 py-1.5 text-xs">
                  <div class="text-amber-700 font-bold mb-0.5" style="font-size:10px">⚠️ PO EXISTS — arrives too late</div>
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-clipboard text-amber-500 shrink-0"></i>
                    <span class="font-semibold text-amber-900">{{ rec.active_po.order_number }}</span>
                    <span class="text-amber-700 flex-1 truncate">· {{ rec.active_po.quantity_tons }}t · {{ rec.active_po.delivery_date }}</span>
                    <template v-if="cancelingId === rec.active_po.id">
                      <button type="button" @click="confirmRemovePO(rec.active_po.id)" :disabled="cancelLoading" class="shrink-0 px-1.5 py-0.5 bg-red-500 text-white rounded font-bold hover:bg-red-600 transition-colors disabled:opacity-50">
                        <i v-if="cancelLoading" class="fas fa-spinner fa-spin"></i><span v-else>Delete?</span>
                      </button>
                      <button type="button" @click="cancelingId = null" class="shrink-0 px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded hover:bg-gray-300 transition-colors">No</button>
                    </template>
                    <button v-else type="button" @click="cancelingId = rec.active_po.id" class="shrink-0 px-1.5 py-0.5 rounded text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors" title="Remove PO"><i class="fas fa-times"></i></button>
                  </div>
                  <div v-if="cancelingId === rec.active_po.id" class="mt-1 text-red-600 font-medium" style="font-size: 10px">Remove PO? System will recalculate.</div>
                </div>
                <!-- Crisis Case badge — shown when an active case exists for this depot+fuel -->
                <div
                  v-if="activeCaseByKey[rec.depot_id + '_' + rec.fuel_type_id]"
                  class="bg-purple-50 border border-purple-300 rounded-lg px-2 py-1.5 text-xs
                         flex items-center gap-1.5 cursor-pointer hover:bg-purple-100 transition-colors"
                  @click="activeTab = 'cases'">
                  <i class="fas fa-spinner fa-spin text-purple-500 shrink-0"></i>
                  <span class="font-semibold text-purple-800">
                    Crisis Case #{{ activeCaseByKey[rec.depot_id + '_' + rec.fuel_type_id].id }} — in progress
                  </span>
                  <span class="text-purple-400 ml-auto">→ Cases</span>
                </div>

                <!-- Crisis actions: Resolve (primary) + Escalate (fallback) -->
                <div class="mt-auto flex flex-col gap-1.5">
                  <button type="button"
                    @click="openCrisisModal(rec)"
                    class="w-full py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-red-600 to-orange-600 text-white hover:from-red-700 hover:to-orange-700 transition-all">
                    <i class="fas fa-bolt mr-1"></i>Resolve Crisis
                  </button>
                  <button type="button"
                    class="w-full py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all border border-gray-200">
                    <i class="fas fa-phone-alt mr-1"></i>Escalate to Management
                  </button>
                </div>
              </div>
            </div><!-- end crisis grid -->
        </div><!-- end crisis v-else -->
      </div><!-- end immediate tab -->

      <!-- ── PROACTIVE PLANNING tab ────────────────────────────────────── -->
      <div v-if="activeTab === 'proactive'">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading...</p>
        </div>
        <div v-else-if="proactiveItems.length === 0" class="text-center py-8">
          <i class="fas fa-check-circle text-green-500 text-3xl"></i>
          <p class="text-sm text-gray-600 mt-2 font-semibold">All stock levels are healthy!</p>
          <p class="text-xs text-gray-500 mt-1">No orders needed at this time.</p>
        </div>
        <div v-else>
          <!-- Urgency bar -->
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
                <div v-if="urgencyCounts[lv.key] > 0" :class="lv.bgClass"
                  :style="{ flex: urgencyCounts[lv.key] }" class="transition-all duration-500"></div>
              </template>
            </div>
          </div>
          <!-- Proactive cards grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
              <div
                v-for="rec in proactiveItems"
                :key="rec.id"
              class="border-2 rounded-xl p-3 flex flex-col gap-2 bg-white"
              :class="rec.po_pending ? 'border-green-400 bg-green-50/40' : getBorderClass(rec.urgency)">

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
                    {{ rec.days_until_critical_level == null ? '∞' : rec.days_until_critical_level === 0 ? '!' : Math.round(rec.days_until_critical_level) }}
                  </div>
                  <div class="leading-tight mt-0.5" style="font-size: 10px; font-weight: 600"
                    :class="getDaysTextClass(rec.urgency)">
                    {{ rec.days_until_critical_level == null ? 'no data' : rec.days_until_critical_level === 0 ? 'CRITICAL' : 'to crit.' }}
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

              </div><!-- end proactive card -->
            </div><!-- end proactive grid -->
          </div><!-- end proactive v-else -->
        </div><!-- end proactive tab -->

      <!-- ── CASES tab ────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'cases'" class="space-y-3">
        <div v-if="casesLoading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading cases...</p>
        </div>
        <div v-else-if="!cases.length" class="text-center py-10">
          <i class="fas fa-clipboard-check text-gray-300 text-4xl mb-3"></i>
          <p class="text-sm font-semibold text-gray-500">No crisis cases yet</p>
          <p class="text-xs text-gray-400 mt-1">
            Cases appear here when you accept a proposal from the
            <button type="button" @click="activeTab = 'immediate'"
              class="text-red-500 font-semibold underline hover:text-red-700">
              Immediate Action
            </button> tab.
          </p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="c in cases" :key="c.id"
            class="border rounded-xl p-4 transition-all"
            :class="c.status === 'resolved'
              ? 'border-gray-200 bg-gray-50/50 opacity-70'
              : 'border-orange-300 bg-orange-50/20'">
            <!-- Case header: status badge + type badge + resolve button -->
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-black px-2 py-0.5 rounded-full"
                  :class="{
                    'bg-red-100 text-red-700':      c.status === 'accepted',
                    'bg-orange-100 text-orange-700': c.status === 'monitoring',
                    'bg-green-100 text-green-700':   c.status === 'resolved',
                    'bg-gray-100 text-gray-600':     c.status === 'proposed',
                  }">
                  {{ c.status?.toUpperCase() }}
                </span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                  {{ c.case_type === 'split_delivery' ? '✂️ Split Delivery' : '🔄 Transfer' }}
                </span>
                <span class="text-xs text-gray-400">#{{ c.id }}</span>
              </div>
              <button v-if="c.status !== 'resolved'"
                type="button"
                @click="resolveCrisisCase(c.id)"
                class="shrink-0 text-xs px-3 py-1 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition-all">
                ✓ Mark Resolved
              </button>
            </div>
            <!-- Details grid -->
            <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-xs mb-3">
              <div>
                <span class="text-gray-400">Critical depot:</span>
                <span class="font-semibold text-gray-800 ml-1">{{ c.receiving_depot_name || ('Depot #' + c.receiving_depot_id) }}</span>
              </div>
              <div>
                <span class="text-gray-400">Fuel:</span>
                <span class="font-semibold text-gray-800 ml-1">{{ c.fuel_type_code || c.fuel_type_name || ('Type #' + c.fuel_type_id) }}</span>
              </div>
              <div>
                <span class="text-gray-400">Split qty:</span>
                <span class="font-semibold text-orange-700 ml-1">{{ c.split_qty_tons }} t</span>
              </div>
              <div>
                <span class="text-gray-400">Qty needed:</span>
                <span class="font-semibold text-gray-800 ml-1">{{ c.qty_needed_tons }} t</span>
              </div>
              <div v-if="c.case_type === 'split_delivery'">
                <span class="text-gray-400">Donor order:</span>
                <span class="font-semibold text-gray-800 ml-1">{{ c.donor_order_number || ('#' + c.donor_order_id) }}</span>
              </div>
              <div v-if="c.case_type === 'transfer'">
                <span class="text-gray-400">Donor depot:</span>
                <span class="font-semibold text-gray-800 ml-1">{{ c.donor_depot_name || ('Depot #' + c.donor_depot_id) }}</span>
              </div>
              <div>
                <span class="text-gray-400">Created:</span>
                <span class="text-gray-600 ml-1">{{ c.created_at?.substring(0, 10) }}</span>
              </div>
            </div>
            <!-- PO link status -->
            <div class="flex gap-6 pt-2 border-t border-gray-200">
              <div class="flex items-center gap-1.5 text-xs"
                :class="c.po_for_critical_id ? 'text-green-600' : 'text-gray-400'">
                <i :class="c.po_for_critical_id ? 'fas fa-check-circle' : 'far fa-circle'"></i>
                <span>PO — Critical depot</span>
                <span v-if="c.po_for_critical_number" class="font-bold ml-0.5">{{ c.po_for_critical_number }}</span>
              </div>
              <div class="flex items-center gap-1.5 text-xs"
                :class="c.po_for_donor_id ? 'text-green-600' : 'text-gray-400'">
                <i :class="c.po_for_donor_id ? 'fas fa-check-circle' : 'far fa-circle'"></i>
                <span>PO — Donor</span>
                <span v-if="c.po_for_donor_number" class="font-bold ml-0.5">{{ c.po_for_donor_number }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ORDER PLAN (Consolidated Order) ─────────────────────────────── -->
      <div v-if="activeTab === 'orderplan'">
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
          <p class="text-sm text-gray-500 mt-2">Loading...</p>
        </div>
        <div v-else-if="!consolidatedBySupplier.length && !noSupplierItems.length" class="text-center py-10">
          <i class="fas fa-check-circle text-green-500 text-3xl mb-3"></i>
          <p class="text-sm font-semibold text-gray-600">No orders needed at this time</p>
          <p class="text-xs text-gray-400 mt-1">All stock levels are within safe thresholds</p>
        </div>
        <div v-else class="space-y-4">

          <!-- Summary strip -->
          <div class="flex flex-wrap items-center gap-3 text-xs bg-gray-50 rounded-xl px-4 py-3">
            <span class="font-bold text-gray-800 text-sm">{{ orderPlanStats.totalItems }} positions</span>
            <span class="text-gray-300">|</span>
            <span class="font-black text-gray-800">{{ orderPlanStats.totalTons.toLocaleString() }} t total</span>
            <span class="text-gray-300">|</span>
            <span v-if="orderPlanStats.mandatoryItems"
              class="flex items-center gap-1 font-bold text-red-600">
              <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
              {{ orderPlanStats.mandatoryItems }} mandatory (CATASTROPHE / CRITICAL)
            </span>
            <span v-if="orderPlanStats.recommendedItems"
              class="flex items-center gap-1 font-semibold text-blue-600">
              <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
              {{ orderPlanStats.recommendedItems }} recommended
            </span>
          </div>

          <!-- Per-supplier blocks -->
          <div v-for="supplier in consolidatedBySupplier" :key="supplier.supplier_id"
            class="border border-gray-200 rounded-xl overflow-hidden">

            <!-- Supplier header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
              <div class="flex items-center gap-3">
                <i class="fas fa-industry text-blue-500 text-sm"></i>
                <span class="font-bold text-gray-800">{{ supplier.supplier_name }}</span>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                  <i class="fas fa-truck text-blue-300"></i>{{ supplier.avg_delivery_days }}d
                </span>
                <span v-if="supplier.price_per_ton" class="text-xs text-gray-400">
                  · {{ Number(supplier.price_per_ton).toLocaleString() }} $/t
                </span>
              </div>
              <div class="flex items-center gap-3 text-sm">
                <span v-if="supplier.mandatory_tons > 0"
                  class="font-bold text-red-600">{{ supplier.mandatory_tons.toLocaleString() }}t <span class="font-normal text-red-400 text-xs">mand.</span></span>
                <span v-if="supplier.recommended_tons > 0"
                  class="font-semibold text-blue-600">{{ supplier.recommended_tons.toLocaleString() }}t <span class="font-normal text-blue-400 text-xs">rec.</span></span>
                <span class="font-black text-gray-900 border-l border-gray-300 pl-3">
                  {{ supplier.total_tons.toLocaleString() }} t
                </span>
              </div>
            </div>

            <!-- Items table -->
            <table class="w-full text-xs">
              <thead>
                <tr class="bg-white border-b border-gray-100">
                  <th class="text-left px-4 py-2 font-semibold text-gray-400">Station</th>
                  <th class="text-left px-3 py-2 font-semibold text-gray-400">Fuel</th>
                  <th class="text-right px-3 py-2 font-semibold text-gray-400">Qty (t)</th>
                  <th class="text-center px-3 py-2 font-semibold text-gray-400">Days to crit.</th>
                  <th class="text-left px-3 py-2 font-semibold text-gray-400">Order by</th>
                  <th class="text-center px-3 py-2 font-semibold text-gray-400"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="item in supplier.items" :key="item.id"
                  class="hover:bg-gray-50 transition-colors"
                  :class="item.mandatory ? 'bg-red-50/30' : ''">
                  <td class="px-4 py-2 font-medium text-gray-800">{{ shortName(item.station_name) }}</td>
                  <td class="px-3 py-2 text-gray-600">{{ item.fuel_type_code || item.fuel_type }}</td>
                  <td class="px-3 py-2 text-right font-bold text-gray-800">
                    {{ item.quantity_tons > 0 ? item.quantity_tons.toLocaleString() : '—' }}
                  </td>
                  <td class="px-3 py-2 text-center">
                    <span v-if="item.days_until_critical != null"
                      class="font-bold" :class="getDaysTextClass(item.urgency)">
                      {{ Math.round(item.days_until_critical) }}d
                    </span>
                    <span v-else class="text-gray-300">∞</span>
                  </td>
                  <td class="px-3 py-2 font-medium whitespace-nowrap"
                    :class="item.last_order_date ? 'text-orange-600' : 'text-gray-300'">
                    {{ item.last_order_date || '—' }}
                  </td>
                  <td class="px-3 py-2 text-center">
                    <span class="px-1.5 py-0.5 rounded font-bold text-[10px]"
                      :class="item.mandatory ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                      {{ item.mandatory ? 'MAND.' : 'REC.' }}
                    </span>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50/80 font-bold">
                  <td colspan="2" class="px-4 py-2 text-gray-600">TOTAL — {{ supplier.supplier_name }}</td>
                  <td class="px-3 py-2 text-right text-gray-900 font-black">{{ supplier.total_tons.toLocaleString() }} t</td>
                  <td colspan="3"></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Items with no supplier configured -->
          <div v-if="noSupplierItems.length"
            class="border border-dashed border-red-200 rounded-xl p-4 bg-red-50/20">
            <div class="flex items-center gap-2 text-xs font-bold text-red-600 mb-3">
              <i class="fas fa-exclamation-circle"></i>
              No supplier offers configured for {{ noSupplierItems.length }} position(s)
            </div>
            <div class="space-y-1">
              <div v-for="item in noSupplierItems" :key="item.id"
                class="flex items-center gap-3 text-xs py-1.5 border-b border-red-100 last:border-0">
                <span class="font-medium text-gray-800">{{ shortName(item.station_name) }}</span>
                <span class="text-gray-400">·</span>
                <span class="text-gray-600">{{ item.fuel_type }}</span>
                <span class="ml-auto px-1.5 py-0.5 rounded font-bold text-[10px]"
                  :class="item.mandatory ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                  {{ item.mandatory ? 'MAND.' : 'REC.' }}
                </span>
              </div>
            </div>
            <p class="text-xs text-red-400 mt-3 italic">
              Go to Parameters → Supply Offers to configure supplier prices for these station + fuel combinations.
            </p>
          </div>

        </div><!-- end v-else -->
      </div><!-- end orderplan tab -->

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

  <!-- Crisis Resolution Modal — fixed overlay, outside overflow-hidden parent -->
  <CrisisResolutionModal
    v-if="showCrisisModal && crisisModalRec"
    :rec="crisisModalRec"
    @close="showCrisisModal = false"
    @resolved="onCrisisResolved"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { procurementApi, ordersApi, crisisApi } from '../services/api';
import CrisisResolutionModal from './CrisisResolutionModal.vue';

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

// ── Crisis Resolution Modal ───────────────────────────────────────────────────
const showCrisisModal = ref(false);
const crisisModalRec  = ref(null);

// ── Cases tab ────────────────────────────────────────────────────────────────
const cases        = ref([]);
const casesLoading = ref(false);

// Count of non-resolved cases for the tab badge
const activeCasesCount = computed(() =>
  cases.value.filter(c => c.status !== 'resolved').length
);

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
    depot_id:                 s.depot_id,
    station_id:               s.station_id,
    station_name:             s.station_name,
    depot_name:               s.depot_name,
    fuel_type_id:             s.fuel_type_id,
    fuel_type:                s.fuel_type_name,
    fuel_type_code:           s.fuel_type_code,
    urgency:                  s.urgency,
    days_left:                s.days_left,
    // null when consumption = 0 (show ∞ in hero); do NOT fall back to days_left
    days_until_critical_level: s.days_until_critical_level ?? null,
    procurement_too_late:      s.procurement_too_late ?? false,
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

// ── Computed: crisis vs proactive split ───────────────────────────────────────
const crisisItems    = computed(() => recommendations.value.filter(r => r.urgency === 'CATASTROPHE'))
const proactiveItems = computed(() => recommendations.value.filter(r => r.urgency !== 'CATASTROPHE'))

// ── Computed: active crisis cases keyed by "depotId_fuelTypeId" ───────────────
// Used to show "🔄 Crisis Case #N — in progress" banner on Immediate Action cards
const activeCaseByKey = computed(() => {
  const map = {}
  for (const c of cases.value) {
    if (c.status === 'resolved') continue
    const key = `${c.receiving_depot_id}_${c.fuel_type_id}`
    if (!map[key]) map[key] = c // keep first (most recent if API returns desc)
  }
  return map
})

// Aggregated urgency buckets for Briefing KPI grid
const mandatoryCount = computed(() =>
  (urgencyCounts.value.CATASTROPHE || 0) + (urgencyCounts.value.CRITICAL || 0)
);
const actSoonCount = computed(() =>
  (urgencyCounts.value.MUST_ORDER || 0) + (urgencyCounts.value.WARNING || 0)
);
const plannedCount = computed(() =>
  urgencyCounts.value.PLANNED || 0
);

// ── Computed: consolidated order plan — grouped by best_supplier ──────────────
const consolidatedBySupplier = computed(() => {
  const map = {}
  for (const s of shortages.value) {
    if (!s.best_supplier?.id) continue  // no supplier → shown separately
    const suppId = s.best_supplier.id
    if (!map[suppId]) {
      map[suppId] = {
        supplier_id:       suppId,
        supplier_name:     s.best_supplier.name,
        avg_delivery_days: s.best_supplier.avg_delivery_days,
        price_per_ton:     s.best_supplier.price_per_ton,
        items:             [],
        total_tons:        0,
        mandatory_tons:    0,
        recommended_tons:  0,
      }
    }
    const isMandatory = ['CATASTROPHE', 'CRITICAL'].includes(s.urgency)
    const qty = s.recommended_order_tons || 0
    map[suppId].items.push({
      id:                  s.depot_id + '_' + s.fuel_type_id,
      station_name:        s.station_name,
      fuel_type:           s.fuel_type_name,
      fuel_type_code:      s.fuel_type_code,
      quantity_tons:       qty,
      urgency:             s.urgency,
      mandatory:           isMandatory,
      days_until_critical: s.days_until_critical_level,
      last_order_date:     s.last_order_date,
    })
    map[suppId].total_tons       += qty
    if (isMandatory) map[suppId].mandatory_tons    += qty
    else             map[suppId].recommended_tons  += qty
  }
  // Sort: suppliers with mandatory items first, then by total descending
  return Object.values(map).sort((a, b) =>
    b.mandatory_tons - a.mandatory_tons || b.total_tons - a.total_tons
  )
})

// Positions where no supplier offer exists for station+fuel
const noSupplierItems = computed(() =>
  shortages.value
    .filter(s => !s.best_supplier?.id)
    .map(s => ({
      id:           s.depot_id + '_' + s.fuel_type_id,
      station_name: s.station_name,
      fuel_type:    s.fuel_type_name,
      urgency:      s.urgency,
      mandatory:    ['CATASTROPHE', 'CRITICAL'].includes(s.urgency),
    }))
)

// Aggregate stats for Order Plan tab badge + summary strip
const orderPlanStats = computed(() => {
  let totalItems = 0, totalTons = 0, mandatoryItems = 0, recommendedItems = 0
  for (const s of shortages.value) {
    totalItems++
    totalTons += s.recommended_order_tons || 0
    if (['CATASTROPHE', 'CRITICAL'].includes(s.urgency)) mandatoryItems++
    else recommendedItems++
  }
  return { totalItems, totalTons: Math.round(totalTons), mandatoryItems, recommendedItems }
})

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
    .filter(s => s.days_until_critical_level != null && s.days_until_critical_level >= 0 && s.days_until_critical_level <= 14)
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

// ── Round tonnage up to nearest wagon (60 t = 1 wagon, industry standard) ─────
function roundUpTons(tons) {
  if (!tons || tons <= 0) return 0
  return Math.ceil(tons / 60) * 60
}

// ── Create Order: navigate to Orders with pre-fill ────────────────────────────
function createOrder(rec) {
  router.push({
    path:  '/orders',
    query: {
      action:        'create_po',
      station_id:    rec.station_id,
      fuel_type_id:  rec.fuel_type_id,
      quantity_tons: roundUpTons(rec.recommended_order_tons),
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

// ── Crisis Modal ──────────────────────────────────────────────────────────────
function openCrisisModal(rec) {
  crisisModalRec.value  = rec;
  showCrisisModal.value = true;
}

// Called by modal @resolved — reload data + cases, close modal
async function onCrisisResolved() {
  showCrisisModal.value = false;
  await Promise.all([loadData(), loadCases()]);
}

// ── Cases tab: load + resolve ──────────────────────────────────────────────────
async function loadCases() {
  casesLoading.value = true;
  try {
    const res = await crisisApi.getCases();
    if (res.data.success) cases.value = res.data.data;
  } catch (e) {
    console.error('loadCases error:', e);
  } finally {
    casesLoading.value = false;
  }
}

async function resolveCrisisCase(id) {
  try {
    await crisisApi.resolveCase(id);
    await loadCases();
  } catch (e) {
    console.error('resolveCrisisCase error:', e);
  }
}

// ── Data loading ──────────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true;
  try {
    const [summaryRes, shortagesRes] = await Promise.all([
      procurementApi.getSummary(),
      procurementApi.getUpcomingShortages(90),
    ]);
    if (summaryRes.data.success)   summary.value   = summaryRes.data.data;
    if (shortagesRes.data.success) shortages.value = shortagesRes.data.data;
  } catch (e) {
    console.error('ProcurementAdvisor load error:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadData();
  loadCases(); // pre-load so the Cases badge shows count immediately
});
</script>

<style scoped>
.pa-tabs { display:flex; gap:0; padding:0 24px; margin:0 -24px; margin-top:12px; margin-bottom:0; }
.pa-tab  { background:#e0e7ff; border:1px solid transparent; padding:10px 20px; font-size:14px;
           color:#4338ca; cursor:pointer; margin-bottom:-1px; border-radius:6px 6px 0 0;
           transition:background .2s,color .2s; display:flex; align-items:center; }
.pa-tab:hover:not(.active) { background:#c7d2fe; }
.pa-tab.active { background:#fff; color:#0f172a; font-weight:600; border:1px solid #e5e7eb; border-bottom:1px solid #fff; }
</style>
