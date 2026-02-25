<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Fixed Black Top Bar with Logo + Menu -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <img
          src="/kkt_logo.png"
          alt="Kitty Kat Technologies"
          class="h-12 w-auto"
          style="filter: brightness(0) invert(1);">
        <nav class="flex items-center gap-6">
          <router-link to="/"           class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</router-link>
          <router-link to="/orders"     class="text-gray-400 hover:text-white transition-colors text-sm">Orders</router-link>
          <router-link to="/transfers"  class="text-gray-400 hover:text-white transition-colors text-sm">Transfers</router-link>
          <router-link to="/parameters" class="text-gray-400 hover:text-white transition-colors text-sm">Parameters</router-link>
          <router-link to="/import"     class="text-white font-medium border-b-2 border-white pb-1 text-sm">Import</router-link>
          <router-link to="/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</router-link>
        </nav>
      </div>
    </div>

    <!-- Spacer -->
    <div class="h-20"></div>

    <!-- Page Content -->
    <div class="max-w-4xl mx-auto px-6 py-8 space-y-6">

      <!-- Page title -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Import</h1>
        <p class="text-sm text-gray-500 mt-1">Sync orders and data from external systems</p>
      </div>

      <!-- ── 1C / ERP Sync widget ── -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
          <h3 class="text-lg font-bold text-gray-800">
            <i class="fas fa-sync-alt text-blue-500 mr-2"></i>
            1C / ERP Sync
          </h3>
          <p class="text-xs text-gray-500 mt-1">
            Pull orders from <strong>erp.kittykat.tech</strong> and import them as ERP orders
          </p>
        </div>

        <!-- Body -->
        <div class="px-6 py-6 space-y-5">

          <!-- Config row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- ERP URL -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">ERP Base URL</label>
              <input
                v-model="erpUrl"
                type="url"
                placeholder="https://erp.kittykat.tech"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Period -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Sync period</label>
              <select
                v-model="periodDays"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="1">Last 1 day</option>
                <option :value="3">Last 3 days</option>
                <option :value="7">Last 7 days</option>
                <option :value="14">Last 14 days</option>
                <option :value="30">Last 30 days</option>
              </select>
            </div>
          </div>

          <!-- Sync button + last sync info -->
          <div class="flex items-center gap-4 flex-wrap">
            <button
              class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition-colors"
              :disabled="syncing"
              @click="runSync"
            >
              <i class="fas fa-sync-alt text-sm" :class="{ 'animate-spin': syncing }"></i>
              {{ syncing ? 'Syncing…' : 'Sync from 1C / ERP' }}
            </button>

            <span v-if="lastSyncedAt" class="text-xs text-gray-400">
              <i class="fas fa-clock mr-1"></i>Last sync: {{ lastSyncedAt }}
            </span>
          </div>

          <!-- Error banner -->
          <div v-if="syncError" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            <i class="fas fa-exclamation-circle mt-0.5 flex-none"></i>
            <span>{{ syncError }}</span>
          </div>

          <!-- Result card -->
          <div v-if="result" class="border border-gray-200 rounded-xl overflow-hidden">
            <!-- Stats row -->
            <div class="grid grid-cols-3 divide-x divide-gray-200 bg-gray-50">
              <div class="px-4 py-3 text-center">
                <div class="text-2xl font-bold" :class="result.imported > 0 ? 'text-green-600' : 'text-gray-400'">
                  {{ result.imported }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">Imported</div>
              </div>
              <div class="px-4 py-3 text-center">
                <div class="text-2xl font-bold text-gray-400">{{ result.skipped }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Skipped (duplicates)</div>
              </div>
              <div class="px-4 py-3 text-center">
                <div class="text-2xl font-bold" :class="result.errors?.length > 0 ? 'text-red-500' : 'text-gray-400'">
                  {{ result.errors?.length ?? 0 }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">Errors</div>
              </div>
            </div>

            <!-- Meta row -->
            <div class="flex flex-wrap gap-x-5 gap-y-1 px-4 py-2.5 text-xs text-gray-500 border-t border-gray-100">
              <span><i class="fas fa-cloud-download-alt mr-1"></i>{{ result.total_found }} orders fetched from ERP</span>
              <span><i class="fas fa-filter mr-1"></i>{{ result.filtered_count }} within period</span>
              <span><i class="fas fa-stopwatch mr-1"></i>{{ result.execution_ms }} ms</span>
            </div>

            <!-- Error log -->
            <div v-if="result.errors?.length" class="border-t border-gray-100 px-4 py-3">
              <div class="text-xs font-semibold text-red-600 mb-1.5">
                <i class="fas fa-exclamation-triangle mr-1"></i>Mapping errors
              </div>
              <ul class="space-y-0.5">
                <li
                  v-for="(err, i) in result.errors"
                  :key="i"
                  class="text-xs text-red-500 font-mono"
                >{{ err }}</li>
              </ul>
            </div>

            <!-- Success note -->
            <div v-if="result.imported > 0 && !result.errors?.length" class="border-t border-gray-100 px-4 py-2.5 flex items-center gap-2 text-xs text-green-600">
              <i class="fas fa-check-circle"></i>
              All orders imported successfully. Check the <router-link to="/orders" class="underline font-medium">Orders</router-link> page.
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { importApi } from '../services/api.js';

const erpUrl    = ref('https://erp.kittykat.tech');
const periodDays = ref(7);

const syncing     = ref(false);
const syncError   = ref(null);
const result      = ref(null);
const lastSyncedAt = ref(null);

const runSync = async () => {
  syncing.value   = true;
  syncError.value = null;
  result.value    = null;

  try {
    const res = await importApi.syncErp(erpUrl.value, periodDays.value);
    if (res.data?.success) {
      result.value      = res.data.data;
      lastSyncedAt.value = result.value.synced_at;
    } else {
      syncError.value = res.data?.error ?? 'Unknown error';
    }
  } catch (e) {
    syncError.value = e.response?.data?.error ?? e.message ?? 'Request failed';
  } finally {
    syncing.value = false;
  }
};
</script>
