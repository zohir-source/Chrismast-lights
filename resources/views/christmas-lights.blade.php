<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Christmas Lights - Laravel</title>
<style>
    body { font-family: system-ui, sans-serif; padding: 20px; background: #0b1220; color: #eee; }
    .controls { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; align-items: center; }
    .control { background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px; }
    .lights-area { display: flex; flex-direction: column; gap: 12px; margin-top: 18px; }
    .row { display:flex; gap: 12px; justify-content: center; }
    .circle {
        border-radius: 50%;
        background: #fff;
        transition: transform 180ms ease, opacity 180ms ease, box-shadow 180ms ease;
        box-shadow: 0 0 6px rgba(0,0,0,0.5) inset;
        opacity: 0.3;
        display: inline-block;
    }
    .circle.bright {
        transform: scale(1.25);
        box-shadow: 0 0 18px rgba(255,255,255,0.9);
    }
    .palette { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
    label small { display:block; color:#bbb; font-size:12px; }
    .per-circle-controls { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    input[type="color"] { width:40px; height:34px; padding:0; border: none; background: transparent; outline: none;}
    .footer-note { margin-top:18px; color:#99a; font-size:13px; }
</style>
</head>
<body>
    <h1>Christmas Lights (Laravel + Blade + JS)</h1>

    <div class="controls">
        <div class="control">
            <button id="startStopBtn">Start</button>
        </div>

        <div class="control">
            <label>
                Interval (ms)
                <input id="intervalInput" type="number" min="50" value="{{ $defaults['interval'] }}" />
            </label>
        </div>

        <div class="control">
            <label>
                Rows (1-7)
                <input id="rowsInput" type="number" min="1" max="7" value="{{ $defaults['rows'] }}" />
            </label>
        </div>

        <div class="control">
            <label>
                Intensity (0.1 - 2.0)
                <input id="intensityInput" type="range" min="0.1" max="2.0" step="0.1" value="{{ $defaults['intensity'] }}" />
                <small id="intensityVal">{{ $defaults['intensity'] }}</small>
            </label>
        </div>

        <div class="control">
            <label>
                Normal opacity
                <input id="normalOpacityInput" type="range" min="0" max="1" step="0.05" value="{{ $defaults['normalOpacity'] }}" />
                <small id="normalOpacityVal">{{ $defaults['normalOpacity'] }}</small>
            </label>
        </div>

        <div class="control">
            <button id="randomizeBtn">Randomize Colors & Sizes</button>
        </div>
    </div>

    <div class="control" style="margin-bottom:12px;">
        <div>
            <strong>Per-lamp controls (7 lampu)</strong>
            <div class="per-circle-controls" id="perControls">
                {{-- JS will populate color pickers and sizes --}}
            </div>
        </div>
    </div>

    <div class="lights-area" id="lightsArea">
        {{-- rows inserted by JS --}}
    </div>

    <div class="footer-note">
        Tip: pilih warna tiap lampu, ubah ukuran, lalu tekan Start. Lampu bergerak dari kiri ke kanan, dan pendahulu kembali normal saat lampu berikutnya menyala.
    </div>

<script>
(() => {
    // initial config dari server
    const defaults = {
        rows: {{ $defaults['rows'] }},
        cols: {{ $defaults['cols'] }},
        interval: {{ $defaults['interval'] }},
        intensity: {{ $defaults['intensity'] }},
        normalOpacity: {{ $defaults['normalOpacity'] }},
        sizes: @json($defaults['sizes']),
        colors: @json($defaults['colors'])
    };

    // state
    let rows = defaults.rows;
    let cols = defaults.cols;
    let interval = defaults.interval;
    let intensity = defaults.intensity;
    let normalOpacity = defaults.normalOpacity;
    let sizes = defaults.sizes.slice();
    let colors = defaults.colors.slice();

    let timer = null;
    let currentIndex = -1;
    let running = false;

    // elements
    const lightsArea = document.getElementById('lightsArea');
    const startStopBtn = document.getElementById('startStopBtn');
    const intervalInput = document.getElementById('intervalInput');
    const rowsInput = document.getElementById('rowsInput');
    const intensityInput = document.getElementById('intensityInput');
    const intensityVal = document.getElementById('intensityVal');
    const normalOpacityInput = document.getElementById('normalOpacityInput');
    const normalOpacityVal = document.getElementById('normalOpacityVal');
    const perControls = document.getElementById('perControls');
    const randomizeBtn = document.getElementById('randomizeBtn');

    // helper
    function createRowHtml(rowIndex) {
        const row = document.createElement('div');
        row.className = 'row';
        row.dataset.row = rowIndex;
        for (let i = 0; i < cols; i++) {
            const c = document.createElement('div');
            c.className = 'circle';
            c.style.width = sizes[i] + 'px';
            c.style.height = sizes[i] + 'px';
            c.style.background = colors[i];
            c.style.opacity = normalOpacity;
            c.dataset.col = i;
            row.appendChild(c);
        }
        return row;
    }

    function render() {
        // clear
        lightsArea.innerHTML = '';
        // create rows
        for (let r = 0; r < rows; r++) {
            lightsArea.appendChild(createRowHtml(r));
        }
        renderPerControls();
    }

    function renderPerControls() {
        perControls.innerHTML = '';
        for (let i = 0; i < cols; i++) {
            const wrap = document.createElement('div');
            wrap.style.display = 'flex';
            wrap.style.flexDirection = 'column';
            wrap.style.alignItems = 'center';
            wrap.style.gap = '6px';

            const colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.value = colors[i];
            colorInput.dataset.idx = i;
            colorInput.addEventListener('input', (e) => {
                colors[i] = e.target.value;
                // update all circles col i
                document.querySelectorAll('.circle').forEach(c => {
                    if (parseInt(c.dataset.col) === i) c.style.background = colors[i];
                });
            });

            const sizeInput = document.createElement('input');
            sizeInput.type = 'number';
            sizeInput.min = '8';
            sizeInput.max = '160';
            sizeInput.value = sizes[i];
            sizeInput.style.width = '72px';
            sizeInput.title = 'Size (px)';
            sizeInput.addEventListener('input', (e) => {
                const val = parseInt(e.target.value) || 8;
                sizes[i] = val;
                document.querySelectorAll('.circle').forEach(c => {
                    if (parseInt(c.dataset.col) === i) {
                        c.style.width = val + 'px';
                        c.style.height = val + 'px';
                    }
                });
            });

            const txt = document.createElement('div');
            txt.style.fontSize = '12px';
            txt.style.color = '#cbd5e1';
            txt.textContent = 'Lampu ' + (i+1);

            wrap.appendChild(txt);
            wrap.appendChild(colorInput);
            wrap.appendChild(sizeInput);
            perControls.appendChild(wrap);
        }
    }

    function step() {
        // move index
        const prev = currentIndex;
        currentIndex = (currentIndex + 1) % cols;

        // reset prev in all rows
        if (prev >= 0) {
            document.querySelectorAll('.row').forEach(row => {
                const c = row.querySelector(`.circle[data-col="${prev}"]`);
                if (c) {
                    c.classList.remove('bright');
                    c.style.opacity = normalOpacity;
                    c.style.filter = 'none';
                    c.style.transform = 'scale(1)';
                }
            });
        }

        // brighten current index
        document.querySelectorAll('.row').forEach(row => {
            const c = row.querySelector(`.circle[data-col="${currentIndex}"]`);
            if (c) {
                c.classList.add('bright');
                // intensity: we interpret intensity as scale factor and stronger glow (box-shadow)
                const scale = Math.max(1, intensity);
                c.style.transform = 'scale(' + (1 + (scale - 1) * 0.4) + ')';
                // make opacity based on intensity (clamp)
                const op = Math.min(1, normalOpacity + (intensity * 0.6));
                c.style.opacity = op;
                // stronger glow using box-shadow set by CSS class + inline shadow
                c.style.boxShadow = `0 0 ${8 + intensity*14}px ${colors[currentIndex]}88, 0 0 4px #00000060 inset`;
            }
        });
    }

    function start() {
        if (running) return;
        running = true;
        startStopBtn.textContent = 'Stop';
        // ensure index starts at -1 so first step lights index 0
        currentIndex = -1;
        timer = setInterval(step, interval);
    }

    function stop() {
        running = false;
        startStopBtn.textContent = 'Start';
        clearInterval(timer);
        timer = null;
        // reset all circles to normal
        document.querySelectorAll('.circle').forEach(c => {
            c.classList.remove('bright');
            c.style.opacity = normalOpacity;
            c.style.transform = 'scale(1)';
            c.style.boxShadow = '0 0 6px rgba(0,0,0,0.5) inset';
        });
        currentIndex = -1;
    }

    // event bindings
    startStopBtn.addEventListener('click', () => {
        if (running) stop();
        else start();
    });

    intervalInput.addEventListener('change', (e) => {
        const v = parseInt(e.target.value) || 200;
        interval = Math.max(50, v);
        if (running) {
            clearInterval(timer);
            timer = setInterval(step, interval);
        }
    });

    rowsInput.addEventListener('change', (e) => {
        let v = parseInt(e.target.value) || defaults.rows;
        v = Math.max(1, Math.min(7, v));
        rows = v;
        render();
    });

    intensityInput.addEventListener('input', (e) => {
        intensity = parseFloat(e.target.value);
        intensityVal.textContent = intensity;
    });

    normalOpacityInput.addEventListener('input', (e) => {
        normalOpacity = parseFloat(e.target.value);
        normalOpacityVal.textContent = normalOpacity;
        // update visible circles
        document.querySelectorAll('.circle').forEach(c => {
            if (!c.classList.contains('bright')) c.style.opacity = normalOpacity;
        });
    });

    randomizeBtn.addEventListener('click', () => {
        // random colors and sizes
        for (let i=0;i<cols;i++){
            colors[i] = '#'+Math.floor(Math.random()*16777215).toString(16).padStart(6,'0');
            sizes[i] = 24 + Math.floor(Math.random()*64);
        }
        // re-render colors/sizes
        document.querySelectorAll('.circle').forEach(c => {
            const i = parseInt(c.dataset.col);
            c.style.background = colors[i];
            c.style.width = sizes[i] + 'px';
            c.style.height = sizes[i] + 'px';
        });
        renderPerControls();
    });

    // init
    intervalInput.value = interval;
    rowsInput.value = rows;
    intensityInput.value = intensity;
    intensityVal.textContent = intensity;
    normalOpacityInput.value = normalOpacity;
    normalOpacityVal.textContent = normalOpacity;

    render();

    // keyboard: space to start/stop
    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space') {
            e.preventDefault();
            if (running) stop(); else start();
        }
    });
})();
</script>

</body>
</html> 