<script setup lang="ts">
import { onMounted } from 'vue';
import GuestLayout from '@/App/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { User } from "@/types";
import axios from "axios";
import { useUserStore } from "@/stores/useUserStore";
import { route } from 'ziggy-js';
const userStore = useUserStore();


const form = useForm({
  username: '',
  password: ''
});
onMounted(() => {
  if (userStore.user) {
    window.location.href = handleRedirect(userStore.user);
  }
});
const submit = async () => {
  try {
    const response = await axios.post('/login', form);
    const user: User = response.data.user;
    userStore.setUser(user); // Lưu vào Pinia
    window.location.href = handleRedirect(user);
  } catch (error) {
    console.log(error)
    form.setError('username', 'Đăng nhập thất bại! Vui lòng kiểm tra lại.');
  }
};
const handleRedirect = (user: User) => {
  return userStore.user.login_redirect ?? route('branches.index')
};
</script>

<template>
  <GuestLayout>

    <Head title="Log in" />

    <div v-if="form.errors.username" class="mb-4 text-sm font-medium text-green-600">
      {{ form.errors.username }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="username" value="Tên đăng nhập" />
        <TextInput id="username" type="text" class="mt-1 block w-full" v-model="form.username" required autofocus autocomplete="username" />
      </div>
      <div class="mt-4">
        <InputLabel for="password" value="Mật khẩu" />
        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="current-password" />
      </div>


      <div class="mt-4 flex items-center justify-end">
        <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          Log in
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
