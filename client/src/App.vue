<script setup>
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast';
</script>

<template>
  <Toast />
  <RouterView />
</template>

<style>
/* Global styles if needed */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
}
</style>
