<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- ヘッダー -->
        <header class="bg-white shadow-sm sticky top-0 z-10">
            <div class="max-w-md mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <h1 class="text-lg font-semibold text-gray-900">{{ title || '作業実績登録' }}</h1>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ログアウト
                    </Link>
                </div>
            </div>
        </header>

        <!-- メインコンテンツ -->
        <main class="max-w-md mx-auto px-4 py-4">
            <slot />
        </main>
    </div>
</template>










