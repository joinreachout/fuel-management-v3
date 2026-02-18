<template>
  <div class="hierarchy-manager">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <p class="text-sm text-gray-500 mt-0.5">
          {{ totalStats.regions }} regions · {{ totalStats.stations }} stations ·
          {{ totalStats.depots }} depots · {{ totalStats.tanks }} tanks
        </p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Search -->
        <div class="relative">
          <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
          <input
            v-model="search"
            placeholder="Search station or depot..."
            class="pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg w-52 focus:outline-none focus:ring-2 focus:ring-blue-300"
          />
        </div>
        <!-- Expand/Collapse all -->
        <button @click="expandAll"
                class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
          Expand all
        </button>
        <button @click="collapseAll"
                class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
          Collapse
        </button>
      </div>
    </div>

    <!-- ── Loading ─────────────────────────────────────────────── -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
    </div>

    <!-- ── Tree ───────────────────────────────────────────────── -->
    <div v-else class="space-y-3">
      <div v-for="region in filteredRegions" :key="region.id">

        <!-- ══ REGION ROW ══════════════════════════════════════ -->
        <div class="border border-gray-200 rounded-xl overflow-hidden">
          <div
            class="flex items-center gap-3 px-4 py-3 bg-gray-900 text-white cursor-pointer select-none"
            @click="toggleRegion(region.id)"
          >
            <i class="fas fa-chevron-right text-xs transition-transform duration-200"
               :class="openRegions.has(region.id) ? 'rotate-90' : ''"></i>
            <span class="font-semibold tracking-wide text-sm uppercase">{{ region.name }}</span>
            <div class="ml-auto flex items-center gap-4 text-xs text-gray-400">
              <span>{{ region.stations_count }} st.</span>
              <span>{{ region.depots_count }} depots</span>
              <span>{{ region.tanks_count }} tanks</span>
              <FillBar :pct="region.fill_pct" class="w-24" />
              <span class="text-gray-300 font-mono">{{ region.fill_pct }}%</span>
            </div>
          </div>

          <!-- ── STATIONS ──────────────────────────────────────── -->
          <div v-show="openRegions.has(region.id)" class="divide-y divide-gray-100">
            <div v-for="station in region.stations" :key="station.id" class="bg-white">

              <!-- STATION ROW -->
              <div
                class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 cursor-pointer group"
                @click="toggleStation(station.id)"
              >
                <i class="fas fa-chevron-right text-xs text-gray-400 transition-transform duration-200"
                   :class="openStations.has(station.id) ? 'rotate-90' : ''"></i>
                <i class="fas fa-train text-gray-400 text-xs"></i>

                <!-- Station name — inline edit -->
                <div class="flex items-center gap-2 flex-1 min-w-0" @click.stop>
                  <template v-if="editing.type === 'station' && editing.id === station.id">
                    <input v-model="editing.name" class="text-sm font-medium border-b border-blue-400 focus:outline-none w-48" @keydown.enter="saveEditing" @keydown.escape="cancelEditing" autofocus />
                    <input v-model="editing.code" class="text-xs font-mono border-b border-blue-400 focus:outline-none w-20" @keydown.enter="saveEditing" @keydown.escape="cancelEditing" />
                    <button @click="saveEditing" class="text-green-600 hover:text-green-800 text-xs"><i class="fas fa-check"></i></button>
                    <button @click="cancelEditing" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                  </template>
                  <template v-else>
                    <span class="text-sm font-medium text-gray-900">{{ station.name }}</span>
                    <span class="text-xs font-mono text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ station.code }}</span>
                    <button @click.stop="startEdit('station', station)"
                            class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-300 hover:text-blue-500 text-xs ml-1">
                      <i class="fas fa-pen"></i>
                    </button>
                  </template>
                </div>

                <!-- Station stats -->
                <div class="flex items-center gap-4 text-xs text-gray-400 ml-auto">
                  <span>{{ station.depots_count }} depots</span>
                  <span>{{ station.tanks_count }} tanks</span>
                  <span class="font-mono text-gray-600">{{ fmtL(station.total_capacity_liters) }} L cap.</span>
                  <FillBar :pct="station.fill_pct" class="w-20" />
                  <span class="font-mono w-10 text-right" :class="fillTextColor(station.fill_pct)">
                    {{ station.fill_pct }}%
                  </span>
                </div>
              </div>

              <!-- ── DEPOTS ──────────────────────────────────── -->
              <div v-show="openStations.has(station.id)" class="bg-gray-50 border-t border-gray-100">
                <div v-for="depot in station.depots" :key="depot.id"
                     class="border-b border-gray-100 last:border-0">

                  <!-- DEPOT ROW -->
                  <div
                    class="flex items-center gap-3 pl-12 pr-5 py-2.5 hover:bg-blue-50/40 cursor-pointer group"
                    @click="toggleDepot(depot.id)"
                  >
                    <i class="fas fa-chevron-right text-xs text-gray-300 transition-transform duration-200"
                       :class="openDepots.has(depot.id) ? 'rotate-90' : ''"></i>
                    <i class="fas fa-warehouse text-blue-400 text-xs"></i>

                    <!-- Depot name — inline edit -->
                    <div class="flex items-center gap-2 flex-1 min-w-0" @click.stop>
                      <template v-if="editing.type === 'depot' && editing.id === depot.id">
                        <input v-model="editing.name" class="text-sm border-b border-blue-400 focus:outline-none w-44" @keydown.enter="saveEditing" @keydown.escape="cancelEditing" autofocus />
                        <input v-model="editing.code" class="text-xs font-mono border-b border-blue-400 focus:outline-none w-20" @keydown.enter="saveEditing" @keydown.escape="cancelEditing" />
                        <button @click="saveEditing" class="text-green-600 hover:text-green-800 text-xs"><i class="fas fa-check"></i></button>
                        <button @click="cancelEditing" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                      </template>
                      <template v-else>
                        <span class="text-sm text-gray-800">{{ depot.name }}</span>
                        <span class="text-xs font-mono text-gray-400 bg-white px-1.5 py-0.5 rounded border border-gray-200">{{ depot.code }}</span>
                        <span v-if="depot.category" class="text-xs text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded">{{ depot.category }}</span>
                        <button @click.stop="startEdit('depot', depot)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-300 hover:text-blue-500 text-xs ml-1">
                          <i class="fas fa-pen"></i>
                        </button>
                      </template>
                    </div>

                    <!-- Depot stats -->
                    <div class="flex items-center gap-3 text-xs text-gray-400 ml-auto">
                      <span>{{ depot.tanks_count }} tanks</span>
                      <FillBar :pct="depot.fill_pct" class="w-16" />
                      <span class="font-mono w-10 text-right" :class="fillTextColor(depot.fill_pct)">
                        {{ depot.fill_pct }}%
                      </span>
                    </div>
                  </div>

                  <!-- ── TANKS ─────────────────────────────── -->
                  <div v-show="openDepots.has(depot.id)"
                       class="pl-16 pr-5 pb-3 pt-1">

                    <!-- Tanks mini-table -->
                    <table class="w-full text-xs">
                      <thead>
                        <tr class="text-gray-400 border-b border-gray-200">
                          <th class="text-left py-1.5 font-medium">Fuel</th>
                          <th class="text-right py-1.5 font-medium">Capacity (L)</th>
                          <th class="text-right py-1.5 font-medium">Current stock (L)</th>
                          <th class="py-1.5 w-32">Fill</th>
                          <th class="py-1.5 w-8"></th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <tr v-for="tank in depot.tanks" :key="tank.id"
                            class="group hover:bg-blue-50/30">
                          <!-- Fuel type -->
                          <td class="py-2">
                            <span class="font-mono bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-[10px]">{{ tank.fuel_type_code }}</span>
                            <span class="ml-1.5 text-gray-700">{{ tank.fuel_type_name }}</span>
                          </td>
                          <!-- Capacity — inline edit -->
                          <td class="text-right py-2">
                            <template v-if="editing.type === 'tank' && editing.id === tank.id">
                              <input v-model.number="editing.capacity" type="number" step="1000"
                                     class="w-28 text-right border-b border-blue-400 focus:outline-none text-xs"
                                     @keydown.escape="cancelEditing" />
                            </template>
                            <template v-else>
                              <span class="font-mono text-gray-600">{{ fmtL(tank.capacity_liters) }}</span>
                            </template>
                          </td>
                          <!-- Stock — inline edit -->
                          <td class="text-right py-2">
                            <template v-if="editing.type === 'tank' && editing.id === tank.id">
                              <input v-model.number="editing.stock" type="number" step="1000"
                                     class="w-28 text-right border-b border-blue-400 focus:outline-none text-xs"
                                     @keydown.enter="saveEditing"
                                     @keydown.escape="cancelEditing" />
                            </template>
                            <template v-else>
                              <span class="font-mono text-gray-800">{{ fmtL(tank.current_stock_liters) }}</span>
                            </template>
                          </td>
                          <!-- Fill bar -->
                          <td class="py-2">
                            <div class="flex items-center gap-1.5 px-2">
                              <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     :class="fillBarColor(tank.fill_pct)"
                                     :style="{ width: Math.min(tank.fill_pct, 100) + '%' }"></div>
                              </div>
                              <span class="w-9 text-right font-mono" :class="fillTextColor(tank.fill_pct)">
                                {{ tank.fill_pct }}%
                              </span>
                            </div>
                          </td>
                          <!-- Edit / Save buttons -->
                          <td class="py-2 text-right">
                            <template v-if="editing.type === 'tank' && editing.id === tank.id">
                              <button @click="saveEditing" class="text-green-600 hover:text-green-800 mr-1"><i class="fas fa-check text-xs"></i></button>
                              <button @click="cancelEditing" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xs"></i></button>
                            </template>
                            <template v-else>
                              <button @click="startEditTank(tank)"
                                      class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-300 hover:text-blue-500">
                                <i class="fas fa-pen text-xs"></i>
                              </button>
                            </template>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- Add tank button -->
                    <div class="mt-2">
                      <template v-if="addingTank === depot.id">
                        <div class="flex items-center gap-2 py-1">
                          <select v-model="newTank.fuel_type_id" class="text-xs border border-gray-200 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-300">
                            <option value="">— fuel type —</option>
                            <option v-for="ft in fuelTypes" :key="ft.id" :value="ft.id">{{ ft.name }}</option>
                          </select>
                          <input v-model.number="newTank.capacity_liters" type="number" placeholder="Capacity (L)"
                                 class="text-xs border border-gray-200 rounded px-2 py-1 w-32 focus:outline-none focus:ring-1 focus:ring-blue-300" />
                          <input v-model.number="newTank.current_stock_liters" type="number" placeholder="Current stock (L)"
                                 class="text-xs border border-gray-200 rounded px-2 py-1 w-32 focus:outline-none focus:ring-1 focus:ring-blue-300" />
                          <button @click="saveTankAdd(depot.id)" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Add</button>
                          <button @click="addingTank = null" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                        </div>
                      </template>
                      <template v-else>
                        <button @click="addingTank = depot.id; newTank = { fuel_type_id: '', capacity_liters: null, current_stock_liters: 0 }"
                                class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-1 py-1">
                          <i class="fas fa-plus text-[10px]"></i> Add tank
                        </button>
                      </template>
                    </div>

                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Save status toast ──────────────────────────────────── -->
    <transition name="fade">
      <div v-if="toast.show"
           class="fixed bottom-6 right-6 px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg flex items-center gap-2 z-50"
           :class="toast.ok ? 'bg-green-600 text-white' : 'bg-red-500 text-white'">
        <i :class="toast.ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        {{ toast.message }}
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { infrastructureApi, parametersApi } from '../services/api';

