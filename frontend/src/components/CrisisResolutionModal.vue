<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    @click.self="$emit('close')">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

      <!-- Header -->
      <div class="bg-gradient-to-r from-red-600 to-orange-600 px-6 py-4 rounded-t-2xl text-white">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold">🚨 Crisis Resolution</h2>
            <p class="text-red-100 text-xs mt-0.5">
              {{ rec.station_name }} · {{ rec.depot_name }} · {{ rec.fuel_type }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
        <!-- Step indicator -->
        <div class="flex items-center gap-2 mt-3">
          <template v-for="(label, i) in stepLabels" :key="i">
            <div class="flex items-center gap-1.5 text-xs"
              :class="step > i + 1 ? 'text-green-300' : step === i + 1 ? 'text-white font-bold' : 'text-white/40'">
              <div class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold border"
                :class="step > i + 1 ? 'bg-green-400 border-green-400 text-white'
                      : step === i + 1 ? 'bg-white border-white text-red-600'
                      : 'border-white/30 text-white/40'">
                <i v-if="step > i + 1" class="fas fa-check" style="font-size: 9px"></i>
                <span v-else>{{ i + 1 }}</span>
              </div>
              <span class="hidden sm:inline">{{ label }}</span>
            </div>
            <div v-if="i < stepLabels.length - 1"
              class="flex-1 h-px"
              :class="step > i + 1 ? 'bg-green-400' : 'bg-white/25'"></div>
          </template>
        </div>
      </div>

      <!-- Body -->
      <div class="p-6">

        <!-- ── STEP 1: Review Options ──────────────────────────────────────── -->
        <div v-if="step === 1">
          <div class="mb-4">
            <h3 class="font-bold text-gray-800 mb-1">Critical Situation Summary</h3>
            <div class="grid grid-cols-3 gap-3 text-sm">
              <div class="bg-red-50 rounded-xl p-3 text-center border border-red-100">
                <div class="text-2xl font-black text-red-700">
                  {{ rec.days_until_critical_level != null ? Math.round(rec.days_until_critical_level) : '!' }}
                </div>
                <div class="text-xs text-red-500 font-semibold mt-0.5">days to critical</div>
              </div>
              <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                <div class="text-xl font-black text-gray-800">{{ formatTons(rec.current_stock_tons) }}</div>
                <div class="text-xs text-gray-500 font-semibold mt-0.5">current stock</div>
              </div>
              <div class="bg-orange-50 rounded-xl p-3 text-center border border-orange-100">
                <div class="text-xl font-black text-orange-700">{{ formatTons(options?.receiving_depot?.qty_needed_tons) }}</div>
                <div class="text-xs text-orange-500 font-semibold mt-0.5">needed to target</div>
              </div>
            </div>
          </div>

          <!-- Loading options -->
          <div v-if="loadingOptions" class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
            <p class="text-sm text-gray-500 mt-2">Finding resolution options…</p>
          </div>

          <!-- No options -->
          <div v-else-if="!hasSplitOptions && !hasTransferOptions" class="bg-gray-50 rounded-xl p-6 text-center">
            <i class="fas fa-times-circle text-red-400 text-3xl mb-3"></i>
            <p class="text-sm font-semibold text-gray-700">No automated options available</p>
            <p class="text-xs text-gray-500 mt-1">No eligible sibling depots found. Please escalate to management.</p>
          </div>

          <!-- Split Delivery options (preferred) -->
          <div v-else>
            <div v-if="hasSplitOptions" class="mb-4">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm font-bold text-blue-700">✈️ Split Delivery</span>
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">Recommended</span>
                <span class="text-xs text-gray-400">— redirect part of an in-transit delivery</span>
              </div>
              <div class="space-y-2">
                <label v-for="opt in options.split_delivery" :key="opt.donor_order_id"
                  class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                  :class="selectedOption?.donor_order_id === opt.donor_order_id
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50/30'">
                  <input type="radio" :value="opt" v-model="selectedOption" class="mt-1 accent-blue-600" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-semibold text-sm text-gray-900">{{ opt.donor_depot_name }}</span>
                      <span class="text-xs text-gray-500">Order #{{ opt.donor_order_number }}</span>
                      <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-medium">
                        arrives {{ opt.delivery_date }}
                      </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                      <div class="bg-white rounded-lg p-1.5 text-center border border-gray-100">
                        <div class="font-bold text-gray-800">{{ formatTons(opt.order_qty_tons) }}</div>
                        <div class="text-gray-400">order total</div>
                      </div>
                      <div class="bg-green-50 rounded-lg p-1.5 text-center border border-green-100">
                        <div class="font-bold text-green-700">{{ formatTons(opt.suggested_split_tons) }}</div>
                        <div class="text-gray-400">can redirect</div>
                      </div>
                      <div class="bg-white rounded-lg p-1.5 text-center border border-gray-100">
                        <div class="font-bold text-gray-600">{{ opt.days_to_delivery }}d</div>
                        <div class="text-gray-400">delivery days</div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Transfer options (fallback) -->
            <div v-if="hasTransferOptions">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm font-bold text-orange-700">↔️ Transfer from Sibling Depot</span>
                <span class="text-xs text-gray-400">— move current stock</span>
              </div>
              <div class="space-y-2">
                <label v-for="opt in options.transfer" :key="opt.donor_depot_id"
                  class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                  :class="selectedOption?.donor_depot_id === opt.donor_depot_id && selectedOption?.type === 'transfer'
                    ? 'border-orange-500 bg-orange-50'
                    : 'border-gray-200 hover:border-orange-300 hover:bg-orange-50/30'">
                  <input type="radio" :value="opt" v-model="selectedOption" class="mt-1 accent-orange-500" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-sm text-gray-900">{{ opt.donor_depot_name }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                      <div class="bg-white rounded-lg p-1.5 text-center border border-gray-100">
                        <div class="font-bold text-gray-800">{{ formatTons(opt.donor_current_stock_tons) }}</div>
                        <div class="text-gray-400">donor stock</div>
                      </div>
                      <div class="bg-orange-50 rounded-lg p-1.5 text-center border border-orange-100">
                        <div class="font-bold text-orange-700">{{ formatTons(opt.suggested_transfer_tons) }}</div>
                        <div class="text-gray-400">can transfer</div>
                      </div>
                      <div class="bg-white rounded-lg p-1.5 text-center border border-gray-100">
                        <div class="font-bold text-gray-600">{{ formatTons(opt.donor_stock_after_transfer_tons) }}</div>
                        <div class="text-gray-400">donor after</div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- ── STEP 2: Confirm Split ───────────────────────────────────────── -->
        <div v-if="step === 2 && selectedOption">
          <h3 class="font-bold text-gray-800 mb-4">Confirm the Proposal</h3>

          <!-- Summary of what will happen -->
          <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
            <div class="text-sm font-bold text-blue-800 mb-2">
              {{ selectedOption.type === 'split_delivery' ? '✈️ Delivery Split Proposal' : '↔️ Transfer Proposal' }}
            </div>
            <div class="space-y-1 text-sm text-blue-700">
              <div v-if="selectedOption.type === 'split_delivery'">
                <span class="font-medium">{{ formatTons(splitQty) }}</span> from
                order #{{ selectedOption.donor_order_number }} ({{ selectedOption.donor_depot_name }})
                will be <strong>redirected</strong> to {{ rec.depot_name }}
              </div>
              <div v-else>
                <span class="font-medium">{{ formatTons(splitQty) }}</span> will be
                <strong>physically transferred</strong> from {{ selectedOption.donor_depot_name }}
                to {{ rec.depot_name }}
              </div>
            </div>
          </div>

          <!-- Adjustable quantity -->
          <div class="mb-4">
            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide block mb-1">
              Quantity to redirect (tons)
              <span class="text-gray-400 font-normal normal-case">
                — max {{ formatTons(selectedOption.max_split_tons ?? selectedOption.max_transfer_tons) }}
              </span>
            </label>
            <input type="number" v-model.number="splitQty"
              :max="selectedOption.max_split_tons ?? selectedOption.max_transfer_tons"
              :min="1" step="5"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Impact table -->
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="font-bold text-gray-700 mb-2">{{ rec.depot_name }} (receives)</div>
              <div class="space-y-1">
                <div class="flex justify-between"><span class="text-gray-500">Current stock</span><span class="font-medium">{{ formatTons(rec.current_stock_tons) }}</span></div>
                <div class="flex justify-between text-green-700"><span>+ Split</span><span class="font-bold">+ {{ formatTons(splitQty) }}</span></div>
                <div class="flex justify-between border-t pt-1 mt-1 font-bold"><span>After split</span><span>{{ formatTons((rec.current_stock_tons || 0) + splitQty) }}</span></div>
              </div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
              <div class="font-bold text-gray-700 mb-2">{{ selectedOption.donor_depot_name }} (gives)</div>
              <div class="space-y-1">
                <div class="flex justify-between"><span class="text-gray-500">Current stock</span><span class="font-medium">{{ formatTons(selectedOption.donor_current_stock_tons) }}</span></div>
                <div class="flex justify-between text-red-600"><span>− Split</span><span class="font-bold">− {{ formatTons(splitQty) }}</span></div>
                <div class="flex justify-between border-t pt-1 mt-1 font-bold"><span>After split</span>
                  <span :class="(selectedOption.donor_current_stock_tons - splitQty) < 0 ? 'text-red-600' : ''">
                    {{ formatTons((selectedOption.donor_current_stock_tons || 0) - splitQty) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-800">
            <i class="fas fa-info-circle mr-1"></i>
            This is a <strong>proposal only</strong>. Contact the supplier to confirm the redirect. The system will track this case.
          </div>
        </div>

        <!-- ── STEP 3: PO for Critical Depot ─────────────────────────────── -->
        <div v-if="step === 3">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-bold">1</div>
            <h3 class="font-bold text-gray-800">Emergency PO — {{ rec.depot_name }}</h3>
          </div>

          <!-- Split fully covers shortage — no PO needed -->
          <div v-if="poForCriticalTons === 0" class="bg-green-50 border border-green-300 rounded-xl p-4 text-center">
            <div class="text-3xl mb-2">✅</div>
            <div class="font-bold text-green-800 text-sm mb-1">Split fully covers the shortage</div>
            <div class="text-xs text-green-600">
              The {{ selectedOption?.type === 'split_delivery' ? 'split delivery' : 'transfer' }}
              of <strong>{{ formatTons(splitQty) }}</strong> is enough to bring
              <strong>{{ rec.depot_name }}</strong> to target level.
              No additional PO needed for this depot.
            </div>
          </div>

          <!-- PO needed — show pre-fill info -->
          <div v-else class="space-y-3 text-sm">
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800">
              Even after the {{ selectedOption?.type === 'split_delivery' ? 'split' : 'transfer' }},
              this depot still needs <strong>{{ formatTons(poForCriticalTons) }}</strong> more to reach target level.
              Place this order now.
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-400 mb-1">Quantity</div>
                <div class="font-bold text-lg text-gray-900">{{ formatTons(poForCriticalTons) }}</div>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-400 mb-1">Urgency</div>
                <div class="font-bold text-red-600">HIGH — Order ASAP</div>
              </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-xs text-gray-600">
              <i class="fas fa-info-circle mr-1 text-blue-400"></i>
              Clicking "Place Order" will open the Create PO form pre-filled for
              <strong>{{ rec.depot_name }}</strong>. After creating, return here to complete the workflow.
            </div>
          </div>
        </div>

        <!-- ── STEP 4: PO for Donor Depot ─────────────────────────────────── -->
        <div v-if="step === 4">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-7 h-7 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center text-xs font-bold">2</div>
            <h3 class="font-bold text-gray-800">Compensating PO — {{ selectedOption?.donor_depot_name }}</h3>
          </div>
          <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 mb-4 text-sm text-orange-800">
            The donor gave <strong>{{ formatTons(splitQty) }}</strong>. This compensating order replaces
            exactly what was redirected.
          </div>
          <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-400 mb-1">Quantity</div>
                <div class="font-bold text-lg text-gray-900">{{ formatTons(poForDonorTons) }}</div>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-400 mb-1">Urgency</div>
                <div class="font-bold text-orange-600">MEDIUM — Normal lead time</div>
              </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-xs text-gray-600">
              <i class="fas fa-info-circle mr-1 text-blue-400"></i>
              Clicking "Place Order" will open the Create PO form pre-filled for
              <strong>{{ selectedOption?.donor_depot_name }}</strong>.
            </div>
          </div>
        </div>

        <!-- ── STEP 5: Done ────────────────────────────────────────────────── -->
        <div v-if="step === 5" class="text-center py-4">
          <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-circle text-green-500 text-3xl"></i>
          </div>
          <h3 class="font-bold text-gray-800 text-lg mb-2">Resolution Accepted</h3>
          <p class="text-sm text-gray-600 mb-4">
            Case #{{ caseId }} is now in <strong>Monitoring</strong>.
            Track it in the <strong>Cases</strong> tab.
          </p>
          <div class="bg-gray-50 rounded-xl p-4 text-xs text-left space-y-2 text-gray-600">
            <div class="flex items-center gap-2">
              <i class="fas fa-check text-green-500 w-4"></i>
              Resolution proposal accepted
            </div>
            <div class="flex items-center gap-2"
              :class="poForCriticalCreated ? 'text-gray-600' : 'text-amber-600'">
              <i :class="poForCriticalCreated ? 'fas fa-check text-green-500' : 'fas fa-exclamation-triangle text-amber-500'" class="w-4"></i>
              PO for {{ rec.depot_name }}
              {{ poForCriticalCreated ? 'created ✓' : '— SKIPPED (place manually!)' }}
            </div>
            <div class="flex items-center gap-2"
              :class="poForDonorCreated ? 'text-gray-600' : 'text-amber-600'">
              <i :class="poForDonorCreated ? 'fas fa-check text-green-500' : 'fas fa-exclamation-triangle text-amber-500'" class="w-4"></i>
              PO for {{ selectedOption?.donor_depot_name }}
              {{ poForDonorCreated ? 'created ✓' : '— SKIPPED (place manually!)' }}
            </div>
          </div>
        </div>

      </div><!-- end body -->

      <!-- Footer buttons -->
      <div class="px-6 pb-6 flex items-center justify-between gap-3">
        <!-- Left: Back / Skip -->
        <div class="flex gap-2">
          <button v-if="step > 1 && step < 5" @click="step--"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>Back
          </button>
          <button v-if="step === 3 || step === 4" @click="skipPO"
            class="px-4 py-2 text-sm text-amber-600 border border-amber-300 rounded-lg hover:bg-amber-50 transition-colors">
            Skip for now
          </button>
        </div>

        <!-- Right: Primary action -->
        <div class="flex gap-2">
          <button v-if="step === 5" @click="$emit('resolved'); $emit('close')"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-gray-800 text-white hover:bg-gray-900 transition-colors">
            Done — Go to Cases tab
          </button>
          <button v-else-if="step === 1" :disabled="!selectedOption || loadingOptions"
            @click="goToStep2"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
            Review Proposal <i class="fas fa-arrow-right ml-1"></i>
          </button>
          <button v-else-if="step === 2" :disabled="acceptLoading"
            @click="acceptProposal"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-40">
            <i v-if="acceptLoading" class="fas fa-spinner fa-spin mr-1"></i>
            Accept Proposal
          </button>
          <!-- Step 3: if split covers 100% → just advance; otherwise place PO -->
          <button v-else-if="step === 3 && poForCriticalTons === 0"
            @click="step++"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
            Next <i class="fas fa-arrow-right ml-1"></i>
          </button>
          <button v-else-if="step === 3"
            @click="openPOForCritical"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
            <i class="fas fa-plus mr-1"></i>Place PO for {{ rec.depot_name }}
          </button>
          <button v-else-if="step === 4"
            @click="openPOForDonor"
            class="px-5 py-2 text-sm font-bold rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition-colors">
            <i class="fas fa-plus mr-1"></i>Place Compensating PO
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { crisisApi } from '../services/api'

const props = defineProps({
  rec: { type: Object, required: true }
})
const emit = defineEmits(['close', 'resolved'])
const router = useRouter()

// ── State ────────────────────────────────────────────────────────────────────
const step           = ref(1)
const loadingOptions = ref(false)
const acceptLoading  = ref(false)
const options        = ref(null)
const selectedOption = ref(null)
const splitQty       = ref(0)
const caseId         = ref(null)
const poForCriticalCreated = ref(false)
const poForDonorCreated    = ref(false)

const stepLabels = ['Options', 'Confirm', 'PO #1', 'PO #2', 'Done']

// ── Computed ─────────────────────────────────────────────────────────────────
const hasSplitOptions = computed(() => options.value?.split_delivery?.length > 0)
const hasTransferOptions = computed(() => options.value?.transfer?.length > 0)

const poForCriticalTons = computed(() => {
  if (!selectedOption.value || !options.value?.receiving_depot) return 0
  const needed = options.value.receiving_depot.qty_needed_tons || 0
  return Math.max(0, roundUpTons(needed - splitQty.value))
})

const poForDonorTons = computed(() => roundUpTons(splitQty.value))

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  await loadOptions()
})

// ── Methods ───────────────────────────────────────────────────────────────────
async function loadOptions() {
  loadingOptions.value = true
  try {
    const res = await crisisApi.getOptions(props.rec.depot_id, props.rec.fuel_type_id)
    options.value = res.data.data
    // Auto-select the first split_delivery option if available
    if (options.value.split_delivery?.length) {
      selectedOption.value = options.value.split_delivery[0]
      splitQty.value = selectedOption.value.suggested_split_tons
    } else if (options.value.transfer?.length) {
      selectedOption.value = options.value.transfer[0]
      splitQty.value = selectedOption.value.suggested_transfer_tons
    }
  } catch (e) {
    console.error('Failed to load crisis options:', e)
  } finally {
    loadingOptions.value = false
  }
}

function goToStep2() {
  // When user selects an option, set the splitQty from suggested value
  if (selectedOption.value) {
    splitQty.value = selectedOption.value.suggested_split_tons
                  ?? selectedOption.value.suggested_transfer_tons
                  ?? 0
  }
  step.value = 2
}

async function acceptProposal() {
  acceptLoading.value = true
  try {
    const opt = selectedOption.value
    const isSplit = opt.type === 'split_delivery'

    const res = await crisisApi.acceptProposal({
      type:               opt.type,
      receiving_depot_id: props.rec.depot_id,
      fuel_type_id:       props.rec.fuel_type_id,
      qty_needed_tons:    options.value.receiving_depot.qty_needed_tons,
      split_qty_tons:     splitQty.value,
      donor_order_id:     isSplit ? opt.donor_order_id : undefined,
      donor_depot_id:     !isSplit ? opt.donor_depot_id : undefined,
    })

    caseId.value = res.data.case_id
    step.value = 3

  } catch (e) {
    alert('Error accepting proposal: ' + (e.response?.data?.error || e.message))
  } finally {
    acceptLoading.value = false
  }
}

// Open Orders Create PO form pre-filled for the CRITICAL depot (PO #1)
function openPOForCritical() {
  router.push({
    path: '/orders',
    query: {
      action:       'create_po',
      station_id:   props.rec.station_id,
      fuel_type_id: props.rec.fuel_type_id,
      quantity_tons: poForCriticalTons.value,
      crisis_case_id: caseId.value,
      crisis_po_role: 'critical',
    }
  })
  // Mark as created (user will return to the Cases tab)
  poForCriticalCreated.value = true
  emit('close')
}

// Open Orders Create PO form pre-filled for the DONOR depot (PO #2)
function openPOForDonor() {
  const opt = selectedOption.value
  router.push({
    path: '/orders',
    query: {
      action:         'create_po',
      station_id:     props.rec.station_id,
      fuel_type_id:   props.rec.fuel_type_id,
      depot_id:       opt.donor_depot_id,
      quantity_tons:  poForDonorTons.value,
      crisis_case_id: caseId.value,
      crisis_po_role: 'donor',
    }
  })
  poForDonorCreated.value = true
  emit('close')
}

function skipPO() {
  if (step.value === 3) {
    poForCriticalCreated.value = false
    step.value = 4
  } else if (step.value === 4) {
    poForDonorCreated.value = false
    step.value = 5
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatTons(val) {
  if (val == null || val === '') return '—'
  const n = parseFloat(val)
  if (isNaN(n)) return '—'
  return n >= 1000
    ? (n / 1000).toFixed(1) + 'K t'
    : n.toFixed(1) + ' t'
}

function roundUpTons(tons) {
  if (!tons || tons <= 0) return 0
  let step = tons < 50 ? 5 : tons < 200 ? 10 : tons < 500 ? 25 : 50
  return Math.ceil(tons / step) * step
}
</script>
