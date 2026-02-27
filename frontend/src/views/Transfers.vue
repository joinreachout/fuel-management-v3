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
              <div class="text-2xl font-bold text-white">{{ Number(stats.total_transfers) || 0 }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Total</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="pendingCount > 0 ? 'text-orange-400' : 'text-white'">
                {{ pendingCount }}
              </div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Pending</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold" :class="inProgressCount > 0 ? 'text-blue-400' : 'text-white'">
                {{ inProgressCount }}
              </div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">In</div>
                <div class="text-white text-xs font-semibold">Progress</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-green-400">{{ Number(stats.completed_transfers) || 0 }}</div>
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
          <div v-if="activeCount > 0"
            class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="fas fa-circle text-orange-400" style="font-size:8px"></i>
            <span class="font-medium text-gray-300">
              {{ activeCount }} active transfers
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
              <span class="text-gray-600">{{ pendingCount }} Pending</span>
            </span>
            <span class="mr-4 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
              <span class="text-gray-600">{{ inProgressCount }} In Progress</span>
            </span>
            <span class="mr-4 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
              <span class="text-gray-600">{{ Number(stats.completed_transfers) || 0 }} Completed</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>
              <span class="text-gray-600">{{ Number(stats.cancelled_transfers) || 0 }} Cancelled</span>
            </span>
            <span class="ml-auto text-gray-400 text-xs mr-3">{{ filteredTransfers.length }} records</span>
            <button type="button" @click="openCreateModal"
              class="flex items-center gap-1.5 px-4 py-1.5 bg-orange-500 text-white rounded-lg text-sm font-semibold hover:bg-orange-600 transition-colors">
              <i class="fas fa-plus text-xs"></i> New Transfer
            </button>
          </div>

          <!-- Filter bar -->
          <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <select v-model="filterStatus"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>

            <select v-model="filterUrgency"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">All Urgency</option>
              <option value="CATASTROPHE">CATASTROPHE</option>
              <option value="CRITICAL">CRITICAL</option>
              <option value="MUST_ORDER">MUST ORDER</option>
              <option value="WARNING">WARNING</option>
              <option value="NORMAL">NORMAL</option>
            </select>

            <input v-model="filterStation"
              type="text" placeholder="Search station…"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 w-44">

            <input v-model="filterFuel"
              type="text" placeholder="Search fuel…"
              class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 w-36">

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
                  <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Actions
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

                  <!-- Actions -->
                  <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <!-- Edit / View -->
                      <button @click="openEditModal(t)"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        :title="t.status === 'pending' ? 'Edit transfer' : 'View details'">
                        <i :class="t.status === 'pending' ? 'fas fa-edit' : 'fas fa-eye'" class="text-xs"></i>
                      </button>

                      <!-- Advance status (pending → in_progress, in_progress → completed) -->
                      <button v-if="t.status === 'pending'"
                        @click="advanceStatus(t, 'in_progress')"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="Mark as In Transit">
                        <i class="fas fa-play text-xs"></i>
                      </button>
                      <button v-else-if="t.status === 'in_progress'"
                        @click="advanceStatus(t, 'completed')"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                        title="Mark as Completed">
                        <i class="fas fa-check text-xs"></i>
                      </button>

                      <!-- Delete (pending only) — 2-step confirm -->
                      <template v-if="t.status === 'pending'">
                        <button v-if="deleteConfirmId !== t.id"
                          @click="deleteConfirmId = t.id"
                          class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                          title="Delete transfer">
                          <i class="fas fa-trash text-xs"></i>
                        </button>
                        <template v-else>
                          <button @click="confirmDelete(t.id)"
                            class="px-2 py-1 rounded text-xs font-bold bg-red-500 text-white hover:bg-red-600 transition-colors">
                            Sure?
                          </button>
                          <button @click="deleteConfirmId = null"
                            class="px-2 py-1 rounded text-xs text-gray-500 hover:text-gray-700 transition-colors">
                            No
                          </button>
                        </template>
                      </template>
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

  <!-- ── Create Transfer Modal ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="showCreateModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 rounded-t-2xl text-white flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold"><i class="fas fa-truck mr-2"></i>New Transfer</h2>
            <p class="text-orange-100 text-xs mt-0.5">Manual fuel transfer between stations</p>
          </div>
          <button @click="showCreateModal = false" class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">From Station *</label>
            <select v-model="createForm.from_station_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
              <option value="">Select station…</option>
              <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">To Station *</label>
            <select v-model="createForm.to_station_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
              <option value="">Select station…</option>
              <option v-for="s in stations" :key="s.id" :value="s.id"
                :disabled="s.id == createForm.from_station_id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Fuel Type *</label>
            <select v-model="createForm.fuel_type_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
              <option value="">Select fuel type…</option>
              <option v-for="f in fuelTypes" :key="f.id" :value="f.id">{{ f.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Quantity (tons) *</label>
              <input v-model.number="createForm.quantity_tons" type="number" min="0.1" step="0.1"
                placeholder="0.0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Est. Days *</label>
              <input v-model.number="createForm.estimated_days" type="number" min="0.5" step="0.5"
                placeholder="1.0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Urgency *</label>
            <select v-model="createForm.urgency"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none">
              <option value="NORMAL">NORMAL</option>
              <option value="MUST_ORDER">MUST ORDER</option>
              <option value="CRITICAL">CRITICAL</option>
              <option value="CATASTROPHE">CATASTROPHE</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
            <textarea v-model="createForm.notes" rows="2" placeholder="Optional notes…"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none resize-none"></textarea>
          </div>

          <div v-if="createError" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ createError }}
          </div>
        </div>

        <div class="px-6 pb-6 flex gap-3">
          <button type="button" @click="showCreateModal = false"
            class="flex-1 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Cancel
          </button>
          <button type="button" @click="submitCreate" :disabled="createLoading"
            class="flex-1 py-2.5 rounded-lg bg-orange-500 text-white text-sm font-bold hover:bg-orange-600 transition-colors disabled:opacity-50">
            <i v-if="createLoading" class="fas fa-spinner fa-spin mr-1"></i>
            Create Transfer
          </button>
        </div>

      </div>
    </div>
  </Teleport>

  <!-- ── Edit / View Detail Modal ─────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showEditModal && editTransfer"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="closeEditModal">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

        <!-- Header -->
        <div class="px-6 py-4 rounded-t-2xl text-white flex items-center justify-between"
          :class="editTransfer.status === 'pending'
            ? 'bg-gradient-to-r from-blue-600 to-indigo-600'
            : editTransfer.status === 'in_progress'
              ? 'bg-gradient-to-r from-blue-500 to-sky-500'
              : 'bg-gradient-to-r from-gray-600 to-gray-700'">
          <div>
            <h2 class="text-lg font-bold">
              <i class="fas fa-truck mr-2"></i>
              Transfer #{{ editTransfer.id }}
            </h2>
            <p class="text-blue-100 text-xs mt-0.5">
              {{ shortName(editTransfer.from_station_name) }} → {{ shortName(editTransfer.to_station_name) }}
              · {{ editTransfer.fuel_type_name }}
            </p>
          </div>
          <button @click="closeEditModal" class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        <div class="p-6 space-y-4">

          <!-- Read-only info row -->
          <div class="grid grid-cols-3 gap-3 text-center text-xs">
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="font-bold text-lg text-gray-900">{{ formatNumber(editTransfer.transfer_amount_liters) }}</div>
              <div class="text-gray-400 mt-0.5">liters</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="font-bold text-lg text-gray-900">{{ formatTons(editTransfer.transfer_amount_liters, editTransfer.density) }}</div>
              <div class="text-gray-400 mt-0.5">tons</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="font-bold text-sm" :class="statusTextClass(editTransfer.status)">
                {{ statusLabel(editTransfer.status) }}
              </div>
              <div class="text-gray-400 mt-0.5">status</div>
            </div>
          </div>

          <!-- Editable fields -->
          <!-- Urgency (editable for pending + in_progress) -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Urgency</label>
            <select v-if="canEdit"
              v-model="editForm.urgency"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
              <option value="NORMAL">NORMAL</option>
              <option value="MUST_ORDER">MUST ORDER</option>
              <option value="CRITICAL">CRITICAL</option>
              <option value="CATASTROPHE">CATASTROPHE</option>
            </select>
            <div v-else class="px-3 py-2 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100">
              {{ editForm.urgency || '—' }}
            </div>
          </div>

          <!-- Est. Days (editable for pending only) -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Est. Days</label>
            <input v-if="editTransfer.status === 'pending'"
              v-model.number="editForm.estimated_days" type="number" min="0.5" step="0.5"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
            <div v-else class="px-3 py-2 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100">
              {{ editTransfer.estimated_days ?? '—' }}
            </div>
          </div>

          <!-- Notes (always editable) -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
            <textarea v-model="editForm.notes" rows="3" placeholder="Notes…"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none resize-none"></textarea>
          </div>

          <!-- Status transition (for pending / in_progress) -->
          <div v-if="editTransfer.status === 'pending' || editTransfer.status === 'in_progress'">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Change Status</label>
            <div class="flex gap-2">
              <template v-if="editTransfer.status === 'pending'">
                <button @click="applyStatusChange('in_progress')"
                  class="flex-1 py-2 rounded-lg bg-blue-500 text-white text-xs font-bold hover:bg-blue-600 transition-colors">
                  <i class="fas fa-play mr-1"></i>Start (In Transit)
                </button>
                <button @click="applyStatusChange('cancelled')"
                  class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-xs font-semibold hover:bg-red-50 transition-colors">
                  <i class="fas fa-ban mr-1"></i>Cancel
                </button>
              </template>
              <template v-else-if="editTransfer.status === 'in_progress'">
                <button @click="applyStatusChange('completed')"
                  class="flex-1 py-2 rounded-lg bg-green-500 text-white text-xs font-bold hover:bg-green-600 transition-colors">
                  <i class="fas fa-check mr-1"></i>Mark Completed
                </button>
                <button @click="applyStatusChange('cancelled')"
                  class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-xs font-semibold hover:bg-red-50 transition-colors">
                  <i class="fas fa-ban mr-1"></i>Cancel
                </button>
              </template>
            </div>
          </div>

          <!-- Timestamps -->
          <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
            <div v-if="editTransfer.created_at">
              <span class="font-semibold">Created:</span> {{ editTransfer.created_at.substring(0,10) }}
            </div>
            <div v-if="editTransfer.started_at">
              <span class="font-semibold text-blue-600">Started:</span> {{ editTransfer.started_at.substring(0,10) }}
            </div>
            <div v-if="editTransfer.completed_at">
              <span class="font-semibold text-green-600">Completed:</span> {{ editTransfer.completed_at.substring(0,10) }}
            </div>
            <div v-if="editTransfer.cancelled_at">
              <span class="font-semibold text-red-500">Cancelled:</span> {{ editTransfer.cancelled_at.substring(0,10) }}
            </div>
          </div>

          <!-- Error -->
          <div v-if="editError" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ editError }}
          </div>

        </div>

        <!-- Footer -->
        <div class="px-6 pb-6 flex gap-3">
          <button type="button" @click="closeEditModal"
            class="flex-1 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Close
          </button>
          <button v-if="canEdit || isNotesEditable"
            type="button" @click="submitEdit" :disabled="editLoading"
            class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50">
            <i v-if="editLoading" class="fas fa-spinner fa-spin mr-1"></i>
            Save Changes
          </button>
        </div>

      </div>
    </div>
  </Teleport>

