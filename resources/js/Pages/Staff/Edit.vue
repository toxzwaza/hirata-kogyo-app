<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    staff: Object,
    staffTypes: Array,
});

const form = useForm({
    staff_type_id: props.staff.staff_type_id,
    name: props.staff.name,
    name_kana: props.staff.name_kana || '',
    login_id: props.staff.login_id,
    password: '',
    birth_date: props.staff.birth_date || '',
    postal_code: props.staff.postal_code || '',
    address: props.staff.address || '',
    tel: props.staff.tel || '',
    email: props.staff.email || '',
    tax_type: props.staff.tax_type,
    active_flag: props.staff.active_flag,
    bank_name: props.staff.bank_name || '',
    branch_name: props.staff.branch_name || '',
    account_type: props.staff.account_type || '',
    account_number: props.staff.account_number || '',
});

const submit = () => {
    form.put(route('staff.update', props.staff.id));
};

const deleteStaff = () => {
    if (confirm('このスタッフを削除しますか？')) {
        form.delete(route('staff.destroy', props.staff.id));
    }
};
</script>

<template>
    <Head title="スタッフ編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">スタッフ編集</h2>
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

                            <!-- 氏名（カナ） -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">氏名（カナ）</label>
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
                                <label class="block text-sm font-medium text-gray-700">パスワード（変更する場合のみ入力）</label>
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

                            <!-- 生年月日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">生年月日</label>
                                <input
                                    v-model="form.birth_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.birth_date }"
                                />
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.birth_date }}
                                </p>
                            </div>

                            <!-- 郵便番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">郵便番号</label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    placeholder="例: 701-0204"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.postal_code }"
                                />
                                <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.postal_code }}
                                </p>
                            </div>

                            <!-- 住所 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">住所</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.address }"
                                />
                                <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.address }}
                                </p>
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

                            <!-- 口座情報セクション -->
                            <div class="border-t border-gray-200 pt-6 mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">口座情報</h3>
                                
                                <!-- 取引銀行名 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">取引銀行名</label>
                                    <input
                                        v-model="form.bank_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        :class="{ 'border-red-500': form.errors.bank_name }"
                                    />
                                    <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.bank_name }}
                                    </p>
                                </div>

                                <!-- 支店名 -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">支店名</label>
                                    <input
                                        v-model="form.branch_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        :class="{ 'border-red-500': form.errors.branch_name }"
                                    />
                                    <p v-if="form.errors.branch_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.branch_name }}
                                    </p>
                                </div>

                                <!-- 口座種別 -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">口座種別</label>
                                    <input
                                        v-model="form.account_type"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        placeholder="例: 普通、当座"
                                        :class="{ 'border-red-500': form.errors.account_type }"
                                    />
                                    <p v-if="form.errors.account_type" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.account_type }}
                                    </p>
                                </div>

                                <!-- 口座番号 -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">口座番号</label>
                                    <input
                                        v-model="form.account_number"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        :class="{ 'border-red-500': form.errors.account_number }"
                                    />
                                    <p v-if="form.errors.account_number" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.account_number }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteStaff"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                削除
                            </button>
                            <div class="flex gap-4">
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


