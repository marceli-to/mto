<template>
  <div>
    <div class="quick-form is-time-entry">
      <div>
        <div>
          <a href @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>
            <span v-if="step == 1">What?</span>
            <span v-if="step == 2">For whom?</span>
            <span v-if="step == 3">When?</span>
          </h1>
          <form @submit.prevent="submit">
            <div>
              <div
                class="form-row is-sm"
                :class="errors.task ? 'has-error': ''"
                :style="step == 1 ? 'display:block' : 'display:none'"
              >
                <input
                  type="text"
                  @focus="removeError('task')"
                  @blur="next($event)"
                  name="name"
                  ref="name"
                  v-model="time.task"
                >
                <div class="autocomplete" style="display:none">
                  <ul>
                    <li>
                      <a href>
                        <span>Redesign Hypno</span> &bull; Studio am Meer
                      </a>
                    </li>
                    <li>
                      <a href>
                        <span>Sammelauftrag</span> «www.swissport.com» &bull; Swissport
                      </a>
                    </li>
                    <li>
                      <a href>
                        <span>Redesign «wbg.ch»</span> &bull; WBG AG
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="form-row is-sm" :style="step == 2 ? 'display:block' : 'display:none'">
                <div class="select-wrapper is-wide">
                  <select v-model="time.project_id" name="client_id" @change="next($event)">
                    <option :value="null">Select project...</option>
                    <option
                      v-for="record in projects"
                      :key="record.id"
                      :value="record.id"
                    >{{ record.name }} &bull; {{ record.client.name }}</option>
                  </select>
                </div>
              </div>
              <div class="form-row is-sm" :style="step == 3 ? 'display:block' : 'display:none'">
                <div class="grid-timer">
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##.####"
                      :masked="true"
                      class="is-datetime is-datepicker"
                      name="date"
                      placeholder="Today, 8.11.2019"
                      v-model="time.date"
                    ></the-mask>
                  </div>
                  <div></div>
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##"
                      :masked="true"
                      class="is-datetime"
                      name="timeStart"
                      placeholder="09.15"
                      v-model="time.timeStart"
                    ></the-mask>
                  </div>
                  <div class="is-separator">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="#e0e0e0"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="feather feather-arrow-right"
                    >
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                      <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                  </div>
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##"
                      :masked="true"
                      class="is-datetime"
                      name="timeEnd"
                      placeholder="10.45"
                      v-model="time.timeEnd"
                    ></the-mask>
                  </div>
                </div>
              </div>
            </div>
            <div class="time-browse">
              <a href="" @click.prevent="prevStep()" v-if="step > 1">Back</a>
              <span v-else></span>
              <button type="submit" class="btn-secondary" v-if="step == 3">Save</button>
              <a href="" @click.prevent="nextStep()" v-if="step < 3">Next</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Utils from "@/mixins/utils";
import Date from "@/mixins/date";
import Quick from "@/mixins/quick";
import { TheMask } from "vue-the-mask";

export default {
  components: {
    TheMask: TheMask
  },

  props: {
    type: String
  },

  mixins: [Utils, Quick, Date],

  data() {
    return {
      errors: {
        task: false
      },

      time: {
        task: null,
        project_id: null,
        is_billable: 1,
        date: null,
        timeStart: null,
        timeEnd: null,
        minutes: null
      },

      step: 1,
      
      entry: {
        1: '',
        2: '',
        3: ''
      },

      projects: null
    };
  },

  created() {
    // remove event listener
    window.removeEventListener("keypress", this.command);

    // set default date
    this.date = this.dateToday();

    // get projects
    this.axios.get(`/api/projects/get`).then(response => {
      this.projects = response.data.data;
    });
  },

  methods: {
    validate() {
      if (!this.time.task) {
        this.errors.task = true;
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
      this.axios.post(`/api/time/create`, this.time).then(response => {
        this.$router.push({ name: "timer" });
        this.$parent.reload();
      });
    },

    toggleForm() {
      this.$parent.toggleForm();
    },

    next(event) {
      let input = event.target;
      if (input.value.length > 0) {
        this.entry[this.step] = input.options != undefined ? input.options[input.options.selectedIndex].text : input.value;
      }
      else {
        this.entry[this.step] = null;
      }
    },

    prevStep() {
      if (this.step > 1) {
        this.step--;
      }
    },

    nextStep() {
      if (this.entry[this.step] != null) {
        this.step++;
      }
    }
  },

  computed: {
   timeEntry() {
     let str = '';
     for(var i = 1; i <= this.step; i++) {
       if (this.entry[i].length > 0) {
        str = str + this.entry[i] + ' • ';
       }
     }
     return str;
    }
  }
};
</script>