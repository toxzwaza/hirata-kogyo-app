<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    drawing: Object,
    clients: Array,
    workRates: { type: Array, default: () => [] },
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '—';
    return '¥' + new Intl.NumberFormat('ja-JP').format(num);
};

const formatDate = (date) => {
    return date ? date : '';
};

// 一覧の検索条件を保持して戻る（編集URLのクエリ文字列を引き継ぐ）
const backUrl = route('drawings.index') + window.location.search;

const form = useForm({
    client_id: props.drawing.client_id,
    product_name: props.drawing.product_name,
    drawing_number: props.drawing.drawing_number,
    image: null,
    weight_per_unit: props.drawing.weight_per_unit,
    active_flag: props.drawing.active_flag,
});

const imagePreview = ref(props.drawing.image_path ? `/storage/${props.drawing.image_path}` : null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.transform((data) => {
        // 画像がnullの場合は除外
        const transformed = { ...data };
        if (!transformed.image) {
            delete transformed.image;
        }
        // _methodフィールドを追加してPUTメソッドを指定
        transformed._method = 'PUT';
        return transformed;
    }).post(route('drawings.update', props.drawing.id), {
        forceFormData: true,
    });
};

const deleteDrawing = () => {
    if (confirm('この図番を削除しますか？')) {
        form.delete(route('drawings.destroy', props.drawing.id));
    }
};
</script>

<template>
    <Head title="図番編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">図番編集</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <!-- 成功メッセージ -->
                    <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ $page.props.flash.success }}
                    </div>
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- 客先 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">客先 *</label>
                                <select
                                    v-model="form.client_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.client_id }"
                                >
                                    <option value="">選択してください</option>
                                    <option
                                        v-for="client in clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.client_id }}
                                </p>
                            </div>

                            <!-- 図番 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">図番 *</label>
                                <input
                                    v-model="form.drawing_number"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.drawing_number }"
                                />
                                <p v-if="form.errors.drawing_number" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.drawing_number }}
                                </p>
                            </div>

                            <!-- 品名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">品名 *</label>
                                <input
                                    v-model="form.product_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.product_name }"
                                />
                                <p v-if="form.errors.product_name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.product_name }}
                                </p>
                            </div>

                            <!-- 1個あたり重量 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">1個あたり重量（kg） *</label>
                                <input
                                    v-model.number="form.weight_per_unit"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.weight_per_unit }"
                                />
                                <p v-if="form.errors.weight_per_unit" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.weight_per_unit }}
                                </p>
                            </div>

                            <!-- 画像 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">図面画像</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.image }}
                                </p>
                                <div v-if="imagePreview" class="mt-4">
                                    <img :src="imagePreview" alt="プレビュー" class="max-w-xs rounded-lg" />
                                </div>
                            </div>

                            <!-- 有効フラグ -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.active_flag"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">使用中</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteDrawing"
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

                <!-- 作業単価（閲覧専用。編集は作業単価マスタで行う） -->
                <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-800">作業単価（現行）</h3>
                        <div class="flex gap-2">
                            <Link
                                :href="route('work-rates.create', { drawing_id: drawing.id })"
                                class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-1.5 px-3 rounded"
                            >
                                この図番の単価を新規登録
                            </Link>
                            <Link
                                :href="route('work-rates.index', { drawing_id: drawing.id })"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-bold py-1.5 px-3 rounded"
                            >
                                全単価履歴を一覧で見る
                            </Link>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 mb-3">
                        個単価 = 1個あたり重量({{ drawing.weight_per_unit }}kg) × 客先kg単価（円単位で四捨五入）。単価の編集は作業単価マスタで行います。
                    </p>

                    <div v-if="workRates.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作業方法</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客先単価<br>(円/kg)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客先個単価<br>(円/個)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">スタッフ単価<br>(円/kg)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">スタッフ残業<br>(円/kg)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">適用期間</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="rate in workRates" :key="rate.work_rate_id">
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ rate.work_method_name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ formatNumber(rate.rate_employee) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ formatNumber(rate.unit_price_employee) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ formatNumber(rate.rate_contractor) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ formatNumber(rate.rate_overtime) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        {{ formatDate(rate.effective_from) }} ～ {{ formatDate(rate.effective_to) }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span
                                            :class="rate.active_flg !== false ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        >
                                            {{ rate.active_flg !== false ? '有効' : '無効' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <Link
                                            :href="route('work-rates.edit', { work_rate: rate.work_rate_id })"
                                            class="text-blue-600 hover:text-blue-900 font-medium"
                                        >
                                            マスタで編集
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-sm text-gray-500 py-4">
                        この図番には現行の作業単価が設定されていません。
                        <Link
                            :href="route('work-rates.create', { drawing_id: drawing.id })"
                            class="text-blue-600 hover:text-blue-900 font-medium ml-1"
                        >
                            新規登録する
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>














