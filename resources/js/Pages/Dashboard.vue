<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="ダッシュボード" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ダッシュボード</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- 作業実績管理 -->
                    <Link
                        :href="route('work-records.index')"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">作業実績管理</h3>
                                    <p class="text-sm text-gray-500">作業実績の登録・編集・確認</p>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <!-- スタッフ請求書 -->
                    <Link
                        :href="route('staff-invoices.index')"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">スタッフ請求書</h3>
                                    <p class="text-sm text-gray-500">スタッフ請求書の作成・確認</p>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <!-- 客先請求書 -->
                    <Link
                        :href="route('client-invoices.index')"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">客先請求書</h3>
                                    <p class="text-sm text-gray-500">客先請求書の作成・確認</p>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <!-- マスタ管理セクション -->
                    <div class="md:col-span-2 lg:col-span-3">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">マスタ管理</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- 客先管理 -->
                            <Link
                                :href="route('clients.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">客先管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">客先情報の管理</p>
                                </div>
                            </Link>

                            <!-- 図番管理 -->
                            <Link
                                :href="route('drawings.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">図番管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">図番・重量・画像の管理</p>
                                </div>
                            </Link>

                            <!-- 作業方法管理 -->
                            <Link
                                :href="route('work-methods.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">作業方法管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">作業方法の管理</p>
                                </div>
                            </Link>

                            <!-- 作業単価管理 -->
                            <Link
                                :href="route('work-rates.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">作業単価管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">作業単価の履歴管理</p>
                                </div>
                            </Link>

                            <!-- スタッフ管理 -->
                            <Link
                                :href="route('staff.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">スタッフ管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">スタッフ情報の管理</p>
                                </div>
                            </Link>

                            <!-- 不良種類管理 -->
                            <Link
                                :href="route('defect-types.index')"
                                class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                            >
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900">不良種類管理</h4>
                                    <p class="text-sm text-gray-500 mt-1">不良種類の管理</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
