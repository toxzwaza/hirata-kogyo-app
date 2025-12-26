<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    defectTypes: Object,
    filters: Object,
});

const search = ref('');

const applySearch = () => {
    router.get(route('defect-types.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    search.value = '';
    router.get(route('defect-types.index'));
};
</script>

<template>
    <Head title="不良種類管理" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">不良種類管理</h2>
                <Link
                    :href="route('defect-types.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    新規登録
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- 検索 -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="flex gap-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="不良名で検索"
                            class="flex-1 rounded-md border-gray-300 shadow-sm"
                            @keyup.enter="applySearch"
                        />
                        <button
                            @click="applySearch"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            検索
                        </button>
                        <button
                            @click="clearSearch"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                            クリア
                        </button>
                    </div>
                </div>

                <!-- 一覧テーブル -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    不良名
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="type in defectTypes.data" :key="type.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ type.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link
                                        :href="route('defect-types.edit', type.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        編集
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ページネーション -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="defectTypes.links">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                全 {{ defectTypes.total }} 件中 {{ defectTypes.from }} - {{ defectTypes.to }} 件を表示
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in defectTypes.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 rounded-md text-sm',
                                        link.active ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>






