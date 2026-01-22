<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('work-methods.store'));
};
</script>

<template>
    <Head title="作業方法登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業方法登録</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">作業方法名 *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end gap-4">
                            <a
                                :href="route('work-methods.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                キャンセル
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ form.processing ? '登録中...' : '登録' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>














