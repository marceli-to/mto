<template>
  <div :class="rowClass">
    <label>{{label}}</label>
    <input
      type="text"
      :value="name">
  </div>
</template>
<script>
export default {
  props: {
    rowClass: String,
    label: String,
    name: String,
  },
}
</script>