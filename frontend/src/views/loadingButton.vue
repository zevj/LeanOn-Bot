<template>
  <button 
    :disabled="loading || disabled"
    @click="handleClick"
    :class="['btn', { 'btn-disabled': loading || disabled }]"
  >
    <span v-if="loading" class="spinner"></span>
    <span v-else>
      <slot />
    </span>
  </button>
</template>

<script>
export default {
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  methods: {
    handleClick() {
      if (this.loading || this.disabled) return;
      this.$emit('click');
    }
  }
}

</script>

<style scoped>
.btn-disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #fff;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: inline-block;
}



@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>