</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { transfersApi, stationsApi, fuelTypesApi } from '../services/api';

// ── State ──────────────────────────────────────────────────────────────────────
const loading   = ref(true);
const transfers = ref([]);
const stats     = ref({});

const stations  = ref([]);
const fuelTypes = ref([]);

// ── Computed stats (cast to Number to avoid string concatenation bugs) ─────────
const pendingCount    = computed(() => Number(stats.value.pending_transfers)     || 0);
const inProgressCount = computed(() => Number(stats.value.in_progress_transfers) || 0);
const activeCount     = computed(() => pendingCount.value + inProgressCount.value);

// ── Create Modal ───────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const createLoading   = ref(false);
const createError     = ref('');
const createForm = reactive({
  from_station_id: '',
  to_station_id:   '',
  fuel_type_id:    '',
  quantity_tons:   '',
  estimated_days:  1.0,
  urgency:         'NORMAL',
  notes:           '',
});

function openCreateModal() {
  Object.assign(createForm, {
    from_station_id: '', to_station_id: '', fuel_type_id: '',
    quantity_tons: '', estimated_days: 1.0, urgency: 'NORMAL', notes: '',
  });
  createError.value = '';
  showCreateModal.value = true;
}

async function submitCreate() {
  createError.value = '';
  if (!createForm.from_station_id || !createForm.to_station_id || !createForm.fuel_type_id
      || !createForm.quantity_tons || !createForm.estimated_days) {
    createError.value = 'Please fill in all required fields.';
    return;
  }
  if (createForm.from_station_id == createForm.to_station_id) {
    createError.value = 'From and To stations must be different.';
    return;
  }
  createLoading.value = true;
  try {
    const res = await transfersApi.create({ ...createForm });
    if (res.data.success) {
      showCreateModal.value = false;
      await loadData();
    } else {
      createError.value = res.data.error || 'Failed to create transfer.';
    }
  } catch (e) {
    createError.value = e.response?.data?.error || 'Server error. Please try again.';
  } finally {
    createLoading.value = false;
  }
}

