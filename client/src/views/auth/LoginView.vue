<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md">
      <h2 class="mb-6 text-2xl font-bold text-center text-gray-800">Connexion</h2>
      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
          <InputText id="email" v-model="email" class="w-full" type="email" required placeholder="nom@exemple.com" />
        </div>
        <div class="mb-6">
          <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Mot de passe</label>
          <Password id="password" v-model="password" class="w-full" :feedback="false" toggleMask required />
        </div>
        <div class="flex items-center justify-between mb-6">
            <a href="#" class="text-sm text-blue-500 hover:underline">Mot de passe oublié ?</a>
        </div>
        <Button label="Se connecter" type="submit" class="w-full" :loading="loading" />
      </form>
      <div class="mt-4 text-center">
        <p class="text-sm text-gray-600">
          Pas encore de compte ? 
          <router-link to="/register" class="text-blue-500 hover:underline">S'inscrire</router-link>
        </p>
      </div>
      <div v-if="error" class="mt-4 text-red-500 text-center text-sm">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';

export default {
    components: {
        InputText,
        Password,
        Button
    },
    data() {
        return {
            email: '',
            password: '',
            loading: false,
            error: ''
        };
    },
    methods: {
        async handleLogin() {
            this.loading = true;
            this.error = '';
            const authStore = useAuthStore();
            try {
                await authStore.login(this.email, this.password);
                this.$router.push(authStore.returnUrl || '/dashboard');
            } catch (err) {
                this.error = 'Email ou mot de passe incorrect.';
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
