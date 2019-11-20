<template>
  <div v-if="hasForm">
    <the-mask
      type="text"
      mask="##.##"
      :masked="true"
      class="is-tiny"
      name="timeStart"
      :value="cStart"
      placeholder="09.15"
      v-model="timeStart"
    ></the-mask>
    <the-mask
      type="text"
      mask="##.##"
      :masked="true"
      class="is-tiny"
      name="timeEnd"
      :value="cEnd"
      placeholder="09.15"
      v-model="timeEnd"
    ></the-mask>
  </div>
  <div v-else @click="toggle()">
    <span>{{cStart}} - {{cEnd}}</span> <em>{{cHours}}</em>
  </div>
</template>
<script>
import { TheMask } from "vue-the-mask";

export default {

  components: {
    TheMask: TheMask
  },

  props: {
    date: String,
    start: String,
    end: String,
    minutes: Number,
  },

  data() {
    return {
      hasForm: false,
      timeStart: this.$props.start,
      timeEnd: this.$props.end,
    };
  },

  methods: {
    toggle() {
      this.hasForm = this.hasForm ? false : true;
    }
  },

  computed: {

    cStart() {
      return this.$moment(this.date + " " +this.start).format('HH.mm');
    },

    cEnd() {
      return this.$moment(this.date + " " +this.end).format('HH.mm');
    },

    cHours() {
      return this.minutes/60 + "h";
    }
  }
}
</script>