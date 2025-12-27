<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    clients: Array,
});

const form = useForm({
    client_id: '',
    product_name: '',
    drawing_number: '',
    image: null,
    weight_per_unit: 0,
    active_flag: true,
});

const imagePreview = ref(null);

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
    form.post(route('drawings.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="図番登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">図番登録</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
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

                        <div class="mt-6 flex justify-end gap-4">
                            <a
                                :href="route('drawings.index')"
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