// ─── Sub-component: fill bar ──────────────────────────────────────────────────
const FillBar = {
  props: { pct: { type: Number, default: 0 } },
  setup(props) {
    const color = computed(() => {
      if (props.pct >= 80) return 'bg-green-500';
      if (props.pct >= 40) return 'bg-yellow-400';
      if (props.pct >= 20) return 'bg-orange-400';
      return 'bg-red-500';
    });
    return { color };
  },
  template: `
    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
      <div class="h-full rounded-full transition-all" :class="color" :style="{ width: Math.min(pct, 100) + '%' }"></div>
    </div>
  `,
};

// ─── State ───────────────────────────────────────────────────────────────────
const loading   = ref(false);
const regions   = ref([]);
const fuelTypes = ref([]);
const search    = ref('');

// Open/collapsed sets
const openRegions  = ref(new Set());
const openStations = ref(new Set());
const openDepots   = ref(new Set());

// Inline editing
const editing = ref({ type: null, id: null, name: '', code: '', category: '', capacity: 0, stock: 0 });
const cancelEditing = () => { editing.value = { type: null, id: null }; };

// Adding tank
const addingTank = ref(null);
const newTank    = ref({ fuel_type_id: '', capacity_liters: null, current_stock_liters: 0 });

// Toast
const toast = ref({ show: false, ok: true, message: '' });
let toastTimer = null;

