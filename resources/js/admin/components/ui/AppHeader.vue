<script setup>
import { ref } from 'vue'
import { PhList, PhX, PhSignOut, PhUsers, PhFolder, PhReceipt, PhCurrencyDollar } from '@phosphor-icons/vue'

const menuOpen = ref(false)
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content

function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function closeMenu() {
  menuOpen.value = false
}

const navigation = [
  { name: 'Projects', route: 'projects', icon: PhFolder },
  { name: 'Clients', route: 'clients', icon: PhUsers },
  { name: 'Invoices', route: 'invoices', icon: PhReceipt },
  { name: 'Expenses', route: 'expenses', icon: PhCurrencyDollar },
]
</script>

<template>
  <header class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <router-link to="/admin" class="flex items-center">
          <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 480">
            <circle cx="240" cy="240" r="240" fill="currentColor"/>
            <path fill="#fff" d="M206.5,138.3c15.1,0,27.3,5,36.5,15.1s13.9,23,13.9,39V296.3q0,5.7-5.4,5.7H225.6q-5.7,0-5.7-5.7V190.4c0-5.1-1.2-9.1-3.5-11.9a12.2,12.2,0,0,0-9.9-4.4c-4.5,0-7.9,1.5-10.2,4.4s-3.5,6.8-3.5,11.9V296.3c0,3.8-1.8,5.7-5.5,5.7H161.2q-5.4,0-5.4-5.7V190.4c0-5.1-1.2-9.1-3.5-11.9s-5.8-4.4-10.2-4.4-7.6,1.5-9.9,4.4-3.5,6.8-3.5,11.9V296.3c0,3.8-2,5.7-5.8,5.7H97.1q-5.4,0-5.4-5.7V192.4q0-24,13.8-39c9.3-10.1,21.5-15.1,36.6-15.1a42.2,42.2,0,0,1,32.2,14.1,42.1,42.1,0,0,1,32.2-14.1Zm70.2,173a5.7,5.7,0,0,1,1.6-4.2,5.8,5.8,0,0,1,4.1-1.6H382.9c3.6,0,5.4,2,5.4,5.8v24.9c0,3.6-1.8,5.5-5.4,5.5H282.4c-3.8,0-5.7-1.9-5.7-5.5V311.3Z"/>
          </svg>
        </router-link>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-6">
          <router-link
            v-for="item in navigation"
            :key="item.route"
            :to="{ name: item.route }"
            class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
            active-class="text-blue-600 font-medium"
          >
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.name }}
          </router-link>
        </nav>

        <!-- Logout Button (Desktop) -->
        <form action="/logout" method="POST" class="hidden md:block">
          <input type="hidden" name="_token" :value="csrfToken">
          <button
            type="submit"
            class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
          >
            <PhSignOut class="w-5 h-5" />
            Logout
          </button>
        </form>

        <!-- Mobile Menu Button -->
        <button
          type="button"
          @click="toggleMenu"
          class="md:hidden p-2 text-gray-600 hover:text-gray-900"
        >
          <PhList v-if="!menuOpen" class="w-6 h-6" />
          <PhX v-else class="w-6 h-6" />
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <Transition name="menu">
      <div v-if="menuOpen" class="md:hidden border-t border-gray-200 bg-white">
        <nav class="px-4 py-3 space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.route"
            :to="{ name: item.route }"
            class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-md"
            active-class="bg-blue-50 text-blue-600"
            @click="closeMenu"
          >
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.name }}
          </router-link>
          <form action="/logout" method="POST" class="pt-2 border-t border-gray-200 mt-2">
            <input type="hidden" name="_token" :value="csrfToken">
            <button
              type="submit"
              class="flex items-center gap-3 px-3 py-2 w-full text-left text-gray-600 hover:bg-gray-100 rounded-md"
            >
              <PhSignOut class="w-5 h-5" />
              Logout
            </button>
          </form>
        </nav>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
.menu-enter-active,
.menu-leave-active {
  transition: all 0.2s ease;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
