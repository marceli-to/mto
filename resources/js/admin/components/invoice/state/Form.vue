<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href="" @click.prevent="hide()" class="icon-close-quick"></a>
          <h1>Update invoice status</h1>
          <form @submit.prevent="submit">
            <div>
              <div class="form-row is-sm">
                <label>Date paid</label>
                <the-mask
                  type="text"
                  mask="##.##.####"
                  :masked="true"
                  class="is-datetime is-datepicker"
                  name="date_paid"
                  v-model="date_paid"
                ></the-mask>
              </div>
              <div class="form-row">
                <label>Status</label>
                <div class="select-wrapper">
                  <select v-model="state_id" name="stateId">
                    <option v-for="(state,index) in states" :key="index" :value="state.id">{{ state.description }}</option>
                  </select>
                </div>
              </div>
              <div class="form-row" v-if="state_id == 6">
                <label>Reason</label>
                <textarea v-model="remarks"></textarea>
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

  mixins: [Utils, Date],

  props: {
    record: Object,
  },

  data() {
    return {
      id: null,
      date_paid: null,
      state_id: null,
      remarks: null,
      states: null
    };
  },

  mounted() {
    if (this.$props.record) {
      this.date_paid = moment(this.$props.record.date_paid).format('DD.MM.YYYY');
      this.state_id = this.$props.record.state_id;
      this.id = this.$props.record.id;
      this.fetchStates(this.$props.record);
    }
  },

  methods: {

    fetchStates(record) {
      this.axios.get(`/api/invoice/states`).then(response => {
        this.states = response.data.data;
      });
    },

    submit() {
      this.update();
    },

    update() {
      
      let data = {
        'date_paid': this.date_paid,
        'state_id': this.state_id,
        'remarks': this.remarks
      };

      let uri = `/api/invoice/update/state/${this.id}`;
      this.axios.post(uri, data).then(response => {
        this.$router.push({ name: "invoices" });
        this.hide();
      });
    },

    hide() {
      this.$parent.toggleStateForm();
      this.$parent.reload();
    }
  },
};
</script>