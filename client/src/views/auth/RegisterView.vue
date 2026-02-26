<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100 py-10">
    <div class="w-full max-w-lg p-8 bg-white rounded-lg shadow-md">
      <h2 class="mb-6 text-2xl font-bold text-center text-gray-800">Inscription</h2>
      
      <div class="flex justify-center mb-6">
        <SelectButton v-model="userType" :options="options" aria-labelledby="basic" />
      </div>

      <form @submit.prevent="handleRegister">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="firstname" class="block mb-2 text-sm font-medium text-gray-600">Prénom</label>
                <InputText id="firstname" v-model="form.firstName" class="w-full" required />
            </div>
            <div>
                <label for="lastname" class="block mb-2 text-sm font-medium text-gray-600">Nom</label>
                <InputText id="lastname" v-model="form.lastName" class="w-full" required />
            </div>
        </div>

        <div class="mb-4">
          <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
          <InputText id="email" v-model="form.email" class="w-full" type="email" required />
        </div>
        
        <div class="mb-6">
          <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Mot de passe</label>
          <Password id="password" v-model="form.password" class="w-full" toggleMask required />
        </div>

        <div v-if="userType === 'Professionnel'" class="mb-6 p-4 bg-blue-50 rounded text-sm text-blue-800">
            En tant que professionnel, vous pourrez créer vos services et recevoir des commandes après validation de votre profil.
        </div>

        <Button label="S'inscrire" type="submit" class="w-full" :loading="loading" />
      </form>
      
      <div class="mt-4 text-center">
        <p class="text-sm text-gray-600">
          Déjà un compte ? 
          <router-link to="/login" class="text-blue-500 hover:underline">Se connecter</router-link>
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
import SelectButton from 'primevue/selectbutton';

export default {
    components: {
        InputText,
        Password,
        Button,
        SelectButton
    },
    data() {
        return {
            userType: 'Candidat',
            options: ['Candidat', 'Professionnel'],
            form: {
                email: '',
                password: '',
                firstName: '',
                lastName: ''
            },
            loading: false,
            error: ''
        };
    },
    methods: {
        async handleRegister() {
            this.loading = true;
            this.error = '';
            const authStore = useAuthStore();
            
            // Prepare data based on backend requirements
            const payload = {
                email: this.form.email,
                plainPassword: this.form.password, // Adjust key if backend expects 'password' or 'plainPassword'
                // type: this.userType // You might need a way to signal the type (Role or separate endpoint)
            };
        
            // Assuming backend handles type differentiation or we hit different endpoints
            // For now, let's assume we pass a generic payload. 
            // REAL IMPLEMENTATION TODO: Check how backend handles registration types (Separate endpoints?)
            
            // Check RegistrationTest.php or relevant controller. 
            // Usually it accepts 'user' object or specific fields.
            
            try {
                await authStore.register(payload);
                // Auto login or redirect to login
                await authStore.login(this.form.email, this.form.password);
                this.$router.push('/dashboard');
            } catch (err) {
                this.error = "Erreur lors de l'inscription. Vérifiez les champs.";
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
