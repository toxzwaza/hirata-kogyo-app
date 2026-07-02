<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { buildBackSuffix } from '@/utils/listFilterBack';

const props = defineProps({
    client: Object,
});

// 一覧の検索条件を保持して戻る（編集URLのクエリ文字列を引き継ぐ）
const backUrl = route('clients.index') + window.location.search;
// 更新・削除後も一覧の絞り込みを保持するため back[...] で引き継ぐ
const backSuffix = buildBackSuffix();

const form = useForm({
    name: props.client.name,
    name_kana: props.client.name_kana,
});

const submit = () => {
    form.put(route('clients.update', props.client.id) + backSuffix);
};

const deleteClient = () => {
    if (confirm('この客先を削除しますか？')) {
        form.delete(route('clients.destroy', props.client.id) + backSuffix);
    }
};
</script>

<template>
    <Head title="客先編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">客先編集</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- 客先名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">客先名 *</label>
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

                            <!-- フリガナ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">フリガナ *</label>
                                <input
                                    v-model="form.name_kana"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.name_kana }"
                                />
                                <p v-if="form.errors.name_kana" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name_kana }}
                                </p>
                            </div>
                        </div>

                        <!-- エラーメッセージ -->
                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <!-- ボタン -->
                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteClient"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                削除
                            </button>
                            <div class="flex gap-4">
                                <a
                                    :href="backUrl"
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
















