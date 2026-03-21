import Alpine from '@alpinejs/csp';

// =============================================================
// darkMode - ダークモード切り替え
// layouts/master.blade.php, layouts/guest.blade.php で使用
// =============================================================
Alpine.data('darkMode', () => ({
    isDark: localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches),
    init() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('darkMode')) {
                this.isDark = e.matches;
                this.applyTheme();
            }
        });
    },
    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('darkMode', this.isDark);
        this.applyTheme();
    },
    applyTheme() {
        document.documentElement.classList.toggle('dark', this.isDark);
    }
}));

// =============================================================
// equipmentIndexPage - 機材マスタ一覧ページ
// master/equipments/index.blade.php で使用
// =============================================================
Alpine.data('equipmentIndexPage', () => ({
    showLineWorksModal: false,
}));

// =============================================================
// repairRecordPage - 修理記録詳細ページ
// repair-records/show.blade.php で使用
// =============================================================
Alpine.data('repairRecordPage', () => ({
    showImageModal: false,
    showStartModal: false,
    showCompleteModal: false,
    showCancelModal: false,
    showDeleteModal: false,
    imageModalSrc: '',
    deleteConfirmation: '',
    repairCostDisplay: '',
    repairCostValue: '',

    get canDelete() {
        return this.deleteConfirmation.toLowerCase() === 'delete';
    },

    openImageModal(src) {
        this.imageModalSrc = src;
        this.showImageModal = true;
    },

    formatRepairCost(event) {
        let value = event.target.value.replace(/[^\d]/g, '');
        if (value === '') {
            this.repairCostDisplay = '';
            this.repairCostValue = '';
            return;
        }
        let numValue = parseInt(value);
        this.repairCostDisplay = numValue.toLocaleString();
        this.repairCostValue = numValue;
    }
}));

// =============================================================
// logoUploader - ロゴ画像アップロード
// admin/company-info/index.blade.php で使用
// =============================================================
Alpine.data('logoUploader', () => ({
    isDragging: false,
    fileName: '',
    previewUrl: '',

    handleDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    },

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.processFile(file);
        }
    },

    processFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('画像ファイルを選択してください。');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('ファイルサイズは2MB以下にしてください。');
            return;
        }
        this.fileName = file.name;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewUrl = e.target.result;
        };
        reader.readAsDataURL(file);
        if (this.$refs.fileInput.files.length === 0) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            this.$refs.fileInput.files = dataTransfer.files;
        }
    }
}));

