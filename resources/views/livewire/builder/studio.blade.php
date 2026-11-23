<div 
    x-data="{
        primaryColor: @entangle('primaryColor').live,
        secondaryColor: @entangle('secondaryColor').live,
        fontFamily: @entangle('fontFamily').live,
        deviceView: @entangle('deviceView'),
        postThemeUpdate() {
            const iframe = document.getElementById('preview-iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage({
                    type: 'UPDATE_THEME',
                    payload: {
                        primaryColor: this.primaryColor,
                        secondaryColor: this.secondaryColor,
                        fontFamily: this.fontFamily
                    }
                }, '*');
            }
        },
        init() {
            this.$watch('primaryColor', () => this.postThemeUpdate());
            this.$watch('secondaryColor', () => this.postThemeUpdate());
            this.$watch('fontFamily', () => this.postThemeUpdate());
        }
    }"
    @template-changed.window="const iframe = document.getElementById('preview-iframe'); if (iframe) { iframe.src = iframe.src; }"
    class="h-screen w-screen flex flex-col overflow-hidden bg-slate-900 text-slate-100 font-sans"
>
    <!-- Top Header Bar -->
    <header class="h-16 bg-slate-950 border-b border-slate-800 px-4 sm:px-6 flex items-center justify-between shrink-0 z-30 shadow-md">
        <!-- Left: Back Button, Business Name, Status Pill -->
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <a 
                href="{{ route('dashboard') }}" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 transition"
                title="Kembali ke Dashboard"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="hidden sm:inline">Dashboard</span>
            </a>

            <div class="h-6 w-px bg-slate-800 hidden sm:block"></div>

            <div class="flex items-center gap-2.5 min-w-0">
                <span class="text-sm font-bold text-white truncate max-w-[140px] sm:max-w-[220px]">
                    {{ $website->business->name }}
                </span>

                <!-- Status Badge -->
                @if ($website->status === 'published')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-800/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                        published
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-950 text-amber-400 border border-amber-800/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span>
                        draft
                    </span>
                @endif
            </div>
        </div>

        <!-- Center: Device Viewport Switcher -->
        <div class="hidden md:flex items-center bg-slate-900 border border-slate-800 rounded-xl p-1 gap-1 shadow-inner">
            <!-- Desktop Button -->
            <button 
                type="button" 
                wire:click="setDeviceView('desktop')"
                @click="deviceView = 'desktop'"
                :class="deviceView === 'desktop' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                title="Tampilan Desktop (100%)"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Desktop</span>
            </button>

            <!-- Tablet Button -->
            <button 
                type="button" 
                wire:click="setDeviceView('tablet')"
                @click="deviceView = 'tablet'"
                :class="deviceView === 'tablet' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                title="Tampilan Tablet (768px)"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Tablet</span>
            </button>

            <!-- Mobile Button -->
            <button 
                type="button" 
                wire:click="setDeviceView('mobile')"
                @click="deviceView = 'mobile'"
                :class="deviceView === 'mobile' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                title="Tampilan Ponsel (375px)"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Mobile</span>
            </button>
        </div>

        <!-- Right: Preview Link & Action Buttons -->
        <div class="flex items-center gap-2.5">
            <!-- Live Preview Link -->
            <a 
                href="{{ url('/s/' . $website->slug) }}" 
                target="_blank" 
                class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 transition"
                title="Buka Website Publik di Tab Baru"
            >
                <span>Live URL</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>

            <!-- Simpan Tema Button -->
            <button 
                type="button" 
                wire:click="saveTheme"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 active:bg-blue-700 shadow-sm shadow-blue-600/30 transition disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="saveTheme" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span>Simpan Tema</span>
                </span>
                <span wire:loading wire:target="saveTheme" class="flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Menyimpan...</span>
                </span>
            </button>

            <!-- Terbitkan Website / Toggle Publish Button -->
            <button 
                type="button" 
                wire:click="togglePublish"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition disabled:opacity-50 {{ $website->status === 'published' ? 'text-amber-300 bg-amber-950/80 hover:bg-amber-900 border border-amber-800' : 'text-emerald-100 bg-emerald-600 hover:bg-emerald-500 shadow-sm shadow-emerald-600/30' }}"
            >
                <span wire:loading.remove wire:target="togglePublish" class="flex items-center gap-1.5">
                    @if ($website->status === 'published')
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <span>Jadikan Draf</span>
                    @else
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Terbitkan Website</span>
                    @endif
                </span>
                <span wire:loading wire:target="togglePublish" class="flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memproses...</span>
                </span>
            </button>
        </div>
    </header>

    <!-- Studio Body: Split View -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- Floating Flash Notification -->
        @if (session('success'))
            <div 
                x-data="{ show: true }" 
                x-show="show" 
                x-init="setTimeout(() => show = false, 4000)"
                class="absolute top-4 right-4 z-50 bg-emerald-950 border border-emerald-700/80 text-emerald-300 text-xs font-semibold px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2"
            >
                <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
                <button type="button" @click="show = false" class="ml-2 text-emerald-400 hover:text-white">&times;</button>
            </div>
        @endif

        <!-- Left Sidebar Panel (~380px) -->
        <aside class="w-full sm:w-[380px] shrink-0 bg-slate-900 border-r border-slate-800 flex flex-col overflow-y-auto">
            <div class="p-5 space-y-6">
                <!-- Section 1: Template Selector -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                            <span>Pilih Template</span>
                        </label>
                        <span class="text-[11px] text-slate-500">{{ $templates->count() }} Pilihan</span>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($templates as $tmpl)
                            @php
                                $isSelected = $templateId === $tmpl->id;
                            @endphp
                            <div 
                                wire:key="template-{{ $tmpl->id }}"
                                wire:click="changeTemplate({{ $tmpl->id }})"
                                class="p-3 rounded-xl border cursor-pointer transition flex items-center justify-between {{ $isSelected ? 'bg-blue-950/40 border-blue-500 ring-1 ring-blue-500/50' : 'bg-slate-800/60 border-slate-700/60 hover:bg-slate-800 hover:border-slate-600' }}"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300' }}">
                                        {{ strtoupper(substr($tmpl->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-white truncate">{{ $tmpl->name }}</h4>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $tmpl->description ?? 'Desain responsif & modern' }}</p>
                                    </div>
                                </div>

                                <div>
                                    @if ($isSelected)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500 text-white">
                                            Aktif
                                        </span>
                                    @else
                                        <button 
                                            type="button" 
                                            class="text-[11px] font-semibold text-slate-400 hover:text-white px-2 py-1 rounded-lg hover:bg-slate-700 transition"
                                        >
                                            Pilih
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-slate-800"></div>

                <!-- Section 2: Color Customizer -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                            <span>Kustomisasi Warna</span>
                        </label>
                        <span class="text-[10px] text-emerald-400 font-mono">0ms Realtime</span>
                    </div>

                    <!-- Primary Color -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="primaryColor" class="text-xs font-semibold text-slate-300">Warna Primer</label>
                            <span class="text-[11px] font-mono text-slate-400" x-text="primaryColor"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Native Color Picker -->
                            <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-700 shrink-0 shadow-inner">
                                <input 
                                    type="color" 
                                    id="primaryColorPicker"
                                    wire:model.live="primaryColor"
                                    x-model="primaryColor"
                                    class="absolute -inset-2 w-14 h-14 cursor-pointer"
                                    aria-label="Warna Primer Picker"
                                >
                            </div>
                            <!-- Hex Input -->
                            <input 
                                type="text" 
                                id="primaryColor"
                                wire:model.live="primaryColor"
                                x-model="primaryColor"
                                maxlength="7"
                                placeholder="#2563EB"
                                class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                        @error('primaryColor')
                            <p class="text-[11px] text-red-400 font-medium">{{ $message }}</p>
                        @enderror

                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1.5 pt-1">
                            @foreach (['#2563EB', '#059669', '#7C3AED', '#E11D48', '#D97706', '#0F172A'] as $preset)
                                <button 
                                    type="button" 
                                    wire:click="$set('primaryColor', '{{ $preset }}')"
                                    @click="primaryColor = '{{ $preset }}'"
                                    class="w-6 h-6 rounded-lg border border-slate-700 hover:scale-110 transition shadow-xs"
                                    style="background-color: {{ $preset }}"
                                    title="{{ $preset }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center justify-between">
                            <label for="secondaryColor" class="text-xs font-semibold text-slate-300">Warna Sekunder</label>
                            <span class="text-[11px] font-mono text-slate-400" x-text="secondaryColor"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Native Color Picker -->
                            <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-700 shrink-0 shadow-inner">
                                <input 
                                    type="color" 
                                    id="secondaryColorPicker"
                                    wire:model.live="secondaryColor"
                                    x-model="secondaryColor"
                                    class="absolute -inset-2 w-14 h-14 cursor-pointer"
                                    aria-label="Warna Sekunder Picker"
                                >
                            </div>
                            <!-- Hex Input -->
                            <input 
                                type="text" 
                                id="secondaryColor"
                                wire:model.live="secondaryColor"
                                x-model="secondaryColor"
                                maxlength="7"
                                placeholder="#1E40AF"
                                class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                        @error('secondaryColor')
                            <p class="text-[11px] text-red-400 font-medium">{{ $message }}</p>
                        @enderror

                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1.5 pt-1">
                            @foreach (['#1E40AF', '#065F46', '#5B21B6', '#9F1239', '#78350F', '#334155'] as $preset)
                                <button 
                                    type="button" 
                                    wire:click="$set('secondaryColor', '{{ $preset }}')"
                                    @click="secondaryColor = '{{ $preset }}'"
                                    class="w-6 h-6 rounded-lg border border-slate-700 hover:scale-110 transition shadow-xs"
                                    style="background-color: {{ $preset }}"
                                    title="{{ $preset }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800"></div>

                <!-- Section 3: Typography / Font Selector -->
                <div class="space-y-3">
                    <label for="fontFamily" class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                        <span>Jenis Huruf (Font)</span>
                    </label>

                    <div class="space-y-2">
                        <select 
                            id="fontFamily"
                            wire:model.live="fontFamily"
                            x-model="fontFamily"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="Inter">Inter (Modern & Bersih)</option>
                            <option value="Poppins">Poppins (Geometrik & Ramah)</option>
                            <option value="Roboto">Roboto (Klasik & Jelas)</option>
                            <option value="Plus Jakarta Sans">Plus Jakarta Sans (Elegan)</option>
                            <option value="Montserrat">Montserrat (Tegas & Kontemporer)</option>
                            <option value="Playfair Display">Playfair Display (Mewah & Klasik)</option>
                            <option value="Outfit">Outfit (Minimalis & Segar)</option>
                        </select>
                        @error('fontFamily')
                            <p class="text-[11px] text-red-400 font-medium">{{ $message }}</p>
                        @enderror

                        <!-- Font Preview Card -->
                        <div 
                            class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/60 text-xs text-slate-300"
                            :style="`font-family: '${fontFamily}', sans-serif;`"
                        >
                            <p class="font-bold text-sm text-white">Inamikro UMKM Studio</p>
                            <p class="text-[11px] text-slate-400 mt-1">Pratinjau jenis huruf secara instan tanpa memuat ulang halaman.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800"></div>

                <!-- Bottom Save Button in sidebar for convenience -->
                <div class="pt-2">
                    <button 
                        type="button" 
                        wire:click="saveTheme"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-600/30 transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Perubahan Tema</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Right Main Panel: Responsive Iframe Preview Canvas -->
        <main class="flex-1 bg-slate-950 flex flex-col items-center justify-center p-2 sm:p-4 overflow-hidden relative">
            <!-- Viewport Container with dynamic responsive sizing -->
            <div 
                class="transition-all duration-300 ease-in-out flex flex-col items-center justify-center"
                :class="{
                    'w-full h-full': deviceView === 'desktop',
                    'w-[768px] max-w-full h-[95%] rounded-2xl shadow-2xl border-4 border-slate-700 overflow-hidden': deviceView === 'tablet',
                    'w-[375px] max-w-full h-[90%] rounded-3xl shadow-2xl border-4 border-slate-700 overflow-hidden': deviceView === 'mobile'
                }"
            >
                <iframe 
                    id="preview-iframe"
                    src="{{ route('builder.preview', $website) }}"
                    class="w-full h-full border-0 bg-white"
                    @load="postThemeUpdate()"
                    title="Pratinjau Desain Website"
                ></iframe>
            </div>
        </main>
    </div>
</div>
