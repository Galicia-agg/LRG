<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    opening_amount: '0.00',
});

function submit() {
    form.post(route('cash-sessions.store'));
}
</script>

<template>
    <Head title="Abrir caja" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Abrir caja</h2>
        </template>

        <div class="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <span class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <Icon name="lock" class="h-5 w-5" />
                </span>

                <form @submit.prevent="submit" class="space-y-4">
                    <p class="text-sm text-slate-600">
                        Debes abrir una caja con el monto inicial en efectivo antes de
                        registrar ventas en el punto de venta.
                    </p>

                    <div>
                        <InputLabel for="opening_amount" value="Monto inicial (efectivo)" />
                        <TextInput
                            id="opening_amount"
                            v-model="form.opening_amount"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="form.errors.opening_amount" class="mt-2" />
                    </div>

                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Abrir caja
                    </PrimaryButton>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
