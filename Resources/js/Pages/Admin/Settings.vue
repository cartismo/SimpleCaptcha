<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    PuzzlePieceIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
    Cog6ToothIcon,
    InformationCircleIcon,
    CalculatorIcon,
    PhotoIcon,
    DocumentCheckIcon,
    ClockIcon,
    AdjustmentsHorizontalIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
});

const storeTabsRef = ref(null);
const saving = ref(false);
const previewCaptcha = ref(null);
const loadingPreview = ref(false);

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.captcha.simple.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => saving.value = false,
    });
};

const resetAll = () => {
    if (confirm('Reset all settings to defaults?') && storeTabsRef.value) {
        Object.assign(storeTabsRef.value.localSettings, props.defaultSettings);
    }
};

const hasChanges = computed(() => {
    if (!storeTabsRef.value) return false;
    const currentStoreSettings = props.storeSettings[storeTabsRef.value.activeStoreId];
    if (!currentStoreSettings) return true;
    const original = { ...props.defaultSettings, ...(currentStoreSettings.settings || {}) };
    return JSON.stringify(storeTabsRef.value.localSettings) !== JSON.stringify(original) ||
           storeTabsRef.value.isEnabled !== currentStoreSettings.is_enabled;
});

const loadPreview = async () => {
    loadingPreview.value = true;
    try {
        const response = await fetch('/api/captcha/generate');
        previewCaptcha.value = await response.json();
    } catch (e) {
        console.error('Failed to load preview', e);
    }
    loadingPreview.value = false;
};

onMounted(() => {
    if (storeTabsRef.value?.localSettings?.enabled) {
        loadPreview();
    }
});

const formLabels = {
    login: { name: 'Login Form', desc: 'Protect user login' },
    register: { name: 'Registration', desc: 'Protect new account creation' },
    checkout_guest: { name: 'Guest Checkout', desc: 'Protect guest checkout form' },
    contact: { name: 'Contact Form', desc: 'Protect contact submissions' },
    newsletter: { name: 'Newsletter', desc: 'Protect newsletter signup' },
    reviews: { name: 'Product Reviews', desc: 'Protect review submissions' },
};

const captchaTypes = [
    { value: 'math', label: 'Math Question', desc: 'Simple arithmetic problems', icon: CalculatorIcon },
    { value: 'image', label: 'Image Text', desc: 'Distorted text image', icon: PhotoIcon },
];

const difficulties = [
    { value: 'easy', label: 'Easy', desc: 'Single digit addition (1+2)' },
    { value: 'medium', label: 'Medium', desc: 'Two digits, add/subtract (15-7)' },
    { value: 'hard', label: 'Hard', desc: 'Two digits, all operations (12*8)' },
];
</script>

