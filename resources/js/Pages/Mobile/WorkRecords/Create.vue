<script setup>
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    currentStaff: Object,
    drawings: Array,
    workMethods: Array,
    defectTypes: Array,
    todayRecords: {
        type: Array,
        default: () => [],
    },
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
const productNameFilter = ref('');
const drawingNumberFilter = ref('');

// フィルターされた図番リスト
const filteredDrawings = computed(() => {
    let filtered = props.drawings;

    // 得意先でフィルター
    if (clientFilter.value) {
        filtered = filtered.filter(d => d.client && d.client.id === parseInt(clientFilter.value));
    }

    // 品名でフィルター（部分一致・大文字小文字無視）
    if (productNameFilter.value) {
        const searchText = productNameFilter.value.toLowerCase();
        filtered = filtered.filter(d =>
            (d.product_name || '').toLowerCase().includes(searchText)
        );
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

// ==== 履歴モーダル ====
const showHistoryModal = ref(false);

const openHistory = () => {
    showHistoryModal.value = true;
    // 背面スクロールを止める
    document.body.style.overflow = 'hidden';
};
const closeHistory = () => {
    showHistoryModal.value = false;
    editingRecord.value = null;
    document.body.style.overflow = '';
};

// ==== 編集モード（履歴モーダル内で切替） ====
const editingRecord = ref(null);

const editForm = useForm({
    drawing_id: '',
    work_method_id: '',
    staff_id: props.currentStaff.id,
    start_date: '',
    start_hour: '',
    start_minute: '',
    end_date: '',
    end_hour: '',
    end_minute: '',
    quantity_good: 0,
    quantity_ng: 0,
    memo: '',
    defects: [],
});

const pad2 = (n) => String(n).padStart(2, '0');

const splitIso = (iso) => {
    const d = new Date(iso);
    return {
        date: `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`,
        hour: pad2(d.getHours()),
        // 既存の5分単位に丸める（本来保存時には丸められているが念のため）
        minute: pad2(Math.floor(d.getMinutes() / 5) * 5),
    };
};

const openEdit = (rec) => {
    if (rec.is_invoiced) return;
    const s = splitIso(rec.start_time);
    const e = splitIso(rec.end_time);
    editForm.clearErrors();
    editForm.drawing_id = rec.drawing_id;
    editForm.work_method_id = rec.work_method_id;
    editForm.staff_id = rec.staff_id;
    editForm.start_date = s.date;
    editForm.start_hour = s.hour;
    editForm.start_minute = s.minute;
    editForm.end_date = e.date;
    editForm.end_hour = e.hour;
    editForm.end_minute = e.minute;
    editForm.quantity_good = rec.quantity_good ?? 0;
    editForm.quantity_ng = rec.quantity_ng ?? 0;
    editForm.memo = rec.memo ?? '';
    editForm.defects = (rec.defects || []).map((d) => ({
        defect_type_id: d.defect_type_id,
        defect_quantity: d.defect_quantity,
    }));
    editingRecord.value = rec;
};

const closeEdit = () => {
    editingRecord.value = null;
    editForm.clearErrors();
};

const editTotalDefectQuantity = computed(() =>
    editForm.defects.reduce((sum, d) => sum + (parseInt(d.defect_quantity) || 0), 0)
);

const editIsDefectQuantityValid = computed(() =>
    editTotalDefectQuantity.value === (parseInt(editForm.quantity_ng) || 0)
);

const editAddDefect = () => {
    editForm.defects.push({ defect_type_id: '', defect_quantity: 1 });
};

const editRemoveDefect = (i) => {
    editForm.defects.splice(i, 1);
};

const submitEdit = () => {
    const startTime = combineDateTime(editForm.start_date, editForm.start_hour, editForm.start_minute);
    const endTime = combineDateTime(editForm.end_date, editForm.end_hour, editForm.end_minute);
    editForm
        .transform((data) => ({
            ...data,
            start_time: startTime,
            end_time: endTime,
        }))
        .patch(route('mobile.work-records.update', editingRecord.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                // 更新後はpropsが再取得されるので、一覧表示に戻すだけでOK
                editingRecord.value = null;
            },
        });
};

// 時刻フォーマット HH:mm
const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${hh}:${mm}`;
};

// 作業時間を HH:mm 表示
const formatWorkMinutes = (minutes) => {
    const m = parseInt(minutes) || 0;
    const h = Math.floor(m / 60);
    const mm = m % 60;
    if (h === 0) return `${mm}分`;
    if (mm === 0) return `${h}時間`;
    return `${h}時間${mm}分`;
};

// 日付ラベル（今日/日付）
const todayLabel = computed(() => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const wd = ['日', '月', '火', '水', '木', '金', '土'][d.getDay()];
    return `${y}/${m}/${day}（${wd}）`;
});

// 集計（本日）
const todaySummary = computed(() => {
    const list = props.todayRecords || [];
    const good = list.reduce((s, r) => s + (parseInt(r.quantity_good) || 0), 0);
    const ng = list.reduce((s, r) => s + (parseInt(r.quantity_ng) || 0), 0);
    const mins = list.reduce((s, r) => s + (parseInt(r.work_minutes) || 0), 0);
    return {
        count: list.length,
        good,
        ng,
        minutes: mins,
    };
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
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl shadow-lg p-6 mb-4 border-2 border-orange-700">
            <div class="text-white">
                <div class="text-sm font-medium mb-2 opacity-90">ログイン中のスタッフ</div>
                <div class="text-3xl font-bold">{{ currentStaff.name }}</div>
            </div>
        </div>

        <!-- 本日の登録履歴を確認するボタン -->
        <button
            type="button"
            @click="openHistory"
            class="w-full mb-6 px-4 py-4 bg-white hover:bg-slate-50 active:bg-slate-100 rounded-xl shadow-md border-2 border-slate-300 flex items-center justify-between transition-all"
        >
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M12 3a9 9 0 1 0 9 9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v6h6" />
                    </svg>
                </div>
                <div class="text-left">
                    <div class="text-lg font-bold text-gray-800">本日の登録履歴</div>
                    <div class="text-sm text-gray-500">{{ todayLabel }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="min-w-[2.5rem] h-10 px-3 rounded-full flex items-center justify-center text-lg font-bold shadow-sm"
                    :class="todaySummary.count > 0 ? 'bg-sky-500 text-white' : 'bg-gray-200 text-gray-500'"
                >{{ todaySummary.count }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                </svg>
            </div>
        </button>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 図番選択（得意先・品名・図番の3軸絞り込み） -->
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-200">
                <label class="block text-lg font-bold text-gray-800 mb-4">図番 *</label>
                <div class="grid grid-cols-1 gap-4">
                    <!-- 得意先フィルター -->
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">得意先</label>
                        <select
                            v-model="clientFilter"
                            class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                            @change="productNameFilter = ''; drawingNumberFilter = ''; form.drawing_id = ''; form.work_method_id = ''"
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

                    <!-- 品名フィルター（部分一致） -->
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">品名</label>
                        <div class="relative">
                            <input
                                v-model="productNameFilter"
                                type="text"
                                placeholder="品名の一部を入力（例：ケーシング）"
                                class="w-full h-14 text-lg rounded-lg border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-4 pr-12"
                            />
                            <button
                                v-if="productNameFilter"
                                type="button"
                                @click="productNameFilter = ''"
                                class="absolute top-1/2 right-3 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 text-xl"
                                aria-label="品名クリア"
                            >×</button>
                        </div>
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
                        <p class="mt-2 text-sm text-gray-500">
                            絞り込み結果: <span class="font-semibold text-gray-700">{{ filteredDrawings.length }}</span> 件
                        </p>
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

        <!-- 本日の登録履歴モーダル -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showHistoryModal"
                    class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center"
                    @click.self="closeHistory"
                >
                    <Transition
                        enter-active-class="transition duration-250 ease-out"
                        enter-from-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                        enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-to-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                        appear
                    >
                        <div
                            v-if="showHistoryModal"
                            class="w-full sm:max-w-2xl bg-slate-50 rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden"
                        >
                            <!-- ヘッダー：一覧モード -->
                            <div
                                v-if="!editingRecord"
                                class="sticky top-0 bg-gradient-to-r from-sky-600 to-blue-700 text-white px-5 py-4 flex items-center justify-between shadow-md"
                            >
                                <div>
                                    <div class="text-xl font-bold">本日の登録履歴</div>
                                    <div class="text-xs opacity-90 mt-0.5">{{ todayLabel }} ／ {{ currentStaff.name }} さん</div>
                                </div>
                                <button
                                    type="button"
                                    @click="closeHistory"
                                    class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 active:bg-white/40 flex items-center justify-center text-2xl font-bold transition"
                                    aria-label="閉じる"
                                >×</button>
                            </div>

                            <!-- ヘッダー：編集モード -->
                            <div
                                v-else
                                class="sticky top-0 bg-gradient-to-r from-orange-600 to-amber-600 text-white px-5 py-4 flex items-center justify-between shadow-md"
                            >
                                <button
                                    type="button"
                                    @click="closeEdit"
                                    class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 active:bg-white/40 flex items-center justify-center transition"
                                    aria-label="一覧に戻る"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="text-center flex-1">
                                    <div class="text-lg font-bold">作業実績を編集</div>
                                    <div class="text-[11px] opacity-90 mt-0.5 font-mono">
                                        {{ formatTime(editingRecord.start_time) }} →
                                        {{ formatTime(editingRecord.end_time) }}
                                    </div>
                                </div>
                                <div class="w-10"></div>
                            </div>

                            <!-- 本体：一覧モード -->
                            <template v-if="!editingRecord">
                                <!-- サマリー -->
                                <div class="px-5 pt-4 pb-2 bg-white border-b border-gray-200">
                                    <div class="grid grid-cols-4 gap-2">
                                        <div class="text-center p-2 rounded-lg bg-slate-50 border border-slate-200">
                                            <div class="text-[11px] text-slate-500 font-semibold">件数</div>
                                            <div class="text-xl font-bold text-slate-800">{{ todaySummary.count }}</div>
                                        </div>
                                        <div class="text-center p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                                            <div class="text-[11px] text-emerald-700 font-semibold">良品計</div>
                                            <div class="text-xl font-bold text-emerald-700">{{ todaySummary.good }}</div>
                                        </div>
                                        <div class="text-center p-2 rounded-lg bg-rose-50 border border-rose-200">
                                            <div class="text-[11px] text-rose-700 font-semibold">不良計</div>
                                            <div class="text-xl font-bold text-rose-700">{{ todaySummary.ng }}</div>
                                        </div>
                                        <div class="text-center p-2 rounded-lg bg-amber-50 border border-amber-200">
                                            <div class="text-[11px] text-amber-700 font-semibold">工数計</div>
                                            <div class="text-lg font-bold text-amber-700 leading-tight">{{ formatWorkMinutes(todaySummary.minutes) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- リスト -->
                                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">
                                    <!-- 空状態 -->
                                    <div v-if="todaySummary.count === 0" class="py-16 text-center">
                                        <div class="text-6xl mb-3">📋</div>
                                        <div class="text-lg font-bold text-gray-600">本日の登録はまだありません</div>
                                        <div class="text-sm text-gray-500 mt-1">作業実績を登録するとここに表示されます</div>
                                    </div>

                                    <!-- 履歴カード -->
                                    <div
                                        v-for="(rec, idx) in todayRecords"
                                        :key="rec.id"
                                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                                    >
                                        <!-- 時刻ヘッダー -->
                                        <div class="flex items-center justify-between bg-gradient-to-r from-slate-800 to-slate-700 text-white px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">
                                                    {{ todayRecords.length - idx }}
                                                </span>
                                                <span class="font-mono font-bold tracking-wide">
                                                    {{ formatTime(rec.start_time) }}
                                                    <span class="opacity-70 mx-1">→</span>
                                                    {{ formatTime(rec.end_time) }}
                                                </span>
                                            </div>
                                            <span class="text-xs bg-amber-400 text-amber-900 px-2 py-0.5 rounded-full font-bold">
                                                {{ formatWorkMinutes(rec.work_minutes) }}
                                            </span>
                                        </div>

                                        <!-- 本体 -->
                                        <div class="p-4 space-y-3">
                                            <!-- 得意先・品名・図番 -->
                                            <div>
                                                <div class="text-xs text-gray-500 mb-0.5">
                                                    {{ rec.drawing?.client?.name || '—' }}
                                                </div>
                                                <div class="text-base font-bold text-gray-800 leading-snug">
                                                    {{ rec.drawing?.product_name || '—' }}
                                                </div>
                                                <div class="inline-flex items-center gap-1 mt-1 text-xs font-mono text-sky-700 bg-sky-50 border border-sky-200 rounded px-2 py-0.5">
                                                    図番: {{ rec.drawing?.drawing_number || '—' }}
                                                </div>
                                                <div class="inline-flex items-center gap-1 mt-1 ml-1 text-xs text-purple-700 bg-purple-50 border border-purple-200 rounded px-2 py-0.5">
                                                    {{ rec.work_method?.name || '—' }}
                                                </div>
                                            </div>

                                            <!-- 数量 -->
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="text-center p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                                                    <div class="text-[11px] text-emerald-700 font-semibold">良品</div>
                                                    <div class="text-xl font-bold text-emerald-700">{{ rec.quantity_good }}</div>
                                                </div>
                                                <div class="text-center p-2 rounded-lg" :class="rec.quantity_ng > 0 ? 'bg-rose-50 border border-rose-200' : 'bg-gray-50 border border-gray-200'">
                                                    <div class="text-[11px] font-semibold" :class="rec.quantity_ng > 0 ? 'text-rose-700' : 'text-gray-500'">不良</div>
                                                    <div class="text-xl font-bold" :class="rec.quantity_ng > 0 ? 'text-rose-700' : 'text-gray-400'">{{ rec.quantity_ng }}</div>
                                                </div>
                                            </div>

                                            <!-- 不良内訳 -->
                                            <div v-if="rec.defects && rec.defects.length > 0" class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2">
                                                <div class="text-[11px] font-bold text-rose-800 mb-1">不良内訳</div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <span
                                                        v-for="d in rec.defects"
                                                        :key="d.id"
                                                        class="text-xs bg-white border border-rose-300 text-rose-700 rounded-full px-2 py-0.5"
                                                    >
                                                        {{ d.defect_type?.name || '—' }}
                                                        <span class="font-bold ml-1">× {{ d.defect_quantity }}</span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- 備考 -->
                                            <div v-if="rec.memo" class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                                <div class="text-[11px] font-bold text-amber-800 mb-0.5">備考</div>
                                                <div class="text-sm text-amber-900 whitespace-pre-wrap break-words">{{ rec.memo }}</div>
                                            </div>

                                            <!-- アクション -->
                                            <div class="pt-1">
                                                <button
                                                    v-if="!rec.is_invoiced"
                                                    type="button"
                                                    @click="openEdit(rec)"
                                                    class="w-full h-11 flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold rounded-lg shadow-sm transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    編集する
                                                </button>
                                                <div
                                                    v-else
                                                    class="w-full h-11 flex items-center justify-center gap-2 bg-gray-100 text-gray-500 rounded-lg border border-gray-200 text-sm font-semibold"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z"/>
                                                    </svg>
                                                    請求書反映済み（編集不可）
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- フッター -->
                                <div class="bg-white border-t border-gray-200 px-4 py-3">
                                    <button
                                        type="button"
                                        @click="closeHistory"
                                        class="w-full h-12 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white font-bold rounded-xl shadow-md transition"
                                    >
                                        閉じる
                                    </button>
                                </div>
                            </template>

                            <!-- 本体：編集モード -->
                            <template v-else>
                                <!-- 対象レコードの要約（読み取り専用） -->
                                <div class="px-4 pt-4 pb-3 bg-white border-b border-gray-200">
                                    <div class="text-[11px] text-gray-500 mb-1">編集対象</div>
                                    <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                                        <div class="text-xs text-gray-500">{{ editingRecord.drawing?.client?.name || '—' }}</div>
                                        <div class="text-sm font-bold text-gray-800 leading-tight">{{ editingRecord.drawing?.product_name || '—' }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span class="text-[11px] font-mono text-sky-700 bg-sky-50 border border-sky-200 rounded px-2 py-0.5">
                                                図番: {{ editingRecord.drawing?.drawing_number || '—' }}
                                            </span>
                                            <span class="text-[11px] text-purple-700 bg-purple-50 border border-purple-200 rounded px-2 py-0.5">
                                                {{ editingRecord.work_method?.name || '—' }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-gray-400 mt-1">※ 図番・作業方法の変更はできません</div>
                                    </div>
                                </div>

                                <!-- 編集フォーム -->
                                <form @submit.prevent="submitEdit" class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                                    <!-- 作業開始時刻 -->
                                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                                        <label class="block text-base font-bold text-gray-800 mb-3">作業開始時刻 *</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">日付</label>
                                                <input
                                                    v-model="editForm.start_date"
                                                    type="date"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-2"
                                                    :class="{ 'border-red-500': editForm.errors.start_time }"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">時間</label>
                                                <select
                                                    v-model="editForm.start_hour"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                                    :class="{ 'border-red-500': editForm.errors.start_time }"
                                                >
                                                    <option v-for="h in Array.from({ length: 18 }, (_, i) => String(i + 6).padStart(2, '0'))" :key="h" :value="h">{{ h }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">分</label>
                                                <select
                                                    v-model="editForm.start_minute"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                                    :class="{ 'border-red-500': editForm.errors.start_time }"
                                                >
                                                    <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <p v-if="editForm.errors.start_time" class="mt-2 text-sm text-red-600 font-semibold">{{ editForm.errors.start_time }}</p>
                                    </div>

                                    <!-- 作業終了時刻 -->
                                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                                        <label class="block text-base font-bold text-gray-800 mb-3">作業終了時刻 *</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">日付</label>
                                                <input
                                                    v-model="editForm.end_date"
                                                    type="date"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-2"
                                                    :class="{ 'border-red-500': editForm.errors.end_time }"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">時間</label>
                                                <select
                                                    v-model="editForm.end_hour"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                                    :class="{ 'border-red-500': editForm.errors.end_time }"
                                                >
                                                    <option v-for="h in Array.from({ length: 18 }, (_, i) => String(i + 6).padStart(2, '0'))" :key="h" :value="h">{{ h }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">分</label>
                                                <select
                                                    v-model="editForm.end_minute"
                                                    class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                                    :class="{ 'border-red-500': editForm.errors.end_time }"
                                                >
                                                    <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <p v-if="editForm.errors.end_time" class="mt-2 text-sm text-red-600 font-semibold">{{ editForm.errors.end_time }}</p>
                                    </div>

                                    <!-- 数量 -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                                            <label class="block text-base font-bold text-gray-800 mb-2">良品数 *</label>
                                            <input
                                                v-model.number="editForm.quantity_good"
                                                type="number"
                                                min="0"
                                                class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3"
                                                :class="{ 'border-red-500': editForm.errors.quantity_good }"
                                            />
                                            <p v-if="editForm.errors.quantity_good" class="mt-1 text-xs text-red-600 font-semibold">{{ editForm.errors.quantity_good }}</p>
                                        </div>
                                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                                            <label class="block text-base font-bold text-gray-800 mb-2">不良数 *</label>
                                            <input
                                                v-model.number="editForm.quantity_ng"
                                                type="number"
                                                min="0"
                                                class="w-full h-12 text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3"
                                                :class="{ 'border-red-500': editForm.errors.quantity_ng }"
                                            />
                                            <p v-if="editForm.errors.quantity_ng" class="mt-1 text-xs text-red-600 font-semibold">{{ editForm.errors.quantity_ng }}</p>
                                        </div>
                                    </div>

                                    <!-- 備考 -->
                                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                                        <label class="block text-base font-bold text-gray-800 mb-2">備考</label>
                                        <textarea
                                            v-model="editForm.memo"
                                            rows="3"
                                            placeholder="備考があれば入力してください"
                                            class="w-full text-base rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-3 py-2"
                                            :class="{ 'border-red-500': editForm.errors.memo }"
                                        />
                                    </div>

                                    <!-- 不良内訳 -->
                                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <label class="block text-base font-bold text-gray-800">不良内訳</label>
                                            <button
                                                type="button"
                                                @click="editAddDefect"
                                                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-sm"
                                            >＋ 追加</button>
                                        </div>
                                        <div v-if="editForm.defects.length > 0" class="space-y-3">
                                            <div
                                                v-for="(defect, i) in editForm.defects"
                                                :key="i"
                                                class="bg-gray-50 rounded-lg p-3 border border-gray-300"
                                            >
                                                <div class="flex gap-2 items-end">
                                                    <div class="flex-1">
                                                        <label class="block text-xs font-semibold text-gray-700 mb-1">不良種類</label>
                                                        <select
                                                            v-model="defect.defect_type_id"
                                                            class="w-full h-11 text-sm rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                                        >
                                                            <option value="">選択してください</option>
                                                            <option v-for="type in defectTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="w-20">
                                                        <label class="block text-xs font-semibold text-gray-700 mb-1">数量</label>
                                                        <input
                                                            v-model.number="defect.defect_quantity"
                                                            type="number"
                                                            min="1"
                                                            class="w-full h-11 text-sm rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 px-2"
                                                        />
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="editRemoveDefect(i)"
                                                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-3 rounded-lg h-11 text-sm shadow-sm"
                                                    >削除</button>
                                                </div>
                                            </div>
                                            <div
                                                class="text-sm font-semibold p-2 rounded-lg"
                                                :class="editIsDefectQuantityValid ? 'bg-green-50 text-green-700 border border-green-300' : 'bg-red-50 text-red-700 border border-red-300'"
                                            >
                                                合計: {{ editTotalDefectQuantity }} / 不良数: {{ editForm.quantity_ng }}
                                                <span v-if="!editIsDefectQuantityValid" class="block mt-1">⚠ 不良内訳の合計が不良数と一致していません</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- エラー -->
                                    <div v-if="editForm.errors.error" class="p-4 bg-red-100 border-2 border-red-400 text-red-700 rounded-lg font-semibold">
                                        {{ editForm.errors.error }}
                                    </div>
                                </form>

                                <!-- フッター：編集モード -->
                                <div class="bg-white border-t border-gray-200 px-4 py-3 grid grid-cols-5 gap-2">
                                    <button
                                        type="button"
                                        @click="closeEdit"
                                        class="col-span-2 h-12 bg-gray-200 hover:bg-gray-300 active:bg-gray-400 text-gray-800 font-bold rounded-xl shadow-sm transition"
                                    >キャンセル</button>
                                    <button
                                        type="button"
                                        @click="submitEdit"
                                        :disabled="editForm.processing"
                                        class="col-span-3 h-12 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >{{ editForm.processing ? '更新中...' : '✓ 保存する' }}</button>
                                </div>
                            </template>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </MobileLayout>
</template>

