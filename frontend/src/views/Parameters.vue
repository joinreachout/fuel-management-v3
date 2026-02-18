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
               TAB 5 — SUPPLIER OFFERS (price_per_ton)
               ═══════════════════════════════════════════════ -->
          <div v-else-if="activeTab === 'supplier-offers'">
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
              <i class="fas fa-handshake mr-2"></i>
              <strong>Supplier Offers</strong>: price per ton and delivery days per
              supplier → station → fuel type route. Update prices as quotes arrive.
            </div>
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Supplier</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Delivers To</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Price / Ton (USD)</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Delivery Days</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Currency</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="offer in supplierOffers" :key="offer.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ offer.supplier_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ offer.station_name }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">{{ offer.fuel_type_code }}</span>
                    {{ offer.fuel_type_name }}
                  </td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="offer.price_per_ton"
                      type="number" step="100"
                      suffix=" $/ton"
                      @save="val => saveSupplierOffer(offer.id, { price_per_ton: val, delivery_days: offer.delivery_days })"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <InlineEdit
                      :value="offer.delivery_days"
                      type="number" step="1"
                      suffix=" days"
                      @save="val => saveSupplierOffer(offer.id, { delivery_days: val, price_per_ton: offer.price_per_ton })"
                    />
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600">{{ offer.currency }}</td>
                  <td class="px-4 py-3">
                    <span :class="offer.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                          class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ offer.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="supplierOffers.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-gray-500">No supplier offers configured</td>
                </tr>
              </tbody>
            </table>
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

const saveSupplierOffer = async (id, data) => {
  try {
    const res = await parametersApi.updateSupplierOffer(id, data);
    flashSave(res.data.success);
    const offer = supplierOffers.value.find(x => x.id === id);
    if (offer) Object.assign(offer, data);
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