<template>
    <AdminLayout :title="`${module.name} Settings`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.modules.installed.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl shadow-lg shadow-purple-500/25">
                            <PuzzlePieceIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Simple CAPTCHA</h1>
                            <p class="text-sm text-gray-500">Self-hosted CAPTCHA Protection</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="hasChanges" class="flex items-center text-sm text-amber-600 font-medium">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        Unsaved changes
                    </span>
                    <button
                        type="button"
                        @click="resetAll"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <ArrowPathIcon class="w-4 h-4 inline mr-2" />
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="saving || !hasChanges"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-violet-600 rounded-xl hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-purple-500/25"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-2" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>

        <StoreSettingsTabs ref="storeTabsRef" :stores="stores" :store-settings="storeSettings" :default-settings="defaultSettings" module-slug="simple-captcha">
            <template #default="{ store, settings, updateSetting, isEnabled }">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Status Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900">Module Status</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status</span>
                                    <span
                                        :class="settings.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ settings.enabled ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Version</span>
                                    <span class="text-sm font-mono text-gray-900">v{{ module.installed_version }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Type</span>
                                    <span class="text-sm text-gray-900">CAPTCHA</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Type -->
                        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl shadow-lg p-5 text-white">
                            <div class="flex items-center space-x-3 mb-3">
                                <component :is="settings.type === 'math' ? CalculatorIcon : PhotoIcon" class="w-8 h-8 opacity-80" />
                                <div>
                                    <p class="text-sm opacity-80">CAPTCHA Type</p>
                                    <p class="text-lg font-bold">{{ settings.type === 'math' ? 'Math Question' : 'Image Text' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Protected Forms Count -->
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg p-5 text-white">
                            <div class="flex items-center space-x-3">
                                <DocumentCheckIcon class="w-8 h-8 opacity-80" />
                                <div>
                                    <p class="text-sm opacity-80">Protected Forms</p>
                                    <p class="text-2xl font-bold">{{ Object.values(settings.protected_forms || {}).filter(Boolean).length }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Help Card -->
                        <div class="bg-green-50 rounded-2xl p-5 border border-green-100">
                            <div class="flex items-start space-x-3">
                                <InformationCircleIcon class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                                <div>
                                    <h4 class="text-sm font-medium text-green-900">No External Services</h4>
                                    <p class="text-sm text-green-700 mt-1">
                                        This CAPTCHA works entirely on your server. No API keys needed and no data sent to third parties.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Settings Forms -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Enable/Disable Toggle -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div :class="settings.enabled ? 'bg-green-100' : 'bg-gray-100'" class="p-3 rounded-xl transition-colors">
                                        <component :is="settings.enabled ? CheckCircleIcon : XCircleIcon"
                                            :class="settings.enabled ? 'text-green-600' : 'text-gray-400'"
                                            class="w-6 h-6"
                                        />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Enable CAPTCHA Protection</h3>
                                        <p class="text-sm text-gray-500">Protect your forms from spam and bots for {{ store?.name }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="updateSetting('enabled', !settings.enabled)"
                                    :class="settings.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    <span
                                        :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- CAPTCHA Type -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <AdjustmentsHorizontalIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">CAPTCHA Type</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <label
                                        v-for="type in captchaTypes"
                                        :key="type.value"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            :checked="settings.type === type.value"
                                            @change="updateSetting('type', type.value)"
                                            class="sr-only"
                                        />
                                        <div
                                            class="p-5 rounded-xl border-2 transition-all"
                                            :class="settings.type === type.value
                                                ? 'border-purple-500 bg-purple-50'
                                                : 'border-gray-200 hover:border-gray-300'"
                                        >
                                            <div class="flex items-center space-x-3 mb-3">
                                                <div :class="settings.type === type.value ? 'bg-purple-100' : 'bg-gray-100'" class="p-2 rounded-lg">
                                                    <component :is="type.icon" :class="settings.type === type.value ? 'text-purple-600' : 'text-gray-500'" class="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <span class="font-medium text-gray-900">{{ type.label }}</span>
                                                    <p class="text-xs text-gray-500">{{ type.desc }}</p>
                                                </div>
                                            </div>
                                            <CheckCircleIcon
                                                v-if="settings.type === type.value"
                                                class="absolute top-3 right-3 w-5 h-5 text-purple-500"
                                            />
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Math Difficulty (shown only for math type) -->
                        <div v-if="settings.type === 'math'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <CalculatorIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Math Difficulty</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-3 gap-4">
                                    <label
                                        v-for="diff in difficulties"
                                        :key="diff.value"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            :checked="settings.difficulty === diff.value"
                                            @change="updateSetting('difficulty', diff.value)"
                                            class="sr-only"
                                        />
                                        <div
                                            class="p-4 rounded-xl border-2 transition-all"
                                            :class="settings.difficulty === diff.value
                                                ? 'border-purple-500 bg-purple-50'
                                                : 'border-gray-200 hover:border-gray-300'"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="font-medium text-gray-900">{{ diff.label }}</span>
                                                <CheckCircleIcon
                                                    v-if="settings.difficulty === diff.value"
                                                    class="w-5 h-5 text-purple-500"
                                                />
                                            </div>
                                            <p class="text-xs text-gray-500">{{ diff.desc }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Image Settings (shown only for image type) -->
                        <div v-if="settings.type === 'image'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <PhotoIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Image Settings</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <!-- Character Length -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Character Length</label>
                                        <div class="flex space-x-2">
                                            <button
                                                v-for="len in [4, 5, 6, 7, 8]"
                                                :key="len"
                                                type="button"
                                                @click="updateSetting('length', len)"
                                                class="flex-1 py-3 rounded-xl border-2 font-medium text-sm transition-all"
                                                :class="settings.length === len
                                                    ? 'border-purple-500 bg-purple-50 text-purple-700'
                                                    : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                            >
                                                {{ len }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Case Sensitive -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Case Sensitivity</label>
                                        <label
                                            class="relative flex items-center p-4 rounded-xl cursor-pointer transition-all"
                                            :class="settings.case_sensitive
                                                ? 'bg-purple-50 border-2 border-purple-200'
                                                : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="settings.case_sensitive"
                                                @change="updateSetting('case_sensitive', $event.target.checked)"
                                                class="sr-only"
                                            />
                                            <div
                                                class="w-10 h-6 rounded-full relative transition-colors mr-3"
                                                :class="settings.case_sensitive ? 'bg-purple-500' : 'bg-gray-300'"
                                            >
                                                <div
                                                    class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                                    :class="settings.case_sensitive ? 'translate-x-5' : 'translate-x-1'"
                                                ></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium" :class="settings.case_sensitive ? 'text-purple-700' : 'text-gray-700'">
                                                    Case Sensitive
                                                </span>
                                                <p class="text-xs text-gray-500">Users must match exact case</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expiry Time -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <ClockIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Expiry Time</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex space-x-2">
                                    <button
                                        v-for="seconds in [60, 180, 300, 600, 900]"
                                        :key="seconds"
                                        type="button"
                                        @click="updateSetting('expiry', seconds)"
                                        class="flex-1 py-3 rounded-xl border-2 font-medium text-sm transition-all"
                                        :class="settings.expiry === seconds
                                            ? 'border-purple-500 bg-purple-50 text-purple-700'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                    >
                                        {{ seconds / 60 }} min
                                    </button>
                                </div>
                                <p class="mt-3 text-xs text-gray-500">Time before CAPTCHA expires and needs to be refreshed</p>
                            </div>
                        </div>

                        <!-- Protected Forms -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <DocumentCheckIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Protected Forms</h2>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Select which forms should require CAPTCHA verification</p>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                                    <label
                                        v-for="(info, key) in formLabels"
                                        :key="key"
                                        class="relative flex items-center p-4 rounded-xl cursor-pointer transition-all"
                                        :class="settings.protected_forms?.[key]
                                            ? 'bg-purple-50 border-2 border-purple-200'
                                            : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="settings.protected_forms?.[key]"
                                            @change="updateSetting('protected_forms', { ...settings.protected_forms, [key]: $event.target.checked })"
                                            class="sr-only"
                                        />
                                        <div
                                            class="w-10 h-6 rounded-full relative transition-colors mr-3 flex-shrink-0"
                                            :class="settings.protected_forms?.[key] ? 'bg-purple-500' : 'bg-gray-300'"
                                        >
                                            <div
                                                class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                                :class="settings.protected_forms?.[key] ? 'translate-x-5' : 'translate-x-1'"
                                            ></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium" :class="settings.protected_forms?.[key] ? 'text-purple-700' : 'text-gray-700'">
                                                {{ info.name }}
                                            </span>
                                            <p class="text-xs text-gray-500">{{ info.desc }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div v-if="settings.enabled" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <Cog6ToothIcon class="w-5 h-5 text-gray-400" />
                                        <h2 class="font-semibold text-gray-900">Live Preview</h2>
                                    </div>
                                    <button
                                        type="button"
                                        @click="loadPreview"
                                        :disabled="loadingPreview"
                                        class="text-sm text-purple-600 hover:text-purple-700 font-medium"
                                    >
                                        <ArrowPathIcon class="w-4 h-4 inline mr-1" :class="{ 'animate-spin': loadingPreview }" />
                                        Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="previewCaptcha && previewCaptcha.enabled" class="bg-gray-100 rounded-xl p-6">
                                    <div v-if="previewCaptcha.type === 'math'" class="text-center">
                                        <p class="text-lg font-medium text-gray-700 mb-4">{{ previewCaptcha.question }}</p>
                                        <input
                                            type="text"
                                            placeholder="Enter answer"
                                            class="w-32 px-4 py-2 text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                        />
                                    </div>
                                    <div v-else class="text-center">
                                        <img :src="previewCaptcha.image" alt="CAPTCHA" class="mx-auto mb-4 rounded-lg" />
                                        <input
                                            type="text"
                                            placeholder="Enter characters"
                                            class="w-40 px-4 py-2 text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                        />
                                    </div>
                                </div>
                                <div v-else class="text-center text-gray-500 py-8">
                                    <p>Save settings to see a preview</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>