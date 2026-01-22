<script setup>
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    currentStaff: Object,
    drawings: Array,
    workMethods: Array,
    defectTypes: Array,
});

// 得意先リスト（重複なし）
const clients = computed(() => {
    const clientMap = new Map();
    props.drawings.forEach(drawing => {
        if (drawing.client && !clientMap.has(drawing.client.id)) {
            clientMap.set(drawing.client.id, drawing.client);
        }
    });
    return Array.from(clientMap.values()).sort((a, b) => a.name.localeCompare(b.name));
});

// フィルター用のリアクティブ変数
const clientFilter = ref('');
const drawingNumberFilter = ref('');

// フィルターされた図番リスト
const filteredDrawings = computed(() => {
    let filtered = props.drawings;
    
    // 得意先でフィルター
    if (clientFilter.value) {
        filtered = filtered.filter(d => d.client && d.client.id === parseInt(clientFilter.value));
    }
    
    // 図番でフィルター（部分一致）
    if (drawingNumberFilter.value) {
        const searchText = drawingNumberFilter.value.toLowerCase();
        filtered = filtered.filter(d => 
            d.drawing_number.toLowerCase().includes(searchText)
        );
    }
    
    return filtered;
});

// 図番入力で選択された場合の処理
const onDrawingNumberInput = (event) => {
    const inputValue = event.target.value.trim();
    if (!inputValue) {
        form.drawing_id = '';
        form.work_method_id = '';
        return;
    }
    
    // 完全一致する図番を探す
    const matchedDrawing = filteredDrawings.value.find(d => d.drawing_number === inputValue);
    if (matchedDrawing) {
        form.drawing_id = matchedDrawing.id;
        form.work_method_id = ''; // 図番が変わったら作業方法をリセット
    } else {
        // 完全一致しない場合は、部分一致で最初に見つかったものを設定
        const partialMatch = filteredDrawings.value.find(d => 
            d.drawing_number.toLowerCase().startsWith(inputValue.toLowerCase())
        );
        if (partialMatch && filteredDrawings.value.length === 1) {
            form.drawing_id = partialMatch.id;
            form.work_method_id = ''; // 図番が変わったら作業方法をリセット
        } else {
            form.drawing_id = '';
            form.work_method_id = '';
        }
    }
};

// 現在の日時を5分単位に丸めて取得
const getCurrentDate = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getCurrentHour = () => {
    const hour = new Date().getHours();
    // 00-05時は選択不可のため、06時に調整
    if (hour >= 0 && hour <= 5) {
        return '06';
    }
    return String(hour).padStart(2, '0');
};

const getCurrentMinute = () => {
    const now = new Date();
    // 分を5分単位に丸める（例: 23分 → 20分、27分 → 25分）
    const minutes = Math.floor(now.getMinutes() / 5) * 5;
    return String(minutes).padStart(2, '0');
};

// 5分単位の選択肢（0, 5, 10, 15, ..., 55）
const minuteOptions = Array.from({ length: 12 }, (_, i) => String(i * 5).padStart(2, '0'));

// URLパラメータから初期値を取得
const getUrlParams = () => {
    const params = new URLSearchParams(window.location.search);
    return {
        start_date: params.get('start_date') || '',
        start_hour: params.get('start_hour') || '',
        start_minute: params.get('start_minute') || '',
        end_date: params.get('end_date') || '',
        end_hour: params.get('end_hour') || '',
        end_minute: params.get('end_minute') || '',
    };
};

// 初期の終了日時を計算（開始時間+1時間、23時の場合は日付も1日進める）
const getInitialEndDateTime = (startDate, startHour) => {
    const date = startDate || getCurrentDate();
    let hour = startHour !== undefined ? parseInt(startHour) : parseInt(getCurrentHour());
    // 00-05時は選択不可のため、06時に調整
    if (hour >= 0 && hour <= 5) {
        hour = 6;
    }
    const endHour = (hour + 1) % 24;
    
    if (hour === 23) {
        // 23時の場合は日付も1日進める
        const dateObj = new Date(date);
        dateObj.setDate(dateObj.getDate() + 1);
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return {
            date: `${year}-${month}-${day}`,
            hour: String(endHour).padStart(2, '0'),
        };
    } else {
        return {
            date: date,
            hour: String(endHour).padStart(2, '0'),
        };
    }
};

