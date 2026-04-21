(function () {
    'use strict';

    var API_KEY  = 'AIzaSyCu-TQuwlS23-r1C54L6bySbwkY_BGbsqA';
    var BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /*
     * Models tried in order until one succeeds.
     * Gemini models are free-tier on AI Studio; Imagen needs Cloud billing.
     */
    var GEMINI_MODELS = [
        'gemini-2.0-flash-preview-image-generation',
        'gemini-2.0-flash-exp'
    ];

    var selectedStyle = 'photorealistic';
    var panelOpen     = false;

    /* ── Toggle panel ───────────────────────────────────────── */
    window.toggleAIChatbot = function () {
        var panel = document.getElementById('aicb-panel');
        var btn   = document.getElementById('aicb-trigger');

        if (!panelOpen) {
            panel.style.display = 'flex';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    panel.classList.add('aicb-visible');
                });
            });
            btn.classList.add('aicb-trigger-open');
            panelOpen = true;
        } else {
            panel.classList.remove('aicb-visible');
            btn.classList.remove('aicb-trigger-open');
            panelOpen = false;
            setTimeout(function () {
                if (!panelOpen) panel.style.display = 'none';
            }, 440);
        }
    };

    /* ── Style selection ────────────────────────────────────── */
    window.selectAICBStyle = function (el) {
        document.querySelectorAll('.aicb-style-pill').forEach(function (p) {
            p.classList.remove('active');
        });
        el.classList.add('active');
        selectedStyle = el.dataset.style;
    };

    /* ── Generate image ─────────────────────────────────────── */
    window.generateAICBImage = async function () {
        var promptEl = document.getElementById('aicb-prompt');
        var prompt   = promptEl.value.trim();

        if (!prompt) {
            aicbToast('Please enter a description first', 'error');
            promptEl.focus();
            return;
        }

        aicbState('loading');

        var fullPrompt = prompt + ', ' + selectedStyle +
                         ' style, highly detailed, cinematic lighting, 4k quality';

        var src = null;

        /* Try every Gemini model variant first */
        for (var i = 0; i < GEMINI_MODELS.length; i++) {
            try {
                src = await callGemini(GEMINI_MODELS[i], fullPrompt);
                console.log('[AI Studio] Success with model:', GEMINI_MODELS[i]);
                break;
            } catch (e) {
                console.warn('[AI Studio] ' + GEMINI_MODELS[i] + ' failed:', e.message);
            }
        }

        /* Fall back to Imagen 3 if all Gemini attempts failed */
        if (!src) {
            try {
                src = await callImagen(fullPrompt);
                console.log('[AI Studio] Success with Imagen 3');
            } catch (e) {
                console.error('[AI Studio] Imagen 3 also failed:', e.message);
                aicbState('form');
                aicbToast('Generation failed: ' + e.message, 'error');
                return;
            }
        }

        /* Render */
        var img   = document.getElementById('aicb-result-img');
        img.onload  = function () { aicbState('result'); };
        img.onerror = function () {
            aicbState('form');
            aicbToast('Image failed to render. Try again.', 'error');
        };
        img.src = src;
    };

    /* ── Gemini generateContent (free tier) ─────────────────── */
    async function callGemini(modelName, prompt) {
        var url = BASE_URL + modelName + ':generateContent?key=' + API_KEY;

        var res = await fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                contents: [{
                    role : 'user',
                    parts: [{ text: 'Create a high-quality image of: ' + prompt }]
                }],
                generationConfig: {
                    responseModalities: ['TEXT', 'IMAGE'],
                    temperature       : 1
                }
            })
        });

        var data = await res.json();
        console.log('[AI Studio] ' + modelName + ' raw response:', data);

        if (!res.ok) {
            throw new Error(
                (data.error && data.error.message) ? data.error.message : 'HTTP ' + res.status
            );
        }

        /* Walk parts looking for inlineData */
        var parts = (data.candidates &&
                     data.candidates[0] &&
                     data.candidates[0].content &&
                     data.candidates[0].content.parts) || [];

        for (var i = 0; i < parts.length; i++) {
            if (parts[i].inlineData && parts[i].inlineData.data) {
                return 'data:' + (parts[i].inlineData.mimeType || 'image/jpeg') +
                       ';base64,' + parts[i].inlineData.data;
            }
        }

        throw new Error(modelName + ': response contained no image data');
    }

    /* ── Imagen 3 (requires Cloud billing) ──────────────────── */
    async function callImagen(prompt) {
        var url = BASE_URL + 'imagen-3.0-generate-002:predict?key=' + API_KEY;

        var res = await fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                instances : [{ prompt: prompt }],
                parameters: {
                    sampleCount      : 1,
                    aspectRatio      : '1:1',
                    safetyFilterLevel: 'BLOCK_SOME',
                    personGeneration : 'ALLOW_ADULT'
                }
            })
        });

        var data = await res.json();
        console.log('[AI Studio] Imagen 3 raw response:', data);

        if (!res.ok) {
            throw new Error(
                (data.error && data.error.message) ? data.error.message : 'HTTP ' + res.status
            );
        }

        var pred = data.predictions && data.predictions[0];
        if (!pred || !pred.bytesBase64Encoded) {
            throw new Error('Imagen 3: response contained no image data');
        }

        return 'data:' + (pred.mimeType || 'image/png') + ';base64,' + pred.bytesBase64Encoded;
    }

    /* ── Reset to form ──────────────────────────────────────── */
    window.resetAICBForm = function () {
        var img    = document.getElementById('aicb-result-img');
        img.src    = '';
        img.onload = img.onerror = null;
        aicbState('form');
    };

    /* ── Publish / Post ─────────────────────────────────────── */
    window.postAICBImage = function () {
        var btn      = document.getElementById('aicb-post-btn');
        var origHTML = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = '<span class="aicb-dots"><span></span><span></span><span></span></span>';

        setTimeout(function () {
            document.getElementById('aicb-prompt').value = '';
            document.getElementById('aicb-result-img').src = '';
            aicbState('form');
            toggleAIChatbot();
            aicbToast('Post published successfully! ✨', 'success');
            btn.disabled  = false;
            btn.innerHTML = origHTML;
        }, 1800);
    };

    /* ── State switcher ─────────────────────────────────────── */
    function aicbState(s) {
        var map = { form: 'aicb-form', loading: 'aicb-loading', result: 'aicb-result' };
        Object.keys(map).forEach(function (key) {
            var el = document.getElementById(map[key]);
            if (!el) return;
            el.style.display       = (key === s) ? 'flex' : 'none';
            el.style.flexDirection = 'column';
        });
    }

    /* ── Toast ──────────────────────────────────────────────── */
    function aicbToast(msg, type) {
        document.querySelectorAll('.aicb-toast').forEach(function (t) { t.remove(); });

        var toast         = document.createElement('div');
        toast.className   = 'aicb-toast aicb-toast-' + (type || 'info');
        toast.textContent = msg;
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('aicb-toast-in'); });
        });

        setTimeout(function () {
            toast.classList.remove('aicb-toast-in');
            setTimeout(function () { toast.remove(); }, 420);
        }, 5000);
    }

})();
