@extends('layouts.auth')
@section('title', 'Upload videos')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Upload videos</h1>
    <p class="text-sm text-gray-400 mb-6">
        Resumable, chunked upload &mdash; large files upload directly without browser limits.
        @if (! is_null($quotaRemaining))
            <br><span class="text-xs text-gray-500">Remaining quota: {{ number_format($quotaRemaining / (1024**3), 2) }} GB</span>
        @endif
    </p>
@endsection
@section('content')
    <div x-data="uploader()" class="space-y-4">
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Category (optional)</label>
            <select x-model="categoryId" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
                <option value="">— None —</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex flex-col items-center justify-center border-2 border-dashed border-white/15 rounded-xl py-10 cursor-pointer hover:border-violet-400/60 transition"
               @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false"
               @drop.prevent="onDrop($event)" :class="dragging ? 'border-violet-400 bg-violet-500/5' : ''">
            <svg class="w-10 h-10 text-violet-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 7.5 7.5 12M12 7.5V21"/></svg>
            <span class="text-sm text-gray-300">Drag &amp; drop a video, or click to browse</span>
            <span class="text-xs text-gray-500 mt-1">Allowed: {{ implode(', ', $allowedFormats) }}</span>
            <input type="file" class="hidden" accept="video/*" @change="onSelect($event)">
        </label>

        <template x-for="job in jobs" :key="job.id">
            <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="text-gray-200 truncate" x-text="job.name"></span>
                    <span class="text-gray-400" x-text="job.state"></span>
                </div>
                <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-violet-500 to-cyan-400 transition-all" :style="`width:${job.progress}%`"></div>
                </div>
                <div class="flex justify-end mt-2 gap-2">
                    <button x-show="job.state==='uploading'" @click="abort(job)" class="text-xs text-red-300 hover:text-red-200">Cancel</button>
                </div>
            </div>
        </template>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const PART_SIZE_FALLBACK = {{ $partSizeMb }} * 1024 * 1024;
        const ROUTES = {
            init: @json(route('admin.uploads.init')),
            sign: (t) => `{{ url('admin/uploads') }}/${t}/sign`,
            chunk: (t, n) => `{{ url('admin/uploads') }}/${t}/parts/${n}`,
            complete: (t) => `{{ url('admin/uploads') }}/${t}/complete`,
            abort: (t) => `{{ url('admin/uploads') }}/${t}`,
        };

        function uploader() {
            return {
                dragging: false,
                categoryId: '',
                jobs: [],
                onSelect(e) { if (e.target.files[0]) this.start(e.target.files[0]); },
                onDrop(e) { this.dragging = false; if (e.dataTransfer.files[0]) this.start(e.dataTransfer.files[0]); },

                async start(file) {
                    const job = { id: Date.now()+Math.random(), name: file.name, progress: 0, state: 'starting', token: null };
                    this.jobs.unshift(job);
                    try {
                        const initRes = await this.post(ROUTES.init, {
                            filename: file.name, size: file.size, content_type: file.type, category_id: this.categoryId || null,
                        });
                        const s = initRes.data;
                        job.token = s.token; job.state = 'uploading';
                        const partSize = s.part_size || PART_SIZE_FALLBACK;
                        const total = Math.ceil(file.size / partSize);
                        const parts = [];

                        for (let i = 0; i < total; i++) {
                            const blob = file.slice(i * partSize, Math.min(file.size, (i + 1) * partSize));
                            const partNumber = i + 1;
                            if (s.mode === 's3') {
                                const signed = await this.post(ROUTES.sign(s.token), { part_number: partNumber });
                                const put = await fetch(signed.data.url, { method: 'PUT', body: blob });
                                if (!put.ok) throw new Error('Part upload failed');
                                parts.push({ PartNumber: partNumber, ETag: (put.headers.get('ETag') || '').replaceAll('"','') });
                            } else {
                                const put = await fetch(ROUTES.chunk(s.token, partNumber), {
                                    method: 'PUT', headers: { 'X-CSRF-TOKEN': CSRF }, body: blob,
                                });
                                if (!put.ok) throw new Error('Chunk upload failed');
                            }
                            job.progress = Math.round((partNumber / total) * 100);
                        }

                        job.state = 'finalising';
                        await this.post(ROUTES.complete(s.token), { parts });
                        job.state = 'queued for processing';
                        job.progress = 100;
                    } catch (err) {
                        job.state = 'failed: ' + err.message;
                    }
                },

                async abort(job) {
                    if (!job.token) return;
                    await fetch(ROUTES.abort(job.token), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
                    job.state = 'cancelled';
                },

                async post(url, body) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(body),
                    });
                    const json = await res.json();
                    if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
                    return json;
                },
            };
        }
    </script>
@endsection
