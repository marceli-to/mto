<template>
<div>
  <notifications classes="notification"/>

  <div class="container">
    <main class="content" role="main">
      <div>
        <h1>{{title}}</h1>
        <form @submit.prevent="submit">
          <div>
            <div class="form-row">
              <label>Date *</label>
              <the-mask
                type="text"
                mask="##.##.####"
                :masked="true"
                class="is-datetime is-datepicker"
                name="date"
                v-model="expense.date"
              ></the-mask>
            </div>
            <div class="form-row" :class="errors.title ? 'has-error': ''">
              <label>Title *</label>
              <input
                type="text"
                @focus="removeError('title')"
                name="title"
                v-model="expense.title">
            </div>
            <div class="form-row">
              <label>Description</label>
              <textarea v-model="expense.description"></textarea>
            </div>
            <div class="form-row" :class="errors.amount ? 'has-error': ''">
              <label>Amount *</label>
              <input
                type="text"
                @focus="removeError('amount')"
                name="amount"
                v-model="expense.amount">
            </div>
            <div class="form-row" :class="errors.currency ? 'has-error': ''">
              <label>Currency *</label>
              <input
                type="text"
                @focus="removeError('currency')"
                name="amount"
                v-model="expense.currency">
            </div>
          </div>
          <form-buttons :route="'expenses'"></form-buttons>
        </form>
      </div>
    </main>
  </div>
</div>
</template>
<script>

// UI
import FormButtons from "@/components/ui/buttons/FormButtons.vue";

// Helpers
import Utils from "@/mixins/utils";
import Quick from "@/mixins/quick";
import Date from "@/mixins/date";
import Progress from "@/mixins/progress";
import { TheMask } from "vue-the-mask";

export default {
  components: {
    FormButtons: FormButtons,
    TheMask: TheMask
  },

  props: {
    type: String
  },

  mixins: [Utils, Quick, Date, Progress],

  data() {
    return {
      errors: {
        title: false,
      },

      expense: {
        date: '',
        title: null,
        description: null,
        amount: null,
        currency: 'CHF'
      },
    };
  },

  created() {

    // get expense data if in edit mode
    if (this.$props.type == "edit") {
      this.fetch(this.$route.params.id);
    }

  },

  methods: {

    fetch(id) {
      this.axios.get(`/api/expense/edit/${id}`).then(response => {
        this.expense = response.data;
        this.expense.date = moment(this.expense.date).format('DD.MM.YYYY');
      });
    },

    validate() {
      if (this.expense.title) {
        return true;
      }
      if (!this.expense.title) {
        this.errors.title = true;
      }
      return false;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return;
      }

      if (this.$props.type == "edit") {
        this.update();
      }

      if (this.$props.type == "create") {
        this.store();
      }
    },

    store() {
      let uri = "/api/expense/create";
      this.axios.post(uri, this.expense).then(response => {
        this.$router.push({ name: "expenses" });
      });
    },

    update() {
      let uri = `/api/expense/update/${this.$route.params.id}`;
      this.axios.post(uri, this.expense).then(response => {
        this.$router.push({ name: "expenses" });
      });
    },
  },

  computed: {
    title() {
      return this.$props.type == "edit"
        ? "Edit expense"
        : "Add expense";
    },
  }
};
</script>