// ─── Computed ────────────────────────────────────────────────────────────────
const totalStats = computed(() => {
  let s = 0, d = 0, t = 0;
  for (const r of regions.value) {
    s += r.stations_count;
    d += r.depots_count;
    t += r.tanks_count;
  }
  return { regions: regions.value.length, stations: s, depots: d, tanks: t };
});

const filteredRegions = computed(() => {
  if (!search.value.trim()) return regions.value;
  const q = search.value.toLowerCase();
  return regions.value.map(region => ({
    ...region,
    stations: region.stations
      .filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.code.toLowerCase().includes(q) ||
        s.depots.some(d => d.name.toLowerCase().includes(q) || d.code.toLowerCase().includes(q))
      )
  })).filter(r => r.stations.length > 0);
});

// ─── Toggle helpers ──────────────────────────────────────────────────────────
const toggleRegion  = (id) => { openRegions.value.has(id)  ? openRegions.value.delete(id)  : openRegions.value.add(id); };
const toggleStation = (id) => { openStations.value.has(id) ? openStations.value.delete(id) : openStations.value.add(id); };
const toggleDepot   = (id) => { openDepots.value.has(id)   ? openDepots.value.delete(id)   : openDepots.value.add(id); };

const expandAll = () => {
  regions.value.forEach(r => {
    openRegions.value.add(r.id);
    r.stations.forEach(s => {
      openStations.value.add(s.id);
      s.depots.forEach(d => openDepots.value.add(d.id));
    });
  });
};
const collapseAll = () => {
  openRegions.value  = new Set();
  openStations.value = new Set();
  openDepots.value   = new Set();
};

