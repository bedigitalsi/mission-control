<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';

const messages = ref([]);
const meta = ref({ current_page: 0, last_page: 1, total: 0 });
const stats = ref({ total: 0, incoming: 0, outgoing: 0, today: 0, senders: [] });
const loading = ref(false);
const filterDirection = ref('');
const filterSender = ref('');
const searchText = ref('');
const expandedIds = ref(new Set());

function relativeTime(dateStr) {
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  return `${days}d ago`;
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleString('en-GB', {
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
  });
}

function formatPhone(phone) {
  if (!phone) return '';
  return phone;
}

const grouped = computed(() => {
  let filtered = messages.value;
  if (searchText.value.trim()) {
    const s = searchText.value.toLowerCase();
    filtered = filtered.filter(m =>
      m.message?.toLowerCase().includes(s) ||
      m.phone_number?.includes(s) ||
      m.sender_name?.toLowerCase().includes(s)
    );
  }
  const groups = {};
  filtered.forEach(msg => {
    const d = ( msg.sent_at || msg.created_at )?.slice(0, 10) || 'Unknown';
    (groups[d] ||= []).push(msg);
  });
  return Object.entries(groups).sort((a, b) => b[0].localeCompare(a[0]));
});

function formatDate(d) {
  const today = new Date().toISOString().slice(0, 10);
  const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
  if (d === today) return 'Today';
  if (d === yesterday) return 'Yesterday';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

async function fetchMessages(page = 1, append = false) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: 50 });
    if (filterDirection.value) params.set('direction', filterDirection.value);
    if (filterSender.value) params.set('sender', filterSender.value);
    const res = await fetch(`/api/sms?${params}`, { credentials: 'same-origin' });
    const json = await res.json();
    if (json.success) {
      messages.value = append ? [...messages.value, ...json.data] : json.data;
      meta.value = json.meta;
    }
  } catch (e) { console.error(e); }
  loading.value = false;
}

async function fetchStats() {
  try {
    const res = await fetch('/api/sms/stats', { credentials: 'same-origin' });
    const json = await res.json();
    if (json.success) stats.value = json.data;
  } catch (e) { console.error(e); }
}

function loadMore() {
  if (meta.value.current_page < meta.value.last_page) {
    fetchMessages(meta.value.current_page + 1, true);
  }
}

function toggle(id) {
  expandedIds.value.has(id) ? expandedIds.value.delete(id) : expandedIds.value.add(id);
}

function applyFilters() {
  fetchMessages(1);
}

onMounted(() => {
  fetchMessages(1);
  fetchStats();
});
</script>

