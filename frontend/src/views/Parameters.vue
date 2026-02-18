<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Fixed Black Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <img src="/kkt_logo.png" alt="Kitty Kat Technologies" class="h-12 w-auto" style="filter: brightness(0) invert(1);">
        <nav class="flex items-center gap-6">
          <a href="#/dashboard" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</a>
          <a href="#/orders" class="text-gray-400 hover:text-white transition-colors text-sm">Orders</a>
          <a href="#/transfers" class="text-gray-400 hover:text-white transition-colors text-sm">Transfers</a>
          <a href="#/parameters" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Parameters</a>
          <a href="#/import" class="text-gray-400 hover:text-white transition-colors text-sm">Import</a>
          <a href="#/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</a>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="pt-20 px-8 py-6">
      <div class="max-w-[1920px] mx-auto">

        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">System Parameters</h1>
            <p class="text-gray-600 mt-1">All values come from the database — click any value to edit inline</p>
          </div>
          <div v-if="saveStatus" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium"
               :class="saveStatus === 'saved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
            <i :class="saveStatus === 'saved' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
            {{ saveStatus === 'saved' ? 'Saved' : 'Error saving' }}
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-t-xl border-b border-gray-200">
          <div class="flex gap-1 px-6 overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-5 py-4 text-sm font-medium transition-all border-b-2 whitespace-nowrap"
              :class="activeTab === tab.id
                ? 'text-blue-600 border-blue-600'
                : 'text-gray-600 border-transparent hover:text-gray-900'">
              <i :class="tab.icon" class="mr-2"></i>
              {{ tab.name }}
              <span v-if="tab.count !== undefined" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100">
                {{ tab.count }}
              </span>
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-b-xl shadow-lg p-6">

          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 1 — SYSTEM PARAMETERS
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'system'">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
              <i class="fas fa-cogs mr-2"></i>
              These are global planning thresholds used by the Procurement Advisor and alert system.
              Changes take effect immediately on next API call.
            </div>

            <div v-for="(params, category) in systemParams" :key="category" class="mb-8">
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3 border-b pb-2">
                {{ formatCategory(category) }}
              </h3>
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-64">Parameter</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-32">Value</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="p in params" :key="p.key" class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono text-gray-700">{{ p.key }}</td>
                    <td class="px-4 py-2">
                      <InlineEdit
                        :value="p.raw_value"
                        :type="p.type === 'integer' ? 'number' : (p.type === 'float' ? 'number' : 'text')"
                        :step="p.type === 'float' ? '0.01' : '1'"
                        @save="val => saveSystemParam(p.key, val)"
                      />
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ p.description }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 2 — FUEL TYPES
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'fuel-types'">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
              <i class="fas fa-info-circle mr-2"></i>
              <strong>Density</strong> (kg/L) is used internally to convert stock volumes between litres and tons.
              Pricing is managed per supplier in the <strong>Supply Offers</strong> tab.
            </div>
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel Type</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Code</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Density (kg/L)</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="ft in fuelTypes" :key="ft.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ ft.name }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ ft.code }}</td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="ft.density"
                      type="number"
                      step="0.001"
                      suffix=" kg/L"
                      @save="val => saveFuelType(ft.id, { density: val })"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 3 — SALES PARAMS (daily consumption)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'sales-params'">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
              <i class="fas fa-chart-line mr-2"></i>
              <strong>Sales Parameters</strong> define the daily consumption rate (litres/day) used for
              stock forecasting and procurement planning. Only active records are shown.
            </div>
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Station</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Depot</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel Type</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Litres / Day</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Effective From</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Effective To</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="sp in salesParams" :key="sp.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ sp.station_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ sp.depot_name }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">{{ sp.fuel_type_code }}</span>
                    {{ sp.fuel_type_name }}
                  </td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="sp.liters_per_day"
                      type="number"
                      step="1"
                      suffix=" L/day"
                      @save="val => saveSalesParam(sp.id, val)"
                    />
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ sp.effective_from }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ sp.effective_to || '—' }}</td>
                </tr>
                <tr v-if="salesParams.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-gray-500">No sales parameters configured</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 4 — STOCK POLICIES (per depot thresholds)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'stock-policies'">
            <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-800">
              <i class="fas fa-layer-group mr-2"></i>
              <strong>Stock Policies</strong> define per-depot alert thresholds (litres).
              These override global system parameters when set.
            </div>
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Station</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Depot</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Tank Capacity (L)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Critical Level (L)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Min Level (L)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Target Level (L)</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="pol in stockPolicies" :key="pol.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ pol.station_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ pol.depot_name }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">{{ pol.fuel_type_code }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ formatNum(pol.capacity_liters) }}</td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="pol.critical_level_liters"
                      type="number" step="1000"
                      suffix=" L"
                      @save="val => saveStockPolicy(pol.id, { critical_level_liters: val, min_level_liters: pol.min_level_liters, target_level_liters: pol.target_level_liters })"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="pol.min_level_liters"
                      type="number" step="1000"
                      suffix=" L"
                      @save="val => saveStockPolicy(pol.id, { min_level_liters: val, critical_level_liters: pol.critical_level_liters, target_level_liters: pol.target_level_liters })"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="pol.target_level_liters"
                      type="number" step="1000"
                      suffix=" L"
                      @save="val => saveStockPolicy(pol.id, { target_level_liters: val, min_level_liters: pol.min_level_liters, critical_level_liters: pol.critical_level_liters })"
                    />
                  </td>
                </tr>
                <tr v-if="stockPolicies.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-gray-500">No stock policies configured</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 5 — SUPPLIER OFFERS (cards layout)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'supplier-offers'">
            <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
              <i class="fas fa-handshake mr-2"></i>
              <strong>Supplier Offers</strong>: one price per fuel type (applies to all stations).
              Delivery days are per route (supplier → station). Click any value to edit.
            </div>

            <!-- Empty state -->
            <div v-if="groupedOffers.length === 0" class="py-12 text-center text-gray-500">
              No supplier offers configured
            </div>

            <!-- Supplier cards grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
              <div
                v-for="supplier in groupedOffers"
                :key="supplier.supplier_id"
                class="border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
              >
                <!-- Card header -->
                <div class="bg-gray-900 px-4 py-3 flex items-center gap-2">
                  <i class="fas fa-truck text-blue-400"></i>
                  <span class="text-white font-semibold text-sm">{{ supplier.supplier_name }}</span>
                  <span class="ml-auto text-xs text-gray-400">{{ supplier.fuelTypes.length }} fuel types · {{ supplier.stations.length }} stations</span>
                </div>

                <!-- Fuel prices section -->
                <div class="p-4 border-b border-gray-100">
                  <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                    <i class="fas fa-dollar-sign mr-1"></i> Prices (USD/ton)
                  </div>
                  <div class="grid grid-cols-2 gap-1.5">
                    <div
                      v-for="ft in supplier.fuelTypes"
                      :key="ft.fuel_type_id"
                      class="flex items-center justify-between bg-gray-50 rounded-lg px-2.5 py-1.5"
                    >
                      <span class="text-xs font-mono text-gray-500 mr-1">{{ ft.fuel_type_code }}</span>
                      <InlineEdit
                        :value="ft.price_per_ton"
                        type="number"
                        step="1"
                        suffix=" $"
                        @save="val => saveOfferPrice(supplier.supplier_id, ft.fuel_type_id, val)"
                      />
                    </div>
                  </div>
                </div>

                <!-- Delivery days section -->
                <div class="p-4">
                  <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                    <i class="fas fa-clock mr-1"></i> Delivery Days per Station
                  </div>
                  <div class="space-y-1">
                    <div
                      v-for="st in supplier.stations"
                      :key="st.station_id"
                      class="flex items-center justify-between"
                    >
                      <span class="text-xs text-gray-600 truncate mr-2">{{ st.station_name }}</span>
                      <InlineEdit
                        :value="st.delivery_days"
                        type="number"
                        step="1"
                        suffix=" days"
                        @save="val => saveOfferDays(supplier.supplier_id, st.station_id, val)"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 6 — INFRASTRUCTURE (Hierarchy Manager)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'infrastructure'">
            <HierarchyManager />
          </div>

          <!-- ═══════════════════════════════════════════════
               TAB 7 — DEPOT TANKS (reference, read-only)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'depot-tanks'">
            <div class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
              <i class="fas fa-database mr-2"></i>
              Physical tank inventory — reference data. Current stock and capacity in litres.
            </div>
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Station</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Depot</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Current Stock (L)</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Capacity (L)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 w-40">Fill %</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="tank in depotTanks" :key="tank.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ tank.station_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ tank.depot_name }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">{{ tank.fuel_type_code }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ formatNum(tank.current_stock_liters) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-mono text-gray-500">{{ formatNum(tank.capacity_liters) }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             :class="fillColor(tank.fill_pct)"
                             :style="{ width: Math.min(tank.fill_pct, 100) + '%' }"></div>
                      </div>
                      <span class="text-xs font-medium w-10 text-right">{{ tank.fill_pct }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { parametersApi } from '../services/api';
import HierarchyManager from '../components/HierarchyManager.vue';
import InlineEdit from '../components/InlineEdit.vue';

// ─── State ───────────────────────────────────────────────────────────────────

const loading    = ref(false);
const saveStatus = ref('');
const activeTab  = ref('infrastructure');

const systemParams    = ref({});
const fuelTypes       = ref([]);
const salesParams     = ref([]);
const stockPolicies   = ref([]);
const supplierOffers  = ref([]);
const depotTanks      = ref([]);

// ─── Tabs ────────────────────────────────────────────────────────────────────

const tabs = computed(() => [
  { id: 'infrastructure',  name: 'Infrastructure',    icon: 'fas fa-sitemap' },
  { id: 'supplier-offers', name: 'Supply Offers',     icon: 'fas fa-handshake',     count: supplierOffers.value.length },
  { id: 'sales-params',    name: 'Sales Params',      icon: 'fas fa-chart-line',    count: salesParams.value.length },
  { id: 'stock-policies',  name: 'Stock Policies',    icon: 'fas fa-layer-group',   count: stockPolicies.value.length },
  { id: 'fuel-types',      name: 'Fuel Types',        icon: 'fas fa-oil-can',       count: fuelTypes.value.length },
  { id: 'system',          name: 'System Parameters', icon: 'fas fa-cogs' },
  { id: 'depot-tanks',     name: 'Depot Tanks',       icon: 'fas fa-gas-pump',      count: depotTanks.value.length },
]);

// ─── Load ────────────────────────────────────────────────────────────────────

const loadAll = async () => {
  loading.value = true;
  try {
    const [sysRes, ftRes, spRes, polRes, offRes, tankRes] = await Promise.all([
      parametersApi.getSystem(),
      parametersApi.getFuelTypes(),
      parametersApi.getSalesParams(),
      parametersApi.getStockPolicies(),
      parametersApi.getSupplierOffers(),
      parametersApi.getDepotTanks(),
    ]);
    if (sysRes.data.success)  systemParams.value   = sysRes.data.data;
    if (ftRes.data.success)   fuelTypes.value      = ftRes.data.data;
    if (spRes.data.success)   salesParams.value    = spRes.data.data;
    if (polRes.data.success)  stockPolicies.value  = polRes.data.data;
    if (offRes.data.success)  supplierOffers.value = offRes.data.data;
    if (tankRes.data.success) depotTanks.value     = tankRes.data.data;
  } catch (e) {
    console.error('Parameters load error:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(loadAll);

// ─── Save helpers ────────────────────────────────────────────────────────────

let saveTimer = null;
const flashSave = (ok) => {
  saveStatus.value = ok ? 'saved' : 'error';
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => { saveStatus.value = ''; }, 2500);
};

const saveSystemParam = async (key, value) => {
  try {
    const res = await parametersApi.updateSystem(key, String(value));
    flashSave(res.data.success);
    // Update local cache
    for (const cat of Object.values(systemParams.value)) {
      const p = cat.find(x => x.key === key);
      if (p) { p.raw_value = String(value); p.value = value; }
    }
  } catch { flashSave(false); }
};

const saveFuelType = async (id, data) => {
  try {
    const res = await parametersApi.updateFuelType(id, data);
    flashSave(res.data.success);
    const ft = fuelTypes.value.find(x => x.id === id);
    if (ft) ft.density = data.density;
  } catch { flashSave(false); }
};

const saveSalesParam = async (id, litersPerDay) => {
  try {
    const res = await parametersApi.updateSalesParam(id, { liters_per_day: litersPerDay });
    flashSave(res.data.success);
    const sp = salesParams.value.find(x => x.id === id);
    if (sp) sp.liters_per_day = litersPerDay;
  } catch { flashSave(false); }
};

const saveStockPolicy = async (id, data) => {
  try {
    const res = await parametersApi.updateStockPolicy(id, data);
    flashSave(res.data.success);
    const pol = stockPolicies.value.find(x => x.id === id);
    if (pol) Object.assign(pol, data);
  } catch { flashSave(false); }
};

// ─── Supplier offers — grouped for card display ───────────────────────────────

const groupedOffers = computed(() => {
  const map = {};
  for (const o of supplierOffers.value) {
    if (!map[o.supplier_id]) {
      map[o.supplier_id] = {
        supplier_id:   o.supplier_id,
        supplier_name: o.supplier_name,
        // keyed maps, converted to arrays below
        _fuelMap:    {},
        _stationMap: {},
      };
    }
    const s = map[o.supplier_id];

    // Fuel type: take first occurrence (price is same across stations)
    if (!s._fuelMap[o.fuel_type_id]) {
      s._fuelMap[o.fuel_type_id] = {
        fuel_type_id:   o.fuel_type_id,
        fuel_type_code: o.fuel_type_code,
        fuel_type_name: o.fuel_type_name,
        price_per_ton:  o.price_per_ton,
      };
    }

    // Station: one entry per station with delivery_days
    if (!s._stationMap[o.station_id]) {
      s._stationMap[o.station_id] = {
        station_id:    o.station_id,
        station_name:  o.station_name,
        delivery_days: o.delivery_days,
      };
    }
  }

  return Object.values(map)
    .map(s => ({
      supplier_id:   s.supplier_id,
      supplier_name: s.supplier_name,
      fuelTypes:     Object.values(s._fuelMap).sort((a, b) => a.fuel_type_code.localeCompare(b.fuel_type_code)),
      stations:      Object.values(s._stationMap).sort((a, b) => a.station_name.localeCompare(b.station_name)),
    }))
    .sort((a, b) => a.supplier_name.localeCompare(b.supplier_name));
});

// Save price for a fuel type → updates ALL rows for this supplier+fuel across all stations
const saveOfferPrice = async (supplierId, fuelTypeId, newPrice) => {
  const targets = supplierOffers.value.filter(
    o => o.supplier_id === supplierId && o.fuel_type_id === fuelTypeId
  );
  try {
    await Promise.all(targets.map(o =>
      parametersApi.updateSupplierOffer(o.id, { price_per_ton: Number(newPrice), delivery_days: o.delivery_days })
        .then(() => { o.price_per_ton = Number(newPrice); })
    ));
    flashSave(true);
  } catch { flashSave(false); }
};

// Save delivery days for a station → updates ALL rows for this supplier+station across all fuel types
const saveOfferDays = async (supplierId, stationId, newDays) => {
  const targets = supplierOffers.value.filter(
    o => o.supplier_id === supplierId && o.station_id === stationId
  );
  try {
    await Promise.all(targets.map(o =>
      parametersApi.updateSupplierOffer(o.id, { price_per_ton: o.price_per_ton, delivery_days: Number(newDays) })
        .then(() => { o.delivery_days = Number(newDays); })
    ));
    flashSave(true);
  } catch { flashSave(false); }
};

// ─── Formatting ──────────────────────────────────────────────────────────────

const formatNum = (n) => n ? parseFloat(n).toLocaleString() : '0';

const formatCategory = (cat) => cat.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

const fillColor = (pct) => {
  if (pct >= 80) return 'bg-green-500';
  if (pct >= 40) return 'bg-yellow-400';
  if (pct >= 20) return 'bg-orange-500';
  return 'bg-red-600';
};
</script>