// ── Edit / View Modal ──────────────────────────────────────────────────────────
const showEditModal  = ref(false);
const editTransfer   = ref(null);   // original transfer data
const editLoading    = ref(false);
const editError      = ref('');
const editForm = reactive({ urgency: '', estimated_days: 1.0, notes: '' });

const canEdit = computed(() =>
  editTransfer.value && ['pending', 'in_progress'].includes(editTransfer.value.status)
);
const isNotesEditable = computed(() => !!editTransfer.value);

function openEditModal(t) {
  editTransfer.value = { ...t };
  editForm.urgency       = t.urgency       || 'NORMAL';
  editForm.estimated_days = t.estimated_days ?? 1.0;
  editForm.notes         = t.notes         || '';
  editError.value        = '';
  showEditModal.value    = true;
}

function closeEditModal() {
  showEditModal.value = false;
  editTransfer.value  = null;
}

async function submitEdit() {
  editError.value = '';
  editLoading.value = true;
  try {
    const payload = { notes: editForm.notes };
    if (canEdit.value) {
      payload.urgency = editForm.urgency;
      if (editTransfer.value.status === 'pending') {
        payload.estimated_days = editForm.estimated_days;
      }
    }
    const res = await transfersApi.update(editTransfer.value.id, payload);
    if (res.data.success) {
      closeEditModal();
      await loadData();
    } else {
      editError.value = res.data.error || 'Update failed.';
    }
  } catch (e) {
    editError.value = e.response?.data?.error || 'Server error.';
  } finally {
    editLoading.value = false;
  }
}

