<script setup>
import { ref, onMounted, watch } from 'vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'verified', 'error']);

const captcha = ref(null);
const answer = ref('');
const loading = ref(false);
const error = ref(null);

const loadCaptcha = async () => {
    loading.value = true;
    error.value = null;
    answer.value = '';
    emit('update:modelValue', '');

    try {
        const response = await fetch('/api/captcha/generate');
        captcha.value = await response.json();
    } catch (e) {
        error.value = 'Failed to load CAPTCHA';
        emit('error');
    }

    loading.value = false;
};

const handleInput = () => {
    if (captcha.value && answer.value) {
        // Create a combined value for form submission
        emit('update:modelValue', JSON.stringify({
            id: captcha.value.id,
            answer: answer.value,
        }));
    } else {
        emit('update:modelValue', '');
    }
};

watch(answer, handleInput);

onMounted(() => {
    loadCaptcha();
});

defineExpose({ refresh: loadCaptcha });
</script>

<template>
    <div class="simple-captcha-widget">
        <div v-if="loading" class="flex items-center justify-center py-4">
            <ArrowPathIcon class="w-6 h-6 text-gray-400 animate-spin" />
        </div>

        <div v-else-if="error" class="text-center py-4">
            <p class="text-red-500 text-sm mb-2">{{ error }}</p>
            <button
                type="button"
                @click="loadCaptcha"
                class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
                Try Again
            </button>
        </div>

        <div v-else-if="captcha && captcha.enabled" class="space-y-3">
            <!-- Math CAPTCHA -->
            <div v-if="captcha.type === 'math'" class="flex items-center space-x-3">
                <span class="text-sm font-medium text-gray-700">{{ captcha.question }}</span>
                <input
                    type="text"
                    v-model="answer"
                    placeholder="?"
                    class="w-20 px-3 py-2 text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autocomplete="off"
                />
                <button
                    type="button"
                    @click="loadCaptcha"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                    title="Refresh"
                >
                    <ArrowPathIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Image CAPTCHA -->
            <div v-else class="space-y-3">
                <div class="flex items-center space-x-3">
                    <img
                        :src="captcha.image"
                        alt="CAPTCHA"
                        class="rounded-lg border border-gray-200"
                    />
                    <button
                        type="button"
                        @click="loadCaptcha"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                        title="Refresh"
                    >
                        <ArrowPathIcon class="w-5 h-5" />
                    </button>
                </div>
                <input
                    type="text"
                    v-model="answer"
                    placeholder="Enter characters"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autocomplete="off"
                />
            </div>
        </div>

        <!-- Hidden input for form submission -->
        <input type="hidden" name="captcha_id" :value="captcha?.id || ''" />
    </div>
</template>

<style scoped>
.simple-captcha-widget {
    min-height: 50px;
}
</style>