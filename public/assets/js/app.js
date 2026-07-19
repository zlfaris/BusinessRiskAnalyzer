document.addEventListener('alpine:init', () => {
    Alpine.data('appData', () => ({

        // ─── Global State ─────────────────────────────────────────────
        view: 'auth',       // 'auth' | 'dashboard'
        dashView: 'wizard', // 'wizard' | 'results'
        loading: false,
        sidebarOpen: false,

        // ─── Auth State ───────────────────────────────────────────────
        isLogin: true,
        authError: null,
        authFieldErrors: { email: '', password: '' },
        user: null,
        authForm: { name: '', email: '', password: '' },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        // ─── Dashboard / Analysis State ───────────────────────────────
        history: [],
        historyLoading: false,
        searchQuery: '',
        analysisError: null,
        wizardErrors: {},
        currentAnalysis: null,
        editId: null,
        wizardForm: {
            business_name: '',
            category: '',
            target_pasar_utama: '',
            market_saturation: '',
            capex: '',
            opex: '',
            average_price: ''
        },

        // ─── Lifecycle ────────────────────────────────────────────────
        init() {
            this.fetchHistory(true);
        },

        // ─── Computed: Filtered History ───────────────────────────────
        get filteredHistory() {
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) return this.history;
            return this.history.filter(item =>
                item.business_name.toLowerCase().includes(q) ||
                item.category.toLowerCase().includes(q)
            );
        },

        // ─── Auth Helpers ─────────────────────────────────────────────
        toggleAuth() {
            this.isLogin = !this.isLogin;
            this.authError = null;
            this.authFieldErrors = { email: '', password: '' };
            this.authForm.password = '';
        },

        validateAuthForm() {
            this.authFieldErrors = { email: '', password: '' };
            let valid = true;

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!this.authForm.email || !emailRegex.test(this.authForm.email)) {
                this.authFieldErrors.email = "Format email tidak valid.";
                valid = false;
            }
            if (!this.authForm.password || this.authForm.password.length < 8) {
                this.authFieldErrors.password = "Password minimal 8 karakter.";
                valid = false;
            }
            return valid;
        },

        // ─── Generic API Fetch Wrapper ────────────────────────────────
        async apiCall(endpoint, method = 'GET', body = null) {
            let pathname = window.location.pathname;
            
            // Remove filename (like index.html or index.php) if present in the URL path
            if (pathname.match(/\/[^\/]+\.[^\/]+$/)) {
                pathname = pathname.substring(0, pathname.lastIndexOf('/'));
            }
            
            // Ensure the path ends with a slash
            if (!pathname.endsWith('/')) {
                pathname += '/';
            }
            
            // Remove leading slash from endpoint
            const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
            
            // Build the dynamic URL and enforce routing through index.php
            const fullUrl = window.location.origin + pathname + 'index.php/' + cleanEndpoint;

            const options = {
                method,
                headers: { 'Content-Type': 'application/json' }
            };
            if (body) {
                options.body = JSON.stringify(body);
            }
            const res = await fetch(fullUrl, options);
            const data = await res.json();

            if (!res.ok) {
                // Auto redirect to login on 401 for any protected endpoint
                if (res.status === 401 && endpoint !== 'api/login') {
                    this.view = 'auth';
                }
                throw new Error(data.error || `Request gagal (HTTP ${res.status})`);
            }
            return data;
        },

        // ─── AUTH METHODS ─────────────────────────────────────────────
        async login() {
            if (!this.validateAuthForm()) return;
            this.loading = true;
            this.authError = null;
            try {
                const data = await this.apiCall('api/login', 'POST', {
                    email: this.authForm.email,
                    password: this.authForm.password
                });
                this.user = data.user;
                this.authForm = { name: '', email: '', password: '' };
                this.view = 'dashboard';
                this.dashView = 'wizard';
                this.fetchHistory();
            } catch (err) {
                this.authError = err.message;
            } finally {
                this.loading = false;
            }
        },

        async register() {
            if (!this.validateAuthForm()) return;
            if (!this.authForm.name || this.authForm.name.trim().length < 2) {
                this.authError = "Nama lengkap wajib diisi.";
                return;
            }
            this.loading = true;
            this.authError = null;
            try {
                await this.apiCall('api/register', 'POST', this.authForm);
                // Auto login after successful register
                await this.login();
            } catch (err) {
                this.authError = err.message;
                this.loading = false;
            }
        },

        async logout() {
            try {
                await this.apiCall('api/logout', 'POST');
            } catch (e) {
                // Ignore errors on logout, always reset state
            }
            this.user = null;
            this.history = [];
            this.currentAnalysis = null;
            this.editId = null;
            this.view = 'auth';
            this.isLogin = true;
        },

        // ─── HISTORY ─────────────────────────────────────────────────
        async fetchHistory(initialCheck = false) {
            this.historyLoading = true;
            try {
                if (initialCheck && !this.user) {
                    const userRes = await this.apiCall('api/user/me', 'GET');
                    this.user = userRes.user;
                }
                const res = await this.apiCall('api/history', 'GET');
                this.history = (res.data || []).map(item => ({
                    ...item,
                    gemini_raw_response: typeof item.gemini_raw_response === 'string'
                        ? JSON.parse(item.gemini_raw_response)
                        : item.gemini_raw_response
                }));
                if (initialCheck) {
                    this.view = 'dashboard';
                }
            } catch (err) {
                if (initialCheck) {
                    this.view = 'auth';
                }
            } finally {
                this.historyLoading = false;
            }
        },

        // ─── CRUD: Load (Read) ────────────────────────────────────────
        loadAnalysis(item) {
            this.currentAnalysis = item;
            this.dashView = 'results';
            this.editId = null;
        },

        // ─── CRUD: Delete ─────────────────────────────────────────────
        async deleteAnalysis(id) {
            if (!confirm('Yakin ingin menghapus analisis ini?')) return;
            try {
                await this.apiCall(`api/analyses/${id}`, 'DELETE');

                // If the deleted item is the currently open one, clear the panel
                if (this.currentAnalysis && this.currentAnalysis.id == id) {
                    this.currentAnalysis = null;
                    this.dashView = 'wizard';
                }

                // Remove from local list
                this.history = this.history.filter(h => h.id != id);
            } catch (err) {
                alert('Gagal menghapus: ' + err.message);
            }
        },

        // ─── CRUD: Edit (prefill wizard) ──────────────────────────────
        editAnalysis() {
            if (!this.currentAnalysis) return;
            this.editId = this.currentAnalysis.id;
            this.wizardForm = {
                business_name:       this.currentAnalysis.business_name,
                category:            this.currentAnalysis.category,
                target_pasar_utama:  this.currentAnalysis.target_pasar_utama,
                market_saturation:   this.currentAnalysis.market_saturation,
                capex:               parseFloat(this.currentAnalysis.capex) || '',
                opex:                parseFloat(this.currentAnalysis.opex) || '',
                average_price:       parseFloat(this.currentAnalysis.average_price) || ''
            };
            this.wizardErrors = {};
            this.analysisError = null;
            this.dashView = 'wizard';
        },

        cancelEdit() {
            this.editId = null;
            this.wizardErrors = {};
            if (this.currentAnalysis) {
                this.dashView = 'results';
            }
        },

        newAnalysis() {
            this.editId = null;
            this.currentAnalysis = null;
            this.analysisError = null;
            this.wizardErrors = {};
            this.wizardForm = {
                business_name: '', category: '', target_pasar_utama: '',
                market_saturation: '', capex: '', opex: '', average_price: ''
            };
            this.dashView = 'wizard';
        },

        // ─── Wizard Validation ────────────────────────────────────────
        validateWizard() {
            this.wizardErrors = {};
            let valid = true;

            if (!this.wizardForm.business_name || this.wizardForm.business_name.trim().length < 5) {
                this.wizardErrors.business_name = "Nama bisnis minimal 5 karakter.";
                valid = false;
            }
            if (!this.wizardForm.category) {
                this.wizardErrors.category = "Pilih kategori bisnis.";
                valid = false;
            }
            if (!this.wizardForm.target_pasar_utama || this.wizardForm.target_pasar_utama.trim().length < 3) {
                this.wizardErrors.target_pasar_utama = "Target pasar minimal 3 karakter.";
                valid = false;
            }
            if (!this.wizardForm.market_saturation) {
                this.wizardErrors.market_saturation = "Pilih tingkat kejenuhan pasar.";
                valid = false;
            }
            if (this.wizardForm.capex === '' || isNaN(this.wizardForm.capex) || Number(this.wizardForm.capex) < 0) {
                this.wizardErrors.capex = "CAPEX harus berupa angka positif.";
                valid = false;
            }
            if (this.wizardForm.opex === '' || isNaN(this.wizardForm.opex) || Number(this.wizardForm.opex) < 0) {
                this.wizardErrors.opex = "OPEX harus berupa angka positif.";
                valid = false;
            }
            if (this.wizardForm.average_price === '' || isNaN(this.wizardForm.average_price) || Number(this.wizardForm.average_price) <= 0) {
                this.wizardErrors.average_price = "Harga jual harus lebih dari 0.";
                valid = false;
            }
            return valid;
        },

        // ─── CRUD: Create / Update ────────────────────────────────────
        async submitAnalysis() {
            if (!this.validateWizard()) return;

            this.loading = true;
            this.analysisError = null;

            const payload = {
                ...this.wizardForm,
                capex:         parseFloat(this.wizardForm.capex),
                opex:          parseFloat(this.wizardForm.opex),
                average_price: parseFloat(this.wizardForm.average_price)
            };

            try {
                let resData;
                if (this.editId) {
                    // UPDATE (PUT)
                    resData = await this.apiCall(`api/analyses/${this.editId}`, 'PUT', payload);
                } else {
                    // CREATE (POST)
                    resData = await this.apiCall('api/analyze', 'POST', payload);
                }

                const geminiResult = resData.gemini_result;

                // Build the object for the Results panel
                const freshAnalysis = {
                    id:                  resData.analysis_id || this.editId,
                    business_name:       payload.business_name,
                    category:            payload.category,
                    target_pasar_utama:  payload.target_pasar_utama,
                    market_saturation:   payload.market_saturation,
                    capex:               payload.capex,
                    opex:                payload.opex,
                    average_price:       payload.average_price,
                    gemini_raw_response: geminiResult,
                    created_at:          new Date().toISOString()
                };

                this.currentAnalysis = freshAnalysis;
                this.dashView = 'results';
                this.editId = null;

                // Reset form
                this.wizardForm = {
                    business_name: '', category: '', target_pasar_utama: '',
                    market_saturation: '', capex: '', opex: '', average_price: ''
                };
                this.wizardErrors = {};

                // Refresh sidebar
                this.fetchHistory();

            } catch (err) {
                this.analysisError = err.message;
            } finally {
                this.loading = false;
            }
        },

        // ─── UI Helpers ───────────────────────────────────────────────
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        getScoreClass(score) {
            if (score === null || score === undefined) return '';
            if (score >= 71) return 'score-good';
            if (score >= 41) return 'score-medium';
            return 'score-bad';
        },

        getScoreLabel(score) {
            if (score >= 71) return 'Layak Lanjut';
            if (score >= 41) return 'Waspada - Perlu Revisi';
            return 'Bahaya Kritis - Stop & Pikirkan Ulang';
        }
    }));
});
