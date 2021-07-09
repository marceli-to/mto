<template>
  <div>
    <page-header />
    <expense-form type="create"></expense-form>
  </div>
</template>
<script>
import PageHeader from '@/layout/PageHeader.vue';
import expenseForm from '@/components/expense/form.vue';
  export default {
    components: {
      PageHeader: PageHeader,
      expenseForm: expenseForm
    }
  }
</script>