// URLパラメータから初期値を取得
const urlParams = getUrlParams();

// 初期値を決定（URLパラメータがあればそれを使用、なければ現在の日時）
const initialStartDate = urlParams.start_date || getCurrentDate();
const initialStartHour = urlParams.start_hour || getCurrentHour();
const initialStartMinute = urlParams.start_minute || getCurrentMinute();
const initialEndDateTime = getInitialEndDateTime(initialStartDate, initialStartHour);

const form = useForm({
    drawing_id: '',
    work_method_id: '',
    staff_id: props.currentStaff.id,
    start_date: initialStartDate,
    start_hour: initialStartHour,
    start_minute: initialStartMinute,
    end_date: urlParams.end_date || initialEndDateTime.date,
    end_hour: urlParams.end_hour || initialEndDateTime.hour,
    end_minute: urlParams.end_minute || getCurrentMinute(),
    quantity_good: 0,
    quantity_ng: 0,
    memo: '',
    defects: [],
});

// 日付・時間・分を結合してdatetime形式（YYYY-MM-DDTHH:mm）に変換
const combineDateTime = (date, hour, minute) => {
    if (!date || !hour || minute === undefined) return '';
    return `${date}T${hour}:${minute}`;
};

// 開始日付・時間・分が変更されたら終了時刻を自動設定
watch([() => form.start_date, () => form.start_hour, () => form.start_minute], () => {
    if (form.start_date && form.start_hour && form.start_minute !== undefined) {
        let startHour = parseInt(form.start_hour) || 0;
        // 00-05時は選択不可のため、06時に調整
        if (startHour >= 0 && startHour <= 5) {
            startHour = 6;
            form.start_hour = '06';
        }
        let endHour = (startHour + 1) % 24;
        
        // 終了時間が00-05時になった場合は、次の日の06時に設定
        if (endHour >= 0 && endHour <= 5) {
            endHour = 6;
            const date = new Date(form.start_date);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            form.end_date = `${year}-${month}-${day}`;
        } else if (startHour === 23) {
            // 23時の場合は日付も1日進める
            const date = new Date(form.start_date);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            form.end_date = `${year}-${month}-${day}`;
        } else {
            // それ以外の場合は開始日付と同じ
            form.end_date = form.start_date;
        }
        
        // 開始時間+1時間を終了時間に設定
        form.end_hour = String(endHour).padStart(2, '0');
        // 開始分を終了分に設定
        form.end_minute = form.start_minute;
    }
});

const addDefect = () => {
    form.defects.push({
        defect_type_id: '',
        defect_quantity: 1,
    });
};

const removeDefect = (index) => {
    form.defects.splice(index, 1);
};

const totalDefectQuantity = computed(() => {
    return form.defects.reduce((sum, defect) => sum + (parseInt(defect.defect_quantity) || 0), 0);
});

const isDefectQuantityValid = computed(() => {
    return totalDefectQuantity.value === form.quantity_ng;
});

// 開始時刻が17時10分以降かどうかを判定
const isOvertimeMode = computed(() => {
    if (!form.start_hour || form.start_minute === undefined) {
        return false;
    }
    const hour = parseInt(form.start_hour) || 0;
    const minute = parseInt(form.start_minute) || 0;
    
    // 17時10分以降（17時10分を含む）
    return hour > 17 || (hour === 17 && minute >= 10);
});

