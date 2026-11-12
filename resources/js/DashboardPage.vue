<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './auth.js';
import { errorMessage } from './http.js';

const auth = useAuthStore();
const router = useRouter();
const name = ref(auth.user.name);
const error = ref('');
const saved = ref(false);
const busy = ref(false);

async function perform(action) {
    busy.value = true;
    error.value = '';
    saved.value = false;

    try {
        await action();
    } catch (requestError) {
        if (requestError.response?.status === 401) {
            auth.user = null;
            await router.replace({ name: 'login' });
        } else {
            error.value = errorMessage(requestError);
        }
    } finally {
        busy.value = false;
    }
}

function save() {
    return perform(async () => {
        await auth.updateName(name.value);
        name.value = auth.user.name;
        saved.value = true;
    });
}

function logout() {
    return perform(async () => {
        await auth.logout();
        await router.replace({ name: 'login' });
    });
}
</script>

<template>
    <section v-if="auth.user" class="panel" aria-labelledby="profile-title">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <h1 id="profile-title" class="text-3xl font-semibold tracking-tight">Мой профиль</h1>
            <button class="secondary-button" type="button" :disabled="busy" @click="logout">Выйти</button>
        </header>

        <dl class="rounded-xl bg-stone-100 px-4 py-3 dark:bg-stone-800">
            <dt class="text-sm text-stone-600 dark:text-stone-400">Электронная почта</dt>
            <dd class="mt-1 break-all font-medium">{{ auth.user.email }}</dd>
        </dl>

        <form class="grid gap-4" @submit.prevent="save">
            <label class="field">
                Имя
                <input v-model="name" class="input" type="text" autocomplete="name" maxlength="255" required>
            </label>
            <button class="primary-button justify-self-start" type="submit" :disabled="busy || name === auth.user.name">
                Сохранить имя
            </button>
        </form>

        <p v-if="saved" class="notice" role="status">Имя обновлено.</p>
        <p v-if="error" class="notice-error" role="alert">{{ error }}</p>
    </section>
</template>
