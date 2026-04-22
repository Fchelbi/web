(function () {
    'use strict';

    var BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    var panelOpen = false;

    /* ── Get / Set API Key from localStorage ─────────────────── */
    function getApiKey() {
        return localStorage.getItem('echocare_gemini_key') || '';
    }
    function setApiKey(key) {
        localStorage.setItem('echocare_gemini_key', key.trim());
    }

    /* ── Toggle panel ───────────────────────────────────────── */
    window.toggleAIChatbot = function () {
        var panel = document.getElementById('aicb-panel');
        var btn   = document.getElementById('aicb-trigger');

        if (!panelOpen) {
            panel.style.display = 'flex';
            // Pre-fill API key input
            var keyInput = document.getElementById('aicb-api-key');
            if (keyInput) keyInput.value = getApiKey();

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

    /* ── Generate Post ─────────────────────────────────────── */
    window.generateAICBPost = async function () {
        var promptEl = document.getElementById('aicb-prompt');
        var prompt   = promptEl.value.trim();
        var keyInput = document.getElementById('aicb-api-key');
        var apiKey   = keyInput ? keyInput.value.trim() : '';

        if (!apiKey) {
            aicbToast('Please enter your Gemini API key first', 'error');
            if (keyInput) keyInput.focus();
            return;
        }

        if (!prompt) {
            aicbToast('Please enter a topic first', 'error');
            promptEl.focus();
            return;
        }

        // Save the key for future use
        setApiKey(apiKey);
        aicbState('loading');

        try {
            var result = await callGeminiText(prompt, apiKey);

            // Save to session storage
            sessionStorage.setItem('ai_post_title', result.title);
            sessionStorage.setItem('ai_post_content', result.content);

            // Redirect to post creation page
            window.location.href = '/post/new';
        } catch (e) {
            console.error('[AI Assistant] Generation failed:', e.message);
            var errorEl = document.getElementById('aicb-error-text');
            if (errorEl) errorEl.textContent = 'Generation failed: ' + e.message;
            aicbState('result');
        }
    };

    /* ── Gemini text generation ─────────────────────────────── */
    async function callGeminiText(prompt, apiKey) {
        var url = BASE_URL + 'gemini-2.0-flash:generateContent?key=' + apiKey;

        var systemPrompt = "You are a supportive, professional AI assistant for a psychology and mental health community forum called EchoCare. Write an engaging, empathetic, and helpful forum post based on the user's prompt. Keep it structured and easy to read.";

        var res = await fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                systemInstruction: {
                    parts: [{ text: systemPrompt }]
                },
                contents: [{
                    role : 'user',
                    parts: [{ text: 'Write a forum post about: ' + prompt }]
                }],
                generationConfig: {
                    temperature: 0.7,
                    responseMimeType: 'application/json',
                    responseSchema: {
                        type: 'object',
                        properties: {
                            title: { type: 'string', description: 'A catchy, supportive title for the forum post' },
                            content: { type: 'string', description: 'The body of the post. Use paragraphs and friendly formatting.' }
                        },
                        required: ['title', 'content']
                    }
                }
            })
        });

        var data = await res.json();

        if (!res.ok) {
            throw new Error(
                (data.error && data.error.message) ? data.error.message : 'HTTP ' + res.status
            );
        }

        var textResponse = data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts && data.candidates[0].content.parts[0] && data.candidates[0].content.parts[0].text;

        if (!textResponse) {
            throw new Error('Response contained no text data');
        }

        return JSON.parse(textResponse);
    }

    /* ── Reset to form ──────────────────────────────────────── */
    window.resetAICBForm = function () {
        aicbState('form');
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

    /* ── Auto-fill logic for /post/new ──────────────────────── */
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.pathname.includes('/post/new') || window.location.pathname.includes('/post/new/')) {
            var savedTitle = sessionStorage.getItem('ai_post_title');
            var savedContent = sessionStorage.getItem('ai_post_content');

            if (savedTitle && savedContent) {
                var titleInput = document.getElementById('post_title');
                var contentInput = document.getElementById('post_content');

                if (titleInput && contentInput) {
                    titleInput.value = savedTitle;
                    contentInput.value = savedContent;

                    // Clear so it doesn't auto-fill again on refresh
                    sessionStorage.removeItem('ai_post_title');
                    sessionStorage.removeItem('ai_post_content');

                    // Show a notification
                    setTimeout(function() {
                        aicbToast('AI Post drafted successfully! Review and publish.', 'success');
                    }, 500);
                }
            }
        }
    });

    /* ============================================================
       TEXT-TO-SPEECH (TTS) — Global utility
       ============================================================ */
    var currentUtterance = null;
    var currentTTSBtn    = null;

    window.ttsSpeak = function (text, btn) {
        // If already speaking — stop
        if (window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
            if (currentTTSBtn) currentTTSBtn.classList.remove('tts-playing');

            // If same button clicked, just stop
            if (currentTTSBtn === btn) {
                currentTTSBtn = null;
                return;
            }
        }

        if (!text || !text.trim()) return;

        var utterance = new SpeechSynthesisUtterance(text.trim());
        utterance.lang  = 'en-US';
        utterance.rate  = 0.95;
        utterance.pitch = 1.0;

        // Try to pick a nice voice
        var voices = window.speechSynthesis.getVoices();
        var preferred = voices.find(function(v) {
            return v.lang.startsWith('en') && v.name.toLowerCase().includes('google');
        }) || voices.find(function(v) {
            return v.lang.startsWith('en');
        });
        if (preferred) utterance.voice = preferred;

        btn.classList.add('tts-playing');
        currentTTSBtn = btn;

        utterance.onend = function () {
            btn.classList.remove('tts-playing');
            currentTTSBtn = null;
        };
        utterance.onerror = function () {
            btn.classList.remove('tts-playing');
            currentTTSBtn = null;
        };

        window.speechSynthesis.speak(utterance);
    };

    // Pre-load voices (some browsers need this)
    if (window.speechSynthesis) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = function() {
            window.speechSynthesis.getVoices();
        };
    }

})();
