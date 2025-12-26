<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    drawings: Object,
    clients: Array,
    filters: Object,
});

const search = ref('');
const clientId = ref(null);
const activeFlag = ref(null);

const applyFilters = () => {
    router.get(route('drawings.index'), {
        search: search.value,
        client_id: clientId.value,
        active_flag: activeFlag.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    clientId.value = null;
    activeFlag.value = null;
    router.get(route('drawings.index'));
};
</script>

<template>
    <Head title="図番管理" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">図番管理</h2>
                <Link
                    :href="route('drawings.create')"
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="図番または品名で検索"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <select
                                v-model="clientId"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべての客先</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="activeFlag"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべて</option>
                                <option :value="true">使用中</option>
                                <option :value="false">使用停止</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="applyFilters"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                検索
                            </button>
                            <button
                                @click="clearFilters"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 一覧テーブル -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    図番
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    品名
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    客先
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    重量（kg/個）
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ステータス
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="drawing in drawings.data" :key="drawing.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ drawing.drawing_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ drawing.product_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ drawing.client.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ drawing.weight_per_unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="drawing.active_flag ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ drawing.active_flag ? '使用中' : '使用停止' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link
                                        :href="route('drawings.edit', drawing.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        編集
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ページネーション -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="drawings.links">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                全 {{ drawings.total }} 件中 {{ drawings.from }} - {{ drawings.to }} 件を表示
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in drawings.links"
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