// Apply status change directly from the modal
async function applyStatusChange(newStatus) {
  editError.value  = '';
  editLoading.value = true;
  try {
    const res = await transfersApi.update(editTransfer.value.id, { status: newStatus });
    if (res.data.success) {
      closeEditModal();
      await loadData();
    } else {
      editError.value = res.data.error || 'Status change failed.';
    }
  } catch (e) {
    editError.value = e.response?.data?.error || 'Server error.';
  } finally {
    editLoading.value = false;
  }
}

// Quick status advance from row buttons (no modal)
async function advanceStatus(t, newStatus) {
  try {
    await transfersApi.update(t.id, { status: newStatus });
    await loadData();
  } catch (e) {
    alert(e.response?.data?.error || 'Status change failed.');
  }
}

// ── Delete ─────────────────────────────────────────────────────────────────────
const deleteConfirmId = ref(null);

async function confirmDelete(id) {
  try {
    await transfersApi.delete(id);
    deleteConfirmId.value = null;
    await loadData();
  } catch (e) {
    alert(e.response?.data?.error || 'Delete failed.');
    deleteConfirmId.value = null;
  }
}

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
    const n = typeof av === 'number' || !isNaN(Number(av))
      ? Number(av) - Number(bv)
      : String(av).localeCompare(String(bv));
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
    NORMAL:      'bg-gray-200 text-gray-600',
  }[u] || 'bg-gray-200 text-gray-600';
}

function urgencyShort(u) {
  return { CATASTROPHE:'CATASTR', CRITICAL:'CRITICAL', MUST_ORDER:'MUST', WARNING:'WARN', NORMAL:'NORMAL' }[u] || u;
}

function statusClass(s) {
  return {
    pending:     'bg-orange-100 text-orange-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
    cancelled:   'bg-gray-100 text-gray-500',
  }[s] || 'bg-gray-100 text-gray-600';
}

function statusTextClass(s) {
  return {
    pending:     'text-orange-600',
    in_progress: 'text-blue-600',
    completed:   'text-green-600',
    cancelled:   'text-gray-500',
  }[s] || 'text-gray-600';
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
      transfers.value = res.data.data  || [];
      stats.value     = res.data.stats || {};
    }
  } catch (e) {
    console.error('Transfers load error:', e);
  } finally {
    loading.value = false;
  }
}

async function loadLookups() {
  try {
    const [stRes, ftRes] = await Promise.all([
      stationsApi.getAll(),
      fuelTypesApi.getAll(),
    ]);
    if (stRes.data?.success)  stations.value  = stRes.data.data  || [];
    else if (Array.isArray(stRes.data)) stations.value = stRes.data;
    if (ftRes.data?.success)  fuelTypes.value = ftRes.data.data  || [];
    else if (Array.isArray(ftRes.data)) fuelTypes.value = ftRes.data;
  } catch (e) {
    console.error('Lookups load error:', e);
  }
}

onMounted(() => {
  updateTime();
  setInterval(updateTime, 60000);
  loadData();
  loadLookups();
});
</script>
