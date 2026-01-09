<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    staffTypes: Array,
});

const form = useForm({
    staff_type_id: '',
    name: '',
    login_id: '',
    password: '',
    address: '',
    tel: '',
    email: '',
    tax_type: 'taxable',
    active_flag: true,
});

const submit = () => {
    form.post(route('staff.store'));
};
</script>

<template>
    <Head title="スタッフ登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">スタッフ登録</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- スタッフ種別 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">スタッフ種別 *</label>
                                <select
                                    v-model="form.staff_type_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.staff_type_id }"
                                >
                                    <option value="">選択してください</option>
                                    <option
                                        v-for="type in staffTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.staff_type_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.staff_type_id }}
                                </p>
                            </div>

                            <!-- 氏名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">氏名 *</label>
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

                            <!-- ログインID -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ログインID *</label>
                                <input
                                    v-model="form.login_id"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.login_id }"
                                />
                                <p v-if="form.errors.login_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.login_id }}
                                </p>
                            </div>

                            <!-- パスワード -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">パスワード *</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- 住所 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">住所</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                />
                            </div>

                            <!-- 電話番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">電話番号</label>
                                <input
                                    v-model="form.tel"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                />
                            </div>

                            <!-- メールアドレス -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">メールアドレス</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- 課税区分 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">課税区分 *</label>
                                <select
                                    v-model="form.tax_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.tax_type }"
                                >
                                    <option value="taxable">課税</option>
                                    <option value="tax_exempt">非課税</option>
                                </select>
                                <p v-if="form.errors.tax_type" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.tax_type }}
                                </p>
                            </div>

                            <!-- 有効フラグ -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.active_flag"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">有効</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-4">
                            <a
                                :href="route('staff.index')"
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











