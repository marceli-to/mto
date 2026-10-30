<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PhPlus, PhTrash, PhArrowUp, PhArrowDown, PhBookmarkSimple, PhCurrencyCircleDollar, PhCaretRight } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import TiptapEditor from '@/components/ui/TiptapEditor.vue'
import QuoteSectionsEditor from './QuoteSectionsEditor.vue'
import QuoteBlockSaveForm from './QuoteBlockSaveForm.vue'

const props = defineProps({
  quoteId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel', 'loaded'])

const { get, post } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const isEdit = computed(() => !!props.quoteId)

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clients = ref([])
const blocks = ref([])
const selectedBlock = ref('')
const saveBlockDialog = ref({ show: false, title: '', content: '' })

const vatOptions = [
  { value: 0, label: 'None' },
  { value: 7.7, label: '7.7%' },
  { value: 8.1, label: '8.1%' }
]

// Pages need a stable key so their editors survive reordering
let pageKey = 0
function makePage(type, content = '', id = null, collapsed = false) {
  return { id, type, content, _key: ++pageKey, _collapsed: collapsed }
}

function emptyQuote() {
  return {
    label: 'Offerte',
    title: '',
    date: formatDateForInput(new Date()),
    client_id: '',
    intro_greeting: '',
    intro_text: '',
    vat_rate: 8.1,
    pages: [],
    sections: []
  }
}

const quote = ref(emptyQuote())

const hasPricesPage = computed(() => quote.value.pages.some(p => p.type === 'prices'))

// Sums CHF positions only; other currencies (licence fees in USD) don't add up
const grandTotal = computed(() => {
  return quote.value.sections
    .filter(s => !s.currency || s.currency === 'CHF')
    .reduce((sum, section) => {
      return sum + section.positions
        .filter(p => !p.amount_text)
        .reduce((sSum, p) => sSum + parseFloat(p.amount || 0), 0)
    }, 0)
})

const blockOptions = computed(() =>
  blocks.value.map(b => ({ value: b.id, label: b.title }))
)

async function fetchData() {
  loading.value = true
  try {
    const [clientsData, blocksData] = await Promise.all([
      get('/api/clients/get'),
      get('/api/quote-blocks/get')
    ])
    clients.value = clientsData.data || []
    blocks.value = blocksData.data || []

    if (isEdit.value) {
      const data = await get(`/api/quote/edit/${props.quoteId}`)
      quote.value = {
        ...data,
        client_id: data.client_id || '',
        date: formatDateForInput(data.date),
        pages: (data.pages || []).map(p => makePage(p.type, p.content || '', p.id, true)),
        sections: (data.sections || []).map(s => ({
          ...s,
          positions: s.positions || [],
          _collapsed: true
        }))
      }
      emit('loaded', data)
    } else {
      quote.value = emptyQuote()
      quote.value.pages = [
        makePage('prices'),
        ...blocks.value.filter(b => b.is_default).map(b => makePage('text', b.content, null, true))
      ]
    }
  } catch (e) {
    error('Failed to load data')
    if (isEdit.value) emit('cancel')
  } finally {
    loading.value = false
  }
}

watch(() => props.quoteId, () => {
  errors.value = {}
  fetchData()
})

function formatDateForInput(date) {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  return d.toISOString().split('T')[0]
}

function validate() {
  errors.value = {}
  if (!quote.value.title?.trim()) {
    errors.value.title = 'Title is required'
  }
  if (!quote.value.label?.trim()) {
    errors.value.label = 'Label is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/quote/update/${props.quoteId}`, quote.value)
      success('Quote updated')
      emit('saved', Number(props.quoteId))
    } else {
      const data = await post('/api/quote/create', quote.value)
      success('Quote created')
      emit('saved', data.quoteId)
    }
  } catch (e) {
    error('Failed to save quote')
  } finally {
    saving.value = false
  }
}

// Page management
function addTextPage() {
  quote.value.pages.push(makePage('text'))
}

function addPricesPage() {
  if (hasPricesPage.value) return
  quote.value.pages.push(makePage('prices'))
}

function addBlockPage() {
  const block = blocks.value.find(b => b.id === Number(selectedBlock.value))
  if (!block) return
  quote.value.pages.push(makePage('text', block.content || ''))
  selectedBlock.value = ''
}

const allCollapsed = computed(() => quote.value.pages.every(p => p._collapsed))

function toggleAllPages() {
  const collapse = !allCollapsed.value
  quote.value.pages.forEach(p => { p._collapsed = collapse })
}

function movePage(index, direction) {
  const target = index + direction
  const pages = quote.value.pages
  if (target < 0 || target >= pages.length) return
  ;[pages[index], pages[target]] = [pages[target], pages[index]]
}

function deletePage(index) {
  const page = quote.value.pages[index]
  if (page.type === 'prices') {
    if (!confirm('Delete the prices page with all its sections and positions?')) return
    quote.value.sections = []
  } else if (page.content && !confirm('Delete this page?')) {
    return
  }
  quote.value.pages.splice(index, 1)
}

// Label for a page card: its first heading, else the start of its text
function pageLabel(page) {
  if (page.type === 'prices') return 'Prices'
  const doc = new DOMParser().parseFromString(page.content || '', 'text/html')
  const heading = doc.querySelector('h2, h3')?.textContent?.trim()
  const text = heading || doc.body.textContent.trim()
  return text ? text.slice(0, 60) : 'Text page'
}

function saveToLibrary(page) {
  saveBlockDialog.value = { show: true, title: pageLabel(page), content: page.content }
}

function onBlockSaved(block) {
  blocks.value.push(block)
  saveBlockDialog.value.show = false
}

const clientOptions = computed(() =>
  clients.value.map(c => ({ value: c.id, label: `${c.name} - ${c.city || ''}` }))
)

onMounted(fetchData)
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="space-y-6">
      <!-- Letter (page 1) -->
      <div class="space-y-6">
        <div class="grid grid-cols-4 gap-4">
          <BaseInput
            v-model="quote.label"
            label="Label"
            placeholder="Offerte"
            required
            :error="errors.label"
            @focus="errors.label = null"
          />
          <div class="col-span-3">
            <BaseInput
              v-model="quote.title"
              label="Title"
              required
              :error="errors.title"
              @focus="errors.title = null"
            />
          </div>
        </div>

        <BaseSelect
          v-model="quote.client_id"
          label="Client"
          :options="clientOptions"
          placeholder="Select a client..."
        />

        <div class="grid grid-cols-2 gap-4">
          <BaseInput
            v-model="quote.date"
            label="Date"
            type="date"
          />
          <BaseSelect
            v-model="quote.vat_rate"
            label="VAT"
            :options="vatOptions"
          />
        </div>

        <BaseInput
          v-model="quote.intro_greeting"
          label="Greeting"
          placeholder="Hallo Laura"
        />

        <TiptapEditor
          v-model="quote.intro_text"
          label="Intro Text"
        />
      </div>

      <!-- Pages -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <h2 class="font-bold">Pages</h2>
          <button
            v-if="quote.pages.length > 0"
            type="button"
            @click="toggleAllPages"
            class="text-sm text-gray-500 hover:text-black cursor-pointer transition-colors"
          >
            {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
          </button>
        </div>
        <p class="text-sm text-gray-500 mb-4">
          Each page starts on a new sheet after the letter. Long text continues on the next sheet automatically.
        </p>

        <div v-if="quote.pages.length === 0" class="text-center py-8 text-gray-500">
          No pages yet.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(page, pageIndex) in quote.pages"
            :key="page._key"
            class="border border-gray-200 rounded-md"
          >
            <div
              :class="[page._collapsed ? 'rounded-md' : 'border-b border-gray-200 rounded-t-md', 'flex items-center gap-3 px-4 py-2 bg-gray-50']"
            >
              <button
                type="button"
                @click="page._collapsed = !page._collapsed"
                class="flex-1 min-w-0 flex items-center gap-3 text-left cursor-pointer"
                :title="page._collapsed ? 'Expand' : 'Collapse'"
              >
                <PhCaretRight
                  :class="[page._collapsed ? '' : 'rotate-90', 'w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform']"
                />
                <span class="text-xs text-gray-400 w-5">{{ pageIndex + 2 }}</span>
                <span
                  :class="[page.type === 'prices' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700', 'px-2 py-0.5 rounded-md text-xs font-medium']"
                >
                  {{ page.type === 'prices' ? 'Prices' : 'Text' }}
                </span>
                <span class="flex-1 text-sm text-gray-700 truncate">
                  {{ page.type === 'prices' ? quote.sections.map(s => s.title).filter(Boolean).join(', ') : pageLabel(page) }}
                </span>
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-if="page.type === 'text'"
                  type="button"
                  @click="saveToLibrary(page)"
                  :disabled="!page.content"
                  class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
                  title="Save to text blocks"
                >
                  <PhBookmarkSimple class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  @click="movePage(pageIndex, -1)"
                  :disabled="pageIndex === 0"
                  class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
                >
                  <PhArrowUp class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  @click="movePage(pageIndex, 1)"
                  :disabled="pageIndex >= quote.pages.length - 1"
                  class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
                >
                  <PhArrowDown class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  @click="deletePage(pageIndex)"
                  class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-100 cursor-pointer rounded-sm transition-colors"
                >
                  <PhTrash class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div v-show="!page._collapsed" class="p-4 space-y-6">
              <TiptapEditor
                v-model="page.content"
                :label="page.type === 'prices' ? 'Text above the prices (optional)' : null"
              />

              <QuoteSectionsEditor
                v-if="page.type === 'prices'"
                :sections="quote.sections"
              />
            </div>
          </div>
        </div>

        <!-- Add pages -->
        <div class="flex flex-wrap items-center gap-2 mt-4">
          <BaseButton type="button" variant="secondary" @click="addTextPage">
            <PhPlus class="w-4 h-4" /> Text page
          </BaseButton>
          <BaseButton v-if="!hasPricesPage" type="button" variant="secondary" @click="addPricesPage">
            <PhCurrencyCircleDollar class="w-4 h-4" /> Prices page
          </BaseButton>
          <div class="flex items-center gap-2 ml-auto">
            <div class="w-64">
              <BaseSelect
                v-model="selectedBlock"
                :options="blockOptions"
                placeholder="Insert text block..."
                compact
              />
            </div>
            <BaseButton type="button" variant="secondary" :disabled="!selectedBlock" @click="addBlockPage">
              Insert
            </BaseButton>
          </div>
        </div>

        <!-- Grand Total -->
        <div v-if="quote.sections.length > 0" class="mt-6 bg-gray-50 rounded-sm px-4 py-3">
          <div class="flex justify-between font-bold">
            <span>Grand Total (CHF)</span>
            <span>{{ formatCurrency(grandTotal) }}</span>
          </div>
        </div>
      </div>

      <!-- Stays in view while scrolling through long quotes -->
      <div class="sticky bottom-0 z-10 flex items-center justify-end gap-3 mt-8 py-4 bg-white border-t border-gray-100">
        <BaseButton type="button" variant="secondary" @click="emit('cancel')">
          {{ isEdit ? 'Close' : 'Cancel' }}
        </BaseButton>
        <BaseButton type="submit" :loading="saving">
          {{ isEdit ? 'Save' : 'Create' }}
        </BaseButton>
      </div>
    </form>

    <QuoteBlockSaveForm
      v-if="saveBlockDialog.show"
      :title="saveBlockDialog.title"
      :content="saveBlockDialog.content"
      @close="saveBlockDialog.show = false"
      @saved="onBlockSaved"
    />
  </div>
</template>