<template>
  <AppLayout>
    <div class="min-h-screen py-8 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="mb-10">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-white tracking-tight flex items-center gap-3">
                <span class="text-3xl">📱</span>
                SMS Messages
              </h1>
              <p class="mt-1 text-gray-400 text-sm">All incoming and outgoing SMS history</p>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
          <div class="bg-[#192d33] border border-[#233f48] rounded-2xl p-4">
            <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
            <div class="text-xs text-gray-400 mt-1">Total Messages</div>
          </div>
          <div class="bg-[#192d33] border border-[#233f48] rounded-2xl p-4">
            <div class="text-2xl font-bold text-emerald-400">{{ stats.incoming }}</div>
            <div class="text-xs text-gray-400 mt-1">📥 Incoming</div>
          </div>
          <div class="bg-[#192d33] border border-[#233f48] rounded-2xl p-4">
            <div class="text-2xl font-bold text-[#13b6ec]">{{ stats.outgoing }}</div>
            <div class="text-xs text-gray-400 mt-1">📤 Outgoing</div>
          </div>
          <div class="bg-[#192d33] border border-[#233f48] rounded-2xl p-4">
            <div class="text-2xl font-bold text-amber-400">{{ stats.today }}</div>
            <div class="text-xs text-gray-400 mt-1">Today</div>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
          <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="searchText"
              type="text"
              placeholder="Search messages, numbers..."
              class="w-full bg-[#192d33]/50 border border-[#233f48]/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/30 focus:border-[#13b6ec]/50 transition-all"
            />
          </div>
          <select
            v-model="filterDirection"
            @change="applyFilters"
            class="rounded-xl border border-[#233f48]/50 bg-[#192d33]/50 px-3 py-2.5 text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/30"
          >
            <option value="">All directions</option>
            <option value="incoming">📥 Incoming</option>
            <option value="outgoing">📤 Outgoing</option>
          </select>
          <select
            v-model="filterSender"
            @change="applyFilters"
            class="rounded-xl border border-[#233f48]/50 bg-[#192d33]/50 px-3 py-2.5 text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/30"
          >
            <option value="">All senders</option>
            <option v-for="s in stats.senders" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        <!-- Loading -->
        <div v-if="loading && messages.length === 0" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-[#13b6ec]/30 border-t-[#13b6ec] rounded-full animate-spin"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="grouped.length === 0" class="text-center py-20">
          <div class="text-6xl mb-4">📱</div>
          <h3 class="text-xl font-semibold text-gray-300 mb-2">No SMS messages yet</h3>
          <p class="text-gray-500 text-sm max-w-md mx-auto">
            Incoming and outgoing SMS will appear here automatically.
          </p>
        </div>

        <!-- Messages Feed -->
        <div v-else class="space-y-6">
          <div v-for="[date, msgs] in grouped" :key="date">
            <!-- Date header -->
            <div class="flex items-center gap-3 mb-3">
              <div class="text-sm font-semibold text-gray-400">{{ formatDate(date) }}</div>
              <div class="flex-1 h-px bg-[#233f48]/50"></div>
              <div class="text-xs text-gray-500">{{ msgs.length }} msg{{ msgs.length > 1 ? 's' : '' }}</div>
            </div>

            <!-- Messages -->
            <div class="space-y-2">
              <div
                v-for="msg in msgs"
                :key="msg.id"
                @click="toggle(msg.id)"
                class="group bg-[#192d33] border rounded-2xl p-5 cursor-pointer transition-all duration-200 hover:shadow-lg hover:shadow-[#13b6ec]/5"
                :class="msg.direction === 'incoming'
                  ? 'border-[#233f48] border-l-4 border-l-emerald-500/60 hover:border-l-emerald-500'
                  : 'border-[#233f48] border-l-4 border-l-[#13b6ec]/60 hover:border-l-[#13b6ec]'"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <span class="text-lg flex-shrink-0">{{ msg.direction === 'incoming' ? '📥' : '📤' }}</span>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-sm text-white">{{ formatPhone(msg.phone_number) }}</span>
                        <span v-if="msg.sender_name"
                          class="text-xs px-2 py-0.5 rounded-full font-medium"
                          :class="{
                            'bg-purple-500/20 text-purple-300': msg.sender_name === 'alex',
                            'bg-pink-500/20 text-pink-300': msg.sender_name === 'sarah',
                            'bg-indigo-500/20 text-indigo-300': msg.sender_name === 'brandon',
                            'bg-gray-500/20 text-gray-300': !['alex','sarah','brandon'].includes(msg.sender_name)
                          }"
                        >{{ msg.sender_name }}</span>
                        <span v-if="msg.from_name" class="text-xs text-gray-500">via {{ msg.from_name }}</span>
                      </div>
                      <p class="text-sm mt-1.5 text-gray-400 leading-relaxed" :class="{ 'line-clamp-2': !expandedIds.has(msg.id) }">
                        {{ msg.message }}
                      </p>
                    </div>
                  </div>
                  <div class="text-xs text-gray-500 whitespace-nowrap flex-shrink-0">
                    {{ relativeTime(msg.sent_at || msg.created_at) }}
                  </div>
                </div>

                <!-- Expanded details -->
                <Transition
                  enter-active-class="transition-all duration-200 ease-out"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition-all duration-150 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <div v-if="expandedIds.has(msg.id)" class="mt-4 pt-4 border-t border-[#233f48] text-xs text-gray-500 space-y-1.5">
                    <div><span class="text-gray-400 font-medium">Time:</span> {{ formatTime(msg.sent_at || msg.created_at) }}</div>
                    <div><span class="text-gray-400 font-medium">Direction:</span> {{ msg.direction }}</div>
                    <div v-if="msg.provider"><span class="text-gray-400 font-medium">Provider:</span> {{ msg.provider }}</div>
                    <div v-if="msg.status"><span class="text-gray-400 font-medium">Status:</span>
                      <span class="ml-1 px-1.5 py-0.5 rounded text-xs"
                        :class="{
                          'bg-emerald-500/20 text-emerald-300': msg.status === 'received' || msg.status === 'delivered',
                          'bg-[#13b6ec]/20 text-[#13b6ec]': msg.status === 'sent',
                          'bg-red-500/20 text-red-300': msg.status === 'failed'
                        }"
                      >{{ msg.status }}</span>
                    </div>
                    <div v-if="msg.external_id"><span class="text-gray-400 font-medium">ID:</span> <code class="text-[#13b6ec] bg-[#0f1f24] px-1.5 py-0.5 rounded">{{ msg.external_id }}</code></div>
                  </div>
                </Transition>
              </div>
            </div>
          </div>

          <!-- Load more -->
          <div v-if="meta.current_page < meta.last_page" class="text-center pt-4">
            <button @click="loadMore" :disabled="loading"
              class="px-6 py-2.5 rounded-xl text-sm font-medium bg-[#13b6ec]/10 text-[#13b6ec] hover:bg-[#13b6ec]/20 transition-all disabled:opacity-40">
              {{ loading ? 'Loading...' : 'Load more' }}
            </button>
          </div>
        </div>

        <!-- Message count -->
        <div v-if="!loading && messages.length > 0" class="mt-8 text-center">
          <span class="text-xs text-gray-600">
            {{ meta.total }} total message{{ meta.total !== 1 ? 's' : '' }}
          </span>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