// 選択された図番に関連する作業方法のみをフィルター
const availableWorkMethods = computed(() => {
    if (!form.drawing_id) {
        return [];
    }
    
    // drawing_idを数値に変換して比較
    const drawingId = typeof form.drawing_id === 'string' ? parseInt(form.drawing_id) : form.drawing_id;
    
    const selectedDrawing = props.drawings.find(d => {
        const dId = typeof d.id === 'string' ? parseInt(d.id) : d.id;
        return dId === drawingId;
    });
    
    if (!selectedDrawing) {
        return [];
    }
    
    // work_ratesまたはworkRatesのどちらかを使用（Laravelのリレーション名の変換に対応）
    const workRates = selectedDrawing.workRates || selectedDrawing.work_rates || [];
    
    if (!workRates || workRates.length === 0) {
        return [];
    }
    
    // work_ratesからwork_method_idを取得（重複を除去）
    const workMethodIds = new Set();
    workRates.forEach(rate => {
        if (rate.work_method_id) {
            const methodId = typeof rate.work_method_id === 'string' ? parseInt(rate.work_method_id) : rate.work_method_id;
            workMethodIds.add(methodId);
        }
    });
    
    // 対応する作業方法を返す
    return props.workMethods.filter(method => {
        const methodId = typeof method.id === 'string' ? parseInt(method.id) : method.id;
        return workMethodIds.has(methodId);
    });
});

const submit = () => {
    // 日付・時間・分を結合してdatetime形式に変換
    const startTime = combineDateTime(form.start_date, form.start_hour, form.start_minute);
    const endTime = combineDateTime(form.end_date, form.end_hour, form.end_minute);
    
    // 一時的にstart_timeとend_timeを設定して送信
    form.transform((data) => ({
        ...data,
        start_time: startTime,
        end_time: endTime,
    })).post(route('mobile.work-records.store'), {
        onSuccess: () => {
            // 登録成功時に同じ画面に戻る（成功メッセージはフラッシュで表示される）
            // 時刻情報を保持してリダイレクト
            const params = new URLSearchParams({
                start_date: form.start_date,
                start_hour: form.start_hour,
                start_minute: form.start_minute,
                end_date: form.end_date,
                end_hour: form.end_hour,
                end_minute: form.end_minute,
            });
            setTimeout(() => {
                window.location.href = route('mobile.work-records.create') + '?' + params.toString();
            }, 100);
        },
    });
};
</script>

