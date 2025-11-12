<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import * as THREE from 'three'; // Import Three.js
import CLOUDS from 'vanta/src/vanta.clouds';
import { onBeforeUnmount, onMounted, ref } from 'vue';
type VantaEffect = { destroy: () => void };

const vantaRef = ref(null);
let vantaEffect = null;

onMounted(() => {
    if (vantaRef.value) {
        vantaEffect = CLOUDS({
            el: vantaRef.value,
            THREE: THREE,
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 20.0,
            minWidth: 20.0,

            backgroundColor: 0xffffff, // Biru Sedang (Medium Blue)
            skyColor: 0x68b8d7, // Biru Langit (Sky Blue)
            cloudColor: 0xadc1de, // Putih Murni
            cloudShadowColor: 0x183550, // Hitam
            sunColor: 0xff9919, // Kuning Pucat
            sunGlareColor: 0xff6633, // Putih Terang
            sunlightColor: 0xff9933, // Lavender Pucat (Memberikan kesan dingin)
            speed: 1.5,
        });
    }
});

onBeforeUnmount(() => {
    if (vantaEffect) {
        vantaEffect.destroy();
    }
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    npk: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<style scoped>
.vanta-container {
    width: 100%;
    height: 100%;
    /* Ensure the container has dimensions to display Vanta.js */
}
</style>

<template>
    <div class="relative min-h-screen">
        <div ref="vantaRef" class="vanta-container absolute inset-0 z-0"></div>
        <AuthBase class="relative z-10" title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="npk">NPK</Label>
                        <Input
                            id="npk"
                            type="npk"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="npk"
                            v-model="form.npk"
                            placeholder="Example : 240473"
                            class="bg-white"
                        />
                        <InputError :message="form.errors.npk" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password">Password</Label>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Password"
                            class="bg-white"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Log in
                    </Button>
                </div>

                <!-- <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
            </div> -->
            </form>
        </AuthBase>
    </div>
</template>
