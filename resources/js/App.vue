<script setup>
import { RouterView } from 'vue-router';
import { useAuthStore } from './auth.js';

const auth = useAuthStore();

function reload() {
    window.location.reload();
}
</script>

<template>
    <main class="mx-auto grid min-h-dvh max-w-2xl content-center px-4 py-12 sm:px-8">
        <section v-if="auth.sessionError" class="panel mx-auto w-full max-w-md">
            <h1 class="text-2xl font-semibold">Не удалось проверить сессию</h1>
            <button class="primary-button" type="button" @click="reload">Повторить</button>
        </section>
        <RouterView v-else :key="$route.path" />
    </main>
</template>
