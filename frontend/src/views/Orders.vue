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

      <!-- Spacer -->
      <div class="h-20"></div>

      <!-- Page Content -->
      <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Page Header -->
        <div class="flex items-start justify-between mb-6">
          <!-- Left: Title -->
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
            <p class="text-sm text-gray-500 mt-1">Manage Purchase Orders and ERP Deliveries</p>
          </div>
          <!-- Right: System KPI chips + action button -->
          <div class="flex items-center gap-10 pt-1">
            <!-- KPI: Total Stations -->
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-gray-900">{{ kpiTotalStations }}</div>
              <div class="h-8 w-px bg-gray-300"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-gray-500 text-xs font-semibold">Total</div>
                <div class="text-gray-500 text-xs font-semibold">Stations</div>
              </div>
            </div>
            <!-- KPI: Below Threshold -->
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="kpiShortages > 0 ? 'text-red-600' : 'text-gray-900'">{{ kpiShortages }}</div>
              <div class="h-8 w-px bg-gray-300"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-gray-500 text-xs font-semibold">Below</div>
                <div class="text-gray-500 text-xs font-semibold">Threshold</div>
              </div>
            </div>
            <!-- KPI: Mandatory Orders -->
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="kpiMandatory > 0 ? 'text-orange-600' : 'text-gray-900'">{{ kpiMandatory }}</div>
              <div class="h-8 w-px bg-gray-300"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-gray-500 text-xs font-semibold">Mandatory</div>
                <div class="text-gray-500 text-xs font-semibold">Orders</div>
              </div>
            </div>
            <!-- KPI: Recommended Orders -->
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-gray-900">{{ kpiRecommended }}</div>
              <div class="h-8 w-px bg-gray-300"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-gray-500 text-xs font-semibold">Recommended</div>
                <div class="text-gray-500 text-xs font-semibold">Orders</div>
              </div>
            </div>
            <!-- New PO button — only visible on Purchase Orders tab -->
            <button v-if="activeTab === 'purchase_orders'"
              @click="openCreateModal"
              class="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition-colors text-sm shadow-sm">
              <i class="fas fa-plus"></i>
              New PO
            </button>
            <!-- Manual ERP entry — fallback when ERP system is unavailable -->
            <button v-if="activeTab === 'erp_deliveries'"
              @click="openCreateErpModal"
              class="flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-medium hover:bg-orange-700 transition-colors text-sm shadow-sm">
              <i class="fas fa-plus"></i>
              Manual Entry
            </button>
          </div>
        </div>

        <!-- ── STATS BAR ── -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-3 mb-5 flex flex-wrap items-center gap-y-2 text-sm">
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

        <!-- ── TABS ── -->
        <div class="flex border-b border-gray-200 mb-5 gap-0">
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

        <!-- ── FILTERS BAR ── -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-5 flex flex-wrap items-center gap-3">
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
        <div v-if="activeTab === 'purchase_orders'" class="bg-white rounded-2xl shadow-lg overflow-hidden">

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
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">PO #</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Station</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fuel Type</th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (L)</th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (T)</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Delivery Date</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="order in poOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">

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

                  <!-- Actions — only for purchase_orders -->
                  <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2">
                      <!-- Print: only for planned -->
                      <button v-if="order.status === 'planned'"
                        @click="printPO(order)"
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                        title="Print PO">
                        <i class="fas fa-print"></i> Print
                      </button>
                      <!-- Cancel: only for planned -->
                      <button v-if="order.status === 'planned'"
                        @click="openCancelModal(order)"
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                        title="Cancel this PO">
                        <i class="fas fa-times"></i> Cancel
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
        <div v-else class="bg-white rounded-2xl shadow-lg overflow-hidden">

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
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order #</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Station</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fuel Type</th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (L)</th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty (T)</th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Price / T</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Delivery Date</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Matched PO</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="order in erpOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">

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

                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- /ERP Deliveries tab -->

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
                  </div>

                  <!-- Quantity -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Quantity (liters) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" v-model.number="form.quantity_liters" min="1" required
                      placeholder="e.g. 45000"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="form.quantity_liters && selectedFuelDensity" class="mt-1 text-xs text-gray-500">
                      ≈ {{ ((form.quantity_liters * selectedFuelDensity) / 1000).toFixed(2) }} tons
                    </p>
                  </div>

                  <!-- Price per ton -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price per ton (USD)</label>
                    <input type="number" v-model.number="form.price_per_ton" min="0" step="0.01"
                      placeholder="e.g. 850"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p v-if="form.quantity_liters && form.price_per_ton && selectedFuelDensity" class="mt-1 text-xs text-gray-500">
                      Total ≈ ${{ ((form.quantity_liters * selectedFuelDensity / 1000) * form.price_per_ton).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
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
                  </div>

                  <!-- Quantity -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Quantity (liters) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" v-model.number="erpForm.quantity_liters" min="1" required
                      placeholder="e.g. 45000"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                    <p v-if="erpForm.quantity_liters && selectedErpFuelDensity" class="mt-1 text-xs text-gray-500">
                      ≈ {{ ((erpForm.quantity_liters * selectedErpFuelDensity) / 1000).toFixed(2) }} tons
                    </p>
                  </div>

                  <!-- Price per ton -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price per ton (USD)</label>
                    <input type="number" v-model.number="erpForm.price_per_ton" min="0" step="0.01"
                      placeholder="e.g. 850"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
                    <p v-if="erpForm.quantity_liters && erpForm.price_per_ton && selectedErpFuelDensity" class="mt-1 text-xs text-gray-500">
                      Total ≈ ${{ ((erpForm.quantity_liters * selectedErpFuelDensity / 1000) * erpForm.price_per_ton).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
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
import { ref, computed, onMounted } from 'vue'
import { ordersApi, stationsApi, fuelTypesApi, suppliersApi, dashboardApi, procurementApi } from '../services/api.js'

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
  station_id:      '',
  fuel_type_id:    '',
  supplier_id:     '',
  quantity_liters: null,
  price_per_ton:   null,
  delivery_date:   '',
  notes:           ''
})

// Manual ERP create modal
const showCreateErpModal = ref(false)
const erpFormSubmitting  = ref(false)
const createErpError     = ref('')
const erpForm = ref({
  station_id:      '',
  fuel_type_id:    '',
  supplier_id:     '',
  quantity_liters: null,
  price_per_ton:   null,
  delivery_date:   '',
  status:          'confirmed',
  notes:           ''
})

// Cancel modal
const showCancelModal  = ref(false)
const cancelSubmitting = ref(false)
const cancelError      = ref('')
const cancelReason     = ref('')
const selectedOrder    = ref(null)

// Print
const printOrder = ref(null)

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
const kpiShortages     = computed(() => dashSummary.value?.alerts?.CRITICAL ?? 0)
const kpiMandatory     = computed(() => procSummary.value?.mandatory_orders  ?? 0)
const kpiRecommended   = computed(() => procSummary.value?.recommended_orders ?? 0)

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
    quantity_liters: null, price_per_ton: null,
    delivery_date: '', notes: ''
  }
  createError.value = ''
  showCreateModal.value = true
}

async function submitCreate() {
  createError.value = ''
  formSubmitting.value = true
  try {
    await ordersApi.create(form.value)
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
    quantity_liters: null, price_per_ton: null,
    delivery_date: '', status: 'confirmed', notes: ''
  }
  createErpError.value = ''
  showCreateErpModal.value = true
}

async function submitCreateErp() {
  createErpError.value = ''
  erpFormSubmitting.value = true
  try {
    await ordersApi.createErp(erpForm.value)
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
// Print PO
// ────────────────────────────────────────────────────────────────────────────
function printPO(order) {
  printOrder.value = order
  setTimeout(() => window.print(), 100)
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
onMounted(() => {
  // Load both tabs in parallel on mount so tab badge counts are accurate
  loadPOOrders()
  loadERPOrders()
  loadOrderStats()
  loadHeaderKpis()
  loadStations()
  loadFuelTypes()
  loadSuppliers()
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
