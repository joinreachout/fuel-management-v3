<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Fixed Black Top Bar with Logo + Menu -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <!-- Logo WHITE -->
        <img
          src="/kkt_logo.png"
          alt="Kitty Kat Technologies"
          class="h-12 w-auto"
          style="filter: brightness(0) invert(1);">

        <!-- Menu -->
        <nav class="flex items-center gap-6">
          <a href="/" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</a>
          <a href="/orders" class="text-gray-400 hover:text-white transition-colors text-sm">Orders</a>
          <a href="/transfers" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Transfers</a>
          <a href="/parameters" class="text-gray-400 hover:text-white transition-colors text-sm">Parameters</a>
          <a href="/import" class="text-gray-400 hover:text-white transition-colors text-sm">Import</a>
          <a href="/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</a>
        </nav>
      </div>
    </div>

    <!-- Spacer for fixed navbar -->
    <div class="h-20"></div>

    <!-- Page Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
      <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
        <i class="fas fa-truck text-6xl text-green-500 mb-6"></i>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Fuel Transfers</h1>
        <p class="text-gray-600 mb-8">Fuel transfer management functionality will be implemented here</p>
        <div class="inline-flex items-center px-6 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
          <i class="fas fa-info-circle mr-2"></i>
          This page is under development. Please check back later.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Transfers page logic will be implemented here
</script>
