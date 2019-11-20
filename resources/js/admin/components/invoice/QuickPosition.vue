<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href="" @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>Add a new position</h1>
          <form @submit.prevent="submit">
            <div class="">
              <div class="form-row is-sm">
                <label>Description</label>
                <textarea v-model="position.description"></textarea>
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
                  </select>
                </div>
              </div>
              <div class="grid-2x1fr" v-if="type == 'bythehour'">
                <div class="form-row">
                  <label>Rate</label>
                  <input type="text" v-model="position.rate" placeholder="i.e. 125">
                </div>
                <div class="form-row">
                  <label>Hours</label>
                  <input type="text" v-model="position.hour" placeholder="i.e. 3.25">
                </div>
              </div>
              <div class="form-row" v-if="type == 'flat'">
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
import Helpers from "@/mixins/helpers";
import Quick from "@/mixins/quick";
import Date from "@/mixins/date";
import { TheMask } from "vue-the-mask";
import moment from 'moment';

export default {
  components: {
    TheMask: TheMask
  },

  mixins: [Helpers, Quick, Date],

  data() {
    return {
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
        amount: null,
      },
      type: 'flat',
    };
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
      this.position.is_flat = this.type == 'flat' ? 1 : 0;
      this.$parent.invoice.positions.push(this.position);
      this.toggleForm();
    },

    toggleForm() {
      this.$parent.toggleForm();
    }
  },
};
</script>