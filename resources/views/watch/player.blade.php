<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $video->title }} — {{ setting('site_name', 'Maal') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <style>
        body{margin:0;background:#000;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .stage{position:relative;width:100%;max-width:1100px;margin:0 auto;aspect-ratio:16/9;background:#000}
        video{width:100%;height:100%;background:#000}
        .wm{position:absolute;pointer-events:none;user-select:none;font-weight:600;white-space:nowrap;transition:all 1s ease;text-shadow:0 1px 3px rgba(0,0,0,.8);z-index:20}
        .overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.82);z-index:30;text-align:center;padding:1.5rem}
        .btn{background:#7c3aed;padding:.6rem 1.2rem;border-radius:.6rem;font-weight:600;color:#fff;border:none;cursor:pointer}
        .btn:hover{background:#6d28d9}
        .btn-ghost{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15)}
        select{background:#111;color:#e5e7eb;border:1px solid #333;border-radius:.4rem;padding:.25rem .5rem}
    </style>
</head>
<body x-data="player()" x-init="init()" class="min-h-screen">
    <header class="flex items-center justify-between px-4 py-3 border-b border-white/10">
        <a href="{{ url('/') }}" class="font-extrabold tracking-wide text-lg" style="background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent">{{ setting('site_name', 'Maal') }}</a>
        <span class="text-sm text-gray-400 truncate max-w-[60%]">{{ $video->title }}</span>
    </header>

    <main class="p-4">
        <div class="stage rounded-xl overflow-hidden" id="stage">
            <video id="video" playsinline controls controlsList="nodownload noremoteplayback" disablepictureinpicture="false" oncontextmenu="return false"></video>

            {{-- Dynamic moving watermark --}}
            <template x-if="wm">
                <div class="wm" :style="wmStyle" x-text="wm.text"></div>
            </template>

            {{-- Processing fallback --}}
            <template x-if="data.mode === 'processing'">
                <div class="overlay flex-col gap-3">
                    <div class="text-lg font-semibold">Your video is being prepared</div>
                    <div class="w-64 h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-violet-500 to-cyan-400" :style="`width:${data.progress||0}%`"></div>
                    </div>
                    <div class="text-sm text-gray-400" x-text="stageLabel(data.status)"></div>
                    <button class="btn mt-2" @click="reloadManifest()">Retry</button>
                </div>
            </template>

            {{-- Locked / unavailable --}}
            <template x-if="data.mode === 'locked' || data.mode === 'unavailable'">
                <div class="overlay flex-col gap-3">
                    <svg class="w-10 h-10 text-violet-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>
                    <div class="text-lg font-semibold" x-text="data.reason || 'This video is locked.'"></div>
                    <template x-if="data.unlock && data.unlock.checkout_url">
                        <a class="btn" :href="data.unlock.checkout_url">Unlock to watch</a>
                    </template>
                    <a class="text-sm text-gray-400 hover:text-gray-200" href="{{ url('/') }}">Back</a>
                </div>
            </template>

            {{-- Preview ended -> unlock modal --}}
            <template x-if="previewEnded">
                <div class="overlay flex-col gap-3">
                    <div class="text-lg font-semibold">Preview ended</div>
                    <p class="text-sm text-gray-400 max-w-sm">Unlock <span x-text="data.unlock?.category?.name"></span> to keep watching in full quality.</p>
                    <template x-if="data.unlock && data.unlock.checkout_url">
                        <a class="btn" :href="data.unlock.checkout_url">Unlock now</a>
                    </template>
                    <button class="btn btn-ghost" @click="restartPreview()">Replay preview</button>
                </div>
            </template>

            {{-- Playback error --}}
            <template x-if="error">
                <div class="overlay flex-col gap-3">
                    <div class="text-lg font-semibold">Playback problem</div>
                    <p class="text-sm text-gray-400">We couldn't play this video right now.</p>
                    <div class="flex gap-2">
                        <button class="btn" @click="retry()">Retry</button>
                        <a class="btn btn-ghost" href="{{ url('/report?video='.$video->id) }}">Report issue</a>
                        <a class="btn btn-ghost" href="{{ url('/') }}">Back</a>
                    </div>
                </div>
            </template>

            {{-- Concurrent stream block --}}
            <template x-if="concurrent">
                <div class="overlay flex-col gap-3">
                    <div class="text-lg font-semibold">Streaming on another device</div>
                    <p class="text-sm text-gray-400 max-w-sm">Your plan allows a limited number of simultaneous streams. Stop playback elsewhere and retry.</p>
                    <button class="btn" @click="concurrent=false; retry()">Retry here</button>
                </div>
            </template>
        </div>

        {{-- Controls bar --}}
        <div class="max-w-[1100px] mx-auto mt-3 flex flex-wrap items-center gap-3 text-sm" x-show="playable">
            <div class="flex items-center gap-2">
                <span class="text-gray-400">Quality</span>
                <select x-model="quality" @change="setQuality()">
                    <template x-for="q in data.qualities" :key="q"><option :value="q" x-text="q"></option></template>
                </select>
            </div>
            <button class="btn-ghost btn" @click="togglePip()" x-show="pipSupported">Picture-in-picture</button>
            <label class="flex items-center gap-2 text-gray-400 ml-auto">
                <input type="checkbox" x-model="autoplayNext"> Autoplay next
            </label>
        </div>

        <div class="max-w-[1100px] mx-auto mt-4">
            <h1 class="text-xl font-semibold">{{ $video->title }}</h1>
            @if ($video->description)
                <p class="text-sm text-gray-400 mt-1">{{ $video->description }}</p>
            @endif
            <template x-if="data.mode === 'preview'">
                <p class="text-xs text-amber-300 mt-2">You're watching a <span x-text="data.preview_seconds"></span>s preview.</p>
            </template>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const HEARTBEAT_URL = @json(route('watch.heartbeat', $video));
        const STOPPED_URL = @json(route('watch.stopped', $video));
        const MANIFEST_URL = @json(route('watch.manifest', $video));

        function player() {
            return {
                data: @json($payload),
                hls: null,
                video: null,
                wm: null,
                wmStyle: 'top:8%;left:6%;',
                quality: 'auto',
                autoplayNext: {{ setting('autoplay_next', true) ? 'true' : 'false' }},
                error: false,
                concurrent: false,
                previewEnded: false,
                pipSupported: document.pictureInPictureEnabled || false,
                get playable() { return this.data.mode === 'full' || this.data.mode === 'preview'; },

                init() {
                    this.wm = this.data.watermark || null;
                    if (this.wm) this.startWatermark();
                    if (this.playable) this.$nextTick(() => this.setup());
                    this.startHeartbeat();
                    window.addEventListener('beforeunload', () => navigator.sendBeacon && this.sendStopped());
                },

                setup() {
                    this.video = document.getElementById('video');
                    const url = this.data.master_url;
                    if (Hls.isSupported()) {
                        this.hls = new Hls({ maxBufferLength: 30 });
                        this.hls.loadSource(url);
                        this.hls.attachMedia(this.video);
                        this.hls.on(Hls.Events.ERROR, (e, d) => { if (d.fatal) this.onFatal(d); });
                    } else if (this.video.canPlayType('application/vnd.apple.mpegurl')) {
                        this.video.src = url; // Safari native HLS
                    }
                    if (this.data.mode === 'preview') this.enforcePreview();
                },

                onFatal(d) {
                    // Try recovery once, then surface a graceful error.
                    if (this.hls && d.type === Hls.ErrorTypes.NETWORK_ERROR) { this.hls.startLoad(); return; }
                    if (this.hls && d.type === Hls.ErrorTypes.MEDIA_ERROR) { this.hls.recoverMediaError(); return; }
                    this.error = true;
                },

                setQuality() {
                    if (!this.hls) return;
                    if (this.quality === 'auto') { this.hls.currentLevel = -1; return; }
                    const idx = this.hls.levels.findIndex(l => `${l.height}p` === this.quality);
                    // Fallback to nearest available level if exact not found.
                    this.hls.currentLevel = idx >= 0 ? idx : -1;
                },

                enforcePreview() {
                    const limit = this.data.preview_seconds || 20;
                    this.video.addEventListener('timeupdate', () => {
                        if (this.video.currentTime >= limit) { this.video.pause(); this.previewEnded = true; }
                    });
                    // Block seeking beyond the allowed preview window.
                    this.video.addEventListener('seeking', () => {
                        if (this.video.currentTime > limit) this.video.currentTime = limit - 0.5;
                    });
                },

                restartPreview() { this.previewEnded = false; this.video.currentTime = 0; this.video.play(); },

                startWatermark() {
                    const move = () => {
                        const top = Math.random() * 80 + 5, left = Math.random() * 70 + 5;
                        this.wmStyle = `top:${top}%;left:${left}%;opacity:${this.wm.opacity};font-size:${this.wm.font_size}px;`;
                    };
                    move();
                    setInterval(move, (this.wm.move_interval || 8) * 1000);
                },

                async startHeartbeat() {
                    if (!this.playable) return;
                    const beat = async () => {
                        try {
                            const res = await fetch(HEARTBEAT_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
                            if (res.status === 409) { this.concurrent = true; this.video && this.video.pause(); }
                        } catch (e) {}
                    };
                    beat();
                    setInterval(beat, 20000);
                },

                sendStopped() {
                    try { navigator.sendBeacon(STOPPED_URL, new Blob([], { type: 'text/plain' })); } catch (e) {}
                },

                async reloadManifest() {
                    const res = await fetch(MANIFEST_URL, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    this.data = json.data;
                    this.error = false;
                    if (this.playable) this.$nextTick(() => this.setup());
                },

                retry() { this.error = false; if (this.hls) { this.hls.destroy(); } this.setup(); },

                async togglePip() {
                    try {
                        if (document.pictureInPictureElement) await document.exitPictureInPicture();
                        else await this.video.requestPictureInPicture();
                    } catch (e) {}
                },

                stageLabel(status) {
                    return ({uploading:'Uploading',uploaded:'Queued',processing:'Analyzing',generating_preview:'Generating preview',encoding:'Encoding qualities',failed:'Processing failed'})[status] || 'Preparing';
                },
            };
        }
    </script>
</body>
</html>
