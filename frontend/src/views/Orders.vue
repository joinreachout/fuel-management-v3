<template>
  <div>
    <!-- ============================================================ -->
    <!--  MAIN UI (hidden during print)                               -->
    <!-- ============================================================ -->
    <div class="no-print min-h-screen bg-gray-50">

      <!-- Fixed Black Top Bar -->
      <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
        <div class="flex items-center gap-8">
          <img src="/kkt_logo.png" alt="Kitty Kat Technologies" class="h-12 w-auto" style="filter: brightness(0) invert(1);">
          <nav class="flex items-center gap-6">
            <router-link to="/" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</router-link>
            <router-link to="/orders" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Orders</router-link>
            <router-link to="/transfers" class="text-gray-400 hover:text-white transition-colors text-sm">Transfers</router-link>
            <router-link to="/parameters" class="text-gray-400 hover:text-white transition-colors text-sm">Parameters</router-link>
            <router-link to="/import" class="text-gray-400 hover:text-white transition-colors text-sm">Import</router-link>
            <router-link to="/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</router-link>
          </nav>
        </div>
      </div>

      <!-- Spacer (black to merge seamlessly with hero header) -->
      <div class="h-20 bg-black"></div>

      <!-- ── DARK HERO HEADER (REV 2.0 style: 3 rows) ── -->
      <header class="bg-black relative">
        <!-- Truck Background Image - Right Side with Gradient Fade -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
          <div class="absolute right-0 top-0 bottom-0 w-2/3" style="
            background-image: linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0.7) 15%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0) 60%), url('/rev3/truck_header.jpg');
            background-size: auto 100%;
            background-position: center right;
            background-repeat: no-repeat;
            opacity: 0.85;
          "></div>
        </div>

        <!-- Header Content -->
        <div class="relative px-8 py-3 pb-24">

          <!-- ROW 1: Title + KPI Row 1 (4 metrics) -->
          <div class="flex items-start justify-between mb-2 mt-6">
            <div>
              <h1 class="text-2xl font-bold text-white mb-1">
                <i class="fas fa-file-alt mr-3 text-gray-400"></i>Orders
              </h1>
              <p class="text-sm text-gray-400">Orders management and tracking system</p>
            </div>
            <div class="flex items-center gap-10 pt-1">
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-white">{{ kpiTotalStations }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Total</div>
                  <div class="text-white text-xs font-semibold">Stations</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold" :class="kpiShortages > 0 ? 'text-red-400' : 'text-white'">{{ kpiShortages }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Shortages</div>
                  <div class="text-white text-xs font-semibold">Predicted</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold" :class="kpiShortages > 0 ? 'text-red-400' : 'text-white'">{{ kpiShortages }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Critical</div>
                  <div class="text-white text-xs font-semibold">Stations</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-white">{{ kpiLowStock }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Low Stock</div>
                  <div class="text-white text-xs font-semibold">Stations</div>
                </div>
              </div>
            </div>
          </div>

          <!-- ROW 2: KPI Row 2 (right-aligned) -->
          <div class="flex justify-end mb-4">
            <div class="flex items-center gap-10">
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold" :class="kpiMandatory > 0 ? 'text-orange-400' : 'text-white'">{{ kpiMandatory }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Mandatory</div>
                  <div class="text-white text-xs font-semibold">Orders</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-white">{{ kpiRecommended }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Recommended</div>
                  <div class="text-white text-xs font-semibold">Orders</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-white">{{ poPlanned + erpInTransit }}</div>
                <div class="h-8 w-0.5 bg-white/40"></div>
                <div class="flex flex-col leading-tight">
                  <div class="text-white text-xs font-semibold">Active</div>
                  <div class="text-white text-xs font-semibold">Orders</div>
                </div>
              </div>
            </div>
          </div>

          <!-- ROW 3: Info chips at bottom of header -->
          <div class="flex items-center gap-4 pb-3">
            <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
              <i class="far fa-clock text-gray-400"></i>
              <span class="font-medium text-gray-300">{{ currentDateTime }}</span>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
              <i class="fas fa-circle text-blue-400" style="font-size:8px"></i>
              <span class="font-medium text-gray-300">{{ poPlanned }} pending orders</span>
            </div>
          </div>

        </div>
      </header>

      <!-- Page Content — ONE BIG WHITE CARD overlapping hero header -->
      <div class="relative -mt-16 z-10">
        <div class="max-w-7xl mx-auto px-6 pt-0 pb-10">

          <!-- ══ BIG WHITE CARD ══ -->
          <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

          <!-- ── STATS BAR ── -->
          <div class="px-5 py-3 border-b border-gray-200 flex flex-wrap items-center gap-y-2 text-sm">
          <!-- PO group label -->
          <span class="flex items-center text-xs font-semibold text-gray-400 uppercase tracking-wider mr-3">
            <i class="fas fa-file-alt mr-1.5"></i>Purchase Orders
          </span>
          <!-- PO stats -->
          <div class="flex items-center gap-4 mr-4">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-gray-400"></span>
              <span class="font-bold text-gray-800">{{ poPlanned }}</span>
              <span class="text-gray-500">Planned</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              <span class="font-bold text-gray-800">{{ poMatched }}</span>
              <span class="text-gray-500">Matched</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-orange-400"></span>
              <span class="font-bold text-gray-800">{{ poExpired }}</span>
              <span class="text-gray-500">Expired</span>
            </span>
          </div>
          <!-- divider -->
          <div class="w-px h-5 bg-gray-200 mx-2 hidden sm:block"></div>
          <!-- ERP group label -->
          <span class="flex items-center text-xs font-semibold text-gray-400 uppercase tracking-wider mr-3">
            <i class="fas fa-truck mr-1.5"></i>ERP Deliveries
          </span>
          <!-- ERP stats -->
          <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-sky-400"></span>
              <span class="font-bold text-gray-800">{{ erpConfirmed }}</span>
              <span class="text-gray-500">Confirmed</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-amber-400"></span>
              <span class="font-bold text-gray-800">{{ erpInTransit }}</span>
              <span class="text-gray-500">In Transit</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-green-500"></span>
              <span class="font-bold text-gray-800">{{ erpDelivered }}</span>
              <span class="text-gray-500">Delivered</span>
            </span>
          </div>
        </div>

        <!-- ── TABS + ACTION BUTTON ── -->
        <div class="flex items-center justify-between border-b border-gray-200 pr-4">
          <div class="flex gap-0">
            <button
              @click="switchTab('purchase_orders')"
              :class="activeTab === 'purchase_orders'
                ? 'border-b-2 border-black text-gray-900 font-semibold'
                : 'text-gray-500 hover:text-gray-700'"
              class="px-5 py-3 text-sm transition-colors focus:outline-none whitespace-nowrap">
              <i class="fas fa-file-alt mr-2"></i>
              Purchase Orders
              <span :class="activeTab === 'purchase_orders' ? 'bg-black text-white' : 'bg-gray-200 text-gray-600'"
                class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium">
                {{ poOrders.length }}
              </span>
            </button>
            <button
              @click="switchTab('erp_deliveries')"
              :class="activeTab === 'erp_deliveries'
                ? 'border-b-2 border-black text-gray-900 font-semibold'
                : 'text-gray-500 hover:text-gray-700'"
              class="px-5 py-3 text-sm transition-colors focus:outline-none whitespace-nowrap">
              <i class="fas fa-truck mr-2"></i>
              ERP Deliveries
              <span :class="activeTab === 'erp_deliveries' ? 'bg-black text-white' : 'bg-gray-200 text-gray-600'"
                class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium">
                {{ erpOrders.length }}
              </span>
            </button>
          </div>
          <!-- Action button — moves with tab, sits at tab bar level -->
          <button v-if="activeTab === 'purchase_orders'"
            @click="openCreateModal"
            class="flex items-center gap-2 px-5 py-2 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition-colors text-sm shadow-sm">
            <i class="fas fa-plus"></i>
            New PO
          </button>
          <button v-if="activeTab === 'erp_deliveries'"
            @click="openCreateErpModal"
            class="flex items-center gap-2 px-5 py-2 bg-orange-600 text-white rounded-xl font-medium hover:bg-orange-700 transition-colors text-sm shadow-sm">
            <i class="fas fa-plus"></i>
            Manual Entry
          </button>
        </div>

        <!-- ── FILTERS BAR ── -->
        <div class="border-b border-gray-100 px-4 py-3 flex flex-wrap items-center gap-3">
          <select v-model="activeFilters.station_id" @change="loadActiveTab"
            class="text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="">All Stations</option>
            <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>

          <select v-model="activeFilters.fuel_type_id" @change="loadActiveTab"
            class="text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="">All Fuel Types</option>
            <option v-for="f in fuelTypes" :key="f.id" :value="f.id">{{ f.name }}</option>
          </select>

          <!-- Status filter — different options per tab -->
          <select v-model="activeFilters.status" @change="loadActiveTab"
            class="text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="">All Statuses</option>
            <template v-if="activeTab === 'purchase_orders'">
              <option value="planned">Planned</option>
              <option value="matched">Matched (ERP confirmed)</option>
              <option value="expired">Expired</option>
              <option value="cancelled">Cancelled</option>
            </template>
            <template v-else>
              <option value="confirmed">Confirmed</option>
              <option value="in_transit">In Transit</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </template>
          </select>

          <input type="date" v-model="activeFilters.date_from" @change="loadActiveTab"
            class="text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            title="Delivery date from">

          <input type="date" v-model="activeFilters.date_to" @change="loadActiveTab"
            class="text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            title="Delivery date to">

          <button @click="clearFilters"
            class="text-sm text-gray-500 hover:text-gray-800 px-2 py-2 rounded-lg transition-colors">
            <i class="fas fa-times mr-1"></i>Clear
          </button>

          <div class="ml-auto text-sm text-gray-500">
            {{ currentOrders.length }} record{{ currentOrders.length !== 1 ? 's' : '' }}
          </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!--  TAB 1: PURCHASE ORDERS                                       -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'purchase_orders'">

          <div v-if="loadingPO" class="flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mr-3"></div>
            <span class="text-gray-500 text-sm">Loading purchase orders...</span>
          </div>

          <div v-else-if="poOrders.length === 0" class="text-center py-16 text-gray-400">
            <i class="fas fa-file-alt text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">No purchase orders found</p>
            <p class="text-sm mt-1">Adjust filters or create a new purchase order</p>
            <button @click="openCreateModal"
              class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-colors">
              <i class="fas fa-plus"></i> New PO
            </button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th @click="toggleSort(poSort, 'id')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    PO # <i :class="sortIconClass(poSort, 'id')"></i>
                  </th>
                  <th @click="toggleSort(poSort, 'station_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Station <i :class="sortIconClass(poSort, 'station_name')"></i>
                  </th>
                  <th @click="toggleSort(poSort, 'fuel_type_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Fuel Type <i :class="sortIconClass(poSort, 'fuel_type_name')"></i>
                  </th>
                  <th @click="toggleSort(poSort, 'quantity_liters')" class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Qty (L) <i :class="sortIconClass(poSort, 'quantity_liters')"></i>
                  </th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (T)</th>
                  <th @click="toggleSort(poSort, 'supplier_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Supplier <i :class="sortIconClass(poSort, 'supplier_name')"></i>
                  </th>
                  <th @click="toggleSort(poSort, 'delivery_date')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Delivery Date <i :class="sortIconClass(poSort, 'delivery_date')"></i>
                  </th>
                  <th @click="toggleSort(poSort, 'status')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Status <i :class="sortIconClass(poSort, 'status')"></i>
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="order in sortedPoOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">

                  <td class="px-5 py-3.5 font-mono text-gray-700 font-medium whitespace-nowrap">
                    {{ order.order_number }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-800 whitespace-nowrap">
                    {{ order.station_name || '—' }}
                  </td>

                  <td class="px-5 py-3.5 whitespace-nowrap">
                    <span class="text-gray-800">{{ order.fuel_type_name }}</span>
                    <span class="text-gray-400 ml-1 text-xs">({{ order.fuel_type_code }})</span>
                  </td>

                  <td class="px-5 py-3.5 text-right font-medium text-gray-700 whitespace-nowrap">
                    {{ formatNum(order.quantity_liters) }}
                  </td>

                  <td class="px-5 py-3.5 text-right text-gray-500 whitespace-nowrap">
                    {{ order.quantity_tons }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">
                    {{ order.supplier_name || '—' }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">
                    {{ formatDate(order.delivery_date) }}
                  </td>

                  <!-- Status badge -->
                  <td class="px-5 py-3.5">
                    <span :class="statusBadgeClass(order.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">
                      {{ statusLabel(order.status) }}
                    </span>
                    <!-- Matched: show linked ERP order reference -->
                    <div v-if="order.status === 'matched' && order.erp_order_id"
                      class="mt-1 text-xs text-blue-500">
                      ERP #{{ order.erp_order_id }}
                    </div>
                    <!-- Cancelled reason -->
                    <div v-if="order.status === 'cancelled' && order.cancelled_reason"
                      class="mt-1 text-xs text-red-500 italic max-w-[180px] truncate cursor-help"
                      :title="order.cancelled_reason">
                      {{ order.cancelled_reason }}
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="px-5 py-3.5">
                    <div class="flex items-center gap-0.5">
                      <button v-if="!['cancelled','delivered'].includes(order.status)"
                        @click="openEditModal(order)"
                        class="w-7 h-7 inline-flex items-center justify-center text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                        title="Edit order">
                        <i class="fas fa-pencil-alt" style="font-size:11px"></i>
                      </button>
                      <button @click="printPO(order)"
                        class="w-7 h-7 inline-flex items-center justify-center text-gray-400 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Print PO">
                        <i class="fas fa-print" style="font-size:11px"></i>
                      </button>
                      <button @click="downloadPoPdf(order)" :disabled="pdfGenerating"
                        class="w-7 h-7 inline-flex items-center justify-center text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors disabled:opacity-40"
                        title="Download PDF">
                        <i :class="pdfGenerating ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'" style="font-size:11px"></i>
                      </button>
                      <button v-if="order.status === 'planned'"
                        @click="openCancelModal(order)"
                        class="w-7 h-7 inline-flex items-center justify-center text-red-400 hover:bg-red-50 rounded-lg transition-colors"
                        title="Cancel this PO">
                        <i class="fas fa-times" style="font-size:11px"></i>
                      </button>
                    </div>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- /Purchase Orders tab -->

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!--  TAB 2: ERP DELIVERIES (read-only)                            -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div v-else>

          <!-- Info banner: ERP orders are read-only -->
          <div class="bg-blue-50 border-b border-blue-100 px-5 py-2.5 text-xs text-blue-700 flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            ERP orders are imported from the ERP system (erp.kittykat.tech) and are read-only.
            Only ERP deliveries contribute to the Forecast chart.
          </div>

          <div v-if="loadingERP" class="flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mr-3"></div>
            <span class="text-gray-500 text-sm">Loading ERP deliveries...</span>
          </div>

          <div v-else-if="erpOrders.length === 0" class="text-center py-16 text-gray-400">
            <i class="fas fa-truck text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">No ERP deliveries found</p>
            <p class="text-sm mt-1">ERP orders are imported via the Import module or added manually</p>
            <button @click="openCreateErpModal"
              class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-medium text-sm hover:bg-orange-700 transition-colors">
              <i class="fas fa-plus"></i> Manual Entry
            </button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th @click="toggleSort(erpSort, 'id')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Order # <i :class="sortIconClass(erpSort, 'id')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'station_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Station <i :class="sortIconClass(erpSort, 'station_name')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'fuel_type_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Fuel Type <i :class="sortIconClass(erpSort, 'fuel_type_name')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'quantity_liters')" class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Qty (L) <i :class="sortIconClass(erpSort, 'quantity_liters')"></i>
                  </th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (T)</th>
                  <th @click="toggleSort(erpSort, 'price_per_ton')" class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Price / T <i :class="sortIconClass(erpSort, 'price_per_ton')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'supplier_name')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Supplier <i :class="sortIconClass(erpSort, 'supplier_name')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'delivery_date')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Delivery Date <i :class="sortIconClass(erpSort, 'delivery_date')"></i>
                  </th>
                  <th @click="toggleSort(erpSort, 'status')" class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none whitespace-nowrap">
                    Status <i :class="sortIconClass(erpSort, 'status')"></i>
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Matched PO</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="order in sortedErpOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">

                  <td class="px-5 py-3.5 font-mono text-gray-700 font-medium whitespace-nowrap">
                    {{ order.order_number }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-800 whitespace-nowrap">
                    {{ order.station_name || '—' }}
                  </td>

                  <td class="px-5 py-3.5 whitespace-nowrap">
                    <span class="text-gray-800">{{ order.fuel_type_name }}</span>
                    <span class="text-gray-400 ml-1 text-xs">({{ order.fuel_type_code }})</span>
                  </td>

                  <td class="px-5 py-3.5 text-right font-medium text-gray-700 whitespace-nowrap">
                    {{ formatNum(order.quantity_liters) }}
                  </td>

                  <td class="px-5 py-3.5 text-right text-gray-500 whitespace-nowrap">
                    {{ order.quantity_tons }}
                  </td>

                  <td class="px-5 py-3.5 text-right text-gray-600 whitespace-nowrap">
                    {{ order.price_per_ton ? '$' + formatNum(order.price_per_ton) : '—' }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">
                    {{ order.supplier_name || '—' }}
                  </td>

                  <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">
                    {{ formatDate(order.delivery_date) }}
                  </td>

                  <td class="px-5 py-3.5">
                    <span :class="statusBadgeClass(order.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">
                      {{ statusLabel(order.status) }}
                    </span>
                  </td>

                  <!-- Matched PO column -->
                  <td class="px-5 py-3.5 text-xs text-gray-500">
                    <span v-if="order.erp_order_id" class="text-blue-600 font-medium">
                      Linked
                    </span>
                    <span v-else class="text-gray-300">—</span>
                  </td>

                  <!-- ERP Actions -->
                  <td class="px-5 py-3.5">
                    <button v-if="order.status !== 'cancelled'"
                      @click="openEditModal(order)"
                      class="w-7 h-7 inline-flex items-center justify-center text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Edit / advance status">
                      <i class="fas fa-pencil-alt" style="font-size:11px"></i>
                    </button>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- /ERP Deliveries tab -->

          </div>
          <!-- /big white card -->
        </div>
      </div>
      <!-- /page content -->

      <!-- ============================================================ -->
      <!--  CREATE PO MODAL                                             -->
      <!-- ============================================================ -->
      <Teleport to="body">
        <div v-if="showCreateModal"
          class="fixed inset-0 z-[100] flex items-center justify-center"
          @click.self="showCreateModal = false">
          <div class="absolute inset-0 bg-black bg-opacity-50"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[92vh] overflow-y-auto">
            <div class="p-8">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                  <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                  New Purchase Order
                </h2>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-xl">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <form @submit.prevent="submitCreate">
                <div class="space-y-4">

                  <!-- Station -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Station <span class="text-red-500">*</span>
                    </label>
                    <select v-model="form.station_id" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select station...</option>
                      <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </div>

                  <!-- Fuel Type -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Fuel Type <span class="text-red-500">*</span>
                    </label>
                    <select v-model="form.fuel_type_id" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select fuel type...</option>
                      <option v-for="f in fuelTypes" :key="f.id" :value="f.id">
                        {{ f.name }} ({{ f.code }})
                      </option>
                    </select>
                  </div>

                  <!-- Supplier -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select v-model="form.supplier_id"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select supplier...</option>
                      <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <!-- Delivery info auto-pulled from supplier offers -->
                    <div v-if="selectedSupplierOffer" class="mt-2 flex items-center gap-3 px-3 py-2 bg-blue-50 rounded-lg border border-blue-100 text-xs text-blue-700">
                      <i class="fas fa-shipping-fast text-blue-400"></i>
                      <span>Delivery: <strong>{{ selectedSupplierOffer.delivery_days }} days</strong> → date auto-filled</span>
                      <span v-if="selectedSupplierOffer.price_per_ton" class="ml-auto text-blue-600 font-medium">
                        Price: ${{ selectedSupplierOffer.price_per_ton }}/ton ✓
                      </span>
                    </div>
                  </div>

                  <!-- Quantity (TONS) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Quantity (tons) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" v-model.number="form.quantity_tons" min="0.1" step="0.1" required
                      placeholder="e.g. 38"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="form.quantity_tons && selectedFuelDensity" class="mt-1 text-xs text-gray-500">
                      ≈ {{ Math.round(form.quantity_tons * 1000 / selectedFuelDensity).toLocaleString() }} liters
                    </p>
                  </div>

                  <!-- Price per ton -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price per ton (USD)</label>
                    <input type="number" v-model.number="form.price_per_ton" min="0" step="0.01"
                      placeholder="e.g. 850"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="form.quantity_tons && form.price_per_ton" class="mt-1 text-xs text-gray-500">
                      Total ≈ ${{ (form.quantity_tons * form.price_per_ton).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                    </p>
                  </div>

                  <!-- Delivery Date -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Delivery Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" v-model="form.delivery_date" required :min="today"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                  </div>

                  <!-- Notes -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea v-model="form.notes" rows="2"
                      placeholder="Optional notes..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">
                    </textarea>
                  </div>

                </div>

                <!-- Actions -->
                <div class="flex gap-3 mt-6">
                  <button type="submit" :disabled="formSubmitting"
                    class="flex-1 py-2.5 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-plus mr-2"></i>
                    {{ formSubmitting ? 'Creating...' : 'Create PO' }}
                  </button>
                  <button type="button" @click="showCreateModal = false"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium text-sm hover:bg-gray-50 transition-colors">
                    Cancel
                  </button>
                </div>

                <p v-if="createError" class="mt-3 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2.5">
                  <i class="fas fa-exclamation-circle mr-1"></i>{{ createError }}
                </p>
              </form>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ============================================================ -->
      <!--  CANCEL MODAL                                                -->
      <!-- ============================================================ -->
      <Teleport to="body">
        <div v-if="showCancelModal"
          class="fixed inset-0 z-[100] flex items-center justify-center"
          @click.self="showCancelModal = false">
          <div class="absolute inset-0 bg-black bg-opacity-50"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
            <div class="p-8">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">
                  <i class="fas fa-ban text-red-500 mr-2"></i>
                  Cancel Purchase Order
                </h2>
                <button @click="showCancelModal = false" class="text-gray-400 hover:text-gray-600 text-xl">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <!-- Order summary -->
              <div class="bg-gray-50 rounded-xl px-4 py-3 mb-5 text-sm text-gray-700">
                <div class="font-semibold text-gray-900 mb-1">{{ selectedOrder?.order_number }}</div>
                <div>{{ selectedOrder?.station_name }} — {{ selectedOrder?.fuel_type_name }}</div>
                <div>Quantity: <span class="font-medium">{{ formatNum(selectedOrder?.quantity_liters) }} L</span></div>
                <div>Delivery: <span class="font-medium">{{ formatDate(selectedOrder?.delivery_date) }}</span></div>
              </div>

              <!-- Reason input -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Reason for cancellation <span class="text-red-500">*</span>
                </label>
                <textarea v-model="cancelReason" rows="3"
                  placeholder="e.g. Wrong quantity entered, wrong station selected..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:outline-none resize-none">
                </textarea>
              </div>

              <!-- Note -->
              <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5 text-xs text-amber-700 flex items-start gap-2">
                <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
                <span>Purchase Orders do not affect the forecast chart. Cancellation is for error correction only — if the ERP already confirmed this order, cancel via ERP.</span>
              </div>

              <!-- Actions -->
              <div class="flex gap-3">
                <button @click="submitCancel"
                  :disabled="!cancelReason.trim() || cancelSubmitting"
                  class="flex-1 py-2.5 bg-red-600 text-white rounded-xl font-medium text-sm hover:bg-red-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                  {{ cancelSubmitting ? 'Cancelling...' : 'Cancel Order' }}
                </button>
                <button @click="showCancelModal = false"
                  class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium text-sm hover:bg-gray-50 transition-colors">
                  Go Back
                </button>
              </div>

              <p v-if="cancelError" class="mt-3 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2.5">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ cancelError }}
              </p>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ============================================================ -->
      <!--  MANUAL ERP ORDER MODAL                                      -->
      <!-- ============================================================ -->
      <Teleport to="body">
        <div v-if="showCreateErpModal"
          class="fixed inset-0 z-[100] flex items-center justify-center"
          @click.self="showCreateErpModal = false">
          <div class="absolute inset-0 bg-black bg-opacity-50"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[92vh] overflow-y-auto">
            <div class="p-8">
              <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-gray-900">
                  <i class="fas fa-truck text-orange-500 mr-2"></i>
                  Manual ERP Entry
                </h2>
                <button @click="showCreateErpModal = false" class="text-gray-400 hover:text-gray-600 text-xl">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <!-- Warning banner -->
              <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5 text-xs text-amber-800 flex items-start gap-2">
                <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0 text-amber-500"></i>
                <span>
                  <strong>Fallback mode.</strong> Use this when the ERP system is unavailable.
                  This order will appear as a <strong>delivery bump on the Forecast chart</strong>.
                </span>
              </div>

              <form @submit.prevent="submitCreateErp">
                <div class="space-y-4">

                  <!-- Station -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Station <span class="text-red-500">*</span>
                    </label>
                    <select v-model="erpForm.station_id" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                      <option value="">Select station...</option>
                      <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </div>

                  <!-- Fuel Type -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Fuel Type <span class="text-red-500">*</span>
                    </label>
                    <select v-model="erpForm.fuel_type_id" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                      <option value="">Select fuel type...</option>
                      <option v-for="f in fuelTypes" :key="f.id" :value="f.id">
                        {{ f.name }} ({{ f.code }})
                      </option>
                    </select>
                  </div>

                  <!-- Supplier -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select v-model="erpForm.supplier_id"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                      <option value="">Select supplier...</option>
                      <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <!-- Delivery info auto-pulled from supplier offers -->
                    <div v-if="selectedErpSupplierOffer" class="mt-2 flex items-center gap-3 px-3 py-2 bg-amber-50 rounded-lg border border-amber-100 text-xs text-amber-700">
                      <i class="fas fa-shipping-fast text-amber-400"></i>
                      <span>Delivery: <strong>{{ selectedErpSupplierOffer.delivery_days }} days</strong> → date auto-filled</span>
                      <span v-if="selectedErpSupplierOffer.price_per_ton" class="ml-auto text-amber-600 font-medium">
                        Price: ${{ selectedErpSupplierOffer.price_per_ton }}/ton ✓
                      </span>
                    </div>
                  </div>

                  <!-- Quantity (TONS) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Quantity (tons) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" v-model.number="erpForm.quantity_tons" min="0.1" step="0.1" required
                      placeholder="e.g. 38"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                    <p v-if="erpForm.quantity_tons && selectedErpFuelDensity" class="mt-1 text-xs text-gray-500">
                      ≈ {{ Math.round(erpForm.quantity_tons * 1000 / selectedErpFuelDensity).toLocaleString() }} liters
                    </p>
                  </div>

                  <!-- Price per ton -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price per ton (USD)</label>
                    <input type="number" v-model.number="erpForm.price_per_ton" min="0" step="0.01"
                      placeholder="e.g. 850"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                    <p v-if="erpForm.quantity_tons && erpForm.price_per_ton" class="mt-1 text-xs text-gray-500">
                      Total ≈ ${{ (erpForm.quantity_tons * erpForm.price_per_ton).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                    </p>
                  </div>

                  <!-- Delivery Date -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Delivery Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" v-model="erpForm.delivery_date" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                  </div>

                  <!-- Status -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="erpForm.status"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                      <option value="confirmed">Confirmed (shipment confirmed, not yet departed)</option>
                      <option value="in_transit">In Transit (truck dispatched, en route)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Both statuses appear as bumps on the Forecast chart</p>
                  </div>

                  <!-- Notes -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea v-model="erpForm.notes" rows="2"
                      placeholder="e.g. Entered manually — ERP system unavailable on 2026-02-23"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none resize-none">
                    </textarea>
                  </div>

                </div>

                <!-- Actions -->
                <div class="flex gap-3 mt-6">
                  <button type="submit" :disabled="erpFormSubmitting"
                    class="flex-1 py-2.5 bg-orange-600 text-white rounded-xl font-medium text-sm hover:bg-orange-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-plus mr-2"></i>
                    {{ erpFormSubmitting ? 'Creating...' : 'Create ERP Entry' }}
                  </button>
                  <button type="button" @click="showCreateErpModal = false"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium text-sm hover:bg-gray-50 transition-colors">
                    Cancel
                  </button>
                </div>

                <p v-if="createErpError" class="mt-3 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2.5">
                  <i class="fas fa-exclamation-circle mr-1"></i>{{ createErpError }}
                </p>
              </form>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ============================================================ -->
      <!--  EDIT ORDER MODAL (PO + ERP)                              -->
      <!-- ============================================================ -->
      <Teleport to="body">
        <div v-if="showEditModal"
          class="fixed inset-0 z-[100] flex items-center justify-center"
          @click.self="showEditModal = false">
          <div class="absolute inset-0 bg-black bg-opacity-50"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[92vh] overflow-y-auto">
            <div class="p-8">

              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-gray-900">
                  <i :class="editingOrder?.order_type === 'erp_order' ? 'fas fa-truck text-orange-500' : 'fas fa-file-alt text-blue-500'" class="mr-2"></i>
                  Edit Order
                  <span class="ml-2 text-sm font-normal text-gray-400 font-mono">{{ editingOrder?.order_number }}</span>
                </h2>
                <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 text-xl">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <form @submit.prevent="submitEdit">
                <div class="space-y-4">

                  <!-- Status (context-aware) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="editForm.status"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <template v-if="editingOrder?.order_type === 'purchase_order'">
                        <option value="planned">Planned</option>
                        <option value="matched">Matched (ERP confirmed)</option>
                        <option value="expired">Expired</option>
                      </template>
                      <template v-else>
                        <option value="confirmed">Confirmed (awaiting dispatch)</option>
                        <option value="in_transit">In Transit (en route)</option>
                        <option value="delivered">Delivered</option>
                      </template>
                    </select>
                  </div>

                  <!-- Station -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Station</label>
                    <select v-model="editForm.station_id"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select station...</option>
                      <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </div>

                  <!-- Fuel Type -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                    <select v-model="editForm.fuel_type_id"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select fuel type...</option>
                      <option v-for="f in fuelTypes" :key="f.id" :value="f.id">{{ f.name }} ({{ f.code }})</option>
                    </select>
                  </div>

                  <!-- Supplier -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select v-model="editForm.supplier_id"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                      <option value="">Select supplier...</option>
                      <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </div>

                  <!-- Quantity (tons) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (tons)</label>
                    <input type="number" v-model.number="editForm.quantity_tons" min="0.1" step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="editForm.quantity_tons && selectedEditFuelDensity" class="mt-1 text-xs text-gray-500">
                      ≈ {{ Math.round(editForm.quantity_tons * 1000 / selectedEditFuelDensity).toLocaleString() }} liters
                    </p>
                  </div>

                  <!-- Price per ton -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price per ton (USD)</label>
                    <input type="number" v-model.number="editForm.price_per_ton" min="0" step="0.01"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="editForm.quantity_tons && editForm.price_per_ton" class="mt-1 text-xs text-gray-500">
                      Total ≈ ${{ (editForm.quantity_tons * editForm.price_per_ton).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                    </p>
                  </div>

                  <!-- Delivery Date -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                    <input type="date" v-model="editForm.delivery_date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                  </div>

                  <!-- Notes -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea v-model="editForm.notes" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                  </div>

                </div>

                <!-- Actions -->
                <div class="flex gap-3 mt-6">
                  <button type="submit" :disabled="editSubmitting"
                    class="flex-1 py-2.5 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i>
                    {{ editSubmitting ? 'Saving...' : 'Save Changes' }}
                  </button>
                  <button type="button" @click="showEditModal = false"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium text-sm hover:bg-gray-50 transition-colors">
                    Cancel
                  </button>
                </div>

                <p v-if="editError" class="mt-3 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2.5">
                  <i class="fas fa-exclamation-circle mr-1"></i>{{ editError }}
                </p>
              </form>

            </div>
          </div>
        </div>
      </Teleport>

    </div>
    <!-- /main UI -->

    <!-- ============================================================ -->
    <!--  PRINT SECTION — visible only in @media print                -->
    <!-- ============================================================ -->
    <div id="print-po" v-if="printOrder">

      <!-- Company header -->
      <div class="po-header">
        <div class="po-company">KITTY KAT TECHNOLOGIES</div>
        <div class="po-rule"></div>
        <div class="po-title">FUEL PURCHASE ORDER</div>
        <div class="po-number">No. {{ printOrder.order_number }}</div>
        <div class="po-rule"></div>
      </div>

      <!-- Meta info -->
      <table class="po-meta">
        <tr>
          <td class="po-meta-label">Date:</td>
          <td class="po-meta-value">{{ formatDate(printOrder.order_date) }}</td>
          <td class="po-meta-label">Delivery Date:</td>
          <td class="po-meta-value">{{ formatDate(printOrder.delivery_date) }}</td>
        </tr>
        <tr>
          <td class="po-meta-label">Station:</td>
          <td class="po-meta-value">{{ printOrder.station_name }}</td>
          <td class="po-meta-label">Supplier:</td>
          <td class="po-meta-value">{{ printOrder.supplier_name || '—' }}</td>
        </tr>
        <tr v-if="printOrder.depot_name">
          <td class="po-meta-label">Depot:</td>
          <td class="po-meta-value" colspan="3">{{ printOrder.depot_name }}</td>
        </tr>
      </table>

      <!-- Line items -->
      <table class="po-table">
        <thead>
          <tr>
            <th>Fuel Type</th>
            <th>Quantity (L)</th>
            <th>Quantity (T)</th>
            <th>Price / T (USD)</th>
            <th>Total (USD)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ printOrder.fuel_type_name }} ({{ printOrder.fuel_type_code }})</td>
            <td>{{ formatNum(printOrder.quantity_liters) }}</td>
            <td>{{ printOrder.quantity_tons }}</td>
            <td>{{ printOrder.price_per_ton ? '$' + formatNum(printOrder.price_per_ton) : '—' }}</td>
            <td>{{ printOrder.total_amount ? '$' + formatNum(printOrder.total_amount) : '—' }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="po-total-label">TOTAL:</td>
            <td class="po-total-val">{{ printOrder.total_amount ? '$' + formatNum(printOrder.total_amount) : '—' }}</td>
          </tr>
        </tfoot>
      </table>

      <!-- Notes -->
      <div v-if="printOrder.notes" class="po-notes">
        <strong>Notes:</strong> {{ printOrder.notes }}
      </div>

      <!-- Signatures -->
      <div class="po-signatures">
        <div class="po-sig">
          <div class="po-sig-line"></div>
          <div class="po-sig-label">Prepared by</div>
        </div>
        <div class="po-sig">
          <div class="po-sig-line"></div>
          <div class="po-sig-label">Reviewed by</div>
        </div>
        <div class="po-sig">
          <div class="po-sig-line"></div>
          <div class="po-sig-label">Approved by</div>
        </div>
      </div>

      <!-- Footer -->
      <div class="po-footer">
        <div class="po-rule"></div>
        <div>Kitty Kat Technologies — Fuel Management System</div>
        <div>Generated: {{ new Date().toLocaleDateString('en-GB') }}</div>
      </div>
    </div>
    <!-- /print section -->

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue'
import { ordersApi, stationsApi, fuelTypesApi, suppliersApi, dashboardApi, procurementApi, parametersApi } from '../services/api.js'

// ────────────────────────────────────────────────────────────────────────────
// State
// ────────────────────────────────────────────────────────────────────────────
const activeTab  = ref('purchase_orders')   // 'purchase_orders' | 'erp_deliveries'

const poOrders   = ref([])
const erpOrders  = ref([])
const stations   = ref([])
const fuelTypes  = ref([])
const suppliers  = ref([])
const loadingPO  = ref(false)
const loadingERP = ref(false)

// Sort state — separate for each tab (default: newest first by id)
// Using reactive() so template auto-unwrapping doesn't break helper functions
const poSort  = reactive({ key: 'id', dir: 'desc' })
const erpSort = reactive({ key: 'id', dir: 'desc' })

// Stats bar
const orderStats = ref([])   // raw array: [{order_type, status, cnt}]

// Header KPIs (system-wide, from dashboard + procurement APIs)
const dashSummary = ref(null)
const procSummary = ref(null)

// Separate filter sets for each tab
const poFilters = ref({
  station_id:   '',
  fuel_type_id: '',
  status:       '',
  date_from:    '',
  date_to:      ''
})

const erpFilters = ref({
  station_id:   '',
  fuel_type_id: '',
  status:       '',
  date_from:    '',
  date_to:      ''
})

// Create PO form
const showCreateModal = ref(false)
const formSubmitting  = ref(false)
const createError     = ref('')
const form = ref({
  station_id:     '',
  fuel_type_id:   '',
  supplier_id:    '',
  quantity_tons:  null,   // user enters tons; converted to liters on submit
  price_per_ton:  null,
  delivery_date:  '',
  notes:          ''
})

// Manual ERP create modal
const showCreateErpModal = ref(false)
const erpFormSubmitting  = ref(false)
const createErpError     = ref('')
const erpForm = ref({
  station_id:     '',
  fuel_type_id:   '',
  supplier_id:    '',
  quantity_tons:  null,   // user enters tons; converted to liters on submit
  price_per_ton:  null,
  delivery_date:  '',
  status:         'confirmed',
  notes:          ''
})

// Cancel modal
const showCancelModal  = ref(false)
const cancelSubmitting = ref(false)
const cancelError      = ref('')
const cancelReason     = ref('')
const selectedOrder    = ref(null)

// Edit modal (PO + ERP)
const showEditModal  = ref(false)
const editSubmitting = ref(false)
const editError      = ref('')
const editingOrder   = ref(null)
const editForm = ref({
  station_id:    '',
  fuel_type_id:  '',
  supplier_id:   '',
  quantity_tons: null,
  price_per_ton: null,
  delivery_date: '',
  status:        '',
  notes:         ''
})

// Print + PDF
const printOrder    = ref(null)
const pdfGenerating = ref(false)

const today = new Date().toISOString().split('T')[0]

// ────────────────────────────────────────────────────────────────────────────
// Computed
// ────────────────────────────────────────────────────────────────────────────

/** Active filters object — points to whichever tab is visible */
const activeFilters = computed(() =>
  activeTab.value === 'purchase_orders' ? poFilters.value : erpFilters.value
)

/** Active orders list for the count in the filter bar */
const currentOrders = computed(() =>
  activeTab.value === 'purchase_orders' ? poOrders.value : erpOrders.value
)

// ── Sort helpers ────────────────────────────────────────────────────────────

const NUMERIC_SORT_KEYS = new Set(['id', 'quantity_liters', 'price_per_ton'])

function applySortOrders(orders, { key, dir }) {
  return [...orders].sort((a, b) => {
    let va = a[key] ?? ''
    let vb = b[key] ?? ''
    if (NUMERIC_SORT_KEYS.has(key)) {
      va = parseFloat(va) || 0
      vb = parseFloat(vb) || 0
      return dir === 'asc' ? va - vb : vb - va
    }
    va = String(va).toLowerCase()
    vb = String(vb).toLowerCase()
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
}

const sortedPoOrders  = computed(() => applySortOrders(poOrders.value,  poSort))
const sortedErpOrders = computed(() => applySortOrders(erpOrders.value, erpSort))

function toggleSort(sortObj, key) {
  if (sortObj.key === key) {
    sortObj.dir = sortObj.dir === 'asc' ? 'desc' : 'asc'
  } else {
    sortObj.key = key
    sortObj.dir = 'asc'
  }
}

function sortIconClass(sortObj, key) {
  if (sortObj.key !== key) return 'fas fa-sort text-gray-300 ml-1'
  return sortObj.dir === 'asc'
    ? 'fas fa-sort-up text-blue-400 ml-1'
    : 'fas fa-sort-down text-blue-400 ml-1'
}

const selectedFuelDensity = computed(() => {
  if (!form.value.fuel_type_id) return null
  const ft = fuelTypes.value.find(f => f.id == form.value.fuel_type_id)
  return ft ? parseFloat(ft.density) : null
})

const selectedErpFuelDensity = computed(() => {
  if (!erpForm.value.fuel_type_id) return null
  const ft = fuelTypes.value.find(f => f.id == erpForm.value.fuel_type_id)
  return ft ? parseFloat(ft.density) : null
})

// Supplier offers — loaded once on mount, used for delivery days + price hints
const supplierOffers = ref([])

/** Best matching offer for PO form (supplier + station + fuel_type if selected) */
const selectedSupplierOffer = computed(() => {
  if (!form.value.supplier_id || !form.value.station_id) return null
  return supplierOffers.value.find(o =>
    o.supplier_id == form.value.supplier_id &&
    o.station_id  == form.value.station_id  &&
    (!form.value.fuel_type_id || o.fuel_type_id == form.value.fuel_type_id)
  ) || null
})

/** Best matching offer for ERP form */
const selectedErpSupplierOffer = computed(() => {
  if (!erpForm.value.supplier_id || !erpForm.value.station_id) return null
  return supplierOffers.value.find(o =>
    o.supplier_id == erpForm.value.supplier_id &&
    o.station_id  == erpForm.value.station_id  &&
    (!erpForm.value.fuel_type_id || o.fuel_type_id == erpForm.value.fuel_type_id)
  ) || null
})

// Stats bar — helper + per-status computed
function statsCount(orderType, status) {
  const found = orderStats.value.find(s => s.order_type === orderType && s.status === status)
  return found ? parseInt(found.cnt) : 0
}
const poPlanned    = computed(() => statsCount('purchase_order', 'planned'))
const poMatched    = computed(() => statsCount('purchase_order', 'matched'))
const poExpired    = computed(() => statsCount('purchase_order', 'expired'))
const erpConfirmed = computed(() => statsCount('erp_order', 'confirmed'))
const erpInTransit = computed(() => statsCount('erp_order', 'in_transit'))
const erpDelivered = computed(() => statsCount('erp_order', 'delivered'))

// Header KPI computeds (system-wide stats from dashboard + procurement)
const kpiTotalStations = computed(() => dashSummary.value?.inventory?.total_stations ?? '—')
const kpiShortages     = computed(() => dashSummary.value?.critical_tanks_count ?? 0)
const kpiLowStock      = computed(() => dashSummary.value?.alerts?.WARNING ?? 0)
const kpiMandatory     = computed(() => procSummary.value?.mandatory_orders  ?? 0)
const kpiRecommended   = computed(() => procSummary.value?.recommended_orders ?? 0)

// Current datetime chip (set once on mount)
const currentDateTime  = ref('')

// ────────────────────────────────────────────────────────────────────────────
// Tab switching
// ────────────────────────────────────────────────────────────────────────────
function switchTab(tab) {
  activeTab.value = tab
  loadActiveTab()
}

// ────────────────────────────────────────────────────────────────────────────
// Data loading
// ────────────────────────────────────────────────────────────────────────────
async function loadPOOrders() {
  loadingPO.value = true
  try {
    const f = poFilters.value
    const params = { order_type: 'purchase_order' }
    if (f.station_id)   params.station_id   = f.station_id
    if (f.fuel_type_id) params.fuel_type_id = f.fuel_type_id
    if (f.status)       params.status       = f.status
    if (f.date_from)    params.date_from    = f.date_from
    if (f.date_to)      params.date_to      = f.date_to

    const res = await ordersApi.getAll(params)
    poOrders.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load PO orders', e)
  } finally {
    loadingPO.value = false
  }
}

async function loadERPOrders() {
  loadingERP.value = true
  try {
    const f = erpFilters.value
    const params = { order_type: 'erp_order' }
    if (f.station_id)   params.station_id   = f.station_id
    if (f.fuel_type_id) params.fuel_type_id = f.fuel_type_id
    if (f.status)       params.status       = f.status
    if (f.date_from)    params.date_from    = f.date_from
    if (f.date_to)      params.date_to      = f.date_to

    const res = await ordersApi.getAll(params)
    erpOrders.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load ERP orders', e)
  } finally {
    loadingERP.value = false
  }
}

/** Load only the currently visible tab (used by filter changes) */
function loadActiveTab() {
  if (activeTab.value === 'purchase_orders') {
    loadPOOrders()
  } else {
    loadERPOrders()
  }
}

async function loadOrderStats() {
  try {
    const res = await ordersApi.getStats()
    orderStats.value = res.data.data || []
  } catch (e) { console.error('loadOrderStats', e) }
}

async function loadHeaderKpis() {
  // Set current datetime chip immediately
  currentDateTime.value = new Date().toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
  try {
    const [dash, proc] = await Promise.all([
      dashboardApi.getSummary(),
      procurementApi.getSummary(),
    ])
    dashSummary.value = dash.data.data || {}
    procSummary.value = proc.data.data || {}
  } catch (e) { console.error('loadHeaderKpis', e) }
}

async function loadStations() {
  try {
    const res = await stationsApi.getAll()
    stations.value = res.data.data || []
  } catch (e) { console.error('loadStations', e) }
}

async function loadFuelTypes() {
  try {
    const res = await fuelTypesApi.getAll()
    fuelTypes.value = res.data.data || []
  } catch (e) { console.error('loadFuelTypes', e) }
}

async function loadSuppliers() {
  try {
    const res = await suppliersApi.getAll()
    suppliers.value = res.data.data || []
  } catch (e) { console.error('loadSuppliers', e) }
}

function clearFilters() {
  if (activeTab.value === 'purchase_orders') {
    poFilters.value = { station_id: '', fuel_type_id: '', status: '', date_from: '', date_to: '' }
    loadPOOrders()
  } else {
    erpFilters.value = { station_id: '', fuel_type_id: '', status: '', date_from: '', date_to: '' }
    loadERPOrders()
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Create PO
// ────────────────────────────────────────────────────────────────────────────
function openCreateModal() {
  form.value = {
    station_id: '', fuel_type_id: '', supplier_id: '',
    quantity_tons: null, price_per_ton: null,
    delivery_date: '', notes: ''
  }
  createError.value = ''
  showCreateModal.value = true
}

async function submitCreate() {
  createError.value = ''
  formSubmitting.value = true
  try {
    // Convert tons → liters for storage (architecture rule: stored as liters)
    const density = selectedFuelDensity.value
    const quantityLiters = density
      ? Math.round(form.value.quantity_tons * 1000 / density)
      : Math.round(form.value.quantity_tons)  // fallback: treat as liters
    const payload = { ...form.value, quantity_liters: quantityLiters }
    delete payload.quantity_tons
    await ordersApi.create(payload)
    showCreateModal.value = false
    await Promise.all([loadPOOrders(), loadOrderStats()])
  } catch (e) {
    createError.value = e.response?.data?.error || 'Failed to create order'
  } finally {
    formSubmitting.value = false
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Manual ERP Order Creation
// ────────────────────────────────────────────────────────────────────────────
function openCreateErpModal() {
  erpForm.value = {
    station_id: '', fuel_type_id: '', supplier_id: '',
    quantity_tons: null, price_per_ton: null,
    delivery_date: '', status: 'confirmed', notes: ''
  }
  createErpError.value = ''
  showCreateErpModal.value = true
}

async function submitCreateErp() {
  createErpError.value = ''
  erpFormSubmitting.value = true
  try {
    // Convert tons → liters for storage
    const density = selectedErpFuelDensity.value
    const quantityLiters = density
      ? Math.round(erpForm.value.quantity_tons * 1000 / density)
      : Math.round(erpForm.value.quantity_tons)
    const payload = { ...erpForm.value, quantity_liters: quantityLiters }
    delete payload.quantity_tons
    await ordersApi.createErp(payload)
    showCreateErpModal.value = false
    await Promise.all([loadERPOrders(), loadOrderStats()])
  } catch (e) {
    createErpError.value = e.response?.data?.error || 'Failed to create ERP order'
  } finally {
    erpFormSubmitting.value = false
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Cancel PO
// ────────────────────────────────────────────────────────────────────────────
function openCancelModal(order) {
  selectedOrder.value  = order
  cancelReason.value   = ''
  cancelError.value    = ''
  showCancelModal.value = true
}

async function submitCancel() {
  if (!cancelReason.value.trim()) return
  cancelSubmitting.value = true
  cancelError.value = ''
  try {
    await ordersApi.cancel(selectedOrder.value.id, cancelReason.value)
    showCancelModal.value = false
    await Promise.all([loadPOOrders(), loadOrderStats()])
  } catch (e) {
    cancelError.value = e.response?.data?.error || 'Failed to cancel order'
  } finally {
    cancelSubmitting.value = false
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Edit Order (PO + ERP)
// ────────────────────────────────────────────────────────────────────────────
function openEditModal(order) {
  editingOrder.value = order
  // Convert stored liters → tons for display using actual density
  const ft      = fuelTypes.value.find(f => f.id == order.fuel_type_id)
  const density = ft ? parseFloat(ft.density) : null
  const qtyTons = density
    ? Math.round((parseFloat(order.quantity_liters) * density / 1000) * 100) / 100
    : parseFloat(order.quantity_tons) || null
  editForm.value = {
    station_id:    order.station_id   || '',
    fuel_type_id:  order.fuel_type_id || '',
    supplier_id:   order.supplier_id  || '',
    quantity_tons: qtyTons,
    price_per_ton: order.price_per_ton  ? parseFloat(order.price_per_ton)  : null,
    delivery_date: order.delivery_date || '',
    status:        order.status        || '',
    notes:         order.notes         || ''
  }
  editError.value = ''
  showEditModal.value = true
}

const selectedEditFuelDensity = computed(() => {
  if (!editForm.value.fuel_type_id) return null
  const ft = fuelTypes.value.find(f => f.id == editForm.value.fuel_type_id)
  return ft ? parseFloat(ft.density) : null
})

async function submitEdit() {
  if (!editingOrder.value) return
  editError.value   = ''
  editSubmitting.value = true
  try {
    const density       = selectedEditFuelDensity.value
    const quantityLiters = density
      ? Math.round(editForm.value.quantity_tons * 1000 / density)
      : Math.round(editForm.value.quantity_tons)

    const payload = {
      station_id:      editForm.value.station_id    || null,
      fuel_type_id:    editForm.value.fuel_type_id  || null,
      supplier_id:     editForm.value.supplier_id   || null,
      quantity_liters: quantityLiters,
      price_per_ton:   editForm.value.price_per_ton || null,
      delivery_date:   editForm.value.delivery_date,
      status:          editForm.value.status,
      notes:           editForm.value.notes         || null,
    }
    if (editForm.value.quantity_tons && editForm.value.price_per_ton) {
      payload.total_amount = Math.round(editForm.value.quantity_tons * editForm.value.price_per_ton * 100) / 100
    }

    await ordersApi.update(editingOrder.value.id, payload)
    showEditModal.value = false
    await Promise.all([loadPOOrders(), loadERPOrders(), loadOrderStats()])
  } catch (e) {
    editError.value = e.response?.data?.error || 'Failed to update order'
  } finally {
    editSubmitting.value = false
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Print PO
// ────────────────────────────────────────────────────────────────────────────
function printPO(order) {
  printOrder.value = order
  setTimeout(() => window.print(), 100)
}

// ────────────────────────────────────────────────────────────────────────────
// Download PO as PDF — REV 2.0 style: navy header bar, colored boxes,
// items table, VAT, terms, signatures. Roboto for Cyrillic support.
// Font is embedded as base64 in robotoBase64.js (pre-encoded by Python at
// build time) — no fetch, no binary conversion bugs, no CDN dependency.
// ────────────────────────────────────────────────────────────────────────────
async function downloadPoPdf(order) {
  pdfGenerating.value = true
  try {
    const [{ jsPDF }, { robotoBase64: fontB64 }] = await Promise.all([
      import('jspdf'),
      import('../utils/robotoBase64.js'),
    ])
    const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' })
    const W   = 210
    const ml  = 20
    const cw  = W - ml * 2   // 170
    let   y   = 20

    // Register Roboto for Unicode / Cyrillic
    doc.addFileToVFS('Roboto-Regular.ttf', fontB64)
    doc.addFont('Roboto-Regular.ttf', 'Roboto', 'normal')

    // ── Colors ───────────────────────────────────────────────────────────────
    const navy   = [15, 23, 42]
    const blue   = [37, 99, 235]
    const green  = [16, 185, 129]
    const gray   = [100, 116, 139]
    const grayLt = [226, 232, 240]
    const white  = [255, 255, 255]
    const black  = [0, 0, 0]

    // ── Helpers ───────────────────────────────────────────────────────────────
    const sf  = (size, color) =>
      doc.setFont('Roboto', 'normal').setFontSize(size).setTextColor(...(color || black))
    const ln  = (y1, col, w) =>
      doc.setDrawColor(...(col || grayLt)).setLineWidth(w || 0.3).line(ml, y1, W - ml, y1)
    const rct = (x, y2, w2, h, fill) =>
      doc.setFillColor(...fill).rect(x, y2, w2, h, 'F')

    // ══ HEADER BAR ═══════════════════════════════════════════════════════════
    rct(0, 0, W, 42, navy)

    sf(22, white)
    doc.text('KITTY KAT', ml, 18)
    sf(22, [96, 165, 250])
    doc.text('TECHNOLOGIES', ml + doc.getTextWidth('KITTY KAT') + 3, 18)

    sf(9, [148, 163, 184])
    doc.text('Fuel Supply Optimization System', ml, 26)

    sf(11, [96, 165, 250])
    doc.text('PURCHASE ORDER', W - ml, 15, { align: 'right' })

    const numW = doc.getTextWidth(order.order_number) + 12
    rct(W - ml - numW, 20, numW, 10, [30, 58, 138])
    sf(12, white)
    doc.text(order.order_number, W - ml - numW / 2, 28, { align: 'center' })

    sf(8, [74, 222, 128])
    doc.text(`● ${(order.status || 'PLANNED').toUpperCase()}`, W - ml, 37, { align: 'right' })

    y = 52

    // ══ DATES ════════════════════════════════════════════════════════════════
    const colR = W / 2 + 10

    sf(8, gray);  doc.text('ORDER DATE',     ml,   y)
    sf(11, black); doc.text(formatDate(order.order_date), ml, y + 5)
    sf(8, gray);  doc.text('DELIVERY DATE',  ml,   y + 14)
    sf(11, black); doc.text(formatDate(order.delivery_date), ml, y + 19)

    sf(8, gray);  doc.text('ORDER ID',       colR, y)
    sf(11, black); doc.text(`#${order.id}`,  colR, y + 5)
    sf(8, gray);  doc.text('CURRENCY',       colR, y + 14)
    sf(11, black); doc.text('USD',           colR, y + 19)

    y += 32
    ln(y, grayLt, 0.3)
    y += 8

    // ══ SUPPLIER + DESTINATION ═══════════════════════════════════════════════
    const halfW = cw / 2 - 4

    rct(ml, y, halfW, 32, [240, 245, 255])
    sf(8, blue);   doc.text('SUPPLIER',           ml + 5, y + 6)
    sf(12, navy);  doc.text(order.supplier_name || '—', ml + 5, y + 14)
    sf(9, gray);   doc.text(`ID: ${order.supplier_id || '—'}`, ml + 5, y + 21)

    const dstX = ml + halfW + 8
    rct(dstX, y, halfW, 32, [240, 253, 244])
    sf(8, green);  doc.text('DELIVERY DESTINATION', dstX + 5, y + 6)
    sf(12, navy);  doc.text(order.station_name || '—', dstX + 5, y + 14)
    sf(9, gray);   doc.text(order.depot_name || '', dstX + 5, y + 21)
    if (order.station_id) doc.text(`Station ID: ${order.station_id}`, dstX + 5, y + 27)

    y += 40

    // ══ ITEMS TABLE ══════════════════════════════════════════════════════════
    rct(ml, y, cw, 10, navy)
    sf(8, white)
    const c = [ml + 3, ml + 58, ml + 93, ml + 120, ml + 150]
    doc.text('PRODUCT',     c[0], y + 7)
    doc.text('QTY (T)',     c[1], y + 7)
    doc.text('VOLUME (L)',  c[2], y + 7)
    doc.text('PRICE/TON',  c[3], y + 7)
    doc.text('TOTAL',       c[4], y + 7)
    y += 10

    rct(ml, y, cw, 14, [249, 250, 251])
    sf(10, navy)
    doc.text(`${order.fuel_type_name} (${order.fuel_type_code})`, c[0], y + 9)
    sf(10, black)
    doc.text(String(order.quantity_tons ?? '—'),                   c[1], y + 9)
    doc.text(formatNum(order.quantity_liters),                     c[2], y + 9)
    doc.text(order.price_per_ton ? '$' + formatNum(order.price_per_ton) : '—', c[3], y + 9)
    sf(10, navy)
    doc.text(order.total_amount ? '$' + formatNum(order.total_amount) : '—', c[4], y + 9)
    y += 14
    ln(y, navy, 0.5)
    y += 2

    // ══ TOTALS ═══════════════════════════════════════════════════════════════
    const totX     = ml + 105
    const subtotal = parseFloat(order.total_amount) || 0
    const vat      = Math.round(subtotal * 0.12 * 100) / 100
    const grand    = Math.round((subtotal + vat) * 100) / 100

    sf(9, gray);   doc.text('Subtotal:',    totX, y + 7)
    sf(9, black);  doc.text('$' + formatNum(subtotal), W - ml, y + 7, { align: 'right' })
    y += 9
    ln(y, grayLt, 0.2)
    sf(9, gray);   doc.text('VAT (12%):',  totX, y + 7)
    sf(9, black);  doc.text('$' + formatNum(vat), W - ml, y + 7, { align: 'right' })
    y += 10
    rct(totX - 3, y, W - ml - totX + 3, 12, navy)
    sf(11, white); doc.text('TOTAL:',       totX + 2, y + 8)
    sf(13, white); doc.text('$' + formatNum(grand), W - ml, y + 8, { align: 'right' })
    y += 22

    // ══ NOTES ════════════════════════════════════════════════════════════════
    if (order.notes) {
      sf(8, gray);  doc.text('NOTES', ml, y); y += 5
      sf(9, black)
      const noteLines = doc.splitTextToSize(order.notes, cw)
      doc.text(noteLines, ml, y)
      y += noteLines.length * 5 + 5
    }

    // ══ TERMS ════════════════════════════════════════════════════════════════
    y += 5
    ln(y, grayLt, 0.3)
    y += 8
    sf(9, navy); doc.text('Terms & Conditions', ml, y); y += 6
    sf(8, gray)
    const terms = [
      '1. Payment terms: Net 30 days from invoice date.',
      '2. Delivery: FOB destination. Supplier responsible for transport to delivery point.',
      '3. Quality: All products must meet KR-10 fuel quality standards.',
      '4. Quantity tolerance: ±2% of ordered quantity is acceptable.',
      '5. This PO is subject to the terms of the master supply agreement between the parties.',
    ]
    terms.forEach(t => { doc.text(t, ml, y); y += 4.5 })

    // ══ SIGNATURES ═══════════════════════════════════════════════════════════
    y += 10
    sf(8, gray); doc.text('AUTHORIZED BY (BUYER)', ml, y)
    doc.setDrawColor(0).setLineWidth(0.5).line(ml, y + 15, ml + 70, y + 15)
    sf(8, gray); doc.text('Name / Signature / Date', ml, y + 20)

    sf(8, gray); doc.text('ACCEPTED BY (SUPPLIER)', colR, y)
    doc.setDrawColor(0).setLineWidth(0.5).line(colR, y + 15, colR + 70, y + 15)
    sf(8, gray); doc.text('Name / Signature / Date', colR, y + 20)

    // ══ FOOTER ═══════════════════════════════════════════════════════════════
    const footY = 279
    rct(0, footY, W, 18, [249, 250, 251])
    doc.setDrawColor(...grayLt).setLineWidth(0.3).line(0, footY, W, footY)
    sf(7, gray)
    doc.text('Kitty Kat Technologies — Fuel Supply Optimization System', ml, footY + 5)
    doc.text('fuel.kittykat.tech/rev3', ml, footY + 10)
    doc.text(`Generated: ${new Date().toLocaleString('en-GB')}`, W - ml, footY + 5, { align: 'right' })
    doc.text('Page 1 of 1', W - ml, footY + 10, { align: 'right' })

    // ══ SAVE ═════════════════════════════════════════════════════════════════
    const fname = `${order.order_number}_${(order.station_name || '').replace(/[^a-zA-Z0-9\u0400-\u04FF]/g, '_')}.pdf`
    doc.save(fname)
  } catch (e) {
    console.error('PDF generation failed', e)
    alert(`PDF generation failed: ${e.message}`)
  } finally {
    pdfGenerating.value = false
  }
}

// ────────────────────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────────────────────
function statusBadgeClass(status) {
  const map = {
    // PO statuses
    planned:    'bg-gray-100 text-gray-700',
    matched:    'bg-blue-100 text-blue-700',
    expired:    'bg-orange-100 text-orange-700',
    // ERP statuses
    confirmed:  'bg-sky-100 text-sky-700',
    in_transit: 'bg-amber-100 text-amber-700',
    delivered:  'bg-green-100 text-green-700',
    // Both
    cancelled:  'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function statusLabel(status) {
  const map = {
    planned:    'Planned',
    matched:    'Matched',
    expired:    'Expired',
    confirmed:  'Confirmed',
    in_transit: 'In Transit',
    delivered:  'Delivered',
    cancelled:  'Cancelled',
  }
  return map[status] || status
}

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatNum(n) {
  if (n == null || n === '') return '—'
  return Number(n).toLocaleString('en-US')
}

// ────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ────────────────────────────────────────────────────────────────────────────
async function loadSupplierOffers() {
  try {
    const res = await parametersApi.getSupplierOffers()
    supplierOffers.value = res.data.data || []
  } catch (e) { console.error('loadSupplierOffers', e) }
}

// ─── Auto-fill Delivery Date + Price from supplier offer ─────────────────────

/** Helper: today + N days → 'YYYY-MM-DD' */
function dateAfterDays(n) {
  const d = new Date()
  d.setDate(d.getDate() + n)
  return d.toISOString().split('T')[0]
}

// PO form: watch supplier offer → auto-fill date & price
watch(selectedSupplierOffer, (offer) => {
  if (!offer) return
  // Auto-fill delivery date
  if (offer.delivery_days) {
    form.value.delivery_date = dateAfterDays(offer.delivery_days)
  }
  // Auto-fill price from contract (only if user hasn't typed one yet)
  if (offer.price_per_ton && !form.value.price_per_ton) {
    form.value.price_per_ton = parseFloat(offer.price_per_ton)
  }
})

// ERP form: same logic
watch(selectedErpSupplierOffer, (offer) => {
  if (!offer) return
  if (offer.delivery_days) {
    erpForm.value.delivery_date = dateAfterDays(offer.delivery_days)
  }
  if (offer.price_per_ton && !erpForm.value.price_per_ton) {
    erpForm.value.price_per_ton = parseFloat(offer.price_per_ton)
  }
})

onMounted(() => {
  // Load both tabs in parallel on mount so tab badge counts are accurate
  loadPOOrders()
  loadERPOrders()
  loadOrderStats()
  loadHeaderKpis()
  loadStations()
  loadFuelTypes()
  loadSuppliers()
  loadSupplierOffers()
})
</script>

<style scoped>
/* ── Screen: hide print section ── */
#print-po {
  display: none;
}
</style>

<style>
/* ── Print styles (global, not scoped) ── */
@media print {
  /* Hide the whole app UI */
  .no-print {
    display: none !important;
  }

  /* Show only the PO blank */
  #print-po {
    display: block !important;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11pt;
    color: #000;
    padding: 20mm 15mm;
    line-height: 1.5;
  }

  /* ── PO Header ── */
  .po-company {
    text-align: center;
    font-size: 20pt;
    font-weight: bold;
    letter-spacing: 3px;
    margin-bottom: 6px;
  }

  .po-rule {
    border-top: 2px solid #000;
    margin: 8px 0;
  }

  .po-title {
    text-align: center;
    font-size: 14pt;
    font-weight: bold;
    letter-spacing: 1px;
    margin: 10px 0 4px;
  }

  .po-number {
    text-align: center;
    font-size: 12pt;
    letter-spacing: 2px;
    margin-bottom: 4px;
  }

  /* ── PO Meta table ── */
  .po-meta {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
  }

  .po-meta td {
    padding: 3px 8px;
    vertical-align: top;
  }

  .po-meta-label {
    width: 110px;
    color: #555;
    font-size: 9pt;
  }

  .po-meta-value {
    font-weight: bold;
    font-size: 10pt;
  }

  /* ── PO Table ── */
  .po-table {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0;
    font-size: 10pt;
  }

  .po-table th {
    background: #000;
    color: #fff;
    padding: 7px 10px;
    text-align: left;
    font-size: 9pt;
    font-weight: bold;
  }

  .po-table td {
    border: 1px solid #bbb;
    padding: 7px 10px;
  }

  .po-table tfoot tr td {
    border-top: 2.5px solid #000;
    font-weight: bold;
  }

  .po-total-label {
    text-align: right;
    color: #444;
    font-size: 10pt;
  }

  .po-total-val {
    font-size: 13pt;
    font-weight: bold;
  }

  /* ── Notes ── */
  .po-notes {
    margin: 14px 0;
    font-size: 9pt;
    color: #444;
    border-left: 3px solid #ccc;
    padding-left: 12px;
  }

  /* ── Signatures ── */
  .po-signatures {
    display: flex;
    justify-content: space-between;
    margin-top: 40mm;
    gap: 20px;
  }

  .po-sig {
    flex: 1;
    text-align: center;
  }

  .po-sig-line {
    border-bottom: 1px solid #000;
    height: 10mm;
    margin-bottom: 6px;
  }

  .po-sig-label {
    font-size: 9pt;
    color: #555;
  }

  /* ── Footer ── */
  .po-footer {
    margin-top: 16px;
    text-align: center;
    font-size: 8pt;
    color: #888;
  }

  .po-footer .po-rule {
    margin-bottom: 8px;
  }
}
</style>
