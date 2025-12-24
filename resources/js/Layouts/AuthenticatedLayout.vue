<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// マスタ管理のルートがアクティブかどうかを判定
const isMasterActive = computed(() => {
    const currentRoute = route().current();
    if (!currentRoute || typeof currentRoute !== 'string') return false;
    
    const masterRoutePrefixes = [
        'clients.',
        'drawings.',
        'work-methods.',
        'work-rates.',
        'staff.',
        'defect-types.',
    ];
    
    return masterRoutePrefixes.some(prefix => currentRoute.startsWith(prefix));
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-gradient-to-r from-[#ebe32b] via-[#f5f055] to-[#ebe32b] border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <img
                                        src="/storage/logo/hirata-logo.png"
                                        alt="平田工業"
                                        class="block h-9 w-auto"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('work-records.index')" :active="route().current('work-records.*')">
                                    作業実績管理
                                </NavLink>
                                <NavLink :href="route('staff-invoices.index')" :active="route().current('staff-invoices.*')">
                                    スタッフ請求書
                                </NavLink>
                                <NavLink :href="route('client-invoices.index')" :active="route().current('client-invoices.*')">
                                    客先請求書
                                </NavLink>
                                
                                <!-- マスタ管理ドロップダウン -->
                                <div class="relative group flex items-center">
                                    <NavLink 
                                        :href="route('clients.index')" 
                                        :active="isMasterActive"
                                        class="cursor-pointer"
                                    >
                                        マスタ管理
                                        <svg
                                            class="ml-1 -mr-0.5 h-4 w-4 inline-block"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </NavLink>
                                    
                                    <!-- ドロップダウンメニュー -->
                                    <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 border border-gray-200">
                                        <div class="py-1">
                                            <Link :href="route('clients.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                客先管理
                                            </Link>
                                            <Link :href="route('drawings.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                図番管理
                                            </Link>
                                            <Link :href="route('work-methods.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                作業方法管理
                                            </Link>
                                            <Link :href="route('work-rates.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                作業単価管理
                                            </Link>
                                            <Link :href="route('staff.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                スタッフ管理
                                            </Link>
                                            <Link :href="route('defect-types.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                不良種類管理
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('work-records.index')" :active="route().current('work-records.*')">
                            作業実績管理
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('staff-invoices.index')" :active="route().current('staff-invoices.*')">
                            スタッフ請求書
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('client-invoices.index')" :active="route().current('client-invoices.*')">
                            客先請求書
                        </ResponsiveNavLink>
                        <div class="px-4 py-2">
                            <div class="text-sm font-medium text-gray-500 mb-1">マスタ管理</div>
                            <div class="pl-4 space-y-1">
                                <ResponsiveNavLink :href="route('clients.index')" :active="route().current('clients.*')">
                                    客先管理
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('drawings.index')" :active="route().current('drawings.*')">
                                    図番管理
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('work-methods.index')" :active="route().current('work-methods.*')">
                                    作業方法管理
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('work-rates.index')" :active="route().current('work-rates.*')">
                                    作業単価管理
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('staff.index')" :active="route().current('staff.*')">
                                    スタッフ管理
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('defect-types.index')" :active="route().current('defect-types.*')">
                                    不良種類管理
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