// ─── Inline edit ─────────────────────────────────────────────────────────────
const startEdit = (type, item) => {
  editing.value = { type, id: item.id, name: item.name, code: item.code, category: item.category ?? '' };
};
const startEditTank = (tank) => {
  editing.value = {
    type: 'tank', id: tank.id,
    capacity: parseFloat(tank.capacity_liters),
    stock:    parseFloat(tank.current_stock_liters),
  };
};

const saveEditing = async () => {
  const e = editing.value;
  try {
    if (e.type === 'station') {
      await infrastructureApi.updateStation(e.id, { name: e.name, code: e.code });
      // Update local state
      for (const r of regions.value) {
        const s = r.stations.find(x => x.id === e.id);
        if (s) { s.name = e.name; s.code = e.code; }
      }
    } else if (e.type === 'depot') {
      await infrastructureApi.updateDepot(e.id, { name: e.name, code: e.code, category: e.category });
      for (const r of regions.value) {
        for (const s of r.stations) {
          const d = s.depots.find(x => x.id === e.id);
          if (d) { d.name = e.name; d.code = e.code; d.category = e.category; }
        }
      }
    } else if (e.type === 'tank') {
      await infrastructureApi.updateTank(e.id, {
        capacity_liters: e.capacity,
        current_stock_liters: e.stock,
      });
      // Update tank + recalc fill_pct
      for (const r of regions.value) {
        for (const s of r.stations) {
          for (const d of s.depots) {
            const t = d.tanks.find(x => x.id === e.id);
            if (t) {
              t.capacity_liters     = e.capacity;
              t.current_stock_liters = e.stock;
              t.fill_pct = e.capacity > 0 ? Math.round(e.stock / e.capacity * 1000) / 10 : 0;
            }
          }
        }
      }
    }
    showToast(true, 'Saved');
    cancelEditing();
  } catch (err) {
    showToast(false, err?.response?.data?.error ?? 'Save error');
  }
};

// ─── Add tank ─────────────────────────────────────────────────────────────────
const saveTankAdd = async (depotId) => {
  const t = newTank.value;
  if (!t.fuel_type_id || !t.capacity_liters) {
    showToast(false, 'Specify fuel type and capacity');
    return;
  }
  try {
    const res = await infrastructureApi.addTank({
      depot_id: depotId,
      fuel_type_id: t.fuel_type_id,
      capacity_liters: t.capacity_liters,
      current_stock_liters: t.current_stock_liters ?? 0,
    });
    // Reload to get updated hierarchy (simplest approach)
    await loadData();
    addingTank.value = null;
    // Re-open the depot we were editing
    openDepots.value.add(depotId);
    showToast(true, 'Tank added');
  } catch (err) {
    showToast(false, err?.response?.data?.error ?? 'Add error');
  }
};

// ─── Load ────────────────────────────────────────────────────────────────────
const loadData = async () => {
  loading.value = true;
  try {
    const [hierarchyRes, ftRes] = await Promise.all([
      infrastructureApi.getHierarchy(),
      parametersApi.getFuelTypes(),
    ]);
    if (hierarchyRes.data.success) {
      regions.value = hierarchyRes.data.data;
      // Auto-expand regions by default
      regions.value.forEach(r => openRegions.value.add(r.id));
    }
    if (ftRes.data.success) {
      fuelTypes.value = ftRes.data.data;
    }
  } catch (e) {
    console.error('Infrastructure load error:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

// ─── Helpers ─────────────────────────────────────────────────────────────────
const fmtL = (n) => n ? Math.round(parseFloat(n)).toLocaleString('en') : '0';

const fillTextColor = (pct) => {
  if (pct >= 80) return 'text-green-600';
  if (pct >= 40) return 'text-yellow-600';
  if (pct >= 20) return 'text-orange-500';
  return 'text-red-600';
};
const fillBarColor = (pct) => {
  if (pct >= 80) return 'bg-green-500';
  if (pct >= 40) return 'bg-yellow-400';
  if (pct >= 20) return 'bg-orange-400';
  return 'bg-red-500';
};

const showToast = (ok, message) => {
  toast.value = { show: true, ok, message };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toast.value.show = false; }, 2800);
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
