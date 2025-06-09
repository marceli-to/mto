<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href="" @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>Add a new position</h1>
          <form @submit.prevent="submit">
            <div class="">
              <div class="form-row is-sm" :class="errors.description ? 'has-error': ''">
                <label>Description</label>
                <textarea v-model="position.description" @focus="removeError('title')"></textarea>
              </div>
              <div class="form-row is-sm">
                <label>Periode</label>
                <input type="text" v-model="position.periode">
              </div>
              <div class="form-row">
                <label>Type</label>
                <div class="select-wrapper">
                  <select v-model="type" name="type">
                    <option value="flat">Flat</option>
                    <option value="bythehour">By the hour</option>
                    <option value="reminder">Reminder fee</option>
                  </select>
                </div>
              </div>
              <div class="grid-2x1fr" v-if="type == 'bythehour'">
                <div class="form-row">
                  <label>Rate</label>
                  <input type="text" v-model="position.rate" placeholder="i.e. 125.00">
                </div>
                <div class="form-row">
                  <label>Hours</label>
                  <input type="text" v-model="position.hours" placeholder="i.e. 3.25">
                </div>
              </div>
              <div class="form-row" v-if="type == 'is_flat'">
                <label>Amount</label>
                <input type="text" name="position.amount" v-model="position.amount">
              </div>
              <div class="form-row" v-if="type == 'is_fee'">
                <label>Amount</label>
                <input type="text" name="position.amount" v-model="position.amount">
              </div>
            </div>
            <div>
              <button type="submit" class="btn-secondary">Speichern</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Utils from "@/mixins/utils";
import Quick from "@/mixins/quick";
import Date from "@/mixins/date";
import { TheMask } from "vue-the-mask";
import moment from 'moment';

export default {
  components: {
    TheMask: TheMask
  },

  mixins: [Utils, Quick, Date],

  props: {
    record: Object,
  },

  data() {
    return {
      isEdit: false,

      errors: {
        description: false,
      },
      position: {
        id: null,
        description: null,
        periode: null,
        rate: null,
        hours: null,
        is_flat: 0,
        is_fee: 0,
        amount: null,
      },
      type: 'bythehour',
    };
  },


  mounted() {
    if (this.$props.record) {
      this.position = this.$props.record;
      this.isEdit = true;

      if (this.position.is_fee) {
        this.type = 'is_fee';
      }
      else if (this.position.is_flat) {
        this.type = 'is_flat';
      }
      else {
        this.type = 'bythehour';
      }
    }
  },

  methods: {

    validate() {
      if (!this.position.description) {
        this.errors.description = true;
        return false;
      }
      return true;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return false;
      }
      this.store();
    },

    store() {
      
      if (this.type == 'flat') {
        this.position.is_flat = 1;
      }
      else {
        this.position.amount = this.position.hours * this.position.rate;
      }

      if (this.isEdit) {
        const index = this.$parent.invoice.positions.findIndex(x => x.id === this.position.id);
        this.$parent.invoice.positions[index] = this.position;
      }
      else {
        this.$parent.invoice.positions.push(this.position);
      }

      this.toggleForm();
    },

    toggleForm() {
      this.$parent.togglePositionForm();
    }
  },
};
</script>