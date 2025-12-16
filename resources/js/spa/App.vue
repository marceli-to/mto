<script setup>
import AppSidebar from '@/components/ui/AppSidebar.vue'
import AppMobileHeader from '@/components/ui/AppMobileHeader.vue'
import AppToast from '@/components/ui/AppToast.vue'
</script>

<template>
  <div>
    <!-- Desktop Sidebar -->
    <AppSidebar class="hidden lg:flex" />
    
    <!-- Mobile Header -->
    <AppMobileHeader />
    
    <!-- Main Content -->
    <main class="lg:ml-64 min-h-[calc(100vh-2rem)] bg-white m-4 rounded-2xl">
      <div class="pt-16 lg:pt-0">
        <div class="p-4 lg:p-6">
          <router-view />
        </div>
      </div>
    </main>
    
    <AppToast />
  </div>
</template>