// =============================================================
// phaseShowPage - フェーズ詳細ページ
// phases/show.blade.php で使用
// Blade データは window.__phaseShowConfig から取得
// =============================================================
Alpine.data('phaseShowPage', () => ({
    showLineWorksModal: false,
    showDeleteModal: false,
    deleteConfirmation: '',
    pdfLoading: false,
    pdfMessage: '',
    flashSuccess: '',

    init() {
        const msg = sessionStorage.getItem('flashSuccess');
        if (msg) {
            this.flashSuccess = msg;
            sessionStorage.removeItem('flashSuccess');
        }
    },

    get canDelete() {
        return this.deleteConfirmation.toLowerCase() === 'delete';
    },

    async downloadPdf() {
        const config = window.__phaseShowConfig || {};
        this.pdfLoading = true;
        this.pdfMessage = 'フェーズPDFを生成中...';
        try {
            const response = await fetch(config.pdfUrl);
            if (!response.ok) {
                throw new Error('PDF生成に失敗しました');
            }
            const blob = await response.blob();
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = config.pdfFilename || 'phase.pdf';
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename\*?=(?:UTF-8'')?([^;\s]+)/i);
                if (filenameMatch) {
                    filename = decodeURIComponent(filenameMatch[1].replace(/['"]/g, ''));
                }
            }
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        } catch (error) {
            console.error('PDF download error:', error);
            alert('PDFのダウンロードに失敗しました。');
        } finally {
            this.pdfLoading = false;
        }
    },

    async sendToLineWorks() {
        const config = window.__phaseShowConfig || {};
        this.showLineWorksModal = false;
        this.pdfLoading = true;
        this.pdfMessage = 'LINE WORKSに送信中...';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch(config.lineWorksUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            this.pdfLoading = false;
            if (data.success) {
                sessionStorage.setItem('flashSuccess', data.message);
                window.location.reload();
            } else {
                alert(data.message || 'LINE WORKSへの送信に失敗しました。');
            }
        } catch (error) {
            console.error('LINE WORKS send error:', error);
            alert('LINE WORKSへの送信に失敗しました。');
            this.pdfLoading = false;
        }
    }
}));

// =============================================================
// inventoryDashboard - 在庫管理ダッシュボード
// inventory/index.blade.php で使用
// Blade データは window.__inventoryConfig から取得
// =============================================================
Alpine.data('inventoryDashboard', () => ({
    loading: false,
    pdfLoading: false,
    pdfMessage: '',
    error: null,
    asOfDate: new Date().getFullYear() + '-' +
              String(new Date().getMonth() + 1).padStart(2, '0') + '-' +
              String(new Date().getDate()).padStart(2, '0'),
    inventoryData: [],
    categories: [],
    locations: [],
    filters: {
        category_id: '',
        location_id: 92,
        search: ''
    },
    showDetailModal: false,
    selectedItem: null,

    openDetailModal(item) {
        this.selectedItem = item;
        this.showDetailModal = true;
    },

    closeDetailModal() {
        this.showDetailModal = false;
        this.selectedItem = null;
    },

    async downloadPdf(allLocations) {
        this.pdfLoading = true;
        this.pdfMessage = allLocations ? '全倉庫の在庫データを処理中...' : '在庫データを処理中...';
        try {
            const config = window.__inventoryConfig || {};
            let url = `${config.exportPdfUrl}?as_of_date=${this.asOfDate}`;
            if (allLocations) {
                url += '&all_locations=1';
            } else {
                url += `&location_id=${this.filters.location_id}`;
            }
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('PDF生成に失敗しました');
            }
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = allLocations ? '在庫一覧_全倉庫.pdf' : '在庫一覧.pdf';
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';\n]+)/i);
                if (filenameMatch) {
                    filename = decodeURIComponent(filenameMatch[1]);
                }
            }
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
        } catch (error) {
            console.error('PDF download error:', error);
            alert('PDF出力に失敗しました: ' + error.message);
        } finally {
            this.pdfLoading = false;
            this.pdfMessage = '';
        }
    },

    async init() {
        const config = window.__inventoryConfig || {};
        this.locations = config.locationStats || [];
        try {
            await this.loadMasterData();
            await this.loadInventoryData();
        } catch (error) {
            console.error('Initialization error:', error);
            this.error = 'システムの初期化中にエラーが発生しました: ' + error.message;
        }
    },

    async loadMasterData() {
        try {
            const categoryResponse = await fetch('/api/schedule/categories');
            if (categoryResponse.ok) {
                const categoryData = await categoryResponse.json();
                this.categories = categoryData.categories || [];
            }
        } catch (error) {
            console.error('Master data loading error:', error);
        }
    },

    async loadInventoryData() {
        this.loading = true;
        this.error = null;
        try {
            const params = new URLSearchParams({
                as_of_date: this.asOfDate,
                ...Object.fromEntries(
                    Object.entries(this.filters).filter(([key, value]) => value !== '')
                )
            });
            console.log('🔍 API Request URL:', `/inventory/api/inventory?${params}`);
            console.log('🔍 Request params:', params.toString());
            const response = await fetch(`/inventory/api/inventory?${params}`);
            console.log('🔍 Response status:', response.status);
            const data = await response.json();
            console.log('🔍 Response data:', data);
            if (data.success) {
                this.inventoryData = data.data || [];
                console.log('✅ Data assigned to inventoryData:', this.inventoryData);
                if (this.inventoryData.length > 0) {
                    console.log('✅ First item detailed:', this.inventoryData[0]);
                    console.log('✅ Equipment object:', this.inventoryData[0].equipment);
                }
            } else {
                throw new Error(data.error || '在庫データの取得に失敗しました');
            }
        } catch (error) {
            console.error('Error loading inventory data:', error);
            this.error = error.message;
            this.inventoryData = [];
        } finally {
            this.loading = false;
        }
    },

    getEquipmentCategoryPath(item) {
        const eq = item ? item.equipment : null;
        if (!eq) return '';
        const catName = (eq.subcategory && eq.subcategory.category) ? eq.subcategory.category.name : '';
        const subName = eq.subcategory ? eq.subcategory.name : '';
        return catName + (subName ? ' > ' + subName : '');
    },

    getEquipmentName(item) {
        return (item && item.equipment && item.equipment.name) ? item.equipment.name : '機材名不明';
    },

    getEquipmentCompanyNumber(item) {
        if (!item || item.quantity <= 0) return '-';
        return (item.equipment && item.equipment.company_number) ? item.equipment.company_number : '-';
    }
}));