<template>
    <Head title="作業実績登録" />

    <MobileLayout title="作業実績登録">
        <!-- 成功メッセージ -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border-2 border-green-400 text-green-700 rounded-lg text-lg font-bold">
            {{ $page.props.flash.success }}
        </div>

        <!-- スタッフ情報（目立つように大きく表示） -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl shadow-lg p-6 mb-6 border-2 border-orange-700">
            <div class="text-white">
                <div class="text-sm font-medium mb-2 opacity-90">ログイン中のスタッフ</div>
                <div class="text-3xl font-bold">{{ currentStaff.name }}</div>
                <!-- <div v-if="currentStaff.staff_type" class="text-lg mt-2 opacity-90">
                    {{ currentStaff.staff_type.name }}
                </div> -->
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 図番選択（2カラム：得意先と図番入力） -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <label class="block text-lg font-bold text-gray-800 mb-4">図番 *</label>
                <div class="grid grid-cols-1 gap-4">
                    <!-- 得意先フィルター -->
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">得意先</label>
                        <select
                            v-model="clientFilter"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            @change="drawingNumberFilter = ''; form.drawing_id = ''; form.work_method_id = ''"
                        >
                            <option value="">すべて</option>
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.name }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- 図番入力（datalist使用） -->
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">図番</label>
                        <input
                            v-model="drawingNumberFilter"
                            type="text"
                            list="drawing-number-list"
                            placeholder="図番を入力してください"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-4"
                            :class="{ 'border-red-500': form.errors.drawing_id }"
                            @input="onDrawingNumberInput"
                        />
                        <datalist id="drawing-number-list">
                            <option
                                v-for="drawing in filteredDrawings"
                                :key="drawing.id"
                                :value="drawing.drawing_number"
                            >
                                {{ drawing.drawing_number }} - {{ drawing.product_name }} ({{ drawing.client.name }})
                            </option>
                        </datalist>
                    </div>
                </div>
                <p v-if="form.errors.drawing_id" class="mt-2 text-base text-red-600 font-semibold">
                    {{ form.errors.drawing_id }}
                </p>
            </div>

            <!-- 作業方法 -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <label class="block text-lg font-bold text-gray-800 mb-4">作業方法 *</label>
                <select
                    v-model="form.work_method_id"
                    class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                    :class="{ 'border-red-500': form.errors.work_method_id }"
                    :disabled="!form.drawing_id"
                >
                    <option value="">{{ form.drawing_id ? '選択してください' : '図番を選択してください' }}</option>
                    <option
                        v-for="method in availableWorkMethods"
                        :key="method.id"
                        :value="method.id"
                    >
                        {{ method.name }}
                    </option>
                </select>
                <p v-if="form.errors.work_method_id" class="mt-2 text-base text-red-600 font-semibold">
                    {{ form.errors.work_method_id }}
                </p>
                <p v-if="!form.drawing_id" class="mt-2 text-base text-gray-500">
                    図番を選択すると作業方法が表示されます
                </p>
            </div>

            <!-- 作業時刻 -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <!-- 残業時間内登録モード表示 -->
                <div v-if="isOvertimeMode" class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl shadow-lg p-4 mb-6 border-2 border-orange-700">
                    <div class="text-white text-lg font-bold text-center">
                        残業時間内登録モード
                    </div>
                </div>
                
                <!-- 作業開始時刻 -->
                <label class="block text-lg font-bold text-gray-800 mb-4">作業開始時刻 *</label>
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <!-- 日付 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">日付</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3"
                            :class="{ 'border-red-500': form.errors.start_time }"
                        />
                    </div>
                    <!-- 時間 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">時間</label>
                        <select
                            v-model="form.start_hour"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            :class="{ 'border-red-500': form.errors.start_time }"
                        >
                            <option
                                v-for="h in Array.from({ length: 18 }, (_, i) => String(i + 6).padStart(2, '0'))"
                                :key="h"
                                :value="h"
                            >
                                {{ h }}
                            </option>
                        </select>
                    </div>
                    <!-- 分 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">分</label>
                        <select
                            v-model="form.start_minute"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            :class="{ 'border-red-500': form.errors.start_time }"
                        >
                            <option
                                v-for="m in minuteOptions"
                                :key="m"
                                :value="m"
                            >
                                {{ m }}
                            </option>
                        </select>
                    </div>
                </div>
                <p v-if="form.errors.start_time" class="mt-2 text-base text-red-600 font-semibold mb-6">
                    {{ form.errors.start_time }}
                </p>

                <!-- 区切り線 -->
                <div class="border-t-2 border-gray-300 my-6"></div>

                <!-- 作業終了時刻 -->
                <label class="block text-lg font-bold text-gray-800 mb-4">作業終了時刻 *</label>
                <div class="grid grid-cols-3 gap-3">
                    <!-- 日付 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">日付</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3"
                            :class="{ 'border-red-500': form.errors.end_time }"
                        />
                    </div>
                    <!-- 時間 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">時間</label>
                        <select
                            v-model="form.end_hour"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            :class="{ 'border-red-500': form.errors.end_time }"
                        >
                            <option
                                v-for="h in Array.from({ length: 18 }, (_, i) => String(i + 6).padStart(2, '0'))"
                                :key="h"
                                :value="h"
                            >
                                {{ h }}
                            </option>
                        </select>
                    </div>
                    <!-- 分 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">分</label>
                        <select
                            v-model="form.end_minute"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            :class="{ 'border-red-500': form.errors.end_time }"
                        >
                            <option
                                v-for="m in minuteOptions"
                                :key="m"
                                :value="m"
                            >
                                {{ m }}
                            </option>
                        </select>
                    </div>
                </div>
                <p v-if="form.errors.end_time" class="mt-2 text-base text-red-600 font-semibold">
                    {{ form.errors.end_time }}
                </p>
            </div>

            <!-- 良品数・不良数（2カラム） -->
            <div class="grid grid-cols-2 gap-4">
                <!-- 良品数 -->
                <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                    <label class="block text-lg font-bold text-gray-800 mb-4">良品数 *</label>
                    <input
                        v-model.number="form.quantity_good"
                        type="number"
                        min="0"
                        class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-4"
                        :class="{ 'border-red-500': form.errors.quantity_good }"
                    />
                    <p v-if="form.errors.quantity_good" class="mt-2 text-base text-red-600 font-semibold">
                        {{ form.errors.quantity_good }}
                    </p>
                </div>

                <!-- 不良数 -->
                <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                    <label class="block text-lg font-bold text-gray-800 mb-4">不良数 *</label>
                    <input
                        v-model.number="form.quantity_ng"
                        type="number"
                        min="0"
                        class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-4"
                        :class="{ 'border-red-500': form.errors.quantity_ng }"
                    />
                    <p v-if="form.errors.quantity_ng" class="mt-2 text-base text-red-600 font-semibold">
                        {{ form.errors.quantity_ng }}
                    </p>
                </div>
            </div>

            <!-- 備考 -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <label class="block text-lg font-bold text-gray-800 mb-4">備考</label>
                <textarea
                    v-model="form.memo"
                    rows="4"
                    placeholder="備考があれば入力してください"
                    class="w-full text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-4 py-3"
                    :class="{ 'border-red-500': form.errors.memo }"
                />
                <p v-if="form.errors.memo" class="mt-2 text-base text-red-600 font-semibold">
                    {{ form.errors.memo }}
                </p>
            </div>

            <!-- 不良内訳 -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <label class="block text-lg font-bold text-gray-800">不良内訳</label>
                    <button
                        type="button"
                        @click="addDefect"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg text-base shadow-md"
                    >
                        ＋ 追加
                    </button>
                </div>
                <div v-if="form.defects.length > 0" class="space-y-4">
                    <div
                        v-for="(defect, index) in form.defects"
                        :key="index"
                        class="bg-gray-50 rounded-lg p-4 border border-gray-300"
                    >
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-base font-semibold text-gray-700 mb-2">不良種類</label>
                                <select
                                    v-model="defect.defect_type_id"
                                    class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                >
                                    <option value="">選択してください</option>
                                    <option
                                        v-for="type in defectTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="block text-base font-semibold text-gray-700 mb-2">数量</label>
                                <input
                                    v-model.number="defect.defect_quantity"
                                    type="number"
                                    min="1"
                                    class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3"
                                />
                            </div>
                            <button
                                type="button"
                                @click="removeDefect(index)"
                                class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-lg h-14 text-base shadow-md"
                            >
                                削除
                            </button>
                        </div>
                    </div>
                    <div class="text-base font-semibold p-3 rounded-lg" :class="isDefectQuantityValid ? 'bg-green-50 text-green-700 border border-green-300' : 'bg-red-50 text-red-700 border border-red-300'">
                        合計: {{ totalDefectQuantity }} / 不良数: {{ form.quantity_ng }}
                        <span v-if="!isDefectQuantityValid" class="block mt-1">
                            ⚠ 不良内訳の合計が不良数と一致していません
                        </span>
                    </div>
                </div>
            </div>

            <!-- エラーメッセージ -->
            <div v-if="form.errors.error" class="p-5 bg-red-100 border-2 border-red-400 text-red-700 rounded-lg text-lg font-semibold">
                {{ form.errors.error }}
            </div>

            <!-- 送信ボタン -->
            <div class="pt-6 pb-8">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-16 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-xl rounded-xl shadow-lg disabled:opacity-50 disabled:cursor-not-allowed border-2 border-orange-700 transition-all"
                >
                    {{ form.processing ? '登録中...' : '✓ 登録する' }}
                </button>
            </div>
        </form>
    </MobileLayout>
</template>

