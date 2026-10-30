<script setup>
import { useConfirm } from '@/composables/useConfirm'
import ConfirmDialog from './ConfirmDialog.vue'

const { dialog, settle } = useConfirm()
</script>

<template>
  <ConfirmDialog
    :show="dialog.show"
    :title="dialog.title"
    :message="dialog.message"
    :confirm-label="dialog.confirmLabel"
    @confirm="settle(true)"
    @cancel="settle(false)"
  />
</template>