// =============================================================
// scheduleManager - スケジュール管理ページ
// schedule/index.blade.php で使用
// =============================================================
Alpine.data('scheduleManager', () => ({
    scheduleData: [],
    categories: [],
    subcategories: [],
    performances: [],
    dateRange: [],
    pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 100,
        total: 0,
        from: 0,
        to: 0
    },
    loading: false,
    error: null,
    filters: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        category_id: '',
        subcategory_id: '',
        equipment_name: '',
        performance_id: '',
        per_page: 100
    },
    showMemoModal: false,
    currentCell: {
        equipmentId: '',
        date: '',
        equipmentName: '',
        memo: '',
        customColor: ''
    },
    cellData: {},
    colorPalette: [
        { name: 'ピンク', value: '#fce7f3' },
        { name: '紫', value: '#e9d5ff' },
        { name: '青', value: '#dbeafe' },
        { name: '緑', value: '#d1fae5' },
        { name: '黄', value: '#fef3c7' },
        { name: 'オレンジ', value: '#fed7aa' },
        { name: '赤', value: '#fee2e2' },
        { name: 'グレー', value: '#e5e7eb' },
        { name: '茶', value: '#ede9e4' },
        { name: 'ライム', value: '#ecfccb' }
    ],

    init() {
        this.loadCategories();
        this.loadSubcategories();
        this.loadPerformances();
        this.loadScheduleData();
    },

    async loadCellMemos() {
        try {
            const params = new URLSearchParams({
                start_date: this.filters.start_date,
                end_date: this.filters.end_date
            });
            const response = await fetch(`/api/schedule/cell-memos?${params}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            if (data.success) {
                this.cellData = {};
                data.memos.forEach(memo => {
                    const key = this.getCellKey(memo.equipment_id, memo.schedule_date);
                    this.cellData[key] = { memo: memo.memo, color: memo.color };
                });
            }
        } catch (error) {
            console.error('Failed to load cell memos:', error);
        }
    },

    async saveCellMemoToAPI(equipmentId, date, memo, color) {
        try {
            const response = await fetch('/api/schedule/cell-memos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ equipment_id: equipmentId, schedule_date: date, memo: memo, color: color })
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'メモの保存に失敗しました');
            }
            return true;
        } catch (error) {
            console.error('Failed to save cell memo:', error);
            alert('メモの保存に失敗しました');
            return false;
        }
    },

    async deleteCellMemoFromAPI(equipmentId, date) {
        try {
            const response = await fetch('/api/schedule/cell-memos', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ equipment_id: equipmentId, schedule_date: date })
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'メモの削除に失敗しました');
            }
            return true;
        } catch (error) {
            console.error('Failed to delete cell memo:', error);
            alert('メモの削除に失敗しました');
            return false;
        }
    },

    getCellKey(equipmentId, date) {
        return `${equipmentId}_${date}`;
    },

    openMemoModal(equipmentId, date, equipmentName) {
        const key = this.getCellKey(equipmentId, date);
        const existing = this.cellData[key] || { memo: '', color: '' };
        this.currentCell = {
            equipmentId: equipmentId,
            date: date,
            equipmentName: equipmentName,
            memo: existing.memo,
            customColor: existing.color
        };
        this.showMemoModal = true;
    },

    closeMemoModal() {
        this.showMemoModal = false;
        this.currentCell = { equipmentId: '', date: '', equipmentName: '', memo: '', customColor: '' };
    },

    async saveCellData() {
        const success = await this.saveCellMemoToAPI(
            this.currentCell.equipmentId,
            this.currentCell.date,
            this.currentCell.memo,
            this.currentCell.customColor
        );
        if (success) {
            const key = this.getCellKey(this.currentCell.equipmentId, this.currentCell.date);
            this.cellData[key] = { memo: this.currentCell.memo, color: this.currentCell.customColor };
            this.closeMemoModal();
        }
    },

    async clearCellData() {
        const success = await this.deleteCellMemoFromAPI(
            this.currentCell.equipmentId,
            this.currentCell.date
        );
        if (success) {
            const key = this.getCellKey(this.currentCell.equipmentId, this.currentCell.date);
            delete this.cellData[key];
            this.closeMemoModal();
        }
    },

    getCellMemo(equipmentId, date) {
        const key = this.getCellKey(equipmentId, date);
        return this.cellData[key]?.memo || '';
    },

    formatCellMemo(equipmentId, date) {
        const memo = this.getCellMemo(equipmentId, date);
        if (!memo) return '';
        const chars = Array.from(memo);
        if (chars.length <= 3) return memo;
        return chars.slice(0, 3).join('') + '...';
    },

    getCellCustomClass(equipmentId, date) {
        const key = this.getCellKey(equipmentId, date);
        const color = this.cellData[key]?.color;
        return color ? 'custom-color' : '';
    },

    getCellCustomStyle(equipmentId, date) {
        const key = this.getCellKey(equipmentId, date);
        const color = this.cellData[key]?.color;
        return color ? `--custom-bg-color: ${color}` : '';
    },

    async loadCategories() {
        try {
            const response = await fetch('/api/schedule/categories', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) this.categories = data.categories;
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    },

    async loadSubcategories(categoryId = null) {
        try {
            const params = new URLSearchParams();
            if (categoryId) params.append('category_id', categoryId);
            const response = await fetch(`/api/schedule/subcategories?${params}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) this.subcategories = data.subcategories;
        } catch (error) {
            console.error('Failed to load subcategories:', error);
        }
    },

    async loadPerformances() {
        try {
            const response = await fetch('/api/schedule/performances', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) this.performances = data.performances;
        } catch (error) {
            console.error('Failed to load performances:', error);
        }
    },

    onCategoryChange() {
        this.filters.subcategory_id = '';
        this.loadSubcategories(this.filters.category_id);
    },

    async loadScheduleData(page = 1) {
        this.loading = true;
        this.error = null;
        try {
            const params = new URLSearchParams({
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
                per_page: this.filters.per_page,
                page: page
            });
            if (this.filters.subcategory_id) {
                params.append('subcategory_id', this.filters.subcategory_id);
            } else if (this.filters.category_id) {
                params.append('category_id', this.filters.category_id);
            }
            if (this.filters.equipment_name) params.append('equipment_name', this.filters.equipment_name);
            if (this.filters.performance_id) params.append('performance_id', this.filters.performance_id);

            const response = await fetch(`/api/schedule/equipment?${params}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) {
                this.scheduleData = data.equipment_schedules;
                this.dateRange = data.date_range;
                this.pagination = data.pagination;
                await this.loadCellMemos();
            } else {
                this.error = data.error || 'データの取得に失敗しました';
            }
        } catch (error) {
            this.error = 'ネットワークエラーが発生しました';
            console.error('API Error:', error);
        } finally {
            this.loading = false;
        }
    },

    loadPage(page) {
        if (this.pagination && page >= 1 && page <= this.pagination.last_page) {
            this.loadScheduleData(page);
        }
    },

    resetFilters() {
        this.filters = {
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            category_id: '',
            subcategory_id: '',
            equipment_name: '',
            performance_id: '',
            per_page: 100
        };
        this.loadSubcategories();
        this.loadScheduleData();
    },

    getStatusClass(status) {
        const classes = {
            'available': 'bg-green-500 text-white',
            'reserved': 'bg-blue-500 text-white',
            'checked_out': 'bg-orange-500 text-white',
            'repair': 'bg-red-500 text-white'
        };
        return classes[status] || 'bg-gray-200 text-gray-700';
    },

    getStatusSymbol(status) {
        const symbols = { 'available': '●', 'reserved': '●', 'checked_out': '●', 'repair': '●' };
        return symbols[status] || '○';
    },

    getStatusText(status) {
        const texts = {
            'available': '利用可能',
            'reserved': '予約済み',
            'checked_out': '使用中',
            'in_use': '使用中',
            'repair': '修理中'
        };
        return texts[status] || '不明';
    },

    getStatusTooltip(statusData) {
        if (!statusData) return '';
        let tooltip = this.getStatusText(statusData.status);
        if (statusData.phase_name) tooltip += `\nフェーズ: ${statusData.phase_name}`;
        if (statusData.performance_title) tooltip += `\n公演: ${statusData.performance_title}`;
        if (statusData.note) tooltip += `\n備考: ${statusData.note}`;
        return tooltip;
    },

    formatDateHeader(date) {
        const d = new Date(date + 'T00:00:00');
        return `${d.getMonth() + 1}/${d.getDate()}`;
    },

    formatDayOfWeek(date) {
        const d = new Date(date + 'T00:00:00');
        const days = ['日', '月', '火', '水', '木', '金', '土'];
        return days[d.getDay()];
    },

    getDayHeaderClass(date) {
        const d = new Date(date + 'T00:00:00');
        const dayOfWeek = d.getDay();
        if (dayOfWeek === 0) return 'sunday-header';
        if (dayOfWeek === 6) return 'saturday-header';
        return '';
    },

    detectSpanPeriods(equipment) {
        const spans = [];
        let currentSpan = null;
        this.dateRange.forEach((date, index) => {
            const status = equipment.daily_status[date];
            const shouldSpan = status && (status.status === 'reserved' || status.status === 'checked_out') && status.phase_name;
            if (shouldSpan) {
                const groupKey = `${status.phase_name}_${status.performance_title || ''}`;
                if (!currentSpan || currentSpan.groupKey !== groupKey) {
                    if (currentSpan) spans.push(currentSpan);
                    currentSpan = {
                        startIndex: index,
                        endIndex: index,
                        phase_name: status.phase_name,
                        performance_title: status.performance_title,
                        status: status.status,
                        groupKey: groupKey
                    };
                } else {
                    currentSpan.endIndex = index;
                }
            } else {
                if (currentSpan) { spans.push(currentSpan); currentSpan = null; }
            }
        });
        if (currentSpan) spans.push(currentSpan);
        return spans;
    },

    isSpanStart(equipment, dateIndex) {
        return this.detectSpanPeriods(equipment).find(span => span.startIndex === dateIndex);
    },

    isSpanMiddle(equipment, dateIndex) {
        return this.detectSpanPeriods(equipment).find(span => span.startIndex < dateIndex && span.endIndex >= dateIndex);
    },

    getSpanLength(equipment, dateIndex) {
        const span = this.detectSpanPeriods(equipment).find(span => span.startIndex === dateIndex);
        return span ? span.endIndex - span.startIndex + 1 : 1;
    },

    formatSpanText(span) {
        if (!span) return '';
        return span.performance_title ? `${span.performance_title} - ${span.phase_name}` : span.phase_name;
    },

    getSpanDates(equipment, dateIndex) {
        const span = this.isSpanStart(equipment, dateIndex);
        if (!span) return [];
        const dates = [];
        for (let i = span.startIndex; i <= span.endIndex; i++) {
            dates.push(this.dateRange[i]);
        }
        return dates;
    },

    getDateStatus(equipment, date) {
        const daily = equipment.daily_status[date];
        return daily ? daily.status : null;
    },

    getDailyStatusObj(equipment, date) {
        return equipment.daily_status[date] || null;
    },

    getSpanCellStyle(equipmentId, spanDate) {
        const custom = this.getCellCustomStyle(equipmentId, spanDate);
        return 'width: 40px; min-width: 40px;' + (custom ? ' ' + custom : '');
    }
}));

// =============================================================
// transferManager - 倉庫間機材移動管理
// equipment-transfer/index.blade.php で使用
// =============================================================
Alpine.data('transferManager', () => ({
    equipmentData: [],
    locations: [],
    categories: [],
    transferData: {},
    loading: false,
    error: null,
    showModal: false,
    showBulkModal: false,
    isBulkTransferring: false,
    initialized: false,
    selectedEquipment: null,
    selectedToLocation: '',
    transferNote: '',
    filters: {
        location_id: '',
        category_id: '',
        search: ''
    },

    async init() {
        window.transferManagerData = this;
        try {
            await this.loadMasterData();
            this.setDefaultLocation();
            await this.loadEquipmentData();
            this.initialized = true;
        } catch (error) {
            console.error('倉庫間移動コンポーネントの初期化に失敗:', error);
            this.error = error.message || '初期化エラーが発生しました';
            this.initialized = true;
        }
    },

    get hasPendingTransfers() {
        return this.pendingTransferCount > 0;
    },

    get pendingTransferCount() {
        return Object.values(this.transferData).filter(data => data && data.toLocationId).length;
    },

    get pendingTransfers() {
        return Object.entries(this.transferData)
            .filter(([equipmentId, data]) => data && data.toLocationId)
            .map(([equipmentId, data]) => {
                const equipment = this.equipmentData.find(eq => eq.id == equipmentId);
                const toLocation = this.locations.find(loc => loc.id == data.toLocationId);
                const currentLocation = this.locations.find(loc => loc.id === equipment?.now_location_id);
                return {
                    equipmentId: equipmentId,
                    equipmentName: equipment?.name || '不明',
                    companyNumber: equipment?.company_number || '-',
                    currentLocationName: currentLocation?.name || '不明',
                    toLocationName: toLocation?.name || '不明'
                };
            });
    },

    get availableDestinations() {
        if (!this.selectedEquipment) return [];
        return this.locations.filter(location => location.id !== this.selectedEquipment.location?.id);
    },

    async loadMasterData() {
        try {
            const locationResponse = await fetch(API_CONFIG.warehouses);
            if (locationResponse.ok) {
                const locationData = await locationResponse.json();
                this.locations = locationData.warehouses || [];
            } else {
                throw new Error('倉庫データの取得に失敗しました');
            }
            const categoryResponse = await fetch(API_CONFIG.categories);
            if (categoryResponse.ok) {
                const categoryData = await categoryResponse.json();
                this.categories = categoryData.categories || [];
            }
        } catch (error) {
            console.error('Master data loading error:', error);
            throw error;
        }
    },

    async loadEquipmentData() {
        this.loading = true;
        this.error = null;
        try {
            const params = new URLSearchParams(
                Object.fromEntries(Object.entries(this.filters).filter(([key, value]) => value !== ''))
            );
            const response = await fetch(`${API_CONFIG.equipment}?${params}`);
            const data = await response.json();
            if (data.success) {
                this.equipmentData = data.data || [];
            } else {
                throw new Error(data.error || 'データの取得に失敗しました');
            }
        } catch (error) {
            console.error('Error loading equipment data:', error);
            this.error = error.message;
            this.equipmentData = [];
        } finally {
            this.loading = false;
        }
    },

    setDefaultLocation() {
        const sumidaWarehouse = this.locations.find(loc => loc.id === CONSTANTS.SUMIDA_WAREHOUSE_ID);
        if (sumidaWarehouse) {
            this.filters.location_id = CONSTANTS.SUMIDA_WAREHOUSE_ID;
        } else if (this.locations.length > 0) {
            this.filters.location_id = this.locations[0].id;
        }
    },

    onLocationChange(event) {
        const selectedValue = parseInt(event.target.value);
        const selectedLocation = this.locations.find(loc => loc.id === selectedValue);
        if (selectedLocation) {
            this.filters.location_id = selectedValue;
            this.loadEquipmentData();
        } else {
            this.setDefaultLocation();
        }
    },

    openTransferModal(equipment) {
        this.selectedEquipment = equipment;
        this.selectedToLocation = '';
        this.transferNote = '';
        this.showModal = true;
    },

    openBulkTransferModal() {
        if (!this.hasPendingTransfers) return;
        this.showBulkModal = true;
    },

    updateTransferDestination(equipmentId, toLocationId) {
        if (!this.transferData[equipmentId]) {
            this.transferData[equipmentId] = {};
        }
        this.transferData[equipmentId].toLocationId = toLocationId || '';
    },

    getTransferDestination(equipmentId) {
        return this.transferData[equipmentId]?.toLocationId || '';
    },

    getAvailableDestinations(equipment) {
        return this.locations.filter(location => location.id !== equipment.now_location_id);
    },

    async executeTransfer() {
        if (!this.selectedEquipment || !this.selectedToLocation) return;
        try {
            const response = await fetch(API_CONFIG.transfer, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    equipment_id: this.selectedEquipment.id,
                    to_location_id: this.selectedToLocation,
                    note: this.transferNote || '倉庫間移動画面からの移動'
                })
            });
            const data = await response.json();
            if (data.success) {
                alert(`${this.selectedEquipment.name}の移動が完了しました`);
                this.showModal = false;
                await this.loadEquipmentData();
            } else {
                throw new Error(data.error || '倉庫間移動に失敗しました');
            }
        } catch (error) {
            console.error('Transfer error:', error);
            alert('移動に失敗しました: ' + error.message);
        }
    },

    async executeBulkTransfer() {
        if (!this.hasPendingTransfers) return;
        this.isBulkTransferring = true;
        try {
            const transfers = Object.entries(this.transferData)
                .filter(([equipmentId, data]) => data && data.toLocationId)
                .map(([equipmentId, data]) => ({
                    equipment_id: parseInt(equipmentId),
                    to_location_id: parseInt(data.toLocationId),
                    note: '一括倉庫間移動'
                }));
            const response = await fetch(API_CONFIG.bulkTransfer, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ transfers: transfers })
            });
            const data = await response.json();
            if (data.success) {
                alert(`${transfers.length}件の機材移動が完了しました`);
                this.transferData = {};
                this.showBulkModal = false;
                await this.loadEquipmentData();
            } else {
                throw new Error(data.error || '一括移動に失敗しました');
            }
        } catch (error) {
            console.error('Bulk transfer error:', error);
            alert('一括移動に失敗しました: ' + error.message);
        } finally {
            this.isBulkTransferring = false;
        }
    }
}));

// =============================================================
// returnSelectManager - 機材返却先選択
// equipment-transfer/return-select.blade.php で使用
// =============================================================
Alpine.data('returnSelectManager', () => ({
    equipmentData: [],
    locations: [],
    categories: [],
    loading: false,
    error: null,
    showBulkModal: false,
    isBulkReturning: false,
    initialized: false,
    returnData: {},
    isBulkReturn: false,
    bulkReturnData: null,
    filters: {
        location_id: '',
        category_id: '',
        search: ''
    },

    async init() {
        window.returnSelectData = this;
        try {
            const returnEquipmentData = sessionStorage.getItem('returnEquipmentData');
            const bulkReturnData = sessionStorage.getItem('bulkReturnData');
            if (!returnEquipmentData && !bulkReturnData) {
                this.error = '返却対象の機材情報が見つかりません。機材詳細画面から返却処理を開始してください。';
                this.initialized = true;
                return;
            }
            if (bulkReturnData) {
                this.bulkReturnData = JSON.parse(bulkReturnData);
                this.isBulkReturn = true;
                await this.loadMasterData();
                await this.loadBulkEquipmentData();
            } else {
                this.returnEquipmentData = JSON.parse(returnEquipmentData);
                this.isBulkReturn = false;
                await this.loadMasterData();
                await this.loadSpecificEquipmentData();
            }
            this.initialized = true;
        } catch (error) {
            console.error('Initialization failed:', error);
            this.error = error.message;
            this.initialized = true;
        }
    },

    get pendingReturnsCount() {
        return Object.keys(this.returnData).filter(equipmentId =>
            this.returnData[equipmentId] && this.returnData[equipmentId].returnLocationId
        ).length;
    },

    get pendingReturns() {
        return Object.entries(this.returnData)
            .filter(([equipmentId, data]) => data && data.returnLocationId)
            .map(([equipmentId, data]) => {
                const equipment = this.equipmentData.find(eq => eq.id == equipmentId);
                const returnLocation = this.locations.find(loc => loc.id == data.returnLocationId);
                return {
                    equipmentId: equipmentId,
                    equipmentName: equipment.name,
                    companyNumber: equipment.company_number || '-',
                    returnLocationName: returnLocation?.name || '不明'
                };
            });
    },

    async loadMasterData() {
        try {
            const locationResponse = await fetch(API_CONFIG.warehouses);
            if (locationResponse.ok) {
                const locationData = await locationResponse.json();
                this.locations = locationData.warehouses || [];
            } else {
                throw new Error('倉庫データの取得に失敗しました');
            }
            const categoryResponse = await fetch(API_CONFIG.categories);
            if (categoryResponse.ok) {
                const categoryData = await categoryResponse.json();
                this.categories = categoryData.categories || [];
            }
        } catch (error) {
            console.error('Master data loading error:', error);
            throw error;
        }
    },

    async loadEquipmentData() {
        this.loading = true;
        this.error = null;
        try {
            const params = new URLSearchParams({
                ...Object.fromEntries(
                    Object.entries(this.filters).filter(([key, value]) => value !== '')
                ),
                location_ids: '92,93,94'
            });
            const response = await fetch(`${API_CONFIG.equipment}?${params}`);
            const data = await response.json();
            if (data.success) {
                this.equipmentData = data.data || [];
            } else {
                throw new Error(data.error || 'データの取得に失敗しました');
            }
        } catch (error) {
            console.error('Error loading equipment data:', error);
            this.error = error.message;
            this.equipmentData = [];
        } finally {
            this.loading = false;
        }
    },

    async loadSpecificEquipmentData() {
        this.loading = true;
        this.error = null;
        try {
            if (!this.returnEquipmentData || !this.returnEquipmentData.equipmentId) {
                throw new Error('機材情報が不正です');
            }
            const equipmentId = this.returnEquipmentData.equipmentId;
            const response = await fetch(`${API_CONFIG.equipment}?equipment_id=${equipmentId}`);
            const data = await response.json();
            if (data.success && data.data.length > 0) {
                this.equipmentData = data.data;
                const equipment = this.equipmentData[0];
                if (equipment) {
                    this.returnData[equipment.id] = { returnLocationId: equipment.location_id };
                }
            } else {
                throw new Error('対象機材が見つかりません');
            }
        } catch (error) {
            console.error('Error loading specific equipment data:', error);
            this.error = error.message;
            this.equipmentData = [];
        } finally {
            this.loading = false;
        }
    },

    async loadBulkEquipmentData() {
        this.loading = true;
        this.error = null;
        try {
            if (!this.bulkReturnData || !Array.isArray(this.bulkReturnData)) {
                throw new Error('一括返却データが不正です');
            }
            const filteredEquipments = this.bulkReturnData.filter(item =>
                item.locationId >= 92 && item.locationId <= 94
            );
            this.equipmentData = filteredEquipments.map(item => ({
                id: item.equipmentId,
                name: item.equipmentName,
                company_number: item.companyNumber,
                location_id: item.locationId,
                management_type: 'individual',
                location: { id: item.locationId, name: `ID: ${item.locationId}` },
                subcategory: { category: { name: 'カテゴリ不明' }, name: 'サブカテゴリ不明' }
            }));
            filteredEquipments.forEach(item => {
                this.returnData[item.equipmentId] = {
                    returnLocationId: item.locationId,
                    phaseEquipmentId: item.phaseEquipmentId
                };
            });
        } catch (error) {
            console.error('Error loading bulk equipment data:', error);
            this.error = error.message;
            this.equipmentData = [];
        } finally {
            this.loading = false;
        }
    },

    getOtherWarehouses(equipment) {
        return this.locations.filter(location => location.id !== equipment.location_id);
    },

    updateReturnDestination(equipmentId, returnLocationId) {
        if (!this.returnData[equipmentId]) {
            this.returnData[equipmentId] = {};
        }
        this.returnData[equipmentId].returnLocationId = returnLocationId || '';
    },

    getReturnDestination(equipmentId) {
        return this.returnData[equipmentId]?.returnLocationId || '';
    },

    openBulkReturnModal() {
        if (this.pendingReturnsCount === 0) return;
        this.showBulkModal = true;
    },

    async executeBulkReturn() {
        if (this.pendingReturnsCount === 0) return;
        this.isBulkReturning = true;
        try {
            const returns = Object.entries(this.returnData)
                .filter(([equipmentId, data]) => data && data.returnLocationId)
                .map(([equipmentId, data]) => ({
                    equipment_id: parseInt(equipmentId),
                    return_location_id: parseInt(data.returnLocationId),
                    phase_equipment_id: data.phaseEquipmentId || this.returnEquipmentData?.phaseEquipmentId || null
                }));
            const response = await fetch(API_CONFIG.bulkReturn, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ returns: returns })
            });
            const data = await response.json();
            if (data.success) {
                alert(`${returns.length}件の機材返却が完了しました`);
                this.returnData = {};
                this.showBulkModal = false;
                sessionStorage.removeItem('returnEquipmentData');
                sessionStorage.removeItem('bulkReturnData');
                const phaseId = this.isBulkReturn
                    ? this.bulkReturnData?.[0]?.phaseId
                    : this.returnEquipmentData?.phaseId;
                if (phaseId) {
                    window.location.href = `/phases/${phaseId}/equipment`;
                } else {
                    window.location.href = '/dashboard';
                }
            } else {
                throw new Error(data.error || '一括返却に失敗しました');
            }
        } catch (error) {
            console.error('Bulk return error:', error);
            alert('一括返却に失敗しました: ' + error.message);
        } finally {
            this.isBulkReturning = false;
        }
    }
}));

// =============================================================
// locationSelectorModal - 場所選択モーダルコンポーネント
// components/location-selector-modal.blade.php で使用
// =============================================================
Alpine.data('locationSelectorModal', (config) => ({
    isOpen: false,
    loading: false,
    locations: [],
    filteredLocations: [],
    selectedLocation: null,
    searchQuery: '',
    errorMessage: '',
    config: config,
    onConfirmCallback: null,

    init() {
        window.addEventListener(`open-${this.config.id}`, (event) => {
            this.open(event.detail);
        });
    },

    async open(options = {}) {
        this.isOpen = true;
        this.selectedLocation = null;
        this.searchQuery = '';
        this.errorMessage = '';
        this.onConfirmCallback = options.onConfirm || null;
        await this.loadLocations();
    },

    close() {
        this.isOpen = false;
        this.selectedLocation = null;
        this.searchQuery = '';
        this.errorMessage = '';
        this.onConfirmCallback = null;
    },

    async loadLocations() {
        this.loading = true;
        try {
            const response = await fetch('/inventory/api/warehouses', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            if (data.success) {
                this.locations = data.warehouses;
                this.filteredLocations = [...this.locations];
            } else {
                this.errorMessage = data.error || '場所の読み込みに失敗しました';
            }
        } catch (error) {
            this.errorMessage = 'ネットワークエラーが発生しました';
            console.error('Location loading error:', error);
        } finally {
            this.loading = false;
        }
    },

    filterLocations() {
        if (!this.searchQuery.trim()) {
            this.filteredLocations = [...this.locations];
            return;
        }
        const query = this.searchQuery.toLowerCase();
        this.filteredLocations = this.locations.filter(location =>
            location.name.toLowerCase().includes(query) ||
            (location.address && location.address.toLowerCase().includes(query)) ||
            location.type.toLowerCase().includes(query)
        );
    },

    selectLocation(location) {
        this.selectedLocation = location;
    },

    confirm() {
        if (this.config.required && !this.selectedLocation) {
            this.errorMessage = '場所を選択してください';
            return;
        }
        if (this.onConfirmCallback) {
            this.onConfirmCallback(this.selectedLocation);
        }
        window.dispatchEvent(new CustomEvent(`${this.config.id}-confirmed`, {
            detail: { location: this.selectedLocation }
        }));
        this.close();
    }
}));
