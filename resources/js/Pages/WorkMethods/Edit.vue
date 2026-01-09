<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    workMethod: Object,
});

const form = useForm({
    name: props.workMethod.name,
});

const submit = () => {
    form.put(route('work-methods.update', props.workMethod.id));
};

const deleteMethod = () => {
    if (confirm('この作業方法を削除しますか？')) {
        form.delete(route('work-methods.destroy', props.workMethod.id));
    }
};
</script>

<template>
    <Head title="作業方法編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業方法編集</h2>
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

                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteMethod"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                削除
                            </button>
                            <div class="flex gap-4">
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
                                    {{ form.processing ? '更新中...' : '更新' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>











