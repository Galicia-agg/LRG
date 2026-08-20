<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    cashSession: Object,
});

const form = useForm({
    closing_amount: '0.00',
    notes: '',
});

function submit() {
    form.patch(route('cash-sessions.update', props.cashSession.id));
}
</script>

<template>
    <Head title="Cerrar caja" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Cerrar caja</h2>
        </template>

        <div class="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <span class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <Icon name="lock" class="h-5 w-5" />
                </span>

                <form @submit.prevent="submit" class="space-y-4">
                    <p class="text-sm text-slate-600">
                        Monto inicial: <span class="font-semibold text-slate-900">Q {{ cashSession.opening_amount }}</span>
                    </p>

                    <div>
                        <InputLabel for="closing_amount" value="Monto contado en caja" />
                        <TextInput
                            id="closing_amount"
                            v-model="form.closing_amount"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="form.errors.closing_amount" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Notas (opcional)" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        ></textarea>
                    </div>

                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Cerrar caja
                    </PrimaryButton>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
