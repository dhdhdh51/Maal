@extends('layouts.app')
@section('title', setting('site_name', 'Maal').' — '.setting('site_tagline', ''))
@section('content')
    {{-- Cinematic hero (3D on capable devices, CSS gradient fallback otherwise) --}}
    <div class="relative rounded-2xl overflow-hidden mb-8 h-56 md:h-80 border border-white/10"
         x-data="hero()" x-init="init()">
        <canvas id="hero3d" class="absolute inset-0 w-full h-full" x-show="use3d" x-cloak></canvas>
        <div class="absolute inset-0" x-show="!use3d"
             style="background:radial-gradient(120% 120% at 20% 0%,rgba(124,58,237,.45),transparent),radial-gradient(120% 120% at 100% 100%,rgba(34,211,238,.35),transparent),#0a0a0b"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        <div class="relative h-full flex flex-col justify-end p-6 md:p-8">
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">{{ $banner->title ?? setting('site_name', 'Maal') }}</h1>
            <p class="text-gray-300 mt-2 max-w-xl">{{ $banner->subtitle ?? setting('site_tagline', 'Premium streaming, unlocked by category.') }}</p>
            <div class="mt-4">
                <a href="{{ $banner->link_url ?? route('categories') }}" class="inline-block bg-violet-600 hover:bg-violet-500 px-5 py-2.5 rounded-lg text-sm font-medium text-white">
                    {{ $banner->cta_label ?? 'Browse categories' }}
                </a>
            </div>
        </div>
    </div>

    @forelse ($sections as $section)
        <section class="mb-9">
            <h2 class="text-lg font-semibold mb-3">{{ $section['title'] }}</h2>
            @if ($section['type'] === 'categories')
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($section['categories'] as $cat)
                        <a href="{{ route('category.show', $cat) }}" class="card relative aspect-[16/10] rounded-xl overflow-hidden glass flex items-end p-4">
                            @if ($cat->cover_image)<img src="{{ cdn_url($cat->cover_image) }}" class="absolute inset-0 w-full h-full object-cover opacity-60" alt="">@endif
                            <span class="relative font-semibold">{{ $cat->name }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach ($section['videos'] as $video)
                        @include('partials.video-card', ['video' => $video])
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <div class="text-center py-20">
            <h1 class="text-2xl font-bold mb-2">Welcome to {{ setting('site_name', 'Maal') }}</h1>
            <p class="text-gray-400">Content is on its way. Check back soon.</p>
            <a href="{{ route('categories') }}" class="inline-block mt-4 bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">Browse categories</a>
        </div>
    @endforelse
@endsection

@push('scripts')
<script>
    function hero() {
        return {
            use3d: false,
            init() {
                // Low-end / reduced-motion detection -> CSS fallback.
                const mem = navigator.deviceMemory || 4;
                const cores = navigator.hardwareConcurrency || 4;
                const mobile = /Mobi|Android/i.test(navigator.userAgent);
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.use3d = !reduce && mem >= 4 && cores >= 4 && !mobile;
                if (this.use3d) this.$nextTick(() => this.start());
            },
            start() {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js';
                s.onload = () => this.render();
                s.onerror = () => { this.use3d = false; };
                document.head.appendChild(s);
            },
            render() {
                const canvas = document.getElementById('hero3d');
                if (!canvas || !window.THREE) { this.use3d = false; return; }
                const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(60, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
                camera.position.z = 9;
                const resize = () => { renderer.setSize(canvas.clientWidth, canvas.clientHeight, false); camera.aspect = canvas.clientWidth / canvas.clientHeight; camera.updateProjectionMatrix(); };
                resize(); window.addEventListener('resize', resize);

                // Floating "video card" planes.
                const cards = [];
                const colors = [0x7c3aed, 0x22d3ee, 0xa78bfa, 0x6366f1];
                for (let i = 0; i < 12; i++) {
                    const geo = new THREE.PlaneGeometry(1.6, 0.9);
                    const mat = new THREE.MeshBasicMaterial({ color: colors[i % colors.length], transparent: true, opacity: 0.35 });
                    const mesh = new THREE.Mesh(geo, mat);
                    mesh.position.set((Math.random() - 0.5) * 16, (Math.random() - 0.5) * 8, (Math.random() - 0.5) * 8);
                    mesh.rotation.set(Math.random(), Math.random(), 0);
                    mesh.userData.spin = (Math.random() - 0.5) * 0.01;
                    scene.add(mesh); cards.push(mesh);
                }
                const animate = () => {
                    if (!this.use3d) return;
                    cards.forEach(c => { c.rotation.y += c.userData.spin; c.position.y += Math.sin(Date.now() * 0.0005 + c.position.x) * 0.002; });
                    renderer.render(scene, camera);
                    requestAnimationFrame(animate);
                };
                animate();
            },
        };
    }
</script>
@endpush
